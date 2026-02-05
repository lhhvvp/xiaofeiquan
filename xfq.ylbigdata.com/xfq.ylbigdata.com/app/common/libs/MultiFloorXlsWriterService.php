<?php
 
namespace app\common\libs;
 
use Vtiful\Kernel\Excel;
 
class MultiFloorXlsWriterService
{
    // 默认宽度
    private $defaultWidth = 16;
    // 默认导出格式
    private $exportType = '.xlsx';
    // 表头最大层级
    private $maxHeight = 1;
    // 文件名
    private $fileName = null;
 
    private $xlsObj;
    private $fileObject;
    private $format;
 
    /**
     * MultiFloorXlsWriterService constructor.
     * @throws \App\Exceptions\ApiException
     */
    public function __construct()
    {
        // 文件默认输出地址
        $path = public_path().'/excel';
        $config = [
            'path' => $path
        ];
 
        $this->xlsObj = (new \Vtiful\Kernel\Excel($config));
    }
 
    /**
     * 设置文件名
     * @param string $fileName
     * @param string $sheetName
     * @author LWW
     */
    public function setFileName(string $fileName = '', string $sheetName = 'Sheet1')
    {
        $fileName = empty($fileName) ? (string)time() : $fileName;
        $fileName .= $this->exportType;
 
        $this->fileName = $fileName;
        $this->fileObject = $this->xlsObj->fileName($fileName, $sheetName);
        $this->format = (new \Vtiful\Kernel\Format($this->fileObject->getHandle()));
    }
 
    /**
     * 设置表头
     * @param array $header
     * @param bool $filter
     * @throws \Exception
     * @author LWW
     */
    public function setHeader(array $header, bool $filter = false)
    {
        if (empty($header)) {
            throw new \Exception('表头数据不能为空');
        }
 
        if (is_null($this->fileName)) {
            self::setFileName(time());
        }
 
        // 获取单元格合并需要的信息
        $colManage = self::setHeaderNeedManage($header);
 
        // 完善单元格合并信息
        $colManage = self::completeColMerge($colManage);
 
        // 合并单元格
        self::queryMergeColumn($colManage, $filter);
 
    }
 
    /**
     * 填充文件数据
     * @param array $data
     * @author LWW
     */
    public function setData(array $data)
    {
        foreach ($data as $row => $datum) {
            foreach ($datum as $column => $value) {
                $this->fileObject->insertText($row + $this->maxHeight, $column, $value);
            }
        }
    }
 
    /**
     * 添加Sheet
     * @param string $sheetName
     * @author LWW
     */
    public function addSheet(string $sheetName)
    {
        $this->fileObject->addSheet($sheetName);
    }
 
    /**
     * 保存文件至服务器
     * @return mixed
     * @author LWW
     */
    public function output()
    {
        return $this->fileObject->output();
    }
 
    /**
     * 输出到浏览器
     * @param string $filePath
     * @throws \Exception
     * @author LWW
     */
    public function excelDownload(string $filePath)
    {
        $fileName = $this->fileName;
        $userBrowser = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/MSIE/i', $userBrowser)) {
            $fileName = urlencode($fileName);
        } else {
            $fileName = iconv('UTF-8', 'GBK//IGNORE', $fileName);
        }
 
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
 
        if (ob_get_contents()) {
            ob_clean();
        }
 
        flush();
 
        if (copy($filePath, 'php://output') === false) {
            throw new \Exception($filePath . '地址出问题了');
        }
 
        // 删除本地文件
        @unlink($filePath);
 
        exit();
    }
 
    /**
     * 组装单元格合并需要的信息
     * @param array $header
     * @param int $col
     * @param int $cursor
     * @param array $colManage
     * @param null $parent
     * @param array $parentList
     * @return array
     * @throws \Exception
     * @author LWW
     */
    private function setHeaderNeedManage(array $header,int $col = 1,int &$cursor = 0,array &$colManage = [], $parent = null,array $parentList = [])
    {
        foreach ($header as $head) {
            if (empty($head['title'])) {
                throw new \Exception('表头数据格式有误');
            }
 
            if (is_null($parent)) {
                // 循环初始化
                $parentList = [];
                $col = 1;
            } else {
                // 递归进入，高度和父级集合通过相同父级条件从已有数组中获取，避免递归增加与实际数据不符
                foreach ($colManage as $value) {
                    if ($value['parent'] == $parent) {
                        $parentList = $value['parentList'];
                        $col = $value['height'];
                        break;
                    }
                }
            }
 
            // 单元格标识
            $column = $this->getColumn($cursor) . $col;
 
            // 组装单元格需要的各种信息
            $colManage[$column] = [
                'title'      => $head['title'],      // 标题
                'cursor'     => $cursor,             // 游标
                'cursorEnd'  => $cursor,             // 结束游标
                'height'     => $col,                // 高度
                'width'      => $this->defaultWidth, // 宽度
                'mergeStart' => $column,             // 合并开始标识
                'hMergeEnd'  => $column,             // 横向合并结束标识
                'zMergeEnd'  => $column,             // 纵向合并结束标识
                'parent'     => $parent,             // 父级标识
                'parentList' => $parentList,         // 父级集合
            ];
 
            if (isset($head['children']) && !empty($head['children']) && is_array($head['children'])) {
                // 有下级，高度加一
                $col += 1;
                // 当前标识加入父级集合
                $parentList[] = $column;
 
                $this->setHeaderNeedManage($head['children'], $col, $cursor, $colManage, $column, $parentList);
            } else {
                // 没有下级，游标加一
                $cursor += 1;
            }
        }
 
        return $colManage;
    }
 
    /**
     * 完善单元格合并信息
     * @param array $colManage
     * @return mixed
     * @author LWW
     */
    private function completeColMerge(array $colManage)
    {
        $this->maxHeight = max(array_column($colManage, 'height'));
        $parentManage = array_column($colManage, 'parent');
 
        foreach ($colManage as $index => $value) {
            // 设置横向合并结束范围：存在父级集合，把所有父级的横向合并结束范围设置为当前单元格
            if (!is_null($value['parent']) && !empty($value['parentList'])) {
                foreach ($value['parentList'] as $parent) {
                    $colManage[$parent]['hMergeEnd'] = self::getColumn($value['cursor']) . $colManage[$parent]['height'];
                    $colManage[$parent]['cursorEnd'] = $value['cursor'];
                }
            }
 
            // 设置纵向合并结束范围：当前高度小于最大高度 且 不存在以当前单元格标识作为父级的项
            $checkChildren = array_search($index, $parentManage);
            if ($value['height'] < $this->maxHeight && !$checkChildren) {
                $colManage[$index]['zMergeEnd'] = self::getColumn($value['cursor']) . $this->maxHeight;
            }
        }
 
        return $colManage;
    }
 
    /**
     * 合并单元格
     * @param array $colManage
     * @param bool $filter
     * @author LWW
     */
    private function queryMergeColumn(array $colManage,bool $filter)
    {
        foreach ($colManage as $value) {
            $this->fileObject->mergeCells("{$value['mergeStart']}:{$value['zMergeEnd']}", $value['title']);
            $this->fileObject->mergeCells("{$value['mergeStart']}:{$value['hMergeEnd']}", $value['title']);
 
            // 设置单元格需要的宽度
            if ($value['cursor'] != $value['cursorEnd']) {
                $value['width'] = ($value['cursorEnd'] - $value['cursor'] + 1) * $this->defaultWidth;
            }
 
            // 设置列单元格样式
            $toColumnStart = self::getColumn($value['cursor']);
            $toColumnEnd = self::getColumn($value['cursorEnd']);
            $this->fileObject->setColumn("{$toColumnStart}:{$toColumnEnd}", $value['width']);
        }
 
        // 是否开启过滤选项
        if ($filter) {
            // 获取最后的单元格标识
            $filterEndColumn = self::getColumn(end($colManage)['cursorEnd']) . $this->maxHeight;
            $this->fileObject->autoFilter("A1:{$filterEndColumn}");
        }
    }
 
    /**
     * 获取单元格列标识
     * @param int $num
     * @return string
     * @author LWW
     */
    private function getColumn(int $num)
    {
        return Excel::stringFromColumnIndex($num);
    }
}
<?php
namespace app\common\libs;
class Maxfile
{
    private $header;
    private $filename;
    private $pageSize;  //每页大小
    private $totalCount; //总数据量
    private $currentPage; //当前页数
    private $data;

    function __construct($header, $filename, $pageSize = 10000)
    {
        $this->header = $header;
        $this->filename = $filename;
        $this->pageSize = $pageSize;
    }

    // 设置总数据量
    function setTotalCount($totalCount)
    {
        $this->totalCount = $totalCount;
    }

    //设置当前页数
    function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;
    }

    // 设置数据
    function setData($data)
    {
        $this->data = $data;
    }

    //获取数据
    function getData()
    {
        $offset = ($this->currentPage - 1) * $this->pageSize;
        $limit = $this->pageSize;
        $data = array_slice($this->data, $offset, $limit);

        return $data;
    }

    //获取总页数
    function getTotalPage()
    {
        $totalPage = ceil($this->totalCount / $this->pageSize);
        return $totalPage;
    }

    function export()
    {
        //定义超时时间和内存限制
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        //打开输出缓冲区
        ob_start();

        //设置响应头
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $this->filename . '.csv";');

        //打开文件资源
        $fp = fopen('php://output', 'w');

        //写入表头
        fputcsv($fp, $this->header);

        //写入数据
        for ($i = 1; $i <= $this->getTotalPage(); $i++) {
            $this->setCurrentPage($i);
            $pageData = $this->getData();
            foreach ($pageData as $row) {
                print_r($row);die;
                fputcsv($fp, $row);
            }
            flush(); //释放输出缓冲区
            ob_flush();
        }

        //关闭文件资源
        fclose($fp);

        //获取并清空输出缓冲区中的数据
        $output = ob_get_clean();

        //输出结果
        echo $output;
    }
}

//使用示例
/*$data = array();
$header = array('id', 'name', 'age');
$filename = 'user';

// 模拟数据
for ($i = 1; $i <= 10000; $i++) {
    $data[] = [$i, 'name_' . $i, 18];
}

$export = new ExportLargeData($header, $filename);
$export->setTotalCount(count($data));
$export->setData($data);
$export->export();
*/
?>
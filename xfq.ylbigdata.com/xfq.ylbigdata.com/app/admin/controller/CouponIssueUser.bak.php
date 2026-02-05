<?php
/**
 * 消费券领取记录控制器
 * @author slomoo <1103398780@qq.com> 2022/07/28
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;
use ip2region\XdbSearcher;
use think\facade\Db;
use app\common\libs\MultiFloorXlsWriterService;

class CouponIssueUser extends Base
{
    // 验证器
    protected $validate = 'CouponIssueUser';

    // 当前主表
    protected $tableName = 'coupon_issue_user';

    // 当前主模型
    protected $modelName = 'CouponIssueUser';

    // 列表
    public function index(){
        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);
        // 获取列表数据
        $columns = MakeBuilder::getListColumns($this->tableName);
        // 插入用户信息字段到第1个元素
        // array_splice($columns, 1, 0, [['users.city','城市','text','',[],'','false']]);
        $add_columns = [
            ['users.mobile','手机号','text','',[],'','false'],
            //['users.idcard','身份证号','text','',[],'','false'],
        ];
        $columns = array_merge($columns,$add_columns);
        // 获取搜索数据
        $search = MakeBuilder::getListSearch($this->tableName);
        // 获取当前模块信息
        $model = '\app\common\model\\' . $this->modelName;
        $module = \app\common\model\Module::where('table_name', $this->tableName)->find();
        // 搜索
        if (Request::param('getList') == 1) {
            $where = MakeBuilder::getListWhere($this->tableName);
            $orderByColumn = Request::param('orderByColumn') ?? $pk;
            $isAsc = Request::param('isAsc') ?? 'desc';
            // 2023-03-10 需要在列表根据IP展示领取位置
            $list = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            
            $dbPath = './ip2region/ip2region.xdb';
    $searcher = XdbSearcher::newWithFileOnly($dbPath);


$sTime = XdbSearcher::now();
foreach ($list['data'] as $key => $value) {
    if($value['ips']){
        $aa = $searcher->search($value['ips']);
        $list['data'][$key]['ips'] = str_replace('|0', '', $aa);
    }
}




            /*$vIndex = XdbSearcher::loadVectorIndexFromFile($dbPath);

            if ($vIndex === null) {
                printf("failed to load vector index from '%s'\n", $dbPath);
                return;
            }

            // 2、使用全局的 vIndex 创建带 VectorIndex 缓存的查询对象。
            try {
                $searcher = XdbSearcher::newWithVectorIndex($dbPath, $vIndex);
            } catch (Exception $e) {
                printf("failed to create vectorIndex cached searcher with '%s': %s\n", $dbPath, $e);
                return;
            }

            // 3、查询
            $sTime = XdbSearcher::now();
            foreach ($list['data'] as $key => $value) {
                if($value['ips']){
                    $aa = $searcher->search($value['ips']);
                    $list['data'][$key]['ips'] = str_replace('|0', '', $aa);
                }
            }*/




            //try {
                // 加载整个 xdb 到内存。
                /*$cBuff = XdbSearcher::loadContentFromFile($xdb);
                if (null === $cBuff) {
                    throw new \RuntimeException("failed to load content buffer from '$xdb'");
                }
                // 使用全局的 cBuff 创建带完全基于内存的查询对象。
                $searcher = XdbSearcher::newWithBuffer($cBuff);
                foreach ($list['data'] as $key => $value) {
                    if($value['ips']){
                        $aa = $searcher->search($value['ips']);
                        $list['data'][$key]['ips'] = str_replace('|0', '', $aa);
                    }
                }*/
            //} catch (\Exception $e) {
            //    $this->apiError("区域获取错误".$e->getMessage());
            //}
            return $list;
        }
        // 消费券分类查询
        $CouponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['class_list' => $CouponClass]);

        // 构建页面
        return TableBuilder::getInstance()
            ->setUniqueId($pk)                              // 设置主键
            ->addColumns($columns)                         // 添加列表字段数据
            ->setSearch($search)                            // 添加头部搜索
            ->addColumn('right_button', '操作', 'btn')      // 启用右侧操作列
            ->addRightButtons($module->right_button)        // 设置右侧操作列
            ->addTopButtons($module->top_button)            // 设置顶部按钮组
            ->addRightButton('info', [                      // 添加额外按钮
                'title' => '查看',
                'icon'  => 'fa fa-search',
                'class' => 'btn btn-primary btn-xs',
                'href'  => url('see', ['parentId' => '__id__'])
            ])
            ->fetch('coupon_issue_user/index');
    }

    // 查看详情
    public function see($parentId)
    {
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';

        $map = [];

        $map[] = ['id','=',$parentId];
        $model  = '\app\common\model\\' . $this->modelName;
        $detail = $model::where($map)->with(['users','couponClass','couponIssue'])->find();
        View::assign(['detail' => $detail]);
        return View::fetch();
    }

    // 团体领取记录
    public function tour(){
        // 获取主键
        $pk = MakeBuilder::getPrimarykey('tour_issue_user');
        // 获取列表数据
        $columns = MakeBuilder::getListColumns('tour_issue_user');
        // 插入用户信息字段到第1个元素
        // array_splice($columns, 1, 0, [['users.city','城市','text','',[],'','false']]);
        $add_columns = [
            ['users.mobile','手机号','text','',[],'','false'],
            //['users.idcard','身份证号','text','',[],'','false'],
        ];
        $columns = array_merge($columns,$add_columns);
        // 获取搜索数据
        $search = MakeBuilder::getListSearch('tour_issue_user');
        // 获取当前模块信息
        $model = '\app\common\model\\' . 'TourIssueUser';
        $module = \app\common\model\Module::where('table_name', 'tour_issue_user')->find();
        // 搜索
        if (Request::param('getList') == 1) {
            $where = MakeBuilder::getListWhere('tour_issue_user');
            if(Request::param('issue_coupon_class_id')){
                $where[] = ['issue_coupon_class_id','=',Request::param('issue_coupon_class_id')];
            }
            $orderByColumn = Request::param('orderByColumn') ?? $pk;
            $isAsc = Request::param('isAsc') ?? 'desc';
            $list  = $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
            foreach ($list['data'] as $key => $value) {
                $list['data'][$key]['time_use'] = $value['time_use'] > 0 ? date("Y-m-d H:i:s",$value['time_use']) : 0;
            }
            return $list;
        }
        // 消费券分类查询
        $CouponClass = \app\common\model\CouponClass::field('id, title')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['class_list' => $CouponClass]);
        // 构建页面
        return TableBuilder::getInstance()
            ->setUniqueId($pk)                              // 设置主键
            ->addColumns($columns)                         // 添加列表字段数据
            ->setSearch($search)                            // 添加头部搜索
            ->addColumn('right_button', '操作', 'btn')      // 启用右侧操作列
            ->addRightButtons($module->right_button)        // 设置右侧操作列
            ->addTopButtons($module->top_button)            // 设置顶部按钮组
            ->addRightButton('info', [                      // 添加额外按钮
                'title' => '查看',
                'icon'  => 'fa fa-search',
                'class' => 'btn btn-primary btn-xs',
                'href'  => url('seee', ['parentId' => '__id__'])
            ])
            ->fetch('coupon_issue_user/tour');//
    }

    // 查看详情
    public function seee($parentId)
    {
        $orderByColumn = $param['orderByColumn'] ?? 'id';
        $isAsc         = $param['isAsc'] ?? 'desc';

        $map = [];

        $map[] = ['id','=',$parentId];
        $model  = '\app\common\model\\' . 'TourIssueUser';
        $detail = $model::where($map)->with(['users','tour'])->find();
        View::assign(['detail' => $detail]);
        return View::fetch();
    }

    // 导出
    public function exportCvs()
    {

        $tableNam = 'coupon_issue_user'; $moduleName = 'CouponIssueUser';
        ob_end_clean();
        // 获取主键
        $pk = \app\common\facade\MakeBuilder::getPrimarykey($tableNam);
        // 搜索
        $where         = \app\common\facade\MakeBuilder::getListWhere($tableNam);
        $orderByColumn = \think\facade\Request::param('orderByColumn') ?? $pk;
        $isAsc         = \think\facade\Request::param('isAsc') ?? 'desc';
        $model         = '\app\common\model\\' . $moduleName;

        $limit = 5000;//每次只从数据库取5000条以防变量缓存太大
        // buffer计数器
        $cnt = 0;
        $xlsTitle = ['编号','分类','名称','面额','领取人','手机号','领取时间'];

        /******************** 调整位置开始 ***************************/
        // 计算总数
        $ids = Request::param('id');
        if(isset($ids)){
            $idsArr = explode(',',$ids);
            $sqlCount = count($idsArr);
            array_push($where,['id','in',$idsArr]);
        }else{
            $sqlCount = $model::count();
        }
        /******************** 调整位置结束 ***************************/
        //$fileName = iconv('utf-8', 'gb2312', 'students');//文件名称
        $moduleName = \app\common\model\Module::where('table_name', $tableNam)->value('module_name');
        $moduleName = $moduleName . date('_YmdHis');// 文件名称可根据自己情况设定
        $zipname = 'zip-' . $moduleName . ".zip";
        // 输出Excel文件头，可把user.csv换成你要的文件名
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $zipname . '"');
        header('Cache-Control: max-age=0');
        $fileNameArr = array();
        // 逐行取出数据，不浪费内存
        for ($i = 0; $i < ceil($sqlCount / $limit); $i++) {
            $fp = fopen($moduleName . '_' . ($i+1) . '.csv', 'w'); //生成临时文件 
            // chmod('attack_ip_info_' . $i . '.csv',777);//修改可执行权限 
            $fileNameArr[] = $moduleName . '_' . ($i+1) . '.csv'; // 将数据通过fputcsv写到文件句柄 
            fputcsv($fp, $xlsTitle);
            
            $start = $i * $limit;
            /******************** 调整位置开始 ***************************/
            // 获取要导出的数据
            $dataArr = $model::getListExport($where, $limit, [$orderByColumn => $isAsc],$start); // 每次查询limit条数据
            /******************** 调整位置结束 ***************************/
            foreach ($dataArr as $key => $val) {
                $tempVal['uuno'] = $val['couponIssue']['uuno'];
                $tempVal['title'] = $val['couponClass']['title'];
                $tempVal['coupon_title'] = $val['coupon_title'];
                $tempVal['coupon_price'] = $val['coupon_price'];
                $tempVal['username'] = $val['users']['name'];
                $tempVal['mobile'] = $val['users']['mobile'];
                $tempVal['create_time'] = $val['create_time'];
                $cnt++;
                if ($limit == $cnt) {
                    // 刷新一下输出buffer，防止由于数据过多造成问题
                    ob_flush();
                    flush();
                    $cnt = 0;
                }
                fputcsv($fp, $tempVal);
            }
            fclose($fp); // 每生成一个文件关闭
        }
        // 进行多个文件压缩
        $zip = new \ZipArchive();
        $zip->open($zipname, $zip::CREATE); // 打开压缩包
        foreach ($fileNameArr as $file) {
            $zip->addFile($file, basename($file)); // 向压缩包中添加文件
        }
        $zip->close();  // 关闭压缩包
        foreach ($fileNameArr as $file) {
            unlink($file); // 删除csv临时文件
        }
        
        // 输出压缩文件提供下载
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header("Content-Type: application/zip"); // zip格式
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . filesize($zipname));
        @readfile($zipname); // 输出文件
        unlink($zipname); // 删除压缩包临时文件
    }

    // 大文件导出
    public function exportLargeFile()
    {
        ini_set("memory_limit","-1");
        ini_set('max_execution_time','300');
        ob_end_clean();
        $tableNam = 'coupon_issue_user'; $moduleName = 'CouponIssueUser';
        $model         = '\app\common\model\\' . $moduleName;
        // 设置导出文件名
        $filename = 'maxfile.csv';
        // 设置每次读取的字节数
        $chunkSize = 1024 * 1024; // 每次读取1MB
        // 打开文件
        $handle = fopen($filename, 'w');
        // 写入表头
        fputcsv($handle, ['编号','分类','名称','面额','领取人','手机号','领取时间']);

        // 查询数据总数
        $total = $model::count();
        // 每页数据量
        $pageSize = 10000; // 每页10000条数据
        // 总页数
        $pageCount = ceil($total / $pageSize);
        // 分页查询数据
        for ($page = 1; $page <= $pageCount; $page++) {
            $data = $model::with(['couponIssue','couponClass','users'])->limit(($page - 1) * $pageSize, $pageSize)->select()->toArray();
            // 分块读取数据
            foreach ($data as $item) {
                // 处理数据
                $rowData = [
                    $item['couponIssue']['uuno'],
                    $item['couponClass']['title'],
                    $item['coupon_title'],
                    $item['coupon_price'],
                    $item['users']['name'],
                    $item['users']['mobile'],
                    $item['create_time'],
                ];
                // 将数据逐块写入文件
                fputcsv($handle, $rowData);
                // 如果数据量过大，可以在每次输出后清空缓冲区，释放内存
                if (ftell($handle) >= $chunkSize) {
                    fflush($handle);
                }
            }
        }
        // 关闭文件
        fclose($handle);

        // 压缩文件
        $zip = new \ZipArchive();
        $zip->open($filename . '.zip', \ZipArchive::CREATE);
        $zip->addFile($filename);
        $zip->close();
        // 删除原始文件
        unlink($filename);
        // 下载压缩后的文件
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '.zip"');
        header('Content-Length: ' . filesize($filename . '.zip'));
        readfile($filename . '.zip');
        // 删除压缩后的文件
        unlink($filename . '.zip');
    }

    public function xlsData()
    {
        $header = [
            [
                'title' => '一级表头1',
                'children' => [
                    [
                        'title' => '二级表头1',
                    ],
                    [
                        'title' => '二级表头2',
                    ],
                    [
                        'title' => '二级表头3',
                    ],
                ]
            ],
            [
                'title' => '一级表头2'
            ],
            [
                'title' => '一级表头3',
                'children' => [
                    [
                        'title' => '二级表头1',
                        'children' => [
                            [
                                'title' => '三级表头1',
                            ],
                            [
                                'title' => '三级表头2',
                            ],
                        ]
                    ],
                    [
                        'title' => '二级表头2',
                    ],
                    [
                        'title' => '二级表头3',
                        'children' => [
                            [
                                'title' => '三级表头1',
                                'children' => [
                                    [
                                        'title' => '四级表头1',
                                        'children' => [
                                            [
                                                'title' => '五级表头1'
                                            ],
                                            [
                                                'title' => '五级表头2'
                                            ]
                                        ]
                                    ],
                                    [
                                        'title' => '四级表头2'
                                    ]
                                ]
                            ],
                            [
                                'title' => '三级表头2',
                            ],
                        ]
                    ]
                ]
            ],
            [
                'title' => '一级表头4',
            ],
            [
                'title' => '一级表头5',
            ],
        ];
        $data= [];
        // header头规则 title表示列标题，children表示子列，没有子列children可不写或为空
        for ($i = 0; $i < 100; $i++) {
            $data[] = [
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
                '这是第'. $i .'行测试',
            ];
        }
        $fileName = '很厉害的文件导出类';
        $xlsWriterServer = new MultiFloorXlsWriterService();
        $xlsWriterServer->setFileName($fileName, '这是Sheet1别名');
        $xlsWriterServer->setHeader($header, true);
        $xlsWriterServer->setData($data);
     
        $xlsWriterServer->addSheet('这是Sheet2别名');
        $xlsWriterServer->setHeader($header);   //这里可以使用新的header
        $xlsWriterServer->setData($data);       // 这里也可以根据新的header定义数据格式
     
        $filePath = $xlsWriterServer->output();     // 保存到服务器
        $xlsWriterServer->excelDownload($filePath); // 输出到浏览器
    }
}

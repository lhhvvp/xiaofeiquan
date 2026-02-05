<?php
/**
 * 首页控制器
 * @author slomoo <slomoo@aliyun.com> 2022-06-10
 */
namespace app\travel\controller;

use think\facade\App;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;

class Index extends Base
{
    // 首页
    public function index()
    {
        // 系统信息
        $mysqlVersion = Db::query('SELECT VERSION() AS ver');
        $config       = [
            'url'             => $_SERVER['HTTP_HOST'],
            'document_root'   => $_SERVER['DOCUMENT_ROOT'],
            'server_os'       => PHP_OS,
            'server_port'     => $_SERVER['SERVER_PORT'],
            'server_ip'       => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '',
            'server_soft'     => $_SERVER['SERVER_SOFTWARE'],
            'php_version'     => PHP_VERSION,
            'mysql_version'   => $mysqlVersion[0]['ver'],
            'max_upload_size' => ini_get('upload_max_filesize'),
            'version'         => App::version(),
            'cms_version'    => Config::get('app.cms_version'),
        ];

        // 查找一周内注册用户信息
        //$user = \app\common\model\Users::where('create_time', '>', time() - 60 * 60 * 24 * 7)->count();

        $mid = session()['travel']['id'];
        // 累计核销金额  
        $writeoff_price  = \app\common\model\WriteOff::where('mid',$mid)->sum('coupon_price');
        // 累计核销数量  当日核销金额  当日核销数量
        $writeoff_number = \app\common\model\WriteOff::where('mid',$mid)->count();
        // 当日核销金额
        $start_time = strtotime(date("Y-m-d 00:00:00"));
        $end_time   = strtotime(date("Y-m-d 23:59:59"));
        $writeoff_day_price = \app\common\model\WriteOff::where('create_time','between',[$start_time,$end_time])->where('mid',$mid)->sum('coupon_price');
        // 当日核销数量
        $writeoff_day_number = \app\common\model\WriteOff::where('create_time','between',[$start_time,$end_time])->where('mid',$mid)->count();

        // 管理员信息
        $seller = \app\common\model\Seller::where('id', '=', session()['travel']['id'])->find();

        // 商家最新核销 top 20
        $top_write_off_20 = \app\common\model\WriteOff::field('w.*')
        ->alias('w')
        ->with("users")
        ->where('w.mid',$mid)->order('w.create_time desc')->limit(20)
        ->select();
        $view = [
            'config'        => $config,
            'writeoff_price'=> $writeoff_price,
            'writeoff_number' => $writeoff_number,
            'writeoff_day_price' => $writeoff_day_price,
            'writeoff_day_number' => $writeoff_day_number,
            'top_write_off_20'  => $top_write_off_20,
            'seller'        => $seller,
        ];
        View::assign($view);
        return View::fetch();
    }

    public function screen(){
        $dataList = [];

        // 核销量--top20day
        // 从现在开始  20天前的时间戳
        $start_time = $start_time = mktime(0,0,0,date('m'),date('d')-20,date('Y'));
        $now_time   = time();
       
        $map = [];//查询条件
        
        // 初始化
        $statMap = array();
        $start_reg_time = strtotime(date('Y-m-d 00:00:00', $start_time));
        for ($t = $start_reg_time; $t < $now_time; $t += 86400) {
            $d = date('Y-m-d', $t);
            $statMap[$d]['date'] = $d;
            $statMap[$d]['msg'] = 0;
        }
        // 查询某一个月内的每天的核销数量
        $writeoff = \app\common\model\WriteOff::where('mid',session()['travel']['id'])->whereBetweenTime('create_time',$start_time, $now_time)
            ->field('FROM_UNIXTIME(create_time,"%Y-%m-%d") as date,count(1) as total')
            ->group('FROM_UNIXTIME(create_time,"%Y-%m-%d")')
            ->select();
        // 数据填充
        foreach($writeoff as $it){
            $statMap[$it['date']]['msg'] = $it['total'];
        }

        $dataList['xAxisData'] = array_values($statMap);

        // 获取用户访问小程序数据概况
        $cacheRs = Cache::get('cache_list_travel');
        if($cacheRs) {
            $dataList['listTrend']  = $cacheRs;
        }else{
            $start_time = $start_time = mktime(0,0,0,date('m'),date('d')-7,date('Y'));
            $start_reg_time = strtotime(date('Y-m-d 00:00:00', $start_time));
            $now_time   = time() - 86400;
            for ($t = $start_reg_time; $t < $now_time; $t += 86400) {
                $d = date('Ymd', $t);
                $statMap_bak[$d] = 0;
            }
            $seriesDataMember = ($statMap_bak);
            $wxInfo = accesstoken();
            if($wxInfo['code']==0  && $wxInfo['msg']=='ok'){
                $url  = "https://api.weixin.qq.com/datacube/getweanalysisappiddailysummarytrend?access_token=".$wxInfo['data']['access_token'];
                // 数据填充
                foreach($seriesDataMember as $key=>$val){
                    $dateData   = json_encode(['begin_date' => "$key",'end_date'=> "$key"]);
                    $rsData = http_curl_post($url,$dateData);
                    $rsList = json_decode($rsData,true);
                    $dataList['listTrend'][$key] = @$rsList['list'];
                }
                Cache::set('cache_list_travel',$dataList['listTrend'],43200);
            }
        }
        return json($dataList);
    }

    // 清除缓存
    public function clear()
    {
        $path = App::getRootPath() . 'runtime';
        if ($this->_deleteDir($path)) {
            $result['msg']   = '清除缓存成功!';
            $result['error'] = 0;
        } else {
            $result['msg']   = '清除缓存失败!';
            $result['error'] = 1;
        }
        $result['url'] = (string)url('login/index');
        return json($result);
    }

    /**
     * 预览
     * @param string $module 模型名称
     * @param string $id     文章id
     * @return \think\response\Redirect
     */
    public function preview(string $module, string $id)
    {
        // 查询当前模块信息
        $model = '\app\common\model\\' . $module;
        $info  = $model::find($id);
        if ($info) {
            // 查询所在栏目信息
            $cate = \app\common\model\Cate::find($info['cate_id']);
            if ($cate->module->getData('model_name') == 'Page') {
                if ($cate['cate_folder']) {
                    $url = $cate['cate_folder'] . '.' . Config::get('route.url_html_suffix');
                } else {
                    $url = $module . Config::get('route.pathinfo_depr') . 'index.' . Config::get('route.url_html_suffix') . '?cate=' . $cate['id'];
                }
            } else {
                if ($cate['cate_folder']) {
                    $url = $cate['cate_folder'] . Config::get('route.pathinfo_depr') . $id . '.' . Config::get('route.url_html_suffix');
                } else {
                    $url = $module . Config::get('route.pathinfo_depr') . 'info.' . Config::get('route.url_html_suffix') . '?cate=' . $cate['id'] . '&id=' . $id;
                }
            }
            if (isset($url) && !empty($url)) {
                // 检测是否开启了域名绑定
                $domainBind = Config::get('app.domain_bind');
                if ($domainBind) {
                    $domainBindKey = array_search('index', $domainBind);
                    $domainBindKey = $domainBindKey == '*' ? 'www.' : ($domainBindKey ? $domainBindKey . '.' : '');
                    $url           = Request::scheme() . '://' . $domainBindKey . Request::rootDomain() . '/' . $url;
                } else {
                    $url = '/index/' . $url;
                }
            }
        }
        return redirect($url);
    }

    /**
     * select 2 ajax分页获取数据
     * @param int    $id      字段id
     * @param string $keyWord 搜索词
     * @param string $rows    显示数量
     * @param string $value   默认值
     * @return array
     */
    public function select2(int $id, string $keyWord = '', string $rows = '10', string $value = '')
    {
        // 字段信息
        $field = \app\common\model\Field::find($id);
        if (is_null($field) || empty($field['relation_model']) || empty($field['relation_field'])) {
            return [];
        }
        $model = '\app\common\model\\' . $field['relation_model'];
        // 获取主键
        $pk = \app\common\model\Module::where('model_name', $field['relation_model'])->value('pk') ?? 'id';
        // 默认值
        if ($value) {
            $valueText = $model::where($pk, $value)->value($field['relation_field']);
            if ($valueText) {
                return [
                    'key'   => $value,
                    'value' => $valueText
                ];
            }
        }

        // 搜索条件
        $where = [];
        if ($keyWord) {
            $where[] = [$field['relation_field'], 'LIKE', '%' . $keyWord . '%'];
        }

        $list = $model::field($pk . ',' . $field['relation_field'])
            ->where($where)
            ->order($pk . ' desc')
            ->paginate([
                'query'     => Request::get(),
                'list_rows' => $rows,
            ]);
        foreach ($list as $k => $v) {
            $v['text'] = $v[$field['relation_field']];
        }
        return $list;
    }

    /**
     * ajax获取多级联动数据
     * @param string $model        模型名称
     * @param string $key          关联模型的主键
     * @param string $keyValue     要展示的字段
     * @param int    $pid          父ID
     * @param string $pidFieldName 关联模型的父级id字段名
     * @return array
     */
    public function linkage(string $model, string $key, string $keyValue, int $pid = 0, string $pidFieldName = 'pid')
    {
        $list   = getLinkageData($model, $pid, $pidFieldName);
        $result = [];
        foreach ($list as $v) {
            $result[] = [
                'key'   => $v[$key],
                'value' => $v[$keyValue],
            ];
        }
        return [
            'code' => 1,
            'list' => $result
        ];
    }

    // 执行删除
    private function _deleteDir($R)
    {
        Cache::clear();
        $handle = opendir($R);
        while (($item = readdir($handle)) !== false) {
            // log目录不可以删除
            if ($item != '.' && $item != '..' && $item != 'log') {
                if (is_dir($R . DIRECTORY_SEPARATOR . $item)) {
                    $this->_deleteDir($R . DIRECTORY_SEPARATOR . $item);
                } else {
                    if ($item != '.gitignore') {
                        if (!unlink($R . DIRECTORY_SEPARATOR . $item)) {
                            return false;
                        }
                    }
                }
            }
        }
        closedir($handle);
        return true;
        //return rmdir($R); // 删除空的目录
    }

    // 检查提示信息
    private function getIndexTips()
    {
        $password = \app\common\model\Admin::where('id', session('admin.id'))->value('password');
        if ($password == md5('admin')) {
            return '<h6 class="mb-0"><i class="icon fas fa-fw fa-exclamation-triangle"></i> 请尽快修改后台初始密码！</h6>';
        }
        return '';
    }
}

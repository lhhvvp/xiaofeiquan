<?php
/**
 * 首页控制器
 * @author slomoo <slomoo@aliyun.com> 2022-06-10
 */
namespace app\admin\controller;

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
        $dbVersion = $this->dbType == 'dm' ? Db::query('SELECT *,ID_CODE FROM V$VERSION') : Db::query('SELECT VERSION() AS banner');
        $config       = [
            'url'             => $_SERVER['HTTP_HOST'],
            'document_root'   => $_SERVER['DOCUMENT_ROOT'],
            'server_os'       => PHP_OS,
            'server_port'     => $_SERVER['SERVER_PORT'],
            'server_ip'       => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '',
            'server_soft'     => $_SERVER['SERVER_SOFTWARE'],
            'php_version'     => PHP_VERSION,
            'mysql_version'   => $dbVersion[0]['banner'],
            'max_upload_size' => ini_get('upload_max_filesize'),
            'version'         => App::version(),
            'cms_version'    => Config::get('app.cms_version'),
        ];

        // 查找一周内注册用户信息
        //$user = \app\common\model\Users::where('create_time', '>', time() - 60 * 60 * 24 * 7)->count();

        // 管理员信息
        $admin = \app\common\model\Admin::where('id', '=', session()['admin']['id'])->find();
        $currentYear = date("Y");
        // 查询累计发放金额=所有消费券的面额
        #$coupon_issue_list = \app\common\model\CouponIssue::select();
        $coupon_issue_list = \app\common\model\CouponIssue::whereYear('create_time', $currentYear)->where('status', 'between', ['0','2'])->select();
        $len = count($coupon_issue_list);
        $coupon_issue_price = 0;
        for ($i=0; $i < $len; $i++) { 
            $coupon_issue_price += $coupon_issue_list[$i]['coupon_price'] * $coupon_issue_list[$i]['total_count'];
        }
        // 查询累计核销金额=所有核销记录中消费券的面额
        $writeoff_price = \app\common\model\WriteOff::whereYear('create_time', $currentYear)->sum('coupon_price');
        $tour_writeoff_price = \app\common\model\TourWriteOff::whereYear('create_time', $currentYear)->sum('coupon_price');
        // 查询今日核销记录=所有核销记录中今日消费券的面额
        $start_time = strtotime(date("Y-m-d 00:00:00"));
        $end_time   = strtotime(date("Y-m-d 23:59:59"));
        $writeoff_day_price = \app\common\model\WriteOff::where('create_time','between',[$start_time,$end_time])->sum('coupon_price');
        // 2023-03-14 查询团体当日核销记录
        $tour_writeoff_day_price = \app\common\model\TourWriteOff::where('create_time','between',[$start_time,$end_time])->sum('coupon_price');
        // 2023-05-05 统计今天散客核销量，团体核销量
        $writeoff_today_sanke_total = \app\common\model\WriteOff::where('create_time','between',[$start_time,$end_time])->count();
        $writeoff_today_tuanti_total = \app\common\model\TourWriteOff::where('create_time','between',[$start_time,$end_time])->count();
        // 消费券四个分类下各个的预计发放总量
        $issuelist = \app\common\model\CouponIssue::field('cid,sum(total_count) as total')->whereYear('create_time', $currentYear)->group('cid')->select();
        $coupon_class = [
            1=>['total'=>0,'price'=>0],
            2=>['total'=>0,'price'=>0],
            3=>['total'=>0,'price'=>0],
            4=>['total'=>0,'price'=>0],
            7=>['total'=>0,'price'=>0],
            8=>['total'=>0,'price'=>0],
        ];
        // 每个类的发行总金额
        $price = \app\common\model\CouponIssue::field('cid,(total_count * coupon_price) as price')->whereYear('create_time', $currentYear)->where('status', 'between', ['0','2'])->select();
        //print_r($price->toArray());die;
        
        // 每个分类下发行总数量
        for ($i=0; $i < count($issuelist); $i++) {
            if($i+1 == $issuelist[$i]['cid']){
                $coupon_class[$i+1]['total'] = $issuelist[$i]['total'];
            }
            $coupon_class[$i+1]['price'] = 0;
            // 每个分类下发行总金额
            for ($j=0; $j < count($price); $j++) { 
                if($i+1 == $price[$j]['cid'])
                    $coupon_class[$i+1]['price'] += $price[$j]['price'];
            }
        }
        // 消费券已经领取总数
        $tp_coupon_issue_user = Db::name('coupon_issue_user')
        ->field('s.cid,count(u.id) as total')
        ->alias('u')
        ->join('coupon_issue s', 's.id = u.issue_coupon_id')
        ->group('s.cid')
        ->whereYear('u.create_time', $currentYear)
        ->select()
        ->toArray();
        // 【团体】消费券已经领取总数
        $tp_tour_issue_user = Db::name('tour_issue_user')
        ->field('s.cid,count(u.id) as total')
        ->alias('u')
        ->join('coupon_issue s', 's.id = u.issue_coupon_id')
        ->group('s.cid')
        ->whereYear('u.create_time', $currentYear)
        ->select()
        ->toArray();
        // 领取记录每张券的面额 * 
        /*$tp_coupon_issue_user_price = Db::name('coupon_issue_user')
        ->field('s.cid,sum(u.coupon_price) as price')
        ->alias('u')
        ->join('coupon_issue s', 's.id = u.issue_coupon_id')
        ->group('s.cid')
        ->select()
        ->toArray();*/

        // 核销记录每张券的面额 * 
        $tp_write_off_price = Db::name('write_off')
        ->field('s.cid,s.uuno,sum(u.coupon_price) as price')
        ->alias('u')
        ->join('coupon_issue s', 's.uuno = u.uuno')
        ->group('s.cid')
        ->whereYear('u.create_time', $currentYear)
        ->select()
        ->toArray();
        // 【团体】核销记录每张券的面额 * 
        $tp_tour_write_off_price = Db::name('tour_write_off')
        ->field('sum(u.coupon_price) as price,s.cid')
        ->alias('u')
        ->join('coupon_issue s', 's.id = u.coupon_issue_id')
        ->group('s.cid')
        ->whereYear('u.create_time', $currentYear)
        ->select()
        ->toArray();

        // 核销总数
        $tp_coupon_issue_user_write_off_total = Db::name('write_off')
        ->field('s.cid,u.uuno,count(u.id) as total')
        ->alias('u')
        ->join('coupon_issue s', 's.uuno = u.uuno')
        ->group('s.cid')
        ->whereYear('u.create_time', $currentYear)
        ->select()
        ->toArray();
        // 【团体】核销总数
        $tp_tour_issue_user_write_off_total = Db::name('tour_write_off')
        ->field('s.cid,u.coupon_issue_id,count(u.id) as total')
        ->alias('u')
        ->join('coupon_issue s', 's.id = u.coupon_issue_id')
        ->group('s.cid')
        ->whereYear('u.create_time', $currentYear)
        ->select()
        ->toArray();

        $coupon_issue_user = [
            1=>['total'=>0,'price'=>0,'write_total'=>0,'write_off_price'=>0],
            2=>['total'=>0,'price'=>0,'write_total'=>0,'write_off_price'=>0],
            3=>['total'=>0,'price'=>0,'write_total'=>0,'write_off_price'=>0],
            4=>['total'=>0,'price'=>0,'write_total'=>0,'write_off_price'=>0],
            7=>['total'=>0,'price'=>0,'write_total'=>0,'write_off_price'=>0],
            8=>['total'=>0,'price'=>0,'write_total'=>0,'write_off_price'=>0],
        ];

        foreach ($coupon_issue_user as $key => $value) {
            // 领取总数
            foreach ($tp_coupon_issue_user as $kk => $vv) {
                if($key == $vv['cid'])
                    $coupon_issue_user[$key]['total'] = $vv['total'];
            }
            foreach ($tp_tour_issue_user as $kk => $vv) {
                if($key == $vv['cid'])
                    $coupon_issue_user[$key]['total'] += $vv['total'];
            }

            // 领取总额
            /*foreach ($tp_coupon_issue_user_price as $kk => $vv) {
                if($key == $vv['cid'])
                    $coupon_issue_user[$key]['price'] = $vv['price'];
            }*/
            // 核销总额
            foreach ($tp_write_off_price as $kk => $vv) {
                if($key == $vv['cid'])
                    $coupon_issue_user[$key]['write_off_price'] = $vv['price'];
            }
            // 合并团体核销总额
            foreach ($tp_tour_write_off_price as $kk => $vv) {
                if($key == $vv['cid']){
                    $coupon_issue_user[$key]['write_off_price'] += $vv['price'];
                }
            }
            // 核销总数
            foreach ($tp_coupon_issue_user_write_off_total as $kk => $vv) {
                if($key == $vv['cid'])
                    $coupon_issue_user[$key]['write_total'] = $vv['total'];
            }
            foreach ($tp_tour_issue_user_write_off_total as $kk => $vv) {
                if($key == $vv['cid'])
                    $coupon_issue_user[$key]['write_total'] += $vv['total'];
            }
        }
        // 入住的商户
        $seller_total = \app\common\model\Seller::where('status',1)->count();
        // 核验人员
        $mvuser_total = \app\common\model\MerchantVerifier::where('status',1)->count();

        // 2023-03-18 运营端首页展示商家核销前三名
        $list_seller_top3  = Db::name('write_off')->alias('aa')
            ->field('count(1) as total, SUM(aa.coupon_price) as price,aa.mid,bb.username,bb.nickname,cc.class_name')
            ->join(['tp_seller'=>'bb'],'aa.mid = bb.id')
            ->join(['tp_seller_class'=>'cc'],'bb.class_id = cc.id')
            ->group('aa.mid')
            ->order('cc.id,price desc')
            ->limit(3)
            ->select()
            ->toArray();
        //print_r($list_seller_top3);die;
        View::assign('list_seller_top3',$list_seller_top3);
        // 2023-03-18 运营端首页展示旅行社核销前三名
        $list_tour_top3  = Db::name('tour_write_off')->alias('aa')
            ->field('count(1) as total, SUM(aa.coupon_price) as price,aa.mid,bb.username,bb.nickname,cc.class_name')
            ->join(['tp_seller'=>'bb'],'aa.mid = bb.id')
            ->join(['tp_seller_class'=>'cc'],'bb.class_id = cc.id')
            ->group('aa.mid')
            ->order('cc.id,price desc')
            ->limit(3)
            ->select()
            ->toArray();
        View::assign('list_tour_top3',$list_tour_top3);

        $view = [
            'config'        => $config,
            'admin'         => $admin,
            'coupon_issue_price'  => $coupon_issue_price,
            'writeoff_price'  => $writeoff_price + $tour_writeoff_price,
            'writeoff_day_price'=> $writeoff_day_price + $tour_writeoff_day_price,
            'writeoff_today_sanke_price' => $writeoff_day_price,
            'writeoff_today_tuanti_price' => $tour_writeoff_day_price,
            'writeoff_today_sanke_total' => $writeoff_today_sanke_total,
            'writeoff_today_tuanti_total' => $writeoff_today_tuanti_total,
            'coupon_class'  => $coupon_class,
            'coupon_issue_user' => $coupon_issue_user,
            'seller_total'  => $seller_total,
            'mvuser_total'  => $mvuser_total,

        ];
        View::assign($view);
        return View::fetch();
    }

    public function screen()
    {
        $currentYear = date("Y");
        $dataList = [];
        // 2023-05-04 查询优化：增加查询缓存结果，1小时
        // 获取用户年龄分布
        $userlist = \app\common\model\Users::field("CASE WHEN age BETWEEN 0 AND 18 THEN '0~18岁' 
                      WHEN age BETWEEN 19 AND 35 THEN '18~35岁' 
                      WHEN age BETWEEN 36 AND 55 THEN '35~55岁' 
                      ELSE '55以上岁' END AS name, 
                 COUNT(*) AS value")
        ->group('name')
        ->cache(3600) // 缓存1小时
        ->select();
        $ageArr = [];
        foreach ($userlist as $item) {
            $name = $item['name'];
            $value = $item['value'];
            if (isset($ageArr[$name])) {
                $ageArr[$name] += $value;
            } else {
                $ageArr[$name] = $value;
            }
        }
        // 结构转换
        $dataList['dataAge'] = [];
        foreach ($ageArr as $name => $value) {
            $dataList['dataAge'][] = ['name' => $name, 'value' => $value];
        }
        /*
        $userlist = \app\common\model\Users::field('age')->select();
        $dataList['dataAge']  = [
            ["name" => "0~18岁","value"=> 0,],
            ["name" => "18~35岁","value"=> 0,],
            ["name" => "35~55岁","value"=> 0,],
            ["name" => "55以上岁","value"=> 0,]
        ];
        foreach ($userlist as $key => $value) {
            if($value['age'] > 0 && $value['age']<=18){
                $dataList['dataAge'][0]['value'] = $dataList['dataAge'][0]['value'] + 1;
            }else if($value['age'] > 18 && $value['age']<=35){
                $dataList['dataAge'][1]['value'] = $dataList['dataAge'][1]['value'] + 1;
            }else if($value['age'] > 35 && $value['age']<=55){
                $dataList['dataAge'][2]['value'] = $dataList['dataAge'][2]['value'] + 1;
            }else if($value['age'] > 55){
                $dataList['dataAge'][3]['value'] = $dataList['dataAge'][3]['value'] + 1;
            }
        }*/
        // 获取用户访问小程序数据概况
        /*$cacheRs = Cache::get('cache_list_Trend');
        if($cacheRs) {
            $dataList['listTrend']  = $cacheRs;
        }else{
            $start_time = $start_time = mktime(0,0,0,date('m'),date('d')-7,date('Y'));
            $start_reg_time = strtotime(date('Y-m-d 00:00:00', $start_time));
            $now_time   = time() - 86400;
            for ($t = $start_reg_time; $t < $now_time; $t += 86400) {
                $d = date('Ymd', $t);
                $statMap[$d] = 0;
            }
            $seriesDataMember = ($statMap);
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
                Cache::set('cache_list_Trend',$dataList['listTrend'],86400);
            }
        }*/
        // 2023-07-28 将小程序人数统计更换为系统每日新增人数、认证人数
        // 近7日 每日的新增人数
        
        $dataList['listTrend']  = $this->listTrend();
        // 地图数据
        $dataList['mapData']  = [
            ["id"=>1,"name" => "榆阳区","value"=> 0,"writeoff"=> 0],
            ["id"=>2,"name" => "横山区","value"=> 0,"writeoff"=> 0],
            ["id"=>3,"name" => "神木市","value"=> 0,"writeoff"=> 0],
            ["id"=>4,"name" => "府谷县","value"=> 0,"writeoff"=> 0],
            ["id"=>5,"name" => "靖边县","value"=> 0,"writeoff"=> 0],
            ["id"=>6,"name" => "定边县","value"=> 0,"writeoff"=> 0],
            ["id"=>7,"name" => "绥德县","value"=> 0,"writeoff"=> 0],
            ["id"=>8,"name" => "米脂县","value"=> 0,"writeoff"=> 0],
            ["id"=>9,"name" => "佳县","value"=> 0,"writeoff"=> 0],
            ["id"=>10,"name" => "吴堡县","value"=> 0,"writeoff"=> 0],
            ["id"=>11,"name" => "清涧县","value"=> 0,"writeoff"=> 0],
            ["id"=>12,"name" => "子洲县","value"=> 0,"writeoff"=> 0],
        ];
        rsort($dataList['mapData']);
        // 全部商家总数
        $sellerlist = \app\common\model\Seller::field('area,count(1) as total')->group('area')->select();
        foreach ($dataList['mapData'] as $key => $value) {
            foreach ($sellerlist as $kk => $vv) {
                if($vv['area']==$value['id']){
                    $dataList['mapData'][$key]['value'] = $vv['total'];
                }
            }
        }
        // 商家核销数
        $writelist = Db::name('write_off')
        ->field('w.mid,s.area,count(w.id) as total')
        ->alias('w')
        ->join('seller s', 'w.mid = s.id')
        ->group('s.area')
        ->whereYear('w.create_time', $currentYear)
        ->select();
        foreach ($dataList['mapData'] as $key => $value) {
            foreach ($writelist as $kk => $vv) {
                if($vv['area']==$value['id']){
                    $dataList['mapData'][$key]['writeoff'] = $vv['total'];
                }
            }
        }
        // 团体用户分布情况
        $tour_write_distribute = Db::name('tour_write_off')
        ->field('w.uid,s.province,s.city,count(1) as total')
        ->alias('w')
        ->join('users s', 'w.uid = s.id')
        ->where('s.province','<>','')
        ->group('s.province')
        ->order('total desc')
        ->limit(10)
        ->select();
        $dataList['tour_write_distribute'] = $tour_write_distribute;
        return json($dataList);
    }

    public function listTrend()
    {
        $toDate = $this->dbType == 'dm' ? "TO_DATE(FROM_UNIXTIME(create_time,'yyyy-mm-dd')) AS ref_date" : "DATE(FROM_UNIXTIME(create_time)) AS ref_date";
        $sql = "SELECT t1.ref_date, t1.visit_total, t2.share_uv
            FROM (
                SELECT $toDate, COUNT(*) AS visit_total
                FROM tp_users
                WHERE create_time >= UNIX_TIMESTAMP(CURRENT_DATE() - INTERVAL '6' DAY)
                GROUP BY ref_date
            ) t1
            LEFT JOIN (
                SELECT $toDate, COUNT(*) AS share_uv
                FROM tp_users
                WHERE create_time >= UNIX_TIMESTAMP(CURRENT_DATE() - INTERVAL '6' DAY)
                AND auth_status = 1
                GROUP BY ref_date
            ) t2 ON t1.ref_date = t2.ref_date
            ORDER BY t1.ref_date;";
        $result = Db::query($sql);

        $data = [];
        foreach ($result as $row) {
            $data[$row['ref_date']][] = [
                'ref_date' => $row['ref_date'],
                'visit_total' => $row['visit_total'],
                'share_pv' => 0, // 如果需要统计其他字段，可以在此处添加相应的查询字段
                'share_uv' => $row['share_uv'],
            ];
        }
        
        return $data;
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

    /**
     * selectSeller 获取指定商户
     * @param int    $id      字段id
     * @param string $keyWord 搜索词
     * @param string $rows    显示数量
     * @param string $value   默认值
     * @return array
     */
    public function selectSeller(int $id, string $keyWord = '', string $rows = '10', string $value = '')
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
            ->where('class_id',2)
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
     * selectTicketCategory 分类
     * @param int    $id      字段id
     * @param string $keyWord 搜索词
     * @param string $rows    显示数量
     * @param string $value   默认值
     * @return array
     */
    public function selectTicketCategory(int $id, string $keyWord = '', string $rows = '10', string $value = '',string $tags='')
    {
        $model = '\app\common\model\TicketCategory';
        // 搜索条件
        $where = [];
        if ($keyWord) {
            $where[] = ['title', 'LIKE', '%' . $keyWord . '%'];
        }
        if($tags!=''){
            $where[] = ['id','=',$id];
            $list = $model::field('id,seller_id,title')
            ->where($where)
            ->find();


        }else{
            $where[] = ['seller_id','=',$id];
            $list = $model::field('id,seller_id,title')
                ->where($where)
                ->where('status',1)
                ->order('id desc')
                ->paginate([
                    'query'     => Request::get(),
                    'list_rows' => $rows,
                ]);
            foreach ($list as $k => $v) {
                $v['text'] = $v['title'];
            }
        }
        return $list;
    }
}

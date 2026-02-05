<?php
/**
 * 发行统计管理控制器
 * @author slomoo <slomoo@aliyun.com> 2022-04-15
 */

namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\View;
use think\facade\Db;

class Issue extends Base
{
    // 用户分布情况
    public function statistics()
    {

        $param = Request::param('date');

        $where = [];
        if ($param) {
            $where[] = ['b.start_time', '=', $param];
            View::assign('datetime', date("Y年m月d日 H:i:s", $param));
        } else {
            View::assign('datetime', '全部');
        }
        // 获取发行日期
        $date = \app\common\model\CouponIssue::field('id,coupon_title,start_time,end_time')
            ->group('start_time')
            ->select()
            ->toArray();
        sort($date);

        // 根据消费券领取时间分组
        View::assign('date', $date);

        // 获取消费券信息
        $list = Db::name('coupon_issue_user')->field('count(1) as total,b.total_count as faxing,a.issue_coupon_id,b.coupon_title,b.coupon_price,b.cid,b.remain_count,b.rollback_num,b.rollback_num_extend')
            ->alias('a')
            ->join(['tp_coupon_issue' => 'b'], 'a.issue_coupon_id = b.id')
            ->where($where)
            ->group('a.issue_coupon_id')
            ->select()
            ->toArray();

        // 获取分类
        $cate = Db::name('coupon_class')->field('id,title')
            ->select()
            ->toArray();

        // 核销数量
        $hexiao = Db::name('write_off')->field('count(1) as total,a.coupon_issue_id,a.coupon_title')
            ->alias('a')
            ->join(['tp_coupon_issue' => 'b'], 'a.coupon_issue_id = b.id')
            ->where($where)
            ->group('a.coupon_issue_id')
            ->select()
            ->toArray();

        foreach ($list as $key => $value) {
            $list[$key]['writeoff'] = 0;
            foreach ($hexiao as $kk => $vv) {
                if ($value['issue_coupon_id'] == $vv['coupon_issue_id']) {
                    $list[$key]['writeoff'] = $vv['total'];
                }
            }
        }

        // 每个券的核销比
        foreach ($list as $key => $value) {
            $floor               = $value['writeoff'] / $value['total'] * 100;
            $list[$key]['ratio'] = sprintf("%.2f", $floor);// 核销比率
        }
        $ration = array_column($list, 'ratio');
        // 按照核销比率排序
        array_multisort($ration, SORT_DESC, $list);

        // 往每个分类下填入数据
        foreach ($cate as $key => $value) {
            $i                          = 0;
            $cate[$key]['price']        = 0;  // 每个分类下消费券面额总计
            $cate[$key]['list']         = []; // 每个分类下的信息
            $cate[$key]['total']        = 0;  // 每个分类下多少个消费券
            $cate[$key]['total_count']  = 0;  // 每个分类下消费券发行量
            $cate[$key]['lingqu_count'] = 0;  // 每个分类下消费券实际领取量
            $cate[$key]['duofa_count']  = 0;  // 每个分类下消费券多领取数量【超卖】
            $cate[$key]['hexiao_count'] = 0;  // 每个分类下消费券核销数量
            foreach ($list as $kk => $vv) {
                if ($vv['cid'] == $value['id']) {
                    $cate[$key]['list'][]       = $vv;
                    $cate[$key]['total']        = $i + 1;
                    $cate[$key]['price']        += $vv['coupon_price'];
                    $cate[$key]['total_count']  += $vv['faxing'];
                    $cate[$key]['lingqu_count'] += $vv['total'];
                    if (($vv['total'] - $vv['faxing']) > 0)
                        $cate[$key]['duofa_count'] += ($vv['total'] - $vv['faxing']);
                    $cate[$key]['hexiao_count'] += $vv['writeoff'];
                    $i++;
                }
            }
        }

        //$list = $this->array_group_by($list,'cid');
        //print_r($cate);die;
        View::assign('cate', $cate);
        return View::fetch();
    }

    // 根据某个key给二维数组分组
    private function array_group_by($arr, $key)
    {
        $grouped = [];
        foreach ($arr as $value) {
            $grouped[$value[$key]][] = $value;
        }
        /*if (func_num_args() > 2) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $parms = array_merge([$value], array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array('array_group_by', $parms);
            }
        }*/
        return $grouped;
    }

    // 商户核销金额统计
    public function seller()
    {

        $cid  = Request::param('cid');
        $time = Request::param('time');

        $where = [];
        if ($cid) {
            $where[] = ['cc.id', '=', $cid];
        }
        if ($time) {
            $create_time       = explode('/', $time);
            $create_time_start = strtotime($create_time[0]);
            $create_time_end   = strtotime($create_time[1] . "23:59:59");
            $where[]           = ['aa.create_time', 'between', [$create_time_start, $create_time_end]];
        }

        // 散客核销信息
        $list = Db::name('write_off')->alias('aa')
            ->field('count(1) as total, SUM(aa.coupon_price) as price,aa.mid,bb.username,bb.nickname,cc.class_name')
            ->join(['tp_seller' => 'bb'], 'aa.mid = bb.id')
            ->join(['tp_seller_class' => 'cc'], 'bb.class_id = cc.id')
            ->where($where)
            ->group('aa.mid')
            ->order('cc.id,price desc')
            ->select()
            ->toArray();

        $total = 0;
        $price = 0;
        foreach ($list as $key => $value) {
            $total += $value['total'];
            $price += $value['price'];
        }
        View::assign('price', $price);
        View::assign('total', $total);
        View::assign('list', $list);
        // 2023-03-16 根据分类进行统计
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->whereIn('id', [2, 4, 5])
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['class_list' => $SellerClass]);
        return View::fetch();
    }

    // 地图详情
    public function showMap($mid = "")
    {
        if (Request::isPost()) {
            // 用户核销经纬度
            $list = \app\common\model\WriteOff::field('id,uw_latitude,uw_longitude,mid')
                ->where('mid', Request::param('mid'))
                ->select()
                ->toArray();

            $result = [
                'code' => 1,
                'msg'  => '请求成功',
                'data' => $list,
                'url'  => '',
                'wait' => 3,
            ];
            return json($result);
        }
        $seller = \app\common\model\Seller::field('id,nickname,longitude,latitude')
            ->where('id', $mid)->find();
        // 商户信息
        View::assign('seller', $seller);
        return View::fetch();
    }

    // 团体发行统计
    public function tour_statistics()
    {

        $param = Request::param('date');

        $where = [];
        if ($param) {
            $where[] = ['b.start_time', '=', $param];
            View::assign('datetime', date("Y年m月d日 H:i:s", $param));
        } else {
            View::assign('datetime', '全部');
        }
        // 获取发行日期
        $date = \app\common\model\CouponIssue::field('id,coupon_title,start_time')
            ->where('cid', 3)
            ->group('start_time')
            ->select()
            ->toArray();
        //unset($date[0], $date[1]);
        sort($date);
        // 根据消费券领取时间分组
        View::assign('date', $date);

        // 获取消费券信息
        $list = Db::name('tour_issue_user')->field('count(1) as total,b.total_count as faxing,a.issue_coupon_id,b.coupon_title,b.coupon_price,b.cid')
            ->alias('a')
            ->join(['tp_coupon_issue' => 'b'], 'a.issue_coupon_id = b.id')
            ->where($where)
            ->group('a.issue_coupon_id')
            ->select()
            ->toArray();
        // 获取分类
        $cate = Db::name('coupon_class')->field('id,title')
            ->select()
            ->toArray();

        // 核销数量
        $hexiao = Db::name('tour_write_off')->field('count(1) as total,a.coupon_issue_id,a.coupon_title')
            ->alias('a')
            ->join(['tp_coupon_issue' => 'b'], 'a.coupon_issue_id = b.id')
            ->where($where)
            ->group('a.coupon_issue_id')
            ->select()
            ->toArray();

        foreach ($list as $key => $value) {
            $list[$key]['writeoff'] = 0;
            foreach ($hexiao as $kk => $vv) {
                if ($value['issue_coupon_id'] == $vv['coupon_issue_id']) {
                    $list[$key]['writeoff'] = $vv['total'];
                }
            }
        }

        // 每个券的核销比
        foreach ($list as $key => $value) {
            $floor               = $value['writeoff'] / $value['total'] * 100;
            $list[$key]['ratio'] = sprintf("%.2f", $floor);// 核销比率
        }
        $ration = array_column($list, 'ratio');
        // 按照核销比率排序
        array_multisort($ration, SORT_DESC, $list);

        // 往每个分类下填入数据
        foreach ($cate as $key => $value) {
            $i                          = 0;
            $cate[$key]['price']        = 0;  // 每个分类下消费券面额总计
            $cate[$key]['list']         = []; // 每个分类下的信息
            $cate[$key]['total']        = 0;  // 每个分类下多少个消费券
            $cate[$key]['total_count']  = 0;  // 每个分类下消费券发行量
            $cate[$key]['lingqu_count'] = 0;  // 每个分类下消费券实际领取量
            $cate[$key]['duofa_count']  = 0;  // 每个分类下消费券多领取数量【超卖】
            $cate[$key]['hexiao_count'] = 0;  // 每个分类下消费券核销数量
            foreach ($list as $kk => $vv) {
                if ($vv['cid'] == $value['id']) {
                    $cate[$key]['list'][]       = $vv;
                    $cate[$key]['total']        = $i + 1;
                    $cate[$key]['price']        += $vv['coupon_price'];
                    $cate[$key]['total_count']  += $vv['faxing'];
                    $cate[$key]['lingqu_count'] += $vv['total'];
                    if (($vv['total'] - $vv['faxing']) > 0)
                        $cate[$key]['duofa_count'] += ($vv['total'] - $vv['faxing']);
                    $cate[$key]['hexiao_count'] += $vv['writeoff'];
                    $i++;
                }
            }
        }

        //$list = $this->array_group_by($list,'cid');
        //print_r($cate);die;
        View::assign('cate', $cate);
        return View::fetch();
    }

    // 团体核销金额统计
    public function tour_seller()
    {

        $cid  = Request::param('cid');
        $time = Request::param('time');

        $where = [];
        if ($cid) {
            $where[] = ['cc.id', '=', $cid];
        }

        if ($time) {
            $create_time       = explode('/', $time);
            $create_time_start = strtotime($create_time[0]);
            $create_time_end   = strtotime($create_time[1] . "23:59:59");
            $where[]           = ['aa.create_time', 'between', [$create_time_start, $create_time_end]];
        }
        // 散客核销信息
        $list = Db::name('tour_write_off')->alias('aa')
            ->field('count(1) as total, SUM(aa.coupon_price) as price,aa.mid,bb.username,bb.nickname,cc.class_name')
            ->join(['tp_seller' => 'bb'], 'aa.mid = bb.id')
            ->join(['tp_seller_class' => 'cc'], 'bb.class_id = cc.id')
            ->where($where)
            ->group('aa.mid')
            ->order('cc.id,price desc')
            ->select()
            ->toArray();

        $total = 0;
        $price = 0;
        foreach ($list as $key => $value) {
            $total += $value['total'];
            $price += $value['price'];
        }
        View::assign('price', $price);
        View::assign('total', $total);
        View::assign('list', $list);
        // 2023-03-16 根据分类进行统计
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->whereIn('id', [2, 3, 4, 5, 6, 7])
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['class_list' => $SellerClass]);
        return View::fetch();
    }

    // 地图详情
    public function tourshowMap($mid = "")
    {
        if (Request::isPost()) {
            // 用户核销经纬度
            $list = \app\common\model\TourWriteOff::field('id,uw_latitude,uw_longitude,mid')
                ->where('mid', Request::param('mid'))
                ->select()
                ->toArray();

            $result = [
                'code' => 1,
                'msg'  => '请求成功',
                'data' => $list,
                'url'  => '',
                'wait' => 3,
            ];
            return json($result);
        }
        $seller = \app\common\model\Seller::field('id,nickname,longitude,latitude')
            ->where('id', $mid)->find();
        // 商户信息
        View::assign('seller', $seller);
        return View::fetch();
    }

    /**
     * [data_summary 数据完全统计报表]
     * @return   [type]            [数据完全统计报表]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-03-21
     * @version  [1.0.0]
     */
    public function data_summary()
    {
        $param = Request::param('date');

        $where = [];
        if ($param) {
            $where[] = ['issue_date', '=', $param];
            View::assign('datetime', date("Y年m月d日 H:i:s", $param));
        } else {
            View::assign('datetime', '全部');
        }

        // 获取发行日期
        $date = \app\common\model\CouponIssue::field('id,coupon_title,start_time,end_time')
            ->group('start_time')
            ->select()
            ->toArray();
        //unset($date[1]);
        sort($date);

        if (!$date) {
            return '<span style="display: block;text-align: center;padding-top: 30px;">没有找到匹配的记录</span>';
        }

        $endTime = end($date);
        View::assign('newDate', $endTime['start_time']);
        // 根据消费券领取时间分组
        // 
        // 按照日期排序
        $start_time = array_column($date, 'start_time');
        // 按照核销比率排序
        array_multisort($start_time, SORT_ASC, $date);
        View::assign('date', $date);

        // 获取消费券信息
        $list = Db::name('data_summary')->field('sum(issue_total) as issue_total,sum(issue_price) as issue_price,sum(receive_total) as receive_total,sum(writeoff_total) as writeoff_total,sum(writeoff_price) as writeoff_price,sum(overstep) as overstep,class_name,class_cid,coupon_title,coupon_id,coupon_price')
            ->where($where)
            ->group('coupon_title')
            ->select()
            ->toArray();
        // 计算核销比
        // 每个券的核销比
        foreach ($list as $key => $value) {
            $floor                        = $value['receive_total'] > 0 ? ($value['writeoff_total'] / $value['receive_total'] * 100) : 0;
            $list[$key]['writeoff_ratio'] = sprintf("%.2f", $floor);// 核销比率
        }
        $ration = array_column($list, 'writeoff_ratio');
        // 按照核销比率排序
        array_multisort($ration, SORT_DESC, $list);

        // 获取分类
        $cate = Db::name('coupon_class')->field('id,title')
            ->select()
            ->toArray();

        // 往每个分类下填入数据
        foreach ($cate as $key => $value) {
            $i                          = 0;
            $cate[$key]['price']        = 0;  // 每个分类下消费券面额总计
            $cate[$key]['list']         = []; // 每个分类下的信息
            $cate[$key]['total']        = 0;  // 每个分类下多少个消费券
            $cate[$key]['total_count']  = 0;  // 每个分类下消费券发行量
            $cate[$key]['lingqu_count'] = 0;  // 每个分类下消费券实际领取量
            $cate[$key]['overstep']     = 0;  // 每个分类下消费券多领取数量【超卖】
            $cate[$key]['hexiao_count'] = 0;  // 每个分类下消费券核销数量
            foreach ($list as $kk => $vv) {
                if ($vv['class_cid'] == $value['id']) {
                    $cate[$key]['list'][]       = $vv;
                    $cate[$key]['total']        = $i + 1;
                    $cate[$key]['price']        += $vv['coupon_price'];
                    $cate[$key]['total_count']  += $vv['issue_total'];
                    $cate[$key]['lingqu_count'] += $vv['receive_total'];
                    $cate[$key]['overstep']     += $vv['overstep'];
                    $cate[$key]['hexiao_count'] += $vv['writeoff_total'];
                    $i++;
                }
            }
        }

        //$list = $this->array_group_by($list,'cid');
        //print_r($cate);die;
        View::assign('cate', $cate);
        return View::fetch();
    }

    /**
     * [coupon_pread 消费券领取数量统计]
     * @return   [type]            [消费券领取数量统计]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-07-14
     * @LastTime 2023-07-14
     * @version  [1.0.0]
     */
    public function seller_number()
    {
        if (Request::isGet()) {
            return View::fetch();
        } else {
            $param = Request::post();
            $where = [
                ['wo.status', '=', 1]
            ];
            $xdata = [];
            if (isset($param['cid']) && !empty($param['cid'])) {
                $where[] = ['s.class_id', '=', $param['cid']];
            }
            if (isset($param['daterange']) && !empty($param['daterange'])) {
                $daterange  = explode(" - ", trim($param['daterange']));
                $start_date = trim($daterange[0]);
                $end_date   = trim($daterange[1]);
                if ($start_date == $end_date) {
                    //一天的数据
                    for ($i = 0; $i < 24; $i++) {
                        $xdata[] = ['label' => $i > 9 ? ($i . ':00') : ('0' . $i . ':00'), 'egt' => (strtotime($start_date) + $i * 3600), 'elt' => (strtotime($start_date) + $i * 3600 + 3600)];
                    }
                } else {
                    $current_time = strtotime($start_date);
                    while ($current_time <= strtotime($end_date)) {
                        $xdata[]      = ['label' => date('m-d', $current_time), 'egt' => $current_time, 'elt' => $current_time + 86399];
                        $current_time = strtotime('+1 day', $current_time);
                    }
                }
            }
            $seller_data      = [];
            $seller_name_data = [];
            $final_data       = [];
            foreach ($xdata as $index => $item) {
                $final_where = array_merge($where, [['wo.create_time', '>=', $item['egt']], ['wo.create_time', '<=', $item['elt']]]);
                $list        = Db::name('write_off')->field("COUNT(*) AS count,s.nickname as name,SUM(coupon_price) as price,wo.mid as mid")->alias("wo")->leftJoin("seller s", "wo.mid = s.id")->where($final_where)->group("wo.mid")->select()->toArray();
                foreach ($list as $k => $v) {
                    if (isset($seller_data[$v['mid']])) {
                        $seller_data[$v['mid']][$item['label']] = $v;
                    } else {
                        $seller_data[$v['mid']]                 = [];
                        $seller_data[$v['mid']][$item['label']] = $v;
                    }
                    if (!isset($seller_name_data[$v['mid']])) {
                        $seller_name_data[$v['mid']] = $v['name'];
                    }
                }
            }
            $final_where = array_merge($where, [['wo.create_time', '>=', $item['egt']], ['wo.create_time', '<=', $item['elt']]]);
            $list        = Db::name('write_off')->field("COUNT(*) AS count,s.nickname as name,SUM(coupon_price) as price,wo.mid as mid")->alias("wo")->leftJoin("seller s", "wo.mid = s.id")->where($final_where)->group("wo.mid")->select()->toArray();
            foreach ($list as $k => $v) {
                if (isset($seller_data[$v['mid']])) {
                    $seller_data[$v['mid']][$item['label']] = $v;
                } else {
                    $seller_data[$v['mid']]                 = [];
                    $seller_data[$v['mid']][$item['label']] = $v;
                }
                if (!isset($seller_name_data[$v['mid']])) {
                    $seller_name_data[$v['mid']] = $v['name'];
                }
            }
            $index = 0;
            foreach ($seller_data as $key => $val) {
                $final_data[$index]         = [];
                $final_data[$index]['name'] = isset($seller_name_data[$key]) ? $seller_name_data[$key] : '-';
                $final_data[$index]['data'] = [];
                foreach ($xdata as $k => $v) {
                    if (!isset($val[$v['label']])) {
                        $final_data[$index]['data'][$k] = ['count' => 0, 'price' => 0];
                    } else {
                        $final_data[$index]['data'][$k] = ['count' => $val[$v['label']]['count'], 'price' => $val[$v['label']]['price']];
                    }
                }
                $index++;
            }
            $this->result(['xdata' => $xdata, 'line_data' => $final_data]);
        }
    }

    /**
     * [realtime_alert 发放金额实时预警]
     * @return   [type]            [发放金额实时预警]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-07-14
     * @LastTime 2023-07-14
     * @version  [1.0.0]
     */
    public function realtime_alert()
    {
        // 散客核销总金额
        $singleCustomerPrice = \app\common\model\WriteOff::sum('coupon_price');
        // 团体券金额统计
        /*$sql = "SELECT
                SUM(t.numbers * i.coupon_price) AS total_price
            FROM
                tp_tour_coupon_group AS g
                LEFT JOIN tp_tour AS t ON g.tid = t.id
                LEFT JOIN tp_coupon_issue AS i ON i.id = g.coupon_issue_id
            WHERE t.`status` != 6";
        $tourCustomerPrice = Db::query($sql);*/

        $tourCustomerPrice = Db::table('tp_tour_coupon_group')
        ->alias('g')
        ->leftJoin('tour t', 'g.tid = t.id')
        ->leftJoin('coupon_issue i', 'i.id = g.coupon_issue_id')
        ->where('t.status', '<>', 6)
        ->fieldRaw('SUM(t.numbers * i.coupon_price) as total')->find();
        
        $total = bcadd($singleCustomerPrice, $tourCustomerPrice['total'],2);
        View::assign('total',['sing'=>$singleCustomerPrice,'tour'=>$tourCustomerPrice['total'],'total'=>$total]);
        return View::fetch();
    }

    /**
     * [coupon_pread 消费券领取分布图]
     * @return   [type]            [消费券领取分布图]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-07-14
     * @LastTime 2023-07-14
     * @version  [1.0.0]
     */
    public function coupon_pread()
    {
        $param = Request::param();

        $issue_coupon_id = ($param['issue_coupon_id'] > 0) ? $param['issue_coupon_id'] : 27;

        $sql    = "SELECT a.uid,a.longitude,a.latitude,a.ips,a.status,b.`name`,b.mobile,b.idcard,b.auth_status 
                FROM tp_coupon_issue_user as a 
                LEFT JOIN tp_users as b on a.uid = b.id 
                WHERE a.issue_coupon_id = " . $issue_coupon_id;
        $reslut = Db::query($sql);

        View::assign('info', json_encode($reslut));
        return View::fetch();
    }

    /**
     * [tourist 商户门票销售情况]
     * @return   [type]            [商户门票销售情况]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-08-18
     * @LastTime 2023-08-18
     * @version  [1.0.0]
     */
    public function ticket_sales() {
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $where = [];
            // 门票名称
            if (isset($param['title'])  && $param['title']!='') { 
                $where[] = ['t.title','like','%'.$param['title'].'%'];
            }
            // 商户名称
            if (!empty($param['nickname'])) {
                $where[] = ['s.nickname', 'like', '%' . $param['nickname'] . '%'];
            }

            // 获取日期范围:默认最近30天
            if (!empty($param['create_time'])) {
                $getDateran = get_dateran($param['create_time']);
                $where[]    = ['tod.create_time', 'between', $getDateran];
            }else{
                $startDate = date('Y-m-d', strtotime("-29 days"));
                $endDate   = date('Y-m-d');
                $where[]   = ['tod.create_time','between',[strtotime($startDate),strtotime($endDate)]];
            }

            $pageSize = $this->pageSize; // 每页展示数量
            $page = $param['page']; // 当前页，默认为第一页

            $query = Db::name('ticket')
            ->alias('t')
            ->join('ticket_order_detail tod', 't.id = tod.ticket_id')
            ->leftJoin('ticket_order ord', 'ord.trade_no = tod.trade_no')
            ->field('s.nickname,s.id as mid,t.id, t.title,tod.ticket_price, SUM(tod.ticket_number) AS total_quantity, SUM(tod.ticket_number * tod.ticket_price) AS total_sales,SUM(ord.amount_price) AS amount_price')
            ->join('seller s','s.id = t.seller_id')
            ->where($where)
            ->group('s.id,t.id')
            ->order('s.id,t.id');

            $result = $query->paginate($pageSize, false, ['page' => $page]);

            $data = $result->items();

            return [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->listRows(),
                'total' => $result->total(),
                'data' => $data,
            ];
        }
        return View::fetch('issue/ticket_sales');
    }

    /**
     * [tourist 单商户门票销售统计情况]
     * @return   [type]            [单商户门票销售统计情况]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-08-18
     * @LastTime 2023-08-18
     * @version  [1.0.0]
     */
    public function single_sales($mid)
    {
        if(Request::param('getList') == 1){
            $param = Request::param();
            
            $where = [];
            $map   = [];
            if(!empty($param['ticket_id'])){
                $where[] = ['t.id', '=', $param['ticket_id']];
                $map[]   = ['id','=',$param['ticket_id']];
            }

            // 获取该商户所有的门票
            $ticketList = \app\common\model\Ticket::where('seller_id',$param['mid'])->where($map)->where('status',1)->select();

            // 读取当前商家的核验人员
            $where[] = ['t.seller_id','=',$param['mid']];

            // 获取日期范围:默认最近7天
            $startDate = isset($param['start_date']) ? $param['start_date'] : date('Y-m-d', strtotime("-6 days"));
            $endDate = isset($param['end_date']) ? $param['end_date']  : date('Y-m-d');

            $where[] = ['tod.create_time','between',[strtotime($startDate),strtotime($endDate)]];

            $data = Db::name('ticket')
                ->alias('t')
                ->join('ticket_order_detail tod', 't.id = tod.ticket_id')
                ->leftJoin('ticket_order ord', 'ord.trade_no = tod.trade_no')
                ->field('t.id, t.title, DATE(FROM_UNIXTIME(ord.create_time)) AS date, tod.ticket_price, 
                          SUM(tod.ticket_number) AS total_quantity, SUM(tod.ticket_number * tod.ticket_price) AS total_sales')
                ->where($where)
                ->whereNotNull('ord.create_time')
                ->group('DATE(FROM_UNIXTIME(ord.create_time))')
                ->select();

            // 构建日期范围数组
            $dateRange = [];
            $currentDate = $startDate;
            while ($currentDate <= $endDate) {
                $dateRange[] = $currentDate;
                $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
            }

            // 构建返回数据格式
            $returnData = [];
            foreach ($dateRange as $date) {
                $returnData[$date] = [
                    'ref_date' => $date,
                    'total_quantity' => 0,
                    'total_sales' => '0.00',
                    'ticket_number' => 0,
                    'list' => [],
                ];
            }

            // 将数据集赋值给日期
            foreach ($data as $item) {
                $returnData[$item['date']]['total_quantity'] = $item['total_quantity'];
                $returnData[$item['date']]['total_sales']    = number_format($item['total_sales'], 2);
                $returnData[$item['date']]['ticket_number'] += 1;
                $returnData[$item['date']]['list'][$item['id']] = [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'total_quantity' => $item['total_quantity'],
                    'total_sales' => number_format($item['total_sales'], 2),
                ];
            }

            // 数据补全：检查每一天是否有数据，如果没有则初始化为0
            foreach ($returnData as $date => $value) {
                foreach ($ticketList as $ticket) {
                    if (!isset($value['list'][$ticket['id']])) {
                        $returnData[$date]['list'][$ticket['id']] = [
                            'id' => $ticket['id'],
                            'title' => $ticket['title'],
                            'total_quantity' => 0,
                            'total_sales' => '0.00',
                        ];
                    }
                }
            }

            // 按照日期排序
            ksort($returnData);

            // 构建最终返回结果
            $result = [];
            foreach ($returnData as $date => $value) {
                $value['list'] = array_values($value['list']); // 重新索引 "list" 数组的键值
                $result[] = $value;
            }

            return json($result);
        }
        // 获取该商户所有的门票
        $ticketList = \app\common\model\Ticket::where('seller_id',$mid)->where('status',1)->select();
        return View::fetch('issue/single_sales',['list'=>$ticketList,'mid'=>$mid]);
    }

    /**
     * [tourist 商户门票订单游客统计]
     * @return   [type]            [商户门票订单游客统计]
     * @api      {method}path
     * @Author   slomoo@aliyun.com
     * @DateTime 2023-08-14
     * @LastTime 2023-08-14
     * @version  [1.0.0]
     */
    public function ticket_tourist() {
        if(Request::param('getList') == 1){
            $param = Request::param();
            $where = [];
            if (!empty($param['name'])) {
                $where[] = ['t.tourist_fullname', 'like', '%' . $param['name'] . '%'];
            }

            $pageSize = $this->pageSize; // 每页展示数量
            $page = $param['page']; // 当前页，默认为第一页

            $query = Db::name('ticket_order_detail')
            ->alias('t')
            ->field('u.uuid,t.tourist_fullname,t.tourist_cert_type,t.tourist_cert_id,t.tourist_mobile,u.name,u.mobile as umobile')
            ->join('ticket_user u','u.uuid = t.uuid')
            ->join('ticket_order ord','ord.trade_no = t.trade_no')
            ->where($where)
            ->group('t.uuid');

            $result = $query->paginate($pageSize, false, ['page' => $page]);

            $data = $result->items();
            return [
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->listRows(),
                'total' => $result->total(),
                'data' => $data,
            ];
        }
        return View::fetch('issue/ticket_tourist');
    }
}

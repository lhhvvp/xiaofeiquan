<?php
/**
 * 阶段性结算对账单控制器
 * @author slomoo <1103398780@qq.com> 2022/09/01
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\Db;
use think\facade\View;
use think\facade\Session;

class VerifyAccountingRecord extends Base
{
    // 验证器
    protected $validate = 'VerifyAccountingRecord';

    // 当前主表
    protected $tableName = 'verify_accounting_record';

    // 当前主模型
    protected $modelName = 'VerifyAccountingRecord';

    // 列表
    public function index()
    {
        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? $pk;
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            
            if (@$param['no']!='') {
                $where[] = ['no','=',$param['no']];
            }
            if (@$param['project_name']!='') {
                $where[] = ['project_name','=',$param['project_name']];
            }
            $model  = '\app\common\model\\' . $this->modelName;
            return $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);
        }
        return View::fetch();
    }

    // 2022-09-01 生成阶段性结算记录 数据冗余
    public function addPost()
    {
        if (Request::isPost()) {
            $data = MakeBuilder::changeFormData(Request::except(['file'], 'post'), $this->tableName);
            // 结算基础数据规划
            $data['no'] = 'JDXOR'.date("ymdH",time()).strtoupper(substr(md5(uniqid()),0,8));
            $cycle      = explode(' - ',$data['cycle']);
            $data['cycle_start'] = strtotime($cycle[0]);
            $data['cycle_end']   = strtotime($cycle[1]);

            // 根据日期范围检索普通商户结算记录：运营审核通过、文旅审核通过
            $accounting = \app\common\model\Accounting::where('status',1)
            ->where('tour_status',1)
            ->whereBetweenTime('create_time', $cycle[0], $cycle[1])
            ->with(['seller','auditRecord'])
            ->select();
            // 根据日期范围检索旅行社结算记录：运营审核通过、文旅审核通过
            $tour_accounting = \app\common\model\TourAccounting::where('status',1)
            ->where('tour_status',1)
            ->whereBetweenTime('create_time', $cycle[0], $cycle[1])
            ->with(['seller','tourAuditRecord'])
            ->select();

            // 如果两个结算记录都未查到数据 返回异常
            if(!$accounting->toArray() && !$tour_accounting->toArray()){
                $this->error('未检索到有效数据，请重新选择结算日期');
            }

            // print_r($accounting->toArray());die;

            $model = '\app\common\model\\' . $this->modelName;
            // 事务操作
            Db::startTrans();
            try {
                // 创建阶段性结算记录 并返回当前基础数据
                $result = $model::create($data);
                // 结算汇总记录数据规划
                // 旅行社数据
                // 统计旅行社下所有游客数量
                $tidsStr = ''; // 团ID字符串
                $tourist_numbers = 0; // 所有团下游客总数
                $collect = [];
                if($tour_accounting->toArray()){
                    foreach ($tour_accounting->toArray() as $key => $value) {
                        $tidArr = explode(',',$value['write_off_ids']);
                        $tidsStr .= $value['write_off_ids'].',';
                        $collect[$key]['vid']           = $result['id'];
                        $collect[$key]['cycle']         = $data['cycle'];
                        $collect[$key]['class_id']      = $value['class_id'];
                        $collect[$key]['class_name']    = $value['nickname'];  // 商家名称
                        $collect[$key]['mid']           = $value['mid'];
                        $collect[$key]['seller_no']     = $value['seller']['no'];
                        $collect[$key]['name']          = $value['seller']['name'];
                        $collect[$key]['mobile']        = $value['seller']['mobile'];
                        $collect[$key]['writeoff_total'] = \app\common\model\Tourist::where('tid','in',$tidArr)->count();
                        $collect[$key]['sum_coupon_price'] = $value['sum_coupon_price'];
                        $collect[$key]['card_name']         = $value['card_name'];
                        $collect[$key]['card_deposit']      = $value['card_deposit'];
                        $collect[$key]['cart_number']       = $value['cart_number'];
                        $collect[$key]['accounting_create_time'] = strtotime($value['create_time']);
                        $collect[$key]['accounting_no']     = $value['no'];
                        $collect[$key]['accounting_data_detail'] = $value['data_url'];
                        $collect[$key]['group_id']      = $value['tourAuditRecord']['group_id'];
                        $collect[$key]['admin_id']      = $value['tourAuditRecord']['admin_id'];
                        $collect[$key]['audit_time']    = strtotime($value['tourAuditRecord']['create_time']);
                    }
                }
                $tidsStr = substr($tidsStr,0,strlen($tidsStr)-1);
                $tidsStr = explode(',',$tidsStr);
                // 统计团下所有游客
                $tourist_numbers = \app\common\model\tourist::where('tid','in',$tidsStr)->count();

                $collect_acc = [];
                if($accounting->toArray()){
                    foreach ($accounting as $key => $value) {
                        $collect_acc[$key]['vid']           = $result['id'];
                        $collect_acc[$key]['cycle']         = $data['cycle'];
                        $collect_acc[$key]['class_id']      = $value['class_id'];
                        $collect_acc[$key]['class_name']    = $value['nickname'];  // 商家名称
                        $collect_acc[$key]['mid']           = $value['mid'];
                        $collect_acc[$key]['seller_no']     = $value['seller']['no'];
                        $collect_acc[$key]['name']          = $value['seller']['name'];
                        $collect_acc[$key]['mobile']        = $value['seller']['mobile'];
                        $collect_acc[$key]['writeoff_total'] = $value['writeoff_total'];
                        $collect_acc[$key]['sum_coupon_price'] = $value['sum_coupon_price'];
                        $collect_acc[$key]['card_name']         = $value['card_name'];
                        $collect_acc[$key]['card_deposit']      = $value['card_deposit'];
                        $collect_acc[$key]['cart_number']       = $value['cart_number'];
                        $collect_acc[$key]['accounting_create_time'] = strtotime($value['create_time']);
                        $collect_acc[$key]['accounting_no']     = $value['no'];
                        $collect_acc[$key]['accounting_data_detail'] = $value['data_url'];
                        $collect_acc[$key]['group_id']      = $value['auditRecord']['group_id'];
                        $collect_acc[$key]['admin_id']      = $value['auditRecord']['admin_id'];
                        $collect_acc[$key]['audit_time']    = strtotime($value['auditRecord']['create_time']);
                    }
                }
                $newArr = array_merge($collect,$collect_acc);
                // 统计渠道数量=商家数量: 根据商户ID去重
                // 
                $upRecord = [];
                $upRecord['sum_total_price'] = 0;
                $upRecord['seller_numbers']  = 0;
                $upRecord['tourist_numbers'] = 0 + $tourist_numbers; // 游客数量 = 景区核销记录数 + 旅行团下游客数量
                $mInfo = array();
                foreach ($newArr as $value) {
                    // 计算总结算金额
                    $upRecord['sum_total_price'] += $value['sum_coupon_price'];
                    // 计算景区游客数量
                    if($value['class_id']!=3){
                        $upRecord['tourist_numbers'] += $value['writeoff_total'];
                    }
                    // 检查重复 并剔除
                    if(isset($mInfo[$value['mid']])) unset($value['mid']);
                    else $mInfo[$value['mid']] = $value;
                }
                $upRecord['seller_numbers'] = count($mInfo);
                // 操作人
                $upRecord['admin_id'] = Session::get('admin.id');
                // 修改对账单信息
                Db::name('VerifyAccountingRecord')->where('id',$result['id'])->data($upRecord)->update();
                // 添加汇总记录
                Db::name('VerifyCollect')->insertAll($newArr);  
                // 提交事务
                Db::commit();    
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success('查询成功','index');
        }
    }

    // 下载PDF
    public function downloadpdf($id)
    {
        // 数据查询
        $vInfo = \app\common\model\VerifyAccountingRecord::where('id',$id)->find();
        //print_r($vInfo->toArray());die;
        $page_1 = '<style type="text/css">
            <!--
            .jsxx {font-size: 18px;}
            .xxxx {font-size: 12px;}
            .title{font-size: 14px;}
            .jsxxs {font-size: 16px;}
            -->
            </style>
            <p>&nbsp;</p>
            <p>&nbsp;</p>
            <p>&nbsp;</p>
            <table width="485" height="402" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td width="485" height="402" align="center" style="font-size: 30px; font-weight:bold;">
                <p>&nbsp;</p>
                <p>清爽榆林消费券阶段性结算对账单</p></td>
              </tr>
            </table>
            <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
              <tr>
                <td width="130" height="35" align="right"><p >项目名称：</p>    </td>
                <td><p >'.$vInfo['project_name'].'</p></td>
              </tr>
              <tr>
                <td height="35" align="right" ><p >结算周期：</p>    </td>
                <td>'.$vInfo['cycle'].'</td>
              </tr>
              <tr>
                <td height="35" align="right"><p >结算单号：</p>    </td>
                <td>'.$vInfo['no'].'</td>
              </tr>
              <tr>
                <td height="35" align="right"><p >结算金额：</p>    </td>
                <td>'.$vInfo['sum_total_price'].' 元</td>
              </tr>
              <tr>
                <td height="35" align="right"><p >渠道数量：</p>    </td>
                <td>'.$vInfo['seller_numbers'].' 家</td>
              </tr>
              <tr>
                <td height="35" align="right">游客数量：</td>
                <td>'.$vInfo['tourist_numbers'].' 人</td>
              </tr>
            </table>
            <table width="325" height="180" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td height="180" align="center" style="font-size: 18px; font-weight:bold;"><p>&nbsp;</p>
                <p>&nbsp;</p></td>
              </tr>
            </table>
            <table width="560" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxx">
              <tr>
                <td width="560" height="35" align="right"><p >提报单位（盖章）：榆林市旅游投资发展有限公司</p>    </td>
              </tr>
              <tr>
                <td height="35" align="right" ><p >日期：'.date("Y年m月d日",time()).'</p></td>
              </tr>
            </table>
            <p>&nbsp;</p>
            ';
            $page_2 = '';
            // 获取结算汇总清单
            $cInfo = \app\common\model\VerifyCollect::where('vid',$id)->with(['sellerClass','admin'])->select();
            //print_r($cInfo->toArray());die;

            $page_2 .= '            <p class="title">附件1：结算汇总清单</p>
            <table width="800" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#999999" class="xxxx">
              <tr>
                <td width="50" height="25" align="center">序号</td>
                <td width="60" align="center">类型</td>
                <td align="center">商户名称</td>
                <td width="100" align="center">商户编号</td>
                <td width="60" align="center">联系人</td>
                <td width="90" align="center">联系电话</td>
                <td width="70" align="center">核销数量</td>
                <td width="80" align="center">结算金额</td>
              </tr>';
              $newArr = [];
              $writeoff_total = 0;
              foreach ($cInfo->toArray() as $key => $value) {
                if(isset($newArr[$value['class_id']])){
                    if(isset($newArr[$value['class_id']]['list'][$value['mid']])){
                        $newArr[$value['class_id']]['list'][$value['mid']]['writeoff_total'] += $value['writeoff_total'];
                        $newArr[$value['class_id']]['list'][$value['mid']]['sum_coupon_price'] += $value['sum_coupon_price'];
                    }else{
                        $newArr[$value['class_id']]['class_id']   = $value['sellerClass']['id'];
                        $newArr[$value['class_id']]['class_name'] = $value['sellerClass']['class_name'];
                        $newArr[$value['class_id']]['list'][$value['mid']]['nickname']     = $value['class_name'];
                        $newArr[$value['class_id']]['list'][$value['mid']]['seller_no']    = $value['seller_no'];
                        $newArr[$value['class_id']]['list'][$value['mid']]['name']         = $value['name'];
                        $newArr[$value['class_id']]['list'][$value['mid']]['mobile']       = $value['mobile'];
                        $newArr[$value['class_id']]['list'][$value['mid']]['writeoff_total'] = $value['writeoff_total'];
                        $newArr[$value['class_id']]['list'][$value['mid']]['sum_coupon_price'] = $value['sum_coupon_price'];
                    }
                    unset($value['class_id']);
                } else {
                    $newArr[$value['class_id']]['class_id']   = $value['sellerClass']['id'];
                    $newArr[$value['class_id']]['class_name'] = $value['sellerClass']['class_name'];
                    $newArr[$value['class_id']]['list'][$value['mid']]['nickname']     = $value['class_name'];
                    $newArr[$value['class_id']]['list'][$value['mid']]['seller_no']    = $value['seller_no'];
                    $newArr[$value['class_id']]['list'][$value['mid']]['name']         = $value['name'];
                    $newArr[$value['class_id']]['list'][$value['mid']]['mobile']       = $value['mobile'];
                    $newArr[$value['class_id']]['list'][$value['mid']]['writeoff_total'] = $value['writeoff_total'];
                    $newArr[$value['class_id']]['list'][$value['mid']]['sum_coupon_price'] = $value['sum_coupon_price'];
                }
              }
              $j = 1;
              $writeoff_numbers = 0;
              $writeoff_prices  = 0;
              foreach ($newArr as $k => $v) {
                    $i = 1;
                    foreach ($v['list'] as $key => $value) {
                        $page_2 .='<tr>
                        <td height="25" align="center">'.$j.'</td>';
                        if($i==1){
                            $page_2 .= '<td rowspan="'.count($v['list']).'" align="center">'.$v['class_name'].'</td>';
                        }
                        $page_2 .='
                        <td align="center">'.$value['nickname'].'</td>
                        <td align="center">'.$value['seller_no'].'</td>
                        <td align="center">'.$value['name'].'</td>
                        <td align="center">'.$value['mobile'].'</td>
                        <td align="center">'.$value['writeoff_total'].'</td>
                        <td align="center">'.$value['sum_coupon_price'].'</td>
                      </tr>';
                       $i++;
                       $j++;
                       $writeoff_numbers += $value['writeoff_total'];
                       $writeoff_prices  += $value['sum_coupon_price'];
                    }
                }
              $page_2 .='<tr>
                <td height="25" colspan="6" align="center">合计</td>
                <td align="center">'.$writeoff_numbers.'</td>
                <td align="center">'.$writeoff_prices.'</td>
              </tr>
            </table>
            <p>&nbsp;</p>
            <table width="800" height="137" border="0" align="center" cellpadding="0" cellspacing="0">
              <tr>
                <td width="265" height="95" align="center" valign="bottom" style="font-size: 16px;">
                    <p>榆林市财政局</p></td>
                <td width="265" align="center" valign="bottom" style="font-size: 16px;">榆林市文化和旅游局</td>
                <td align="center" valign="bottom" style="font-size: 16px;">榆林市旅游投资发展有限公司</td>
              </tr>
              <tr>
                <td height="32" align="center" style="font-size: 16px;">（签字盖章）</td>
                <td height="32" align="center" style="font-size: 16px;">（签字盖章）</td>
                <td height="32" align="center" style="font-size: 16px;">（签字盖章）</td>
              </tr>
            </table>
            <p>&nbsp;</p>
            <p>&nbsp;</p>
            <p>&nbsp;</p>
            <p class="title">&nbsp;</p>
            <p class="title">&nbsp;</p>
            <p class="title">&nbsp;</p>
            <p class="title">&nbsp;</p>
            <p class="title">&nbsp;</p>
            <p class="title">&nbsp;</p>
            <p class="title">&nbsp;</p>
            <p class="title">&nbsp;</p>
            <p class="title">&nbsp;</p>
            <p class="title">&nbsp;</p>
            <p class="title">&nbsp;</p>
            <p class="title">&nbsp;</p>
            <p class="title">&nbsp;</p>
            <p class="title">&nbsp;</p>
             <p class="title">&nbsp;</p>           

            ';
            $page_3 = '';
            $page_3 .= '            <p class="title">附件2：各商户结算清单</p>';

            $pageArr = [];
            foreach ($cInfo->toArray() as $key => $value) {
                if(isset($pageArr[$value['mid']])){
                    $pageArr[$value['mid']]['list'][$key]['nickname']     = $value['class_name'];
                    $pageArr[$value['mid']]['list'][$key]['accounting_no']    = $value['accounting_no'];
                    $pageArr[$value['mid']]['list'][$key]['name']         = $value['name'];
                    $pageArr[$value['mid']]['list'][$key]['admin_name']   = $value['admin']['nickname'];
                    $pageArr[$value['mid']]['list'][$key]['mobile']       = $value['mobile'];
                    $pageArr[$value['mid']]['list'][$key]['audit_time']       = $value['audit_time'];
                    $pageArr[$value['mid']]['list'][$key]['writeoff_total'] = $value['writeoff_total'];
                    $pageArr[$value['mid']]['list'][$key]['sum_coupon_price'] = $value['sum_coupon_price'];
                    $pageArr[$value['mid']]['list'][$key]['accounting_create_time'] = $value['accounting_create_time'];
                } else {
                    $pageArr[$value['mid']]['mid']   = $value['id'];
                    $pageArr[$value['mid']]['class_name'] = $value['class_name'];
                    $pageArr[$value['mid']]['cycle'] = $value['cycle'];
                    $pageArr[$value['mid']]['card_name'] = $value['card_name'];
                    $pageArr[$value['mid']]['card_deposit'] = $value['card_deposit'];
                    $pageArr[$value['mid']]['cart_number']  = $value['cart_number'];
                    $pageArr[$value['mid']]['list'][$key]['nickname']     = $value['class_name'];
                    $pageArr[$value['mid']]['list'][$key]['accounting_no']    = $value['accounting_no'];
                    $pageArr[$value['mid']]['list'][$key]['name']         = $value['name'];
                    $pageArr[$value['mid']]['list'][$key]['admin_name']   = $value['admin']['nickname'];
                    $pageArr[$value['mid']]['list'][$key]['audit_time']       = $value['audit_time'];
                    $pageArr[$value['mid']]['list'][$key]['mobile']       = $value['mobile'];
                    $pageArr[$value['mid']]['list'][$key]['writeoff_total'] = $value['writeoff_total'];
                    $pageArr[$value['mid']]['list'][$key]['sum_coupon_price'] = $value['sum_coupon_price'];
                    $pageArr[$value['mid']]['list'][$key]['accounting_create_time'] = $value['accounting_create_time'];
                }
              }
            foreach ($pageArr as $key => $value) {
                $page_3 .='<table width="790" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxxs">
                      <tr>
                        <td width="400" height="35" align="left">商户名称：'.$value['class_name'].'</td>
                        <td align="right">结算周期：'.$value['cycle'].'</td>
                      </tr>
                    </table><table width="800" border="1" align="center" cellpadding="0" cellspacing="0" bordercolor="#999999" class="xxxx">
                  <tr>
                    <td width="40" height="25" align="center">序号</td>
                    <td width="140" align="center">结算单号</td>
                    <td width="80" align="center">结算数量</td>
                    <td width="100" align="center">结算金额</td>
                    <td width="150" align="center">申请时间</td>
                    <td width="90" align="center">审核人</td>
                    <td width="150" align="center">审核时间</td>
                  </tr>';
                  $touris_page_numbers = 0;
                  $touris_page_prices  = 0;
                  $i = 0;
                  foreach ($value['list'] as $k => $v) {
                      $page_3 .='<tr>
                        <td height="25" align="center">'.($i+1).'</td>
                        <td align="center">'.$v['accounting_no'].'</td>
                        <td align="center">'.$v['writeoff_total'].'</td>
                        <td align="center">'.$v['sum_coupon_price'].'</td>
                        <td align="center">'.date("Y-m-d_H:i:s",$v['accounting_create_time']).'</td>
                        <td align="center">'.$v['admin_name'].'</td>
                        <td align="center">'.date("Y-m-d_Hi:s",$v['audit_time']).'</td>
                      </tr>';
                      $i++;
                      $touris_page_prices  += $v['writeoff_total'];
                      $touris_page_numbers += $v['sum_coupon_price'];
                  }
                  $page_3 .= '<tr>
                    <td colspan="7" align="center" width="800">
                    <table width="790" border="0" align="center" cellpadding="0" cellspacing="0" class="jsxxs">

                      <tr>
                        <td width="100" height="35" align="right">结算金额：</td>
                        <td width="300" align="left">'.$touris_page_numbers.'</td>
                        <td width="100" align="right">游客数量：</td>
                        <td width="300" align="left">'.$touris_page_prices.'</td>
                      </tr>
                      <tr>
                        <td height="35" align="right">账户名称：</td>
                        <td align="left">'.$value['card_name'].'</td>
                        <td align="right">账户号码：</td>
                        <td align="left">'.$value['cart_number'].'</td>
                      </tr>
                      <tr>
                        <td height="35" align="right">开户银行：</td>
                        <td colspan="3" align="left">'.$value['card_deposit'].'</td>
                      </tr>
                    </table></td>
                  </tr>
                </table>
                <p>&nbsp;</p>';
            }
            $html = $page_1.$page_2.$page_3;
            //echo $html;die;
            // 创建PDF文件
            $data_detail = downloadPdf($html,'清爽榆林消费券阶段性结算对账单_'.time().'.pdf');
    }
}

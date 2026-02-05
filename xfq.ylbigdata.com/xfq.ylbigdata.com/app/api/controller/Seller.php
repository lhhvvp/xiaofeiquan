<?php
/**
 * @desc   商家模块API
 * @author slomoo
 * @email slomoo@aliyun.com
 */
declare (strict_types = 1);
namespace app\api\controller;

use app\api\BaseController;
use app\api\middleware\Auth;
use app\api\service\JwtAuth;
use think\facade\Db;
use think\facade\Request;

class Seller extends BaseController
{
    /**
     * 控制器中间件 [账号登录、注册、小程序登录、注册 不需要鉴权]
     * @var array
     */
	protected $middleware = [
    	Auth::class => ['except' 	=> ['detail','cate','list'] ]
    ];

    /**
     * @api {post} /seller/cate 商家分类
     * @apiDescription  返回商家分类
     */
    public function cate()
    {
        $all[] = [
            'id' => 1,
            'class_name' => '全部',
        ];
        // 商家分类
        $seller_class = \app\common\model\SellerClass::where('status',1)->select()->toArray();
        $class['cate'] = array_merge($all,$seller_class);
        // 轮播
        $class['slide'] = \app\common\model\Slide::where('status',1)->where('tags','list')->select()->toArray();
        $this->apiSuccess('请求成功',$class);
    }

    /**
     * @api {post} /seller/list 根据分类ID查询对应的商家信息
     * @apiDescription  返回商家信息
     */
    public function list()
    {
        $param = get_params();
        if(empty($param['class_id'])){
            $this->apiError('请检查商户分类ID是否正确');
        }
        if(!isset($param['latitude']) || !isset($param['longitude'])){
            $this->apiError('参数异常');
        }

        if(!isset($param['page']) || !isset($param['limit'])){
            $this->apiError('参数异常');
        }

        $where = 'status = 1';
        if($param['class_id']!=1){
            $where .= ' and class_id = '.$param['class_id'];
        }

        $latitude = $param["latitude"];
        $longitude = $param["longitude"];
        $SQRT       = 'SQRT(
                                    POW( SIN( PI()*( '.$latitude.' - latitude )/ 360 ), 2 )+ COS( PI()* 29.504164 / 180 )* COS( '.$latitude.' * PI()/ 180 )* POW( SIN( PI()*( '.$longitude.' - longitude )/ 360 ), 2 ))';
        // 商家信息
        $sql = "SELECT
                    id,status,nickname,image,mobile,do_business_time,address,content,longitude,latitude,class_id,distance
                FROM
                    (
                    SELECT
                        *,
                        round((
                                2 * 6378.137 * ASIN($SQRT)) * 1000 
                        ) AS distance 
                    FROM
                        tp_seller
                    ) a 
                WHERE ".$where."
                ORDER BY
                    distance ASC limit ".$param['page'].",".$param['limit']."";
        $list = Db::query($sql);

        if(!empty($list)){
            foreach ($list as $key => $value) {
                // 2023-03-18 经纬度未获取到返回 0
                $list[$key]['distance'] = 0;
                if($latitude!=1){
                    $list[$key]['distance'] = $value['distance'] / 1000;
                }
            }
        }
        /*
        $where = [];
        if($param['class_id']!=1){
            $where[] = ['class_id','=',$param['class_id']];
        }
        $rows = empty($param['limit']) ? 10 : $param['limit'];
        $list = \app\common\model\Seller::where($where)
        -> paginate($rows, false, ['query' => $param])
        -> toArray();*/
        $this->apiSuccess('请求成功',$list);
    }

    /**
     * @api {post} /seller/detail 根据商家ID查询对应的商家信息
     * @apiDescription  返回商家详细信息
     */
    public function detail()
    {
        $param = get_params();
        if(empty($param['seller_id'])){
            $this->apiError('请检查商户ID是否正确');
        }
        if(!isset($param['latitude']) || !isset($param['longitude'])){
            $this->apiError('参数异常');
        }
        $latitude = $param["latitude"];
        $longitude = $param["longitude"];
        $SQRT       = 'SQRT(
                                    POW( SIN( PI()*( '.$latitude.' - latitude )/ 360 ), 2 )+ COS( PI()* 29.504164 / 180 )* COS( '.$latitude.' * PI()/ 180 )* POW( SIN( PI()*( '.$longitude.' - longitude )/ 360 ), 2 ))';
        // 商家信息
        $sql = "SELECT
                    id,status,nickname,image,mobile,do_business_time,address,content,longitude,latitude,class_id,distance,comment_rate,comment_num,appt_open,appt_limit
                FROM
                    (
                    SELECT 
                        *,
                        round((
                                2 * 6378.137 * ASIN($SQRT)) * 1000 
                        ) AS distance 
                    FROM
                        tp_seller
                    ) a 
                WHERE id = ".$param['seller_id']."
                ORDER BY
                    distance ASC";
        $list['detail'] = Db::query($sql);
        // 米转千米
        $list['detail'] = $list['detail'][0];
        // 2023-03-18 经纬度未获取到返回 0
        $list['detail']['distance'] = $latitude!=1 ? $list['detail']['distance'] / 1000 : 0;
        // 消费券信息
        //$class_id = [1=>2,2=>4,3=>3,4=>5,];
        //$class_id = [2=>1,3=>3,4=>2,5=>4,];
        $class_id_res = $list['detail']['class_id'];
        //$map = [['use_store','=', $class_id[$class_id_res]], ['use_stroe_id','=',0]];
        //$map = [['use_store','=', $class_id_res], ['use_stroe_id','=',0]];
        /*$list['coupon'] = \app\common\model\CouponIssue::whereOr(function($query) use ($map){
            $query->whereOr($map);
        })
        ->whereOr(['use_store' => 1])
        ->where('status',1)
        ->where('coupon_type','in',[1,2])
        ->whereFindInSet('use_stroe_id',$param['seller_id'])
        -> select();*/
        $coupon_sql = "SELECT * FROM `tp_coupon_issue` WHERE (( `use_store` = $class_id_res OR `use_stroe_id` = '0' ) 
                            OR `use_store` = 1 
                        ) 
                        AND `status` = 1 
                        AND `coupon_type` IN ( 1, 2 ) 
                        AND FIND_IN_SET(
                        '".$param['seller_id']."',
                        `use_stroe_id`)";
        $list['coupon'] = Db::query($coupon_sql);
        // 2023-05-31 收藏信息返回空字符串
        /*$uid = $param['uid'];
        // 收藏信息
        $list['detail']['collection'] = \app\common\model\Seller::field('id')
            -> with(['coll' => function ($query) use ($uid) {
                $query->where('uid', $uid);
            }])
            -> where('id',$param['seller_id'])
            -> find();*/
        $list['detail']['collection'] = '';
        // 分支机构
        $list['detail']['seller_child_node'] = \app\common\model\SellerChildNode::field('nickname,address,latitude,longitude,no,name,mobile,id')
            -> where('mid',$param['seller_id'])
            -> select();
        $this->apiSuccess('请求成功',$list);
    }

    /**
     * @api {post} /seller/search 搜索商家信息
     * @apiDescription  返回商家信息
     */
    public function search()
    {
        $param = get_params();
        if(!empty($param['nickname'])){
            $rows = empty($param['limit']) ? 10 : $param['limit'];
            $list = \app\common\model\Seller::field('id,status,nickname,image,mobile,do_business_time,address,content,longitude,latitude,class_id')->where('nickname','like','%' . $param['nickname'] . '%')
            -> paginate($rows, false, ['query' => $param])
            -> toArray();
            $this->apiSuccess('请求成功',$list);
        }
    }

    /**
     * @api {post} /seller/bindCheckOpenid 绑定核验人员
     * @apiDescription  返回商家信息
     */
    public function bindCheckOpenid(){
        if (!Request()->isPost()) {
            $this->apiError('禁止访问！');
        }
        $uid    = Request::param('uid/d',0); // 用户表主键ID
        $mid    = Request::param('mid/d',0); // 商户ID
        $openid = Request::param('openid'); // 小程序openid
        $uuid   = Request::param('uuid/d',0); // 核验表主键ID
        $mInfo  = \app\common\model\Seller::where('id',$mid)
            -> find();
        if(!$mInfo){
            $this->apiError('商户不存在');
        }
        if(!$openid){
            $this->apiError('参数错误');
        }
        if($mInfo['status']==0){
            $this->apiError('该商户已被禁用');
        }
        // 查询是否注册过
        $uInfo  = \app\common\model\Users::where('id',$uid)
            -> find();
        if(!$uInfo){
            $this->apiError('当前用户不存在');
        }
        if($uInfo['status']==0){
            $this->apiError('该用户已被禁用');
        }

        // 2022-08-25 一个用户只能绑定一个商家
        $mvInfo = \app\common\model\MerchantVerifier::where('openid',$openid)->find();
        if(!empty($mvInfo)){
            $this->apiError('您已绑定其他商家!');
        }

        $res = \app\common\model\MerchantVerifier::where(['id'=>$uuid])->where(['mid'=>$mid])->data(['openid'=>$openid,'uid'=>$uid,'update_time'=>time()])->update();
        $code = $res ? 1 : 0;

        $this->apiSuccess('请求成功',$code);
    }
}
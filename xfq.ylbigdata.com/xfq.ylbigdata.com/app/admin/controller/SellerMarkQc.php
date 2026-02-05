<?php
/**
 * 商户打卡管理控制器
 * @author xuemm 2024/01/15
 */
namespace app\admin\controller;

// 引入框架内置类
use think\facade\Db;
use think\facade\Request;

// 引入表格和表单构建器
use app\common\facade\MakeBuilder;
use app\common\builder\FormBuilder;
use app\common\builder\TableBuilder;
use think\facade\Session;
use think\facade\View;
use Overtrue\Pinyin\Pinyin;
class SellerMarkQc extends Base
{
    // 验证器
    protected $validate = 'SellerMarkQc';

    // 当前主表
    protected $tableName = 'seller_mark_qc';

    // 当前主模型
    protected $modelName = 'SellerMarkQc';

    // 列表
    public function index()
    {
        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->order('sort asc')
            ->select()
            ->toArray();
        View::assign(['class_list' => $SellerClass]);
        $sellerClassArr = array_column($SellerClass, 'class_name', 'id');

        // 获取主键
        $pk = MakeBuilder::getPrimarykey($this->tableName);
        // 搜索
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? $pk;
            $isAsc         = $param['isAsc'] ?? 'desc';
            $where = [];
            if (@$param['class_id']!='') {
                $where[] = ['Seller.class_id','=',$param['class_id']];
            }
            if (@$param['nickname']!='') {
                $where[] = ['Seller.nickname','like',"%".$param['nickname']."%"];
            }
            $model  = '\app\common\model\\' . $this->modelName;
            $list = $model::getRewriteList($where, $this->pageSize, [$orderByColumn => $isAsc]);

            //关联表赋值
            for ($i=0; $i < count($list['data']); $i++) {
                @$list['data'][$i]['nickname'] = $list['data'][$i]['Seller']['nickname'];
                @$list['data'][$i]['class_name'] = $sellerClassArr[$list['data'][$i]['Seller']['class_id']];
            }

            return $list;
        }

        return View::fetch('seller_mark_qc/index');
    }

    // 添加商户二维码
    public function addSellerMarkQc()
    {
        // 商户
        $SellerList = \app\common\model\Seller::field('id, class_id, nickname')
            ->where('status',1)
            ->where('class_id','<>', 7)
            ->where('nickname','<>', '')
            ->order('class_id asc,create_time desc')
            ->select()
            ->toArray();

        //商户类型
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->order('sort asc')
            ->select()
            ->toArray();
        $sellerClassArr = array_column($SellerClass, 'class_name', 'id');
        View::assign(['sellerClassArr' => $sellerClassArr,'seller_list' => $SellerList]);
        return View::fetch('seller_mark_qc/addSellerMarkQc');
    }

    // 编辑商户二维码
    public function editSellerMarkQc()
    {
        $param = Request::param();

        // 查询商户二维码详情
        $SellerMarkQc = \app\common\model\SellerMarkQc::where('id',$param['id'])
            ->order('create_time desc')
            ->find();
        View::assign(['sellerMarkQc' => $SellerMarkQc]);

        // 商户
        $SellerList = \app\common\model\Seller::field('id, class_id, nickname')
            ->where('nickname','<>', '')
            ->order('class_id asc,create_time desc')
            ->select()
            ->toArray();

        //商户类型
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->order('sort asc')
            ->select()
            ->toArray();
        $sellerClassArr = array_column($SellerClass, 'class_name', 'id');
        View::assign(['sellerClassArr' => $sellerClassArr,'seller_list' => $SellerList]);

        return View::fetch('seller_mark_qc/editSellerMarkQc');
    }

    // 添加保存
    public function addPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');

            // xss过滤
            $data['seller_id']           = removeXSS(filterText($data['seller_id']));
            $data['day_threshold_value'] = removeXSS(filterText($data['day_threshold_value']));

            $result = $this->validate($data,'SellerMarkQc');
            if (true !== $result) {
                $this->error($result);
            }

            $model = '\app\common\model\\' . $this->modelName;

            // 检查数据是否存在
            $uname = $model::where(['seller_id' => $data['seller_id']])->find();
            if($uname){
                $this->error('商户打卡二维码已存在');
            }

            $data['code_time_expire'] = time();

            //查询商家信息
            $sellerInfo = \app\common\model\Seller::where('id', $data['seller_id'])->find();

            $qrInfoArr = self::base64EncodeImage($data['seller_id'], $sellerInfo['class_id'], $data['code_time_expire']);
            $data['qrcode_url']  = $qrInfoArr['data_code'];
            $data['qrcode_base'] = $qrInfoArr['base64_image'];

            $result = $model::addPost($data);
            if ($result['error']) {
                $this->error($result['msg']);
            } else {
                $this->success($result['msg'], 'index');
            }
        }
    }

    //处理
    public function base64EncodeImage($seller_id, $class_id, $code_time_expire) {
        // 二维码生成唯一code 规则：商家id_商家分类首字母_时间戳
        $class_id_name = [2=>'JQ',3=>'LXS',4=>'JY',5=>'YY', 6=>'JD', 7=>'GYS']; // 商户分类
        $dataCode   = symencryption($seller_id . '_' . $class_id_name[$class_id] . "_" . $code_time_expire, 'cyylewmjjmcode');//数据加密

        $qrcodeFilePath = Qrcode($dataCode);//生成二维码,返回文件路径

        $image_path = app()->getRootPath() . 'public' . $qrcodeFilePath;//图片在项目中的全路径

        //生成图片base64编码
        $image_info = getimagesize($image_path);
        $image_data = fread(fopen($image_path, 'r'), filesize($image_path));
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));

        unlink($image_path);//删除生成的二维码

        return [
            'data_code' => $dataCode,
            'base64_image' => $base64_image,
        ];
    }

    // 编辑保存
    public function editPost()
    {
        if (Request::isPost()) {
            $data = Request::except(['file'], 'post');

            // xss过滤
            $data['day_threshold_value'] = removeXSS(filterText($data['day_threshold_value']));
            $result = $this->validate($data,'SellerMarkQc');
            if (true !== $result) {
                $this->error($result);
            }
            
            $data['code_time_expire'] = time();
            //查询商家信息
            $sellerInfo = \app\common\model\Seller::where('id', $data['seller_id'])->find();
            $qrInfoArr = self::base64EncodeImage($data['seller_id'], $sellerInfo['class_id'], $data['code_time_expire']);
            $data['qrcode_url']  = $qrInfoArr['data_code'];
            $data['qrcode_base'] = $qrInfoArr['base64_image'];

            $where['id'] = $data['id'];
            \app\common\model\SellerMarkQc::update($data, $where);
            $this->success('修改成功!', 'index');
        }
    }

    // 查看商户二维码详情
    public function see()
    {
        $param = Request::param();

        // 查询商户二维码详情
        $SellerMarkQc = \app\common\model\SellerMarkQc::where('id',$param['id'])
            ->order('create_time desc')
            ->find();
        View::assign(['seller_mark_qc' => $SellerMarkQc]);

        // 查询商家详情
        $Seller = \app\common\model\Seller::where('id', $SellerMarkQc['seller_id'])
            ->order('create_time desc')
            ->find();

        // 商户分类
        $SellerClass = \app\common\model\SellerClass::field('id, class_name')
            ->where('id',$Seller['class_id'])
            ->find();
        $Seller['class_id'] = $SellerClass['class_name'];
        View::assign(['seller_list' => $Seller]);

        return View::fetch('seller_mark_qc/see');
    }

    // 预览二维码
    public function preview()
    {
        $param = Request::param();

        // 查询商家详情
        $Seller = \app\common\model\Seller::where('id', $param['seller_id'])
            ->order('create_time desc')
            ->find();

        $qrInfoArr = self::base64EncodeImage($param['seller_id'], $Seller['class_id'], time());
        View::assign(['qrcode_base' => $qrInfoArr['base64_image']]);
        return View::fetch('seller_mark_qc/preview');
    }

    // 查看商户打卡列表
    public function getRecordList($qc_id)
    {
        View::assign(['qc_id' => $qc_id]);
        // 搜索
        $pk = MakeBuilder::getPrimarykey('SellerMarkQcUserRecord');
        if (Request::param('getList') == 1) {
            $param = Request::param();
            $orderByColumn = $param['orderByColumn'] ?? $pk;
            $isAsc         = $param['isAsc'] ?? 'desc';

            $model  = '\app\common\model\SellerMarkQcUserRecord';
            $where = [];
            $where[] = ['qc_id', '=', Request::param('qc_id')];
            $list =  $model::getList($where, $this->pageSize, [$orderByColumn => $isAsc]);

            return $list;
        }
        return View::fetch('seller_mark_qc/getRecordList');
    }

    // 删除
    public function del(string $id)
    {
        if (Request::isPost()) {
            if (strpos($id, ',') !== false) {
                return $this->selectDel($id);
            }
            $model = '\app\common\model\\' . $this->modelName;
            Db::name('seller_mark_qc_user_record')->where('qc_id', $id)->delete();
            return $model::del($id);
        }
    }

    // 批量删除
    public function selectDel(string $id){
        if (Request::isPost()) {
            $ids = explode(',',$id);
            Db::name('seller_mark_qc_user_record')->whereIn('qc_id', $ids)->delete();

            $model = '\app\common\model\\' . $this->modelName;
            return $model::selectDel($id);
        }
    }
}

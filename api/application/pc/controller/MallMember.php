<?php
/**
 * Created by PhpStorm.
 * User: 刘甜可真是个大
 * Date: 2018/5/25
 * Time: 15:13
 */

namespace app\pc\controller;
use app\pc\model\BankBindModel;
use app\pc\model\MallMemberModel;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;
use think\log;
use PDOException;
use think\Config;
use com\Sms;

class MallMember extends Common
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 个人中心->个人资料
     * */

    public function personalData(){
        $id = Session::get("user.id");
        $member = new MallMemberModel();
        $rs = $member->getOne($id);
        if($rs){
            echo json_encode(['status'=>0,'msg'=>'','data'=>$rs[0]]);
        }else{
            echo json_encode(['status'=>1,'msg'=>'暂无资料,请联系管理员!']);
        }
        die;
    }

    /**
     * 个人中心->信息修改
     * */
    public function updateInfo(){
        $id = Session::get("user.id");
        $param = request()->param();
        $data = [
            'travel_agency' => trim($param['travel_agency']),  //姓名
            'mobile' => trim($param['mobile']), //电话
            'birthday' => strtotime($param['birthday']),  //生日
            'sex' => trim($param['sex']),  //性别 2女1男
            'email' => trim($param['email']), // 邮箱
//            'country' => trim($param['country']), //国家 如果不要这个传空
            'province' => trim($param['province']), //省
            'city' => trim($param['city']), //市
            'address' => trim($param['address']),//详细地址
        ];
        try{
            $rs = Db::table('too_mall_member')->where('id', $id)->update($data);
            if (! $rs) throw new PDOException('修改失败！');
            Db::commit();
            echo json_encode(['status'=>0,'msg'=>'修改成功','data'=>[]]);die;
        }catch (PDOException $exception){
            Db::rollback();
            echo json_encode(['status'=>-1,'msg'=>$exception->getMessage(),'data'=>[]]);die;
        }
    }

    /**
     * 个人中心->修改头像
     * */
    public function setHeader(){
        $param = request()->param();
        if(empty($_FILES)){
            echo json_encode(['status'=>1,'msg'=>'请选择上传文件']);
        }
        //检查是否通过http post上传
        if(!is_uploaded_file($_FILES['file']['tmp_name'])){
            echo json_encode(['status'=>1,'msg'=>'上传文件方式非法！']);
        }
        //通过扩展名判断是否为excel
        $fileInfo = pathinfo($_FILES['file']['name']);
        $fileType = strtolower($fileInfo['extension']);
        if($fileType != 'jpg' && $fileType != 'png' && $fileType != 'JPG'){
            echo json_encode(['status'=>1,'msg'=>'上传文件只能为jpg,png,JPG']);
        }
        //判断上传文件是否超过2M 2097152
        $fileSize = $_FILES['file']['size'];
        if($fileSize > 2097152){
            echo json_encode(['status'=>1,'msg'=>'上传文件大小不能超过' . (2097152/1024/1024) . 'M']);
        }
        //处理上传文件
        $uploadhelper = new UplaodYang();
        $now=date("Y-m",time());
        $path='public/uploads/pc/userHeaderImg/'.$now;
        /*以时间来命名上传的文件*/
        $str = date ( 'Ymdhis' );
        $file_name = $str . "." . $fileType;
        if($uploadhelper->upload($file_name, $fileType,$_FILES['file']['tmp_name'],$path)){
            $true_path = 'http://'.$_SERVER['SERVER_NAME'].'/'. $file_name;
            Db::startTrans();
            try{
                $rs = Db::table('too_mall_member')->where('id', $param['id'])->update(['headimg' => $true_path]);
                if (! $rs) throw new PDOException('上传失败！');
                Db::commit();
                echo json_encode(['status'=>0,'msg'=>'上传成功!','data'=>[]]);die;
            }catch (PDOException $exception){
                Db::rollback();
                echo json_encode(['status'=>-1,'msg'=>$exception->getMessage(),'data'=>[]]);die;
            }
            echo json_encode(['status'=>0,'msg'=>'上传成功!']);
        }else{
            echo json_encode(['status'=>1,'msg'=>'上传失败!']);
        }

    }

    /**
     * 个人中心->账号安全
     * */
    public function accountSecurity(){
        $param = request()->param();
        $param['id'] = Session::get('user.id');
        $member = new MallMemberModel();
        switch ($param['action']){
            case 'display';
                $rs = $member->getAllInfo($param);
                break;
            case 'verification';
                $rs = $member->verificationGo($param);
                break;
//            case 'bankType';
//                $bank = Db::name("bank")->select();
//                echo json_encode(['status'=>0,'msg'=>'','data'=>$bank]);
//                break;
            default:
                echo json_encode(['status'=>0,'msg'=>'emmm','data'=>'emmm']);
        }
        echo json_encode($rs);die;
    }

    /**
     * 个人中心->我的银行卡
     * */
    public function myBankCard(){
        $param = request()->param();
        $bank_bind = new BankBindModel();
        $param['id'] = Session::get('user.id');
//        $param['action'] = 'add';
        switch ($param['action']){
            case 'display';
                $rs =  $bank_bind->myBank($param);
                break;
            case 'add';
                $rs = $bank_bind->addBank($param);
                break;
            case 'bankType';
                $bank = Db::name("bank")->select();
                echo json_encode(['status'=>0,'msg'=>'','data'=>$bank]);
                break;
            case 'sendCord';
                $rs = $this->sendCord();
                break;
            default:
                echo json_encode(['status'=>0,'msg'=>'emmm','data'=>'emmm']);
        }
        echo json_encode($rs);die;
    }

//    private function addBank(){
//        $info = request()->param();
//        $bank_no = $info['bank_no']; //存在
//        $key = 'e72aced86f41fba0d955e1014dc4b8fa';
//        $url='http://v.juhe.cn/bankcardinfo/query?key='.$key.'&bankcard='.$bank_no;
//        $curl = curl_init();
//        curl_setopt($curl, CURLOPT_URL, $url);
//        curl_setopt($curl, CURLOPT_HEADER, 0);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//        $tmpInfo = curl_exec($curl);
//        curl_close($curl);
//        $result = json_decode($tmpInfo,true);
//        if($result['error_code'] != 0){
//            $res['status'] = '-1';
//            $res['msg'] = '银行卡号填写错误';
//            return json($res);
//        }
//        if(isset($result['result']['cardtype']) && $result['result']['cardtype'] != "借记卡"){
//            $res['status'] = '-2';
//            $res['msg'] = '信用卡不可用';
//
//            return json($res);
//        }
//        $member_id = Session::get("member_id");
//        $bank_info = Db::name("bank_bind")->where("member_id",$member_id)->find();
//        if(empty($bank_info)){
//            $data['bank_no'] = $bank_no;
//            $data['bank_name'] = $result['result']['bank'];
//            $data['member_id'] = $member_id;
//            $data['username'] = $info['username'];
//            $data['mobile'] = $info['mobile'];
//            $data['status'] = 1;
//            $bool = Db::name("bank_bind")->insert($data);
//            if($bool){
//                $res['code'] = 0;
//                $res['msg'] = '绑定成功!';
//                return json($res);
//            }else{
//                $res['code'] = '-1';
//                $res['msg'] = '绑定失败!';
//                return json($res);
//            }
//        }else{
//            $data['bank_no'] = $bank_no;
//            $data['bank_name'] = $result['result']['bank'];
//            $data['member_id'] = $member_id;
//            $data['username'] = $info['username'];
//            $data['mobile'] = $info['mobile'];
//            $data['status'] = 1;
//            $bool = Db::name("bank_bind")->where("member_id",$member_id)->update($data);
//            if($bool){
//                $res['status'] = 0;
//                $res['msg'] = '绑定成功!';
//                return json($res);
//            }else{
//                $res['status'] = '-1';
//                $res['msg'] = '绑定失败!';
//                return json($res);
//            }
//        }
//    }


    /**
     * 个人中心->修改密码
     * */
    public function setPassword(){
        $param = request()->param();
        $rs = Db::table('too_mallmember')->where('id',Session::get('user.id'))->field('password')->find();
        if(md5($rs['password']) !== md5($param['old_password'])){
            echo json_encode(['status'=>1,'msg'=>'当前密码错误!','data'=>[]]);die;
        }
        if(md5($rs['new_password']) !== md5($param['new_password_two'])){
            echo json_encode(['status'=>1,'msg'=>'两次密码输入不同!','data'=>[]]);die;
        }

        Db::startTrans();
        try{
            $rs = Db::table('too_mall_member')->where('id', Session::get('user.id'))->update(['password' => md5($param['password'].'lvpaipc')]);
            if (! $rs) throw new PDOException('修改密码失败！');
            Db::commit();
            echo json_encode(['status'=>0,'msg'=>'修改成功','data'=>[]]);die;
        }catch (PDOException $exception){
            Db::rollback();
            echo json_encode(['status'=>-1,'msg'=>$exception->getMessage(),'data'=>[]]);die;
        }
    }

    /**
     * 个人中心->修改手机号
     * */
    public function setPhone(){
        $param = request()->param();
        $param['id'] = Session::get('user.id');
        Db::startTrans();
        try{
            if (! preg_match('/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\d{8}$/', $param['mobile']))
                throw new PDOException('手机号格式不正确！');
            $rs = Db::table('too_mall_member')->where('id', $param['id'])->update(['mobile' => $param['mobile']]);
            if (! $rs) throw new PDOException('修改手机失败！');
            Db::commit();
            echo json_encode(['status'=>0,'msg'=>'修改成功','data'=>[]]);die;
        }catch (PDOException $exception){
            Db::rollback();
            echo json_encode(['status'=>-1,'msg'=>$exception->getMessage(),'data'=>[]]);die;
        }
    }


    /**
     * 个人中心->修改邮箱
     * */
    public function setEmail(){
        $param = request()->param();
        $param['id'] = Session::get('user.id');
        Db::startTrans();
        try{
            if (! preg_match('/^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/', $param['email']))
                throw new PDOException('邮箱格式不正确！');
            $rs = Db::table('too_mall_member')->where('id', $param['id'])->update(['email' => $param['email']]);
            if (! $rs) throw new PDOException('验证邮箱失败！');
            Db::commit();
            echo json_encode(['status'=>0,'msg'=>'验证成功','data'=>[]]);die;
        }catch (PDOException $exception){
            Db::rollback();
            echo json_encode(['status'=>-1,'msg'=>$exception->getMessage(),'data'=>[]]);die;
        }
    }


    /**
     * 个人中心->修改安全问题
     * */
    public function setSecurity(){
        $param = request()->param();
        $param['member_id'] = Session::get('user_id');
        Db::startTrans();
        try{
            $rs = Db::table('too_member_secutity')->where('member_id', $param['member_id'])->delete();
            if (! $rs) throw new PDOException('修改失败！');
            $insert = [];
            foreach ($param['rows'] as $k => $v) {
                $insert[$k] = array(
                    'member_id' => $param['member_id'],
                    'problem_type' => $v['problem_type'],
                    'answer' => $v['answer'],
                    'createtime' => time()
                );
            }
            $rs = Db::table('too_member_cashnum_examine_img')->insertAll($insert);
            if (! $rs) throw new PDOException('修改安全问题失败');
            Db::commit();
            echo json_encode(['status'=>0,'msg'=>'修改安全问题成功','data'=>[]]);die;
        }catch (PDOException $exception){
            Db::rollback();
            echo json_encode(['status'=>-1,'msg'=>$exception->getMessage(),'data'=>[]]);die;
        }
    }

    /**
     * 发送手机验证码
     * */
    private function sendCord(){
        $mobile = Request::instance()->param('mobile');
        $code = get_rand_num();
        Session::set('sms_pc_bank',$code);
        $this->send($mobile,$code);
    }

    public function send($mobile,$data) {
        $sms = new Sms();
//        $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        $data = json_encode(['code'=>$data],JSON_UNESCAPED_UNICODE);
        $return = $sms->send('中惠旅短信平台','SMS_135791827',$mobile,$data);
        Log::write($return);
        return $return;
    }


}

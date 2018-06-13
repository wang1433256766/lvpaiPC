<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/5/26
 * Time: 10:45
 */
namespace app\pc\model;

use think\Db;
use think\Model;
use think\Session;
use PDOException;

class BankBindModel extends PublicyangModel
{
    public function __construct()
    {
        parent::__construct();
    }

    protected $table = 'too_bank_bind';
    //显示字段
    protected $list_fields = array();

    /**
     * 查询已绑定的银行卡
     * */
    public function myBank($data){
        $sql = "select * 
                from {$this->table} 
                WHERE member_id = {$data['id']}";
        $rs = $this->query($sql);
        if($rs){
            return ['status'=>0,'msg'=>'','data'=>$rs];
        }else{
            return ['status'=>1,'msg'=>'暂无银行卡',$data=>''];
        }
    }

    /**
     * 添加银行卡
     **/
    public function addBank(){
        $info = request()->param();
        $info['bank_no'] = 6210984980012298701;
        $sms_pc_bank= Session::get('sms_pc_bank');
        if($info['code']!=$sms_pc_bank){
            $res['status'] = '-1';
            $res['msg'] = '验证码不正确！';
            return $res;
        }
        $bank_no = $info['bank_no']; //存在
        $key = 'e72aced86f41fba0d955e1014dc4b8fa';
        $url='http://v.juhe.cn/bankcardinfo/query?key='.$key.'&bankcard='.$bank_no;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $tmpInfo = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($tmpInfo,true);
        if($result['error_code'] != 0){
            $res['status'] = '-1';
            $res['msg'] = '银行卡号填写错误';
            return $res;
        }
        if(isset($result['result']['cardtype']) && $result['result']['cardtype'] != "借记卡"){
            $res['status'] = '-1';
            $res['msg'] = '信用卡不可用';
            return $res;
        }
        $member_id = Session::get("user.id");
        $info['username'] = '杨增文';
        $info['mobile'] = '17373193687';
        $bank_info = Db::name("bank_bind")->where('member_id',$member_id)->where('bank_no',$bank_no)->find();
        if(empty($bank_info)){
            $data['bank_no'] = $bank_no;
            $data['bank_name'] = $result['result']['bank'];
            $data['member_id'] = $member_id;
            $data['username'] = $info['username'];
            $data['mobile'] = $info['mobile'];
            $data['status'] = 1;
            $data['bank_id'] = $info['bank_id'];
            try{
                $bool = Db::name("bank_bind")->insert($data);
                if($bool){
                    Db::commit();
                    $res['status'] = 0;
                    $res['msg'] = '绑定成功!';
                    return $res;
                }else{
                    throw new PDOException('绑定失败！');
                }
            }catch (PDOException $exception){
                Db::rollback();
                echo json_encode(['status'=>-1,'msg'=>$exception->getMessage(),'data'=>[]]);die;
            }
        }else{
            $data['bank_no'] = $bank_no;
            $data['bank_name'] = $result['result']['bank'];
            $data['member_id'] = $member_id;
            $data['username'] = $info['username'];
            $data['mobile'] = $info['mobile'];
            $data['status'] = 1;
            $data['bank_id'] = $info['bank_id'];
            try{
                $bool = Db::name("bank_bind")->where(" member_id",$member_id)->where('bank_no',$bank_no)->update($data);
                if($bool){
                    Db::commit();
                    $res['status'] = 0;
                    $res['msg'] = '绑定成功!';
                    return $res;
                }else{
                    throw new PDOException('绑定失败！');
                }
            }catch (PDOException $exception){
                Db::rollback();
                $res['status'] = -1;
                $res['msg'] = $exception->getMessage();
                return $res;
            }
        }
    }


}
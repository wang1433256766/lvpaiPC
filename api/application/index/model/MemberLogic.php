<?php
namespace app\index\model;

use app\common\model\MemberModel;
use think\Session;

class MemberLogic extends Logic
{
    static public function checkPhone($phone)
    {
        $memberModel = new MemberModel();
        $check = $memberModel->where('phone',$phone)->find();
        return $check ? true : false;
    }
    static public function checkSms($sms)
    {
        if(Session::get('sms')!=$sms){
            return false;
        }else{
            return true;
        }
    }
    static public function checkId($id)
    {
        $check = MemberModel::get(['id'=>$id]);
        return $check ? true : false;
    }
}
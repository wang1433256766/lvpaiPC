<?php

namespace app\mobile\controller;

use think\Controller;
use think\Db;
use think\Request;
use think\Session;
use think\Log;

class Bind extends Controller
{
    public function bind()
    {
        $member_id = Session::get('member_id');
        $member = Db::name('mall_member')->where('id', $member_id)->find();
        $this->assign('member', $member);
        return $this->fetch();
    }

    public function check()
    {
        $mobile = Request::instance()->param('mobile');
        // $member = Db::name('mall_member')->where('mobile', $mobile)->find();
        // if ($member && ($member['openid'] || $member['pc_openid'])) {
        //     $res = array(
        //         'status' => false,
        //         'info' => '该手机号已被绑定',
        //     );
        //     exit(json_encode($res));
        // }
        $code = get_rand_num();
        Session::set('sms_login', $code);
        $prefix = '';
        $res = array(
            'status' => false,
            'info' => '发送失败',
        );
        $user = "cf_zhonghuilv";
        $pass = "eb2a1a963b116ae15e7cb2bf41382bf4";
        $content = "[旅拍秀秀]尊敬的会员,请您在页面中输入以下验证码:{#CODE#},完成验证!";
        $content = str_ireplace('{#CODE#}', $code, $content);
        $msg = new\com\Msg($user, $pass);
        $info = $msg->sendMsg($mobile, $prefix, $content);
        log::write('duanxin');
        log::write($info);
        if ($info['code'] == 2) {
            $res['status'] = true;
            $res['info'] = '发送成功';
        }
        echo json_encode($res);
    }

    public function login()
    {
        $info = Request::instance()->param();
        $res = array(
            'status' => false,
            'info' => '绑定失败',
        );
        $res['info'] = '请填写姓名';
        !empty($info['name']) or die(json_encode($res));

        $res['info'] = '请填写手机号码';
        !empty($info['mobile']) or die(json_encode($res));

        $res['info'] = '请提交验证码';
        !empty($info['code']) or die(json_encode($res));

        $res['info'] = '验证码错误';
        $sms_login = Session::get('sms_login');
        $sms_login == $info['code'] or die(json_encode($res));
        $res['info'] = '帐号不存在';
        $where['id'] = Session::get('member_id');


        log::write('member');
        log::write(Session::get('member_id'));
        $member = Db::name('mall_member')->where($where)->find();
        if ($member) {
            $data['mobile'] = $info['mobile'];
            $data['name'] = $info['name'];
            $data['travel_agency'] = $info['travel_agency'] ? $info['travel_agency'] : '';
            $data['login_time'] = time();

            Db::name('mall_member')->where($where)->update($data);
            $res['status'] = true;
            $res['info'] = '绑定成功';
        }
        die(json_encode($res));
    }

    public function verify()
    {
        $verify = new \org\Verify();
        $verify->codeSet = '0123456789';
        $verify->imageH = 30;
        $verify->imageW = 100;
        $verify->fontSize = 16;
        $verify->length = 3;
        $verify->useCurve = false;
        ob_clean();
        $verify->entry();
    }
}
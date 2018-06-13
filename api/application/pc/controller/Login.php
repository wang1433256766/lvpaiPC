<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/5/25
 * Time: 17:07
 */

namespace app\pc\controller;


use app\pc\model\MallMemberModel;
use think\Cookie;
use think\Db;
use think\Request;
use think\Session;
use com\Sms;


class Login extends Common
{
    // 验证登录信息
    public function validateLogin()
    {
        $login_info = Request::instance()->param();
        if ($login_info['username'] == '' || $login_info['password'] == '') $this->ajaxReturn(1, '', '账号密码不存在');
        if (isset($login_info['check']) && $login_info['check']) {
            Cookie::set('username', $login_info['username']);
            Cookie::set('password', $login_info['password']);
        }
        $res = MallMemberModel::validateLoginInfo($login_info['username'], $login_info['password']);
        if ($res) {
            $user = ['id' => end($res)['id'], 'type' => end($res)['type']];
            Session::set('user', $user);
            $this->ajaxReturn(0, '', '登录成功');
        } else $this->ajaxReturn(1, '', '账号密码错误');
    }

    // 发送手机验证码
    public function sendCord(){
        $mobile = Request::instance()->param('mobile');
        $count = MallMemberModel::getCountByMobile($mobile);
        if ($count < 1) $this->ajaxReturn(1, '', '该手机号未注册');
        $code = $this->getRandNum();
        Session::set('login_pc_code', $code);
        Session::set('login_pc_mobile', $mobile);
        $send_res = $this->send($mobile, $code);
        if ($send_res === true) $this->ajaxReturn(0, '', '发送成功');
        else $this->ajaxReturn(1, '', $send_res);
    }

    /**
     * 获取指定长度的随机数字
     * @param int $len 字符串长度
     * @return string 返回指定长度字符串
     */
    public function getRandNum($len = 6){
        $rand_arr = range('0', '9');
        shuffle($rand_arr);//打乱顺序
        $rand = array_slice($rand_arr, 0,$len);
        return implode('', $rand);
    }

    public function send($mobile, $data) {
        $sms = new Sms();
        $data = json_encode(['code'=>$data],JSON_UNESCAPED_UNICODE);
        $return = $sms->send('中惠旅短信平台', 'SMS_136161381', $mobile, $data);
        return $return;
    }

    // 手机验证码验证
    public function validateCode()
    {
        $info = Request::instance()->post();
        if ($info['code'] == Session::get('login_pc_code')) {
            $mobile = Session::get('login_pc_mobile');
            if (! $mobile || $mobile != $info['mobile']) $this->ajaxReturn(1, '', '请重新获取验证码');
            $member = MallMemberModel::getModelByMobile($mobile);
            if (is_string($member)) {
                $user_info = ['mobile' => $mobile];
                $member_id = MallMemberModel::insertMemberInfo($user_info);
                $user = ['id' => $member_id, 'mobile' => $mobile];
                Session::set('user', $user);
            } else Session::set('user', $member);
            $this->ajaxReturn(0, '', '验证成功');
        } else $this->ajaxReturn(1, '', '验证码错误');
    }

    // 登出
    public function logout()
    {
        Session::clear();
        $this->ajaxReturn(0, '', '退出成功');
    }

    // 微信回调
    public function qrCodeCallback()
    {
        $code = Request::instance()->param('code');
        if (! $code) $this->ajaxReturn(1, '', '信息缺失');
        $wechat = new Wechat();
        $callback_data = $wechat->getAccessTokenByCode($code);
        if (! isset($callback_data['errcode'])) {
            $user_info = $wechat->getUserInfo($callback_data['access_token'], $callback_data['openid']);
            if (isset($user_info['errcode'])) $this->ajaxReturn(1, '', $user_info['errmsg']);
            $member = MallMemberModel::getModelByUnionId($callback_data['unionid']);
            // 用户信息整合
            $user_info['pc_openid'] = $user_info['openid'];
            $user_info['headimg'] = $user_info['headimgurl'];
            unset($user_info['privilege'], $user_info['openid'], $user_info['headimgurl'], $user_info['language']);
            // 用户不存在
            if (is_string($member)) {
                $member_id = MallMemberModel::insertMemberInfo($user_info);
                if ($member_id) {
                    // 下一步 绑定手机号
                    Session::set('user.id', $member_id);
                    $this->ajaxReturn(2, '', '当前用户未绑定手机号');
                } else $this->ajaxReturn(1, '', '插入用户信息失败');
            } else {
                // 无论更新成功与否都能继续执行
                MallMemberModel::updateMemberInfo(['id' => $member['id']], $user_info);
                Session::set('user.id', $member['id']);
                if (! $member['mobile']) {
                    $this->ajaxReturn(2, '', '当前用户未绑定手机号');
                } else {
                    $this->ajaxReturn(0, '', '登录成功');
                }
            }
        } else $this->ajaxReturn(1, '', $callback_data['errmsg']);
    }

    // 发送绑定手机验证码
    public function getRegisterCode()
    {
        $mobile = Request::instance()->post('mobile');
        $count = MallMemberModel::getCountByMobile($mobile);
        if ($count > 0) $this->ajaxReturn(1, '', '该手机号已绑定');
        $code = $this->getRandNum();
        Session::set('register_pc_code', $code);
        Session::set('register_pc_mobile', $mobile);
        $send_res = $this->send($mobile, $code);
        if ($send_res === true) $this->ajaxReturn(0, '', '发送成功');
        else $this->ajaxReturn(1, '', $send_res);
    }

    // 绑定手机号
    public function validateRegisterCode()
    {
        $info = Request::instance()->post();
        if (! isset($info['code']) || ! isset($info['travel_agency']) || ! isset($info['name'])) $this->ajaxReturn(1, '', '信息缺失');
        $code = $info['code'];
        if ($code == Session::get('register_pc_code')) {
            Session::delete('register_pc_code');
            $member_id = Session::get('user.id');
            $member = MallMemberModel::getModelById($member_id);
            if (is_string($member)) $this->ajaxReturn(1, '', $member);
            if (! Session::get('register_pc_mobile')) $this->ajaxReturn(1, '', '验证码已过期,请重新扫码登录');
            $update_info = [
                'mobile' => Session::get('register_pc_mobile'),
                'travel_agency' => $info['travel_agency'],
                'name' => $info['name']
            ];
            $update_res = MallMemberModel::updateMemberInfo(['id' => $member_id], $update_info);
            if (is_string($update_res)) $this->ajaxReturn(1, '', $update_res);
            else $this->ajaxReturn(0, '', '绑定成功');
        } else $this->ajaxReturn(1, '', '验证码错误');

    }


}
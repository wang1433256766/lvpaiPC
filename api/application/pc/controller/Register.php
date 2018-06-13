<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/5/25
 * Time: 16:13
 */

namespace app\pc\controller;


use app\pc\model\MallMemberModel;
use think\captcha\Captcha;
use think\Request;

class register extends Common
{
    // 验证手机号是否被注册
    public function validateMobile()
    {
        $mobile = Request::instance()->param('mobile');
        if (! $mobile) $this->ajaxReturn(1, '' , '手机号不能为空');
        if (! preg_match('/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\d{8}$/', $mobile)) $this->ajaxReturn(1, '' , '手机号格式错误');
        $num = MallMemberModel::getCountByMobile($mobile);
        if ($num > 0) $this->ajaxReturn(1, '' , '该手机号已被注册');
        else $this->ajaxReturn(0, '' , '');
    }

    /**
     * 生成验证码图片
     * @return \think\Response
     */
    public function getCaptcha()
    {
        $captcha = new Captcha();
        $captcha->length = 4;
        $captcha->reset = false;
        return $captcha->entry();
    }

    // 校验验证码
    public function validateCaptcha()
    {
        $code = Request::instance()->post('code');
        $captcha = new Captcha();
        if (! $captcha->check($code)) $this->ajaxReturn(1, '' , '验证码错误');
        else $this->ajaxReturn(0, '' , '校验成功');
    }

    // 校验用户信息
    public function validateRegister()
    {
        $info = Request::instance()->post();
        if (! preg_match('/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\d{8}$/', $info['mobile'])) $this->ajaxReturn(1, '' , '手机号格式错误');
        $captcha = new Captcha();
        if (! $captcha->check($info['code'])) $this->ajaxReturn(1, '' , '验证码错误');
        if ($info['password'] !== $info['re_password']) $this->ajaxReturn(1, '' , '两次密码不相同');
        if (! $info['agree']) $this->ajaxReturn(1, '' , '请勾选旅拍秀秀会员协议');
        $user_info = [
            'mobile' => $info['mobile'],
            'password' => md5($info['password'] . 'lvpaipc')
        ];
        $member_id = MallMemberModel::insertMemberInfo($user_info);
        if ($member_id) {
            session('member_id', $member_id);
            $this->ajaxReturn(0, '' , '注册成功');
        } else $this->ajaxReturn(1, '' , '注册失败');
    }







}
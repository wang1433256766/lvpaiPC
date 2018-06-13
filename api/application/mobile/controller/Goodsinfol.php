<?php
namespace app\mobile\controller;
use think\Db;
use think\Controller;
use think\Request;
use think\Log;
use think\Session;
use think\Config;
use com\PHPQRCode;

class Goodsinfol extends Base
{
	public function goodsdetail() {
        $id = Request::instance()->param('id');
        session('spot_id',$id);
        $ac = Request::instance()->param('ac');
        if ($ac) {
            $promote_id = authcode($ac,'DECODE');
            $type = Db::name('mall_member')->where('id',$promote_id)->value('type');
            if ($type > 0) {
                session('promote_id',$promote_id);
            }
        }
        $ty = session('type');
        if ($ty == 0) {
            $member_id = isset($promote_id)?$promote_id:0;
        }else {
            $member_id  = session('member_id');
        }
        $data = Db::name('ticket')->where('id',$id)->find();
        $comment = Db::name('shop_spot_comment')->where('ticket_id',$id)->order('id desc')->find();
        $num = Db::name('shop_spot_comment')->where('ticket_id',$id)->count('id');
        $img = Db::name('mall_member')->where('id',$comment['member_id'])->value('headimg');
        $type = session('type');
        $this->assign('member_id',$member_id);
        $this->assign('type',$type);
        $this->assign('img',$img);
        $this->assign('num',$num);
        $this->assign('comment',$comment);
        $this->assign('data',$data);
	    return $this->fetch();
    }
}
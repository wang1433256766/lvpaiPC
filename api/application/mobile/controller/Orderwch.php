<?php
namespace app\mobile\controller;
use think\Db;
use think\Controller;
use think\Request;
use think\Log;

class Orderwch extends Base
{
	public function index()
	{
        $member_id = session('member_id');
        $where['too_wch_order.member_id'] = $member_id;
        $where['too_wch_order.status'] = ['in','0,1,2,4,5'];
        $all = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price,too_score_goods.integral")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")
                ->where($where)->order('add_time desc')->select();
        $where['too_wch_order.status'] = 0;
        $notpay = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price,too_score_goods.integral")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")
                ->where($where)->order('add_time desc')->select();
        $where['too_wch_order.status'] = 2;
        $notuse = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price,too_score_goods.integral")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")
                ->where($where)->order('add_time desc')->select();
        $where['too_wch_order.status'] = 5;
        $complete = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price,too_score_goods.integral")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")
                ->where($where)->order('add_time desc')->select();
        $where['too_wch_order.status'] = ['in','4,5,6'];
        $after = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price,too_score_goods.integral")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")
                ->where($where)->order('add_time desc')->select();

        $this->assign('all',$all);
        $this->assign('notpay',$notpay);
        $this->assign('notuse',$notuse);
        $this->assign('complete',$complete);
        $this->assign('after',$after);
	    return $this->fetch();
	}

	public function notpay() {
        $id = Request::instance()->param('id');
        if ($id) {
            $pay = new \com\Paywch();
            $res = $pay->mwxpay($id,2);
            $data = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")->where('too_wch_order.id',$id)->find();
            $address = Db::name("member_address")->where("id",$data['address_id'])->find();
            $this->assign('address',$address);
            $this->assign('res',$res);
            $this->assign('data',$data);
            return $this->fetch();
        }

    }

    public function suborder() {
        $id = Request::instance()->param('id');
        $data = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")->where('too_wch_order.id',$id)->find();
        $pay = new \com\Paych();
        $res = $pay->mwxpay($id,1);
        $address = Db::name("member_address")->where("id",$data['address_id'])->find();
        $this->assign('address',$address);
        $this->assign('res',$res);
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function commen() {
        $id = Request::instance()->param('id');
        if ($id) {
            $this->assign('id',$id);
            return $this->fetch();
        }
    }

    public function subcomment() {
	    if (Request::instance()->isPost()) {
	        $member_id = session('member_id');
	        $id = Request::instance()->param('id');
            $order = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")->where('too_wch_order.id',$id)->find();
            $data['order_id'] = $order['id'];
            $data['wch_id'] = $order['product_id'];
            $data['member_id'] = $member_id;
	        $data['content'] = Request::instance()->param('content');
	        $data['add_time'] = time();
            $bool = Db::name('shop_spot_comment')->insert($data);
            if ($bool) {
                $res['status'] = TRUE;
            }else {
                $res['status'] = FALSE;
                $res['info'] = '遇到的问题了，发表失败';
            }
            echo json_encode($res);
        }
    }

    public function notuse() {
        $id = Request::instance()->param('id');
        if ($id) {
            $member_id = session('member_id');
            $mobile = Db::name('mall_member')->where('id',$member_id)->value('mobile');
            $data = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")->where('too_wch_order.id',$id)->find();
            // $where['id'] = ['in',$data['traveler_ids']];
            // $traveller = Db::name('member_traveler_info')->where($where)->select();
            // $this->assign('traveller',$traveller);
            $address = Db::name("member_address")->where("id",$data['address_id'])->find();
            $this->assign('address',$address);
            $this->assign('data',$data);
            $this->assign('mobile',$mobile);
            return $this->fetch();
        }
        return $this->fetch();
    }

    public function evaluation() {
        $id = Request::instance()->param('id');
        if ($id) {
            $comment = Db::name('shop_spot_comment')->where('order_id',$id)->order('id desc')->value('content');
            $data = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")->where('too_wch_order.id',$id)->find();
            $where['id'] = ['in',$data['traveler_ids']];
            $traveller = Db::name('member_traveler_info')->where($where)->select();
            $address = Db::name("member_address")->where("id",$data['address_id'])->find();
            $this->assign('address',$address);
            $this->assign('comment',$comment);
            $this->assign('traveller',$traveller);
            $this->assign('data',$data);
            return $this->fetch();
        }
        return $this->fetch();
    }

    public function complete() {
        $id = Request::instance()->param('id');
        if ($id) {
            $data = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")->where('too_wch_order.id',$id)->find();
            $where['id'] = ['in',$data['traveler_ids']];
            $traveller = Db::name('member_traveler_info')->where($where)->select();
            $address = Db::name("member_address")->where("id",$data['address_id'])->find();
            $this->assign('address',$address);
            $this->assign('traveller',$traveller);
            $this->assign('data',$data);
            return $this->fetch();
        }
        return $this->fetch();
    }

    public function refund() {
        $id = Request::instance()->param('id');
        if ($id) {
            $data = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")->where('too_wch_order.id',$id)->find();
            $where['id'] = ['in',$data['traveler_ids']];
            $traveller = Db::name('member_traveler_info')->where($where)->select();
            $address = Db::name("member_address")->where("id",$data['address_id'])->find();
            $this->assign('address',$address);
            $this->assign('traveller',$traveller);
            $this->assign('data',$data);
            return $this->fetch();
        }
        return $this->fetch();
    }

    public function refunddel() {
        $id = Request::instance()->param('id');
        if ($id) {
            $data = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")->where('too_wch_order.id',$id)->find();
            $where['id'] = ['in',$data['traveler_ids']];
            $traveller = Db::name('member_traveler_info')->where($where)->select();
            $address = Db::name("member_address")->where("id",$data['address_id'])->find();
            $this->assign('address',$address);
            $this->assign('traveller',$traveller);
            $this->assign('data',$data);
            return $this->fetch();
        }
        return $this->fetch();
    }



	//取消订单
	public function cancel() {
	    if (Request::instance()->isPost()) {
	        $id = Request::instance()->param('id');
	        $data['too_wch_order.status'] = 3;
	        $bool = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")->where('too_wch_order.id',$id)->update($data);
	        if ($bool) {
	            $res['status'] = TRUE;
            }else {
	            $res['status'] = FALSE;
	            $res['info'] = '遇到的问题了，取消失败';
            }
            echo json_encode($res);
	        exit;
        }
        $res['info'] = '非法请求';
        echo json_encode($res);
    }

    //申请退款
    public function apply() {
        if (Request::instance()->isPost()) {
            $id = Request::instance()->param('id');
            $refund_reason = Request::instance()->param('refund_reason');
            $data['too_wch_order.refund_reason'] = $refund_reason;
            $data['too_wch_order.status'] = 6;
            $bool = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")->where('too_wch_order.id',$id)->update($data);
            if ($bool) {
                $res['status'] = TRUE;
            }else {
                $res['status'] = FALSE;
                $res['info'] = '遇到的问题了，申请失败';
            }
            echo json_encode($res);
            exit;
        }
        $res['info'] = '非法请求';
        echo json_encode($res);
    }

    //取消退款
    public function applyCancel() {
        if (Request::instance()->isPost()) {
            $id = Request::instance()->param('id');
            $data['too_wch_order.status'] = 1;
            $bool = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")->where('too_wch_order.id',$id)->update($data);
            if ($bool) {
                $res['status'] = TRUE;
            }else {
                $res['status'] = FALSE;
                $res['info'] = '遇到的问题了，取消失败';
            }
            echo json_encode($res);
            exit;
        }
        $res['info'] = '非法请求';
        echo json_encode($res);
    }

    //删除订单
    public function del() {
        if (Request::instance()->isPost()) {
            $id = Request::instance()->param('id');
            $data['too_wch_order.status'] = 1;
            $bool = Db::name('wch_order')
                ->field("too_wch_order.*,too_score_goods.name,too_score_goods.price")
                ->join("too_score_goods","too_wch_order.product_id = too_score_goods.id")->where('too_wch_order.id',$id)->update($data);
            if ($bool) {
                $res['status'] = TRUE;
            }else {
                $res['status'] = FALSE;
                $res['info'] = '遇到的问题了，删除失败';
            }
            echo json_encode($res);
            exit;
        }
        $res['info'] = '非法请求';
        echo json_encode($res);
    }



}
<?php
namespace app\mobile\controller;
use think\Db;
use think\Controller;
use think\Request;
use think\Log;

class Order extends Controller
{
	public function index()
	{
        $member_id = session('member_id');
        $where['member_id'] = $member_id;
        $where['status'] = ['in','0,1,2,4,5,6'];
        $all = Db::name('spot_order')->where($where)->order('add_time desc')->select();
        $where['status'] = 0;
        $notpay = Db::name('spot_order')->where($where)->select();
        $where['status'] = 1;
        $notuse = Db::name('spot_order')->where($where)->select();
        $where['status'] = 5;
        $complete = Db::name('spot_order')->where($where)->select();
        $where['status'] = ['in','2,4'];
        $after = Db::name('spot_order')->where($where)->select();

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
            $pay = new \com\Pay();
            $res = $pay->mwxpay($id,2);
            $data = Db::name('spot_order')->where('id',$id)->find();
            $where['id'] = ['in',$data['traveler_ids']];
            $traveller = Db::name('member_traveler_info')->where($where)->select();
            $this->assign('res',$res);
            $this->assign('traveller',$traveller);
            $this->assign('data',$data);
            return $this->fetch();
        }

    }

    public function suborder() {
        $id = Request::instance()->param('id');
        $data = Db::name('spot_order')->where('id',$id)->find();
        $pay = new \com\Pay();
        $res = $pay->mwxpay($id,1);
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
            $order = Db::name('spot_order')->where('id',$id)->find();
            $data['order_id'] = $order['id'];
            $data['ticket_id'] = $order['ticket_id'];
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
            $data = Db::name('spot_order')->where('id',$id)->find();
            //去除部分退款id 数组array_diff去重
            $traveler_ids =explode(",",$data['traveler_ids']);
            $refund_ids=explode(",", $data['refund_ids']);
            $traveler_ids=array_diff($traveler_ids,$refund_ids);
            $where['id'] = ['in',$traveler_ids];
            $traveller = Db::name('member_traveler_info')->where($where)->select();
            //是否团队票
            $is_team=DB::name('ticket')->where('id',$data['ticket_id'])->value('is_team');
            $this->assign('is_team',$is_team);
            $this->assign('traveller',$traveller);
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
            $data = Db::name('spot_order')->where('id',$id)->find();
            $where['id'] = ['in',$data['traveler_ids']];
            $traveller = Db::name('member_traveler_info')->where($where)->select();
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
            $data = Db::name('spot_order')->where('id',$id)->find();
            $where['id'] = ['in',$data['traveler_ids']];
            $traveller = Db::name('member_traveler_info')->where($where)->select();
            $this->assign('traveller',$traveller);
            $this->assign('data',$data);
            return $this->fetch();
        }
        return $this->fetch();
    }

    public function refund() {
        $id = Request::instance()->param('id');
        if ($id) {
            $data = Db::name('spot_order')->where('id',$id)->find();
            $where['id'] = ['in',$data['traveler_ids']];
            $traveller = Db::name('member_traveler_info')->where($where)->select();
            $this->assign('traveller',$traveller);
            $this->assign('data',$data);
            return $this->fetch();
        }
        return $this->fetch();
    }

    public function refunddel() {
        $id = Request::instance()->param('id');
        if ($id) {
            $data = Db::name('spot_order')->where('id',$id)->find();
            $where['id'] = ['in',$data['traveler_ids']];
            $traveller = Db::name('member_traveler_info')->where($where)->select();
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
	        $data['status'] = 3;
	        $bool = Db::name('spot_order')->where('id',$id)->update($data);
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

    //申请退款(2018.4.24前版本使用)
    public function apply() {
        if (Request::instance()->isPost()) {
            $id = Request::instance()->param('id');
            $refund_reason = Request::instance()->param('refund_reason');
            $data['refund_reason'] = $refund_reason;
            $data['status'] = 2;
            $bool = Db::name('spot_order')->where('id',$id)->update($data);
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
//申请退款
    public function tuikuan(){
        if(Request::instance()->isAjax()){
            $info= Request::instance()->param();
            //前端传的出游人id ,退款理由
            $new_refund_ids=substr($info['traveller_ids'], 0, -1);
            $new_refund_reason=$info['refund_reason'];
            $order_sn=$info['order'];
            //数据库部分退款id,及理由
            $order_info=Db::name('spot_order')->where('order_sn',$order_sn)->find();
            $before_refund_ids=$order_info['refund_ids'];
            $before_refund_reason=$order_info['refund_reason'];
            //将新的退款与旧的重组
            if($before_refund_ids){
                $data['refund_ids']=$before_refund_ids.','.$new_refund_ids;
                $data['refund_reason']=$before_refund_reason.','.$new_refund_reason;
            }else{
                    $data['refund_ids']=$new_refund_ids;
                    $data['refund_reason']=$new_refund_reason;
            }
            
            $traveler_ids= $order_info['traveler_ids'];
            $traveler_ids =explode(",",$traveler_ids);
            $refund_arr=explode(",", $data['refund_ids']);
            //判断是否全部退款
           if(count($traveler_ids)==count($refund_arr)){
               $data['status'] = 2;
               $data['refund_time']=date("Y-m-d H:i:s");
               $result= Db::name('spot_order')->where('order_sn',$order_sn)->update($data);
               return $result;
           } 
           elseif(count($traveler_ids)>count($refund_arr)){
            $data['status']=6;
            $data['refund_time']=date("Y-m-d H:i:s");
            $result=Db::name('spot_order')->where('order_sn',$order_sn)->update($data);
            return $result;
            }
           
        }
    }

    //取消退款
    public function applyCancel() {
        if (Request::instance()->isPost()) {
            $id = Request::instance()->param('id');
            $data['status'] = 1;
            $data['refund_ids']='';
            $data['refund_reason']='';
            $bool = Db::name('spot_order')->where('id',$id)->update($data);
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
            $data['status'] = 1;
            $bool = Db::name('spot_order')->where('id',$id)->update($data);
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


    //获取未核销的人数
    public function getnocheck($orderid)
    {
        
        
    }

}
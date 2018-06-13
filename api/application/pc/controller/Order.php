<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/5/30
 * Time: 09:57
 */

namespace app\pc\controller;

use app\pc\model\OrderModel;
use think\Controller;
use think\Request;
use app\pc\model\MemberTravelerInfoModel;
use think\Session;
use think\Db;
use PDOException;

class Order extends Common
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 订单列表
     * */
    public function orderList(){
        $param = request()->param();
        $member = new OrderModel();
        $rs = $member->getAllList($param);
        echo json_encode($rs);die;
    }
    /**
     * 订单详情
     * */
    public function getOrderInfo(){
        $param = request()->param();
        $member = new OrderModel();
        $rs = $member->getOne($param['id']);
        if($rs!=false){
            echo json_encode(['status'=>0,'msg'=>'','data'=>$rs[0]]);
        }else{
            echo json_encode(['status'=>1,'msg'=>'暂无数据!','data'=>'']);
        }
        die;
    }

    /**
     * 根据订单id查询出游人
     * */
    public function getTrv(){
        $param = request()->param();
        $member = new OrderModel();
        $rs = $member->getOne($param['id']);
        $refund = [];
        $trv = [];
        if(!empty($rs[0]['traveler_ids'])){
            $sql = "select t.use_name,t.use_card,t.id,t.mobile from too_member_traveler_info t where t.id in({$rs[0]['traveler_ids']})";
            $trv = Db::query($sql);
        }
        if(!empty($rs[0]['refund_reason'])){
            $sql = "select t.use_name,t.use_card,t.id from too_member_traveler_info t where t.id in({$rs[0]['refund_reason']})";
            $refund = Db::query($sql);
        }
        $new = [];
        foreach ($trv as $k=>$v){
            if(in_array($v,$refund)){
                $v['status'] = 2;
                $new[] = $v;
            }else{
                $v['status'] = 1;
                $new[] = $v;
            }
        }
        echo json_encode(['status'=>0,'msg'=>'','data'=>$new]);die;
    }

    /**
     * 申请退款
     * traveller_ids 退款人集合字符串
     * refund_reason 退款理由
     * order 订单号
     * */
    public function tuikuan(){
        if(Request::instance()->isPost()){
            $info= Request::instance()->param();
            //前端传的出游人id ,退款理由
//            $new_refund_ids=substr($info['traveller_ids'], 0, -1);
//            $new_refund_reason=$info['refund_reason'];
//            $order_sn=$info['order'];
//            //数据库部分退款id,及理由
//            $order_info=Db::name('spot_order')->where('order_sn',$order_sn)->find();
//            $before_refund_ids=$order_info['refund_ids'];
//            $before_refund_reason=$order_info['refund_reason'];
//            //将新的退款与旧的重组
//            if($before_refund_ids){
//                $data['refund_ids']=$before_refund_ids.','.$new_refund_ids;
//                $data['refund_reason']=$before_refund_reason.','.$new_refund_reason;
//            }else{
//                $data['refund_ids']=$new_refund_ids;
//                $data['refund_reason']=$new_refund_reason;
//            }
//
//            $traveler_ids= $order_info['traveler_ids'];
//            $traveler_ids =explode(",",$traveler_ids);
//            $refund_arr=explode(",", $data['refund_ids']);
            try{
                //判断是否全部退款
//                if(count($traveler_ids)==count($refund_arr)){
                    $data['status'] = 2;
                    $data['refund_time']=date("Y-m-d H:i:s");
                    $result= Db::name('spot_order')->where('order_sn',trim($info['order_sn']))->update($data);
                    if($result===false){
                        throw new PDOException('申请失败！');
                    }
//                }
//                elseif(count($traveler_ids)>count($refund_arr)){
//                    $data['status']=6;
//                    $data['refund_time']=date("Y-m-d H:i:s");
//                    $result=Db::name('spot_order')->where('order_sn',$order_sn)->update($data);
//                    if($result===false){
//                        throw new PDOException('申请失败！');
//                    }
//                }
                Db::commit();
                echo json_encode(['status'=>0,'msg'=>'申请退款成功!']);die;
            }catch (PDOException $exception){
                Db::rollback();
                echo json_encode(['status'=>-1,'msg'=>$exception->getMessage(),'data'=>[]]);die;
            }
        }
        $res['status'] = -1;
        $res['msg'] = '非法请求';
        echo json_encode($res);
    }

    //取消退款
    public function applyCancel() {
        if (Request::instance()->isPost()) {
            $id = Request::instance()->param('order_sn');
            $data['status'] = 1;
            $data['refund_ids']='';
            $data['refund_reason']='';
            try{
                $bool = Db::name('spot_order')->where('order_sn',$id)->update($data);
                if($bool===false){
                    throw new PDOException('取消失败！');
                }
                Db::commit();
                echo json_encode(['status'=>0,'msg'=>'取消退款成功!']);die;
            }catch (PDOException $exception){
                Db::rollback();
                echo json_encode(['status'=>-1,'msg'=>$exception->getMessage(),'data'=>[]]);die;
            }
            exit;
        }
        $res['status'] = -1;
        $res['msg'] = '非法请求';
        echo json_encode($res);
    }

    //删除订单
    public function del() {
        if (Request::instance()->isPost()) {
            $id = Request::instance()->param('id');
            $data['delete'] = 1;
            try{
                $bool = Db::name('spot_order')->where('id',$id)->update($data);
                if($bool===false){
                    throw new PDOException('删除失败！');
                }
                Db::commit();
                echo json_encode(['status'=>0,'msg'=>'删除成功!']);die;
            }catch (PDOException $exception){
                Db::rollback();
                echo json_encode(['status'=>-1,'msg'=>$exception->getMessage(),'data'=>[]]);die;
            }
            exit;
        }
        $res['status'] = -1;
        $res['msg'] = '非法请求';
        echo json_encode($res);
    }

}
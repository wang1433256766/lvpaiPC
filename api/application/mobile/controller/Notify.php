<?php
namespace app\mobile\controller;
use think\Controller;
use think\Db;
use think\Request;
use think\Log;
use think\Config;


class Notify extends Controller
{
    //核销来自票务云的回调
	public function index()
	{
	    echo '200';
        if (Request::instance()->isPost()) {
            $poststr = file_get_contents('php://input');
            $arr = json_decode($poststr,true);       //json转换成数组
            
            $where['order_sn'] = $arr['remoteSn'];
            $where['order_code'] = $arr['orderSn'];
           // $where['status'] = 1;
            $bool = Db::name('spot_order')->where($where)->find();
            Log::write($bool);
            Log::write("bool");
            if (!empty($bool)) {
                $data['status'] = 5;
                $data['up_time'] = time();
                Db::name('spot_order')->where($where)->update($data);
                //游客加旅行币
                $member['score'] = $bool['order_total']*0.05*20;
                Db::name('mall_member')->where('id',$bool['member_id'])->setInc('score',$member['score']);
                $detail['title'] = '使用门票';
                $detail['member_id'] = $bool['member_id'];
                $detail['num'] = $member['score'];
                $detail['add_time'] = time();
                $detail['type'] = 0;
                Db::name('travel_details')->insert($detail);
                if ($bool['promote_id']) {
                    //分销商返佣
                    $where1['member_id'] = $bool['promote_id'];
                    $where1['give_id'] = $bool['member_id'];
                    $bo = Db::name('member_client')->where($where1)->find();
                    $mem = Db::name('mall_member')->where('id',$bool['member_id'])->find();
                    $type = Db::name('mall_member')->where('id',$bool['promote_id'])->value('type');
                    $distribution = Db::name('ticket')->where('id',$bool['ticket_id'])->value('distribution');
                    $refund_num = '';
                    //获取退款人数
                    if($bool['refund_price'] > 0)
                    {
                        //实消费
                       // $real_payment = $bool['total'] - $bool['refund_price'];
                        $refund_num =  floor($bool['refund_price']/$bool['price']);
                    }
                    
                   
                    $distribution = $distribution * ($bool['num'] - $refund_num);

                    $second_distribution = Db::name('ticket')->where('id',$bool['ticket_id'])->value('second_distribution');
                    $second_distribution = $second_distribution * ($bool['num'] - $refund_num);
                    if($type == 1)
                    {
                        if (!$bo) {
                            $click['member_id'] = $bool['promote_id'];
                            $click['nickname'] = Db::name('mall_member')->where('id',$bool['member_id'])->value('nickname');
                            $click['mobile'] = $mem['mobile'];
                            $click['add_time'] = time();
                            $click['give_id'] = $bool['member_id'];
                            $click['price'] = $distribution;
                            Db::name('member_client')->insert($click);
                        }else {
                            Db::name('member_client')->where($where1)->setInc('price',$distribution);
                        }
                        // 插入增加佣金记录
                        //先查询有没有发过佣金
                        $promote_info = Db::name("member_promote")->where("order_id",$bool['id'])->find();
                        if(empty($promote_info))
                        {
                            $promote['member_id'] = $bool['promote_id'];
                            $promote['order_id'] = $bool['id'];
                            $promote['total'] = $distribution;
                            $promote['ticket_id'] = $bool['ticket_id'];
                            $promote['add_time'] = time();
                            Db::name('member_promote')->insert($promote);
                            //if()
                            $FirIncmoney = Db::name('mall_member')->where('id',$bool['promote_id'])->setInc('money',$distribution);
                           //是否成功返佣
                           log::write($FirIncmoney);
                           log::write("是否成功返佣一级");
                        }
                        
                    }
                    if($type == 2)
                    {
                        if (!$bo) {
                            $click['member_id'] = $bool['promote_id'];
                            $click['nickname'] = Db::name('mall_member')->where('id',$bool['member_id'])->value('nickname');
                            $click['mobile'] = $mem['mobile'];
                            $click['add_time'] = time();
                            $click['give_id'] = $bool['member_id'];
                            $click['price'] = $second_distribution;
                           $re = Db::name('member_client')->insert($click);
                           if($re)
                           {
                             $parent_id = Db::name("mall_member")->where("id",$bool['promote_id'])->value("parent_id");
                             //二级给一级返利金额
                             $rebate = $distribution - $second_distribution;
                             $rebate_res = Db::name("mall_member")->where("id",$parent_id)->setInc("money",$rebate);
                             //是否成功返利
                             log::write($rebate_res);
                             log::write("是否成功返利");
                           }
                        }else {
                            $re = Db::name('member_client')->where($where1)->setInc('price',$second_distribution);
                            if($re)
                           {
                             $parent_id = Db::name("mall_member")->where("id",$bool['promote_id'])->value("parent_id");
                             //二级给一级返利金额
                             $rebate = $distribution - $second_distribution;
                             Db::name("mall_member")->where("id",$parent_id)->setInc("money",$rebate);
                           }
                        }

                        $second_promote_info = Db::name("member_promote")->where("order_id",$bool['id'])->find();
                        if(empty($second_promote_info))
                        {
                            $SenIncmoney = Db::name('mall_member')->where('id',$bool['promote_id'])->setInc('money',$second_distribution);

                            log::write($SenIncmoney);
                           log::write("是否成功返佣二级");
                            $promote['member_id'] = $bool['promote_id'];
                            $promote['order_id'] = $bool['id'];
                            $promote['total'] = $second_distribution;
                            $promote['ticket_id'] = $bool['ticket_id'];
                            $promote['add_time'] = time();
                            Db::name('member_promote')->insert($promote);
                        }
                        
                    }

                     $checkorder =  Db::name("check_order")->where("orderSn",$bool['order_code'])->find();
                    if(empty($checkorder)){
                        $check_bool = $this->checkOrderInsert($bool['id']);
                        log::write($check_bool);
                        log::write("插入到核销库");

                    }

                }
            }
        }
	}


   public function checkOrderInsert($orderid)
    //public function checkOrderInsert()
    {
        $where['spot.status'] = 5;
        $where['spot.payment'] = ['>',1];
        $where['too_ticket.is_team'] = 1;
        $where['spot.id'] = $orderid;
        $where['too_mall_member.type'] = ['>',0];
        $info = Db::name("spot_order")
                ->alias("spot")
                ->field("spot.*,too_mall_member.name,too_mall_member.id as memberId,too_mall_member.channel_id,too_mall_member.type as member_type,too_ticket.id,too_ticket.distribution,too_ticket.second_distribution")
                ->join("too_mall_member","too_mall_member.id = spot.member_id")
                ->join("too_ticket","too_ticket.id = spot.ticket_id")
                ->where($where)->find();
        if(!empty($info) && $info['total'] > $info['price']){
            if($info['spot_id'] == 10004){
            $data['spotName'] = '石燕湖';
            }
            if($info['spot_id'] == 10005){
                $data['spotName'] = '石牛寨';//景区
            }
            $data['orderSn'] = $info['order_code'];//WT订单号
            $data['ticketName'] = $info['ticket_name'];
            $data['orderNum'] = $info['num'];
            $data['price'] = $info['price'];
            $data['totalPrice'] = $info['total'];
            $data['checkNum'] = ($info['total'] - $info['refund_price'])/$info['price'];
            $data['settlePrice'] = $info['total'] - $info['refund_price'];
            if($info['member_type'] == 1){
                $data['money'] = $data['checkNum'] * $info['distribution'];
            }
            if($info['member_type'] == 2){
                $data['money'] = $data['checkNum'] * $info['second_distribution'];
            }
            if(!empty($info['travel_agency'])){
                $data['customerName'] = $info['travel_agency'];
            }else{
                $data['customerName'] = $info['name'];
            }
            $data['userName'] = $info['name'];
            $data['travelDate'] = $info['travel_date'];
            $data['payWay'] = $info['pay_way'];
            if($info['channel_id']){
                $data['salas'] = Db::name("mall_member")->where("id",$info['channel_id'])->value("name");
            }else{
                $data['salas'] = 'NULL';
            }
            $check_order = Db::name("check_order")->where("orderSn",$info['order_code'])->find();
            if($data['checkNum'] >= 10 && empty($check_order)){
                $bool =  Db::name("check_order")->insert($data);
            }
           
        }
        
        
    }

	//核销来自票务云的回调
    public function fenxiao_notify($orderid)
    {
        //$id = request()->param("orderid");
        if ($orderid) {
           // $poststr = file_get_contents('php://input');
            //$arr = json_decode($poststr,true);       //json转换成数组
            
            $where['id'] = $orderid;
           //$where['order_code'] = $arr['orderSn'];
            $bool = Db::name('spot_order')->where($where)->find();
            Log::write($bool);
            Log::write("bool");
            if (!empty($bool)) {
                $data['status'] = 5;
                $data['up_time'] = time();
                Db::name('spot_order')->where($where)->update($data);
                //游客加旅行币
                $member['score'] = $bool['order_total']*0.05*20;
                Db::name('mall_member')->where('id',$bool['member_id'])->setInc('score',$member['score']);
                $detail['title'] = '使用门票';
                $detail['member_id'] = $bool['member_id'];
                $detail['num'] = $member['score'];
                $detail['add_time'] = time();
                $detail['type'] = 0;
                Db::name('travel_details')->insert($detail);
                if ($bool['promote_id']) {
                    //分销商返佣
                    $where1['member_id'] = $bool['promote_id'];
                    $where1['give_id'] = $bool['member_id'];
                    $bo = Db::name('member_client')->where($where1)->find();
                    $mem = Db::name('mall_member')->where('id',$bool['member_id'])->find();
                    $type = Db::name('mall_member')->where('id',$bool['promote_id'])->value('type');
                    $distribution = Db::name('ticket')->where('id',$bool['ticket_id'])->value('distribution');
                    $refund_num = '';
                    //获取退款人数
                    if($bool['refund_price'] > 0)
                    {
                        //实消费
                       // $real_payment = $bool['total'] - $bool['refund_price'];
                        $refund_num =  floor($bool['refund_price']/$bool['price']);
                    }
                    
                   
                    $distribution = $distribution * ($bool['num'] - $refund_num);

                    $second_distribution = Db::name('ticket')->where('id',$bool['ticket_id'])->value('second_distribution');
                    $second_distribution = $second_distribution * ($bool['num'] - $refund_num);
                    if($type == 1)
                    {
                        if (!$bo) {
                            $click['member_id'] = $bool['promote_id'];
                            $click['nickname'] = Db::name('mall_member')->where('id',$bool['member_id'])->value('nickname');
                            $click['mobile'] = $mem['mobile'];
                            $click['add_time'] = time();
                            $click['give_id'] = $bool['member_id'];
                            $click['price'] = $distribution;
                            Db::name('member_client')->insert($click);
                        }else {
                            Db::name('member_client')->where($where1)->setInc('price',$distribution);
                        }
                        // 插入增加佣金记录
                        //先查询有没有发过佣金
                        $promote_info = Db::name("member_promote")->where("order_id",$bool['id'])->find();
                        if(empty($promote_info))
                        {
                            $promote['member_id'] = $bool['promote_id'];
                            $promote['order_id'] = $bool['id'];
                            $promote['total'] = $distribution;
                            $promote['ticket_id'] = $bool['ticket_id'];
                            $promote['add_time'] = time();
                            Db::name('member_promote')->insert($promote);
                            //if()
                            $FirIncmoney = Db::name('mall_member')->where('id',$bool['promote_id'])->setInc('money',$distribution);
                           //是否成功返佣
                           log::write($FirIncmoney);
                           log::write("是否成功返佣一级");
                        }
                        
                    }
                    if($type == 2)
                    {
                        if (!$bo) {
                            $click['member_id'] = $bool['promote_id'];
                            $click['nickname'] = Db::name('mall_member')->where('id',$bool['member_id'])->value('nickname');
                            $click['mobile'] = $mem['mobile'];
                            $click['add_time'] = time();
                            $click['give_id'] = $bool['member_id'];
                            $click['price'] = $second_distribution;
                           $re = Db::name('member_client')->insert($click);
                           if($re)
                           {
                             $parent_id = Db::name("mall_member")->where("id",$bool['promote_id'])->value("parent_id");
                             //二级给一级返利金额
                             $rebate = $distribution - $second_distribution;
                             $rebate_res = Db::name("mall_member")->where("id",$parent_id)->setInc("money",$rebate);
                             //是否成功返利
                             log::write($rebate_res);
                             log::write("是否成功返利");
                           }
                        }else {
                            $re = Db::name('member_client')->where($where1)->setInc('price',$second_distribution);
                            if($re)
                           {
                             $parent_id = Db::name("mall_member")->where("id",$bool['promote_id'])->value("parent_id");
                             //二级给一级返利金额
                             $rebate = $distribution - $second_distribution;
                             Db::name("mall_member")->where("id",$parent_id)->setInc("money",$rebate);
                           }
                        }

                        $second_promote_info = Db::name("member_promote")->where("order_id",$bool['id'])->find();
                        if(empty($second_promote_info))
                        {
                            $SenIncmoney = Db::name('mall_member')->where('id',$bool['promote_id'])->setInc('money',$second_distribution);

                            log::write($SenIncmoney);
                           log::write("是否成功返佣二级");
                            $promote['member_id'] = $bool['promote_id'];
                            $promote['order_id'] = $bool['id'];
                            $promote['total'] = $second_distribution;
                            $promote['ticket_id'] = $bool['ticket_id'];
                            $promote['add_time'] = time();
                            Db::name('member_promote')->insert($promote);
                        }
                        
                    }
                    $checkorder =  Db::name("check_order")->where("orderSn",$bool['order_code'])->find();
                    if(empty($checkorder)){
                        $check_bool = $this->checkOrderInsert($bool['id']);
                        log::write($check_bool);
                        log::write("插入到核销库");
                        
                        return $check_bool;
                    }

                    
                }
            }
        }
    }
}
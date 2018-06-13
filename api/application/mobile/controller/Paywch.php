<?php
namespace app\mobile\controller;
use think\Controller;
use think\Db;
use think\Request;
use think\Log;
use think\Config;


class Paywch extends Controller
{
    public function notify()
    {
        $postStr = file_get_contents("php://input");
        $info = xmlToArray($postStr);
        Log::write($info);
        $out_trade_no = $info['out_trade_no'];
        $trade_no = $info['transaction_id'];
        $order = Db::name('wch_order')->where('order_sn',$out_trade_no)->find();
        if ($order['status'] > 0) {
            echo 'SUCCESS';
            exit;
        }
        if ($order && $info['return_code'] == 'SUCCESS') {
            $data['payment'] = $info['total_fee'] / 100;
            $data['status'] = 1;
            $data['pay_way'] = '微信支付';
            $data['pay_time'] = time();
            $data['trade_no'] = $trade_no;
            $res = Db::name('wch_order')->where('id',$order['id'])->update($data);
            if ($order['status'] == 0) {
                // $res = $this->checkOrder($order);
                // Log::write($res);
                // if ($res['code'] == 200){
                //     $order1['UUcode']   = $res['qrCode'];
                //     $order1['order_code'] = $res['orderSn'];
                //     $order1['up_time'] = time();
                //     Db::name('spot_order')->where('id',$order['id'])->update($order1);
                // }
                if (!empty($order)) {

                    $score = Db::name('mall_member')->where('id',$order['member_id'])->value('score');
                    //开始减除使用的积分
                    $pro_score = Db::name("score_goods")->where("id",$order['product_id'])->value("integral");
                    $total_score = $pro_score * $order['pro_num'];
                    Db::name("mall_member")->where("id",$order['member_id'])->SetDec("score",$order['pay_score']);
                    //经该 商品 销量+1
                    Db::name("score_goods")->where("id",$order['product_id'])->SetInc("already_count",$order['pro_num']);
                    //更改订单状态
                    Db::name("wch_order")->where("id",$order['id'])->setField("status",1);
                    
                    $xf_data['title'] = '兑换文创产品';
                    $xf_data['member_id'] = $order['member_id'];
                    $xf_data['num'] = '-'.$total_score;
                    $xf_data['add_time'] = time();
                    Db::name('travel_details')->insert($detail);
                }
            }
            echo 'SUCCESS';
        }

    }

    public function checkOrder($info) {
        $admin = Config::get('pwy');
        $time =time();
        $user = Db::name('member_traveler_info')->field('use_name,use_card')->where('id','in',$info['traveler_ids'])->select();
        $use_card = array_column($user,'use_card');
        $use_name = array_column($user,'use_name');
        $username = implode(',',$use_name);
        $id_card = implode(',',$use_card);
        $data =[
            'account'=>$admin['ac'],
            'timestamp'=>$time,
            'sing'=>md5($admin['pw'].$time.$admin['pw']),
            'spotId'=> $info['spot_id'],
            'ticketId' => $info['t_id'],
            'otherSn' => $info['order_sn'],
            'oprice' => $info['price'],
            'onum' => $info['num'],
            'playTime' => $info['travel_date'],
            'useName'=> $username,
            'mobile'=> $info['mobile'],
            'useCard' => $id_card,
            'smsSend'=>1,
            'paymode'=>2
        ];
        $url ='http://cloud.zhonghuilv.net/spot/SubmitOrder';
        $res = request_post($url,$data);
        $res = json_decode($res,TRUE);
        return $res;
    }

}
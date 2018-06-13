<?php
namespace app\mobile\controller;
use think\Controller;
use think\Db;
use think\Request;
use think\Log;
use think\Config;
use com\Msg;


class Pay extends Controller
{
	public function notify()
	{
        $postStr = file_get_contents("php://input");
        $info = xmlToArray($postStr);
        Log::write($info);
        $out_trade_no = $info['out_trade_no'];
        $trade_no = $info['transaction_id'];
        $order = Db::name('spot_order')->where('order_sn',$out_trade_no)->find();
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
            $res = Db::name('spot_order')->where('id',$order['id'])->update($data);
            if ($order['status'] == 0) {
                log::write($res);
                log::write($order['is_team']);
                if($order['is_team'] == 0)
                {
                    $res = $this->checkOrder($order);
                }
                else
                {
                    $res = $this->checkTeamOrder($order);
                }
                
                Log::write($res);
                if ($res['code'] == 200){
                    $order1['UUcode']   = $res['qrCode'];
                    $order1['order_code'] = $res['orderSn'];
                    $order1['up_time'] = time();
                    Db::name('spot_order')->where('id',$order['id'])->update($order1);
                    $this->send($order,$res['qrCode']);
                }
                if ($order['rebate_total'] != 0) {
                    $score = Db::name('mall_member')->where('id',$order['member_id'])->value('score');
                    $arr['score'] = $score - $order['rebate_total'] * 20;
                    Db::name('mall_member')->where('id',$order['member_id'])->setField('score', $arr['score']);
                    $detail['title'] = '门票减免';
                    $detail['member_id'] = $order['member_id'];
                    $detail['num'] = '-'.$order['rebate_total']*20;
                    $detail['add_time'] = time();
                    $detail['type'] = 0;
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
            'smsSend'=>0,
            'paymode'=>2
        ];
        $url ='http://cloud.zhonghuilv.net/spot/SubmitOrder';
        $res = request_post($url,$data);
        $res = json_decode($res,TRUE);

        //年卡需要向石燕湖 石牛寨 都下一单
        if ($info['t_id'] == 999) {
            $data =[
                'account'=>$admin['ac'],
                'timestamp'=>$time,
                'sing'=>md5($admin['pw'].$time.$admin['pw']),
                'spotId'=> 10004,
                'ticketId' => 998,
                'otherSn' => $info['order_sn'],
                'oprice' => $info['price'],
                'onum' => $info['num'],
                'playTime' => $info['travel_date'],
                'useName'=> $username,
                'mobile'=> $info['mobile'],
                'useCard' => $id_card,
                'smsSend'=>0,
                'paymode'=>2
            ];
            $url ='http://cloud.zhonghuilv.net/spot/SubmitOrder';
            $res = request_post($url,$data);
            $res = json_decode($res,TRUE);
        }
        return $res;
    }

    public function checkTeamOrder($info) {
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
            'smsSend'=>0,
            'paymode'=>2,
            'CustomerName' => $info['travel_agency'],
            'OrderType' => 1,
        ];
        $url ='http://cloud.zhonghuilv.net/spot/SubmitOrder';
        $res = request_post($url,$data);
        $res = json_decode($res,TRUE);

        // //年卡需要向石燕湖 石牛寨 都下一单
        // if ($info['t_id'] == 999) {
        //     $data =[
        //         'account'=>$admin['ac'],
        //         'timestamp'=>$time,
        //         'sing'=>md5($admin['pw'].$time.$admin['pw']),
        //         'spotId'=> 10004,
        //         'ticketId' => 998,
        //         'otherSn' => $info['order_sn'],
        //         'oprice' => $info['price'],
        //         'onum' => $info['num'],
        //         'playTime' => $info['travel_date'],
        //         'useName'=> $username,
        //         'mobile'=> $info['mobile'],
        //         'useCard' => $id_card,
        //         'smsSend'=>0,
        //         'paymode'=>2
        //     ];
        //     $url ='http://cloud.zhonghuilv.net/spot/SubmitOrder';
        //     $res = request_post($url,$data);
        //     $res = json_decode($res,TRUE);
        // }
        return $res;
    }

    public function send($order,$code)
    {
        $content = '尊敬的用户，你已成功购买{#PRODUCT#}，消费日期：{#DATE#}，凭证号：{#CODE}，请凭身份证入园或短信凭证码刷码入园，此为凭证，请妥善保管';
        $content = str_ireplace('{#PRODUCT#}', $order['ticket_name'], $content);
        $content = str_ireplace('{#DATE#}', $order['travel_date'], $content);
        $content = str_ireplace('{#CODE}', $code, $content);
        $mobile = $order['mobile'];
        $prefix = '';
        $user = 'cf_zhonghuilv';
        $pass = 'eb2a1a963b116ae15e7cb2bf41382bf4';

        $msg = new \com\Msg($user,$pass);
        $info = $msg->sendMsg($mobile, $prefix, $content);
        Log::write($info);

    }


}
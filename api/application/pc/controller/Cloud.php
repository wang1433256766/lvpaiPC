<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/5/28
 * Time: 11:58
 */

namespace app\pc\controller;


use think\Config;

class Cloud extends Common
{
    private $url = 'http://cloud.zhonghuilv.net/spot/';
    private $account = '1';
    private $password = 'yxgs';
    const WECHAT_PAY = 2; // 微信支付
    const BALANCE_PAY = 3; // 余额支付

    /**
     * 订单验证
     * @param $spot_id
     * @param $t_id
     * @param $order_sn
     * @param $price
     * @param $num
     * @param $travel_date
     * @param $use_name
     * @param $mobile
     * @param $use_card
     * @param $travel_agency
     * @param int $is_team
     * @return mixed
     */
    public function validationOrder($spot_id, $t_id, $order_sn, $price, $num, $travel_date, $use_name, $mobile, $use_card, $travel_agency, $is_team = 1)
    {
        $post_data = $this->readyCloud($spot_id, $t_id, $order_sn, $price, $num, $travel_date, $use_name, $mobile, $use_card, $travel_agency, $is_team);
        return json_decode($this->httpPost($this->url . 'ValidationOrder', $post_data), true);
    }


    /**
     * 提交订单
     * @param $spot_id
     * @param $t_id
     * @param $order_sn
     * @param $price
     * @param $num
     * @param $travel_date
     * @param $use_name
     * @param $mobile
     * @param $use_card
     * @param $pay_mode
     * @param $sms_send
     * @param $travel_agency
     * @param string $remark
     * @param int $is_team
     * @return mixed
     */
    public function submitOrder($spot_id, $t_id, $order_sn, $price, $num, $travel_date, $use_name, $mobile, $use_card, $pay_mode, $sms_send, $travel_agency, $remark = '', $is_team = 1)
    {
        $post_data = $this->readyCloud($spot_id, $t_id, $order_sn, $price, $num, $travel_date, $use_name, $mobile, $use_card, $travel_agency, $is_team);
        $post_data['paymode'] = $pay_mode;
        $post_data['smsSend'] = $sms_send;
        $post_data['remark'] = $remark;
        return json_decode($this->httpPost($this->url . 'SubmitOrder', $post_data), true);

    }

    /**
     * 查询订单
     * @param $order_no
     * @param $cloud_order
     * @return mixed
     */
    public function orderQuery($order_no, $cloud_order)
    {
        $post_data = [
            'account' => $this->account,
            'timestamp' => time(),
            'sing' => strtolower(md5($this->password . time() . $this->password)),
            'cloudOrderSn' => $cloud_order,
            'remoteOrderSn' => $order_no
        ];
        return json_decode($this->httpPost($this->url . 'OrderQuery', $post_data), true);
    }


    /**
     * 公共部分组装
     * @param $spot_id
     * @param $t_id
     * @param $order_sn
     * @param $price
     * @param $num
     * @param $travel_date
     * @param $use_name
     * @param $mobile
     * @param $use_card
     * @param $travel_agency
     * @param int $is_team
     * @return array
     */
    public function readyCloud($spot_id, $t_id, $order_sn, $price, $num, $travel_date, $use_name, $mobile, $use_card, $travel_agency, $is_team)
    {
        $post_data = [
            'account' => $this->account,
            'timestamp' => time(),
            'sing' => strtolower(md5($this->password . time() . $this->password)),
            'spotId' => $spot_id,
            'ticketId' => $t_id,
            'otherSn' => $order_sn,
            'oprice' => $price,
            'onum' => $num,
            'playTime' => $travel_date,
            'useName' => $use_name,
            'mobile' => $mobile,
            'useCard' => $use_card,
            'travel_agency' => $travel_agency,
            'is_team' => $is_team
        ];
        return $post_data;
    }

    /**
     * 查询门票列表
     * @param $spot_id
     * @param $t_id
     * @return mixed
     */
    public function getTicketList($spot_id, $t_id)
    {
        $post_data = [
            'account' => $this->account,
            'timestamp' => time(),
            'sing' => strtolower(md5($this->password . time() . $this->password)),
            'spotId' => $spot_id,
            'ticketId' => $t_id
        ];
        return json_decode($this->httpPost($this->url . 'GetTicketList', $post_data), true);
    }
}
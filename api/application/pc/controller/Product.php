<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/5/26
 * Time: 10:23
 */

namespace app\pc\controller;


use app\pc\model\MallMemberModel;
use app\pc\model\MemberTravelerInfoModel;
use app\pc\model\SpotOrderModel;
use app\pc\model\TicketModel;
use app\pc\model\TravelDetailsModel;
use think\Log;
use think\Request;
use think\Session;

class Product extends Common
{
    // 获取当前景点信息
    public function getProductPriceInfo()
    {
        $product_id = Request::instance()->param('id');
        $ticket = TicketModel::getProductFieldInfo($product_id, 'id,ticket_name,market_price,shop_price,img,sale_num,opentime');
        $member = MallMemberModel::getModelById(Session::get('user.id'));
        if (! is_string($ticket) && $ticket) {
            $data = end($ticket);
            $data['travel_agency'] = is_array($member) ? $member['travel_agency'] : '';
            $this->ajaxReturn(0, $data, '');
        } else $this->ajaxReturn(1, '', '景点不存在');
    }

    /**
     * 匹配服务器上的图片
     * @param string $content
     * @param string $suffix
     * @return null|string|string[]
     */
    private function setUrl($content="", $suffix="http://lvpai.zhonghuilv.net")
    {
        $pregRule = "/<[img|IMG].*?src=[\'|\"](?!http:\/\/)(.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?>/";
        $content = preg_replace($pregRule, '<img src="'.$suffix.'${1}'.'" style="max-width:100%" />', $content);
        return $content;
    }

    /**
     * 获取景点介绍信息
     */
    public function getProductDesc()
    {
        $product_id = Request::instance()->param('id');
        $ticket = TicketModel::getProductFieldInfo($product_id, 'desc');
        if (is_string($ticket) || ! $ticket) $this->ajaxReturn(1, '', '景点介绍不存在');
        else {
            $content = $this->setUrl(end($ticket)['desc']);
            $this->ajaxReturn(0, ['desc' => $content], '');
        }
    }

    // 温馨提示
    public function getProductTips()
    {
        $product_id = Request::instance()->param('id');
        $ticket = TicketModel::getProductFieldInfo($product_id, 'tips');
        if (is_string($ticket) || ! $ticket) $this->ajaxReturn(1, '', '景点介绍不存在');
        else $this->ajaxReturn(0, end($ticket), '');
    }

    // 安全须知
    public function getProductSafety()
    {
        $product_id = Request::instance()->param('id');
        $ticket = TicketModel::getProductFieldInfo($product_id, 'safety');
        if (is_string($ticket) || ! $ticket) $this->ajaxReturn(1, '', '景点介绍不存在');
        else $this->ajaxReturn(0, end($ticket), '');
    }

    // 线路介绍
    public function getProductAddress()
    {
        $product_id = Request::instance()->param('id');
        $ticket = TicketModel::getProductFieldInfo($product_id, 'address');
        if (is_string($ticket) || ! $ticket) $this->ajaxReturn(1, '', '景点介绍不存在');
        else $this->ajaxReturn(0, end($ticket), '');
    }

    // 获取出游人信息
    public function getTravelInfo()
    {
        $member_id = Session::get('user.id');
        $traveler_info = MemberTravelerInfoModel::getTravelerInfo($member_id);
        if (is_string($traveler_info)) $this->ajaxReturn(1, '', $traveler_info);
        else $this->ajaxReturn(0, $traveler_info, '');
    }

    // 创建订单
    public function createOrder()
    {
        $order_info = Request::instance()->param();
        $member_id = Session::get('user.id');
        if (! isset($order_info['ticket_id']) || ! $order_info['ticket_id']) $this->ajaxReturn(1, '', '当前景区票异常');
        if (! isset($order_info['travel_date']) || ! $order_info['travel_date']) $this->ajaxReturn(1, '', '请选择出发时间');
//        if (strtotime($order_info['travel_date']) <= strtotime(date('Y-m-d'))) $this->ajaxReturn(1, '', '不允许购买当天或以前日期的票');
        if (! isset($order_info['num']) || $order_info['num'] < 10) $this->ajaxReturn(1, '', '出游人数不允许小于10');
        if (! isset($order_info['travel_agency']) || ! $order_info['travel_agency']) $this->ajaxReturn(1, '', '请填写团队名称');
        if ($order_info['traveler_ids'] != '') {
            if (count(explode(',', trim($order_info['traveler_ids']))) != $order_info['num']) $this->ajaxReturn(1, '', '出游人数与票数不等,您可以选择不填写出游人');
            if ($this->verifyCard($order_info['traveler_ids'], $order_info['ticket_id'])) $this->ajaxReturn(1, '', '同张身份证30天内只能买两次相同的票');
        }
        // 获取选择的门票信息
        $ticket = TicketModel::getProductFieldInfo($order_info['ticket_id'], 'spot_id,t_id,ticket_name,market_price,shop_price,is_team,buy_time');
        if (is_string($ticket)) $this->ajaxReturn(1, '', $ticket);
        $ticket = end($ticket);
        if (isset($ticket['buy_time']) && $ticket['buy_time']) {
            $buy_time = strtotime(date('Y-m-d ' . $ticket['buy_time']));
            if (time() >= $buy_time) $this->ajaxReturn(1, '', '本店已打烊,客官明日请早');
        }
        // 获取当前人的信息
        $member = MallMemberModel::getModelById($member_id);
        if (is_string($member)) $this->ajaxReturn(2, '', $member);
        // 组装订单数据
        $data = [
            'source' => 'pc',
            'order_sn' => 'LP' . date('ymdhis', time()) . $this->get_rand_num(4),
            'ticket_id' => $order_info['ticket_id'],
            'spot_id' => $ticket['spot_id'],
            't_id' => $ticket['t_id'],
            'ticket_name' => $ticket['ticket_name'],
            'cost_price' => $ticket['market_price'],
            'price' => $ticket['shop_price'],
            'num' => $order_info['num'],
            'travel_date' => $order_info['travel_date'],
            'traveler_ids' => $order_info['traveler_ids'],
            'order_total' => $ticket['shop_price'] * $order_info['num'],
            'total' => $ticket['shop_price'] * $order_info['num'],
            'member_id' => $member_id,
            'mobile' => $member['mobile'],
            'rebate_total' => 0,
            'travel_agency' => $order_info['travel_agency'],
            'is_team' => isset($order_info['is_team']) ? $order_info['is_team'] : 1,
            'add_time' => time(),
            'status' => 0,
            'promote_id' => $member['type'] > 0 ? $member['id'] : 0
        ];
        $check = isset($order_info['check']) ? $order_info['check'] : 0;
        // 是否使用旅行币
        if ($check == 1) {
            list($data['order_total'], $data['total']) = $this->scoreConversion($member['score'], $data['order_total']);
        } else $data['total'] = $data['order_total'];
        // 获取出游人信息
        $member_traveler_info = [];
        if ($order_info['traveler_ids']) {
            $member_traveler_info = MemberTravelerInfoModel::getModelByIds($order_info['traveler_ids']);
            if (is_string($member_traveler_info)) $this->ajaxReturn(1, '', $member_traveler_info);
        }
        // 票务云验证订单
        $cloud = new Cloud();
        /** @var array $member_traveler_info */
        $validate_res = $cloud->validationOrder($ticket['spot_id'], $ticket['t_id'], $data['order_sn'], $data['total'], $order_info['num'], $order_info['travel_date'], implode(',', array_column($member_traveler_info, 'use_name')), $member['mobile'], implode(',', array_column($member_traveler_info, 'use_card')), $order_info['travel_agency']);
        if ($validate_res['code'] != 200) $this->ajaxReturn(1, '', $validate_res['message']);
        $msg = SpotOrderModel::insertOrder($data, $order_info['ticket_id']);
        if (is_string($msg)) $this->ajaxReturn(1, '', $msg);
        else $this->ajaxReturn(0, ['order_no' => $data['order_sn']], '创建订单成功');
    }

    /**
     * 使用旅行币后金额换算
     * @param string | int $score        旅行币数量
     * @param string | int $order_total  需要支付的总金额
     * @return array
     */
    public function scoreConversion($score, $order_total)
    {
        $rebate_total = number_format($score / 20,2);
        // 优惠金额大于应付总金额
        if ($rebate_total >= $order_total) {
            // 优惠金额等于总金额 强制性付费一分
            $rebate_total = $order_total;
            $total = 0.01;
        } else {
            $total = $order_total - $rebate_total;
        }
        return [$rebate_total, $total];
    }

    // 调起微信支付
    public function payByWechat()
    {
        $order_no = Request::instance()->param('order_no');
        if (! $order_no) $this->ajaxReturn(1, '', '订单号缺失');
        /** @var array $order */
        $order = SpotOrderModel::getModelByOrderNo($order_no);
        if (is_string($order)) $this->ajaxReturn(1, '', $order);
        $wechat = new Wechat();
        // $order['total'] * 100
        $res = $wechat->jsWechat( $order['total'] * 100, 'NATIVE', $_SERVER['SERVER_NAME'] . '/pc/wechat/notify', $order_no, $order['ticket_name']);
        if ($res['result_code'] == 'SUCCESS') {
            $this->ajaxReturn(0, '', 'http://paysdk.weixin.qq.com/example/qrcode.php?data=' . $res['code_url']);
        } else $this->ajaxReturn(1, '', $res['err_code_des']);
    }

    /**
     * 验证身份证近期是否使用过
     * @param $ids
     * @param $ticket_id
     * @return bool
     */
    public function verifyCard($ids, $ticket_id) {
        if (! $ids) return false;
        $flag = false;
        $arr = explode(',', trim($ids));
        foreach($arr as $v) {
            if (SpotOrderModel::checkTicket($ticket_id, $v) > 1) {
                $flag = true;
                break;
            }
        }
        return $flag;
    }

    /**
     * 支付完成后的回调处理
     * @param $post_data
     */
    public function operateAfterPay($post_data)
    {
        $order_sn = $post_data['out_trade_no'];
        /** @var array $order */
        $order = SpotOrderModel::getModelByOrderNo($order_sn);
        if (is_string($order)) Log::write(date('Y-m-d H:i:s') . 'order_no为' . $order_sn . '获取失败');
        if ($post_data['return_code'] == 'SUCCESS') {
            // 支付完成后订单表数据
            SpotOrderModel::updateInformationAfterPay($post_data, $order['id']);
            // 获取出游人信息
            /** @var array $member_traveler_info */
            $member_traveler_info = [];
            if ($order['traveler_ids']) $member_traveler_info = MemberTravelerInfoModel::getModelByIds($order['traveler_ids']);
            // 向票务系统提交订单
            $cloud = new Cloud();
            $sub_res = $cloud->submitOrder($order['spot_id'], $order['t_id'], $order_sn, $order['total'] * 100, $order['num'], $order['travel_date'], implode(',', array_column($member_traveler_info, 'use_name')), $order['mobile'], implode(',', array_column($member_traveler_info, 'use_card')), Cloud::WECHAT_PAY, 1, $order['travel_agency']);
            Log::write($sub_res);
            if ($sub_res['code'] != 200) {
                Log::write(date('Y-m-d H:i:s') . '订单提交失败,原因如下: ' . $sub_res['message']);
            } else {
                // 支付成功以后接受票务云返回凭证码进行存储
                $update_data = [
                    'UUcode' => isset($sub_res['qrCode']) ? $sub_res['qrCode'] : $sub_res['zhlCode'],
                    'order_code' => $sub_res['orderSn'],
                    'up_time' => time(),
                ];
                $update_res = SpotOrderModel::updateInformationById($update_data, $order['id']);
                // 更新失败记入日志
                if (is_string($update_res)) Log::write(date('Y-m-d H:i:s') . 'id:' . $order['id'] . '更新订单新失败' . $update_res);
                // 扣除减免的积分
                $user_id = Session::get('user.id');
                if ($order['rebate_total'] != 0) {
                    $res = TravelDetailsModel::insertTravelDetails('门票减免', '-' . $order['rebate_total'] * 20, $user_id, 0);
                    if (is_string($res)) Log::write(date('Y-m-d H:i:s') . 'id:' . $user_id . '积分消费详情插入失败:' . $res);
                }
            }
        }
    }

    // 验证订单是否成功
    public function getStatusByOrderNo()
    {
        $order_no = Request::instance()->post('order_no');
        if (! $order_no) $this->ajaxReturn(1, '', '信息缺失');
        $order = SpotOrderModel::getModelByOrderNo($order_no);
        if (! is_string($order)) {
            if ($order['status'] != 0) {
                if ($order['status'] == 1) $this->ajaxReturn(0, '', '支付成功');
            } else $this->ajaxReturn(2, '', '未支付');
        } else $this->ajaxReturn(1, '', $order);
    }

//    public function test()
//    {
//        $order_sn = 'LP1806110425576413';
//        $order = SpotOrderModel::getModelByOrderNo($order_sn);
//        $member_traveler_info = [];
//        if ($order['traveler_ids']) $member_traveler_info = MemberTravelerInfoModel::getModelByIds($order['traveler_ids']);
//        $use_name = implode(',', array_column($member_traveler_info, 'use_name'));
//        $use_card = implode(',', array_column($member_traveler_info, 'use_card'));
//        var_dump($use_name);
//        var_dump($use_card);
//    }




}
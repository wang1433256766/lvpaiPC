<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/5/25
 * Time: 16:21
 */

namespace app\pc\controller;


use app\pc\model\MallMemberModel;
use app\pc\model\TicketModel;
use think\Session;

class Index extends Common
{
    // 获取用户信息
    public function getUserInfo()
    {
        $member = MallMemberModel::getModelById(Session::get('user.id'));
        if (! is_string($member)) {
            $return_data = [
                'nickname' => $member['nickname'] ? $member['nickname'] : $member['mobile'],
                'headimg' => $member['headimg']
            ];
            $this->ajaxReturn(0, $return_data);
        } else $this->ajaxReturn(1, '' ,$member);
    }

    // 获取产品信息
    public function getProducts()
    {
        $allProducts = TicketModel::getAllProducts('id,ticket_name,market_price,shop_price,img,sale_num');
        if (is_string($allProducts)) $this->ajaxReturn(1, '', $allProducts);
        else $this->ajaxReturn(0, $allProducts, '');
    }
}

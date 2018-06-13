<?php

namespace app\admin\model;

use think\Model;

class ScoreorderModel extends Model
{
	protected $table = 'too_wch_order';

	public function getOrderByWhere($where, $offset, $limit)
    {
      return $this->field("too_wch_order.id,too_wch_order.order_ship,too_wch_order.add_time,too_wch_order.order_sn,
        too_wch_order.pro_num,too_score_goods.integral,too_wch_order.status,too_score_goods.name as goodsname,too_mall_member.nickname,too_mall_member.last_login_time,too_member_address.phone,too_member_address.username")
                    ->join("too_score_goods","too_score_goods.id=too_wch_order.product_id")
                    ->join("too_mall_member","too_mall_member.id=too_wch_order.member_id")
                    ->join("too_member_address","too_member_address.member_id=too_wch_order.member_id")
                    ->where($where)->limit($offset, $limit)->select();
    }

    public function getAllOrder($where)
    {
        return $this->where($where)->count();
    }
}
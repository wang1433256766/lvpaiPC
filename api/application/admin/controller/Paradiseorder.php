<?php

namespace app\admin\controller;

use think\Db;
use think\Controller;
use app\admin\model\ParadiseModel;

// 乐园模块的订单类
class Paradiseorder extends Controller
{
	public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['order_sn'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $order = new ParadiseModel();
            $selectResult = $order->getOrderByWhere($where, $offset, $limit);

            unset($selectResult['add_time']);

            $order_status = config('paradise_order_status');

            foreach($selectResult as $key=>$vo){
                
                $selectResult[$key]['add_time'] = date('Y-m-d h:i:s', $vo['add_time']); 

                // 订单状态
                $selectResult[$key]['status'] = $order_status[$vo['status']];

                $operate = [
                    '删除' => "javascript:rotaCancel('". $vo['id'] ."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $order->getAllOrder($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }
}
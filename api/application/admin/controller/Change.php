<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\model\ChangeModel;

class Change extends Controller
{
	// 兑换列表
	public function index()
	{
		if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['username'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $change = new ChangeModel();
            $selectResult = $change->getChangeByWhere($where, $offset, $limit);

            $status = config('change_status');

            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['status'] = $status[$vo['status']];

                $operate = [
                    '编辑' => url('user/userEdit', ['id' => $vo['id']]),
                    '删除' => "javascript:userDel('".$vo['id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
                
            }

            $return['total'] = $change->getAllChange($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
	}
}
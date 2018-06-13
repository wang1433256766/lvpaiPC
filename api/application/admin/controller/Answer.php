<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\model\AnswerModel;

class Answer extends Controller
{
	public function index()
	{
		if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['answer'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $answer = new AnswerModel();
            $selectResult = $answer->getAnswerByWhere($where, $offset, $limit);

            $status = config('user_status');

            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['status'] = 1;

                $operate = [
                    '删除' => "javascript:answerDel('".$vo['answer_id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $answer->getAllAnswer($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
	}

    // 删除回答
    public function answerDel()
    {
        $id = input('param.id');

        $role = new AnswerModel();
        $flag = $role->delAnswer($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }
}
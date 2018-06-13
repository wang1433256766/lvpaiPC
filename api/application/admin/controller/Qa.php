<?php

namespace app\admin\Controller;

use think\Controller;
use app\admin\model\QuestionModel;


class Qa extends Controller
{
	public function question()
	{	
		if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['title'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $question = new QuestionModel();
            $selectResult = $question->getQuestionByWhere($where, $offset, $limit);

            $status = config('user_status');

            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['status'] = 1;

                $operate = [
                    '删除' => "javascript:questionDel('".$vo['q_id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $question->getAllQuestion($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
	}

	// 删除问题
    public function questionDel()
    {
        $id = input('param.id');

        $role = new QuestionModel();
        $flag = $role->delQuestion($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    // 回答列表 
    public function answer()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['title'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $question = new QuestionModel();
            $selectResult = $question->getAnswerByWhere($where, $offset, $limit);

            $status = config('user_status');

            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['status'] = 1;

                $operate = [
                    '删除' => "javascript:questionDel('".$vo['a_id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $question->getAllQuestion($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }
}
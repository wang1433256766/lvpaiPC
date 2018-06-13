<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\model\RotaModel;
use app\admin\model\NewsModel;

class Rota extends Controller 
{
	public function index()
	{
		if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['title'] = ['like', $param['searchText']];
            }
            $news = new RotaModel();
            $selectResult = $news->getNewsByWhere($where, $offset, $limit);

            $base = config('base');


            foreach($selectResult as $key=>$vo){

               $selectResult[$key]['base'] = $base[$vo['base']];


                $operate = [
                    '编辑' => url('news/newsEdit', ['id' => $vo['id']]),
                    '取消轮播' => "javascript:cancelRota('".$vo['id']."')",
                    '删除' => "javascript:newsDel('".$vo['id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $news->getAllNews($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();	
	}

	public function newsDel()
	{
		$id = input('id');

		$new = new NewsModel;

		//删除图片
		$new->delImg($id);

		$res = $new->delNews($id);

		return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
	}

	// 取消轮播
	public function cancelRota()
	{
		$id = input('id');

		$new = new RotaModel;

		$res = $new->cancelRota($id);

		return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
	}
}
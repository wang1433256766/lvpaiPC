<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\model\NewsRotaModel;
use app\admin\model\NewsModel;
use think\Db;

// 新闻轮播
class Newsrota extends Controller 
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
            $news = new NewsRotaModel();

            $selectResult = $news->getNewsByWhere($where, $offset, $limit);
            // $base = config('base');


            foreach($selectResult as $key=>$vo){

               // $selectResult[$key]['base'] = $base[$vo['base']];

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
		$param = request()->param();
		$id = $param['id'];

		$new = new NewsRotaModel;

		$res = $new->cancelRota($id);

		return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
	}

	// 改变轮播顺序
	public function changeSort()
	{
		$param = request()->param();

		$arr['rota_sort'] = $param['value'];

		$upd_res = Db::name('hd_news')->where('id', $param['id'])->update($arr);

		$param['status'] = $upd_res;

		return $param;
	}

	// 改变阅读量
	public function changeClicks()
	{
		$param = request()->param();

		$arr['read_num'] = $param['value'];

		$upd_res = Db::name('hd_news')->where('id', $param['id'])->update($arr);

		$param['status'] = $upd_res;

		return $param;
	}

	// 修改评论数量
	public function changePl()
	{
		$param = request()->param();

		$arr['pl_num'] = $param['value'];

		$upd_res = Db::name('hd_news')->where('id', $param['id'])->update($arr);

		$param['status'] = $upd_res;
		return $param;
	}

}
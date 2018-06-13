<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\model\GuModel;
use think\Db;

class Gu extends Controller
{
	//咕咕列表，默认以最新出现
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $btn = isset($param['btn']) ? $param['btn'] : 'new';

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['gugu_content'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $gu = new GuModel();
            $where['ban'] = 0;
            $selectResult = $gu->getGuByWhere($where, $offset, $limit, $btn);

            // $status = config('user_status');

            foreach($selectResult as $key=>$vo){

                // $selectResult[$key]['status'] = $status[$vo['status']];

                $operate = [
                    '删除' => "javascript:guDel('".$vo['id']."')",
                    '封禁' => "javascript:guBan('".$vo['id']."')",
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $gu->getAllGu($where, $btn);  //总数据

            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    // 修改咕咕的阅读量
    public function changeReadNum()
    {
    	$param = request()->param();

    	$arr['read_num'] = $param['value'];

    	$upd_res = Db::name('gugu_article')->where('id', $param['id'])->update($arr);

    	$param['status'] = $upd_res;

    	return $param;
    }

    // 修改咕咕的点赞量
    public function changeFavorNum()
    {
    	$param = request()->param();

    	$arr['like_num'] = $param['value'];
    	$upd_res = Db::name('gugu_article')->where('id', $param['id'])->update($arr);
    	$param['status'] = $upd_res;

    	return $param;
    }

    // 删除咕
    public function guDel()
    {
        $id = input('id');

        $del_res = Db::name('gugu_article')->where('id', $id)->delete();

        if ($del_res)
        {
            $arr['code'] = 1;
        }
        else
        {
            $arr['code'] = 0;
        }
        return $arr;    
    }

    // 封禁咕
    public function guBan()
    {
        $id = input('id');

        $param['ban'] = 1;
        $param['ban_time'] = date('Y-m-d h:i:s');
        $upd_res = Db::name('gugu_article')->where('id', $id)->update($param);

        if ($upd_res)
        {
            $arr['code'] = 1;
        }
        else
        {
            $arr['code'] = 0;
        }
        return $arr;
    }

    // 封禁咕咕列表
    public function banList()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['gugu_content'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $gu = new GuModel();
            $selectResult = $gu->getBanList($where, $offset, $limit);

            // $status = config('user_status');

            foreach($selectResult as $key=>$vo){

                // $selectResult[$key]['status'] = $status[$vo['status']];

                $operate = [
                    '删除' => "javascript:guDel('".$vo['id']."')",
                    '解封' => "javascript:cancelBanGu('".$vo['id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $gu->getBanNum($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    // 解封咕咕
    public function cancelBanGu()
    {
        $id = input('id');

        $param['ban'] = 0;
        $param['cancel_ban_time'] = date('Y-m-d h:i:s');

        $upd_res = Db::name('gugu_article')->where('id', $id)->update($param);

        if ($upd_res)
        {
            $arr['code'] = 1;
        }
        else
        {
            $arr['code'] = 0;
        }
        return $arr;
    }
}
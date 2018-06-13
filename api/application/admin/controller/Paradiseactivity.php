<?php

namespace app\admin\controller;

use think\Db;
use think\Controller;
use app\admin\model\ParadiseModel;

class Paradiseactivity extends Controller
{
	public function bannerList()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['username'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $banner = new ParadiseModel();
            $selectResult = $banner->getBannerByWhere($where, $offset, $limit);

            foreach($selectResult as $key=>$vo){

                $id = $vo['id'];
                
                $operate = [ 
                    '查看图片' => "./showImg/id/$id",
                    '取消轮播' => "javascript:rotaCancel('".$vo['id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $banner->getAllBanner($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    // 取消轮播
    public function rotaCancel()
    {
    	$id = input('id');

    	$upd_res = Db::name('paradise_activity')->where('id', $id)->update(['status' => 0]);

    	$arr = [];
    	$arr['code'] = $upd_res ? 1 : 0;
    	return $arr;
    }

    // 改变轮播顺序
    public function changeSort()
    {
        // 接收参数
        $param = request()->param();

        $arr = [];
        $arr['sort'] = $param['value'];
        $upd_res = Db::name('paradise_activity')
                   ->where('id', $param['id'])
                   ->update($arr);
        return $upd_res ? 1 : 0;
    }

    // 查看图片
    public function showImg($id)
    {
        $img = Db::name('paradise_activity')
               ->where('id', $id)
               ->value('cover_img');

        $this->assign('img', $img);
        return $this->fetch();
    }

    // 添加轮播图
    public function bannerAdd()
    {
        if (request()->isPost())
        {
            $param = request()->param();

            $destination = getDestination(1);

            move_uploaded_file($_FILES['img']['tmp_name'], $destination);

            $param['cover_img'] = 'http://zhlsfnoc.com/' . $destination;

            $ins_res = Db::name('paradise_activity')
                       ->insert($param);
            if ($ins_res)
            {
                $this->success('添加成功', 'bannerlist');
            }
            else
            {
                $this->error('添加失败', 'bannerlist');
            }
            die;
        }

        return $this->fetch();
    }
}
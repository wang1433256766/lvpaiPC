<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\model\ActivityModel;
use app\admin\model\UserType;
use think\Db;

class Activity extends Controller
{
    // 活动列表
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['activity_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $activity = new ActivityModel();
            $selectResult = $activity->getActivityList($where, $offset, $limit);

            $status = ['0' => '<span class="badge badge-info">隐藏</span>',
        '1' => '<span class="badge badge-danger">显示</span>'];

            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['status'] = $status[$vo['status']];


                $operate = [
                    '编辑' => url('activityEdit', ['id' => $vo['activity_id']]),
                    '删除' => "javascript:activityDel('".$vo['activity_id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
                
            }

            $return['total'] = $activity->getActivity($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    // 删除活动
    public function activityDel()
    {
        $id = input('param.id');

        // 获取当前用户id
        session('ding', '无用数据');
        session('ding', null);

        // 因为删除活动，所以-1
        Db::name('hd_writer')->where('admin_id', session('id'))->setDec('activity_num');

        $activity = new ActivityModel();
        $flag = $activity->delActivity($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    // 增加活动
    public function activityAdd()
    {
        session('ding', '无用数据');
        session('ding', null);

        if(request()->isPost()){

            $param = request()->param();

            $file = request()->file('image1');
            if($file)
            {
                $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'activity');
                if($info){
                    $filename = $info->getSaveName();
                    // 输出 42a79759f284b767dfcb2a0197904287.jpg
                    // $filename = $info->getFilename();
                    $filename = str_replace('\\', '/', $filename);
                    $arr['img_path'] = 'http://zhlsfnoc.com' . '/uploads' . '/activity/' . $filename;

                }else{
                    // 上传失败获取错误信息
                    $arr['img_path'] = 0;
                }
            }
            if (0 == $param['rota'])
            {
                $arr['rota'] = 1;
            }
            else
            {
                $arr['rota'] = 0;
            }
            $arr['writer_id'] = session('id');
            $arr['activity_name'] = $param['name'];
            $arr['activity_content'] = $param['content'];
            $arr['rota_sort'] = $param['rota_sort'];

            // 因为增加了一篇活动，所以该作者活动+1
            Db::name('hd_writer')->where('admin_id', session('id'))->setInc('activity_num');
            
            $res = Db::name('activity')->insert($arr);

            if ($res)
            {
                $this->success('添加成功','index');
            }
            else
            {
                $this->error('添加失败', 'index');
            }   
        }

        $role = new UserType();
        $this->assign([
            'role' => $role->getRole(),
            'status' => ['是', '否']
        ]);

        return $this->fetch();
    }

    // 编辑活动
    public function activityEdit()
    {
    	if (request()->isPost())
    	{
    		// 获取模版页面的值
    		$param = request()->param();

    		// 获取图片对象
            $file = request()->file('image1');
            
            // 如果没有重新选择图片，那么$file是null
            if($file)
            {
                $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'activity');
                if($info){
                    $filename = $info->getSaveName();
                    $filename = str_replace('\\', '/', $filename);
                    $img_path = 'http://zhlsfnoc.com' . '/uploads' . '/activity/' . $filename;
                }else{
                    // 上传失败获取错误信息
                    $img_path = 0;
                }
            }
            // 更新活动，判断是否修改图片
            if (isset($img_path))
            {
            	// 得到活动id
            	$activity_id = $param['id'];
	            unset($param['id']);

	            // 修改过的图片路径
	            $param['img_path'] = $img_path;
	       
	       		// 更新活动
	            $res = Db::name('activity')->where('activity_id', $activity_id)->update($param);

	            if ($res)
	            {
	            	$this->success('修改成功', 'index');
	            }
				else
				{
					$this->error('修改失败', 'index');
				}    	
            }
            else  // 没有修改图片
            {
            	$activity_id = $param['id'];
	            unset($param['id']);

	            $res = Db::name('activity')->where('activity_id', $activity_id)->update($param);

	            if ($res)		
	            {
	            	$this->success('修改成功', 'index');
	            }
				else
				{
					$this->error('修改失败', 'index');
				}  
            }
    	}

    	// 得到活动id
    	$activity_id = input('id');

    	// 得到活动的数据
    	$activity = Db::name('activity')->where('activity_id', $activity_id)->find();

    	$this->assign('activity', $activity);

    	return $this->fetch();
    } 

    // 改变点赞数量
    public function changeFavorNum()
    {
        $param = request()->param();

        $upd_res = Db::name('activity')->where('activity_id', $param['id'])->update(['like_num' => $param['value']]);
        $param['status'] = $upd_res;

        return $param;
    }

    // 改变阅读数量
    public function changeReadNum()
    {
        $param = request()->param();

        $upd_res = Db::name('activity')->where('activity_id', $param['id'])->update(['read_num' => $param['value']]);
        $param['status'] = $upd_res;

        return $param;
    }
}
<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\model\TravelModel;
use think\Db;
use think\Request;
use think\Config;

class Ticket extends Base
{
    public function index()
    {
        $where = '';
        $data = Db::name('ticket')->where($where)->paginate(10,false,['query'=>Request::instance()->param(),]);
        $page = $data->render();
        $this->assign('data',$data);
        $this->assign('page',$page);

        return $this->fetch();
    }

    public function add() {
        if (Request::instance()->isPost()) {
            $file = request()->file('thumb');
            $info = Request::instance()->param();
            if (!empty($file)) {
                $upload_path = Config::get('upload_path');
                $file_info = $file->move('.' . $upload_path['spot']);
                if ($file_info) {
                    $data['img'] = $upload_path['spot'] . $file_info->getSaveName();
                }
            }
            $info['img'] = isset($data['img'])?$data['img']:'';
            $info['add_time'] = time();
            $bool = Db::name('ticket')->insert($info);
            if ($bool) {
                return $this->success('添加成功', '/admin/ticket/index.html');
            }else {
                return $this->error('添加失败');
            }
        }
        return $this->fetch();
    }

    public function edit() {
        $id = Request::instance()->param('id');
        if ($id) {
            $data = Db::name('ticket')->where('id',$id)->find();
            $this->assign('data',$data);
            return $this->fetch();
        }
    }

    public function editAction() {
        if (Request::instance()->isPost()) {
            $file = request()->file('thumb');
            $info = Request::instance()->param();
            if (!empty($file)) {
                $upload_path = Config::get('upload_path');
                $file_info = $file->move( '.' . $upload_path['spot']);
                if ($file_info) {
                    $data['img'] = $upload_path['spot'] . $file_info->getSaveName();
                    $info['img'] = $data['img'];
                }
            }
            $info['up_time'] = time();
            $id = $info['id'];
            unset($info['id']);
            $bool = Db::name('ticket')->where('id',$id)->update($info);
            if ($bool) {
                return $this->success('修改成功', '/admin/ticket/index.html');
            }else {
                return $this->error('修改失败');
            }
        }
        return $this->fetch();
    }

    public function addimage() {
        $id = Request::instance()->param('id');
        $where['id'] = $id;
        $data = Db::name('ticket')->where($where)->find();
        $this->assign('data',$data);
        return  $this->fetch();
    }

    public function uploadimg() {
        $request = Request::instance();
        $info = $request->param();
        $id = $info['id'];
        $file = $request->file("picture");
        if (!empty($file)) {
            $upload_path = Config::get('upload_path');
            $file_info = $file->move('.'.$upload_path['poster']);
            if ($file_info) {
                $data['poster'] = $upload_path['poster'] . $file_info->getSaveName();
                $bool = Db::name('ticket')->where('id','eq',$id)->update($data);
                if ($bool) {
                    return $this->success('修改成功', '/admin/ticket/index.html');
                }else {
                    return $this->error('修改失败');
                }
            }
        }else {
            return $this->error('修改失败');
        }

    }


}
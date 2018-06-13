<?php
namespace app\admin\controller;
use think\Request;
use app\admin\model\NewsMenuModel;
use app\admin\model\ThemeModel;
use app\admin\model\NewsModel;
class Newclass extends Base
{
	public function index()
	{
		if(request()->isAjax())
		{
			$param = input('param.');
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            //判断视图传值
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $menu = new NewsMenuModel();
            $selectResult = $menu->getNewsMenuBy($where, $offset, $limit);
            $theme = new ThemeModel();
            $new = new NewsModel;
            //整合数据
            foreach($selectResult as $key=>$vo){
            	//查询当前id下的新闻菜单
            	$id = $vo['id'];
            	$themes = $theme->getOneTheme($vo['parent_id']);
            	$nid = $vo['id'];
            	$number = $new->MenuNews($nid);
            	$selectResult[$key]['theme_id'] = $themes['name'];
            	$selectResult[$key]['news'] = $number;
            	//操作整合
                $operate = [
                    '编辑' => url('newclass/newclassEdit', ['id' => $vo['id']]),
                    '删除' => "javascript:newsMenuDel('".$vo['id']."')"
                ];
               	$time = date('Y-m-d h:i:s', 1501035929);
                $selectResult[$key]['operate'] = showOperate($operate);
                $selectResult[$key]['addtime'] = $time;

            }

            $return['total'] = $menu->getAllMenu($where);  //总数据
            $return['rows'] = $selectResult;
            return json($return);
		}
		return $this->fetch();
	}
	public function newclassEdit()
	{
		$menu = new NewsMenuModel;
		$theme = new ThemeModel;
		if(request()->isPost())
		{
			$param = request()->param();

			$param['parent_id'] = $param['theme_id'];
			$param['id'] = $param['type_id'];

			unset($param['type_id']);
			unset($param['theme_id']);

			$result = $menu->editNewsMenu($param);
			
			if ($result)
			{
				$this->success('修改成功', 'index');
			}
			else
			{
				$this->error('修改失败', 'index');
			}
		}
		$id = input('id');
		$this->assign([
			'theme' => $theme->getTheme(),	
			'menu' => $menu->getOneNewsMenu($id)
			]);
		return $this->fetch();
	}
	public function newsMenuDel()
	{
		$id = input('id');
		$new = new NewsMenuModel;
		$res = $new->delNewsMenu($id);
		return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);
	}
	public function newsMenuAdd()
	{
		$theme = new ThemeModel;
		$menu = new NewsMenuModel;
		if(request()->isPost()){
			$data = $_POST;
			$data['parent_id'] = $data['theme_id'];
			unset($data['theme_id']);

			$res = $menu->insertNewsMenu($data);
			if ($res) {
                return $this->success('添加成功', 'index');
                die;
            }
            else{
                return $this->error('添加失败');
                die;
            }
		}
		$this->assign([
			'theme' => $theme->getTheme()
			]);
		return $this->fetch();
	}
}
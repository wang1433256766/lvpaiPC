<?php
namespace app\admin\controller;
use think\Db;
use think\Cache;
use think\Request;
use think\Config;
use think\Session;
use think\Image;
use app\admin\controller\Base;
/**
 * 系统设置
 */
class System extends Base
{
	/**
	 * 基本设置
	 * @AuthorHTL
	 * @DateTime  2016-05-31T11:39:40+0800
	 * @return    [type]                   [description]
	 */
	public function index()
	{

		$data = Db::name('config')->select();
		foreach ($data as $k => $v) {
			$info[$v['name']] = $v;
		}
		$this->assign('info',$info);
		return  $this->fetch();	
	}
	/**
	 * 更新站点配置
	 * @AuthorHTL naka1205
	 * @DateTime  2016-08-28T11:06:23+0800
	 * @return    [type]                   [description]
	 */
	public function update()
	{
		$a =  true;
		if (request()->isPost()) {
			$info = Request::instance()->param();
			//dump($info);
			
			$file = request()->file('thumb');

			if (!empty($file)) {
				$upload_path = Config::get('upload_path');
				//dump($upload_path);exit;
				$file_info = $file->move('.' . $upload_path['system']);
				if ($file_info) {
					$info['logo'] = $upload_path['system'] . $file_info->getSaveName();
				}
			}
			//dump($info);exit;
			foreach ($info as $k => $v) {
				$data['value'] = $v;
				//$data['up_time'] = time();
				$bool = Db::name('config')->where('name',$k)->update($data);
				 if (!$bool) {
				 	$a = false;
				 }
				
			}


		}
		if ($a) {

			return $this->success('更新成功', '/admin/system/index.html');
			die;
		}
		else{
			return $this->error('更新失败');
			die;
		}		
	}
	/**
	 * 广告
	 * @AuthorHTL naka1205
	 * @DateTime  2016-08-27T08:48:58+0800
	 * @return    [type]                   [description]
	 */
	public function ad()
	{
		$info = Request::instance()->param();
		$id = isset($info['id']) ? $info['id'] : 0;
		if ($id > 0) {
			$where['position_id'] = $id;
		}else{
			$where = '';
		}
		$data = Db::name('ad')->where($where)->paginate(10,false,['query'=>$info]);
		$page = $data->render();

		$position = Db::name('ad_position')->where('type',1)->select();
		$this->assign('position',$position);
		$info['id'] = $id; 
		$this->assign('data',$data);
		$this->assign('page',$page);
		$this->assign('info',$info);
		return  $this->fetch();	
	}
	public function position()
	{
	        $res = array(
	            'status' => false,
	            'info' => '操作失败',
	            );
		if (request()->isPost()) {
			$type = Config::get('position');
			$info = Request::instance()->param();
			$data['name'] = $info['pname'];
			$data['type'] = $info['type'];			

			if (isset($info['id'])) {
				$data['id'] = $info['id'];
				$data['up_time'] = time();
				$bool = Db::name('ad_position')->update($data);
			}else{
				$data['add_time'] = time();
				$bool = Db::name('ad_position')->insert($data);
			}
			
			if ($bool) {
				$res['status'] = true;  
				$res['info'] = $data;   
			}
		}
		echo json_encode($res);		
	}
	public function add()
	{
		$request = Request::instance();
		if ($request->isPost()) {

			$info = $request->param();
			$file = request()->file('thumb');			
			if (!empty($file)) {
				$upload_path = Config::get('upload_path');
				$file_info = $file->move( '.' . $upload_path['system']);
				if ($file_info) {
					$data['img'] = $upload_path['system'] . $file_info->getSaveName();
				}
			}

			$data['name'] = $info['name'];
			$data['position_id'] = $info['position_id'];
			$data['desc'] = $info['desc'];
			$data['url'] = $info['url'];
			$data['width'] = $info['width'];
			$data['height'] = $info['height'];
			$data['start_time'] = strtotime($info['start_time']);
			$data['end_time'] = strtotime($info['end_time']);
			$data['add_time'] = time();
			// $data['status'] = $info['status'];
			
			$bool = Db::name('ad')->insert($data);
			if ($bool) {

				return $this->success('添加成功', '/admin/system/ad.html');
				die;
			}
			else{
				return $this->error('添加失败');
				die;
			}
		}
		$position = Db::name('ad_position')->where('type',1)->select();
		$this->assign('position',$position);
		return  $this->fetch();
	}

	public function edit()
	{
		$request = Request::instance();
		$id = $request->param('id');
		if ($request->isPost()) {

			$info = $request->param();
			$file = request()->file('thumb');

			if (!empty($file)) {
				$upload_path = Config::get('upload_path');
				$file_info = $file->move( '.' . $upload_path['system']);
				if ($file_info) {
					$data['img'] = $upload_path['system'] . $file_info->getSaveName();
				}
			}
			$data['id'] = $id;
			$data['name'] = $info['name'];
			$data['position_id'] = $info['position_id'];
			$data['desc'] = $info['desc'];
			$data['url'] = $info['url'];
			$data['width'] = $info['width'];
			$data['height'] = $info['height'];
			$data['start_time'] = strtotime($info['start_time']);
			$data['end_time'] = strtotime($info['end_time']);
			//$data['new'] = $info['new'];
			//$data['status'] = $info['status'];
			
			$bool = Db::name('ad')->update($data);
			if ($bool) {

				return $this->success('修改成功', '/admin/system/ad.html');
				die;
			}
			else{
				return $this->error('修改失败');
				die;
			}
		}
		$info = Db::name('ad')->find($id);
		$position = Db::name('ad_position')->where('type',1)->select();
		$this->assign('info',$info);
		$this->assign('position',$position);
		return  $this->fetch();
	}

	
	

}
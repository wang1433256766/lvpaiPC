<?php
namespace app\admin\controller;
use think\Db;
use think\Cache;
use think\Request;
use think\Config;
use think\Session;
use think\Image;
/**
 * 酒店
 * @AuthorHTL naka1205
 * @DateTime  2016-05-09T22:04:12+0800
 */
class Hotel extends Base
{
	public function index()
	{

		$id = Request::instance()->param('id');
		if ($id) {
			$region = Db::name('mall_shop_region')->field('id')->where('parent_id',$id)->select();
			$id_arr = array($id);
			foreach ($region as $key => $value) {
				$id_arr[] = $value['id'];
			}
			$where['region_id'] = ['in',$id_arr];
		}else{
			$where = '';
		}

		$data = Db::name('mall_shop_hotel')->where($where)->paginate(5);
		$page = $data->render();

		$region = Db::name('mall_shop_region')->where('parent_id',0)->select();
		//dump($region);exit;
		$this->assign('region',$region);

		$this->assign('data',$data);
		$this->assign('page',$page);
		return  $this->fetch();
	}

	/**
	 * 添加酒店
	 * @AuthorHTL
	 * @DateTime  2016-05-31T11:39:40+0800
	 * @return    [type]                   [description]
	 */
	public function add()
	{
		$request = Request::instance();
		if ($request->isPost()) {

			$info = $request->param();
			$file = request()->file('thumb');		
			if (!empty($file)) {
				$upload_path = Config::get('upload_path');
				$file_info = $file->move('.'.$upload_path['hotel']);

				if ($file_info) {
					$data['thumb'] = $upload_path['hotel'] . $file_info->getSaveName();
				}
			}

			$data['title'] = $info['title'];
			// $data['tag'] = $info['tag'];
			$data['desc'] = $info['desc'];
			$data['address'] = $info['address'];
			$data['region_id'] = $info['region_id'];
			// $data['longitude'] = $info['longitude'];
			// $data['latitude'] = $info['latitude'];
			//$data['thumb'] = $info['thumb'];
			$data['sale_num'] = $info['sale_num'];
			$data['clicks_num'] = $info['clicks_num'];
			$data['share_num'] = $info['share_num'];
			$data['content'] = $info['content'];
			$data['notice'] = $info['notice'];
			$data['status'] = $info['status'];
			$data['seo_title'] = $info['seo_title'];
			$data['seo_desc'] = $info['seo_desc'];
			$data['seo_key'] = $info['seo_key'];
			$data['add_time'] = time();

			$bool = Db::name('mall_shop_hotel')->insert($data,false,true);
			if ($bool) {
				$where['admin_id'] = session('user.id');
				$where['hotel_id'] = 0;
				$where['up_time'] = 0;
				$spot_img = Db::name('mall_shop_hotel_img')->where($where)->select();
				if (!empty($spot_img)) {
					foreach ($spot_img as $key => $value) {
						$img_data['id'] = $value['id'];
						$img_data['hotel_id'] = $bool;
						$img_data['up_time'] = time();
						Db::name('mall_shop_hotel_img')->update($img_data);
					}
				}

				return $this->success('添加成功', '/admin/hotel/index.html');
				die;
			}
			else{
				return $this->error('添加失败');
				die;
			}
		}
		
		$region = Db::name('mall_shop_region')->where('parent_id',0)->select();
		$this->assign('region',$region);

		$attr_info = Db::name('mall_shop_hotel_attr')->select();
		$attr = child_merge($attr_info);
		$this->assign('attr',$attr);
		return  $this->fetch();
	}

	/**
	 * 编辑酒店
	 * @AuthorHTL
	 * @DateTime  2016-05-31T11:39:40+0800
	 * @return    [type]                   [description]
	 */
	public function edit()
	{
		$request = Request::instance();
		$id = $request->param('id');
		//dump($id);exit;
		
		//dump($hotel);exit;
		if ($request->isPost()) {

			$info = $request->param();
			$file = request()->file('thumb');

			if (!empty($file)) {
				$upload_path = Config::get('upload_path');
				$file_info = $file->move( '.' . $upload_path['hotel']);
				if ($file_info) {
					$data['thumb'] = $upload_path['hotel'] . $file_info->getSaveName();
				}
			}

			$data['id'] = $id;
			$data['title'] = $info['title'];
			// $data['tag'] = $info['tag'];
			$data['desc'] = $info['desc'];
			$data['address'] = $info['address'];
			$data['region_id'] = $info['region_id'];
			// $data['longitude'] = $info['longitude'];
			// $data['latitude'] = $info['latitude'];
			//$data['thumb'] = $info['thumb'];
			$data['sale_num'] = $info['sale_num'];
			$data['clicks_num'] = $info['clicks_num'];
			$data['share_num'] = $info['share_num'];
			$data['content'] = $info['content'];
			$data['notice'] = $info['notice'];
			$data['status'] = $info['status'];
			$data['seo_title'] = $info['seo_title'];
			$data['seo_desc'] = $info['seo_desc'];
			$data['seo_key'] = $info['seo_key'];
			$data['add_time'] = time();
			if (isset($info['attr'])) {
				$data['attr_id']  =  implode ( "," ,$info['attr']);
			}
			$bool = Db::name('mall_shop_hotel')->update($data);
			if ($bool) {
				$where['admin_id'] = session('user.id');
				$where['hotel_id'] = 0;
				$where['up_time'] = 0;
				$spot_img = Db::name('mall_shop_hotel_img')->where($where)->select();
				if (!empty($spot_img)) {
					foreach ($spot_img as $key => $value) {
						$img_data['id'] = $value['id'];
						$img_data['hotel_id'] = $id;
						$img_data['up_time'] = time();
						Db::name('mall_shop_hotel_img')->update($img_data);
					}
				}

				return $this->success('修改成功', '/admin/hotel/index.html');
				die;
			}
			else{
				return $this->error('修改失败');
				die;
			}
		}
		
		$info = Db::name('mall_shop_hotel')->find($id);

		$region = Db::name('mall_shop_region')->where('parent_id',0)->select();

		$attr_info = Db::name('mall_shop_hotel_attr')->select();
		$info['attr_id'] = json_encode(explode  (",",$info['attr_id']));
		$attr = child_merge($attr_info);
		$this->assign('info',$info);
		$this->assign('region',$region);
		$this->assign('attr',$attr);
		return  $this->fetch();
	}




//属性列表
	public function attr()
	{
		$id = Request::instance()->param('id');
		if ($id) {
			$where['parent_id'] = $id;
		}else{
			$where = '';
		}

		$data = Db::name("mall_shop_hotel_attr")->where($where)->paginate(10);		
		$page = $data->render();
		$attr = Db::name("mall_shop_hotel_attr")->where('parent_id',0)->select();
		$this->assign('attr',$attr);
		$this->assign('data',$data);
		$this->assign('page',$page);
		return  $this->fetch();	
	}
//编辑酒店属性
	public function ajaxAttr()
	{

		$info = Request::instance()->param();
		$data['name'] = $info['name'];
		$data['type'] = $info['type'];
		$data['parent_id'] = $info['parent_id'];
		$id = isset($info['id']) ? $info['id'] : 0;
		if($id > 0){
			$data['id'] = $id;
			$attr = Db::name('mall_shop_hotel_attr')->update($data);
		}else{
			$data['add_time'] = time();
			$attr = Db::name('mall_shop_hotel_attr')->insert($data);
		}
		
		if($attr){
			echo 'y';
		}else{
			echo 'n';
		}	

	}

	/**
	 * AJAX 上传文件
	 * @AuthorHTL
	 * @DateTime  2016-05-31T10:51:45+0800
	 * @return    [type]                   [description]
	 */
	public function ajax()
	{

		$request = Request::instance();

		$res = array(
			'status' => false,
			'info' => '操作失败',
			);

		$info = $request->param();
		$file = $request->file('file');

		if (!empty($file)) {
			$upload_path = Config::get('upload_path');
			$file_info = $file->move( '.' . $upload_path['hotel']);
			
			if ($file_info) {
				$save_name = $file_info->getSaveName();
				$img = $upload_path['hotel'] . $save_name;

				$image_name = $file->getInfo('name');

				/*$image = Image::open($file);

				$width = $image->width();
				$height = $image->height(); 

				$big_path = $upload_path['hotel'] . 'big_'  . $image_name;
				$small_path = $upload_path['hotel'] . 'small_'  . $image_name;

				$image->thumb($width / 2 , $height / 2)->save('.' . $big_path);
				$image->thumb($width / 4 , $height / 4)->save('.' . $small_path);*/

				$data = array(
					'hotel_id' => 0,
					'admin_id' => session('id'),
					'name' => $image_name,
					'img' => $img,	
					
					'add_time' => time()
					);
				$bool = Db::name('mall_shop_hotel_img')->insert($data);
				if ($bool) {
					$res['status'] = true;
					$res['info'] = $data;
				}
			}
		}

		return json_encode($res);

	}


	/**
	 * 获取相册图片
	 * @AuthorHTL
	 * @DateTime  2016-05-31T10:52:03+0800
	 * @return    [type]                   [description]
	 */
     public function getImg()
    {
			$id = Request::instance()->param('id');
			$act = Request::instance()->param('act');
			$name = Request::instance()->param('name');
            $res = array(
                    'status' => false,
                    'info' => '操作失败',
                    );
            if ($id > 0 || !empty($name)) {
                    switch ($act) {
                            case 'all':
                            $data = Db::name('mall_shop_hotel_img')->where("hotel_id",$id)->select();
                            break;
                            case 'name':
							$where['name'] = $name;
							$where['admin_id'] = session('user.id');
							$where['hotel_id'] = 0;
							$where['up_time'] = 0;
                            $data = Db::name('mall_shop_hotel_img')->where($where)->find();
                            break;    
                            default:
                            $data = Db::name('mall_shop_hotel_img')->find($id);
                            break;
                    }  
                    if ($data) {
                            $res['status'] = true;  
                            $res['info'] = $data;   
                    }
            }
            echo json_encode($res);
    } 
    /**
     * 更新图片信息
     * @AuthorHTL
     * @DateTime  2016-08-30T10:10:17+0800
     * @return    [type]                   [description]
     */
	public function updateImg()
	{
		$id = Request::instance()->param('id');
		$name = Request::instance()->param('name');
		$value = Request::instance()->param('value');
		$res = array(
			'status'=>false,
			'info'=>''
			);
		if (isset($id) && $id > 0) {
			$res['status'] = true;
			$where['id'] = $id ;
			//$where['admin_id'] = session('user.id');
			$res['info'] = Db::name('mall_shop_hotel_img')->where($where)->setField('name',$value);			
		}else if (!empty($value)) {
			$res['status'] = true;
			$where['name'] = $name ;
			$where['admin_id'] = session('user.id');
			$where['hotel_id'] = 0;
			$where['up_time'] = 0;
			$res['info'] = Db::name('mall_shop_hotel_img')->where($where)->setField('name',$value);			
		}

		echo json_encode($res);		
	}    
//酒店房间
	public function room(){
		// $data = Db::name('mall_shop_hotel')->paginate(5);
		// $page = $data->render();
		// $this->assign('data',$data);
		// $this->assign('page',$page);
		$id = Request::instance()->param('id');
		//dump($id);exit;
		if(empty($id)){
			$data = Db::name('mall_shop_hotel_room')->paginate(10);
			$page = $data->render();
		}else{

		$data = Db::name('mall_shop_hotel_room')->where('hotel_id',$id)->paginate(10);
		//dump($data);exit;
		$page = $data->render();
	    }
		$this->assign('data',$data);
		$this->assign('page',$page);
		return  $this->fetch();
	}
//添加房间信息
	public function room_add(){
		$hotel_id = input('get.id');
		if (request()->isPost()) {

			$info = Request::instance()->param();
			$data['hotel_id'] = $info['hotel_id'];
			$data['title'] = $info['title'];
			// $data['tag'] = $info['tag'];
			$data['desc'] = $info['desc'];
			$data['room_num'] = $info['room_num'];
			$data['market_price'] = $info['market_price'];
			$data['shop_price'] = $info['shop_price'];
			$data['distribution'] = $info['distribution'];
			$data['sale_start'] = isset($info['sale_start'])?strtotime($info['sale_start']):'';
			$data['sale_end'] = isset($info['sale_end'])?strtotime($info['sale_end']):'';
			$data['score'] = $info['score'];
			$data['status'] = $info['status'];
			$data['sale_num'] = $info['sale_num'];
			// $data['sort'] = $info['sort'];
			$data['add_time'] = time();
			$bool = Db::name('mall_shop_hotel_room')->insert($data,false,true);
			if ($bool) {
				$room_sn = get_num($bool,14);
				Db::name('mall_shop_hotel_room')->where('id',$bool)->setField('room_sn',$room_sn);
				//更新价格
				$max_price = Db::name('mall_shop_hotel_room')->where('hotel_id',$info['hotel_id'])->max('market_price');
				$min_price = Db::name('mall_shop_hotel_room')->where('hotel_id',$info['hotel_id'])->min('shop_price');
				$hotel['shop_price'] = $min_price;
				$hotel['market_price'] = $max_price;
				Db::name('mall_shop_hotel')->where('id',$info['hotel_id'])->update($hotel);
				return $this->success('添加成功', '/admin/hotel/room.html');
				die;
			}
			else{
				return $this->error('添加失败','/admin/hotel/room.html');
				die;
			}
		}
		
		$hotel = Db::name('mall_shop_hotel')->select();
		$this->assign('hotel',$hotel);
		return  $this->fetch();
	}
//修改房间信息
	public function room_edit(){
		$room_id = Request::instance()->param('room_id');
		if (request()->isPost()) {

			$info = Request::instance()->param();
			$data['hotel_id'] = $info['hotel_id'];
			$data['title'] = $info['title'];
			// $data['tag'] = $info['tag'];
			$data['desc'] = $info['desc'];
			$data['room_num'] = $info['room_num'];
			$data['market_price'] = $info['market_price'];
			$data['shop_price'] = $info['shop_price'];
			$data['distribution'] = $info['distribution'];
			$data['sale_start'] = isset($info['sale_start'])?strtotime($info['sale_start']):'';
			$data['sale_end'] = isset($info['sale_end']) ? strtotime($info['sale_end']):'';
			$data['score'] = $info['score'];
			$data['status'] = $info['status'];
			$data['sale_num'] = $info['sale_num'];
			// $data['sort'] = $info['sort'];
			$data['add_time'] = time();
			$bool = Db::name('mall_shop_hotel_room')->where('id',$room_id)->update($data,false,true);
			if ($bool) {
				//更新价格
				$room = Db::name('mall_shop_hotel_room')->where('id',$room_id)->find();
				$max_price = Db::name('mall_shop_hotel_room')->where('hotel_id',$room['hotel_id'])->max('market_price');
				$min_price = Db::name('mall_shop_hotel_room')->where('hotel_id',$room['hotel_id'])->min('shop_price');
				$hotel['shop_price'] = $min_price;
				$hotel['market_price'] = $max_price;
				Db::name('mall_shop_hotel')->where('id',$room['hotel_id'])->update($hotel);
				
				return $this->success('修改成功', "/admin/hotel/index.html");
				die;
			}
			else{
				return $this->error('修改失败','/admin/hotel/index.html');
				die;
			}
		}
		
		$room = Db::name('mall_shop_hotel_room')->where('id',$room_id)->find();
		$hotel = Db::name('mall_shop_hotel')->select();
		$this->assign('hotel',$hotel);
		$this->assign('room',$room);
		return  $this->fetch();
	}

	public function status()
	{
		$id = Request::instance()->param('id');
		$act = Request::instance()->param('act');
		$value = Request::instance()->param('value');
		$tab = 'mall_shop_hotel_room';
		$res = array(
			'status' => false,
			'info' => '操作失败',
			);
		$change = false;
		if ($id > 0) {
			switch ($act) {
				case 'edit':
					$data = Db::name($tab)->find($id);
					break;
				case 'del':
					$data = Db::name($tab)->delete($id);
					break;  
				case 'sort':
					$data = Db::name($tab)->where('id',$id)->setField('sort',$value);
					break;
				case 'new':
					$new = $value ? 0 :1;
					$data = Db::name($tab)->where('id',$id)->setField('new',$new);
					break;                                
				case 'base':
					$base = $value ? 0 :1;
					$data = Db::name($tab)->where('id',$id)->setField('base',$base);
					break;
				case 'hot':
					$hot = $value ? 0 :1;
					$data = Db::name($tab)->where('id',$id)->setField('hot',$hot);
					break;    
				case 'cheap':
					$cheap = $value ? 0 :1;
					$data = Db::name($tab)->where('id',$id)->setField('cheap',$cheap);
					break; 
				case 'sale':
					$sale = $value ? 0 :1;
					$data = Db::name($tab)->where('id',$id)->setField('sale',$sale);   
					$change = true;                       
					break;   
				case 'self':
					$self = $value ? 0 :1;
					$data = Db::name($tab)->where('id',$id)->setField('self',$self);
					$change = true;   
					break;  
				case 'today':
					$today = $value ? 0 :1;
					$data = Db::name($tab)->where('id',$id)->setField('today',$today);
					break; 
				case 'status':
					$status = $value ? 0 :1;
					$data = Db::name($tab)->where('id',$id)->setField('status',$status);
					$change = true;   
					break;   
				default:
					$data = Db::name($tab)->find($id);
					break;
			}	
		}  
		if ($data) {
			//更新价格
			if ($change) {
				$room = Db::name('mall_shop_hotel_room')->find($id);
				$where_room['hotel_id'] = $room['hotel_id'];
				$where_room['room_num'] = ['>',0];
				$where_room['status'] = 1;
				$where_room['sale'] = 1;
				$where_room['self'] = 0;
				$max_price = Db::name('mall_shop_hotel_room')->where($where_room)->max('market_price');
				$min_price = Db::name('mall_shop_hotel_room')->where($where_room)->min('shop_price');
				$hotel['shop_price'] = $min_price;
				$hotel['market_price'] = $max_price;
				Db::name('mall_shop_hotel')->where('id',$room['hotel_id'])->update($hotel);  
			}
			$res['status'] = true;  
			$res['info'] = $data;   

		}
		echo json_encode($res);
	}  
	
}

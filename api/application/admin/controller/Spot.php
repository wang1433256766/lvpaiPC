<?php
namespace app\admin\controller;
use think\Request;
use think\Db;
use think\Config;

/**
 * 景区管理
 */
class Spot extends Base{
	/**
	 * 景区列表
	 * @AuthorHTL
	 * @DateTime  2016-05-31T11:39:40+0800
	 * @return    [type]                   [description]
	 */
	public function index()
	{  
	   $info = request()->param();

		if (isset($info['r_id']) &&  $info['r_id'] > 0) {
			$where['province_id'] = $info['r_id'];
			$info['key'] = '';
		}else if (isset($info['key']) && !empty($info['key'])) {
			$where['title'] = ['like',"%{$info['key']}%"];
		}else{
			$info['key'] = '';
			$where = '';
		}
		//dump($where);	
		//$where['add_time'] = ['>',0];
		//$where['add_time'] = ['>',0];
		$data = db('mall_spot')->where($where)->paginate(10,false,['query'=>request()->param(),]);
		//dump($data);

		$page = $data->render();

		
		
		$region = db('mall_shop_region')->where('parent_id',0)->select();
		// if(Request::instance()->param('id')){
		//     $where['province_id'] = Request::instance()->param('id');
		// }
		//dump($region);
		$this->assign('region',$region);
		$this->assign('data',$data);
		$this->assign('page',$page);
		$this->assign('info',$info);
		return  $this->fetch();	
	}
	/**
	 * 编辑景区
	 * @AuthorHTL
	 * @DateTime  2016-05-31T11:39:40+0800
	 * @return    [type]                   [description]
	 */
	public function edit()
	{
		$request = Request::instance();
		$id = $request->param('id');
		if ($request->isPost()) {

			$info = $request->param();
			$file = request()->file('thumb');

			if (!empty($file)) {
				$upload_path = config('upload_path');
				//dump($upload_path);
				$file_info = $file->move( '.' . $upload_path['spot']);
				if ($file_info) {
					$data['thumb'] = $upload_path['spot'] . $file_info->getSaveName();
				}
			}

			//dump($info);exit;	
			$data['title'] = $info['title'];
			
			$data['desc'] = $info['desc'];
			$data['address'] = $info['address'];
			$data['province_id'] = $info['province_id'];
			$data['province'] = Db::name('mall_shop_region')->where('id',$info['province_id'])->value('name');
			$data['city_id'] = $info['city_id'];
			$data['city'] = Db::name('mall_shop_region')->where('id',$info['city_id'])->value('name');
			$data['theme_id'] = $info['theme_id'];
			$data['theme'] = Db::name('mall_spot_attr')->where('id',$info['theme_id'])->value('name');
			$data['longitude'] = isset($info['longitude']) ? $info['longitude'] : '';
			$data['latitude'] = isset($info['latitude']) ? $info['latitude'] : '';
			$data['show_num'] = isset($info['show_num']) ? $info['show_num'] : 0;
			$data['clicks_num'] = isset($info['clicks_num']) ? $info['clicks_num'] : 0;
			$data['share_num'] = isset($info['share_num']) ? $info['share_num'] : 0;
			$data['content'] = isset($info['content']) ? $info['content'] : '';
			$data['status'] = isset($info['status']) ? $info['status'] : 0;
			$data['seo_title'] = isset($info['seo_title']) ? $info['seo_title'] : '';
			$data['seo_desc'] = isset($info['seo_desc']) ? $info['seo_desc'] : '';
			$data['seo_key'] = isset($info['seo_key']) ? $info['seo_key'] : '';
			$data['opening'] = isset($info['opening']) ? $info['opening'] : '';
			$data['take'] = isset($info['take']) ? $info['take'] : '';
			$data['certificate'] = isset($info['certificate']) ? $info['certificate'] : '';
			$data['crowd'] = isset($info['crowd']) ? $info['crowd'] : '';
			$data['reminder'] = isset($info['reminder']) ? $info['reminder'] : '';
			$data['add_time'] = time();
			if (isset($info['attr'])) {
				$data['attr_id']  =  implode( "," ,$info['attr']);
			}

			$bool = Db::name('mall_spot')->where('id',$info['id'])->update($data);
			//dump($data);exit;
			if ($bool) {
				//景点图片
				$where['admin_id'] = session('user.id');
				$where['spot_id'] = 0;
				$where['up_time'] = 0;
				$spot_img = Db::name('mall_spot_img')->where($where)->select();
				if (!empty($spot_img)) {
					foreach ($spot_img as $key => $value) {
						// $img_data['id'] = $value['id'];
						$img_data['spot_id'] = $id;
						//$img_data['status'] = 1;
						$img_data['up_time'] = time();
						Db::name('mall_spot_img')->where('id',$value['id'])->update($img_data);
					}
				}

				return $this->success('修改成功', '/admin/spot/index.html');
				die;
			}
			else{
				return $this->error('修改失败');
				die;
			}
		}
		$info = Db::name('mall_spot')->find($id);
		//$info['content'] = str_replace('/upload', 'http://www.zhonghuilv.net/upload', $info['content']);
		//dump($info);

		$province = Db::name('mall_shop_region')->where('parent_id',0)->select();
		$city = Db::name('mall_shop_region')->where('parent_id',$info['province_id'])->select();


		$where['field'] = 'theme';
		$theme = Db::name('mall_spot_attr')->where($where)->select();
		
		$this->assign('info',$info);
		$this->assign('province',$province);
		$this->assign('city',$city);
		$this->assign('theme',$theme);
		return  $this->fetch();
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
						$data = Db::name('mall_spot_img')->where("spot_id",$id)->select();
					break;
					case 'name':
						$where['name'] = $name;
						$where['admin_id'] = session('user.id');
						$where['spot_id'] = 0;
						$where['up_time'] = 0;
						$data = Db::name('mall_spot_img')->where($where)->find();
					break;    
					default:
						$data = Db::name('mall_spot_img')->find($id);
					break;
                }  
                if ($data) {
                    	$res['status'] = true;  
                    	$res['info'] = $data;   
                }
        }
        echo json_encode($res);
    }  

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
			$res['info'] = Db::name('mall_spot_img')->where($where)->setField('name',$value);			
		}else if (!empty($value)) {
			$res['status'] = true;
			$where['name'] = $name ;
			$where['admin_id'] = session('user.id');
			$where['spot_id'] = 0;
			$where['up_time'] = 0;
			$res['info'] = Db::name('mall_spot_img')->where($where)->setField('name',$value);			
		}

		echo json_encode($res);		
	}
	/**
	 * 添加景区
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
				$file_info = $file->move('.' . $upload_path['spot']);
				if ($file_info) {
					$data['thumb'] = $upload_path['spot'] . $file_info->getSaveName();
				}
			}

			$data['title'] = $info['title'];
			$data['desc'] = $info['desc'];
			$data['address'] = $info['address'];
			$data['province_id'] = $info['province_id'];
			$data['province'] = Db::name('mall_shop_region')->where('id',$info['province_id'])->value('name');
			$data['city_id'] = $info['city_id'];
			$data['city'] = Db::name('mall_shop_region')->where('id',$info['city_id'])->value('name');
			$data['theme_id'] = $info['theme_id'];
			$data['theme'] = Db::name('mall_spot_attr')->where('id',$info['theme_id'])->value('name');			
			$data['longitude'] = isset($info['longitude']) ? $info['longitude'] : '';
			$data['latitude'] = isset($info['latitude']) ? $info['latitude'] : '';
			$data['show_num'] = isset($info['show_num']) ? $info['show_num'] : 0;
			$data['clicks_num'] = isset($info['clicks_num']) ? $info['clicks_num'] : 0;
			$data['share_num'] = isset($info['share_num']) ? $info['share_num'] : 0;
			$data['content'] = isset($info['content']) ? $info['content'] : '';
			$data['status'] = isset($info['status']) ? $info['status'] : 0;
			$data['seo_title'] = isset($info['seo_title']) ? $info['seo_title'] : '';
			$data['seo_desc'] = isset($info['seo_desc']) ? $info['seo_desc'] : '';
			$data['opening'] = isset($info['opening']) ? $info['opening'] : '';
			$data['take'] = isset($info['take']) ? $info['take'] : '';
			$data['certificate'] = isset($info['certificate']) ? $info['certificate'] : '';
			$data['crowd'] = isset($info['crowd']) ? $info['crowd'] : '';
			$data['reminder'] = isset($info['reminder']) ? $info['reminder'] : '';
			$data['seo_key'] = isset($info['seo_key']) ? $info['seo_key'] : '';
			$data['add_time'] = time();
			$data['type'] = $info['type'];

			$bool = Db::name('mall_spot')->insert($data,false,true);
			if ($bool) {

				//更新图片
				$where['admin_id'] = session('user.id');
				$where['spot_id'] = 0;
				$where['up_time'] = 0;
				$spot_img = Db::name('mall_spot_img')->where($where)->select();
				if (!empty($spot_img)) {
					foreach ($spot_img as $key => $value) {
						// $img_data['id'] = $value['id'];
						$img_data['spot_id'] = $bool;
						$img_data['status'] = 1;
						$img_data['up_time'] = time();
						Db::name('mall_spot_img')->where('id',$value['id'])->update($img_data);
					}
				}

				return $this->success('添加成功', '/admin/spot/index.html',array('user'=>'我曹'));
				die;
			}
			else{
				return $this->error('添加失败');
				die;
			}
		}

		$region = Db::name('mall_shop_region')->where('parent_id',0)->select();
		$this->assign('region',$region);

		$where['field'] = 'theme';
		$theme = Db::name('mall_spot_attr')->where($where)->select();
		
		$this->assign('theme',$theme);
		return  $this->fetch();
	}
	/*
	查看景区
	 *@AuthorHTL
	 * @DateTime  2017-07-22T09:36:31+0800
	 	@return    [type]                   [description]
	 */
	public function spotCheck(){
		$id=request()->param('spot_id');
		$info = Db::name('mall_spot')->field('content')->find($id);
		$info = str_replace('/upload', 'http://www.zhonghuilv.net/upload', $info);
		$this->assign('info',$info);
		//dump($info);
		return $this->fetch();
	}
	/**
	 * 数据导出
	 */
	public function exportExcel()
	{
		$time = date("Y-m-d",time());
		$xlsName  = "商城景点".$time;	 
        $xlsCell  = array(
        	'景区ID',
        	'类型',
            '省份',
            '城市',
            '景区等级',
            '主题',
            '标题',
            '简介',
            '地址',
            '价格',
            '原价',
            '显示状态',
            '推荐',
            '热门',
            '特惠',
            '排序',
        );
        $xlsData  = Db::name('mall_spot')->Field('id,type,province,city,grade,theme,title,desc,address,shop_price,market_price,status,base,hot,cheap,sort')->order('id asc')->select();
        if($xlsData){
        	$info = phpexcel($xlsData,$xlsCell,$xlsName);
        	//dump($info);        	 
        	if($info){
        		return $this->success('导出成功','/admin/spot/index.html');
        	}else{
        		return $this->error('导出失败','/admin/spot/index.html');
        	}
        }else{
    		return $this->error('导出失败','/admin/spot/index.html');
    	}
        
	}
	public function ticket()
	{
		$info = Request::instance()->param();
		//dump($info);
		if (isset($info['spot_id']) &&  $info['spot_id'] > 0) {
			$where['spot_id'] = $info['spot_id'];
		}else if (isset($info['key']) && !empty($info['key'])) {
			$where['title'] = ['like',"%{$info['key']}%"];
		}else{
			$info['key'] = '';
			$where = '';
		}
		$data = Db::name('mall_spot_ticket')->where($where)->paginate(10,false,['query'=>Request::instance()->param(),]);
		
		$page = $data->render();
		//dump($page);
		$spot = Db::name('mall_spot')->field('id,title')->select();
		//dump($spot);
		$this->assign('spot',$spot);			
		$this->assign('data',$data);
		$this->assign('page',$page);
		$this->assign('info',$info);
		return  $this->fetch();			
	}
	/**
	 * 编辑门票
	 * @AuthorHTL
	 * @DateTime  2016-05-31T11:39:40+0800
	 * @return    [type]                   [description]
	 */
	public function editticket()
	{
		$id = Request::instance()->param('id');
		if (request()->isPost()) {

			$info = Request::instance()->param();
			//var_dump($info);

			$sale_start = isset($info['sale_start']) ? strtotime($info['sale_start']) : '';
			$sale_end = isset($info['sale_end']) ? strtotime($info['sale_end']) : '';

			$begin_date = isset($info['begin_date']) ? strtotime($info['begin_date']) : '';
			$end_date = isset($info['end_date']) ? strtotime($info['end_date']) : '';


			$data['id'] = $id;
			$data['spot_id'] = $info['spot_id'];
			$data['title'] = $info['title'];
			$data['desc'] = $info['desc'];
			$data['remark'] = $info['remark'];
			$data ['refund_info'] = $info['refund_info'];
			$data['take'] = $info['take'];
			$data['goods_code'] = isset($info['goods_code']) ? $info['goods_code'] :'';
			
			$data['market_price'] = $info['market_price'];
			$data['shop_price'] = $info['shop_price'];
			
			$data['distribution'] = $info['distribution'];
			$data['score'] = $info['score'];

			$data['goods_num'] = $info['goods_num'];
			$data['show_num'] = $info['show_num'];
			
			//$data['sale_num'] = $info['sale_num'];
			$data['sale_start'] = $sale_start;
			$data['sale_end'] = $sale_end;
			$data['begin_date'] = $begin_date;
			$data['end_date'] = $end_date;

			$data['real_name'] = $info['real_name'];
			$data['need_card'] = $info['need_card'];

			$data['sale_max'] = $info['sale_max'];
			$data['sale_min'] = $info['sale_min'];

			$data['before_days'] = $info['before_days'];
			$data['before_time'] = $info['before_time'];
			$data['reserve_type'] = $info['reserve_type'];
			$data['reserve_days_limit'] = $info['reserve_days_limit'];

			$data['reserve_times'] = $info['reserve_times'];
			$data['reserve_total_tickets'] = $info['reserve_total_tickets'];


			$data['is_refund'] = $info['is_refund'];
			$data['is_partial_refund'] = $info['is_partial_refund'];


			$data['check_way'] = $info['check_way'];
			$data['charge_type'] = $info['charge_type'];
			$data['charge'] = $info['charge'];
			$data['day'] = $info['day'];
			$data['hour'] = $info['hour'];
			

			$data['status'] = $info['status'];
			$data['up_time'] = time();

			$bool = Db::name('mall_spot_ticket')->update($data);
			if ($bool) {
				//更新价格
				$ticket = Db::name('mall_spot_ticket')->value('spot_id');
				$where_spot['spot_id'] = $ticket['spot_id'];
				$where_spot['goods_num'] = ['>',0];
				$where_spot['status'] = 1;
				$max_price = Db::name('mall_spot_ticket')->where($where_spot)->max('market_price');
				$min_price = Db::name('mall_spot_ticket')->where($where_spot)->min('shop_price');
				$spot['shop_price'] = $min_price;
				$spot['market_price'] = $max_price;
				Db::name('mall_spot')->where('id',$ticket['spot_id'])->update($spot);
				return $this->success('修改成功', '/admin/spot/ticket.html');
				die;
			}
			else{
				return $this->error('修改失败');
				die;
			}
		}

		$ly_config = Config::get('ly');

		$info = Db::name('mall_spot_ticket')->find($id);
		$spot = Db::name('mall_spot')->field('id,title')->select();
		$this->assign('spot',$spot);
		$this->assign('check_way',$ly_config['reservecheckWay']);
		$this->assign('reserve_type',$ly_config['reserve_type']);
		$this->assign('charge_type',$ly_config['charge_type']);
		$this->assign('info',$info);
		return  $this->fetch();
	}
	/**
	 * 添加门票
	 * @AuthorHTL
	 * @DateTime  2016-05-31T11:39:40+0800
	 * @return    [type]                   [description]
	 */
	public function addticket()
	{
		if (request()->isPost()) {

			$info = Request::instance()->param();
			//销售日期
			$sale_start = isset($info['sale_start']) ? strtotime($info['sale_start']) : time();
			$sale_end = isset($info['sale_end']) ? strtotime($info['sale_end']) : 0;
			//使用日期
			$begin_date = isset($info['begin_date']) ? strtotime($info['begin_date']) : time();
			$end_date = isset($info['end_date']) ? strtotime($info['end_date']) : 0;

			$data['spot_id'] = $info['spot_id'];
			$data['title'] = $info['title'];
			$data['desc'] = $info['desc'];
			$data['remark'] = $info['remark'];
			$data['refund_info'] = $info['refund_info'];
			$data['goods_code'] = isset($info['goods_code']) ? $info['goods_code'] :'';
			$data['goods_num'] = isset($info['goods_num']) ? $info['goods_num'] : 65535;
			$data['market_price'] = $info['market_price'];
			$data['shop_price'] = $info['shop_price'];
			$data['distribution'] = $info['distribution'];
			//$data['sale_num'] = $info['sale_num'];
			$data['sale_start'] = $sale_start;
			$data['sale_end'] = $sale_end;
			$data['status'] = isset($info['status']) ? $info['status'] : 1 ;
			$data['score'] = $info['score'];
			$data['add_time'] = time();
			$data['type'] = $info['type'];

			$bool = Db::name('mall_spot_ticket')->insert($data,false,true);
			if ($bool) {
				$goods_sn = get_num($bool,25);
				Db::name('mall_spot_ticket')->where('id',$bool)->setField('goods_sn',$goods_sn);
				//更新价格
				$where_spot['spot_id'] = $info['spot_id'];
				$where_spot['goods_num'] = ['>',0];
				$where_spot['status'] = 1;
				$max_price = Db::name('mall_spot_ticket')->where($where_spot)->max('market_price');
				$min_price = Db::name('mall_spot_ticket')->where($where_spot)->min('shop_price');
				$spot['shop_price'] = $min_price;
				$spot['market_price'] = $max_price;
				Db::name('mall_spot')->where('id',$info['spot_id'])->update($spot);
				return $this->success('添加成功', '/admin/spot/ticket.html');
				die;
			}
			else{
				return $this->error('添加失败');
				die;
			}
		}
		$ly_config = Config::get('ly');

		$spot = Db::name('mall_spot')->field('id,title')->select();
		$this->assign('spot',$spot);
		$this->assign('check_way',$ly_config['reservecheckWay']);
		$this->assign('reserve_type',$ly_config['reserve_type']);
		$this->assign('charge_type',$ly_config['charge_type']);
		return  $this->fetch();
	}
	public function attr()
	{
		$info = Request::instance()->param();
		//dump($info);
		if (isset($info['id']) && $info['id'] > 0) {
			$where['parent_id'] = $info['id'];
		}else{
			$where = '';
		}

		$data = db('mall_spot_attr')->where($where)->order('sort desc')->paginate(10,false,['query'=>$info]);
		//dump($data);		
		 $page = $data->render();
		 //dump($page);
		 $attr = db('mall_spot_attr')->where('parent_id',0)->select();
		 $this->assign('attr',$attr);
		 $this->assign('data',$data);
		 $this->assign('page',$page);
		 return  $this->fetch();	
	}
	public function ajaxAttr()
	{
		$info = Request::instance()->param();
		$data['name'] = $info['name'];
		$data['type'] = $info['type'];
		$data['parent_id'] = $info['parent_id'];
		$id = isset($info['id']) ? $info['id'] : 0;
		if($id > 0){
			$data['id'] = $id;
			$attr = Db::name('mall_spot_attr')->update($data);
		}else{
			$data['add_time'] = time();
			$attr = Db::name('mall_spot_attr')->insert($data);
		}
		
		if($attr){
			echo 'y';
		}else{
			echo 'n';
		}		
	}
	public function getCity()
	{
		$request = Request::instance();
		$id = $request->param('id');
		$res = array(
			'status' => false,
			'info' => '操作失败',
			);
		if ($id) {
			$where['parent_id'] = $id;
			$region = Db::name('mall_spot_region')->where($where)->select();
			$res['status'] = true;		
			$res['info'] = $region;		
		}	
		echo json_encode($res);	
	}

}
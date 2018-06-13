<?php
namespace app\mobile\controller;

use think\Db;
use think\Controller;
use think\Request;
use think\Session;
use app\mobile\controller\Base;
use think\log;
use think\Config;

class Memberwch extends Base{
	/*public function _initialize(){
		$member_id = session::get('member_id');
		$member = Db::name('mall_member')->where('id',$member_id)->find();
		if($member){
			if (!$member['mobile']) {
	        			$this->redirect('/mobile/bind/bind.html');
	        		}
		}
		
	}*/

	public function index(){

		$where['status'] = 1;

		$product = Db::name('score_goods')
				   ->where($where)
				   ->order('sort')
				   ->select();
		$this->assign("product",$product);
		return $this->fetch();	
	}

	public function wch_info()
	{
		// 接收参数
		$param = request()->param();
		$member_id = Session::get("member_id");

		$id = isset($param['product_id']) ? $param['product_id'] : 0;

		if (0 == $id)
		{
			$res['msg'] = '无产品id';
			return json($res);
		}

		$product = Db::name('score_goods')
				   ->where('id', $id)
				   ->find();

		// 收货地址，如果未填写收货地址，那么为空
		if (0 == $member_id)
		{
			$product['receive_address'] = '';
		}
		else // 登录状态下
		{	
			// 将该用户的所有地址拿出来
			$receive_address = Db::name('member_address')
							   ->field('id as address_id, province_city, address, status')
							   ->where('member_id', $member_id)
							   ->select();

			// 判断是否有默认地址
			foreach ($receive_address as $k => $v)
			{
				if (1 == $v['status'])
				{
					$product['receive_address'] = $v['province_city'] . $v['address'];
					$product['address_id'] = $v['address_id'];
					break;
				}
				else
				{
					$product['receive_address'] =  $v['province_city'] . $v['address'];
					$product['address_id'] = $v['address_id'];
				}
			}
		}

		// 如果用户没有填写地址
		if (empty($product['receive_address']))
		{
			$product['receive_address'] = '';
		}

		// 默认显示，同城包邮，0
		if ('' == $product['receive_address'])
		{
			$product['freight'] = 0;
		}
		else // 当有地址的时候，判断是否为同城
		{	
			// 调用处理地址函数
			$product['receive_address'] = handleAddress($product['receive_address']);


			$city = mb_substr($product['receive_address'], 3, 3, 'utf-8');

			if ('长沙市' == $city)
			{
				$product['freight'] = 0;
			}
			else
			{
				$product['freight'] = 1; // 10元运费，1
			}
		}	
		$score = Db::name("mall_member")->where("id",$member_id)->value("score");
		$product['change_process'] = config('change_process');
		$comment = Db::name('shop_spot_comment')
					->join("too_mall_member","too_mall_member.id = too_shop_spot_comment.member_id")
					->field("member_id,content,too_shop_spot_comment.add_time,too_mall_member.headimg,too_mall_member.nickname")->where('wch_id',$id)
					->order('too_shop_spot_comment.id desc')->find();
        $num = Db::name('shop_spot_comment')->where('wch_id',$id)->count('id');
		$res['product'] = $product;
		$res['score'] = $score;
		$res['comment'] = $comment;
		$res['comment_num'] = $num;

		$this->assign("res",$res);
		return $this->fetch();
	}
	//创建订单
	public function creatorder()
	{
		$param = request()->param();
		$product_id = isset($param['product_id']) ? $param['product_id'] : 0;
		$member_id = Session::get("member_id");

		// 获得产品信息
		$product = Db::name('score_goods')->where('id', $product_id)->find();

		// 判断库存数量
		if (0 == $product['stock'])
		{
			$res['msg'] = '库存为0';
			return json($res);
		}

		if (0 == $product_id)
		{
			$res['msg'] = '无产品id';
			return json($res);
		}
		if (0 == $member_id)
		{
			$res['msg'] = '无当前用户id';
			return json($res);
		}

		// 订单号
		$param['order_sn'] = date('ymdhis',time()).get_rand_num();
		Session::set("wch_order_sn",$param['order_sn']);
		$param['member_id'] = $member_id;
		//unset($param['member_id']);

		$param['add_time'] = time();

		// 往订单插入记录
		$ins_res = Db::name('wch_order')->insert($param);

		if ($ins_res)
		{
			$product['ordersn'] = $param['order_sn'];
			$res['order'] = $product;
			$res['status'] = true;
		}
		else
		{
			$res['status'] = false;
		}

		return json($res);

	}

	//订单详情
	public function orderdetails()
	{
		$param = request()->param();
		$order_sn = Session::get("wch_order_sn");
		

		$member_id = Session::get("member_id");
		$order_info = Db::name("wch_order")->field("too_score_goods.name,too_score_goods.integral,too_score_goods.price,too_wch_order.id")->join("too_score_goods","too_score_goods.id = too_wch_order.product_id")->where("order_sn",$order_sn)->find();
		//收货地址
		$where['member_id'] = $member_id;
		$where['status'] = 1;
		$address = Db::name("member_address")->where($where)->select();

		//查询当前积分余额
		$score = Db::name("mall_member")->where("id",$member_id)->value("score");

		$info['address'] = $address;
		$info['order'] = $order_info;
		$info['score'] = $score;
		$info['order_sn'] = $order_sn;



		$this->assign("info",$info);
		return $this->fetch();
	}

	//收货地址
	public function selectravel()
	{
		$order_sn = Session::get("wch_order_sn");
		$member_id = Session::get("member_id");
		$address = Db::name("member_address")->where("member_id",$member_id)->select();

		$this->assign("order_sn",$order_sn);
		$this->assign("address",$address);
		return $this->fetch();
	}

	//增加收货地址
	public function add_address() {
        if (Request::instance()->isPost()) {
            $member_id = session::get('member_id');
            $info = Request::instance()->param();
            $info['member_id'] = $member_id;
            $bool = Db::name('member_address')->insert($info);
            if ($bool) {
                $res['status'] = true;
                $res['info'] = '添加成功';
            }else {
                $res['status'] = FALSE;
                $res['info'] = '添加失败';
            }
            echo json_encode($res);
            exit;
        }
        return $this->fetch();
    }

    //修改收货地址
    public function edit_address() {
        $id = Request::instance()->param('id');
        if (Request::instance()->isPost()) {
            $info = Request::instance()->param();
            $bool = Db::name('member_address')->update($info);
            if ($bool) {
                $res['status'] = true;
                $res['info'] = '修改成功';
            }else {
                $res['status'] = FALSE;
                $res['info'] = '修改失败';
            }
            echo json_encode($res);
            exit;
        }
        $data = Db::name('member_address')->where('id',$id)->find();
        $this->assign('data',$data);
        return $this->fetch();
    }

    //设置为默认地址
    public function set_address()
    {
    	$param = request()->param();
    	$where['member_id'] = Session::get("member_id");
    	$where['status'] = 1;
    	Db::name("member_address")->where($where)->setField("status",0);
    	if($param['id'])
    	{
    		$bool = Db::name("member_address")->where("id",$param['id'])->setField("status",1);
    		

    		if($bool)
    		{
    			$res = array("status"=>true,"info"=>"设置成功!");
    		}
    		else
    		{
    			$res = array("status"=>false,"info"=>"网络错误!");
    		}

    		return json($res);
    	}
    	else
    	{
    		$res = array("status"=>false,"info"=>"网络连接失败!");
    	}

    	return json($res);
    }


	//确认支付
	public function confirmpay()
	{
		$param = request()->param();
		log::write($param);
		$order_sn = Session::get("wch_order_sn");
		$member_id = Session::get("member_id");
		if (Request::instance()->isPost()) {
			$order_info = Db::name("wch_order")->where("order_sn",$order_sn)->find();
			$pro_info = Db::name("score_goods")->where("id",$order_info['product_id'])->find();
			$score = Db::name("mall_member")->where("id",$member_id)->value("score");

			if(!empty($order_info))
			{
				$data['address_id'] = $param['address_id'];
				$data['add_time'] = time();
				$data['freight'] = 0;
				$data['status'] = 0;
				$data['pro_num'] = $param['nums'];
				$data['total_free'] = $pro_info['price'] * $param['nums'];
				if($param['check']  == 1)
				{
					$data['total_free'] = $pro_info['price'] * $param['nums'] - ($score/20);
					$data['pay_score'] = $score;
					if($score > $order_info['pro_num']*$pro_info['integral'])
					{
						$data['pay_score'] = $order_info['pro_num']*$pro_info['integral'];
					}
				}
				else
				{

				}
				
				$bool  =  Db::name("wch_order")->where("order_sn",$order_sn)->update($data);

				if($bool)
				{
					$res = array("status"=>true,"info"=>"确认成功!");
				}
				else
				{
					$res = array("status"=>false,"info"=>"网络连接失败!");
				}

				return json($res);
			}
		}

		$info = Db::name("wch_order")->where("order_sn",$order_sn)->find();

		$address = Db::name("member_address")->where("id",$info['address_id'])->find();

		$product_info = Db::name("score_goods")->where("id",$info['product_id'])->find();
		$res_info['order_info'] = $info;
		$res_info['address_info'] = $address;
		$res_info['product_info'] = $product_info;
		$id = $info['id'];
        $pay = new \com\Paywch();
        $res = $pay->mwxpay($id,1);
        log::write($res);
        log::write("res");
        $this->assign('res',$res);
		$this->assign("res_info",$res_info);
		

		return $this->fetch();
	}

	//查看商品全部评价
	public function allcoment()
	{
		$param = request()->param();
		if($param['product_id'])
		{
			$allcoment = Db::name("shop_spot_comment")->where("wch_id",$param['product_id'])->order("add_time desc")->limit(50)->select();
		}
		$num = $allcoment = Db::name("shop_spot_comment")->where("wch_id",$param['product_id'])->count('id');
		$this->assign('num',$num);
		$this->assign('id',$param['product_id']);
		$this->assign("allcoment",$allcoment);

		return $this->fetch();
	}

	
}
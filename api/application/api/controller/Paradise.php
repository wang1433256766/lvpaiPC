<?php

namespace app\api\controller;

use think\Db;
use think\Log;
use com\alipay\Service\AopClient;
use com\alipay\Service\request\AlipayTradeAppPayRequest;
use app\api\model\ParadiseModel;

// 乐园类
class Paradise
{	
	// 文创产品列表
	public function getProductList()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		$where['status'] = 1;
		$where['type'] = 1;

		$product = Db::name('paradise_product')
				   ->field('id as product_id, name, score, cash, cover_img, end_time, stock')
				   ->where($where)
				   ->order('sort')
				   ->select();

		$res['body']['product_list'] = $product;
		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}

	// 首页
	public function getHomepageData()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接收参数
		$param = request()->param();

		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		$page = isset($param['page']) ? $param['page'] : 1;

		define('PAGESIZE', 10);
		$offset = ($page - 1) * PAGESIZE;

		if (0 == $cur_user_id)
		{
			$res['body']['score'] = 0;
		}
		else
		{
			$res['body']['score'] = Db::name('mall_member')
					 ->where('id', $cur_user_id)
					 ->value('score');
		}

		$banner = Db::name('paradise_activity')
				  ->field('id as banner_id, cover_img')
				  ->limit(5)
				  ->order('sort')
				  ->where('status', 1)
				  ->select();

		$where['status'] = 1;

		$product = Db::name('paradise_product')
				   ->field('id as product_id, name, score, cash, cover_img, end_time, stock')
				   ->where($where)
				   ->limit($offset, PAGESIZE)
				   ->order('sort')
				   ->select();
		
		$res['body']['banner_img'] = $banner;
		$res['body']['product'] = $product;
		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}

	public function showZeroTime()
	{
		echo strtotime(date("Y-m-d"));
		echo '<br>';
		echo time();
		echo '<br>';
		$second = time() - strtotime(date("Y-m-d"));

		echo $second;
	}

	// 任务列表
	public function getTaskList()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];		

		// 接收参数
		$param = request()->param();
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;

		if (0 == $cur_user_id)
		{
			$res['msg'] = '无用户id';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		// 用户任务表的条件
		$cond['user_id'] = $cur_user_id;

		// 得到零点时间戳
		$zerotime = strtotime(date("Y-m-d"));

		// 找出当前用户的记录
		$task = Db::name('member_task')
				->field('id, type, finish_time')
				->where($cond)
				->select();

		$arr = [];
		// 分享状态
		$arr['share'] = 0;
		
		$sign = 0;
		foreach ($task as $k => $v)
		{
			switch ($v['type']) {
					case '1':
						# code...
						break;
					case '2': // 发表咕咕
						if ($v['finish_time'] < $zerotime)
						{	
							$arr['send'] = 0;
						}
						else
						{
							$arr['send'] = 1;
						}
						break;
					case '3': // 普通签到
						if (0 == $sign)
						{
							if ($v['finish_time'] < $zerotime)
							{	
								$arr['sign'] = 0;
							}
							else
							{
								$arr['sign'] = 1;
								$sign = 1;
							}
						}
						break;
					case '4': // 特殊签到
						if (0 == $sign)
						{
							if ($v['finish_time'] < $zerotime)
							{	
								$arr['sign'] = 0;
							}
							else
							{
								$arr['sign'] = 1;
								$sign = 1;
							}
						}
						break;
					default:
						# code...
						break;
				}	
		}

		// 如果还未签到过，那么任务表中没有记录，所以可以签到
		if (empty($arr['sign']))
		{
			$arr['sign'] = 0;
		}

		// 如果还没有发表过，那么任务表中没有记录，所以可以发送
		if (empty($arr['send']))
		{
			$arr['send'] = 0;
		}

		// 分子，每日完成进度
		$molecule = 0;
		foreach ($arr as $v)
		{
			if (1 == $v)
			{
				$molecule++;
			}
		}

		// 个人信息完善条件
		$msg_cond['id'] = ['eq', $cur_user_id];
		$msg_cond['nickname'] = ['neq', 'null'];
		$msg_cond['headimg'] = ['neq', 'null'];
		$msg_cond['address'] = ['neq', 'null'];

		$arr['person_msg'] = Db::name('mall_member')->where($msg_cond)->count();

		$arr['birth'] = 'string';
		$arr['register'] = 1;

		// 得到用户积分
		$arr['score'] = Db::name('mall_member')
				 ->where('id', $cur_user_id)
				 ->value('score');
		$arr['molecule'] = $molecule;

		$res['body']['task_list'] = $arr;
		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}	

	public function sendGu()
	{
		$cond['type'] = 2;
		$cond['user_id'] = 6;

		// 得到零点时间
		$zerotime = strtotime(date("Y-m-d"));

		// 查询任务表中是否有记录
		$task = Db::name('member_task')->field('id, finish_time')->where($cond)->find();
		// dump($task);
		// exit();
		// 无记录时，插入新记录
		if(empty($task))
		{
			$arr = [];
			$arr['type'] = 2;
			$arr['finish_time'] = time();
			$arr['user_id'] = 6;
			$ins_res = Db::name('member_task')->insert($arr);

			if ($ins_res)
			{

			}
		}
		else
		{
			// 如果有记录，那么判断一下完成时间
			if ($task['finish_time'] < $zerotime)
			{
				$update['finish_time'] = time();
				$upd_res = Db::name('member_task')->where('id', $task['id'])->update($update);
			}
		}
	}

	public function lastDayTime()
	{
		$time = strtotime('2018-01-08 16:32:32');

		dump($time);
		echo '<br>';
		$today = strtotime(date("Y-m-d"));
		dump($today);

		echo '<br>', $today - $time;
	}

	public function getNews()
	{
		$news = Db::name('news_copy')
				->field('id, title, pic1, pic2, pic3')
				->select();

		foreach ($news as $k => $v)
		{
			if (($k % 17) == 0)
			{
				$new[] = $v;
			}
		}
		return json_encode($new);
	}

	// 文创产品详情
	public function getProductDetail()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接收参数
		$param = request()->param();

		$id = isset($param['product_id']) ? $param['product_id'] : 0;
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;

		if (0 == $id)
		{
			$res['msg'] = '无产品id';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		$product = Db::name('paradise_product')
				   ->field('id as product_id, name, score, cash, cover_img, stock, desc, end_time')
				   ->where('id', $id)
				   ->find();

		// 收货地址，如果未填写收货地址，那么为空
		if (0 == $cur_user_id)
		{
			$product['receive_address'] = '';
		}
		else // 登录状态下
		{	
			// 将该用户的所有地址拿出来
			$receive_address = Db::name('member_address')
							   ->field('id as address_id, province_city, address, status')
							   ->where('member_id', $cur_user_id)
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

		$product['change_process'] = config('change_process');
		$res['body']['product'] = $product;

		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}

	// 确认兑换页面
	public function getConfirmPay()
	{	
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接收参数
		$param = request()->param();

		$id = isset($param['product_id']) ? $param['product_id'] : 0;
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		$address_id = isset($param['address_id']) ? $param['address_id'] : 0;

		if (0 == $address_id)
		{
			$res['msg'] = '没有地址id';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		if (0 == $id)
		{
			$res['msg'] = '无产品id';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		if (0 == $cur_user_id)
		{
			$res['msg'] = '无用户id';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		$score = Db::name('mall_member')
				 ->where('id', $cur_user_id)
				 ->value('score');
		$res['body']['score'] = $score;

		$product = Db::name('paradise_product')
				   ->field('id as product_id, name, score, cash, cover_img')
				   ->where('id', $id)
				   ->find();

		// 拿到该用户选中的地址
		$receive_address = Db::name('member_address')
						   ->field('id as address_id, province_city, address, status, username as receive_nickname, phone')
						   ->where('id', $address_id)   
						   ->find();
		$receive_address['user_id'] = $cur_user_id;
		// 调用处理地址函数
		$receive_address['receive_address'] = handleAddress($receive_address['province_city'] .  $receive_address['address']);
		unset($receive_address['address']);
		unset($receive_address['province_city']);
		unset($receive_address['status']);

		$city = mb_substr($receive_address['receive_address'], 3, 3, 'utf-8');

		if ('长沙市' == $city)
		{
			$product['freight'] = 0;
		}
		else
		{
			$product['freight'] = 10; // 10元运费，1
		}
		
		$res['body']['product'] = $product;
		$res['body']['receive_msg'] = $receive_address;

		$res['body']['total_cash'] = $product['cash'] + $product['freight'];

		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}	

	// 确认支付按钮，创建订单
	public function createOrder()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接收参数
		$param = request()->param();

		$product_id = isset($param['product_id']) ? $param['product_id'] : 0;
		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		$address_id = isset($param['address_id']) ? $param['address_id'] : 0;
 
		// 获得产品信息
		$product = Db::name('paradise_product')->field('name, score, cash, stock')->where('id', $product_id)->find();

		// 判断库存数量
		if (0 == $product['stock'])
		{
			$res['msg'] = '库存为0';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		if (0 == $product_id)
		{
			$res['msg'] = '无产品id';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}
		if (0 == $cur_user_id)
		{
			$res['msg'] = '无当前用户id';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}
		if (0 == $address_id)
		{
			$res['msg'] = '无地址id';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		// 订单号
		$param['order_sn'] = $this->getOrderTradeNo();
		$param['user_id'] = $param['cur_user_id'];
		unset($param['cur_user_id']);

		$param['add_time'] = time();

		// 往订单插入记录
		$ins_res = Db::name('paradise_order')->insert($param);

		if ($ins_res)
		{
			$product['order_sn'] = $param['order_sn'];
			$res['body']['order'] = $product;
		}
		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}
	
	// 支付宝支付
	public function aliPay()
    {	
    	// 接收参数		    	
		$info = request()->param();		
		if(!isset($info['order_sn']) || empty($info['order_sn'])){
			$res['code'] = -1;
			$res['msg'] = '参数不正确';
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE);
		}
		//实例化支付接口
		$aop = new AopClient();

		$aop->gatewayUrl = "https://openapi.alipay.com/gateway.do"; //支付宝网关

		$aop->appId = '2017120800458912';

		$aop->rsaPrivateKey = "MIIEpQIBAAKCAQEAwZzZUXRPcQsCcUXtC/BUaYQtPhvBV4RD5sFhoZY8fd6F/cl3bVf01HJbwyELxG2bhdQ+ohWP6fY9fCWdjvdc7c6XGiULU0aRlM4PzJFcB0ijaKUXANx656bRefsPx2NCcsg7Y4WjZ3GFqfplR8GvMIH1cFcXlMwPA5sIDLePQt9oRpUku+9JEPcKpMTUT2Q5TtVSbmBMi3uQlLrsnlOYMgue2lqLiWH5ULLzhyVDok1Iyr0ZMZNBU2ljPPv1LLrWQBM4E5N6Ok3TS10j6O1RHFOAaSc7NbXnkU2RigjcRVZC1SJvKJhUKqUrN8SrZksw/dtT72rigAjZIJel7poIfwIDAQABAoIBACgM/zOHYOucgGvYMDoZBA0zx3wil7M37Cfu9vhLMMZE4ujl5SucV0wfP/Y0fs+qcNKVXkN/PF8EjiGBUn+Bkhqrcrx4z0fxwCnNGp2RsDzdfNgn1oNaGJ6U9p5KHjB7ywIdg5OJ0IyUgjbwUkUuvsPFDnWnvnwMXkq7pkWjLyKA4X3+AJA4kXxc6voqZTafgOFLm2MdyG8e3OZyH7H9GST9/TVuTks+0BA73fDB5+YuFg6IQGnXBa8ipNKVDnQ/3dcJjbOXhAyuLKkUYitGnEqzrIID82hhK6eYjs1r17dRwXHbndf9Sc7miXj4vRU5n4IvxD80DgWR2ojIlu/+0ZkCgYEA/7J4OmzhACChO9+vA8bRmylpy/T2QCUCTop2nraHMBJK/EfMxRLdt+WvZPH2CXHDf1MLi/BiTHMLtwR46p6AD0WsH23kdzcll1PAqiDrmUNS/rVGa69559Aia4D8g4bBWHhKLs5NLTUjeWGFoloanXe5+gjC2X7qIaBRK4xoovsCgYEAwdeN9W1EOJF+93sEpH9YG6Xfm0Uav1Icp3maFpNRm0bp7cub9jVPkMO1sramAmNVm67Tp+qCS63/hZAADCx74oyqMX6qsbw1aIbgo7VfhPOSQyrQo3t/bjApAdr/rGTldPkQh2/Huov7CAKn1hUbsknP4XbcqJMleZvS16fbmU0CgYEAroNnLTEMa6LZiFzFPUWf309nhq1cuDDKH0K/bNlU8Qk7Jm32ovaaXp2jlLjTXGTJsgfC1LKu1FCcyT+DK1YcPtAlhpnhGUCJJnwh/btUMRlS9sZQFsT/5agXJdo1/aqmITSQJNvmrpka6ggWRdVLXvfM9YqvOfQ15ddeLuO4sqsCgYEAkDzTNzKtNuKc9Y4mcDkJ2NjewGz1cDOrersze2T3rnFgFEATt2sH35Khm2/pq5E63HOEr0gC2EmK/1mdj3FyZauB4P5+CW8g1ry2X1rQzt9nkG9FfMCim91eRjEtWpxhWV8Te5QjrJ4Il3dSaCygxVPDSePmYdHMaucWRr5y22kCgYEAr98CqaWmTvnKU1ZriCMEnbMPsVqhad3vHFSCu5ZKiq478KFtDhGwAPUW6fbgDe8fSDtEC2ceWeOzDV52L6ymKW5hiik555+jRhvz1JeXfXgOFvjw/VtER6wQ+T5QluA3cTLXfi6np8gQmkE1K1c7SG4GRDFolILL2nbJLJl0/a0=";

		$aop->alipayrsaPublicKey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlHlW/CQJluIo5BTeVdubHsuoZZoWLglBwEA+u52qkDmWhOG2phXKtndBvgEagYzxczKIw3AIVXEVX8GCtuotyQKgqYCE6Yt9Jg0kbSsRUoFbDsex3UXBLNzoHvRgAuR4rcO/O+pBgxKNsUNX1My8qQeQr4P24OeQwY8Bi7JobVV+M+qAsIS4rWEqJBd9sMrxBo1isP5B2ynjjuwoqvb5uBm+fFJcIXdxfetE6qiL1k+hcc9rsI5jU3b9AERgTJbweslAETbdO9m7QYiNXiQKVVT8dY6hpgblwoGbxsjX3wDrvTGk2WNdXtZ+v1KfFOy6rmA+XZwvXrMf5ZVba8mrSwIDAQAB";

		// 添加相关参数
		$aop->postCharset = 'UTF-8';
		$aop->format = "json";
		$aop->signType = 'RSA2';

		$appRequest = new AlipayTradeAppPayRequest();
		//SDK已经封装掉了公共参数，这里只需要传入业务参数

		// $out_trade_no = date('Ymdhis') . mt_rand(0, 9999);

		// 获取订单号
		$order_sn = $info['order_sn'];

		// 根据订单号来查询一条记录，产品id
		$order = Db::name('paradise_order')->field('product_id')->where('order_sn', $order_sn)->find();

		// 根据产品id来拿到产品信息
		$product = Db::name('paradise_product')
				   ->field('name, cash, type')
				   ->where('id', $order['product_id'])
				   ->find();

		// 因为文创产品不同，所以订单标题要判断
		switch ($product['type']) {
			case '1': // 抱枕
				$product['title'] = '抱枕';
				break;
			case '2': // T袖
				$product['title'] = 'T袖';
				break;
			case '3': // 明信片
				$product['title'] = '明信片';
				break;
			default:
				$product['title'] = '文创产品';
				break;
		}

		
		$arr = [
				'body' => $product['name'], // 订单描述
				'subject' => $product['title'],  // 订单标题
				'timeout_express' => '30m',
				'out_trade_no' => $order_sn,   // 商户自己生成的订单号
				// 'total_amount' => $product['cash'],  // 实际
				'total_amount' => '0.01', // 测试
				'product_code' => 'QUICK_MSECURITY_PAY',
		]; 	       

		$bizcontent = json_encode($arr);
		$url = "http://www.zhlsfnoc.com/api/Paradise/aliPayNotify";
		$appRequest->setNotifyUrl($url);	//设置异步通知地址
		$appRequest->setBizContent($bizcontent);

		//这里和普通的接口调用不同，使用的是sdkExecute
		$response = $aop->sdkExecute($appRequest);
		// dump($response);
		// exit();
		//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题

		// echo htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
		//$response = htmlspecialchars($response);

		if ($response)
		{	
			$res['code'] = 1;
			$res['msg'] = '操作成功';
			$res['body']['response'] = $response;
		}
		// Log::write('支付宝支付');
		// Log::write($response);
		return json_encode($res,JSON_UNESCAPED_UNICODE);
		// 如果最后有问题可以尝试把htmlspecialchars方法去掉，直接返回$response
	}

	// 支付宝异步通知
	public function aliPayNotify()
	{
		$aop = new AopClient;

		$aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlHlW/CQJluIo5BTeVdubHsuoZZoWLglBwEA+u52qkDmWhOG2phXKtndBvgEagYzxczKIw3AIVXEVX8GCtuotyQKgqYCE6Yt9Jg0kbSsRUoFbDsex3UXBLNzoHvRgAuR4rcO/O+pBgxKNsUNX1My8qQeQr4P24OeQwY8Bi7JobVV+M+qAsIS4rWEqJBd9sMrxBo1isP5B2ynjjuwoqvb5uBm+fFJcIXdxfetE6qiL1k+hcc9rsI5jU3b9AERgTJbweslAETbdO9m7QYiNXiQKVVT8dY6hpgblwoGbxsjX3wDrvTGk2WNdXtZ+v1KfFOy6rmA+XZwvXrMf5ZVba8mrSwIDAQAB';

		$flag = $aop->rsaCheckV1($_POST, NULL, "RSA2");  //验证签名
		Log::write('支付宝回调');
		Log::write($_POST);
		log::write($flag);
		// 验证签名成功
		if ($flag)
		{
			// 得到订单号
			$out_trade_no = $_POST['out_trade_no'];

			// 得到交易状态
			$trade_status = $_POST['trade_status'];

			//得到支付宝交易号
			$trade_no = $_POST['trade_no'];

			//得到交易付款时间
			$gmt_payment = $_POST['gmt_payment'];

			// 得到订单金额
			$total_amount = $_POST['total_amount'];

			// 得到卖家的支付宝帐号
			$seller_email = $_POST['seller_email'];

			// 得到应用id
			$app_id = $_POST['app_id'];

			// 得到卖家支付宝帐号
			$seller_email = $_POST['seller_email'];
			// 先判断应用id是否相等
			if ($app_id == '2017120800458912')
			{
				// 再判断seller_email是否相等
				if ('2295836330@qq.com' == $seller_email)
				{
					// 接收订单号，去表中查询记录
					$orderInfo = Db::name('paradise_order')
							->field('product_id, user_id, freight')
				            ->where('order_sn',$out_trade_no)
				            ->find();

				    // 得到产品信息
				    $product = Db::name('paradise_product')
				    		->field('cash, score')
				    		->where('id', $orderInfo['product_id'])
				    		->find();

				    // 判断运费
				    if (0 == $orderInfo['freight'])
				    {
				    	$freight = 0;
				    }
				    else
				    {
				    	$freight = 10;
				    }

	
					// 再判断订单号是否存在
				    if ($orderInfo)
				    {
				    	// 最后判断金额是否相等
				    	// if ($total_amount == $product['cash'])
				    	// {	
				    		// OK，到这里全都验证成功，那么更新记录，改变订单状态
				    		$upd_param = [
				    			'pay_way' => 2,
				    			'pay_time' => strtotime($_POST['gmt_payment']),
				    			'status' => 1, 
				    		];

				    		$upd_res = Db::name('paradise_order')
				    				   ->where('order_sn', $out_trade_no)
				    				   ->update($upd_param);
				    		// 扣除用户积分
				    		Db::name('mall_member')
				    		->where('id', $orderInfo['user_id'])
				    		->setDec('score', $product['score']);
				    		Log::write($product['score']);

				    		if ($upd_res)
				    		{
				    			echo 'success';
				    			Log::write('支付宝交易状态：success');
				    			Log::write('订单号：');
				    			Log::write($out_trade_no);
				    		}
				    	// }
				    	// else
				    	// {
				    	// 	echo 'fail';
				    	// 	Log::write('支付金额不相等');
				    	// }
				    }
				    else
				    {
				    	echo 'fail';
				    	Log::write('订单号不存在');
				    }
				}	
				else
				{
					echo 'fail';
					Log::write('支付宝帐号不相等');
				}				
			}
			else
			{
				echo 'fail';
				Log::write('应用id不相等');
			}
		}
		else
		{
			echo 'fail';
			Log::write('验签失败');
		}
	}

	//调用微信统一下单接口
	 //@param body  string 商品描述 128长度
	 //@param order_sn  string 订单编号
    public function getPrePayOrder()
    {
	    $wx = new Wxpay();
	    $info = request()->param();

	    // 判断参数
	 	if(!isset($info['body']) || !isset($info['order_sn']) || empty($info['body']) ||empty($info['order_sn']))
	 	{
	    	$res['code'] = -1;
	    	$res['msg'] = '参数不正确';
	    	$res['body'] = array();
	    	return json_encode($res,JSON_UNESCAPED_UNICODE);
	    }	

	    $body = mb_substr($info['body'],0,30,'utf-8');
	    
	    $order_sn = $info['order_sn'];
	    $data = '咕咕旅行-'.$body;
	    
	    $result = $wx->getPrePayOrder($data, $order_sn);

	    // 判断是否为数组	
	 	if(is_array($result)){

			   	if($result['return_code'] == 'SUCCESS')
			   	{
			   		if($result['result_code'] == 'SUCCESS')
			   		{
			   			// 调起支付接口需要7个参数
			   			$arr['appid'] = $result['appid']; // 应用ID
				   		$arr['partnerid'] = $result['mch_id']; // 商户号	
				   		$arr['prepayid'] = $result['prepay_id']; // 预支付交易会话ID	
				   		$arr['package'] = 'Sign=WXPay'; // 扩展字段	
				   		$arr['noncestr'] = $wx->createNoncestr(); // 随机字符串	
				   		$arr['timestamp'] = time(); // 时间戳	
				   		//生成签名
				   		$data = $wx->getSign($arr);
				   		$arr['sign'] = $data; // 签名
				   		
			   			$res['code'] = 1;
			   			$res['msg'] = '操作成功';
			   			$res['body'] = $arr;

			   			return json_encode($res,JSON_UNESCAPED_UNICODE);
			   		}
			   		else
			   		{
			   			$res['body'] = array();
			   			$res['code'] = -3;
			   			$res['msg'] = $result['err_code_des'];
			   			return json_encode($res, JSON_UNESCAPED_UNICODE);
			   		}		   				   		
			   	}
			   	else
			   	{
			   		$res['body'] = array();
			   		$res['code'] = 0;
			   		$res['msg'] = $result['return_msg'];
			   		return json_encode($res,JSON_UNESCAPED_UNICODE);
			   	}
			   }
			   else
			   {
				   	$res['body'] = array();
				   	$res['code'] = -2;
				   	$res['msg'] = '该订单号不存在';
				   	return json_encode($res,JSON_UNESCAPED_UNICODE);
			   }

	}


	//微信支付结果回调
    public function NotifyData()
    {
    	$wx = new Wxpay();
    	//获取微信回调信息
    	$response = $wx->getNotifyData();
    	// log::write('微信回调');
    	// Log::write($response);
    	if($response['return_code'] == 'SUCCESS')
    	{
    		if($response['result_code'] == 'SUCCESS')
    		{   
    			// 根据微信传过来的订单号去表中查询记录
				$orderInfo = Db::name('paradise_order')
							->field('id, product_id, user_id')
				            ->where('order_sn',$response['out_trade_no'])
				            ->find();

				$score = Db::name('paradise_product')
						   ->where('id', $orderInfo['product_id'])
						   ->value('score');
				Log::write($score);

				// 判断订单号是否存在
				if ($orderInfo)
				{
					// 订单号存在，那么更新记录，改变订单状态
					$upd_param['pay_way'] = 1;

					Log::write('支付完成时间：');
					Log::write($response['time_end']);
					$upd_param['pay_time'] = handleWxTime($response['time_end']); // 支付时间
					$upd_param['status'] = 1; // 支付状态，已支付

					$upd_res = Db::name('paradise_order')
							   ->where('order_sn', $response['out_trade_no'])
							   ->update($upd_param);

					// 减少用户积分
					Db::name('mall_member')
					->where('id', $orderInfo['user_id'])
					->setDec('score', $score);


					// 判断更新是否成功
					if ($upd_res)
					{
						Log::write('微信异步，订单号：');
						Log::write($response['out_trade_no']);
						$res['return_code'] = 'SUCCESS';
						$con = $wx->arrayToXml($res);
						return $con;
					}
					else
					{
						Log::write('微信支付，更新记录失败？？？');
						Log::write($orderInfo);
						$res['return_code'] = 'FAIL';
						$con = $wx->arrayToXml($res);
						return $con;
					}
				}
				else // 订单号不存在
				{
					Log::write('微信支付失败？？？');
					Log::write($orderInfo);
					$res['return_code'] = 'FAIL';
					$con = $wx->arrayToXml($res);
					return $con;
				}
    		}
    	}
    }

    
    // 兑换记录接口，显示该用户已支付的订单
	public function getChangeRecord()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接收参数
		$param = request()->param();

		$cur_user_id = isset($param['cur_user_id']) ? $param['cur_user_id'] : 0;
		if (0 == $cur_user_id)
		{
			$res['msg'] = '用户id不存在';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		// 去订单表中查询当前用户已支付的订单
		$order_cond = [];
		$order_cond['user_id'] = $cur_user_id;
		$order_cond['status'] = 1; // 1是已支付的意思，后期肯定需要再加其他的状态，如：已签收

		// 得到很多个文创产品的id，以支付时间来倒序得到它们
		$id_arr = Db::name('paradise_order')
						 ->field('product_id, freight')
						 ->where($order_cond)
						 ->order('pay_time desc')
						 ->select();


		$product_list = [];
		foreach ($id_arr as $v)
		{
			// 少个运费字段
			$product_list[] = Db::name('paradise_product')
							->field('id as product_id, name, cover_img, cash, score, desc')
							->where('id', $v['product_id'])
							->find();
		}
		foreach ($product_list as $k => $v)
		{
			$product_list[$k]['freight'] = $id_arr[$k]['freight'];
		}

		$res['body']['change_record'] = $product_list;

		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}

	// 支付成功后的一些信息
	public function getPayMsg()
	{
		$res = [
			'code' => 1,
			'msg' => '操作成功',
			'body' => []
		];

		// 接收参数
		$param = request()->param();

		$order_sn = isset($param['order_sn']) ? $param['order_sn'] : 0;
		$type = isset($param['type']) ? $param['type'] : 0;

		if (0 == $order_sn)
		{
			$res['msg'] = '订单号不存在';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		if (0 == $type)
		{
			$res['msg'] = '类型不存在';
			return json_encode($res, JSON_UNESCAPED_UNICODE);
		}

		$paradise = new ParadiseModel();

		if (1 == $type)
		{
			$pay_msg = $paradise->getProductOrderMsg($order_sn);
		}
		else
		{
			$pay_msg = $paradise->getTicketOrderMsg($order_sn);
		}

		$res['body']['pay_msg'] = $pay_msg;
		
		return json_encode($res, JSON_UNESCAPED_UNICODE);
	}

	// 得到订单号
	public function getOrderTradeNo()
	{

		$str = '';
		for ($i=0; $i<18; $i++)
		{
			if (0 == $i)
			{
				$str = mt_rand(0, 9);
			}
			else
			{
				$str = $str . mt_rand(0, 9);
			}
		}
		return date('ymdhis') . $str;
	}
}
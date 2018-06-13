<?php
namespace app\api\controller;
use think\Db;
use think\Request;
use think\Log;
use think\Config;
use com\alipay\Service\AopClient;
use com\alipay\Service\request\AlipayTradeAppPayRequest;
class Mall{
	//惠趣商城首页
	public function getMallIndex(){
		//轮播图
		$spot =  [1,3,4];
		foreach($spot as $k=>$v){
				$img = Db::name('shop_spot_img')->field('spot_id,img')->where('spot_id',$v)->find();
				$start = strpos($img['img'],'/');
				//dump($start);
				if($start == 0){
					$img['img'] = 'http://www.zhonghuilv.net'.$img['img'];
				}
				$nav[] = $img;
			}
		//景点
		$spotInfo = Db::name('shop_spot')
					->field('id as spot_id,title_short as title,app_logo')
					->where('type','ZHL')
					->where('t_id','neq',0)
					->whereOr('id','eq',4124)
					->where('status',1)
					->select();
		//log::write('景点');
		//log::write($spotInfo);			
		//门票
		$ticketInfo = Db::name('shop_spot_ticket a')
					  ->join('shop_spot b','a.spot_id = b.id')
					  ->field('a.id as ticket_id,a.title,a.shop_price,b.thumb')
					  ->where('a.spot_id','in',[1,3,4])
					  ->where('a.status',1)
					  ->limit(5)
					  ->select();
		$res['code'] = 1;
		$res['msg'] = '操作成功';
		$res['body']['nav'] = $nav;
		$res['body']['spotInfo'] = $spotInfo;
		$res['body']['ticketInfo'] = $ticketInfo;
		return json_encode($res,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	}
	//更多景点列表	
	public function getSpotList(){
	   $spotInfo = Db::name('shop_spot')
					->field('id as spot_id,title,desc,thumb,shop_price,market_price')
					->where('id','in',[1,3,4])
					->where('status',1)
					->select();
		$spotInfo = getTodayOrder($spotInfo);
		$res['code'] = 1;
		$res['msg'] = '操作成功';
		$res['body'] = $spotInfo;
		return json_encode($res,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);						 
	}
	//获取景点详情
	//spot_id 景点id int
	//ticket_id 门票id int
	//member_id 会员id int
	public function getSpotDetail(){
		$info = Request::instance()->param();
		$spot_id = isset($info['spot_id']) ? $info['spot_id'] : '';
		$ticket_id = isset($info['ticket_id']) ? $info['ticket_id'] : '';
		$member_id = isset($info['member_id']) ? $info['member_id'] : 0;
		if((empty($spot_id) && empty($ticket_id)) || ($spot_id && $ticket_id)){
			$res['code'] = -1;
			$res['msg'] = '参数不正确';
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
		}
		//轮播图
		if($spot_id){
			$thumblist = Db::name('shop_spot_img')
						->where('spot_id',$spot_id)
						->field('img')
						->select();
		}elseif($ticket_id){
			$spot_id = Db::name('shop_spot_ticket')
					   ->where('id',$ticket_id)
					   ->value('spot_id');
			$thumblist = Db::name('shop_spot_img')
						->where('spot_id',$spot_id)
						->field('img')
						->select();		   	
		}
		//dump($thumblist);exit;
		foreach ($thumblist as $k => $v){
            $start = strpos($v['img'],'/');
            if($start == 0){
                $thumblist[$k] = 'http://www.zhonghuilv.net'.$v['img'];
            }else{
            	$thumblist[$k] = $v['img'];
            }

        }
        
        
        //dump($thumblist);
        $spotInfo = Db::name('shop_spot')
        			->field('id as spot_id,province,city,title,desc,address,longitude,latitude,content,opening,take,certificate,reminder')
        			->where('id',$spot_id)
        			->find();
        if(!$spotInfo){
        	$res['code'] = -2;
        	$res['msg'] = '没有找到对应的景点';
        	$res['body'] = [];
        	return json_encode($res,JSON_UNESCAPED_UNICODE);
        }	
        	$spotInfo['content'] = str_replace("\"", "'", $spotInfo['content']);
	        $spotInfo['reminder'] = str_replace('②', "\n②", $spotInfo['reminder']);
	    	$spotInfo['reminder'] = str_replace('③', "\n③", $spotInfo['reminder']);
	   		$spotInfo['reminder'] = str_replace('④', "\n④", $spotInfo['reminder']);
	        $spotInfo['content'] = str_replace("/upload", "http://www.zhonghuilv.net/upload", $spotInfo['content']);		
        //判断是否收藏
        if($member_id == 0){
        	$spotInfo['collect_status'] = 0;
        }else{
        	$collect_info = Db::name('member_collect')
        					->where('member_id',$member_id)
        					->where('type',5)
        					->where('post_id',$spot_id)
        					->find();
        	if($collect_info){
        		$spotInfo['collect_status'] = 1;
        	}else{
        		$spotInfo['collect_status'] = 0;
        	}
        }
        			//dump($spotInfo);exit;
        
        
        if($thumblist){
        	$spotInfo['img'] = $thumblist[1];
        }
        //$spotInfo['content'] = str_replace("<h4>", "", $spotInfo['content']);
        //$spotInfo['content'] = str_replace("</h4>", "", $spotInfo['content']);
        //$spotInfo['content'] = str_replace("<p>", "", $spotInfo['content']);
        //$spotInfo['content'] = str_replace("</p>", "", $spotInfo['content']);
        $ticketInfo = Db::name('shop_spot_ticket')
        			 ->field('id as ticket_id,desc,title,shop_price,market_price,today')
        			 ->where('spot_id',$spot_id)
        			 ->where('status',1)
        			 ->select();
        //猜你喜欢
        $like = Db::name('shop_spot')
				->field('id as spot_id,title,desc,thumb,shop_price,market_price')
				->where('type','ZHL')
				->where('id','neq',$spot_id)
				->where('id','in',[1,3,4])
				->where('status',1)
				->select();
				//dump($spotInfo);
        $res['code'] = 1;
        $res['msg'] = '操作成功';
        $res['body']['nav'] = $thumblist;
        $res['body']['spotInfo'] = $spotInfo;
        $res['body']['ticketInfo'] = $ticketInfo;
        $res['body']['like'] = $like;
        return json_encode($res,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); 			 				
    	

	}
	//新增出行人
	/**
	*@param   $[member_id] [用户id]   int
	*@param   $[name] [姓名]  string
	*@param   $[certificate] [证件号码] string
	*@param   $[mobile] [手机号码] mobile
	*@param   $[sex] [性别] 0代表女 1代表男
	*@param   $[email] [邮箱] string
	* 
	 */
	public function addTraveler(){
		$info = Request::instance()->param();
		if(!isset($info['member_id']) || !isset($info['name']) || !isset($info['certificate'])  || !isset($info['mobile'])  || !isset($info['sex']) || empty($info['member_id']) || empty($info['name']) || empty($info['certificate']) || empty($info['mobile'])){
			$res['code'] = -1;
			$res['msg'] = '参数不正确';
			$res['body'] = array();
      		return json_encode($res,JSON_UNESCAPED_UNICODE);
      	}
      	$email = isset($info['email']) ? $info['email']: '';
      	if(strlen($info['mobile']) != 11){
			$res['code'] = -2;
      		$res['msg'] = '手机号码长度不是11位';
      		$res['body'] = array();
       		return json_encode($res,JSON_UNESCAPED_UNICODE);
		}else{
      		$n = preg_match("/^1[34578]\d{9}$/",$info['mobile'],$array);
			if($n != 1){
				$res['code'] = -3;
	      		$res['msg'] = '手机号码不是合法的手机号码';
	      		$res['body'] = array();
	      		return json_encode($res,JSON_UNESCAPED_UNICODE);
			}
		}
		$vadidate = new \com\VadidateIdCard();
		$bool = $vadidate->validateIDCard($info['certificate']);
	    if(!$bool){
		    $res['code'] = -4 ;
		    $res['msg'] = '不是合法的身份证';
		    $res['body'] = array();
		    return json_encode($res,JSON_UNESCAPED_UNICODE);
	    }
	    // $pattern = '/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i';
	    // if(preg_match($pattern, $info['email'])){
	    // 	$res['msg'] = '邮箱地址不合法';
	    // 	$res['code'] = -5;
	    // 	return json_encode($res,JSON_UNESCAPED_UNICODE);
	    // }
	    $certificate = Db::name('member_traveler_info')
	    				->where('member_id',$info['member_id'])
	    				->where('status',1)
	    				->column('certificate');
	    if(in_array($info['certificate'],$certificate)){
	    	$res['msg'] = '出行人已经存在';
	    	$res['code'] = -5;
	    	$res['body'] = array();
	    	return json_encode($res,JSON_UNESCAPED_UNICODE);
	    }
		$date = substr($info['certificate'],6,8);
        $time = date('Ymd');
  	    $timeDiff = $time - $date;
        if($timeDiff > 180000){
      	  $body['types'] = 1;
        }else{
      	  $body['types'] = 0;
        }
        $body['member_id'] = $info['member_id'];
        $body['name'] = $info['name'];
        $body['sex'] = $info['sex'];
        $body['mobile'] = $info['mobile'];	
        $body['certificate'] = $info['certificate'];
        $body['email'] = $email;
        $result = Db::name('member_traveler_info')->insert($body,false,true);
        if($result){
        	$traveler_info = Db::name('member_traveler_info')
        					 ->where('id',$result)
        					 ->field('id as traveler_id,member_id,types,name,sex,mobile,certificate,mobile,email')
        					 ->find();
        	$res['code'] = 1;
        	$res['msg'] = '操作成功';
        	$res['body'] = $traveler_info;
        }else{
        	$res['code'] = 0;
        	$res['msg'] = '操作失败';
        	$res['body'] = array();
        }
        return json_encode($res,JSON_UNESCAPED_UNICODE);
	}
	//修改出行人
	
	/**
	*@param   $[member_id] [用户id]   int
	*@param   $[name] [姓名]  string
	*@param   $[certificate] [证件号码] string
	*@param   $[mobile] [手机号码] int
	*@param   $[sex] [性别] 0代表女 1代表男
	*@param   $[email] [邮箱] string
	*@param   $[traveler_id] [<出行人id>] int
	* 
	 */
	public function updateTraveler(){
		$info = Request::instance()->param();
		if(!isset($info['member_id']) || !isset($info['name']) || !isset($info['certificate'])  || !isset($info['mobile']) ||!isset($info['traveler_id']) || empty($info['member_id']) || empty($info['name']) || empty($info['certificate']) || empty($info['mobile']) || empty($info['traveler_id'])){
			$res['code'] = -1;
			$res['msg'] = '参数不正确';
			$res['body'] = array();
      		return json_encode($res,JSON_UNESCAPED_UNICODE);
      	}
      	$email = isset($info['email']) ? $info['email']: '';
      	if(strlen($info['mobile']) != 11){
			 $res['code'] = -2;
      		$res['msg'] = '手机号码长度不是11位';
      		$res['body'] = array();
       		return json_encode($res,JSON_UNESCAPED_UNICODE);
		}else{
      		$n = preg_match("/^1[34578]\d{9}$/",$info['mobile'],$array);
			if($n != 1){
				$res['code'] = -3;
	      		$res['msg'] = '手机号码不是合法的手机号码';
	      		$res['body'] = array();
	      		return json_encode($res,JSON_UNESCAPED_UNICODE);
			}
		}
		$vadidate = new \com\VadidateIdCard();
		$bool = $vadidate->validateIDCard($info['certificate']);
	    if(!$bool){
		    $res['code'] = -4 ;
		    $res['msg'] = '不是合法的身份证';
		    $res['body'] = array();
		    return json_encode($res,JSON_UNESCAPED_UNICODE);
	    }
	    $certificate = Db::name('member_traveler_info')
	    				->where('member_id',$info['member_id'])
	    				->where('id','neq',$info['traveler_id'])
	    				->where('status',1)
	    				->column('certificate');
	    if(in_array($info['certificate'],$certificate)){
	    	$res['msg'] = '出行人已经存在';
	    	$res['code'] = -5;
	    	$res['body'] = array();
	    	return json_encode($res,JSON_UNESCAPED_UNICODE);
	    }
		$date = substr($info['certificate'],6,8);
        $time = date('Ymd');
  	    $timeDiff = $time - $date;
        if($timeDiff > 180000){
      	  $body['types'] = 1;
        }else{
      	  $body['types'] = 0;
        }
        $body['member_id'] = $info['member_id'];
        $body['name'] = $info['name'];
        $body['sex'] = $info['sex'];
        $body['mobile'] = $info['mobile'];	
        $body['certificate'] = $info['certificate'];
        $body['email'] = $email;
        $result = Db::name('member_traveler_info')
                  ->where('id',$info['traveler_id'])
                  ->update($body);
    	if($result){
        	$res['code'] = 1;
        	$res['msg'] = '操作成功';
        	$res['body'] = array();
        }else{
        	$res['code'] = 0;
        	$res['msg'] = '操作失败';
        	$res['body'] = array();
        }
        return json_encode($res,JSON_UNESCAPED_UNICODE);
	}
	//删除出游人
	/**
	*@param [int] $[traveler_id] [<出游人id>]
	*@param [int] $[member_id] [<用户id>]
	 */
	public function deleteTraveler(){
		$info = Request::instance()->param();
		if(!isset($info['traveler_id']) || empty($info['traveler_id']) || !isset($info['member_id']) || empty($info['member_id'])){
			$res['code'] = -1;
			$res['msg'] = '参数不正确';
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE);
		}
		$result = Db::name('member_traveler_info')
				  ->where('member_id',$info['member_id'])
				  ->where('id',$info['traveler_id'])
				  ->delete();
		if($result){
			$res['code'] = 1;
			$res['msg'] = '操作成功';
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE);
		}else{
			$res['code'] = 0;
			$res['msg'] = '操作失败';
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE);
		}
	}
	 //获取出游人
	 //@param int member_id 用户id
	public function getTraveler(){
		$info = Request::instance()->param();
		if(!isset($info['member_id']) || empty($info['member_id'])){
			$res['code'] = -1;
			$res['msg'] = '参数不正确';
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE);
		}
		$travelInfo = Db::name('member_traveler_info')
					  ->field('id as traveler_id,name,mobile,certificate,email,sex')
					  ->where('member_id',$info['member_id'])
					  ->where('status',1)
					  ->select();
		$res['code'] = 1;
		$res['msg'] = '操作成功';
		$res['body'] = $travelInfo;
		return json_encode($res,JSON_UNESCAPED_UNICODE);			  
	}
	//创建订单
	/**
	*
	*@param   $[member_id] [<会员id>] int
	*@param   $[ticket_id] [<门票id>] int
	*@param   $[ticket_num] [<门票数量>] int
	*@param   $[traveler_ids] [<出游人id>] string 用逗号隔开
	*@param   $[travel_date] [<出游日期>]  string
	*@param   $[get_ticket_name] [<取票人姓名>]  int
	*@param   $[get_ticket_mobile] [<取票人手机号码>]  int  
	*@param   $[get_ticket_certificate] [<取票人身份证号码>]  string  
	 */
	public function createOrder(){
		$info = Request::instance()->param();
		//dump($info);
		if(!isset($info['member_id']) || !isset($info['ticket_id']) || !isset($info['ticket_num']) || !isset($info['traveler_ids']) || !isset($info['travel_date']) || !isset($info['get_ticket_name']) || !isset($info['get_ticket_mobile']) ){
			//echo 1;
			$res['msg'] = '参数不正确';
			$res['code'] = -1;
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE);
		}
		if(empty($info['member_id']) || empty($info['ticket_id']) || empty($info['ticket_num']) || empty($info['traveler_ids']) || empty($info['travel_date']) || empty($info['get_ticket_name']) || empty($info['get_ticket_mobile'])){
			//echo 2;
			$res['msg'] = '参数不正确';
			$res['code'] = -1;
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE);
		}
		//判断出游人数和票数是否一致
		$ticket_num = $info['ticket_num'];
		$traveler_num = count(explode(',', $info['traveler_ids']));
		if($ticket_num != $traveler_num){
			$res['code'] = -2;
			$res['msg'] = '出游人数和票数不一致';
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE);
		}
		$ticketInfo = Db::name('shop_spot_ticket')
					  ->where('id',$info['ticket_id'])
					  ->where('status',1)
					  ->find();	
		//判断票是否今日可定
		if($ticketInfo['today'] == 0){
			$res['code'] = -3;
			$res['msg'] = '今日票不可订';
			$res['body'] = array();
			if($info['travel_date'] == date('Y-m-d',time())){
				return json_encode($res,JSON_UNESCAPED_UNICODE);
			 } 	
		}
		//判断手机号码是否合法
		if(preg_match('/^1[3456789]\d{9}$/',$info['get_ticket_mobile']) != 1){
			$res['code'] = -4;
			$res['msg'] = '不是合法的手机号码';
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE);
		}
		
		$orderInfo['source'] = 'APP';
		$orderInfo['type'] = 'PFT';
		$orderInfo['order_sn'] = date('ymdhis',time()).get_rand_num();
		$orderInfo['spot_id'] = $ticketInfo['spot_id'];
		$orderInfo['ticket_id'] = $info['ticket_id'];
		$orderInfo['ticket_name'] = $ticketInfo['title'];
		$orderInfo['price'] = $ticketInfo['shop_price'];
		$orderInfo['num'] = $info['ticket_num'];
		$orderInfo['travel_date'] = $info['travel_date'];
		$orderInfo['traveler_ids'] = $info['traveler_ids'];
		$orderInfo['get_ticket_name'] = $info['get_ticket_name'];
		$orderInfo['get_ticket_mobile'] = $info['get_ticket_mobile'];
		//$orderInfo['get_ticket_certificate'] = $info['get_ticket_certificate'];
		$orderInfo['member_id'] = $info['member_id'];										
		$orderInfo['order_total'] = $info['ticket_num'] * $ticketInfo['shop_price'];				
		$orderInfo['add_time'] = time();
		$result = Db::name('spot_order')
				  ->insertGetId($orderInfo);

		if($result){
			$order_info = Db::name('spot_order')
						->field('id as order_id,order_sn,ticket_name,price,num,travel_date,add_time,order_total,status,refund_reason,refund_way,refund_time')
						->where('id',$result)
						->find();
						//dump($order_info);
		    $order_info['spot_name'] = Db::name('shop_spot')->where('id',$ticketInfo['spot_id'])->value('title');
		    $order_info['take'] = $ticketInfo['take'];
			$res['code'] = 1;
			$res['msg'] = '操作成功';
			$res['body'] = $order_info;
		}else{
			$res['code'] = 0;
			$res['msg'] = '操作失败';
			$res['body'] = array();
		}
		return json_encode($res,JSON_UNESCAPED_UNICODE);	

	}
	//获取订单列表
	//member_id 会员id int
	
	public function getOrderList(){
		
	}
	//获取订单详情
	//@param order_sn 订单编号 string
	//@param member_id 用户id int
	public function getOrderDetail(){
		$info = Request::instance()->param();
		if(!isset($info['order_sn']) || empty($info['order_sn']) || !isset($info['member_id']) || empty($info['member_id'])){
			$res['msg'] = '参数不正确';
			$res['code'] = -1;
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE);
		}
		$bool = Db::name('spot_order')
				->where('member_id',$info['member_id'])
				->where('order_sn',$info['order_sn'])
				->field('*,id as order_id')
				->find();		
		if(!$bool){
			$res['code'] = -2;
			$res['msg'] = '没有找到该订单';
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE);
		}
		//出行人列表
		$id = explode(',',$bool['traveler_ids']);
		$travelerlist = Db::name('member_traveler_info')
						->where('id','in',$id)
						->field('id as traveler_id,name,certificate')
						->select();
		$bool['travelerList'] = $travelerlist;				
		$bool['add_time'] = date('Y-m-d H:i:s',$bool['add_time']);
		unset($bool['id']);
		$res['code'] = 1;
		$res['msg'] = '操作成功';
		$res['body'] = $bool;
		return json_encode($res,JSON_UNESCAPED_UNICODE);
	}
	//调用微信统一下单接口
	 //@param body  string 商品名称 128长度
	 //@param order_sn  string 订单编号
    public function getPrePayOrder(){  
	    $wx = \com\Wxpay::getInstance();
	    $info = request()->param();
	 if(!isset($info['body']) || !isset($info['order_sn']) || empty($info['body']) ||empty($info['order_sn'])){
	    	$res['code'] = -1;
	    	$res['msg'] = '参数不正确';
	    	$res['body'] = array();
	    	return json_encode($res,JSON_UNESCAPED_UNICODE);
	    }	
	    $body = mb_substr($info['body'],0,30,'utf-8');
	    $order_sn = $info['order_sn'];
	    $data = '咕咕旅行-'.$body;
	    $result = $wx->getPrePayOrder($data,$order_sn);
	    //dump($result);
	    //Log::write('微信支付');
	    //Log::write($result);
	 	if(is_array($result)){
			   	if($result['return_code'] == 'SUCCESS'){
			   		if($result['result_code'] == 'SUCCESS'){
			   			$arr['appid'] = $result['appid'];
				   		$arr['partnerid'] = $result['mch_id'];
				   		$arr['prepayid'] = $result['prepay_id'];
				   		$arr['package'] = 'Sign=WXPay';
				   		$arr['noncestr'] = $wx->createNoncestr();
				   		$arr['timestamp'] = time();
				   		//生成签名
				   		$data = $wx->getSign($arr);
				   		$arr['sign'] = $data;
				   		
			   			$res['code'] = 1;
			   			$res['msg'] = '操作成功';
			   			$res['body'] = $arr;
			   			return json_encode($res,JSON_UNESCAPED_UNICODE);
			   		}else{
			   			$res['body'] = array();
			   			$res['code'] = -3;
			   			$res['msg'] = $result['err_code_des'];
			   			return json_encode($res,JSON_UNESCAPED_UNICODE);
			   		}		   				   		
			   	}else{
			   		$res['body'] = array();
			   		$res['code'] = 0;
			   		$res['msg'] = $result['return_msg'];
			   		return json_encode($res,JSON_UNESCAPED_UNICODE);
			   	}
			   }else{
			   	$res['body'] = array();
			   	$res['code'] = -2;
			   	$res['msg'] = '该订单号不存在';
			   	return json_encode($res,JSON_UNESCAPED_UNICODE);
			   }

		}
	//微信支付结果回调
    public function NotifyData(){
    	$wx = \com\Wxpay::getInstance();
    	//获取微信回调信息
    	$response = $wx->getNotifyData();
    	log::write('微信回调');
    	Log::write($response);
    	if($response['return_code'] == 'SUCCESS'){
    		if($response['result_code'] == 'SUCCESS'){   			
				$orderInfo = Db::name('spot_order')
				            ->where('order_sn',$response['out_trade_no'])
				            ->find();
			if($orderInfo['status'] == 0){
				            	$spot_id = Db::name('shop_spot')
				 			->where('id',$orderInfo['spot_id'])
				 			->value('t_id');
				 $ticket_id = Db::name('shop_spot_ticket')
				 			 ->where('id',$orderInfo['ticket_id'])
				 			 ->value('t_id');	
				$bool2 = false;
				$order_rebate = Db::name('spot_order')->where('order_sn',$response['out_trade_no'])->find();
				if($order_rebate['rebate_total']>0){
					$member_score = Db::name('mall_member')->where('id',$order_rebate['member_id'])->value('score');
					$left_score['score'] = $member_score-$order_rebate['rebate_total']*100;
					$member = Db::name('mall_member')->where('id',$order_rebate['member_id'])->update($left_score);
				}
				$order_info['trade_no'] = $response['transaction_id'];
				$order_info['pay_way'] = '微信支付';
				$order_info['payment'] = $response['total_fee'] / 100-$order_rebate['rebate_total'];
				$order_info['up_time'] = time();
				$order_info['status'] = 1;
				$order_info['pay_time'] = time();
				$bool2 = Db::name('spot_order')->where('order_sn',$response['out_trade_no'])->update($order_info);
				//Log::write($orderInfo);
				//Log::write('订单状态');
				if($orderInfo['type'] == 'PFT'){
					$traveler_ids = Db::name('spot_order')
								   ->where('status',1)
								   ->where('order_sn',$response['out_trade_no'])
								   ->value('traveler_ids');
								   log::write($traveler_ids);
					$traveler_id = explode(',',$traveler_ids);
					$travelInfo = Db::name('member_traveler_info')
								  ->where('id',$traveler_id[0])
								  ->value('certificate');
								  log::write($travelInfo);	
					//向票付通下单
					$re = $this->pft($spot_id,$ticket_id,$orderInfo['order_sn'],$orderInfo['order_sn'],$orderInfo['num'],$orderInfo['travel_date'],$orderInfo['get_ticket_name'],$orderInfo['get_ticket_mobile'],$travelInfo);
					if(isset($re['Rec']['UUcode']) && !empty($re['Rec']['UUcode'])){
						$res['UUcode'] = $re['Rec']['UUcode'];
	                    $res['UUqrcodeURL'] = $re['Rec']['UUqrcodeURL'];
	                    $res['order_code'] = $re['Rec']['UUordernum'];
	                    Db::name('spot_order')->where('id',$orderInfo['id'])->update($res);
	                    $status = 'ticket_book';
	                    $pftlog = $this->sendpft($status,$orderInfo['get_ticket_name'],$orderInfo['ticket_name'],$orderInfo['num'],$orderInfo['order_total'],$orderInfo['order_sn'],$orderInfo['get_ticket_mobile'],$re['Rec']['UUcode'],$re['Rec']['UUqrcodeURL']);
	                	log::write('pft回调数据');
	                	log::write($re);				
	                					}
						}
						if($bool2){
						  	$res['return_code'] = 'SUCCESS';
							$res['return_msg'] = 'Ok';
							$con = $wx->arrayToXml($res);
							return $con;
						}	
				            }	
    		}
    	}


    }
    	// 支付宝下单
	// order_sn string 订单编号
     public function aliPay()
    {			    	

    	//vendor();为TP5框架的方法，作用：导入第三方框架类库

		//vendor('alipay.aop.AopClient');

		//vendor('alipay.aop.request.AlipayTradeAppPayRequest','.php');
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

		// $aop->alipayrsaPublicKey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyk0xG+WX7S95sqJy26FFQbhR/fSMM01U363KaRj/uZp/IEy++5r9XIpbTQFAMoIu9pzNKIoHz4FQRCu1fuCc5WXOJyR6xjxRwsk08vwiU7UnAbiIiKd8/0X7M+Ta6y+JBMFuSxBe6OawmGbtSvMQltzqkh+017JFZFb8z5rCBZeu5YXeKzK/ZQCSkXh54dUCOTTNiRue5opd9tKaMZ/IulurytGxfnVMVQmzyKgiVHSL3fj96vl1W3rPu0ehQGSzKr7sB/d0TGRWwH+5qS+1R7xDuGiScdDVsMdEsi2+Uf00ZXMxWuNr/pAeNwH39vG+vgMSu3J80eKYSL22io2RAQIDAQAB
// ";

		$aop->alipayrsaPublicKey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlHlW/CQJluIo5BTeVdubHsuoZZoWLglBwEA+u52qkDmWhOG2phXKtndBvgEagYzxczKIw3AIVXEVX8GCtuotyQKgqYCE6Yt9Jg0kbSsRUoFbDsex3UXBLNzoHvRgAuR4rcO/O+pBgxKNsUNX1My8qQeQr4P24OeQwY8Bi7JobVV+M+qAsIS4rWEqJBd9sMrxBo1isP5B2ynjjuwoqvb5uBm+fFJcIXdxfetE6qiL1k+hcc9rsI5jU3b9AERgTJbweslAETbdO9m7QYiNXiQKVVT8dY6hpgblwoGbxsjX3wDrvTGk2WNdXtZ+v1KfFOy6rmA+XZwvXrMf5ZVba8mrSwIDAQAB";



		$aop->postCharset = 'UTF-8';

		$aop->format = "json";
		$aop->signType = 'RSA2';

		//实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay

		$appRequest = new AlipayTradeAppPayRequest();
		//SDK已经封装掉了公共参数，这里只需要传入业务参数

		$out_trade_no = date('Ymdhis') . mt_rand(0, 9999);

		// $arr = [
		// 	'body' => '余额充值',  //订单描述	
		//     'subject' => '充值',  //订单标题
		//     'timeout_express' => '30m',
		//     'out_trade_no' => $out_trade_no, //商户网站唯一订单号
		//     'total_amount' => '0.01', //订单总金额
		//     'product_code' => 'QUICK_MSECURITY_PAY', //固定值
		// ];

		// 获取订单号
		$order_sn = $info['order_sn'];

			// 根据订单号来查询一条记录，拿到2个字段：产品名字，订单金额
			$order = Db::name('spot_order')->field('ticket_name, order_total,rebate_total,member_id')->where('order_sn', $order_sn)->find();
			if($order['member_id'] == 2919265||$order['member_id']==1||$order['member_id'] = 2919285){
				$order_total = 0.01;
			}else{
				$order_total =$order['order_total']-$order['rebate_total'];
			}

			$arr = [
				'body' => $order['ticket_name'], // 订单描述
				'subject' => '门票',  // 订单标题
				'timeout_express' => '30m',
				'out_trade_no' => $order_sn,   // 商户自己生成的订单号
				'total_amount' => $order_total,
				//'total_amount' => '0.01',
				'product_code' => 'QUICK_MSECURITY_PAY',
			];        
        if($arr){
        	$res['body']= $arr;
        }
		$bizcontent = json_encode($arr);
		$url = "http://www.zhlsfnoc.com/api/Mall/aliPayNotify";
		$appRequest->setNotifyUrl($url);	//设置异步通知地址
		$appRequest->setBizContent($bizcontent);

		//这里和普通的接口调用不同，使用的是sdkExecute
		$response = $aop->sdkExecute($appRequest);

		// dump($response);
		//htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题

		// echo htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
		//$response = htmlspecialchars($response);

		if ($response)
		{	
			$res['code'] = 1;
			$res['msg'] = '操作成功';
			$res['body']['response'] = $response;
		}
		Log::write('支付宝支付');
		Log::write($response);
		return json_encode($res,JSON_UNESCAPED_UNICODE);
		// 如果最后有问题可以尝试把htmlspecialchars方法去掉，直接返回$response
	}
	public function aliPayNotify(){
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
			if ($app_id == '2017120800458912'){
				// 再判断seller_email是否相等
				if ('2295836330@qq.com' == $seller_email){

						$orderInfo = Db::name('spot_order')
				            ->where('order_sn',$out_trade_no)
				            ->find();
						 $spot_id = Db::name('shop_spot')
						 			->where('id',$orderInfo['spot_id'])
						 			->value('t_id');
						 $ticket_id = Db::name('shop_spot_ticket')
						 			 ->where('id',$orderInfo['ticket_id'])
						 			 ->value('t_id');	
						// 再判断订单号是否存在
						if ($orderInfo){
							// 再判断订单金额是否相等
							//if ($order_total == $total_amount){

									// 当进入这里，就是4个东西都判断成功
									$array1 = array(
										'trade_no'	=> $_POST['trade_no'],
										'payment'	=> $orderInfo['order_total']-$orderInfo['rebate_total'], 
										'pay_time'	=> strtotime($_POST['gmt_payment']),
										'pay_way'	=> '支付宝支付',
										'status'	=> 1,
										'up_time'	=> time()
										);
									
									$res1 = Db::name('spot_order')->where('order_sn', $out_trade_no)->update($array1);	
									log::write($res1);
									log::write('状态');
									$order_rebate = Db::name('spot_order')->where('order_sn',$out_trade_no)->find();
									if($order_rebate['rebate_total']>0){
										$member_score = Db::name('mall_member')->where('id',$order_rebate['member_id'])->value('score');
										$left_score['score'] = $member_score-$order_rebate['rebate_total']*100;
										$member = Db::name('mall_member')->where('id',$order_rebate['member_id'])->update($left_score);
									}									
									if($res1){
										if($orderInfo['type'] == 'PFT'){
											$traveler_ids = Db::name('spot_order')
														   ->where('status',1)
														   ->where('order_sn',$out_trade_no)
														   ->value('traveler_ids');
														   log::write($traveler_ids);
											//$traveler_id = explode(',',$traveler_ids);
											
											$travelInfo = Db::name('member_traveler_info')
														  ->where('id','in',$traveler_ids)
														  ->select();
														  log::write($travelInfo);
											$use_name = '';
											$use_card = '';
											foreach($travelInfo as $v){
												$use_name.= ($use_name == '' ? '' : ',').$v['name'];
												$use_card.= ($use_card == '' ? '' : ',').$v['certificate'];

											}
											log::write($use_name.$use_card);	
											//向票付通下单
											$re = $this->pft($spot_id,$ticket_id,$orderInfo['order_sn'],$orderInfo['order_sn'],$orderInfo['num'],$orderInfo['travel_date'],$use_name,$orderInfo['get_ticket_mobile'],$use_card);
											log::write('票付通');
											log::write($re);
											if(isset($re['Rec']['UUcode']) && !empty($re['Rec']['UUcode'])){
											$res['order_code'] = $re['Rec']['UUordernum'];
											$res['UUcode'] = $re['Rec']['UUcode'];
				                            $res['UUqrcodeURL'] = $re['Rec']['UUqrcodeURL'];
				                            Db::name('spot_order')->where('id',$orderInfo['id'])->update($res);
				                            $status = 'ticket_book';
				                            //发送票付通类短信
				                            $this->sendpft($status,$orderInfo['get_ticket_name'],$orderInfo['ticket_name'],$orderInfo['num'],$orderInfo['order_total'],$orderInfo['order_sn'],$orderInfo['get_ticket_mobile'],$re['Rec']['UUcode'],$re['Rec']['UUqrcodeURL']);
				                            }
										}
										
										
									}
									if($res1){
										echo 'success';
									}
									
							// }else{
							// 	echo '金额不相等';
							// }
						}else{
							echo '订单号不存在';
						}
					
				}else{
					echo '支付宝帐号不相等';
				}
			}else{
					echo '应用id不相等';
			}

		}else{
			// 验签失败
			 echo '验签失败';
		}  
	}
    //向票付通下单
    private function pft($spot_id,$ticket_id,$order_sn,$order_total,$product_num,$use_date,$use_name,$mobile,$use_card) {
        $admin = Config::get('admin');
        $arr = array(
            "ac" => $admin['ac'],
            "pw" => $admin['pw'],
            "lid" => $spot_id,                     //景区id
            "tid" => $ticket_id,                       //门票id
            "remotenum" => $order_sn,             //我们的订单号
            "tprice" => $order_total,                 //结算价，单位：分
            "tnum" => $product_num,               //数量
            "playtime" => $use_date,               //游玩日期
            "ordername" => $use_name,              //取票人姓名
            "ordertel" => $mobile,                   //取票人电话
            "contactTEL" => $mobile,                 //联系人电话
            "smsSend" => "1",                 //是否发送短信（0 发送1 不发送注：发短信只会返回双方订单号，不发短信才会将凭证信息返回
            "paymode" => "2",                  //扣款方式 （0 使用账户余额 2 使用供应商授信支付 4 现场支付注：余额不足返回错误122
            "ordermode" => 0,                  //下单方式
            "assembly" => "",                   //集合地点 可为空
            "series" => "",                         //团号  可为空
            "concatID" => 0,                        //联票id 未开放 0
            "pCode" => 0,                           //套票id 未开放 0
            "m" => '486497',                              //供应商id  查询门票列表的UUaid
            "personID" => $use_card,                       //身份证
            "memo" => "",                           //备注

        );
        $soap = new \SoapClient('http://open.12301.cc/openService/MXSE.wsdl');
        $result =$soap->__soapCall('PFT_Order_Submit',$arr);       //提交订单
        $result1 = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        log::write('票务通回调');
        Log::write($result1);
        return $result1;

    }
           //发送票付通订单短信(带二维码)
  public function sendpft($status,$usename,$product_name,$product_num,$order_total,$order_sn,$mobile,$UUcode,$UUqrcodeURL)
    {
        if ($status = 'ticket_book') {
            $content = config('sms_pft');
            $content = str_ireplace('{#NAME#}', $usename, $content);
            $content = str_ireplace('{#PRODUCT#}', $product_name, $content);
            $content = str_ireplace('{#NUM#}', $product_num, $content);
            $content = str_ireplace('{#TOTAL#}', $order_total, $content);
            $content = str_ireplace('{#ORDER_SN#}', $order_sn, $content);
            $content = str_ireplace('{#UUcode#}', $UUcode, $content);
            $content = str_ireplace('{#UUqrcodeURL#}', $UUqrcodeURL, $content);
            $mobile = $mobile;
        }elseif ($status = 'error') {
            $content = config('sms_error');
            $content = str_ireplace('{#NAME#}', $usename, $content);
            $content = str_ireplace('{#MOBILE#}', $mobile, $content);
            $content = str_ireplace('{#PRODUCT#}', $product_name, $content);
            $content = str_ireplace('{#NUM#}', $product_num, $content);
            $content = str_ireplace('{#TOTAL#}', $order_total, $content);
            $content = str_ireplace('{#ORDER_SN#}', $order_sn, $content);

            $mobile = '13548708010';
        }
        $prefix = '';
        $user = config('sms_username');
        $pass = config('sms_password');

        $msg = new \com\Msg($user,$pass);
        $info = $msg->sendMsg($mobile, $prefix, $content);
        Log::write($info);

    }
    //景区折扣下单界面
    public function order_inter(){
    	$info = Request::instance()->param();
    	$res = array(
            'status' => false,
            'code' => 0,
            'msg' => '操作失败',
            );
    	$ticket = Db::name('shop_spot_ticket')->where('id',$info['ticket_id'])->where('status',1)->find();
	$ticket['score'] = Db::name('mall_member')->where('id',$info['member_id'])->value('score');
	if($ticket){
		$res['status'] = true;
		$res['code'] = 1;
		$res['msg'] = '操作成功';
		$res['body'] = $ticket;
	}
	return json_encode($res,JSON_UNESCAPED_UNICODE);

    }

    //折扣商品下单
    public function order_discound(){
    	$info = Request::instance()->param();
    	$score = Db::name('mall_member')->where('id',$info['member_id'])->value('score');
    	$res = array(
            'status' => false,
            'msg' => '登录失败',
            );
    	$res['msg'] = '请提交姓名';
            !empty($info['get_ticket_name']) or die(json_encode($res,JSON_UNESCAPED_UNICODE));

            $res['msg'] = '请提交手机号码';
            !empty($info['get_ticket_mobile']) or die(json_encode($res,JSON_UNESCAPED_UNICODE));

            $res['msg'] = '请提交出游人信息';
            !empty($info['traveler_ids']) or die(json_encode($res,JSON_UNESCAPED_UNICODE));

            $res['msg'] = '请提交游玩日期';
            !empty($info['travel_date']) or die(json_encode($res,JSON_UNESCAPED_UNICODE));

            //判断出游人数和票数是否一致
		$ticket_num = $info['ticket_num'];
		$traveler_num = count(explode(',', $info['traveler_ids']));
		if($ticket_num != $traveler_num){
			$res['code'] = -2;
			$res['msg'] = '出游人数和票数不一致';
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE);
		}
		$ticketInfo = Db::name('shop_spot_ticket')
					  ->where('id',$info['ticket_id'])
					  ->where('status',1)
					  ->find();	
		//判断票是否今日可定
		if($ticketInfo['today'] == 0){
			$res['code'] = -3;
			$res['msg'] = '今日票不可订';
			$res['body'] = array();
			if($info['travel_date'] == date('Y-m-d',time())){
				return json_encode($res,JSON_UNESCAPED_UNICODE);
			 } 	
		}
		//判断手机号码是否合法
		if(preg_match('/^1[3456789]\d{9}$/',$info['get_ticket_mobile']) != 1){
			$res['code'] = -4;
			$res['msg'] = '不是合法的手机号码';
			$res['body'] = array();
			return json_encode($res,JSON_UNESCAPED_UNICODE);
		}
		

            $member = Db::name('mall_member')->where('id',$info['member_id'])->find();
            if($member){
            	$orderInfo['source'] = 'APP';
		$orderInfo['type'] = 'PFT';
		$orderInfo['order_sn'] = date('ymdhis',time()).get_rand_num();
		$orderInfo['spot_id'] = $ticketInfo['spot_id'];
		$orderInfo['ticket_id'] = $info['ticket_id'];
		$orderInfo['ticket_name'] = $ticketInfo['title'];
		$orderInfo['price'] = $ticketInfo['shop_price'];
		$orderInfo['num'] = $info['ticket_num'];
		$orderInfo['travel_date'] = $info['travel_date'];
		$orderInfo['traveler_ids'] = $info['traveler_ids'];
		$orderInfo['get_ticket_name'] = $info['get_ticket_name'];
		$orderInfo['get_ticket_mobile'] = $info['get_ticket_mobile'];
		//$orderInfo['get_ticket_certificate'] = $info['get_ticket_certificate'];
		$orderInfo['member_id'] = $info['member_id'];										
		$orderInfo['order_total'] = $info['ticket_num'] * $ticketInfo['shop_price'];				
		$orderInfo['add_time'] = time();
		if($info['score_type'] = 1){
			$orderInfo['rebate_total'] = $info['rebate_total'];
		}
		$result = Db::name('spot_order')
				  ->insertGetId($orderInfo);
            }
            if($result){
			$order_info = Db::name('spot_order')->field('id as order_id,order_sn,ticket_name,price,num,travel_date,add_time,order_total,status,refund_reason,refund_way,refund_time,rebate_total')->where('id',$result)->find();
						//dump($order_info);
		    $order_info['spot_name'] = Db::name('shop_spot')->where('id',$ticketInfo['spot_id'])->value('title');
		    $order_info['take'] = $ticketInfo['take'];
		    $order_info['order_total'] =  strval(number_format($order_info['order_total']-$order_info['rebate_total'],2));
		    
			$res['code'] = 1;
			$res['msg'] = '操作成功';
			$res['body'] = $order_info;
		}else{
			$res['code'] = 0;
			$res['msg'] = '操作失败';
			$res['body'] = array();
		}
		return json_encode($res,JSON_UNESCAPED_UNICODE);	
    }	
}
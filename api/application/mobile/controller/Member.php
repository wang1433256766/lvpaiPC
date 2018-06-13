<?php
namespace app\mobile\controller;

use app\mobile\model\MemberCashnumExamine;
use think\Db;
use think\Controller;
use think\Request;
use think\Session;
use app\mobile\controller\Base;
use think\log;
use think\Config;

class Member extends Base{
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
		return $this->fetch();	
	}

	public function member_bus(){
		$member_id = session::get('member_id');
//		var_dump($member_id);die;
		$ac = request()->param('ac');
		
		if(!empty($ac))
		{
			 $parent_id = authcode($ac,'DECODE');
			 $member_parent = Db::name("mall_member")->field("parent_id,type")->where("id",$member_id)->find();
			 if(empty($member_parent['parent_id']) && $member_parent['type'] == 0)
			 {
			 	$data['type'] = 2;
			 	$data['parent_id'] = $parent_id;
			 	Db::name("mall_member")->where("id",$member_id)->update($data);
			 }
		}

		$member_traveler = Db::name('mall_member')->where('id',$member_id)->find();

		$info['member_traveler'] = $member_traveler;
//		echo '<pre>';
//        var_dump($member_traveler);die;
		$where_traveler_sum['member_id'] = $member_id;
		$where_traveler_sum['type'] = 1;
		$member_traveler_sum = Db::name('travel_details')->where($where_traveler_sum)->count();
		$info['member_traveler_sum'] = $member_traveler_sum;

		$member_cashnum = Db::name('member_cashnum_examine')->where('member_id',$member_id)->count();
		$info['member_cashnum'] = $member_cashnum;

		//æŸ¥è¯¢æœ‰å¤šå°‘æ¡è®¢å•
		$where['promote_id'] = $member_id;
		$where['status'] = 5;
		$order_num = Db::name("spot_order")->where($where)->count("id");

		$this->assign('info',$info);
		$this->assign('order_num',$order_num);
        $this->assign('member_id',$member_id);
		return $this->fetch();	
	}



	public function member_comment(){
		if(session::has('member_id')){
			$member_id = session::get('member_id');
			$member = Db::name('mall_member')->where('id',$member_id)->find();
			$info = Db::name('shop_spot_comment')->where('member_id',$member_id)->select();
			foreach ($info as $k => $v) {
				$info[$k]['title'] = Db::name('shop_spot_ticket')->where('id',$v['ticket_id'])->value('title');
				$info[$k]['price'] = Db::name('shop_spot_ticket')->where('id',$v['ticket_id'])->value('shop_price');
			}
		}
		$this->assign('info',$info);
		$this->assign('member',$member);
		return $this->fetch();	
	}

	public function member_client(){
		if(session::has('member_id')){
			$member_id = session::get('member_id');
			$order_promote = Db::name('member_client')->where('member_id',$member_id)->select();
			
			//echo Db::name('spot_order')->getLastSql();
			 //dump($order_promote);
		}
		$this->assign('order_promote',$order_promote);
		return $this->fetch();	
	}

	public function member_details(){
		if(session::has('member_id')){
			$member_id = session::get('member_id');
			$today_sum = Db::name('member_promote')->where('member_id',$member_id)->whereTime('add_time','today')->count();
			$info['today_sum'] = $today_sum;

			$today_price = Db::name('member_promote')->where('member_id',$member_id)->whereTime('add_time','today')->field('sum(total) as price')->find();
			$info['today_price'] = $today_price;

			$promote_sum = Db::name('member_promote')->where('member_id',$member_id)->count();
			$info['promote_sum'] = $promote_sum;

			$promote_price = Db::name('member_promote')->where('member_id',$member_id)->field('sum(total) as price')->find();
			$info['promote_price'] = $promote_price;

			$order_promote_time = Db::name('member_promote')->where('member_id',$member_id)->field("FROM_UNIXTIME(add_time,'%Y-%m-%d') as days")->order('add_time desc')->group('days')->select();
			$info['order_promote_time'] = $order_promote_time;

			$order_promote = Db::name('member_promote')->where('member_id',$member_id)->order('add_time')->select();
			foreach ($order_promote as $k => $v) {
				$order_promote[$k]['title'] = Db::name('ticket')->where('id',$v['ticket_id'])->value('ticket_name');
				$order_promote[$k]['num'] = Db::name('spot_order')->where('id',$v['order_id'])->value('num');
				$order_promote[$k]['days'] = date("Y-m-d",$v['add_time']);
			}
			$info['order_promote'] = $order_promote;
			//dump($order_promote);
			//echo Db::name('member_promote')->getLastSql();
		}
		$this->assign('info',$info);
		return $this->fetch();	
	}

	public function member_information(){
		$member_id = session::get('member_id');
		$info = Db::name('member_traveler_info')->where('member_id',$member_id)->select();
		$this->assign('info',$info);
		return $this->fetch();	
	}

	public function delect_contact(){
		$info = Request::instance()->param();
		$res = array(
		            'status' => false,
		            'info' => '操作失败',
		            );
		if($info['id']){
			$delect_contact = Db::name('member_traveler_info')->where('id',$info['id'])->delete();
			if($delect_contact){
				$res['status'] = true;
				$res['info'] = '操作成功';
			}
		}
		

		echo json_encode($res);
	}

	public function member_addpeople(){

		return $this->fetch();
	}

	public function submit_add(){
		$info = Request::instance()->param();
	        	$res['status'] = FALSE;
	            if (!isCreditNo($info['use_card'])) {
	                $res['info'] = '身份证错误';
	                echo json_encode($res);
	                exit;
	            }
		        $info['member_id'] = session::get('member_id');
		        $bool = Db::name('member_traveler_info')->insert($info);
		if ($bool) {
		            $res['status'] = TRUE;
		            $res['info'] = '操作成功';
	            }else {
	                $res['info'] = '操作失败';
	            }
	            echo json_encode($res);
	}
	public function member_mod(){
		$info = Request::instance()->param();
		if($info['id']){
			$bool = Db::name('member_traveler_info')->where('id',$info['id'])->find();
		}
		//dump($bool);
		$this->assign('bool',$bool);
		return $this->fetch();
	}

	public function submit_mod(){
		$info = Request::instance()->param();
	        	$res['status'] = FALSE;
	            if (!isCreditNo($info['use_card'])) {
	                $res['info'] = '身份证错误?';
	                echo json_encode($res);
	                exit;
	            }
		        //$info['member_id'] = session::get('member_id');
		        $data['use_card'] = $info['use_card'];
		        $data['use_name'] = $info['use_name'];
		        $bool = Db::name('member_traveler_info')->where('id',$info['id'])->update($data);
		if ($bool) {
		            $res['status'] = TRUE;
		            $res['info'] = '操作成功';
	            }else {
	                $res['info'] = '操作失败';
	            }
	            echo json_encode($res);
	}

	public function member_tour(){
		$member_id = session::get('member_id');
		$info = Db::name('travel_details')->where('member_id',$member_id)->select();

		$this->assign('info',$info);

		return $this->fetch();
	}

	

	public function member_earnmoney(){

		return $this->fetch();
	}
	public function member_cashnum(){
		$member_id = session::get('member_id');
		$info = Db::name('member_cashnum_examine')->where('member_id',$member_id)->select();
		foreach ($info as $k => $v) {
            switch ($v['status']){
                case 1:
                    $info[$k]['status'] = '未审核';
                    break;
                case 2:
                    $info[$k]['status'] = '审核通过';
                    break;
                case 3:
                    $info[$k]['status'] = '审核失败';
                    break;
                case 4:
                    $info[$k]['status'] = '提现成功';
                    break;
                case 5:
                    $info[$k]['status'] = '提现失败';
                    break;
                case 6:
                    $info[$k]['status'] = '取消提现';
                    break;
                default:
                    $info[$k]['status'] = '未知';
                    break;
            }
        }
		$this->assign('info',$info);

		return $this->fetch();
	}
	public function member_usemoney(){
		$data = Db::name('ticket')->select();
	    	$this->assign('data',$data);

		return $this->fetch();
	}

	public function member_donate(){
		$member_id = session::get('member_id');
		$info = Db::name('mall_member')->where('id',$member_id)->find();
		$type = 0;
		if(session::has('client')){
			$client = session::get('client');
			//dump($client);
			$where_client['id'] = $client['client_id'];
			$member_client = Db::name('member_client')->where($where_client)->find();
			//dump($member_client);
			$type = 1;
			$this->assign('member_client',$member_client);
		}

		$this->assign('info',$info);
		$this->assign('type',$type);

		return $this->fetch();
	}

	public function member_pick(){

		$member_id = session::get('member_id');
		$info = Db::name('member_client')->where('member_id',$member_id)->select();

		$this->assign('info',$info);

		return $this->fetch();
	}

	public function member_y(){
		$info = Request::instance()->param();
		$res = array(
			            'status' => false,
			            'info' => '操作失败',
			            );
		if($info){
			session::set('client',$info);
			$res['status'] = true;
			$res['info'] = '操作成功';
		}
	echo json_encode($res);	
	}

	public function member_give(){
		$info = Request::instance()->param();
		$res = array(
			            'status' => false,
			            'info' => '操作失败',
			            );
		$member_id = session::get('member_id');
		$member_score = Db::name('mall_member')->where('id',$member_id)->find();
		$res['info'] = '旅行币不足';
		if(intval($member_score['score'])<intval($info['sum'])){
			echo json_encode($res);
			die;
		}
		if($info){
			$member_client = Db::name('member_client')->where('id',$info['id'])->find();

			$member_give = Db::name('mall_member')->where('id',$member_client['give_id'])->find();
			$data_give['score'] = $member_give['score']+$info['sum'];
			$member_gives = Db::name('mall_member')->where('id',$member_client['give_id'])->update($data_give);

			$member = Db::name('mall_member')->where('id',$member_client['member_id'])->find();
			$data_member['score'] = $member['score']-$info['sum'];
			$member_s = Db::name('mall_member')->where('id',$member_client['member_id'])->update($data_member);

			if($member_s){
				$data_detaile['member_id'] = $member_client['member_id'];
				$data_detaile['title'] = '赠送客户';
				$data_detaile['num'] = -$info['sum'];
				$data_detaile['add_time'] = time();
				$data_detaile['give_id'] = $member_client['give_id'];
				$data_detaile['cue_type'] = 1;
				$member_details = Db::name('travel_details')->insert($data_detaile);
				if($member_details){
					session::delete('client');
					$res['status'] = true;
					$res['info'] = '操作成功';
				}
			}
			
		}
	echo json_encode($res);	
	}

	public function member_poster(){
		$info = Request::instance()->param();
		$member_id = $info['member_id'];
		$ac = authcode($member_id);
		$name = Db::name('mall_member')->where('id',$member_id)->value('name');
		$base = new Base();
		$haibao = $base->poster($member_id);
		//dump($haibao);
		$route = substr($haibao,1);

		$options = Config::get('weixin');
	        $weixin = new \com\Wechat($options);
	        $js_ticket = $weixin->getJsTicket();
	        $js_sign = array();
	        $data['status'] = 1;
	        if (!$js_ticket) {
	            \think\Cache::clear();
	            $js_ticket = $weixin->getJsTicket();
	            if (!$js_ticket) {
	                $str = "获取js_ticket失败！<br>";
                	    $str .= '错误码：'.$weixin->errCode;
                	    $str .= ' 错误原因：'.\com\wechat\Derrcode::getErrText($weixin->errCode);
	                Log::write($str,'notice');
	                $data['status'] = 0;
	            }
	        }
	        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	        $js_sign = $weixin->getJsSign($url);
	        $js_sign['appid'] = $options['appid'];
	        if (!isset($js_sign['timestamp'])) {
	            $js_sign['timestamp'] = time();
	        }
	        $this->assign('js_sign',$js_sign);
	        $this->assign('name',$name);
	        $this->assign('ac',$ac);
		$this->assign('member_id',$member_id);
		$this->assign('route',$route);
		return $this->fetch();
	}

	public function fenxiao_poster(){
		$info = Request::instance()->param();
		$member_id = $info['member_id'];
		$ac = authcode($member_id);
		$name = Db::name('mall_member')->where('id',$member_id)->value('name');
		$base = new Base();
		$haibao = $base->fx_poster($member_id);
		//dump($haibao);
		$route = substr($haibao,1);
		$options = Config::get('weixin');
	            $weixin = new \com\Wechat($options);
	            $js_ticket = $weixin->getJsTicket();
	            $js_sign = array();
	            $data['status'] = 1;
	        if (!$js_ticket) {
	            \think\Cache::clear();
	            $js_ticket = $weixin->getJsTicket();
	            if (!$js_ticket) {
	                $str = "获取js_ticket失败！<br>";
                	    $str .= '错误码：'.$weixin->errCode;
                            $str .= ' 错误原因：'.\com\wechat\Derrcode::getErrText($weixin->errCode);
	                Log::write($str,'notice');
	                $data['status'] = 0;
	            }
	        }
	        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	        $js_sign = $weixin->getJsSign($url);
	        $js_sign['appid'] = $options['appid'];
	        if (!isset($js_sign['timestamp'])) {
	            $js_sign['timestamp'] = time();
	        }
	        $this->assign('js_sign',$js_sign);
	        $this->assign('name',$name);
	        $this->assign('ac',$ac);
		$this->assign('member_id',$member_id);
		$this->assign('route',$route);
		return $this->fetch();
	}

	public function ajax() {
	        $info = Request::instance()->param();
	        Log::write('ttttt');
	        Log::write($info);
	    }

	public function poster_ajax(){
		$res = array(
			            'status' => false,
			            'info' => '您没有权限',
			            );
		$member_id = session::get('member_id');
		$info = Db::name('mall_member')->where('id',$member_id)->find();
		if($info['type'] == 1 || $info['type'] ==2){
			$res['status'] = true;
			$res['info'] = '操作成功';
		}
		echo json_encode($res);
	}

	public function point_out(){
		$res = array(
			            'status' => false,
			            'info' => '操作失败',
			            'data' =>'',
			            );
		$member_id = session::get('member_id');
		$where['give_id'] = $member_id;
		$where['cue_type'] = 1;
		$info = Db::name('travel_details')->where($where)->find();
		if($info){
			$info['sum'] = abs($info['num']);
			$info['member_id'] = Db::name('mall_member')->where('id',$info['member_id'])->value('name');
			$res['status'] = true;
			$res['info'] = '操作成功';
			$res['data'] = $info;
		}
		echo json_encode($res);	
	}

	public function point_ajax(){
		$info = Request::instance()->param();
		$res = array(
			            'status' => false,
			            'info' => '操作失败',
			            );
		$where['id'] = $info['id'];
		$data['cue_type'] = 2;
		$bool = Db::name('travel_details')->where('id',$info['id'])->update($data);
		if($bool){
			$res['status'] = true;
			$res['info'] = '操作成功';
		}
		echo json_encode($res);	
	}

	public function member_moneyfunds(){
		$member_id = session::get('member_id');
		$member = Db::name('mall_member')->where('id',$member_id)->find();
		$this->assign('member',$member);
        $options = Config::get('weixin');
        $weixin = new \com\Wechat($options);
        $js_ticket = $weixin->getJsTicket();
        $data['status'] = 1;
        if (!$js_ticket) {
            \think\Cache::clear();
            $js_ticket = $weixin->getJsTicket();
            if (!$js_ticket) {
                $str = "获取js_ticket失败！<br>";
                $str .= '错误码：'.$weixin->errCode;
                $str .= ' 错误原因：'.\com\wechat\Derrcode::getErrText($weixin->errCode);
                Log::write($str,'notice');
                $data['status'] = 0;
            }
        }
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $js_sign = $weixin->getJsSign($url);
        $js_sign['appid'] = $options['appid'];
        if (!isset($js_sign['timestamp'])) {
            $js_sign['timestamp'] = time();
        }
        $this->assign('js_sign',$js_sign);
        $where_bank['status'] = 1;
        $where_bank['member_id'] = $member_id;
        $bank = Db::name("bank_bind")
        		->field("too_bank_bind.*,too_bank.BankId,too_bank.BankName")
        		->join("too_bank","too_bank.BankId = too_bank_bind.bank_id")
        		->where($where_bank)->find();
        $this->assign('bank',$bank['bank_no']);
		return $this->fetch();
	}

	//绑定银行卡
	public function bind_bank()
	{
		if(request()->isPost())
		{
			$info = request()->param();
			$bank_no = $info['bank_no']; //存在  
			$key = 'e72aced86f41fba0d955e1014dc4b8fa';
			$url='http://v.juhe.cn/bankcardinfo/query?key='.$key.'&bankcard='.$bank_no;  
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_HEADER, 0);
    		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    		$tmpInfo = curl_exec($curl);
    		curl_close($curl);
			$result = json_decode($tmpInfo,true);  
			if($result['error_code'] != 0){
				$res['code'] = '-1';
				$res['msg'] = '银行卡号填写错误';

				return json($res);
			}
			if(isset($result['result']['cardtype']) && $result['result']['cardtype'] != "借记卡"){
				$res['code'] = '-2';
				$res['msg'] = '信用卡不可用';

				return json($res);
			}
			$member_id = Session::get("member_id");
			$bank_info = Db::name("bank_bind")->where("member_id",$member_id)->find();
			if(empty($bank_info)){
				$data['bank_no'] = $bank_no;
				$data['bank_name'] = $result['result']['bank'];
				$data['member_id'] = $member_id;
				$data['username'] = $info['username'];
				$data['mobile'] = $info['mobile'];
				$data['status'] = 1;
				$bool = Db::name("bank_bind")->insert($data);
				if($bool){
					$res['code'] = 1;
					$res['msg'] = '绑定成功!';
					return json($res);
				}else{
					$res['code'] = '-1';
					$res['msg'] = '绑定失败!';
					return json($res);
				}
			}else{
				$data['bank_no'] = $bank_no;
				$data['bank_name'] = $result['result']['bank'];
				$data['member_id'] = $member_id;
				$data['username'] = $info['username'];
				$data['mobile'] = $info['mobile'];
				$data['status'] = 1;
				$bool = Db::name("bank_bind")->where("member_id",$member_id)->update($data);
				if($bool){
					$res['code'] = 1;
					$res['msg'] = '绑定成功!';
					return json($res);
				}else{
					$res['code'] = '-1';
					$res['msg'] = '绑定失败!';
					return json($res);
				}
			}
			
		}

		$bank = Db::name("bank")->select();
		$this->assign('bank',$bank);

		return $this->fetch();
	}

	//提现申请通知页
	public function withdraw_notify()
	{
		return $this->fetch();		
	}

    /**
     * 提现到微信零钱
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function total_ajax(){
		$info = Request::instance()->param();
		$res = array(
		    'status' => false,
            'info' => '操作失败',
        );
		$member_id = session::get('member_id');
		$member = Db::name('mall_member')->where('id',$member_id)->find();
		$res['info'] = '金额不足';
		if($member['money']<$info['num']) exit(json_encode($res));
        // 处理图片开始
        $img_path = '';
        if (! key_exists('img', $info))exit(json_encode(array('code' => 10001, 'content' => '', 'msg' =>
        '请上传凭证')));
        foreach ($info['img'] as $k => $v) {
            $data = base64_decode(substr($v, 23));
            $destination =$this->getDestination();
            file_put_contents($destination, $data);
            $true_path = 'http://'.$_SERVER['SERVER_NAME'].'/'. $destination;
            if ('' == $img_path) {
                $img_path = $true_path;
            } else {
                $img_path = $img_path . ',' . $true_path;
            }
        }
        $insert_data = array(
            'member_id' => $member_id,
            'createtime' => time(),
            'money' => $info['num'],
            'status' => MemberCashnumExamine::UNAUDITED,
        );
        $insert_res = MemberCashnumExamine::insertInformation($insert_data, $member['money'], $img_path);
        if ($insert_res === true) {
            exit(json_encode(array('code' => 10000, 'content' => '', 'msg' => '提交成功')));
        } else {
            exit(json_encode(array('code' => 10001, 'content' => '', 'msg' => $insert_res)));
        }
	}

	/**
     * 提现到银行卡
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function draw_bank(){
		$info = Request::instance()->param();
		$res = array(
		    'status' => false,
            'info' => '操作失败',
        );
		$member_id = session::get('member_id');
		$member = Db::name('mall_member')->where('id',$member_id)->find();
		$bankId = Db::name("bank_bind")->where("member_id",$member_id)->value("id");
		if(!$bankId) exit(json_encode($res));
		$res['info'] = '金额不足';
		if($member['money']<$info['num']) exit(json_encode($res));
        // 处理图片开始
        $img_path = '';
        if (! key_exists('img', $info))exit(json_encode(array('code' => 10001, 'content' => '', 'msg' =>
        '请上传凭证')));
        foreach ($info['img'] as $k => $v) {
            $data = base64_decode(substr($v, 23));
            $destination =$this->getDestination();
            file_put_contents($destination, $data);
            $true_path = 'http://'.$_SERVER['SERVER_NAME'].'/'. $destination;
            if ('' == $img_path) {
                $img_path = $true_path;
            } else {
                $img_path = $img_path . ',' . $true_path;
            }
        }
        $insert_data = array(
            'member_id' => $member_id,
            'createtime' => time(),
            'money' => $info['num'],
            'status' => MemberCashnumExamine::UNAUDITED,
            'bankId' => $bankId
        );
        $insert_res = MemberCashnumExamine::insertInformation($insert_data, $member['money'], $img_path);
        if ($insert_res === true) {
            exit(json_encode(array('code' => 10000, 'content' => '', 'msg' => '提交成功')));
        } else {
            exit(json_encode(array('code' => 10001, 'content' => '', 'msg' => $insert_res)));
        }
	}

    /**
     * 为上传文件命名唯一名称
     * @return string
     */
    function getDestination( )
    {
        $date = date('Ymd');
        $filename = 'uploads/money_funds/' .$date;
        if (file_exists($filename)) {
            return $filename . '/' . time().mt_rand(0, 99999). '.jpg';
        } else {
            mkdir($filename, 0777);
            chmod($filename, 0777);
            return $filename . '/' .  time().mt_rand(0, 99999) . '.jpg';
        }
    }

    public function member_address(){
        $member_id = session::get('member_id');
        $info = Db::name('member_address')->where('member_id',$member_id)->select();
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function add_address() {
        if (Request::instance()->isPost()) {
            $member_id = session::get('member_id');
            $info = Request::instance()->param();
            $info['member_id'] = $member_id;
            $bool = Db::name('member_address')->insert($info);
            if ($bool) {
                $res['status'] = true;
                $res['info'] = '操作成功';
            }else {
                $res['status'] = FALSE;
                $res['info'] = '操作失败';
            }
            echo json_encode($res);
            exit;
        }
        return $this->fetch();
    }

    public function edit_address() {
        $id = Request::instance()->param('id');
        if (Request::instance()->isPost()) {
            $info = Request::instance()->param();
            $bool = Db::name('member_address')->update($info);
            if ($bool) {
                $res['status'] = true;
                $res['info'] = '操作成功';
            }else {
                $res['status'] = FALSE;
                $res['info'] = '操作失败';
            }
            echo json_encode($res);
            exit;
        }
        $data = Db::name('member_address')->where('id',$id)->find();
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function del_address() {
        if (Request::instance()->param()) {
            $id = Request::instance()->param('id');
            $bool = Db::name('member_address')->where('id',$id)->delete();
            if ($bool) {
                $res['status'] = true;
                $res['info'] = '操作成功';
            }else {
                $res['status'] = FALSE;
                $res['info'] = '操作失败';
            }
            echo json_encode($res);
            exit;
        }
    }
    //分销管理
    public function member_guangli(){
    	$member_id = session::get('member_id');

    	//$where['too_mall_member.type'] = 2;
    	$where['parent_id'] = $member_id;
    	$fx_user_number = Db::name("mall_member")->where($where)->count();
    	$where['too_spot_order.status'] = 5;
    	$order_info = array();
    	// æœ‰è®¢å•çš„åˆ†é”€å•?
    	$info_member_id = '';
    	$info = Db::name("mall_member")
    			->field("count(1) as order_num ,sum(too_spot_order.price) as order_price,too_mall_member.id,too_mall_member.name,too_mall_member.mobile,too_ticket.distribution,too_ticket.second_distribution,too_spot_order.num")
    			->join("too_spot_order","too_spot_order.promote_id = too_mall_member.id")
    			->join("too_ticket","too_ticket.id = too_spot_order.ticket_id")
    			->group("too_mall_member.id")
    			->where($where)->select();	
    	
    	foreach ($info as $vo) {
    		
    	}

    	//æŸ¥è¯¢åä¸‹çš„æ‰€æœ‰åˆ†é”€å•†æœ‰æ²¡æœ‰æˆäº¤
    	$fx_info = Db::name("mall_member as one")->field("name,mobile,id")->where("parent_id",$member_id)->select();
	  $temp = array();
	   $temp2 = array();
    	 foreach ($fx_info as $k=>$v){
		  $v=join(',',$v); //é™ç»´,ä¹Ÿå¯ä»¥ç”¨implode,å°†ä¸€ç»´æ•°ç»„è½¬æ¢ä¸ºç”¨é€—å·è¿žæŽ¥çš„å­—ç¬¦ä¸²
		  $temp[$k]=$v;
		 }
		 $temp=array_unique($temp); //åŽ»æŽ‰é‡å¤çš„å­—ç¬¦ä¸²,ä¹Ÿå°±æ˜¯é‡å¤çš„ä¸€ç»´æ•°ç»?
		 foreach ($temp as $k => $v){
		  $array=explode(',',$v); //å†å°†æ‹†å¼€çš„æ•°ç»„é‡æ–°ç»„è£?
		  //ä¸‹é¢çš„ç´¢å¼•æ ¹æ®è‡ªå·±çš„æƒ…å†µè¿›è¡Œä¿®æ”¹å³å¯
		  $temp2[$k]['order_num'] =0;
		  $temp2[$k]['order_price'] =0.00;
		  $temp2[$k]['id'] =$array[2];
		  $temp2[$k]['name'] =$array[0];
		  $temp2[$k]['mobile'] =$array[1];
		  $temp2[$k]['distribution'] =0.00;
		  $temp2[$k]['second_distribution'] =0.00;
		  $temp2[$k]['num'] =0;
		 }
		 foreach ($info as $key => $value) {
		 	foreach ($temp2 as $k => $v) {
		 		if($value['id'] == $v['id'])
		 		{
		 			unset($temp2[$k]);
		 			//unset($info[$key]);
		 		}
		 	}
		 }	

		
    	$info  = (array_merge($info,$temp2));



    	//æ²¡æœ‰è®¢å•çš„åˆ†é”€å•?
    	$this->assign('info',$info);
    	$this->assign('fx_user_number',$fx_user_number);
    	$this->assign('member_id',$member_id);

    	return $this->fetch();
    }
   //分销管理列表
    public function fenxiao_list(){
    	$fenxiao_member_id = request()->param('member_id');
    	$where['too_spot_order.status'] = 5;
    	$where['too_spot_order.promote_id'] = $fenxiao_member_id;
    	$order_list = Db::name("spot_order")
    					->field("too_spot_order.ticket_name,count(1) as order_num,sum(price) as order_price,too_ticket.distribution,too_ticket.second_distribution,too_ticket.id as ticiket_id,too_spot_order.ticket_id as order_ticket_id,too_spot_order.num")
    					->join("too_ticket","too_ticket.id = too_spot_order.ticket_id")
    					->group("too_spot_order.ticket_name")
    					->where($where)->select();

    	$name = Db::name('mall_member')->where("id",$fenxiao_member_id)->value("name");
    	

    	
    	$this->assign('order_list',$order_list);
    	$this->assign('name',$name);

    	return $this->fetch();
    }

//    //分销管理列表
//    public function member_order(){
//        $fenxiao_member_id = session('member_id');;
//        $where['too_spot_order.status'] = 5;
//        $where['too_spot_order.promote_id'] = $fenxiao_member_id;
//        $order_list = Db::name("spot_order")
//            ->field("too_spot_order.ticket_name,count(1) as order_num,sum(price) as order_price,too_ticket.distribution,too_ticket.second_distribution,too_ticket.id as ticiket_id,too_spot_order.ticket_id as order_ticket_id,too_spot_order.num")
//            ->join("too_ticket","too_ticket.id = too_spot_order.ticket_id")
//            ->group("too_spot_order.ticket_name")
//            ->where($where)->select();
//        $name = Db::name('mall_member')->where("id",$fenxiao_member_id)->value("name");
//        $this->assign('order_list',$order_list);
//        $this->assign('name',$name);
//        return $this->fetch();
//    }

    public function fenxiao_detail()
    {
        $member_id = session('member_id');
//        $ticket_name = Request::instance()->get('ticket_name');
        $sql = "SELECT `too_spot_order`.*, `too_ticket`.distribution FROM `too_spot_order` LEFT JOIN `too_ticket` ON `too_ticket`.id = `too_spot_order`.ticket_id WHERE `too_spot_order`.status = 5 AND `too_spot_order`.promote_id = {$member_id} ORDER BY `too_spot_order`.add_time DESC";
        $data = Db::query($sql);
        $this->assign('complete',$data);
        return $this->fetch();
    }


}
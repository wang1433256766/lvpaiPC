<?php
namespace app\index\controller;
use think\Db;
use think\Log;
use com\Wechat;
use com\PHPQRCode;
/**
 * 回调
 * @AuthorHTL naka1205
 * @DateTime  2016-05-29T22:04:12+0800
 */

class Back
{
	public $options;
	public $open_id;
	public $key;
	public $weixin;
	public $app_id;
	function __construct()
	{
		$this->options = \think\Config::get('weixin');
	}
	/**
	 * 回调
	 * @AuthorHTL
	 * @DateTime  2016-06-01T15:55:09+0800
	 * @return    [type]                   [description]
	 */
	public function index()
	{

	$this->weixin = new Wechat($this->options);
	$this->weixin->valid();
	$type = $this->weixin->getRev()->getRevType();
	$this->open_id = $this->weixin->getRevFrom();

	// $wechat_id = $this->weixin->getRevTo();
	// $app_id = Db::name('wechat_app')->where('wechat_id',$wechat_id)->find();

	// if (!$app_id) {
	// 	die;
	// }
	// $this->app_id = $app_id['id'];
		switch($type) {
			case  Wechat::MSGTYPE_TEXT:
				$this->msgText();
				exit;
				break;		
			case  Wechat::MSGTYPE_EVENT:
				$this->event();
				break;
			default:
				//$this->weixin->text("欢迎关注中惠旅！")->reply();
				break;
		}
	}

	public function _empty($name)
    {
        return $this->callBack($name);
    }

    protected function callBack($name)
    {
        $app = Db::name('wechat_app')->where('wechat_id',$name)->find();
        if ($app) {
        	$this->options['appid'] = $app['appid'];
			$this->options['appsecret'] = $app['appsecret'];
			$this->app_id = $app['id'];
        }
        $this->index();
    }


	public function event()
	{
		
		$event_arr = $this->weixin->getRevEvent();
		switch($event_arr['event']) {

			case Wechat::EVENT_SUBSCRIBE:
				$this->subscribe();
				break;
			case Wechat::EVENT_UNSUBSCRIBE:
				$this->unsubscribe();
				break;		
			case Wechat::EVENT_MENU_CLICK:
				$this->key = $event_arr['key'];
				$this->menuText();
				break;
			default:
				//$this->weixin->text("欢迎关注中惠旅！")->reply();
				break;
		}
	}
	public function clear()
	{
		\think\Cache::clear(); 
	}
	/**
	 * 关注
	 * @AuthorHTL
	 * @DateTime  2016-06-01T15:54:42+0800
	 * @return    [type]                   [description]
	 */
	public function subscribe()
	{

		$where_key['app_id'] = $this->app_id;
		$where_key['key'] = '关注';
		$where_key['status'] = 1;
		$text = Db::name('wechat_msg_text')->where($where_key)->value('text');
		if (!empty($text)) {
				$this->weixin->text($text)->reply();
		}

		$wxuser = $this->weixin->getUserInfo($this->open_id);
		if (empty($wxuser['nickname']) || empty($wxuser['openid'])) {
			$this->clear();
			$wxuser = $this->weixin->getUserInfo($this->open_id);
		}
		Log::write($wxuser);
		Log::write($this->app_id);

		$where['open_id'] = $this->open_id;
		//$where['unionid'] = $wxuser['unionid'];
		$where['app_id'] = $this->app_id;
		$wechat_user = Db::name('wechat_user')->where($where)->find();

		if ($wechat_user) {

			$wechat_data = array(
				'nickname'=>preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $wxuser['nickname']),				
				'province'=>preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $wxuser['province']),
				'city'=>msubstr(preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $wxuser['city']),20),
				'headimgurl'=>$wxuser['headimgurl'],
				'remark'=>$wxuser['remark'],
				'sex'=>intval($wxuser['sex']),
				'status'=>1,
				'add_time'=>$wxuser['subscribe_time']							
			);
			Db::name('wechat_user')->where('id',$wechat_user['id'])->update($wechat_data);
			$member_id = $wechat_user['member_id'];
		}else{
			$member =  Db::name('member')->where('unionid',$wxuser['unionid'])->find();
			
			if (!$member) {
				$member_data = array(
					'nickname' =>preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $wxuser['nickname']),
					'unionid' =>$wxuser['unionid'],
					// 'recode'=>get_reg_code(),
					'login_num'=>1,
					'login_time'=>time()
				);
				$member_id = Db::name('member')->insert($member_data,false,true);
			}else{
				$member_id = $member['id'];
			}

			$wechat_data = array(
				'member_id'=>$member_id,
				'unionid'=>$wxuser['unionid'],
				'open_id'=>$this->open_id,
				'nickname'=>preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $wxuser['nickname']),				
				'province'=>preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $wxuser['province']),
				'city'=>msubstr(preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $wxuser['city']),20),
				'headimgurl'=>$wxuser['headimgurl'],
				'remark'=>$wxuser['remark'],
				'sex'=>intval($wxuser['sex']),
				'app_id'=>$this->app_id,
				'status'=>1,
				'add_time'=>time()							
			);
			Db::name('wechat_user')->insert($wechat_data);
		}
		$where_recode['app_id'] = $this->app_id;
		$where_recode['member_id'] = $member_id;
		$where_recode['status'] = 0;
		$promote_id = Db::name('recode')->where($where_recode)->value('promote_id');
		if ($promote_id) {
			Db::name('member')->where('id',$promote_id)->setInc('score',10);
			Db::name('member')->where('id',$member_id)->setField('promote_id',$promote_id);
			Db::name('recode')->where($where_recode)->setField('status',1);
			$where_promote['app_id'] = $this->app_id;
			$where_promote['member_id'] = $promote_id;
			$open_id = Db::name('wechat_user')->where($where_promote)->value('open_id');
			$score = Db::name('member')->where('id',$promote_id)->value('score');
			$name = $wechat_data['nickname'];
			$data = array(
			    "touser"=>$open_id,
			    "msgtype"=>"text",
			    "text"=>array("content"=>"您的朋友【".$name."】关注了，奖励您10积分，您现有积分".$score."分")
				);
			$this->weixin->sendCustomMessage($data);
		}

	}
	/**
	 * 取关
	 * @AuthorHTL
	 * @DateTime  2016-06-01T15:54:42+0800
	 * @return    [type]                   [description]
	 */
	public function unsubscribe()
	{
		// $wxuser = $this->weixin->getUserInfo($this->open_id);
		// if (!$wxuser) {
		// 	$this->clear();
		// 	$wxuser = $this->weixin->getUserInfo($this->open_id);
		// }
		$where['open_id'] = $this->open_id;
		$where['app_id'] = $this->app_id;

		$wechat_user = Db::name('wechat_user')->where($where)->find();
		if ($wechat_user['member_id'] > 0) {
			Db::name('member')->where('id',$wechat_user['member_id'])->setField('status',0);

			$where_recode['app_id'] = $this->app_id;
			$where_recode['member_id'] = $wechat_user['member_id'];
			$promote_id = Db::name('recode')->where($where_recode)->value('promote_id');
			if ($promote_id) {
				Db::name('recode')->where($where_recode)->setField('status',0);
				Db::name('member')->where('id',$promote_id)->setDec('score',10);
				// Db::name('member')->where('id',$promote_id)->setDec('recode_num',1);
				$where_promote['app_id'] = $this->app_id;
				$where_promote['member_id'] = $promote_id;
				$open_id = Db::name('wechat_user')->where($where_promote)->value('open_id');
				$score = Db::name('member')->where('id',$promote_id)->value('score');
				$name = $wechat_user['nickname'];
				$data = array(
				    "touser"=>$open_id,
				    "msgtype"=>"text",
				    "text"=>array("content"=>"您的朋友【".$name."】取消关注了，为您减去10积分，您现有积分".$score."分")
					);
				$this->weixin->sendCustomMessage($data);
			}
			
		}

		Db::name('wechat_user')->where($where)->setField('status',0);	
	}	
	/**
	 * 菜单点击
	 * @AuthorHTL
	 * @DateTime  2016-06-03T14:15:25+0800
	 * @return    [type]                   [description]
	 */
	public function menuText()
	{
		$where['app_id'] = $this->app_id;
		$where['key'] = $this->key;
		$where['status'] = 1;
		if ($this->key == 'menu_2_2') {
			$data = array(
			    "touser"=>$this->open_id,
			    "msgtype"=>"text",
			    "text"=>array("content"=>"正在为您生成个人海报，请稍候...")
				);
			$this->weixin->sendCustomMessage($data);
			$this->poster();	
			$this->weixin->image($this->media_id)->reply();
			die;
		}else if($this->key == '积分规则') {
			$this->getpic();	
			$this->weixin->image($this->media_id)->reply();
			die;
		}
		elseif ($this->key == "个人海报") {
			$data = array(
			    "touser"=>$this->open_id,
			    "msgtype"=>"text",
			    "text"=>array("content"=>"正在为您生成个人海报，请稍候...")
				);
			$this->weixin->sendCustomMessage($data);
			$this->yyposter();	
			$this->weixin->image($this->media_id)->reply();
			die;
		}


		$text = Db::name('wechat_msg_text')->where($where)->value('text');

		$data = array();
		if ($text) {
				$this->weixin->text($text)->reply();
		}
		else{
			$where['status'] = 1;			
			$info = Db::name('wechat_msg_new')->where($where)->select();
			foreach ($info as $key => $value) {
				$data[$key]['Title'] = $value['title'];
				$data[$key]['PicUrl'] = 'http://wx.zhonghuilv.net/'.$value['picurl'];
				$data[$key]['Description'] = $value['description'];
				$data[$key]['Url'] = $value['url'];
			}
			$this->weixin->news($data)->reply();
		}
			
	}
	/**
	 * 文本回复
	 * @AuthorHTL
	 * @DateTime  2016-06-03T17:34:58+0800
	 * @return    [type]                   [description]
	 */
	public function msgText()
	{
		$key = $this->weixin->getRevContent();
	//	$key_one=substr( $key, 0, 1 );
	//	$keys = substr($key,0,11);
	//	$key_len = strlen($key);
	//	$phone = '';
	//	$name ='';
	/*	if ($key_one == 1 && is_numeric($keys) && $key_len>11)
		{
		    $phone = substr( $key, 0, 11 );
		    $str_num = strlen($phone);
		    $name = substr($key,$str_num-11);
		}
		else if(is_numeric(substr( $key,-11)) && $key_len>11)
		{
		    $phone = substr( $key,-11);
		    $str_num = strlen($phone);
		    $name = substr($key,$str_num-11);
		}
		else 
		{
		    $data = array(
		        "touser"=>$this->open_id,
		        "msgtype"=>"text",
		        "text"=>array("content"=>"对不起，您填写的信息有误!请确认后重新输入。")
		    );
		    $this->weixin->sendCustomMessage($data);
		    die;
		}*/
		
		
		
		//$phoneinfo = Db::name("cross")->where("phone",$phone)->find();
		/*if(!empty($phoneinfo))
		{
		    $data = array(
		        "touser"=>$this->open_id,
		        "msgtype"=>"text",
		        "text"=>array("content"=>"对不起，您填写的信息于".$phoneinfo['add_time']."已领取过兑奖码!您的兑奖码是：".$phoneinfo['code'].",请确认保存。")
		    );
		    $this->weixin->sendCustomMessage($data);
		    die;
		}*/
		if($key == '个人海报') {
			$data = array(
			    "touser"=>$this->open_id,
			    "msgtype"=>"text",
			    "text"=>array("content"=>"正在为您生成个人海报，请稍候...")
				);
			$this->weixin->sendCustomMessage($data);
			$this->poster();	
			$this->weixin->image($this->media_id)->reply();
			die;
		}
		/*else if($key == $key)
		{
		    $data = array(
		        "touser"=>$this->open_id,
		        "msgtype"=>"text",
		        "text"=>array("content"=>"正在为您生成兑奖码，请稍候...")
		    );
		    $this->weixin->sendCustomMessage($data);
		    $this->cross($phone,$name);
		    $this->weixin->image($this->media_id)->reply();
		    die;
		}	*/
		$text = Db::name('wechat_msg_text')->where('key',$key)->value('text');
		if ($text) {
			$this->weixin->text($text)->reply();
		}
		else{
			// $where['app_id'] = $this->app_id;
			// $where['status'] = 1;
			// $where['key'] = $key;
			// $info = Db::name('wechat_msg_new')->where($where)->select();
			// $data = array();
			// foreach ($info as $key => $value) {
			// 		$data[$key]['Title'] = $value['title'];
			// 		$data[$key]['PicUrl'] = 'http://wx.zhonghuilv.net/'.$value['picurl'];
			// 		$data[$key]['Description'] = $value['description'];
			// 		$data[$key]['Url'] = $value['url'];
			// }
			// $this->weixin->news($data)->reply();
		}			
	}
	/**
	 * 图文回复
	 * @AuthorHTL
	 * @DateTime  2016-06-13T15:44:09+0800
	 * @return    [type]                   [description]
	 */
	public function msgNews()
	{
		$content = $this->weixin->getRevContent();
		$where['app_id'] = $this->app_id;
		$where['status'] = 1;
		$where['key'] = $content;
		$info = Db::name('wechat_msg_new')->where($where)->select();
		foreach ($info as $key => $value) {
				$data[$key]['Title'] = $value['title'];
				$data[$key]['PicUrl'] = 'http://wx.zhonghuilv.net/'.$value['picurl'];
				$data[$key]['Description'] = $value['description'];
				$data[$key]['Url'] = $value['url'];
		}
		$this->weixin->news($data)->reply();
	}

	public function getpic()
	{
		$pic = './upload/temp/jifenguize.jpg';
		$data = array("media" => @$pic);
		$info = $this->weixin->uploadMedia($data,'image');
		if (!isset($info['media_id'])) {
			$this->clear();
			$info = $this->weixin->uploadMedia($data,'image');
		}
		$this->media_id = $info['media_id'];
	}

	/**
	 * 生成海报
	 * @AuthorHTL
	 * @DateTime  2016-07-01T17:09:41+0800
	 * @return    [type]                   [description]
	 */
	public function poster()
	{
		set_time_limit(0);
		\think\Loader::import('PHPQRCode', EXTEND_PATH);

		$where['open_id'] = $this->open_id;
		$where['app_id'] = 1;	

		$wechat_user  = Db::name('wechat_user')->where($where)->find();
		//$unionid = Db::name('member')->where('id',$wechat_user['member_id'])->value('unionid');
		$member = Db::name('member')->where('id',$wechat_user['member_id'])->find();

		$ac = authcode($wechat_user['member_id'].'|'. $member['unionid'] .'|'.$this->app_id);

		$url = "http://wx.zhonghuilv.net/weixin/promote/index/ac/$ac";

		$path_nama = date('his',time()).getRandCode(6);
		$QR = PHPQRCode::get($url,$path_nama);
		Log::write($QR);
		$QR = "./upload/qrcode/" . date('Ymd') . "/" . "$path_nama.png";//已经生成的原始二维码图  
		
		$pic = './upload/temp/poster8.jpg';	//海报图片
		
	    $QR = imagecreatefromstring(file_get_contents($QR));   
	    $pic = imagecreatefromstring(file_get_contents($pic));   

	    $QR_width = imagesx($QR);//二维码图片宽度   
	    $QR_height = imagesy($QR);//二维码图片高度   
	    $pic_width = imagesx($pic);//海报图片宽度   
	    $pic_height = imagesy($pic);//海报图片高度   

	    //重新组合图片并调整大小   
	    //粘贴二维码
	    imagecopyresampled($pic,$QR, 55, 330, 0, 0,160,160,$QR_width,$QR_height);

			//粘贴头像
			// $logo = imagecreatefromstring(file_get_contents($logo)); 

			// $logo_width = imagesx($logo);//头像图片宽度   
		 //    $logo_height = imagesy($logo);//头像图片高度 
		 //    imagecopyresampled($pic,$logo, 20, 680, 0, 0,60,60,$logo_width,$logo_height);
		 //    //写入文本字体
		 //    $ttfPath = './upload/temp/1.ttf';
			// $text = '一起加入我们的快乐之家！';
			// $color = imagecolorallocate($pic,0,0,0);

			// $len = mb_strlen($text, 'utf-8');
			// for ($i = 0; $i < $len; $i++) {
	  //           $code[$i] = iconv_substr($text, $i, 1, 'utf-8');
	  //           imagettftext($pic, 16, 0, 140 + 16 * ($i + 1) * 1.5, 720, $color, $ttfPath, $code[$i]);
	  //       }
	    $ttfPath = './upload/temp/1.ttf';
        // $remark = empty($wechat_user['remark']) ? $wechat_user['nickname'] :$wechat_user['remark'];
        $remark = $member['name'];
		$len = mb_strlen($remark, 'utf-8');
		//$color = imagecolorallocate($pic,0,0,0);
		$color = imagecolorallocate($pic,0,0,0);
		for ($i = 0; $i < $len; $i++) {
            $code[$i] = iconv_substr($remark, $i, 1, 'utf-8');
            imagettftext($pic, 16, 0, 90 + 16 * ($i + 1) * 1.5, 310, $color, $ttfPath, $code[$i]);
        }

		//输出图片   
		$poster_path = "./upload/poster/" . date('Y-m-d') . '/';
		if (! is_dir($poster_path)) {
			mkdir($poster_path);
		}   
		$poster_file = $poster_path . date('Ymdhis') . getRandCode(6) . ".jpg" ;
		imagejpeg($pic,$poster_file);
		
		$data = array("media" => @$poster_file);
		$info = $this->weixin->uploadMedia($data,'image');
		if (!isset($info['media_id'])) {
			$this->clear();
			$info = $this->weixin->uploadMedia($data,'image');
		}
		$this->media_id = $info['media_id'];
	}
	
	/**
	 * 景区运营生成海报
	 * @AuthorHTL
	 * @DateTime  2016-07-01T17:09:41+0800
	 * @return    [type]                   [description]
	 */
	public function yyposter()
	{
	    set_time_limit(0);
	    \think\Loader::import('PHPQRCode', EXTEND_PATH);
	
	    $where['open_id'] = $this->open_id;
	    $where['app_id'] = 1;
	
	    $wechat_user  = Db::name('wechat_user')->where($where)->find();
	    //$unionid = Db::name('member')->where('id',$wechat_user['member_id'])->value('unionid');
	    $member = Db::name('member')->where('id',$wechat_user['member_id'])->find();
	
	    $ac = authcode($wechat_user['member_id'].'|'. $member['unionid'] .'|'.$this->app_id);
	
	    $url = "http://wx.zhonghuilv.net/weixin/promote/index/ac/$ac";
	
	    $path_nama = date('his',time()).getRandCode(6);
	    $QR = PHPQRCode::get($url,$path_nama);
	    Log::write($QR);
	    $QR = "./upload/qrcode/" . date('Ymd') . "/" . "$path_nama.png";//已经生成的原始二维码图
	
	    $pic = './upload/temp/posteryy.jpg';	//海报图片
	
	    $QR = imagecreatefromstring(file_get_contents($QR));
	    $pic = imagecreatefromstring(file_get_contents($pic));
	
	    $QR_width = imagesx($QR);//二维码图片宽度
	    $QR_height = imagesy($QR);//二维码图片高度
	    $pic_width = imagesx($pic);//海报图片宽度
	    $pic_height = imagesy($pic);//海报图片高度
	
	    //重新组合图片并调整大小
	    //粘贴二维码
	    imagecopyresampled($pic,$QR, 55, 330, 0, 0,160,160,$QR_width,$QR_height);
	
	    //粘贴头像
	    // $logo = imagecreatefromstring(file_get_contents($logo));
	
	    // $logo_width = imagesx($logo);//头像图片宽度
	    //    $logo_height = imagesy($logo);//头像图片高度
	    //    imagecopyresampled($pic,$logo, 20, 680, 0, 0,60,60,$logo_width,$logo_height);
	    //    //写入文本字体
	    //    $ttfPath = './upload/temp/1.ttf';
	    // $text = '一起加入我们的快乐之家！';
	    // $color = imagecolorallocate($pic,0,0,0);
	
	    // $len = mb_strlen($text, 'utf-8');
	    // for ($i = 0; $i < $len; $i++) {
	    //           $code[$i] = iconv_substr($text, $i, 1, 'utf-8');
	    //           imagettftext($pic, 16, 0, 140 + 16 * ($i + 1) * 1.5, 720, $color, $ttfPath, $code[$i]);
	    //       }
	    $ttfPath = './upload/temp/1.ttf';
	    // $remark = empty($wechat_user['remark']) ? $wechat_user['nickname'] :$wechat_user['remark'];
	    $remark = $member['name'];
	    $len = mb_strlen($remark, 'utf-8');
	    //$color = imagecolorallocate($pic,0,0,0);
	    $color = imagecolorallocate($pic,0,0,0);
	    for ($i = 0; $i < $len; $i++) {
	        $code[$i] = iconv_substr($remark, $i, 1, 'utf-8');
	        imagettftext($pic, 16, 0, 90 + 16 * ($i + 1) * 1.5, 310, $color, $ttfPath, $code[$i]);
	    }
	
	    //输出图片
	    $poster_path = "./upload/poster/" . date('Y-m-d') . '/';
	    if (! is_dir($poster_path)) {
	        mkdir($poster_path);
	    }
	    $poster_file = $poster_path . date('Ymdhis') . getRandCode(6) . ".jpg" ;
	    imagejpeg($pic,$poster_file);
	
	    $data = array("media" => @$poster_file);
	    $info = $this->weixin->uploadMedia($data,'image');
	    if (!isset($info['media_id'])) {
	        $this->clear();
	        $info = $this->weixin->uploadMedia($data,'image');
	    }
	    $this->media_id = $info['media_id'];
	}
	/**
	 * 一颗旅行菌奇幻漂流参与方式
	 * @AuthorHTL  Bela
	 * @DateTime  2017-06-08 T16:21:58+0800
	 * @return
	 */
 	public function cross_code()
	{
	    $cross_pic = './upload/temp/lxjcode.jpg';
	    $data = array("media" => @$cross_pic);

	    $info = $this->weixin->uploadMedia($data,'image');
	    if (!isset($info['media_id'])) {
	    
	        $info = $this->weixin->uploadMedia($data,'image');
	    }
	    $this->media_id = $info['media_id'];
	}

	/**
	 * 玻璃桥景区石牛寨奇幻漂流参与方式
	 * @AuthorHTL  Bela
	 * @DateTime  2017-06-08 T16:21:58+0800
	 * @return
	 */
 	public function snz_code()
	{
	    $cross_pic = './upload/temp/snzcode.jpg';
	    $data = array("media" => @$cross_pic);

	    $info = $this->weixin->uploadMedia($data,'image');
	    if (!isset($info['media_id'])) {
	    
	        $info = $this->weixin->uploadMedia($data,'image');
	    }
	    $this->media_id = $info['media_id'];
	}


	/**
	 * 兑奖码海报
	 * @AuthorHTL  Bela
	 * @DateTime  2017-06-08 T18:21:58+0800
	 * @return
	 */
	public function cross($phone,$name)
	{
	    set_time_limit(0);
	    \think\Loader::import('PHPQRCode', EXTEND_PATH);
	    //获取当前用户open_id
	    $cross_pic = './upload/temp/emptycode.jpg';	//海报图片
	
	    $pic = imagecreatefromstring(file_get_contents($cross_pic));
	
	
	    //重新组合图片并调整大小
	    //粘贴二维码
	    $ttfPath = './upload/temp/1.ttf';
	    $code =rand(10000000,99999999);
	
	
	   
	    $len = mb_strlen($code, 'utf-8');
	    //$color = imagecolorallocate($pic,0,0,0);
	    $color = imagecolorallocate($pic,0,0,0);
	    
	    $len = mb_strlen($phone, 'utf-8');
	    //$color = imagecolorallocate($pic,0,0,0);
	    $color = imagecolorallocate($pic,0,0,0);
	    //for ($i = 0; $i < $len; $i++) {
	        imagettftext($pic, 40, 0, 570, 2252, $color, $ttfPath,$phone );
	    //}
	    
	    $len = mb_strlen($code, 'utf-8');
	    //$color = imagecolorallocate($pic,0,0,0);
	    $color = imagecolorallocate($pic,0,0,0);
	    //for ($i = 0; $i < $len; $i++) {
	        imagettftext($pic, 40, 0, 600, 2355, $color, $ttfPath, $code);
	    //}
	    
	
	    //输出图片
	    $poster_path = "./upload/cross/" . date('Y-m-d') . '/';
	    
	    if (! is_dir($poster_path)) {
	        mkdir($poster_path);
	    }
	    $poster_file = $poster_path . date('Ymdhis') . getRandCode(6) . ".jpg" ;
	    imagejpeg($pic,$poster_file);
	   
	    if(!empty($phone) && !empty($code))
	    {
	        $data = array(
	            'username' => $name,
	            'code' => $code,
	            'phone'=> $phone,
	            'app_id' => 1,
	            'thumb'=> $poster_file
	        );
	        Db::name("cross")->insert($data);
	    }
	
	    $data = array("media" => $poster_file);
	    $info = $this->weixin->uploadMedia($data,'image');
	    if(!$info)
	    {
	        if (!isset($info['media_id'])) {
	            $this->clear();
	            $info = $this->weixin->uploadMedia($data,'image');
	        }
	    }
	    $this->media_id = $info['media_id'];
	}

}

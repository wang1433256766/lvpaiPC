<?php
namespace app\admin\controller;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use think\Db;
use think\Log;
use com\Wechat;
use com\PHPQRCode;
use think\Controller;
use app\admin\model\FansModel;
use app\admin\model\MemberModel;
use app\admin\model\AppModel;
use app\admin\model\MtextModel;
use app\admin\model\TextModel;
use app\admin\model\MenuModel;
use app\admin\model\RecodeModel;
use think\Request;
use think\Model;
use com\wechat\Dwechat;
use org\upload\driver\Local;
class Back
{
	public $options;
	public $open_id;
	public $key;
	public $weixin;
	public $app_id;
	function __construct()
	{
		$this->options = \think\Config::get('wechat');
	}
	/**
	 * 回调
	 * @AuthorHTL
	 * @DateTime  2017-03-01T15:55:09+0800
	 * @return   
	 */
	public function index()
	{
		$this->weixin = new Wechat($this->options);
	   	$this->weixin->valid();
	    $type = $this->weixin->getRev()->getRevType();
	   	$this->open_id = $this->weixin->getRevFrom();
	    	$log = $this->weixin->getRev();
	    	log::write($log);
	    	//信息回复
		switch($type) {
			case  Wechat::MSGTYPE_TEXT:
				$this->msgText();
				exit;
				break;
 			case  Wechat::MSGTYPE_EVENT:
 			    $this->event();
			    break;
			default:
				
				break;	    
	   }
	}
	
 	public function event()
 	{
 	    $event_arr = $this->weixin->getRevEvent();
 	    switch ($event_arr['event'])
 	    {
 	        case Wechat::EVENT_SUBSCRIBE:
	            $this->subscribe();
 	            break;
 	        case Wechat::EVENT_UNSUBSCRIBE:
 	            $this->unsubscribe();
	            break;
	        case Wechat::EVENT_MENU_CLICK:
 	            $this->key=$event_arr['key'];
	            $this->menuClick();
	            break;
	        default:
 	            break;
 	    }
 	}
	public function _empty($name)
	{
	    return $this->callBack($name);
	}
	
	protected function callBack($name)
	{
	    $app = new AppModel();
	    $appinfo = $app->getOneAppName($name);
	    if ($appinfo) {
	        $this->options['appid'] = $appinfo['appid'];
	        $this->options['appsecret'] = $appinfo['secret'];
	        $this->app_id = $appinfo['appid'];
	    }
	    $this->index();
	}
	public function clear()
	{
	    \think\Cache::clear();
	}
	/**
	 * 关注
	 * @AuthorHTL	Bela
	 * @DateTime  2017-03-16
	 * @return    [type]                   [description]
	 */
	public function subscribe()
	{
	    //获取点击关注对应的回复内容
	    $where_key['appid'] = $this->app_id;
	    $where_key['key'] ='关注';
	    $where_key['status'] = '1';
	    $text = new TextModel();
	    $textinfo = $text->getOneTextValue($where_key);
	    //如果回复内容不为空  则向微信端发送对应信息
	    if(!empty($textinfo))
	    {
	        $this->weixin->text($textinfo)->reply();
	    }
	    //根据当前open_id向微信端获取用户信息
	    $wxuser = $this->weixin->getUserInfo($this->open_id);
	    //若openid或用名昵称为空 则清除数据  重新向微信端获取数据
	    if(empty($wxuser['nickname'])||empty($wxuser['openid']))
	    {
	        $this->clear();
	        $wxuser = $this->weixin->getUserInfo($this->open_id);
	    }
	    //根据open_id和app_id获取当前用户信息
	    $where['open_id'] = $this->open_id;
	    $where['app_id'] = $this->app_id;
	    $fans = new FansModel();
	    $fansinfo = $fans->getOneFans($where);
	    
	    //若查询信息为真则向粉丝表插入当前用户信息
	    if($fansinfo)
	    {
	        $wechat_data = array(
	            'member_id' => $fansinfo['member_id'],
	            'nickname'=>preg_replace('/[\x{10000}-\x{10FFFF}]/u','',$wxuser['nickname']),
	            'province'=>preg_replace('/[\x{10000}-\x{10FFFF}]/u','',$wxuser['province']),
	            'city'=>mb_substr(preg_replace('/[\x{10000}-\x{10FFFF}]/u','',$wxuser['city']),20),
	            'headimgurl'=>$wxuser['headimgurl'],
	            'remark'=>$wxuser['remark'],
	            'sex'=>intval($wxuser['sex']),
	            'status'=>1,
	            'add_time'=>$wxuser['subscribe_time']
	        );
	        $fans->editFans($wechat_data);
	        //取上面查到的用户member_id给$member_id
	        $member_id = $fansinfo['member_id'];
	    }
	    else
	    {
	        //根据从微信服务器获取到的unionid查询一条用户信息  然后将数据插入到数据库中
	        $unionid = $wxuser['unionid'];
	        $mb = new MemberModel();
	        $member = $mb->getOneMemberInfo($unionid);
	        if($member)
	        {
	            $member_data = array(
	                'nickname' => preg_replace('/[\x{10000}-\x{10FFFF}]/u','',$wxuser['nickname']),
	                'unionid' => $wxuser['unionid'],
	                'login_num'=>1,
	                'login_time'=>time()
	            );
	            $member_id = $mb->insertMember($member_data,false,true);
	            
	        }
	        else 
	        {
	            $member_id = $fansinfo['member_id'];
	            
	        }
	        $wechat_data = array(
	            'member_id'=>$member_id,
	            'unionid'=> $wxuser['unionid'],
	            'open_id'=>$this->open_id,
	            'nickname'=>preg_replace('/[\x{10000}-\x{10FFFF}]/u','',$wxuser['nickname']),
	            'province'=>preg_replace('/[\x{10000}-\x{10FFFF}]/u','',$wxuser['province']),
	            'city'=>mb_substr(preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $wxuser['city']),20),
	            'headimgurl'=>$wxuser['headimgurl'],
	            'remark'=>$wxuser['remark'],
	            'sex'=>intval($wxuser['sex']),
	            'app_id'=>$this->app_id,
	            'status'=>1,
	            'add_time'=>time()
	            
	        );
	        $fans->insertFans($wechat_data);
	    }
	    $where_recode['app_id']=$this->app_id;
	    $where_recode['member_id']=$member_id;
	    $where_recode['status']=0;
	    $rec = new RecodeModel();
	    $market_id = $rec->getMarketId($where_recode);
	    if($market_id)
	    {
	        $mb->setIncScore($market_id);
	        $mb->setFieldMarketId($member_id,$market_id);
	        $rec->RecsetStatus($where_recode);
	        $where_market['app_id']=$this->app_id;
	        $where_market['member_id']=$market_id;
	        $open_id = $fans->getFansOpenId($where_market);
	        $score=$mb->getMbScore($market_id);
	        $name = $wechat_data['nickname'];
	        $data = array(
	            "touser" =>$open_id,
	            "msgtype"=>"text",
	           "text"=>array("content"=>"您的朋友【".$name."】关注了,奖励您10积分，您现有积分".$score."分")
	        );
	        $this->weixin->sendCustomMessage($data);
	        
	    }
	}
	/**
	 * 取关 Bela
	 * @AuthorHTL
	 * @DateTime  2017-03-13T15:54:42+0800
	 * @return    
	 */
	public function unsubscribe()
	{
	    $where['open_id'] = $this->open_id;
	    $where['app_id'] = $this->app_id;
	    $fans = new FansModel();
	    $fans_info = $fans->getOneFans($where);
	    $mem_id = $fans_info['member_id'];
	    if($mem_id > 0 )
	    {
	        $mb = new MemberModel();
	        $member = $mb->MbsetStatus($mem_id);
	        
	        $where_recode['app_id'] = $this->app_id;
	        $where_recode['member_id'] = $mem_id;
	        $rec = new RecodeModel();
	        $market_id = $rec->getMarketId($where_recode);
	        if($market_id)
	        {
	            $rec->RecsetFields($where_recode);
	            $mb->MbsetDec($market_id);
	            $where_market['app_id'] = $this->app_id;
	            $where_market['member_id'] = $market_id;
	            $open_id = $fans->getFansOpenId($where_market);
	            $score = $mb->getMbScore($market_id);
	            $name = $fans_info['nickname'];
	            $data = array(
	                "touser"=>$open_id,
	                "msgtype"=>"text",
	                "text"=>array("content"=>"您的朋友【".$name."】取消关注了，为您减去10积分，您现有积分".$score."分")
	            );
	            $this->weixin->sendCustomMessage($data);
	        }
	        
	    }
	    $fans->FansetStatus($where);
	}

	/**
	 * 菜单点击
	 * @AuthorHTL
	 * @DateTime  2017-03-14T14:15:25+0800
	 * @return
	 */
	public function menuClick()
	{
	    $where_key['appid'] = $this->app_id;
	    $where_key['key'] = $this->key;
	    $where_key['status'] = 1;

	    if($this->key == "个人海报")
	    {
	        $data = array(
	            "touser" =>$this->open_id,
	            "msgtype"=>"text",
	            "text"=>array("content"=>"正在为您生成个人海报,请稍候...")
	        );
	        $this->weixin->sendCustomMessage($data);
	        $this->poster();
	        $this->weixin->image($this->media_id)->reply();
	        die;
	    }
	    else if($this->key=="积分规则")
	    {
	        $this->getpic();
	        $this->weixin->image($this->media_id)->reply();
	        die;
	    }
	    
	    else if($key == "报名参与")
	    {
	        $data = array(
	            "touser"=>$this->open_id,
	            "msgtype"=>"text",
	            "text"=>array("content"=>"正在为您推送参与方式，请稍候...")
	        );
	        $this->weixin->sendCustomMessage($data);
	        $this->poster_code();
	        $this->weixin->image($this->media_id)->reply();
	        die;
	    }
	    $text = new TextModel();
	    $textinfo = $text ->getOneTextValue($where_key);
	    if($textinfo)
	    {
	        $this->weixin->text($textinfo)->reply();
	    }
	    else
	    {
	        $where['status'] = 1;
	        $mtext = new MtextModel();
	        $mt_info = $mtext->getMtextwhere($where);
	        foreach ($mt_info as $key=>$value)
	        {
	            $data[$key]['Title'] = $value['title'];
	            $data[$key]['PicUrl']='http://wechats.zhonghuilv.net/'.$value['img'];
	            $data[$key]['Description']=$value['descs'];
	            $data[$key]['Url']=$value['url'];
	        }
	        $this->weixin->news($data)->reply();
	    }
	}
	/**
	 * 文本回复
	 * @AuthorHTL	xiang
	 * @DateTime  2017-03-08
	 * @return    [type]                   [description]
	 */
	public function msgText()
	{
		$mtext = new MtextModel;
		$wtext = new TextModel;
		
		$key = $this->weixin->getRevContent();	
		if($key == "报名参与")
		{
		    $data = array(
		        "touser"=>$this->open_id,
		        "msgtype"=>"text",
		        "text"=>array("content"=>"正在为您推送参与方式，请稍候...")
		    );
		    $this->weixin->sendCustomMessage($data);
		    $this->poster_code();
		    $this->weixin->image($this->media_id)->reply();
		    die;
		}
		
		$key_one=substr( $key, 0, 1 );
		$keys = substr($key,0,11);
		$phone = '';
		$name ='';
		if ($key_one == 1 && is_numeric($keys))
		{
		    $phone = substr( $key, 0, 11 );
		    $str_num = strlen($phone);
		    $name = substr($key,$str_num-11);
		    log::write("bebebeb");
		    log::write($name);
		}
		else if(is_numeric(substr( $key,-11)))
		{
		    $phone = substr( $key,-11);
		    $str_num = strlen($phone);
		    $name = substr($key,$str_num-11);
		    //$name = mb_substr( $key,-12,0);
		    log::write("bebebeb");
		    log::write($name);
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
		}
		
		
		
		$phoneinfo = Db::name("cross")->where("phone",$phone)->find();
		if(!empty($phoneinfo))
		{
		    $data = array(
		        "touser"=>$this->open_id,
		        "msgtype"=>"text",
		        "text"=>array("content"=>"对不起，您填写的信息于".$phoneinfo['add_time']."已领取过兑奖码!您的兑奖码是：".$phoneinfo['code'].",请确认保存。")
		    );
		    $this->weixin->sendCustomMessage($data);
		    die;
		}
		
		
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
		else if($key == $key)
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
		}
		    
		$where['status'] = 1;		
		$where['key'] = $key;		
		$textarr = $wtext->getAllTextwhere($where);
		$text = $textarr['content'];
		if ($text) {
			$this->weixin->text($text)->reply();
		}
		else{
			$where['app_id'] = $this->app_id;
			$where['status'] = 1;
			$where['key'] = $key;
			$info = $mtext->getMtextwhere($where);
			if($info){
			$data = array();
			foreach ($info as $key => $value) {
					$data[$key]['Title'] = $value['title'];
					$data[$key]['PicUrl'] = 'http://wechats.zhonghuilv.net/'.$value['img'];
					$data[$key]['Description'] = $value['descs'];
					$data[$key]['Url'] = $value['url'];
			}
			$this->weixin->news($data)->reply();
		}else{
			$this->weixin->text('信息不存在,请确认后重新输入！！！')->reply();
		}
		}			
	}
	
	public function getpic()
	{
	    $pic = './uploads/temp/jifenguize.jpg';
	    $data = array("media" => @$pic);
	    $info = $this->weixin->uploadMedia($data,'image');
	    if (!isset($info['media_id'])) {
	       
	        $info = $this->weixin->uploadMedia($data,'image');
	    }
	    $this->media_id = $info['media_id'];
	}
	
	
	 /**
	 * 生成海报
	 * @AuthorHTL  Bela
	 * @DateTime  2017-03-09T10:21:58+0800
	 * @return
	 */
    	public function poster()
	{
		set_time_limit(0);
		\think\Loader::import('PHPQRCode', EXTEND_PATH);
		//获取当前用户open_id
		$Oid= $this->open_id;
	
		//依据open_id查出当前用户所有信息
		$fans = new FansModel();
		$wechat_user  = $fans->getOneFansBy($Oid);
		$member_id = $wechat_user['member_id'];
		$unionid = $wechat_user['unionid'];


		//对取出的数据进行加密
		$ac = authcode($member_id.'|'.$unionid);

		$url = "http://wechats.zhonghuilv.net/index/market/index/ac/$ac";
		$path_nama = date('his',time()).getRandCode(6);
		$QR = PHPQRCode::get($url,$path_nama);
		
		$QR = "./uploads/qrcode/" . date('Ymd') . "/" . "$path_nama.png";//已经生成的原始二维码图  
	
		$pic = './uploads/temp/poster8.jpg';	//海报图片
		
		$QR = imagecreatefromstring(file_get_contents($QR));   
		$pic = imagecreatefromstring(file_get_contents($pic));   

		$QR_width = imagesx($QR);//二维码图片宽度   
		$QR_height = imagesy($QR);//二维码图片高度   
		$pic_width = imagesx($pic);//海报图片宽度   
		$pic_height = imagesy($pic);//海报图片高度   

		 //重新组合图片并调整大小   
		 //粘贴二维码
		 imagecopyresampled($pic,$QR, 55, 330, 0, 0,160,160,$QR_width,$QR_height);

	   	 $ttfPath = './uploads/temp/1.ttf';
        $remark = empty($wechat_user['remark']) ? $wechat_user['nickname'] :$wechat_user['remark'];
		$len = mb_strlen($remark, 'utf-8');
		//$color = imagecolorallocate($pic,0,0,0);
		$color = imagecolorallocate($pic,0,0,0);
	   for ($i = 0; $i < $len; $i++) {
            $code[$i] = iconv_substr($remark, $i, 1, 'utf-8');
            imagettftext($pic, 16, 0, 90 + 16 * ($i + 1) * 1.5, 310, $color, $ttfPath, $code[$i]);
        }

		//输出图片   
		$poster_path = "./uploads/poster/" . date('Y-m-d') . '/';
		if (! is_dir($poster_path)) {
			mkdir($poster_path);
		}   
		$poster_file = $poster_path . date('Ymdhis') . getRandCode(6) . ".jpg" ;
		imagejpeg($pic,$poster_file);
		
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
	 /**
	 * 创建菜单
	 * @AuthorHTL  xiang
	 * @DateTime  2017-03-13
	 * @return
	 */	
	public function createMenus($id)
	{
		$where['status'] = 1;
		$where['app_id'] = $id;
		$model = new MenuModel;
		//查询当前APP下的所有数据
		$menu = $model->getMenuBy($where);

		$datas=[];
		//拼接数据
		$i = 0;
		foreach ($menu as $key => $value) {
			if($value['parent_id'] == 0)
			{
			    $datas[$i]['type'] = $value['type'];
				$datas[$i]['name'] = $value['name'];
				$datas[$i]['key'] = $value['key'];
				$where['parent_id'] = $value['id'];
				$menus = $model->getMenuBy($where);
				foreach ($menus as $k => $v) {
					$arr[$k]['type'] = $v['type'];
					$arr[$k]['name'] = $v['name'];
					//var_dump($k);
					if($v['type'] == 'view'){
						$arr[$k]['url'] = $v['url'];
					}else if($v['type'] == 'click'){
						$arr[$k]['key'] = $v['key'];
					}
					$datas[$i]['sub_button'] = $arr;
					
				}
				$i++;
			}
			
			
		}
	
		$data['button'] = $datas;
		
		//调用微信接口
		$creatMn = new Wechat($this->options);
		$result = $creatMn->createMenu($data);
		if($result)
		{
		    $this->sucess("./appMenu","创建成功!正在跳转，请稍候...");
		}
		else 
		{
		    $this->error("./appMenu","创建失败!请检查错误...");
		}


		
	}
	 /**
	 * 撤销菜单
	 * @AuthorHTL  xiang
	 * @DateTime  2017-03-13
	 * @return
	 */
	public function delMenu()
	{
		$app_id = Request::instance()->param('id');
		$weixin = new Wechat($this->options);
		$result = $weixin->deleteMenu();
		dump($result);die;
		$where['app_id'] = $app_id;
		if($result){
			$model = new MenuModel;
			$info = $model->updateAllMenu($where);
			if($info){
				return $this->success('撤销成功');
                die;
            }
		}else{
                return $this->error('撤销失败');
                die;
			}
	}	
	
	/**
	 * 兑奖码海报
	 * @AuthorHTL  Bela
	 * @DateTime  2017-05-11T10:21:58+0800
	 * @return
	 */
	public function cross($phone,$name)
	{
	    set_time_limit(0);
	    \think\Loader::import('PHPQRCode', EXTEND_PATH);
	    //获取当前用户open_id
	    $cross_pic = './uploads/temp/emptycross.jpg';	//海报图片
	
	    $pic = imagecreatefromstring(file_get_contents($cross_pic));
	
	
	    //重新组合图片并调整大小
	    //粘贴二维码
	    $ttfPath = './uploads/temp/1.ttf';
	    
	
	
	   
	    $len = mb_strlen($code, 'utf-8');
	    //$color = imagecolorallocate($pic,0,0,0);
	    $color = imagecolorallocate($pic,0,0,0);
	    
	    $len = mb_strlen($phone, 'utf-8');
	    //$color = imagecolorallocate($pic,0,0,0);
	    $color = imagecolorallocate($pic,0,0,0);
	    //for ($i = 0; $i < $len; $i++) {
	        imagettftext($pic, 24, 0, 360, 1005, $color, $ttfPath,$phone );
	    //}
	    
	    $len = mb_strlen($code, 'utf-8');
	    //$color = imagecolorallocate($pic,0,0,0);
	    $color = imagecolorallocate($pic,0,0,0);
	    //for ($i = 0; $i < $len; $i++) {
	        imagettftext($pic, 24, 0, 380, 1065, $color, $ttfPath, $code);
	    //}
	    
	
	    //输出图片
	    $poster_path = "./uploads/cross/" . date('Y-m-d') . '/';
	    
	    if (! is_dir($poster_path)) {
	        mkdir($poster_path);
	    }
	    $poster_file = $poster_path . date('Ymdhis') . getRandCode(6) . ".jpg" ;
	    imagejpeg($pic,$poster_file);
	    $code =rand(10000000,99999999);
	    if(!empty($phone) && !empty($code))
	    {
	        $data = array(
	            'name' => $name,
	            'code' => $code,
	            'phone'=> $phone,
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
	/**
	 * 丛林穿越参与方式
	 * @AuthorHTL  Bela
	 * @DateTime  2017-05-11T10:21:58+0800
	 * @return
	 */
	public function poster_code()
	{
	    set_time_limit(0);
	    \think\Loader::import('PHPQRCode', EXTEND_PATH);
	    //获取当前用户open_id
	    $cross_pic = './uploads/temp/yikelvxingjun.jpg';	//海报图片
	
	    $pic = imagecreatefromstring(file_get_contents($cross_pic));
	
	
	    
	     
	
	    //输出图片
	    $poster_path = "./uploads/cross/" . date('Y-m-d') . '/';
	    if (! is_dir($poster_path)) {
	        mkdir($poster_path);
	    }
	    $poster_file = $poster_path . date('Ymdhis') . getRandCode(6) . ".jpg" ;
	    imagejpeg($pic,$poster_file);
	
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
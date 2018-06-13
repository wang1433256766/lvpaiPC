<?php
namespace app\mobile\controller;
use think\Db;
use think\Controller;
use think\Request;
use think\Log;
use think\Session;
use think\Config;
use com\PHPQRCode;

class Mall extends Base
{
	public function index()
	{
        $ac = Request::instance()->param('ac');
        if ($ac) {
            $promote_id = authcode($ac,'DECODE');
            $type1 = Db::name('mall_member')->where('id',$promote_id)->value('type');
            if ($type1 > 0) {
                session('promote_id',$promote_id);
            }
        }
        $id = session('member_id');
        $type = Db::name('mall_member')->where('id',$id)->value('type');
        $this->assign('type',$type);
	    $data = Db::name('ticket')->where('status',1)->order("sale_num desc")->select();
	    $this->assign('data',$data);
	    return $this->fetch();
	}

    public function goodsdetail() {
        $id = Request::instance()->param('id');
        session('spot_id',$id);
        $ac = Request::instance()->param('ac');
        if ($ac) {
            $promote_id = authcode($ac,'DECODE');
            $type = Db::name('mall_member')->where('id',$promote_id)->value('type');
            if ($type > 0) {
                session('promote_id',$promote_id);
            }
        }
        $ids = session('member_id');
        $ty = Db::name('mall_member')->where('id',$ids)->value('type');
        if ($ty == 0) {
            $member_id = isset($promote_id)?$promote_id:0;
        }else {
            $member_id  = session('member_id');
        }
        $data = Db::name('ticket')->where('id',$id)->find();
        $comment = Db::name('shop_spot_comment')->where('ticket_id',$id)->order('id desc')->find();
        $num = Db::name('shop_spot_comment')->where('ticket_id',$id)->count('id');
        $img = Db::name('mall_member')->where('id',$comment['member_id'])->value('headimg');
        $type = session('type');
        $this->assign('member_id',$member_id);
        $this->assign('ty',$ty);
        $this->assign('type',$type);
        $this->assign('img',$img);
        $this->assign('num',$num);
        $this->assign('comment',$comment);
        $this->assign('data',$data);
	    return $this->fetch();
    }

    public function evaluation() {
        $id = Request::instance()->param('id');
        if ($id) {
            $comment = Db::name('shop_spot_comment')->alias('s')->field('s.*,m.nickname,m.headimg')->join('mall_member m','s.member_id=m.id')->where('s.ticket_id',$id)->order('id desc')->select();
            $num = Db::name('shop_spot_comment')->where('ticket_id',$id)->count('id');
            $this->assign('id',$id);
            $this->assign('num',$num);
            $this->assign('comment',$comment);
            return $this->fetch();
        }

    }

    public function order() {
        if (Request::instance()->isPost()) {
            $member_id = session('member_id');

            $info = Request::instance()->param();
            //同张身份证30天内最多买两张票
            $veri = $this->verifyCard($info['ids'],$info['id']);
            if ($veri == 1) {
                $res['status'] = FALSE;
                $res['info'] = '同张身份证30天内只能买两次相同的票';
                echo json_encode($res);exit;
            }

            $data['source'] = 'mobile';
            $data['order_sn'] = 'LP'.date('ymdhis', time()) . get_rand_num(4);
            $ticket = Db::name('ticket')->where('id',$info['id'])->find();
            $member = Db::name('mall_member')->field('score,mobile,travel_agency')->where('id',$member_id)->find();            //可用旅行币
            $score = $member['score'];
            $data['ticket_id'] = $info['id'];
            $data['spot_id'] = $ticket['spot_id'];
            $data['t_id'] = $ticket['t_id'];
            $data['ticket_name'] = $ticket['ticket_name'];
            $data['cost_price'] = $ticket['market_price'];
            $data['price'] = $ticket['shop_price'];
            $data['num'] = $info['num'];
            $data['travel_date'] = $info['use_date'];
            $data['traveler_ids'] = $info['ids'];
            $data['order_total'] = $data['price']*$data['num'];
            $data['total'] = $data['order_total'];
            $data['member_id'] = $member_id;
            $data['rebate_total'] = 0;
            $data['mobile'] = $member['mobile'];
            if($ticket['is_team'] == 1)
            {
                $data['is_team'] = 1;
                $data['travel_agency'] = $member['travel_agency'];
            }
            else
            {
                $data['is_team'] = 0;
            }
            if (session('promote_id')) {
                $data['promote_id'] = session('promote_id');
            }else {
                //不是通过二维码进入的分销商  分销id就是本人
                $type = Db::name('mall_member')->where('id',$member_id)->value('type');
                if ($type > 0){
                    $data['promote_id'] = $member_id;
                }
            }
            if ($info['check'] == 1) {                   //使用旅行币
                $rebate_total = number_format($score/20,2);
                if ($rebate_total >= $data['order_total']) {
                    $data['rebate_total'] = $data['order_total'];
                    $data['total'] = 0.01;
                }else {
                    $data['rebate_total'] = $rebate_total;
                    $data['total'] = $data['price']*$data['num'] - $data['rebate_total'];
                }
            }
            $data['status'] = 0;
            $data['add_time'] = time();
            $result = $this->checkOrder($data);
            if ($result['code'] != 200) {
                $res['status'] = FALSE;
                $res['info'] = $result['message'];
                echo json_encode($res);exit;
            }
            $order_id = Db::name('spot_order')->insertGetId($data);
            if ($order_id) {
                Db::name('ticket')->where('id',$info['id'])->setInc('sale_num');
                $res['info'] = $order_id;
                $res['status'] = true;
            }else {
                $res['status'] = FALSE;
                $res['info'] = '创建订单失败';
            }
            echo json_encode($res);
        }
    }

// 团队票  可以不选出游人
    public function team_order() {
        Log::write(100);
        if (Request::instance()->isPost()) {
            $member_id = session('member_id');
            $info = Request::instance()->param();
            //如果出游人有传值 判断同张身份证30天内最多买两张票 
            if (!empty($info['ids'])) {
                $veri = $this->verifyCard($info['ids'],$info['id']);
                if ($veri == 1) {
                    $res['status'] = FALSE;
                    $res['info'] = '同张身份证30天内只能买两次相同的票';
                    echo json_encode($res);exit;
                }
            }
            if (empty($info['travel_agency'])) {
                    $res['status'] = FALSE;
                    $res['info'] = '请填写团队名称';
                    echo json_encode($res);exit;
                
            }
            

            $data['source'] = 'mobile';
            $data['order_sn'] = 'LP'.date('ymdhis', time()) . get_rand_num(4);
            $ticket = Db::name('ticket')->where('id',$info['id'])->find();
            $member = Db::name('mall_member')->field('score,mobile,travel_agency')->where('id',$member_id)->find();            //可用旅行币
            $score = $member['score'];
            $data['ticket_id'] = $info['id'];
            $data['spot_id'] = $ticket['spot_id'];
            $data['t_id'] = $ticket['t_id'];
            $data['ticket_name'] = $ticket['ticket_name'];
            $data['cost_price'] = $ticket['market_price'];
            $data['price'] = $ticket['shop_price'];
            $data['num'] = $info['num'];
            $data['travel_date'] = $info['use_date'];
            if(isset( $info['ids'])){
                    $data['traveler_ids'] = $info['ids'];
            }else{$data['traveler_ids']=0;}
            $data['order_total'] = $data['price']*$data['num'];
            $data['total'] = $data['order_total'];
            $data['member_id'] = $member_id;
            $data['rebate_total'] = 0;
            $data['mobile'] = $member['mobile'];
            $data['travel_agency'] = $member['travel_agency'];
            $data['is_team'] = 1;
            log::write($data);
            if (session('promote_id')) {
                $data['promote_id'] = session('promote_id');
            }else {
                //不是通过二维码进入的分销商  分销id就是本人
                $type = Db::name('mall_member')->where('id',$member_id)->value('type');
                if ($type > 0){
                    $data['promote_id'] = $member_id;
                }
            }
            if ($info['check'] == 1) {                   //使用旅行币
                $rebate_total = number_format($score/20,2);
                if ($rebate_total >= $data['order_total']) {
                    $data['rebate_total'] = $data['order_total'];
                    $data['total'] = 0.01;
                }else {
                    $data['rebate_total'] = $rebate_total;
                    $data['total'] = $data['price']*$data['num'] - $data['rebate_total'];
                }
            }
            $data['status'] = 0;
            $data['add_time'] = time();
            $result = $this->checkTeamOrder($data);
            if ($result['code'] != 200) {
                $res['status'] = FALSE;
                $res['info'] = $result['message'];
                echo json_encode($res);exit;
            }
            
            $order_id = Db::name('spot_order')->insertGetId($data);
            if ($order_id) {
                Db::name('ticket')->where('id',$info['id'])->setInc('sale_num');
                $res['info'] = $order_id;
                $res['status'] = true;
            }else {
                $res['status'] = FALSE;
                $res['info'] = '创建订单失败';
            }
            echo json_encode($res);
        }
    }

    public function createorder() {
        $member_id = session('member_id');
        $type=Db::name('mall_member')->where('id',$member_id)->value('type');
        $sale_type=Db::name('mall_member')->where('id',$member_id)->value('sala_type');
        $score = Db::name('mall_member')->where('id',$member_id)->value('score');
        $travel_agency = Db::name('mall_member')->where('id',$member_id)->value('travel_agency');
        $id = Request::instance()->param('id');
        $str = Request::instance()->param('str');
        $date = Request::instance()->param('use_date');
        $buy_num = Request::instance()->param('buy_num');


        $data = Db::name('ticket')->where('id',$id)->find();
        $use_date = isset($date)?$date:'请选择日期';
        if($data['is_team'] == 1){
            $buy_num = isset($buy_num)?$buy_num:10;
        }else{
            $buy_num = isset($buy_num)?$buy_num:1;
        }
        
        $start_date = date('Y-m-d',time()+86400);
        $date=explode('-',$start_date);
        if (!$id) {
            $id = session('spot_id');
        }
        $len = 1;
        if ($str) {
            $where['id'] = ['in',$str];
            $arr = explode(',',$str);
            $len = count($arr);
            $info = Db::name('member_traveler_info')->where($where)->select();
            $this->assign('info',$info);
        }
        
         $this->assign('type',$type);
        $this->assign('sale_type',$sale_type);
        $this->assign('use_date',$use_date);
        $this->assign('ids',$str);
        $this->assign('len',$len);
        $this->assign('score',$score);
        $this->assign('travel_agency',$travel_agency);
        $this->assign('data',$data);
        $this->assign('date',$date);
        $this->assign('buy_num',$buy_num);
        $this->assign('ticketid',$id);
	    return $this->fetch();
    }

    public function selectTravel() {
        $member_id = session('member_id');
        $num = Request::instance()->param('num');
        $use_date = Request::instance()->param('use_date');
        $buy_num = Request::instance()->param('buy_num');
        $ticketid = Request::instance()->param('id');
        $data = Db::name('member_traveler_info')->where('member_id',$member_id)->select();
        $this->assign('use_date',$use_date);
        $this->assign('num',$num);
        $this->assign('data',$data);
        $this->assign('buy_num',$buy_num);
        $this->assign('ticketid',$ticketid);
	    return $this->fetch();
    }

    public function adduser() {
        $num = Request::instance()->param('num');
        $this->assign('num',$num);
	    return $this->fetch();
    }

    public function ajax_save() {
	    if (Request::instance()->isPost()) {
	        $res['status'] = FALSE;
            $member_id  = session('member_id');
	        $info = Request::instance()->param();
            if (!isCreditNo($info['use_card'])) {
                $res['info'] = '身份证格式错误';
                echo json_encode($res);
                exit;
            }
	        $info['member_id'] = $member_id;
	        $bool = Db::name('member_traveler_info')->insert($info);
	        if ($bool) {
	            $res['status'] = TRUE;
	            $res['info'] = '添加成功';
            }else {
                $res['info'] = '添加失败';
            }
            echo json_encode($res);
	        exit;
        }
    }

    public function goodsposter() {
	    $id = Request::instance()->param('id');
        $member_id = Request::instance()->param('member_id');
        $ticket = Db::name('ticket')->where('id',$id)->find();
        $ac = authcode($member_id);
        $str = $this->oposter($id,$member_id);
        $route = substr($str,1);

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

        $this->assign('ticket',$ticket);
        $this->assign('member_id',$member_id);
        $this->assign('ac',$ac);
        $this->assign('route',$route);
	    return $this->fetch();
    }

    public function ajax() {
        $info = Request::instance()->param();
        $member_id = session('member_id');
        $where['is_share'] = 1;
        $start = strtotime(date('Y-m-d',time()));
        $end = $start+86399;
        $where['add_time'] = ['between',[$start,$end]];
        $where['member_id'] = $member_id;
        $bool = Db::name('travel_details')->where($where)->find();
        if (!$bool) {
            $data['member_id'] = $member_id;
            $data['title'] = '分享到'.$info['type'];
            $data['num'] = 50;
            $data['add_time'] = time();
            $data['is_share'] = 1;
            Db::name('travel_details')->insert($data);
            Db::name('mall_member')->where('id', $member_id)->setInc('score', 50);
        }
    }

    /**
     * 生成海报
     * @AuthorHTL
     * @DateTime  2016-07-01T17:09:41+0800
     * @return    [type]                   [description]
     */
    public function oposter($id,$member_id)
    {
        set_time_limit(0);
        \think\Loader::import('PHPQRCode', EXTEND_PATH);
        //$member_id = session::get('member_id');

        $ac = authcode($member_id);

        $url = "http://lvpai.zhonghuilv.net/mobile/goodsinfol/goodsdetail/id/$id/ac/$ac";

        $path_nama = date('Ymdhis',time()).getRandCode(6);
        $time_day = date('Ymd');
        $QR = PHPQRCode::get($url,$path_nama);

        $QR = "./uploads/qrcode/$time_day/$path_nama.png";//已经生成的原始二维码图
        $pic = './static/mobile/images/poster.jpg';
        $bool = Db::name('ticket')->where('id',$id)->value('poster');
        if ($bool) {
            $pic = '.'.$bool;
        }

        $QR = imagecreatefromstring(file_get_contents($QR));
        $pic = imagecreatefromstring(file_get_contents($pic));

        $QR_width = imagesx($QR);//二维码图片宽度
        $QR_height = imagesy($QR);//二维码图片高度
        $pic_width = imagesx($pic);//海报图片宽度
        $pic_height = imagesy($pic);//海报图片高度

        //重新组合图片并调整大小
        //粘贴二维码
        imagecopyresampled($pic,$QR, 460, 1050, 0, 0,210,210,$QR_width,$QR_height);

        //输出图片
        $poster_path = "./upload/mobile/" . date('Y-m-d') . '/';
        if (! is_dir($poster_path)) {
            mkdir($poster_path);
        }
        $poster_file = $poster_path . date('Ymdhis') . getRandCode(6) . ".jpg" ;
        imagejpeg($pic,$poster_file);

        return $poster_file;
    }

    public function checkOrder($info) {
        $admin = Config::get('pwy');
        $time =time();
        if(isset($info['traveler_ids'])){
        $user = Db::name('member_traveler_info')->field('use_name,use_card')->where('id','in',$info['traveler_ids'])->select();
        $use_card = array_column($user,'use_card');
        $use_name = array_column($user,'use_name');
        $username = implode(',',$use_name);
        $id_card = implode(',',$use_card);
        }
        else{
            $id_card=''; 
        }
        $data =[
            'account'=>$admin['ac'],
            'timestamp'=>$time,
            'sing'=>md5($admin['pw'].$time.$admin['pw']),
            'spotId'=> $info['spot_id'],
            'ticketId' => $info['t_id'],
            'otherSn' => $info['order_sn'],
            'oprice' => $info['price'],
            'onum' => $info['num'],
            'playTime' => $info['travel_date'],
            'useName'=> $username,
            'mobile'=> $info['mobile'],
            'useCard' =>$id_card
        ];
        $url ='http://cloud.zhonghuilv.net/spot/ValidationOrder';
        $res = request_post($url,$data);
        $res = json_decode($res,TRUE);

        return $res;
    }

    public function checkTeamOrder($info) {
        $admin = Config::get('pwy');
        $time =time();
        $user = Db::name('member_traveler_info')->field('use_name,use_card')->where('id','in',$info['traveler_ids'])->select();
        $use_card = array_column($user,'use_card');
        $use_name = array_column($user,'use_name');
        $username = implode(',',$use_name);
        $id_card = implode(',',$use_card);

        $data =[
            'account'=>$admin['ac'],
            'timestamp'=>$time,
            'sing'=>md5($admin['pw'].$time.$admin['pw']),
            'spotId'=> $info['spot_id'],
            'ticketId' => $info['t_id'],
            'otherSn' => $info['order_sn'],
            'oprice' => $info['price'],
            'onum' => $info['num'],
            'playTime' => $info['travel_date'],
            'useName'=> $username,
            'mobile'=> $info['mobile'],
            'useCard' =>$id_card,
        ];
        $url ='http://cloud.zhonghuilv.net/spot/ValidationOrder';
        $res = request_post($url,$data);
        $res = json_decode($res,TRUE);
        //log::write($data);
        //log::write($res);
        return $res;
    }

    public function verifyCard($ids,$ticket_id) {
        $arr = explode(',',$ids);
        $nowTime = time();
        $flag = 0;
        foreach($arr as $v) {
            $where['status'] = ['in','1,5'];
            $where['add_time'] = ['>',$nowTime-30*24*3600];
            $where['ticket_id'] = $ticket_id;
            $num = Db::name('spot_order')->where($where)->where("FIND_IN_SET($v,traveler_ids)")->count();
            if ($num > 1) {
                $flag = 1;
                break;
            }
        }
        return $flag;
    }

    public function onekeydel(){
        $member_id = Session::get("member_id");
        if($member_id){
            $bool = Db::name("member_traveler_info")->where("member_id",$member_id)->delete();
            if($bool){
                $res['code'] = 1;
                $res['msg'] = '删除成功!';
            }else{
                $res['code'] = -1;
                $res['msg'] = '删除失败!';
            }

            return json($res);
        }
        
    }

}
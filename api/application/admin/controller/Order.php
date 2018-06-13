<?php
namespace app\admin\controller;
use think\Db;
use think\Cache;
use think\Request;
use think\Config;
use think\Session;
use think\Image;
use think\Log;
use app\admin\model\OrderModel;
use app\api\controller\Refund;
/**
 * 订单管理
 */
class Order extends Base
{
    public function index()
    {
        $info = Request::instance()->param();

        $where = array();
        if (isset($info['status']) && $info['status'] != '-1') {
            $where['status'] = $info['status'];
        }
        if (isset($info['key']) && !empty($info['key']) ) {
            $field = isset($info['type']) && !empty($info['type']) ? $info['type'] : 'order_sn';
            $where[$field] = $info['key'];
        }

        if (isset($info['id']) && $info['id'] > 0) {
            $where['id'] = $info['id'];
        }
        if (isset($info['spot_id']) && $info['spot_id'] > 0) {
            $where['spot_id'] = $info['spot_id'];
        }

        if (isset($info['from']) && !empty($info['from']) ) {
            $start = $info['from'];
            $end = !empty($info['to']) ? $info['to'] : date('Y-m-d',time());
            $time['start'] = $start;
            $time['end'] = $end;
            $data = Db::name('spot_order')->where($where)->whereTime('add_time', 'between', [$start,$end])->order('add_time desc')->paginate(10,false,['query'=>$info]);

            Session::set('order_time',$time);
        }else{
            $data = Db::name('spot_order')->where($where)->order('add_time desc')->paginate(10,false,['query'=>$info]);
        }

        Session::set('spot_order',$where);

        $page = $data->render();
        //分配初始化数据
        $order_status = Config::get('order_status');
        //dump($order_status);exit;
        $info['key'] = isset($info['key']) ? $info['key'] : '';
        $info['type'] = isset($info['type']) ? $info['type'] : '';
        $info['from'] = isset($info['from']) ? $info['from'] : '';
        $info['to'] = isset($info['to']) ? $info['to'] : '';
        $info['status'] = isset($info['status']) ? $info['status'] : '-1';

        $this->assign('info',$info);
        $this->assign('data',$data);
        $this->assign('page',$page);
        $this->assign('order_status',$order_status);


        $url = "http://cloud.zhonghuilv.net/index/spot/lvpaiSpot";
        $spotlist = https_request($url);
        $spotlist = json_decode($spotlist,true);

        $this->assign("spotlist",$spotlist);
        return  $this->fetch();
    }
//订单详情
    public function detail()
    {
        $request = Request::instance();
        $param = $request->param();
        $order = Db::name('spot_order')->where('id',$param['id'])->find();
        //$order = Db::name('spot_order')->where('id',1769)->find();    //

        // //获取订单下的每个身份证核销状态
        $infos_ = [];
        $traveler_id_s = array();
        $traveler_num = 0;
        if(!empty($order['UUcode']))
        {
            if ($order['spot_id'] == 10004) {
                $url = 'http://61.186.100.83:8081/Order/QueryOrderVaildInfo?uucode='.$order['UUcode'].'&pftOrdersn='.$order['order_code'];
            }else {
                $url = 'http://220.169.155.57:8081/Order/QueryOrderVaildInfo?uucode='.$order['UUcode'].'&pftOrdersn='.$order['order_code'];
            }
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($curl);
            $datalist = json_decode($output,true);
            $infos_ = json_decode($datalist['Data']['VaildList'],true);
//            echo '<pre>';
//            var_dump($infos_);die;

            //把数据返回给票务云
            $pw_infos['mobile'] = $order['mobile'];
            $pw_infos['idCardS'] = json_encode($infos_);
            $pw_infos['qrCode'] = $order['UUcode'];
            $insertPwUrl = 'http://cloud.zhonghuilv.net/Spot/markupIdcard';
            $insertPw_output = Post($pw_infos,$insertPwUrl);
            $traveler_num = 0;
            foreach ($infos_ as $value) {
                $where_card['use_card'] = $value['identity'];
                $where_card['member_id'] = $order['member_id'];
                $id_card = Db::name("member_traveler_info")->where($where_card)->select();
                if($value['identity'] != 1 && empty($id_card))
                {
                    $data['member_id'] = $order['member_id'];
                    $data['use_name'] = $value['UserName'];
                    $data['use_card'] = $value['identity'];
                    $data['status'] = 1;
                    $data['add_time'] = time();

                    $traveler_id = Db::name("member_traveler_info")->insertGetId($data);
                    array_push($traveler_id_s,$traveler_id);
                }
                if($value['IsCheck']!=0){
                    $traveler_num++;
                }

            }

           // $traveler_num = count($traveler_id_s);

            //获取当前时间
            //当前时间
            $current_time = strtotime(date('Y-m-d',time()));;
            //获取游玩时间
            $use_date = strtotime($order['travel_date']);
            //if()
            if($current_time > $use_date && $order['status'] == 1)
            {
                $tk_price = ($order['num'] - $traveler_num) * $order['price'];

                Db::name("spot_order")->where('id',$param['id'])->setField("refund_price",$tk_price);
                Db::name("spot_order")->where('id',$param['id'])->setField("status",2);
            }


            $traveler_id_s = implode(",",$traveler_id_s);


            $this->assign('infos_',$infos_);
        }
        $this->assign('traveler_num',$traveler_num);
        //这里修改了出游人信息 现改为已核销的订单会修改为票务系统传来的出游人信息
        if($order['status']==5){
            $bool = Db::name("spot_order")->where('id',$param['id'])->setField("traveler_ids",$traveler_id_s);
            if($bool)
            {
                $where['id'] = ['in',$traveler_id_s];
            }
        }

        $where['id'] = ['in',$order['traveler_ids']];
        $info = Db::name('member_traveler_info')->where($where)->select();
        //部分退款人信息
        $refund_name="";
        $refund_price = '';
        if($order['refund_price'] > 0){
            $where['id']=['in',$order['refund_ids']];
            $name=Db::name('member_traveler_info')->where($where)->field('use_card')->select();
            $refund_name='';
            foreach ($name as $key => $value) {
                $refund_name.=",".$name[$key]['use_name'];
            }
            $refund_names=substr($refund_name,1);
            $refund_num=count(explode(",", $order['refund_ids']));
            $refund_price=$order['refund_price'];

        }

        $this->assign('refund_price',$refund_price);
        $this->assign('refund_name',$refund_name);
        $this->assign('info',$info);
        $this->assign('order',$order);
        return  $this->fetch();
    }

    public function queryorder()
    {
        $request = Request::instance();

        return  $this->fetch();
    }



    public function check(){
        if (Request::instance()->isPost()) {
            $id = Request::instance()->param('id');
            $type = Request::instance()->param('type');
            $res = array(
                'status' => false,
                'info' => '操作失败',
            );
            $data['id'] = $id;
            $order_price = Db::name("spot_order")->field("refund_price,total")->where("id",$id)->find();
            if ($type == 'cancel') {
                $data['status'] = 4;
            }
            $data['up_time'] = time();
            if($order_price['refund_price'] == $order_price['total']){
                $refund_obj = new Refund();
                $refund_info = $refund_obj->index($id);
                log::write($refund_info);
                log::write("refund_info");
                $bool = Db::name('spot_order')->update($data);
                if ($bool) {
                    if ($data['status'] == 3) {
                        $this->cancel($id);
                    }
                    $res['status'] = true;
                    $res['info'] = '操作成功';
                }
            }else{
                $refund_obj = new Refund();
                $refund_info = $refund_obj->index($id);
                log::write($refund_info);
                log::write("refund_info");
                if ($refund_info) {
                    $res['status'] = true;
                    $res['info'] = '操作成功';
                }else{
                    $res['status'] = false;
                    $res['info'] = '操作失败';
                }
            }

            echo json_encode($res);
        }

    }

    public function cancel($id) {
        $admin = Config::get('pwy');
        $time =time();
        $cloudOrderSn = Db::name('spot_order')->where('id',$id)->value('order_code');
        $data =[
            'account'=>$admin['ac'],
            'timestamp'=>$time,
            'sing'=>md5($admin['pw'].$time.$admin['pw']),
            'cloudOrderSn'=> $cloudOrderSn,
            'code' => 0,
        ];
        $url ='http://cloud.zhonghuilv.net/spot/OrderChange';
        $res = request_post($url,$data);
        $res = json_decode($res,TRUE);
        Log::write($res);
        return $res;
    }

    public function verify() {
        $nowdate = date('Y-m-d',time());
        $where['status'] = 1;
        $where['travel_date'] = ['<',$nowdate];
        $data = Db::name('spot_order')->where($where)->order('id desc')->paginate(10);
        $page = $data->render();
        $this->assign('data',$data);
        $this->assign('page',$page);
        return $this->fetch();

    }

    public function verify_ajax() {
        if (Request::instance()->isPost()) {
            $id = Request::instance()->param('id');
            $where['id'] = $id;
            $where['status'] = 1;
            $data['status'] = 5;
            $data['up_time'] = time();
            $bool = Db::name('spot_order')->where($where)->find();
            if ($bool) {
                $result = $this->notify($id);
                $res['status'] = true;
                $res['info'] = '核销成功';
            }else {
                $res['status'] = FALSE;
                $res['info'] = '核销失败';
            }
            echo json_encode($res);
            exit;
        }
    }

    //手动核销 计算返佣 结算旅行币
    protected function notify($id) {
        $order = Db::name('spot_order')->field('order_sn,order_code')->where('id',$id)->find();
        $res['remoteSn'] = $order['order_sn'];
        $res['orderSn'] = $order['order_code'];
        $data = json_encode($res);
        $url ='http://lvpai.zhonghuilv.net/mobile/notify/index';
        $result = request_post($url,$data);
        return $result;
    }


    public function export()
    {
        set_time_limit(0);
        ob_end_clean();
        $now_date = date('Y年m月d日',time());
        $expTitle= $now_date .'-订单数据';
        $xlsCell = array(
            array('add_time','订单日期'),
            array('order_sn','订单编号'),
            //array('spot','所属景区'),
            array('ticket_name','商品名称'),
            array('use_name','用户名称'),
            array('mobile','用户电话'),
            array('travel_agency','旅行社'),
            array('use_date','使用时间'),
            array('use_card','身份证'),
            array('order_total','订单金额'),
            array('trade_no','支付编号'),
            array('pay_way','支付方式'),
            array('num','订购数量'),
            array('check_num','入园数量'),
            array('refund_price','退款金额'),
            array('status','订单状态'),
            array('chayi','入园差异'),
        );
        $where = Session::get('spot_order');
        if (Session::has('order_time')) {
            $order_time = Session::get('order_time');
            $info = Db::name('spot_order')->where($where)->whereTime('add_time', 'between', [$order_time['start'],$order_time['end']])->select();
        }else{
            $info = Db::name('spot_order')->where($where)->select();
        }

        $data = array();

        foreach ($info as $key => $value)
        {

            $traveler_ids=explode(',',$value['traveler_ids']);
            //var_dump($traveler_ids);
            for($i=0;$i<count($traveler_ids);$i++){
                $name[$i]=db::name('member_traveler_info')->where('id',$traveler_ids[$i])->value('use_name');
                $card[$i]=db::name('member_traveler_info')->where('id',$traveler_ids[$i])->value('use_card');

            }

            $use_name= Db::name("mall_member")->where("id",$value['member_id'])->value("name");
            $use_card=implode(",",$card);
            //var_dump($use_name);
            // die;


            $data[$key]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
            $data[$key]['order_sn'] = $value['order_sn'];
            //	$data[$key]['spot'] = $spot['title'];
            $data[$key]['ticket_name'] = $value['ticket_name'];
            $data[$key]['use_name'] = $use_name;
            $data[$key]['mobile'] = "\t".$value['mobile']."\t";
            $data[$key]['travel_agency'] = $value['travel_agency'];
            //$data[$key]['num'] = $value['num'];
            $data[$key]['use_date'] = $value['travel_date'];
            $data[$key]['use_card'] = "\t".$use_card."\t";
            $data[$key]['order_total'] = $value['order_total'];
            $data[$key]['trade_no'] = "\t".$value['trade_no']."\t";
            $data[$key]['pay_way'] = $value['pay_way'];
            $data[$key]['num'] = $value['num'];
            $data[$key]['check_num'] = ($value['total'] - $value['refund_price'])/$value['price'];
            $data[$key]['refund_price'] = $value['refund_price'];
            $data[$key]['status'] = order_status($value['status']);
            $data[$key]['chayi'] = round(100 - (($value['total'] - $value['refund_price'])/$value['price'])/ $value['num'] * 100) ."%";


        }
        exportExcel($expTitle,$xlsCell,$data);

    }

    // public function CommData()
    // {
    //     ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
    //     set_time_limit(0);// 通过set_time_limit(0)可以让程序无限制的执行下去
    //    // $interval=60*60*24;// 每隔半小时运行
    //     //获取当前时间戳
    //     $current_time = date('Y-m-d',strtotime("+1 day"));

    //     $where['travel_date'] = $current_time;
    //     $where['status'] = 1;
    //   //  do{
    //             $order_info = Db::name("spot_order")->where($where)->select();

    //             $traveler_id_s = [];
    //             foreach ($order_info as $val) {
    //                 if(!empty($val['UUcode']))
    //                 {
    //                     $url = 'http://61.186.100.83:8081/Order/QueryOrderVaildInfo?uucode='.$val['UUcode'].'&pftOrdersn='.$val['order_code'];
    //                     $curl = curl_init();
    //                     curl_setopt($curl, CURLOPT_URL, $url);
    //                     curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //                     $output = curl_exec($curl);
    //                     $datalist = json_decode($output,true);
    //                     $infos_ = json_decode($datalist['Data']['VaildList'],true);


    //                     //把数据返回给票务云
    //                     $pw_infos['mobile'] = $val['mobile'];
    //                     $pw_infos['idCardS'] = json_encode($infos_);
    //                     $pw_infos['qrCode'] = $val['UUcode'];
    //                     $insertPwUrl = 'http://cloud.zhonghuilv.net/Spot/markupIdcard';
    //                     $insertPw_output = Post($pw_infos,$insertPwUrl);
    //                     dump($insertPw_output);
    //                     die;
    //                     if($insertPw_output != '')
    //                     {
    //                     	  foreach ($infos_ as $value) {

    //                          if($value['identity'] != 1)
    //                          {
    //                              $data['member_id'] = $val['member_id'];
    //                              $data['use_name'] = $value['UserName'];
    //                              $data['use_card'] = $value['identity'];
    //                              $data['status'] = 1;
    //                              $data['add_time'] = time();

    //                              $traveler_id = Db::name("member_traveler_info")->insertGetId($data);
    //                              $bool =  array_push($traveler_id_s,$traveler_id);

    //                             if($bool)
    //                             {
    //                             	  $refund_obj = new Refund();
    //                                $refund_info = $refund_obj->index($val['id']);
    //                             }
    //                             else
    //                             {
    //                              echo 0;
    //                             }
    //                          }
    //                          else
    //                          {
    //                          	 $refund_obj = new Refund();
    //                                $refund_info = $refund_obj->index($val['id']);
    //                          }

    //                     	}
    //                     }
    //                     else
    //                     {

    //                     }


    //                 }     
    //             }

    //           //  sleep($interval);
    //       //  }while(true);

    // }


    // //退款
    // public function refund()
    // {

    // 		$ref= strtoupper(md5("appid=your_appid&mch_id=your_mch_id&nonce_str=123456&op_user_id=646131"
    //     "&out_refund_no=201608142308&out_trade_no=860524080535541654&refund_fee=3&total_fee=3"
    //      "&key=suiji123"));//sign加密MD5

    //  $refund=array(
    //     'appid'=>'your_appid',//应用ID，固定
    //     'mch_id'=>'your_mch_id',//商户号，固定
    //     'nonce_str'=>'123456',//随机字符串
    //     'op_user_id'=>'646131',//操作员
    //     'out_refund_no'=>'201608142308',//商户内部唯一退款单号
    //     'out_trade_no'=>'860524080535541654',//商户订单号,pay_sn码 1.1二选一,微信生成的订单号，在支付通知中有返回
    //     // 'transaction_id'=>'1',//微信订单号 1.2二选一,商户侧传给微信的订单号
    //     'refund_fee'=>'3',//退款金额
    //     'total_fee'=>'3',//总金额
    //     'sign'=>$ref//签名
    //  );

    //  $url="https://api.mch.weixin.qq.com/secapi/pay/refund";;//微信退款地址，post请求
    //  $xml=arrayToXml($refund);

    //  $ch=curl_init();
    //  curl_setopt($ch,CURLOPT_URL,$url);
    //  curl_setopt($ch,CURLOPT_HEADER,1);
    //  curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    //  curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,1);//证书检查
    //  curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
    //  curl_setopt($ch,CURLOPT_SSLCERT,dirname(__FILE__).'/cert/apiclient_cert.pem');
    //  curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
    //  curl_setopt($ch,CURLOPT_SSLKEY,dirname(__FILE__).'/cert/apiclient_key.pem');
    //  curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
    //  curl_setopt($ch,CURLOPT_CAINFO,dirname(__FILE__).'/cert/rootca.pem');
    //  curl_setopt($ch,CURLOPT_POST,1);
    //  curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);

    //  $data=curl_exec($ch);
    //  if($data){ //返回来的是xml格式需要转换成数组再提取值，用来做更新
    //     curl_close($ch);
    //     var_dump($data);
    //  }else{
    //     $error=curl_errno($ch);
    //     echo "curl出错，错误代码：$error"."<br/>";
    //     echo "<a href='http://curl.haxx.se/libcurl/c/libcurs.html'>;错误原因查询</a><br/>";
    //     curl_close($ch);
    //     echo false;
    //  }
    // }

    //作用：产生随机字符串，不长于32位
    private function createNoncestr($length = 32 ){
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ ) {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    //作用：生成签名
    private function getSign($Obj){
        foreach ($Obj as $k => $v){
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String."&key=".$this->key;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }



    ///作用：格式化参数，签名过程需要使用
    private function formatBizQueryParaMap($paraMap, $urlencode){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v){
            if($urlencode)
            {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0){
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }


    public function getCheckName()
    {
        $param = request()->param();
        $order = Db::name('spot_order')->where('id',$param['orderid'])->find();
        //$order = Db::name('spot_order')->where('id',1892)->find();    //

        // //获取订单下的每个身份证核销状态 
        $infos_ = [];
        $traveler_id_s = array();
        $traveler_num = 0;
        if(!empty($order['UUcode']))
        {
            if ($order['spot_id'] == 10004) {
                $url = 'http://61.186.100.83:8081/Order/QueryOrderVaildInfo?uucode='.$order['UUcode'].'&pftOrdersn='.$order['order_code'];
            }else {
                $url = 'http://220.169.155.57:8081/Order/QueryOrderVaildInfo?uucode='.$order['UUcode'].'&pftOrdersn='.$order['order_code'];
            }
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($curl);
            $datalist = json_decode($output,true);
            $infos_ = json_decode($datalist['Data']['VaildList'],true);
            foreach ($infos_ as $value) {
                if($value['IsCheck'] == 0)
                {
                    $Check_num = count($infos_);
                }

            }

            return $Check_num;
        }

    }

    public function editRefundPrice()
    {
        $param = request()->param();
        $order = Db::name('spot_order')->where('id',$param['orderid'])->find();
        if(!empty($order)){
            $bool = Db::name("spot_order")->where('id',$param['orderid'])->setField("refund_price",$param['tk_amount']);
            if($bool){
                $res['code'] = 1;
                $res['msg'] = '修改成功';
            }else{
                $res['code'] = '-1';
                $res['msg'] = '获取失败';
            }
            return json($res);
        }
    }

    /**
     * 杨 订单管理开始
     * */
    public function listNew(){
        $param = request()->param();
        if(empty($param['action'])){
            $param['action'] = '';
        }
//        echo '<pre>';
//        var_dump($param);die;

        $action = $param['action'];
        switch ($action){
            case '':
                $url = "http://cloud.zhonghuilv.net/index/spot/lvpaiSpot";
                $spotlist = https_request($url);
                $spotlist = json_decode($spotlist,true);
                $this->assign("spotlist",$spotlist);
                return $this->fetch();
                break;
            case 'ajaxList':
                $this->getAjaxList();
                break;
            case 'export':
                $this->exportList();
                break;
            default:
                self::ajaxReturn(400,'非法操作!','');
        }
    }

    /**
     * 获取订单列表
     * */
    private function getAjaxList(){
        $param = request()->param();
        $page = $param['pageNumber'];//页码
        $rows =  $param['pageSize'];//条数
        $sortName = $param['sortName'];
        $sortOrder =  $param['sortOrder'];
        $condition = '';
        if(!empty($param['str_time'])){
            $condition .= " and add_time > ".strtotime($param['str_time']);
        }
        if(!empty($param['end_time'])){
            $condition .= " and add_time < ".strtotime($param['end_time']);
        }
        if(!empty($param['order_sn'])){
            $condition .= " and order_sn like '%".$param['order_sn']."%'";
        }
        if($param['status']!=-1){
            $condition .= " and status =".$param['status'];
        }
        if($param['spot_id']!=-1){
            $condition .= " and spot_id =".$param['spot_id'];
        }
        if(!empty($param['keyList'])){
            $condition_1 = " and travel_agency like '%".$param['keyList']."%'";
        }else{
            $condition_1 = '';
        }
        $order = new OrderModel();
        $row = $order->getList($page,$rows,$condition.$condition_1,$sortName,$sortOrder);
        if($row['total'] > 0 ){
            $data['data']['total']=$row['total'];
            $data['data']['rows']=$row['rows'];
            $data['success']=200;
            $data['message']=null;
        }else{
            $condition_1 = " and mobile like '%".$param['keyList']."%'";
            $row = $order->getList($page,$rows,$condition.$condition_1,$sortName,$sortOrder);
            if($row['total'] > 0 ){
                $data['data']['total']=$row['total'];
                $data['data']['rows']=$row['rows'];
                $data['success']=200;
                $data['message']=null;
            }else{
                $data['data']['total']=0;
                $data['data']['rows']=[];
                $data['success']=200;
                $data['message']='暂无数据';
            }
        }
        echo json_encode($data);die;

    }


    /**
     * 导出订单
     * */
    private function exportList(){
        $param = request()->param();

        $condition = '1=1 ';
        if(!empty($param['str_time'])){
            $condition .= " and t.add_time > ".strtotime($param['str_time']);
        }
        if(!empty($param['end_time'])){
            $condition .= " and t.add_time < ".strtotime($param['end_time']);
        }
        if(!empty($param['order_sn'])){
            $condition .= " and t.order_sn like '%".$param['order_sn']."%'";
        }
        if($param['status']!=-1){
            $condition .= " and t.status =".$param['status'];
        }
        if($param['spot_id']!=-1){
            $condition .= " and t.spot_id =".$param['spot_id'];
        }
        if(!empty($param['keyList'])){
            $condition_1 = " and t.travel_agency like '%".$param['keyList']."%'";
        }else{
            $condition_1 = '';
        }

        $order = new OrderModel();
//        var_dump($condition.$condition_1);
        $sql = "select t.*,t1.name 
                from too_spot_order t 
                left JOIN too_mall_member t1 
                ON t.member_id = t1.id 
                WHERE {$condition}{$condition_1}";
//        var_dump($sql);die;

        $info = $order->query($sql);
        $data = array();
        foreach ($info as $key => $value)
        {

            $traveler_ids=explode(',',$value['traveler_ids']);
            //var_dump($traveler_ids);
            for($i=0;$i<count($traveler_ids);$i++){
                $name[$i]=db::name('member_traveler_info')->where('id',$traveler_ids[$i])->value('use_name');
                $card[$i]=db::name('member_traveler_info')->where('id',$traveler_ids[$i])->value('use_card');

            }

            $use_name= Db::name("mall_member")->where("id",$value['member_id'])->value("name");
            $use_card=implode(",",$card);
            //var_dump($use_name);
            // die;


            $data[$key]['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
            $data[$key]['order_sn'] = $value['order_sn'];
            //	$data[$key]['spot'] = $spot['title'];
            $data[$key]['ticket_name'] = $value['ticket_name'];
            $data[$key]['use_name'] = $use_name;
            $data[$key]['mobile'] = "\t".$value['mobile']."\t";
            $data[$key]['travel_agency'] = $value['travel_agency'];
            //$data[$key]['num'] = $value['num'];
            $data[$key]['use_date'] = $value['travel_date'];
            $data[$key]['use_card'] = "\t".$use_card."\t";
            $data[$key]['order_total'] = $value['order_total'];
            $data[$key]['trade_no'] = "\t".$value['trade_no']."\t";
            $data[$key]['pay_way'] = $value['pay_way'];
            $data[$key]['num'] = $value['num'];
            $data[$key]['check_num'] = ($value['total'] - $value['refund_price'])/$value['price'];
            $data[$key]['refund_price'] = $value['refund_price'];
            $data[$key]['status'] = order_status($value['status']);
            $data[$key]['chayi'] = round(100 - (($value['total'] - $value['refund_price'])/$value['price'])/ $value['num'] * 100) ."%";
            $data[$key]['refund_price'] = $value['refund_price'];


        }
//        $row = $order->getList($page,$rows,$condition.$condition_1,$sortName,$sortOrder);
        $title_info = array(
            array('field' => 'add_time', 'title' => '订单日期', 'width' => 10),
            array('field' => 'order_sn', 'title' => '订单编号', 'width' => 20),
            array('field' => 'ticket_name', 'title' => '商品名称', 'width' => 30),
            array('field' => 'use_name', 'title' => '用户名称', 'width' => 10),
            array('field' => 'mobile', 'title' => '用户电话', 'width' => 12),
            array('field' => 'travel_agency', 'title' => '旅行社名称', 'width' => 15),
//            array('field' => 'gest_day', 'title' => '孕天', 'width' => 12),
            array('field' => 'use_date', 'title' => '使用时间', 'width' => 12),
            array('field' => 'use_card', 'title' => '身份证', 'width' => 22),
            array('field' => 'order_total', 'title' => '订单金额', 'width' => 12),
            array('field' => 'trade_no', 'title' => '支付编号', 'width' => 15),
            array('field' => 'pay_way', 'title' => '支付方式', 'width' => 12),
            array('field' => 'num', 'title' => '订购数量', 'width' => 12),
            array('field' => 'check_num', 'title' => '入园数量', 'width' => 12),
            array('field' => 'refund_price', 'title' => '退款金额', 'width' => 12),
            array('field' => 'status', 'title' => '订单状态', 'width' => 12),
            array('field' => 'chayi', 'title' => '入园差异', 'width' => 12),
        );
//        var_dump($data);die;
        $order = new ExcelInport();
        $title = 'All-订单';
        if(!empty($param['str_time'])&&!empty($param['end_time'])){
            $title = date('Y-m-d',strtotime($param['str_time'])).'到'.date('Y-m-d',strtotime($param['end_time'])).'-订单';
        }
        if(empty($param['str_time'])&&!empty($param['end_time'])){
            $title = date('Y-m-d',strtotime($param['str_time'])).'以前-订单';
        }
        if(!empty($param['str_time'])&&empty($param['end_time'])){
            $title = date('Y-m-d',strtotime($param['str_time'])).'以后-订单';
        }
        $order->writeExcel($data, $title_info, $title,'order');
    }

}
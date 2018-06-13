<?php
//  微信提现接口
namespace app\admin\controller;
use com\CAcert;
use think\Db;
use think\Cache;
use think\Request;
use think\Config;
use think\Session;
use think\Image;
use think\Log;
use app\mobile\controller\Notify;

class Refund 
{
    protected $appid;
    protected $mch_id;
    protected $key;
    protected $openid;
    protected $order_sn;
    protected $title;
    protected $total_price;
    protected $trade_no;
    protected $refund_fee;
    protected $total_fee;
    protected $qrCode;
    protected $ticketId;


     public function index($id) {
       // $param = Request::instance()->param();
        $order_sn = 'Tk'.date('ymdhis',time()).get_rand_num();
        
        //$order_info = Db::name("spot_order")->where("id",$param['order_id'])->find();
        $order_info = Db::name("spot_order")->where("id",$id)->find();
        $openid_info = Db::name('mall_member')->where('id',$order_info['member_id'])->field('openid')->find();
      //  dump($data);
        if (!$openid_info) {
            $info['code'] = '-1';
            $info['msg'] = '信息错误';
            return json_encode($info,JSON_UNESCAPED_UNICODE);
            exit;
        }
        $this->openid = $openid_info['openid'];
            // dump($data['openid']);die;
        $data['order_sn'] = $order_info['order_sn'];
        $data['member_id'] =$order_info['member_id'];
        $data['refund_price'] = $order_info['refund_price'];
        $data['create_time'] = time();
        $data['order_id'] = $id;
        $order = Db::name('member_refund')->insert($data);
         if (!$order) {
            $info['code'] = '-1';
            $info['msg'] = '信息错误';
            return json_encode($info,JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $config = Config::get('wxpay');
        $this->appid = $config['appid'];
        $this->mch_id = $config['mchid'];
        $this->key = $config['key'];
        $this->order_sn = $order_info['order_sn'];
        $this->refund_fee = $order_info['refund_price'];
        $this->total_fee = floatval($order_info['total']);//$order_info['refund_price'];
        $this->qrCode = $order_info['UUcode'];
        $this->ticketId = $order_info['t_id'];
      //  dump($this->total_fee);
        $this->trade_no = $order_info['trade_no'];//$order_info['refund_price'];
        $return = $this->refund();
        return json_encode($return);
    }

    public function refund(){

        $return=$this->weixinapp();
       
        return $return;
    }

    private function weixinapp(){
        $url='https://api.mch.weixin.qq.com/secapi/pay/refund';
       $params=array(
          'appid'=>'wxe71d7cb038a75be3',//应用ID，固定
          'mch_id'=>'1497847032',//商户号，固定
          'nonce_str'=>$this->createNoncestr(),//随机字符串
          'op_user_id'=>'1497847032',//操作员 
          'out_refund_no'=>$this->trade_no,//,//商户内部唯一退款单号
          'out_trade_no'=>$this->order_sn,//,//商户订单号,pay_sn码 1.1二选一,微信生成的订单号，在支付通知中有返回
          'refund_fee'=>$this->refund_fee * 100,//退款金额
          'total_fee'=>$this->total_fee * 100,
          
       );
        //签名
       	$params['sign']=$this->getSign($params);
        $xmlData=arrayToXml($params);
       $return_data=xmlToArray(postXmlSSLCurl($xmlData,$url));
       log::write($return_data);
       $return = [];
        if($return_data['result_code'] == 'SUCCESS')
        {
            Db::name("member_refund")->where("order_sn",$this->order_sn)->setField("status",1);
            //调票务云接口核销该订单
            // $url = 'http://cloud.zhonghuilv.net/spot/checkAllOrder';
            // $data['qrCode'] = $this->qrCode;
            // $data['ticketId'] = $this->ticketId;
            // $checkall_ = https_request($url,$data); 
            // log::write($checkall_);
            // log::write("checkall_".$this->order_sn);
            $notify = new Notify();
            $orderid = Db::name("spot_order")->where("order_sn",$this->order_sn)->value("id");
            $notify_res = $notify->fenxiao_notify($orderid);
        }
        else
        {
            $return['code'] = 1;
            $return['msg']  = "退款失败!";
            $return['return_msg'] = $return_data;
        }
        return json_encode($return);
    }


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


   

}








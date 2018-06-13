<?php
namespace app\api\controller;
use think\Request;
use think\Cookie;
use think\Db;

class Wxpay {
    /*
    配置参数
    */
    private $config = array(
        'appid' => "wx82943c0b8d1bfca2",    /*微信开放平台上的应用id*/
        'mch_id' => "1494961042",   /*微信申请成功之后邮件中的商户id*/
        'api_key' => "gsk4lkds9sdadsm7m3mhnn23h43jjk23",    /*在微信商户平台上自己设定的api密钥 32位*/
        'notify_url' => 'http://www.zhlsfnoc.com/api/Paradise/NotifyData' /*自定义的回调程序地址*/
    );
    private static $instance;

    public static function getInstance() {
        if(self::$instance == null) {
            self::$instance = new Wxpay();
        }
        return self::$instance;
    }


    //下单,获取prepay_id
    public function getPrePayOrder($body,$out_trade_no){

        // 下单接口，需要传入10个参数，将由$data数组来组成
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $notify_url = $this->config["notify_url"];
        $onoce_str = $this->createNoncestr();
        $data["appid"] = $this->config["appid"]; // 应用id
        $data["body"] = $body; // 商品描述
        $data["mch_id"] = $this->config['mch_id']; // 商户号
        $data["nonce_str"] = $onoce_str; // 随机字符串
        $data["notify_url"] = $notify_url; // 异步地址
        $data["out_trade_no"] = $out_trade_no; // 订单号
        $data["spbill_create_ip"] = $this->get_client_ip(); // 终端ip  

        // 根据订单号来得到产品id
        $orderInfo = Db::name('paradise_order')->field('id, product_id')->where('order_sn', $out_trade_no)->find();

        $cash = Db::name('paradise_product')->where('id', $orderInfo['product_id'])->value('cash');

        $data["total_fee"] = 1; // 订单金额，测试
        // $data["total_fee"] = $cash * 100; // 订单金额，实际

        // 判断该记录是否存在
        if($orderInfo)
        {
            $data["trade_type"] = "APP"; // 交易类型

            $sign = $this->getSign($data);

            $data["sign"] = $sign; // 签名
            $xml = $this->arrayToXml($data);


            $response = $this->postXmlCurl($xml, $url); 
            //dump($sign);
            //dump($data);
            //将微信返回的结果xml转成数组
            $response = $this->xmlToArray($response);
            //dump($response);
            //返回数据
            return $response;
         }
         else
         {
            return '该订单不存在';
         }
    }

    /*生成签名*/
    public function getSign($Obj){

        foreach($Obj as $k => $v){
            $Parameters[$k] = $v;
        }

        //签名步骤一：按字典序排序参数
         ksort($Parameters);
         $String = $this->formatBizQueryParaMap($Parameters, false);
         //return $String;
         //echo '【string1】'.$String.'</br>';
        // //签名步骤二：在string后加入KEY
         $String = $String."&key=".$this->config['api_key'];
         //return $String;
        // //echo "【string2】".$String."</br>";
        // //签名步骤三：MD5加密
         $String = md5($String);
        // //echo "【string3】 ".$String."</br>";
        // //签名步骤四：所有字符转为大写
         $result_ = strtoupper($String);
        // //echo "【result】 ".$result_."</br>";
         return $result_;
    }


    /**
    *  作用：产生随机字符串，不长于32位
    */
    public function createNoncestr( $length = 32 ){
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {  
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
        }  
        return $str;
    }


    //数组转xml
    public function arrayToXml($arr){
        $xml = "<xml>";
        foreach ($arr as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">"; 
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";  
            }
        }
        $xml.="</xml>";
        return $xml; 
    }

      
    /**
    *  作用：将xml转为array
    */
    public function xmlToArray($xml){   
        //将XML转为array        
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);    
        return $array_data;
    }


    /**
    *  作用：以post方式提交xml到对应的接口url
    */
    public function postXmlCurl($xml,$url,$second=30){   
        //初始化curl        
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果

        if($data){
            curl_close($ch);
            return $data;
        }else{ 
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>"; 
            curl_close($ch);
            return false;
        }
    }


    /*
    获取当前服务器的IP
    */
    public function get_client_ip(){
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        } else {
            $cip = "unknown";
        }
        return $cip;
    }
    

    /**
    *  作用：格式化参数，签名过程需要使用
    */
    public function formatBizQueryParaMap($paraMap, $urlencode){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v){
            if($urlencode){
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

    /**
     * Wechat::buildPackage()
     * 生成package
     * @param array $parameter
     * @return string
     */
    public function buildPackage($parameter) {
            
        $filter = array('bank_type', 'body', 'partner', 'out_trade_no', 'total_fee', 'fee_type', 'notify_url', 'spbill_create_ip', 'input_charset');
        $base = array(
            'notify_url' => $this->wechat_config['notify_url'], 
            'bank_type' => 'WX',
            'fee_type' => '1',
            'input_charset' => 'UTF-8',
            'partner' => $this->wechat_config['partner_id'],
             );
        $parameter = array_merge($parameter, $base);
        $array = array();
        foreach ($parameter as $k => $v) {
            if (in_array($k, $filter)) {
                $array[$k] = $v;
            }
        }
        ksort($array);
        reset($array);
        $signPars = ''; 
        foreach ($array as $k => $v) {
            $signPars .= $k."=".$v."&";
        }
        $sign = strtoupper(md5($signPars.'key='.$this->wechat_config['partner_key']));
        $signPars = '';
        foreach ($array as $k => $v) {
            $signPars .= strtolower($k) . "=" . urlencode($v) . "&";
        }        
        
        return $signPars . 'sign=' . $sign;
    }
    //查询订单结果
    //@param out_trade_no string 订单号
    public function getQueryOrder($out_trade_no){

        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
         $data = array(
            'appid'        =>    $this->config['appid'],
            'mch_id'    =>       $this->config['mch_id'],
            'out_trade_no'    =>    $out_trade_no,
            'nonce_str'            => $this->createNoncestr()
            );
        $sign = $this->getSign($data);
        $data['sign'] = $sign;
        $xml = $this->arrayToXml($data);
        $response = $this->postXmlCurl($xml, $url); 
        $response = $this->XmlToArray($response);
        return $response;
    }
    /**
     * 接收支付结果通知参数
     * @return Object 返回结果对象；
     */
    public function getNotifyData() {
        $postXml = $GLOBALS["HTTP_RAW_POST_DATA"];  //接受通知参数；
        if (empty($postXml)) {
            return false;
        }
        $postArr = $this->xmlToArray($postXml);
        if (!empty($postArr['return_code'])) {
            if ($postArr['return_code'] == 'FAIL') {
                return false;
            }
        }
        return $postArr;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/5/26
 * Time: 14:28
 */

namespace app\pc\controller;


use think\Log;
use think\Session;

class Wechat extends Common
{
    private $app_id = 'wxe71d7cb038a75be3';
    private $mch_id = '1497847032';
    private $key = 'gsk4lkds9sdadsm7m3mhnn23h43jjk23';
    private $app_secret = '15a06ff355889b6cec8dd9039696355b';
    private $pc_app_id = 'wx84ea8a0b6433aaf8';
    private $pc_app_secret = '2af45576c12fc40ffe4424d9ad2af1c4';

    /**
     * 微信统一下单
     * @param int     $fee          价格 单位: 元
     * @param string  $type         交易类型
     * @param string  $notify_url   回调地址
     * @param null    $out_trade_no 商品订单编号
     * @param string  $body         商品描述
     * @return mixed
     */
    public function jsWechat($fee, $type, $notify_url, $out_trade_no = null, $body = '商品')
    {
        $post = array(
            'appid' => $this->app_id,
            'mch_id' => $this->mch_id,
            'nonce_str' => $this->nonceStr(),
            'body' => $body,
            'out_trade_no' => $out_trade_no,
            'total_fee' => $fee,
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'] == '::1' ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'],
            'notify_url' => $notify_url,
            'trade_type' => $type,
        );
        ksort($post);
        $sign = strtoupper(md5(urldecode(http_build_query($post) . '&key=' . $this->key)));
        $post['sign'] = $sign;
        $data = $this->arrayToXml($post);
        /** @var array $data */
        $return_data = $this->httpPost('https://api.mch.weixin.qq.com/pay/unifiedorder', $data, false);
        return json_decode(json_encode(simplexml_load_string($return_data, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * 随机32位字符串
     * @return string
     */
    private function nonceStr()
    {
        $result = '';
        $str = 'QWERTYUIOPASDFGHJKLZXVBNMqwertyuioplkjhgfdsamnbvcxz';
        for ($i = 0; $i < 32; $i++) {
            $result .= $str[rand(0, 48)];
        }
        return $result;
    }

    /**
     * 数组转xml格式
     * @param $arr
     * @return string
     */
    protected function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . $this->arrayToXml($val) . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 微信支付回调
     * @return void
     */
    public function notify()
    {
        // 获取支付结果callback
        $post_data = file_get_contents("php://input");
        Log::write($post_data);
        $post_data = json_decode(json_encode(simplexml_load_string($post_data, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $product = new Product();
        $product->operateAfterPay($post_data);
    }

    /**
     * 根据code获取access_token 等信息
     * @param $code
     * @return mixed
     */
    public function getAccessTokenByCode($code)
    {
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->pc_app_id}&secret={$this->pc_app_secret}&code={$code}&grant_type=authorization_code";
        $data = $this->httpGet($url);
        return json_decode($data, true);
    }

    /**
     * 登录二维码地址
     * @return string
     */
    public function getLoginQrCode()
    {
        $state = md5(rand(1, 10000));
        Session::set('login_state', $state);
        return "https://open.weixin.qq.com/connect/qrconnect?appid=wx84ea8a0b6433aaf8&redirect_uri=http%3a%2f%2fwww.shanshuiyinxiang.com%2fcallback.html&response_type=code&scope=snsapi_login&state={$state}#wechat_redirect";
    }

    /**
     * 获取用户信息
     * @param $access_token
     * @param $openid
     * @return mixed
     */
    public function getUserInfo($access_token, $openid)
    {
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $data = $this->httpGet($url);
        return json_decode($data, true);
    }


}
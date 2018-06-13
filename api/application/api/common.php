<?php
/**
 * 生成操作按钮
 * @param array $operate 操作按钮数组
 */
function showOperate($operate = [])
{
    if(empty($operate)){
        return '';
    }
    $option = <<<EOT
<div class="btn-group">
    <button class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
        操作 <span class="caret"></span>
    </button>
    <ul class="dropdown-menu">
EOT;

    foreach($operate as $key=>$vo){

        $option .= '<li><a href="'.$vo.'">'.$key.'</a></li>';
    }
    $option .= '</ul></div>';

    return $option;
}

/**
 * 将字符解析成数组
 * @param $str
 */
function parseParams($str)
{
    $arrParams = [];
    parse_str(html_entity_decode(urldecode($str)), $arrParams);
    return $arrParams;
}

/**
 * 子孙树 用于菜单整理
 * @param $param
 * @param int $pid
 */
function subTree($param, $pid = 0)
{
    static $res = [];
    foreach($param as $key=>$vo){

        if( $pid == $vo['pid'] ){
            $res[] = $vo;
            subTree($param, $vo['id']);
        }
    }

    return $res;
}

/**
 * 整理菜单住方法
 * @param $param
 * @return array
 */
function prepareMenu($param)
{
    $parent = []; //父类
    $child = [];  //子类

    foreach($param as $key=>$vo){

        if($vo['typeid'] == 0){
            $vo['href'] = '#';
            $parent[] = $vo;
        }else{
            $vo['href'] = url($vo['control_name'] .'/'. $vo['action_name']); //跳转地址
            $child[] = $vo;
        }
    }

    foreach($parent as $key=>$vo){
        foreach($child as $k=>$v){

            if($v['typeid'] == $vo['id']){
                $parent[$key]['child'][] = $v;
            }
        }
    }
    unset($child);

    return $parent;
}

/**
 * 解析备份sql文件
 * @param $file
 */
function analysisSql($file)
{
    // sql文件包含的sql语句数组
    $sqls = array ();
    $f = fopen ( $file, "rb" );
    // 创建表缓冲变量
    $create = '';
    while ( ! feof ( $f ) ) {
        // 读取每一行sql
        $line = fgets ( $f );
        // 如果包含空白行，则跳过
        if (trim ( $line ) == '') {
            continue;
        }
        // 如果结尾包含';'(即为一个完整的sql语句，这里是插入语句)，并且不包含'ENGINE='(即创建表的最后一句)，
        if (! preg_match ( '/;/', $line, $match ) || preg_match ( '/ENGINE=/', $line, $match )) {
            // 将本次sql语句与创建表sql连接存起来
            $create .= $line;
            // 如果包含了创建表的最后一句
            if (preg_match ( '/ENGINE=/', $create, $match )) {
                // 则将其合并到sql数组
                $sqls [] = $create;
                // 清空当前，准备下一个表的创建
                $create = '';
            }
            // 跳过本次
            continue;
        }

        $sqls [] = $line;
    }
    fclose ( $f );

    return $sqls;
}
/**
 * discuz!金典的加密函数原版      需要URL转码   urlencode ( );
 * @param string $string 明文 或 密文
 * @param string $operation DECODE表示解密,其它表示加密
 * @param string $key 密匙
 * @param int $expiry 密文有效期
 */
function authcode($string, $operation = 'ENCODE', $key = '', $expiry = 0) 
{
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    if($operation == 'DECODE') 
    {
        $string = str_replace('[a]','+',$string);
        $string = str_replace('[b]','&',$string);
        $string = str_replace('[c]','/',$string);
    }
    $ckey_length = 4;

    // 密匙
    $key = md5($key ? $key : \think\Config::get('auth_key')); // AUTH_KEY 项目配置的密钥

    // 密匙a会参与加解密
    $keya = md5(substr($key, 0, 16));
    // 密匙b会用来做数据完整性验证
    $keyb = md5(substr($key, 16, 16));
    // 密匙c用于变化生成的密文
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
    // 参与运算的密匙
    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    // 产生密匙簿
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 核心加解密部分
    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if($operation == 'DECODE') 
    {
        // substr($result, 0, 10) == 0 验证数据有效性
        // substr($result, 0, 10) - time() > 0 验证数据有效性
        // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
        // 验证数据有效性，请看未加密明文的格式
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16))
        {
            return substr($result, 26);
        } 
        else 
        {
            return '';
        }
    }
    else 
    {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        $ustr = $keyc.str_replace('=', '', base64_encode($result));
        $ustr = str_replace('+','[a]',$ustr);
        $ustr = str_replace('&','[b]',$ustr);
        $ustr = str_replace('/','[c]',$ustr);
        return $ustr;
    }
    
}
/**
 * 获取指定长度的随机字符串
 * @param int $len 字符串长度
 * @return string 返回指定长度字符串
 */
function getRandCode($len=6){
    $rand_arr=array_merge(range('a', 'z'),range('A', 'Z'),range('0', '9'));
    shuffle($rand_arr);//打乱顺序
    $rand=array_slice($rand_arr, 0,$len);
    return implode('', $rand);
}
/**
 * 请求数据到短信接口，检查环境是否 开启 curl init。
 * @param int $len 字符串长度
 * @return string 返回指定长度字符串
 */
function Post($curlPost,$url){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
    $return_str = curl_exec($curl);
    curl_close($curl);
    return $return_str;
}
/**
 * 将 xml数据转换为数组格式。
 * @param int $len 字符串长度
 * @return string 返回指定长度字符串
 */
function xml_to_array($xml){
    $reg = "/<(\w+)[^-->]*>([\\x00-\\xFF]*)<\\/\\1>/";
    if(preg_match_all($reg, $xml, $matches)){
        $count = count($matches[0]);
        for($i = 0; $i < $count; $i++){
            $subxml= $matches[2][$i];
            $key = $matches[1][$i];
            if(preg_match( $reg, $subxml )){
                $arr[$key] = xml_to_array( $subxml );
            }else{
                $arr[$key] = $subxml;
            }
        }
    }
    return $arr;
}
/**
 * 函数返回随机整数。
 * @param int $len 字符串长度
 * @return string 返回指定长度字符串
 */
function random($length = 6 , $numeric = 0) {
    PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
    if($numeric) {
        $hash = sprintf('%0'.$length.'d', mt_rand(0, pow(10, $length) - 1));
    } else {
        $hash = '';
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
        $max = strlen($chars) - 1;
        for($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
    }
    return $hash;
}
/**
 * 获取指定长度的随机数字
 * @param int $len 字符串长度
 * @return string 返回指定长度字符串
 */
function get_rand_num($len=6){
    $rand_arr = range('0', '9');
    shuffle($rand_arr);//打乱顺序
    $rand=array_slice($rand_arr, 0,$len);
    return implode('', $rand);
}
/**
 * 获取客户端ip
 * @AuthorHTL
 * @DateTime  2016-08-13T10:26:55+0800
 * @return    [type]                   [description]
 */
function client_ip()
{
    $keys = array ('HTTP_X_FORWARDED_FOR', 'CLIENT_IP', 'REMOTE_ADDR' );
    foreach ( $keys as $key )
    {
        if (isset($_SERVER[$key]))
        {
            return $_SERVER[$key];
        }
    }
    return null;
}
/**
 * @explain
 * 用于获取用户openid
 **/
function getOpenId($appid,$appsecret,$code)
{
    $access_token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $appsecret . "&code=" . $code . "&grant_type=authorization_code";
    $access_token_json = https_request($access_token_url);
    $access_token_array = json_decode($access_token_json, TRUE);
    return $access_token_array;
}
/**
 * @explain
 * 通过code获取用户openid以及用户的微信号信息
 * @return
 * @remark
 * 获取到用户的openid之后可以判断用户是否有数据，可以直接跳过获取access_token,也可以继续获取access_token
 * access_token每日获取次数是有限制的，access_token有时间限制，可以存储到数据库7200s. 7200s后access_token失效
 **/
function getUserInfo($access_token,$openid)
{

    $userinfo_url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=" . $openid."&lang=zh_CN";
    $userinfo_json = $this->https_request($userinfo_url);
    $userinfo_array = json_decode($userinfo_json, TRUE);
    return $userinfo_array;
}
/**
 * @explain
 * 发送http请求，并返回数据
 **/
function https_request($url, $data = null)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (!empty($data)) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}


function download($name, $url){
 if(!is_dir(dirname($name))){
    mkdir(dirname($name));
   }
$str = file_get_contents($url);
file_put_contents($name, $str);
//输出一些东西,要不窗口一直黑着,感觉怪怪的
echo strlen($str);
 echo "\n";
}


function createParam ($paramArr,$showapi_secret) {
    $paraStr = "";
    $signStr = "";
    ksort($paramArr);
    foreach ($paramArr as $key => $val) {
        if ($key != '' && $val != '') {
            $signStr .= $key.$val;
            $paraStr .= $key.'='.urlencode($val).'&';
        }
    }
    $signStr .= $showapi_secret;//排好序的参数加上secret,进行md5
    $sign = strtolower(md5($signStr));
    $paraStr .= 'showapi_sign='.$sign;//将md5后的值作为参数,便于服务器的效验
    echo "排好序的参数:".$signStr."<br>\r\n";
    return $paraStr;
}

// 转化为秒数
function transferSecond($str)
{   

    $arr = explode(':', $str);
    $hour = $arr[0];

    $minute = $arr[1];

    $second = $arr[2];
    
    return (3600 * $hour) + (60 * $minute) + $second;
}


// 处理时间
function handleTime($date)
{
            // 处理时间
            $time = time() - strtotime($date);
            $time += 126;
            // 判断时间是否超过1天
            if (86400 <= $time)
            {
                $time_res = substr($date, 5, 11);
            }
            else // 小于1天 
            {
                // 判断时间是否超过1小时
                if (3600 <= $time)
                {
                    $hour = floor($time / 3600);
                    $time_res = $hour . '小时前';
                }
                else // 小于1小时
                {
                    // 判断时间是否超过1分钟
                    if (60 <= $time)
                    {
                        $minute = floor($time / 60);
                        $time_res = $minute . '分钟前';
                    }
                    else // 小于1分钟
                    {   
                        $time_res = '刚刚';
                    }
                }
            }        
            return $time_res;
}

// 得到唯一id
function getUuid()
{
    return md5(uniqid() . mt_rand(0, 99999) . microtime(true));
}

// 处理微信返回的支付完成时间
function handleWxTime($time)
{

    // 20141030133525  
    $year = substr($time, 0, 4);
    $month = substr($time, 4, 2);
    $day = substr($time, 6, 2);                
    $hour = substr($time, 8, 2);               
    $minute = substr($time, 10, 2);                
    $second = substr($time, 12, 2);             

    $time_str = $year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $minute . ':' . $day;
    
    return strtotime($time_str);
}

// 处理安卓和ios传的地址不同
function handleAddress($address)
{
    $new_address = $address;
    if (11 == mb_strlen($address))
    {
        $new_address = mb_substr($address, 0, 3, 'utf-8') . mb_substr($address, 4, 3, 'utf-8') . mb_substr($address, 8, 3, 'utf-8') . mb_substr($address, 11, 100, 'utf-8');
    }
    
    return $new_address;
}

// 得到路径和文件名
function getDestination($type)
{   
    $date = date('Y-m-d');
    if (1 == $type) // 乐园活动的图片
    {
        $filename = 'uploads/paradise/activity/' . $date;
        if (file_exists($filename))
        {
            return $filename . '/' . getUuid() . '.jpg';
        }
        else
        {
            mkdir($filename);
            return $filename . '/' . getUuid() . '.jpg';
        }
    }
    else if (2 == $type) // 咕咕的图片
    {
        $filename = 'uploads/gugu/img/' . $date;
        if (file_exists($filename))
        {
            return $filename . '/' . getUuid() . '.jpg';
        }
        else
        {
            mkdir($filename);
            return $filename . '/' . getUuid() . '.jpg';
        }
    } 
    else if (3 == $type) // 分享的静态页面
    {
        $filename = 'static/share/' . $date;
        if (file_exists($filename)) 
        {
            return $filename . '/' . getUuid() . '.html';
        }
        else
        {
            mkdir($filename);
            return $filename . '/' . getUuid() . '.html';
        }
    }      
}
<?php
use think\log;
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
 * 获取栏目名称
 * @AuthorHTL naka1205
 * @DateTime  2016-08-28T15:38:21+0800
 * @param     [type]                   $id    [description]
 * @param     string                   $tab   [description]
 * @param     string                   $field [description]
 * @return    [type]                          [description]
 */
function get_cat_name($id,$tab='article_cate',$field='name')
{

    $data = \think\Db::name($tab)->where('id',$id)->value($field);
    if($tab == 'role'){
        $name = '超级管理员';
    }else{
        $name = '无';
    }
    return $data ? $data : $name;
}

function get_spot_name($id) {
    $spot = array(
      '10000'=>'石燕湖',
      '10001'=>'湄江',
      '10002'=>'石牛寨'
    );
    $res = isset($spot[$id])?$spot[$id]:'';
    return $res;
}

/**
 * 获取上级地区名称
 * @AuthorHTL
 * @DateTime  2016-09-08T15:54:07+0800
 * @param     [type]                   $id [description]
 * @return    [type]                       [description]
 */
function get_shop_region_name($id)
{
    $name = '地区';
    if ($id > 0) {
        $name = \think\Db::name('mall_shop_region')->where('id',$id)->value('name');
    }
    return $name;
}
/**
 * 获取所属酒店名称
 * @AuthorHTL
 * @DateTime  2016-09-08T15:54:07+0800
 * @param     [type]                   $id [description]
 * @return    [type]                       [description]
 */
function get_hotel_name($id)
{
    $name = '酒店名称';
    if($id){
        $name = \think\Db::name('mall_shop_hotel')->where('id',$id)->value('title');
    }
    return $name;
}
/**
 * 根据栏目ID获取子栏目
 * @param array $arr
 * @param int $cat_id
 * @return array 返回子栏目ID
 */
function child_merge($arr,$id= 0){
    $data=array();
    foreach ($arr as $k =>$v){      
        if ($v['parent_id'] == $id){
            $data[$k] = $v;
            foreach ($arr as $child){
                if($child['parent_id'] == $v['id']){
                    $data[$k]['child'][] = $child;
                }
            }
        }
    }
    return $data;
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
/* 
 * 
 *中文字符串切割  
 *  
 */
function mbStrSplit ($string, $len=1) {
    $start = 0;
    $strlen = mb_strlen($string);
    while ($strlen) {
        $array[] = mb_substr($string,$start,$len,"utf8");
        $string = mb_substr($string, $len, $strlen,"utf8");
        $strlen = mb_strlen($string);
    }
    return $array;
}



// 得到唯一id
function getUuid()
{
    return md5(uniqid() . mt_rand(0, 99999) . microtime(true));
}

// 得到路径和文件名
function getDestination($type)
{   
    $date = date('Y-m-d');
    if (1 == $type)
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
}

// 处理产品兑换结束时间，将字符串转换为时间戳
function handleEndTime($time)
{
    return strtotime($time) + 86400;
}

//请求数据到短信接口，检查环境是否 开启 curl init。
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

/**
 * @explain
 * 发送http请求，并返回数据
 **/
function get_https_request($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
   // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
   // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}



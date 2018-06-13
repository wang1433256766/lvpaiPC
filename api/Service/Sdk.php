<?php
namespace com\alipay\Service;

class Sdk
{


	public function __construct($options)
	{
		$this->token = isset($options['token'])?$options['token']:'';
		$this->encodingAesKey = isset($options['encodingaeskey'])?$options['encodingaeskey']:'';
		$this->appid = isset($options['appid'])?$options['appid']:'';
		$this->appsecret = isset($options['appsecret'])?$options['appsecret']:'';
		$this->debug = isset($options['debug'])?$options['debug']:false;
		$this->logcallback = isset($options['logcallback'])?$options['logcallback']:false;
	}

	/**
	 * For weixin server validation
	 */
	private function checkSignature($str='')
	{

	}

	/**
	 * For weixin server validation
	 * @param bool $return 是否返回
	 */
	public function valid($return=false)
    {

    }

	/**
	 * 设置发送消息
	 * @param array $msg 消息数组
	 * @param bool $append 是否在原消息数组追加
	 */
    public function Message($msg = '',$append = false){

    }

    /**
     * 日志记录，可被重载。
     * @param mixed $log 输入日志
     * @return mixed
     */
    protected function log($log){

    }


	public static function xmlSafeStr($str)
	{
		return '<![CDATA['.preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/",'',$str).']]>';
	}

	/**
	 * 数据XML编码
	 * @param mixed $data 数据
	 * @return string
	 */
	public static function data_to_xml($data) {
	    $xml = '';
	    foreach ($data as $key => $val) {
	        is_numeric($key) && $key = "item id=\"$key\"";
	        $xml    .=  "<$key>";
	        $xml    .=  ( is_array($val) || is_object($val)) ? self::data_to_xml($val)  : self::xmlSafeStr($val);
	        list($key, ) = explode(' ', $key);
	        $xml    .=  "</$key>";
	    }
	    return $xml;
	}

	/**
	 * XML编码
	 * @param mixed $data 数据
	 * @param string $root 根节点名
	 * @param string $item 数字索引的子节点名
	 * @param string $attr 根节点属性
	 * @param string $id   数字索引子节点key转换的属性名
	 * @param string $encoding 数据编码
	 * @return string
	*/
	public function xml_encode($data, $root='xml', $item='item', $attr='', $id='id', $encoding='utf-8') {
	    if(is_array($attr)){
	        $_attr = array();
	        foreach ($attr as $key => $value) {
	            $_attr[] = "{$key}=\"{$value}\"";
	        }
	        $attr = implode(' ', $_attr);
	    }
	    $attr   = trim($attr);
	    $attr   = empty($attr) ? '' : " {$attr}";
	    $xml   = "<{$root}{$attr}>";
	    $xml   .= self::data_to_xml($data, $item, $id);
	    $xml   .= "</{$root}>";
	    return $xml;
	}

	/**
	 * 过滤文字回复\r\n换行符
	 * @param string $text
	 * @return string|mixed
	 */
	private function _auto_text_filter($text) {
		if (!$this->_text_filter) return $text;
		return str_replace("\r\n", "\n", $text);
	}

	/**
	 * 设置回复消息
	 * Example: $obj->text('hello')->reply();
	 * @param string $text
	 */
	public function text($text='')
	{
		$FuncFlag = $this->_funcflag ? 1 : 0;
		$msg = array(
			'ToUserName' => $this->getRevFrom(),
			'FromUserName'=>$this->getRevTo(),
			'MsgType'=>self::MSGTYPE_TEXT,
			'Content'=>$this->_auto_text_filter($text),
			'CreateTime'=>time(),
			'FuncFlag'=>$FuncFlag
		);
		$this->Message($msg);
		return $this;
	}
	/**
	 * 设置回复消息
	 * Example: $obj->image('media_id')->reply();
	 * @param string $mediaid
	 */
	public function image($mediaid='')
	{
		$FuncFlag = $this->_funcflag ? 1 : 0;
		$msg = array(
			'ToUserName' => $this->getRevFrom(),
			'FromUserName'=>$this->getRevTo(),
			'MsgType'=>self::MSGTYPE_IMAGE,
			'Image'=>array('MediaId'=>$mediaid),
			'CreateTime'=>time(),
			'FuncFlag'=>$FuncFlag
		);
		$this->Message($msg);
		return $this;
	}

	/**
	 * 设置回复消息
	 * Example: $obj->voice('media_id')->reply();
	 * @param string $mediaid
	 */
	public function voice($mediaid='')
	{
		$FuncFlag = $this->_funcflag ? 1 : 0;
		$msg = array(
			'ToUserName' => $this->getRevFrom(),
			'FromUserName'=>$this->getRevTo(),
			'MsgType'=>self::MSGTYPE_VOICE,
			'Voice'=>array('MediaId'=>$mediaid),
			'CreateTime'=>time(),
			'FuncFlag'=>$FuncFlag
		);
		$this->Message($msg);
		return $this;
	}

	/**
	 * 设置回复消息
	 * Example: $obj->video('media_id','title','description')->reply();
	 * @param string $mediaid
	 */
	public function video($mediaid='',$title='',$description='')
	{
		$FuncFlag = $this->_funcflag ? 1 : 0;
		$msg = array(
			'ToUserName' => $this->getRevFrom(),
			'FromUserName'=>$this->getRevTo(),
			'MsgType'=>self::MSGTYPE_VIDEO,
			'Video'=>array(
			        'MediaId'=>$mediaid,
			        'Title'=>$title,
			        'Description'=>$description
			),
			'CreateTime'=>time(),
			'FuncFlag'=>$FuncFlag
		);
		$this->Message($msg);
		return $this;
	}

	/**
	 * 设置回复音乐
	 * @param string $title
	 * @param string $desc
	 * @param string $musicurl
	 * @param string $hgmusicurl
	 * @param string $thumbmediaid 音乐图片缩略图的媒体id，非必须
	 */
	public function music($title,$desc,$musicurl,$hgmusicurl='',$thumbmediaid='') {
		$FuncFlag = $this->_funcflag ? 1 : 0;
		$msg = array(
			'ToUserName' => $this->getRevFrom(),
			'FromUserName'=>$this->getRevTo(),
			'CreateTime'=>time(),
			'MsgType'=>self::MSGTYPE_MUSIC,
			'Music'=>array(
				'Title'=>$title,
				'Description'=>$desc,
				'MusicUrl'=>$musicurl,
				'HQMusicUrl'=>$hgmusicurl
			),
			'FuncFlag'=>$FuncFlag
		);
		if ($thumbmediaid) {
			$msg['Music']['ThumbMediaId'] = $thumbmediaid;
		}
		$this->Message($msg);
		return $this;
	}

	/**
	 * 设置回复图文
	 * @param array $newsData
	 * 数组结构:
	 *  array(
	 *  	"0"=>array(
	 *  		'Title'=>'msg title',
	 *  		'Description'=>'summary text',
	 *  		'PicUrl'=>'http://www.domain.com/1.jpg',
	 *  		'Url'=>'http://www.domain.com/1.html'
	 *  	),
	 *  	"1"=>....
	 *  )
	 */
	public function news($newsData=array())
	{
		$FuncFlag = $this->_funcflag ? 1 : 0;
		$count = count($newsData);

		$msg = array(
			'ToUserName' => $this->getRevFrom(),
			'FromUserName'=>$this->getRevTo(),
			'MsgType'=>self::MSGTYPE_NEWS,
			'CreateTime'=>time(),
			'ArticleCount'=>$count,
			'Articles'=>$newsData,
			'FuncFlag'=>$FuncFlag
		);
		$this->Message($msg);
		return $this;
	}

	/**
	 *
	 * 回复微信服务器, 此函数支持链式操作
	 * Example: $this->text('msg tips')->reply();
	 * @param string $msg 要发送的信息, 默认取$this->_msg
	 * @param bool $return 是否返回信息而不抛出到浏览器 默认:否
	 */
	public function reply($msg=array(),$return = false)
	{
		if (empty($msg)) {
		    if (empty($this->_msg))   //防止不先设置回复内容，直接调用reply方法导致异常
		        return false;
			$msg = $this->_msg;
		}
		$xmldata=  $this->xml_encode($msg);
		$this->log($xmldata);
		if ($this->encrypt_type == 'aes') { //如果来源消息为加密方式
		    $pc = new \com\wechat\Prpcrypt($this->encodingAesKey);
		    $array = $pc->encrypt($xmldata, $this->appid);
		    $ret = $array[0];
		    if ($ret != 0) {
		        $this->log('encrypt err!');
		        return false;
		    }
		    $timestamp = time();
		    $nonce = rand(77,999)*rand(605,888)*rand(11,99);
		    $encrypt = $array[1];
		    $tmpArr = array($this->token, $timestamp, $nonce,$encrypt);//比普通公众平台多了一个加密的密文
		    sort($tmpArr, SORT_STRING);
		    $signature = implode($tmpArr);
		    $signature = sha1($signature);
		    $xmldata = $this->generate($encrypt, $signature, $timestamp, $nonce);
		    $this->log($xmldata);
		}
		if ($return)
			return $xmldata;
		else
			echo $xmldata;
	}

    /**
     * xml格式加密，仅请求为加密方式时再用
     */
	private function generate($encrypt, $signature, $timestamp, $nonce)
	{
	    //格式化加密信息
	    $format = "<xml>
<Encrypt><![CDATA[%s]]></Encrypt>
<MsgSignature><![CDATA[%s]]></MsgSignature>
<TimeStamp>%s</TimeStamp>
<Nonce><![CDATA[%s]]></Nonce>
</xml>";
	    return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
	}

	/**
	 * GET 请求
	 * @param string $url
	 */
	private function http_get($url){
		$oCurl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}else{
			return false;
		}
	}

	/**
	 * POST 请求
	 * @param string $url
	 * @param array $param
	 * @param boolean $post_file 是否文件上传
	 * @return string content
	 */
	private function http_post($url,$param,$post_file=false){
		$oCurl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		if (is_string($param) || $post_file) {
			
			if (isset($param['media'])){
				//$cfile = new \CURLFile(realpath($param['media']));
				$cfile = '@'.realpath($param['media']);
				$strPOST = array('media'=>$cfile);
			}else{
				$strPOST = $param;
			}

		} else {
			$aPOST = array();
			foreach($param as $key=>$val){
				$aPOST[] = $key."=".urlencode($val);
			}
			$strPOST =  join("&", $aPOST);
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($oCurl, CURLOPT_POST,true);
		// if (isset($param['media'])){
		// 	curl_setopt($oCurl, CURLOPT_SAFE_UPLOAD, false);
		// }
		curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}else{
			return false;
		}
	}

	/**
	 * 设置缓存，按需重载
	 * @param string $cachename
	 * @param mixed $value
	 * @param int $expired
	 * @return boolean
	 */
	protected function setCache($cachename,$value,$expired){
		//TODO: set cache implementation
		return false;
	}

	/**
	 * 获取缓存，按需重载
	 * @param string $cachename
	 * @return mixed
	 */
	protected function getCache($cachename){
		//TODO: get cache implementation
		return false;
	}

	/**
	 * 清除缓存，按需重载
	 * @param string $cachename
	 * @return boolean
	 */
	protected function removeCache($cachename){
		//TODO: remove cache implementation
		return false;
	}


}
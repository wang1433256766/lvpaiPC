<?php

namespace app\mobile\controller;

use think\Controller;
use think\Db;
use think\Request;
use com\Wechat;
use com\PHPQRCode;
use think\Session;

/**
 * 前台基数控制器
 * @AuthorHTL naka1205
 * @DateTime  2016-05-29T22:04:12+0800
 */
class Base extends Controller
{
    public $options;
    public $open_id;
    public $access_token;
    public $wxuser;

    public function _initialize()
    {
        $this->options = \think\Config::get('weixin');
        if (!session('?member_id')) {
            $this->auth();
            $this->save();
            $member_id = session::get('member_id');
            $member = Db::name('mall_member')->where('id', $member_id)->find();
            if ($member) {
                if (!$member['mobile']) {
                    $this->redirect('/mobile/bind/bind.html');
                }
            }
        } else {
            $member_id = session::get('member_id');
            $member = Db::name('mall_member')->where('id', $member_id)->find();
            if ($member) {
                if (!$member['mobile']) {
                    $this->redirect('/mobile/bind/bind.html');
                }
            }
        }
    }

    public function save()
    {
        $where['openid'] = $this->open_id;
        $wechat_user = Db::name('mall_member')->where($where)->find();
        if (empty($wechat_user) && isset($this->wxuser['unionid']) && $this->wxuser['unionid'] != '') {
            $condition = ['unionid' => $this->wxuser['unionid']];
            $wechat_user = Db::name('mall_member')->where($condition)->find();
        }
        if (empty($wechat_user)) {
            if (isset($this->wxuser['headimg'])) {
                $member_data = array(
                    'nickname' => $this->wxuser['nickname'],
                    'sex' => $this->wxuser['sex'],
                    'province' => $this->wxuser['province'],
                    'headimg' => $this->wxuser['headimg'],
                    'city' => $this->wxuser['city'],
                    'remark' => $this->wxuser['remark'],
                    'status' => $this->wxuser['status'],
                    'add_time' => $this->wxuser['add_time'],
                    'openid' => $this->wxuser['openid'],
                    'login_ip' => client_ip(),
                    'login_num' => 1,
                    'login_time' => time(),
                    'unionid' => $this->wxuser['unionid']
                );
                $member_id = Db::name('mall_member')->insert($member_data);
            } else {
                $member_data = array(
                    'nickname' => $this->wxuser['nickname'],
                    'sex' => $this->wxuser['sex'],
                    'province' => $this->wxuser['province'],
                    'headimg' => $this->wxuser['headimgurl'],
                    'city' => $this->wxuser['city'],
                    'remark' => $this->wxuser['remark'],
                    'status' => $this->wxuser['status'],
                    'add_time' => $this->wxuser['add_time'],
                    'openid' => $this->wxuser['openid'],
                    'login_ip' => client_ip(),
                    'login_num' => 1,
                    'login_time' => time(),
                    'unionid' => $this->wxuser['unionid']
                );
                $member_id = Db::name('mall_member')->insert($member_data);
            }
        } else {
            if (isset($this->wxuser['headimg'])) {
                $wechat_data = array(
                    'nickname' => $this->wxuser['nickname'],
                    'sex' => $this->wxuser['sex'],
                    'province' => $this->wxuser['province'],
                    'city' => $this->wxuser['city'],
                    'headimg' => $this->wxuser['headimg'],
                    'remark' => $this->wxuser['remark'],
                    'status' => $this->wxuser['status'],
                    'last_login_time' => $this->wxuser['add_time'],
                    'unionid' => $this->wxuser['unionid'],
                    'openid' => $this->wxuser['openid'],
                );
                Db::name('mall_member')->where("id", $wechat_user['id'])->update($wechat_data);
                Db::name('mall_member')->where('id', $wechat_user['id'])->setInc('login_num');
            } else {
                $wechat_data = array(
                    'nickname' => $this->wxuser['nickname'],
                    'sex' => $this->wxuser['sex'],
                    'province' => $this->wxuser['province'],
                    'city' => $this->wxuser['city'],
                    'headimg' => $this->wxuser['headimgurl'],
                    'remark' => $this->wxuser['remark'],
                    'status' => $this->wxuser['status'],
                    'last_login_time' => $this->wxuser['add_time'],
                    'unionid' => $this->wxuser['unionid'],
                    'openid' => $this->wxuser['openid'],
                );
                Db::name('mall_member')->where("id", $wechat_user['id'])->update($wechat_data);
                Db::name('mall_member')->where('id', $wechat_user['id'])->setInc('login_num');
            }
        }
        $member = Db::name('mall_member')->field('id,type')->where('openid', $this->open_id)->find();
        session('member_id', $member['id']);
        session('type', $member['type']);
    }

    public function auth()
    {
        $scope = 'snsapi_base';
        $code = Request::instance()->param('code');
        /*if (!$code && session('?openid')) {
            if (!$this->wxuser) $this->wxuser = session('wxuser');
            $this->open_id = session('openid');
            return $this->open_id;
        } else {*/
            $we_obj = new Wechat($this->options);
            if ($code) {
                $json = $we_obj->getOauthAccessToken();
                if (!$json) {
                    session('wx_redirect', null);
                    die('获取用户授权失败，请重新确认');
                }
                $this->open_id = $json["openid"];
                session('openid', $json["openid"]);
                $access_token = $json['access_token'];
                $userinfo = $we_obj->getUserInfo($this->open_id);
                if ($userinfo && !empty($userinfo['nickname'])) {
                    $this->wxuser = array(
                        'openid' => $this->open_id,
                        'nickname' => preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $userinfo['nickname']),
                        'province' => preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $userinfo['province']),
                        'city' => preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $userinfo['city']),
                        'country' => preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $userinfo['country']),
                        'headimg' => $userinfo['headimgurl'],
                        'sex' => intval($userinfo['sex']),
                        'remark' => $userinfo['remark'],
                        'status' => 1,
                        'add_time' => time(),
                        'unionid' => $this->getUnionId($this->open_id)
                    );
                } elseif (strstr($json['scope'], 'snsapi_userinfo') !== false) {
                    $userinfo = $we_obj->getOauthUserinfo($access_token, $this->open_id);
                    if ($userinfo && !empty($userinfo['nickname'])) {
                        $this->wxuser = array(
                            'openid' => $this->open_id,
                            'nickname' => preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $userinfo['nickname']),
                            'province' => preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $userinfo['province']),
                            'city' => preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $userinfo['city']),
                            'country' => preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $userinfo['country']),
                            'headimgurl' => $userinfo['headimgurl'],
                            'remark' => '',
                            'sex' => intval($userinfo['sex']),
                            'status' => 1,
                            'add_time' => time(),
                            'unionid' => $this->getUnionId($this->open_id)
                        );
                    } else {
                        return $this->open_id;
                    }
                }
                if ($this->wxuser) {
                    session('wxuser', $this->wxuser);
                    session('openid', $json["openid"]);
                    session('wx_redirect', null);
                    return $this->open_id;
                }
                $scope = 'snsapi_userinfo';
            }
            if ($scope == 'snsapi_base') {
                $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                session('wx_redirect', $url);
            } else $url = session('wx_redirect');
            if (!$url) {
                session('wx_redirect', null);
                die('获取用户授权失败');
            }
            $oauth_url = $we_obj->getOauthRedirect($url, "wxbase", $scope);
            $this->redirect($oauth_url, 302);
//        }


    }

    /**
     * 生成海报
     * @AuthorHTL
     * @DateTime  2016-07-01T17:09:41+0800
     * @return    [type]                   [description]
     */
    public function poster($member_id)
    {
        set_time_limit(0);
        \think\Loader::import('PHPQRCode', EXTEND_PATH);
        //$member_id = session::get('member_id');

        $ac = authcode($member_id);

        $url = "http://lvpai.zhonghuilv.net/mobile/mall/index/ac/$ac";

        $path_nama = date('Ymdhis', time()) . getRandCode(6);
        $time_day = date('Ymd');
        $QR = PHPQRCode::get($url, $path_nama);

        $QR = "./uploads/qrcode/$time_day/$path_nama.png";//已经生成的原始二维码图
        $pic = './static/mobile/images/poster.jpg';

        // $this->open_id = $this->weixin->getRevFrom();
        // $member_info = \think\Db::name('wechat_user')->where('open_id',$this->open_id)->find();
        // $QR_info = $this->weixin->getQRCode($member_info['member_id']);

        // 	$QR = $this->weixin->getQRUrl($QR_info['ticket']);
        // 	if (empty($QR)) {
        // 		return;
        // 	}
        // $headimgurl = str_replace('/0', '/64', $member_info['headimgurl']);
        // $logo = saveImg($headimgurl,'member');
        // $logo = str_replace('/upload', './upload',$logo);

        //$logo = $member_info['headimgurl'];
        // if (empty($logo)) {
        //$logo = './upload/temp/logo.jpg';
        // }


        //$logo = "http://wx.qlogo.cn/mmopen/7xUXKuhefJL5amD3PHBgxfuPzw0BYLOArgTgBezw6qs0jSQJKjFbqud3ib0Oca35nia8ETX22rFibicPmibG5ibPIgtg/64";
        $QR = imagecreatefromstring(file_get_contents($QR));
        $pic = imagecreatefromstring(file_get_contents($pic));

        $QR_width = imagesx($QR);//二维码图片宽度
        $QR_height = imagesy($QR);//二维码图片高度
        $pic_width = imagesx($pic);//海报图片宽度
        $pic_height = imagesy($pic);//海报图片高度

        //重新组合图片并调整大小
        //粘贴二维码
        imagecopyresampled($pic, $QR, 370, 990, 0, 0, 230, 230, $QR_width, $QR_height);

        //粘贴头像
        //$logo = imagecreatefromstring(file_get_contents($logo));

        //$logo_width = imagesx($logo);//头像图片宽度
        //$logo_height = imagesy($logo);//头像图片高度
        //imagecopyresampled($pic,$logo, 20, 680, 0, 0,60,60,$logo_width,$logo_height);
        //写入文本字体
        /*$ttfPath = './upload/temp/1.ttf';
        $text = '一起加入我们的快乐之家！';
        $color = imagecolorallocate($pic,0,0,0);

        $len = mb_strlen($text, 'utf-8') - 1;
        for ($i = 0; $i < $len; $i++) {
            $code[$i] = iconv_substr($text, $i, 1, 'utf-8');
            imagettftext($pic, 16, 0, 140 + 16 * ($i + 1) * 1.5, 720, $color, $ttfPath, $code[$i]);
        }*/

        //输出图片
        $poster_path = "./upload/mobile/" . date('Y-m-d') . '/';
        if (!is_dir($poster_path)) {
            mkdir($poster_path);
        }
        $poster_file = $poster_path . date('Ymdhis') . getRandCode(6) . ".jpg";
        imagejpeg($pic, $poster_file);

        return $poster_file;

        /*$data = array("media" => $poster_file);
        $info = $this->weixin->uploadMedia($data,'image');
        $this->media_id = $info['media_id'];*/

    }

    /**
     * 生成申请分销的海报
     * @AuthorHTL
     * @DateTime  2016-07-01T17:09:41+0800
     * @return    [type]                   [description]
     */
    public function fx_poster($member_id)
    {
        set_time_limit(0);
        \think\Loader::import('PHPQRCode', EXTEND_PATH);
        //$member_id = session::get('member_id');

        $ac = authcode($member_id);

        $url = "http://lvpai.zhonghuilv.net/mobile/member/member_bus/ac/$ac";

        $path_nama = date('Ymdhis', time()) . getRandCode(6);
        $time_day = date('Ymd');
        $QR = PHPQRCode::get($url, $path_nama);

        $QR = "./uploads/qrcode/$time_day/$path_nama.png";//已经生成的原始二维码图
        $pic = './static/mobile/images/second_poster.jpg';

        // $this->open_id = $this->weixin->getRevFrom();
        // $member_info = \think\Db::name('wechat_user')->where('open_id',$this->open_id)->find();
        // $QR_info = $this->weixin->getQRCode($member_info['member_id']);

        // 	$QR = $this->weixin->getQRUrl($QR_info['ticket']);
        // 	if (empty($QR)) {
        // 		return;
        // 	}
        // $headimgurl = str_replace('/0', '/64', $member_info['headimgurl']);
        // $logo = saveImg($headimgurl,'member');
        // $logo = str_replace('/upload', './upload',$logo);

        //$logo = $member_info['headimgurl'];
        // if (empty($logo)) {
        //$logo = './upload/temp/logo.jpg';
        // }


        //$logo = "http://wx.qlogo.cn/mmopen/7xUXKuhefJL5amD3PHBgxfuPzw0BYLOArgTgBezw6qs0jSQJKjFbqud3ib0Oca35nia8ETX22rFibicPmibG5ibPIgtg/64";
        $QR = imagecreatefromstring(file_get_contents($QR));
        $pic = imagecreatefromstring(file_get_contents($pic));

        $QR_width = imagesx($QR);//二维码图片宽度
        $QR_height = imagesy($QR);//二维码图片高度
        $pic_width = imagesx($pic);//海报图片宽度
        $pic_height = imagesy($pic);//海报图片高度

        //重新组合图片并调整大小
        //粘贴二维码
        imagecopyresampled($pic, $QR, 400, 990, 0, 0, 230, 230, $QR_width, $QR_height);

        //粘贴头像
        //$logo = imagecreatefromstring(file_get_contents($logo));

        //$logo_width = imagesx($logo);//头像图片宽度
        //$logo_height = imagesy($logo);//头像图片高度
        //imagecopyresampled($pic,$logo, 20, 680, 0, 0,60,60,$logo_width,$logo_height);
        //写入文本字体
        /*$ttfPath = './upload/temp/1.ttf';
        $text = '一起加入我们的快乐之家！';
        $color = imagecolorallocate($pic,0,0,0);

        $len = mb_strlen($text, 'utf-8') - 1;
        for ($i = 0; $i < $len; $i++) {
            $code[$i] = iconv_substr($text, $i, 1, 'utf-8');
            imagettftext($pic, 16, 0, 140 + 16 * ($i + 1) * 1.5, 720, $color, $ttfPath, $code[$i]);
        }*/

        //输出图片
        $poster_path = "./upload/mobile/" . date('Y-m-d') . '/';
        if (!is_dir($poster_path)) {
            mkdir($poster_path);
        }
        $poster_file = $poster_path . date('Ymdhis') . getRandCode(6) . ".jpg";
        imagejpeg($pic, $poster_file);

        return $poster_file;

        /*$data = array("media" => $poster_file);
        $info = $this->weixin->uploadMedia($data,'image');
        $this->media_id = $info['media_id'];*/

    }

    public function getUnionId($openid)
    {
        $return_data = $this->httpGet("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxe71d7cb038a75be3&secret=15a06ff355889b6cec8dd9039696355b");
        $data = json_decode($return_data, true);
        if (!isset($data['errcode'])) $access_token = $data['access_token'];
        else return '';
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $res = $this->httpGet($url);
        $res = json_decode($res, true);
        return isset($res['unionid']) ? $res['unionid'] : '';
    }

    protected function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($curl);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $info = substr($data, $headerSize);
        curl_close($curl);
        return $info;
    }


}
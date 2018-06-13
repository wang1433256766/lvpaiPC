<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/6/6
 * Time: 10:44
 */

namespace app\pc\controller;


use think\Db;

class Test
{
    private $app_id = 'wxe71d7cb038a75be3';
    private $app_secret = '15a06ff355889b6cec8dd9039696355b';

    /**
     * 获取access_token
     * @return mixed
     */
    public function getAccessToken()
    {
        $return_data = $this->httpGet("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this->app_id . "&secret=" . $this->app_secret);
        $data = json_decode($return_data, true);
        if (isset($data['errcode'])) {
            $this->ajaxReturn(10001, '', $data['errmsg']);
        } else return $data['access_token'];
    }

    /**
     * 获取用户的unionId
     * @param $openid
     * @return bool|string
     */
    public function getUnionId($openid)
    {
        /*$token_time = session('?token_time') ? session('token_time') : 0;
        if (session('?token') && time() < $token_time) {
            $token = session('token');
        } else {
            $token = $this->getAccessToken();
            session('token_time', time() + 3600);
            session('token', $token);
        }*/
        $token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token={$token}";
        $return_data = $this->httpPost($url, $openid);
        if (isset($return_data['errcode'])) $this->ajaxReturn(10001, '', $return_data['errmsg']);
        return json_decode($return_data, true);
    }

    public function updateUnionid()
    {
        set_time_limit(0);
        $step = 100;
        $offset = 3223;
        do {
            $members = Db::table('too_mall_member')->field('id,openid')->limit($offset, $step)->order('id asc')->select();
            $data = [];
            foreach ($members as $k => $v) {
                if ($v['openid']) {
                    $data[] = [
                        'openid' => $v['openid'],
                        'lang' => 'zh_CN'
                    ];
                }
            }
            $post_data = ['user_list' => $data];
            var_dump($post_data);die;
            $res = $this->getUnionId($post_data);
            foreach ($res['user_info_list'] as $k => $v) {
                if (isset($v['unionid'])) {
                    $update[] = [
                        'openid' => $v['openid'],
                        'unionid' => $v['unionid']
                    ];
                }
            }
            Db::startTrans();
            foreach ($update as $k => $v) {
                $t = Db::table('too_mall_member')->where(['openid' => $v['openid']])->update(['unionid' => $v['unionid']]);
                if (!$t) {
                    echo($v['openid'] . '保存失败');
                } else {
                    echo($v['openid'] . '保存成功');
                }
                sleep(1);
            }
            Db::commit();
            $offset += $step;
        } while ($offset < 3323);
    }

    public function getUnionidAlone()
    {
        $openid = 'ocrvr0kYEgZeC3K9qSLXB56kURGg';
        $token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$token}&openid={$openid}&lang=zh_CN";
        $res = $this->httpGet($url);
        var_dump($res);
    }

    protected function httpPost($url, $post_data, $is_json = true)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if ($is_json) {
            $post_data = json_encode($post_data);
            $header = array(
                'Content-type: application/json;charset=utf-8',
                'Content-Length: ' . strlen($post_data)
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
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


    public function ajaxReturn($status, $data, $msg = '', $type = 'JSON')
    {
        switch (strtoupper($type)) {
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $data = array('status' => $status, 'data' => $data, 'msg' => $msg);
                exit(json_encode($data));
            case 'EVAL' :
                // 返回可执行的js脚本
                header('Content-Type:text/html; charset=utf-8');
                exit($data);
        }
    }

}
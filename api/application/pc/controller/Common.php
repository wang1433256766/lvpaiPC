<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/5/25
 * Time: 16:09
 */

namespace app\pc\controller;

use think\Controller;

class Common extends Controller
{
    public function _initialize()
    {
        parent::_initialize();
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            header("Access-Control-Allow-Credentials: true");
        }

//        if (! session('user_id')) {
//            $this->redirect('Login/login');
//        }
    }

    /**
     * post请求
     * @param string $url      请求地址
     * @param array $post_data 请求数据
     * @param bool $is_json    是否是json流
     * @return mixed
     */
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

    /**
     * 获取指定长度的随机数字
     * @param int $len 字符串长度
     * @return string 返回指定长度字符串
     */
    protected function get_rand_num($len = 6){
        $rand_arr = range('0', '9');
        shuffle($rand_arr);//打乱顺序
        $rand = array_slice($rand_arr, 0,$len);
        return implode('', $rand);
    }

}
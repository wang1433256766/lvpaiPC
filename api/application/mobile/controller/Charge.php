<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/27
 * Time: 17:14
 */

namespace app\mobile\controller;
use think\Request;
use think\Db;

class Charge extends \think\Controller
{
    public function index(){
     

        
       
        return  $this->fetch();
    }

    public function query(){
        $res = Request::instance();
        if ($res->isPost()){
            $res = $res->param();
            $where['status'] = 1;
            $where['UUcode'] = $res['code'];
            $data  = Db::name('spot_order')->field('id,ticket_name,status,num')->where($where)->select();
//            echo 123;exit;
            if ($data){
                echo json_encode($data);
            }else{
                $data['status'] = false;
                $data['info'] = '订单不存在';
                echo json_encode($data);

            }

        }
    }

    public function make(){
        $param = Request::instance();
        if ($param->isPost()){
            $id = $param->param('id');
            $order = Db::name('spot_order')->field('order_sn,order_code')->where('id',$id)->find();
            $res['remoteSn'] = $order['order_sn'];
            $res['orderSn'] = $order['order_code'];
            $data = json_encode($res);
            $url ='http://lvpai.zhonghuilv.net/mobile/notify/index';
            $result = request_post($url,$data);
            return $result;
            // if ($data){
            //     $info['info'] = '核销成功';
            //     $info['status'] = true;
            // }else{
            //     $info['info'] = '核销失败';
            //     $info['status'] = false;
            // }
            // echo json_encode($info);
        }
    }

    public function ggc(){
        $where['status'] = '1';
        $where['spot_id'] = '57055';
        $where['ticket_id'] = '142999';
        $data  = Db::name('xcx_order')->where($where)->field('id,up_time')->select();
        foreach ($data as $v){
            if ($v['up_time']+'300' < time()){
                $update['status'] = 5;
                Db::name('xcx_order')->where('id',$v['id'])->update($update);
            }
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
}
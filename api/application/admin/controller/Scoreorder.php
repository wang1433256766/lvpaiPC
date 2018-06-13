<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use app\admin\model\ScoreorderModel;
use think\Request;
use think\Session;

class Scoreorder extends Controller
{
	public function index()
	{
		if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                 $where['order_sn'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $order = new ScoreorderModel();	
            $selectResult = $order->getOrderByWhere($where, $offset, $limit);
            $status = config('order_status');
           // $order_ship = config('order_ship');
            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['last_login_time'] = date('Y-m-d H:i:s', $vo['last_login_time']);
                $selectResult[$key]['status'] = $status[$vo['status']];

                $operate = [
                    '发货' => "javascript:send('".$vo['id']."')",
                    '关闭' => "javascript:OrderDel('".$vo['id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
                
                if( 1 == $vo['id'] ){
                	$selectResult[$key]['operate'] = '';
                }
            }

            $return['total'] = $order->getAllOrder($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
	}

    //短信通知
    public function send()
    {
        $orderid = Session::get("orderid");
        //短信接口地址
        $target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
        //获取手机号
        $member_id = Db::name("wch_order")->where("id",$orderid)->value("member_id");
        $mobile = Db::name("member_address")->where("member_id",$member_id)->value("phone");


        //获取快递单号
        $tracking_number = Request::instance()->param("tracking_number");
        //获取订单号
        $order = Db::name("wch_order")->where("id",$orderid)->value("order_sn");
        //获取用户姓名
        $member_id = Db::name("wch_order")->where("id",$orderid)->value("member_id");
        $username = Db::name("mall_member")->where("id",$member_id)->value("nickname");
        $user ='cf_zhonghuilv';
        $password ='eb2a1a963b116ae15e7cb2bf41382bf4';
        $post_data = "account=".$user."&password=".$password."&mobile=".$mobile."&content=".rawurlencode("亲爱的【".$username."】先生/女士,您的订单【".$order."】小拓已经打包好正在飞速向您发射，快递单号为【".$tracking_number."】,请您随时关注！");
        //用户名是登录ihuyi.com账号名（例如：cf_demo123）
        //查看密码请登录用户中心->验证码、通知短信->帐户及签名设置->APIKEY
        $gets =  xml_to_array(Post($post_data, $target));
        Db::name("wch_order")->where("id",$orderid)->setField("status",0);
        if($gets)
        {
            $res = array("status"=>true,"info"=>"短信通知成功!");
        }
        else
        {
            $res = array("status"=>false,"info"=>"短信通知失败，请确认后操作!");
        }
        return json($res);

        /*if($gets['SubmitResult']['code']==2){
            $_SESSION['mobile'] = $mobile;
            $_SESSION['mobile_code'] = $mobile_code;
        }
        echo $gets['SubmitResult']['msg'];*/
    }

    public function sendid()
    {
        $orderid = Request::instance()->param("id");
        Session::set("orderid",$orderid);
    }

    public function OrderDel()
    {
        $orderid = Request::instance()->param("id");
        if($orderid)
        {
            $bool = Db::name("wch_order")->where("id",$orderid)->setField("status",5);
            if($bool)
            {
                $res = array("status"=>true,"info"=>"关闭交易成功!");
            }
            else
            {
                $res = array("status"=>true,"info"=>"关闭交易失败!");
            }
            return json($res);
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/6/5
 * Time: 09:47
 */
namespace app\pc\controller;

use think\Request;
use think\Db;
use app\pc\model\ShopSpotComModel;

class ShopSpotCom extends Common
{
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 根据登陆id获取订单与点评信息
     * */
    public function getListInfo(){
        $param = request()->param();
        $mysql = new ShopSpotComModel();
        $rs = $mysql->getListInfo($param);
        echo json_encode($rs);die;
    }

    /**
     * 追加点评
     * */
    public function setSpotCom(){
        $param = request()->param();
        switch ($param['action']){
            case 'add' :
                $rs = Db::table('too_spot_order')->where('order_sn',trim($param['order_snm']))->field('spot_id , ticket_id')->find();
                break;
            case 'update':
                $data = [

                ];
                break;
            default:
                echo json_encode(['status'=>1,'msg'=>'非法操作!','data'=>[]]);
        }
    }

}
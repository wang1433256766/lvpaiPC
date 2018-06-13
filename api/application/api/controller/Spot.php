<?php
namespace app\api\controller;
use think\Controller;
use think\Db;
use think\Request;

class Spot extends Controller
{
    //景区折扣列表
    public function index()
    {
    	
    	 $info = Db::name('shop_spot')->where('cheap',1)->field('id as spot_id,title,desc,thumb,shop_price,market_price,sale_day')->select();


    	 if($info){
    	 	$res = array(
	            'code' => 1,
	            'msg' => '操作成功',
	            'body' => $info,
	            );
    	 }
    	 return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
     }
}
<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/5/15
 * Time: 10:29
 */
namespace app\admin\controller;

use think\Controller;


class Publicyang extends Base
{
    public function __construct(){
        parent::__construct();
    }

    public static function ajaxReturn($code,$msg,$arr){
        echo json_encode(['code'=>$code,'msg'=>$msg,'rows'=>$arr]);
    }

}
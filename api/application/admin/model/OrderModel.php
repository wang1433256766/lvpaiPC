<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/5/28
 * Time: 16:01
 */
namespace app\admin\model;

use think\Db;
use think\Model;


class OrderModel extends PublicyangModel
{
    public function __construct(){
        parent::__construct();
    }
    protected $table = 'too_spot_order';
    //显示字段
    protected $list_fields = array();


    public function preprocess($rs,$stime,$etime){
//        foreach ($rs as $k=>$v){
//            $rs[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
//        }
        return $rs;
    }




}
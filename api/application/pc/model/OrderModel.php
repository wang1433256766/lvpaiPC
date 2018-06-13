<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/5/30
 * Time: 10:04
 */
namespace app\pc\model;

use think\Db;
use think\Model;
use think\Session;
use PDOException;

class OrderModel extends PublicyangModel
{
    public function __construct(){
        parent::__construct();
    }

    protected $table = 'too_spot_order';
    //显示字段
    protected $list_fields = array();



    /**
     * 查询订单
     * */
    public function getAllList($data){
        $user_id = Session::get('user.id');
        $where = " and t.member_id = ".$user_id;
        if(isset($data['status'])){
            if($data['status'] != -1){
                $where .= " and t.status = ".trim($data['status']);
            }
        }
        $sql = "select t.id,t.add_time,t.order_sn,t.ticket_name,t.num,t.travel_date,t.order_total,t.status 
                from {$this->table} t 
                WHERE 1=1 and t.delete = 0 and t.source = 'pc' {$where}
                order BY t.id DESC ";
//        var_dump($sql);die;
        $rs = $this->query($sql);
        if($rs!==false){
            return ['status'=>0,'msg'=>'','data'=>$rs];
        }else{
            return ['status'=>-1,'msg'=>'查询失败!','data'=>''];
        }
    }



}
<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/6/5
 * Time: 09:49
 */

namespace app\pc\model;

use think\Db;
use think\Model;
use think\Session;

class ShopSpotComModel extends PublicyangModel
{
    public function __construct()
    {
        parent::__construct();
    }

    protected $T_spot_comment = 'too_shop_spot_comment';
    protected $T_mall = 'too_mall_member';
    protected $T_shop_order = 'too_shop_order';
    protected $T_order_coment = 'too_shop_order_coment';
    protected $T_member_promote = 'too_member_promote';
    //显示字段
    protected $list_fields = array();

    public  function getListInfo($data){
        $user_id = Session::get('user.id');
        $where = '';
        if(!empty($data['s_time'])){
            $where .= " and t.add_time > ".strtotime($data['s_time']);
        }
        if(!empty($data['e_time'])){
            $where .= " and t.add_time < ".strtotime($data['e_time']);
        }
        $sql = "
            select t1.*, t2.*, t3.total as tc_toral,t.ticket_name
            from {$this->T_shop_order} t 
            LEFT JOIN {$this->T_order_coment} t1 
            ON t.id = t1.order_id 
            LEFT JOIN {$this->T_spot_comment} t2 
            ON t.id = t2.order_id
            LEFT JOIN {$this->T_member_promote} t3 
            ON t.id = t3.order_id
            WHERE t.member_id = {$user_id} and t2.status = 0 and t.source = 'pc' {$where}
        ";
        $rs = $this->query($sql);
        if($rs!==false){
            return ['status'=>0,'msg'=>'','data' => $rs];
        }else{
            return ['status'=>1,'msg'=>'查询失败请联系管理员!','data' => []];
        }
    }

}
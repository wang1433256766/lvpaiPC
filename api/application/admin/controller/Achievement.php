<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/6/6
 * Time: 15:46
 */
namespace app\admin\controller;

use app\manage\model\AchievementModel;
use think\db;
use think\Session;

class Achievement extends Publicyang
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 渠道业绩
     * */
    public function channelList(){
        $param = request()->param();
        if(empty($param['action'])){
            $param['action'] = '';
        }
        $action = $param['action'];
        switch ($action){
            case '':
                $where['sala_type'] = 1;
//                $where['status'] = 1;
                $rs = Db::table('too_mall_member')->where($where)->field('id,name')->select();
                $this->assign('user_rs',$rs);
                return $this->fetch();
                break;
            case 'ajaxList':
                $this->ajaxList('json');
                break;
            case 'getOne':
                $this->getOne($param);
                break;
            case 'getTwo':
                $this->getTwo($param);
                break;
            case 'export':
                $this->export();
            default:
                self::ajaxReturn(400,'非法操作!','');
        }
    }

    protected function ajaxList($re){
        // 接收参数
        $param = request()->param();
        if($re=='json'){
            $page = $param['pageNumber'];//页码
            $limit =  $param['pageSize'];//条数
        }

//        $sortName = $param['sortName'];
//        $sortOrder =  $param['sortOrder'];
        $condition = '';
        if(!empty($param['str_time'])){
            $time_arr = explode(' - ',$param['str_time']);
            $str=date('Y-m-01', strtotime($time_arr[1]));
            $end = strtotime("$str +1 month -1 day")+24*3600;
            $str=date('Y-m-01', strtotime($time_arr[0]));
            $str = strtotime($str);
        }else{
            $str=date('Y-m-01', strtotime(date("Y-m-d")));
            $end = strtotime("$str +1 month -1 day")+24*3600;
            $str = strtotime($str);
        }
        $condition .= ' and t.add_time >= '.$str.' and t.add_time < '.$end;
        if(!empty($param['xs_id'])){
            if($param['xs_id']!=-1){
                if($param['one_id']!=-1){
                    $new[] = $param['one_id'];
                    $rs = Db::query("select id from too_mall_member WHERE channel_id = {$param['one_id']}  or parent_id = {$param['one_id']} ");
                    foreach ($rs as $k=>$v)
                        $new[] = $v['id'];
                    $whe = implode(',',$new);
                    unset($rs);
                }else{
                    $new[] = $param['xs_id'];
                    $rs = Db::query("select id from too_mall_member WHERE channel_id = {$param['xs_id']}  or parent_id = {$param['xs_id']} ");
                    foreach ($rs as $k=>$v)
                        $new[] = $v['id'];
                    $whe = implode(',',$new);
                    unset($rs);
                }
            }else{
                $where['sala_type'] = 1;
                $rs = Db::table('too_mall_member')->where($where)->field('id')->select();
                foreach ($rs as $k=>$v)
                    $new[] = $v['id'];
                $whe = implode(',',$new);
                $rs = Db::query("select id from too_mall_member WHERE channel_id in({$whe})  or parent_id in($whe) ");
                foreach ($rs as $k=>$v)
                    $new[] = $v['id'];
                $whe = implode(',',$new);
            }
            if(!empty($whe)){
                $condition .= " and t.member_id in($whe)";
            }

        }
//        var_dump($condition);die;
        //订单id集合
        $sql = "
            select t.id,t.status
                    from too_spot_order t 
                    LEFT JOIN too_mall_member t1 
                    ON t.member_id = t1.id
                    LEFT JOIN too_member_promote t2
                    ON t.id = t2.order_id
                    WHERE 1=1 and t.delete = 0 {$condition}
                    ORDER BY t.member_id ASC 
        ";
        $id_rs = Db::query($sql);
//        var_dump($sql);
        if($id_rs){
            $ok = 0;//已核销订单
            foreach ($id_rs as $k=>$v){
                $new_1[] = $v['id'];
                if($v['status']==5){
                    $ok ++;
                }
            }

            $id_rs = implode(',',$new_1);
            if($param['xs_id']>0){
                $sql = "
                select sum(t.total)  as allTotal
                from too_member_promote t 
                WHERE t.member_id = {$param['xs_id']} and t.order_id in($id_rs)
            ";
            }else{
                $sql = "
                select sum(t.total) as allTotal
                from too_member_promote t 
                WHERE  t.order_id in($id_rs)
            ";
            }
            $promote_fee_all = Db::query($sql);
            if($promote_fee_all[0]['allTotal']<0){
                $promote_fee_all = 0;
            }else{
                $promote_fee_all = $promote_fee_all[0]['allTotal'];
            }
            unset($sql);
        }

        //总行数
        $query = " select COUNT(1) as numrows,sum(t.total) as total_all ,sum(t.refund_price) as refund_price_all 
                     , sum(t.payment) as payment_all
                    from too_spot_order t 
                    LEFT JOIN too_mall_member t1 
                    ON t.member_id = t1.id
                    LEFT JOIN too_member_promote t2
                    ON t.id = t2.order_id
                    WHERE 1=1 and t.delete = 0 {$condition} 
                    ORDER BY t.member_id ASC ";
//        echo '<pre>';
//        var_dump($query);
        $total_arr = Db::query($query);
//        var_dump($total_arr);die;

        $total = $total_arr[0]['numrows'];
        if($re=='json'){
            $total_page = ceil($total / $limit);//总页数
            if($page <= 1){
                $page = 1;
            }elseif($page >= $total_page){
                $page = $total_page;
            }
            $offset = ($page - 1 ) * $limit;
            if($offset<0){
                $offset = 0;
            }
            $sql = "select t.*,t1.type,t1.name,t.status as member_status
                    from too_spot_order t 
                    LEFT JOIN too_mall_member t1 
                    ON t.member_id = t1.id
                    WHERE 1=1 and t.delete = 0  {$condition} 
                    ORDER BY t.member_id ASC  LIMIT {$offset} , {$limit}";
        }else{
            $sql = "select t.*,t1.type,t1.name,t.status as member_status
                    from too_spot_order t 
                    LEFT JOIN too_mall_member t1 
                    ON t.member_id = t1.id
                    WHERE 1=1 and t.delete = 0  {$condition} 
                    ORDER BY t.member_id ASC  ";
        }


        $row = Db::query($sql);
        $data['data']['tj'] = 1;
        if($total > 0 ){
           $data['data']['tj'] = [
               'total_all' =>$total_arr[0]['total_all'],
               'num' => $total_arr[0]['numrows'],
               'refund_price' => $total_arr[0]['refund_price_all'],
               'promote_fee' => $promote_fee_all,
               'payment' => $total_arr[0]['payment_all'],
               'ok'=>$ok
           ];
            $data['data']['total']=$total;
            $data['data']['rows']=$row;
            $data['success']=200;
            $data['message']=null;
        }else{
            $data['data']['total']=0;
            $data['data']['rows']=[];
            $data['success']=200;
            $data['message']='暂无数据';
        }
        if($re =='json'){
            echo json_encode($data);die;
        }else{
            return $data;
        }

    }

    /**
     *导出渠道业绩
     * */
    private function export(){
        $param = request()->param();
        $rs = $this->ajaxList('return');
        $rs = $rs['data'];

        foreach ($rs['rows'] as $k=>$v){
            $rs['rows'][$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
//            if($v['type']==1){
//                $rs['rows'][$k]['type'] = '一级代理';
//            }else if($v['type']==2){
//                $rs['rows'][$k]['type'] = '二级代理';
//            }else{
//                $rs['rows'][$k]['type'] = '普通';
//            }
            if($v['status']==0){
                $rs['rows'][$k]['status'] = '未支付';
            }

            else if($v['status']==1){
                $rs['rows'][$k]['status'] = '已支付';
            }
            else if($v['status']==2){
                $rs['rows'][$k]['status'] = '处理中';
            } else if($v['status']==3){
                $rs['rows'][$k]['status'] = '已取消';
            } else if($v['status']==4){
                $rs['rows'][$k]['status'] = '已退款';
            } else if($v['status']==5){
                $rs['rows'][$k]['status'] = '已核销';
            }else if($v['status']==5){
                $rs['rows'][$k]['status'] = '部分退款';
            }else {
                $rs['rows'][$k]['status'] = '未知';
            }
        }
//        $row = $order->getList($page,$rows,$condition.$condition_1,$sortName,$sortOrder);
        $title_info = array(
            array('field' => 'name', 'title' => '代理姓名', 'width' => 10),
//            array('field' => 'type', 'title' => '代理等级', 'width' => 10),
//            array('field' => 'add_time', 'title' => '订购日期', 'width' => 25),
            array('field' => 'order_sn', 'title' => '订单编号', 'width' => 30),
            array('field' => 'travel_agency', 'title' => '旅行社名称', 'width' => 30),
            array('field' => 'ticket_name', 'title' => '商品名称', 'width' => 30),
            array('field' => 'mobile', 'title' => '用户电话', 'width' => 15),
//            array('field' => 'gest_day', 'title' => '孕天', 'width' => 12),
            array('field' => 'num', 'title' => '订购数量', 'width' => 12),
            array('field' => 'order_total', 'title' => '支付金额', 'width' => 12),
            array('field' => 'refund_price', 'title' => '退款金额', 'width' => 12),
            array('field' => 'status', 'title' => '订单状态', 'width' => 15)
        );
//        var_dump($data);die;
        $order = new ExcelInport();
        if(empty($param['str_time'])){
            $now_date = date('Y-m-d',time());
        }else{
            $now_date = $param['str_time'];
        }
        if($param['xs_id']>0){
            if($param['one_id']!=-1){
                $name = Db::table('too_mall_member')->where('id',$param['one_id'])->field('name')->find();
                $title = $now_date.'-渠道业绩-'.$name['name'];
            }else{
                $name = Db::table('too_mall_member')->where('id',$param['xs_id'])->field('name')->find();
                $title = $now_date.'-渠道业绩-'.$name['name'];
            }

        }else{
            $title = $now_date.'-渠道业绩';
        }
        $order->writeExcel($rs, $title_info, $title,'channellist');
    }

    /**
     * 根据销售获取一级经销商
     * */
    protected function getOne($data){
        $id = $data['id'];
        $sql = "
            select id,name 
            from too_mall_member 
            WHERE channel_id = {$id} and type > 0  or parent_id = {$id} and type > 0
        ";
        $rs = Db::query($sql);
        if($rs!==false){
            echo json_encode(['code'=>200,'rows'=>$rs]);
        }else{
            echo json_encode(['code'=>200,'msg'=>'查询失败!']);
        }
        die;
    }

    /**
     * 根据一级经销商获取二级经销商
     * */
    protected function getTwo($data){
        $id = $data['id'];
        $sql = "
            select id,name 
            from too_mall_member 
            WHERE parent_id = {$id} and type = 2 
        ";
        $rs = Db::query($sql);
        if($rs!==false){
            echo json_encode(['code'=>200,'rows'=>$rs]);
        }else{
            echo json_encode(['code'=>200,'msg'=>'查询失败!']);
        }
        die;
    }



    /**
     * 直客业绩
     * */
    public function cirectGuest(){
        $param = request()->param();
        if(empty($param['action'])){
            $param['action'] = '';
        }
        $action = $param['action'];
        switch ($action){
            case '':
                $where['sala_type'] = 2;
//                $where['status'] = 1;
                $rs = Db::table('too_mall_member')->where($where)->field('id,name')->select();
                $this->assign('user_rs',$rs);
                return $this->fetch();
                break;
            case 'ajaxList':
                $this->ajaxListGuest('json');
                break;

            case 'export':
                $this->exportGuest();
            default:
                self::ajaxReturn(400,'非法操作!','');
        }
    }

    /**
     * 直客销售业绩
     * */
    private function ajaxListGuest($re){
        // 接收参数
        $param = request()->param();
        if($re=='json'){
            $page = $param['pageNumber'];//页码
            $limit =  $param['pageSize'];//条数
        }

//        $sortName = $param['sortName'];
//        $sortOrder =  $param['sortOrder'];
        $condition = '';
        if(!empty($param['str_time'])){
            $time_arr = explode(' - ',$param['str_time']);
            $str=date('Y-m-01', strtotime($time_arr[1]));
            $end = strtotime("$str +1 month -1 day")+24*3600;
            $str=date('Y-m-01', strtotime($time_arr[0]));
            $str = strtotime($str);
        }else{
            $str=date('Y-m-01', strtotime(date("Y-m-d")));
            $end = strtotime("$str +1 month -1 day")+24*3600;
            $str = strtotime($str);
        }
        $condition .= ' and t.add_time >= '.$str.' and t.add_time < '.$end;
        if(!empty($param['xs_id'])){
            if($param['xs_id']!=-1){
                $condition .= " and t.member_id = ".trim($param['xs_id']);
            }else{
                $where['sala_type'] = 2;
                $rs = Db::table('too_mall_member')->where($where)->field('id')->select();
                foreach ($rs as $k=>$v)
                    $new[] = $v['id'];
                $whe = implode(',',$new);
                if(!empty($whe)){
                    $condition .= " and t.member_id in($whe)";
                }
            }
        }
        //订单id集合
        $sql = "
            select t.id,t.status
                    from too_spot_order t 
                    LEFT JOIN too_mall_member t1 
                    ON t.member_id = t1.id
                    LEFT JOIN too_member_promote t2
                    ON t.id = t2.order_id
                    WHERE 1=1 and t.delete = 0 {$condition}
                    ORDER BY t.member_id ASC 
        ";
        $id_rs = Db::query($sql);
        if($id_rs){
            $ok = 0;//已核销订单
            foreach ($id_rs as $k=>$v){
                $new_1[] = $v['id'];
                if($v['status']==5){
                    $ok ++;
                }
            }

            $id_rs = implode(',',$new_1);
            if($param['xs_id']>0){
                $sql = "
                select sum(t.total)  as allTotal
                from too_member_promote t 
                WHERE t.member_id = {$param['xs_id']} and t.order_id in($id_rs)
            ";
            }else{
                $sql = "
                select sum(t.total) as allTotal
                from too_member_promote t 
                WHERE  t.order_id in($id_rs)
            ";
            }
            $promote_fee_all = Db::query($sql);
            if($promote_fee_all[0]['allTotal']<0){
                $promote_fee_all = 0;
            }else{
                $promote_fee_all = $promote_fee_all[0]['allTotal'];
            }
            unset($sql);
        }

        //总行数
        $query = " select COUNT(1) as numrows,sum(t.total) as total_all ,sum(t.refund_price) as refund_price_all 
                     , sum(t.payment) as payment_all
                    from too_spot_order t 
                    LEFT JOIN too_mall_member t1 
                    ON t.member_id = t1.id
                    WHERE 1=1 and t.delete = 0 {$condition} 
                    ORDER BY t.member_id ASC ";
//        LEFT JOIN too_member_promote t2
//                    ON t.id = t2.order_id
        $total_arr = Db::query($query);
        $total = $total_arr[0]['numrows'];
        if($re=='json'){
            $total_page = ceil($total / $limit);//总页数
            if($page <= 1){
                $page = 1;
            }elseif($page >= $total_page){
                $page = $total_page;
            }
            $offset = ($page - 1 ) * $limit;
            if($offset<0){
                $offset = 0;
            }
            $sql = "select t.*,t1.type,t1.name,t.status as member_status
                    from too_spot_order t 
                    LEFT JOIN too_mall_member t1 
                    ON t.member_id = t1.id
                    WHERE 1=1 and t.delete = 0  {$condition} 
                    ORDER BY t.member_id ASC  LIMIT {$offset} , {$limit}";
        }else{
            $sql = "select t.*,t1.type,t1.name,t.status as member_status
                    from too_spot_order t 
                    LEFT JOIN too_mall_member t1 
                    ON t.member_id = t1.id
                    WHERE 1=1 and t.delete = 0  {$condition} 
                    ORDER BY t.member_id ASC  ";
        }


        $row = Db::query($sql);
        $data['data']['tj'] = 1;
        if($total > 0 ){
            $data['data']['tj'] = [
                'total_all' =>$total_arr[0]['total_all'],
                'num' => $total_arr[0]['numrows'],
                'refund_price' => $total_arr[0]['refund_price_all'],
                'promote_fee' => $promote_fee_all,
                'payment' => $total_arr[0]['payment_all'],
                'ok'=>$ok
            ];
            $data['data']['total']=$total;
            $data['data']['rows']=$row;
            $data['success']=200;
            $data['message']=null;
        }else{
            $data['data']['total']=0;
            $data['data']['rows']=[];
            $data['success']=200;
            $data['message']='暂无数据';
        }
        if($re =='json'){
            echo json_encode($data);die;
        }else{
            return $data;
        }

    }
    /**
     *导出渠道业绩
     * */
    private function exportGuest(){
        $param = request()->param();
        $rs = $this->ajaxListGuest('return');
        $rs = $rs['data'];

        foreach ($rs['rows'] as $k=>$v){
            $rs['rows'][$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);

            if($v['status']==0){
                $rs['rows'][$k]['status'] = '未支付';
            }

            else if($v['status']==1){
                $rs['rows'][$k]['status'] = '已支付';
            }
            else if($v['status']==2){
                $rs['rows'][$k]['status'] = '处理中';
            } else if($v['status']==3){
                $rs['rows'][$k]['status'] = '已取消';
            } else if($v['status']==4){
                $rs['rows'][$k]['status'] = '已退款';
            } else if($v['status']==5){
                $rs['rows'][$k]['status'] = '已核销';
            }else if($v['status']==5){
                $rs['rows'][$k]['status'] = '部分退款';
            }else {
                $rs['rows'][$k]['status'] = '未知';
            }
        }
//        $row = $order->getList($page,$rows,$condition.$condition_1,$sortName,$sortOrder);
        $title_info = array(
            array('field' => 'add_time', 'title' => '订购日期', 'width' => 25),
            array('field' => 'order_sn', 'title' => '订单编号', 'width' => 30),
            array('field' => 'ticket_name', 'title' => '商品名称', 'width' => 30),
            array('field' => 'mobile', 'title' => '用户电话', 'width' => 15),
//            array('field' => 'gest_day', 'title' => '孕天', 'width' => 12),
            array('field' => 'num', 'title' => '订购数量', 'width' => 12),
            array('field' => 'order_total', 'title' => '支付金额', 'width' => 12),
            array('field' => 'refund_price', 'title' => '退款金额', 'width' => 12),
            array('field' => 'status', 'title' => '订单状态', 'width' => 15)
        );
//        var_dump($data);die;
        $order = new ExcelInport();
        if(empty($param['str_time'])){
            $now_date = date('Y-m-d',time());
        }else{
            $now_date = $param['str_time'];
        }
        if($param['xs_id']>0){
            $name = Db::table('too_mall_member')->where('id',$param['xs_id'])->field('name')->find();
            $title = $now_date.'-直客业绩-'.$name['name'];
        }else{
            $title = $now_date.'-直客业绩';
        }
        $order->writeExcel($rs, $title_info, $title,'channellist');
    }



}
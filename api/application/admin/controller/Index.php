<?php
// +----------------------------------------------------------------------
// | Zhl
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Belasu <88487088@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;
use app\admin\model\ArticleModel;
use app\admin\model\MemberModel;
use app\admin\model\FansModel;
use think\Model;
use think\Db;
use think\Request;
use think\Session;

class Index extends Base
{
    public function index()
    {
        return $this->fetch('/index');
    }

    /**
     * 后台默认首页
     * @return mixed
     */
    public function indexPage()
    {
        //旅拍商城成交额
        $where['status'] = 1;
        $turnover = Db::name("spot_order")->field("sum(order_total)")->sum("order_total");
        //文创产品成交额
        $wch_over = Db::name("wch_order")->where($where)->sum("total_free");
        //秀秀互动人数
        $show_user = Db::name("gugu_article")->count(1);
        $comm_num =  Db::name("gugu_comment")->count(1);
        $shou_favor = Db::name("gugu_favor")->count(1);

        $inter_num = $show_user + $comm_num + $shou_favor;
        //总用户数
        $all_user = Db::name("mall_member")->count(1);
        //一级分销商人数
        $first = Db::name("mall_member")->where("type",1)->count(1);
        //二级分销商人数
        $second = Db::name("mall_member")->where("type",2)->count(1);


        //当日业绩排名表
        $where_day['too_spot_order.status'] = 5;
        $day = date("Y-m-d",strtotime("-1 day"));
        $day = strtotime($day);
        $where_day['too_spot_order.add_time'] = ['>',$day];
       // dump($where_day);
        $days = Db::name("mall_member as mall")
                ->field("sum(too_spot_order.order_total) as total_price,mall.name,mall.mobile,too_mall_member.name as super_name,mall.id")
                ->join("too_spot_order","mall.id = too_spot_order.promote_id")
                ->join("too_mall_member","too_mall_member.id = mall.parent_id")
                ->group("too_spot_order.promote_id")
                ->order("sum(order_total) desc")
                ->where($where_day)
                ->limit(10)
                ->select();

        //当月业绩排名表
       //ini_set('date.timezone','PRC');
            $thismonth = date('m');
            $thisyear = date('Y');
            if ($thismonth == 1) {
             $lastmonth = 12;
             $lastyear = $thisyear - 1;
            } else {
             $lastmonth = $thismonth - 1;
             $lastyear = $thisyear;
            }
            $lastStartDay = $lastyear . '-' . $lastmonth . '-1';
            $lastEndDay = $lastyear . '-' . $lastmonth . '-' . date('t', strtotime($lastStartDay));
            $b_time = strtotime($lastStartDay);//上个月的月初时间戳
            $e_time = strtotime($lastEndDay);//上个月的月末时间戳
     //   $where_day['too_spot_order.add_time'] = ['>',$mouth];
       // dump($where_day);
        $where_mouth['too_spot_order.status'] = 5;
        $where_mouth['too_spot_order.add_time'] = ['>',$b_time];
        $where_mouth['too_spot_order.add_time'] = ['<',$e_time];
        $mouths = Db::name("mall_member as mall")
                ->field("sum(too_spot_order.order_total) as total_price,mall.name,mall.mobile,too_mall_member.name as super_name,mall.id")
                ->join("too_spot_order","mall.id = too_spot_order.promote_id")
                ->join("too_mall_member","too_mall_member.id = mall.parent_id")
                ->group("too_spot_order.promote_id")
                ->order("sum(order_total) desc")
                ->where($where_mouth)
                ->limit(10)
                ->select();

        //当年业绩排名表
        $where_year['too_spot_order.status'] = 5;
       // $year = date("Y-m-d",strtotime("-1 year"));
        //dump($year);
        $last= date("Y",time());  
        $last_lastday = $last."0101";//去年最后一天  
        $last_firstday = $last."1231";//去年第一天
        
        $beg_day = strtotime($last_lastday);
        $end_day = strtotime($last_firstday);

       // $year = strtotime($year);
        $where_year['too_spot_order.add_time'] = ['>',$beg_day];
        $where_year['too_spot_order.add_time'] = ['<',$end_day];
       // dump($where_day);
        $years = Db::name("mall_member as mall")
                ->field("sum(too_spot_order.order_total) as total_price,mall.name,mall.mobile,too_mall_member.name as super_name,mall.id")
                ->join("too_spot_order","mall.id = too_spot_order.promote_id")
                ->join("too_mall_member","too_mall_member.id = mall.parent_id")
                ->group("too_spot_order.promote_id")
                ->order("sum(order_total) desc")
                ->where($where_year)
                ->limit(10)
                ->select();

        //查询待退款金额
        $refound = Db::name("spot_order")->where("status",2)->sum("order_total");
        //查询已支付金额
        $payyed = Db::name("spot_order")->where("status",1)->sum("order_total");
        //查询待支付金额
        $payying = Db::name("spot_order")->where("status",0)->sum("order_total");
        //查询已完成金额
        $overing = Db::name("spot_order")->where("status",5)->sum("order_total");

        $amount = [
            'refound' =>$refound,
            'payyed'  => $payyed,
            'payying' => $payying,
            'overing' => $overing
        ];

        //半年利润
        //今日销售冠军
        $today = mktime(0,0,0,date('m'),date('d')-1,date('Y'));
        $where_mvp['too_spot_order.add_time'] = ['>',$today];
        $where_mvp['too_spot_order.status'] = 5;
        $mvp = Db::name("mall_member")->field("sum(too_spot_order.order_total) as order_total,too_mall_member.name")
                ->join("too_spot_order","too_spot_order.promote_id = too_mall_member.id")
                ->where($where_mvp)
                ->limit(1)
                ->group("too_mall_member.id")
                ->order("order_total desc")
                ->select();
        //总订单笔数
        $order_all_num = Db::name("spot_order")->field("count(1) as order_num,sum(order_total) as order_total")->where("status",5)->select();
        //上月订单数
        $order_last_mouth = Db::name("spot_order")->field("count(1) as order_num,sum(order_total) as order_total")->where($where_mouth)->select();

        //上月销售额
        $last_mouth_money = Db::name('spot_order')->where($where_mouth)->sum("order_total");
        //本月销售额
        $beginDate=date('Y-m-01', strtotime(date("Y-m-d")));
        $enddate = date('Y-m-d', strtotime("$beginDate +1 month -1 day"));
        $where_this_mouth['add_time'] = ['>',strtotime($beginDate)];
        $where_this_mouth['add_time'] = ['<',strtotime($enddate)];
        $where_this_mouth['status'] = 5;
        $this_mouth_money = Db::name('spot_order')->where($where_this_mouth)->sum("order_total");
        //增长比
        $grow_rate = ($this_mouth_money - $last_mouth_money)/$last_mouth_money * '100%';



        //已关注数
        $followed = Db::name("mall_member")->count(1);
        //未关注数
        $no_follow = Db::name("mall_member")->where("status",0)->count(1);
        //已注册数
        $logined = Db::name("mall_member")->where("mobile != ''")->count(1);

        $user_fenbu = [
            'followed' => $followed,
            'no_follow'=> $no_follow,
            'logined'  => $logined
        ];




        $this->assign('first',$first);
        $this->assign('second',$second);
        $this->assign('all_user',$all_user);
        $this->assign('inter_num',$inter_num);
        $this->assign('turnover',$turnover);
        $this->assign('wch_over',$wch_over);
        $this->assign('days',$days);
        $this->assign('mouths',$mouths);
        $this->assign('years',$years);
        $this->assign('amount',$amount);
        $this->assign('mvp',$mvp);
        $this->assign('order_all_num',$order_all_num);
        $this->assign('order_last_mouth',$order_last_mouth);
        $this->assign('grow_rate',$grow_rate);
        $this->assign('user_fenbu',$user_fenbu);
        return $this->fetch('index');
    }

    //营销员的list
    public function order_list()
    {
        $member_id = request()->param("member_id");
        $info = Request::instance()->param();
        $order_list = [];
        $page = '';
        if($member_id)
        {
            $where['status'] = 5;
            $where['promote_id'] = $member_id;
            $order_list = Db::name("spot_order")->where($where)->order("add_time desc")->paginate(10);
            $page = $order_list->render();
        }




        // $where = array();
        // if (isset($info['status']) && $info['status'] != '-1') {
        //     $where['status'] = $info['status'];
        // }
        // if (isset($info['key']) && !empty($info['key']) ) {
        //     $field = isset($info['type']) && !empty($info['type']) ? $info['type'] : 'order_sn';
        //     $where[$field] = $info['key'];
        // }

        // if (isset($info['id']) && $info['id'] > 0) {
        //     $where['id'] = $info['id'];
        // }
        
        // if (isset($info['from']) && !empty($info['from']) ) {
        //     $start = $info['from'];
        //     $end = !empty($info['to']) ? $info['to'] : date('Y-m-d',time());
        //     $time['start'] = $start;
        //     $time['end'] = $end;
        //     $data = Db::name('spot_order')->where($where)->whereTime('add_time', 'between', [$start,$end])->order('add_time desc')->paginate(10,false,['query'=>$info]);

        //     Session::set('order_time',$time);
        // }else{
        //     $data = Db::name('spot_order')->where($where)->order('add_time desc')->paginate(10,false,['query'=>$info]);
        // }

        // Session::set('spot_order',$where);
        
        // $page = $data->render();
        // //分配初始化数据   
        // $order_status = Config::get('order_status');
        // //dump($order_status);exit;
        // $info['key'] = isset($info['key']) ? $info['key'] : '';
        // $info['type'] = isset($info['type']) ? $info['type'] : '';
        // $info['from'] = isset($info['from']) ? $info['from'] : '';
        // $info['to'] = isset($info['to']) ? $info['to'] : '';
        // $info['status'] = isset($info['status']) ? $info['status'] : '-1';

        // $this->assign('info',$info);
        // $this->assign('data',$data);
        // $this->assign('page',$page);
        // $this->assign('order_status',$order_status);
        $user_name = Db::name("mall_member")->where("id",$member_id)->value("name");
        $this->assign('order_list',$order_list);
        $this->assign('page',$page);
        $this->assign('user_name',$user_name);

        return $this->fetch();
        
    }


    
}

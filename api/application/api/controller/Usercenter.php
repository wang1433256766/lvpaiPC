<?php

namespace app\api\controller;

use think\Request;
use think\Db;
use think\log;

class Usercenter
{

    //成功失败数据返回函数
    public function msgInfo($result, $data)
    {

        $res = array(
            'code' => 0,
            'msg' => '操作失败',
            'body' => array(),
        );
        if (isset($result)) {
            $res = array(
                'code' => 1,
                'msg' => '操作成功',
                'body' => $data,
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

//分页判断
    public function pageJudge($page, $page_size, $result, $arrayInfo)
    {
        $res = array(
            'code' => 0,
            'msg' => '操作失败',
            'body' => $arrayInfo,
        );

        if (count($result) >= $page_size) {
            $res['code'] = 1;
            $res['msg'] = '操作成功';
            $res['body']['page'] = $page + 1;
            $res['body']['content'] = $result;

            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (count($result) < $page_size) {
            $res['code'] = 1;
            $res['msg'] = '操作成功';
            $res['body']['noMoreData'] = 1;
            $res['body']['content'] = $result;

            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            //  echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

//时间处理函数
    public function timeManage($time)
    {


        if ($time > strtotime(date("Y-m-d"), time())) {

            if (time() - $time >= 3600)//时间在1小时外
            {
                $time = floor((time() - $time) / 3600);
                $time = date("h", $time) . '小时前';
            } elseif (time() - $time < 3600 && time() - $time > 60)//时间在1小时内
            {
                $time = floor((time() - $time) / 60);
                $time = $time . '分钟前';
            } elseif (time() - $time < 60) {
                $time = time() - $time . '秒前';
            }
        } else {
            $time = date('Y-m-d', $time);
        }
        return $time;

    }


    public function index()
    {
        $data = array();
        $request = Request::instance();
        $info = $request->param();
        $member_id = $info['member_id'];
        $userInfo = Db::name("mall_member")->where('id', $info['member_id'])->find();
        if ($userInfo) {
            //连续签到天数

            $data = $userInfo;
            $data['member_id'] = $data['id'];
            unset($data['id']);
            $data['countday'] = $this->countDay($member_id);
            $today_sign = Db::name('member_sign')->where(['sign_time' => ['>', strtotime("today")], 'id' => $member_id])->select();
            $data['today_sign'] = $today_sign ? 1 : 0;
            $data['fans'] = Db::name('hd_fans')->where('user_id', $member_id)->count();
            $data['focus'] = Db::name('hd_fans')->where('fans_id', $member_id)->count();
        }
        $this->msgInfo($userInfo, $data);
    }


//连续签到天数
    public function countDay($member_id)
    {
        //连续签到天数   60*60*24=86400为一天时间戳
        $count_day = 0;
        $count = 1;
        //该用户所有签到记录
        $sign_time = DB::name('member_sign')->where('member_id', $member_id)->field('sign_time')->order('sign_time desc')->select();
        $arr = array();
        $arr2 = array();
        //今天凌晨
        $today = strtotime("today");
        //昨天凌晨
        $yesterday = $today - 86400;
        //如果签到大于1天
        if (!isset($sign_time)) {
            $count_day = 0;
        } elseif (count($sign_time)) {
            foreach ($sign_time as $v) {
                //取出所有签到日期时间戳
                $arr[] = $v['sign_time'];
                $arr2[] = date('Y-m-d', $v['sign_time']);//将签到时间戳转成日期

            }
            foreach ($arr2 as $v) {
                $arr3[] = strtotime("$v");//将日期转换成统一时间戳
            }

        }
        if (count($arr) == 1 && $arr[0] >= $today || count($arr) == 1 && $arr[0] >= $yesterday)//只有今天或昨天签到了
        {
            $count_day = 1;
        } elseif (@$arr[0] >= $yesterday)//如果最近签到时间是昨天之前
        {
            $count_day = 1;
            for ($i = 0; $i < count($arr3) - 1; $i++) {
                if ($arr3[$i] - $arr3[$i + 1] <= 86401) {
                    $count_day = ++$count;
                } else {
                    break;
                }
            }
        }
        return $count_day;
    }





    //订单接口
    //订单状态[0]未付款,[1]已支付,[2]处理中,[3]已取消,[4]已退款,[5]已核销,[6]已完成
    /**
     * @Author   czk
     * @DateTime 2017-12-19
     * @Params
     * @param    type       [0] [全部订单]
     * @param    type       [1] [待付款]
     * @param    type       [2] [待出行]
     * @param    type       [3] [待评价]
     * @param    type       [4] [退款/取消]
     */
    public function myOrder()
    {
        $res = array(
            'code' => 0,
            'msg' => '操作失败',
            'body' => '',
        );

        $request = Request::instance();
        $info = $request->param();
        $page_size = 10;
        $type = isset($info['type']) ? $info['type'] : 1;
        $page = isset($info['page']) ? $info['page'] : 1;
        $offset = ($page - 1) * $page_size;
        $arrayInfo = array();
        if (!isset($info['member_id'])) {
            $res['msg'] = '缺少用户id';
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }
        //将超时订单转已取消
        $outtime_order = Db::name('spot_order')->where(['member_id' => $info['member_id'], 'status' => 0])->field('id,add_time')->select();
        foreach ($outtime_order as $key => $value) {
            if (time() - $outtime_order[$key]['add_time'] >= 1200)
                $result = DB::name("spot_order")->where('id', $outtime_order[$key]['id'])->update(['status' => 3]);
            // var_dump($outtime_order[$key]);
        }


        switch ($type) {
            //全部订单
            case 1:
                $orderInfo = Db::name('spot_order a')
                    ->join('shop_spot_ticket b', 'a.ticket_id=b.id')
                    ->where(['a.member_id' => $info['member_id'], 'a.delete' => 0, 'b.status' => 1])
                    ->field('a.id as order_id,a.order_sn,a.ticket_name,a.travel_date,
                                                a.price,a.order_total,a.rebate_total,b.type_ticket,a.num,a.add_time,a.status')
                    ->limit($offset, $page_size)
                    ->order('a.add_time desc')
                    ->select();
                //var_dump($orderInfo);
                $this->pageJudge($page, $page_size, $orderInfo, $arrayInfo);
                break;
            //待付款
            case 2:
                $orderInfo = Db::name('spot_order a')->join('shop_spot_ticket b', 'a.ticket_id=b.id')
                    ->where(['a.member_id' => $info['member_id'], 'a.status' => 0, 'a.delete' => 0
                        , 'b.status' => 1])
                    ->field('a.id as order_id,a.order_sn,a.ticket_name,a.travel_date,a.price,a.order_total,a.rebate_total,b.type_ticket,a.num,a.add_time,a.status')
                    ->limit($offset, $page_size)
                    ->order('a.add_time desc')
                    ->select();
                $this->pageJudge($page, $page_size, $orderInfo, $arrayInfo);
                break;
            //待出行
            case 3:
                $orderInfo = Db::name('spot_order a')->join('shop_spot_ticket b', 'a.ticket_id=b.id')
                    ->where(['a.member_id' => $info['member_id'], 'a.status' => 1, 'a.delete' => 0,
                        'b.status' => 1])
                    ->field('a.id as order_id,a.order_sn,a.ticket_name,a.travel_date,a.price,a.order_total,a.rebate_total,b.type_ticket,a.num,a.add_time,a.status')
                    ->limit($offset, $page_size)
                    ->order('a.add_time desc')
                    ->select();
                $this->pageJudge($page, $page_size, $orderInfo, $arrayInfo);
                break;
            //已完成
            case 4:
                $orderInfo = Db::name('spot_order a')->join('shop_spot_ticket b', 'a.ticket_id=b.id')
                    ->where(['a.member_id' => $info['member_id'], 'a.status' => 6, 'a.delete' => 0,
                        'b.status' => 1])
                    ->field('a.id as order_id,a.order_sn,a.ticket_name,a.travel_date,a.price,a.order_total,a.rebate_total,b.type_ticket,a.num,a.add_time,a.status')
                    ->limit($offset, $page_size)
                    ->order('a.add_time desc')
                    ->select();
                $this->pageJudge($page, $page_size, $orderInfo, $arrayInfo);
                break;
            //处理中/已退款的
            case 5:
                $orderInfo = Db::name('spot_order a')->join('shop_spot_ticket b', 'a.ticket_id=b.id')
                    ->where(['a.member_id' => $info['member_id'], 'a.delete' => 0, 'b.status' => 1])
                    ->where('a.status', ['=', 2], ['=', 4], 'or')
                    ->field('a.id as order_id,a.order_sn,a.ticket_name,a.travel_date,a.price,a.order_total,a.rebate_total,b.type_ticket,a.num,a.add_time,a.status')
                    ->limit($offset, $page_size)
                    ->order('a.add_time desc')
                    ->select();
                $this->pageJudge($page, $page_size, $orderInfo, $arrayInfo);

            default:
                break;
        }


    }

    //订单状态管理
    public function orderManage()
    {
        $res = array(
            'code' => 0,
            'msg' => '操作失败',
            'body' => '',
        );
        $request = Request::instance();
        $info = $request->param();
        if (empty($info['type']) || empty($info['order_id'])) {
            $res['msg'] = '缺少参数';
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }
        //type   1取消订单  2 删除订单  3申请退款
        $orderInfo = Db::name('spot_order')->where('id', $info['order_id'])->find();
        if (!isset($orderInfo)) {
            $res['msg'] = '没有这个订单';
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }
        switch ($info['type']) {
            case '1':
                if ($orderInfo['status'] == 0) {
                    $result = Db::name('spot_order')->where('id', $info['order_id'])->update(['status' => 3, 'up_time' => time()]);
                    if ($result) {
                        $res['code'] = 1;
                        $res['msg'] = '已取消订单';
                        $res['body']['orderManage'] = 1;
                        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        return;
                    }
                } else {
                    $res['msg'] = '订单条件不符合';
                    $res['body']['orderManage'] = 0;
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    return;
                }
                break;
            case '2':
                if ($orderInfo['status'] == 3 || $orderInfo['status'] == 4 || $orderInfo['status'] == 5 || $orderInfo['status'] == 6) {
                    $result = Db::name('spot_order')->where('id', $info['order_id'])->update(['delete' => 1, 'up_time' => time()]);
                    if ($result) {
                        $res['code'] = 1;
                        $res['msg'] = '已删除订单';
                        $res['body']['orderManage'] = 1;
                        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        return;
                    }
                } else {
                    $res['msg'] = '订单条件不符合';
                    $res['body']['orderManage'] = 0;
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    return;
                }
            //退款
            case '3':
                if ($orderInfo['status'] == 1 && isset($info['refund_reason'])) {
                    $result = Db::name('spot_order')->where('id', $info['order_id'])
                        ->update(['status' => 2, 'refund_reason' => $info['refund_reason'], 'up_time' => time()]);
                    if ($result) {
                        $res['code'] = 1;
                        $res['msg'] = '审核中';
                        $res['body']['orderManage'] = 1;
                        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        return;
                    }
                } else {
                    $res['msg'] = '订单条件不符合退款,或退款理由不充分';
                    $res['body']['orderManage'] = 0;
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    return;
                }
            //取消退款
            case '4':
                if ($orderInfo['status'] == 2) {
                    $result = Db::name('spot_order')->where('id', $info['order_id'])->update(['status' => 1, 'up_time' => time()]);
                    if ($result) {
                        $res['code'] = 1;
                        $res['msg'] = '已付款';
                        $res['body']['orderManage'] = 1;
                        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        return;
                    }
                } else {
                    $res['msg'] = '订单条件不符合';
                    $res['body']['orderManage'] = 0;
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    return;
                }
            default:
                $res['msg'] = '呃呃呃,什么情况';
                $res['body']['orderManage'] = 0;
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
        }


    }


    //地址的增删改查
    public function myAddress()
    {
        $res = array(
            'code' => 0,
            'msg' => '操作失败',
            'body' => array(),
        );
        $request = Request::instance();
        $info = $request->param();

        if (empty($info['type'])) {
            $res['msg'] = '缺少type,增1 删2 改3 查4';
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        } else {
            $type = $info['type'];
            foreach ($info as $k => $v) {
                if ($k == 'type') unset($info[$k]);
            }
        }

        switch ($type)// 增1 删2 改3 查4
        {         //增
            case 1:
                if (empty($info['member_id']) || empty($info['phone']) || empty($info['address']) || empty($info['username'])
                    || empty($info['post_code']) || empty($info['province_city'])) {
                    $res['msg'] = '缺少参数';
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    return;
                } elseif (!preg_match("/^1\d{10}$/", $info['phone'])) {
                    $res['msg'] = '手机格式错误！';
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    return;
                } else {
                    $result = Db::name('member_address')->insert($info);
                    if ($result) {
                        $res['code'] = 1;
                        $res['msg'] = '增加成功';
                        $info['address_id'] = $result;
                        $res['body'] = $info;
                        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        return;
                    }
                }
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            //删
            case 2:
                if (empty($info['address_id'])) {
                    $res['msg'] = '缺少地址id';
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    return;
                } else {
                    $result = DB::name('member_address')->where('id', $info['address_id'])->delete();
                    if ($result) {
                        $res['code'] = 1;
                        $res['msg'] = '删除成功';
                        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        return;
                    }
                }
                echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                break;
            //改
            case 3:
                if (empty($info['address_id']) || empty($info['phone']) && empty($info['address']) && empty($info['username']) && empty($info['post_code']) && empty($info['province_city'])) {
                    $res['msg'] = '缺少参数';
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    return;
                } else {
                    $id = $info['address_id'];
                    unset($info['address_id']);
                    $result = Db::name('member_address')->where('id', $id)->update($info);
                    if ($result) {
                        $res['code'] = 1;
                        $res['msg'] = '更改成功';
                        $res['body'] = $info;
                        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        return;
                    } else {
                        $res['msg'] = '更改失败';
                        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        return;
                    }

                }

            //查
            case 4:
                if (empty($info['member_id'])) {
                    $res['msg'] = '缺少地址id';
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    return;
                }
                $result = Db::name('member_address')->where('member_id', $info['member_id'])->field('id as address_id,phone,address,username,status,post_code,province_city')->select();
                $this->msgInfo($result, $result);
                break;
            //修改默认地址
            case 5:
                if (empty($info['address_id']) || empty($info['member_id'])) {
                    $res['msg'] = '缺少地址id';
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    return;
                }
                $result1 = Db::name('member_address')->where(['member_id' => $info['member_id'], 'status' => 1])->setField('status', '0');
                $result2 = Db::name('member_address')->where('id', $info['address_id'])->setField('status', '1');
                if ($result2) {
                    $res = array(
                        'code' => 1,
                        'msg' => '操作成功',
                        'body' => array(),
                    );
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;
                }
        }

    }


//我的收藏 资讯 游记 问答 商品
    public function myCollect()
    {
        $request = Request::instance();
        $info = $request->param();
        $type = isset($info['type']) ? $info['type'] : 0;
        $member_id = $info['member_id'];
        $page_size = 10;
        $page = isset($info['page']) ? $info['page'] : 1;
        $offset = ($page - 1) * $page_size;
        $arrayInfo = array();
        //调取 0-全部收藏        1.普通新闻  专题新闻  2.游记  3.问答  4.景点门票

        $all_collect = array();
        if ($type == 0) {
            $collect = DB::name('member_collect')->where('member_id', $member_id)->field('type, post_id, add_time')->order('add_time desc')->limit($offset, $page_size)->select();
        } elseif ($type == 1) {
            $collect = DB::name('member_collect')->where('member_id', $member_id)->where('type', ['=', 1], ['=', 2], 'or')->field('type, post_id, add_time')->order('add_time desc')->limit($offset, $page_size)->select();
        } elseif ($type == 2 || $type == 3 || $type == 4) {
            $collect = DB::name('member_collect')->where('member_id', $member_id)->where('type', $type + 1)->field('type, post_id, add_time')->order('add_time desc')->limit($offset, $page_size)->select();
        } else {
            $res = array(
                'code' => 0,
                'msg' => "type参数错误",
                'body' => array(),
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        for ($i = 0; $i < count($collect); $i++) {
            //普通新闻
            if ($collect[$i]['type'] == 1) {

                $all_collect[$i] = Db::name('hd_news a')
                    ->join('hd_article_cate b', 'a.cate_id=b.id')
                    ->where('a.id', $collect[$i]['post_id'])
                    ->field('a.id as collect_id,a.title, a.pic1 as img,b.name as cate_name')
                    ->find();
                if (isset($all_collect[$i])) {
                    $all_collect[$i]['add_time'] = $collect[$i]['add_time'];
                    $all_collect[$i]['price'] = '';
                    $all_collect[$i]['type'] = 1;
                } else {
                    unset($all_collect[$i]);
                }
                /*  var_dump($all_collect[$i]);
                   echo '<br/>';
                   echo '<br/>';*/
            }
            //专题新闻
            if ($collect[$i]['type'] == 2) {
                $all_collect[$i] = Db::name('hd_article_topic')
                    ->where('id', $collect[$i]['post_id'])
                    ->field('id as collect_id,title,thumb as img')
                    ->find();

                if (isset($all_collect[$i])) {
                    $all_collect[$i]['add_time'] = $collect[$i]['add_time'];
                    $all_collect[$i]['price'] = '';
                    $all_collect[$i]['type'] = 2;
                    $all_collect[$i]['cate_name'] = '惠说新闻';
                } else {
                    unset($all_collect[$i]);
                }
            }

            //游记
            if ($collect[$i]['type'] == 3) {

                $all_collect[$i] = Db::name('travels')
                    ->where('id', $collect[$i]['post_id'])
                    ->field('id as collect_id,title, pic1 as img')
                    ->find();
                if (isset($all_collect[$i])) {
                    $all_collect[$i]['add_time'] = $collect[$i]['add_time'];
                    $all_collect[$i]['price'] = '';
                    $all_collect[$i]['type'] = 3;
                    $all_collect[$i]['cate_name'] = '游记';
                } else {
                    unset($all_collect[$i]);
                }

            }
            //2问答
            if ($collect[$i]['type'] == 4) {

                $all_collect[$i] = Db::name('qa_question')
                    ->where('id', $collect[$i]['post_id'])
                    ->field('id as collect_id,title, img')
                    ->find();

                if (isset($all_collect[$i])) {
                    $all_collect[$i]['add_time'] = $collect[$i]['add_time'];
                    $all_collect[$i]['price'] = '';
                    $all_collect[$i]['type'] = 4;
                    $all_collect[$i]['cate_name'] = '问答';
                } else {
                    unset($all_collect[$i]);
                }
            }
            //门票
            if ($collect[$i]['type'] == 5) {

                $all_collect[$i] = Db::name('shop_spot')
                    ->where('id', $collect[$i]['post_id'])
                    ->field('id as collect_id,title, thumb as img ,shop_price as price')
                    ->find();

                if (isset($all_collect[$i])) {
                    $all_collect[$i]['add_time'] = $collect[$i]['add_time'];
                    $all_collect[$i]['type'] = 5;
                    $all_collect[$i]['cate_name'] = '门票';
                } else {
                    unset($all_collect[$i]);
                }
            }
            /*      if($collect[$i]['type']=='team'//4)
                  {
                        $all_collect[$i]=Db::name('mall_team_product_detail')
                        ->where('productid',$collect[$i]['post_id'])
                        ->field('id as team_id,productName as title, thumb as img, priceAdultMin as price')
                        ->find();
                        $all_collect[$i]['add_time']=$collect[$i]['add_time'];
                        $all_collect[$i]['type']=4;
                        $all_collect[$i]['cate_name']='跟团游';
                  }*/

        }
        $this->pageJudge($page, $page_size, $all_collect, $arrayInfo);

    }


//我的点评 游记评论 咕咕评论 资讯评论
    public function myComments()
    {
        $request = Request::instance();
        $info = $request->param();
        $member_id = $info['member_id'];

        $res = array(
            'code' => 0,
            'msg' => '操作失败',
            'body' => array(),
        );
        $myTravels = DB::name('travels_comment')->where('member_id', $member_id)->select();
        if ($myTravels) {
            $data['myTravels'] = $myTravels;
        } else {
            $data['myTravels'] = [];
        }
        $myGugu = DB::name('gugu_comment')->where('member_id', $member_id)->select();
        if ($myGugu) {
            $data['myGugu'] = $myGugu;
        } else {
            $data['myGugu'] = [];
        }
        $my_hd = DB::name('hd_comment')->where('member_id', $member_id)->select();
        if ($my_hd) {

            $data['my_hd'] = $my_hd;

        } else {
            $data['my_hd'] = [];
        }
        if ($data) {
            $res = array(
                'code' => 1,
                'msg' => '操作成功',
                'body' => $data,
            );
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

//我的问答接口
    public function myQa()
    {
        $request = Request::instance();
        $info = $request->param();
        $member_id = $info['member_id'];
        $type = isset($info['type']) ? $info['type'] : 0;
        $page_size = 10;
        $page = isset($info['page']) ? $info['page'] : 1;
        $offset = ($page - 1) * $page_size;

        if (empty($info['member_id'])) {
            $res['msg'] = '缺少用户id';
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        } else {

            //我的回答数量
            $arrayInfo['my_answer'] = DB::name('qa_answer')->where('user_id', $member_id)->count();
            //金牌回答
            $arrayInfo['gold_answer'] = db::name('qa_answer')
                ->where('user_id', $member_id)
                ->where('favor_num', '>', 50)
                ->count();
            //采纳率  金牌回答数/我回答的总问题数
            $arrayInfo['accept'] = $arrayInfo['my_answer'] ? $arrayInfo['gold_answer'] / $arrayInfo['my_answer'] : 0;
            //总阅读量
            $arrayInfo['total_read_num'] = Db::name('qa_answer')->where('user_id', $member_id)->sum('read_num');
            //总评论数
            $arrayInfo['total_comment_num'] = Db::name('qa_answer')->where('user_id', $member_id)->sum('comment_num');

        }
        // echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        switch ($type) {
            //推荐问题10个
            case 0:
                $question = db::name('qa_question')->where('user_id', $member_id)->order('add_time desc')
                    ->field('id as question_id ,title,read_num,fans_num,answer_num,add_time')
                    ->limit($offset, $page_size)->select();
                //每个问题的回答数
                /*  foreach ($question as $key => $value)
                  {

                             $question[$key]['comment_num']=db::name('qa_answer')
                                                            ->where('question_id',$question[$key]['question_id'])
                                                            ->count();
                   }  */
                foreach ($question as $key => $value) {
                    $time = strtotime($question[$key]['add_time']);
                    $question[$key]['add_time'] = $this->timeManage($time);
                    $question[$key]['answer_id'] = '';
                    $question[$key]['answer_content'] = '';
                    $question[$key]['favor_num'] = '';

                }
                $this->pageJudge($offset, $page_size, $question, $arrayInfo);

                break;
            //我的回答
            case 1:
                $my_answer = db::name('qa_answer  a')->join('qa_question b', 'a.question_id=b.id')
                    ->where('a.user_id', $member_id)
                    ->order('b.add_time desc')
                    ->field('b.id as question_id,b.title,b.read_num,b.fans_num,b.answer_num,b.add_time
                                            , a.id as answer_id , a.content as answer_content,a.favor_num')
                    ->limit($offset, $page_size)
                    ->select();

                foreach ($my_answer as $key => $value) {
                    $time = strtotime($my_answer[$key]['add_time']);
                    $my_answer[$key]['add_time'] = $this->timeManage($time);
                }

                $this->pageJudge($offset, $page_size, $my_answer, $arrayInfo);
                break;
            //我关注的问题
            case 2:
                $my_question = db::name('qa_attention a')->join('qa_question b', 'a.question_id=b.id')
                    ->where('a.member_id', $member_id)
                    ->order('b.add_time desc')
                    ->field('b.id as question_id,b.title, b.read_num, b.fans_num, b.answer_num,b.add_time')
                    ->limit($offset, $page_size)
                    ->select();
                foreach ($my_question as $key => $value) {
                    $time = strtotime($my_question[$key]['add_time']);
                    $my_question[$key]['add_time'] = $this->timeManage($time);
                    $my_question[$key]['answer_id'] = '';
                    $my_question[$key]['answer_content'] = '';
                    $my_question[$key]['favor_num'] = '';
                }

                $this->pageJudge($offset, $page_size, $my_question, $arrayInfo);
                break;
            //我提出的问题
            case 3:
                $my_question = db::name('qa_question')->where('user_id', $member_id)->order('add_time desc')->field('id as question_id ,title,read_num,fans_num,answer_num,add_time')->limit($offset, $page_size)
                    ->select();

                foreach ($my_question as $key => $value) {
                    $time = strtotime($my_question[$key]['add_time']);
                    $my_question[$key]['add_time'] = $this->timeManage($time);
                    $my_question[$key]['answer_id'] = '';
                    $my_question[$key]['answer_content'] = '';
                    $my_question[$key]['favor_num'] = '';
                }

                $this->pageJudge($offset, $page_size, $my_question, $arrayInfo);
                break;
        }
    }


//我的消息 咕咕消息 问答消息 系统消息
    public function myMsg()
    {
        $request = Request::instance();
        $info = $request->param();
        $res = array(
            'code' => 0,
            'msg' => '操作失败',
            'body' => array(),
        );

        if (empty($info['member_id']) || empty($info['type'])) {
            $res['msg'] = '缺少参数';
            return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $member_id = $info['member_id'];

        $myMsg = db::name('member_message')->where('receive_id', $member_id)->field('sender_id,msg_title,msg_content,msg_time,msg_status')->select();
        var_dump($myMsg);


    }

    /**
     * 个人资料修改
     */

    public function modInfo()
    {
        $request = Request::instance();
        $info = $request->param();

        $res = array(
            'code' => 0,
            'msg' => '操作失败',
            'body' => array(),
        );

        if (empty($info['member_id'])) {
            $res['msg'] = '缺少用户id';
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $info['id'] = $info['member_id'];
        unset($info['member_id']);

        if (!empty($info['headimg'])) {
            $data = base64_decode($info['headimg']);
            $filename_str = time() . $info['id'] . '.jpg';
            $filename = ROOT_PATH . '/public/uploads/headimg/' . $filename_str;
            file_put_contents($filename, $data);
            $filename_str = 'http://zhlsfnoc.com/uploads/headimg/' . $filename_str;
            $info['headimg'] = $filename_str;

        } else {
            unset($info['headimg']);
        }

        $result = Db::name('mall_member')->where('id', $info['id'])->update($info);

        if ($result) {

            $this->index($info['id']);
            return;
        } else {
            echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }


    }


}
	
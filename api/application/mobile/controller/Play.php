<?php
namespace app\mobile\controller;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;
use think\Log;
class Play extends Base {
	public function index()
    {
        
        $info = Db::name("choujiang_log")->field("member_id,cj_name,too_mall_member.name,too_choujiang_log.add_time")->join("too_mall_member","too_mall_member.id = too_choujiang_log.member_id")->limit(50)->order("add_time desc")->select();

        $this->assign("info",$info);
		return $this->fetch();
	}

    public function addlog()
    {
        $param = request()->param();
        $member_id = Session::get("member_id");
        $str = $param['result'];
        $data_num = mb_substr($str, -3);
        $lxb_num = preg_replace('/\D/s', '', $str);
        $choujiang_num = Db::name("choujiang_num")->where('member_id',$member_id)->value("num");
        $res = array();
        $travel_details['member_id'] = $member_id;
        $travel_details['title'] = '大转盘抽奖';
        $travel_details['num'] = '+'.$lxb_num;
        $travel_details['add_time'] = time();
        $travel_details['type'] = 0;
        if($choujiang_num < 2 && $choujiang_num != NULL)
        {
            if(!empty($param['result']))
            {
                $data['member_id'] = $member_id ;
                $data['add_time'] = time();
                $data['cj_name']  = $param['result'];
                if($param['result'] !== '对不起,您的抽奖次数已用完!' && $param['result'] !== '谢谢参与')
                {
                    if($data_num === '旅行币')
                    {
                        //插入旅行币来源
                        Db::name("travel_details")->insert($travel_details);
                        $rfe=Db::name("mall_member")->where("id",$member_id)->setInc("score",$lxb_num);
                        log::write($rfe);
                    }
                    
                    
                    //插入抽奖记录
                    $bool =  Db::name("choujiang_log")->insert($data);

                    Db::name("choujiang_num")->where("member_id",$member_id)->setInc("num",1);
                    if($bool)
                    {
                        $res = array("status"=>true,"info"=>$param['result']);
                    }
                    else
                    {
                         $res = array("status"=>false,"info"=>"网络异常,请重连后再试!");
                    }
                    return json($res);

                }
   
            }
            else
            {
                $res = array("status"=>false,"info"=>"网络异常,请重连后再试!");
            }
            return json($res);
        }
        elseif($choujiang_num == 2)
        {
            $res = array("status"=>false,"info"=>"对不起,您今日抽奖次数已用完!");

        }
        elseif($choujiang_num == NULL)
        {
            $chj_data['member_id'] = $member_id;
            $chj_data['num'] = 1;

            Db::name("choujiang_num")->insert($chj_data);
            if(!empty($param['result']))
            {
                if($data_num === '旅行币')
                {
                    //插入旅行币来源
                    Db::name("travel_details")->insert($travel_details);
                    $rfe=Db::name("mall_member")->where("id",$member_id)->setInc("score",$lxb_num);
                        log::write($rfe);
                }
                $data['member_id'] = $member_id ;
                $data['add_time'] = time();
                $data['cj_name']  = $param['result'];
                if($param['result'] != '对不起,您的抽奖次数已用完!')
                {
                    //插入抽奖记录
                    $bool =  Db::name("choujiang_log")->insert($data);
                    if($bool)
                    {
                        $res = array("status"=>true,"info"=>$param['result']);
                    }
                    else
                    {
                         $res = array("status"=>false,"info"=>"网络异常,请重连后再试!");
                    }
                    return json($res);

                }
   
            }
            else
            {
                $res = array("status"=>false,"info"=>"网络异常,请重连后再试!");
            }
            return json($res);

        }
        return json($res);
        

    }
    
   

    //中奖记录
	public function zhong_log()
    {
        $member_id = Session::get("member_id");
        $info = Db::name("choujiang_log")->where("member_id",$member_id)->order("add_time desc")->select();
        foreach ($info as $key => $value) {
           if($value['cj_name'] == '谢谢参与')
            {
                Db::name("choujiang_log")->where("cj_name",'谢谢参与')->delete();
            }
        }
        

        $this->assign("info",$info);
        return $this->fetch();
    }
}
<?php
namespace app\mobile\controller;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;
use think\Log;

Class Commdata
{
	 //每隔20小时  自动清零
    public function zidong()
    {
        $interval=60*60*15;// 每隔半小时运行
        do{
            $bool = Db::name("choujiang_num")->where("status",0)->delete();
            log::write($bool);

          //ToDo
          sleep($interval);// 等待5分钟
        }
        while(true);
    }


    public function CommData()
    {
        ignore_user_abort();//关掉浏览器，PHP脚本也可以继续执行.
        set_time_limit(0);// 通过set_time_limit(0)可以让程序无限制的执行下去
        $interval=60*24;// 每隔半小时运行
        //获取当前时间戳
        $current_time = date('Y-m-d');
        $where['travel_date'] = $current_time;
        $where['status'] = 1;
        do{
                $order_info = Db::name("spot_order")->where($where)->select();
                $traveler_id_s = [];
                foreach ($order_info as $val) {
                    if(!empty($val['UUcode']))
                    {
                        $url = 'http://61.186.100.83:8081/Order/QueryOrderVaildInfo?uucode='.$val['UUcode'].'&pftOrdersn='.$val['order_code'];
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_URL, $url);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        $output = curl_exec($curl);
                        $datalist = json_decode($output,true);
                        $infos_ = json_decode($datalist['Data']['VaildList'],true);


                        //获取当前时间
                        //当前时间
                        $current_time = strtotime(date('Y-m-d',time()));;
                        //获取游玩时间
                        $use_date = strtotime($val['travel_date']);
                        //if()
                        if($current_time > $use_date && $val['status'] == 1)
                        {
                           $tk_price = ($val['num'] - $traveler_num) * $val['price'];

                           Db::name("spot_order")->where('id',$val['id'])->setField("refund_price",$tk_price);
                           Db::name("spot_order")->where('id',$val['id'])->setField("status",2);
                        }


                        //把数据返回给票务云
                        $pw_infos['mobile'] = $val['mobile'];
                        $pw_infos['idCardS'] = json_encode($infos_);
                        $pw_infos['qrCode'] = $val['UUcode'];
                        $insertPwUrl = 'http://cloud.zhonghuilv.net/Spot/markupIdcard';
                        $insertPw_output = Post($pw_infos,$insertPwUrl);

                        foreach ($infos_ as $value) {
                            if($value['identity'] != 1)
                            {
                                $data['member_id'] = $val['member_id'];
                                $data['use_name'] = $value['UserName'];
                                $data['use_card'] = $value['identity'];
                                $data['status'] = 1;
                                $data['add_time'] = time();

                                $traveler_id = Db::name("member_traveler_info")->insertGetId($data);
                               $bool =  array_push($traveler_id_s,$traveler_id);
                               if($bool)
                               {
                                        $refund_obj = new Refund();
                                      $refund_info = $refund_obj->index($val['id']);
                               }
                               else
                               {
                                echo 0;
                               }
                            }
                            
                        }
                    }     
                }

                sleep($interval);
            }while(true);
        
    }
}
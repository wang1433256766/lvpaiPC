<?php
namespace app\api\controller;
use think\Request;
use think\Db;
  class Sign{


//签到页面 已签到日期返回
   public function signDay()
    {
            $request = Request::instance();
            $info = $request->param();
            
                $res = array(
                            'code' => 1,
                            'msg' => '操作成功',
                            'body' => array(),
                            );

            if(empty($info['member_id'])){
                $res['code']=0;
                $res['msg']='缺少用户id';
               echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
               return;
             }
        $member_id=$info['member_id']; 

        //本月开始时间戳  
        $beginThismonth=mktime(0,0,0,date('m'),1,date('Y')); 
       // $beginThismonth=mktime(0,0,0,1,3,2018); 
        //本月的第一天是星期几 星期日=0
        $date_first=date('w', $beginThismonth);
         // 判断返回的起始日期
       
        $start_return_time=$beginThismonth-86400*intval($date_first);
     
        //var_dump(date('Y-m-d',$start_return_time));
        //需要本月签到日期 
        $this_month_sign=DB::name('member_sign')->where(['sign_time'=>['>',$start_return_time],'member_id'=>$member_id])->field('sign_time,gps_sign_id')->select();

        $sign_date=array();
            foreach ($this_month_sign as  $k=>$v) 
              {
                 $sign_date[$k]['sign_year']=date('Y',$v['sign_time']); 

                 $sign_date[$k]['sign_month']=date('m',$v['sign_time']);

                 $sign_date[$k]['sign_day']=date('d',$v['sign_time']);

                 $sign_date[$k]['sign_date']=date('Y-m-d',$v['sign_time']);

                 $sign_date[$k]['is_gps']=$v['gps_sign_id']?1:0;
              }
        $res['body']['count_day']=$this->countDay($member_id);
        $res['body']['sign_date']=$sign_date;
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 

    }





    //每日签到模块
    public function dailySign()
	    {   
			      $request = Request::instance();
            $info = $request->param();
            
            if(empty($info['member_id'])){
              $res = array(
                            'code' => 0,
                            'msg' => '缺少用户id',
                            'body' => array(),
                            );
               echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
               return;
             }
        $member_id=$info['member_id'];

        //判断用户id是否存在
        $is_member=db::name('mall_member')->where('id',$member_id)->value('id');
        if(!$is_member){
           $res = array(
                            'code' => 0,
                            'msg' => '用户id不存在',
                            'body' => array(),
                            );
           echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
           return;
        }


     $last_sign_time=DB::name('member_sign')->where(['member_id'=>$member_id,'gps_sign_id'=>0])->max('sign_time');
     if(!$last_sign_time){
                $last_sign_time=0;
          }
    // $total_sign=$this->countDay($member_id);
     //签到条件判断 如果最后签到时间<当日零点时间
     if( $last_sign_time<strtotime(date("Y-m-d"),time()))
     {
             //签到时间写入
               $data['member_id']=$member_id;
               $data['sign_time']=time();
               $result=DB::name('member_sign')->insert($data);
               $score=2;
              //积分表按规则写入
              $result2=DB::name('mall_member')->where('id',$member_id)->setInc('score',$score);
              $data['score']=$score;
              $data['total_score']=Db::name('mall_member')->where('id',$member_id)->value('score');
              $data['is_sign']=1; 
                if($result2){

                    //任务表写入记录
                    $task['user_id']=$member_id;
                    $task['finish_time']=time();
                    $task['type']=3;
                    $taskresult=db::name('member_task')->insert($task);
          
                    $res = array(
                              'code' => 1,
                              'msg' => '签到成功',
                              'body' => $data,
                              );
                    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);                  
                  }
     }

	     else{
	     	 $res = array(
		            'code' => 0,
		            'msg' => '今天已经签过到了',
		            'body' => ['is_sign'=>1],
		        );
  		 echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  	    }

    } 

	    


//连续签到天数
public function countDay($member_id)
    {
       //连续签到天数   60*60*24=86400为一天时间戳  
        $count_day=0;
        $count=1;
         //该用户所有签到记录
        $sign_time=DB::name('member_sign')->where('member_id',$member_id)->field('sign_time')->order('sign_time desc')->select();
        $arr=array();
        $arr2=array();
        //今天凌晨
        $today=strtotime("today");
        //昨天凌晨
        $yesterday=$today-86400;
        //如果签到大于1天
         if(!isset($sign_time)){
            $count_day=0;
         }
        elseif(count($sign_time))
        {
                foreach ($sign_time as  $k=>$v) 
                    {
                      //取出所有签到日期时间戳
                      $arr[]=$v['sign_time'];
                      $arr2[]=date('Y-m-d',$v['sign_time']);//将签到时间戳转成日期
                      //var_dump($arr2[$k]);
                    }
                    foreach ($arr2 as $v) {
                        $arr3[]=strtotime("$v");//将日期转换成统一时间戳
                    }

        }
            if(count($arr)==1&&$arr[0]>= $today||count($arr)==1&&$arr[0]>=$yesterday)//只有今天或昨天签到了 
              {
                    $count_day=1;
              }
               elseif(@$arr[0]>=$yesterday)//如果最近签到时间是昨天之前
              {
                     $count_day=1;
                for($i=0;$i<count($arr3)-1;$i++)
                {
                     if($arr3[$i]-$arr3[$i+1]<=86401&&$arr3[$i]-$arr3[$i+1]!=0)
                        {   
                           $count_day=++$count;
                        }
                         else{break;}
                }
            }
          return $count_day;
    } 

//特殊签到
  public function  gpsSign()
  {
            $request = Request::instance();
            $info = $request->param();
            
           if(empty($info['member_id'])||empty($info['lng'])||empty($info['lat'])){

                  $res = array(
                                'code' => 0,
                                'msg' => '缺少参数',
                                'body' => array(),
                                );
                   echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
                   return;

             }
      
       //判断用户id是否存在
        $is_member=db::name('mall_member')->where('id',$info['member_id'])->value('id');
        if(!$is_member){
           $res = array(
                            'code' => 0,
                            'msg' => '用户id不存在',
                            'body' => array(),
                            );
           echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);  
           return;
        }          
 
        //取出所有定点
        $result=db::name('member_gpssign')->select();
        //遍历比对 找出所有的签到点 离定点的距离
       foreach ($result as $key => $value) {
           $sign_spot[$key]['id']=$result[$key]['id'];
           $sign_spot[$key]['address_id']=$result[$key]['address_id'];
           $sign_spot[$key]['radius']=$result[$key]['radius'];
           $sign_spot[$key]['distance']=$this->getDistance($info['lat'],$info['lng'],$result[$key]['lat'],$result[$key]['lng']);
         
       }
          
          
          //选出最小的距离点
          $min=$sign_spot[0]['distance'];
          $nearest_address=$sign_spot[0];
         for($i=1;$i<count($sign_spot);$i++){

             
                 if ($sign_spot[$i]['distance']<=$min)
                   {
                         $min=$sign_spot[$i]['distance'];
                         $nearest_address=$sign_spot[$i];
                    }
          }
         //nearest_address 是否在半径范围内
         if($nearest_address['radius']-$nearest_address['distance']<0){
                   $res = array(
                      'code' => 0,
                      'msg' => '特殊签到失败,不在范围内',
                      'body' => array(),
                  );
                 echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                 return;
         }
         else{

                     //特殊签到地点id
                     $data['gps_sign_id']=$nearest_address['address_id'];
                     
                     //上次该地点签到时间
                      $last_sign_time=DB::name('member_sign')->where(['member_id'=>$info['member_id'],'gps_sign_id'=>$data['gps_sign_id']])->max('sign_time');
                   
                          if(!$last_sign_time)
                             {
                              $last_sign_time=0;
                              }
                 //签到条件判断 如果最后签到时间<当日零点时间
                    if( $last_sign_time<strtotime(date("Y-m-d"),time()))
                         {   
                             //签到表写入记录   
                             $data['sign_time']=time();
                             $data['member_id']=$info['member_id'];
                             $result=db::name('member_sign')->insert($data);
                             //签到积分到账
                             $data['score']=db::name('member_gpssign')->where('id',$nearest_address['id'])->value('sign_score');
                             $result2=db::name('mall_member')->where('id',$info['member_id'])->setInc('score',$data['score']);

                                 if($result&&$result2){

                                      //任务表写入记录
                                       $task['user_id']=$info['member_id'];
                                       $task['finish_time']=time();
                                       $task['type']=4;
                                       $taskresult=db::name('member_task')->insert($task);

                                  $data['sign_adress']=db::name('member_gpssign')->where('id',$nearest_address['id'])->value('sign_address');

                                           $res = array(
                                                          'code' => 1,
                                                          'msg' => '特殊签到成功',
                                                          'body' => $data,
                                                          );
                                   echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);            
                                 }
                        }
                          else{
                               $res = array(
                                'code' => 0,
                                'msg' => '该地点今天已经签过到了',
                                'body' => array(),
                                    );
                              echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                  
                
                               }
 
               }





  }

//距离计算函数
function getDistance($lat1, $lng1, $lat2, $lng2){   
          $earthRadius = 6367000; //approximate radius of earth in meters   
          $lat1 = ($lat1 * pi() ) / 180;   
          $lng1 = ($lng1 * pi() ) / 180;   
          $lat2 = ($lat2 * pi() ) / 180;   
          $lng2 = ($lng2 * pi() ) / 180;   
          $calcLongitude = $lng2 - $lng1;   
          $calcLatitude = $lat2 - $lat1;   
          $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);   
          $stepTwo = 2 * asin(min(1, sqrt($stepOne)));   
          $calculatedDistance = $earthRadius * $stepTwo;   
          return round($calculatedDistance);   
        }   

}
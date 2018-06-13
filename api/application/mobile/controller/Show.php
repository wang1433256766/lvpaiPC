<?php

namespace app\mobile\controller;
use think\Controller;
use think\Model;
use think\Db;
use think\Session;
use com\JSSDK2;
use think\log;
use think\Config;
class Show extends Base
{
//时间处理函数 
   
   public function timeManage($time)
        {
             $time= strtotime($time);
             
             if ($time>strtotime(date("Y-m-d"),time())) 
               {
       
                     if(time()-$time>=3600)//时间在1小时外
                               {
                                $time= floor((time()-$time)/3600);
                                $time=date("h",$time).'小时前';
                               }  

                           elseif(time()-$time<3600&&time()-$time>60)//时间在1小时内
                                   {
                                    $time= floor((time()-$time)/60);
                                    $time=$time.'分钟前';
                                    }

                              elseif(time()-$time<60)
                                     {
                                        $time=time()+1-$time.'秒前';
                                         }
                }
                                         
               else{
                       $time=date('Y-m-d',$time);
                   }
                     return $time;
                                  
        }

//我的秀秀

    public function show()
    {
         //$member_id=1;
       $member_id=session::get("member_id");
        $param = request()->param(); 

        //var_dump($member_id);

        $page = isset($param['page']) ? $param['page'] : 1;

        $pagesize=3;

        $offset = ($page - 1) * $pagesize;

      if(isset($param['member_id']))
             {
                    $where="a.member_id=".$param['member_id'];
              }
             else{
                       $where='';
                 }

        $show = Db::name('gugu_article a')->field('a.id as article_id, m.id as member_id, a.add_time, a.gugu_content, a.video_path, a.cover_img, a.address, a.like_num as favor_num, a.read_num, m.nickname, m.headimg as headimgurl, a.img_path, a.comment_num, a.duration')->
            join('mall_member m', 'm.id = a.member_id')->

            where($where)->

            order('add_time desc')->limit($offset, $pagesize)->select();
       //判断是否是最后一页     
       if(count($show)<$pagesize)
       {
              $page=0;
       }
       else{
             $page=$page+1;
       }  

        //统一时间处理 +顺便把图片字符串转数组+ 还有当前用户点赞加亮
            foreach ($show as $key => $value)
               {
                 $show[$key]['add_time']= $this->timeManage($show[$key]['add_time']);
                 $is_up=Db::name('gugu_favor')->where(['cur_user_id'=>$member_id,
                                                                              'post_id'=>$show[$key]['article_id']])->find();
                       if ($is_up) 
                       {
                         $show[$key]['is_up']=1;
                       }
                       else
                       {
                        $show[$key]['is_up']=0;
                       }

                               if(!empty($show[$key]['img_path']))
                                 {
                                        $show[$key]['img_path']=explode(",", $show[$key]['img_path']);
                                   }
                                   else{

                                        $show[$key]['img_path']=[];
                                   }
               }
          //var_dump($show);
                   if (request()->isAjax()){
                                
                                  return $show;

                                  }
            $this->assign('show',$show);
            $this->assign('page',$page);
            return $this->fetch('show');
    }

    public function showDetails($article_id)
    {
       $member_id=session::get("member_id");
       //$member_id=1;
       //单条秀秀数据
        $showDetails = Db::name('gugu_article a')->field('a.id as article_id, m.id as member_id, a.add_time, a.gugu_content, a.video_path, a.cover_img, a.address, a.like_num as favor_num, a.read_num, m.nickname, m.headimg as headimgurl, a.img_path, a.comment_num, a.duration')->
            join('mall_member m', 'm.id = a.member_id')->

            where('a.id',$article_id)->

            order('add_time desc')->find();
          
  
           //统一时间处理 
           $showDetails['add_time']= $this->timeManage($showDetails['add_time']);
           //把图片字符串转数组
           if(!empty($showDetails['img_path']))
             {
                    $showDetails['img_path']=explode(",", $showDetails['img_path']);
               }
               else{

                    $showDetails['img_path']=[];
               }
          //点赞图标加亮
      
                 $is_up=Db::name('gugu_favor')->where(['cur_user_id'=>$member_id,
                                                                              'post_id'=>$showDetails['article_id']])->find();
                       if ($is_up) 
                       {
                         $showDetails['is_up']=1;
                       }
                       else
                       {
                        $showDetails['is_up']=0;
                       }

     //单条秀秀所有评论
     $comment = Db::name('gugu_comment c')->field('c.id as comment_id, c.info as content, c.add_time, c.favor_num, m.nickname, m.headimg')->

        join('mall_member m', 'm.id = c.member_id')/*->order('add_time desc')*/->where(['gugu_id'=>$article_id,'ban'=>0])->select();
    //评论时间处理
      foreach ($comment as $key => $value) {
           $comment[$key]['add_time']= $this->timeManage($comment[$key]['add_time']);
        }
         // var_dump($comment);

         $this->assign('showDetails',$showDetails);
         $this->assign('comment',$comment);

         return $this->fetch('showDetails');
    }


//点赞
    public function up()
    {
        $param= request()->param();
        //$member_id=1;
         $member_id=session::get("member_id");
        $data['cur_user_id']=$member_id;
        $data['post_id']=$param['article_id'];
        $data['type']=1;
        $data['status']=1;
        //首先判断是否点赞过
        $is_up= db::name('gugu_favor')->where(['cur_user_id'=>$member_id,'type'=>1,'post_id'=>$param['article_id']])->find();
        if ($is_up) {
              //取消点赞 
              $result1=db::name('gugu_article')->where('id',$param['article_id'])->setDec('like_num',1);
              $result=db::name('gugu_favor')->where(['cur_user_id'=>$member_id,'type'=>1,'post_id'=>$param['article_id']])->delete();
                 if ( $result) 
                  {
                       return  0;
                  }
        }
        else{
                 //点赞
                  $result1=db::name('gugu_article')->where('id',$param['article_id'])->setInc('like_num',1);
                  $result=db::name('gugu_favor')->insert($data);
                  if ( $result) 
                  {
                       return  1;
                  }

        }
       
    }
//发表评论
   public function subComment()
   {
       $param= request()->param();
       //$member_id=1;
        $member_id=session::get("member_id");
       $data['member_id']=$member_id;
       $data['gugu_id']=$param['article_id'];
       $data['info']=$param['gugu_content'];
       $data['add_time']=date('Y-m-d H:i:s',time());
       //评论入库
       $result=db::name("gugu_comment")->insert($data);
       //评论次数加1
      $result1=db::name('gugu_article')->where('id',$param['article_id'])->setInc('comment_num',1);
       if ($result) {
        //头像 昵称
         $info=db::name('mall_member')->where('id',$member_id)->field("nickname,headimg")->find();
         //时间处理
         $data['add_time']= $this->timeManage($data['add_time']);
         $data['nickname']=$info['nickname'];
         $data['headimg']=$info['headimg']; 
         return $data;
       }
   }
  //发表秀秀
  public function showPublish()
  {

       $options = Config::get('weixin');
        $weixin = new \com\Wechat($options);
        $js_ticket = $weixin->getJsTicket();
        $js_sign = array();
        $data['status'] = 1;
        if (!$js_ticket) {
            \think\Cache::clear();
            $js_ticket = $weixin->getJsTicket();
            if (!$js_ticket) {
                $str = "获取js_ticket失败！<br>";
                $str .= '错误码：'.$weixin->errCode;
                $str .= ' 错误原因：'.\com\wechat\Derrcode::getErrText($weixin->errCode);
                Log::write($str,'notice');
                $data['status'] = 0;
            }
        }
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $js_sign = $weixin->getJsSign($url);
        $js_sign['appid'] = $options['appid'];
        if (!isset($js_sign['timestamp'])) {
            $js_sign['timestamp'] = time();
        }
        $this->assign('js_sign',$js_sign);
//var_dump($js_sign);

    return $this->fetch('showPublish');
  }    


  // 一张一张的图片上传
	public function getImage($id)
	{
    		//$param = request()->param();

    		$data = json_decode($this->get_php_file("access_token.php"));

    		$access_token = $data->access_token;
    		$media_id = $id;
       //  Log::write($id);
    		$url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=".$access_token."&media_id=".$media_id;

    		$ch = curl_init($url);
    		$targetName = 'pic/'.mt_rand(0, 9999999).'.jpg';

    		$img_path = 'http://lvpai.zhonghuilv.net/' . $targetName;

            $fp = fopen($targetName, 'wb'); // 打开写入
            
            
                curl_setopt($ch, CURLOPT_FILE, $fp); // 设置输出文件的位置，值是一个资源类型
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_exec($ch);
                curl_close($ch);
                fclose($fp);

            return $img_path ;
	}

   

  	

//发表秀秀
    public function submitXiu()
    {
          $param = request()->param();
           $member_id=session::get("member_id");
         // Log::write('哈哈');
        //  Log::write($param['serverIds'][0]);
           //Log::write($param['serverIds'][1]);
          // echo json_encode($param['serverIds']);
           
              //对图片数组进行处理
             // $data['img_path']=isset($param['img_path']) ? $param['img_path']:'';
              if(empty($param['serverIds']))
              {
                   // Log::write('我了个擦擦');
                    $data['img_path']='';
              }
             
              else{
                   
                     $data['img_path']=[];

                        for($i=0;$i<count($param['serverIds']);$i++){
                          
                             $img_path= $this->getImage($param['serverIds'][$i]);

                             array_push($data['img_path'], $img_path);
                         

                        }
                        $data['img_path']=implode(",",$data['img_path']);
      
                      }
          
              $data['gugu_content']=$param['gugu_content'];
              $data['member_id']=$member_id;
              $data['add_time']=date('Y-m-d H:i:s',time());
             $result=db::name('gugu_article')->insert($data);
              //return $data['img_path'];
              if($result){
                            return 1;
                         }
            

    }


     public function showPublishTwo()
     {
        $options = Config::get('weixin');
        $weixin = new \com\Wechat($options);
        $js_ticket = $weixin->getJsTicket();
        $js_sign = array();
        $data['status'] = 1;
        if (!$js_ticket) {
            \think\Cache::clear();
            $js_ticket = $weixin->getJsTicket();
            if (!$js_ticket) {
                $str = "获取js_ticket失败！<br>";
                $str .= '错误码：'.$weixin->errCode;
                $str .= ' 错误原因：'.\com\wechat\Derrcode::getErrText($weixin->errCode);
                Log::write($str,'notice');
                $data['status'] = 0;
            }
        }
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $js_sign = $weixin->getJsSign($url);
        $js_sign['appid'] = $options['appid'];
        if (!isset($js_sign['timestamp'])) {
            $js_sign['timestamp'] = time();
        }
        $this->assign('js_sign',$js_sign);
      return $this->fetch('showPublishTwo');
     }
//发表咕咕
     public function  dUpload(){


                          if (request()->isAjax())
                                  {
                                      // 接收参数
                                      $param = request()->param();

                                      $gugu_content = isset($param['gugu_content']) ? $param['gugu_content'] : '';

                                       $address= $param['address'];
                                      // 处理图片开始
                                     //Log::write($param);
                                      $img_path = '';
                                      foreach ($param['img'] as $k => $v)
                                      {   
                                          $data = base64_decode(substr($v, 23));

                                          $destination =$this->getDestination( );
                                          file_put_contents($destination, $data);

                                          //$true_path = 'http://lvpai.zhonghuilv.net/' . $destination;
                                          $true_path = 'http://'.$_SERVER['SERVER_NAME'].'/'. $destination;
                                          if ('' == $img_path)
                                          {
                                              $img_path = $true_path;
                                          }
                                          else
                                          {
                                              $img_path = $img_path . ',' . $true_path;
                                          }
                                      }    
                                      // 处理图片结束
                                      
                                      $arr['img_path'] = $img_path;
                                      $arr['gugu_content'] = $gugu_content;
                                      $arr['member_id'] = session::get("member_id");
                                      $arr['address']=$address;
                                        //查找是否是今天第一次发秀秀
                                       $frist_time=db::name('travel_details')->where(['add_time'=>['>',strtotime("today")],'member_id'=>$arr['member_id']])->select();
                                       if(!$frist_time){
                                        $a=db::name('travel_details')->insert(['member_id'=>$arr['member_id'],'title'=>'发表秀秀','add_time'=>time(),'num'=>5]);
                                       $b=db::name('mall_member')->where('id',$arr['member_id'])->setInc('score',5);
                                       }

                                     $result=Db::name('gugu_article')->insert($arr);
                                      
                                      return $result;
                                  }   

     }

function getDestination( )
{   
    $date = date('Ymd');

        $filename = 'uploads/gugu/' .$date;
        if (file_exists($filename))
        {
            return $filename . '/' . time().mt_rand(0, 99999). '.jpg';
        }
        else
        {
            mkdir($filename);
            return $filename . '/' .  time().mt_rand(0, 99999) . '.jpg';
        }
     
}
//地址选择
    function  gaode()
    {
            $param = request()->param();
            $radius=500;
            $key="097229cf5f060757fec559cea6d4559b";
          $url = "http://restapi.amap.com/v3/geocode/regeo?key=".$key."&location=".$param['longitude'].",".$param['latitude']."&radius=".$radius."&extensions=all";
           //接收接口返回地理信息
           $content = file_get_contents($url);
           //转成数组
          $content=json_decode($content,true); 
          //得到周边所有位置
          $all_address=$content['regeocode']['pois'];
          //取出所有位置名称
          foreach ($all_address as $key => $value) {
            $address[$key]=$value['name'];
          }
          return  $address;

    }
 //删除秀秀
     function delXiu()
     {
         if (request()->isAjax())
                                  {
                                      // 接收参数
                                      $param = request()->param();
                                      $article_id = isset($param['article_id']) ? $param['article_id'] : '';
                                      $member_id=session::get("member_id");
                                      $is_have=db::name('gugu_article')->where('id',$article_id)->find();
                                      if($is_have['member_id']==$member_id){
                                           $result=db::name('gugu_article')->where('id',$article_id)->delete();
                                           return 1;
                                        }
                                        else{
                                          return 0;
                                        }
                                      }
     }

}

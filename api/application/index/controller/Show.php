<?php

namespace app\index\controller;
use think\Controller;
use think\Model;
use think\Db;
use think\Session;
class Show extends Controller
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
                                        $time=time()-$time.'秒前';
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
         
        $param = request()->param(); 

        //var_dump($member_id);

        $page = isset($param['page']) ? $param['page'] : 1;

        $pagesize=10;

        $offset = ($page - 1) * $pagesize;

      if(isset($param['member_id']))
             {
                    $where="a.member_id=".$param['member_id'];
                    $user_id=$param['member_id'];
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

        //统一时间处理  顺便把图片字符串转数组
        foreach ($show as $key => $value) {
           $show[$key]['add_time']= $this->timeManage($show[$key]['add_time']);
           if(!empty($show[$key]['img_path']))
             {
                    $show[$key]['img_path']=explode(",", $show[$key]['img_path']);
               }
               else{

                    $show[$key]['img_path']=[];
               }
        }
          //var_dump($show);
        //将多图片秀秀转数组


            $this->assign('show',$show);
            $this->assign('page',$page);
        return $this->fetch('show');
    }

    public function showDetails($article_id)
    {
       //单条秀秀数据
        $showDetails = Db::name('gugu_article a')->field('a.id as article_id, m.id as member_id, a.add_time, a.gugu_content, a.video_path, a.cover_img, a.address, a.like_num as favor_num, a.read_num, m.nickname, m.headimg as headimgurl, a.img_path, a.comment_num, a.duration')->
            join('mall_member m', 'm.id = a.member_id')->

            where('a.id',$article_id)->

            order('add_time desc')->find();
          

        //统一时间处理  顺便把图片字符串转数组
       
           $showDetails['add_time']= $this->timeManage($showDetails['add_time']);
           if(!empty($showDetails['img_path']))
             {
                    $showDetails['img_path']=explode(",", $showDetails['img_path']);
               }
               else{

                    $showDetails['img_path']=[];
               }

     //单条秀秀所有评论
     $comment = Db::name('gugu_comment c')->field('c.id as comment_id, c.info as content, c.add_time, c.favor_num, m.nickname, m.headimg')->

        join('mall_member m', 'm.id = c.member_id')->order('add_time desc')->where('gugu_id', $article_id)->select();
    //评论时间处理
      foreach ($comment as $key => $value) {
           $comment[$key]['add_time']= $this->timeManage($comment[$key]['add_time']);
        }
          //var_dump($comment);

         $this->assign('showDetails',$showDetails);
         $this->assign('comment',$comment);

         return $this->fetch('showDetails');
    }


    
}

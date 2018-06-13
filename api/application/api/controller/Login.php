<?php
namespace app\api\controller;
use think\Request;
use think\Db;
use think\Session;
/*use \extend\com\Msg;*/
class Login{

   public function getCheckCode()
    {
        $code = rand(1000,9999);
        $info = request()->param();        
        $res = array();
        if(!isset($info['type']) || !isset($info['mobile'])){
            $res['code'] = -1;
            $res['msg'] = '参数不正确';
            return json_encode($res,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
        $mobile = isset($info['mobile']) ? $info['mobile'] : '';
        $type = isset($info['type']) ? $info['type'] : '';
        Session::set($mobile.'sfnoc_sms_'.$type,$code);
        /*$a=Session::get($mobile.'sfnoc_sms_'.$type);
        var_dump($a);*/
        $prefix = '';
        $res = array(
            'code' => 0,
            'msg' => '发送失败'
        );

        $user = 'cf_zhonghuilv';
        $pass = 'eb2a1a963b116ae15e7cb2bf41382bf4';
        $content ='您的短信验证码:{#CODE#},1分钟后失效，请及时输入，完成用户注册!';
        $content = str_ireplace('{#CODE#}', $code, $content);
  
        $msg = new \com\Msg($user,$pass);
        $info = $msg->sendMsg($mobile, $prefix, $content);
        if ($info['code'] == 2) {
            $res['code'] = 1;
            $res['msg'] = '发送成功';
        }else{
            $res['code'] = 0;
            $res['msg'] = $info['msg'];
        }
       
        return json_encode($res,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        
    }

//手机注册
    public function insertMemberInfo()
    {
        $request = Request::instance();
        $info = $request->param();
              $res = array(
                            'code' => 0,
                            'msg' => '操作失败',
                            'body' => array(),
                        );

                        $mobile = isset($info['mobile']) ? $info['mobile'] : '';
                        //$code = isset($info['code']) ? $info['code'] : '';
                        $password = isset($info['password']) ? $info['password'] : '';

                        $code= isset($info['code']) ? $info['code'] : '';
                        //$re_pass = isset($info['re_password']) ? $info['re_password'] : '';

                        
                        
                        if(empty($mobile)){
                            $res['msg'] = '请提交手机号码';
                            return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }

                         
                        if(empty($code)){
                            $res['msg'] = '请提交验证码';
                            return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                        
                        if (!preg_match("/^1\d{10}$/", $mobile)) {
                            $res['msg'] = '请提交11位的标准手机号码！！';
                            return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);;
                        }
                        
                        $recode=Session::get($mobile.'sfnoc_sms_10');
                        //var_dump($recode);
                        if($recode!=$code)
                        {
                            $res['msg'] = '验证码不正确';
                            return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 
                        }
                        if(empty($password)){
                            $res['msg'] = '请提交密码';
                            return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
          
                        if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,18}$/", $password)) {
                            $res['msg'] = '请提交6-16位字母数字组合的密码！！';
                            return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                        
                        $res['msg'] = '手机号码已被注册';
                        $where['mobile'] = $mobile;
                        $bool = Db::name('mall_member')->where($where)->find();
                        if($bool){
                            return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }

                        $data['password'] = md5($password);
                        $data['mobile'] = $mobile;
                        $data['nickname'] = $mobile;
                        $data['type']=0;
                        $data['headimg']='';
                        $data['address']='';
                        $data['sex']=3;
                        $data['score']=1000;
                        $data['login_time'] = time();
                        $data['last_login_time'] = time();
                        $data['add_time'] = time();
                        $data['status'] = 1;
                        $data['proinfo']='这个家伙很懒,什么都没有留下!';
                        
                        
                        $member_id = Db::name('mall_member')->insert($data,false,true);
                        $data['member_id'] = $member_id;

                        if ($member_id > 0) {
                            //注册成功即登陆
                            $res['body']['fans']=0;
                            $res['body']['focus']=0;
                            $res['code'] = 1;
                            $res['msg'] = '注册成功';
                            $res['body'] = $data;
                            return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }else{
                            $res['code'] = 0;
                            $res['msg'] = '注册失败';
                            return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
 
                  

        }
        

       



  //登录接口
    public function sendLoginInfo()
    {
        $res = array(
            'code' => 0,
            'msg' => '操作失败',
            'body' => array(),
        );
        $request = Request::instance();
        $info = request()->param();
  
       switch($info['type'])
       {
          case 0:
                
               if(empty($info['mobile']))
                  {
                    $res['msg'] = '请提交账号';
                    return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                   }
                
               
                if (!preg_match("/^1\d{10}$/", $info['mobile'])) 
                {
                     $res['msg'] = '账号格式错误！';
                    return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                $hasmember = db('mall_member')->where('mobile', $info['mobile'])->find();
                if (empty($hasmember)) 
                {
                    $res['msg'] = '用户不存在，请先注册';
                    return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                }
              
               /*
               //验证码
                $recode=Session::get($mobile.'sfnoc_sms_0');
                
                if($recode!=$info['code'])
                  {
                      $res['msg'] = '验证码不正确';
                     return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 
                  }*/

                if (-1 == $hasmember['status']) 
                {
                    $res['code'] = 0;
                    $res['msg'] = '该账号被禁用';
                    return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                
                 //账号密码登陆
                 if (md5($info['password']) != $hasmember['password']) 
                 {      
                        $res['code'] = 0;
                        $res['msg'] = '密码错误';
                        return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    
                 }
                elseif($info['type'] == 0||md5($info['password']) == $hasmember['password'])
                {
                    //动态密码登陆
                    $res['code'] = 1;
                    $res['msg'] = '账号密码正确';
                    $res['body']= db('mall_member')->where('mobile', $info['mobile'])->find();
                    $res['body']['member_id']=$res['body']['id'];
                    unset($res['body']['id']);
                    $res['body']['contday']=$this->countDay($res['body']['member_id']);
                    //粉丝
                    $res['body']['fans']=Db::name('hd_fans')->where('user_id',$res['body']['member_id'])->count();
                    //关注人数
                    $res['body']['focus']=Db::name('hd_fans')->where('fans_id',$res['body']['member_id'])->count();

                   return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                break;
                //微博,微信,qq登陆
           /*     case 2:
                    
                    if(empty($info['openid'])||empty($info['type'])||)
                    {
                        $res['code'] = 0;
                        $res['msg'] = '请提交openid';
                        return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                    else
                    {
                       $res['body']= db('mall_member')->where('openid', $info['openid'])->field('id as member_id,mobile,nickname,headimg,score,status,login_time,last_login_time,proinfo')->find();

                       return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }

               }
               break;*/
        
        }

//重置密码
 public function rePassword()
  {
    $request = Request::instance();
    $info = $request->param();
    $res = array(
            'code' => 0,
            'msg' => '操作失败',
            'body' => array(),
        );
     if(empty($info['mobile'])){
        $res['msg']='请提交手机号码';
        return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
     }

     if(empty($info['code'])){
        $res['code']='请提交验证码';
        return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 
     }

      $recode=Session::get($mobile.'sfnoc_sms_11');
      if($info['code']!=$recode){
        $res['code']='验证码不正确';
        return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 
      }

     if(empty($info['password'])){
        $res['msg']='请提交密码';
        return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 
     }

      $oldPassword=Db::name('mall_member')->where('mobile',$info['mobile'])->field('password')->find();
      //dump($oldPassword);
      if($info['password']==$oldPassword['password']){
         $res['msg']='密码不能与原密码相同';
         return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); 
      }
      if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,18}$/", $info['password'])){
                            $res['msg'] = '请提交6-16位字母数字组合的密码！！';
                            return json_encode($res, JSON_UNESCAPED_UNIODE | JSON_UNESCAPED_SLASHES);
                        }

      else{
        $result=Db::name('mall_member')->where('mobile',$info['mobile'])->update(['password'=>$info['password']]);
        if($result){
            $res['code']=1;
            $res['msg']='重置成功';
            return json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
      }


  }      


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
                foreach ($sign_time as  $v) 
                    {
                      //取出所有签到日期时间戳
                      $arr[]=$v['sign_time'];
                      $arr2[]=date('Y-m-d',$v['sign_time']);//将签到时间戳转成日期
                      
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
                     if($arr3[$i]-$arr3[$i+1]<=86401)
                        {   
                           $count_day=++$count;
                        }
                         else{break;}
                }
            }
          return $count_day;
    } 


        //生成签名，存session（存session有过期时间，看需要设置，若除退出登陆，永不失效，可考虑存数据库）
    /*  $session_id = $hasmember['mobile'].'zhlsfnoc';
        $check_sign = md5($hasmember['mobile'].$hasmember['password']);
        session($session_id,$check_sign);*/

        //更新管理员状态
/*        $param = [
            'login_num' => $hasmember['login_num'] + 1,
            'login_ip' => request()->ip(),
            'last_ip' => request()->ip(),
            'login_time' => time(),
            'last_time' => time()
        ];
        db('hd_member')->where('id', $hasmember['id'])->update($param);


        $res['code'] = 1;
        $res['msg'] = '登陆成功';
        $res['body'] = $hasmember;
        $res['session_id'] = $session_id;
        $res['check_sign'] = $check_sign;
        return json_encode($res);

    }*/
}
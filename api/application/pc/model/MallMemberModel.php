<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/5/25
 * Time: 16:21
 */

namespace app\pc\model;

use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\Model;
use PDOException;

class MallMemberModel extends PublicyangModel
{

    public function __construct(){
        parent::__construct();
    }
    protected $table = 'too_mall_member';
    //显示字段
    protected $list_fields = array();


    public function preprocess($rs,$stime,$etime){
//
//        foreach ($rs as $k=>$v){
//            $rs[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
//            $sql = "select nickname, mobile
//                    from too_mall_member
//                    WHERE id = ".$v['member_id'];
//            $user_info = $this->query($sql);
//            $rs[$k]['nickname'] = $user_info[0]['nickname'];
//            $rs[$k]['mobile'] = $user_info[0]['mobile'];
//        }

        return $rs;
    }


    /**
     * 个人中心->账号安全
     * */
    public function getAllInfo($data){
        $sql = "select password_security,mobile,email
                from {$this->table} 
                WHERE id = {$data['id']}";
        $rs = $this->query($sql);
        if($rs){
            $data_new['password_security'] = $rs[0]['password_security'];
            $data_new['mobile'] = $rs[0]['mobile'];
            $data_new['email'] = $rs[0]['email'];
        }else{
            return ['status'=>1,'msg'=>'查无此用户!','data'=>''];
        }
        $sql = "select t.*,t1.problem 
                from too_member_security t 
                LEFT JOIN too_member_security_type t1 
                ON t.problem_type = t1.id 
                LEFT JOIN {$this->table} t2 
                ON t.member_id = t2.id 
                WHERE t2.id = {$data['id']}";
        $rs = $this->query($sql);
        if($rs){
            $data_new['rows'] = $rs;
            return ['status'=>0,'msg'=>'','data'=>$data_new];
        }else{
            return ['status'=>2,'msg'=>'未设置问题!','data'=>''];
        }
    }

    /**
     * 个人中心->验证安全问题
     * */
    public function verificationGo($data){
        $sql = "select t.*,t1.problem 
                from too_member_security t 
                LEFT JOIN too_member_security_type t1 
                ON t.problem_type = t1.id 
                LEFT JOIN {$this->table} t2 
                ON t.member_id = t2.id 
                WHERE t2.id = {$data['id']}";
        $rs = $this->query($sql);
//        $veri = [];
        foreach ($rs as $k=>$v){
//            $veri[$v['problem_type']] = $v;
            if($data['rows'][$v['problem_type']]!==$v['answer']){
                echo json_encode(['status'=>1,'msg'=>'验证失败!','data'=>'']);
                break;
            }
        }
        echo json_encode(['status'=>0,'msg'=>'验证成功!','data'=>'']);
        die;
    }


    /**
     * 获取该手机号的个数
     * @param $mobile
     * @return int|string
     */
    public static function getCountByMobile($mobile)
    {
        $condition = ['mobile' => $mobile];
        return Db::table('too_mall_member')->where($condition)->count(1);
    }

    /**
     * 获取该用户信息
     * @param $unionId
     * @return int|string
     */
    public static function getModelByUnionId($unionId)
    {
        $condition = ['unionid' => $unionId];
        try {
            $member = Db::table('too_mall_member')->where($condition)->find();
            if (! $member) throw new DataNotFoundException('该用户信息不存在');
            else return $member;
        } catch (DataNotFoundException $e) {
            return $e->getMessage();
        } catch (ModelNotFoundException $e) {
            return $e->getMessage();
        } catch (DbException $e) {
            return $e->getMessage();
        }
    }

    /**
     * 获取该手机号的信息
     * @param $mobile
     * @return int|string
     */
    public static function getModelByMobile($mobile)
    {
        $condition = ['mobile' => $mobile];
        try {
            $member =  Db::table('too_mall_member')->where($condition)->find();
            if (! $member) throw new DataNotFoundException('该用户信息不存在');
            else return $member;
        } catch (DataNotFoundException $e) {
            return $e->getMessage();
        } catch (ModelNotFoundException $e) {
            return $e->getMessage();
        } catch (DbException $e) {
            return $e->getMessage();
        }
    }

    /**
     * 插入用户信息
     * @param $data
     * @return int|string
     */
    public static function insertMemberInfo($data)
    {
        return Db::table('too_mall_member')->insertGetId($data);
    }

    public static function getModelById($id)
    {
        $condition = ['id' => $id];
        try {
            $res =  Db::table('too_mall_member')->where($condition)->find();
            if (! $res) {
                throw new DataNotFoundException('该用户信息不存在');
            } else return $res;
        } catch (DataNotFoundException $e) {
            return $e->getMessage();
        } catch (ModelNotFoundException $e) {
            return $e->getMessage();
        } catch (DbException $e) {
            return $e->getMessage();
        }
    }

    /**
     * 更新用户信息
     * @param $condition
     * @param $data
     * @return int|string
     */
    public static function updateMemberInfo($condition, $data)
    {
        try {
            $res = Db::table('too_mall_member')->where($condition)->update($data);
            if (! $res) throw new PDOException('更新数据失败');
            return $res;
        } catch (PDOException $e) {
            return $e->getMessage();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * 判断登录
     * @param $username
     * @param $password
     * @return mixed
     */
    public static function validateLoginInfo($username, $password)
    {
        $password = md5($password . 'lvpaipc');
        $sql = "SELECT * FROM `too_mall_member` WHERE `mobile` = '{$username}' OR `email` = '{$username}' AND `password` = '{$password}'";
        return Db::query($sql);
    }
}
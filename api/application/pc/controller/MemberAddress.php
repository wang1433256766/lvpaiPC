<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/5/29
 * Time: 14:06
 */
namespace app\pc\controller;

use PDOException;
use think\Controller;
use think\Request;
use app\pc\model\MemberTravelerInfoModel;
use think\Session;
use think\Db;

class MemberAddress extends Common
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 收货地址列表
     * */
    public function infoList(){
        $param = request()->param();
        $param['id'] = Session::get('user.id');
        $sql = "select * 
                from too_member_address
                WHERE member_id = {$param['id']} 
                order by id DESC ";
        $rs = Db::query($sql);
        echo json_encode(['status'=>0,'msg'=>'','data'=>$rs]);die;
    }

    /**
     * 设置单个收货地址
     * */
    public function setTravelerInfo(){
        $param = request()->param();
        if($param['action']=='look'){
            $rs = Db::table('too_member_address')->where('id',trim($param['id']))->find();
            echo json_encode(['status'=>0,'msg'=>'','data'=>$rs]);die;
        }
        try {
            if($param['action']=='add'){
                $data = [
                    'post_code'=>$param['post_code'],
                    'province_city'=> $param['province_city'],
                    'address'=> $param['address'],
                    'status' => 1,
                    'phone' => $param['phone'],
                    'username' => $param['username'],
                ];
                $data['add_time'] = date('Y-m-d H:i:s',time());
                $data['member_id'] = Session::get('user.id');
                $examine_img = Db::table('too_member_address')->insert($data);
                if (! $examine_img) throw new PDOException('添加失败!');
                $msg = '添加成功';
            }
            else if($param['action']=='update'){
                $data = [
                    'post_code'=>$param['post_code'],
                    'province_city'=> $param['province_city'],
                    'address'=> $param['address'],
                    'phone' => $param['phone'],
                    'username' => $param['username'],
                ];
                $examine_img = Db::table('too_member_address')->where('id',$param['id'])->update($data);
                if (! $examine_img) throw new PDOException('修改失败!');
                $msg = '修改成功!';
            }
            else if($param['action'] == 'del'){
                $examine_img = Db::table('too_member_address')->where('id',$param['id'])->delete();
                if (! $examine_img) throw new PDOException('删除失败!');
                $msg = '删除成功!';
            }
            else if($param['action'] == 'delAll'){
                $ids = $param['ids'];
                $sql = "delete from too_member_address WHERE id in ({$ids})";
                $examine_img = Db::execute($sql);
                if (! $examine_img) throw new PDOException('删除失败!');
                $msg = '删除成功!';
            }
            else{
                throw new PDOException('非法操作!');
            }
            Db::commit();
            echo json_encode(['status'=>0,'msg'=>$msg,'data'=>[]]);
        }catch (PDOException $exception){
            Db::rollback();
            echo json_encode(['status'=>1,'msg'=>$exception->getMessage(),'data'=>[]]);
        }
    }

}
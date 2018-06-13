<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/5/26
 * Time: 10:53
 */

namespace app\pc\model;


use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use PDOException;
//use think\exception\PDOException;

use think\Model;
use think\Session;

class MemberTravelerInfoModel extends PublicyangModel
{


    public function __construct()
    {
        parent::__construct();
    }

    protected $table = 'too_member_traveler_info';
    //显示字段
    protected $list_fields = array();


    /**
     * 读取所有出游人列表
     * */
    public function getAllList($data){
        $sql = "select * 
                from {$this->table} 
                WHERE member_id = {$data['id']}";
        $rs = $this->query($sql);
        if($rs){
            return ['status'=>0,'msg'=>'','data'=>$rs];
        }else{
            return ['status'=>1,'msg'=>'暂无出游人信息','data'=>''];
        }
    }

    /**
     * 处理导入数据
     * */
    public function sctonum($num, $double = 5){
        if(false !== stripos($num, "e")){
            $a = explode("e",strtolower($num));
            return bcmul($a[0], bcpow(10, $a[1], $double), $double);
        }
    }

    public function addImport($data){
        $member_id = Session::get('user.id');
        foreach ($data as $k => $v) {
            if(empty($v['use_card'])){
                unset($data[$k]);
            }else{
                if(strpos('E',$v['use_card'])===false){
                    $data[$k]['use_card'] = $this->sctonum($v['use_card'],0);
                }
                if($v['old']<=12){
                    $data[$k]['types'] = 0;
                }
                if($k=='身份证'){
                    $data[$k]['card_type'] = 1;
                }else{
                    $data[$k]['card_type'] = 1;
                }
                $data[$k]['types'] = $v['old'] < 13 ? 0 : 1;
                $data[$k]['member_id'] = $member_id;
                $data[$k]['sex'] = '保密';
                $data[$k]['email'] = '';
                $data[$k]['default'] = 0;
                $data[$k]['status'] = 1;
                $data[$k]['add_time'] = time();
                $data[$k]['up_time'] = time();
            }
        }
        try {
            $examine_img = Db::table('too_member_traveler_info')->insertAll($data);
            if (! $examine_img) throw new PDOException('请检验格式!');
            Db::commit();
            return ['status'=>0,'msg'=>'上传成功!','data'=>[]];
        }catch (PDOException $exception){
            Db::rollback();
            return ['status'=>1,'msg'=>$exception->getMessage(),'data'=>[]];
        }
    }

    /**
     * 获取出游人信息
     * @param $member_id
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getTravelerInfo($member_id)
    {
        $condition = ['member_id' => $member_id];
        try {
            $res = Db::table('too_member_traveler_info')->field('id,use_name,use_card')->where($condition)->select();
            if (! $res) throw new DataNotFoundException('出游人信息不存在');
            else return $res;
        } catch (DataNotFoundException $e) {
            return $e->getMessage();
        }
    }


    /**
     * 获取合集
     * @param $ids
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getModelByIds($ids)
    {
        $condition['id'] = ['in', $ids];
        try {
            $res = Db::table('too_member_traveler_info')->field('use_name,use_card')->where($condition)->select();
            if (! $res) throw new DataNotFoundException('出游人信息不存在');
            else return $res;
        } catch (DataNotFoundException $e) {
            return $e->getMessage();
        } catch (ModelNotFoundException $e) {
            return $e->getMessage();
        } catch (DbException $e) {
            return $e->getMessage();
        }
    }


}
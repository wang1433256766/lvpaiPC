<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;

class GroupModel extends Model
{
    protected  $table = 'too_member_group';

    /**
     * 获取所有的分组列表
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getGroupByWhere($where, $offset, $limit)
    {
        
        return  $this->where($where)->limit($offset, $limit) ->select();                       
                       
    }

    /**
     *获取所有的分组数量
     * @param $where
     */
    public function getAllGroup($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 添加分组
     * @param $param
     */
    public function insertGroup($param)
    {
        try{

            $result =  $this->validate('GroupValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加分组成功'];
            }
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑分组
     * @param $param
     */
    public function editGroup($param)
    {
        try{

            $result =  $this->validate('GroupValidate')->save($param, ['gr_id' =>$param['gr_id']]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑分组成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据分组 id获取信息
     * @param $id
     */
    public function getOneGroup($group_id)
    {
        return $this->where('id', $group_id)->find();
    }

    /**
     * 删除分组
     * @param $id
     */
    public function delGroup($id)
    {
        try{

            $this->where('gr_id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除分组成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    //获取所有的分组信息
    public function getGroup()
    {
        return $this->select();
    }

    

   /*  /**
     * 获取App信息
     * @param $id
     */
    public function getGroupInfo($id){

        $result = db('group')->where('gr_id', $id)->find();
        if(empty($result['gr_name'])){
            $where = '';
        }else{
            $where = 'id in('.$result['gr_name'].')';
        }
        $res = db('node')->field('control_name,action_name')->where($where)->select();
        foreach($res as $key=>$vo){
            if('#' != $vo['action_name']){
                $result['action'][] = $vo['control_name'] . '/' . $vo['action_name'];
            }
        }

        return $result;
    }
}
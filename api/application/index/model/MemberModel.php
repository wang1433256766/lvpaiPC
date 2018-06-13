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
namespace app\index\model;

use think\Model;

class MemberModel extends Model
{
    protected  $table = 'too_member';

    /**
     * 获取所有的会员列表
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getMemberByWhere($where, $offset, $limit)
    {

        return $this->where($where)->limit($offset, $limit)->select();
    }
    /**
     * 导出数据
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function exportData()
    {
       return $this->field('too_member.*')
            ->join('too_group', 'too_member.id = too_group.member_id')
            /* ->where($where)->limit($offset, $limit) */-> order('read_nums desc')->select();
    }
    /**
     *获取所有的会员数量
     * @param $where
     */
    public function getAllMember($where)
    {
        return $this->where($where)->count();
    }
    /**
     *根据member_id获取唯一的unionid
     * @param $where
     */
    public function getUnionid($member_id)
    {
        return $this->where('id',$member_id)->value('unionid');
    }

    /**
     * 添加会员
     * @param $param
     */
    public function insertMember($param)
    {
        try{

            $result =  $this->validate('MemberValidate')->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加会员成功'];
            }
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑会员
     * @param $param
     */
    public function editMember($param,$id)
    {
        try{

            $result =  $this->validate('MemberValidate')->save($param, ['id' => $id]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '编辑会员成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 根据member_ id获取信息
     * @param $id
     */
    public function getOneMember($id)
    {
        return $this->where('id', $id)->find();
    }
    
    /**
     * 根据$where_member获取会员信息
     * @param $id
     */
    public function getOneMb($where_member)
    {
        return $this->where($where_member)->find();
    }

    /**
     * 删除会员
     * @param $id
     */
    public function delMember($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除会员成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    //获取所有的会员信息
    public function getMember()
    {
        return $this->select();
    }

    

   /* 
    * 获取会员信息
    * @param $id
    */
    public function getMemberInfo($id){

        $result = db('member')->where('id', $id)->find();
        if(empty($result['member_name'])){
            $where = '';
        }else{
            $where = 'id in('.$result['member_name'].')';
        }
        $res = db('node')->field('control_name,action_name')->where($where)->select();
        foreach($res as $key=>$vo){
            if('#' != $vo['action_name']){
                $result['action'][] = $vo['control_name'] . '/' . $vo['action_name'];
            }
        }

        return $result;
    }
    
    /*
     * 修改会员状态
     * @param $id
     */
    public function editStatus($member_id,$val)
    {
    try{

            $result =  $this->save(['status' => $val], ['id' => $member_id]);

            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '修改成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /*
     *获取今日分享数及阅读书
     *   
     */
    public function shareTo($where, $offset, $limit)
    {
       return  $this->where($where)->whereTime('share_time', 'today')->select();        
    }
    /*
     *获取本周分享数及阅读书
     *
     */
    public function shareWe($where, $offset, $limit)
    {
        return  $this->where($where)->whereTime('share_time', 'week')->select();
    }
    /*
     *获取本月分享数及阅读书
     *
     */
    public function shareMo($where, $offset, $limit)
    {
        return  $this->where($where)->whereTime('share_time', 'month')->select();
    }
    /**
     * 授权后修改Fans信息
     * @param $param
     */
    public function updateFansInfo($wechat_data)
    {
        try{
    
            $result =  $this->save($wechat_data);
    
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
    
                return ['code' => 1, 'data' => '', 'msg' => '编辑会员成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}
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

        return $this->field("too_member.*,too_member_group.name")
                    ->join("too_member_group","too_member_group.id=too_member.group_id")
                    ->where($where)->order("id desc")->limit($offset, $limit)->select();
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
    public function getMbUnionid($member_id)
    {
        return $this->where('id',$member_id)->value('unionid');
    }
    /**
     *根据unionid查出会员信息
     * @param $where
     */
    public function getOneMemberInfo($unionid)
    {
        return $this->where('unionid',$unionid)->find();
    }
    /**
     *根据open_id查出会员信息
     * @param $where
     */
    public function getOneMemberOpenId($open_id)
    {
        return $this->where('openid',$open_id)->find();
    }
    /**
     *根据member_id查出会员信息
     * @param $where
     */
    public function getOneMemberId($member_id)
    {
        return $this->where('id',$member_id)->find();
    }
    /**
     *根据手机号查出会员信息
     * @param $where
     */
    public function getOneMemberPhone($phone)
    {
        return $this->where('phone',$phone)->find();
    }
    /**
     * 添加会员
     * @param $param InsertMemberPhone
     */
    public function insertMember($member_data)
    {
        try{

            $result =  $this->validate('MemberValidate')->save($member_data,false,true);
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
     * 手机端注册会员
     * @param $param InsertMemberPhone
     */
    public function InsertMemberPhone($data)
    {
        try{
    
            $result =  $this->save($data,false,true);
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
    //根据member_id修改登陆次数
    public function setIncLognum($member_id)
    {
        try{
        
            $result =  $this->where('id',$member_id)->setInc('login_num');
        
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
    /*
     *修改积分
     *
     */
    public function setIncScore($market_id)
    {
        return $this->where('market_id',$market_id)->setInc('score',10);
    }
    /*
     *会员ID
     *
     */
    public function setFieldMarketId($member_id,$market_id)
    {
        return $this->where('id',$member_id)->setField('market_id',$market_id);
    }
    //获取 Fans积分
    public function getMbScore($market_id)
    {
       return $this->where('market_id',$market_id)->value('score'); 
    }
    //修改会员状态为0
    public function MbsetStatus($mem_id)
    {
        return $this->where('id',$mem_id)->setField('status',0);
        
    }
    public function MbsetDec($market_id)
    {
        return $this->where('market_id',$market_id)->setDec('score',10);
    }
    /**
     * 根据点赞数排名
     */
    public function getLikeNums()
    {
        return $this->field('too_wx_user.headimgurl,clicks_num,nickname,top')
                    ->join('too_wx_user','too_wx_user.member_id=too_member.id')
                    ->order('clicks_num')
                    ->select();
        
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
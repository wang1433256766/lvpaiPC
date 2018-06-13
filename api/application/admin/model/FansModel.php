<?php
// +----------------------------------------------------------------------
// | ZHl
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Belasu <88487088@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;

class FansModel extends Model
{
    protected  $table = 'too_wx_user';

    /**
     * 根据搜索条件获取fans列表
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getFansByWhere($where,$limit,$offset)
    {

        return $this->where($where)->limit($offset,$limit)->select();
    }
    /**
     * 根据对应的APPID查询及搜索条件获取fans列表
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getFanByWhere($app_id)
    {        
        return $this->join("too_app","too_app.id=too_wx_user.app_id")
                    ->where($app_id)
                    ->limit("45")
                    ->select();
    }
    //获取APP所有信息及APP下对应的粉丝列表
    
    /**
     * 根据搜索条件获取所有的fans数量
     * @param $where
     */
    public function getAllFans($where)
    {
        return $this->where($where)->count();
    }
    /**
     * 根据member_id获取所有的Fans信息
     * @param $where
     */
    public function getOneFansMid($member_id)
    {
        return $this->where('member_id',$member_id)->find();
    }
    /**
     * 根据APP_ID获取所有的fans数量
     * @param $where
     */
    public function getFansNum($id)
    {
        return $this->where('app_id',$id)->count('id');
    }
    public function getFansOpenId($where_market)
    {
        return $this->where($where_market)->value('open_id');
    }
    //修改粉丝状态
    public function FansetStatus($where)
    {
        return $this->where($where)->setField('status',0);
    }

    /**
     * 插入fans消息
     * @param $param
     */
    public function insertFans($wechat_data)
    {
        try{

            $result =  $this->save($wechat_data);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{

                return ['code' => 1, 'data' => '', 'msg' => '添加成功'];
            }
            //PDOException  异常类
        }catch( PDOException $e){

            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * 编辑fans信息
     * @param $param
     */
    public function editFans($wechat_data)
    {
        try{

            $result =  $this->save($wechat_data);

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
    
    /**
     * 根据id获取fans信息
     * @param
     */
    public function getOneFans($where)
    {
        return $this->where($where)->find();
    }
    
    /**
     * 根据unionid获取fans信息
     * @param
     */
    public function getOneFansUid($unionid)
    {
        return $this->where('unionid',$unionid)->find();
    }

    /**
     * 根据unionid获取fans信息
     * @param
     */
    public function getOneFansOid($open_id)
    {
        return $this->where('open_id',$open_id)->find();
    }
    /**
     * 删除fans信息
     * @param $id
     */
     public function delFans($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    //获取所有的fans信息
    public function getFans()
    {
        return $this->limit("15")->select();
    }

    /* //获取角色的权限节点
    public function getRuleById($id)
    {
        $res = $this->field('rule')->where('id', $id)->find();

        return $res['rule'];
    }

    /**
     * 分配权限
     * @param $param
     */
    /*public function editAccess($param)
    {
        try{
            $this->save($param, ['id' => $param['id']]);
            return ['code' => 1, 'data' => '', 'msg' => '分配权限成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    } */

    /**
     * 获取fans信息
     * @param $id
     */
    public function getFansInfo($id){

        $result = Db::name('wx_fans')->where('id', $id)->find();
        if(empty($result['fans'])){
            $where = '';
        }else{
            $where = 'id in('.$result['fans'].')';
        }
       $res = Db::table('too_text')->field('kb,content,status,sort,addtime')->where($where)->select();
        foreach($res as $key=>$vo){
            if('#' != $vo['action_name']){
                $result['action'][] = $vo['control_name'] . '/' . $vo['action_name'];
            }
        }

        return $result;
    }
    /**
     * 关注
     * @param 
     */
    public function subEdit($member_id)
    {
        try{
    
            $result =  $this->where('member_id',$member_id)->setField('status',1);
    
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
    
                return ['code' => 1, 'data' => '', 'msg' => '关注成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    
    }
    /**
     *取消关注
     * @param $id
     */
    public function unsubEdit($member_id)
    {
        try{
    
           $result =  $this->where('member_id',$member_id)->setField('status',2);
    
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
    
                return ['code' => 1, 'data' => '', 'msg' => '取消关注成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
    /**
     *取消关注
     * @param $id
     */
    public function getOneFansBy($Oid)
    {
        return $this->where("open_id",$Oid)->find();
    }
}
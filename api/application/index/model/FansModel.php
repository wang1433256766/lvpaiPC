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

class FansModel extends Model
{
    protected  $table = 'too_wx_user';

    /**
     * 根据搜索条件获取fans列表
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getFansByWhere($where, $offset, $limit,$a_id)
    {

        return $this->where($where)->where('appid',$a_id)->limit($offset, $limit)->select();
    }

    /**
     * 根据搜索条件获取所有的fans数量
     * @param $where
     */
    public function getAllFans($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入fans消息
     * @param $param
     */
    public function insertFans($param)
    {
        try{

            $result =  $this->save($param);
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
    public function editFans($param,$id)
    {
        try{

            $result =  $this->validate('FansValidate')->save($param, ['id' => $id]);

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
     * 授权后修改Fans信息
     * @param $param
     */
    public function updateFansInfo($wechat_data)
    {
        try{
    
            $result =  $this->save($wechat_data,['id'=>$wechat_data['id']]);
    
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
     * 根据id获取fans信息
     * @param $id
     */
    public function getOneFans($where)
    {
        return $this->where($where)->find();
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
        return $this->select();
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

        $result = Db::name('fans')->where('id', $id)->find();
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
    
}
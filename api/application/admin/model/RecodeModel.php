<?php
// +----------------------------------------------------------------------
// | Zhl
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022  All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Belasu <belasu@foxmail.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;

class RecodeModel extends Model
{
    protected $table = 'too_recode';

    /**
     * 获取所有的推广信息
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getRecodeByWhere($where, $offset, $limit)
    {
         return $this->where($where)->limit($offset, $limit)->select();
    }

    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllRecode($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 根据id获取角色信息
     * @param $id
     */
    public function getOneRecode($where_recode)
    {
        return $this->where($where_recode)->find();
    }
    /**
     * 根据$where_recode获取推广ID
     * @param $id
     */
    public function getMarketId($where_recode)
    {
        return $this->where($where_recode)->value('market_id');
    }
    //修改推广状态为
    public function RecsetStatus($where_recode)
    {
        return $this->where($where_recode)->setField('status',1);
    }
    //修改推广状态为
    public function RecsetFields($where_recode)
    {
        return $this->where($where_recode)->setField('status',0);
    }
    /**
     * 添加推广码信息
     * @param 
     */
    public function insertRecode($param)
    {
        try{
    
            $result =  $this->save($param);
            if(false === $result){
                // 验证失败 输出错误信息
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
    
                return ['code' => 1, 'data' => '', 'msg' => '添加成功'];
            }
        }catch( PDOException $e){
    
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

   
}
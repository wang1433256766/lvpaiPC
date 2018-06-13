<?php
// +----------------------------------------------------------------------
// | Zhl
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <88487088@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\model;


use think\Model;

class TextModel extends Model
{
    protected  $table = 'too_text';

    /**
     * 根据搜索条件获取文本回复列表信息
     * @param $where
     * @param $offset
     * @param $limit
     */
    public function getTextByWhere($where, $offset, $limit)
    {

        return $this->where($where)->limit($offset, $limit)->select();
    }

    /**
     * 根据搜索条件获取所有的文本回复设置数量
     * @param $where
     */
    public function getAllText($where)
    {
        return $this->where($where)->count();
    }
    /**
     * 根据搜索条件获取所有的文本回复
     * @param $where
     */
    public function getAllTextwhere($where)
    {
        return $this->where($where)->find();
    }
    /**
     * 根据搜索条件获取所有的文本回复
     * @param $where_key
     */
    public function getOneTextValue($where_key)
    {
        return $this->where($where_key)->value('content');
    }
    
    /**
     * 插入文本回复消息
     * @param $param
     */
    public function insertText($param)
    {
        try{

            $result =  $this->validate('TextValidate')->save($param);
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
     * 编辑文本消息回复 信息
     * @param $param
     */
    public function editText($param)
    {
        try{

            $result =  $this->validate('TextValidate')->save($param, ['id' => $param['id']]);

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
     * 根据id获取文本回复信息
     * @param $id
     */
    public function getOneText($id)
    {
        return $this->where('id', $id)->find();
    }

    /**
     * 删除文本回复信息
     * @param $id
     */
     public function delText($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    //获取所有的文本回复信息
    public function getText()
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
     * 获取文本回复信息
     * @param $id
     */
    public function getTextInfo($id){

        $result = Db::name('text')->where('id', $id)->find();
        if(empty($result['text'])){
            $where = '';
        }else{
            $where = 'id in('.$result['text'].')';
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
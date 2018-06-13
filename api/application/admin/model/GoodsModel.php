<?php

namespace app\admin\model;

use think\Model;

// 商品模型
class GoodsModel extends Model
{
	protected $table = 'too_score_goods';

	// 插入一条商品
	public function insertGoods($param)
	{
		return $this->insert($param);
	}

    public function getOne($id)
    {
        return $this->where("id = $id")->find();
    }

    // 根据条件得到记录
	public function getGoodsByWhere($where, $offset, $limit)
    {
        return $this->field('too_score_goods.id, too_score_goods.name, too_score_goods.typeid, too_score_goods.integral, too_score_goods.stock, too_score_goods.already_count, too_score_goods.status')->where($where)->limit($offset, $limit)->select();
    }

    // 得到所有记录数量
    public function getAllGoods($where)
    {
        return $this->where($where)->count();
    }


    // 商品删除
    public function goodsDel($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除商品成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    // 商品的更新
    public function updateOne($param)
    {
        $id = $param['id'];
        return $this->where("id = $id")->update($param);
    }
    /**
     * 添加应用
     * @param $param
     */
    public function insertApp($param)
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
    /**
     * 更新商品
     * @param $param
     */
    public function editGoods($param)
    {
        try{

            $result =  $this->save($param, ['id' => $param['id']]);

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
     * 根据id获取信息
     * @param $id
     */
    public function getOneGoods($id)
    {
        return $this->where('id', $id)->find();
    }
    /**
     * 根据id删除图片
     * @param $id
     */
    public function delImg($id){
        $path = $this->where('id',$id)->field('image')->find();
        $path = substr($path['image'],1);
        if(!empty($path)){
            @unlink($path);
        }
    }
}
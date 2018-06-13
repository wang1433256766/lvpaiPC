<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/5/26
 * Time: 10:15
 */

namespace app\pc\model;


use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Model;

class TicketModel extends Model
{
    /**
     * 获取商品信息
     * @param $field
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getAllProducts($field)
    {
        $condition = ['status' => 1];
        try {
            $res =  Db::table('too_ticket')->field($field)->where($condition)->select();
            if (! $res) throw new DataNotFoundException('信息不存在');
            return $res;
        } catch (DataNotFoundException $e) {
            return $e->getMessage();
        } catch (ModelNotFoundException $e) {
            return $e->getMessage();
        } catch (DbException $e) {
            return $e->getMessage();
        }
    }

    /**
     * 获取商品信息规定字段信息
     * @param $product_id
     * @param $field
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getProductFieldInfo($product_id, $field)
    {
        $condition = ['status' => 1, 'id' => $product_id];
        try {
            $res = Db::table('too_ticket')->field($field)->where($condition)->select();
            if (! $res) {
                throw new DataNotFoundException('该票信息不存在');
            } else return $res;
        } catch (DataNotFoundException $e) {
            return $e->getMessage();
        } catch (ModelNotFoundException $e) {
            return $e->getMessage();
        } catch (DbException $e) {
            return $e->getMessage();
        }
    }

}
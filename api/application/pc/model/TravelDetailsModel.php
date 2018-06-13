<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/5/30
 * Time: 10:32
 */

namespace app\pc\model;


use think\Db;
use think\Model;

class TravelDetailsModel extends Model
{
    /**
     * 插入积分详情
     * @param $title
     * @param $num
     * @param $member_id
     * @return bool|string
     */
    public static function insertTravelDetails($title, $num, $member_id, $type)
    {
        Db::startTrans();
        try {
            $data = [
                'title' => $title,
                'num' => $num,
                'member_id' => $member_id,
                'type' => $type,
                'add_time' => time()
            ];
            $score = Db::name('mall_member')->where('id', $member_id)->value('score');
            if (! Db::name('mall_member')->where('id', $member_id)->setField('score', $score - abs($num))) throw new \PDOException('积分消耗信息失败');
            if (!  Db::table('too_travel_details')->insert($data)) throw new \PDOException('插入积分详情失败');
            Db::commit();
            return true;
        } catch (\PDOException $e) {
            Db::rollback();
            return $e->getMessage();
        }

    }

}
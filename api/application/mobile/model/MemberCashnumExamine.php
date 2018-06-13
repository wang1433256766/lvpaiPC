<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/5/15
 * Time: 15:26
 */

namespace app\mobile\model;

use PDOException;
use think\Db;
use think\Model;

class MemberCashnumExamine extends Model
{
    const UNAUDITED = 1;
    const AUDIT_PASS = 2;
    const AUDIT_FAILED = 3;

    /**
     * 插入提现申请
     * @param $data
     * @param $money
     * @param string $img
     * @return bool|string
     * @throws \think\Exception
     */
    public static function insertInformation($data, $money,  $img = '')
    {
        Db::startTrans();
        try {
            $member_cash = Db::table('too_member_cashnum_examine')->insertGetId($data);
            if (! $member_cash) throw new PDOException('提交审核失败');
            if ($img != '') {
                $img_arr = explode(',', trim($img, ','));
                foreach ($img_arr as $k => $v) {
                    $img_arr[$k] = array(
                        'img_url' => $v,
                        'examine_id' => $member_cash,
                        'createtime' => time()
                    );
                }
                $examine_img = Db::table('too_member_cashnum_examine_img')->insertAll($img_arr);
                if (! $examine_img) throw new PDOException('保存凭证失败');
            }
            $update_res = Db::table('too_mall_member')->where('id', $data['member_id'])->update(['money' => $money - $data['money']]);
            if (! $update_res) throw new PDOException('更新提现金额失败');
            Db::commit();
            return true;
        } catch (PDOException $exception) {
            Db::rollback();
            return $exception->getMessage();
        }
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: shawn
 * Date: 2018/5/28
 * Time: 11:04
 */

namespace app\pc\model;


use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\Log;
use think\Model;

class SpotOrderModel extends Model
{
    /**
     * 判断当前人是否在当月已经购买过本票
     * @param $ticket_id
     * @param $should
     * @return mixed
     */
    public static function checkTicket($ticket_id, $should)
    {
        $condition = [
            'status' => ['in', '1, 5'],
            'add_time' => ['>', time() - 30 * 24 * 3600],
            'ticket_id' => $ticket_id
        ];
        $res = Db::name('spot_order')->where($condition)->where("FIND_IN_SET($should, traveler_ids)")->count();
        return $res;
    }

    /**
     * 插入订单信息
     * @param $data
     * @param $ticket_id
     * @return int|string
     */
    public static function insertOrder($data, $ticket_id)
    {
        Db::startTrans();
        try {
            $order = Db::name('spot_order')->insert($data);
            if ($order) {
                $r = Db::name('ticket')->where('id', $ticket_id)->setInc('sale_num', $data['num']);
                if (! $r) throw new \PDOException('创建订单失败');
                Db::commit();
                return true;
            } else throw new \PDOException('创建订单失败');
        } catch (\PDOException $e) {
            Db::rollback();
            return $e->getMessage();
        } catch (Exception $e) {
            Db::rollback();
            return $e->getMessage();
        }
    }

    /**
     * 通过订单号获取订单信息
     * @param $order_no
     * @return string
     */
    public static function getModelByOrderNo($order_no)
    {
        $condition = ['order_sn' => $order_no];
        try {
            $order = Db::name('spot_order')->where($condition)->find();
            if (! $order) {
                Log::write('order_no:' . $order_no . '未发现订单信息');
                throw new DataNotFoundException('未发现订单信息');
            }
            return $order;
        } catch (DataNotFoundException $e) {
            return $e->getMessage();
        } catch (ModelNotFoundException $e) {
            return $e->getMessage();
        } catch (DbException $e) {
            return $e->getMessage();
        }
    }

    /**
     * 支付后更新订单信息
     * @param $post_data
     * @param $id
     */
    public static function updateInformationAfterPay($post_data, $id)
    {
        $condition = ['id' => $id];
        $update_data = [
            'payment' => $post_data['total_fee'] / 100,
            'status' => 1,
            'pay_way' => '微信支付',
            'pay_time' => time(),
            'trade_no' => $post_data['transaction_id'],
        ];
        try {
            $res = Db::table('too_spot_order')->where($condition)->update($update_data);
            if (! $res) throw new \PDOException('更新订单信息失败');
        } catch (PDOException $e) {
            Log::write(date('Y-m-d H:i:s') . 'order_sn为:' . $post_data['out_trade_no'] . '更新订单新失败' . $e->getMessage());
        } catch (Exception $e) {
            Log::write(date('Y-m-d H:i:s') . 'order_sn为:' . $post_data['out_trade_no'] . '更新订单新失败' . $e->getMessage());
        }
    }


    /**
     * 更新订单信息
     * @param $update_data
     * @param $id
     * @return string
     */
    public static function updateInformationById($update_data, $id)
    {
        $condition = ['id' => $id];
        try {
            $res = Db::table('too_spot_order')->where($condition)->update($update_data);
            if (! $res) throw new \PDOException('更新订单信息失败');
            return true;
        } catch (PDOException $e) {
            return $e->getMessage();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

}
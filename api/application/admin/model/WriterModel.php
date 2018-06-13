<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class WriterModel extends Model
{
	protected $table = 'too_hd_writer';

    // 得到用户列表
	public function getWriterByWhere($where, $offset, $limit, $btn)
    {
        $where['ban'] = 0;

        // 以咕咕数量来倒序显示
        if ('gu' == $btn)
        {
            $userInfo = Db::name('mall_member m')->field('w.*, m.nickname')->
            join('hd_writer w', 'm.id = w.user_id')
            ->limit($offset, $limit)->order('gu_num desc')->where($where)->select();
        }
        else if ('fans' == $btn)
        {
            $userInfo = Db::name('mall_member m')->field('w.*, m.nickname')->
            join('hd_writer w', 'm.id = w.user_id')
            ->limit($offset, $limit)->order('fans_num desc')->where($where)->select();
        }
        else if ('follow' == $btn)
        {
            $userInfo = Db::name('mall_member m')->field('w.*, m.nickname')->
            join('hd_writer w', 'm.id = w.user_id')
            ->limit($offset, $limit)->order('follow_num desc')->where($where)->select();
        }
        else if ('question' == $btn)
        {
            $userInfo = Db::name('mall_member m')->field('w.*, m.nickname')->
            join('hd_writer w', 'm.id = w.user_id')
            ->limit($offset, $limit)->order('question_num desc')->where($where)->select();
        }
        else if ('answer' == $btn)
        {
            $userInfo = Db::name('mall_member m')->field('w.*, m.nickname')->
            join('hd_writer w', 'm.id = w.user_id')
            ->limit($offset, $limit)->order('answer_num desc')->where($where)->select();
        }
        else
        {
            
        }
        
        return $userInfo;
    }

    // 得到用户个数
    public function getAllWriter($where)
    {
        return $this->where('ban', 0)->count();
    }

    // 得到被禁言的用户列表
    public function getBanWriterList($where, $offset, $limit)
    {
        $where['ban'] = 1;  

        $userInfo = Db::name('mall_member m')->field('w.*, m.nickname')->
        join('hd_writer w', 'm.id = w.user_id')
        ->limit($offset, $limit)->order('ban_time desc')->where($where)->select();
        
        return $userInfo;
    }

    // 得到被禁言的用户个数
    public function getBanWriterNum()
    {
        return $this->where('ban', 1)->count();
    }
}
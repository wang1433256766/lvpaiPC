<?php

namespace app\admin\model;

use think\Model;

class AnswerModel extends Model
{
	protected $table = 'too_qa_answer';

	public function getAnswerByWhere($where, $offset, $limit)
    {
        return $this->field('too_qa_answer.*, u.nickname, q.title')->
        join('too_qa_user u', "u.user_id = too_qa_answer.user_id")->
        join('too_qa_question q', "q.question_id = too_qa_answer.question_id")->
        where($where)->limit($offset, $limit)->select();
    }

    public function getAllAnswer($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 删除回答
     * @param $id
     */
    public function delAnswer($id)
    {
        try{

            $this->where('answer_id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除回答成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }
}
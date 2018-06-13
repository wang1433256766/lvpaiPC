<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class QuestionModel extends Model
{
	protected $table = 'too_qa_question';

	public function getQuestionByWhere($where, $offset, $limit)
    {
        $question = Db::name('qa_question q')->field('q.id as q_id, q.title, q.read_num, q.answer_num, q.add_time as q_add_time, m.nickname')->
        join('hd_member m', 'm.id = q.user_id')->
        order('q.add_time desc')->select();
        return $question;
    }

    public function getAllQuestion($where)
    {
        return $this->where($where)->count();
    }


    /**
     * 删除问题
     * @param $id
     */
    public function delQuestion($id)
    {
        try{

            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => '删除问题成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    // 回答列表
    public function getAnswerByWhere()
    {
        $answer = Db::name('qa_answer a')->field('a.content as a_content, a.favor_num as a_favor_num, a.add_time as a_add_time, m.nickname, a.id as a_id, q.title as q_title')->
        join('hd_member m', 'm.id = a.user_id')->
        join('qa_question q', 'q.id = a.question_id')->
        order('a.add_time desc')->select();

        for ($i=0; $i<count($answer); $i++)
        {
            $answer[$i]['a_content'] = explode('\n', $answer[$i]['a_content'])[0];

        }
        // log::write('MMP');
        // log::write($answer);

        return $answer;
    }
}
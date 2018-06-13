<?php
namespace app\admin\model;

use think\Model;



class VoterecodeModel extends Model
{
  //protected $table = "too_vote";
  protected $table = "too_form";

	//查询所有信息
	public function getVoterecode($wherea,$whereb, $offset, $limit)
	{
            $res = $this->field('too_form.*,too_vote.title,too_voterecord.ip,too_voterecord.wecha_id,too_voterecord.addtime,too_voterecord.wecha_id')
            ->join('too_vote', 'too_form.vid = too_vote.id')
            ->join('too_voterecord','too_voterecord.form_id=too_form.id' )
            ->where($wherea)->where($whereb)->limit($offset, $limit)->order('id desc')->select();
            return $res;
	    
  	}
	//查询所有记录
	public function getAllAlbum($where)
    {
        return $this->where($where)->count();
    }
    //获取所有信息
    public function getAlbum()
    {
        return $this->select();
    }

    
  
    
    
    /**
     * 根据id删除信息
     * @param $id
     */
    public function delImg($id){
        $path = $this->where('id',$id)->field('picurl')->find();//是要删除五张表的，现在只删除一张表
        $path = substr($path['picurl'],1);
        if(!empty($path)){
            @unlink($path);
        }
    }
    
    
    
}
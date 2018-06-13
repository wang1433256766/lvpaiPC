<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/5/15
 * Time: 14:20
 */
namespace app\admin\model;

use think\Db;
use think\Model;
use think\Log;

class MemberCashnumExamineModel extends PublicyangModel
{
    public function __construct(){
        parent::__construct();
    }
    protected $table = 'too_member_cashnum_examine';
    //显示字段
    protected $list_fields = array('id','member_id','createtime','money','status','bankId');


    public function preprocess($rs,$stime,$etime){

        foreach ($rs as $k=>$v){
            $rs[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
            $sql = "select nickname, mobile 
                    from too_mall_member
                    WHERE id = ".$v['member_id'];
            $user_info = $this->query($sql);
            $rs[$k]['nickname'] = $user_info[0]['nickname'];
            $rs[$k]['mobile'] = $user_info[0]['mobile'];
        }

        return $rs;
    }

    /**
     * 获取凭证图片
     * */
    public function getImgAll($id){
        $sql = "
            select * 
            from too_member_cashnum_examine_img t 
            LEFT JOIN too_member_cashnum_examine t1 
            ON t.examine_id = t1.id 
            WHERE t1.id = {$id}
        ";
        $rs = $this->query($sql);
        return $rs;
    }

    /**
     * 修改提现审批状态
     * */
    public function setStatus($id,$status){
            switch ($status){
                case 2:
                    $rs = $this->setStatusGo($id);
                    break;
                case 3:
                    $rs = true;
                    break;
                case 6:
                    $rs = $this->setStatusBack($id);
                    break;
                default:
                    return false;
            }
            if($rs!==false){
                if($status!=2){
                    $sql = "update too_member_cashnum_examine 
                set status = {$status},is_go = 0 where  id = {$id}";
                    $rs = $this->execute($sql);
                    if($rs!==false){
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    return true;
                }
            }else{
                return false;
            }
    }
    private function setStatusGo($id){
        $sql = "select member_id,money,bankId from too_member_cashnum_examine WHERE id = ".$id;
        $rs = $this->query($sql);
       // $info = array();
        if($rs[0]['bankId'] == ''){
            $msg = new \com\Wxfukuai();
            $info = $msg->wx($rs[0]['member_id'],$rs[0]['money']);
        }else{
            $msg = new \com\Bank();
            $info = $msg->index($id);
        }
       log::write($info);

//        $info = simplexml_load_string($info, 'SimpleXMLElement', LIBXML_NOCDATA);
//        $rs = json_decode(json_encode($info),TRUE);
        if($info['return_code']=='SUCCESS'){
            $sql = "update too_member_cashnum_examine 
                set status = 4,is_go = 0  where  id = ".$id;
             $this->execute($sql);
            return true;
        }else{
            $sql = "update too_member_cashnum_examine 
                set status = 5,is_go = 0  where  id = ".$id;
            $this->execute($sql);
            echo json_encode(['code'=>400,'msg'=>$rs['err_code_des']]);die;
            return false;
        }
//        var_dump($info);die;
    }

    private function setStatusBack($id){
        $sql = "select member_id,money from too_member_cashnum_examine WHERE id = ".$id;
        $rs = $this->query($sql);
        $sql = "update too_mall_member set money = money + ".$rs[0]['money'].' where id = '.$rs[0]['member_id'];
        $rs = $this->execute($sql);
        if($rs!==false){
            return true;
        }else{
            return false;
        }
    }

}
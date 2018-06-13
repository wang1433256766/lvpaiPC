<?php
/**
 * Created by PhpStorm.
 * User: yara
 * Date: 2018/5/15
 * Time: 10:28
 */
namespace app\admin\controller;

use app\manage\model\MemberCashnumExamineModel;
use think\Db;

class Putforward extends Publicyang
{
    public function __construct(){
        parent::__construct();
    }
    //提现审核列表
    public function index(){
        $param = request()->param();
        if(empty($param['action'])){
            $param['action'] = '';
        }
        $action = $param['action'];
        switch ($action){
            case '':
                return $this->fetch();
                break;
            case 'ajaxList':
                $this->putForward();
                break;
            case 'lookImg':
                $this->lookImg();
                break;
            case 'setStatus':
                $this->setStatus($param['id'],$param['status']);
                break;
            default:
                self::ajaxReturn(400,'非法操作!','');
        }
    }

    /**
     * 提现审核列表ajax
     * */
    private function putForward(){
        // 接收参数
        $param = request()->param();
        $page = $param['pageNumber'];//页码
        $rows =  $param['pageSize'];//条数
        $sortName = $param['sortName'];
        $sortOrder =  $param['sortOrder'];
        $condition = '';
        if(!empty($param['str_time'])){
            $condition .= " and createtime > ".strtotime($param['str_time']);
        }
        if(!empty($param['end_time'])){
            $condition .= " and createtime < ".strtotime($param['end_time']);
        }
        $mysql = new \app\admin\model\MemberCashnumExamineModel();
//        $mysql = new Gu();
        $row = $mysql->getList($page,$rows,$condition,$sortName,$sortOrder);
        if($row['total'] > 0 ){
            $data['data']['total']=$row['total'];
            $data['data']['rows']=$row['rows'];
            $data['success']=200;
            $data['message']=null;
        }else{
            $data['data']['total']=0;
            $data['data']['rows']=[];
            $data['success']=200;
            $data['message']='暂无数据';
        }
        echo json_encode($data);die;
    }

    /**
     * 查看凭证图片
     * */
    public function lookImg(){
        $param = request()->param();
        $mysql = new \app\admin\model\MemberCashnumExamineModel();
        $rs = $mysql->getImgAll($param['id']);
        self::ajaxReturn(200,'',$rs);
    }

    /**
     * 修改提现审批
     * */
    public function setStatus($id,$status){
        if(empty($id) || empty($status)){
            self::ajaxReturn(400,'请选择数据！','');
        }

        $mysql = new \app\admin\model\MemberCashnumExamineModel();
        $is_go =Db::table('too_member_cashnum_examine')->where('id',$id)->find();
        if($is_go['is_go']==0){
            $data['is_go'] = 1;
            Db::table('too_member_cashnum_examine')->where('id',$id)->update($data);
            $rs = $mysql->setStatus($id,$status);
        }else{
            self::ajaxReturn(400,'请勿从复操作！','');die;
        }
        if($rs){
            self::ajaxReturn(200,'修改成功！','');
        }else{
            self::ajaxReturn(400,'修改失败！','');
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: 运营部王许蕾可真是个漂亮的小姐姐->手机号15575126883
 * Date: 2018/5/28
 * Time: 09:41
 */

namespace app\pc\controller;

use think\Controller;
use think\db\exception\DataNotFoundException;
use think\Request;
use app\pc\model\MemberTravelerInfoModel;
use think\Session;
use think\Db;
use PDOException;

class MemberTravelerInfo extends Common
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 出游人列表
     * */
    public function infoList(){
        $param = request()->param();
        $param['id'] = Session::get('user.id');
        $member = new MemberTravelerInfoModel();
        $rs = $member->getAllList($param);
        echo json_encode($rs);die;
    }

    /**
     * 导入出游人excel
     * */
    public function excleImport(){
        //检查上传文件
        $fileInfo = $this->checkUploadFile();
        //处理上传文件
        $uploadhelper= new UplaodYang();
        $now=date("Y-m",time());
        $path='public/uploads/pc/memberTravelerInfoExcle/'.$now;
        /*以时间来命名上传的文件*/
        $str = date ( 'Ymdhis' );
        $file_name = $str . "." . $fileInfo['fileType'];
        /*判别是不是.xls文件，判别是不是excel文件*/
        if($uploadhelper->upload($file_name, $fileInfo['fileType'],$fileInfo['tmpFile'],$path))
        {
            $fileName = $path . '/' . $file_name;
            $rs = $uploadhelper->readExcel($fileName);

            if($rs === false){
                echo json_encode(['status'=>1,'msg'=>'解析excel出错!']);
            }
            //格式化数据
            $data = $this->formatData($rs);
            $member = new MemberTravelerInfoModel();
            //保存数据
            $rs = $member->addImport($data);
            echo json_encode($rs);die;
        }else{
            echo json_encode(['status'=>1,'msg'=>'上传失败!']);
        }
    }

    /**
     * 检查上传文件类型，大小
     * @param $_FILES
     * @return string 文件类型
     */
    private function checkUploadFile(){

        if(empty($_FILES)){
            echo json_encode(['status'=>1,'msg'=>'请选择上传文件!']);
        }
        //检查是否通过http post上传
        if(!is_uploaded_file($_FILES['file']['tmp_name'])){
            echo json_encode(['status'=>1,'msg'=>'上传文件方式非法!']);
        }
        //通过扩展名判断是否为excel
        $fileInfo = pathinfo($_FILES['file']['name']);
        $fileType = strtolower($fileInfo['extension']);
        if($fileType != 'xls' && $fileType != 'xlsx'){
            echo json_encode(['status'=>1,'msg'=>'上传文件只能为excel!']);
        }

        //判断上传文件是否超过2M 2097152
        $fileSize = $_FILES['file']['size'];
        if($fileSize > 2097152){
            echo json_encode(['status'=>1,'msg'=>'上传文件大小不能超过' . (2097152/1024/1024) . 'M']);
        }

        return array('fileType'=>$fileType,'tmpFile'=>$_FILES['file']['tmp_name']);
    }

    /**
     * 格式化上传数据
     * */
    private function formatData($excel_data=''){
        if(empty($excel_data) || empty($excel_data['values'])){
            echo json_encode(['status'=>1,'msg'=>'excel中没有内容!']);
        }
        $data = array();
        $i=0;
        foreach ($excel_data['values'] as $k => $v) {
            $data[$i] = array(
                'use_name' => isset($v['A']) ? trim($v['A']) : '',
                'old' => isset($v['B']) ? trim($v['B']) : '',
                'card_type' => isset($v['C']) ? trim($v['C']) : '',
                'use_card' => isset($v['D']) ? trim($v['D']) : '',
                'mobile' => isset($v['E']) ? trim($v['E']) : '',
            );
            $i++;
        }
        array_shift($data);
        return $data;
    }


    /**
     * 设置联系人
     * */
    public function setTravelerInfo(){
        $param = request()->param();
        if($param['action']=='look'){
            $rs = Db::table('too_member_traveler_info')->where('id',trim($param['id']))->find();
            echo json_encode(['status'=>0,'msg'=>'','data'=>$rs]);die;
        }

        try {
            if($param['action']=='add'){
                $where = [
                    'use_card'=>trim($param['use_card']),
                    'member_id'=>Session::get('user.id')
                ];
                $rs = Db::table('too_member_traveler_info')->where($where)->find();
                if($rs['id']>0){
                    throw new DataNotFoundException('该联系人已存在!');
                }
                if(empty($param['use_card'])){
                    throw new DataNotFoundException('请填写身份证号!');
                }else{
                    if(!$this->is_idcard($param['use_card'])){
                        throw new DataNotFoundException('请填写正确身份证号!');
                    }
                }
                $data = [
                    'use_name'=>$param['use_name'],
                    'sex'=> $param['sex'],
                    'types'=> $param['types'],
                    'mobile' => $param['mobile'],
                    'email' => '',
                    'use_card' => $param['use_card'],
                    'status' => 1,
                    'up_time' => time(),
                    'old' => $param['old'],
                    'card_type' => $param['card_type']
                ];
                $data['default'] = 0;
                $data['add_time'] = time();
                $data['member_id'] = Session::get('user.id');
                $examine_img = Db::table('too_member_traveler_info')->insert($data);
                if (! $examine_img) throw new PDOException('添加失败!');
                $msg = '添加成功';
            }
            else if($param['action']=='update'){
                $rs = Db::table('too_member_traveler_info')->where('id',trim($param['id']))->find();
//                var_dump($rs['use_card']);
//                var_dump($param['use_card']);die;
                if($rs['use_card']!=$param['use_card']){
                    $where = [
                        'use_card'=>trim($param['use_card']),
                        'member_id'=>Session::get('user.id')
                    ];
                    $rs = Db::table('too_member_traveler_info')->where($where)->find();
                    if($rs['id']>0){
                        throw new DataNotFoundException('该联系人已存在!');
                    }
                }
                $data = [
                    'use_name'=>$param['use_name'],
                    'sex'=> $param['sex'],
                    'types'=> $param['types'],
                    'mobile' => $param['mobile'],
//                    'email' => $param['email'],
                    'use_card' => $param['use_card'],
//                    'status' => $param['status'],
                    'up_time' => time(),
                    'old' => $param['old'],
                    'card_type' => $param['card_type']
                ];
                $data['up_time'] = time();
                $examine_img = Db::table('too_member_traveler_info')->where('id',$param['id'])->update($data);
                if (! $examine_img) throw new PDOException('修改失败!');
                $msg = '修改成功!';
            }
            else if($param['action'] == 'del'){
                $examine_img = Db::table('too_member_traveler_info')->where('id',$param['id'])->delete();
                if (! $examine_img) throw new PDOException('删除失败!');
                $msg = '删除成功!';
            }
            else if($param['action'] == 'delAll'){
                $ids = $param['ids'];
                $sql = "delete from too_member_traveler_info WHERE id in ({$ids})";
                $examine_img = Db::execute($sql);
                if (! $examine_img) throw new PDOException('删除失败!');
                $msg = '删除成功!';
            }
            else{
                throw new PDOException('非法操作!');
            }
            Db::commit();
            echo json_encode(['status'=>0,'msg'=>$msg,'data'=>[]]);
        }catch (PDOException $exception){
            Db::rollback();
            echo json_encode(['status'=>1,'msg'=>$exception->getMessage(),'data'=>[]]);
        }catch (DataNotFoundException $e) {
            echo json_encode(['status'=>1,'msg'=>$e->getMessage(),'data'=>[]]);
        }
    }

    /********************php验证身份证号码是否正确函数*********************/
    private function is_idcard( $id )
    {
        $id = strtoupper($id);
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arr_split = array();
        if(!preg_match($regx, $id))
        {
            return FALSE;
        }
        if(15==strlen($id)) //检查15位
        {
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";

            @preg_match($regx, $id, $arr_split);
            //检查生日日期是否正确
            $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
            if(!strtotime($dtm_birth))
            {
                return FALSE;
            } else {
                return TRUE;
            }
        }
        else      //检查18位
        {
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match($regx, $id, $arr_split);
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
            if(!strtotime($dtm_birth)) //检查生日日期是否正确
            {
                return FALSE;
            }
            else
            {
                //检验18位身份证的校验码是否正确。
                //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                $sign = 0;
                for ( $i = 0; $i < 17; $i++ )
                {
                    $b = (int) $id{$i};
                    $w = $arr_int[$i];
                    $sign += $b * $w;
                }
                $n = $sign % 11;
                $val_num = $arr_ch[$n];
                if ($val_num != substr($id,17, 1))
                {
                    return false;
                } //phpfensi.com
                else
                {
                    return true;
                }
            }
        }

    }

}
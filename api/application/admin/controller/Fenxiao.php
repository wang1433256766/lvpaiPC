<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\model\FenxiaoModel;
use think\Db;

// 旅行视频类
class Fenxiao extends Controller
{
	public function index()
	{
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            $where['type'] = 1;
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['mobile'] = ['like', '%' . $param['searchText'] . '%'];
               
            }
            $video = new FenxiaoModel();
            $selectResult = $video->getMemberByWhere($where, $offset, $limit);
            $status = config('member_status');
            $type = config('type_status');

            foreach($selectResult as $key=>$vo){
            	$selectResult[$key]['status'] = $status[$vo['status']];
            	$selectResult[$key]['type'] = $type[$vo['type']];
            	$selectResult[$key]['add_time'] = date('Y-m-d H:i:s',$vo['add_time']);
                $operate = [
                    '降级' => "javascript:delfenxiao('".$vo['id']."')",
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $video->getMemberVideo($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    //二级分销
    public function secondf()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['too_mall_member.mobile'] = ['like', '%' . $param['searchText'] . '%'];
               
            }
            $video = new FenxiaoModel();
            $selectResult = $video->getSecondByWhere($where, $offset, $limit);
           
            $status = config('member_status');
            $type = config('type_status');

            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['status'] = $status[$vo['status']];
                $selectResult[$key]['type'] = $type[$vo['type']];
                $selectResult[$key]['add_time'] = date('Y-m-d H:i:s',$vo['add_time']);
                $operate = [
                    '降级' => "javascript:delfenxiao('".$vo['id']."')",
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $video->getMemberVideo($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
        
    }

    // 处理分销申请
    public function apply()
    {
    	if (request()->isPost())
    	{
    		// 接收参数
    		$param = request()->param();
            //查出营销员ID
            $superior_member_id = Db::name("mall_member")->where("mobile",$param['superior_mobile'])->value("id");
            //查出申请人ID
            $agents_member_id = Db::name("mall_member")->where("mobile",$param['agents_mobile'])->value("id");
            if($superior_member_id && $agents_member_id)
            {
                $date_channel['channel_id'] = $superior_member_id;
                $bool = Db::name("mall_member")->where("id",$agents_member_id)->update($date_channel);
                $data_type['type'] = 1;
                $ins_res = Db::name('mall_member')->where("mobile",$param['agents_mobile'])->update($data_type);
            
                if ($ins_res == true && $bool == true)
                {
                    $this->success('申请成功', 'index');
                }
                else
                {
                    $this->error('申请失败', 'index');
                }
            }
            else
            {
                $this->error('上级不存在或申请人未注册,请检查手机号的是否正确!', 'index');
            }
    		
    		
    	}

    	return $this->fetch();
    }
    // // 处理分销申请
    // public function apply()
    // {
    //     if (request()->isPost())
    //     {
    //         // 接收参数
    //         $param = request()->param();
    //         //查出上级ID
    //        // $superior_member_id = Db::name("mall_member")->where("mobile",$param['superior_mobile'])->value("id");
    //         //查出申请人ID
    //         $agents_member_id = Db::name("mall_member")->where("mobile",$param['agents_mobile'])->value("id");
    //         if ($agents_member_id)
    //         {
    //            // $bool = Db::name("mall_member")->where("id",$agents_member_id)->setField('parent_id',$superior_member_id);
    //             $ins_res = Db::name('mall_member')->where("id",$agents_member_id)->SetField("type",1);
            
    //             if ($ins_res)
    //             {
    //                 $this->success('申请成功', 'index');
    //             }
    //             else
    //             {
    //                 $this->error('申请人未注册,或已是一级代理!', 'index');
    //             }
    //         }
    //         else
    //         {
    //             $this->error('申请人未注册,请检查手机号是否正确!', 'index');
    //         }
            
            
    //     }

    //     return $this->fetch();
    // }

    //分销降级处理
    public function delfenxiao()
    {
    	if (request()->isAjax())
    	{
    		// 接收参数
    		$param = request()->param();
    		
    		$ins_res = Db::name('mall_member')->where("id",$param['id'])->SetField("type",0);
    		
    		if ($ins_res)
    		{
    			$res = array("status" => true);
    		}
    		else
    		{
    			$res = array("status" => false);
    		}

    		return json($res);
    	}
    }

   
}
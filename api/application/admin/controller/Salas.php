<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\model\SalasModel;
use think\Db;
use think\Request;
use think\Session;
use think\Config;
// 旅行视频类
class Salas extends Controller
{
    //渠道列表
	public function channellist()
	{
        if(request()->isAjax()){

            $param = input('param.');
//            echo '<pre>';
//            var_dump($param);
            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            $where['sala_type'] = 1;
//            if(!empty($param['searchText'])){
//                $where['name'] = ['like', '%' . $param['searchText'] . '%'];
//            }
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['mobile'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $video = new SalasModel();
            $selectResult = $video->getChannelByWhere($where, $offset, $limit);
//            var_dump($where);die;
            $status = config('member_status');
            $type = config('sala_type');

            foreach($selectResult as $key=>$vo){
            	$selectResult[$key]['status'] = $status[$vo['status']];
            	$selectResult[$key]['sala_type'] = $type[$vo['sala_type']];
            	$selectResult[$key]['add_time'] = date('Y-m-d H:i:s',$vo['add_time']);
                $operate = [
                    '详情' => url('salas/channel_travel', ['id' => $vo['id']]),
                    '注销' => "javascript:logout('".$vo['id']."')",
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }
//            if(!empty($param['searchText'])){
//                $where['name'] = ['like', '%' . $param['searchText'] . '%'];
//            }

            $return['total'] = $video->getMemberVideo($where);  //总数据
            $return['rows'] = $selectResult;
//            var_dump($return);die;
            return json($return);
        }

        return $this->fetch();
    }

    //新增渠道
    public function addchannel()
    {
        $param = request()->param("phone");
        if($param)
        {
            $member = Db::name("mall_member")->where("mobile",$param)->find();
            if(!empty($member))
            {
                $bool = Db::name("mall_member")->where('mobile',$param)->setField("sala_type",1);
                if($bool)
                {
                    $res['code'] = 1;
                    $res['msg'] = '添加成功!';
                }
                else
                {
                    $res['code'] = '-1';
                    $res['msg'] = '已经是渠道了，请勿重复操作!';
                }
                return json($res);

            }
            else
            {
                $res['code'] = '-1';
                $res['msg'] = '用户不存在!';                
            }
            return json($res);
            
        }
        else
        {
            $res['code'] = '-1';
            $res['msg'] = '请填写正确的手机号!';
        }
        return json($res);
        
    }

    //直客列表
    public function directlist()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            $where['sala_type'] = 2;
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['name'] = ['like', '%' . $param['searchText'] . '%']; 
            }
            $video = new SalasModel();
            $selectResult = $video->getDirectByWhere($where, $offset, $limit);
           
            $status = config('member_status');
            $type = config('sala_type');

            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['status'] = $status[$vo['status']];
                $selectResult[$key]['sala_type'] = $type[$vo['sala_type']];
                $selectResult[$key]['add_time'] = date('Y-m-d H:i:s',$vo['add_time']);
                $operate = [
                    '详情' => url('salas/record_info', ['id' => $vo['id']]),
                    '注销' => "javascript:logout('".$vo['id']."')",
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $video->getMemberVideo($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
        
    }

    //新增直客
    public function adddirect()
    {
        $param = request()->param("phone");
        if($param)
        {
            $member = Db::name("mall_member")->where("mobile",$param)->find();
            if(!empty($member))
            {
                $bool = Db::name("mall_member")->where('mobile',$param)->setField("sala_type",2);
                if($bool)
                {
                    $res['code'] = 1;
                    $res['msg'] = '添加成功!';
                }
                else
                {
                    $res['code'] = '-1';
                    $res['msg'] = '已经是直客了，请勿重复操作!';
                }
                return json($res);

            }
            else
            {
                $res['code'] = '-1';
                $res['msg'] = '用户不存在!';                
            }
            return json($res);
            
        }
        else
        {
            $res['code'] = '-1';
            $res['msg'] = '请填写正确的手机号!';
        }
        return json($res);
        
    }

    // 处理直客申请
    public function directapply()
    {
    	

    	return $this->fetch();
    }

    // 处理直客申请
    public function channelapply()
    {
        

        return $this->fetch();
    }

    //分销注销
    public function logout()
    {
    	if (request()->isAjax())
    	{
    		// 接收参数
    		$param = request()->param();
    		
    		$ins_res = Db::name('mall_member')->where("id",$param['id'])->SetField("sala_type",0);
    		
    		if ($ins_res)
    		{
    			$res['code'] = 1;
                $res['msg'] = '注销成功!';
    		}
    		else
    		{
    			$res['code'] = '-1';
                $res['msg'] = '注销失败!';
    		}

    		return json($res);
    	}
    }

    public function record_info()
    {
        $member_id = request()->param("member_id");
        Session::set("promote_id",$member_id);

            $member = Db::name("mall_member")->where("id",$member_id)->value("name");
            $member_id = Session::get("promote_id");
            

            $info = Request::instance()->param();

            $where = [];
            if (isset($info['status']) && $info['status'] != '-1') {
                $where['status'] = $info['status'];
            }
            if (isset($info['key']) && !empty($info['key']) ) {
                $field = isset($info['type']) && !empty($info['type']) ? $info['type'] : 'order_sn';
                $where[$field] = $info['key'];
            }

            if (isset($info['id']) && $info['id'] > 0) {
                $where['id'] = $info['id'];
            }
            if (isset($info['spot_id']) && $info['spot_id'] > 0) {
                $where['spot_id'] = $info['spot_id'];
            }
            $where['member_id'] = $member_id;
            $where['status'] = ['>',0];
            if (isset($info['from']) && !empty($info['from']) ) {
                $start = $info['from'];
                $end = !empty($info['to']) ? $info['to'] : date('Y-m-d',time());
                $time['start'] = $start;
                $time['end'] = $end;
                $data = Db::name('spot_order')->where($where)->whereTime('add_time', 'between', [$start,$end])->order('add_time desc')->paginate(10,false,['query'=>$info]);

                Session::set('order_time',$time);
            }else{
                $data = Db::name('spot_order')->where($where)->order('add_time desc')->paginate(10,false,['query'=>$info]);
            }

            Session::set('spot_order',$where);

            $page = $data->render();
            //分配初始化数据   
            $order_status = Config::get('order_status');
            //dump($order_status);exit;
            $info['key'] = isset($info['key']) ? $info['key'] : '';
            $info['type'] = isset($info['type']) ? $info['type'] : '';
            $info['from'] = isset($info['from']) ? $info['from'] : '';
            $info['to'] = isset($info['to']) ? $info['to'] : '';
            $info['status'] = isset($info['status']) ? $info['status'] : '-1';

            $this->assign('info',$info);
            $this->assign('data',$data);
            $this->assign('page',$page);
            $this->assign('order_status',$order_status);

            //$record_info = Db::name("spot_order")->where("promote_id",$member_id)->select();
            //$this->assign("record_info",$record_info);
            $this->assign("member",$member);

            $url = "http://cloud.zhonghuilv.net/index/spot/lvpaiSpot";
            $spotlist = https_request($url);
            $spotlist = json_decode($spotlist,true);

            $this->assign("spotlist",$spotlist);

        


        return $this->fetch();
    }

    public function channel_travel($id)
    {
        $member = Db::name("mall_member")->where("id",$id)->value("name");
        $where['type'] = ['>',0];
        $where['parent_id'] = $id;
        $info = Db::name("mall_member")->where($where)->paginate(10);
        $page = $info->render();

        $this->assign([
            'info' => $info,
            'page' => $page,
            'member' => $member
        ]);
        return $this->fetch();        
    }

   
}
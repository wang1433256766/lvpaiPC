<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use app\admin\model\RotaModel;

class Rotamanage extends Controller
{
    // 轮播列表，将景区和活动一起轮播
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['title'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $spot = new RotaModel();
            $selectResult = $spot->getRotaList($where, $offset, $limit);

            // 因为有两种类型的轮播，所以要区分
            foreach($selectResult as $key=>$vo){

                $type = $vo['type'];
                if ('活动' == $type)
                {
                    $type = 0;
                }
                else
                {
                    $type = 1;
                }

                $id = $vo['id'];
                if (0 == $vo['status'])
                {
                    $selectResult[$key]['status'] = "<span class='badge badge-info pointer' id='hideBtn$id' onclick = 'changeShowStatus(1, $type, $id)'>隐藏</span>";
                }
                else
                {
                    $selectResult[$key]['status'] = "<span class='badge badge-danger pointer' id='showBtn$id' onclick = 'changeShowStatus(0, $type, $id)'>显示</span>";
                }

                // 将id和类型变成一个字符串
                $msg_str = $vo['id'] . ',' . $vo['type'];
                $img = $vo['img'];
                $operate = [
                    '查看图片' => "./listShowImg/img/$msg_str",
                    '取消轮播' => "javascript:cancelRota('" . $msg_str . "')",
                    ];

                $selectResult[$key]['operate'] = showOperate($operate);
                
                
            }

            $return['total'] = $spot->getCount($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    // 改变显示状态
    public function changeShowStatus()
    {
        $param = request()->param();
        
        // 判断是否为哪种类型轮播
        if (0 == $param['type'])
        {
            $res = Db::name('activity')->where('activity_id', $param['id'])->update(['status' => $param['val']]);

            if ($res)
            {
                return 1;
            }
            else
            {
                return 0;
            }
        }
        else if (1 == $param['type'])
        {       
            $res = Db::name('shop_spot')->where('id', $param['id'])->update(['status' => $param['val']]);
            
            if ($res)
            {
                return 1;
            }
            else
            {
                return 0;
            }
        }   
        else
        {
            return 0;
        }
    }

    // 取消轮播，接收一个id和type组合的字符串
    public function cancelRota()
    {
        $msg_str = input('param.msg_str');

        $arr = explode(',', $msg_str);
        $id = $arr[0];
        $type = $arr[1];

        $spot = new RotaModel();

        $flag = $spot->cancelRota($arr[0], $arr[1]);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }   

    // 增加景区轮播
    public function addSpotRota()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where = $param['searchText'];
            }
            $spot = new RotaModel();
            $selectResult = $spot->getSpotByWhere($where, $offset, $limit);

            $rota_status = ['<span class="badge badge-info',
        '<span class="badge badge-danger">正在轮播</span>'];

            foreach($selectResult as $key=>$vo){

                // 因为是增加景区轮播，所以类型直接是1
                $type = 1;
                $id = $vo['id'];

                if (0 == $vo['rota'])
                {
                    $selectResult[$key]['rota_status'] = "<span class='badge badge-info pointer' onclick = 'changeRotaStatus(1, $type, $id)'>不是轮播</span>";
                }
                else
                {
                    $selectResult[$key]['rota_status'] = "<span class='badge badge-danger pointer' onclick = 'changeRotaStatus(0, $type, $id)'>正在轮播</span>";
                }

                if (0 == $vo['status'])
                {
                    $selectResult[$key]['spot_status'] = "<span class='badge badge-info pointer' onclick = 'changeShowStatus(1, $type, $id)'>隐藏</span>";
                }
                else
                {
                    $selectResult[$key]['spot_status'] = "<span class='badge badge-danger pointer' onclick = 'changeShowStatus(0, $type, $id)'>显示</span>";
                }

                $str_msg = $vo['id'] . ',' . '景点';
                $operate = [
                    '查看图片' => "listShowImg/img/$str_msg",
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
                }

            $return['total'] = $spot->getSpotCount($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }


    // 增加活动轮播
    public function addActivityRota()
    {
    	if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['activity_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $activity = new RotaModel();
            $selectResult = $activity->getActivityByWhere($where, $offset, $limit);

            

            foreach($selectResult as $key=>$vo){

                if ('活动' == $vo['type'])
                {
                    $type = 0;
                }
                else if ('景区' == $vo['type'])
                {
                    $type = 1;
                }
                else
                {

                }
                $id = $vo['activity_id'];

                if (0 == $vo['status'])
                {
                    $selectResult[$key]['spot_status'] = "<span class='badge badge-info pointer' onclick = 'changeShowStatus(1, $type, $id)'>隐藏</span>";
                }
                else
                {
                    $selectResult[$key]['spot_status'] = "<span class='badge badge-danger pointer' onclick = 'changeShowStatus(0, $type, $id)'>显示</span>";
                }

                if (0 == $vo['rota'])
                {
                    $selectResult[$key]['rota_status'] = "<span class='badge badge-info pointer' onclick = 'changeRotaStatus(1, $type, $id)'>不是轮播</span>";
                }
                else
                {
                    $selectResult[$key]['rota_status'] = "<span class='badge badge-danger pointer' onclick = 'changeRotaStatus(0, $type, $id)'>正在轮播</span>";
                }

                $str = $vo['activity_id'] . ',' . '活动';
                $operate = [
                    '查看图片' => "./listShowImg/img/$str",
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $activity->getActivityCount($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    // 改变轮播排序
    public function changeSort()
    {
        $param = request()->param();

        if (0 == $param['type'])
        {
            $res = Db::name('activity')->where('activity_id', $param['id'])->update(['rota_sort' => $param['sort']]);
            if ($res)
            {
                return 1;
            }
        }
        else if (1 == $param['type'])
        {
            $res = Db::name('shop_spot')->where('id', $param['id'])->update(['rota_sort' => $param['sort']]);
            if ($res)
            {
                return 1;
            }
        }
        else
        {
            return 0;   
        }
    }

    // 改变轮播状态
    public function changeRotaStatus()
    {
        $param = request()->param();
        
        // 0表示活动
        if (0 == $param['type'])
        {
            $res = Db::name('activity')->where('activity_id', $param['id'])->update(['rota' => $param['val']]);

            if ($res)
            {
                return 1;
            }
            else
            {
                return 0;
            }
        }
        else if (1 == $param['type'])  // 1表示景区
        {
            $res = Db::name('shop_spot')->where('id', $param['id'])->update(['rota' => $param['val']]);

            if ($res)
            {
                return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            return 0;
        }
    }

    // 显示图片
    public function listShowImg()
    {
        $str_msg = input('img');

        $arr = explode(',', $str_msg);

        if ('活动' == $arr[1])
        {
            $img = Db::name('activity')->where('activity_id', $arr[0])->value('img_path');
            $img = explode(',', $img);
            $img = $img[0];

            $this->assign('img', $img);
            return $this->fetch();
        }
        else if ('景点' == $arr[1])
        {   

            $img = Db::name('shop_spot')->where('id', $arr[0])->value('thumb');

            $this->assign('img', $img);
            return $this->fetch();
        }
        else
        {

        }
    }
}
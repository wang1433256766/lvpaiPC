<?php

namespace app\admin\controller;

use think\Controller;
use app\admin\model\TravelModel;
use think\Db;

class Travel extends Controller
{
    public function hotData()
    {
        $param = input('param.');

        dump($param);
    }

    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            // 状态，得到最新，还是最热 
            $btn = isset($param['btn']) ? $param['btn'] : 'new';

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['title'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $travel = new TravelModel();
            $selectResult = $travel->getTravelByWhere($where, $offset, $limit, $btn);

            $status = config('user_status');

            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['status'] = 1;

                $operate = [
                    '编辑' => url('travelEdit', ['id' => $vo['id']]),
                    '删除' => "javascript:travelsDel('".$vo['id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $travel->getAllTravel($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    // 删除游记
    public function travelsDel()
    {
        $id = input('param.id');


        $role = new TravelModel();
        $flag = $role->delTravel($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    // 改变游记点赞数量
    public function changeFavorNum()
    {
        $param = request()->param();

        $arr['favor_num'] = $param['value'];
        $upd_res = Db::name('travels')->where('id', $param['id'])->update($arr);

        $param['status'] = $upd_res;
        return $param;
    }

    // 修改游记的阅读量
    public function changeReadNum()
    {
        $param = request()->param();

        $arr['read_num'] = $param['value'];
        $upd_res = Db::name('travels')->where('id', $param['id'])->update($arr);
        $param['status'] = $upd_res;

        return $param;
    }

    // 修改游记的回复量
    public function changeReplyNum()
    {
        $param = request()->param();

        $arr['reply_num'] = $param['value'];
        $upd_res = Db::name('travels')->where('id', $param['id'])->update($arr);
        $param['status'] = $upd_res;

        return $param;
    }

    // 添加游记
    public function travelAdd()
    {
        if (request()->isPost())
        {
            // 接收参数
            $param = request()->param();
           
            $destination = $this->getFilename();

            $res = move_uploaded_file($_FILES['img']['tmp_name'], $destination);

            // 封面图片路径
            $param['pic1'] = 'http://zhlsfnoc.com/' . $destination;

            $pattern = '/<[a-z]*\s[a-z]*="/';
            $param['content'] = preg_replace($pattern, '&lt;img src="http://zhlsfnoc.com', $param['content']);
            $param['user_id'] = mt_rand(8, 12); // 在用户表中加了5个假用户
            $ins_res = Db::name('travels')->insert($param);

            if ($ins_res)
            {
                $this->success('添加成功', 'index');
            }
            else
            {
                $this->error('添加失败', 'index');

            }
            exit();
        }

        return $this->fetch();
    }

    public function getFilename()
    {
        $date = 'uploads/travel/' . date('Y-m-d');
        if (is_dir($date))
        {
            $uuid = $this->getUuid();
            $filename = $uuid . '.jpg';
            return $date . '/' . $filename;
        }
        else
        {
            mkdir($date);
            $uuid = $this->getUuid();
            $filename = $uuid . '.jpg';
            return $date . '/' . $filename;
        }
    }

    public function getUuid()
    {
        return md5(uniqid() . microtime(true) . mt_rand(0, 99999));
    }

    // 编辑游记
    public function travelEdit()
    {
        $id = input('id');
        $travel = Db::name('travels')
                  ->field('id, pic1, title, content, address, play_time, play_num, person_price, trip_days')
                  ->where('id', $id)
                  ->find();

        // 接收修改的内容且更新
        if (request()->isPost())
        {
            $param = request()->param();
            unset($param['id']);
			// 修改图片之后的操作
			if (0 == $_FILES['img']['error'])
			{
				// 得到旧图片路径
				$old_img_path = Db::name('travels')->where('id', $param['travel_id'])->value('pic1');
				// 删除旧图片
				unlink(substr($old_img_path, 20));

				// 上传新图片
				move_uploaded_file($_FILES['img']['tmp_name'], substr($old_img_path, 20));
				$param['pic1'] = $old_img_path;
			}
			$travel_id = $param['travel_id'];
			unset($param['travel_id']);	

			$upd_res = Db::name('travels')->where('id', $travel_id)->update($param);
			
			$this->success('更新成功', 'index');
        }

        $this->assign('travel', $travel);
        return $this->fetch();
    }
}
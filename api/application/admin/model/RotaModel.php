<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class RotaModel extends Model
{
	protected $table = 'too_shop_spot';

	// 得到轮播列表
	public function getRotaList($where, $offset, $limit)
	{
		// 得到活动轮播
		$activity = Db::name('activity')->field('activity_id, img_path, activity_name, add_time, status, rota_sort')->where(['rota' => 1, 'status' => 1])->select();	
		
		// 得到景区轮播
		$spot = Db::name('shop_spot')->field('id, thumb, title, status, rota_sort, add_time')->where(['rota' => 1, 'status' => 1])->select();

		// 因为是从两个表中拿出来的数据，所以要先把字段统一
		for ($i=0; $i<count($activity); $i++)
		{
			$activity[$i]['type'] = '活动';
			$activity[$i]['id'] = $activity[$i]['activity_id'];
			unset($activity[$i]['activity_id']);	

			$activity[$i]['img'] = explode(',', $activity[$i]['img_path']);
			$activity[$i]['img'] = $activity[$i]['img'][0];
			unset($activity[$i]['img_path']);

			$activity[$i]['name'] = $activity[$i]['activity_name'];
			unset($activity[$i]['activity_name']);
		}	

		// 处理字段统一问题
		for ($i=0; $i<count($spot); $i++)
		{
			$spot[$i]['type'] = '景点';
			$spot[$i]['img'] = $spot[$i]['thumb'];
			unset($spot[$i]['thumb']);

			$spot[$i]['name'] = $spot[$i]['title'];
			unset($spot[$i]['title']);

			$spot[$i]['add_time'] = date('Y-m-d h:i:s', $spot[$i]['add_time']);
		}

		// 合并2个数组
		$arr = array_merge($spot, $activity);

		// 根据rota_sort将数组排序，rota_sort是轮播顺序
		$length = count($arr);
		for ($i=0; $i<$length-1; $i++)
		{
			for ($j=$i+1; $j<$length; $j++)
			{
				if ($arr[$i]['rota_sort'] > $arr[$j]['rota_sort'])
				{
					$temp['rota_sort'] = $arr[$i]['rota_sort'];
					$temp['name'] = $arr[$i]['name'];
					$temp['id'] = $arr[$i]['id'];
					$temp['status'] = $arr[$i]['status'];
					$temp['add_time'] = $arr[$i]['add_time'];
					$temp['img'] = $arr[$i]['img'];
					$temp['type'] = $arr[$i]['type'];

					$arr[$i]['rota_sort'] = $arr[$j]['rota_sort'];
					$arr[$i]['name'] = $arr[$j]['name'];
					$arr[$i]['id'] = $arr[$j]['id'];
					$arr[$i]['status'] = $arr[$j]['status'];
					$arr[$i]['add_time'] = $arr[$j]['add_time'];
					$arr[$i]['img'] = $arr[$j]['img'];
					$arr[$i]['type'] = $arr[$j]['type'];


					$arr[$j]['rota_sort'] = $temp['rota_sort'];
					$arr[$j]['name'] = $temp['name'];
					$arr[$j]['id'] = $temp['id'];
					$arr[$j]['status'] = $temp['status'];
					$arr[$j]['add_time'] = $temp['add_time'];
					$arr[$j]['img'] = $temp['img'];
					$arr[$j]['type'] = $temp['type'];
 				}
			}
		} 

		// 将轮播顺序改成 input:t形式，到时候可以用ajax改
		for ($i=0; $i<$length; $i++)
		{
			$type = $arr[$i]['type'];
			if ('活动' == $type)
			{
				$type = 0;
			}
			else if ('景点' == $type)
			{
				$type = 1;
			}

			$id = $arr[$i]['id'];
			$rota_sort = $arr[$i]['rota_sort'];
			$arr[$i]['rota_sort'] = "<input type='text' value='$rota_sort' size='4' onblur = 'changeSort($id, this.value, $type)'>";
		}

		session('rota_count', $length);

		return $arr;
	}

	// 得到轮播数量
	public function getCount($where)
	{
		$count = session('rota_count');
		session('rota_count', null);
		return $count;
	}

    /**
     * 取消轮播，根据type来更新rota
     * @param $id, $type
     */
    public function cancelRota($id, $type)
    {
        try{
        	switch ($type) {
        		case '活动':
        			Db::name('activity')->where('activity_id', $id)->update(['rota' => 0]);
        			break;
        		case '景点':
        			Db::name('shop_spot')->where('id', $id)->update(['rota' => 0]);
        			break;
        		default:
        			
        			break;
        	}
            return ['code' => 1, 'data' => '', 'msg' => '取消轮播成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    // 根据条件搜索景区
    public function getSpotByWhere($where, $offset, $limit)
    {
    	if (empty($where))
    	{
    		return null;
    	}
    	else
    	{
    		$condition['title'] = ['like', "%$where%"];
    		return Db::name('shop_spot')->field('id, title, status, rota')->where($condition)->select();
    	}
    }

    // 得到符合搜索条件的景区数量
    public function getSpotCount($where)
    {
    	$condition['title'] = ['like', "%$where%"];
    	$count = Db::name('shop_spot')->where($condition)->count();
    	return $count;
    }

    // 得到符合搜索条件的活动数量
    public function getActivityCount($where)
    {
    	return Db::name('activity')->where($where)->count();
    }

    // 根据条件搜索活动
    public function getActivityByWhere($where, $offset, $limit)
    {
    	if (empty($where))
    	{
    		return null;
    	}
    	else
    	{
    		$arr = Db::name('activity')->field('activity_id, activity_name, status, rota')->where($where)->select();
    		foreach ($arr as $k => $v)
    		{
    			$arr[$k]['type'] = '活动';
    		}
    		return $arr;
    	}
    }
}
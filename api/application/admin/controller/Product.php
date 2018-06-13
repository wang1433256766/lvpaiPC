<?php

namespace app\admin\controller;

use think\Controller;
use think\Db;
use app\admin\model\ProductModel;

class Product extends Controller
{
	// 文创产品列表
	public function index()
	{
		if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $product = new ProductModel();
            $selectResult = $product->getProductByWhere($where, $offset, $limit);

            foreach($selectResult as $key=>$vo){

                $operate = [
                    '编辑' => url('productEdit', ['id' => $vo['id']]),
                    '删除' => "javascript:productDel('".$vo['id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
            }

            $return['total'] = $product->getAllProduct($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
	}

	// 添加文创产品
	public function productAdd()
	{
		if (request()->isPost())
		{
			// 接收参数
			$param = request()->param();

			$destination = $this->getFilename() . '.jpg';

			move_uploaded_file($_FILES['img']['tmp_name'], $destination);

			// 处理结束时间
			$param['end_time'] = handleEndTime($param['end_time']);

			$param['cover_img'] = 'http://zhlsfnoc.com/' . $destination;
			$ins_res = Db::name('paradise_product')->insert($param);
			
			if ($ins_res)
			{
				$this->success('添加成功', 'index');
			}
			else
			{
				$this->error('添加失败', 'index');
			}
		}

		return $this->fetch();
	}

	public function getFilename()
	{
		$dir = 'uploads/paradise/product/' . date('Y-m-d');
		if (is_dir($dir))
		{
			return $dir . '/' . getUuid();
		}
		else
		{
			mkdir($dir);
			return $dir . '/' . getUuid();
		}
	}

	// 编辑文创产品
	public function productEdit()
	{
		$id = input('id');

		$product = Db::name('paradise_product')
				   ->field('id, name, desc, score, cash, stock, cover_img, end_time')
				   ->where('id', $id)
				   ->find();
		$product['end_time'] = date('Y-m-d', $product['end_time']);

		if (request()->isPost())
		{
			$param = request()->param();
			$param['id'] = $param['product_id'];
			unset($param['product_id']);

			// 处理结束时间
			$param['end_time'] = handleEndTime($param['end_time']);
			
			// 得到旧图片的路径
			$old_path = Db::name('paradise_product')->where('id', $param['id'])->value('cover_img');

			$destination = substr($old_path, 20);
			unlink($destination);	
			
			move_uploaded_file($_FILES['img']['tmp_name'], $destination);

			Db::name('paradise_product')->where('id', $param['id'])->update($param);

			$this->success('编辑成功', 'index');
		}

		$this->assign('product', $product);
		return $this->fetch();
	}

	// 删除文创产品
	public function productDel()
	{
		$id = input('id');

		$del_res = Db::name('paradise_product')->where('id', $id)->delete();
		

		$arr['code'] = $del_res ? 1 : 0;

		return $arr;
	}
}
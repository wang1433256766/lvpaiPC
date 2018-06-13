<?php

namespace app\admin\controller;

use app\admin\model\NewsModel;

use think\Request;

use think\Db;

use app\admin\model\NewsMenuModel;

use think\File;

use think\Controller;

use think\log;

class News extends controller

{

	public function index()

	{

		if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];

            $offset = ($param['pageNumber'] - 1) * $limit;



            $where = [];

            //判断视图传值

            if (isset($param['searchText']) && !empty($param['searchText'])) {

                $where['title'] = ['like', '%' . $param['searchText'] . '%'];

            }

            $new = new NewsModel();

            $selectResult = $new->getNewsBy($where, $offset, $limit);

            $newmenu = new NewsMenuModel;

            //整合数据

            $base = config('base');

            foreach($selectResult as $key=>$vo){

               $selectResult[$key]['base'] = $base[$vo['base']];

            	//操作整合

                $operate = [

                    '编辑' => url('news/newsEdit', ['id' => $vo['id']]),

                    '插入音频' => url('news/audio', ['id' => $vo['id']]),

                    '取消置顶' => url('news/offbase', ['id' => $vo['id']]),

                    '置顶' => url('news/onbase', ['id' => $vo['id']]),

                    '删除' => "javascript:newsDel('".$vo['id']."')"

                ];

                $selectResult[$key]['operate'] = showOperate($operate);  

            }



            $return['total'] = $new->getAllNews($where);  //总数据

            $return['rows'] = $selectResult;

            

            return json($return); 

           

        }

       



		return $this->fetch();

	}

	public function audio()
	{

		$id = input('id');

		if(request()->isPost()){                   

            $data = $_POST;

            $id = $data['id'];

            $file = request()->file('thumb');

            $new = new NewsModel;            

            //判断是否有图片上传

            if (!empty($file)) {

                //更新图片               

                $new->delImg($id);

                $file_info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'news');

                if ($file_info) {

                    $data['thumb'] = $file_info->getSaveName();

                    $data['thumb'] = 'http://zhlsfnoc.com'."/uploads/news/".str_replace('\\', "/", $data['thumb']);

                    $data['base'] = 1;

                    $data['mp3_status'] = 1;
                }

            }

                                   

            $res = $new->newsEdit($data);

            if ($res) {

                return $this->success('上传音频成功', 'index');

                die;

            }

            else{

                return $this->error('上传音频失败', 'index');

                die;

            }

        }

		$new = new NewsModel;

		$newmenu = new NewsMenuModel;

		$this->assign([

			'new' => $new->getOne($id),

			'menu' => $newmenu->getNewsMenu()

			]);

		return $this->fetch();

	}

	

	//添加轮播新闻

	public function newsAdd()
	{

        $menu = new NewsMenuModel;

        if(request()->isPost()) {

            $data = $_POST;

            $files = request()->file('img');

            $length = count($files);
            // 当没有选择封面图片的时候，数组长度为0
            if(0 == count($files))
            {	
            	$param = request()->param();
					
				$res = Db::name('hd_news')->insert($param);

				if ($res)
				{
					$this->success('添加成功', 'index');
				}           	
				else
				{
					$this->eroor('添加失败', 'index');
				}
            }
            else // 当有图片的时候
            {
                $param = request()->param();
                // 1张图
            	if (1 == $length) 
            	{
					$info = $files[0]->move('uploads/news');
					$filename = $info->getSaveName();
					$filename = str_replace('\\', '/', $filename);
					$param['pic1'] = 'http://zhlsfnoc.com/uploads/news/' . $filename;
					$ins_res = Db::name('hd_news')->insert($param);             		
					if ($ins_res)
					{
						$this->success('添加成功', 'index');
					}
					else
					{
						$this->error('添加失败', 'index');
					}
            	}
            	else if (2 == $length) // 2张图
            	{ 	
            		for ($i=0; $i<2; $i++)
            		{
            			$info = $files[$i]->move('uploads/news');
            			$filename = $info->getSaveName();
            			$filename = str_replace('\\', '/', $filename);

            			$key = 'pic' . ($i+1); 
            			$param["$key"] = 'http://zhlsfnoc.com/uploads/news/' . $filename;
            		}

            		$ins_res = Db::name('hd_news')->insert($param);

            		if ($ins_res)
            		{
            			$this->success('添加成功', 'index');
            		}
            		else
            		{
            			$this->error('添加失败', 'index');
            		}
            	}	
            	else // 3张图
            	{
            		for ($i=0; $i<3; $i++)
            		{
            			$info = $files[$i]->move('uploads/news');
            			$filename = $info->getSaveName();
            			$filename = str_replace('\\', '/', $filename);

            			$key = 'pic' . ($i+1);
            			$param["$key"] = 'http://zhlsfnoc.com/uploads/news/' . $filename;

            		}

            		$ins_res = Db::name('hd_news')->insert($param);
            		if ($ins_res)
            		{
            			$this->success('添加成功', 'index');
            		}
            		else
            		{
            			$this->error('添加失败', 'index');
            		}
            	}

            }



        }  

        $this->assign([

            'menu' => $menu->getNewsMenu()

            ]);

		return $this->fetch();

	}





    //修改新闻
    public function newsEdit()
    {
        $id = Request::instance()->param("id");
        
        $menu = new NewsMenuModel;

        $new = new NewsModel();

        if(request()->isPost()) {

        	// 获取模版页面的值
            $data_post = $_POST;

            // 获取该新闻id
            $id = $data_post['id'];

            // 获取三张图片对象
            $files = request()->file('img');
                foreach ($files as $key=>$value)
                {
                	// 当修改两张图片时
                    if(!empty($files[0]) && !empty($files[1])) 
                    {

                        $info1= $files[0]->move(ROOT_PATH . 'public' . DS . 'uploads'. DS . 'news');

                        if (!empty($info1)) {

                            $data_a['pic1'] = $info1->getSaveName();

                            $data_a['pic1'] = 'http://zhlsfnoc.com'."/uploads/news/".str_replace('\\', "/", $data_a['pic1']);



                        }

                        $info2= $files[1]->move(ROOT_PATH . 'public' . DS . 'uploads'. DS . 'news');

                        if(!empty($info2)){

                            $data_b['pic2'] = $info2->getSaveName();

                            $data_b['pic2'] = 'http://zhlsfnoc.com'."/uploads/news/".str_replace('\\', "/", $data_b['pic2']);
                        }

                        $param['title'] = $data_post['title'];

                        $param['id'] = $data_post['id'];

                        $param['username'] = $data_post['username'];

                        $param['cate_id'] = $data_post['cate_id'];

                        $param['pic1'] = $data_a['pic1'];

                        $param['pic2'] = $data_b['pic2'];

                        $param['pic3'] = '';

                        $param['content'] = $data_post['content'];

                        $param['base'] = 1;

                        $param['desc'] = $data_post['desc'];



                        $flag = $new->newsEdit($param);
                     
                        if ($flag) {
                            $this->success('修改成功', "index");
                            exit();
                        }
                        else{
                            $this->error('修改失败', 'index');
                            exit();
                        }

                    }
                    elseif(!empty($files[0]) && !empty($files[1])  && !empty($files[2]))  // 当修改三张图片时
                    {

                        $info1= $files[0]->move(ROOT_PATH . 'public' . DS . 'uploads'. DS . 'news');

                        if (!empty($info1)) {

                            $data_a['pic1'] = $info1->getSaveName();

                            $data_a['pic1'] = 'http://zhlsfnoc.com'."/uploads/news/".str_replace('\\', "/", $data_a['pic1']);



                        }

                        $info2= $files[1]->move(ROOT_PATH . 'public' . DS . 'uploads'. DS . 'news');

                        if(!empty($info2)){

                            $data_b['pic2'] = $info2->getSaveName();

                            $data_b['pic2'] = 'http://zhlsfnoc.com'."/uploads/news/".str_replace('\\', "/", $data_b['pic2']);
                        }

                        $info3= $files[2]->move(ROOT_PATH . 'public' . DS . 'uploads'. DS . 'news');

                        if (!empty($info3)) {

                            $data_c['pic3'] = $info3->getSaveName();

                            $data_c['pic3'] = 'http://zhlsfnoc.com'."/uploads/news/".str_replace('\\', "/", $data_c['pic3']);
                        }

                        $param['title'] = $data_post['title'];

                        $param['id'] = $data_post['id'];

                        $param['username'] = $data_post['username'];

                        $param['cate_id'] = $data_post['cate_id'];

                        $param['pic1'] = $data_a['pic1'];

                        $param['pic2'] = $data_b['pic2'];

                        $param['pic3'] = $data_c['pic3'];

                        $param['content'] = $data_post['content'];

                        $param['base'] = 1;

                        $param['desc'] = $data_post['desc'];

                        $flag = $new->newsEdit($param);

                        if ($flag) {
                            $this->success('修改成功', 'index');
                            exit();
                        }

                        else{
                            return $this->error('修改失败', 'index');
                            exit();
                        }

                    }
                    elseif(!empty($files[0]))
                    {
                        $info1= $files[0]->move(ROOT_PATH . 'public' . DS . 'uploads'. DS . 'news');

                        if (!empty($info1)) {

                            $data_a['pic1'] = $info1->getSaveName();

                            $data_a['pic1'] = 'http://zhlsfnoc.com'."/uploads/news/".str_replace('\\', "/", $data_a['pic1']);

                            $data_a['base'] = 1;

                        }
                        $param['title'] = $data_post['title'];
                        $param['id'] = $data_post['id'];
                        $param['username'] = $data_post['username'];
                        $param['cate_id'] = $data_post['cate_id'];
                        $param['pic1'] = $data_a['pic1'];
                        $param['pic2'] = '';
                        $param['pic3'] = '';
                        $param['content'] = $data_post['content'];
                        $param['base'] = 1;
                        $param['desc'] = $data_post['desc'];

                        $flag = $new->newsEdit($param);

                        if ($flag) {
                            $this->success('修改成功', 'index');
                            exit();
                        }
                        else{
                            return $this->error('修改失败', 'index');
                            exit();
                        }

                    }
                }
            }

        $onenews = Db::name("hd_news")->where("id",$id)->find();

        $this->assign([
            'onenews' =>$onenews,
            'menu' => $menu->getNewsMenu()
        ]);

        return $this->fetch();
    }
	

    // 编辑新闻
    public function newsEdit1()
    {
    	$param = request()->param();
    	$image1 = request()->file('img1'); // 第一张图片
    	$image2 = request()->file('img2'); // 第二张图片
    	$image3 = request()->file('img3'); // 第三张图片

    	// 0 3 1 2 3 2 2 2
    	// 三张图片都不选
    	if (empty($image1) && empty($image2) && empty($image3)) 
    	{
    		$res = Db::name('hd_news')->where('id', $param['id'])->update($param);
    		
    		if ($res)
    		{
    			$this->success('修改成功', 'index');
    		}
    		else
    		{
    			$this->error('什么都没改', 'index');
    		}
    	}   
    	// 选三张图片
		else if ($image1 && $image2 && $image3)
		{
			// 因为要上传三张图片，所以放入一个数组中
            $arr[] = $image1;
            $arr[] = $image2;
            $arr[] = $image3;
          	for ($i=0; $i<3; $i++)
          	{
          		$info = $arr[$i]->move(ROOT_PATH . 'public' . DS . 'uploads' . DS .  'news');	
          		if ($info)
          		{
          			$filename = $info->getSaveName();
          			$filename = str_replace('\\', '/', $filename);	
          			$filename = 'http://zhlsfnoc.com/' . 'uploads/' . 'news/' . $filename;	
          			$pic[] = $filename; 
          		}
          		else
          		{
          			$pic = 0;
          		}
          	}
         	$param['pic1'] = $pic[0];
         	$param['pic2'] = $pic[1];
         	$param['pic3'] = $pic[2];
         	
         	$res = Db::name('hd_news')->where('id', $param['id'])->update($param);

         	if ($res)
         	{
         		$this->success('修改成功', 'index');
         	}
         	else
         	{
         		$this->eroor('修改失败', 'index');
         	}
		}    
		// 选第一张图片，另外两张为null
		else if ($image1 && empty($image2) && empty($image3))
		{	
			$info = $image1->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'news');
			if ($info)
			{
				$filename = $info->getSaveName();
				$filename = str_replace('\\', '/', $filename);
          		$filename = 'http://zhlsfnoc.com/' . 'uploads/' . 'news/' . $filename;
			}
			$param['pic1'] = $filename;

			$res = Db::name('hd_news')->where('id', $param['id'])->update($param);

         	if ($res)
         	{
         		$this->success('修改成功', 'index');
         	}
         	else
         	{
         		$this->eroor('修改失败', 'index');
         	}
		}
		// 选第二张图片
		else if ($image2 && empty($image1) && empty($image3))
		{
			$info = $image2->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'news');
			if ($info)
			{
				$filename = $info->getSaveName();
				$filename = str_replace('\\', '/', $filename);
          		$filename = 'http://zhlsfnoc.com/' . 'uploads/' . 'news/' . $filename;
			}
			$param['pic2'] = $filename;

			$res = Db::name('hd_news')->where('id', $param['id'])->update($param);

         	if ($res)
         	{
         		$this->success('修改成功', 'index');
         	}
         	else
         	{
         		$this->eroor('修改失败', 'index');
         	}
		}
		// 选第三张图片
		else if ($image3 && empty($image1) && empty($image2))
		{
			$info = $image3->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'news');
			if ($info)
			{
				$filename = $info->getSaveName();
				$filename = str_replace('\\', '/', $filename);
          		$filename = 'http://zhlsfnoc.com/' . 'uploads/' . 'news/' . $filename;
			}
			$param['pic3'] = $filename;

			$res = Db::name('hd_news')->where('id', $param['id'])->update($param);

         	if ($res)
         	{
         		$this->success('修改成功', 'index');
         	}
         	else
         	{
         		$this->eroor('修改失败', 'index');
         	}
		}
		// 选第1, 2张图片
		else if ($image2 && $image1 && empty($image3))
		{
			$arr[] = $image1;
			$arr[] = $image2;

			for ($i=0; $i<2; $i++)
			{
				$info = $arr[$i]->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'news');	

				if ($info)
				{
					$filename = $info->getSaveName();
					$filename = str_replace('\\', '/', $filename);
					$filename = 'http://zhlsfnoc.com/' . 'uploads/' . 'news/' . $filename;
					$pic[] = $filename;
				}
			}
			$param['pic1'] = $pic[0];
			$param['pic2'] = $pic[1];

			$res = Db::name('hd_news')->where('id', $param['id'])->update($param);

			if ($res)
			{
				$this->success('修改成功', 'index');
			}
			else
			{
				$this->error('修改失败', 'index');
			}
		}
		// 选第2, 3张图片
		else if ($image2 && $image3 && empty($image1))
		{
			$arr[] = $image2;
			$arr[] = $image3;

			for ($i=0; $i<2; $i++)
			{
				$info = $arr[$i]->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'news');	

				if ($info)
				{
					$filename = $info->getSaveName();
					$filename = str_replace('\\', '/', $filename);
					$filename = 'http://zhlsfnoc.com/' . 'uploads/' . 'news/' . $filename;
					$pic[] = $filename;
				}
			}
			$param['pic2'] = $pic[0];
			$param['pic3'] = $pic[1];

			$res = Db::name('hd_news')->where('id', $param['id'])->update($param);

			if ($res)
			{
				$this->success('修改成功', 'index');
			}
			else
			{
				$this->error('修改失败', 'index');
			}
		}
		// 选第1, 3张图片
		else if ($image1 && $image3 && empty($image2))
		{
			$arr[] = $image1;
			$arr[] = $image3;

			for ($i=0; $i<2; $i++)
			{
				$info = $arr[$i]->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'news');	

				if ($info)
				{
					$filename = $info->getSaveName();
					$filename = str_replace('\\', '/', $filename);
					$filename = 'http://zhlsfnoc.com/' . 'uploads/' . 'news/' . $filename;
					$pic[] = $filename;
				}
			}
			$param['pic1'] = $pic[0];
			$param['pic3'] = $pic[1];

			$res = Db::name('hd_news')->where('id', $param['id'])->update($param);

			if ($res)
			{
				$this->success('修改成功', 'index');
			}
			else
			{
				$this->error('修改失败', 'index');
			}
		}
    	

    }
	/*

	 * 新闻置顶

	 * Bela

	 * 2017-07-14 15:32:18

	 * 

	 */

	public function onbase()

	{

	    $id = Request::instance()->param("id");

	    if(!empty($id))

	    {

	        $info = Db::name("hd_news")->where("id",$id)->value("base");

	       if($info == 0)

	       {

	           $bool = Db::name("hd_news")->where("id",$id)->setField("base",1);

	           if($bool)

	           {

	              $this->redirect("/admin/news/index");

	           }

	           else

	           {

	              $this->redirect("/admin/news/index");

	           }

	       }

	       else 

	       {

	            $this->redirect("/admin/news/index");

	       }

	          

	    }

	}

	

	/*

	 * 新闻取消置顶

	 * Bela

	 * 2017-07-14 15:42:42

	 *

	 */

	public function offbase()

	{

	    $id = Request::instance()->param("id");

	    if(!empty($id))

	    {

	        $info = Db::name("hd_news")->where("id",$id)->value("base");

	       if($info == 1)

	       {

	           $bool = Db::name("hd_news")->where("id",$id)->setField("base",0);

	           if($bool)

	           {

	              $this->redirect("/admin/news/index");

	           }

	           else

	           {

	              $this->redirect("/admin/news/index");

	           }

	       }

	       else 

	       {

	            $this->redirect("/admin/news/index");

	       }

	          

	    }

	}

	

	

	public function newsDel()

	{

		$id = input('id');

		$new = new NewsModel;

		//删除图片

		$new->delImg($id);

		$res = $new->delNews($id);

		return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);

	}

	public function lists()

	{		

			$new = new NewsModel();

			$result = $new->getAll();

			return json($result);		

	}

    public function listsOne()

    {

        $id = input('id');

        $new = new NewsModel;

        $menu = new NewsMenuModel;

        $result = $new->getOne($id);

        $menu_id = $result['cate_id'];

        $menus = $menu->getOneNewsMenu($menu_id);

        $result['cate_id'] = $menus['name'];

        return json($result);

    }

    public function getNews()

    {

        $contents = file_get_contents("http://news.sina.com.cn/china/xlxw/2017-03-26/doc-ifycstww1133496.shtml");

        preg_match_all('/<h>(.*?)<\/h>/',$contents,$arr);

        $str = "";

        foreach ($arr[0] as $k => $v) {

            $str .=$v;

        }

        $str = preg_replace("/<a>.*<\/a>/","",$str);

        dump($str);die;

    }

    //载入专题新闻

    public function Ztnewsadd()

    {

        $menu = new NewsMenuModel;

        if(request()->isPost()) {

            $data = $_POST;

            $file = request()->file('thumb'); 

            //判断是否有图片上传          

            if (!empty($file)) {

                $file_info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'article');

                if ($file_info) {

                    $data['thumb'] = $file_info->getSaveName();

                    $data['thumb'] = "/uploads/article/".str_replace('\\', "/", $data['thumb']);

                }

            }

            

            $flag = Db::name("zt_link")->insert($data);

            if ($flag) {

                return $this->success('添加成功');

                die;

            }

            else{

                return $this->error('添加失败');

                die;

            }

        }  

        $this->assign([

            'menu' => $menu->getNewsMenu()

            ]);

		return $this->fetch();

    }

    // 带MP3的新闻  
    public function audionews()
    {   
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (isset($param['searchText']) && !empty($param['searchText'])) {
                $where['title'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $user = new NewsModel();
            $selectResult = $user->getMp3NewsByWhere($where, $offset, $limit);

            $status = config('user_status');

            foreach($selectResult as $key=>$vo){

                // $selectResult[$key]['status'] = $status[$vo['status']];

                $operate = [
                    '编辑' => url('news/newsEdit', ['id' => $vo['id']]),
                    '删除' => "javascript:newsDel('".$vo['id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);
                
            }

            $return['total'] = $user->getAllMp3News($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    // 改变带mp3新闻的轮播顺序
    public function changeSort()
    {
        // 接收参数
        $param = request()->param();

        $arr['rota_sort'] = $param['value'];
        $upd_res = Db::name('hd_news')->where('id', $param['id'])->update($arr);
        $param['status'] = $upd_res;

        return $param;
    }

    // 改变带mp3新闻的阅读量
    public function changeReadNum()
    {
        // 接收参数
        $param = request()->param();

        $arr['read_num'] = $param['value'];
        $upd_res = Db::name('hd_news')->where('id', $param['id'])->update($arr);
        $param['status'] = $upd_res;

        return $param;
    }

    // 改变带mp3新闻的评论量
    public function changeCommentNum()
    {
        // 接收参数
        $param = request()->param();

        $arr['pl_num'] = $param['value'];
        $upd_res = Db::name('hd_news')->where('id', $param['id'])->update($arr);
        $param['status'] = $upd_res;

        return $param;
    }
}
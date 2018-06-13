<?php

namespace app\admin\controller;

use app\admin\model\TopicModel;

use think\model;

use think\Request;

use QL\QueryList;

use phpQuery;

use think\log;

use think\Db;

use think\File;

use think\Image;

class Topic extends Base

{

	public function index()

	{

	    //判断请求的内容是否为Ajax请求 如果不是ajax请求执行if

	    if(request()->isAjax()){

	        //将得到的输入的值赋给$param

	        $param = input('param.');

	        //获取每页显示的记录数

	        $limit = $param['pageSize'];

	        //计算偏移量

	        $offset = ($param['pageNumber'] - 1) * $limit;

	    

	        //给查询条件赋初值   为空

	        $where = [];

	        //判断若搜索框内有输入值 则执行按搜索条件查询结果

	        if (isset($param['searchText']) && !empty($param['searchText'])) {

	            $where['title'] = ['like', '%' . $param['searchText'] . '%'];

	        }

	        //搜索框没值 则列出所有的分组

	        $topic = new TopicModel();

	        $selectResult = $topic->getArticleByWhere($where, $offset, $limit);

	        //设置配置参数 status

	        $status = config('topic_status');

	        //循环读出所有的分组

	       foreach($selectResult as $key=>$vo){



                //$selectResult[$vo]['status'] = $status[$vo['status']];



                $operate = [

                    '编辑' => url('Topic/textEdit', ['id' => $vo['id']]),

                    '删除' => "javascript:textDel('".$vo['id']."')"                    

                ];



                //每条结果都展示一个操作按钮

                $selectResult[$key]['operate'] = showOperate($operate);

            }

	    

	        $return['total'] = $topic->getAllArticle($where);  //总数据

	        $return['rows'] = $selectResult;

	    

	        return json($return);

	    }

		return $this->fetch();

	}

	

	//导入惠说新闻

	public function hsxwload()

	{

	   

	    $url = Request::instance()->param('url');

	    $tag = \think\Config::get('weixin.tag');

	    //$url = 'http://mp.weixin.qq.com/s?__biz=MzI4OTA4MDExNw==&mid=2653040245&idx=2&sn=5d0931fdfd90ec2a1ed014401c87a5e4&scene=4#wechat_redirect';

	    \think\Loader::import('QueryList', EXTEND_PATH);

	    \think\Loader::import('phpQuery', EXTEND_PATH);

	    set_time_limit(0);

	    $page = $url ;

	    $reg = array(

	        'title' => array("#activity-name","text"),

	        'content' => array('#js_content','html','',function($content){

	            $doc = phpQuery::newDocumentHTML($content);

	            $imgs = pq($doc)->find('img');

	            $rand_num = rand(0,count($imgs)-1);

	            foreach ($imgs as $img) {

	                $src = pq($img)->attr('src');

	                $localSrc = './uploads/article/'.date('Y-m-d').'/';

	                if (! is_dir($localSrc)) {

	                    mkdir($localSrc);

	                }

	                $localSrc .= date('Ymdhis',time()).getRandCode(6).'.jpg';

	                $stream = file_get_contents($src);

	                file_put_contents($localSrc,$stream);

	                $localSrc = str_replace('./', '/', $localSrc);

	                pq($img)->attr('src',$localSrc);

	                pq($img)->attr('style',"width:auto;max-width:100%;");

	            }

	

	            $section = pq($doc)->find('section');

	            //$rand_num = rand(0,count($section)-1);

	            foreach ($section as $styles) {

	                $style = pq($styles)->attr('style');

	                //$style = str_replace("http://mmbiz.qpic.cn", "", $str);

	                $url = stristr ( $style ,  'http://mmbiz.qpic.cn' );

	                if (empty($url)) {

	                    continue;

	                }

	                $url = stristr ( $url ,  ')' , true );

	                $url = $url;

	

	                $localSrc = './uploads/article/'.date('Y-m-d').'/';

	                if (! is_dir($localSrc)) {

	                    mkdir($localSrc);

	                }

	                $localSrc .= date('Ymdhis',time()).getRandCode(6).'.jpg';

	                $stream = file_get_contents($url);

	                file_put_contents($localSrc,$stream);

	                $localSrc = str_replace('./', '/', $localSrc);

	

	                pq($img)->attr('style',"-webkit-border-image: url($localSrc)");

	                pq($img)->attr('style',"background-image: url($localSrc)");

	            }

	            return $doc->htmlOuter();

	        })

	    );

	    

	    $request = Request::instance();

	    $thumb ='';

	    if(request()->isPost()) {

	        $data = $_POST;

	        $file = request()->file('thumb');

	        //判断是否有图片上传

	        if (!empty($file)) {

	            $file_info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'article');

	            if ($file_info) {

	                $thumb = $file_info->getSaveName();

	                $thumb = "/uploads/article/".str_replace('\\', "/", $thumb);

	            }

	        

	

        	    $ql = QueryList::Query($page,$reg);

        	    $data = $ql->getData();

        	   $data[0]['content'] = str_replace("/uploads",'http://wechats.zhonghuilv.net/uploads',$data[0]['content']);

        	    if ($data[0]['title'] && !empty($data[0]['title'] )) {

        	        $info['title'] = $data[0]['title'];

        	        $info['topic_id'] = 1;

        	        $info['thumb'] = 'http://wechats.zhonghuilv.net'.$thumb;

        	        $info['content'] = $data[0]['content'];

        	        $info['member_id'] = 5;

        	        $info['status'] = 1;

        	        /*$str = strip_tags($data[0]['content']);

        	        $desc = str_replace("有 趣  |  有 用  |  与 你 有 关", "", $str);

        	        $desc = str_replace(array("\r\n", "\r", "\n","有 趣  |  有 用  |  与 你 有 关"), "", $str);

        	*/

        	        $info['desc'] = $data[0]['title'];

        	        $bool = Db::name('article_topic')->insert($info);

        	        if ($bool) {

        	            $this->success("导入成功","/admin/topic/index");

        	        }

        	        else{

        	            $this->error("导入失败","/admin/topic/index");

        	        }

        	    }

	

	        }

	    }

	}

	

	

	//导入有声旅行

	public function yslxload()

	{

	

	    $url = Request::instance()->param('url');

	    $tag = \think\Config::get('weixin.tag');

	    //$url = 'http://mp.weixin.qq.com/s?__biz=MzI4OTA4MDExNw==&mid=2653040245&idx=2&sn=5d0931fdfd90ec2a1ed014401c87a5e4&scene=4#wechat_redirect';

	    \think\Loader::import('QueryList', EXTEND_PATH);

	    \think\Loader::import('phpQuery', EXTEND_PATH);

	    set_time_limit(0);

	    $page = $url ;

	    $reg = array(

	        'title' => array("#activity-name","text"),

	        'content' => array('#js_content','html','',function($content){

	            $doc = phpQuery::newDocumentHTML($content);

	            $imgs = pq($doc)->find('img');

	            $rand_num = rand(0,count($imgs)-1);

	            foreach ($imgs as $img) {

	                $src = pq($img)->attr('src');

	                $localSrc = './uploads/article/'.date('Y-m-d').'/';

	                if (! is_dir($localSrc)) {

	                    mkdir($localSrc);

	                }

	                $localSrc .= date('Ymdhis',time()).getRandCode(6).'.jpg';

	                $stream = file_get_contents($src);

	                file_put_contents($localSrc,$stream);

	                $localSrc = str_replace('./', '/', $localSrc);

	                pq($img)->attr('src',$localSrc);

	                pq($img)->attr('style',"width:auto;max-width:100%;");

	            }

	

	            $section = pq($doc)->find('section');

	            //$rand_num = rand(0,count($section)-1);

	            foreach ($section as $styles) {

	                $style = pq($styles)->attr('style');

	                //$style = str_replace("http://mmbiz.qpic.cn", "", $str);

	                $url = stristr ( $style ,  'http://mmbiz.qpic.cn' );

	                if (empty($url)) {

	                    continue;

	                }

	                $url = stristr ( $url ,  ')' , true );

	                $url = $url;

	

	                $localSrc = './uploads/article/'.date('Y-m-d').'/';

	                if (! is_dir($localSrc)) {

	                    mkdir($localSrc);

	                }

	                $localSrc .= date('Ymdhis',time()).getRandCode(6).'.jpg';

	                $stream = file_get_contents($url);

	                file_put_contents($localSrc,$stream);

	                $localSrc = str_replace('./', '/', $localSrc);

	

	                pq($img)->attr('style',"-webkit-border-image: url($localSrc)");

	                pq($img)->attr('style',"background-image: url($localSrc)");

	            }

	            return $doc->htmlOuter();

	        })

	    );

	    

	    $request = Request::instance();

	    $thumb ='';

	    if(request()->isPost()) {

	        $data = $_POST;

	        $file = request()->file('thumb');

	        //判断是否有图片上传

	        if (!empty($file)) {

	            $file_info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'article');

	            if ($file_info) {

	                $thumb = $file_info->getSaveName();

	                $thumb = "/uploads/article/".str_replace('\\', "/", $thumb);

	            }

	        

	

        	    $ql = QueryList::Query($page,$reg);

        	    $data = $ql->getData();

        	   $data[0]['content'] = str_replace("/uploads",'http://wechats.zhonghuilv.net/uploads',$data[0]['content']);

        	    if ($data[0]['title'] && !empty($data[0]['title'] )) {

        	        $info['title'] = $data[0]['title'];

        	        $info['topic_id'] = 2;

        	        $info['thumb'] = 'http://wechats.zhonghuilv.net'.$thumb;

        	        $info['content'] = $data[0]['content'];

        	        $info['member_id'] = 5;

        	        $info['status'] = 1;



        	        $info['desc'] =$data[0]['title'];

        	        $bool = Db::name('article_topic')->insert($info);

        	        if ($bool) {

        	            $this->success("导入成功","/admin/topic/index");

        	        }

        	        else{

        	            $this->error("导入失败","/admin/topic/index");

        	        }

        	    }

	

	        }

	    }

	}

	

	

	//导入有料旅行

	public function yllxload()

	{

	

	    $url = Request::instance()->param('url');

	    $tag = \think\Config::get('weixin.tag');

	    //$url = 'http://mp.weixin.qq.com/s?__biz=MzI4OTA4MDExNw==&mid=2653040245&idx=2&sn=5d0931fdfd90ec2a1ed014401c87a5e4&scene=4#wechat_redirect';

	    \think\Loader::import('QueryList', EXTEND_PATH);

	    \think\Loader::import('phpQuery', EXTEND_PATH);

	    set_time_limit(0);

	    $page = $url ;

	    $reg = array(

	        'title' => array("#activity-name","text"),

	        'content' => array('#js_content','html','',function($content){

	            $doc = phpQuery::newDocumentHTML($content);

	            $imgs = pq($doc)->find('img');

	            $rand_num = rand(0,count($imgs)-1);

	            foreach ($imgs as $img) {

	                $src = pq($img)->attr('src');

	                $localSrc = './uploads/article/'.date('Y-m-d').'/';

	                if (! is_dir($localSrc)) {

	                    mkdir($localSrc);

	                }

	                $localSrc .= date('Ymdhis',time()).getRandCode(6).'.jpg';

	                $stream = file_get_contents($src);

	                file_put_contents($localSrc,$stream);

	                $localSrc = str_replace('./', '/', $localSrc);

	                pq($img)->attr('src',$localSrc);

	                pq($img)->attr('style',"width:auto;max-width:100%;");

	            }

	

	            $section = pq($doc)->find('section');

	            //$rand_num = rand(0,count($section)-1);

	            foreach ($section as $styles) {

	                $style = pq($styles)->attr('style');

	                //$style = str_replace("http://mmbiz.qpic.cn", "", $str);

	                $url = stristr ( $style ,  'http://mmbiz.qpic.cn' );

	                if (empty($url)) {

	                    continue;

	                }

	                $url = stristr ( $url ,  ')' , true );

	                $url = $url;

	

	                $localSrc = './uploads/article/'.date('Y-m-d').'/';

	                if (! is_dir($localSrc)) {

	                    mkdir($localSrc);

	                }

	                $localSrc .= date('Ymdhis',time()).getRandCode(6).'.jpg';

	                $stream = file_get_contents($url);

	                file_put_contents($localSrc,$stream);

	                $localSrc = str_replace('./', '/', $localSrc);

	

	                pq($img)->attr('style',"-webkit-border-image: url($localSrc)");

	                pq($img)->attr('style',"background-image: url($localSrc)");

	            }

	            return $doc->htmlOuter();

	        })

	    );

	    

	    $request = Request::instance();

	    $thumb ='';

	    if(request()->isPost()) {

	        $data = $_POST;

	        $file = request()->file('thumb');



	        //判断是否有图片上传

	        if (!empty($file)) {

	            $file_info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'article');

	            if ($file_info) {

	                $thumb = $file_info->getSaveName();

	                $thumb = "/uploads/article/".str_replace('\\', "/", $thumb);

	            }

	        

	

        	    $ql = QueryList::Query($page,$reg);

        	    $data = $ql->getData();

        	   $data[0]['content'] = str_replace("/uploads",'http://wechats.zhonghuilv.net/uploads',$data[0]['content']);

        	    if ($data[0]['title'] && !empty($data[0]['title'] )) {

        	        $info['title'] = $data[0]['title'];

        	        $info['topic_id'] = 3;

        	        $info['thumb'] = 'http://wechats.zhonghuilv.net'.$thumb;

        	        $info['content'] = $data[0]['content'];

        	        $info['member_id'] = 5;

        	        $info['status'] = 1;

        	        /*$str = strip_tags($data[0]['content']);

        	        $desc = str_replace("有 趣  |  有 用  |  与 你 有 关", "", $str);

        	        $desc = str_replace(array("\r\n", "\r", "\n","有 趣  |  有 用  |  与 你 有 关"), "", $str);

        	

        	        $info['desc'] = msubstr($desc,50,0,"utf-8", false);*/

                    $info['desc'] = $data[0]['title'];

        	        $bool = Db::name('article_topic')->insert($info);

        	        if ($bool) {

        	            $this->success("导入成功","/admin/topic/index");

        	        }

        	        else{

        	            $this->error("导入失败","/admin/topic/index");

        	        }

        	    }

	

	        }

	    }

	}

	

	

	//导入不惠你看

	public function bhnkload()

	{

	

	    $url = Request::instance()->param('url');

	    $tag = \think\Config::get('weixin.tag');

	    //$url = 'http://mp.weixin.qq.com/s?__biz=MzI4OTA4MDExNw==&mid=2653040245&idx=2&sn=5d0931fdfd90ec2a1ed014401c87a5e4&scene=4#wechat_redirect';

	    \think\Loader::import('QueryList', EXTEND_PATH);

	    \think\Loader::import('phpQuery', EXTEND_PATH);

	    set_time_limit(0);

	    $page = $url ;

	    $reg = array(

	        'title' => array("#activity-name","text"),

	        'content' => array('#js_content','html','',function($content){

	            $doc = phpQuery::newDocumentHTML($content);

	           $imgs = pq($doc)->find('img');

	            $rand_num = rand(0,count($imgs)-1);

	            foreach ($imgs as $img) {

	                $src = pq($img)->attr('src');

	                $localSrc = './uploads/article/'.date('Y-m-d').'/';

	                if (! is_dir($localSrc)) {

	                    mkdir($localSrc);

	                }

	                $localSrc .= date('Ymdhis',time()).getRandCode(6).'.jpg';

	                $stream = file_get_contents($src);

	                file_put_contents($localSrc,$stream);

	                $localSrc = str_replace('./', '/', $localSrc);

	                pq($img)->attr('src',$localSrc);

	                pq($img)->attr('style',"width:auto;max-width:100%;");

	            }

	

	            $section = pq($doc)->find('section');

	            //$rand_num = rand(0,count($section)-1);

	            foreach ($section as $styles) {

	                $style = pq($styles)->attr('style');

	                //$style = str_replace("http://mmbiz.qpic.cn", "", $str);

	                $url = stristr ( $style ,  'http://mmbiz.qpic.cn' );

	                if (empty($url)) {

	                    continue;

	                }

	                $url = stristr ( $url ,  ')' , true );

	                $url = $url;

	

	                $localSrc = './uploads/article/'.date('Y-m-d').'/';

	                if (! is_dir($localSrc)) {

	                    mkdir($localSrc);

	                }

	                $localSrc .= date('Ymdhis',time()).getRandCode(6).'.jpg';

	                $stream = file_get_contents($url);

	                file_put_contents($localSrc,$stream);

	                $localSrc = str_replace('./', '/', $localSrc);

	

	                pq($img)->attr('style',"-webkit-border-image: url($localSrc)");

	                pq($img)->attr('style',"background-image: url($localSrc)");

	            }

	            return $doc->htmlOuter();

	        })

	    );

	    

	    $request = Request::instance();

	    $thumb ='';

	    if(request()->isPost()) {

	        $data = $_POST;

	        $file = request()->file('thumb');



	        //判断是否有图片上传

	        if (!empty($file)) {

	            $file_info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'article');

	            if ($file_info) {

	                $thumb = $file_info->getSaveName();

	                $thumb = "/uploads/article/".str_replace('\\', "/", $thumb);

	            }

	        

	

        	    $ql = QueryList::Query($page,$reg);

        	    $data = $ql->getData();

        	   $data[0]['content'] = str_replace("/uploads",'http://wechats.zhonghuilv.net/uploads',$data[0]['content']);

        	    if ($data[0]['title'] && !empty($data[0]['title'] )) {

        	        $info['title'] = $data[0]['title'];

        	        $info['topic_id'] = 4;

        	        $info['thumb'] = 'http://wechats.zhonghuilv.net'.$thumb;

        	        $info['content'] = $data[0]['content'];

        	        $info['member_id'] = 5;

        	        $info['status'] = 0;

        	        /*$str = strip_tags($data[0]['content']);

        	        $desc = str_replace("有 趣  |  有 用  |  与 你 有 关", "", $str);

        	        $desc = str_replace(array("\r\n", "\r", "\n","有 趣  |  有 用  |  与 你 有 关"), "", $str);

        	

        	        $info['desc'] = msubstr($desc,50,0,"utf-8", false);*/

                    $info['desc'] = $data[0]['title'];

        	        $bool = Db::name('article_topic')->insert($info);

        	        if ($bool) {

        	            $this->success("导入成功","/admin/topic/index");

        	        }

        	        else{

        	            $this->error("导入失败","/admin/topic/index");

        	        }

        	    }

	

	        }

	    }

	}



	public function textedit()
    {
        $id = Request::instance()->param("id");
        $topic = new TopicModel();;
        if(request()->isPost()) {
            $info_post = $_POST;
            $files = request()->file('img');
           	
           	if (null == $files)
           	{
           		Db::name('hd_article_topic')->where('id', $info_post['id'])->update($info_post);

           		$this->success('修改成功', 'index');
           	}

                foreach ($files as $key=>$value)
                {
                    if(!empty($files[0]) && empty($files[1]) )
                    {
                        $info4= $files[0]->move(ROOT_PATH . 'public' . DS . 'uploads'. DS . 'news');
                        if(!empty($info4)){
                            $data_b['thumb'] = $info4->getSaveName();
                            $data_b['thumb'] = 'http://wechats.zhonghuilv.net'."/uploads/news/".str_replace('\\', "/", $data_b['thumb']);
                        }
                        $param['title'] = $info_post['title'];
                        $param['id'] = $info_post['id'];
                        $param['topic_id'] = $info_post['topic_id'];
                        $param['thumb'] = $data_b['thumb'];
                        $param['audio_path'] = '';
                        $flag = $topic->textEdit($param);
                        if ($flag) {
                            return $this->success('修改成功',"/admin/topic/index");
                            die;
                        }
                        else{
                            return $this->error('修改失败');
                            die;
                        }
                    }elseif(!empty($files[0]) && !empty($files[1]))

                    {

                        $info1= $files[0]->move(ROOT_PATH . 'public' . DS . 'uploads'. DS . 'news');



                        if (!empty($info1)) {

                            $data_a['thumb'] = $info1->getSaveName();

                            $data_a['thumb'] = 'http://wechats.zhonghuilv.net'."/uploads/news/".str_replace('\\', "/", $data_a['thumb']);

                        }

                        $info2= $files[1]->move(ROOT_PATH . 'public' . DS . 'uploads'. DS . 'news');

                        if(!empty($info2)){

                            $data_b['audio_path'] = $info2->getSaveName();

                            $data_b['audio_path'] = 'http://wechats.zhonghuilv.net'."/uploads/news/".str_replace('\\', "/", $data_b['audio_path']);

                        }

                        $param['title'] = $info_post['title'];

                        $param['id'] = $info_post['id'];

                        $param['topic_id'] = $info_post['topic_id'];

                        $param['thumb'] = $data_a['thumb'];

                        $param['audio_path'] = $data_b['audio_path'];



                        $flag = $topic->textEdit($param);

                        if ($flag) {

                            return $this->success('修改成功',"/admin/topic/index");

                            die;

                        }

                        else{

                            return $this->error('修改失败');

                            die;

                        }

                    }

                   else

                   {

                       return $this->error('资料填写不全!',"/admin/topic/index");

                       die;

                   }





                }

            }

        $onenews = Db::name("hd_article_topic")->where("id",$id)->find();

        $menu = Db::name("hd_topic_cate")->select();

        $this->assign([

            'onenews' =>$onenews,

            'menu' => $menu

        ]);

        return $this->fetch();

    }



    public function newsDel($id)

    {

       // $id = Request::instance()->param("id");

        $new = new TopicModel();



        $res = $new->delArticle($id);

        return json(['code' => $res['code'], 'data' => $res['data'], 'msg' => $res['msg']]);

    }


    // 修改专题新闻的阅读量
    public function changeReadNum()
    {
    	$param = request()->param();

    	$arr['read_num'] = $param['value'];
    	$upd_res = Db::name('hd_article_topic')->where('id', $param['id'])->update($arr);
    	$param['status'] = $upd_res;

    	return ['id' => $param['id'], 'value' => $param['value'], 'status' => 1];
    }

    // 修改专题新闻的点赞量
    public function changeFavorNum()
    {
    	$param = request()->param();

    	$arr['like_num'] = $param['value'];
    	$upd_res = Db::name('hd_article_topic')->where('id', $param['id'])->update($arr);
    	$param['status'] = $upd_res;

    	return $param;
    }	
}
<?php



// 评论管理控制器 

namespace app\admin\controller;



use think\Controller;

use app\admin\model\CommentModel;

use think\Db;

use think\log;



class Comment extends Controller

{	





	// 咕咕评论列表

	public function guList()

	{

        if(request()->isAjax()){



            $param = input('param.');



            $limit = $param['pageSize'];

            $offset = ($param['pageNumber'] - 1) * $limit;



            $where = [];

            if (isset($param['searchText']) && !empty($param['searchText'])) {

                $where['info'] = ['like', '%' . $param['searchText'] . '%'];

            }

            $comment = new CommentModel();

            $selectResult = $comment->getGuCommentList($where, $offset, $limit);





            foreach($selectResult as $key=>$vo){

                $operate = [

                   /* '删除' => "javascript:guCommentDel('".$vo['id']."')",*/

                    '封禁' => "javascript:guCommentBan('".$vo['id']."')"

                ];



                $selectResult[$key]['operate'] = showOperate($operate);

            }



            $return['total'] = $comment->getGuCommentNum($where);  //总数据

            $return['rows'] = $selectResult;

            // Log::write($selectResult);

            return json($return);

        }



        return $this->fetch();

	}



	// 修改咕咕评论的点赞数量

	public function changeGuCommentFavorNum()

	{

		$param = request()->param();



		$arr['favor_num'] = $param['value'];



		$upd_res = Db::name('gugu_comment')->where('id', $param['id'])->update($arr);

		$param['status'] = $upd_res;



		return $param;

	}



	// 删除咕咕评论

	public function guCommentDel()

	{

		$id = input('id');



		$del_res = Db::name('gugu_comment')->where('id', $id)->delete();



		$arr['code'] = $del_res ? 1 : 0;

		return $arr;

	}



	// 封禁咕咕评论

	public function guCommentBan()

	{

		$id = input('id');



		$param['ban'] = 1;

		$param['ban_time'] = date('Y-m-d h:i:s');
		 //给评论数减去1
      		  $gugu_id=db::name('gugu_comment')->where('id', $id)->value('gugu_id');

      		  $comment_num=Db::name('gugu_article')->where('id',$gugu_id)->setDec('comment_num');


		$upd_res = Db::name('gugu_comment')->where('id', $id)->update($param);



		$arr['code'] = $upd_res ? 1 : 0;

		return $arr;

	}



	// 咕咕评论封禁列表

	public function guBanList()

	{	

        if(request()->isAjax()){



            $param = input('param.');



            $limit = $param['pageSize'];

            $offset = ($param['pageNumber'] - 1) * $limit;



            $where = [];

            if (isset($param['searchText']) && !empty($param['searchText'])) {

                $where['info'] = ['like', '%' . $param['searchText'] . '%'];

            }

            $comment = new CommentModel();

            $selectResult = $comment->getGuBanList($where, $offset, $limit);





            foreach($selectResult as $key=>$vo){
  

                $operate = [

                   /* '删除' => "javascript:guCommentDel('".$vo['id']."')",*/

                    '解封' => "javascript:guCommentCancelBan('".$vo['id']."')"

                ];



                $selectResult[$key]['operate'] = showOperate($operate);

            }



            $return['total'] = $comment->getGuBanNum($where);  //总数据

            $return['rows'] = $selectResult;


            
            return json($return);

        }



        return $this->fetch();

	}



	// 解封咕咕评论

	public function guCommentCancelBan()

	{

		$id = input('id');



		$param['ban'] = 0;

		$param['cancel_ban_time'] = date('Y-m-d h:i:s');

		//秀秀评论数量加1

       		 $gugu_id=db::name('gugu_comment')->where('id', $id)->value('gugu_id');

        		$comment_num=Db::name('gugu_article')->where('id',$gugu_id)->setInc('comment_num');


		$upd_res = Db::name('gugu_comment')->where('id', $id)->update($param);



		$arr['code'] = $upd_res ? 1 : 0;

		return $arr;

	}

// 商品评论列表

	public function goods()

	{

        if(request()->isAjax()){



            $param = input('param.');



            $limit = $param['pageSize'];

            $offset = ($param['pageNumber'] - 1) * $limit;



            $where = [];

            if (isset($param['searchText']) && !empty($param['searchText'])) {

                $where['info'] = ['like', '%' . $param['searchText'] . '%'];

            }

            $comment = new CommentModel();

            $selectResult = $comment->getGoodsCommentList($where, $offset, $limit);





            foreach($selectResult as $key=>$vo){

                $operate = [

                   /* '删除' => "javascript:guCommentDel('".$vo['id']."')",*/

                    '封禁' => "javascript:goodsCommentBan('".$vo['id']."')"

                ];



                $selectResult[$key]['operate'] = showOperate($operate);

            }



            $return['total'] = $comment->getGuCommentNum($where);  //总数据

            $return['rows'] = $selectResult;

            // Log::write($selectResult);

            return json($return);

        }



        return $this->fetch();

	}



// 封禁商品评论

	public function goodsCommentBan()

	{

		$id = input('id');



		$param['status'] = 1;

		$param['up_time'] = date('Y-m-d h:i:s');



		$upd_res = Db::name('shop_spot_comment')->where('id', $id)->update($param);



		$arr['code'] = $upd_res ? 1 : 0;

		return $arr;

	}	


  	public function goodsBanList()

	{

        if(request()->isAjax()){



            $param = input('param.');



            $limit = $param['pageSize'];

            $offset = ($param['pageNumber'] - 1) * $limit;



            $where = [];

            if (isset($param['searchText']) && !empty($param['searchText'])) {

                $where['info'] = ['like', '%' . $param['searchText'] . '%'];

            }

            $comment = new CommentModel();

            $selectResult = $comment->GoodsBanList($where, $offset, $limit);





            foreach($selectResult as $key=>$vo){

                $operate = [

                   /* '删除' => "javascript:guCommentDel('".$vo['id']."')",*/

                    '解封' => "javascript:goodsCancelBan('".$vo['id']."')"

                ];



                $selectResult[$key]['operate'] = showOperate($operate);

            }



            $return['total'] = db::name('shop_spot_comment')->where('status',1)->count();  //总数据

            $return['rows'] = $selectResult;

            // Log::write($selectResult);

            return json($return);

        }

        return $this->fetch();

	}  


	public function goodsCancelBan()
	{
       
		$id = input('id');



		$param['status'] = 0;

		$param['up_time'] = date('Y-m-d h:i:s');



		$upd_res = Db::name('shop_spot_comment')->where('id', $id)->update($param);



		$arr['code'] = $upd_res ? 1 : 0;

		return $arr;

	}
}



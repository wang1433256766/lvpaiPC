<?php    
namespace app\admin\controller ;
use think\Controller;
use app\admin\model\GoodsModel;
use think\Request;
	class Score extends Controller
	{
			// 商品列表页面
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
            $goods = new GoodsModel();
            $selectResult = $goods->getGoodsByWhere($where, $offset, $limit);
         

            $status = config('score_status');

            foreach($selectResult as $key=>$vo){
                $selectResult[$key]['status'] = $status[$vo['status']];
                $operate = [
                    '编辑' => url('Score/goodsEdit', ['id' => $vo['id']]),
                    '删除' => "javascript:goodsDel('".$vo['id']."')"
                ];

                $selectResult[$key]['operate'] = showOperate($operate);

            }
 
            $return['total'] = $goods->getAllGoods($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

		return $this->fetch();
	}

    /**
     * 添加商品
     * @AuthorHTL   xiang
     * @DateTime  2017-03-05
     * @return    bool
     */
    public function scoreAdd(){
        $request =  Request::instance();
        if($request->isPost()){
            $data = $_POST;
            $file = request()->file('image');
            if(!empty($file)){
                $file_info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'score');
                if($file_info){
                    $data['image'] = $file_info->getSaveName();
                    $data['image'] = "/uploads/score/".str_replace('\\', "/", $data['image']); 
                }
            }
            $mt = new GoodsModel();
            $flag = $mt->insertApp($data);
            if ($flag) {
                return $this->success("添加成功","/admin/score/index.html");
                die;
            }
            else{
                return $this->error('添加失败');
                die;
            }
        }
        $this->assign([          
            'status' => config('alt_status')
        ]); 
        $this->assign('type', ['实物礼品', '景区门票', '优惠券']);
        return $this->fetch();
    }

    /**
     * 修改商品
     * @AuthorHTL   xiang
     * @DateTime  2017-03-05
     * @return    bool
     */
    public function goodsEdit(){            
        if(request()->isPost()){                   
            $data = $_POST;
            $id = $data['id'];
            $file = request()->file('image');
            $Goods = new GoodsModel;            
            //判断是否有图片上传
            if (!empty($file)) {
                //更新图片               
                $res = $Goods->delImg($id);
                $file_info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'score');
                if ($file_info) {
                    $data['image'] = $file_info->getSaveName();
                    $data['image'] = "/uploads/score/".str_replace('\\', "/", $data['image']);
                }
            }                       
            $flag = $Goods->editGoods($data);
            if ($flag) {
                return $this->success("修改成功","/admin/score/index.html");
                die;
            }
            else{
                return $this->error('修改失败');
                die;
            }
        }
        $a = new GoodsModel;
        $id = input('param.id');
        $Goods = $a->getOneGoods($id);
        $this->assign([
            'Goods' => $a->getOneGoods($id),
            'status' => config('alt_status'),
        ]);        
        return $this->fetch();
    }

    // 删除商品
    public function goodsDel()
    {
        $id = request()->param('id');

        $goods = new GoodsModel();
        $flag = $goods->goodsDel($id);
        return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
    }

    //短信通知
    public function send()
    {
        $orderid = Session::get("orderid");
        //短信接口地址
        $target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
        //获取手机号
        $member_id = Db::name("score_order")->where("id",$orderid)->value("member_id");
        $mobile = Db::name("score_address")->where("member_id",$member_id)->value("phone");


        //获取快递单号
        $tracking_number = Request::instance()->param("tracking_number");
        //获取订单号
        $order = Db::name("score_order")->where("id",$orderid)->value("order_sn");
        //获取用户姓名
        $username = Db::name("score_address")->where("id",$orderid)->value("username");
        $user ='cf_zhonghuilv';
        $password ='eb2a1a963b116ae15e7cb2bf41382bf4';
        $post_data = "account=".$user."&password=".$password."&mobile=".$mobile."&content=".rawurlencode("亲爱的【".$username."】先生/女士,您的订单【".$order."】小拓已经打包好正在飞速向您发射，快递单号为【".$tracking_number."】,请您随时关注！");
        //用户名是登录ihuyi.com账号名（例如：cf_demo123）
        //查看密码请登录用户中心->验证码、通知短信->帐户及签名设置->APIKEY
        $gets =  xml_to_array(Post($post_data, $target));
        Db::name("score_order")->where("id",$orderid)->setField("status",0);
        if($gets)
        {
            $res = array("status"=>true,"info"=>"短信通知成功!");
        }
        else
        {
            $res = array("status"=>false,"info"=>"短信通知失败，请确认后操作!");
        }
        return json($res);

        /*if($gets['SubmitResult']['code']==2){
            $_SESSION['mobile'] = $mobile;
            $_SESSION['mobile_code'] = $mobile_code;
        }
        echo $gets['SubmitResult']['msg'];*/
    }

    public function sendid()
    {
        $orderid = Request::instance()->param("id");
        Session::set("orderid",$orderid);
    }

    public function OrderDel()
    {
        $orderid = Request::instance()->param("id");
        if($orderid)
        {
            $bool = Db::name("score_order")->where("id",$orderid)->setField("status",6);
            if($bool)
            {
                $res = array("status"=>true,"info"=>"关闭交易成功!");
            }
            else
            {
                $res = array("status"=>true,"info"=>"关闭交易失败!");
            }
            return json($res);
        }
    }

	}
<?php
namespace app\admin\controller;
use app\admin\model\Node;
use think\Request;
use think\Db;
use think\Controller;
use com\Uploader;

class Base extends Controller
{
    public $ueditor_config;
    public function _initialize()
    {
        $username = session('username');
        if(empty($username)){

            $this->redirect('login/index');
        }

        //检测权限
        $control = lcfirst( request()->controller() );
        $action = lcfirst( request()->action() );

        //跳过登录系列的检测以及主页权限
        if(!in_array($control, ['login', 'index'])){

            /*if(!in_array($control . '/' . $action, session('action'))){
                $this->error('没有权限');
            }*/
        }

        //获取权限菜单
        $node = new Node();
        //dump($node->getNodeInfo(1));
        $this->assign([
            'username' => session('username'),
            'menu' => $node->getMenu(session('rule')),
            'rolename' => session('role')
        ]);

    }
    public function ajax()
    {
        $id = Request::instance()->param('id');
        $act = Request::instance()->param('act');
        $tab = Request::instance()->param('tab');
    
        $res = array(
            'status' => false,
            'info' => '操作失败',
        );
        if ($id > 0) {
            switch ($act) {
                case 'edit':
                    $data = \think\Db::name($tab)->find($id);
                    break;
                case 'del':
                    $data = \think\Db::name($tab)->delete($id);
                    break;
                default:
                    $data = \think\Db::name($tab)->find($id);
                    break;
            }
            if ($data) {
                $res['status'] = true;
                $res['info'] = $data;
            }
        }
        echo json_encode($res);
    }
     public function ajax1()
    {
            $id = Request::instance()->param('id');
            $act = Request::instance()->param('act');
            $tab = Request::instance()->param('tab');
            $value = Request::instance()->param('value');
            $res = array(
                    'status' => false,
                    'info' => '操作失败',
                    );
            
            if ($id > 0) {
                    switch ($act) {
                            case 'edit':
                            $data = Db::name($tab)->find($id);
                            break;
                            case 'del':
                            $data = Db::name($tab)->delete($id);
                            break;  
                            case 'sort':
                            $data = Db::name($tab)->where('id',$id)->setField('sort',$value);
                            break;
                            case 'sale_num':
                            $data = Db::name($tab)->where('id',$id)->setField('sale_num',$value);
                            break;
                            case 'new':
                            $new = $value ? 0 :1;
                            $data = Db::name($tab)->where('id',$id)->setField('new',$new);
                            break;                                
                            case 'base':
                            $base = $value ? 0 :1;
                            $data = Db::name($tab)->where('id',$id)->setField('base',$base);
                            break;
                            case 'hot':
                            $hot = $value ? 0 :1;
                            $data = Db::name($tab)->where('id',$id)->setField('hot',$hot);
                            break;    
                            case 'cheap':
                            $cheap = $value ? 0 :1;
                            $data = Db::name($tab)->where('id',$id)->setField('cheap',$cheap);
                            break; 
                            case 'sale':
                            $sale = $value ? 0 :1;
                            $data = Db::name($tab)->where('id',$id)->setField('sale',$sale);
                            break;   
                             case 'project':
                            $project = $value ? 0 :1;
                            $data = Db::name($tab)->where('id',$id)->setField('project',$project);
                            break; 
                            case 'self':
                            $self = $value ? 0 :1;
                            $data = Db::name($tab)->where('id',$id)->setField('self',$self);
                            break;  
                            case 'today':
                            $today = $value ? 0 :1;
                            $data = Db::name($tab)->where('id',$id)->setField('today',$today);
                            break; 
                            case 'status':
                            $status = $value ? 0 :1;
                            $data = Db::name($tab)->where('id',$id)->setField('status',$status);
                            break;   
                            case 'check_out':
                            $check_out = $value ? 0 :1;
                            $data = Db::name($tab)->where('id',$id)->setField('check_out',$check_out);
                            break;   
                            default:
                            $data = Db::name($tab)->find($id);
                            break;
                    }  
                    if ($data) {
                            $res['status'] = true;  
                            $res['info'] = $data;   
                    }
            }
            echo json_encode($res);
    }

    public function ueditor()
    {

        $this->ueditor_config = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents("./static/admin/js/ueditor/config.json")), true);
        $action = $_GET['action'];

        switch ($action) {
            case 'config':
                $result =  json_encode($this->ueditor_config);
                break;
            /* 上传图片 */
            case 'uploadimage':
                $result = $this->upload();
                break;
            /* 上传涂鸦 */
            case 'uploadscrawl':
                $result = $this->upload();
                break;
            /* 上传视频 */
            case 'uploadvideo':
                $result = $this->upload();
                break;
            /* 上传文件 */
            case 'uploadfile':
                $result = $this->upload();
                break;
            /* 列出图片 */
            case 'listimage':
                $result = $this->lists();
                break;
            /* 列出文件 */
            case 'listfile':
                $result = $this->lists();
                break;
            /* 抓取远程文件 */
            case 'catchimage':
                $result = $this->crawler();
                break;

            default:
                $result = json_encode(array(
                    'state'=> '请求地址出错'
                ));
                break;
        }

        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode(array(
                    'state'=> 'callback参数不合法'
                ));
            }
        } else {
            echo $result;
        }
    }
    public function upload()
    {
        \think\Loader::import('Uploader', EXTEND_PATH);
        /* 上传配置 */
        $base64 = "upload";
        switch (htmlspecialchars($_GET['action'])) {
            case 'uploadimage':
                $config = array(
                    "pathFormat" => $this->ueditor_config['imagePathFormat'],
                    "maxSize" => $this->ueditor_config['imageMaxSize'],
                    "allowFiles" => $this->ueditor_config['imageAllowFiles']
                );
                $fieldName =$this->ueditor_config['imageFieldName'];
                break;
            case 'uploadscrawl':
                $config = array(
                    "pathFormat" => $this->ueditor_config['scrawlPathFormat'],
                    "maxSize" => $this->ueditor_config['scrawlMaxSize'],
                    "allowFiles" => $this->ueditor_config['scrawlAllowFiles'],
                    "oriName" => "scrawl.png"
                );
                $fieldName = $this->ueditor_config['scrawlFieldName'];
                $base64 = "base64";
                break;
            case 'uploadvideo':
                $config = array(
                    "pathFormat" => $this->ueditor_config['videoPathFormat'],
                    "maxSize" => $this->ueditor_config['videoMaxSize'],
                    "allowFiles" => $this->ueditor_config['videoAllowFiles']
                );
                $fieldName = $this->ueditor_config['videoFieldName'];
                break;
            case 'uploadfile':
            default:
                $config = array(
                    "pathFormat" => $this->ueditor_config['filePathFormat'],
                    "maxSize" => $this->ueditor_config['fileMaxSize'],
                    "allowFiles" => $this->ueditor_config['fileAllowFiles']
                );
                $fieldName = $this->ueditor_config['fileFieldName'];
                break;
        }

        /* 生成上传实例对象并完成上传 */
        $up = new Uploader($fieldName, $config, $base64);

        /**
         * 得到上传文件所对应的各个参数,数组结构
         * array(
         *     "state" => "",          //上传状态，上传成功时必须返回"SUCCESS"
         *     "url" => "",            //返回的地址
         *     "title" => "",          //新文件名
         *     "original" => "",       //原始文件名
         *     "type" => ""            //文件类型
         *     "size" => "",           //文件大小
         * )
         */

        /* 返回数据 */
        return json_encode($up->getFileInfo());
    }

    public function lists()
    {
        /* 判断类型 */
        switch ($_GET['action']) {
            /* 列出文件 */
            case 'listfile':
                $allowFiles = $this->ueditor_config['fileManagerAllowFiles'];
                $listSize = $this->ueditor_config['fileManagerListSize'];
                $path = $this->ueditor_config['fileManagerListPath'];
                break;
            /* 列出图片 */
            case 'listimage':
            default:
                $allowFiles = $this->ueditor_config['imageManagerAllowFiles'];
                $listSize = $this->ueditor_config['imageManagerListSize'];
                $path = $this->ueditor_config['imageManagerListPath'];
        }
        $allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);

        /* 获取参数 */
        $size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
        $start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
        $end = $start + $size;

        /* 获取文件列表 */
        $path = $_SERVER['DOCUMENT_ROOT'] . (substr($path, 0, 1) == "/" ? "":"/") . $path;
        $files = getfiles($path, $allowFiles);
        if (!count($files)) {
            return json_encode(array(
                "state" => "no match file",
                "list" => array(),
                "start" => $start,
                "total" => count($files)
            ));
        }

        /* 获取指定范围的列表 */
        $len = count($files);
        for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
            $list[] = $files[$i];
        }
        //倒序
        //for ($i = $end, $list = array(); $i < $len && $i < $end; $i++){
        //    $list[] = $files[$i];
        //}

        /* 返回数据 */
        $result = json_encode(array(
            "state" => "SUCCESS",
            "list" => $list,
            "start" => $start,
            "total" => count($files)
        ));

        return $result;
    }

    public function crawler()
    {
        \think\Loader::import('Uploader', EXTEND_PATH);
        /* 上传配置 */
        $config = array(
            "pathFormat" => $this->ueditor_config['catcherPathFormat'],
            "maxSize" => $this->ueditor_config['catcherMaxSize'],
            "allowFiles" => $this->ueditor_config['catcherAllowFiles'],
            "oriName" => "remote.png"
        );
        $fieldName = $this->ueditor_config['catcherFieldName'];

        /* 抓取远程图片 */
        $list = array();
        if (isset($_POST[$fieldName])) {
            $source = $_POST[$fieldName];
        } else {
            $source = $_GET[$fieldName];
        }
        foreach ($source as $imgUrl) {
            $item = new Uploader($imgUrl, $config, "remote");
            $info = $item->getFileInfo();
            array_push($list, array(
                "state" => $info["state"],
                "url" => $info["url"],
                "size" => $info["size"],
                "title" => htmlspecialchars($info["title"]),
                "original" => htmlspecialchars($info["original"]),
                "source" => htmlspecialchars($imgUrl)
            ));
        }

        /* 返回抓取数据 */
        return json_encode(array(
            'state'=> count($list) ? 'SUCCESS':'ERROR',
            'list'=> $list
        ));        
    }

}
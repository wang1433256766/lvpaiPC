<?php
// +----------------------------------------------------------------------
// | Zhl
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Belasu <belasu@foxmail.com>
namespace app\api\controller;
use think\Controller;
use think\Model;
use think\Request;
use think\Db;
use think\Session;
use think\Log;




class Index extends Controller
{
    public function index()
    {
        return json(1);
    }
}




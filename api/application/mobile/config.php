<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$
return [
    
     'default_module'        => 'mobile',
    // 默认控制器名
    'default_controller'    => 'Index',
    // 默认操作名
    'default_action'        => 'index',
     'session'               => [
			    'prefix'         => 'mobile',
			    'type'           => '',
			    'auto_start'     => true,
			     ],
    'cache'  => [
            'path'=> CACHE_PATH.'mobile/',
            'prefix' => 'mobile',
            'expire' => 3600,
        ],
     'log'  => [
         // 日志记录方式，内置 file socket 支持扩展
         'type'  => 'File',
         // 日志保存目录
         'path' => LOG_PATH .'mobile' . DS,  // 日志保存目录
         // 日志记录级别
         'level' => ['log', 'error'],
         //单独记录
         'apart_level'   =>  ['error', 'sql', 'info'],
     ],

    //中惠旅全网运营微信互联配置
    'weixin'    =>[
        'token'    => 'shWz63HxF6QxC3z0',
        'encodingaeskey' => 'DkIwoJ73MyfoZQYmnoa5oEqHhJCa8WkFUVvv4i3J38X',
        'appid' => 'wxe71d7cb038a75be3',
        'appsecret' => '15a06ff355889b6cec8dd9039696355b',
    ],
    'ly'    => [
        'reservecheckWay' => [
            '60501'=>'手机号',
            '60502'=>'身份证号',
            '60504'=>'会员号',
            '60505'=>'手机识别码',
        ],
        'SceneryGrade' => ['A级景区','2A景区','3A景区','4A景区','5A景区'],
        'reserve_type' => ['无','日','周','月','年'],
        'charge_type' => [
            '0'=>'无',
            '22301'=>'单票手续费',
            '22302'=>'总额手续费',
            '22303'=>'总额百分比手续费',
        ],
    ],
     //票务云账号
     'pwy' => [
         'ac' =>'1',
         'pw' => 'yxgs'
     ],

];

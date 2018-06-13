<?php
return [
	'weixin'	=>[
		'token' =>	'shWz63HxF6QxC3z0',
		'encodingaeskey' => 'DkIwoJ73MyfoZQYmnoa5oEqHhJCa8WkFUVvv4i3J38X',
		'appid' => 'wxe71d7cb038a75be3',
		'appsecret' => '15a06ff355889b6cec8dd9039696355b',
		'debug' => true,
	],

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module'        => 'index',
    // 默认控制器名
    'default_controller'    => 'Index',
    // 默认操作名
    'default_action'        => 'index',
    'session'               => [
                'prefix'         => 'index',
                'type'           => '',
                'auto_start'     => true,
                 ],
    'cache'  => [
            'type'   => 'file',
            'path'=> CACHE_PATH.'index/', 
            'prefix' => 'index',
            'expire' => 3600,
        ], 
    'log'                    => [
        // 日志记录方式，支持 file socket trace sae browser
        'type' => 'File',
        // 日志保存目录
        'path' => LOG_PATH .'index' . DS,
        // error和sql日志单独记录
        'apart_level' => ['error','sql'],
        //单个日志文件的大小限制，超过后会自动记录到第二个文件
        'file_size' =>2097152,
    ],
    //模板参数替换
    'view_replace_str'       => array(
        '__CSS__'    => '/static/admin/css',
        '__JS__'     => '/static/admin/js',
        '__IMG__' => '/static/admin/images',
    ),
    
    //管理员状态
    'user_status' => [
        '1' => '正常',
        '2' => '禁止登录'
    ],
    //角色状态
    'role_status' => [
        '1' => '启用',
        '2' => '禁用'
    ],
    //图文回复
    'imgtxt_status' => [
        '1' => '启用',
        '2' => '停用'
    ],
    //应用管理
    'alt_status' => [
        '1' => '启用',
        '2' => '停用'
    ],
    'menu_status' => [
        '1' => '启用',
        '2' => '停用'
    ],
    //文本回复
    'text_status' => [
        '1' => '启用',
        '2' => '禁用'
    ],
    //粉丝管理
    'fans_status' => [
        '1' => '已关注',
        '2' => '已取关'
    ],
    //粉丝性别
    'fans_sex' => [
        '1' => '男',
        '2' => '女',
        '0' => '保密'
    ],
    'menu_type' => [
        'click' => '消息回复',
        'view' => '链接跳转'
    ],
    'upload_path'    => [
        'mtext'  => '/public/uploads/mtext/',
        'app'  => '/public/uploads/app/',
        'album'  => '/public/uploads/album/'
    ],
    'cache'  => [
        'type'   => 'File',
        'path'=> CACHE_PATH .'admin' . DS,
        'prefix' => 'admin',
        'expire' => 3600,
    ],
    'view_replace_str'       => array(
        '__CSS__'    => '/static/admin/css',
        '__JS__'     => '/static/admin/js',
        '__IMG__' => '/static/admin/images',
    ),
];
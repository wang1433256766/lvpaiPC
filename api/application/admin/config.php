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
    //票务云账号
    'pwy' => [
        'ac' =>'1',
        'pw' => 'yxgs'
    ],
'session'   => [
                'prefix'         => 'admin',
                'type'           => '',
                'auto_start'     => true,
            ],
    //上传文件目录设置            
    'upload_path'    => [
                'hotelspot'  => '/upload/hotelspot/',
                'spot'  => '/upload/spot/',
                'hotel' => '/upload/hotel/',
                'desc' => '/upload/desc/',
                'around' => '/upload/around/',
                'product' => '/upload/product/',
                'article' => '/upload/article/',
                'forum' => '/upload/forum/',
                'route' => '/upload/route/',
                'destination' => '/upload/destination/',
                'mobile' => '/upload/mobile/',
                'app' => '/upload/app/',
                'index' => '/upload/index/',
                'market' => '/upload/market/',
                'theme' => '/upload/theme/',
                'score' => '/upload/score/',
                'prize' => '/upload/prize/',
                'mtext'  => '/public/uploads/mtext/',
                'app'  => '/public/uploads/app/',
                'album'  => '/public/uploads/album/',
                'poster'  => '/uploads/poster/',
                'qrcode'  => '/uploads/qrcode/',
            ],
    'cache'  => [
            'type'   => 'File',
            'path'=> CACHE_PATH .'admin' . DS,  
            'prefix' => 'admin',
            'expire' => 3600,
            ], 
    'log'  => [
            // 日志记录方式，内置 file socket 支持扩展
            'type'  => 'File',
            // 日志保存目录
            'path' => LOG_PATH .'admin' . DS,  // 日志保存目录
            // 日志记录级别
            'level' => ['log', 'error'],
            //单独记录
            'apart_level'   =>  ['error', 'sql', 'info'],
            ],    
    'position'  => [
           '导航', '图片广告', '文字链接', 
            ],  
    'member'  => [
           '普通会员', '分销会员'
            ],  
    'order_status'  => [
            '未付款', '已支付', '处理中', '已取消', '已退款', '已核销', '已完成'
            ], 
    'score_type'  => [
            '景区门票',  '优惠券', '实物礼品', '虚拟卡券'
            ],    
    'prize_type'  => [
            '景区门票',  '优惠券', '实物礼品', '积分', '虚拟卡券'
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
    //更多配置参数
    'mk_spot'  => [
           '全部', '石牛寨', '石燕湖', '湄江', 
            ], 
    'bt_spot'  => [
           '全部', '石牛寨', '石燕湖', '湄江', 
            ],
            
    //模板参数替换
    'view_replace_str'       => array(
        '__CSS__'    => '/static/admin/css',
        '__JS__'     => '/static/admin/js',
        '__IMG__' => '/static/admin/images',
    ),

    //管理员状态
    'user_status' => [
        '0' => '禁用',
        '1' => '正常'
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
    //菜单管理
    'menu_status' => [
        '1' => '启用',
        '2' => '停用'
    ],
    
    //菜单管理
    'base' => [
        '0' => '<span onlick="onbase(this)" class="badge badge-info">置顶</span>',
        '1' => '<span onlick="offbase(this)" class="badge badge-danger">取消置顶</span>'
    ],
    'spot_status' => [
        '0' => '<span class="badge badge-info">隐藏</span>',
        '1' => '<span class="badge badge-danger">显示</span>'
    ],
    'member_status' => [
        '0' => '<span class="badge badge-info">隐藏</span>',
        '1' => '<span class="badge badge-danger">显示</span>'
    ],
    'sala_type'=> [
        '1' => '<span class="badge badge-info">渠道</span>',
        '2' => '<span class="badge badge-danger">直客</span>'
    ],
    'type_status' => [
        '0' => '<span class="badge badge-danger">普通</span>',
        '1' => '<span class="badge badge-danger">一级</span>',
        '2' => '<span class="badge badge-info">二级</span>'
    ],
     //文本回复
    'text_status' => [
        '1' => '<span class="badge badge-info">启用</span>',
        '2' => '<span class="badge badge-danger">禁用</span>'
    ],
    //广告类别
    'rota_status' => [
        '1' => '<span class="badge badge-info">视频</span>',
        '2' => '<span class="badge badge-danger">新闻</span>'
    ],
    //文章状态
    'article_status' => [
        '0' => '<span class="badge badge-warning">隐藏</span>',
        '1' => '<span class="badge badge-info">启用</span>',
        '2' => '<span class="badge badge-danger">禁用</span>',
        '3' => '<span class="badge badge-danger">草稿</span>'
    ],
    //文章栏目状态
    'arttype_status' =>[
        '0' => '<span class="badge badge-warning">隐藏</span>',
        '1' => '<span class="badge badge-info">正常</span>',
        '2' => '<span class="badge badge-danger">禁用</span>',
    ],
    //会员状态
    'member_status' =>[
        '0' => '<span class="badge badge-warning">冻结</span>',
        '1' => '<span class="badge badge-info">正常</span>'
    ],
    //会员分组状态
    'member_group_status'=>[
        '0' => '<span class="badge badge-warning">冻结</span>',
        '1' => '<span class="badge badge-info">正常</span>'
    ],
     //粉丝管理
    'fans_status' => [
        '0' => '<span class="badge badge-info">已关注</span>',
        '1' => '<span class="badge badge-danger">已取关</span>'
    ],


      //粉丝性别
    'fans_sex' => [
        '1' => '男',
        '2' => '女',
        '0' => '保密'
    ],
      //报名审核状态
    'apply_status' => [
        '1' => '已审',
        '2' => '未审',
        
    ],
       //报名性别
    'apply__sex' => [
        '1' => '男',
        '2' => '女',
         ],
        
         //视频应用状态
    'video_status' => [
        '1' => '<span class="label label-primary">启用</span>',
        '2' => '<span class="label label-warning">禁用</span>'
    ],
    
    //专题新闻
    'topic_status'=>[
        '1' => '<span class="label label-primary">启用</span>',
        '2' => '<span class="label label-warning">禁用</span>'
    ],
    // 乐园订单状态
    'paradise_order_status'=>[
        '0' => '<span class="label label-warning">未支付</span>',
        '1' => '<span class="label label-info">已支付</span>',
    ],

    // 文创订单状态
    'order_status'=>[
        '0' => '<span class="label label-warning">未付款</span>',
        '1' => '<span class="label label-info">已支付</span>',
        '2' => '<span class="label label-info">处理中</span>',
        '3' => '<span class="label label-info">已取消</span>',
        '4' => '<span class="label label-info">已退款</span>',
        '5' => '<span class="label label-info">完成</span>',
    ],

    'menu_type' => [
        'click' => '消息回复',
        'view' => '链接跳转'
    ],         
  
    'cache'  => [
        'type'   => 'File',
        'path'=> CACHE_PATH .'admin' . DS,  
        'prefix' => 'admin',
        'expire' => 3600,
    ],

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log'                    => [
        // 日志记录方式，支持 file socket trace sae browser
        'type' => 'File',
        // 日志保存目录
        'path' => LOG_PATH .'admin' . DS,
        // error和sql日志单独记录
        'apart_level' => ['error','sql'],
        //单个日志文件的大小限制，超过后会自动记录到第二个文件
        'file_size' =>2097152,
    ],
    //微信互联配置
    'wxpay'    =>[
        'token'    => 'shWz63HxF6QxC3z0',
        'encodingaeskey' => 'DkIwoJ73MyfoZQYmnoa5oEqHhJCa8WkFUVvv4i3J38X',
        'appid' => 'wxe71d7cb038a75be3',
        'appsecret' => '15a06ff355889b6cec8dd9039696355b',
        'key' => 'gsk4lkds9sdadsm7m3mhnn23h43jjk23',
        'mchid' => '1497847032'
    ],
    //玻璃桥石牛寨微信互联配置
    'wechat'    =>[
        'token' =>  'shWz63HxF6QxC3z0',
        'encodingaeskey' => 'dqkH51r7V7lXV6iRqLZIEKBi3IRGAXqN26y9654h4rQ',
        'appid' => 'wx2bd1966a59c67f2d',
        'appsecret' => 'bae1626ab8ffdb564a8d0d4f2f007f1b',
        'back' => 'http://wechats.zhonghuilv.net/mobile/person/index.html',
    ],
];

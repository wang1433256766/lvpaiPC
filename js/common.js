$(function(){
	//判断用户是否登录
    $.ajax({
        async: false,
        type: 'POST',
        url: 'http://lvpai.zhonghuilv.net/pc/index/getUserInfo',
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        },
        data: {},
        success: function(res){
            if(res.status == 0){
                $(".self-info").html('<div class="head-portrait" style="background:url(\''+res.data.headimg+'\');background-size:100%;background-position: center center;background-repeat: no-repeat;"></div>'+
                '<div class="self-name" title="'+res.data.nickname+'">'+(res.data.nickname.length>8?res.data.nickname.substring(0,8)+'...':res.data.nickname)+'&nbsp;&or;'+
                    '<ul>'+
                        '<li><a href="./selfinfo.html?flag=1">个人中心</a></li>'+
                        '<li><a href="./selfinfo.html?flag=2">订单中心</a></li>'+
                        '<li><a href="./selfinfo.html?flag=3">我的点评</a></li>'+
                        '<li><a href="./selfinfo.html?flag=4">出游信息</a></li>'+
                        '<li><a href="./selfinfo.html?flag=5">收货地址</a></li>'+
                        '<hr>'+
                        '<a class="logout">退出登录</a>'+
                    '</ul>'+
                '</div>');
            }
        }
    });

    //登出
    $(".logout").on("click", function(){
        $.ajax({
            type: 'GET',
            url: 'http://lvpai.zhonghuilv.net/pc/login/logout',
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            },
            data: {},
            success: function(res){
                if(res.status == 0){
                    window.location.href = './login.html';
                }
            }
        });
        // $.get('http://lvpai.zhonghuilv.net/pc/login/logout',function(){
        //     window.location.href = './login.html';
        // })
    })
})
//tab插件
;
(function($) {

    $.fn.tab = function(options) {
        // 将defaults 和 options 参数合并到{}
        var opts = $.extend({}, $.fn.tab.defaults, options);

        return this.each(function() {
            var obj = $(this);

            $(obj).find('.tab_header li').on('click', function() {
                $(obj).find('.tab_header li').removeClass('active');
                $(this).addClass('active');
                
                $(obj).find('.tab_content .tab_content_item').hide();
                $(obj).find('.tab_content .tab_content_item').eq($(this).index()).show();
            })
        });
        // each end
    }

    //定义默认
    $.fn.tab.defaults = {

    };

})(jQuery);

$(function() {
    $("#tab").tab();

    var obj = new WxLogin({
        self_redirect: false,
        id:"wx-code", 
        appid: "wx84ea8a0b6433aaf8", 
        scope: "snsapi_login", 
        redirect_uri: "http://www.shanshuiyinxiang.com/weixin_login.html",
        state: "",
        style: "",
        href: "http://www.shanshuiyinxiang.com/css/weixin_code.css"
    });
    
    var myreg=/^[1][3,4,5,7,8][0-9]{9}$/;  //手机号正则
    
    var checkPhone = $(".check-phone"),
        checkCode = $(".check-code"),
        mobile = $("#phone"),
        validateCode = $("#validateCode");

    //手机号校验
    mobile.bind('blur', function(){
        if(mobile.val().trim().length>0){
            if(!myreg.test(mobile.val())){
                checkPhone.html('请填写正确的手机号！'); 
            }else{
                checkPhone.html('');
            }
        }
    })
    mobile.bind('input propertychange', function(){
        if(myreg.test(mobile.val())){
            checkPhone.html(''); 
        }
    })
    
    //获取验证码
    $(".getValidateBtn").on('click', function(){
        var flag = true;
        if(mobile.val().trim().length>0){
            if(!myreg.test(mobile.val())){
                checkPhone.html('请填写正确的手机号！'); 
                flag = false;
            }else{
                checkPhone.html('');
            }
        }else{
            checkPhone.html('请填写手机号！'); 
            flag = false;
        }
        
        if(flag == false){
            return false;
        }

        $.ajax({
            type: 'POST',
            url: 'http://lvpai.zhonghuilv.net/pc/login/sendCord',
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            },
            data: {mobile: mobile.val()},
            success: function(res){
                if(res.status == 0){
                    addCookie("secondsremained", 60, 60); //添加cookie记录,有效时间60s
                    settime($(".getValidateBtn")); //开始倒计时
                }else{
                    checkPhone.html(res.msg);
                }
                
            }
        });
        

    });
    var v = getCookieValue("secondsremained") ? getCookieValue("secondsremained") : 0;//获取cookie值

    if(v > 0) {
        settime($(".getValidateBtn")); //开始倒计时
    }

    //login操作
    $(".loginBtn").click(function(){
        var flag = true;
        if(mobile.val().trim().length>0){
            if(!myreg.test(mobile.val())){
                checkPhone.html('请填写正确的手机号！'); 
                flag = false;
            }else{
                checkPhone.html('');
            }
        }else{
            checkPhone.html('请填写手机号！'); 
            flag = false;
        }

        if(validateCode.val().trim().length<=0){
            checkCode.html('请填写验证码！'); 
            flag = false;
        }

        validateCode.bind('input propertychange', function(){
            if(validateCode.val().trim().length == 6){
                checkCode.html(''); 
            }
        })
        
        if(flag == false){
            return false;
        }

        $.ajax({
            type: 'POST',
            url: 'http://lvpai.zhonghuilv.net/pc/login/validateCode',
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            },
            data: {code:validateCode.val(),mobile:mobile.val()},
            success: function(res){
                console.log(res.status);
                if(res.status == 0){
                    window.location.href = "./index.html";
                }else{
                    checkCode.html(res.msg);
                }
            }
        })
    })
})

//发送验证码时添加cookie
function addCookie(name, value, expiresHours) {
    var cookieString = name + "=" + escape(value);
    //判断是否设置过期时间,0代表关闭浏览器时失效
    if(expiresHours > 0) {
        var date = new Date();
        date.setTime(date.getTime() + expiresHours * 1000);
        cookieString = cookieString + ";expires=" + date.toUTCString();
    }
    document.cookie = cookieString;
}
//修改cookie的值
function editCookie(name, value, expiresHours) {
    var cookieString = name + "=" + escape(value);
    if(expiresHours > 0) {
        var date = new Date();
        date.setTime(date.getTime() + expiresHours * 1000); //单位是毫秒
        cookieString = cookieString + ";expires=" + date.toGMTString();
    }
    document.cookie = cookieString;
}


//根据名字获取cookie的值
function getCookieValue(name) {
    var strCookie = document.cookie;
    var arrCookie = strCookie.split("; ");
    for(var i = 0; i < arrCookie.length; i++) {
        var arr = arrCookie[i].split("=");
        if(arr[0] == name) {
            return unescape(arr[1]);
            break;
        }
    }

}

//开始倒计时
var countdown;
function settime(obj) {
    countdown = getCookieValue("secondsremained");
    var tim = setInterval(function() {
            countdown--;
            obj.attr("disabled", true);
            obj.css('cursor','not-allowed');
            obj.text("重新发送(" + countdown + ")");
            if(countdown <= 0 ) {
                clearInterval(tim);
                $(obj).removeAttr("disabled");
                obj.css('cursor','pointer');
                $(obj).text("获取验证码");
            }
            editCookie("secondsremained", countdown, countdown + 1);
        }, 1000) //每1000毫秒执行一次

}

//获取url参数
/*
encodeURI()是Javascript中真正用来对URL编码的函数
eg:
    编码： Javascript:encodeURI("春节");
    解码: Javascript:decodeURI("%E6%98%A5%E8%8A%82");
*/
(function ($) {
    $.getUrlParam = function (name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return decodeURI(r[2]); return null;
    }
})(jQuery);

$(function() {
    //获取url的id参数
    var id = $.getUrlParam('id');

    (function($) {

        $.fn.tab = function(options) {
            // 将defaults 和 options 参数合并到{}
            var opts = $.extend({}, $.fn.tab.defaults, options);

            return this.each(function() {
                var obj = $(this);
                $(obj).find('.tab_header li').on('click', function() {
                    $(obj).find('.tab_header li').removeClass('active');
                    $(this).addClass('active');
                    tabContent(id);
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

    $("#tab").tab();

    var defaultsVal = 10;
    $(".spe-input").val(defaultsVal);

    //选择出游日期的控件
    laydate.render({
      elem: '#choice-date' //指定元素
    });

    if(id){
        var shopPrice = null;
        $.ajax({
            async: false,
            type: 'POST',
            url: 'http://lvpai.zhonghuilv.net/pc/product/getProductPriceInfo',
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            },
            data: {id:id},
            success: function(res){
                //console.log(res);
                if(res.status == 0){
                    shopPrice = res.data.shop_price;
                    $(".goupiao-left").css('background-image','url(http://lvpai.zhonghuilv.net'+res.data.img+')');
                    $(".ticket-name").text(res.data.ticket_name);
                    $("#opentime").text('景区开放时间：'+res.data.opentime);
                    $("#shop-price").text(res.data.shop_price);
                    $("#market-price").text('门市价：￥'+res.data.market_price);
                    $("#sale-num").text(res.data.sale_num);
                    $("#address").text(res.data.address);
                    $("#travel_agency").val(res.data.travel_agency);
                    $("#total-price").text('￥'+shopPrice*$(".spe-input").val());
                }
                
            }
        });

        priceDetail(shopPrice);

        //计算总价格
        $(".spe-input").on('blur', function(){
            if(!$(".spe-input").val() || $(".spe-input").val()<10){
                $(".spe-input").val(10);
            }
            $("#total-price").text('￥'+shopPrice*$(".spe-input").val());
            priceDetail(shopPrice);
        })

        //出游人数的加减按钮
        $(".button1").on("click", function(){
            var defaultsVal = parseInt($(".spe-input").val()) || 10;
            if(defaultsVal <= 10){
                defaultsVal = 10;
            }else{
                defaultsVal -= 1;
            }
            $(".spe-input").val(defaultsVal);
            $("#total-price").text('￥'+shopPrice*$(".spe-input").val());
            priceDetail(shopPrice);
        });
        $(".button2").on("click", function(){
            var defaultsVal = parseInt($(".spe-input").val()) || 10;
            if(defaultsVal < 10){
                defaultsVal = 10;
            }else{
                defaultsVal += 1;
            }
            $(".spe-input").val(defaultsVal);
            $("#total-price").text('￥'+shopPrice*$(".spe-input").val());
            priceDetail(shopPrice);
        });

        // $('.multiple-agency').select2({
        //     width:'317px',
        //     placeholder: '请选择出游人'
        // });

        //获取出游人信息
        $.ajax({
            type: 'GET',
            url: 'http://lvpai.zhonghuilv.net/pc/product/getTravelInfo',
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            },
            success: function(res){
                //console.log(res);
                if(res.status == 0){
                    res.data.forEach(function(v,i){
                        $(".multiple-agency").append('<option value="'+v.id+'">'+v.use_name+'</option>');
                    });
                }
                $('.multiple-agency').select2({
                    width:'317px',
                    placeholder: '请选择出游人'
                });
            }
        })

        $("#choice-date").poshytip({
            content: '请选择出游日期！',
            className: 'tip-yellowsimple',
            showOn: 'focus',
            alignTo: 'target',
            alignX: 'right',
            alignY: 'center',
            offsetX: 5,
            showTimeout: 100
        });
        $("#travel_agency").poshytip({
            content: '请选择团队名称！',
            className: 'tip-yellowsimple',
            showOn: 'focus',
            alignTo: 'target',
            alignX: 'right',
            alignY: 'center',
            offsetX: 5,
            showTimeout: 100
        });
        $(".multiple-agency").poshytip({
            content: '请选择与出游人数匹配的出游人！',
            className: 'tip-yellowsimple',
            showOn: 'focus',
            alignTo: 'target',
            alignX: 'right',
            alignY: 'bottom',
            offsetX: 322,
            showTimeout: 100
        });

        //控制选择出游人的提醒
        $(".multiple-agency").on("change", function(){
            var traveler_ids = $(".multiple-agency").val();
            if(!traveler_ids){
                $('.multiple-agency').poshytip('hide');
            }else{
                if(traveler_ids.length != $(".spe-input").val()){
                    $('.multiple-agency').poshytip('show');
                }else{
                    $('.multiple-agency').poshytip('hide');
                }
            }
        });

        //生成订单
        $("#create-order").on("click", function(){
            var travel_date = $("#choice-date").val();
            var num = $(".spe-input").val();
            var travel_agency = $("#travel_agency").val();
            var traveler_ids = $(".multiple-agency").val();
            var traveler_ids_str = '';
            var returnFlag = true;
            //校验信息
            if(!travel_date){
                $('#choice-date').poshytip('show');
                returnFlag = false;
            }
            if(!travel_agency){
                $('#travel_agency').poshytip('show');
                returnFlag = false;
            }
            if(!traveler_ids){
                $('.multiple-agency').poshytip('hide');
                //returnFlag = false;
            }else{
                traveler_ids.forEach(function(v){
                    traveler_ids_str += v+',';
                })
                traveler_ids_str = traveler_ids_str.substring(0, traveler_ids_str.length - 1);
                if(traveler_ids.length != $(".spe-input").val()){
                    returnFlag = false;
                }
            }
            if(!returnFlag){
                return false;
            }
            //请求接口
            $.ajax({
                type: 'POST',
                url: 'http://lvpai.zhonghuilv.net/pc/product/createOrder',
                dataType: 'json',
                xhrFields: {
                    withCredentials: true
                },
                data: {ticket_id:id,travel_date:travel_date,num:num,travel_agency:travel_agency,traveler_ids:traveler_ids_str},
                success: function(res){
                    if(res.status == 0){
                        window.location.href = "./selfinfo.html?flag=2";
                    }else{
                        alert(res.msg);
                    }
                }
            })
        });

        tabContent(id);
        
    }



})

//明细
function priceDetail(shopPrice){
    $("#tooltip-poshytip").poshytip({
        className: 'tip-yellow',
        content: '费用明细：<br><br>门票&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;￥'+shopPrice+'x'+$(".spe-input").val(),
        bgImageFrameSize: 30
    });
}

//获取对应tab的内容
function tabContent(id){
    //内容部分接口
    if($("#tab .tab_header li").eq(0).hasClass('active')){
        //获取景点介绍
        getContent('getProductDesc',id,0);
    }else if($("#tab .tab_header li").eq(1).hasClass('active')){
        //获取线路介绍
        getContent('getProductAddress',id,1);
    }else if($("#tab .tab_header li").eq(2).hasClass('active')){
        //获取温馨提示
        getContent('getProductTips',id,2);
    }else if($("#tab .tab_header li").eq(3).hasClass('active')){
        //获取安全须知
        getContent('getProductSafety',id,3);
    }
}

//获取内容
function getContent(apiName,id,index){
    $.ajax({
        type: 'GET',
        url: 'http://lvpai.zhonghuilv.net/pc/product/'+apiName,
        dataType: 'json',
        data: {id:id},
        success: function(res){
            if(index == 0){
                $("#tab .tab_content .tab_content_item").eq(index).html(res.data.desc);
            }else if(index == 1){
                $("#tab .tab_content .tab_content_item").eq(index).html(res.data.address);
            }else if(index == 2){
                $("#tab .tab_content .tab_content_item").eq(index).html(res.data.tips);
            }else if(index == 3){
                $("#tab .tab_content .tab_content_item").eq(index).html(res.data.safety);
            }
            
        }
    });
}
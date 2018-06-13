$(function() {
    // 轮播
    $(".fullSlide").hover(function() {
            $(this).find(".prev,.next").stop(true, true).fadeTo("show", 0.5)
        },
        function() {
            $(this).find(".prev,.next").fadeOut()
        });
    $(".fullSlide").slide({
        titCell: ".hd ul",
        mainCell: ".bd ul",
        effect: "fold",
        autoPlay: true,
        autoPage: true,
        trigger: "click",
        startFun: function(i) {
            var curLi = jQuery(".fullSlide .bd li").eq(i);
            if (!!curLi.attr("_src")) {
                curLi.css("background-image", curLi.attr("_src")).removeAttr("_src")
            }
        }
    });

    //获取产品中心内容块
    $.ajax({
        type: 'POST',
        url: 'http://lvpai.zhonghuilv.net/pc/index/getProducts',
        dataType: 'json',
        // xhrFields: {
        //     withCredentials: true
        // },
        data: {},
        success: function(res){
            if(res.status == 0){
                var productDatas = res.data;
                var productContent = '';
                productDatas.forEach(function(v,i){
                    productContent += '<li>'+
                                        '<a href="./product_detail.html?id='+v.id+'">'+
                                            '<div class="img">'+
                                                '<img src="http://lvpai.zhonghuilv.net'+v.img+'" alt="">'+
                                            '</div>'+
                                            '<div class="introduce">'+
                                                '<p>'+v.ticket_name+'</p>'+
                                                '<div class="introduce-detail">'+
                                                    '<div class="sale">销量：'+v.sale_num+'</div>'+
                                                    '<div class="price">'+
                                                        '<span class="source-price"><s>门市价：'+v.market_price+'</s></span>&nbsp;&nbsp;&nbsp;&nbsp;'+
                                                        '<span class="now-price">&yen;'+v.shop_price+'</span>'+
                                                    '</div>'+
                                                '</div>'+
                                                '<hr class="hx">'+
                                            '</div>'+
                                        '</a>'+
                                    '</li>';
                })
                $("#product-content").html(productContent);
            }
        }
    })
    
})
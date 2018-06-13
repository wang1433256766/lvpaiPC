(function($) {

    $.fn.tab = function(options) {
        // 将defaults 和 options 参数合并到{}
        var opts = $.extend({}, $.fn.tab.defaults, options);

        return this.each(function() {
            var obj = $(this);
            $(obj).find('.tab_header').eq(0).find('li').on('click', function() {
                $(obj).find('.tab_header').eq(0).find('li').removeClass('active');
                $(this).addClass('active');
                if (obj[0].className == 'main-tab') {
                    $(".self-name li").removeClass('active');
                    $(".self-name li").eq($(this).index()).addClass('active');
                    tabAjaxOpt();
                }
                $(obj).find('.tab_content').eq(0).children('.tab_content_item').hide();
                $(obj).find('.tab_content').eq(0).children('.tab_content_item').eq($(this).index()).show();
            })
        });
        // each end
    }

    //定义默认
    $.fn.tab.defaults = {

    };

})(jQuery);

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
                var nickname = res.data.nickname.length>8?res.data.nickname.substring(0,8)+'...':res.data.nickname;
                $("#login-name").text(nickname);
                $("#login-name").attr('title',res.data.nickname);
                $(".head-portrait").css('background','url('+res.data.headimg+')');
                $(".head-portrait").css('background-size','100%');
                $(".head-portrait").css('background-position','center center');
                $(".head-portrait").css('background-repeat','no-repeat');
            }else{
                window.location.href = './index.html';
            }
        }
    });

    var flag = $.getUrlParam('flag');
    $(".self-name li").removeClass('active');
    $(".main-tab").find('.tab_header').eq(0).find('li').removeClass('active');
    $(".self-name li").find('.tab_content').eq(0).children('.tab_content_item').hide();
    $(".main-tab").find('.tab_content').eq(0).children('.tab_content_item').hide();
    if(flag == 1){
        controlActive(0);
    }else if(flag == 2){
        controlActive(1);
    }else if(flag == 3){
        controlActive(2);
    }else if(flag == 4){
        controlActive(3);
    }else if(flag == 5){
        controlActive(4);
    }

    $(".main-tab").tab();
    $(".sub-tab").tab();
    $(".self-name li").click(function() {
        $(".self-name li").removeClass('active');
        $(this).addClass('active');

        $(".main-tab").find('.tab_header').eq(0).find('li').removeClass('active');
        $(".main-tab").find('.tab_header').eq(0).find('li').eq($(this).index()).addClass('active');

        $(".main-tab").find('.tab_content').eq(0).children('.tab_content_item').hide();
        $(".main-tab").find('.tab_content').eq(0).children('.tab_content_item').eq($(this).index()).show();
        tabAjaxOpt();
    })

    //订单中心
    $(".order-center li").on("click", function() {
        $(".order-center li").removeClass('active');
        $(this).addClass('active');
        getOrderList($(this).index()-1);
    })

    //登出
    $(".logout").on("click", function(){
        $.get('http://lvpai.zhonghuilv.net/pc/login/logout',function(){
            window.location.href = './login.html';
        })
    })

    tabAjaxOpt();

    //添加出游人信息
    $(".addTravelBtn").on("click", function(){
        layer.open({
            type: 2,
            title: '添加出游人',
            maxmin: true,
            shadeClose: true, //点击遮罩关闭层
            shade: 0.4,
            area : ['800px' , '520px'],
            content: './add_traveler.html',
            success:function(layero,index){},
            end:function(){
                var handle_msg = $("#handle_msg").val();
                if($("#handle_status").val() == 0){
                    layer.msg(handle_msg,{
                        icon: 1,
                        time: 1000
                    },function(){
                        getTravelerList();
                        $("#handle_status").val('undefined');
                    });
                }else if($("#handle_status").val() != 'undefined'){
                    layer.msg(handle_msg,{icon:2});
                }
            }  
        });
    });

    //批量删除出游人信息
    $(".batchDel").click(function(){
        if($(".singleSelect:checked").length<=0){
            layer.msg('请选择要删除的出游人',{icon:2});
            return false;
        }
        var ids = '';
        //获取id集合
        for(var i=0; i<$(".singleSelect:checked").length; i++){
            ids += $(".singleSelect:checked")[i].id+',';
        };
        ids = ids.substring(0, ids.length - 1); 
        $.ajax({
            type: 'POST',
            url: 'http://lvpai.zhonghuilv.net/pc/Member_traveler_info/setTravelerInfo',
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            },
            data: {action: 'delAll', ids: ids},
            success: function(res){
                if(res.status == 0){
                    layer.msg(res.msg,{
                        icon: 1,
                        time: 1000
                    },function(){
                        getTravelerList();
                    });
                }else{
                    layer.msg(res.msg,{icon: 2})
                }
            }
        })
    })

    //导入联系人模板
    $(".exportTravelBtn").click(function(){
        window.location.href = "./assert/moban.xls";
    });

    //添加收货地址
    $(".addAddrBtn").on("click", function(){
        layer.open({
            type: 2,
            title: '添加收货地址',
            maxmin: true,
            shadeClose: true, //点击遮罩关闭层
            shade: 0.4,
            area : ['800px' , '420px'],
            content: './add_address.html',
            success:function(layero,index){},
            end:function(){
                var handle_msg = $("#handle_msg").val();
                if($("#handle_status").val() == 0){
                    layer.msg(handle_msg,{
                        icon: 1,
                        time: 1000
                    },function(){
                        getAddrList();
                        $("#handle_status").val('undefined');
                    });
                }else if($("#handle_status").val() != 'undefined'){
                    layer.msg(handle_msg,{icon:2});
                }
            }  
        });
    })

    //编辑个人信息
    $("#editPerInfo").on("click", function(){
        if($(".cancelBtn").hasClass('hiddenflag')){
            $(".part1").addClass('hiddenflag');
            $(".part2").removeClass('hiddenflag');
            $(".cancelBtn").removeClass('hiddenflag');
            $("#editPerInfo").text('保存');
            //将值赋到input框中
            $("#i-nickname").val($("#nickname").text());
            $("#i-teamname").val($("#teamname").text());
            $("#i-mobile").val($("#mobile").text());
            $("#i-birthday").val($("#birthday").text());
            $("#i-sex").val($("#sex").text()=='男'?'1':'2');
            $("#i-email").val($("#email").text());
            $("#i-province").val($("#province-city").text().split('-')[0]);
            $("#i-city").val($("#province-city").text().split('-')[1]);
            $("#i-address").val($("#address").text());
        }else{
            //调用修改接口，然后调用获取个人信息接口
            if(!$("#i-teamname").val() || !$("#i-mobile").val() || !$("#i-province").val() || !$("#i-city").val()){
                alert("带红心的必填");
                return false;
            }
            $.ajax({
                type: 'POST',
                url: 'http://lvpai.zhonghuilv.net/pc/Mall_member/updateInfo',
                dataType: 'json',
                xhrFields: {
                    withCredentials: true
                },
                data: {travel_agency:$("#i-teamname").val(),mobile:$("#i-mobile").val(),birthday:$("#i-birthday").val(),sex:$("#i-sex option:selected").val(),email:$("#i-email").val(),province:$("#i-province").val(),city:$("#i-city").val(),address:$("#i-address").val()},
                success: function(res){
                    if(res.status == 0){
                        getPersonalInfo();
                        $(".part1").removeClass('hiddenflag');
                        $(".part2").addClass('hiddenflag');
                        $(".cancelBtn").addClass('hiddenflag');
                        $("#editPerInfo").text('编辑');
                    }
                }
            })
        }
    })
    //取消编辑
    $(".cancelBtn").click(function(){
        $(".part1").removeClass('hiddenflag');
        $(".part2").addClass('hiddenflag');
        $(".cancelBtn").addClass('hiddenflag');
        $("#editPerInfo").text('编辑');
    })

})

//tab切换时匹配的样式切换
function controlActive(index){
    $(".self-name li").eq(index).addClass('active');
    $(".self-name li").find('.tab_content').eq(0).children('.tab_content_item').eq(index).show();
    $(".main-tab").find('.tab_header').eq(0).find('li').eq(index).addClass('active');
    $(".main-tab").find('.tab_content').eq(0).children('.tab_content_item').eq(index).show();
}

//切换到对应的tab加载对应的接口
function tabAjaxOpt(){
    //内容部分接口
    if($(".self-name li").eq(0).hasClass('active')){
        //获取个人中心的内容
        getPersonalInfo();
    }else if($(".self-name li").eq(1).hasClass('active')){
        //获取订单中心的内容
        getOrderList(-1);
    }else if($(".self-name li").eq(2).hasClass('active')){
        //获取我的点评的内容
    }else if($(".self-name li").eq(3).hasClass('active')){
        //获取出游信息的内容
        getTravelerList();
    }else if($(".self-name li").eq(4).hasClass('active')){
        //获取收货地址的内容
        getAddrList();
    }
}

//获取收获地址列表
function getAddrList(){
    $.ajax({
        type: 'GET',
        url: 'http://lvpai.zhonghuilv.net/pc/Member_address/infoList',
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        },
        success: function(res){
            var addrContent = '';
            if(res.status == 0){
                res.data.forEach(function(v,i){
                    addrContent += '<tr>'+
                                        '<td>'+v.username+'</td>'+
                                        '<td>'+v.province_city+'</td>'+
                                        '<td>'+v.address+'</td>'+
                                        '<td>'+v.post_code+'</td>'+
                                        '<td>'+v.phone+'</td>'+
                                        '<td>'+
                                            '<p><a href="#" onclick="addrUpdate(\''+v.id+'\')">修改</a></p>'+
                                            '<p><a href="#" onclick="addrDel(\''+v.id+'\')">删除</a></p>'+
                                        '</td>'+
                                    '</tr>';
                })
            }
            $("#addr-info").html(addrContent);
        }
    })
}

//获取出游信息列表
function getTravelerList(){
    $.ajax({
        async: false,
        type: 'GET',
        url: 'http://lvpai.zhonghuilv.net/pc/Member_traveler_info/infoList',
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        },
        success: function(res){
            //console.log(res);
            var travelerContent = '';
            var sexStr = null;
            if(res.status == 0){
                res.data.forEach(function(v,i){
                    if(v.sex == "保密"){
                        sexStr = "保密"
                    }else if(v.sex == 1){
                        sexStr = "男";
                    }else if(v.sex == 2){
                        sexStr = "女";
                    }
                    travelerContent += '<tr>'+
                                            '<td><input type="checkbox" class="singleSelect" id="'+v.id+'"/></td>'+
                                            '<td>'+v.use_name+'</td>'+
                                            '<td>'+v.old+'</td>'+
                                            '<td>'+sexStr+'</td>'+
                                            '<td>'+(v.card_type==1?"身份证":"")+'</td>'+
                                            '<td>'+v.use_card+'</td>'+
                                            '<td>'+v.mobile+'</td>'+
                                            '<td>'+
                                                '<p><a href="#" onclick="travelerUpdate(\''+v.id+'\')">修改</a></p>'+
                                                '<p><a href="#" onclick="travelerDel(\''+v.id+'\')">删除</a></p>'+
                                            '</td>'+
                                        '</tr>';
                })
            }
            $("#traveler-info").html(travelerContent);
            $(".allSelect").prop('checked', false);
            //点击全选按钮触发全选操作
            $(".allSelect").click(function(){
                if($(this).prop('checked')){
                    $(".singleSelect").prop('checked', true);
                }else{
                    $(".singleSelect").prop('checked', false);
                }
            });
            //点击单个的按钮，改变是否是全选状态
            $(".singleSelect").click(function(){
                $(".allSelect").prop('checked',$('.singleSelect:checked').length == $('.singleSelect').length ? true : false);  
                /*阻止向上冒泡，以防再次触发点击操作*/  
                event.stopPropagation();
            })
        }
    })
}

//获取订单列表
function getOrderList(status){
    $.ajax({
        type: 'GET',
        url: 'http://lvpai.zhonghuilv.net/pc/order/orderList',
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        },
        data: {status:status},
        success: function(res){
            var orderContent = '';
            var orderStatus = null;
            var orderOpt = null;
            if(res.status == 0){
                res.data.forEach(function(v,i){
                    if(v.status == 0){
                        orderStatus = '待付款';
                        orderOpt = '付款';
                    }else if(v.status == 1){
                        orderStatus = '已支付';
                        orderOpt = '退款';
                    }else if(v.status == 2){
                        orderStatus = '处理中';
                        orderOpt = '取消退款';
                    }else if(v.status == 3){
                        orderStatus = '已取消';
                        orderOpt = '';
                    }else if(v.status == 4){
                        orderStatus = '已退款';
                        orderOpt = '';
                    }else if(v.status == 5){
                        orderStatus = '完成';
                        orderOpt = '';
                    }else if(v.status == 6){
                        orderStatus = '部分退款';
                        orderOpt = '';
                    }
                    orderContent += '<tr>'+
                                        '<td>'+
                                            '<p>下单日期：'+new Date(v.add_time*1000).format('yyyy-MM-dd')+'</p>'+
                                            '<p>'+v.ticket_name+'</p>'+
                                        '</td>'+
                                        '<td>'+
                                            '<p>订单编号：'+v.order_sn+'</p>'+
                                            '<p>'+v.num+'人</p>'+
                                        '</td>'+
                                        '<td>'+v.travel_date+'</td>'+
                                        '<td>&yen;'+v.order_total+'</td>'+
                                        '<td>'+
                                            '<p>'+orderStatus+'</p>'+
                                            '<p style="cursor:pointer;" onclick="showDetail(\''+v.id+'\')">订单详情</p>'+
                                        '</td>'+
                                        '<td><a href="#" onclick="opt(\''+v.status+'\',\''+v.order_sn+'\')">'+orderOpt+'</a></td>'+
                                    '</tr>';
                })
            }
            $("#order-list").html(orderContent);
        }
    })
}

//付款操作
function opt(status,orderSn){
    //console.log(status+'---'+orderSn);
    if(status == 0){ //付款
        $.ajax({
            type: 'POST',
            url: 'http://lvpai.zhonghuilv.net/pc/product/payByWechat',
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            },
            data: {order_no:orderSn},
            success: function(res){
                //console.log(res);
                if(res.status == 0){
                    layer.open({
                      type: 1,
                      title: '微信付款码',
                      area: ['360px', '360px'],
                      shadeClose: true, //点击遮罩关闭
                      content: '<div style="height:318px;background:url('+res.msg+') no-repeat;background-size:60%;background-position:center center;"></div>',
                      end:function(){
                            window.clearInterval(int); //销毁定时器
                        }
                    });
                    //启动定时器
                    var int = self.setInterval(function(){pay_status(int,orderSn)},1000);
                }else{
                    layer.msg(res.msg,{icon:2});
                }
            }
        })
    }else if(status == 1){ //退款
        $.ajax({
            type: 'POST',
            url: 'http://lvpai.zhonghuilv.net/pc/order/tuikuan',
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            },
            data: {order_sn:orderSn},
            success: function(res){
                if(res.status == 0){
                    layer.msg(res.msg,{icon:1,time:1000},function(){
                        getOrderList($($(".order-center li.active")).index()-1);
                    });
                }else{
                    layer.msg(res.msg,{icon:2});
                }
            }
        })
    }else if(status == 2){ //取消退款
        $.ajax({
            type: 'POST',
            url: 'http://lvpai.zhonghuilv.net/pc/order/applyCancel',
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            },
            data: {order_sn:orderSn},
            success: function(res){
                if(res.status == 0){
                    layer.msg(res.msg,{icon:1,time:1000},function(){
                        getOrderList($($(".order-center li.active")).index()-1);
                    });
                }else{
                    layer.msg(res.msg,{icon:2});
                }
            }
        })
    }
}

//显示订单详情
function showDetail(orderId){
    layer.open({
      type: 2,
      title: '订单详情',
      maxmin: true,
      shadeClose: true, //点击遮罩关闭层
      shade: 0.8,
      area : ['800px' , '520px'],
      content: './order_detail.html?orderId='+orderId
    });
}

//修改出游人信息
function travelerUpdate(travelerId){
    layer.open({
        type: 2,
        title: '编辑出游人信息',
        maxmin: true,
        shadeClose: true, //点击遮罩关闭层
        shade: 0.4,
        area : ['800px' , '520px'],
        content: './add_traveler.html?travelerId='+travelerId,
        success:function(layero,index){},
        end:function(){
            var handle_msg = $("#handle_msg").val();
            if($("#handle_status").val() == 0){
                layer.msg(handle_msg,{
                    icon: 1,
                    time: 1000
                },function(){
                    getTravelerList();
                    $("#handle_status").val('undefined');
                });
            }else if($("#handle_status").val() != 'undefined'){
                layer.msg(handle_msg,{icon:2});
            }
        }
    });
}

//删除出游人信息
function travelerDel(travelerId){
    $.ajax({
        type: 'POST',
        url: 'http://lvpai.zhonghuilv.net/pc/Member_traveler_info/setTravelerInfo',
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        },
        data: {action: 'del',id: travelerId},
        success: function(res){
            if(res.status == 0){
                layer.msg(res.msg,{icon:1,time:1000},function(){
                    getTravelerList();
                });
            }else{
                layer.msg(res.msg,{icon:2});
            }
        }
    })
}

//导入联系人
function readText(filepath){
    if (filepath.files && filepath.files[0]) {
        if(filepath.files[0].name.split('.')[1].indexOf('xls') == -1){
            layer.msg('请选择Excel格式文件！',{
                icon: 2
            });
            return false;
        }
        var formData = new FormData();
        formData.append("file" , filepath.files[0]);
        $.ajax({
            type: 'POST',
            url: 'http://lvpai.zhonghuilv.net/pc/Member_traveler_info/excleImport',
            xhrFields: {
                withCredentials: true
            }, 
            data: formData,  
            async: false,  
            cache: false,  
            contentType: false,  
            processData: false,  
            success: function(res){
                var res = JSON.parse(res);
                if(res.status == 0){
                    layer.msg(res.msg,{
                        icon: 1,
                        time: 1000
                    },function(){
                        getTravelerList();
                    });
                }else{
                    layer.msg(res.msg,{
                        icon: 2
                    })
                }
            }
        })
    }
}

//修改收获地址
function addrUpdate(addrId){
    layer.open({
        type: 2,
        title: '修改收货地址',
        maxmin: true,
        shadeClose: true, //点击遮罩关闭层
        shade: 0.4,
        area : ['800px' , '420px'],
        content: './add_address.html?addrId='+addrId,
        success:function(layero,index){},
        end:function(){
            var handle_msg = $("#handle_msg").val();
            if($("#handle_status").val() == 0){
                layer.msg(handle_msg,{
                    icon: 1,
                    time: 1000
                },function(){
                    getAddrList();
                    $("#handle_status").val('undefined');
                });
            }else if($("#handle_status").val() != 'undefined'){
                layer.msg(handle_msg,{icon:2});
            }
        }  
    });
}

//删除收获地址
function addrDel(addrId){
    $.ajax({
        type: 'POST',
        url: 'http://lvpai.zhonghuilv.net/pc/Member_address/setTravelerInfo',
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        },
        data: {action: 'del',id: addrId},
        success: function(res){
            if(res.status == 0){
                layer.msg(res.msg,{icon:1,time:1000},function(){
                    getAddrList();
                });
            }else{
                layer.msg(res.msg,{icon:2});
            }
        }
    })
}

//获取个人资料
function getPersonalInfo(){
    $.ajax({
        type: 'GET',
        url: 'http://lvpai.zhonghuilv.net/pc/Mall_member/personalData',
        dataType: 'json',
        xhrFields: {
            withCredentials: true
        },
        success: function(res){
            if(res.status == 0){
                $("#nickname").text(res.data.nickname);
                $("#teamname").text(res.data.travel_agency);
                $("#mobile").text(res.data.mobile);
                $("#birthday").text(res.data.birthday);
                $("#sex").text(res.data.sex==1?'男':'女');
                $("#email").text(res.data.email);
                $("#province-city").text(res.data.province+'-'+res.data.city);
                $("#address").text(res.data.address?res.data.address:'');
            }
        }
    })
}

//获取付款状态
function pay_status(int,orderSn){
    $.ajax({   
        type:'post', 
        url:'http://lvpai.zhonghuilv.net/pc/product/getStatusByOrderNo',
        dataType:'json', 
        xhrFields: {
            withCredentials: true
        },  
        data:{order_no:orderSn},  
        success:function(res){   
            if(res.status == 0){
                window.clearInterval(int); //销毁定时器
                setTimeout(function(){
                    //跳转到结果页面，并传递状态
                    layer.closeAll();
                    layer.msg(res.msg,{
                        icon:1,
                        time: 1000
                    },function(){
                        getOrderList($($(".order-center li.active")).index()-1);
                    });
                },1000)
                
            }else if(res.status == 1){
                window.clearInterval(int); //销毁定时器
                setTimeout(function(){
                    //跳转到结果页面，并传递状态
                    layer.closeAll();
                    layer.msg(res.msg,{icon:2});
                },1000)
            }else if(res.status == 2){
                //未支付的状态
            }
        }, 
        error:function(){
            console.log(error);   
            window.clearInterval(int); //销毁定时器
        } 

  });
}

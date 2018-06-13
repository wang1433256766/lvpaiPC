$(function(){
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

	var orderId = $.getUrlParam('orderId');
	//console.log(orderId);

	//获取订单信息
	$.ajax({
		type: 'GET',
		url: 'http://lvpai.zhonghuilv.net/pc/order/getOrderInfo',
		dataType: 'json',
		xhrFields: {
            withCredentials: true
        },
		data: {id:orderId},
		success: function(res){
			//console.log(res);
			if(res.status == 0){
				$("#orderNo").text(res.data.order_sn);
				$("#orderDate").text(new Date(res.data.add_time*1000).format('yyyy-MM-dd'));
				if(res.data.status == 0){
					$("#orderStatus").text('待付款');
				}else if(res.data.status == 1){
					$("#orderStatus").text('待出行');
				}else if(res.data.status == 2){
					$("#orderStatus").text('处理中');
				}else if(res.data.status == 3){
					$("#orderStatus").text('已取消');
				}else if(res.data.status == 4){
					$("#orderStatus").text('已退款');
				}else if(res.data.status == 5){
					$("#orderStatus").text('完成');
				}else if(res.data.status == 6){
					$("#orderStatus").text('部分退款');
				}
				$("#orderName").text(res.data.ticket_name);
				$("#traverDate").text(res.data.travel_date);
				$("#orderUnitPrice").text(res.data.price);
				$("#orderNum").text(res.data.num);
			}
		}
	});

	//获取出游人信息
	$.ajax({
		type: 'GET',
		url: 'http://lvpai.zhonghuilv.net/pc/order/getTrv',
		dataType: 'json',
		xhrFields: {
            withCredentials: true
        },
		data: {id:orderId},
		success: function(res){
			console.log(res);
			var content = '';
			if(res.status == 0){
				res.data.forEach(function(v,i){
					content += '<tr>'+
									'<td>'+v.use_name+'</td>'+
									'<td>'+v.mobile+'</td>'+
									'<td>'+v.use_card+'</td>'+
								'</tr>';
				})
			}
			$("#travelInfo").html(content);
		}
	})
})
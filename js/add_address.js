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

	var addrId = $.getUrlParam('addrId');

	if(addrId){
		//获取出游人详细信息
		$.ajax({
			async: false,
	        type: 'GET',
	        url: 'http://lvpai.zhonghuilv.net/pc/Member_address/setTravelerInfo',
	        dataType: 'json',
	        xhrFields: {
	            withCredentials: true
	        },
	        data: {action: 'look',id: addrId},
	        success: function(res){
	            if(res.status == 0){
	            	$("#userName").val(res.data.username);
	            	$("#detailAddr").val(res.data.address);
	            	$("#postCode").val(res.data.post_code);
	            	$("#userMobile").val(res.data.phone);

	            	$("#provinceCityArea").distpicker({
						province: res.data.province_city.split('-')[0],
					  	city: res.data.province_city.split('-')[1],
					  	district: res.data.province_city.split('-')[2]
					})
	            }        
	        }
	    })
	}else{
		//省市区三级联动插件
		$("#provinceCityArea").distpicker({
			province: '---- 选择省 ----',
		  	city: '---- 选择市 ----',
		  	district: '---- 选择区 ----'
		});
	}

	//提交操作
	$("#submitBtn").on("click", function(){
		var proviceId = $("#provinceAddr option:selected").val();
		var cityId = $("#cityAddr option:selected").val();
		var areaId = $("#areaAddr option:selected").val();

		var username = $("#userName").val();
		var province_city = proviceId+'-'+cityId+'-'+areaId;
		var address = $("#detailAddr").val();
		var post_code = $("#postCode").val();
		var phone = $("#userMobile").val();

		var flag = true;
		var phoneReg = /^[1][3,4,5,7,8][0-9]{9}$/;  //手机号正则
		var telReg = /^0\d{2,3}-?\d{7,8}$/;  //电话号码正则

		if(!username){
			layer.tips('请填写收货人!', '#userName',  {tipsMore: true});
			flag = false;
		}
		if(!phone){
			layer.tips('请填写联系方式!', '#userMobile',  {tipsMore: true});
			flag = false;
		}else{
			if(!phoneReg.test(phone) && !telReg.test(phone)){
				layer.tips('请填写正确的手机号码或电话号码!', '#userMobile',  {tipsMore: true});
				flag = false;
			}
		}

		if(!flag){
			return false;
		}

		var params = {username:username,province_city:province_city,address:address,post_code:post_code,phone:phone};
		if(addrId){
			params.action = 'update';
			params.id = addrId;
		}else{
			params.action = 'add';
		}

		$.ajax({
	        type: 'POST',
	        url: 'http://lvpai.zhonghuilv.net/pc/Member_address/setTravelerInfo',
	        dataType: 'json',
	        xhrFields: {
	            withCredentials: true
	        },
	        data: params,
	        success: function(res){
	        	var index = parent.layer.getFrameIndex(window.name);
	            parent.$("#handle_status").val(res.status);
	            parent.$("#handle_msg").val(res.msg);
	        	if(res.status == 0){
	              	parent.layer.close(index);
	        	}
	        }
	    });
	})
})
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

	var travelerId = $.getUrlParam('travelerId');
	//console.log(travelerId);

	if(travelerId){
		//获取出游人详细信息
		$.ajax({
			async: false,
	        type: 'GET',
	        url: 'http://lvpai.zhonghuilv.net/pc/Member_traveler_info/setTravelerInfo',
	        dataType: 'json',
	        xhrFields: {
	            withCredentials: true
	        },
	        data: {action: 'look',id: travelerId},
	        success: function(res){
	            if(res.status == 0){
	            	$("#userName").val(res.data.use_name);
	            	$("#userAge").val(res.data.old);
	            	$("#userSex").val(res.data.sex);
	            	$("#userType").val(res.data.types);
	            	$("#userMobile").val(res.data.mobile);
	            	$("#userCardType").val(res.data.card_type);
	            	$("#userCard").val(res.data.use_card);
	            }        
	        }
	    })
	}

	//提交操作
	$("#submitBtn").on("click", function(){
		var use_name = $("#userName").val();
		var old = $("#userAge").val();
		var sex = $("#userSex option:selected").val();
		var types = $("#userType option:selected").val();
		var mobile = $("#userMobile").val();
		var card_type = $("#userCardType option:selected").val();
		var use_card = $("#userCard").val();

		var flag = true;
		var phoneReg = /^[1][3,4,5,7,8][0-9]{9}$/;  //手机号正则
		var telReg = /^0\d{2,3}-?\d{7,8}$/;  //电话号码正则
		//  ^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$
		//  ^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{2}$
		var cardReg = /(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{2}$)/; //身份证正则

		if(!use_name){
			layer.tips('请填写姓名!', '#userName',  {tipsMore: true});
			flag = false;
		}
		if(!mobile){
			layer.tips('请填写联系方式!', '#userMobile',  {tipsMore: true});
			flag = false;
		}else{
			if(!phoneReg.test(mobile) && !telReg.test(mobile)){
				layer.tips('请填写正确的手机号码或电话号码!', '#userMobile',  {tipsMore: true});
				flag = false;
			}
		}
		if(!use_card){
			layer.tips('请填写证件号码!', '#userCard',  {tipsMore: true});
			flag = false;
		}else{
			if(!cardReg.test(use_card)){
				layer.tips('请填写正确的证件号码!', '#userCard',  {tipsMore: true});
				flag = false;
			}
		}

		if(!flag){
			return false;
		}

		var params = {use_name:use_name,old:old,sex:sex,types:types,mobile:mobile,card_type:card_type,use_card:use_card};
		if(travelerId){
			params.action = 'update';
			params.id = travelerId;
		}else{
			params.action = 'add';
		}

		$.ajax({
	        type: 'POST',
	        url: 'http://lvpai.zhonghuilv.net/pc/Member_traveler_info/setTravelerInfo',
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
	        	}else{
	        		layer.tips(res.msg, '#userCard',  {tipsMore: true});
	        	}
	        }
	    });
	})
})
var rental_car_search_location_ref=/^\/o\/rentalCar\/search/;
if(rental_car_search_location_ref.test(window.location.pathname)) {
	$(function(){

		var page = parseInt($("#carpage").val()) ,total =  parseInt($("#total").val()),stop= true;

		var stop=true;
		if(page == "" || total == "" || page == total){
			$(window).unbind("scroll",getRentalCars);
		}
		else {
			console.log("aaaaaaaaaaaa");
			$(window).bind("scroll",getRentalCars);

		}
		function  getRentalCars (){
			totalheight = parseFloat($(window).height()) + parseFloat($(window).scrollTop());
			console.log("$(document).height()::", $(document).height(), "totalheight:::" + totalheight);
			if (($(document).height() - 10) <= totalheight) {
				if (stop == true) {
					stop = false;

					$("#Loading").show();
					$.post("/api/operator/getRentalCars",
							{
								"page": page + 1,
								"userID": $("#userID").val(),
								"carStaus":parseInt($(".car-staus").val()),
								"carType":parseInt($(".car-type").val())
							},
							function (data, status) {
								console.log(data);
								if (status) {

									page = parseInt(data["page"]);

									if (data.errorCode == 0) {
										addCars(data["rentalCars"]);
										stop= true;
										$("#Loading").hide();
										console.log("page::::"+page,"total::::::::"+total);
										if(page > total || page==total){
											$("#Loading").text("已经是最后一页了！！！");
											$("#Loading").show();
											setTimeout(function(){
												$("#Loading").animate({ opacity:'0',zIndex:0},"slow");
											},6000);
											$(window).unbind("scroll",getRentalCars);
										}
									}
									else {
										alert(data.errorMessage);
									}
								}
							});
				}
			}
		}

		function addCars (data){
			console.log(data);
			for(var i= 0;i<data.length;i++){
				var rentalCar = data[i];

				var temp= $('<a href="/rentalCar/show'+rentalCar["rentalCarID"]+'"> <div class="car-cont '+ (rentalCar["status"]==301?"onrent":'') +'"> \
                        <div class="row clearfix">\
                        <div class="left-cont">\
                        <div class="img-cont">\
                        <img src="'+ rentalCar["image"]+'">\
                        </div>\
                        </div>\
                        <div class="right-cont"> \
                        <div class="text"> \
                        <p>'+rentalCar["license"] +'</p> \
                        <p><span class="car-t">车型：</span>' +rentalCar["car"]["name"] +'&nbsp;&nbsp; \
            <span class="car-t"> 续航：</span>'+ (rentalCar["mileage"] ? rentalCar["mileage"]+"km" : '<span class="color-red">异常</span>')+'</p>\
                        <p class="status">'+(rentalCar["online"] ==1 ?"已上线&nbsp;&nbsp;-":'<span class="color-red">已下线&nbsp;&nbsp;-</span>')+(rentalCar["status"] == 300? '<span class="color-green">未租赁</span>': rentalCar["status"] == 301? '<span class="color-yellow">租赁中</span>':"")+'</p>\
                        </div>\
                        </div> \
                        </div> \
                        <div class="row"> \
                        <p>'  +rentalCar["rentalStation"]["name"] +'</p>\
                        </div>\
                        </div>\
                </a>') ;
				$(".container").append($(temp));
			}

		}
	})
}


$("#page.rental_car.charging").delegate("#charging-submit","click",function(){
	var msg=1;
	var degree=$("input[name='degree']").val();
	var cost=$("input[name='cost']").val();
	var mileage=$("input[name='mileage']").val();
	var regnum=/^[1-9]\d*|[1-9]\d*\.\d*|0\.\d*[1-9]\d*|0?\.0+|0$/;
	var regnum1=/^[1-9]\d{0,2}$/;
	//充电数
	if(degree==""){
		alert("请填写充电数");
		msg=0;
		return false;
	}
	if(!(regnum.test(degree))){
		alert("充电数书写有误");
		msg=0;
		return false;
	}

	//充电费用
	if(cost==""){
		alert("请填写充电费");
		msg=0;
		return false;
	}
	if(!regnum.test(cost)){
		alert("充电费用书写有误");
		msg=0;
		return false;
	}
	//续航数
	if(mileage==""){
		alert("请填写续航数");
		msg=0;
		return false;
	}
	if(!regnum1.test(mileage)){
		alert("续航数书写有误");
		msg=0;
		return false;
	}
	if(msg==1){
		var r=confirm("确认提交?")
		if (r==true)
		{
			return true;
		}
		else
		{
			return false;
		}

	}

});

$("#page.rental_car.show").delegate("a","click",function(){

	$("#page.rental_car.show .shadow").show();
});

var operate={
	"find":{
		0:'',
		1:'找车成功请注意车辆双闪',
	},
	"open":{
		0:'确认开门?',
		1:'开门成功'
	},
	"close":{
		0:'确认锁门?',
		1:'锁门成功'
	},
	"on":{
		0:'',
		1:'通电成功'
	},
	"off":{
		0:'',
		1:'断电成功'
	},
    "reset":{
        0:'确认初始化设备?',
        1:'初始化成功'
    }
};
$("#page.rental_car.show .rental-lock").delegate("button","click",function(){
	var car_operate=$(this).attr("operate");
	var parent= $(this).parents(".rental-lock");
	var id = $("#rental-car-id").val();
	var user_id = $("#user-id").val();
	var r;
	if($(parent).hasClass("progressing")){
		$(".input-data").val("");
		$(".alert-min").attr("operate",car_operate);
		$(".alert-min").show();
		$(".all").show();
		return false;
	}
	if(operate[car_operate][0]){
		r=confirm(operate[car_operate][0]);
	}
	if(operate[car_operate][0]==""||r==true){
		$.post("/api/rentalCar/operate",
			{
				rentalCarID:id,
				userID:user_id,
				operate:car_operate
			},
			function(data,status){
				if(status){
					if(data.errorCode==0){
						alert(operate[car_operate][1]);

					}else{
						alert(data.errorMessage);
					}

				}

			});
	}

});


$("#page.rental_car.show .alert-min").delegate(".alert-cancel","click",function(){
	$(".alert-min .input-data").val("")
	$(".alert-min").css("display","none");
	$(".all").css("display","none");
	$(".alert-min .input-error").text("");
	return false;
});
$("#page.rental_car.show .alert-min").delegate(".alert-submit","click",function(){
	var car_operate=$(".alert-min").attr("operate");
	var id = $("#rental-car-id").val();
	var user_id = $("#user-id").val();
	var car_license_num=$.trim($("#car-license").val());
	var car_str=car_license_num.substr(1);
	var exg=new RegExp("^"+car_str,'gi');
	var r;
	var car=$.trim($(".alert-min .input-data").val());
	if(exg.test(car)){
		$(".alert-min .input-error").text("");
		$(".alert-min").css("display","none");
		$(".all").css("display","none");
		$(".alert-min .input-data").val("");
		$(".alert-min .input-error").text("");
		if(operate[car_operate][0]){
			r=confirm(operate[car_operate][0]);
		}
		if(operate[car_operate][0]==""||r==true){
			$.post("/api/rentalCar/operate",
				{
					rentalCarID:id,
					userID:user_id,
					operate:car_operate
				},
				function(data,status){
					if(status){
						if(data.errorCode==0){
							alert(operate[car_operate][1]);

						}else{
							alert(data.errorMessage);
						}

					}

				});
		}
	}
	else{
		$(".input-error").text("你输入的车牌号有误");
	}
});



$("#page.rental_car.edit .top-menu").delegate(".submit","click",function(){
	var input=$("input");
	for(var i=0;i<$(input).length;i++){
		if($.trim($(input[i]).val())==""){
			alert("请填写全部数据,再提交！");
			return false;
		}
	}
	$("#page.rental_car.edit form").submit();
});

$("#page.rental_car.online").delegate("input","click",function(){
	$(this).toggleClass("checked");
});

$("#page.rental_car.online").delegate("button","click",function(){
	var require=$(".require");
	var input=$(".checked");
	var req=$(".require.checked");
	var car_id=$(this).attr("carId");
	var user_id=$(this).attr("userId");
	var json={};
	var remarkStr="";
	if($(req).length==$(require).length){
		for(var i=0;i<$(input).length;i++){
			json[i]=$(input[i]).attr("chId");
		}
		if($.trim($("textarea").val())!=""){
			remarkStr=$.trim($("textarea").val());
		}
		$("#page.rental_car.online form .text").val(JSON.stringify(json));
			$.post("/api/rentalCar/online",
				{
					rentalCarID:car_id,
					userID:user_id,
					status:1,
					reason:JSON.stringify(json),
					remark:remarkStr
				},
				function(data,status){
					if(status){
						if(data.errorCode==0){
							alert("上线成功！");
							window.location.href=$(".left-cont").attr("href");
						}else{
							alert(data.errorMessage);
						}
					}
				});
		/*$("#page.rental_car.online form").submit();*/

	}
	else {
		alert("＊为必填项请检查后确认");
		return false;
	}
});


$("#page.rental_car.offline").delegate("input","click",function(){
	$(this).toggleClass("checked");
});



$("#page.rental_car.offline textarea").blur(function(){
	$(this).val($.trim($(this).val()));
	if($(this).val()){
		$(this).addClass("checked");
	}
	else {
		$(this).removeClass("checked");
	}
}).keyup(function(){
	$(this).triggerHandler("blur");
});



$("#page.rental_car.offline").delegate("button","click",function(){
	var require=$(".require");
	var input=$(".checked");
	var req=$(".require.checked");
	if($(req).length!=$(require).length){
		alert("＊为必填项请检查后确认");
		return false;
	}
	var car_id=$(this).attr("carId");
	var user_id=$(this).attr("userId");
	var input=$(".checked");
	var json={};
	var remarkStr="",i=0;
	for(i=0;i<$(input).length;i++){
		json[i]=$(input[i]).attr("chId");
	}
	remarkStr=$.trim($("textarea").val());
	var r=confirm("确定将"+$("#license").val()+"的车辆下线吗？");
if(r) {

	if (($(input).length > 1 || remarkStr ) && $("input[name='orderID']").length) {
		$("#page.rental_car.offline form .text").val(JSON.stringify(json));
		$.post("/api/operator/endRentalOrder",
			{
				orderID: $(".orderID").val(),
				userID: user_id,
				reason: JSON.stringify(json),
				remark: remarkStr
			},
			function (data, status) {
				if (status) {
					if (data.errorCode == 0) {
						alert("人工还车成功！");
						window.location.href = $(".left-cont").attr("href");
					} else {
						alert(data.errorMessage);
					}
				}
			});
	}
	else if (($(input).length || remarkStr) && !$("input[name='orderID']").length) {
		for (var i = 0; i < $(input).length; i++) {
			json[i] = $(input[i]).attr("chId");
		}
		remarkStr = $.trim($("textarea").val());
		$("#page.rental_car.offline form .text").val(JSON.stringify(json));

		$.post("/api/rentalCar/online",
			{
				rentalCarID: car_id,
				userID: user_id,
				status: 0,
				reason: JSON.stringify(json),
				remark: remarkStr
			},
			function (data, status) {
				if (status) {
					if (data.errorCode == 0) {
						alert("下线成功！");
						window.location.href = $(".left-cont").attr("href");
					} else {
						alert(data.errorMessage);
					}
				}
			});
		/*$("#page.rental_car.offline form").submit();*/
	}
	else {
		alert("需要最少选择一种下线原因");
	}
}
});

$("#page.rental_car.search").delegate(".condit-status","click",function(){
	$(this).siblings(".top-cont").find(".dropdown").hide();
	$(this).find(".dropdown").show();
	$(".all").show();
	$(".condit-type .arrow1").removeClass("chick-arrow");
	$(".condit-status .arrow1").addClass("chick-arrow");
});
$("#page.rental_car.search").delegate(".condit-type","click",function(){
	$(this).siblings(".top-cont").find(".dropdown").hide();
	$(this).find(".dropdown").show();
	$(".all").show();
	$(".condit-type .arrow1").addClass("chick-arrow");
	$(".condit-status .arrow1").removeClass("chick-arrow");
});
$("#page.rental_car.search").delegate(".drop-li","click",function(event){

	$(this).parents(".top-cont").find(".top-text").text($(this).find("span").text());
	$(this).siblings('.drop-li').removeClass("check");
	$(this).addClass("check")
	if($(this).parents(".top-cont").hasClass("condit-status")){
		$(".car-staus").val($(this).attr("ctypeid"));
	}
	if($(this).parents(".top-cont").hasClass("condit-type")){
		$(".car-type").val($(this).attr("ctypeid"));
	}
	$(".dropdown").hide();
	$(".all").hide();
	$(this).parents(".top-cont").find(".arrow1").removeClass("chick-arrow");
	$(".car-search").submit();
	event.stopPropagation();
});
$("#page.rental_car.search").delegate(".all","click",function(){
	$(".dropdown").hide();
	$(".all").hide();
	$(".arrow1").removeClass()("chick-arrow");
});

$("#page.rental_car.searchlicense").delegate(".licensePlace","click",function(){
	$(this).find(".dropdown").show();
	$(".all").show();
	$(".arrow1").addClass("chick-arrow");
});

$("#page.rental_car.searchlicense").delegate(".drop-li","click",function(event){
	console.log($(this).text());
	$(this).parents(".search-top").find(".top-text").text($(this).text());

	$(this).siblings('.drop-li').removeClass("check");
	$(this).addClass("check");
	$(".place").val($(this).attr("placeid"));
	$(".dropdown").hide();
	$(".all").hide();
	$(".arrow1").removeClass("chick-arrow");
	event.stopPropagation();
});

$("#page.rental_car.searchlicense").delegate(".button","click",function(){
	var license= $.trim($(".input input").val());
	if(license==""){
		alert("请输入车牌号");
		return false;
	}
	license=license.toUpperCase();
	$(".plate").val(license);
	$(".dropdown").hide();
	$(".all").hide();
	$(".arrow1").removeClass("chick-arrow");
	$(".car-search").submit();
});

$("#page.rental_car.searchlicense").bind("click",function(e){
	var target = $(e.target);
	if(target.closest(".dropdown").length == 0 && target.closest(".licensePlace").length == 0){
		$(".dropdown").hide();
		$(".all").hide();
		$(".arrow1").removeClass("chick-arrow");
	}

});


$("#page.rental_car.edit").delegate(".submit","click",function(){
	var input=$("input");
	for(var i=0;i<$(input).length;i++){
		if($.trim($(input[i]).val())==""){
			alert("请填写全部数据,再提交！");
			return false;
		}
	}
	$("form").submit();
});
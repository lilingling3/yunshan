$("#page.order.show .change-s").on("click",function(){
    $("#change-station").submit();
});

var order= $("#page.order.show #orderID").val();
if(sessionStorage .getItem('sharecar'+order)==1){
    $("#page.order.show .key-notice").show();
    $(".shadow-layer").show();
    sessionStorage .setItem('sharecar'+order, 2);
}


$("#page.order.show").delegate(".key-notice .btn","click",function(){
    setTimeout(function(){
        $(".key-notice").hide();
        $(".shadow-layer").hide();
    },500);

});

$("#page.order.show").delegate(".journey .key-cont","click",function(){
    $(".key-notice").css("display","block");
    $(".shadow-layer").css("display","block");
});

$("#page.order.show").delegate(".opencar","click",function(){
    var order =$("#orderID").val();
    var member =$("#userID").val();
    if($("#useTime").val()){
        $("#page.order.show .shadow").show();

        $.post("/api/order/lock",
            {
                userID:member,
                orderID:order,
                status:0
            },
            function(data,status){
                console.log(status);
                if(status){
                    if (data.errorCode == 0||data.errorCode == -90001 ) {
                        if(data.errorCode == 0){
                            alert('开门成功');
                            if (!sessionStorage.getItem('sharecar' + order)) {
                                sessionStorage.setItem('sharecar' + order, 1);
                            }
                        }
                        window.location.reload();
                    }

                    else{

                        alert(data.errorMessage);
                        $("#page.order.show .shadow").hide();
                    }

                }

            });
    }
 else {

        var r = confirm("开门取车后将不能取消订单!");

        if (r == true) {

            $("#page.order.show .shadow").show();

            $.post("/api/order/lock",
                {
                    userID: member,
                    orderID: order,
                    status: 0
                },
                function (data, status) {
                    console.log(status);
                    if (status) {
                        if (data.errorCode == 0||data.errorCode == -90001 ) {
                            if(data.errorCode == 0){
                                alert('开门成功');
                                if (!sessionStorage.getItem('sharecar' + order)) {
                                    sessionStorage.setItem('sharecar' + order, 1);
                                }
                            }
                            window.location.reload();
                        }

                        else {

                            alert(data.errorMessage);
                            $("#page.order.show .shadow").hide();
                        }

                    }

                });
            return false;
        }
        else {
            return false;
        }
    }
});


/**
 * lock car
 */
$("#page.order.show").delegate(".lockcar","click",function(){

    var order  =$("#orderID").val();
    var member =$("#userID").val();
    $("#page.order.show .shadow").show();
    $.post("/api/order/lock",
        {
            userID:member,
            orderID:order,
            status:1
        },
        function(data,status){
            if(status){

                if(data.errorCode==0){

                    alert('锁门成功');
                    $("#page.order.show .shadow").hide();

                }else{

                    alert(data.errorMessage+ "|" +data.errorCode);
                    $("#page.order.show .shadow").hide();
                }

            }

        });
});

$("#page.order.show").delegate(".findcar","click",function(){

    var order =$("#orderID").val();
    var member =$("#userID").val();
    $("#page.order.show .shadow").show();
    $.post("/api/order/find",
        {
            userID:member,
            orderID:order
        },
        function(data,status){
            if(status){

                if(data.errorCode==0){

                    alert('找车成功');
                    $("#page.order.show .shadow").hide();

                }else{

                    alert(data.errorMessage);
                    $("#page.order.show .shadow").hide();
                }

            }

        });


});

$("#page.order.show").delegate(".cancelcar","click",function(){
    var order =$("#orderID").val();
    var user =$("#userID").val();

    var orderFreeCancelCount,offset,alert_str;
    //获取用户取消了多少次
    $.post("/api/account/user",
        {
            userID:user
        },
        function(data,status){

            if(status){
                if(data.errorCode==0){
                    orderFreeCancelCount=data["user"]["orderFreeCancelCount"];

                    $.post("/api/order/getRentOrder",
                        {
                            userID:user,
                            orderID:order
                        },
                        function(data,status){

                            if(status){
                                if(data.errorCode==0){
                                    offset=data["order"]["offset"];
                                    if(offset<900 && orderFreeCancelCount<2){
                                        var ordernum =2-orderFreeCancelCount;
                                        alert_str="今天还有"+ordernum+"次免费取消机会,是否确认取消行程 ?";
                                    }
                                    else if(offset<900 && orderFreeCancelCount>=2){
                                        alert_str="已使用完每天免费取消机会,取消行程将收取用车费用,是否确定取消行程?";
                                    }
                                    else if(offset>=900){
                                        alert_str="已超出免费取消时间范围,取消行程将收取租赁费用,是否确定取消行程?";
                                    }
                                    var r=confirm(alert_str);

                                    if (r==true)
                                    {
                                        $.post("/api/order/cancel_15",
                                            {
                                                userID:user,
                                                orderID:order
                                            },
                                            function(data,status){

                                                if(status){

                                                    if(data.errorCode==0 || data.errorCode==-50008 || data.errorCode==-50003|| data.errorCode==-50013){

                                                        var timestamp = Date.parse(new Date());
                                                        window.location.href=window.location.href+"?date="+timestamp;
                                                        window.location.reload;

                                                        /* return false;*/
                                                    }else{

                                                        alert(data.errorMessage);
                                                        location.reload();
                                                    }
                                                }
                                            });
                                    }
                                    else
                                    {
                                        return false;
                                    }

                                }else{

                                    alert(data.errorMessage);
                                    location.reload();
                                }
                            }
                        });


                }else{

                    alert(data.errorMessage);
                    location.reload();
                }
            }
        });



});

if(window.location.pathname=="/mobile/order/rental/list") {
    var orderpage = 2;
    $("#page.order.list").delegate(".moreorderbtn", "click", function () {

        var member = $(".orderlist").attr("user");

        $.post("/api/order/list",
            {
                userID: member,
                page: orderpage
            },
            function (data, status) {
                if (status) {
                    if (data.errorCode == 0) {

                        addorders(data["orders"]);

                        if (orderpage >= data["pageCount"]) {
                            $(".moreorderbtn").addClass("hide");
                        }
                        orderpage++;
                    } else {
                        alert(data.errorMessage);
                    }
                }
            });
    });


    function addorders(orders) {
        var list = (".orderlist");

        for (var i = 0; i < orders.length; i++) {
            var order = createorder(orders[i]);
            $(order).appendTo($(list));
        }
    }


    function createorder(order) {

        var a = $("<a class='/mobile/order/rental/show/" + order['orderID'] + "'></a>");
        var order_li = $("<li class='order-li status" + order["status"] + "'></li>");
        $(order_li).appendTo($(a));

        var litop = $("<div class='row border-bottom'><p class='b'>" + order["createTime"] + "<span class='status-top1>查看</span></p></div>");
        $(litop).appendTo($(order_li));

        var row = $("<div class='row'></div>");
        $(row).appendTo($(order_li));

        var column = $("<div class='column column-info'></div>");
        $(column).appendTo($(row));

        var item1 = $("<div class='item'><p>车牌</p>" + order["rentalCar"]["license"] + "</div>");
        $(item1).appendTo($(column));

        var item2 = $("<div class='item'><p>型号</p>" + order["rentalCar"]["car"]["name"] + "</div>");
        $(item2).appendTo($(column));

        var item3 = $("<div class='item'></div>");
        var statusstr = "<p>状态</p>";
        if (order["cancelTime"]) {
            statusstr += "<span class='status-top" + order["status"] + "'>已取消</span>";
        }
        else if (order["payTime"]) {
            statusstr += "<span class='status-top" + order["status"] + "'>已完成</span>";
        }
        else if (order["endTime"]) {
            statusstr += "<span class='status-top" + order["status"] + "'>待支付</span>";
        }
        else if (order["useTime"]) {
            statusstr += "<span class='status-top" + order["status"] + "'>未取车</span>";
        }


        $(item3).html(statusstr);

        $(item3).appendTo($(column));

        return a;

    }

}


$("#page.order.back").delegate(".back-notice .btn","click",function(){

    $("#page.order.back .shadow").show();
    $("#orderinfo").submit();
});

/*待付款*/


var paycouponpage= 1,paycouponpagecount,orderCost,maxWalletAmount,orderCouponAmount,unusecouponflag= 1;

$(function(){
    orderCost=parseFloat($("#orderCost").val());
    maxWalletAmount=parseFloat($("#maxWalletAmount").val());
    orderCouponAmount=parseFloat($("#couponID").attr("amount"));
    changecoupondata($("#couponID").val(),orderCouponAmount);
});

$("#page.order.show").delegate(".wcontbtn","click",function(){
    $("#page.order.show .pay-cont .paybtn").unbind("click",orderpay);
    $(".wconticon").toggleClass("check");
    if($(".nousewcont").hasClass(("hide"))){
        $(".usewcont").addClass("hide");
        $(".nousewcont").removeClass("hide");
        maxWalletAmount=0;
        $("#useWalletAmount").val(0);
        changecoupondata($("#couponID").val(),orderCouponAmount);
    }
    else {
        $(".usewcont").removeClass("hide");
        $(".nousewcont").addClass("hide");
        maxWalletAmount=parseFloat($("#maxWalletAmount").val());
        changecoupondata($("#couponID").val(),orderCouponAmount);
    }
});

$("#page.order.show").delegate(".couponbtn","click",function(){
    $("#page.order.show .pay-cont .paybtn").unbind("click",orderpay);
    orderCouponFlag=1
    $(".couponicon").toggleClass("check");
    if($(".nousecoupon").hasClass(("hide"))){
        $(".usecoupon").addClass("hide");
        $(".nousecoupon").removeClass("hide");
        unusecouponflag=0;
        changecoupondata(0,0);
    }
    else {
        $(".usecoupon").removeClass("hide");
        $(".nousecoupon").addClass("hide");
        unusecouponflag=1;
        changecoupondata($("#oldcoupon").val(),$("#oldcoupon").attr("amount"));
    }

});


$("#page.order.show").delegate(".price .changecoupon","click",function(){
    $("#page.order.show .pay-cont .paybtn").unbind("click",orderpay);
    paycouponpage=1;
    paycouponpagecount=1;

    $(".couponlist .lists").empty();
    var member=$("#userID").val();
    var order=$("#orderID").val();
    $(".couponlist").addClass("show");
    $("body").addClass("overhide");
    $(".divlayer").show();
    getcouponPost(member,order);
});

$("#page.order.show").delegate(".couponlist .unusecoupon","click",function(){
    $(".couponlist").removeClass("show");
    $("body").removeClass("overhide");
    $(".divlayer").hide();
    $(".couponbtn").removeClass("check");
    $(".usecoupon").addClass("hide");
    $(".nousecoupon").removeClass("hide");
    unusecouponflag=0;//不使用优惠券
    changecoupondata(0,0);
});

$("#page.order.show").delegate(".couponlist .close","click",function(){
    $(".couponlist").removeClass("show");
    $("body").removeClass("overhide");
    $(".divlayer").hide();
    $("#page.order.show .car-button .pay").unbind("click",orderpay);
    $("#page.order.show .car-button .pay").bind("click",orderpay);
});

function getcouponPost(member,order){

    $.post("/api/coupon/list",
        {
            userID:member,
            orderID:order,
            page:paycouponpage
        },
        function(data,status){
            if(status){

                if(data.errorCode==0){
                    addCouponList(data["coupons"]);
                    paycouponpagecount=data["pageCount"];
                    if(paycouponpagecount==1|| paycouponpage==paycouponpagecount){
                        $(".couponlist .addmorecoupon").hide();
                    }
                    else {
                        paycouponpage++;
                    }

                }else{

                    alert(data.errorMessage);

                }

            }

        });

}

function addCouponList(coupons){
    if(coupons.length>0){
        $(".couponlist").addClass("show");
        $("body").addClass("overhide");
        $(".divlayer").show();
    }
    for(var i=0;i<coupons.length;i++){
        if(coupons[i]["orderValid"]==501){
            var coupon=createCoupon(coupons[i]);
            $(coupon).appendTo($(".couponlist .lists"));
            $(coupon).bind("click",clickcoupon);
        }
    }
}

function createCoupon(coupon) {
    var section = $("<section></section>");
    $(section).attr("couponID", coupon["couponID"]);
    $(section).attr("couponAmount", coupon["amount"]);
    var row = $("<div class='row'></div>");

    $(row).appendTo($(section));

    var coupon_cont = $("<div class='coupon-cont'></div>");
    $(coupon_cont).appendTo($(row));
    var incont = $("<div class='incont'></div>");
    var yen = "<span class='amount'>" + coupon["amount"] + "</span>";
    $(incont).html(yen);

    $(incont).appendTo($(coupon_cont));
    var text = $("<div class='coup-text'></div>");
    $(text).appendTo($(row));

    var  carLevel;
    if (coupon.carLevel.length>0) {
        carLevel = coupon.carLevel;
    } else {
        carLevel = "全部车型";
    }

    if (coupon["needHour"] ==0 && coupon["needAmount"] ==0 && coupon["carLevel"] ==0) {

        var p = "<p>" + coupon["name"] + "</p><p class='date'>有效期至" + coupon["endTime"] + "</p><p class='needhour'>无使用规则</p>";

    } else {

        if (coupon["needHour"] >0 && coupon["needAmount"] >0) {
            var p = "<p>" + coupon["name"] + "</p><p class='date'>有效期至" + coupon["endTime"] + "</p><p class='needhour'>规则:满" + coupon["needHour"] + "小时 |"+coupon.needAmount+"元以上 | "+carLevel+"</p>";

        } else if(coupon["needHour"] >0) {
            var p = "<p>" + coupon["name"] + "</p><p class='date'>有效期至" + coupon["endTime"] + "</p><p class='needhour'>规则:满" + coupon["needHour"] + "小时 |"+carLevel+"</p>";

        } else if(coupon["needAmount"] >0) {
            var p = "<p>" + coupon["name"] + "</p><p class='date'>有效期至" + coupon["endTime"] + "</p><p class='needhour'>规则:"+coupon.needAmount+"元以上 | "+carLevel+"</p>";

        } else {
            var p = "<p>" + coupon["name"] + "</p><p class='date'>有效期至" + coupon["endTime"] + "</p><p class='needhour'>规则:"+carLevel+"</p>";
        }
    }

    $(text).html(p);

    var cheched="<div class='cheched'><span></span></div>";
    if(coupon["couponID"]==$("#couponID").val()){
        $(section).appendTo(section);
    }

    return section;

}

function clickcoupon(couponDiv){
    unusecouponflag=1;
    var couponID=$(this).attr("couponid");
    var couponAmount=$(this).attr("couponAmount");

    $(".couponlist").removeClass("show");
    $("body").removeClass("overhide");
    $(".divlayer").hide();
    changecoupondata(couponID,couponAmount);

}
/*计算余额，需支付钱数*/
function changecoupondata(couponID,couponAmount){
    $("#page.order.show .pay-cont .paybtn").unbind("click",orderpay);

    if((!$(".nousecoupon").hasClass("nomaxcoupon"))&& unusecouponflag ==1){

        $(".usecoupon").removeClass("hide");
        $(".nousecoupon").addClass("hide");
    }


    $("#couponID").val(couponID);
    $("#couponID").attr("amount",couponAmount);
    if(couponAmount>0){
        $("#oldcoupon").val(couponID);
        $("#oldcoupon").attr("amount",couponAmount);
    }

    $(".couponnum").text(couponAmount);
    orderCouponAmount=parseFloat(couponAmount);
    var walletAmountCont=$(".walletAmountCont");
    var usrCouponAmount=$(".usecoupon");
    var needpayamount=$(".needpayamount");
    var ttt=maxWalletAmount+orderCouponAmount

    if(orderCouponAmount>=orderCost){

        $(needpayamount).text("0");
        $(walletAmountCont).text("0");
        $("#useWalletAmount").val(0);

    }
    else if((maxWalletAmount+orderCouponAmount)>=orderCost){

        $(walletAmountCont).text(accSub(orderCost,orderCouponAmount));
        $("#useWalletAmount").val(accSub(orderCost,orderCouponAmount));
        $(needpayamount).text("0");
    }
    else if((maxWalletAmount+orderCouponAmount)< orderCost){

        $(walletAmountCont).text(maxWalletAmount);
        $("#useWalletAmount").val(maxWalletAmount);
        var amount=accAdd(maxWalletAmount,orderCouponAmount);
        $(needpayamount).text(accSub(orderCost,amount));
    }

    $("#page.order.show .pay-cont .paybtn").bind("click",orderpay);
}
  var orderPayFlag = 0;
var rental_order_pay_location_ref=/^\/mobile\/order\/rental\/show/;
if(rental_order_pay_location_ref.test(window.location.pathname)) {
    $(function () {
        pushHistory();
        window.addEventListener("popstate", function (e) {
            // alert("我监听到了浏览器的返回按钮事件啦");//根据自己的需求实现自己的功能
            window.location.href = "/mobile/order/rental/list";
        }, false);

    });
}

var rental_order_list_location_ref=/^\/mobile\/order\/rental\/list/;
if(rental_order_list_location_ref.test(window.location.pathname)) {
    $(function () {
        pushHistory();
        window.addEventListener("popstate", function(e) {
            // alert("我监听到了浏览器的返回按钮事件啦");//根据自己的需求实现自己的功能
            window.location.href = "/mobile/index";
        }, false);

});
}
function pushHistory() {
    var state = {
        title: "title",
        url: "/mobile/order/rental/list"
    };
    window.history.pushState(state, "", "#");
}
function orderpay(){
    console.log("pay begin-----------------------");
    if(orderPayFlag==0){
        orderPayFlag++;
    }else {
        return;
    }
    var member=$("#userID").val();
    var order=$("#orderID").val();
    var coupon=$("#couponID").val();
    var wallet=$("#useWalletAmount").val();
    $("#page.order.show .pay-cont .paybtn").unbind("click",orderpay);
    $.post("/api/order/rentOrderPay",
        {
            userID:member,
            orderID:order,
            couponID:coupon,
            wallet:wallet
        },
        function(data,status){
            console.log("pay rentOrderPay-----------------------",data);
            if(status){
                if(data.errorCode==0){
                    location.href = $("#orderpayform").attr("action")+"?coupon="+$("#couponID").val()+"&wallet="+$("#useWalletAmount").val()
                   // $("#orderpayform").submit();
                }else{
                    alert(data.errorMessage);
                    orderPayFlag--;
                    $("#page.order.show .pay-cont .paybtn").bind("click",orderpay);
                }

            }

        });
}

function accSub(arg1, arg2) {
    var r1, r2, m, n;
    try {
        r1 = arg1.toString().split(".")[1].length;
    }
    catch (e) {
        r1 = 0;
    }
    try {
        r2 = arg2.toString().split(".")[1].length;
    }
    catch (e) {
        r2 = 0;
    }
    m = Math.pow(10, Math.max(r1, r2)); //last modify by deeka //动态控制精度长度
    n = (r1 >= r2) ? r1 : r2;
    return ((arg1 * m - arg2 * m) / m).toFixed(n);
}
Number.prototype.sub = function (arg) {
    return accMul(arg, this);
};

// 给Number类型增加一个mul方法，调用起来更加方便。

function accAdd(arg1, arg2) {
    var r1, r2, m, c;
    try {
        r1 = arg1.toString().split(".")[1].length;
    }
    catch (e) {
        r1 = 0;
    }
    try {
        r2 = arg2.toString().split(".")[1].length;
    }
    catch (e) {
        r2 = 0;
    }
    c = Math.abs(r1 - r2);
    m = Math.pow(10, Math.max(r1, r2));
    if (c > 0) {
        var cm = Math.pow(10, c);
        if (r1 > r2) {
            arg1 = Number(arg1.toString().replace(".", ""));
            arg2 = Number(arg2.toString().replace(".", "")) * cm;
        } else {
            arg1 = Number(arg1.toString().replace(".", "")) * cm;
            arg2 = Number(arg2.toString().replace(".", ""));
        }
    } else {
        arg1 = Number(arg1.toString().replace(".", ""));
        arg2 = Number(arg2.toString().replace(".", ""));
    }
    return (arg1 + arg2) / m;
}

Number.prototype.add = function (arg) {
    return accAdd(arg, this);
};


/*待付款结束*/
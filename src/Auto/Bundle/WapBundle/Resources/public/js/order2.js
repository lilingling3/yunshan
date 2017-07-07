
var order2= $("#page.order2.show #orderID").val();
if(sessionStorage .getItem('sharecar'+order2)==1){
    $("#page.order2.show .key-notice").show();
    $(".shadow-layer").show();

    sessionStorage .setItem('sharecar'+order2, 2);
}



$("#page.order2.show").delegate(".opencar","click",function(){
    var order =$("#orderID").val();
    var member =$("#userID").val();
    if($("#useTime").val()){
        $("#page.order2.show .shadow").show();

        $.post("/api/order/lock",
            {
                userID:member,
                orderID:order,
                status:0
            },
            function(data,status){
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
                        $("#page.order2.show .shadow").hide();
                    }

                }

            });
    }
    else {
        var r = confirm("开门取车后将不能取消订单!");

        if (r == true) {

            $("#page.order2.show .shadow").show();

            $.post("/api/order/lock",
                {
                    userID: member,
                    orderID: order,
                    status: 0
                },
                function (data, status) {
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
                            $("#page.order2.show .shadow").hide();
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



$("#page.order2.show").delegate(".findcar","click",function(){

    var order =$("#orderID").val();
    var member =$("#userID").val();
    $("#page.order2.show .shadow").show();
    $.post("/api/order/find",
        {
            userID:member,
            orderID:order
        },
        function(data,status){
            if(status){

                if(data.errorCode==0){

                    alert('找车成功');
                    $("#page.order2.show .shadow").hide();

                }else{

                    alert(data.errorMessage);
                    $("#page.order2.show .shadow").hide();
                }

            }

        });


});



$("#page.order2.back").delegate(".back-notice button","click",function(){

    $("#page.order2.back .shadow").show();
    $(this).parents('form').submit();


});

$("#page.order2.show").delegate(".key-notice button","click",function(){
    $(".key-notice").hide();
    $(".shadow-layer").hide();
});


$("#page.order2.show").delegate(".introduction","click",function(){
    $(".key-notice").show();
    $(".shadow-layer").show();
});

$("#page.order2.show").delegate(".cancel","click",function(){
    var order =$("#orderID").val();
    var user =$("#userID").val();

    var orderFreeCancelCount,offset,alert_str,r;
    //获取用户取消了多少次
    $.ajax({
        type: "get",
        url: "/api/account/user",
        async:true,
        data:JSON.stringify({userID:user}),
        dataType: "json",
        error: function(){
            alert('Error loading XML document');
        },
        success: function(data,status){//如果调用php成功
            if(status) {

                if (data.errorCode == 0) {
                    console.log("/api/account/user");
                    console.log(data);
                    orderFreeCancelCount=data["user"]["orderFreeCancelCount"];
                }
                else {
                    alert(data.errorMessage);
                }
            }
        }
    });


    $.ajax({
        type: "POST",
        url: "/api/order/getRentOrder",
        async:true,
        data:JSON.stringify({userID:user,orderID:order}),
        dataType: "json",
        error: function(){
            alert('Error loading XML document');
        },
        success: function(data,status){//如果调用php成功
            if(status) {
                console.log("/api/order/getRentOrder");
                console.log(data);
                if (data.errorCode == 0) {
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
                    console.log(alert_str);
                    r=confirm(alert_str);
                }
                else{
                    alert(data.errorMessage);
                }
            }
        }
    });


    if (r==true){
        $.ajax({
            type: "POST",
            url: "/api/order/cancel_15",
            async:true,
            data:JSON.stringify({userID:user,orderID:order}),
            dataType: "json",
            error: function(){
                alert('Error loading XML document');
            },
            success: function(data,status){//如果调用php成功
                if(status){
                    if(data.errorCode==0 || data.errorCode==-50008 || data.errorCode==-50003|| data.errorCode==-50013){
                        var timestamp = Date.parse(new Date());
                        window.location.href=window.location.href+"?date="+timestamp;
                        window.location.reload;
                    }else{
                        alert(data.errorMessage);
                        location.reload();
                    }
                }
            }
        });
    }


});

$("#page.order2.show").delegate(".rental-info .address-chs","click",function(){
    $("#change-station").submit();
});

var paycouponpage= 1,paycouponpagecount,orderCost,maxWalletAmount,orderCouponAmount,unusecouponflag=1;

$(function(){
    orderCost=parseFloat($("#orderCost").val());
    maxWalletAmount=parseFloat($("#maxWalletAmount").val());
    orderCouponAmount=parseFloat($("#couponID").attr("amount"));
    changecoupondata($("#couponID").val(),orderCouponAmount);
});
$("#page.order2.show").delegate(".wcont","click",function(){
    $("#page.order2.show .car-button .pay").unbind("click",orderpay);
    $(this).toggleClass("dontuse");
    $(this).find(".wbtn").toggleClass("right");
    if($(this).hasClass(("dontuse"))){
        maxWalletAmount=0;
        $("#useWalletAmount").val(0);
        changecoupondata($("#couponID").val(),orderCouponAmount);
    }
    else {
        maxWalletAmount=parseFloat($("#maxWalletAmount").val());
        changecoupondata($("#couponID").val(),orderCouponAmount);
    }
});



$("#page.order2.show").delegate(".rental-coupon .change-coupon","click",function(){
    $("#page.order2.show .car-button .pay").unbind("click",orderpay);
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


$("#page.order2.show").delegate(".couponlist .addmorecoupon","click",function(){
    var member=$("#userID").val();
    var order=$("#orderID").val();

        getcouponPost(member, order);

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
    var yen = "&yen;<span class='amount'>" + coupon["amount"] + "</span>";
    $(incont).html(yen);

    $(incont).appendTo($(coupon_cont));
    var text = $("<div class='coup-text'></div>");
    $(text).appendTo($(row));
    if (coupon["needHour"] > 0) {

    var p = "<p>" + coupon["name"] + "</p><p class='date'>有效期至" + coupon["endTime"] + "</p><p class='needhour'>满" + coupon["needHour"] + "小时可用</p>";
    $(text).html(p);
    }
    else {
        var p = "<p>" + coupon["name"] + "</p><p class='date'>有效期至" + coupon["endTime"] + "</p><p class='needhour'>无使用条件</p>";
        $(text).html(p);
    }



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

function changecoupondata(couponID,couponAmount){
    $("#page.order2.show .car-button .pay").unbind("click",orderpay);
    if(unusecouponflag==1){
        $(".rental-coupon .use-coupon .use-coupon-amount-t").text("元");
        $(".rental-coupon .use-coupon .use-coupon-amount-t").addClass("red");
        $(".use-coupon-amount").show();
    }

    $("#couponID").val(couponID);
    $("#couponID").attr("amount",couponAmount);
    $(".use-coupon-amount").text(couponAmount);
    orderCouponAmount=parseFloat(couponAmount);
    var walletAmountCont=$(".walletAmount");
    var usrCouponAmount=$(".use-coupon-amount");
    var needpayamount=$(".needpayamount");
    var ttt=maxWalletAmount+orderCouponAmount

    if(orderCouponAmount>=orderCost){

        $(needpayamount).text("0");
        $(walletAmountCont).text("0");
        $("#useWalletAmount").val(0);
    }
    else if((maxWalletAmount+orderCouponAmount)>=orderCost){

        $(walletAmountCont).text(orderCost-orderCouponAmount);
        $("#useWalletAmount").val(orderCost-orderCouponAmount);
        $(needpayamount).text("0");
    }
    else if((maxWalletAmount+orderCouponAmount)< orderCost){

        $(walletAmountCont).text(maxWalletAmount);
        $("#useWalletAmount").val(maxWalletAmount);
        $(needpayamount).text(orderCost-(maxWalletAmount+orderCouponAmount));
    }

    $("#page.order2.show .car-button .pay").bind("click",orderpay);
}


$("#page.order2.show").delegate(".couponlist .unusecoupon","click",function(){
    $(".couponlist").removeClass("show");
    $("body").removeClass("overhide");
    $(".divlayer").hide();
    changecoupondata(0,0);
    $(".use-coupon-amount").text("");
    $(".rental-coupon .use-coupon .use-coupon-amount-t").removeClass("red");
    $(".rental-coupon .use-coupon .use-coupon-amount-t").text("不使用");

    $(".use-coupon-amount").hide();
    unusecouponflag=0;
});


$("#page.order2.show").delegate(".couponlist .close","click",function(){
    $(".couponlist").removeClass("show");
    $("body").removeClass("overhide");
    $(".divlayer").hide();
    $("#page.order2.show .car-button .pay").unbind("click",orderpay);
    $("#page.order2.show .car-button .pay").bind("click",orderpay);
});




$("#page.order2.show .car-button .pay").bind("click",orderpay);



function orderpay(){
    var member=$("#userID").val();
    var order=$("#orderID").val();
    var coupon=$("#couponID").val();
    var wallet=$("#useWalletAmount").val();

    $.post("/api/order/rentOrderPay",
        {
            userID:member,
            orderID:order,
            couponID:coupon,
            wallet:wallet
        },
        function(data,status){
            if(status){
                if(data.errorCode==0){

                    $("#orderpayform").submit();

                }else{

                    alert(data.errorMessage);

                }

            }

        });
}

var orderpage=2;


$("#page.order2.list").delegate(".moreorderbtn","click",function(){

    var member=$(".orderlist").attr("user");

    $.post("/api/order/list",
        {
            userID:member,
            page:orderpage
        },
        function(data,status){
            if(status){
                if(data.errorCode==0){

                    addorders(data["orders"]);

                    if(orderpage>=data["pageCount"]){
                        $(".moreorderbtn").addClass("hide");
                    }
                    orderpage++;
                }else{
                    alert(data.errorMessage);
                }
            }
        });
});


function addorders(orders){
    var list=(".orderlist");

    for(var i=0;i<orders.length;i++){
        var order=createorder(orders[i]);
        $(order).appendTo($(list));
    }
}


function createorder(order){

    var a = $("<a class='/wap/order2/rental/show/"+order['orderID']+"'></a>");
    var order_li=$("<li class='order-li status"+order["status"]+"'></li>");
    $(order_li).appendTo($(a));

    var litop=$("<div class='row border-bottom'><p class='b'>"+order["createTime"]+"<span class='status-top1>查看</span></p></div>");
    $(litop).appendTo($(order_li));

    var row=$("<div class='row'></div>");
    $(row).appendTo($(order_li));

    var column=$("<div class='column column-info'></div>");
    $(column).appendTo($(row));

    var item1=$("<div class='item'><p>车牌</p>"+order["rentalCar"]["license"]+"</div>");
    $(item1).appendTo($(column));

    var item2=$("<div class='item'><p>型号</p>"+order["rentalCar"]["car"]["name"]+"</div>");
    $(item2).appendTo($(column));

    var item3=$("<div class='item'></div>");
    var statusstr="<p>状态</p>";
    if(order["cancelTime"]){
        statusstr+="<span class='status-top"+order["status"]+"'>已取消</span>";
    }
    else if(order["payTime"]){
        statusstr+="<span class='status-top"+order["status"]+"'>已完成</span>";
    }
    else if(order["endTime"]){
        statusstr+="<span class='status-top"+order["status"]+"'>待支付</span>";
    }
    else if(order["useTime"]){
        statusstr+="<span class='status-top"+order["status"]+"'>未取车</span>";
    }


    $(item3).html(statusstr);

    $(item3).appendTo($(column));

    return a;

}
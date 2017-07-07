$("#page.rental_coupon.coupon .coupon-code").delegate(".button","click",function(){
    var code=$("#page.rental_coupon.coupon .coupon-code .code").val();
    code=code.trim();
    if(code==""){
        alert("请输入兑换码！");
    }
    else{
      $("#page.rental_coupon.coupon .coupon-code .code-inner form").submit();
    }
});

var car=function(){

    var t=([].shift.apply(arguments)/1000);
    var oil= Math.round(t*0.08*100)/100;
    var time =t*6;
    var myTime = ordresharetime(time);
    var tree=Math.round(t*0.01*100)/100;
    return {"oil":oil,"time":myTime,"tree":tree}
}
$(function(){
    var ordersharenum=car($(".rental_coupon.app_share_order #mileage").val());
    $(".rental_coupon.app_share_order .oil-num").text(ordersharenum["oil"]+"升");
    $(".rental_coupon.app_share_order .sun-num").text(ordersharenum["time"]);
    $(".rental_coupon.app_share_order .tree-num").text(ordersharenum["tree"]+"棵");

    $(".rental_coupon.app_share_order .btn").click(function(){
        var mobile=$.trim($(".input-cont input").val());
        var re = /^1[3|4|5|7|8]\d{9}$/;

        if(mobile==""){
            $(".error").text("请输入手机号");
            $(".error").addClass("show");
        }

        if (!re.test(mobile)) {
            $(".error").text("手机号有误");
            $(".error").addClass("show");
        }
        else{
            $(".error").removeClass("show");
            loginanimationend();
        }
    });



    $(".rental_coupon.app_share_order .login .phone input").bind('focus', function(){
        $(".login .warn").text("");
    });
    function loginanimationend(){
        var pnone= $.trim($(".input-cont input").val());;
        var aid=$("#aid").val();
        var orderID=$("#orderID").val();
        if($.trim(pnone)==""){
            $(".login .warn").text("请输入手机号");
        }
        if ($.checkMobile(pnone)) {
            $.post("/api/coupon/get/orderShare",
                {
                    mobile:pnone,
                    couponActivityID:aid,
                    orderID:orderID
                },
                function(data,status){
                    if(status){
                        var str="";
                        if(data.errorCode==0){

                            alert("领取成功，恭喜您获得"+data.couponAmount+"元优惠券");

                        }
                        //优惠券已被领光
                        if(data.errorCode=='-70007'){
                            alert("抱歉啊~您来晚了，优惠券已经领光了");
                        }
                        if(data.errorCode=='-70005'){
                            alert("您已经领过了，快去账户查看吧");

                        }

                    }

                });
            return true;
        } else {
            $(".error").text("手机号有误，请重新输入");
            return false;
        }

    }
});


function ordresharetime(t){
    var time=t;
    var day,hour,minute,second;
    var str="";
    if((time/86400)>1){
        day=parseInt(time/86400);
        str=day+"天";
        time=time%86400;
    }
    if((time/3600)>1){
        hour=parseInt(time/3600);
        str+=hour+"时";
        time=time%3600;
    }
    if((time/60)>1){
        minute=parseInt(time/60);
        str+=minute+"分";
        time=time%60;
    }
    if(time){
        str+=Math.round(time*100)/100+"秒";
    }
    if(str==""){
        str=0+"秒";
    }
    return str;
}


$(".rental_coupon.rental_coupon.share_app").delegate(".button","click",function(){
    var mobile= $.trim($("#phone").val());

    var uid= $("#uid").val();
    var aid=$("#aid").val();
    if(mobile==""){
        alert("请输入手机号！");
        return false;
    }
    if(!$.checkMobile(mobile)){
        alert('手机号错误!');
        return false;
    }
    $.post("/api/coupon/get/appShare",
        {
            mobile:mobile,
            couponActivityID:aid,
            userID:uid
        },
        function(data,status){
            if(status){
                var str="";
                console.log(data);
                if(data.errorCode==0){
                    $(".coupun .light").show();
                    str="<p>领取成功</p><p>30元优惠券已放入你账户</p><p>快快加入绿色出行吧</p>";
                    $(".text").html(str);
                    $(".row-down").show();
                    $(".row-down").siblings(".row").hide();

                }
                else if(data.errorCode==-70008){
                    alert("抱歉老用户无法领取");
                }
                else if(data.errorCode==-70005){
                    alert("您已领取");
                }
               else{
                    alert(data.errorMessage);
                }

            }

        });
});
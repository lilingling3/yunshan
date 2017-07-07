

var car=function(){

    var t=([].shift.apply(arguments)/1000);
    var oil= Math.round(t*0.08*100)/100;
    var time =t*6;
    var myTime = ordresharetime(time);
    var tree=Math.round(t*0.01*100)/100;
    return {"oil":oil,"time":myTime,"tree":tree}
}

var check={
    mobile:function(str){
        var re = /^1[3|4|5|7|8]\d{9}$/;

        if (re.test(str)) {
            return true;
        } else {
            return false;
        }
    }
};
$(function(){
    var shareordermileage=$("#mileage").val()*1000;
    var ordersharenum=car(shareordermileage);
    $(".oil-num").text(ordersharenum["oil"]+"升");
    $(".sun-num").text(ordersharenum["time"]);
    $(".tree-num").text(ordersharenum["tree"]+"棵");

    $(".btn").bind("tap",shareOrder);


    function shareOrder(){
        $(".btn").unbind("tap",shareOrder);
        var mobile=$.trim($(".input-cont input").val());
        var re = /^1[3|4|5|7|8]\d{9}$/;

        if(mobile==""){
            $(".error").text("请输入手机号");
            $(".error").addClass("show");
            $(".btn").bind("tap",shareOrder);
        }

        if (!re.test(mobile)) {
            $(".error").text("手机号有误");
            $(".error").addClass("show");
            $(".btn").bind("tap",shareOrder);
        }
        else{
            $(".error").removeClass("show");
            loginanimationend();
        }
    }


    $(".login .phone input").bind('focus', function(){
        $(".login .warn").text("");
    });
    function loginanimationend(){
        var pnone= $.trim($(".input-cont input").val());
        var aid=$("#aid").val();
        var orderID=$("#orderID").val();
        if($.trim(pnone)==""){
            $(".login .warn").text("请输入手机号");
        }
        if (check.mobile(pnone)) {
            $.post("/api/coupon/get/orderShare",
                {
                    mobile:pnone,
                    couponActivityID:aid,
                    orderID:orderID
                },
                function(data,status){
                    if(status){
                        var str="";
                        console.log(data);
                        data=JSON.parse(data);
                        if(data.errorCode==0){

                            alert("领取成功，恭喜您获得"+data.couponAmount+"元优惠券");
                            $(".btn").bind("tap",shareOrder);
                        }
                        //优惠券已被领光
                        else if(data.errorCode=='-70007'){
                            alert("抱歉啊~您来晚了，优惠券已经领光了");
                            $(".btn").bind("tap",shareOrder);
                        }
                        else if(data.errorCode=='-70005'){
                            alert("您已经领过了，快去账户查看吧");
                            $(".btn").bind("tap",shareOrder);

                        }
                        else {
                            alert(data.errorMessage);
                            $(".btn").bind("tap",shareOrder);
                        }

                    }

                });
            return true;
        } else {
            $(".error").text("手机号有误，请重新输入");
            $(".btn").bind("tap",shareOrder);
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


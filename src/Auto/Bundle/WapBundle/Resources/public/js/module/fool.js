
$(function(){
    $(".activity.fool_day input").focus(function(){
        $(".activity.fool_day .cont").css("top","-15%");
    });

    $(".activity.fool_day input").bind("blur",function(){
        $(".activity.fool_day .cont").css("top","34%");
    });


    var isIPHONE = navigator.userAgent.toUpperCase().indexOf('IPHONE')!= -1;
// 元素失去焦点隐藏iphone的软键盘
    function objBlur(id,time){
        if(typeof id != 'string') throw new Error('objBlur()参数错误');
        var obj = document.getElementById(id),
            time = time || 300,
            docTouchend = function(event){
                if(event.target!= obj){
                    setTimeout(function(){
                        obj.blur();
                        document.removeEventListener('touchend', docTouchend,false);
                    },time);
                }
            };
        if(obj){
            obj.addEventListener('focus', function(){
                document.addEventListener('touchend', docTouchend,false);
            },false);
        }else{
            throw new Error('objBlur()没有找到元素');
        }
    }

    if(isIPHONE){
        var input = new objBlur('fool-mobile');
        input=null;
    }

    $(".activity.fool_day .in-cont .button").bind("tap",function(event){
        setTimeout(function(){
            var mobile_re = /^1[3|4|5|7|8]\d{9}$/;
            var mobile =$(".activity.fool_day #fool-mobile").val();
            if(mobile==""){
                $(".info-error").text("请输入手机号");
                return false;
            }
            if(!mobile_re.test(mobile)){
                $(".info-error").text("请核对号码");
                return false;
            }
            $(".info-error").text("");
            $.post("/api/coupon/get",
                {
                    mobile:mobile,
                    couponActivityID:1207
                },
                function(data,status){
                    if(status){
                        data=JSON.parse(data);

                        if(data.errorCode==0){
                            $(".alert-cont section").html('<p>优惠券领取成功</p><p>发放到您的账户了</p>');
                            $(".all").css("display","block");
                            $(".alert-cont").css("display","block");
                        }
                        else  if(data.errorCode=='-70005'){

                            $(".alert-cont section").html('<p>您已经领取过了</p><p>快去我的账户查看</p>');
                            $(".all").css("display","block");
                            $(".alert-cont").css("display","block");
                        }
                        else{
                            alert(data.errorMessage);
                            location.reload();
                        }

                    }

                });


        },320);
    });


});
$(function(){
   

    $(".page1 .btn").click(function(){
        $(".page1").css("display","none");
        $(".page2").css("display","block");
        $(".video-cont").triggerHandler("click");
    });

    $(".page2 .btn2").click(function(){
        $(".page2 iframe").remove();
        $(".page2").css("display","none");
        $(".page3 .text img").attr("src","/bundles/autowap/images/tg0fd-text3.png");
        $(".page3 .btn img").attr("src","/bundles/autowap/images/tg0fd-btn2.png");
        $(".page3").css("display","block");

    });
    $(".page2 .btn22").click(function(){
        $(".page2 iframe").remove();
        $(".page2").css("display","none");
        $(".page3 .text img").attr("src","/bundles/autowap/images/tg0fd-text4.png");
        $(".page3 .btn img").attr("src","/bundles/autowap/images/tg0fd-btn22.png");
        $(".page3").css("display","block");

    });


    $(".page3 .btn").bind("click",tg);

    function tg(){
        $(".page3 .in img").removeClass("shake");
        var mobile=$(".in input").val();
        setTimeout(function(){
            if(!/(^1[3|4|5|7|8]\d{9})$/.test(mobile)){
                $(".page3 .in img").addClass("shake");
                return false;
            }
            $(".page3 .btn").unbind("click",tg);

            $.post("/api/coupon/get",
                {
                    mobile:mobile,
                    couponActivityID:4103
                },
                function(data,status){
                    if(status){
                        data=JSON.parse(data);
                        if(data.errorCode==0){
                            $(".page3").css("display","none");
                            $(".page4").css("display","block");

                        }
                        else  if(data.errorCode=='-70005'){
                            $(".page3 .btn").bind("click",tg);
                            alert("该手机号码已经领取过优惠券");
                        }
                        else{

                            alert(data.errorMessage);
                            location.reload();
                        }

                    }

                });


        },20);
    }

    $(".input-cont input").focus(function(){
        $(this).attr("placeholder"," ")
    });


    $(".input-cont input").blur(function(){
        $(this).attr("placeholder","请输入手机号码，我们车里见")
    });
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
    var input = new objBlur('mobile');
    input=null;
}


var url ="http://s95.cnzz.com/z_stat.php?id=1258676939&web_id=1258676939";
var auditCount = function (url) {
    var scriptTag = document.createElement('script');
    scriptTag.setAttribute('src', url);
    scriptTag.setAttribute('language', 'JavaScript');
    $('head').first().append(scriptTag);
};
$(".page4 .a-cont .a1").click(function(e){
    e.preventDefault();
    auditCount(url);
    var t=$(this).attr("href");
    /*  window.location.href=$(this).attr("href");*/
    setTimeout(function(){
        window.location.href=t;
    },600);

});
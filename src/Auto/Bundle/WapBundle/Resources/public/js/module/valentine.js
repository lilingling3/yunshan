/******load******/

var images=[
    "/bundles/autowap/images/77-01.jpg","/bundles/autowap/images/77-02.jpg","/bundles/autowap/images/77-03.jpg","/bundles/autowap/images/77-04.jpg","/bundles/autowap/images/77-05.jpg","/bundles/autowap/images/77-06.jpg","/bundles/autowap/images/77-07.jpg","/bundles/autowap/images/77-08.jpg","/bundles/autowap/images/77-09.jpg","/bundles/autowap/images/77-10.jpg","/bundles/autowap/images/77-11.jpg","/bundles/autowap/images/77-12.jpg","/bundles/autowap/images/77-13.jpg","/bundles/autowap/images/77-14.jpg","/bundles/autowap/images/77-15.jpg","/bundles/autowap/images/77-16.jpg","/bundles/autowap/images/77-17.jpg",
    "/bundles/autowap/images/77-again.png","/bundles/autowap/images/77-arrow.png","/bundles/autowap/images/77-bg.png","/bundles/autowap/images/77-bg2.png","/bundles/autowap/images/77-btn.png","/bundles/autowap/images/77-btn2.png","/bundles/autowap/images/77-input-img.png","/bundles/autowap/images/77-iphone.png","/bundles/autowap/images/77-layer.png","/bundles/autowap/images/77-logo.png","/bundles/autowap/images/77-page1-text.png","/bundles/autowap/images/77-page2-flower1.png","/bundles/autowap/images/77-page2-flower2.png","/bundles/autowap/images/77-page2-text.png","/bundles/autowap/images/77-result-top.png",
    "/bundles/autowap/images/77-sbtn.png","/bundles/autowap/images/77-srborder.png","/bundles/autowap/images/77-str.png","/bundles/autowap/images/77-text.png","/bundles/autowap/images/77-wexinshare.jpg"
];
var imagesLoaded = 0;
var percentageLoaded = 0;
var totalImages = images.length;

for(var i=0;i<totalImages;i++){
    (function(){
        var img = new Image();
        img.src = images[i];
        img.onload = function () {
            imagesLoaded++;
            $("#loading_percent").text(Math.floor((imagesLoaded/totalImages)*100));
            if(imagesLoaded==totalImages){
                $("#loading_percent").text("100");
                setTimeout(function(){
                    $("#loading").addClass("hide");
                    $(".wrapper").removeClass("hide");
                },300);

            }
        };
    })(i);

}

/***********load************/
var verify={
    "name":function(value){
        var reg=/^[\u4E00-\u9FA5]{2,4}$/;
        if(reg.test(value)){
            return true;
        }
        else{
            return false;
        }
    },
    "mobile":function(value){
        var reg=/^1[3|4|5|7|8]\d{9}$/;
        if(reg.test(value)){
            return true;
        }
        else{
            return false;
        }
    }
};
var btnimg={
    0:"/bundles/autowap/images/77-graybtn.png",
    1:"/bundles/autowap/images/77-btn.png"
}

var tchanose=["sweet-index","sweet-text","site-img","site-address","site-phone","site-reason","site-vegetable","sweet100"],activityId;

$(".submitbtn").bind("tap",btnsubmit);
function btnsubmit(){
    if(verify.name($("#man").val()) &&verify.name($("#madam").val())){
        var man=$("#man").val();
        var madam=$("#madam").val();
        $.post("/api/coupon/getValentineData",
            {
                man_name:man,
                woman_name:madam
            },
            function(data,status){
                if(status){
                    data=JSON.parse(data);
                    resultInfo(man,madam,data);
                }
                else{
                    location.reload();
                }
            });
    }
    else{
        $(".page2 .reminder").css("opacity","1");
        setTimeout(function(){
            $(".page2 .reminder").animate({opacity:0},1000,"swing");
        },3000);
    }
}

function resultInfo(man,madam,dateinfo){
    var str1;
    var info=dateinfo;
    if(man.length>2 || madam.length>2){
        str1=man+"和"+madam+"的匹配指数是:"
    }
    else{
        str1="男神"+man+"和女神"+madam+"的匹配指数是:"
    }
    $(".result .sldata .p1").text(str1);
    activityId=info["cactivity-id"];
    for(var i=0;i<tchanose.length;i++){
        var infoIndex=tchanose[i];
        if(info[infoIndex]){
            if(infoIndex=="site-img"){
                $("."+infoIndex).attr("src",info[infoIndex]);
            }
            else{
                $("."+infoIndex).text(info[infoIndex]);
            }

        }
        else{
            $("."+infoIndex).parent(".row").empty();
        }
    }
    $(".wrapper").addClass("hide");
    $(".result").removeClass("hide");
}


$(".result .sbtn").bind("tap",phonebtn);

function phonebtn(){
    if(!verify.mobile($("#phone").val())){
        alert("手机号错误!");
        return false;
    }
    var phone=$("#phone").val();
    $(".result .sbtn").unbind("tap",phonebtn);
    $.post("/api/coupon/get2",
        {
            mobile:phone,
            couponActivityID:activityId
        },
        function(data,status){
            if(status){
                data=JSON.parse(data);
                if(data.errorCode==0){
                    var phonestr=phone.substring(0, 3) + "****" + phone.substring(7, 11);
                    $(".leshare .phone").text(phonestr);
                    $(".result").addClass("hide");
                    $(".leshare").removeClass("hide");
                    $(".result .sbtn").bind("tap",phonebtn);
                }
                else  if(data.errorCode=='-70005'){
                    alert("该手机号码已经领取过优惠券");
                    $(".result .sbtn").bind("tap",phonebtn);
                }
                else{
                    alert(data.errorMessage);

                    $(".result .sbtn").bind("tap",phonebtn);
                }

            }
            else{
                $(".result .sbtn").bind("tap",phonebtn);
                
            }

        });


}

$(".leshare .lebtnagain").tap(function(){
    var timestamp=new Date().getTime();
    window.location.href=window.location.href+"?date="+timestamp;
});

$(".leshare .lebtnr").tap(function(){
    $(".leshare .layer").removeClass("hide");
});

$(".leshare .layer").tap(function(){
    $(".leshare .layer").addClass("hide");
});
$(".rules").tap(function(){
    window.location.href=$(this).attr("href");
});


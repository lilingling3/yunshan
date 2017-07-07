var phone=$("#phone");
$(phone).focus(function(){
    if($(this).val()==""){
        $(".button").removeClass("ybttm");
        return false;
    }
    if(!/(^1[3|4|5|7|8]\d{9})$/.test($(this).val())){
        $(".button").removeClass("ybttm");
        return false;
    }
    $(".button").addClass("ybttm");

}).keyup(function(){
    $(this).triggerHandler("focus");
});


$(".button").click(function(){
    var mobile=$(phone).val();
    if(!/(^1[3|4|5|7|8]\d{9})$/.test(mobile)){
        $(".button").removeClass("ybttm");
        return false;
    }

     $.post("/api/coupon/get",
         {
             mobile:mobile,
             couponActivityID:2735
         },
         function(data,status){
             if(status){
                 data=JSON.parse(data);
                 if(data.errorCode==0){
                     $(".cptext .usrPhone").text(mobile);
                     $(".top-text .text").hide();
                     $(".top-text .coupon").show();
                     $(".register .cptext").show();
                     $(".register .input-cont").hide();
                     $(".top-bg1").hide();
                     $(".top-bg2").show();
                     $(".button").hide();
                     $(".button-href").css("display","block");
                     $(".coupon-href").hide();
                     $(".cf-text").show();

                 }
                 else  if(data.errorCode=='-70005'){
                     alert("该手机号码已经领取过优惠券");
                 }
                 else{

                     alert(data.errorMessage);
                     location.reload();
                 }

             }

         });
    window.scrollTo(0,0);
});

var url ="http://s95.cnzz.com/z_stat.php?id=1258676939&web_id=1258676939";
var auditCount = function (url) {
    var scriptTag = document.createElement('script');
    scriptTag.setAttribute('src', url);
    scriptTag.setAttribute('language', 'JavaScript');
    $('head').first().append(scriptTag);
};
$(".button-href").click(function(e){
    e.preventDefault();
    auditCount(url);
    var t=$(this).attr("href");
  /*  window.location.href=$(this).attr("href");*/
    setTimeout(function(){
        window.location.href=t;
    },500);

});





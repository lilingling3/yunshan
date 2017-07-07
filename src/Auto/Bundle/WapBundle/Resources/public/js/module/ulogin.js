
/**验证码登录**/

var vfy={
    checkMobile:function (str) {

        var re = /^1[3|4|5|7|8]\d{9}$/;

        if (re.test(str)) {
            return true;
        } else {
            return false;
        }
    },
    checkCode:function (str) {

        var re = /^\d{4}$/;

        if (re.test(str)) {
            return true;
        } else {
            return false;
        }
    }
};

var img={
    0:"/bundles/autowap/images/activity-login-input3.png",
    1:"/bundles/autowap/images/activity-login-input4.png",
    2:"/bundles/autowap/images/activity-login-btton1.png",
    3:"/bundles/autowap/images/activity-login-btton.png"
};
$(".code-button").on("tap.codeClick",codeclik);

function codeclik(){
    verify(1);
    var mobile = $("#mobile").val();
    if(!vfy.checkMobile(mobile)){
        return false;
    }
    $(".code-button").off('.codeClick');


    $.post("/api/account/login/code",
        {
            mobile:mobile
        },
        function(data,status){
            if(status){
                data=JSON.parse(data);
                if(data.errorCode==0){
                    $(".code-button").find("img").attr("src",img[1]);
                    var time=59;
                    $(".code-t").text("重发（59）");

                    var timer=setInterval(function(){
                        $(".code-t").text("重发（"+--time+"）");

                        if(time==-1){
                            clearInterval(timer);

                            $(".code-button").on("tap.codeClick",codeclik);
                            $(".code-t").text('重新发送');
                            $(".code-button").find("img").attr("src",img[0]);
                        }
                    },1000);

                }else{
                    $(".code-button").on("tap.codeClick",codeclik);
                    alert(data.errorMessage)
                }

            }

        });

}


$(".btn-cont").on("tap",submitfn);

function submitfn(){
    if($(".btn").hasClass("btntrue")){

    var tflag=verify(3);
    if(!tflag){
        return false;
    }
    var mobile = $.trim($("#mobile").val());
    var code = $.trim($("#code").val());
    var source = $.trim($("#source").val());

    $.post("/api/account/verify/login",
        {
            mobile:mobile,
            code:code,
            source:source
        },
        function(data,status){
            data=JSON.parse(data);
            if(data.errorCode==0){

                $("#codelogin2Verify").submit();
            }
            else{
                $("#code").siblings(".error-icon").addClass("visible");
                $(".btn-img").attr("src",img[2]);
                $(".btn").removeClass("btntrue");
                return false;
            }

        });
    return false;

    }
};


$("input").blur(function(){
    verify($(this).attr("flag"));
});
$("#code").focus(function () {
    verify($(this).attr("flag"));
}).keyup(function(){
    $(this).triggerHandler("focus");
});

function verify(num){
    var mobile = $.trim($("#mobile").val());
    var code = $("#code").val();
    var flag=false;
    if(num==1 ||num==3){
        if(!vfy.checkMobile(mobile)){
            $("#mobile").siblings(".error-icon").addClass("visible");
        }
        else {
            $("#mobile").siblings(".error-icon").removeClass("visible");
        }
    }
    if(num==3) {
        if (!vfy.checkCode(code)) {
            $("#code").siblings(".error-icon").addClass("visible");
        }
    }
    if(!vfy.checkMobile(mobile)||!vfy.checkCode(code)){
        $(".btn-img").attr("src",img[2]);
        $(".btn").removeClass("btntrue");

    }
    if(vfy.checkMobile(mobile)&&vfy.checkCode(code)){
        $(".error-icon").removeClass("visible");
        $(".btn-img").attr("src",img[3]);
        $(".btn").addClass("btntrue");
        flag=true;
    }

    return flag;

};


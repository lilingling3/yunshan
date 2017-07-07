
/**验证码登录**/

$("#page.activity.codelogin .login-form .code").click(function(e){


    var mobile = $.trim($("#mobile").val());
    if(mobile==""){
        $(".login-error").addClass("show");
        $(".login-error").text('请输入手机号');
        return false;
    }
    if(!$.checkMobile(mobile)){
        $(".login-error").addClass("show");
        $(".login-error").text('手机号错误!');
        return false;
    }
    $.post("/api/account/login/code",
        {
            mobile:mobile
        },
        function(data,status){
            if(status){
                if(data.errorCode==0){
                    $(".code").text("查看验证码");
                    $(".code").attr("disabled", "disabled");
                    $(".code").css("background-color", "#eee");
                    var time=59;
                    $(".code").text(59);
                    var timer=setInterval(function(){
                        $(".code").text(--time);
                        if(time==-1){
                            clearInterval(timer);
                            $(".code").attr("disabled", false);
                            $(".code").text('获取验证码');
                            $(".code").css("background-color", "#fff");
                        }
                    },1000);

                }else{
                    alert(data.errorMessage)
                }

            }

        });
});



$("#page.activity.codelogin .submit-button .login-in").click(function(e){

    var code = $("#code").val();
    var source = $.trim($("#source").val());
    var mobile = $.trim($("#mobile").val());


    if(mobile==""){
        $(".login-error").addClass("show");
        $(".login-error").text('请输入手机号');
        return false;
    }
    if(!$.checkMobile(mobile)){
        $(".login-error").addClass("show");
        $(".login-error").text('手机号错误!');
        return false;
    }
    if(code==""){
        $(".login-error").text('请输入验证码');
        $(".login-error").addClass("show");
        return false;
    }

    $.post("/api/account/verify/login",
        {
            mobile:mobile,
            code:code,
            source:source

        },
        function(data,status){
            if(data.errorCode==0){

                $("#codelogin2Verify").submit();
            }
            else{
                alert(data.errorMessage);
                return false;
            }



        });
    return false;
});


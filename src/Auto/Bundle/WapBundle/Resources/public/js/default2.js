$("#page.default2.forget .login-form .code").click(function(){

    var mobile = $.trim($("#userm").val());
    $.post("/api/account/forget/getCode",
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

function count(ele,time,msm){

    $(ele);
    $(ele).text(time);
    if(time==-1){
        clearInterval(timer);
        $(ele).text(msm);
    }
}


$("#page.default2.forget .submit-button .login-in").click(function(e){
    var code = $("#user-code").val();

    var mobile = $.trim($("#userm").val());

    if(code==""){
        alert('验证码错误！');
        return false;
    }
    var password = $.trim($("#password").val());
    if($.passwordsVerify(password)==-2){
        $(".login-error").text('请输入密码');
        $(".login-error").removeClass("black");
        return false;
    }
    if($.passwordsVerify(password)!=1){
        $(".login-error").text('密码长度应为6到16位数字或字母');
        $(".login-error").removeClass("black");
        return false;
    }
   $.post("/api/account/forget/verify",
        {
            mobile:mobile,
            code:code
        },
        function(data,status){

            if(data.errorCode==0){
                $("#page.default2.forget form").submit();
            }
            else{
                alert(data.errorMessage);
                return false;
            }



        });
    return false;
});




/**验证码登录**/

$("#page.default2.codelogin .login-form .code").click(function(e){


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



$("#page.default2.codelogin .submit-button .login-in").click(function(e){

    var code = $("#code").val();

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
            code:code

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


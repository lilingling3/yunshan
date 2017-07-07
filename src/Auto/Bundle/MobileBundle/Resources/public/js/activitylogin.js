if(window.location.pathname=="/mobile/activity/login/wjsoho"||window.location.pathname=="/mobile/activity/login/gglc"||window.location.pathname=="/mobile/activity/login/slt") {

    var verify={
        mobile:function(num){
            if(num==""|| $.trim(num)==""){
                $(".error").text("请填写手机号");
                return false;
            }
            if(!$.checkMobile(num)){
                $(".error").text("手机号错误");
                return false;
            }
            return true;
        },
        code:function(num){
            if(num==""){
                $(".error").text("请填写验证码");
                return false;
            }
            return true;
        }
    };

    $("#page.default.login .btn-code").on("click.codeclick",getcode);


    function getcode(){
        var mobile=$("#mobile").val();
        if(! verify.mobile(mobile)){
            return false;
        }
        $(".error").text("");
        $("#page.default.login .btn-code").off(".codeclick");
        $.post("/api/account/login/code",
            {
                mobile:mobile
            },
            function(data,status){
                if(status){
                    if(data.errorCode==0){
                        $(".code-t").removeClass("blue");
                        var time=59;
                        var timestr=time+"秒";
                        $(".code-t").text(timestr);
                        var timer=setInterval(function(){
                            var timestr=--time+"秒";
                            $(".code-t").text(timestr);
                            if(time==-1){
                                clearInterval(timer);
                                $(".code-t").addClass("blue");
                                $(".code-t").text('获取验证码');
                                $("#page.default.login .btn-code").on("click.codeclick",getcode);
                            }
                        },1000);

                    }else{
                        $("#page .default.login .btn-code").on("click.codeclick",getcode);
                        alert(data.errorMessage)
                    }

                }

            });
    }


    $("#page.default.login .btn-subimt").on("click",submitclick);


    function submitclick(){
        var mobile = $.trim($("#mobile").val());
        var code = $.trim($("#code").val());

        if(!verify.mobile(mobile)){
            return false;
        }
        if(!verify.code(code)){
            return false;
        }
        $(".error").text("");
        $.post("/api/account/verify/login",
            {
                mobile:mobile,
                code:code,
                source:$("#source").val()
            },
            function(data,status){
                if(data.errorCode==0){
                    $("#loginform").submit();
                }
                else{
                    alert(data.errorMessage);
                    return false;
                }

            });
        return false;
    }
}

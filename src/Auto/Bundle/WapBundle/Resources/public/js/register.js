$("#page.default.register").delegate(".code-button","click",function(){


    var mobile =$("#mobile").val();
   
    if(!$.checkMobile(mobile)){
        alert("手机号无效");
        return false;
    }
    $.post("/api/account/getCode",
        {
            mobile:$("#mobile").val()
        },
        function(data,status){
            if(status){
                if(data.errorCode==0){

                    $(".code-button").attr("disabled", "disabled");
                    $(".code-button").css("background-color", "#eee");

                    var d=new Date();
                    d.setSeconds(d.getSeconds()+59);
                    var m=d.getMonth()+1;
                    var time = d.getFullYear()+'-'+m+'-'+d.getDate()+' '+d.getHours()+':'+ d.getMinutes()+':'+d.getSeconds();

                    var id = ".code-button";
                    var end_time = new Date(Date.parse(time.replace(/-/g, "/"))).getTime(),//月份是实际月份-1
                        sys_second = (end_time-new Date().getTime())/1000;
                    var timer = setInterval(function(){

                        if (sys_second > 1) {
                            sys_second -= 1;
                            var day = Math.floor((sys_second / 3600) / 24);
                            var hour = Math.floor((sys_second / 3600) % 24);
                            var minute = Math.floor((sys_second / 60) % 60);
                            var second = Math.floor(sys_second % 60);

                            var time_text = '';
                            if(day>0){
                                time_text+=day+'天';
                            }
                            if(hour>0){
                                if(hour<10){
                                    hour='0'+hour;
                                }
                                time_text+=hour+'小时';

                            }
                            if(minute>0){
                                if(minute<10){
                                    minute='0'+minute;
                                }
                                time_text+=minute+'分';

                            }
                            if(second>0){
                                if(second<10){
                                    second='0'+second;
                                }
                                time_text+=second+'秒';
                            }

                            $(id).text(time_text);

                        } else {
                            clearInterval(timer);
                            $(".code-button").attr("disabled", false);
                            $(".code-button").text('获取验证码');
                            $(".code-button").css("background-color", "#fff");
                        }
                    }, 1000);

                }else{
                    alert(data.errorMessage)
                }

            }

        });
});

$("#page.default.register").delegate("#sub-register","click",function(){

    var code = $(".code-text").val();

    var mobile = $.trim($("#mobile").val());
    if(mobile==""){
    	alert('请输入手机号');
        return false;
    }
    if(!$.checkMobile(mobile)){
    	alert('手机号错误!');
        return false;
    }
    var password = $("#password").val();

    if(code==''){
        alert('请输入验证码');
        return false;
    }



    $.post("/api/account/verify",
        {
            mobile:mobile,
            code:code
        },
        function(data,status){
            if(status){
                if(data.errorCode==0){
					if($.passwordsVerify(password)==-2){
                        alert('请输入密码');
                        return false;
                    }
                    if($.passwordsVerify(password)==-1){
                        alert('密码长度应为6到16位数字或字符');
                        return false;
                    }else{
                        $("#page.default.register form").submit();
                    }

                }else{
                    alert(data.errorMessage);
                    return false;
                }

            }

        });


});




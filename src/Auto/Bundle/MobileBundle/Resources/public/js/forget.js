$("#page.default.forget").delegate(".code-button","click",function(){
   
     var mobile = $.trim($("#mobile").val());
    if(mobile==""){
    	alert('请输入手机号');
        return false;
    }
    if(!$.checkMobile(mobile)){
    	alert('手机号错误!');
        return false;
    }
   
    $.post("/api/account/forget/getCode",
        {
            mobile:$("#mobile").val()
        },
        function(data,status){
            if(status){
                if(data.errorCode==0){
                    $(".code-button").text("查看验证码");
                    $(".code-button").attr("disabled", "disabled");
                    $(".code-button").css("background-color", "#eee");
                    var time=59;
                    $(".code-button").text(59);
                    var timer=setInterval(function(){
                    		$(".code-button").text(--time);
                    		if(time==-1){
                    			clearInterval(timer);
                    			 $(".code-button").attr("disabled", false);
	                            $(".code-button").text('获取验证码');
	                            $(".code-button").css("background-color", "#fff");
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


$("#page.default.forget").delegate("#sub-register","click",function(){

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
    if(code==""){
    	alert('验证码错误！');
        return false;
    	}
    var password = $.trim($("#password").val());
    if($.passwordsVerify(password)==-2){
        alert('请输入密码');
        return false;
    }
    if($.passwordsVerify(password)!=1){
        alert('密码长度应为6到16位数字或字母');
        
        return false;
    }

    $.post("/api/account/forget/verify",
        {
            mobile:mobile,
            code:code
        },
        function(data,status){

           if(data.errorCode==0){
                 $("#page.default.forget form").submit();
                }
            else{
                alert(data.errorMessage);
                return false;
            }



        });
    return false;
});


function checkMobile(str) {
    var
        re = /^1\d{10}$/
    if (re.test(str)) {
        return true;
    } else {
        return false;
    }
}



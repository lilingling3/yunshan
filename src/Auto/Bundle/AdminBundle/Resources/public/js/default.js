$(function(){
    $(".adminbackgo").bind("click",function(e){
        e.preventDefault();
        window.history.back(-1);
    });
    $(".operate_submit").click(function(){

        var starttime=$(".Operatestarttime").val();
        var endtime=$(".Operateendtime").val();
        if(starttime||endtime){
            if(starttime&&endtime){
                if(starttime>endtime){
                    alert("结束时间应该大于开始时间！");
                    return false;
                }
            }else{
                alert("请同时选择开始时间和结束时间！");
                return false;
            }

        }


    })

    $("#auto_bundle_managerbundle_rechargeactivity_endTime").find('select').addClass('market_all');
    $("#auto_bundle_managerbundle_rechargeactivity_startTime").find('select').addClass('market_all');
    $("#auto_bundle_managerbundle_rechargeactivity_startTime_time_minute").removeClass('market_all').addClass('market_minute');
    $("#auto_bundle_managerbundle_rechargeactivity_endTime_time_minute").removeClass('market_all').addClass('market_minute');


    $("#auto_bundle_managerbundle_marketactivity_endTime").find('select').addClass('market_all');
    $("#auto_bundle_managerbundle_marketactivity_startTime").find('select').addClass('market_all');
    $("#auto_bundle_managerbundle_marketactivity_startTime_time_minute").removeClass('market_all').addClass('market_minute');
    $("#auto_bundle_managerbundle_marketactivity_endTime_time_minute").removeClass('market_all').addClass('market_minute');


    $("#auto_bundle_managerbundle_maintenance_record_downTime").find('select').addClass('market_all');
    $("#auto_bundle_managerbundle_maintenance_record_maintenanceTime").find('select').addClass('market_all');
    $("#auto_bundle_managerbundle_maintenance_record_downTime_time_minute").removeClass('market_all').addClass('market_minute');
    $("#auto_bundle_managerbundle_maintenance_record_maintenanceTime_time_minute").removeClass('market_all').addClass('market_minute');


    $("#form_maintenanceRecord_downTime").find('select').addClass('market_all');
    $("#form_maintenanceRecord_maintenanceTime").find('select').addClass('market_all');
    $("#form_maintenanceRecord_downTime_time_minute").removeClass('market_all').addClass('market_minute');
    $("#form_maintenanceRecord_maintenanceTime_time_minute").removeClass('market_all').addClass('market_minute');

    $("#auto_bundle_managerbundle_inspection_inspectionTime").find('select').addClass('market_all');
    $("#auto_bundle_managerbundle_inspection_inspectionTime_minute").removeClass('market_all').addClass('market_minute');


    $("#auto_bundle_managerbundle_settleclaim_claimTime").find('select').addClass('market_all');
    $("#auto_bundle_managerbundle_settleclaim_claimTime_minute").removeClass('market_all').addClass('market_minute');

    $("#auto_bundle_managerbundle_settleclaim_applyTime").find('select').addClass('market_all');
    $("#auto_bundle_managerbundle_settleclaim_applyTime_time_minute").removeClass('market_all').addClass('market_minute');

    $("#auto_bundle_managerbundle_settleclaim_settleTime").find('select').addClass('market_all');
    $("#auto_bundle_managerbundle_settleclaim_settleTime_time_minute").removeClass('market_all').addClass('market_minute');





    $("#admin.default.login .login-form .code").click(function(e){

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
        $(".login-error").text('');
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



    $("#admin.default.login .submit-button .login-in").click(function(e){

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

                    $("#codeloginVerify").submit();
                }
                else{
                    alert(data.errorMessage);
                    return false;
                }



            });
        return false;
    });



    jQuery.extend({
        passwordsVerify:function(pass){
            //四到六位数字或者字母
            //为空返回-2
            //小于6位或者大于16位返回-1
            //不合法返回0
            //合法返回1
            var patrn=/[^\w]$/;
            if(pass==""){
                return -2;
            }
            if(pass.length<6||pass.length>16){
                return -1;
            }
            else {
                var result = patrn.test(pass);
                if(result){
                    return 0;
                }
                return 1;
            }

        },

        checkMobile:function (str) {

            var re = /^1[3|4|5|7|8]\d{9}$/;

            if (re.test(str)) {
                return true;
            } else {
                return false;
            }
        }

    });
})
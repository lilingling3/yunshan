if((window.location.pathname).substr(0,23)=="/admin/member/auth/edit"){
    YUI().use('node', 'event', 'anim', function(Y){

        Y.on('click', function (e) {
            e.preventDefault();
            var i = parseInt(Y.one('#admin.member.auth-edit img').getData('rotate')) ;
            var m = (i+1)%4;
            Y.one('#admin.member.auth-edit img').setStyle('transform', "rotate("+m*90+"deg)");
            Y.one('#admin.member.auth-edit img').setData('rotate',i+1);

        }, '.MagicZoomPup');


        Y.on('click', function (e) {
            e.preventDefault();

            var status = Y.one('#form_AuthMember_licenseAuthError').get('value');
            var msg = '';
            var type=this.get('text');
            if(type=="提交"){
                if(status==0){

                    if(Y.one('#form_AuthMember_licenseEndDate').get('value')==''){
                        msg = '请填写证件有效结束日期';
                    };

                    if(Y.one('#form_AuthMember_documentNumber').get('value')==''){
                        msg = '请填写档案号';
                    };

                    if(Y.one('#form_AuthMember_IDNumber').get('value')==''){
                        msg = '请填写驾照号码';
                    };
                    if(Y.one('#form_name').get('value')==''){
                        msg = '请填写姓名';
                    };

                }

                if(msg != ''){
                    alert(msg);
                }else{
                    this.ancestor('form').submit();
                }
            }else{
                window.location.href="/admin/member/auth/list";
            }

        }, '#admin.member.auth-edit form button');



        Y.on('keyup', function (e) {

            Y.one('#word-count').set('text',this.get('value').length);

        }, '#admin.sms.send #form_content');

    });
}

if((window.location.pathname).substr(0,27)=="/admin/authMember/auth/edit"){
    YUI().use('node', 'event', 'anim', function(Y){
/*
        Y.on('click', function (e) {
            e.preventDefault();
            var i = parseInt(Y.one('#admin.member.auth-edit img').getData('rotate')) ;
            var m = (i+1)%4;
            Y.one('#admin.member.auth-edit img').setStyle('transform', "rotate("+m*90+"deg)");
            Y.one('#admin.member.auth-edit img').setData('rotate',i+1);

        }, '.MagicZoom');*/
        Y.on('click', function (e) {
            e.preventDefault();
            var i = parseInt(Y.one('#admin.member.auth-edit #zoom1 img').getData('rotate')) ;
            var m = (i+1)%4;
            Y.one('#admin.member.auth-edit #zoom1 img').setStyle('transform', "rotate("+m*90+"deg)");
            Y.one('#admin.member.auth-edit #zoom1 img').setData('rotate',i+1);

        }, '#zoom1');
        Y.on('click', function (e) {
            e.preventDefault();
            var i = parseInt(Y.one('#admin.member.auth-edit #zoom2 img').getData('rotate')) ;
            var m = (i+1)%4;
            Y.one('#admin.member.auth-edit #zoom2 img').setStyle('transform', "rotate("+m*90+"deg)");
            Y.one('#admin.member.auth-edit #zoom2 img').setData('rotate',i+1);

        }, '#zoom2');
        Y.on('click', function (e) {
            e.preventDefault();
            var i = parseInt(Y.one('#admin.member.auth-edit #zoom3 img').getData('rotate')) ;
            var m = (i+1)%4;
            Y.one('#admin.member.auth-edit #zoom3 img').setStyle('transform', "rotate("+m*90+"deg)");
            Y.one('#admin.member.auth-edit #zoom3 img').setData('rotate',i+1);

        }, '#zoom3');



         Y.on('click', function (e) {
            e.preventDefault();

            var licenseImageAuthError = Y.one('#form_AuthMember_licenseImageAuthError').get('value');
            var idImageAuthError = Y.one('#form_AuthMember_idImageAuthError').get('value');
            var idHandImageAuthError = Y.one('#form_AuthMember_idHandImageAuthError').get('value');
            var mobileCallError = Y.one('#form_AuthMember_mobileCallError').get('value');
            var msg = '';
            var type=this.get('text');
            if(type=="保存"){
                if(licenseImageAuthError==0 && idImageAuthError==0 && idHandImageAuthError==0){

                    if(Y.one('#form_AuthMember_licenseEndDate').get('value')==''){
                        msg = '请填写证件有效结束日期';
                    };

                    if(Y.one('#form_AuthMember_licenseStartDate').get('value')==''){
                        msg = '请填写证件有效开始日期';
                    };

                    if(Y.one('#form_AuthMember_documentNumber').get('value')==''){
                        msg = '请填写档案号';
                    };

                    if(Y.one('#form_AuthMember_IDNumber').get('value')==''){
                        msg = '请填写驾照号码';
                    };

                    if(Y.one('#form_name').get('value')==''){
                        msg = '请填写姓名';
                    };

                    if(Y.one('#form_IdNumber').get('value')==''){
                        msg = '请填写身份证号';
                    };

                    if(Y.one('#form_address').get('value')==''){
                        msg = '请填写身份证住址';
                    };

                    if(Y.one('#form_nation').get('value')==''){
                        msg = '请填写民族';
                    };

                    if(Y.one('#form_sex').get('value')==''){
                        msg = '请填写性别';
                    };

                    //if(mobileCallError == 0){
                    //    if(Y.one('#form_AuthMember_validateResult').get('value')==''){
                    //        msg = '请提交第三方认证';
                    //    };
                    //};
                }
                if(msg != ''){
                    alert(msg);
                }else{
                    this.ancestor('form').submit();
                }
            }

        }, '#admin.member.auth-edit form button');


        Y.on('keyup', function (e) {

            Y.one('#word-count').set('text',this.get('value').length);

        }, '#admin.sms.send #form_content');

    });

    $(function() {


        $("#form_AuthMember_licenseImageAuthError").on("change", function () {
            var licenseImageAuthError = $("#form_AuthMember_licenseImageAuthError").val();
            var idImageAuthError = $("#form_AuthMember_idImageAuthError").val();
            var idHandImageAuthError = $("#form_AuthMember_idHandImageAuthError").val();
            var mobileCallError = $("#form_AuthMember_mobileCallError").val();
            if ( (licenseImageAuthError == 0) && (idImageAuthError == 0) && (idHandImageAuthError == 0) && mobileCallError == 0) {
                $('#hid').show();
                $(".auth_validate").attr("disabled", false);
            } else {
                $('#hid').hide();
                $(".auth_validate").attr("disabled", true);
            }

        })

        $("#form_AuthMember_idImageAuthError").on("change", function () {
            var licenseImageAuthError = $("#form_AuthMember_licenseImageAuthError").val();
            var idImageAuthError = $("#form_AuthMember_idImageAuthError").val();
            var idHandImageAuthError = $("#form_AuthMember_idHandImageAuthError").val();
            var mobileCallError = $("#form_AuthMember_mobileCallError").val();
            if ( (licenseImageAuthError == 0) && (idImageAuthError == 0) && (idHandImageAuthError == 0) && mobileCallError == 0) {
                $('#hid').show();
                $(".auth_validate").attr("disabled", false);
            } else {
                $('#hid').hide();
                $(".auth_validate").attr("disabled", true);
            }

        })

        $("#form_AuthMember_idHandImageAuthError").on("change", function () {
            var licenseImageAuthError = $("#form_AuthMember_licenseImageAuthError").val();
            var idImageAuthError = $("#form_AuthMember_idImageAuthError").val();
            var idHandImageAuthError = $("#form_AuthMember_idHandImageAuthError").val();
            var mobileCallError = $("#form_AuthMember_mobileCallError").val();
            if ( (licenseImageAuthError == 0) && (idImageAuthError == 0) && (idHandImageAuthError == 0) && mobileCallError == 0) {
                $('#hid').show();
                $(".auth_validate").attr("disabled", false);
            } else {
                $('#hid').hide();
                $(".auth_validate").attr("disabled", true);
            }

        })

        $("#form_AuthMember_mobileCallError").on("change", function () {
            var licenseImageAuthError = $("#form_AuthMember_licenseImageAuthError").val();
            var idImageAuthError = $("#form_AuthMember_idImageAuthError").val();
            var idHandImageAuthError = $("#form_AuthMember_idHandImageAuthError").val();
            var mobileCallError = $("#form_AuthMember_mobileCallError").val();
            if ( (licenseImageAuthError == 0) && (idImageAuthError == 0) && (idHandImageAuthError == 0) && mobileCallError == 0) {
                $('#hid').show();
                $(".auth_validate").attr("disabled", false);
            } else {
                $(".auth_validate").attr("disabled", true);
                $('#hid').hide();
            }

        })

    })

    $(".auth_validate").click(function(e){
        e.preventDefault();
        var name = $.trim($("#form_name").val());
        var sex = $.trim($("#form_sex option:selected").text());
        var nation = $.trim($("#form_nation").val());
        var IdNumber = $.trim($("#form_IdNumber").val());
        var address = $.trim($("#form_address").val());
        var mobile = $.trim($("span#mobile").text());

        if(name == ''){
            $(".error").addClass("show");
            $(".error").text("姓名不能为空!");
            return false;
        } else {
            $(".error").addClass("hide");
            $(".error").text('');
        }

        if(sex == ''){
            $(".error").addClass("show");
            $(".error").text("性别不能为空!");
            return false;
        } else {
            $(".error").addClass("hide");
            $(".error").text('');
        }

        if(nation == ''){
            $(".error").addClass("show");
            $(".error").text("民族不能为空!");
            return false;
        } else {
            $(".error").addClass("hide");
            $(".error").text('');
        }

        if(IdNumber == ''){
            $(".error").addClass("show");
            $(".error").text("身份证号不能为空!");
            return false;
        } else {
            $(".error").addClass("hide");
            $(".error").text('');
        }

        if(address == ''){
            $(".error").addClass("show");
            $(".error").text("身份证住址不能为空!");
            return false;
        } else {
            $(".error").addClass("hide");
            $(".error").text('');
        }

        if(mobile == ''){
            $(".error").addClass("show");
            $(".error").text("电话号码不能为空!");
            return false;
        } else {
            $(".error").addClass("hide");
            $(".error").text('');
        }

        var Wi = [ 7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2, 1 ];    // 加权因子
        var ValideCode = [ 1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2 ];            // 身份证验证位值.10代表X
        function IdCardValidate(idCard) {
            idCard = trim(idCard.replace(/ /g, ""));               //去掉字符串头尾空格
            if (idCard.length == 15) {
                return isValidityBrithBy15IdCard(idCard);       //进行15位身份证的验证
            } else if (idCard.length == 18) {
                var a_idCard = idCard.split("");                // 得到身份证数组
                if(isValidityBrithBy18IdCard(idCard)&&isTrueValidateCodeBy18IdCard(a_idCard)){   //进行18位身份证的基本验证和第18位的验证
                    return true;
                }else {
                    return false;
                }
            } else {
                return false;
            }
        }
        /**
         * 判断身份证号码为18位时最后的验证位是否正确
         * @param a_idCard 身份证号码数组
         * @return
         */
        function isTrueValidateCodeBy18IdCard(a_idCard) {
            var sum = 0;                             // 声明加权求和变量
            if (a_idCard[17].toLowerCase() == 'x') {
                a_idCard[17] = 10;                    // 将最后位为x的验证码替换为10方便后续操作
            }
            for ( var i = 0; i < 17; i++) {
                sum += Wi[i] * a_idCard[i];            // 加权求和
            }
            valCodePosition = sum % 11;                // 得到验证码所位置
            if (a_idCard[17] == ValideCode[valCodePosition]) {
                return true;
            } else {
                return false;
            }
        }
        /**
          * 验证18位数身份证号码中的生日是否是有效生日
          * @param idCard 18位书身份证字符串
          * @return
          */
        function isValidityBrithBy18IdCard(idCard18){
            var year =  idCard18.substring(6,10);
            var month = idCard18.substring(10,12);
            var day = idCard18.substring(12,14);
            var temp_date = new Date(year,parseFloat(month)-1,parseFloat(day));
            // 这里用getFullYear()获取年份，避免千年虫问题
            if(temp_date.getFullYear()!=parseFloat(year)
                  ||temp_date.getMonth()!=parseFloat(month)-1
                  ||temp_date.getDate()!=parseFloat(day)){
                    return false;
            }else{
                return true;
            }
        }
          /**
           * 验证15位数身份证号码中的生日是否是有效生日
           * @param idCard15 15位书身份证字符串
           * @return
           */
          function isValidityBrithBy15IdCard(idCard15){
              var year =  idCard15.substring(6,8);
              var month = idCard15.substring(8,10);
              var day = idCard15.substring(10,12);
              var temp_date = new Date(year,parseFloat(month)-1,parseFloat(day));
              // 对于老身份证中的你年龄则不需考虑千年虫问题而使用getYear()方法
              if(temp_date.getYear()!=parseFloat(year)
                      ||temp_date.getMonth()!=parseFloat(month)-1
                      ||temp_date.getDate()!=parseFloat(day)){
                        return false;
                }else{
                    return true;
                }
          }
        //去掉字符串头尾空格
        function trim(str) {
            return str.replace(/(^\s*)|(\s*$)/g, "");
        }

        $("#form_AuthMember_validateResult").val('');

        if (IdCardValidate(IdNumber) == true) {
            $.post("/admin/authMember/getMsg",{name:name,sex:sex,nation:nation,IdNumber:IdNumber,address:address,mobile:mobile},function (result) {

                $("#form_AuthMember_validateResult").val(result.validateResult);

            })
        } else {
            $("#form_AuthMember_validateResult").val('');
            alert("请输入15-18位有效身份证");
            return false;
        }




    });




}

$(function() {


    $("#form_AuthMember_licenseAuthError").on("change", function () {
        var value = $("#form_AuthMember_licenseAuthError").val();
        if (value != 0) {
            $(this).parent().siblings().children().children('input').css("color", "#999").attr("readonly", "readonly");
            $(this).parent().siblings().children('input').css("color", "#999").attr("readonly", "readonly");

        } else {
            $(this).parent().siblings().children('input').css("color", "#000").removeAttr("readonly");
            $(this).parent().siblings().children().children('input').css("color", "#000").removeAttr("readonly");
        }

    })
    //$(".auth_form").children().children('input').css("color", "#999").attr("readonly", "readonly");
    //$(".auth_form").children().children('select').attr("disabled", "disabled");
    //$(".auth_edit").click(function(){
    //    $(".auth_form").children().children('input').css("color", "#000").removeAttr("readonly");
    //    $(".auth_form").children().children('select').removeAttr("disabled");
    //})
    //$(".auth_submit").click(function(){
    //    $(".auth_form").children().children('select').removeAttr("disabled");
    //})
    if (screen.availWidth < 800) {
        $(".main_img").css("width", "100%");
        $(".two").css("display", "none");
        $(".other").addClass("allwd");
        $("#zoom1").removeClass("MagicZoom");
        $("#admin .main-left ul li .menu-li").css({"width": "105px", "height": "auto"})
        var bodyTouch = util.toucher($('body')[0]);
        bodyTouch.on('swipeRight', '.swipe', function (e) {
            $(".two").removeClass("column");
            $(".two").css({
                "display": "block",
                "width": "90px !important",
                "position": "absolute",
                "top": "0px",
                "left": "0px",
                "z-index": "10"
            });
        }).on('swipeLeft', '.swipe', function (e) {
            $(".two").css("display", "none");
        });
        YUI().use('node', 'event', 'anim', function (Y) {
            Y.on('click', function (e) {
                e.preventDefault();
                var i = parseInt(Y.one('#admin.member.auth-edit img').getData('rotate'));
                var m = (i + 1) % 4;
                Y.one('#admin.member.auth-edit img').setStyle('transform', "rotate(" + m * 90 + "deg)");
                Y.one('#admin.member.auth-edit img').setData('rotate', i + 1);

            }, '.main_img');
        });

    }
    function verify() {
        $(".confirm").remove();

        $.get("/admin/authMember/getMessage", function (data) {

            if (data.status) {

                var template = "<div class='confirm'>" +
                    "<h2>系统消息<span class='close-confirm'>+</span></h2>" +
                    "<p>有未审核消息</p>" +
                    "</div>";

                $("body").append(template);
            } else {
            }
        })

    }
    verify();
    setInterval(verify, 50000);

    $("body").delegate(".confirm p", "click", function () {
        window.location.href = "/admin/authMember/auth/list";
    });
    $("body").delegate(".confirm h2 span", "click", function () {
        $(".confirm").remove();//css("display", "none");
    });


    $("#form_AuthMember_licenseStartDate").attr('placeholder', '1999-10-09');
    $("#form_AuthMember_licenseEndDate").attr('placeholder', '1999-10-09');


    function compareTo(start, end) {
        var date1 = new Date(start);  //开始时间
        var date2 = new Date(end);    //结束时间
        var date3 = date2.getTime() - date1.getTime()  //时间差的毫秒数
        //计算出相差天数#}
        var days = Math.floor(date3 / (24 * 3600 * 1000))
        //计算出小时数
        var leave1 = date3 % (24 * 3600 * 1000)    //计算天数后剩余的毫秒数
        var hours = Math.floor(leave1 / (3600 * 1000))
        //计算相差分钟数#}
        var leave2 = leave1 % (3600 * 1000)        //计算小时数后剩余的毫秒数#}
        var minutes = Math.floor(leave2 / (60 * 1000))
        //计算相差秒数#}
        var leave3 = leave2 % (60 * 1000)      //计算分钟数后剩余的毫秒数#}
        var seconds = Math.round(leave3 / 1000)
        var times = days + "天 " + hours + "小时 " + minutes + " 分钟";
        return times;
        // alert(" 相差 "+days+"天 "+hours+"小时 "+minutes+" 分钟"+seconds+" 秒")#}
    }

    var endlen=$(".endTime");
    var uselen=$(".useTime");
    var retalTimelen=$(".retalTime");
    var len=uselen.length;
    for(var i=0;i<len;i++){
        var end=$(endlen[i]).val();
        var use=$(uselen[i]).val();
        var endTime=end.replace(/-/g,"/");
        var useTime=use.replace(/-/g,"/");
        var retalTime=compareTo(useTime,endTime);
        $(retalTimelen[i]).text(retalTime);

    }

    if((window.location.pathname).substr(0,23)=="/admin/coupon/kind/edit"){
        $("#auto_bundle_managerbundle_couponkind_validDay").parent().siblings().find("input").attr("readonly","readonly");
    }

    $(".time_submit").click(function(){
        var starttime=$(".starttime").val();
        var endtime=$(".endtime").val();
        if(starttime&&endtime){
            if(starttime>endtime){
                alert("结束时间应该大于开始时间！");
                return false;
            }
        }else{
            alert("请同时选择开始时间和结束时间！");
            return false;
        }


    })

    //matterList
    $(".matter_choice").on("change",function(){
        var matter_choice_value=$(this).val();
        if(matter_choice_value=="member"){
            $(".matter_choice1").show().siblings().hide();
        }else if(matter_choice_value=="operate"){
            $(".matter_choice2").show().siblings().hide();
        }else{
            $(".matter_choice_none").show().siblings().hide();
    }
    })
    if((window.location.pathname=="/admin/statistics/list")
        ||window.location.pathname=="/admin/statistics/operate"
        ||window.location.pathname=="/admin/statistics/car/operate"
        ||window.location.pathname=='/admin/dataChart/operate'
        ||window.location.pathname=='/admin/dataChart/order'
        ||window.location.pathname=='/admin/dataChart/register'
        ||window.location.pathname=='/admin/dataChart/identification'
        ||window.location.pathname=='/admin/regionDataChart/operate'
        ||window.location.pathname=='/admin/regionDataChart/order'
        ||window.location.pathname=='/admin/regionDataChart/register'
        ||window.location.pathname=='/admin/regionDataChart/identification'
        ||window.location.pathname=='/admin/statistics/location'
        ||window.location.pathname=='/admin/rechargeActivity/list'
        ||window.location.pathname=='/admin/insuranceRecord/list'
    ) {
        laydate({
            elem: '#J-xl'
        });
        laydate({
            elem: '#J-xl2'
        });
    }

})







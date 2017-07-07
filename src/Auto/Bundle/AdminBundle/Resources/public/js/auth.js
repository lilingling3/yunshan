$(function(){
    $("#admin.step2 .IDnumber_data").click(function(e){
        e.preventDefault();
        $(".loading-layer").show();
        $.ajax({
            type: "post",
            url: "/admin/auth/changJingData",
            data: {
                image:$('#admin.step2 .IDnumber').attr("src"),
                type:1
            },
            dataType: "json",
            success: function(data){
                if(data.errorCode==0){
                    if(data['result']==""){
                        alert("获取不到数据！");
                        $(".loading-layer").hide();
                        return false;
                    }
                    var card = JSON.parse(data['result']['data']);
                    $("#form_auth_IDAddress").val(card['address']);
                    $("#form_auth_IDNumber").val(card['id_card_number']);
                    $("#form_member_name").val(card['name']);
                    $("#form_member_nation").val(card['race']);
                  
                    if(card.gender=="男"){
                        $("#form_member_sex").val(901);
                    }
                    if(card.gender=="女"){
                        $("#form_member_sex").val(902);
                    }

                    alert("获取成功");
                    $(".loading-layer").hide();
                }else{
                    alert("获取失败");
                    $(".loading-layer").hide();
                }
            }
        })
    })



    $("#admin.step2 .auth_submit").click(function(e){
        e.preventDefault();
        var type1=$("#type1").val();
        var type2=$("#type2").val();
        var type3=$("#type3").val();
        var type4=$("#type4").val();
        var submitstype=$(this).attr("submitstype");
        if(type1 ==''||type1==null){

            alert("请验证手机号");
            return false;

        }
        if(type2 ==''||type2==null){

            alert("请验证是否有犯罪记录");
            return false;

        }
        if(type3 ==''||type3==null){

            alert("请验证驾照是否一致");
            return false;

        }
        if(type4 ==''||type4==null){

            alert("请验证身份证是否一致");
            return false;

        }
        var num=parseInt($("#type1").attr("verifyresult"))+parseInt($("#type2").attr("verifyresult"))+parseInt($("#type3").attr("verifyresult"))+parseInt($("#type4").attr("verifyresult"));

        if(submitstype==1 && num==0){
            alert("第三方审核通过，不能认证失败!");
            return false;
        }
        var verifystr={};
        var verify_results=$(".verify-result");
        for(var i= 0;i<verify_results.length;i++){
            verifystr[$(verify_results[i]).attr("verifytype")]={
                'status':$(verify_results[i]).attr("verifytext"),
                "code":$(verify_results[i]).attr("verifycode")
            };
        }
        $("#form_auth_validateNewResult").val(JSON.stringify(verifystr));
        $("#form_auth_submitType").val($(this).attr("submitstype"));
        if(type1==1&&type2==2&&type3==3&&type4==4){
            $("#admin.step2 form").submit();

        }

    })



    $("#admin.step2 .driver_data").click(function(e){

        e.preventDefault();
        $(".loading-layer").show();
        $.ajax({
            type: "post",
            url: "/admin/auth/changJingData",
            data: {
                image:$('#admin.step2 .driver').attr("src"),
                type:2
            },
            dataType: "json",
            success: function(data){
                if(data.errorCode==0){

                    var card = JSON.parse(data.result.data);
                    var month=0;
                    var day=0;
                    var valid_year=0;
                    var startDate="";
                    if(card['valid_for']!=''){
                        valid_year = parseInt(card.valid_for);
                    }
                    if(card["valid_from"]&& card["valid_from"]['month'] && card["valid_from"]['day']&&card['valid_from']['year']){
                        month = card.valid_from.month;
                        day = card.valid_from.day;
                        if(month<10){
                            month = '0'+month;
                        }
                        if(day<10){
                            day = '0'+day;
                        }
                        startDate = card.valid_from.year+'-'+month+'-'+day;
                    }
                    else{
                        alert("获取日期信息不全！");
                    }

                    valid_year = valid_year?valid_year:0;
                    var endData = '';
                    if(valid_year>0 && card.valid_from['year']!=''){
                        endData = (parseInt(card.valid_from.year)+valid_year)+'-'+month+'-'+day;
                    }
                    $("#form_auth_licenseNumber").val(card['license_number']);
                    $("#form_auth_licenseStartDate").val(startDate);
                    $("#form_auth_licenseValidYear").val(valid_year);
                    $("#form_auth_licenseEndDate").val(endData);
                    $("#form_auth_licenseAddress").val(card.address);

                    if(card.address){
                        var arr = getProvinceCity(card.address);
                        $("#form_auth_licenseProvince").val(arr[0]);
                        $("#form_auth_licenseCity").val(arr[1]);
                    }
                    alert("获取成功");
                }else{
                    alert("获取失败");
                }
                $(".loading-layer").hide();
            }
        })
          })

    $("#admin.step2 .IDnumber_auth,#admin.step2 .driver_auth,#admin.step2 .mobile_auth,#admin.step2 .crime_auth").click(function(e){
        e.preventDefault();
        var type = $(this).attr('type');
        var mobile = $("#mobile").val();
        var name = $("#form_member_name").val();
        var IDnumber = $("#form_auth_IDNumber").val();
        var province = $("#form_auth_licenseProvince").val();
        var city = $("#form_auth_licenseCity").val();

        if(name==null||name==''){
            $(".verify-result4").text("不一致");
            $(".verify-result4").removeClass("color-gray");
            $(".verify-result4").removeClass("color-green");
            $(".verify-result4").addClass("color-red");
            alert("无效姓名，请先获取身份证信息");
            return  false;
        }

        if(IDnumber==null||IDnumber==''){
            $(".verify-result4").text("不一致");
            $(".verify-result4").removeClass("color-gray");
            $(".verify-result4").removeClass("color-green");
            $(".verify-result4").addClass("color-red");
            alert("无效身份证，请先获取身份证信息");
            return  false;
        }

        if(type==3){
            if(province==null||city==null){
                $(".verify-result4").text("不一致");
                $(".verify-result4").removeClass("color-gray");
                $(".verify-result4").removeClass("color-green");
                $(".verify-result4").addClass("color-red");
                alert("无效省份城市信息，请根据驾照信息填写");
                return  false;
            }
        }



        $("#type"+type).val(type);

        var dom= $(".verify-result"+type);
        $(".loading-layer").show();
        $.ajax({
            type: "post",
            url: "/admin/auth/changJingAuth",
            data: {
                mobile:mobile,
                type:type,
                name:name,
                IDnumber:IDnumber,
                province:province,
                city:city
            },
            dataType: "json",
            success: function(data){
                if(data.errorCode==0){
                    if(! data['result'] ||! data['result']['data']){
                        alert("获取不到数据");
                        return false;
                    }
                    var result;
                    if(typeof(data.result.data)=='string'){
                        result = JSON.parse(data.result.data);
                    }
                    else  result =data.result.data;
                    $(dom).attr("verifycode",result['code']);
                    $(dom).attr("verifytext",result['status']);
                    
                    if((result['code']=="200" && result['status']=="一致") ||(result['code']=="200" && result['status']=="无不良记录")){
                 
                        $("#type"+type).attr("verifyresult","0");
                        var str='一致';
                        if(result['status']){
                            str=result['status'];
                        }
                       else if(type==2){
                            str="无不良记录";
                        }

                        $(dom).text(str);
                        $(dom).removeClass("color-gray");
                        $(dom).removeClass("color-red");
                        $(dom).addClass("color-green");
                    }
                
                    else {
                        var str="不一致";
                        if(result['status']){
                            str=result['status'];
                        }
                        else if(result['status']=="系统无记录"){
                            str="系统无记录";
                        }
                      /*  else if(type==2){
                            str="存在不良记录";
                        }*/

                        $(dom).text(str);

                        $(dom).removeClass("color-gray");
                        $(dom).removeClass("color-green");
                        $(dom).addClass("color-red");
                        $("#auto_bundle_managerbundle_auth_step2_validateError").val(1);
                        $("#type"+type).attr("verifyresult","1");
                    }
                   

                    alert(result.status);
                }else{
                    alert("获取失败");
                }
                $(".loading-layer").hide();
            }
        })
    })
});


function getProvinceCity(string) {
    var str1 = "市";
    var str2 = "省";
    var str3 = "自治区";
    var str4 = "自治州";
    var pindex1 =cindex1 = cindex2 = pindex2 = pindex3 = -1;
    var province = pro = city = "";
    index = string.indexOf(str1);
    pindex1 = string.indexOf(str1);
    pindex2 = string.indexOf(str2);
    pindex3 = string.indexOf(str3);
    if(pindex1>-1){
        province = string.split(str1)[0];
        if(province!=''){
            var arr=["北京","上海","天津","重庆"];
            if(arr.toString().indexOf(province)>-1){
                pro = province+"市";
                city = pro;
            }
        }
    }
    if(pindex2>-1){
        province = string.split(str2)[0];
        if(province!=''){
            pro = province+str2;
            var str = string.replace(pro,'');
            if(str.indexOf(str4)>-1){
                var ci1 = str.split(str4)[0];
                if(ci1 !=""){
                    city = ci1+str4;
                }
            }else if(str.indexOf(str1)>-1){
                var ci2 = str.split(str1)[0];
                if(ci2!=""){
                    city = ci2+str1;
                }
            }
        }
    }

    if(pindex3>-1){
        province = string.split(str3)[0];
        if(province!=''){
            pro = province+str3;
            var str = string.replace(pro,'');
            if(str.indexOf(str4)>-1){
                var ci1 = str.split(str4)[0];
                if(ci1 !=""){
                    city = ci1+str4;
                }
            }else if(str.indexOf(str1)>-1){
                var ci2 = str.split(str1)[0];
                if(ci2!=""){
                    city = ci2+str1;
                }
            }
        }
    }
    return [pro,city];
}



$("#admin.auth.show .reset,#admin.member.authshow .reset").bind("click",function(e){
    e.preventDefault();
    $(".apply-ok").show();
});
$("#admin.auth.show .apply-ok .cancel,#admin.member.authshow .apply-ok .cancel").bind("click",function(e){
    $(".apply-ok").hide();
});
$("#admin.auth.show .apply-ok .submit-do,#admin.member.authshow .apply-ok .submit-do").bind("click",function(e){
    window.location.href=$(".reset").attr("href");
});
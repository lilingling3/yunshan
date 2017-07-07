$("#admin.rechargeActivity.list").delegate(".delete", "click", function(e){
    e.preventDefault();
    $(".apply-ok").show();
    var rechargeid=$(this).attr("rechargeid")
    $(".apply-ok").attr("rechargeid",rechargeid);
});
$("#admin.rechargeActivity.list").delegate(".refund-cancel", "click", function(){
    $(".apply-ok").css("display", "none");
});
$("#admin.rechargeActivity.list").delegate(".submit-do", "click", function(){
    $(".apply-ok").css("display", "none");
    var that=this;
    var rechargeid=$(".apply-ok").attr("rechargeid");
    var str='delete'+rechargeid;
    console.log($("#userID").val());
    var tr=$('.'+str).parents("tr");
    $.post("/api/recharge/activity/delete",
        {
            userID:$("#userID").val(),
            rechargeActivityId:rechargeid
        },
        function(data,status){
            if(status){
                console.log(data);
                if (data.errorCode == 0) {
                    window.location.reload();
                    // $(tr).remove();
                }
                else {
                    alert(data.errorMessage);
                }
            }

        });
});
$("#admin.rechargeActivity.new .field .submit-tj").bind("click", function(e){
    e.preventDefault();
    var starttime=$("#form_startTime").val();
    var endtime=$("#form_endTime").val();
    if(starttime==''||endtime==""){
        alert("请选择时间！");
        return false;
    }
    var label1=$(".label1").siblings("input").val();
    var label2=$(".label2").siblings("input").val();
    var label3=$(".label3").siblings("input").val();
    var label4=$(".label4").siblings("input").val();
    var label5=$(".label5").siblings("input").val();
    var label6=$(".label6").siblings("input").val();

    var arr=[label1,label2,label3,label4,label5,label6];
    var json=[];
    $.post("/api/recharge/activity/validate",
        {
            userID:$('#userID').val(),
            activity:$('#activityID').val(),
            startime:starttime,
            endtime:endtime
        },
        function(data,status){
            if(status){
                console.log(data);
                if (data.errorCode == 0) {

                    for(var i=0;i<arr.length;i++){

                        if ($.inArray(arr[i],json) == -1) {

                            json[i] = arr[i];

                        } else {
                            alert(parseInt(i+1)+"阶梯数据重复！");
                            return false;
                        }
                    }

                    $('form').submit();
                }
                else {
                    alert(data.errorMessage);
                    return false;
                }
            }
            else {
                return false;
            }
        });





});
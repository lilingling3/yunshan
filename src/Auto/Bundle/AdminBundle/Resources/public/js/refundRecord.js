
if((window.location.pathname).substr(0,23)=="/admin/refundRecord/new"){

    $(function() {

        $("#applyRefund").click(function(e){
            e.preventDefault();
            $(".error").text("");
            var refundInstrustions = $("#refundInstrustions").val();
            if(refundInstrustions == ''){
                //$(".error").addClass("show");
                $(".error").text("请输入退款说明!");
                setTimeout("$('.error').text('');", 2000 );
                return false;
            }

            $(".apply-refund").css("display", "block");


        });

        $(".refund-cancel").on("click", function () {
            $(this).parents(".door").css("display", "none");
        })
        $(".apply-refund").delegate(".apply", "click", function () {
            $("#applyRefundFrom").submit();
        });

    })

}

if((window.location.pathname).substr(0,25)=="/admin/refundRecord/check"){

    $(function() {

        $(".applyFailed").click(function(e){
            e.preventDefault();
            $(".error").text("");
            var checkFailedReason = $("#checkFailedReason").val();
            if(checkFailedReason == ''){
                $(".error").text("请输入审核失败原因!");
                setTimeout("$('.error').text('');", 2000 );
                return false;
            }

            $(".apply-failed").css("display", "block");


        });

        $(".refund-cancel").on("click", function () {
            $(this).parents(".door").css("display", "none");
        })
        $(".apply-failed").delegate(".submit-do", "click", function () {
            $("#checkFailedFrom").submit();
        });

        $(".applyOk").click(function(e){
            e.preventDefault();
            var returnfunds= $("input[name='actualRefundAmount[]']");
            var deductfunds= $("input[name='refundAmount[]']");
            $(".returnfund").text(0);
            $(".deductfund").text(0);
            var returnfundsvalue= 0,deductfundvalue=0;
            var re = /^[1|2|3|4|5|6|7|8|9]\d+[\d|.]\d*$/;
            for(var i=0;i<returnfunds.length;i++){
                if($(returnfunds[i]).val()==""){
                    alert("请填写金额！");
                    return false;
                }
                else if(! re.test($(returnfunds[i]).val())){
                    alert("金额格式有误！");
                    return false;
                }
                else{
                    returnfundsvalue=returnfundsvalue+Number($(returnfunds[i]).val());
                    $(".returnfund").text(returnfundsvalue);
                }

            }
            for(var i=0;i<deductfunds.length;i++){
                if($(deductfunds[i]).val()==""){
                    alert("请填写金额");
                    return false;
                }
                else if(! re.test($(deductfunds[i]).val())){
                    alert("金额格式有误！");
                    return false;
                }
                else {
                    deductfundvalue=deductfundvalue+Number($(deductfunds[i]).val());
                    $(".deductfund").text(deductfundvalue);
                }

            }
            $(".apply-ok").css("display", "block");


        });

        $(".apply-ok").delegate(".submit-do", "click", function () {
            $("#checkOkFrom").submit();
        });


        $("#addRefundOrder").click(function(e){
            e.preventDefault();
            var html = '<tr>'
                +'<td>'
                +'<select class="ui search dropdown" name="channel[]">'
                +'<option value="weixin">微信</option>'
                +'<option value="zhifubao">支付宝</option>'
                +'</select>'
                +'</td>'
                +'<td><div class="ui input"><input type="text" name="tradeNo[]" placeholder="渠道流水号"></div></td>'
                +'<td><div class="ui input"><input type="text" name="actualRefundAmount[]" placeholder="100"></div></td>'
                +'<td><div class="ui input"><input type="text" name="refundAmount[]" placeholder="120"></div></td>'
                +'</tr>';
            $("#order_tbody").append(html);
        });

    })

}

if((window.location.pathname).substr(0,26)=="/admin/refundRecord/refund"){
    //$(".refund-do").click(function(e){
    //    e.preventDefault();
    //    $.post(
    //        "/admin/refundRecord/refundDo",
    //        {name:name,sex:sex,nation:nation,IdNumber:IdNumber,address:address,mobile:mobile},
    //        function (result) {
    //            window.location.reload();
    //        //$("#form_AuthMember_validateResult").val(result.validateResult);
    //    })
    //});

    var href_str;
    $(".retrunbtn").bind("click",function(e){
        e.preventDefault();
        var tds=$(this).parents("table").find("td");
        $(".channel").text($(tds[0]).text());
        $(".serialnum").text($(tds[1]).text());
        $(".returnfund").text($(tds[2]).text());
        $(".deductfund").text($(tds[3]).text());
        $(".returntime").text($(tds[4]).text());
        $(".apply-ok").show();
        href_str=$(this).attr("href");
    });

    $(".apply-ok .refund-cancel").bind("click",function(){
        $(".apply-ok").hide();
    });

    $(".apply-ok .submit-do").bind("click",function(){
        location.href=href_str;
    });
}




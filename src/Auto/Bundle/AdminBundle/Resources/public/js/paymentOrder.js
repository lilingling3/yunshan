
if((window.location.pathname).substr(0,23)=="/admin/paymentOrder/new"){

    $(function() {

        $("#form_kind").on("change", function () {
            var kind = $("#form_kind").val();
            if ( kind == 99) {
                $('#payment_order_reason').show();
                $('#form_reason').attr('required',true);
            } else {
                $('#payment_order_reason').hide();
                $('#form_reason').val('');
                $('#form_reason').attr('required',false);
            }

        });

        //$(".paymentOrderNewSubment").click(function(e){
        //    e.preventDefault();
        //    var kind = $("#form_kind").val();
        //    var reason = $("#form_reason").val();
        //
        //    if(kind == 99){
        //        $(".error").addClass("show");
        //        $(".error").text("姓名不能为空!");
        //        return false;
        //    }
        //
        //});

    })

    $(".paymentOrderNewSubment").bind("click",function(e){
        e.preventDefault();
        var re = /^1[3|4|5|8|7]\d{9}$/;
        var re1 = /^[1|2|3|4|5|6|7|8|9]\d*$/;
        if(! re.test($("#mobile").val())){
            alert("手机号有误！");
            return false;
        }
        if(!re1.test($("#form_amount").val())){
            alert("缴费金额有误！");
            return false;
        }

        $.post("/admin/member/getName",
            {
                mobile:$("#mobile").val()
            },
            function(data,status){
                if(status) {

                    $(".payorder-ok .phone").text($("#mobile").val());
                    $(".payorder-ok .name").text(data.name);
                    $(".payorder-ok .money").text($("#form_amount").val());
                    $(".payorder-ok .paytype").text($("#form_kind").children('option:selected').text());
                    $(".payorder-ok").show();
                }
            });
    });

    $(".payorder-ok .payorder-do").bind("click",function(){
        $("form").submit();
    });

    $(".payorder-ok .payorder-cancel").bind("click",function(){
        $(".payorder-ok").hide();
    });

}







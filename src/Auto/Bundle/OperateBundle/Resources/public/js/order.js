$(function(){
    $(".rental_order.show .order-details .in-row .in-t").each(function(){
        if($(this).text()!='-'){
            var time=$(this).text().split(" ");
            var time_str="<p>"+time[0]+"</p><p>"+time[1]+"</p>";
            $(this).html(time_str);
        }
    });

    $("#page.rental_order.check,#page.rental_order.end").delegate("#check-button","click",function(){

        var msg= '';
        $('.check-items input[type="checkbox"]').each(function(){

            if(!$(this).is(":checked")){
                msg = '请检查确认项';
            }

            });


        if($('.mileage input').val()==''){
            msg = '请填写续航里程';
        }

        if(msg ==''){
            var r=confirm("确认提交?")
            if (r==true)
            {
                return true;
            }
            else
            {
                return false;
            }
        }else{
            alert(msg);
            return false;
        }




    });

    $("#page.rental_order.show").delegate(".cancel-order","click",function(){

        var order = $(this).attr('order-data');
        var user = $(this).attr('user-data');

        var r=confirm("确认取消该订单?")
        if (r==true)
        {

            $.post("/api/order/cancel",
                {
                    userID:user,
                    orderID:order
                },
                function(data,status){
                    if(status){
                        if(data.errorCode==0){
                            alert('已完成');
                            window.location.reload();

                        }else{
                            alert(data.errorMessage);
                        }

                    }

                });
        }
        else
        {
            return false;
        }


    });

    $("#page.rental_order.show").delegate(".get-car","click",function(){

        var order = $(this).attr('order-data');
        var user = $(this).attr('user-data');
        var status = 3;

        var r=confirm("确认使用?")
        if (r==true)
        {

            $.post("/api/order/lock",
                {
                    userID:user,
                    orderID:order,
                    status:status
                },
                function(data,status){
                    if(status){
                        if(data.errorCode==0){
                            alert('已完成');
                            window.location.reload();

                        }else{
                            alert(data.errorMessage);
                        }

                    }

                });
        }
        else
        {
            return false;
        }


    });

    $("#page.rental_order.show").delegate(".back-car","click",function(){

        var order = $(this).attr('order-data');
        var user = $(this).attr('user-data');
        var status = 3;

        var r=confirm("确认还车?")
        if (r==true)
        {

            $.post("/api/order/back",
                {
                    userID:user,
                    orderID:order,
                    status:status
                },
                function(data,status){
                    if(status){
                        if(data.errorCode==0){
                            alert('已完成');
                            window.location.reload();

                        }else{
                            alert(data.errorMessage);
                        }

                    }

                });
        }
        else
        {
            return false;
        }


    });

    $("#page.rental_order.end_order").delegate(".rental-submit","click",function(){
        var r=confirm('人工还车会造成断电请确认！');
        var id = $("#rental-car-id").val();
        var user_id = $("#user-id").val();
        /*$("#page.rental_order.end_order .alert-min").show();
        $(".all").show();*/
        if(r==true){
            $(".end_order_data").submit();
        }
    });
    $("#page.rental_order.end_order").delegate(".alert-min .alert-cancel","click",function(){
        $("#page.rental_order.end_order .alert-min .input-error").text("");
        $("#page.rental_order.end_order .alert-min").hide();
        $(".all").hide();
    });
    $("#page.rental_order.end_order .alert-min").delegate(".alert-submit","click",function(){
        var car_license_num=$.trim($("#car-license").val());
        var car_str=car_license_num.substr(1);
        var exg=new RegExp("^"+car_str,'gi');
        var car=$.trim($("#page.rental_order.end_order .alert-min  .input-data").val());
        if(car==""){
            $("#page.rental_order.end_order .alert-min .input-error").text("请输入车牌号");
            return false;
        }
        if(exg.test(car)){
            $(".online-off .input-error").text("");
             $(".end_order_data").submit();
        }
        else{
            $("#page.rental_order.end_order .alert-min .input-error").text("你输入的车牌号有误");
            return false;
        }
    });

});


$("#page.rental_order.check").delegate("#charging-submit","click",function(){

    var msg= '';

    $('.charging-car input[type="text"]').each(function(){

        if($(this).val()==''){
            msg = '请填写相关数据';
        }
    });
    if(msg ==''){
        var r=confirm("确认提交?");
        if (r==true)
        {
            return true;
        }
        else
        {
            return false;
        }
    }else{
        alert(msg);
        return false;
    }

});
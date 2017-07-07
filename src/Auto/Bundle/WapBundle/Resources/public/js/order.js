
var order= $("#page.rental_order.show .open").attr('order-data');
if(sessionStorage .getItem('sharecar'+order)==1){
    $("#page.rental_order.show .key-notice").show();
    sessionStorage .setItem('sharecar'+order, 2);
}



$("#page.rental_order.show").delegate(".car-open .open","click",function(){
    var order =$(this).attr('order-data');
    var member =$(this).attr('user-data');
    if($(".use-car .back").length>0){
        $("#page.rental_order.show .shadow").show();

        $.post("/api/order/lock",
            {
                userID:member,
                orderID:order,
                status:0
            },
            function(data,status){
                if(status){
                    if (data.errorCode == 0||data.errorCode == -90001 ) {
                        if(data.errorCode == 0){
                            alert('开门成功');
                            if (!sessionStorage.getItem('sharecar' + order)) {
                                sessionStorage.setItem('sharecar' + order, 1);
                            }
                        }
                        window.location.reload();
                    }

                    else{

                        alert(data.errorMessage);
                        $("#page.rental_order.show .shadow").hide();
                    }

                }

            });
    }
    else {
        var r = confirm("开门取车后将不能取消订单!");

        if (r == true) {

            $("#page.rental_order.show .shadow").show();

            $.post("/api/order/lock",
                {
                    userID: member,
                    orderID: order,
                    status: 0
                },
                function (data, status) {
                    if (status) {
                        if (data.errorCode == 0||data.errorCode == -90001 ) {
                            if(data.errorCode == 0){
                                alert('开门成功');
                                if (!sessionStorage.getItem('sharecar' + order)) {
                                    sessionStorage.setItem('sharecar' + order, 1);
                                }
                            }
                            window.location.reload();
                        }

                        else {

                            alert(data.errorMessage);
                            $("#page.rental_order.show .shadow").hide();
                        }

                    }

                });
            return false;
        }
        else {
            return false;
        }
    }
});



$("#page.rental_order.show").delegate(".car-open .find","click",function(){

    var order =$(this).attr('order-data');
    var member =$(this).attr('user-data');
    $("#page.rental_order.show .shadow").show();
    $.post("/api/order/find",
        {
            userID:member,
            orderID:order
        },
        function(data,status){
            if(status){

                if(data.errorCode==0){

                    alert('寻车成功');
                    $("#page.rental_order.show .shadow").hide();

                }else{

                    alert(data.errorMessage);
                    $("#page.rental_order.show .shadow").hide();
                }

            }

        });


});



$("#page.rental_order.back").delegate(".back-notice button","click",function(){

    $("#page.rental_order.back .shadow").show();
    $(this).parents('form').submit();


});

$("#page.rental_order.show").delegate(".key-notice button","click",function(){
	$(".key-notice").hide();
    $(".allshade").hide();
});


$("#page.rental_order.show").delegate(".use-car .introduction","click",function(){
    $(".key-notice").hide();
    $(".key-notice").show();
});

$("#page.rental_order.show").delegate(".car-button .cancel","click",function(){
	var order = $(this).attr('order-data');
    var user = $(this).attr('user-data');
    var orderFreeCancelCount,offset,alert_str;
    //获取用户取消了多少次
    $.post("/api/account/user",
        {
            userID:user
        },
        function(data,status){

            if(status){
                if(data.errorCode==0){
                    orderFreeCancelCount=data["user"]["orderFreeCancelCount"];
                    $.post("/api/order/getRentOrder",
                        {
                            userID:user,
                            orderID:order
                        },
                        function(data,status){

                            if(status){
                                if(data.errorCode==0){
                                    offset=data["order"]["offset"];
                                    if(offset<900 && orderFreeCancelCount<2){
                                        alert_str="您确定要取消该行程吗?";
                                    }
                                    else if(offset<900 && orderFreeCancelCount>=2){
                                        alert_str="您已超出每天免费取消次数,取消行程将收取租赁费用,是否确定取消行程?";
                                    }
                                    else if(offset>=900){
                                        alert_str="已超出免费取消时间范围,取消行程将收取租赁费用,是否确定取消行程?";
                                    }
                                    var r=confirm(alert_str);

                                    if (r==true)
                                    {
                                        $.post("/api/order/cancel_15",
                                            {
                                                userID:user,
                                                orderID:order
                                            },
                                            function(data,status){
                                                
                                                if(status){

                                                    if(data.errorCode==0 || data.errorCode==-50008 || data.errorCode==-50003|| data.errorCode==-50013){
                                                        window.location.reload();
                                                        return false;
                                                    }else{

                                                        alert(data.errorMessage);
                                                        location.reload();
                                                    }
                                                }
                                            });
                                    }
                                    else
                                    {
                                        return false;
                                    }

                                }else{

                                    alert(data.errorMessage);
                                    location.reload();
                                }
                            }
                        });


                }else{

                    alert(data.errorMessage);
                    location.reload();
                }
            }
        });



});



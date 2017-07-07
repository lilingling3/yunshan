var recharge_location_ref=/^\/mobile\/recharge\/list/;
if(window.location.pathname=="/mobile/recharge/show") {
    $("#page.recharge.show").delegate(".center-recharge .center-one .center-two", "click", function () {
        $(".center-two").removeClass('divOver');
       $(this).addClass('divOver');

        $(".bottom-recharge .code").html('充值'+parseFloat($(this).attr('amount'))+"元");
        $("#amount").val(parseFloat($(this).attr('amount')));
        if($("#rechargeActivity").val()==0){
            $(".top-recharge .account").text('充'+parseFloat($(this).attr('amount'))+',得'+(parseFloat($(this).attr('amount'))+parseFloat($(this).attr('step'))));
        }

    })




    $("#page.recharge.show").delegate(".bottom-recharge .code", "click", function () {
        var member = $("#userID").val();
        if($("#amount").val()!=0){
            $("form").submit();
    }
        else {
            alert("请选择一个充值金额");
        }
    });

}




if(recharge_location_ref.test(window.location.pathname)) {


    $("#page.recharge.list").delegate(".moreorderbtn", "click", function () {
        console.log('222222222');
        var member = $("#userID").val();
        var recharge_page = $(".orderlist").attr("page");
        var recharge_page_count = $(".orderlist").attr("pagecount");
        if (recharge_page_count <= recharge_page) {
            $(".moreorderbtn").remove();

            return false;
        }
        $.post("/api/recharge/record",
            {
                userID: member,
                page: recharge_page
            },
            function (data, status) {
                if (status) {
                    console.log(data);
                    if (data.errorCode == 0) {
                        adddeposts(data["list"]);
                        $(".orderlist").attr("page", ++recharge_page)
                    } else {
                        //alert(data.errorMessage);
                    }
                }
            });
    });

    function adddeposts(list) {
        for(var i= 0,recharge;recharge = list[i++];){
            var a = new createNode(recharge);
            //console.log(a);
            $(a).appendTo(".orderlist");
        }
    }

    function createNode(recharge) {
        var order_li = $("<div class='order-li status102'></div>");
        var row = $("<div class='row rowpadding'></div>");
        $(row).appendTo($(order_li));
        var column = $("<div class='column column-info'></div>");
        $(column).appendTo($(row));
        var item1 = $("<div class='item1'></div>");
        $(item1).appendTo($(column));
        var b = $("<p class='b'>" + recharge.operate + "</p>");
        $(b).appendTo($(item1));
        var time = $("<p class='time'>" + recharge.createTime + "</p>");
        $(time).appendTo($(item1));

        var item = $("<div class='item'></div>");
        $(item).appendTo($(column));

        var status_top102 = $("<span class='status-top102'>" + recharge.amount + "</span>");
        $(status_top102).appendTo($(item));
        var str1=recharge.amount;
        str1=parseFloat(str1).toFixed(2);
        var str1=str1.replace(/(?=(?:\d{3})+(?!\d))/g,',');
        var statusstr = "";
        if (recharge.operateID==1 || recharge.operateID==2) {
            statusstr += "<span class='status-green'> + " + str1 + "</span>";
        }
        else {
            statusstr += "<span class='status-orange'>-" + str1 + "</span>";
        }

        $(item).html(statusstr);
        $(item).appendTo($(column));
        var str=recharge.wallet;
        var str=str.replace(/(?=(?:\d{3})+(?!\d))/g,',');
        var result = $("<p class='result'>" +"余额"+ str +"元"+ "</p>");
        $(result).appendTo($(item));


        return order_li;
    }


    
    //
    //$("#page.recharge.list").ready(function createNode(recharge){
    //
    //
    //});

}



var deposit_location_ref=/^\/mobile\/deposit\/list/;
if(deposit_location_ref.test(window.location.pathname)) {

$("#page.deposit.list").delegate(".right-cont .code", "click", function () {
	var member = $("#userID").val();
	if($("#auth").val()==299){
		$("form").submit();
	}
    else alert("请认证");
});



$("#page.deposit.list").delegate(".right-pay", "click", function () {
	if($("#auth").val()==299){
	    
	 	$.post("/api/deposit/refund",
	        {
	        	userID: $('#userID').val(),
	        },
	        function(data,status){
	            if(data.errorCode==0){

               		window.location.reload();
	            }
	            else{
	                alert(data.errorMessage);
	                return false;
	            }
	        });
	}
 	else {

 		alert("请认证")
 	};

});

$("#page.deposit.list").delegate(".moreorderbtn", "click", function () {
	console.log("ninhao");
	var member = $("#userID").val();
	var deposit_page = $(".orderlist").attr("page");
	var deposit_page_count = $(".orderlist").attr("pagecount");
	if(deposit_page_count <= deposit_page){
		$(".moreorderbtn").remove();

		return false;
	}
	$.post("/api/deposit/list",
			{
				userID: member,
				page: deposit_page
			},
			function (data, status) {
				if (status) {
					if (data.errorCode == 0) {
						adddeposts(data["deposit"]);
						$(".orderlist").attr("page",++deposit_page)
					} else {
						alert(data.errorMessage);
					}
				}
			});
});

	function  adddeposts(deposits){
		for(var i=0,deposit;deposit = deposits[i++];){
			var a=new createNode(deposit);
			console.log(a);
			$(a).appendTo(".orderlist");
		}


	}

	function createNode(deposit) {
		var order_li = $("<div class='order-li status102'></div>");
		var row = $("<div class='row rowpadding'></div>");
		$(row).appendTo($(order_li));
		var column = $("<div class='column column-info'></div>");
		$(column).appendTo($(row));
		var item1 = $("<div class='item1'></div>");
		$(item1).appendTo($(column));
		var statusstr = "";
		if ((deposit.amount - deposit.actualRefundAmount) == 0) {
			statusstr += "<p class='b'>租车押金</p>";
		}
		else if (deposit.refundTime && !deposit.endTime) {
			statusstr += "<p class='b'>租车押金</p>";
		}
		else {
			statusstr += "<p class='b'>租车押金（扣除" + (deposit.amount - deposit.actualRefundAmount) + "元）</p>";
		}

		$(item1).html(statusstr);
		$(item1).appendTo($(column));

		var item = $("<div class='item'></div>");
		//$(item).appendTo($(column));
		$(item).appendTo($(column));
		var p1 = $("<p>" + deposit.payTime + "</p>");
		//$(p1).appendTo($(item1));
		$(p1).appendTo($(item1));
		var status_top = $("<span class='status-top102'>" + "</span>");
		var statusstr1 = "";
		if (deposit.refundTime && deposit.endTime == "") {
			statusstr1 += " <span class='status-black'>" + parseFloat(deposit.amount).toFixed(2)+ "</span>";
		}
		else if (deposit.refundTime && deposit.endTime) {
			var depositiontemp;
			if (deposit.actualRefundAmount == " " || deposit.actualRefundAmount == 0) {
				depositiontemp = -0;
			}
			else {
				depositiontemp = -(parseFloat(deposit.actualRefundAmount).toFixed(2));
			}
			statusstr1 += "<span class='status-orange'>" + (parseFloat(depositiontemp).toFixed(2)) + "</span>";
		}
		else {
			statusstr1 += "<span class='status-green'> + " + (parseFloat(deposit.amount).toFixed(2)) + "</span>";
		}

		$(item).html(statusstr1);
		//var p2 = $("<p>" + "</p>");
		var statusstr2 ="";
		if (deposit.refundTime && deposit.endTime == "") {
			//p2 += "退款中";
			statusstr2 += "<p class='result'>退款中</p>";
		}
		else if(deposit.refundTime && deposit.endTime)
		{
			//p2+="已退款";
			statusstr2 += "<p class='result'>已退款</p>";
		}else{
			//p2+="已缴纳";
			statusstr2 += "<p class='result'>已缴纳</p>";
		}

		$(statusstr2).appendTo($(item));
		return order_li;
	}

	//var a=new createNode();
	//	console.log(a);
     //   $(a).appendTo(".orderlist");

}
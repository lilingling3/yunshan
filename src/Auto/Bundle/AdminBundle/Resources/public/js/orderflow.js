if((window.location.pathname).substr(0,36)=="/admin/orderFlow/recharge/refund/new"){

	var walletamount = 0;

	$('#submit').click(function(){

		var mobile = $('#form_mobile').val();
		if (mobile == '') {

			alert('请输入电话号码');
			return false;
		}

		var recharge = $('#form_chargeamount').val();

		if (recharge == '') {

			alert('请输入退扣余额');
			return false;
		}


		var actualrecharge = $('#form_actualamount').val();

		if (actualrecharge == '') {

			alert('请输入实退金额');
			return false;
		}


		var remark = $('#form_remark').val();

		if (remark == '') {

			alert('请输入备注');
			return false;
		}

		$.post("/admin/orderFlow/check",
            {
            	mobile:mobile,
                recharge:recharge
            },
            function(data,status){

            	if (data.errorCode === 0) {

            		// 填充第一次提醒信息
            		$('#memberMobile').text(mobile);
            		$('#rechargeAmount').text(recharge);

            		$('.refundframe').css('display', 'block');

            		walletamount = data.walletAmount;
            		
            	} else {

            		alert(data.errorMessage);
            	}
            });

	});

	$('input[name="confirm"]').click(function(){

		var res = confirm('扣款后帐号所剩余额为：' + walletamount + "元，您确认扣款吗？");
		if (res == true) {
			$('form').submit();
		}
	});


	$('input[name="cancel"]').click(function(){

		$('.refundframe').css('display', 'none');
	});

















}
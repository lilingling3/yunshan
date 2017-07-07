$(function(){
    
    var depositOrderId = 0;
    var depositAmount  = 0;

    $('.closeWd').click(function(){
        hideFrame();
        $('input[name="refund"]').val("");
        depositOrderId = 0;
    });


    $('.requestRefund').click(function(){

        showFrame();
        depositOrderId = $(this).prev().prev().val();
        depositAmount  = $(this).prev().val();
    });

    $('input[name="submit"]').click(function(){

        var input = $('input[name="refund"]').val();

        if ( input === "" ) {

            alert('请输入实际退款金额！');
            
            return false;
        } else if ( depositAmount < input) {
            alert('输入的钱多于实际押金金额！');
            return false;
        }

        $.post("/admin/deposit/refund",
            {
                OrderId:depositOrderId,
                refundAmount: input
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
    });

    function showFrame() {
        $('.refundframe').css('display','block');
    }

    function hideFrame() {
        $('.refundframe').css('display','none');
    }

})
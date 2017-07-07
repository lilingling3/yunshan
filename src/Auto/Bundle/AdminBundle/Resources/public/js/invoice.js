$(function(){
    $("#invoice").click(function(){
        $('.invoice').css('display','block')
    })

    $("#delivery").click(function(){
        $('.delivery').css('display','block')
    })

    $('.invoice-yes').click(function(){
        $.ajax({
            type: "GET",
            url: "/admin/invoice/update",
            data: {
                id:$('.invoice-yes').data('invoiceid'),
                member:$('.invoice-yes').data('member'),
            },
            dataType: "json",
            success: function(data){
                if(data.errorCode==1){
                    window.location='/admin/invoice/invoicedList';
                }
            }
        })

    })


    $('.delivery-yes').click(function(){
        $.ajax({
            type: "GET",
            url: "/admin/invoice/deliveryUpdate",
            data: {
                id:$('.delivery-yes').data('invoiceid'),
                member:$('.delivery-yes').data('member'),
                deliveryCompany : $("#deliveryCompany").val(),
                deliveryNumber : $("#deliveryNumber").val()
            },
            dataType: "json",
            success: function(data){
                if(data.errorCode==1){
                    window.location='/admin/invoice/invoicedList';
                }
            }
        })

    })




})
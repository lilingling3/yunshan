$(function() {
    $("#area").on("change", function () {
        var showType = 'select';
        var getType = 'online';
        if((window.location.pathname).substr(0,19)=="/admin/operator/set" || (window.location.pathname).substr(0,25)=="/admin/regionOperator/set"){
            showType = 'checkbox';
            getType = 'all';
        }
        if((window.location.pathname).substr(0,28)=="/admin/statistics/operateTwo"){
            getType = 'all';
        }

        $.ajax({
            type: "GET",
            url: "/admin/statistics/station",
            data: {
                gettype:getType,
                areaid:$("#area").val()
            },
            dataType: "json",
            success: function(data){
                var arr = data.data;
                var html = '';
                if(data.count){
                    for(i=0;i<data.count;i++){
                        if(showType == 'checkbox'){
                            if(i == 0){
                                html += '<table class="ui celled table"><tbody>';
                            }
                            if(i%3 == 0){
                                html += '<tr>'
                            }
                            html += '<td><div class="ui checkbox">'
                                +'<input type="checkbox" name="rental_station[]" value="'+arr[i].id+'"/><label>'+arr[i].name+'</label>'
                                +'</div></td>';
                            if(i%3 == 2 || i+1 == data.count){
                                html += '</tr>'
                            }
                            if(i+1 == data.count){
                                html += '</tbody></table>';
                            }
                        }else{
                            html += '<option value="'+arr[i].id+'" >'+arr[i].name+'</option>';
                        }
                    }
                }
                $("#rental_station").html(html);
            }
        })
    })
    $(".operate_submit").on("click", function () {
        if ($('#rental_station option:selected').val() == 0) {
            alert("请选择租赁点！");
            return false;
        }
    })

    $(".amount_submit").on("click", function () {
        if (!$('#J-xl').val()) {
            alert("请选择开始时间！");
            return false;
        }
        if (!$('#J-xl2').val()) {
            alert("请选择结束时间！");
            return false;
        }
        if( $('#J-xl').val() > $('#J-xl2').val() ){
            alert("请选择开始时间不能大于结束时间！");
            return false;
        }
    })



    var count=$('#dataCount tr').length;
    console.log('count'+count);
    if(count!=0){
        var n=count-2;
        var m=count-2;
    }else{
        var n=0;
        var m=0;
    }

    for(var i= 1;i < count;i++){
        for(var j= i+1;j < count;j++){
            if($($('#dataCount tr')[i]).data('car-id')==$($('#dataCount tr')[j]).data('car-id')){
                $($('#dataCount tr')[j]).addClass('ndis');
            }

        }
    }

    for(var i= 1;i < count;i++){
        if($($('#dataCount tr')[i]).hasClass('ndis')){
            n=n-1;
        }
    }

    for(var i= 1;i < count;i++){
        for(var j= i+1;j < count;j++){
            if($($('#dataCount tr')[i]).data('cartype-id')==$($('#dataCount tr')[j]).data('cartype-id')){
                $($('#dataCount tr')[j]).addClass('carTypes_two');
            }

        }
    }

    for(var i= 1;i < count;i++){
        if($($('#dataCount tr')[i]).hasClass('carTypes_two')){
            m=m-1;
        }
    }

    var  stay_time= 0,rental_time= 0,day_time= 0,night_time= 0,rental_count= 0,day_count= 0,
    night_count= 0,rental_revenue= 0,coupon_amount= 0,rental_amount=0;
   // var Len=$($('#dataCount tr').hasClass('ndis')).length;
   // console.log('Len'+Len);
   // var effLen=count-Len-2;
    var len=$("#dataCount tbody").children().length;
    console.log('len'+len);
    for(var i=1;i<len;i++){
        if($($('#dataCount tr')[i]).not('.ndis').length!=0){
        stay_time += parseFloat($($('#dataCount tr')[i]).find("td").eq(2).data('stay'));
        rental_time += parseFloat($($('#dataCount tr')[i]).find("td").eq(3).data('rental'));
        //日间出租时长
        day_time += parseFloat($($('#dataCount tr')[i]).find("td").eq(6).data('day'));
        //夜间出租时长
        night_time += parseFloat($($('#dataCount tr')[i]).find("td").eq(9).data('night'));
        //订单数
        rental_count += parseInt($($('#dataCount tr')[i]).find("td").eq(12).text());
        //日间订单数
        day_count += parseInt($($('#dataCount tr')[i]).find("td").eq(15).text());
        //夜间订单数
        night_count += parseInt($($('#dataCount tr')[i]).find("td").eq(18).text());
        //营收
        rental_revenue += parseFloat($($('#dataCount tr')[i]).find("td").eq(21).text());
        //优惠券
        coupon_amount += parseFloat($($('#dataCount tr')[i]).find("td").eq(22).text());
        //实收
        rental_amount += parseFloat($($('#dataCount tr')[i]).find("td").eq(23).text());
        }
    }


    $(".rentalCars").text(n);
    n = (stay_time/24).toFixed(2);
    $(".carTypes").text(m+'种');
    $(".stay_time").text(stay_time.toFixed(2));
    $(".rental_time").text(rental_time.toFixed(2));
    $(".rental_rate").text((((rental_time/stay_time).toFixed(4))*100).toFixed(2)+"%");
    //单车出租时长
    $(".single_rental_time").text(((rental_time/n).toFixed(2)));
    //日间出租时长
    $(".day_time").text(day_time.toFixed(2));
    //日间出租率
    $(".day_rental_rate").text((((day_time/(18*n)).toFixed(4))*100).toFixed(2)+"%");
    //日间单车出租时长
    $(".single_day_time").text((day_time/n).toFixed(2));
    //夜间出租时长
    $(".night_time").text(night_time.toFixed(2));

    //夜间出租率
    $(".night_rental_rate").text((((night_time/(6*n)).toFixed(4))*100).toFixed(2)+"%");
    //夜间单车出租时长
    $(".single_night_time").text((night_time/n).toFixed(2));
    //订单数
    $(".rental_count").text(rental_count);
    //频次
    $(".frequency").text((rental_count/n).toFixed(2));
    //单均时长
    if(rental_count==0){
        $(".single_order_time").text(0.00);
    }else{
        $(".single_order_time").text((rental_time/rental_count).toFixed(2));
    }

    //日间订单数
    $(".day_count").text(day_count);
    //日间频次
    $(".day_frequency").text((day_count/n).toFixed(2));
    //日间单均时长
    if(day_count==0){
        $(".day_single_order_time").text(0.00);
    }else{
        $(".day_single_order_time").text((day_time/day_count).toFixed(2));
    }

    //夜间订单数
    $(".night_count").text(night_count);
    //夜间频次
    $(".night_frequency").text((night_count/n).toFixed(2));
    //夜间单均时长
    if(night_count==0){
        $(".night_single_order_time").text(0.00);
    }else{
        $(".night_single_order_time").text((night_time/night_count).toFixed(2));
    }

    //营收
    $(".rental_revenue").text(rental_revenue.toFixed(2));
    //优惠券
    $(".coupon_amount").text(coupon_amount.toFixed(2));
    //实收
    $(".rental_amount").text(rental_amount.toFixed(2));
    //车均营收
    $(".single_car_revenue").text((rental_revenue/n).toFixed(2));
    //单均收入
    if(rental_count==0){
        $(".single_order").text(0);
    }else{
        $(".single_order").text((rental_revenue/rental_count).toFixed(2));
    }

    $("#dataCount").css('display','block');


    //获取参数
    function getQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]); return null;
    } ;
    //定位
    console.log($('.sts_over').length);
    if($('.sts_over').length!=0){
    function getCar(status) {
        $.ajax({
            type: "get",
            url: "/admin/statistics/car",
            data: {
                rentalStation: getQueryString('rental_station'),
                status: status
            },
            dataType: "json",
            success: function(data){
                if (data.status == 300) {
                    $('.sts_over_bottom p span.stic_loc_waiting').text(data.CarCount);
                } else if(data.status == 301) {
                    $('.sts_over_bottom p span.stic_loc_renting').text(data.CarCount);
                }else if(data.status == 702) {
                    $('.sts_over_bottom p span.stic_loc_off').text(data.CarCount);
                }else if(data.status == 404) {
                    $('.sts_over_bottom p span.stic_loc_abnormal').text(data.CarCount);
                }else if(data.status == 703) {
                    $('.sts_over_bottom p span.stic_loc_down_rent').text(data.CarCount);
                }
            }
        });
    }
     getCar(300);getCar(301);getCar(702);getCar(404);getCar(703);
    setInterval(function(){getCar(300)}, 10000);
    setInterval(function(){getCar(301)}, 10000);
    setInterval(function(){getCar(702)}, 10000);
    setInterval(function(){getCar(404)}, 10000);
    setInterval(function(){getCar(703)}, 10000);
    setInterval(console.log('hahah'), 10000);
    }
})
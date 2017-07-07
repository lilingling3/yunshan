$("#page.rental_car.change_station  .seek").delegate(".button","click",function(){
    $(".searchByName").submit();
});



$("#page.rental_car.change_station  .container").delegate(".order-row","click",function(){
    var  user=$(this).attr("user"),order=$(this).attr("order"),station=$(this).attr("station");

    $.post("/api/order/changeReturnStation",
        {
            userID:user,
            orderID:order,
            rentalStationID:station
        },
        function(data,status){
            if(status){
                if(data.errorCode==0){

                    window.location.href=$(".top-menu .go-there").attr("href");
                }

                else{

                    alert(data.errorMessage);
                    location.reload();
                }

            }

        });

});

$("#page.rental_car.show #choose-time").delegate(".time-button","click",function(){

    var day = $('#choose-time .day').val();
    var hour = $('#choose-time .hour').val();
    var minute = $('#choose-time .minute').val();
    var id = $('#rental-id').val();
    $("#rental-day").val(day);
    $("#rental-hour").val(hour);
    $("#rental-minute").val(minute);
    $("#choose-time").hide();

    $.post("/api/rentalCar/getCost",
        {
            'rentalID':id,
            day:day,
            hour:hour,
            minute:minute
        },
        function(data,status){
            if(status){
                if(data.errorCode==0){
                    $('.rental-back .choose-time span').html(data.dateTime);
                    $('#rental-cost .amount').text(data.rentalPrice.cost);
                    $('#rental-time').val(data.dateTime);
                    $("#rental-cost").show();

                }else{
                    alert(data.errorMessage)
                }

            }

        });


});

$("#page.rental_car.show").delegate(".rental-notice .use","click",function(){
    var orderFreeCancelCount,alert_str,r;
    var user = $("#userID").val();
    var rentalCarID = $("#rentalCarID").val();
    var returnStationID = $("#returnStationID").val();
    $.post("/api/account/user",
        {
            userID:user
        },
        function(data,status){

            if(status){
                if(data.errorCode==0){

                    orderFreeCancelCount=data["user"]["orderFreeCancelCount"];
                  if(orderFreeCancelCount<2){
                      alert_str="确认使用后将开始计费，15分钟内可免费取消行程。每天可免费取消2次";
                  }
                    else {
                      alert_str="确认使用后将开始计费，您今日已取消行程2次，再次取消将收取租赁费用。";
                  }
                r=confirm(alert_str);
                    if (r==true)
                    {
                       /* $('.rental-notice form').submit();*/
                        $.post("/api/rentalCar/order",
                            {
                                rentalCarID:rentalCarID,
                                userID:user,
                                returnStationID:returnStationID
                            },
                            function(data,status){
                                if(status){
                                    if(data.errorCode==0){
                                        window.location.href='/wap/order/rental/show/'+data['order']['orderID'];
                                    }
                                    else if(data.errorCode==-50002){
                                        alert(data.errorMessage);
                                        window.location.href='/wap/order/rental/list';

                                    }
                                    else if(data.errorCode==-60002){
                                        alert(data.errorMessage);
                                        window.location.href='/wap/illegalRecord';

                                    }
                                    else{
                                        alert(data.errorMessage)
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





});


var time_limit=72;
var rental_date = new Date();
var rental_hour = rental_date.getHours();
var rental_minute = rental_date.getMinutes();
var rental_today=addNDays(rental_date,0);
var rental_endday=addNDays(rental_date,time_limit/24);
var rental_end_time=addNDays1(rental_date,time_limit);

/*******/


$("#page.rental_car.cost").delegate(".choose-time","click",function(){
    $("#choose-time").show();
    initTime();
});


$("#page.rental_car.cost #choose-time").delegate(".day","change",function(){

    var ele_day = $('#choose-time .day').val();
    rentalSetHour(ele_day);
    rentalSetMinute();


});

$("#page.rental_car.cost #choose-time").delegate(".hour","change",function() {
    rentalSetMinute();

});




$("#page.rental_car.cost #choose-time").delegate(".time-button","click",function(){
    var myDate = new Date();
    var date_day=myDate.toLocaleDateString().replace(/\//g,'-');


    var day = $('#choose-time .day').val();
    var hour = $('#choose-time .hour').val();
    var minute = $('#choose-time .minute').val();
    var date_time=hour+":"+minute;
    var id = $('#rental-id').val();
    $("#rental-day").val(day);
    $("#rental-hour").val(hour);
    $("#rental-minute").val(minute);
    $("#choose-time").hide();
    $('.rental-back .choose-time .back_time').html(day+" "+date_time);
    $("#page.rental_car.cost .rental-notice .use1").addClass("use");
    $("#page.rental_car.cost .rental-notice .use1").removeClass("use1");
});


$("#page.rental_car.cost .rental-notice").delegate(".use","click",function(){

    var id = $('#rental-id').val();
    var day = $("#rental-day").val();;
    var hour =$("#rental-hour").val();
    var minute =$("#rental-minute").val();
    var ele_this=$(this);
    $.post("/api/rentalCar/getCost",
        {
            rentalID:id,
            day:day,
            hour:hour,
            minute:minute
        },
        function(data,status){

            if(status){

                if(data.errorCode==0){

                    var day_name=$("#rental-cost .title-l b");

                    var day_cost= $('#rental-cost .cost-r b');
                    var all_time=0;
                    for(var i=0;i<data.rentalPrice.rentalPrice.length;i++){

                        if(data.rentalPrice.rentalPrice[i].time>0 ){
                            all_time+=data.rentalPrice.rentalPrice[i].time;

                            $(day_name[i]).text(timeFormat(data.rentalPrice.rentalPrice[i].time));

                        }
                        else {
                            $(day_name[i]).text("00:00:00");
                        }
                        $(day_cost[i]).text(data.rentalPrice.rentalPrice[i].amount);
                    }
                    /*  $('#rental-cost .day-time').text(timeFormat(data.rentalPrice.rentalPrice[0].time));
                     $('#rental-cost .day-cost').text(data.rentalPrice.rentalPrice[0].amount);
                     $('#rental-cost .night-time').text(timeFormat(data.rentalPrice.rentalPrice[1].time));

                     $('#rental-cost .night-cost').text(data.rentalPrice.rentalPrice[1].amount);*/

                    $('#rental-cost .all-time').text(timeFormat(all_time));

                    $('#rental-cost .all-cost').text(data.rentalPrice.cost);
                    $('#rental-time').val(data.dateTime);
                    $(".rental-price").show();

                    //$("#page.rental_car.cost .rental-notice .use").text("再次评估车辆费用");
                    $(ele_this).text("再次评估车辆费用");
                    $(ele_this).addClass("use1");
                    $(ele_this).removeClass("use");

                }else{

                    alert(data.errorMessage)
                }

            }

        });


});



function timeFormat(time){
    var second=time%60;
    var time1=parseInt(time/60);
    var minute=time1%60;
    var hour=parseInt(time1/60);
    if(second<10){
        second="0"+second;
    }
    if(minute<10){
        minute="0"+minute;
    }
    if(hour<10){
        hour="0"+hour;
    }
    ;
    return hour+":"+minute+":"+second;
}


function initTime(){
    rental_date = new Date();

    rental_hour = rental_date.getHours();
    rental_minute = rental_date.getMinutes();
    if(rental_hour==23&&rental_minute>=50){
        rental_date.setHours(0);
        rental_date.setMinutes(0);
        rental_date.setSeconds(0);
        rental_date.setMilliseconds(0);
        rental_date = new Date(rental_date.getTime()+1*24*60*60*1000);
        rental_hour = rental_date.getHours();
        rental_minute = rental_date.getMinutes();
        rental_today=addNDays(rental_date,0);
        rental_endday=addNDays(rental_date,time_limit/24);
        rental_end_time=addNDays1(rental_date,time_limit);
    }
    else if(rental_hour!=23&&rental_minute>=50){
        rental_date.setHours(rental_hour+1);
        rental_date.setMinutes(0);
        rental_date.setSeconds(0);
        rental_date.setMilliseconds(0);
        rental_date = new Date(rental_date.getTime());
        rental_hour = rental_date.getHours();
        rental_minute = rental_date.getMinutes();
        rental_today=addNDays(rental_date,0);
        rental_endday=addNDays(rental_date,time_limit/24);
        rental_end_time=addNDays1(rental_date,time_limit);
    }
    else if(rental_hour!=23&&rental_minute%10==0){
        rental_date.setMinutes(rental_minute+10);
        rental_date.setSeconds(0);
        rental_date.setMilliseconds(0);
        rental_date = new Date(rental_date.getTime());
        rental_hour = rental_date.getHours();
        rental_minute = rental_date.getMinutes();
        rental_today=addNDays(rental_date,0);
        rental_endday=addNDays(rental_date,time_limit/24);
        rental_end_time=addNDays1(rental_date,time_limit);
    }

    rentalSetTime();
    var day=$('#choose-time .day').val();
    rentalSetHour(rental_today);
    rentalSetMinute();
}



//时间相关的函数
function addNDays(date,n){
    var time=date.getTime();
    var newTime=new Date(time+n*24*60*60*1000);
    var year=newTime.getFullYear();
    var month=newTime.getMonth()+1;
    var day=newTime.getDate();
    if(month<10){
        month="0"+month;
    }
    if(day<10){
        day="0"+day;
    }
    var newTimeNum=year+"-"+month+"-"+day;
    return newTimeNum;
};
function addNDays1(date,n){
    var time=date.getTime();
    var newTime=new Date(time+n*60*60*1000);
    var end_time={
        "hour":newTime.getHours(),
        "minutes":newTime.getMinutes()
    }

    return end_time;
};

function rentalSetTime(){


    $(".day").empty();
    //设置day

    var str="";
    var i=0;
    if(rental_hour==23&&rental_minute>50){
        i=1;
    }
    if(time_limit>24){
        for(i;i<=(time_limit/24);i++){
            str+="<option value="+addNDays(rental_date,i)+">"+addNDays(rental_date,i)+"</option>"
        }
    }
    else{
        if(rental_hour==23&&rental_minute>50){
            str="<option value="+addNDays(rental_date,1)+">"+addNDays(rental_date,1)+"</option>";
        }
        else if(hour<(24-time_limit)){
            str="<option value="+addNDays(rental_date,0)+">"+addNDays(rental_date,0)+"</option>";
        }
        else {
            str="<option value="+addNDays(rental_date,0)+">"+addNDays(rental_date,0)+"</option><option value="+addNDays(rental_date,1)+">"+addNDays(rental_date,1)+"</option>";
        }
    }
    $(".day").html(str);
}
//设置小时
function rentalSetHour(day){
    $(".hour").empty();
    var hourflag=time_limit/24;
    //显示的是今天
    var temp=rental_hour;
    if(day==rental_today && hourflag==0){
        while(temp<(time_limit+12)&&temp<24){
            if(temp<10){
                var $option = $("<option>0"+temp+"</option>");
                $option.attr("value","0"+temp);
                $option.appendTo($(".hour"));
            }
            else {
                var $option = $("<option>"+temp+"</option>");
                $option.attr("value",temp);
                $option.appendTo($(".hour"));
            }
            temp++;
        }
    }

    else if(day==rental_today && hourflag!=0){
        while(temp<24){
            if(temp<10){
                var $option = $("<option>0"+temp+"</option>");
                $option.attr("value","0"+temp);
                $option.appendTo($(".hour"));
            }
            else {
                var $option = $("<option>"+temp+"</option>");
                $option.attr("value",temp);
                $option.appendTo($(".hour"));
            }
            temp++;
        }
    }
    //显示的是最后一天，且不是当天
    else if(day==rental_endday&& hourflag!=0){
        for(var i=0;i<=rental_end_time ["hour"];i++){
            if(i<10){
                var $option = $("<option>"+"0"+i+"</option>");
                $option.attr("value","0"+i);
                $option.appendTo($(".hour"));
            }
            else{
                var $option = $("<option>"+i+"</option>");
                $option.attr("value",i);
                $option.appendTo($(".hour"));
            }

        }
    }
    else {
        for(var i=0;i<24;i++){
            if(i<10){
                var $option = $("<option>"+"0"+i+"</option>");
                $option.attr("value","0"+i);
                $option.appendTo($(".hour"));
            }
            else{
                var $option = $("<option>"+i+"</option>");
                $option.attr("value",i);
                $option.appendTo($(".hour"));
            }
        }
    }
}



function rentalSetMinute(){
    var ele_day = $.trim($('#choose-time .day').val());
    var ele_hour = parseInt($.trim($('#choose-time .hour').val()));
    var start_min=0;
    //当天，当前小时
    var m_option="";
    if(ele_day==rental_today && ele_hour==rental_hour){
        if(rental_minute>50){
            start_min=0;
        }else{
            start_min = Math.ceil(rental_minute/10)*10;
        }

        for(var i=start_min;i<60;i=i+10){
            var ii = '';
            if(i<10){
                m_option+='<option value="'+"0"+i+'">0'+i+'</option>';
            }else{
                m_option+='<option value="'+i+'">'+i+'</option>';
            }
        }

    }
    else if(ele_day==rental_endday&& rental_hour==rental_end_time ["hour"]){
        for(var i=0;i<=rental_end_time ["minutes"];i=i+10){
            if(i<10){
                m_option+='<option value="'+"0"+i+'">0'+i+'</option>';
            }else{
                m_option+='<option value="'+i+'">'+i+'</option>';
            }
        }

    }
    else {
        for(var i=0;i<60;i=i+10){
            if(i<10){
                m_option+='<option value="'+"0"+i+'">0'+i+'</option>';
            }else{
                m_option+='<option value="'+i+'">'+i+'</option>';
            }
        }


    }
    $("#choose-time .minute").empty();
    $("#choose-time .minute").append(m_option);
}
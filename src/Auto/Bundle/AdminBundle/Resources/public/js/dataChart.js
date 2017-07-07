$(function(){
    //选择天数
 $('.chart_time .chart_top_a').click(function(){
     var  day=parseInt($(this).data('day'));
    $(this).addClass('chart_top_active').siblings().removeClass('chart_top_active');
     $('#days').val(day);
 })
    //取消天数
    $('.chart_time .chart_top_a').dblclick(function(){
        $(this).removeClass('chart_top_active');
        $('#days').val('');
    })
    //判断是否同时选中了天数和时间
    $('.chart_operate_submit').click(function(){
        if($("#days").val()&&$("#startTime").val()){
            alert('请只选择其中一个条件');
           return false;
        }
    })
    //切换
    $('.chart_order').click(function(){
        $(this).css('color','rgba(255, 102, 0, 1)').siblings().css('color','#000');
        $('#order_cont').removeClass('ndis');
        $('#income_cont').addClass('ndis');
    })
    $('.chart_income').click(function(){
        $(this).css('color','rgba(255, 102, 0, 1)').siblings().css('color','#000');
        $('#income_cont').removeClass('ndis');
        $('#order_cont').addClass('ndis');
    })
    var color=['#E7521B','#E9E5E1','#F7B530','#C3D79B','#95B3D7','#009966','#12f6aa','#1eb6fd','#0fec4d'];
    //车辆分部
    var spreadLen=$("#spread p").length,countCar=1;var doughnut = new Array();
    for(var i=0;i<spreadLen;i++){
        for(var j=i+1;j<spreadLen;j++){
            if($($("#spread p")[i]).data('id')==$($("#spread p")[j]).data('id')){
                $($("#spread p")[j]).addClass('ndis');
                $($("#spread p")[i]).find('.cont_mid_sp2').text(countCar+=1);
            }
            /*else{
                $($("#spread p")[i]).find('.cont_mid_sp2').text(1);
            }*/
        }
    }
    var doughnutIndex=0;var countCars=0;
    for(var i=0;i<spreadLen;i++){
        if(!$($("#spread p")[i]).hasClass('ndis')){
            $($("#spread p")[i]).find('.cont_mid_sp1').css('background',color[doughnutIndex]);
            countCars+=parseInt($($("#spread p")[i]).find('.cont_mid_sp2').text());
            doughnut[doughnutIndex]={
                value : $($("#spread p")[i]).find('.cont_mid_sp2').text(),
                color: color[doughnutIndex],
                label: $($("#spread p")[i]).data('name')
        }
            doughnutIndex+=1
    }
    }

    for(var i=0;i<spreadLen;i++){
        if(countCars!=0){
            $($("#spread p")[i]).find('.cont_mid_sp3').text(((parseInt($($("#spread p")[i]).find('.cont_mid_sp2').text())/countCars)*100).toFixed(2)+'%')
        }else{
            $($("#spread p")[i]).find('.cont_mid_sp3').text((0).toFixed(2)+'%');
        }

    }
    $('#spread').removeClass('ndis');

    var doughnutData =doughnut;

    //下线原因
    var doughnut2Index=0;var doughnut2 = new Array(),countOffs=0;
    for(var i=0;i<5;i++){
        countOffs+=parseInt($($("#offReason p")[i]).find('.cont_mid_sp2').text());
            doughnut2[doughnut2Index]= {
                value: $($("#offReason p")[i]).find('.cont_mid_sp2').text(),
                color: color[i],
                label: $($("#offReason p")[i]).data('name')
            }
            doughnut2Index+=1
        }
    for(var i=0;i<5;i++){
        if(countOffs!=0){
            $($("#offReason p")[i]).find('.cont_mid_sp3').text(((parseInt($($("#offReason p")[i]).find('.cont_mid_sp2').text())/countOffs)*100).toFixed(2)+'%');
        }else{
            $($("#offReason p")[i]).find('.cont_mid_sp3').text((0).toFixed(2)+'%');
        }

    }
    var doughnutData2 =doughnut2;

    //车型占比
    var json={};
    var typeLen=$("#carType p").length,countType=1,doughnut3 = new Array(),doughnut3Index= 0,countTypes=0;
    for(var i=0;i<typeLen;i++){
        var name=$($("#carType p")[i]).data('name');
        if(json[name]){

            json[name]["key"]++;
            json[name]["pos"].push(i);
            /*Array.prototype.push(json[name]["pos"],i);*/
        }
        else{
            json[name]={};

            json[name]["key"]=1;
            json[name]["pos"]=Array();
            json[name]["pos"].push(i);
        }
    }
    for(var i=0;i<typeLen;i++){
        //console.log(json);
        for(var j=i+1;j<typeLen;j++){
            if($($("#carType p")[i]).data('name')==$($("#carType p")[j]).data('name')){
                $($("#carType p")[j]).addClass('ndis');
                $($("#carType p")[i]).find('.cont_mid_sp2').text(json[$($("#carType p")[i]).data('name')]["key"]);
                //console.log(json['众泰芝麻']["key"]);
            }
        }

        //if( $($("#carType p")[i]).find('.cont_mid_sp3').data('name')==$($("#carType p")[i+1]).data('name')){
        //
        //}
    }
    for(var i=0;i<typeLen;i++){
        if(!$($("#carType p")[i]).hasClass('ndis')){
            $($("#carType p")[i]).find('.cont_mid_sp1').css('background',color[doughnut3Index]);
            countTypes+=parseInt($($("#carType p")[i]).find('.cont_mid_sp2').text());
            doughnut3[doughnut3Index]= {
            value: $($("#carType p")[i]).find('.cont_mid_sp2').text(),
            color: color[doughnut3Index],
            label: $($("#carType p")[i]).data('name')
        }
        doughnut3Index+=1;
        }
    }
    for(var i=0;i<typeLen;i++){
        if(!$($("#carType p")[i]).hasClass('ndis')){
            $($("#carType p")[i]).find('.cont_mid_sp3').text(((parseInt($($("#carType p")[i]).find('.cont_mid_sp2').text())/countTypes)*100).toFixed(2)+'%');
        }
    }
    $('#carType').removeClass('ndis');

    var doughnutData3 =doughnut3;
    //车型订单
    var orderLen=$("#Order p").length,countOrder=1,countOrders= 0,doughnut4 = new Array(),doughnut4Index=0;
    for(var i=0;i<orderLen;i++){
        for(var j=i+1;j<orderLen;j++){
            if($($("#Order p")[i]).data('id')==$($("#Order p")[j]).data('id')){
                $($("#Order p")[j]).addClass('ndis');
                $($("#Order p")[i]).find('.cont_mid_sp2').text(countOrder+=1);
            }
            /*else{
                $($("#Order p")[i]).find('.cont_mid_sp2').text(1);
            }*/
        }
        //if( $($("#carType p")[i]).find('.cont_mid_sp3').data('name')==$($("#carType p")[i+1]).data('name')){
        //
        //}
    }
    for(var i=0;i<orderLen;i++){
        if(!$($("#Order p")[i]).hasClass('ndis')){
            $($("#Order p")[i]).find('.cont_mid_sp1').css('background',color[doughnut4Index]);
            countOrders+=parseInt($($("#Order p")[i]).find('.cont_mid_sp2').text());
            doughnut4[doughnut4Index]= {
                value: $($("#Order p")[i]).find('.cont_mid_sp2').text(),
                color: color[doughnut4Index],
                label: $($("#Order p")[i]).data('name')
            }
            doughnut4Index+=1;
        }
    }
    for(var i=0;i<orderLen;i++){
        if(!$($("#Order p")[i]).hasClass('ndis')){
            $($("#Order p")[i]).find('.cont_mid_sp3').text(((parseInt($($("#Order p")[i]).find('.cont_mid_sp2').text())/countOrders)*100).toFixed(2)+'%');
        }
    }
    $('#Order').removeClass('ndis');
    var doughnutData4 =doughnut4;

    //车型收入
    var incomeLen=$("#Income p").length,doughnut5 = new Array(),doughnut5Index= 0,countAmounts=0;
    for(var i=0;i<incomeLen;i++){
        for(var j=i+1;j<incomeLen;j++){
            if($($("#Income p")[i]).data('id')==$($("#Income p")[j]).data('id')){
                $($("#Income p")[j]).addClass('ndis');
            }
        }

    }
    for(var i=0;i<incomeLen;i++){
        if(!$($("#Income p")[i]).hasClass('ndis')){
            $($("#Income p")[i]).find('.cont_mid_sp1').css('background',color[doughnut5Index]);
            countAmounts+=parseInt($($("#Income p")[i]).find('.cont_mid_sp2').text());
            doughnut5[doughnut5Index]= {
                value: $($("#Income p")[i]).find('.cont_mid_sp2').text(),
                color: color[doughnut5Index],
                label: $($("#Income p")[i]).data('name')
            }
            doughnut5Index+=1;
        }
    }
    for(var i=0;i<incomeLen;i++){
        if(!$($("#Income p")[i]).hasClass('ndis')){
            if(countAmounts!=0){
                $($("#Income p")[i]).find('.cont_mid_sp3').text(((parseInt($($("#Income p")[i]).find('.cont_mid_sp2').text())/countAmounts)*100).toFixed(2)+'%');
            }else{
                $($("#Income p")[i]).find('.cont_mid_sp3').text('0.00%');
            }
        }
    }
    $('#Income').removeClass('ndis');
    var doughnutData5 =doughnut5;

    //优惠券抵用
    var doughnutCouponIndex=0;var doughnutCoupon = new Array(),countCoupons=0;
    for(var i=0;i<2;i++){
        $($("#coupunCount p")[i]).find('.cont_mid_sp1').css('background',color[i]);
        countCoupons+=parseInt($($("#coupunCount p")[i]).find('.cont_mid_sp2').text());
        doughnutCoupon[doughnutCouponIndex]= {
            value: $($("#coupunCount p")[i]).find('.cont_mid_sp2').text(),
            color: color[i],
            label: $($("#coupunCount p")[i]).data('name')
        }
        doughnutCouponIndex+=1
    }
    for(var i=0;i<2;i++){
        if(countCoupons!=0){
            $($("#coupunCount p")[i]).find('.cont_mid_sp3').text(((parseInt($($("#coupunCount p")[i]).find('.cont_mid_sp2').text())/countCoupons)*100).toFixed(2)+'%');
        }else{
            $($("#coupunCount p")[i]).find('.cont_mid_sp3').text((0).toFixed(2)+'%');
        }

    }
    var doughnutCoupunData =doughnutCoupon;

    //用户使用频次
    var doughnutFrequencyIndex=0;var doughnutFrequency = new Array(),countFrequencys=0;
    for(var i=0;i<6;i++){
        $($("#Frequency p")[i]).find('.cont_mid_sp1').css('background',color[i]);
        countFrequencys+=parseInt($($("#Frequency p")[i]).find('.cont_mid_sp2').text());
        doughnutFrequency[doughnutFrequencyIndex]= {
            value: $($("#Frequency p")[i]).find('.cont_mid_sp2').text(),
            color: color[i],
            label: $($("#Frequency p")[i]).data('name')
        }
        doughnutFrequencyIndex+=1
    }
    for(var i=0;i<6;i++){
        if(countFrequencys!=0){
            $($("#Frequency p")[i]).find('.cont_mid_sp3').text(((parseInt($($("#Frequency p")[i]).find('.cont_mid_sp2').text())/countFrequencys)*100).toFixed(2)+'%');
        }else{
            $($("#Frequency p")[i]).find('.cont_mid_sp3').text((0).toFixed(2)+'%');
        }

    }
    var doughnutFrequencyData =doughnutFrequency;

    //单次使用时长
    var doughnutSingleTimeIndex=0;var doughnutSingleTime = new Array(),countSingleTimes=0;
    for(var i=0;i<6;i++){
        $($("#singleTime p")[i]).find('.cont_mid_sp1').css('background',color[i]);
        countSingleTimes+=parseInt($($("#singleTime p")[i]).find('.cont_mid_sp2').text());
        doughnutSingleTime[doughnutSingleTimeIndex]= {
            value: $($("#singleTime p")[i]).find('.cont_mid_sp2').text(),
            color: color[i],
            label: $($("#singleTime p")[i]).data('name')
        }
        doughnutSingleTimeIndex+=1
    }
    for(var i=0;i<6;i++){
        if(countSingleTimes!=0){
            $($("#singleTime p")[i]).find('.cont_mid_sp3').text(((parseInt($($("#singleTime p")[i]).find('.cont_mid_sp2').text())/countSingleTimes)*100).toFixed(2)+'%');
        }else{
            $($("#singleTime p")[i]).find('.cont_mid_sp3').text((0).toFixed(2)+'%');
        }

    }
    var doughnutSingleTimeData =doughnutSingleTime;


    //用户所在地区
    var PopDistribution_len=$('#PopDistribution p').length;var users=0;
    for(var i=0;i<PopDistribution_len;i++){
        for(var j=i+1;j<PopDistribution_len;j++){
            if($($('#PopDistribution p')[i]).data('id')==$($('#PopDistribution p')[j]).data('id')){
                users+=1;
                if($($('#PopDistribution p')[i]).val()==0){
                    $($('#PopDistribution p')[i]).addClass('ndis');
                }else{
                    $($('#PopDistribution p')[j]).addClass('ndis');
                }
            }else{
                users=1;
            }
        } $($('#PopDistribution p')[i]).find('.cont_mid_sp2').text(users);
    }
    $('#PopDistribution').removeClass('ndis');var countDis=0;
    for(var i=0;i<PopDistribution_len;i++){
        if(!$($('#PopDistribution p')[i]).hasClass('ndis')){
            countDis+=parseInt($($("#PopDistribution p")[i]).find('.cont_mid_sp2').text());
            $($("#PopDistribution p")[i]).find('.cont_mid_sp3').text(((parseInt($($("#PopDistribution p")[i]).find('.cont_mid_sp2').text())/countDis)*100).toFixed(2)+'%');
        }
    }

    var doughnutPopDistributionIndex=0;var doughnutPopDistribution = new Array()
    for(var i=0;i<PopDistribution_len;i++){
        if(!$($("#PopDistribution p")[i]).hasClass('ndis')){
            $($("#PopDistribution p")[i]).find('.cont_mid_sp1').css('background',color[doughnutPopDistributionIndex]);
            doughnutPopDistribution[doughnutPopDistributionIndex]= {
                value: $($("#PopDistribution p")[i]).find('.cont_mid_sp2').text(),
                color: color[doughnutPopDistributionIndex],
                label: $($("#PopDistribution p")[i]).data('name')
            }
            doughnutPopDistributionIndex+=1
        }
    }
    var doughnutPopDistributionData=doughnutPopDistribution;

    //认证结果
    var doughnutidentifyResultIndex=0;var doughnutidentifyResult = new Array(),countidentifyResults=0;
    for(var i=0;i<3;i++){
        $($("#identifyResult p")[i]).find('.cont_mid_sp1').css('background',color[i]);
        countidentifyResults+=parseInt($($("#identifyResult p")[i]).find('.cont_mid_sp2').text());
        doughnutidentifyResult[doughnutidentifyResultIndex]= {
            value: $($("#identifyResult p")[i]).find('.cont_mid_sp2').text(),
            color: color[i],
            label: $($("#identifyResult p")[i]).data('name')
        }
        doughnutidentifyResultIndex+=1
    }
    for(var i=0;i<3;i++){
        if(countidentifyResults!=0){
            $($("#identifyResult p")[i]).find('.cont_mid_sp3').text(((parseInt($($("#identifyResult p")[i]).find('.cont_mid_sp2').text())/countidentifyResults)*100).toFixed(2)+'%');
        }else{
            $($("#identifyResult p")[i]).find('.cont_mid_sp3').text((0).toFixed(2)+'%');
        }

    }
    var doughnutIdentifyResultData =doughnutidentifyResult;

    //认证失败原因
    var doughnutIdentifyFailIndex=0;var doughnutIdentifyFail = new Array(),countIdentifyFails=0;
    for(var i=0;i<9;i++){
        $($("#identifyFail p")[i]).find('.cont_mid_sp1').css('background',color[i]);
        countIdentifyFails+=parseInt($($("#identifyFail p")[i]).find('.cont_mid_sp2').text());
        doughnutIdentifyFail[doughnutIdentifyFailIndex]= {
            value: $($("#identifyFail p")[i]).find('.cont_mid_sp2').text(),
            color: color[i],
            label: $($("#identifyFail p")[i]).data('name')
        }
        doughnutIdentifyFailIndex+=1
    }
    for(var i=0;i<9;i++){
        if(countIdentifyFails!=0){
            $($("#identifyFail p")[i]).find('.cont_mid_sp3').text(((parseInt($($("#identifyFail p")[i]).find('.cont_mid_sp2').text())/countIdentifyFails)*100).toFixed(2)+'%');
        }else{
            $($("#identifyFail p")[i]).find('.cont_mid_sp3').text((0).toFixed(2)+'%');
        }

    }
    var doughnutIdentifyFailData =doughnutIdentifyFail;

    if(window.location.pathname=="/admin/dataChart/operate" || window.location.pathname=="/admin/regionDataChart/operate"){
        if(spreadLen!=0){
            var ctx2 = document.getElementById("chart-area").getContext("2d");
            window.myDoughnut = new Chart(ctx2).Doughnut(doughnutData, {responsive : true});
        }

        var ctx2 = document.getElementById("chart-area1").getContext("2d");
        window.myDoughnut = new Chart(ctx2).Doughnut(doughnutData2, {responsive : true});
        var ctx2 = document.getElementById("chart-area2").getContext("2d");
        window.myDoughnut = new Chart(ctx2).Doughnut(doughnutData3, {responsive : true});
        var ctx2 = document.getElementById("chart-area3").getContext("2d");
        window.myDoughnut = new Chart(ctx2).Doughnut(doughnutData4, {responsive : true});
        var ctx2 = document.getElementById("chart-area4").getContext("2d");
        window.myDoughnut = new Chart(ctx2).Doughnut(doughnutData5, {responsive : true});

    };
    if(window.location.pathname=="/admin/dataChart/order" || window.location.pathname=="/admin/regionDataChart/order"){
        var ctx2 = document.getElementById("chartOrder-area1").getContext("2d");
        window.myDoughnut = new Chart(ctx2).Doughnut(doughnutCoupunData, {responsive : true});
        var ctx2 = document.getElementById("chartOrder-area2").getContext("2d");
        window.myDoughnut = new Chart(ctx2).Doughnut(doughnutFrequencyData, {responsive : true});
        var ctx2 = document.getElementById("chartOrder-area3").getContext("2d");
        window.myDoughnut = new Chart(ctx2).Doughnut(doughnutSingleTimeData, {responsive : true});
        var ctx2 = document.getElementById("chartOrder-area4").getContext("2d");
        window.myDoughnut = new Chart(ctx2).Doughnut(doughnutPopDistributionData, {responsive : true});
    }
    if(window.location.pathname=="/admin/dataChart/identification" || window.location.pathname=="/admin/regionDataChart/identification"){
        var ctx2 = document.getElementById("chartIdentify-area1").getContext("2d");
        window.myDoughnut = new Chart(ctx2).Doughnut(doughnutIdentifyResultData, {responsive : true});
        var ctx2 = document.getElementById("chartIdentify-area2").getContext("2d");
        window.myDoughnut = new Chart(ctx2).Doughnut(doughnutIdentifyFailData, {responsive : true});
    }


//订单统计
    var randomScalingFactor = function(){ return Math.round(Math.random()*100)};
   var order_input_len=$('.chart_order_input').length;
    for(var i=0;i<order_input_len;i++){
        for(var j=i+1;j<order_input_len;j++){
            if($($('.chart_order_input')[i]).data('day')==$($('.chart_order_input')[j]).data('day')){
                if($($('.chart_order_input')[i]).val()==0){
                    $($('.chart_order_input')[i]).addClass('ndis');
                }else{
                    $($('.chart_order_input')[j]).addClass('ndis');
                }

            }
        }
    }
    var arryLabels = [];var arrayData=[];
    for(var i=0;i<order_input_len;i++){
        if(!$($(".chart_order_input")[i]).hasClass('ndis')){
            arryLabels.push($($('.chart_order_input')[i]).data('day'));
            arrayData.push($($('.chart_order_input')[i]).val());
        }
    }


    var barChartData = {
        labels : arryLabels,
        datasets : [
            {
                fillColor : "rgba(50, 160, 221, 1)",
                strokeColor : "rgba(50, 160, 221, 1)",
                highlightFill: "rgba(50, 160, 221, 1)",
                highlightStroke: "rgba(50, 160, 221, 1)",
                data : arrayData
            }
        ]

    }
    if(window.location.pathname=="/admin/dataChart/order" || window.location.pathname=="/admin/regionDataChart/order"){
            var ctx = document.getElementById("canvas").getContext("2d");
            window.myBar = new Chart(ctx).Bar(barChartData, {
                responsive : true
            });

    }
    //注册用户
    var register_input_len=$('.chart_register_input').length;
    for(var i=0;i<register_input_len;i++){
        for(var j=i+1;j<register_input_len;j++){
            if($($('.chart_register_input')[i]).data('day')==$($('.chart_register_input')[j]).data('day')){
                if($($('.chart_register_input')[i]).val()==0){
                    $($('.chart_register_input')[i]).addClass('ndis');
                }else{
                    $($('.chart_register_input')[j]).addClass('ndis');
                }
            }
        }
    }
    var registerLabels = [];var registerData=[];
    for(var i=0;i<register_input_len;i++){
        if(!$($(".chart_register_input")[i]).hasClass('ndis')){
            registerLabels.push($($('.chart_register_input')[i]).data('day'));
            registerData.push($($('.chart_register_input')[i]).val());
        }
    }


    var barRegisterData = {
        labels : registerLabels,
        datasets : [
            {
                fillColor : "rgba(50, 160, 221, 1)",
                strokeColor : "rgba(50, 160, 221, 1)",
                highlightFill: "rgba(50, 160, 221, 1)",
                highlightStroke: "rgba(50, 160, 221, 1)",
                data : registerData
            }
        ]

    }
    if(window.location.pathname=="/admin/dataChart/register" || window.location.pathname=="/admin/regionDataChart/register"){
        var ctx = document.getElementById("register_canvas").getContext("2d");
        window.myBar = new Chart(ctx).Bar(barRegisterData, {
            responsive : true
        });

    }


    //认证数据
    var identification_input_len=$('.chart_identification_input').length;
    for(var i=0;i<identification_input_len;i++){
        for(var j=i+1;j<identification_input_len;j++){
            if($($('.chart_identification_input')[i]).data('day')==$($('.chart_identification_input')[j]).data('day')){
                if($($('.chart_identification_input')[i]).val()==0){
                    $($('.chart_identification_input')[i]).addClass('ndis');
                }else{
                    $($('.chart_identification_input')[j]).addClass('ndis');
                }
            }
        }
    }
    var identificationLabels = [];var identificationData=[];
    for(var i=0;i<identification_input_len;i++){
        if(!$($(".chart_identification_input")[i]).hasClass('ndis')){
            identificationLabels.push($($('.chart_identification_input')[i]).data('day'));
            identificationData.push($($('.chart_identification_input')[i]).val());
        }
    }


    var barRegisterData = {
        labels : identificationLabels,
        datasets : [
            {
                fillColor : "rgba(50, 160, 221, 1)",
                strokeColor : "rgba(50, 160, 221, 1)",
                highlightFill: "rgba(50, 160, 221, 1)",
                highlightStroke: "rgba(50, 160, 221, 1)",
                data : identificationData
            }
        ]

    }
    if(window.location.pathname=="/admin/dataChart/identification" || window.location.pathname=="/admin/regionDataChart/identification"){
        var ctx = document.getElementById("identification_canvas").getContext("2d");
        window.myBar = new Chart(ctx).Bar(barRegisterData, {
            responsive : true
        });

    }

})



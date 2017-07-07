var map, geolocation,alertplay=0;

$(function(){
    var url=window.location.href;
    var url_arry=url.split("/");
    if(url_arry[url_arry.length-1]=='login'){
        sessionStorage .setItem('alert-flag',"");
    }

    if(url_arry[url_arry.length-1]=='station'&& alertplay==1){
        
        if( sessionStorage .getItem('alert-flag')!=$("#mobile").val()){
            var img= $("<img src="+'/bundles/autowap/images/fool-alert.png'+ ">");
            $(img).load(function(){
                $(".station.list .alert-cont").show();
                $(".station.list .all").show();
                sessionStorage .setItem('alert-flag', $("#mobile").val());
            });

        }

        $(".station.list .close").click(function(){
            setTimeout(function(){
                $(".station.list .alert-cont").hide();
                $(".station.list .all").hide();
            },320);

        });
    }

    if(url_arry[url_arry.length-1]=='station'||(url_arry[url_arry.length-3]=="rentalCar"&&url_arry[url_arry.length-2]=="list"||url_arry[url_arry.length-2]=='map'))
    {

//加载地图，调用浏览器定位服务
        map = new AMap.Map('container', {
            resizeEnable: true,
            level:13, //设置地图缩放级别
            doubleClickZoom:false, //双击放大地图
            scrollWheel:true//鼠标滚轮缩放地图
        });
        map.plugin('AMap.Geolocation', function() {
            geolocation = new AMap.Geolocation({
                enableHighAccuracy: true,//是否使用高精度定位，默认:true
                timeout: 10000,          //超过10秒后停止定位，默认：无穷大
                // buttonOffset: new AMap.Pixel(10, 20),//定位按钮与设置的停靠位置的偏移量，默认：Pixel(10, 20)
                //zoomToAccuracy: true,      //定位成功后调整地图视野范围使定位位置及精度范围视野内可见，默认：false
                showMarker: true,        //定位成功后在定位到的位置显示点标记，默认：true
                showCircle: true,        //定位成功后用圆圈表示定位精度范围，默认：true
                panToLocation: true,     //定位成功后将定位到的位置作为地图中心点，默认：true
                buttonPosition:'RB'
            });
            map.addControl(geolocation);
            geolocation.getCurrentPosition();
            AMap.event.addListener(geolocation, 'complete', onComplete);//返回定位信息
            AMap.event.addListener(geolocation, 'error', onError);      //返回定位出错信息
        });
//解析定位结果
        function onComplete(data) {
            var getLng=data.position.getLng();
            var getLat=data.position.getLat();
            var walking = new AMap.Walking(); //构造路线导航类
            //根据起终点坐标规划步行路线，您如果想修改结果展现效果，请参考页面：http://lbs.amap.com/fn/css-style/
            var stations=$(".station-info");
            for(var i=0;i<stations.length;i++){
                (function(i)
                {
                    var station_longitude = $(stations[i]).attr("longitude");
                    var station_latitude = $(stations[i]).attr("latitude");
                    walking.search(new AMap.LngLat(getLng, getLat), new AMap.LngLat(station_longitude, station_latitude), function (status, result) {
                        if (status === 'complete') {
                            var str = "距您" + distance(result.routes[0].distance) + "，步行" + walktimeForm(result.routes[0].time);
                            if($(stations[i]).find(".car-title .geoinfo_show").length==0){
                                var infoshow = $("<span class='geoinfo_show'></span>");
                            }

                            $(infoshow).html(str);
                            $(infoshow).appendTo($(stations[i]).find(".car-title"));
                            (new Lib.AMap.WalkingRender()).autoRender({
                                data: result,
                                map: map,
                                panel: "panel"
                            });
                        }
                    });
                })(i);
            }

        }
//解析定位错误信息
        function onError(data) {
            alert( '定位失败');
        }
    }
function distance(dis){
    var dis_str="";
    if(Math.floor(dis/1000)){
        if(Math.floor((dis%1000)/100)){
            dis_str="约<span class='color-green'>"+Math.floor(dis/1000)+"."+Math.floor((dis%1000)/100)+"</span>公里";
        }
       else {
            dis_str="约<span class='color-green'>"+Math.floor(dis/1000)+"</span>公里";
        }
    }
    else {
        dis_str="约<span class='color-green'>"+dis+"</span>米";
    }
    return dis_str;
}
    function walktimeForm(time){
        var time_str="";
        if(time==86400){
            time_str="约<span class='color-green'>"+1+"天";
        }
        else if(time>86400){
            time_str="大于<span class='color-green'>"+1+"天";
        }
        else {
            if(Math.floor(time/3600)){
                if(time%3600){

                    time_str="约<span class='color-green'>"+Math.floor(time/3600)+"</span>小时<span class='color-green'>"+Math.floor((time%3600)/60)+"</span>分钟";
                }
                else{
                    time_str="约<span class='color-green'>"+Math.floor(time/3600)+"</span>小时";
                }
            }
            else {
                time_str="约<span class='color-green'>"+Math.floor(time/60)+"</span>分钟";
            }
        }
        return time_str;
    }


});


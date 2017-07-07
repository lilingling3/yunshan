
if(window.location.pathname=="/mobile/station") {

    map = new AMap.Map('container', {
        resizeEnable: true,
        level:17, //设置地图缩放级别
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

    map.plugin(["AMap.Geocoder"], function() {
    });
//解析定位结果
    function onComplete(data) {
        var getLng=$("#lng").val();
        var getLat=$("#lat").val();
        var walking = new AMap.Walking(); //构造路线导航类
        //根据起终点坐标规划步行路线，您如果想修改结果展现效果，请参考页面：http://lbs.amap.com/fn/css-style/
        var stations=$(".stations");
        var mileages=$(".mileage");
        for(var i=0;i<stations.length;i++){
            (function(i)
            {
                var station_longitude = $(stations[i]).attr("lon");
                var station_latitude = $(stations[i]).attr("lat");
                walking.search(new AMap.LngLat(getLng, getLat), new AMap.LngLat(station_longitude, station_latitude), function (status, result) {
                    if (status === 'complete') {
                        var str = distance(result.routes[0].distance);
                        $(mileages[i]).text(str);

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
    function distance(dis){
        var dis_str="";
        if(Math.floor(dis/1000)){
            if(Math.floor((dis%1000)/100)){
                dis_str=Math.floor(dis/1000)+"."+Math.floor((dis%1000)/100)+"公里";
            }
            else {
                dis_str=Math.floor(dis/1000)+"公里";
            }
        }
        else {
            dis_str=dis+"米";
        }
        return dis_str;
    }
}

if(window.location.pathname=="/mobile/station/back") {
    $("#page.rentalCar2.change-station").delegate(".search .btn","click",function(){
        var address=$.trim($(".search input").val());
        if(address==""){
            return false;
        }
        else {
            changegeocoder(address);
        }
    });
    var changeSurl=window.location.href,changefun,changePos={
            "lng":$("#backS").attr("lng"),
            "lat":$("#backS").attr("lat"),
            "backType":$("#backS").attr("backType")
        },
        imgbg={
            0:'/bundles/automobile/images/2.0-station-le.png',
            1:'/bundles/automobile/images/2.0-station1-le.png',
            2:'/bundles/automobile/images/2.0-station.png',
            3:'/bundles/automobile/images/2.0-station1.png',
            4:'/bundles/automobile/images/2.0-blue-point.png'
        };

    var changechsinfo,changeauto,changeMarkers=[];
    var changeGetFuc=function(maxlat,minlat,maxlng,minlng,backType){
        this.maxlat=maxlat;
        this.minlat=minlat;
        this.maxlng=maxlng;
        this.minlng=minlng;
        this.stations=null;
        this.backType=backType;
    }
    changeGetFuc.prototype.getstations=function() {
        $.post("/api/station/list",
            {
                maxlat: this.maxlat,
                minlat: this.minlat,
                maxlng: this.maxlng,
                minlng: this.minlng,
                backType:this.backType
            },
            function (data, status) {
                if (status) {
                    if (data.errorCode == 0) {
                        this.stations=data["stations"];
                        changechsinfo=changestationinfo(this.stations);
                        changeAddMarkers(changechsinfo["stations"]);
                    }
                }
                else {
                }
            });
    };


    map = new AMap.Map('container', {
        resizeEnable: true,
        level:14, //设置地图缩放级别
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
            buttonPosition:'LT'
        });
        map.addControl(geolocation);
        geolocation.getCurrentPosition();
        AMap.event.addListener(geolocation, 'complete', onComplete);//返回定位信息
        AMap.event.addListener(geolocation, 'error', onError);      //返回定位出错信息
    });

    map.plugin(["AMap.Geocoder"], function() {
    });

    /**
     *  解析定位结果
     */
    function onComplete(data) {
        var getLng=$("#lng").val();
        var getLat=$("#lat").val();

        // alert(getLng+"/"+getLat);
        var walking = new AMap.Walking(); //构造路线导航类
        //根据起终点坐标规划步行路线，您如果想修改结果展现效果，请参考页面：http://lbs.amap.com/fn/css-style/
        var stations=$(".stations");
        var mileages=$(".mileage");
        for(var i=0;i<stations.length;i++){
            (function(i)
            {
                var station_longitude = $(stations[i]).attr("lon");
                var station_latitude = $(stations[i]).attr("lat");
                walking.search(new AMap.LngLat(getLng, getLat), new AMap.LngLat(station_longitude, station_latitude), function (status, result) {
                    if (status === 'complete') {
                        var str = distance(result.routes[0].distance);
                        $(mileages[i]).text(str);

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

    /**
     *  定位失败
     */
    function onError(data) {
        alert( '定位失败');
    }

    /**
     *  计算距离
     */
    function distance(dis){
        var dis_str="";
        if(Math.floor(dis/1000)){
            if(Math.floor((dis%1000)/100)){
                dis_str=Math.floor(dis/1000)+"."+Math.floor((dis%1000)/100)+"公里";
            }
            else {
                dis_str=Math.floor(dis/1000)+"公里";
            }
        }
        else {
            dis_str=dis+"米";
        }
        return dis_str;
    }
        // map = new AMap.Map("container", {
        //     panToLocation: true,     //定位成功后将定位到的位置作为地图中心点，默认：true
        //     // 设置缩放级别
        //     center:[changePos.lng, changePos.lat],
        //     zoom: 14,
        //     resizeEnable: true
        // });
        // map.plugin(["AMap.Walking"], function() {
        //     walking = new AMap.Walking(); //构造路线导航类
        // });
        /* walking = new AMap.Walking();*/ //构造路线导航类

        changeautotipinput = new AMap.Autocomplete({
            input: "tipinput"
        });
        AMap.event.addListener(changeautotipinput, "select", changeautoselect);

        function changeautoselect(e){
            document.getElementById("tipinput").value=e.poi.name;
            var lnglat=e.poi.location;
            addChangeCenterMarker(lnglat);
            map.setCenter([lnglat.lng, lnglat.lat]);
        }

        EventListener=AMap.event.addListener(map, 'moveend', changeMapmoveed);
        changeStationonComplete();
    
        function changeStationonComplete(){
            changefun=null;
            var pos=map.getBounds();
            changefun=new changeGetFuc(pos.northeast.lat,pos.southwest.lat,pos.northeast.lng,pos.southwest.lng,changePos["backType"]);
            changefun.getstations();
        }



        function changeMapmoveed(){
            changeRemoveMarker(changeMarkers);
            changeMarkers=[];
            changeStationonComplete();
        }

        function changeRemoveMarker(markers){
            map.remove(markers);
        }
        function changestationinfo(stations){
            var st={};
            st["length"]=0;
            st["stations"]={};
            var s=stations;

            for(var i= 0;i<s.length;i++){
                var img=0;
                var station=s[i];
                if(station["parkingSpaceCount"]>0){

                if(station["backType"]==601){
                    if(station["parkingSpaceCount"]){
                        img=0;
                    }
                    else {
                        img=1;
                    }
                }
                else {
                    if(station["parkingSpaceCount"]){
                        img=2;
                    }
                    else {
                        img=3;
                    }
                }

                st["length"]++;
                st["stations"][station["rentalStationID"]]={
                    "lng":station["longitude"],
                    "lat":station["latitude"],
                    "parkingSpaceCount":station["parkingSpaceCount"],
                    "icon":imgbg[img],
                    "backType":station["backType"],
                    "name":station["name"],
                    "street":station["street"]
                };

                }
            }

            return st;
        }


        function changeAddMarkers(stations){
            for(var id in stations){
                changeAddMarker(id,stations[id]);
            }
        }

        function changeAddMarker(id,sattion){
            var changemarker = new AMap.Marker({
                icon:sattion["icon"],
                position: [sattion.lng,sattion.lat],
                extData:id
            });
            changeMarkers.push(changemarker);
            changemarker.setMap(map);
            var cont="<div clas='info-content'>"+"<img src='"+sattion["icon"]+"'/></div>";
            changemarker.setLabel({//label默认蓝框白底左上角显示，样式className为：amap-marker-label
                offset: new AMap.Pixel(-25,-79)//修改label相对于maker的位置
            });
            changemarker.on('click', changeMarkerClick);
        }

        function changegeocoder(str) {
            var geocoder = new AMap.Geocoder({
                city: "010", //城市，默认：“全国”
                radius: 1000 //范围，默认：500
            });
            //地理编码,返回地理编码结果
            geocoder.getLocation(str, function(status, result) {
                if (status === 'complete' && result.info === 'OK') {
                    var pos=changegeocoder_CallBack(result)
                    addChangeCenterMarker(pos);
                    map.setCenter([pos.lng, pos.lat]);
                }
            });
        }


        function addChangeCenterMarker(point){
            marker = new AMap.Marker({
                position: [point.lng,point.lat],
                icon: new AMap.Icon({
                    size: new AMap.Size(15, 15),  //图标大小
                    offset: new AMap.Pixel(-8, -6), //相对于基点的偏移位置
                    image:imgbg[4],
                    imageOffset: new AMap.Pixel(0,0)
                })
            });
            marker.setMap(map);
        }

        function changegeocoder_CallBack(data) {
            var resultStr = {};
            //地理编码结果数组
            var geocode = data.geocodes;

            for (var i = 0; i < geocode.length; i++) {
                //拼接输出html
                resultStr["lng"]=geocode[i].location.getLng()
                resultStr["lat"]=geocode[i].location.getLat();
                break;
            }
            return resultStr;
        }
        function changeMarkerClick(e){
            var id =  e.target.getExtData();
            var station=changechsinfo["stations"][id];
            sinfo(station);
            $("#backS").val(id);

        }

    $("#page.rentalCar2.change-station").delegate(".gocarshow","click",function(){
        $("#backstion").submit();
    });

    $("#page.station.back .sinfo").delegate(".queding","click",function(){
        if($("#orderID").val()){
            var  user=$("#user").val(),order=$("#orderID").val(),station=$("#backS").val();

            $.post("/api/order/changeReturnStation",
                {
                    userID:user,
                    orderID:order,
                    rentalStationID:station
                },
                function(data,status){
                    if(status){
                        if(data.errorCode==0){

                            window.location.href=$(".top-menu .ordershow").attr("href");
                        }

                        else{

                            alert(data.errorMessage);
                            location.reload();
                        }

                    }

                });
        }

        else {
            $("#backstion").submit();
        }
    });

    function sinfo(station){
        $(".sinfo").css("display","block");
        $(".amap-zoomcontrol").addClass("btposi");
        $(".sinfo .sname").text(station["name"]);
        $(".sinfo .info p").text(station["street"]);
    }

}
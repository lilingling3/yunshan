if(window.location.pathname=="/mobile/login") {

    var verify={
        mobile:function(num){
            if(num==""|| $.trim(num)==""){
                $(".error").text("请填写手机号");
                return false;
            }
            if(!$.checkMobile(num)){
                $(".error").text("手机号错误");
                return false;
            }
            return true;
        },
        code:function(num){
            if(num==""){
                $(".error").text("请填写验证码");
                return false;
            }
            return true;
        }
    };

    $("#page.default.login .btn-code").on("click.codeclick",getcode);


    function getcode(){
        var mobile=$("#mobile").val();
        if(! verify.mobile(mobile)){
            return false;
        }
        $(".error").text("");
        $("#page.default.login .btn-code").off(".codeclick");
        $.post("/api/account/login/code",
            {
                mobile:mobile
            },
            function(data,status){
                if(status){
                    if(data.errorCode==0){
                       $(".code-t").removeClass("blue");
                        var time=59;
                        var timestr=time+"秒";
                        $(".code-t").text(timestr);
                        var timer=setInterval(function(){
                            var timestr=--time+"秒";
                            $(".code-t").text(timestr);
                            if(time==-1){
                                clearInterval(timer);
                                $(".code-t").addClass("blue");
                                $(".code-t").text('获取验证码');
                                $("#page.default.login .btn-code").on("click.codeclick",getcode);
                            }
                        },1000);

                    }else{
                        $("#page .default.login .btn-code").on("click.codeclick",getcode);
                        alert(data.errorMessage)
                    }

                }

            });
    }


    $("#page.default.login .btn-subimt").on("click",submitclick);


   function submitclick(){
       var mobile = $.trim($("#mobile").val());
       var code = $.trim($("#code").val());

       if(!verify.mobile(mobile)){
            return false;
       }
       if(!verify.code(code)){
           return false;
       }
       $(".error").text("");
       $.post("/api/account/verify/login",
           {
               mobile:mobile,
               code:code,
               source:3
           },
           function(data,status){
               if(data.errorCode==0){
                  $("#loginform").submit();
               }
               else{
                   alert(data.errorMessage);
                   return false;
               }

           });
       return false;
   }
}

if(window.location.pathname=="/mobile/index") {

    var memberpso=0;

    var indextimg_src=[
        "/bundles/automobile/images/indext1.png",
        "/bundles/automobile/images/indext2.png",
        "/bundles/automobile/images/indext3.png",
        "/bundles/automobile/images/indext4.png",
        "/bundles/automobile/images/indext5.png",
        "/bundles/automobile/images/indext6.png"
    ];
    var randomNum = Math.random()*6;
    var index_index=parseInt(randomNum,10);
    
    function getMobileOS () {

        var ua = navigator.userAgent.toLowerCase();
        //系统判断
        if(ua.match(/iPhone/i)=="iphone") {//iphone

            //iphone
            //alert("iphone");
            return 1;
        }else if(ua.match(/Android/i)=="android"){//android

            //android
            //alert("android");
            return 0;
        }
    }

    var curmobile = getMobileOS();

    getLocation();
    function getLocation(){
        if (navigator.geolocation){
            navigator.geolocation.getCurrentPosition(showPosition,showError);
        }else{
            memberpso=0;
        }
    }
    function showError(error){
	
        switch(error.code) {
            case error.PERMISSION_DENIED:
                memberpso=0;
                break;
            case error.POSITION_UNAVAILABLE:
                memberpso=0;
                break;
            case error.TIMEOUT:
                memberpso=0;
                break;
            case error.UNKNOWN_ERROR:
                memberpso=0;
                break;
        }
    }
    function showPosition(){
        memberpso=1;
    }


    $("#page.default.index .indextcont img").attr("src",indextimg_src[index_index]);

    $("#page.default.index .stitle-cont .stitle").on("click",function(){
        $(this).addClass("cur");
        $(this).siblings(".stitle").removeClass("cur");
        var lists=$(".stations-list .list");
        var index=$(this).index();
        $(lists[index]).show();
        $(lists[index]).siblings().hide();
    });

    $("#page.default.index .gsbtn").on("click",function(){
     if($(".lng").val() && $(".lat").val()){
         $("#lonlat").submit();
     }
    });

    $("#page.default.index .address").on("focus",function(){
            $("#lonlatseek").submit();
    });

    map = new AMap.Map('mapcontainer', {
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
        AMap.event.addListener(geolocation, 'complete', onmapComplete);//返回定位信息
        AMap.event.addListener(geolocation, 'error', onmapError);      //返回定位出错信息
    });

    map.plugin(["AMap.Geocoder"], function() {
    });
//解析定位结果

    function onmapComplete(data) {
        console.log(memberpso);
        var getLng=data.position.getLng();
        var getLat=data.position.getLat();

        //is mobile iphone
        if ( curmobile == 1 ) {
            memberpso = 1;
        }
        mapregeocoder([getLng,getLat]);
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
        
        if(memberpso==1){
            getStations(getLat,getLng);
        }

    }

    function getStations(lat,lon){

        var lat= lat;
        var lon=lon;
        console.log(lat);
        console.log(lon);
        $.post("/api/station/searchByCoordinate",
            {
                latitude:lat,
                longitude:lon
            },
                function (data, status) {
                    console.log(status);
                if (status) {
                    console.log(data);
                    if (data.errorCode == 0) {
                        console.log(data);
                        var stations=data["stations"];
                        if (stations.length>0) {
                            addStations(stations,lat,lon);
                        } else {
                            $('.nonewStations').html('您附近无可用租赁点');
                        }
                        
                    }
                }
                else {
                }
            });
    }

    function addStations(stations,lat,lon){
        var stationsarray=[];
        console.log(stations);
        var list=$(".stations-list .list1");
        $(list).empty();
        $(".stitle-cont .stitle1 p").text("推荐租赁点");
        for(var i=0;i<stations.length;i++) {
            var s = stations[i];
            if (s["usableRentalCarCount"] > 0) {
                stationsarray.push(s);
            }
            if (stationsarray.length == 3) break;
        }

        var walking = new AMap.Walking(); //构造路线导航类
        //根据起终点坐标规划步行路线，您如果想修改结果展现效果，请参考页面：http://lbs.amap.com/fn/css-style/

        for(var i=0;i<stationsarray.length;i++){
            (function(i)
            {
                var station_longitude =stationsarray[i]["longitude"];
                var station_latitude = stationsarray[i]["latitude"];
                var s=stationsarray[i];
                walking.search(new AMap.LngLat(lon, lat), new AMap.LngLat(station_longitude, station_latitude), function (status, result) {
                    if (status === 'complete') {
                        var str = distance(result.routes[0].distance);
                        stationsarray[i]["mileages"]=str;
                        addstationinfo(stationsarray[i]);
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


    function addstationinfo(station){
        var list=$(".stations-list .list1");
        var s=station;
        console.log(s);
        var st=$("<div class='inner stations' lon='"+s["longitude"]+"'lat='"+s["latitude"]+"'></div>");
        var a=$("<a class='href' href='/mobile/rentalCar/list/"+s["rentalStationID"]+"'></a>");
        $(st).append($(a));
        var p1=$("<p class='black row1'></p>");

        var pstr1='<span class="left-cont">'+s["name"]+'</span><span class="useable">可用<span class="carnum">'+s["usableRentalCarCount"]+'</span>辆</span>';
        $(p1).html(pstr1);
        $(p1).appendTo($(st));

        var pstr2;
        var p2=$("<p class='row2'></p>");

        //s["mileages"] = parseInt(s["mileages"]) > 10 ? ">10公里" : s["mileages"];

        if(s["backType"]==601){
         pstr2='<span class="left-cont"><span class="yi">随</span>' +s["street"]+'</span><span class="green mileage">'+s["mileages"]+'</span>';
         }
         else {
         pstr2='<span class="left-cont">' +s["street"]+'</span><span class="green mileage">'+s["mileages"]+'</span>';
         }

       $(p2).html(pstr2);
        console.log("aaa::"+$(p2).text());
        $(p2).appendTo($(st));

        $(st).appendTo($(list));
    }





    function mapregeocoder(lnglatXY) {  //逆地理编码

       var geocoder = new AMap.Geocoder({
            radius: 1000,
            extensions: "all"
        });

         geocoder.getAddress(lnglatXY, function(status, result) {
            if (status === 'complete' && result.info === 'OK') {
                mapgeocoder_CallBack(result,lnglatXY);

            }
        });
    }

    function mapgeocoder_CallBack(data,lnglatXY) {
        //alert(lnglatXY[0] + "/" + lnglatXY[1]);
        // alert("memberpso:" + memberpso + "/data:" + data);
        console.log(memberpso);

        if(memberpso==1) {

            var t = new mapgetaddress(data), lng = lnglatXY[0], lat = lnglatXY[1];
            t.pois = t.point(t.pois);
            t.roads = t.point(t.roads);

            var pois;
            for (var i = 0; i < t.pois.length; i++) {
                if (t.pois[i]["distance"] < 150 && t.pois[i]["location"]["lng"] && t.pois[i]["location"]["lat"]) {
                    pois = t.pois[i];
                }
            }
            var address = "";
            if (t.isneighborhood()) {
                address = t.neighborhood;
            }
            else if (pois) {
                address = pois["name"];
                lng = pois["location"]["lng"];
                lat = pois["location"]["lat"];

            }
            else {
                address = t.roads[0]["name"];
                lng = t.roads[0]["location"]["lng"];
                lat = t.roads[0]["location"]["lat"];
            }
            if (address == "") {
                $(".address").val("获取位置失败，请自行输入位置");
                $(".lng").val("");
                $(".lat").val("");
            }
            else {
                $(".address").val(address);
                $(".lng").val(lng);
                $(".lat").val(lat);
            }
            console.log("address::"+address);
        }
        if(memberpso==0) {
            $(".address").val("获取位置失败，请手动输入");
            $(".lng").val("");
            $(".lat").val("");
        }
    }
/**********/
    var  mapgetaddress=function(data) {
        this.neighborhood=data["regeocode"]["addressComponent"]["neighborhood"];
        this.pois=data["regeocode"]["pois"];
        this.roads=data["regeocode"]["roads"];
    }
    mapgetaddress.prototype.isneighborhood=function(){
        if(this.neighborhood !=""){
            return this.neighborhood;
        }
        return false;
    }

    mapgetaddress.prototype.point=function(data){
        var head= 0,poisarray=data;
        poisarray.sort(this.compare);
        return poisarray;
    }


    mapgetaddress.prototype.compare=function(value1,value2){
        if(value1["distance"]<value2["distance"]){
            return -1;
        }else if(value1["distance"]>value2["distance"]) {
            return 1;
        }else {
            return 0;
        }
    }

//解析定位错误信息
    function onmapError(data) {
        alert( '定位失败');
    }
    function distance(dis){
        var dis_str="";
        if(Math.floor(dis/1000)>0){
            if (Math.floor(dis/1000)>10) {
                dis_str=">10公里";
            }
            else {
                dis_str=Math.floor(dis/1000)+"."+Math.floor((dis%1000)/100)+"公里";
            }
        }
        else {
            dis_str=dis+"米";
        }
        return dis_str;
    }

}


if(window.location.pathname=="/mobile/seek") {
    map = new AMap.Map('seekcontainer', {
        resizeEnable: true,
        level:13, //设置地图缩放级别
        doubleClickZoom:false, //双击放大地图
        scrollWheel:true//鼠标滚轮缩放地图
    });
    var cityname,autoOptions,auto;

    map.getCity(function(result) {
        console.log(result);
        cityname=result.province;
        autoOptions = {
            input: "address",
            city:cityname
        };
        auto = new AMap.Autocomplete(autoOptions);
        AMap.event.addListener(auto, "complete", seekcomplete);//注册监听，当选中某条记录时会触发
    });

    document.getElementById('address').addEventListener('input', function(event) {
        var keyword = $("#address").val();

        auto.search(keyword);
    }, false);


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
        AMap.event.addListener(geolocation, 'complete', onseekComplete);//返回定位信息
        AMap.event.addListener(geolocation, 'error', onseekError);      //返回定位出错信息
    });

    map.plugin(["AMap.Geocoder"], function() {
    });


    function seekcomplete(e) {
        seek_geocoder_CallBack({
            "pois":e["tips"]
        });
    }


    //解析定位结果
    function onseekComplete(data) {
        var getLng=$(".nowaddress").attr("lng");
        var getLat=$(".nowaddress").attr("lat");
        if(getLat && getLng){
            seekregeocoder([getLng,getLat]);
        }
        
    }

    function seekregeocoder(lnglatXY) {  //逆地理编码
        var geocoder = new AMap.Geocoder({
            radius: 1000,
            extensions: "all"
        });
        geocoder.getAddress(lnglatXY, function(status, result) {
            if (status === 'complete' && result.info === 'OK') {
                seek_geocoder_CallBack({
                    "neighborhood":result["regeocode"]["addressComponent"]["neighborhood"],
                    "pois":result["regeocode"]["pois"],
                    "roads":result["regeocode"]["roads"]
                });

            }
        });
    }

    function seek_geocoder_CallBack(data) {
        var taddress=new seekgetaddress(data);
        var t=taddress.options;
        if(t.pois==""){
            return ;
        }
        console.log(t.pois);
        t.pois=taddress.point(t.pois);
        console.log(t.pois);
        $(".resluts").empty();
        var lengtht=t.pois.length,posts=t.pois;
        for(var i=0;i<lengtht;i++){
            if(posts[i]["location"]){
            var t='<section class="row" lng="'+posts[i]["location"]["lng"]+'"lat="'+posts[i]["location"]["lat"]+'">\
                <p class="sitename">'+posts[i]["name"]+'</p>\
                <p class="stree">'+posts[i]["address"]+'</p>\
                </section>';
            $(t).appendTo($(".resluts"));
            }
        }

    }

    $("#page.default.seek .resluts").on("click",function(ev){
        var ev = ev || window.event;
        var target = ev.target || ev.srcElement;
        var node;
        if(target.nodeName.toLowerCase() == "section"){
            node=$(target);

        }
        else if(target.nodeName.toLowerCase() == "p") {
            node=$(target).parent("section");
        }
        $("#lng").val($(node).attr("lng"));
        $("#lat").val($(node).attr("lat"));
        var newsite=$(node).find(".sitename").text();
        $("#inputaddress").val(newsite);
        $(".lnglat").submit();
    });


    $("#page.default.seek .nowaddress").on("click",function(ev){
        var ev = ev || window.event;
        var target = ev.target || ev.srcElement;
        //alert(target.innerHTML);
        if(target.nodeName.toLowerCase() == "span"){
           relocate();
        }
        else {
            $("#inputaddress").val($(this).find(".sitename").text());
            $("#lng").val($(this).attr("lng"));
            $("#lat").val($(this).attr("lat"));
            $(".lnglat").submit();
        }
    });
    var  seekgetaddress=function(data) {
        var options={
            "neighborhood":"",
            "pois":"",
            "roads":""
        };
        this.options = $.extend({}, this.options, data);
    }
    seekgetaddress.prototype.isneighborhood=function(){
        if(this.neighborhood !=""){
            return this.neighborhood;
        }
        return false;
    }

    seekgetaddress.prototype.point=function(data){
        var head= 0,poisarray=data;
        poisarray.sort(this.compare);
        return poisarray;
    }


    seekgetaddress.prototype.compare=function(value1,value2){
        if(value1["distance"]<value2["distance"]){
            return -1;
        }else if(value1["distance"]>value2["distance"]) {
            return 1;
        }else {
            return 0;
        }
    }

//解析定位错误信息
    function onseekError(data) {
        alert( '定位失败');
    }

    function relocate(){
        AMap.service('AMap.Geolocation', function() {
            geolocation = new AMap.Geolocation({
                enableHighAccuracy: !0,
                maximumAge: 0,
                convert: !0,
                showMarker: !0,
                showCircle: !1,
                panToLocation: !0,
                zoomToAccuracy: !0
            });
            map.addControl(geolocation);
            geolocation.getCurrentPosition();
            AMap.event.addListener(geolocation, 'complete', onComplete);//返回定位信息
            AMap.event.addListener(geolocation, 'error', onError);      //返回定位出错信息
        });

        function onError(data) {
            alert( '定位失败');
        }

//解析定位结果
        function onComplete(data) {
            var getLng=data.position.getLng();

            var getLat=data.position.getLat();
            var lnglatXY=[getLng,getLat];
            AMap.service(["AMap.Geocoder"],
                function() {
                    var n = new AMap.Geocoder({
                        radius: 1e3,
                        extensions: "all"
                    });
                    n.getAddress(lnglatXY,
                        function(e, n) {
                            if ("complete" === e && "OK" === n.info) {
                                var r={}, i = n.regeocode;
                                r["lng"] = lnglatXY[0];
                                r["lat"] = lnglatXY[1];
                                if (i.pois && i.pois[0] && i.pois[0].name) {
                                    r["name"] = i.pois[0].name;
                                    r["lng"] = i.pois[0]["location"]["lng"];
                                    r["lat"] = i.pois[0]["location"]["lat"];
                                }
                                else {
                                    var s = i.addressComponent || {};
                                    if (s.building) {
                                        r["name"] = s.building;
                                    }
                                    else if (s.neighborhood) {
                                        r["name"] = s.neighborhood;

                                    }
                                    else if (s.street) {
                                        var u = s.street;
                                        if (s.streetNumber) {
                                            var a = s.streetNumber.split("号");
                                            r["name"] = 1 === a.length ? u + s.streetNumber + "号": u + s.streetNumber;

                                        } else {
                                            r["name"] = u;
                                        }
                                    } else r["name"] = i.formatted_address
                                }
                                $(".nowaddress .sitename").text(r["name"]);
                                $(".nowaddress").attr("lng",r["lng"]);
                                $(".nowaddress").attr("lat",r["lat"]);
                                console.log(r["name"]);
                                auto.search(r["name"]);
                            }

                        })
                })
        }
    }


}

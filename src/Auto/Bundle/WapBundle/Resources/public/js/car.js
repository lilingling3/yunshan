
var carurl=window.location.href;
var carurl_arry=carurl.split("/");
if(carurl_arry[carurl_arry.length-1]=='rentalCar'&& carurl_arry[carurl_arry.length-2]=="rentalCar2") {
    var carfun, EventListener, stationifo, movemapflag, walking, map, markers, flagbycar = $(".rentalCar2.rentalCar #stationid").val(), flagmap = 0,
        position = {
            lng: $(".rentalCar2.rentalCar #lng").val(),
            lat: $(".rentalCar2.rentalCar #lat").val(),
            "img": '/bundles/autowap/images/2.0-blue-point.png'
        };


    var markers = [], circles = [], carshow = 0, caritems, rentalCars = [];
    $(function () {
        position = {
            lng: $(".rentalCar2.rentalCar #lng").val(),
            lat: $(".rentalCar2.rentalCar #lat").val(),
            "img": '/bundles/autowap/images/2.0-blue-point.png'
        };

        caritems = $(".item").length;

    });
    imgbg = {
        0: '/bundles/autowap/images/2.0-station-le.png',
        1: '/bundles/autowap/images/2.0-station1-le.png',
        2: '/bundles/autowap/images/2.0-station.png',
        3: '/bundles/autowap/images/2.0-station1.png'
    };

    var imgicon = '/bundles/autowap/images/2.0-caricon.png';


    var getFuc = function (maxlat, minlat, maxlng, minlng) {
        this.maxlat = maxlat;
        this.minlat = minlat;
        this.maxlng = maxlng;
        this.minlng = minlng;
        this.stations = null;
    }
    getFuc.prototype.setstations = function () {
        stationifo = null;
        $.post("/api/station/list",
            {
                maxlat: this.maxlat,
                minlat: this.minlat,
                maxlng: this.maxlng,
                minlng: this.minlng
            },
            function (data, status) {
                if (status) {
                    if (data.errorCode == 0) {
                        this.stations = data["stations"];
                        stationifo = stationinfo(this.stations);

                        addMarkers(stationifo["stations"]);
                      
                        addCenterMarker(position);

                        /* addCenterMarker(position);*/
                        if (flagmap == 0) {
                            tip(stationifo);
                        }

                        if (!stationifo && !movemapflag) {
                            /* createcircleLayer();*/
                            movemapflag = 1;
                        }

                        if (flagbycar) {
                            walkSearch(flagbycar);
                            flagbycar = "";
                        }
                    }
                    else {
                        if (flagmap == 0) {
                            addCenterMarker(position);
                            /*createcircleLayer();*/
                            tip(stationifo);
                        }
                    }

                }

            });


    };


    map = new AMap.Map("container", {
        panToLocation: true,     //定位成功后将定位到的位置作为地图中心点，默认：true
        // 设置缩放级别
        center: [position.lng, position.lat],
        zoom: 14,
        resizeEnable: true
    });
    map.plugin(["AMap.Walking"], function () {
        walking = new AMap.Walking(); //构造路线导航类
    });
    onStationComplete();
    /*     walking = new AMap.Walking();//构造路线导航类*/
    EventListener = AMap.event.addListener(map, 'moveend', mapmoveed);


    $(".rentalCar2.rentalCar .point").click(function () {
        flagmap = 0;
        map.setCenter([position.lng, position.lat]);
        AMap.event.removeListener(EventListener);
        movemapflag = null;
        onStationComplete();
        setTimeout(function () {
            EventListener = AMap.event.addListener(map, 'moveend', mapmoveed);
        }, 1000);
    })


    function addMarkers(stations) {
        for (var id in stations) {
            addMarker(id, stations[id]);
        }

    }

    function addCenterMarker(point) {
        marker = new AMap.Marker({
            position: [point.lng, point.lat],
            offset: new AMap.Pixel(-8, -6), //相对于基点的偏移位置
            icon: new AMap.Icon({
                size: new AMap.Size(15, 15),  //图标大小
                image: point["img"],
                imageOffset: new AMap.Pixel(0, 0)
            })
        });


        marker.setMap(map);


    }


    function addMarker(id, sattion) {
        marker = new AMap.Marker({
            icon: sattion["icon"],
            position: [sattion.lng, sattion.lat],
            extData: id
        });
        markers.push(marker);

        marker.setMap(map);
        marker.on('click', markerClick);
        var cont = "<div clas='info-content'>" + "<img src='" + sattion["icon"] + "'/></div>";

        marker.setLabel({//label默认蓝框白底左上角显示，样式className为：amap-marker-label
            offset: new AMap.Pixel(-25, -79)//修改label相对于maker的位置
        });


    }

    /*移动地图*/
    function mapmoveed() {
        removeMarker(markers);
        map.remove(circles);
        circles = [];
        markers = [];
        onStationComplete();

    }

    function createcircleLayer() {
        var t = new AMap.LngLat(position["lng"], position["lat"]);
        map.remove(circles);
        circles = [];
        var circle = new AMap.Circle({
            center: t,
            radius: 1000,
            strokeColor: '#006600',
            strokeStyle: 'solid',
            strokeWeight: 1,
            fillColor: '#006600',
            fillOpacity: 0.5
        });
        circle.setMap(map);
        circles.push(circle);
    };


    function markerClick(e) {
        console.log(e);
        flagmap = 1;
        var id = e.target.getExtData();
        walkSearch(id);

        $(".point").css("bottom", "270px");
        $(".amap-touch-toolbar .amap-zoomcontrol").css("bottom", "152px");


        //mapmoveed();
    }

    function walkSearch(id) {

        var station = stationifo["stations"][id];
        AMap.event.removeListener(EventListener);
        map.clearMap();
        addCenterMarker(position);
        $.post("/api/rentalCar/getCarsByStation",
            {
                stationID: id
            },
            function (data, status) {
                if (status) {
                    if (data.errorCode == 0) {
                        rentalCars = [];

                        rentalCars = useableCars(data["rentalCars"]);

                        addRentalCar(rentalCars, data["rentalStation"]);
                    }
                }
                else {


                }
            });

        walking.search(new AMap.LngLat(station.lng, station.lat), new AMap.LngLat(position.lng, position.lat), function (status, result) {
            if (status === 'complete') {
                //map.remove(circles);
                circles = [];
                var str = "<span class='location-icon'></span><span class='walk'>您距租赁点" + distance(result.routes[0].distance) + "</span><span class='walk-time'>步行需" + walktimeForm(result.routes[0].time) + "</span>";
                $(".tip").html(str);

                (new Lib.AMap.WalkingRender()).autoRender({
                    data: result,
                    map: map,
                    panel: "panel"
                });
                EventListener = AMap.event.addListener(map, 'moveend', mapmoveed);
            }
        });

        addMarker(id, station);
    }

    function removeMarker(markers) {

        map.remove(markers);
    }


    function onStationComplete() {

        fun = null;
        var pos = map.getBounds();
        fun = new getFuc(pos.northeast.lat, pos.southwest.lat, pos.northeast.lng, pos.southwest.lng);
        fun.setstations();
    }


    function a() {

        fun = null;
        var pos = map.getBounds();
        fun = new getFuc(pos.northeast.lat, pos.southwest.lat, pos.northeast.lng, pos.southwest.lng);
        fun.setstations();
    }


    function stationinfo(stations) {
        var st = {};
        st["length"] = 0;
        st["stations"] = {};
        var s = stations;


        for (var i = 0; i < s.length; i++) {
            var img = 0;
            var station = s[i];

            if (station["backType"] == 601) {
                if (station["usableRentalCarCount"]) {
                    img = 0;
                }
                else {
                    img = 1;
                }
            }
            else {
                if (station["usableRentalCarCount"]) {
                    img = 2;
                }
                else {
                    img = 3;
                }
            }
            st["length"]++;
            st["stations"][station["rentalStationID"]] = {
                "lng": station["longitude"],
                "lat": station["latitude"],
                "icon": imgbg[img]
            };
        }
        return st;
    }


    function distance(dis) {
        var dis_str = "";
        if (Math.floor(dis / 1000)) {
            if (Math.floor((dis % 1000) / 100)) {
                dis_str = "约<span class='color-green'>" + Math.floor(dis / 1000) + "." + Math.floor((dis % 1000) / 100) + "</span>公里";
            }
            else {
                dis_str = "约<span class='color-green'>" + Math.floor(dis / 1000) + "</span>公里";
            }
        }
        else {
            dis_str = "约<span class='color-green'>" + dis + "</span>米";
        }
        return dis_str;
    }

    function walktimeForm(time) {
        var time_str = "";
        if (time == 86400) {
            time_str = "约<span class='color-green'>" + 1 + "天";
        }
        else if (time > 86400) {
            time_str = "大于<span class='color-green'>" + 1 + "天";
        }
        else {
            if (Math.floor(time / 3600)) {
                if (time % 3600) {

                    time_str = "约<span class='color-green'>" + Math.floor(time / 3600) + "</span>小时<span class='color-green'>" + Math.floor((time % 3600) / 60) + "</span>分钟";
                }
                else {
                    time_str = "约<span class='color-green'>" + Math.floor(time / 3600) + "</span>小时";
                }
            }
            else {
                time_str = "约<span class='color-green'>" + Math.floor(time / 60) + "</span>分钟";
            }
        }
        return time_str;
    }

    function tip(stations) {


        if (!stations) {
            $(".tip").html(" 附近一公里暂无租赁点，拖动地图查找其他位置");
        }
        else {
            $(".tip").html("附近一公里有" + stations["length"] + "个租赁点");

        }

    }

//carshow,caritems;
    /************************/


    function luboL() {
        if (carshow < $(".item").length - 1 && !$(".item").is(":animated")) {
            var flag = carshow;
            $(".car-show .left-arrow").show();
            if (carshow == $(".item").length - 2) {
                $(".car-show .right-arrow").hide();
            }
            $($(".item")[flag]).animate({left: "-100%"}, 1000, "swing", function () {
                carshow++;
            });
            $($(".item")[flag + 1]).css("left", "100%");
            $($(".item")[flag + 1]).animate({left: "0%"}, 1000, "swing");
            $($(".ul-position li")[flag + 1]).siblings("li").removeClass("cur");
            $($(".ul-position li")[flag + 1]).addClass("cur");

            $(".rentalCar2.rentalCar #stationid").val(rentalCars[flag + 1]["rentalStation"]["rentalStationID"]);
            $(".rentalCar2.rentalCar #carid").val(rentalCars[flag + 1]["rentalCarID"]);

        }
    }


    function luboR() {
        if (carshow > 0 && !$(".item").is(":animated")) {
            $(".car-show .right-arrow").show();
            if (carshow == 1) {
                $(".car-show .left-arrow").hide();
            }
            var flag = carshow;
            $($(".item")[flag]).animate({left: "100%"}, 1000, "swing", function () {
                $($(".item")[flag]).css("left", "-100%");
                carshow--;
            });
            $($(".item")[flag - 1]).animate({left: "0%"}, 1000, "swing");
            $($(".ul-position li")[flag - 1]).siblings("li").removeClass("cur");
            $($(".ul-position li")[flag - 1]).addClass("cur");

            $(".rentalCar2.rentalCar #stationid").val(rentalCars[flag - 1]["rentalStation"]["rentalStationID"]);
            $(".rentalCar2.rentalCar #carid").val(rentalCars[flag - 1]["rentalCarID"]);


        }
    }


    function useableCars(rentalCars){
        var useablecars=[];
        for(var i= 0,car,cars=rentalCars;car=rentalCars[i++];){
            if(car.status==300){
                useablecars.push(car);
            }
        }

        return useablecars;
    }


    function addRentalCar(rentalcars, rentalStation) {
        carshow = 0;

        /*    $(".car-show .left-arrow").unbind("click",luboL);
         $(".car-show .right-arrow").unbind("click",luboR);*/
        $(".show-inner").empty();
        $(".ul-position").empty();
        var li = "", useablecarnum = 0;
        if (rentalcars.length > 1) {
            for (var i = 0, rentacar, rentalCars = rentalcars; rentacar = rentalCars[i++];) {
                var item = rentalcarsInfo(rentacar);
                if (!item) {
                    continue;
                }
                useablecarnum++;
                $(item).appendTo($(".show-inner"));

                if (i == 1) {
                    li += "<li class='cur'></li>";
                }
                else {
                    li += "<li></li>";
                }

            }

            if (useablecarnum == 0) {
                $(".car-cont").css("display", "block");
                $(".car-show").css("display", "none");
                $(".nocar").css("display", "block");

            }
            else {
                $(".ul-position").html(li);
                $(".car-cont").css("display", "block");
                $(".car-show").css("display", "block");
                $(".nocar").css("display", "none");


                $(".rentalCar2.rentalCar #carid").val(rentalCars[0]["rentalCarID"]);
                $(".car-show .right-arrow").show();
                /*  $(".car-show .left-arrow").bind("click",luboR);
                 $(".car-show .right-arrow").bind("click",luboL);*/
                $(".car-show .left-arrow").hide();
                $(".rentalCar2.rentalCar #stationid").val(rentalCars[0]["rentalStation"]["rentalStationID"]);
            }
        }

        else if (rentalcars.length == 1) {
            for (var i = 0, rentacar, rentalCars = rentalcars; rentacar = rentalCars[i++];) {
                var item = rentalcarsInfo(rentacar);
                if (!item) {
                    continue;
                }
                $(item).appendTo($(".show-inner"));
                useablecarnum++;

            }
            if (useablecarnum == 0) {
                $(".car-cont").css("display", "block");
                $(".car-show").css("display", "none");
                $(".nocar").css("display", "block");

            }
            else {
                $(".car-cont").css("display", "block");
                $(".car-show").css("display", "block");
                $(".nocar").css("display", "none");
                $(".car-show .left-arrow").hide();
                $(".car-show .right-arrow").hide();

                $(".rentalCar2.rentalCar #stationid").val(rentalCars[0]["rentalStation"]["rentalStationID"]);
                $(".rentalCar2.rentalCar #carid").val(rentalCars[0]["rentalCarID"]);
            }
        }

        else {
            $(".ul-position").html(li);
            $(".car-cont").css("display", "block");
            $(".car-show").css("display", "none");
            $(".nocar").css("display", "block");

        }
        $(".car-cont .title").text(rentalStation["name"]);
        if (rentalStation["backType"] == 600) {
            $(".car-cont .title").removeClass("titlebg");
        }
        else {
            $(".car-cont .title").addClass("titlebg");
        }


    }

    function rentalcarsInfo(rentalcar) {

        var item = $("<div class='item'></div>");

        var left = $("<div class='left-cont'></div>");
        var car_img = $("<div class='car-img'></div>");
        var carimg = $("<img />");
        $(carimg).attr("src", rentalcar["image"]);
        $(carimg).appendTo($(car_img));
        $(car_img).appendTo($(left));
        $(left).appendTo($(item));


        var right = $("<div class='right-cont'></div>");
        var license = $("<p class='license'></p>");
        var license_cont = "<span class='license-cont'><span>" + rentalcar["license"] + "</span></span>";
        $(license).html(license_cont);
        var rowd = $("<div class='rowd'></div>");
        var rowd_cont = "<p> <span class='car-info'></span>" + rentalcar["car"]["name"] + "<span class='car-info'>续航" + rentalcar["mileage"] + "公里</span> </p>";
        $(rowd).html(rowd_cont);


        var price_str = "";
        var price = $("<div class='price'></div>");
        if (rentalcar["rentalPrice"].length < 2) {
            return false;
        }
        if (rentalcar["rentalPrice"]) {

            for (var i = 0, data, datas = rentalcar["rentalPrice"]; data = datas[i++];) {
                price_str += "<span>" + data["name"] + "<b>&nbsp;" + data["price"] + "</b>元/时</span>";
            }

        }


        $(price).html(price_str);

        $(license).appendTo($(right));
        $(rowd).appendTo($(right));

        $(price).appendTo($(right));



        $(right).appendTo($(item));
        $(left).appendTo($(item));

        $(item).appendTo($(item));

        return item;

    }


    $(".rentalCar2.rentalCar .car-cont .car-show .btn").click(function () {
        $("#car-form").submit();
    });


    var end_time = 0, start_time, gesture_time = 150;

    function GetSlideAngle(dx, dy) {
        return Math.atan2(dy, dx) * 180 / Math.PI;//返回角度
    }

    function GetSlideDirection(startX, startY, endX, endY) {
        var dy = startY - endY;
        var dx = endX - startX;
        var result = 0;
        //如果滑动距离太短
        if ((Math.abs(dx) < 5 && Math.abs(dy) < 5) || ((end_time - start_time) <= gesture_time)) {
            return result;
        }

        var angle = GetSlideAngle(dx, dy);
        if (angle >= -45 && angle < 45) {//向右
            result = 4;
        } else if (angle >= 45 && angle < 135) {
            result = 1;//向上
        } else if (angle >= -135 && angle < -45) {
            result = 2;//向下
        }
        else if ((angle >= 135 && angle <= 180) || (angle >= -180 && angle < -135)) {
            result = 3;//向左
        }

        return result;
    }

//根据起点和终点返回方向 1：向上，2：向下，3：向左，4：向右,0：未滑动
    function GetSlideDirection(startX, startY, endX, endY) {
        var dy = startY - endY;
        var dx = endX - startX;
        var result = 0;
        //如果滑动距离太短
        if ((Math.abs(dx) < 5 && Math.abs(dy) < 5) || ((end_time - start_time) <= gesture_time)) {
            return result;
        }

        var angle = GetSlideAngle(dx, dy);
        if (angle >= -45 && angle < 45) {//向右
            result = 4;
        } else if (angle >= 45 && angle < 135) {
            result = 1;//向上
        } else if (angle >= -135 && angle < -45) {
            result = 2;//向下
        }
        else if ((angle >= 135 && angle <= 180) || (angle >= -180 && angle < -135)) {
            result = 3;//向左
        }

        return result;
    }

    var car_show = $("#page.rentalCar2.rentalCar .car-show .daily");
    document.getElementById("car-show-daily").addEventListener('touchstart', function (ev) {
        event.preventDefault();
        startX = ev.touches[0].pageX;
   
        startY = ev.touches[0].pageY;
        start_time = new Date().getTime();

    }, false);
    document.getElementById("car-show-daily").addEventListener('touchend', function (ev) {
        event.preventDefault();
        end_time = new Date().getTime();
        var endX, endY;
        endX = ev.changedTouches[0].pageX;
        endY = ev.changedTouches[0].pageY;

        var direction = GetSlideDirection(startX, startY, endX, endY);

        switch (direction) {
            case 0:
                break;
            case 1:


                break;
            case 2:

                break;
            case 3:
                luboL();
                break;
            case 4:
                luboR();
                break;
            default:
        }
    }, false);


    document.querySelector(".car-show .left-arrow").addEventListener("touchstart", function (e) {
        e.preventDefault();
        luboR();
    });

    document.querySelector(".car-show .right-arrow").addEventListener("touchstart", function (e) {
        e.preventDefault();
        luboL();
    });


    $(".car-show .left-arrow").click(function (e) {

        luboR();
    });
    $(".car-show .right-arrow").click(function (e) {

        luboL();
    });


}
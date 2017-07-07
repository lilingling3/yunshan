if(window.location.pathname=="/admin/statistics/location" || window.location.pathname=='/admin/regionStatistics/location') {
//初始化地图对象，加载地图
    var map = new AMap.Map("container", {resizeEnable: true});
    var infoWindow = new AMap.InfoWindow({offset: new AMap.Pixel(0, -30)});
    var lnglats = [
        [116.368904, 39.923423],
        [116.382122, 39.921176],
        [116.387271, 39.922501]
    ];
//轨迹回放
    function completeEventHandler() {
        marker3 = new AMap.Marker({
            map: map,
            //draggable:true, //是否可拖动
            position: new AMap.LngLat(116.273881, 39.807409),//基点位置
            icon: "/bundles/autoadmin/images/car.png", //marker图标，直接传递地址url
            offset: new AMap.Pixel(0, -6), //相对于基点的位置
            autoRotation: true
        });
        //实例化信息窗体
        marker3.content = "租赁点：" + $('#moveData').data('station') + "<br/>车型：" + $('#moveData').data('type') + "<br/>车牌：" + $('#moveData').data('plate') + "<br/>";
        marker3.emit('click', {target: marker3});
        marker3.on('click', markerClick);

//实例化信息窗体
        var title = '云杉智行';
        var infoWindow = new AMap.InfoWindow({
            isCustom: true,  //使用自定义窗体
            offset: new AMap.Pixel(16, -45)
        });

        function markerClick(e) {
            infoWindow.setContent(createInfoWindow(title, e.target.content));
            infoWindow.open(map, e.target.getPosition());
        }

        var lngX = 116.273881;
        var latY = 39.807409;
        /*lineArr = new Array();
         lineArr.push(new AMap.LngLat(lngX,latY));
         for (var i = 1; i <30; i++){
         lngX = lngX+Math.random()*0.05;
         if(i%2){
         latY = latY+Math.random()*0.0001;
         }else{
         latY = latY+Math.random()*0.06;
         }
         lineArr.push(new AMap.LngLat(lngX,latY));
         }alert(lineArr);
         console.log(lineArr);*/
        //绘制轨迹
        var polyline = new AMap.Polyline({
            map: map,
            path: lineArr,
            strokeColor: "#03f",//线颜色
            strokeOpacity: 1,//线透明度
            strokeWeight: 4,//线宽
            strokeStyle: "solid",//线样式
        });
    }

    $('#CarInfo').click(function () {
        if (!$('#plate_number').val() && $('#license_place').val() != 0) {
            alert('请输入车牌号');
            return false;
        }
        if ($('#plate_number').val() && $('#license_place').val() == 0) {
            alert('请选择车牌归属地');
            return false;
        }
    })
    $('#playBack').click(function () {
        $.ajax({
            type: "get",
            url: "/admin/statistics/playBackInterface",
            data: {
                plate_place: $('#moveData').data('plateplace'),
                plate_number: $('#plate_number').val(),
                start_time: $('#J-xl').val(),
                end_time: $('#J-xl2').val()
            },
            dataType: "json",
            success: function (data) {
                var lngX = 116.273881;
                var latY = 39.807409;
                var position = data.position;
                lineArr = new Array();

                $("#mileage").text(data.mileage);

                for (var i = ( position.length - 1); i >= 0; i--) {
                    lngX = position[i]['coordinate'][0];//.longitude;
                    latY = position[i]['coordinate'][1];//.latitude;
                    lineArr.push([lngX, latY]);
                    //lineArr.push(new AMap.LngLat(lngX,latY));
                }

                map.clearMap();
                completeEventHandler();

            }
        })

    })


    function startRun() {  //开始播放动画
                           // alert(lineArr);
        var speed = $('#speed').val();
        marker3.moveAlong(lineArr, (speed * 3600));   //开始轨迹回放
    }

    function endRun() {   //结束动画播放
        marker3.stopMove();  //暂停轨迹回放
    }

//轨迹回放结束
    var len = $('.rentalCar').length;

//获取参数
    function getQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]);
        return null;
    };
//定位
    var markers = [];

    function getPosition() {
        if (markers) {
            map.remove(markers);
        }

        $.ajax({
            type: "get",
            url: "/admin/statistics/locationInterface",
            data: {
                rentalStation: getQueryString('rental_station'),
                city: getQueryString('city'),
                plate_number: getQueryString('plate_number'),
            },
            dataType: "json",
            success: function (data) {
                var rentalCar = data.rentalCars;
                for (var i = 0; i < rentalCar.length; i++) {

                    if ($('#abnormal').is(":checked") && rentalCar[i].mileage == 0) {
                        if ((rentalCar[i].position).length != 0) {
                            var marker = new AMap.Marker({
                                icon: "/bundles/autoadmin/images/abnormal.png",
                                position: [rentalCar[i].position.longitude, rentalCar[i].position.latitude],//[$($('.rentalCar')[i]).data('position-longitude'),$($('.rentalCar')[i]).data('position-latitude')],
                                map: map
                            });
                        } else {
                            var marker = new AMap.Marker({
                                position: [116.492799, 39.934850333334],// [$($('.rentalCar')[i]).data('position-longitude'),$($('.rentalCar')[i]).data('position-latitude')],
                                visible: false,
                                map: map
                            });
                        }

                    } else if ($('#renting').is(":checked") && rentalCar[i].status == 301) {
                        var marker = new AMap.Marker({
                            icon: "/bundles/autoadmin/images/renting.png",
                            position: [rentalCar[i].position.longitude, rentalCar[i].position.latitude],//[$($('.rentalCar')[i]).data('position-longitude'),$($('.rentalCar')[i]).data('position-latitude')],
                            map: map
                        });
                    } else if ($('#waiting').is(":checked") && rentalCar[i].status == 300) {
                        var marker = new AMap.Marker({
                            icon: "/bundles/autoadmin/images/wait.png",
                            position: [rentalCar[i].position.longitude, rentalCar[i].position.latitude],// [$($('.rentalCar')[i]).data('position-longitude'),$($('.rentalCar')[i]).data('position-latitude')],
                            map: map
                        });
                    } else if ($('#off').is(":checked") && rentalCar[i].status == 702) {
                        var marker = new AMap.Marker({
                            icon: "/bundles/autoadmin/images/off.png",
                            position: [rentalCar[i].position.longitude, rentalCar[i].position.latitude],// [$($('.rentalCar')[i]).data('position-longitude'),$($('.rentalCar')[i]).data('position-latitude')],
                            map: map
                        });
                    } else if ($('#offRent').is(":checked") && rentalCar[i].status == 702 && rentalCar[i].status == 301) {
                        var marker = new AMap.Marker({
                            icon: "/bundles/autoadmin/images/offRent.png",
                            position: [rentalCar[i].position.longitude, rentalCar[i].position.latitude],// [$($('.rentalCar')[i]).data('position-longitude'),$($('.rentalCar')[i]).data('position-latitude')],
                            map: map
                        });
                    } else {
                        var marker = new AMap.Marker({
                            position: [rentalCar[i].position.longitude, rentalCar[i].position.latitude],// [$($('.rentalCar')[i]).data('position-longitude'),$($('.rentalCar')[i]).data('position-latitude')],
                            visible: false,
                            map: map
                        });
                    }


                    var Carstatus, mileage;
                    if (rentalCar[i].mileage == 0) {
                        Carstatus = '设备异常';
                        mileage = '-'
                    } else {
                        mileage = rentalCar[i].mileage
                        if (rentalCar[i].status == 300) {
                            Carstatus = '待租赁';
                        } else if (rentalCar[i].status == 301) {
                            Carstatus = '租赁中';
                        } else if (rentalCar[i].status == 702) {
                            Carstatus = '下线中';
                        } else {
                            Carstatus = '下线租赁中';
                        }
                    }
                    if (rentalCar[i].mileage != 0) {
                        marker.content = "租赁点：" + rentalCar[i].rentalStation.name + "<br/>车型：" + rentalCar[i].car.name + "<br/>车牌：" + rentalCar[i].license + "<br/>状态：" + Carstatus + "<br/>续航：" + mileage + "<br/><a class='a_color a_normal' href='/admin/rentalcar/show/" + rentalCar[i].rentalCarID + "'>详细信息</a>";
                    } else {
                        marker.content = "租赁点：" + rentalCar[i].rentalStation.name + "<br/>车型：" + rentalCar[i].car.name + "<br/>车牌：" + rentalCar[i].license + "<br/>状态：" + Carstatus + "<br/>续航：" + mileage + "<br/><a class='a_color a_unusual' href='/admin/rentalcar/show/" + rentalCar[i].rentalCarID + "'>详细信息</a>";
                    }
                    marker.emit('click', {target: marker});
                    marker.on('click', markerClick);

                    //实例化信息窗体
                    var title = '方恒假日酒店' + (i + 1) + '<span style="font-size:11px;color:#F00;">价格:318</span>';
                    var infoWindow = new AMap.InfoWindow({
                        isCustom: true,  //使用自定义窗体
                        offset: new AMap.Pixel(16, -45)
                    });
                    markers.push(marker);
                }

                function markerClick(e) {
                    infoWindow.setContent(createInfoWindow(title, e.target.content));
                    infoWindow.open(map, e.target.getPosition());
                }

//构建自定义信息窗体
                function createInfoWindow(title, content) {
                    var info = document.createElement("div");
                    info.className = "info";

                    //可以通过下面的方式修改自定义窗体的宽高
                    //info.style.width = "400px";
                    // 定义顶部标题
                    var top = document.createElement("div");
                    var titleD = document.createElement("div");
                    var closeX = document.createElement("span");
                    top.className = "info-top";
                    titleD.innerHTML = title;
                    closeX.innerHTML = "X";
                    closeX.className = "close-window";
                    //closeX.src = "http://webapi.amap.com/images/close2.gif";
                    closeX.onclick = closeInfoWindow;

                    top.appendChild(titleD);
                    top.appendChild(closeX);
                    info.appendChild(top);

                    // 定义中部内容
                    var middle = document.createElement("div");
                    middle.className = "info-middle";
                    middle.style.backgroundColor = 'black';
                    middle.innerHTML = content;
                    info.appendChild(middle);

                    // 定义底部内容
                    //var bottom = document.createElement("div");
                    //bottom.className = "info-bottom";
                    //bottom.style.position = 'relative';
                    //bottom.style.top = '0px';
                    //bottom.style.margin = '0 auto';
                    //var sharp = document.createElement("img");
                    //sharp.src = "http://webapi.amap.com/images/sharp.png";
                    //bottom.appendChild(sharp);
                    //info.appendChild(bottom);
                    return info;
                }

//关闭信息窗体
                function closeInfoWindow() {
                    map.clearInfoWindow();
                }

                map.setFitView();
            }
        });
    }

    if (window.location.pathname == '/admin/statistics/location') {
        if ($('.sts_over').length != 0) {
            //getPosition();
            //setInterval(getPosition, 10000);
        }
    }


//复选框
    $("[type='checkbox']").change(function () {
        //getPosition();
        closeInfoWindow();
    })
    function markerClick(e) {
        infoWindow.setContent(createInfoWindow(title, e.target.content));
        infoWindow.open(map, e.target.getPosition());
    }

//构建自定义信息窗体
    function createInfoWindow(title, content) {
        var info = document.createElement("div");
        info.className = "info";

        //可以通过下面的方式修改自定义窗体的宽高
        //info.style.width = "400px";
        // 定义顶部标题
        var top = document.createElement("div");
        var titleD = document.createElement("div");
        var closeX = document.createElement("span");
        top.className = "info-top";
        titleD.innerHTML = title;
        closeX.innerHTML = "X";
        closeX.className = "close-window";
        //closeX.src = "http://webapi.amap.com/images/close2.gif";
        closeX.onclick = closeInfoWindow;

        top.appendChild(titleD);
        top.appendChild(closeX);
        info.appendChild(top);

        // 定义中部内容
        var middle = document.createElement("div");
        middle.className = "info-middle";
        middle.style.backgroundColor = 'black';
        middle.innerHTML = content;
        info.appendChild(middle);

        // 定义底部内容
//            var bottom = document.createElement("div");
//            bottom.className = "info-bottom";
//            bottom.style.position = 'relative';
//            bottom.style.top = '0px';
//            bottom.style.margin = '0 auto';
//            var sharp = document.createElement("img");
//            sharp.src = "http://webapi.amap.com/images/sharp.png";
//            bottom.appendChild(sharp);
//            info.appendChild(bottom);
        return info;
    }

//关闭信息窗体
    function closeInfoWindow() {
        map.clearInfoWindow();
    }

    map.setFitView();
}


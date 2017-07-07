var rentalcarOrientationlocation_ref=/^\/admin\/rentalcar\/orientation/;

if(rentalcarOrientationlocation_ref.test(window.location.pathname)){
    var start = {
        elem: '#start',
        format: 'YYYY/MM/DD hh:mm:ss',
        istime: true,
        istoday: false,
        choose: function(datas){
            end.min = datas; //开始日选好后，重置结束日的最小日期
            end.start = datas //将结束日的初始值设定为开始日
        }
    };
    var end = {
        elem: '#end',
        format: 'YYYY/MM/DD hh:mm:ss',
        min: laydate.now(),
        istime: true,
        istoday: false,
        choose: function(datas){
            start.max = datas; //结束日选好后，重置开始日的最大日期
        }
    };
    laydate(start);
    laydate(end);

    var metod={
        lon:"",
        lat:"",
        license:"",
        setmarker: function () {
            var orientation_marker = new AMap.Marker({
                position: [this.lon,this.lat],
                content:"<div class='markercont offline'>"+this.license+"</div>",
                extData:{"license":this.license}
            });
            orientation_marker.setMap(map);

        },
        setCenter: function () {
            map.setCenter([this.lon,this.lat]);
        },
        clearMap: function () {
            map.clearMap();
        },
        addmarkers:function(data){
            console.log(data);
            console.log(data.lon);
            this.lon=data["lon"];
             this.lat=data["lat"];
             this.license=data["license"];
            this.clearMap();
            this.setmarker();
            this.setCenter();
        },
        changeCenter:function(lon,lat){
            this.lon=lon;
            this.lat=lat;
            this.setCenter();
        }
    };
    var map = new AMap.Map("container", {
        resizeEnable: true,
        center: [113.39327692145,23.059419454579],
        zoom: 13
    });
    if($("#rentalCar-lon").val() && $("#rentalCar-lat").val()){
        var data={
            lon:$("#rentalCar-lon").val(),
            lat:$("#rentalCar-lat").val(),
            license:$(".car-license").text()
        };
        metod.addmarkers(data);
    }
    $("#carlicenseplace").click(function (e) {
        e.preventDefault();
        if($("#plate_number").val()==null || $.trim($("#plate_number").val())==null){
          return false;
        }
        var license=$("#plate_number").val()+$.trim($("#plate_number").val());
        $.post("/api/rentalCar/car/detail",
            {
                "licenseplate":license
            },
            function (data, status) {
                if (status) {
                    if (data.errorCode == 0) {
                        console.log(data);

                        if(data["remainMileage"]==null || data["remainMileage"]==0){
                            $(".car-remainMileage").text("");
                        }
                        else {
                            $(".car-remainMileage").text(data["remainMileage"]+"km");
                        }
                        if(data["onlineStatus"]==1){
                            $(".onlineStatus").text("离线");
                        }
                        else if(data["onlineStatus"]==2){
                            $(".onlineStatus").text("在线");
                        }
                        else{
                            $(".onlineStatus").text("未知");
                        }

                        if(data["carStatus"]==300){
                            $(".carStatus").text("待租赁");
                        }
                        else  if(data["carStatus"]==301){
                            $(".carStatus").text("租赁中");
                        }
                        else {
                            $(".carStatus").text("");
                        }
                        $(".reportTime").text(data["reportTime"]);

                    }
                    else {
                        alert(data.errorMessage);
                    }

                    var cardata={
                        lon:data["longitude"],
                        lat:data["latitude"],
                        license:license
                    };
                    metod.addmarkers(cardata);
                }
                else {
                }
            });
    });

}
    $(function () {


        $(".getstation").on("click", function () {
            $(".gps").css("display", "block");
            $('#framenow').attr('src', $('#framenow').attr('src'));
        })
        $(".gps").on("click", function () {
            $(this).css("display", "none");
        })
        $(".opendoor").on("click", function () {
            $(".open-door").css("display", "block");
        });
        $(".closedoor").on("click", function () {
            $(".close-door").css("display", "block");
        });
        $(".onElectric").on("click", function () {
            $(".on_electric").css("display", "block");
        });
        $(".offElectric").on("click", function () {
            $(".off_electric").css("display", "block");
        });

        $(".resetDevice").on("click", function () {
            $(".reset_device").css("display", "block");
        });


        $(".cancel").on("click", function () {
            $(this).parents(".door").css("display", "none");
            $(this).siblings("input").val("");
        })
        $(".open-door").delegate(".open", "click", function () {

            var rentalCarID = $("#rental-car-id").val();
            var userID = $("#rental-user-id").val();
            var car_license_num = $.trim($("#car-license").text());
            var car_str = car_license_num.substr(1);
            var exg = new RegExp("^" + car_str, 'gi');
            var car = $.trim($(this).siblings("input").val());
            if (exg.test(car)) {
                $(this).siblings("input").val("");
                $.post("/api/rentalCar/operate",
                    {
                        operate: "open",
                        userID: userID,
                        rentalCarID: rentalCarID


                    },
                    function (data, status) {
                        $(".open-door").css("display", "none");
                        if (status) {
                            if (data.errorCode == 0) {
                                alert('已完成');

                            } else {
                                alert(data.errorMessage);

                            }

                        }

                    });
            }
            else {
                alert("你输入的车牌号有误");

            }
        });
        $(".reset_device").delegate(".reset", "click", function () {

            var rentalCarID = $("#rental-car-id").val();
            var userID = $("#rental-user-id").val();
            var car_license_num = $.trim($("#car-license").text());
            var car_str = car_license_num.substr(1);
            var exg = new RegExp("^" + car_str, 'gi');
            var car = $.trim($(this).siblings("input").val());
            if (exg.test(car)) {
                $(this).siblings("input").val("");
                $.post("/api/rentalCar/operate",
                    {
                        operate: "reset",
                        userID: userID,
                        rentalCarID: rentalCarID


                    },
                    function (data, status) {
                        $(".reset_device").css("display", "none");
                        if (status) {
                            if (data.errorCode == 0) {
                                alert('已完成');

                            } else {
                                alert(data.errorMessage);

                            }

                        }

                    });
            }
            else {
                alert("你输入的车牌号有误");

            }
        });
        $(".close-door").delegate(".lock", "click", function () {

            var rentalCarID = $("#rental-car-id").val();
            var userID = $("#rental-user-id").val();
            var car_license_num = $.trim($("#car-license").text());
            var car_str = car_license_num.substr(1);
            var exg = new RegExp("^" + car_str, 'gi');
            var car = $.trim($(this).siblings("input").val());
            if (exg.test(car)) {
                $(this).siblings("input").val("");
                $.post("/api/rentalCar/operate",
                    {
                        operate: "close",
                        userID: userID,
                        rentalCarID: rentalCarID
                    },
                    function (data, status) {
                        $(".close-door").css("display", "none");
                        if (status) {
                            if (data.errorCode == 0) {
                                alert('已完成');

                            } else {
                                alert(data.errorMessage);

                            }

                        }

                    });
            }
            else {
                alert("你输入的车牌号有误");
            }
        });


        $(".on_electric").delegate(".on", "click", function () {

            var rentalCarID = $("#rental-car-id").val();
            var userID = $("#rental-user-id").val();
            var car_license_num = $.trim($("#car-license").text());
            var car_str = car_license_num.substr(1);
            var exg = new RegExp("^" + car_str, 'gi');
            var car = $.trim($(this).siblings("input").val());
            if (exg.test(car)) {
                $(this).siblings("input").val("");
                $.post("/api/rentalCar/operate",
                    {
                        operate: "on",
                        userID: userID,
                        rentalCarID: rentalCarID


                    },
                    function (data, status) {
                        $(".on_electric").css("display", "none");
                        if (status) {
                            if (data.errorCode == 0) {
                                alert('已完成');

                            } else {
                                alert(data.errorMessage);

                            }

                        }

                    });
            }
            else {
                alert("你输入的车牌号有误");

            }
        });
        $(".off_electric").delegate(".off", "click", function () {

            var rentalCarID = $("#rental-car-id").val();
            var userID = $("#rental-user-id").val();
            var car_license_num = $.trim($("#car-license").text());
            var car_str = car_license_num.substr(1);
            var exg = new RegExp("^" + car_str, 'gi');
            var car = $.trim($(this).siblings("input").val());
            if (exg.test(car)) {
                $(this).siblings("input").val("");
                $.post("/api/rentalCar/operate",
                    {
                        operate: "off",
                        userID: userID,
                        rentalCarID: rentalCarID


                    },
                    function (data, status) {
                        $(".off_electric").css("display", "none");
                        if (status) {
                            if (data.errorCode == 0) {
                                alert('已完成');

                            } else {
                                alert(data.errorMessage);

                            }

                        }

                    });
            }
            else {
                alert("你输入的车牌号有误");

            }
        });
    })





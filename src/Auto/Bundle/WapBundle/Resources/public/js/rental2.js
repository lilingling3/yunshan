
$("#page.rentalCar2.show").delegate(".top-menu .gocarshow b","click",function(e){
    e.stopPropagation();
    e.preventDefault();
    $("#car-form").submit();
});

$("#page.rentalCar2.show").delegate(".change-s .change-a","click",function(){
    $("#change-station").submit();
});


$("#page.rentalCar2.show").delegate(".orderbtn","click",function(){
    $("#submitorder").submit();
});



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
        0:'/bundles/autowap/images/2.0-station-le.png',
        1:'/bundles/autowap/images/2.0-station1-le.png',
        2:'/bundles/autowap/images/2.0-station.png',
        3:'/bundles/autowap/images/2.0-station1.png',
        4:'/bundles/autowap/images/2.0-blue-point.png'
    };

var changeSurlArry=changeSurl.split("/"),changechsinfo,changeauto,changeMarkers=[];
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

if(changeSurlArry[changeSurlArry.length-1]=='changeStation') {

    map = new AMap.Map("container", {
        panToLocation: true,     //定位成功后将定位到的位置作为地图中心点，默认：true
        // 设置缩放级别
        center:[changePos.lng, changePos.lat],
        zoom: 14,
        resizeEnable: true
    });
    map.plugin(["AMap.Walking"], function() {
        walking = new AMap.Walking(); //构造路线导航类
    });
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
            "icon":imgbg[img]
        };
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
    var id = e.target.getExtData();
    var station=changechsinfo["stations"][id];
    if($("#orderID").val()){
        var  user=$("#user").val(),order=$("#orderID").val(),station=id;

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
    }
    else {
        if (station["parkingSpaceCount"] > 0) {
            $("#backS").val(id);
            $("#backstion").submit();
        }
    }
}





}



$("#page.rentalCar2.change-station").delegate(".gocarshow","click",function(){
    $("#backstion").submit();
});



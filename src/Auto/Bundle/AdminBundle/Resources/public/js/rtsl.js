var rentalstation_location_ref=/^\/admin\/rentalstation\/rtsl/;
if(rentalstation_location_ref.test(window.location.pathname)){

    var cluster,clusteroff, clustermarkers = [],clusteroffmarkers=[],car_count=0;
    var map = new AMap.Map("container", {
        resizeEnable: true,
        center: [113.39327692145,23.059419454579],
        zoom: 13
    });


    $(".fourteen").css("width","100% !important");

// 随机向地图添加50个标注点
    var mapBounds = map.getBounds();
    var sw = mapBounds.getSouthWest();
    var ne = mapBounds.getNorthEast();
    var lngSpan = Math.abs(sw.lng - ne.lng);
    var latSpan = Math.abs(ne.lat - sw.lat);
    var changeMarkers=[],timeer;
    var clusterflag={
        "offline":{no:3,has:5,amount:0},
        "online":{no:2,has:4,amount:0}
    };
    var changeStyle={
        amount:function(num){
            $(".car-count").text(toThousands(num));
        },
        update: function (datestr) {
            $('.update').text(datestr);
        }
    };

    var text_Re={};
    var citystations=$(".citystations .station-cityid");
    for(var i=0;i<citystations.length;i++){
        var t;
        var lis=$($(citystations[i]).find("li"));
        var cityid=$(citystations[i]).attr("cityid");
        text_Re[cityid]=[];
        for(var j=0;j<lis.length;j++){
            t={
                "sid":$(lis[j]).attr("sid"),
                "name":$(lis[j]).text()
            };
            text_Re[cityid].push(t);
        }
    }

    console.log(text_Re);

    lenght=text_Re.length;
    var ele_table=$(".choose-text").find("table");

    $("#stationname").keyup(function(){

        var cityid=$("#city").val();
        var reg=text_Re[cityid];
        var str=$(this).val();
        $(".choose-text").find("table").empty();
        var temp=new RegExp(str);
        if(reg==null || reg==undefined){
            $(".error").show();
            $(".choose-text").addClass("hide");
            $(".error").text("该城市没有租赁点");
            return false;
        }


        else if($.trim($(this).val())==""){
            $(".error").hide();
            for(var i=0;i<reg.length;i++){
                var ele_td=$("<td></td>");
                $(ele_td).attr("sid",reg[i]["sid"]);
                $(ele_td).text(reg[i]["name"]);
                var ele_tr=$("<tr></tr>");
                $(ele_tr).append($(ele_td));
                $(ele_table).append($(ele_tr));
            }
        }

        else {
            $(".error").hide();
            for(var i=0;i<reg.length;i++){
                if(temp.test(reg[i]["name"])){
                    var ele_td=$("<td></td>");
                    $(ele_td).attr("sid",reg[i]["sid"]);
                    $(ele_td).text(reg[i]["name"]);
                    var ele_tr=$("<tr></tr>");
                    $(ele_tr).append($(ele_td));
                    $(ele_table).append($(ele_tr));
                }

            }
        }

        var l=377;
        var ele_td=$(".choose-text").find("td");

        if($(ele_td).length>=10){
            $(".choose-text").css({height:"309px"});
        }
        else {
            $(".choose-text").css({height:"auto"});
        }
        console.log("showshow");
        $(".choose-text").removeClass("hide");
        $(".choose-text tr").hover(function(){
            $(this).addClass("hover");
        },function(){
            $(this).removeClass("hover");
        });

    }).focus(function(){
        $(this).triggerHandler("keyup");});


    $(".choose-text").delegate("td","click",function(e){
        var str=$(this).text();
        $("#stationname").val(str);
        $("#stationname").attr("sid",$(this).attr("sid"));
        $(".choose-text").addClass("hide");
        e?e.stopPropagation():event.cancelBubble = true;
    });
    $(".layer-btn-cont").delegate(".choosestation","click",function(e){
        console.log("showshow");
        $(".choose-text").removeClass("hide");
        e?e.stopPropagation():event.cancelBubble = true;
    });

    $(document).bind("click",function(e){
        var target = $(e.target);
        console.log(target);
        console.log(target.closest(".choose-text").length);
        if(target.closest(".choose-text").length == 0 && target.closest("#stationname").length==0){
            console.log("sssssssssssssss");
            $(".choose-text").addClass("hide");
        }

    })


    $(".choose-text").find("tr").hover(function(){
        $(this).addClass("hover");
    },function(){
        $(this).removeClass("hover");
    });


    /*AMap.event.addDomListener(document.getElementById('add0'), 'click', function() {
     addCluster(0);
     });
     AMap.event.addDomListener(document.getElementById('add1'), 'click', function() {
     addCluster(1);
     });*/
    /*AMap.event.addDomListener(document.getElementById('add2'), 'click', function() {
     addCluster(2);
     });*/

// 添加点聚合

    var sts = [ {
        url: "/bundles/autoadmin/images/circle-yellow.png",
        size: new AMap.Size(32, 32),
        offset: new AMap.Pixel(-16, -30)
    }, {
        url: "/bundles/autoadmin/images/circle-yellow.png",
        size: new AMap.Size(48, 48),
        offset: new AMap.Pixel(-24, -45),
        textColor: '#CC0066'
    }];

    var stsoff = [ {
        url: "/bundles/autoadmin/images/circle-red.png",
        size: new AMap.Size(32, 32),
        offset: new AMap.Pixel(-16, -30)
    }, {
        url: "/bundles/autoadmin/images/circle-red.png",
        size: new AMap.Size(48, 48),
        offset: new AMap.Pixel(-24, -45),
        textColor: '#CC0066'
    }];
    Cluster(null,null,null,null);
    function addCluster(tag) {

        if (tag == 1) {
            if (cluster) {
                cluster.setMap(null);
            }
            if (clusteroff) {
                clusteroff.setMap(null);
            }
            map.plugin(["AMap.MarkerClusterer"], function() {
                cluster = new AMap.MarkerClusterer(map, clustermarkers, {
                    styles: sts,maxZoom:17.9
                });
            });
            console.log(clusteroffmarkers);
            map.plugin(["AMap.MarkerClusterer"], function() {
                clusteroff = new AMap.MarkerClusterer(map, clusteroffmarkers, {
                    styles: stsoff,maxZoom:17.9
                });
            });
        }
        else if(tag == 2){//取消设备在线
            if (cluster) {
                cluster.setMap(null);
            }
        }
        else if(tag == 3){//取消设备离线
            if (cluster) {
                clusteroff.setMap(null);
            }
        }
        else if(tag == 4){//显示设备在线
            map.plugin(["AMap.MarkerClusterer"], function() {
                cluster = new AMap.MarkerClusterer(map, clustermarkers, {
                    styles: sts,maxZoom:17.9
                });
            });
        }
        else if(tag == 5){//显示设备离线
            map.plugin(["AMap.MarkerClusterer"], function() {
                clusteroff = new AMap.MarkerClusterer(map, clusteroffmarkers, {
                    styles: stsoff,maxZoom:17.9
                });
            });
        }
    }
    $(".clusterlocation").click(function(e){
        clustermarkers=null;
        clustermarkers=[];
        clusteroffmarkers=null;
        clusteroffmarkers=[];
        clearInterval(timeer);
        changeRemoveMarker(changeMarkers);
        map.clearMap();
        clusteroff.setMap(null);
        Cluster(null,null,null,null);
        $(".clusterlocation").addClass("active");
        $(".reallocation").removeClass("active");
        $(".clusterlocation-side").removeClass("hide");
    });

    $("#CarInfo").click(function(){
        var car =$("#car").val();
        var province =$("#province").val();
        var city =$("#city").val();
        var stationID=$("#stationname").attr("sid");
        Cluster(car,province,city,stationID);
    });
    function  Cluster(car,province,city,stationID){
        var newcar=(car && car!=0)?car:"";
        var newprovince=(province && province!=0)?province:"";
        var newcity=(city && city!=0)?city:"";
        var newstationID=(stationID && stationID!=0)?stationID:"";
        clustermarkers=null;
        clustermarkers=[];
        clusteroffmarkers=null;
        clusteroffmarkers=[];


        console.log("car::"+car);
        console.log("province::"+province);
        console.log("city::"+city);
        console.log("stationID::"+stationID);
        console.log( $(".equipment-checkbox"));
        $(".equipment-checkbox").attr("checked", true);
        $(".equipment-checkbox").prop("checked",true);
        if(cluster){
            cluster.setMap(null);
        }
        if (clusteroff) {
            clusteroff.setMap(null);
        }
        $.post("/api/rentalCar/curLocation",
            {
                carType:newcar,
                city:newcity,
                province:newprovince,
                stationID:newstationID
            },
            function(data,status){
                if(status){
                    console.log(data);
                    if(data.errorCode==0){
                        var offlinelist=data["offlinelist"];
                        var offl=offlinelist.length;
                        var onlinelist=data["onlinelist"];
                        var onl=onlinelist.length;
                        changeStyle.update(data["reportTime"]);
                        changeStyle.amount(offl+onl);
                        clusterflag["offline"]["amount"]=offl;
                        clusterflag["online"]["amount"]=onl;
                        for (var i = 0; i < offl; i++) {
                            var pos=offlinelist[i];
                            var markerPosition = [pos["lon"], pos["lat"]];
                            var marker = new AMap.Marker({
                                position: markerPosition,
                                content:"<div class='markercont offline'>"+pos["license"]+"</div>",
                                offset: {x: -8,y: -34},
                                extData:pos["license"],
                                extData:{"license":pos["license"]}
                            });
                            clusteroffmarkers.push(marker);
                            //changemarker.setMap(map);
                            marker.on('click', markerClick);
                        }

                        for (var i = 0; i < onl; i++) {
                            var pos=onlinelist[i];
                            var markerPosition = [pos["lon"], pos["lat"]];
                            var marker = new AMap.Marker({
                                position: markerPosition,
                                content:"<div class='markercont'>"+pos["license"]+"</div>",
                                offset: {x: -8,y: -34},
                                extData:{"license":pos["license"]}
                            });
                            clustermarkers.push(marker);
                            marker.on('click', markerClick);
                        }
                        addCluster(1);
                    }else{
                        alert(data.errorMessage);
                    }
                }
            });


    }
    $("#province").bind("change",function(e){
        console.log("wwwwwwwwwwwwwwwwwwwwwwwwww");
        $("#stationname").val("");
        $("#stationname").attr("sid","");
        var provinceid=$(this).val();
        console.log($("#city option[name='default']"));
        $("#city option").attr("selected",false);
        $("#city option[name='default']").attr("selected",true);
        console.log($("#city").val());
        $("#city option[name='city']").css("display","none");
        console.log( $("#city option[provinceid='"+provinceid+"']"));

        $("#city option[provinceid='"+provinceid+"']").css("display","block");
    });
    $("#city").bind("change",function(){
        console.log("citycitycitycitycitycitycitycitycitycity");
        $("#stationname").val("");
        $("#stationname").attr("sid","");
    });


    $(".equipment-checkbox").click(function () {
        $(this).toggleClass("checked");
        var flag=$(this).attr("equiqmenttype");
        var checkouen=$(".equipment-checkbox.checked");
        var num=0;
        for(var i= 0;i<checkouen.length;i++){
            var flagt=$(checkouen[i]).attr("equiqmenttype");
            num+=clusterflag[flagt]["amount"]
        }
        changeStyle.amount(num);
        if($(this).hasClass("checked")){
            addCluster(clusterflag[flag]["has"]);
        }
        else {
            addCluster(clusterflag[flag]["no"]);
        }
    });

    function toThousands(num) {
        var result = [ ], counter = 0;
        num = (num || 0).toString().split('');
        for (var i = num.length - 1; i >= 0; i--) {
            counter++;
            result.unshift(num[i]);
            if (!(counter % 3) && i != 0) { result.unshift(','); }
        }
        return result.join('');
    }

    $(".getmapsize").click(function(){
        console.log("mapsizemapsizemapsizemapsize--------");
        console.log(map.getSize());
        console.log(map.getScale());
        console.log("mapsizemapsizemapsizemapsize**********");
    });





    /*实时坐标*/

    $("#admin.rentalstation.rtsl .layer-button").delegate(".reallocation","click",function(e){
        $(".clusterlocation-side").addClass("hide");
        $(".car-detail").addClass("hide");
        clustermarkers=null;
        clustermarkers=[];
        clusteroffmarkers=null;
        clusteroffmarkers=[];
        timeer=setInterval("getcarspos()",5000);
        cluster.setMap(null);
        clusteroff.setMap(null);
        map.clearMap();
        $(".reallocation").addClass("active");
        $(".clusterlocation").removeClass("active");
        $(".clusterlocation-side").addClass("hide");
    });

    function  getcarspos(){
        $.post("/api/rentalCar/curLocation",function(data,status){
            if(status){
                if(data.errorCode==0){
                    changeRemoveMarker(changeMarkers);
                    addmarks(data);
                }else{
                    alert(data.errorMessage);
                }
            }
        });
    }



    function addmarks(markers){
        //var l=markers.length/*>500?500:markers.length*/;
        console.log(markers);
        var offlinelist=markers["offlinelist"];
        var offl=offlinelist.length;
        var onlinelist=markers["onlinelist"];
        var onl=onlinelist.length;

        for(var i= 0;i<offl;i++){
            var markerarr=offlinelist[i];
            var markerpos=markerarr;
            if(!markerpos["lon"] || !markerpos["lat"]){
                continue;
            }
            var changemarker = new AMap.Marker({
                position: [markerpos["lon"],markerpos["lat"]],
                content:"<div class='markercont offline'>"+markerpos["license"]+"</div>",
                extData:{"license":markerpos["license"]}
            });
            changeMarkers.push(changemarker);
            changemarker.setMap(map);
            changemarker.on('click', markerClick);
        }
        for(var i= 0;i<onl;i++){
            var markerarr=onlinelist[i];
            var markerpos=markerarr;
            if(!markerpos["lon"] || !markerpos["lat"]){
                continue;
            }
            var changemarker = new AMap.Marker({
                position: [markerpos["lon"],markerpos["lat"]],
                content:"<div class='markercont'>"+markerpos["license"]+"</div>",
                extData:{"license":markerpos["license"]}
            });
            changeMarkers.push(changemarker);
            changemarker.setMap(map);
            changemarker.on('click', markerClick);
        }


    }





    function changeRemoveMarker(markers){
        map.remove(markers);
    }

    function  markerClick(e){
        $(".car-detail").removeClass("hide");
        var  marker_data =  e.target.getExtData();
        console.log(marker_data["license"]);
        $(".car-license").text(marker_data["license"]);
        $.post("/api/rentalCar/car/detail",
            {
                "licenseplate":marker_data["license"]
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
                        $(".reportTime").text(data["reportTime"]);

                    }
                    else {
                        alert(data.errorMessage);
                    }
                }
                else {
                }
            });

    }

    $(".car-detail .close").click(function () {
        $(".car-detail").hide();
    });

    $("#carorientation").click(function(e){
        e.preventDefault();
        var licensePlaces =$("#licensePlaces").val();
        var plate_number = $.trim($("#plate_number").val());
        $("#plate_number").val(plate_number);
        if(licensePlaces==0 || plate_number==null){
            alert("请选择车牌归属地和填写车牌号！");
            return false;
        }
        else {
            $(".rentalcar-location").submit();
        }
    });
}
YUI().use('auto-cascading', function (Y) { });

var businessDistrict_location_ref=/^\/admin\/area\/businessDistrict\/new/;
var businessDistrictEdit_location_ref=/^\/admin\/area\/businessDistrict\/edit/;
var areaNew_location_ref=/^\/admin\/area\/new/;
var areaEdit_location_ref=/^\/admin\/area\/edit/;
var areaProvinceEdit_location_ref=/^\/admin\/area\/province\/edit/;
console.log( areaProvinceEdit_location_ref.test(window.location.pathname));
if(areaEdit_location_ref.test(window.location.pathname) || businessDistrict_location_ref.test(window.location.pathname) || businessDistrictEdit_location_ref.test(window.location.pathname)|| areaNew_location_ref.test(window.location.pathname)|| areaProvinceEdit_location_ref.test(window.location.pathname)){

    var pre_lat = $(".latitude").val() ? $(".latitude").val() : 39.915;
    var pre_lng = $(".longitude").val() ? $(".longitude").val() : 116.4035;
    var map = new AMap.Map("area-business-allmap", {
        resizeEnable: true,
        center: [pre_lng, pre_lat],
        zoom: 13
    });
    map.plugin(["AMap.ToolBar"], function () {
        var toolBar = new AMap.ToolBar();
        map.addControl(toolBar);
    });

    addMarker(pre_lng, pre_lat);
    var clickEventListener = AMap.event.addListener(map, 'click', function (e) {
        marker.setMap(null);
        $(".longitude").val(e.lnglat.getLng());
        $(".latitude").val(e.lnglat.getLat());
        addMarker(e.lnglat.getLng(), e.lnglat.getLat());
    });

    function addMarker(lng, lat) {

        marker = new AMap.Marker({
            icon: "/bundles/autoadmin/images/map-marker.png",
            position: new AMap.LngLat(lng, lat)
        });
        marker.setMap(map);  //在地图上添加点

    }
}

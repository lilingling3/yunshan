$(document).ready(function(){
    var rentalstation_rtsl_url=/^\/admin\/rentalstation\/rtsl/;
    if(! rentalstation_rtsl_url.test(window.location.pathname)) {
        if ($("#admin").hasClass('rentalstation')) {
            (function (exports) {

                var pre_lat = $("#auto_bundle_managerbundle_rentalstation_latitude").val() ? $("#auto_bundle_managerbundle_rentalstation_latitude").val() : 39.915;
                var pre_lng = $("#auto_bundle_managerbundle_rentalstation_longitude").val() ? $("#auto_bundle_managerbundle_rentalstation_longitude").val() : 116.4035;


                var map = new AMap.Map('allmap', {

                    view: new AMap.View2D({
                        center: new AMap.LngLat(pre_lng, pre_lat),
                        zoom: 12
                    })

                });
                //地图中添加地图操作ToolBar插件
                map.plugin(["AMap.ToolBar"], function () {
                    var toolBar = new AMap.ToolBar();
                    map.addControl(toolBar);
                });

                addMarker(pre_lng, pre_lat);


                var clickEventListener = AMap.event.addListener(map, 'click', function (e) {
                    marker.setMap(null);
                    $("#auto_bundle_managerbundle_rentalstation_longitude").val(e.lnglat.getLng());
                    $("#auto_bundle_managerbundle_rentalstation_latitude").val(e.lnglat.getLat());
                    addMarker(e.lnglat.getLng(), e.lnglat.getLat());
                });


                function addMarker(lng, lat) {

                    marker = new AMap.Marker({
                        icon: "/bundles/autoadmin/images/map-marker.png",
                        position: new AMap.LngLat(lng, lat)
                    });
                    marker.setMap(map);  //在地图上添加点
                }


            })(window);
        }
    }
});
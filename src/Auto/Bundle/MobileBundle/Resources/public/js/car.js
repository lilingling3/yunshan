var car_location_ref=/^\/mobile\/car\/show/;
if(car_location_ref.test(window.location.pathname)) {
    $(".tabcont").delegate(".tab","click",function(){
        $(this).addClass("active");
        $(this).siblings(".tab").removeClass("active");
        var tab_data=$(this).attr("tab-d");
        $(".tabdata-cont "+tab_data).addClass("active");
        $(".tabdata-cont "+tab_data).siblings(".tabdata").removeClass("active");

    })
}
var database_location_ref=/^\/mobile\/activity\/database/;
var api_location_ref=/^\/mobile\/activity\/api/;
if(database_location_ref.test(window.location.pathname)||api_location_ref.test(window.location.pathname)) {
    var leftrank=$(".leftrank");
    var jsonrank={};
    for(var i=0;i<leftrank.length;i++){
        jsonrank[$(leftrank[i]).find("span").text()]=i;
    }
    console.log(jsonrank);
    $("#page.activity .nav").delegate(".leftrank", "click", function () {
        var navrow=$(this).parents(".navrow");
        $(".navrow").toggleClass("active");
        $(navrow).siblings(".navrow").removeClass("active");
        var toph=$(this).parents(".navrow").hasClass("active")?(20-jsonrank[$(this).find("span").text()]*38)+"px":'20px';
        $(".nav").css("top",toph);
    });
    $("#page.activity .nav").delegate(".navrow .rank2", "click", function () {
        $(this).parent(".navrow").siblings().removeClass("active");
        $(".rank2").removeClass("actived");
        $(this).addClass("actived");
    });

}


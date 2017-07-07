$(function(){

    if($("#page .count_down").length>0){
        var time = ($(".count_down").attr("data-time"));
           countDown(time,".count_down");
    }

});
function countDown(time,id){

    var end_time = new Date(Date.parse(time.replace(/-/g, "/"))).getTime();//月份是实际月份-1

    var sys_second = (end_time-new Date().getTime())/1000;

    var timer = setInterval(function(){

        if (sys_second > 1) {
            sys_second -= 1;
            var day = Math.floor((sys_second / 3600) / 24);
            var hour = Math.floor((sys_second / 3600) % 24);
            var minute = Math.floor((sys_second / 60) % 60);
            var second = Math.floor(sys_second % 60);

            var time_text = '';
            if(day>0){
                time_text+=day+'天';
            }
            if(hour>0){
                if(hour<10){
                    hour='0'+hour;
                }
                time_text+=hour+'小时';

            }
            if(minute>0){
                if(minute<10){
                    minute='0'+minute;
                }
                time_text+=minute+'分';

            }
            if(second>0){
                if(second<10){
                    second='0'+second;
                }
                time_text+=second+'秒';
            }

            $(id).text(time_text);

        } else {
            clearInterval(timer);
        }
    }, 1000);

}

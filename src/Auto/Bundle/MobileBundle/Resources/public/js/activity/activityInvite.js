
var incident={
    initialize: function () {
        $(".btn").bind("tap",function() {
            submintData();
            $(".btn").unbind("click");
        });
        $(".btn").bind("click",function() {
            submintData();
            $(".btn").unbind("tap");
        });
        $(".btn").removeClass("btn-gray");
    },
    end:function(){
        $(".btn").unbind("tap");
        $(".btn").unbind("click");
        $(".btn").addClass("btn-gray");
    }
};

incident.initialize();
function submintData(){
    var mobile = $('#mobile').val();
    if (mobile == "") {
        alert('请输入手机号');
        return false;
    }
    if (! checkMobile(mobile)) {
        alert('手机号错误!');
        return false;
    }
    mobile=parseInt(mobile);
    var useid=$("#useid").val();
    var utoken=$("#utoken").val();
    var channel=$("#channel").val();
    incident.end();
    $.post("/api/invite/addInvite",
        {
            userID:useid,
            token:utoken,
            channel:channel,
            mobile:mobile
        },
        function(data,status){
            if(status){
                data=JSON.parse(data);
                if (data.errorCode == 0 ) {
                    window.location.href="http://a.app.qq.com/o/simple.jsp?pkgname=com.drivevi.drivevi";
                }
                else{
                    alert(data.errorMessage);
                    incident.initialize();
                    return ;
                }

            }
        });
    return;
}
function checkMobile (str) {

    var re = /^1[3|4|5|8|7]\d{9}$/;

    if (re.test(str)) {
        return true;
    } else {
        return false;
    }
}

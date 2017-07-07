var rentalCar_location_ref=/^\/mobile\/rentalCar\/question\/\d+/;
$("#page.rental_car.show .change-s").on("click",function(){
    $("#change-station").submit();
});

$("#page.rental_car.show .affirmorder").bind("click",function(){
    $("#submitorder").submit();
    $("#page.rental_car.show .affirmorder").unbind("click");
});

if(rentalCar_location_ref.test(window.location.pathname) ) {
    $('.q-cont').click(function () {
     $(this).toggleClass("active");
    });
    var variable={
        "input": function(){
            this.input=$('<input type="text" name="images"  />');
            return  this.input;
        },
        "img":function(){
            this.img=$("<img src='' />");
            return this.img;
        },
        "dom":function(flag){
            var dom=$('<div class="img-cont">\
                <span class="pluse-icon"></span>\
                <div class="inner-img-cont " flag="'+flag+'"> \
                <span class="plus"></span> \
                </div>\
                </div>');

            return  dom;
        }
    },magesArr={};
    wx.config({
        'debug': false,
        'appId': $("#appId").val(),
        'timestamp':$("#timestamp").val() ,
        'nonceStr': $("#nonceStr").val(),
        'signature': $("#signature").val(),
        'jsApiList': ['chooseImage',"checkJsApi","uploadImage","getNetworkType"] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
    });
    wx.ready(function () {
        // 在这里调用 API
        wx.checkJsApi({
            jsApiList: [
                'getNetworkType',
                'chooseImage',
                'uploadImage'
            ],
            success: function (res) {
                console.log(JSON.stringify(res));
            }
        });
    });
       var num=0;
    $(".b-cont .inner").delegate(".inner-img-cont","click",function(){
        var that=this;
       /* var url="http://testys.cn/bundles/automobile/images/banner1.png";
        setimg(url,num++,$(this));*/
        wx.chooseImage({
            count: 1, // 默认9
            sizeType: ['compressed'], // 可以指定是原图还是压缩图，默认'original', 'compressed'二者都有
            sourceType: ['camera'], // 可以指定来源是相册还是相机，默认二者都有
            success: function (res) {
                var localIds=res.localIds;
                setTimeout(wxloadimg(localIds,$(that)),100);
            }
        })
    });

    $(".b-cont .inner").delegate(".pluse-icon","click",function(){
        var flag = $(this).siblings(".inner-img-cont").attr("flag");
        delete magesArr[flag];
        $(this).parent().remove();
        var dom = new  variable.dom(flag);
        $(dom).appendTo($(".inner"));
        var imgshow = $(".inner-img-cont.active");

        var imgshowlength= imgshow.length;

        if(imgshowlength > 0){
            var imgcont= $(imgshow[imgshowlength-1]).parent().siblings(".img-cont").find(".inner-img-cont").not(".active");
            var length=imgcont.length;
            $(imgcont).removeClass("show");
            $(imgcont[0]).addClass("show");
        }

    });


    function wxloadimg(e,temp){
        wx.uploadImage({
            localId: e.toString(), // 需要上传的图片的本地ID，由chooseImage接口获得
            isShowProgressTips: 1, // 默认为1，显示进度提示
            success: function (res) {
                setimg(e,res.serverId,$(temp));
            },
            fail: function (res) {
               alert("失败");
            }
        });
    }

    function setimg(localIds,serverId,temp){
        var imgDom;
        if($(temp).find("img").length>0){
            imgDom=$(temp).find("img");
        }
        else {
            imgDom= new variable.img();
            $(imgDom).appendTo($(temp));
            var num=$(".b-cont .title i").text();
            var imgnum=parseInt(num.split("/")[0]);
            var t=imgnum+1;
            t=t>3?3:t;
            $(".b-cont .title i").text(t+"/3");
            $(temp).addClass("active");
            $(temp).parent().next(".img-cont").find(".inner-img-cont").addClass("show");
        }
        magesArr[$(temp).attr("flag")]= serverId;
        $(temp).siblings(".pluse-icon").addClass("show");
        $(imgDom).attr("src",localIds)
        $(temp).find(".plus").hide();
    }
    $(".btn").click(function(){
        var t=jsonJoin(magesArr,",")
        $("#images-arr").val(t);
       if($(".q-cont.active").length<1){
            alert("请至少选择一个问题！");
            return false;
        }
       if($(".inner-img-cont img").length<1 ||　$("#images-arr").val()==""){
            alert("请至上传一张照片！");
            return false;
        }
        var qs=$(".q-cont.active");
        var qstemp="";
        for(var i=0;i<qs.length;i++){
            qstemp+=$(qs[i]).attr("qid");
        }
        $("#questions").val(qstemp);

       $("form").submit();
    });
    function jsonJoin(data,t){
         var temp="";
        for (var o in data){
            temp = temp == "" ? o : temp + t + o;
        }
       return temp;
    }
}


if(window.location.pathname=="/mobile/account/identify") {
    var verify_img={
        "licenseImage": {
            "model":"/bundles/automobile/images/example-license.png",
            "photo":"",
            "localId":""
        },
        "idImage": {
            "model":"/bundles/automobile/images/example-idcard.png",
            "photo":"",
            "localId":""
        },
        "idHandImage": {
            "model":"/bundles/automobile/images/example-person.png",
            "photo":"",
            "localId":""
        }
    };


        $.each($('.auth-file .uploadimg'), function (i, item) {
            console.log(item);
            console.log($(item).val());
            console.log(item.value);
            console.log($(item).attr("name"));
            if (item.value) {
                verify_img[$(item).attr("name")]["photo"]=item.value;
            }

        });

console.log(verify_img);
$(".verify-img").click(function () {
    var that=this;
    if($(".authstatus").attr("authstatus") !=201){
        setTimeout(function(){
            if($(that).hasClass("camera")){
                paizhao($(that).attr("dtype"));
            }
            else{
                $(".float-layer").addClass("show");
                console.log(that);
                showLayer(verify_img[$(that).attr("dtype")],$(that).attr("dtype"));
            }
        },300);
    }


});
    function showLayer(data,t){

        $(".float-layer .layer-img1 img").attr("src",data["model"]);
        $(".float-layer .layer-img-cont2").addClass("hide");
        $(".float-layer .btn-cont").attr("imgcon",t);
        if(data["photo"]){
            $(".float-layer .layer-img2 img").attr("src",data["photo"]);
            $(".float-layer .layer-btn1").removeClass("hide");
        }
        else{
            $(".float-layer .layer-img2 img").attr("src","");
            $(".float-layer .layer-btn1").addClass("hide");
        }
    }

    $("#page.account.identify").delegate(".float-layer .layer-btn1", "click", function () {
        $(".float-layer .layer-btn1").addClass("hide");
        $(".float-layer .layer-img-cont2").removeClass("hide");

    });

    $("#page.account.identify").delegate(".float-layer .layer-btn2", "click", function () {
        paizhao($(this).parent(".btn-cont").attr("imgcon"));
    });

    $("#page.account.identify").delegate(".float-layer .fork", "click", function () {
        $(".float-layer").removeClass("show");

    });


    /* $("#page.account.identify").delegate(".auth-file .upload", "change", function () {
            var objUrl = getObjectURL(this.files[0]);
            if (this.files[0].size > 8000000) {
                alert('图片太大,请控制在2M之内!');
                return false;
            }
            if (objUrl) {
                $($(this).attr("img-data") + " img").attr("src", objUrl);
            }
        });*/
    $("#page.account.identify").delegate(".upload-submit1", "click", function () {
        var s = true;
       $.each($('.auth-file .uploadimg'), function (i, item) {
            if (!item.value) {
                alert('请上传照片');
                s = false;
                return false;
            }

        });
        if (s) $(this).parents('form').submit();
    });


    function getObjectURL(file) {
        var url = null;
        if (window.createObjectURL != undefined) { // basic
            url = window.createObjectURL(file);
        } else if (window.URL != undefined) { // mozilla(firefox)
            url = window.URL.createObjectURL(file);
        } else if (window.webkitURL != undefined) { // webkit or chrome
            url = window.webkitURL.createObjectURL(file);
        }
        return url;
    }
}
$("#page.account.auth .balance").on("click",function(){
    $(".layer").addClass("show");
    $(".floatlayer").addClass("show");
});

$("#page.account.auth .floatlayer .closelayer").on("click",function(){
    $(".layer").removeClass("show");
    $(".floatlayer").removeClass("show");
});


$(".cover").click(function(){
    $(this).parents('li').find('input').trigger('click');
    //$("input[name=licenseImage]").trigger('click');
});

$(".identify-submit").click(function() {
    var s = true;

    //status
    var status = $("#licenseimage").attr("authstatus");

    //upload status
    var lnImgs = $("input[name='licenseImage']").val().length;
    var iImgs = $("input[name='idImage']").val().length;
    var iHImgs = $("input[name='idHandImage']").val().length;

    //is image upload ;success: 0 false 1 :
    var lienceSts    = $("#licenseimage").attr('img-flag');
    var idimageSts   = $("#idimage").attr('img-flag');
    var idhandimgSts = $("#idhandimage").attr('img-flag');

console.log(status);
console.log(lnImgs);
console.log(iImgs);
console.log(iHImgs);
console.log("lienceSts:" + lienceSts);
console.log("idimageSts:" + idimageSts);
console.log("idhandimgSts:" + idhandimgSts);
console.log( typeof(lienceSts) );
if(lnImgs != ""){
	console.log("hello");
}

    if( status ==200 ){
        if( !$("input[name='licenseImage']").val() ) {
            s = false;
        }
        if( !$("input[name='idImage']").val() ) {
            s = false;
        }
        if( !$("input[name='idHandImage']").val() ) {
            s = false;
        }
    }

    if ( status ==202 || status ==203) {

        if ( parseInt(lienceSts) && (lnImgs < 1) ) {
            console.log('aaa');
            s = false;
        }

        if ( parseInt(idimageSts) && (iImgs < 1) ) {
            console.log('bbb');
            s = false;
        } 
        if ( parseInt(idhandimgSts) && (iHImgs < 1) ) {
            console.log('ccc');
            s = false;
        }
    }

    if(!s) {
        alert('请根据提示上传照片');
        return;
    }
    $('form').trigger('submit');
});






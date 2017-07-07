// JavaScript Document
$(document).ready(function () {
    var arr=[
        "/bundles/autowap/main/img/bg1.jpg",
        "/bundles/autowap/main/img/bg2.jpg",
        "/bundles/autowap/main/img/bg2.jpg",
        "/bundles/autowap/main/img/img1.png",
        "/bundles/autowap/main/img/img2.png",
        "/bundles/autowap/main/img/img3.png",
        "/bundles/autowap/main/img/img4.png",
        "/bundles/autowap/main/img/img5.png",
        "/bundles/autowap/main/img/img6.png",
        "/bundles/autowap/main/img/img7.png",
        "/bundles/autowap/main/img/img8.png",
        "/bundles/autowap/main/img/img33.png",
        "/bundles/autowap/main/images/wx_ion1.png",
        "/bundles/autowap/main/images/img1.png",
        "/bundles/autowap/main/images/img2.png",
        "/bundles/autowap/main/images/img3.png",
        "/bundles/autowap/main/images/img4.png",
        "/bundles/autowap/main/images/img5.png",
        "/bundles/autowap/main/images/img6.png",
        "/bundles/autowap/main/images/img7.png",
        "/bundles/autowap/main/images/img8.png",
        "/bundles/autowap/main/images/img9.png",
        "/bundles/autowap/main/images/img10.png",
        "/bundles/autowap/main/images/img11.png",
        "/bundles/autowap/main/images/img12.png",
        "/bundles/autowap/main/images/img13.png",
        "/bundles/autowap/main/images/img14.png"
    ];
    $(".hudnfg img").each(function(index, element) {
        arr.push($(this).attr("src"));
    });
    allimgL=arr.length;
    var loader = new PxLoader();
    for(var i=0;i<allimgL;i++) {
        var pxImage = new PxLoaderImage(arr[i]);
        pxImage.imageNumber = i + 1;
        loader.add(pxImage);
    }
    snum=0;
    //初始化声音控件;
    //soundManager.setup({});
    soundManager.url = '"/bundles/autowap/main/js/';
    soundManager.flashVersion = 8;
    //soundManager.useHighPerformance = true; // 减少延误
    // reduce the default 1 sec delay to 500 ms
    soundManager.flashLoadTimeout = 500;
    // mp3 is required by default, but we don't want any requirements
    soundManager.audioFormats.mp3.required = true;
    // flash may timeout if not installed or when flashblock is installed
    soundManager.allowPolling = true;


    soundManager.onready(function() {
        glass1=soundManager.createSound({
            id: 'glass1',
            url: '../../bundles/autowap/main/audio/1.mp3',
            autoLoad: true,
            onfinish: function() {
                //soundManager.play("aCymbalSound1");
            }
        });
        glass2=soundManager.createSound({
            id: 'glass2',
            url: '../../bundles/autowap/main/audio/2.mp3',
            autoLoad: true,
            onfinish: function() {
                //soundManager.play("aCymbalSound1");
            }
        });

    });
    soundManager.ontimeout(function(status) {
        // no flash, go with HTML5 audio
        soundManager.useHTML5Audio = true;
        soundManager.preferFlash = false;
        soundManager.reboot();
    });
    loader.addProgressListener(function(e){
        var cnum=e.completedCount;
        var tnum=e.totalCount;
        var percent=parseInt(((Number(cnum))/(Number(tnum)))*100);
        $(".title_loadnum span").html(percent);
        if(percent>=100){
            setTimeout(function(){
                $(".load").fadeOut(1000);
                setTimeout(function(){
                    $(".page").eq(0).addClass("curt");
                    //$(".page5").show();
                    setTimeout(function(){
                        //$(".the_news").show();
                        //wxstart();
                        //$(".costion").eq(0).addClass("curt");
                    },200);
                },1100);

            },300);
        }

    });
    loader.start();
});

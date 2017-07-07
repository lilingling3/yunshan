// JavaScript Document
$(document).ready(function () {
	var arr=[
		"/bundles/autowap/lecar/images/bg1.jpg",
		"/bundles/autowap/lecar/images/bg2.jpg",
		"/bundles/autowap/lecar/images/bg2.jpg",
		"/bundles/autowap/lecar/images/img1.png",
		"/bundles/autowap/lecar/images/img2.png",
		"/bundles/autowap/lecar/images/img3.png",
		"/bundles/autowap/lecar/images/img4.png",
		"/bundles/autowap/lecar/images/img5.png",
		"/bundles/autowap/lecar/images/img6.png",
		"/bundles/autowap/lecar/images/img7.png",
		"/bundles/autowap/lecar/images/img8.png",
		"/bundles/autowap/lecar/images/img9.png",
		"/bundles/autowap/lecar/images/img10.png",
		"/bundles/autowap/lecar/images/img11.png",
		"/bundles/autowap/lecar/images/img12.png",
		"/bundles/autowap/lecar/images/img13.png",
		"/bundles/autowap/lecar/images/img14.png",
		"/bundles/autowap/lecar/images/img15.png",
		"/bundles/autowap/lecar/images/img16.png",
		"/bundles/autowap/lecar/images/img17.png",
		"/bundles/autowap/lecar/images/img18.png",
		"/bundles/autowap/lecar/images/img19.png",
		"/bundles/autowap/lecar/images/img20.png",
		"/bundles/autowap/lecar/images/img21.png",
		"/bundles/autowap/lecar/images/img22.png",
		"/bundles/autowap/lecar/images/img23.png",
		"/bundles/autowap/lecar/images/img24.png",
		"/bundles/autowap/lecar/images/img25.png",
		"/bundles/autowap/lecar/images/img26.png",
		"/bundles/autowap/lecar/images/img27.png",
		"/bundles/autowap/lecar/images/img28.png",
		"/bundles/autowap/lecar/images/img29.png",
		"/bundles/autowap/lecar/images/img30.png",
		"/bundles/autowap/lecar/images/img31.png",
		"/bundles/autowap/lecar/images/img32.png",
		"/bundles/autowap/lecar/images/img33.png",
		"/bundles/autowap/lecar/images/mf.png"
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
		soundManager.url = 'js/'; 
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
			url: '',
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
				},1000);
			},300);
		}
		
	});
	loader.start();
});

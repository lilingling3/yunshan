// JavaScript Document
$(document).ready(function () {
	var arr=[
		"/bundles/autowap/images/goldfinger/1.png",
		"/bundles/autowap/images/goldfinger/2.png",
		"/bundles/autowap/images/goldfinger/3.png",
		"/bundles/autowap/images/goldfinger/4.png",
		"/bundles/autowap/images/goldfinger/5.png",
		"/bundles/autowap/images/goldfinger/6.png",
		"/bundles/autowap/images/goldfinger/y1.png",
		"/bundles/autowap/images/goldfinger/y2.png",
		"/bundles/autowap/images/goldfinger/img1.png",
		"/bundles/autowap/images/goldfinger/img2.png",
		"/bundles/autowap/images/goldfinger/img3.png",
		"/bundles/autowap/images/goldfinger/img4.png",
		"/bundles/autowap/images/goldfinger/img5.png",
		"/bundles/autowap/images/goldfinger/img6.png",
		"/bundles/autowap/images/goldfinger/img7.png",
		"/bundles/autowap/images/goldfinger/img8.png",
		"/bundles/autowap/images/goldfinger/img9.png",
		"/bundles/autowap/images/goldfinger/img10.png",
		"/bundles/autowap/images/goldfinger/img11.png",
		"/bundles/autowap/images/goldfinger/img12.png",
		"/bundles/autowap/images/goldfinger/img13.png",
		"/bundles/autowap/images/goldfinger/img15.png",
		"/bundles/autowap/images/goldfinger/img16.png"
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
			url: '/bundles/autowap/audio/glass1.mp3',
			autoLoad: true,
			onfinish: function() { 
				//soundManager.play("aCymbalSound1");
			}
		  });
		  glass2=soundManager.createSound({
			id: 'glass2',
			url: '/bundles/autowap/audio/glass2.mp3',
			autoLoad: true,
			onfinish: function() { 
				//soundManager.play("aCymbalSound1");
			}
		  });
		  glass3=soundManager.createSound({
			id: 'glass3',
			url: '/bundles/autowap/audio/glass3.mp3',
			autoLoad: true,
			onfinish: function() { 
				//soundManager.play("aCymbalSound1");
			}
		  });
		glass=soundManager.createSound({
			id: 'glass',
			url: '/bundles/autowap/audio/Glass.mp3',
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
					setTimeout(function(){
						$(".tit1").addClass("titleIn");
						setTimeout(function(){
							shake=1;
						},500);
					},200);
				},1100);
				
			},300);
		}
		
	});
	loader.start();
});

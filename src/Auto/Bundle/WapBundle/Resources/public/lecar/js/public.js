// JavaScript Document
 var iniCss = {
		height: document.documentElement.clientHeight,
		width: document.documentElement.clientWidth
	}
	$("html,body").css(iniCss);
	$(".load").css(iniCss);
	ah=$(window).height();
	$("body").on("touchmove",function(){
		 event.preventDefault();
	});
if(/Android (\d+\.\d+)/.test(navigator.userAgent)){}else{
	$("body").addClass("mphone");
}
var shake=autoplay=clickd=-1;
var canpaly=canpaly1=canpaly2=canpaly3=canpaly4=unkclic=1;
mpdunclick=-1;
//clickd=1;
$(document).ready(function () {
	FastClick.attach(document.body);
	//头内饰点击;
	$(".chod_spn").click(function(){
		$(".cartips").hide();
		$(".car_top").hide();
		if(canpaly1>0){
			canpaly1=-1;
			
		}else{
			unkclic=-1;	
		}
		gameadd();
		
	});
	//车尾内饰;
	$(".new_cold").click(function(){
		$(".cartips").hide();
		$(".car_bot").hide();
		$(".car_top").hide();
	});
	//继续寻宝;
	$(".gonext,.colsed").click(function(){
			unkclic=1;
			$(".gametiops").hide();
		
	});
	//前往游戏;
	$(".startbuton").click(function(){
		$(".page2").show().css("-webkit-transform","translateY("+ah+"px)");
		setTimeout(function(){
			$(".page1").addClass("stion").css("-webkit-transform","translateY("+-ah+"px)");
			$(".page2").addClass("stion").css("-webkit-transform","translateY("+0+"px)");
			setTimeout(function(){
				$(".page1").removeClass("curt");
				$(".page2").addClass("curt");
				$(".stion").removeClass("stion");
			},500);
		},50);
	});
	//开始游戏；
	$(".butonstat").click(function(){
		$(".gamestart").hide();
		time_out();
	});
	//重新玩;
	$(".replay").click(function(){
		$(".page1").addClass("stion").css("-webkit-transform","translateY("+0+"px)");
		$(".page2").addClass("stion").css("-webkit-transform","translateY("+ah+"px)");
		setTimeout(function(){
			$(".page2").removeClass("curt");
			$(".page1").addClass("curt");
			$(".stion").removeClass("stion");
			$(".m2").html(5);
			$(".m3").html(0);
			$(".m4").html(0);
			canpaly=canpaly1=canpaly2=canpaly3=canpaly4=unkclic=1;
			gamenum=0;
			$(".cartips").hide();
			$(".cartips div").hide();
			$(".gametiops div").hide();
			$(".gametiops").hide();
			$(".Zero").removeClass("Zerobg1");
			$(".Zero").removeClass("Zerobg2");
			$(".Zero").removeClass("Zerobg3");
			$(".Zero").removeClass("Zerobg4");
			$(".gamestart").show();
		},500);
	});
	//前往邀请函
	$(".gostart").click(function(){
		mpdunclick=1;
		$(".musiced").removeClass("hide");
		$(".audied")[0].play();
		$(".page3").show().css("-webkit-transform","translateY("+ah+"px)");
		setTimeout(function(){
			$(".page2").addClass("stion").css("-webkit-transform","translateY("+-ah+"px)");
			$(".page3").addClass("stion").css("-webkit-transform","translateY("+0+"px)");
			setTimeout(function(){
				$(".page2").removeClass("curt");
				$(".page3 .costion:first").addClass("curt");
				$(".stion").removeClass("stion");
				
				//$("body").on("touchstart");
			},500);
		},50);
	});
});
$("body").on("touchstart",function(){
	if(mpdunclick>0){
		mpdunclick=-1;
		$(".audied")[0].play();
	}
});
gamenum=0;
function gameadd(){
	if(gamenum<4){
		if(canpaly>0){
			if(unkclic>0){
				index=gamenum;
				gamenum=gamenum+1;
			}else{
				index=4;
			}
			$(".gametiops").show();
			$(".gametiops div").hide();
			$(".gamtps").eq(index).show();
			setTimeout(function(){
				$(".Zero").addClass("Zerobg"+gamenum);
			},100);
		}else{
			$(".gametiops").show();
			$(".gametiops div").hide();
			$(".over").show();
		}
	}else{
		$(".gametiops").show();
		$(".gametiops div").hide();
		$(".gamtps").eq(3).show();
	}
}

//倒计时;
//time_out();
function time_out(){
	m1=Number($(".m1").html());
	m2=Number($(".m2").html());
	m3=Number($(".m3").html());
	m4=Number($(".m4").html());
	if(m4>0){
		m4=m4-1;
		$(".m4").html(m4);
		pret=setTimeout(function(){
			time_out()
		},1000);
	}else if(m3>0){
		m3=m3-1;
		$(".m3").html(m3);
		$(".m4").html(9);
		pret=setTimeout(function(){
			time_out()
		},1000);
	}else if(m2>0){
		m2=m2-1;
		$(".m2").html(m2);
		$(".m3").html(5);
		$(".m4").html(9);
		pret=setTimeout(function(){
			time_out()
		},1000);
	}else{
		canpaly=-1;
		gameover();
	}
}
//游戏结束;
function gameover(){
	if(gamenum<4){
		$(".cartips").hide();
		$(".cartips div").hide();
		$(".gamtps").hide();
		$(".gametiops").show();
		$(".over").show();
	}
}
//
//滑屏
lock=false;
$(".bgfdf").on('touchstart', function(event){
	//event.preventDefault();
	  if(lock)return;
	  distance=0;
	  var target =$(".curt"),
		prev = target.prev('.costion'),
		next = target.next('.costion');
		target.removeClass('animation');
		 if(prev.length !== 0){
			prev.removeClass('animation').show().css('-webkit-transform','translate3d(0,'+(-prev.height())+'px,0)');
		}
		if(next.length !== 0){
			next.removeClass('animation').show().css('-webkit-transform','translate3d(0,'+(next.height())+'px,0)');
		}
		window.oldY = event.originalEvent.targetTouches[0].screenY;
})
.on('touchmove', function(event){
				event.preventDefault();
				if(lock)return;
				 var target =$(".curt"),
					prev = target.prev('.costion'),
					next = target.next('.costion'),
					height = prev.height() || next.height();
				var newY = event.originalEvent.targetTouches[0].screenY;
				var distance = window.distance = window.oldY - newY;
				var a = 1-Math.abs(distance*0.8)/height;
				if(prev.length == 0 && distance<0)return;
				if(next.length == 0 && distance>0)return;
				/*target.css('-webkit-transform', 'translateY('+-distance+'px)');
				target.css('z-index', 19);
				next.css("z-index",21);
				prev.css("z-index",21);
				next.css('-webkit-transform','translate3d(0,'+(height-distance)+'px,0)');
				prev.css('-webkit-transform','translate3d(0,'+(-height-distance)+'px,0)');*/
	})
.on('touchend', function(event){
	event.preventDefault();
	$(".costion").css("z-index","")
	if(lock)return;
	var target =$(".curt"),
		prev = target.prev('.costion'),
		next = target.next('.costion'),
		height = prev.height() || next.height();
	var distance = window.distance;
	if(distance > 0){
		if(next.length == 0)return;
		if(Math.abs(distance) > 60){
			lock=true;
			next.show().css('-webkit-transform','translate3d(0,'+(height)+'px,0)');
			setTimeout(function(){
				next.css("z-index",21);
				prev.css("z-index",21);
				prev.hide();
				setTimeout(function () {
					target.hide();
					$(".curt").removeClass("curt");
					next.addClass('curt');
					$(".costion").css("z-index","").hide();
					$(".curt").show();
					setTimeout(function(){lock=false;},300);
				},600);
				target.addClass('animation').css('-webkit-transform','translate3d(0,'+(-height)+'px,0)');
				next.addClass('animation').css('-webkit-transform','translate3d(0,0,0)');
			},30);
		} else {
			//lock=false;
			target.addClass('animation').css('-webkit-transform', 'none');
			next.addClass('animation').css('-webkit-transform','translate3d(0,'+(height)+'px,0)');
		}
	} else if(distance < 0){
		if(prev.length == 0)return;
		if(Math.abs(distance) > 60){
				prev.show().css('-webkit-transform','translate3d(0,'+(-height)+'px,0)');
				setTimeout(function(){
					lock=true;
					next.css("z-index",21);
					prev.css("z-index",21);
					next.hide();
					setTimeout(function () {
						target.hide();
						$(".curt").removeClass("curt");
						prev.addClass('curt');
						 $(".costion").css("z-index","").hide();
						$(".curt").show();
						setTimeout(function(){lock=false;},300);
					}, 600);
					target.addClass('animation').css('-webkit-transform','translate3d(0,'+(height)+'px,0)');
					prev.addClass('animation').css('-webkit-transform','translate3d(0,0,0)');
				},30);
		} else {
			//lock=false;
			target.addClass('animation').css('-webkit-transform', 'none');
			prev.addClass('animation').css('-webkit-transform','translate3d(0,'+(-height)+'px,0)');
		}
	}
  
});
//音乐事件；
$(".musiced").on("click",function(){
	mpdunclick=-1;
	if($(this).attr("class")=="musiced"){
		$(".msyuddtd").removeClass("kuddtz");
		$(this).addClass("mstop");
		$(".audied")[0].pause();
		$(".yifd").hide();
	}else{
		$(this).removeClass("mstop");
		$(".msyuddtd").addClass("kuddtz");
		$(".audied")[0].play();
		$(".yifd").show();
	}
});
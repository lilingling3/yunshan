// JavaScript Document
var iniCss = {
	height: document.documentElement.clientHeight,
	width: document.documentElement.clientWidth
}
$("html,body").css(iniCss);
$(".load").css(iniCss);
ah=$(window).height();
$(".tell_list").css("min-height",ah-100);
$("body").on("touchmove",function(){
	event.preventDefault();
});
var shake=autoplay=clickd=-1;
//clickd=1;
$(document).ready(function () {
	FastClick.attach(document.body);
	$(".butnd").click(function(){
		$(".page0").fadeOut(1000);
		$(".page1").fadeIn(1000);
		setTimeout(function(){
			$(".the_news").show();
			wxstart();
			$(".curt").removeClass("curt");
			$(".page").eq(1).addClass("curt");
		},1100);
	});
	$(".conliost li").click(function(){
		$(".chosder").removeClass("chosder");
		$(this).addClass("chosder");
	});
/*	$(".byiond span").click(function(){
		$(".page3").show().css("-webkit-transform","translateY("+ah+"px)");
		setTimeout(function(){
			$(".page2").addClass("stion").css("-webkit-transform","translateY("+-ah+"px)");
			$(".page3").addClass("stion").css("-webkit-transform","translateY("+0+"px)");
			setTimeout(function(){
				$(".page2").removeClass("curt").hide();
				$(".page3").addClass("curt");
				$(".stion").removeClass("stion");
			},500);
		},50);
	});*/

	$(".byiond .btn-cont .btn-nogo").click(function(){
		$(".dialogue").css("display","block");
		$(".byiondimg").hide();
		 $(".byiondimg1").show();
		$(".btn-cont").hide();
		$(".btn-cont1").show();
	});
	//买定离手;
	$(".but1").click(function(){
		if($(".chosder").size()>0){
			$(".page4").show().css("-webkit-transform","translateY("+ah+"px)");
			setTimeout(function(){
				$(".page3").addClass("stion").css("-webkit-transform","translateY("+-ah+"px)");
				$(".page4").addClass("stion").css("-webkit-transform","translateY("+0+"px)");
				setTimeout(function(){
					$(".page3").removeClass("curt").hide();
					$(".page4").addClass("curt");
					$(".stion").removeClass("stion");
				},500);
			},50);
		}else{
			alert("亲，请先点击押注");
		}
	});
	$(".but2").click(function(){
		mpdunclick=1;
		$(".musiced").removeClass("hide");
		$(".audied")[0].play();
		$(".page5").show().css("-webkit-transform","translateY("+ah+"px)");
		setTimeout(function(){
			$(".page4").addClass("stion").css("-webkit-transform","translateY("+-ah+"px)");
			$(".page5").addClass("stion").css("-webkit-transform","translateY("+0+"px)");
			setTimeout(function(){
				$(".page4").removeClass("curt");
				$(".page5 .costion:first").addClass("curt");
				$(".stion").removeClass("stion");

				//$("body").on("touchstart");
			},500);
		},50);
	});
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
//获取时间；
var d = new Date()
var vYear = d.getFullYear()
var vMon = d.getMonth() + 1
var vDay = d.getDate();
var h = d.getHours();
var m = d.getMinutes();
var se = d.getSeconds();
var day = d .getDay();
var week=new Array('星期日','星期一','星期二','星期三','星期四','星期五','星期六');
$(".week").html(week[day]);
$("#h").html(h);
$("#m").html(m);
if(h<10){
	$("#h").html("0"+h);
}
if(m<10){
	$("#m").html("0"+m);
}
$(".mon").html(vMon);
$(".data").html(vDay);
var i=1;
function wxstart(){
	glass1.play();
	if(i>0){
		i=i-1;
		$(".the_news li").eq(i).show().addClass("frst");
		$(".the_news li").eq(i).show().addClass("folde");
		setTimeout(function(){
			if(i>0){
				$(".the_news li").eq(i).removeClass("frst");
				wxstart();
			}else{
				//$(".m_audied").remove();
			}
		},400);
	}

}
//滑动解锁；
$(".page1").on('touchstart', function(event){
	event.preventDefault();
	distance=0;
	window.oldX = event.originalEvent.targetTouches[0].screenX;
	windtj=750;
});
$(".page1").on('touchmove', function(event){
	event.preventDefault();
	newX = event.originalEvent.targetTouches[0].screenX;
	var distance = window.distance = newX-window.oldX;
	adffg=-750+Number(distance)
	$(".page1").css({"-webkit-transform":"translateX("+distance+"px)"});
	$(".page2").css({"-webkit-transform":"translateX("+adffg+"px)"});
});
$(".page1").on('touchend', function(event){
	event.preventDefault();
	if(distance>160){
		$(".page1").addClass("stion").css({"-webkit-transform":"translateX("+750+"px)"});
		$(".page2").addClass("stion").css({"-webkit-transform":"translateX("+0+"px)"});
		setTimeout(function(){
			$(".page1").hide();
			setTimeout(function(){
				glass2.play();
				$(".tell_list li").eq(0).show().addClass("choude");
				setTimeout(function(){
					wxtell();
				},1000);
			},200);
		},400);
	}else{
		$(".page1").css({"-webkit-transform":"translateX("+0+"px)"});
		$(".page2").css({"-webkit-transform":"translateX("+-750+"px)"});
	}

});
size=$(".tell_list li").not(".dialogue").size();
function wxtell(){
	imndex=$(".choude").index();
	if(imndex<(size-1)){
		imndex=imndex+1;
		glass2.play();
		$(".choude").removeClass("choude");
		$(".tell_list li").eq(imndex).addClass("show").addClass("choude");
		setTimeout(function(){wxtell()},1500);
	}else{
		$(".byiond").show();
	}
}


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

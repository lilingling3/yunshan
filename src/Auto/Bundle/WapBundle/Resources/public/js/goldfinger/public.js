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
var shake=autoplay=clickd=-1;
//clickd=1;
$(document).ready(function () {
	FastClick.attach(document.body);
	$(".glass").click(function(){
		if(clickd>0){
			clickd=-1;
			glass.play();
			dglass();

		}
	});
	$(".startbuton").click(function(){
		scroolpage();
	});
	$(".follrtbuton a").click(function(){
		nm=$(this).index();
		$(".tit4 span").eq(nm).css("display","block");
		$(".but_subit").attr("data-num",nm);
		setTimeout(function(){scroolpage();},20);
		setTimeout(function(){
			$("iframe").remove();
		},300);
	});
	var phonenum = /^1([38]\d|4[57]|5[0-35-9]|7[06-8]|8[89])\d{8}$/;
	$(".but_subit").on("touchstart",function(){
		if($(".inputd input").val()!=""){
			if(!phonenum.test($(".inputd input").val())){
				alert("手机号码不正确");
			}else{

				$.post("/api/coupon/get",
					{
						mobile:$(".inputd input").val(),
						couponActivityID:5700
					},
					function(data,status){
						if(status){
							if(data.errorCode==0){
								$(".table").addClass("table_out");
								$(".last_page").show();
								setTimeout(function(){
									$(".last_page").addClass("last_paged");
								},1000);

							}
							else  if(data.errorCode=='-70005'){
								alert("该手机号码已经领取过优惠券");
							}
							else{

								alert(data.errorMessage);
								location.reload();
							}

						}

					});

			}
		}else{
			alert("请您填写手机号")
		}
	});
	$(".share").click(function(){
		$(".sharbg").fadeIn(1000);
	});
	$(".sharbg").click(function(){
		$(".sharbg").fadeOut(500);
	});
});
//音乐事件；
$(".musiced").on("click",function(){
	unclick=-1;
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
//玻璃破裂;
function dglass(){
	if($(".choshow").size()>0){
		index=$(".choshow").index();
		if(index<$(".galslit li").size()-1){
			index=index+1;
			$(".choshow").removeClass("choshow");
			$(".galslit li").eq(index).addClass("choshow");
			if(autoplay>0){
				setTimeout(function(){
					dglass();
				},100);
			}
		}else{
			//$(".glsas").css("opacity",1);
			$(".glass").fadeOut(500);
			setTimeout(function(){
				//$(".startbuton").addClass("start_am");
			},500);
		}
	}else{
		$(".galslit li").eq(0).addClass("choshow");
	}
}
//竖屏滚动;
ah=$(window).height();
function scroolpage(){
	numd=$(".curt").index();
	numd=numd+1;
	$(".page").eq(numd).show().css("-webkit-transform","translateY("+ah+"px)");
	setTimeout(function(){
		$(".curt").addClass("stion").css("-webkit-transform","translateY("+-(ah)+"px)");
		$(".page").eq(numd).addClass("stion").css("-webkit-transform","translateY("+(0)+"px)");
		setTimeout(function(){
			$(".curt").removeClass("curt").hide();
			$(".page").eq(numd).addClass("curt");
		},320);
	},50);
}
//摇一摇;
opd=1;
$(document).ready(function(e) {
	if(window.DeviceMotionEvent) {
		var speed =25;
		var x = y = z = lastX = lastY = lastZ = 0;
		var handler = function(){
			var acceleration =event.accelerationIncludingGravity;
			x = acceleration.x;
			y = acceleration.y;
			if(Math.abs(x-lastX) > speed || Math.abs(y-lastY) > speed) {
				if(shake>0){
					shake=-1;

					if($(".choshow").size()<1){
						//glass1.play();
						$(".glass1")[0].play();
					}else if($(".choshow").index()==0){
						//glass2.play();
						$(".glass2")[0].play();
					}else if($(".choshow").index()==1){
						//glass3.play();
						$(".glass3")[0].play();
					}
					setTimeout(function(){
						dglass();
						opd=opd-0.1;
						$(".glsas").css("opacity",opd);
						setTimeout(function(){
							num=$(".choshow").index()+1;
							if(num<3){
								setTimeout(function(){
									shake=1;
								},300);
							}else{
								$(".tit1").addClass("titleOut");
								$(".tit2").addClass("titleIn");
								autoplay=1;
								clickd=1;
								$(".startbuton").addClass("start_am");
							}
						},20);
					},100);
				}
			}
			lastX = x;
			lastY = y;
		};
		window.addEventListener('devicemotion',handler, false);

	}
});

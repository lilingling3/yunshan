$(".head-icon img").animate({width:"126px", opacity: 'show'},1000,function(){
	$(this).animate({width:"106px"},200)
});
$(".text-1").animate({opacity:1},1500);
var text1_w=$(".text-cont1").width();
$(".text-cont1").css("margin-left",-text1_w/2);
$(".text-cont1").animate({marginTop:"0px",opacity:1},1000);
$(window).resize(function(){
		var text1_w=$(".text-cont1").width();
$(".text-cont1").css("margin-left",-text1_w/2);
$(".text-cont1").animate({marginTop:"0px",opacity:1},1000);
});


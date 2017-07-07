
$(function(){
    $('.lr-center').click(function(){
        $(".page1").css("display","none");
        $(".page2").css("display","block");
        window.scrollTo(0,0);
    })
    $(".btn_con img:nth-child(4)").click(function(){
        $(".page2").css("display","none");
        $(".page3").css("display","block");
        window.scrollTo(0,0);
    });
    $(".btn_con img:nth-child(4)").siblings().click(function(){
        $(".page2").css("display","none");
        $(".page4").css("display","block");
        window.scrollTo(0,0);
    });
})
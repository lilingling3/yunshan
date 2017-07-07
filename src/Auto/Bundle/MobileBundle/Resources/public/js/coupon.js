
$("#page.coupon.useable .coupon-code").delegate(".button","click",function(){
    var code=$("#page.coupon.useable .coupon-code .code").val();
    code=code.trim();
    if(code==""){
        alert("请输入兑换码！");
    }
    else{
        $("#page.coupon.useable .coupon-code .code-inner form").submit();
    }
});




var useablepage=unuseablepage=2;
var couponbg={
    0:"/bundles/autowap/images/2.0-coupon-bg.png",
    1:"/bundles/autowap/images/2.0-outdated.png"
}

$("#page.coupon.unuseable").delegate(".morecouponbtn","click",function(){

    var member=$(this).attr("user");

    $.post("/api/coupon/unusable/list",
        {
            userID:member,
            page:unuseablepage
        },
        function(data,status){
            if(status){
                if(data.errorCode==0){

                    addcoupons(data["coupons"],1);

                    if(unuseablepage>=data["pageCount"]){
                        $(".morecouponbtn").addClass("hide");
                    }
                    unuseablepage++;
                }else{
                    alert(data.errorMessage);
                }
            }
        });
});


$("#page.coupon.useable").delegate(".morecouponbtn","click",function(){

    var member=$(this).attr("user");
    console.log("member:::"+member)
    $.post("/api/coupon/usable/list",
        {
            userID:member,
            page:useablepage
        },
        function(data,status){
            if(status){
                if(data.errorCode==0){

                    addcoupons(data["coupons"],0);
                    console.log(data);
                    if(useablepage>=data["pageCount"]){
                        $(".morecouponbtn").addClass("hide");
                    }
                    useablepage++;
                }else{
                    alert(data.errorMessage);
                }
            }
        });
});


function addcoupons(coupons,flag){
    var list=(".list");

    for(var i=0;i<coupons.length;i++){
        var coupondom=createcoupon(coupons[i],flag);
        $(coupondom).appendTo($(list));
    }
}


function createcoupon(coupon,flag){
    var section;
    if(flag==0){
        section = $("<section class='avaiable'></section>");
    }
    else {
        section = $("<section class='unavaiable'></section>");
    }

    var row=$("<div class='row'></div>");
    $(row).appendTo($(section));

    var coupon_cont=$("<div class='coupon-cont'></div>");
    var incont=$("<div class='incont'></div>");
    $(coupon_cont).appendTo(row);
    $(incont).appendTo($(coupon_cont));

    var couponstr="<span class='amount'>"+coupon.amount+"</span>";
    $(incont).html(couponstr);

    var coup_text=$("<div class='coup-text'></div>");
    $(coup_text).appendTo($(row));

    var p1=$("<p>"+coupon.name+"</p>");
    $(p1).appendTo($(coup_text));

    var p2=$("<p class='date'></p>");
    $(p2).appendTo($(coup_text));

    var p2str="有效期至"+coupon.endTime;
    var statusstr="<p>状态</p>";
    if(coupon.valid ==401){
        p2str+="[已使用]";
    }
    else if(coupon.valid ==402){
        p2str+="[已过期]";
    }
    $(p2).html(p2str);

    var p3=$("<p class='needhour'></p>");
    $(p3).appendTo($(coup_text));
    var p3str="";
    if(coupon.needHour ==0){
        p3str="无使用条件";
    }
    else {
        p3str="条件:满"+coupon.needHour+"小时 | "+coupon.needAmount+"元以上 | "+coupon.carLevel;
    }

    $(p3).html(p3str);

    return section;

}
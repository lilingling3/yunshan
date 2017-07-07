$("#admin.rechargeActivity.list").delegate(".delete", "click", function(e){
    e.preventDefault();
    $(".apply-ok").show();
});

$("#admin.rechargeActivity.list").delegate(".refund-cancel", "click", function(){
    $(".apply-ok").css("display", "none");
});

$("#admin.rechargeActivity.list").delegate(".submit-do", "click", function(){
    $(".apply-ok").css("display", "none");

});
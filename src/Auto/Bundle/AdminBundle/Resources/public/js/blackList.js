$(function(){
    $(".black_button_submit").on("click",function(){
        if($('#form_limit option:selected').val()==0){
            alert("请选择拉黑时限");
            return false;
        }else if($('#form_blacklist_reason option:selected').val()==0){
            alert("请选择拉黑种类");
            return false;
        }
    })
})
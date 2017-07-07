$("#page.app.show").delegate(".addexplain","click",function(){
    var num=$(this).attr("explainnum");
    num++;
    $(this).attr("explainnum",num)
    var str='<div class="menu-li">\
        <label for="color-input">'+num+':</label>\
    <span class="text-r">\
        <input type="text" class="explain" name="explain" placeholder="explain" />\
        </span>\
        </div>';
console.log($(str));
    $(".explaincont").append($(str));

});

console.log($("#page.app.show .verify-data"));
$("#page.app.show .verify-data").delegate(".submit-data","click",function(e){
    e.preventDefault();
    var explains=$(".explain");
    var temp={};
    for(var i=0,str;str=explains[i++];){
        temp[i-1]=$(str).val();
    }
    $(".explainjson").val(JSON.stringify(temp));
    $("form").submit();
});
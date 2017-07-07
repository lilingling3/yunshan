var partner_edit_location_ref=/^\/admin\/partner\/edit/;
var    partnerOperateCarsIdLsjson = {},partnerOperaterIdLsjson = {};
if(partner_edit_location_ref.test(window.location.pathname)){
      $(function(){

          if($(".operaterID").val()){
              var poids = $(".operaterID").val().split(",");
              for(var i= 0;i<poids.length;i++){
                  var span = $('<span class="ui twitter  button"><b>'+poids[i]+'</b><i class="close">&Chi;</i></span>');
                  partnerOperaterIdLsjson[poids[i]] = true;
                  $(".operaterIDCont").append($(span));
              }
          }
               console.log($("#operateCarsIdLs").val());



           if( $(".operateCars").val()){
               var pocars = $(".operateCarsCont b");
               for(var i= 0;i<pocars.length;i++){
                   partnerOperateCarsIdLsjson[$(pocars[i]).text()] = true;
               }
           }

      })
}
$("#admin.partner .fields").delegate(".close","click",function(){
    var temp = $(this).parent("span").text();
    if($(this).siblings("b").attr("carid")){
        delete partnerOperateCarsIdLsjson[temp];
    }
    else {
        delete partnerOperaterIdLsjson[temp];
    }
    $(this).parent("span").remove();
});
$("#admin.partner .fields").delegate(".addoperaterID","click",function(){
    if($("#operaterIDinput").val()== ""){
      alert("请填入操作人员id!");
        return ;
    }
    var  operaterIDinput = $("#operaterIDinput").val();
    if(partnerOperaterIdLsjson[operaterIDinput]){
        alert("已经存在此操作人id!");
        return ;
    }
    var span = $('<span class="ui twitter  button"><b>'+$("#operaterIDinput").val()+'</b><i class="close">&Chi;</i></span>');
    partnerOperaterIdLsjson[operaterIDinput] = true;
    $(".operaterIDCont").append($(span));
    $("#operaterIDinput").val("");


});
$("#admin.partner .fields").delegate(".addoperateCars","click",function(){
    if($("#operateCarInput").val()== ""){
        alert("操作车辆");
        return ;
    }
    var car = $("#operateCarInput").val();
    if(partnerOperateCarsIdLsjson[car]){
        alert("已经存在此车牌号!!");
        return ;
    }
    $(".layer").removeClass("hide");
    $("#page.order.show .shadow").show();

    $.post("/admin/rentalcar/carId",
        {
            plate:car
        },
        function(data,status){
            console.log(status);
            if(status){
                if (data.errorCode == 0 ) {
                    var span = $('<span class="ui twitter  button"><b carid="'+data["id"]+'">'+car+'</b><i class="close">&Chi;</i></span>');
                    $(".operateCarsCont").append($(span));
                    $(".layer").addClass("hide");
                    $("#operateCarInput").val("")

                    partnerOperateCarsIdLsjson[car] = true;
                }
                else{
                    alert(data.errorMessage);
                    $(".layer").addClass("hide");
                }
            }
            else {
                alert("请求失败");
                $(".layer").addClass("hide");
            }
        });
});

$("#admin.partner").delegate(".butnsubmit","click",function(e){
    e.preventDefault();
    var reg = /^[1-9][0-9]*$/;
    if($(".partnername").val() == ""){
        alert("填写名字");
        return false;
    }
    var num = $(".operatenum").val();
    if(!reg.test(num)){
      alert("每小时操作限制次数需为整数");
        return false;
    }
   var oids = $(".operaterIDCont span b");
    var ids = [];
    if($(oids).length == 0){
        alert("添加操作人员ID");
        return false;
    }
    for(var i= 0; i<$(oids).length;i++){
        ids.push($(oids[i]).text());
    }
        $(".operaterID").val(ids);

    var ocars = $(".operateCarsCont span b");
    var cars = [];
    if($(ocars).length == 0){
        alert("添加操作车辆");
        return false;
    }
    for(var i= 0; i<$(ocars).length;i++){
        cars.push($(ocars[i]).attr("carid"));
    }
    $(".operateCars").val(cars);
    $("form").submit();

});
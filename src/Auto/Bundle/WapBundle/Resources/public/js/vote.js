$("#page.vote").delegate(".add-vote","click",function(){
    var option = $(this).attr('option-data');
    var vote = $("#vote-id").val();
    var count = $(".option"+option).text();
    var user = $("#vote-user").val();
    $.post("/m/vote/index",
        {

            option:option,
            vote:vote,
            user:user
        },
        function(data,status){
            if(status){
                if(data.errorCode==0){
                    $(".option"+option).text(parseInt(count)+1);
                    alert('投票成功');
                }else{
                    alert(data.errorMessage)
                }

            }

        });
});
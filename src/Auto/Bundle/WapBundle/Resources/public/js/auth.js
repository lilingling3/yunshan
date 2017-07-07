$("#page.account.auth").delegate(".auth-file .upload","change",function(){
    var objUrl = getObjectURL(this.files[0]) ;
    if(this.files[0].size>8000000){
        alert('图片太大,请控制在2M之内!');
        return false;
    }

    if (objUrl) {

        $($(this).attr("img-data")+" img").attr("src", objUrl) ;
    }

});

$("#page.account.auth").delegate(".upload-submit","click",function(){
    var s = true;
    $.each($('.auth-file .upload'), function(i, item){
        if(!item.value){
            alert('请上传照片');
            s = false;
            return false;
        }

    });


        if(s) $(this).parents('form').submit();

});



function getObjectURL(file) {
    var url = null ;
    if (window.createObjectURL!=undefined) { // basic
        url = window.createObjectURL(file) ;
    } else if (window.URL!=undefined) { // mozilla(firefox)
        url = window.URL.createObjectURL(file) ;
    } else if (window.webkitURL!=undefined) { // webkit or chrome
        url = window.webkitURL.createObjectURL(file) ;
    }
    return url ;
}
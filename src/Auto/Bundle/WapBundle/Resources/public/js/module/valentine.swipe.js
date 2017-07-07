window.onload=function(){
    //返回角度
    function GetSlideAngle(dx, dy) {
        return Math.atan2(dy, dx) * 180 / Math.PI;//返回角度
    }

    //根据起点和终点返回方向 1：向上，2：向下，3：向左，4：向右,0：未滑动
    function GetSlideDirection(startX, startY, endX, endY) {
        var dy = startY - endY;
        var dx = endX - startX;
        var result = 0;

        //如果滑动距离太短
        if(Math.abs(dx) < 2 && Math.abs(dy) < 2) {
            return result;
        }

        var angle = GetSlideAngle(dx, dy);
        if(angle >= -45 && angle < 45) {//向右
            result = 4;
        }else if (angle >= 45 && angle < 135) {
            result = 1;//向上
        }else if (angle >= -135 && angle < -45) {
            result = 2;//向下
        }
        else if ((angle >= 135 && angle <= 180) || (angle >= -180 && angle < -135)) {
            result = 3;//向左
        }

        return result;
    }

    //滑动处理
    var startX, startY;
    var pagecont=document.getElementById("pagescont");
    pagecont.addEventListener('touchstart',function (ev) {
        var obj = ev.srcElement ? ev.srcElement : ev.target;
        if(obj.tagName.toLocaleLowerCase()!="input"){
            $("input").trigger("blur");
            ev.preventDefault();
        }

        startX = ev.touches[0].pageX;
        startY = ev.touches[0].pageY;
    }, false);
    pagecont.addEventListener('touchend',function (ev) {
        var obj = ev.srcElement ? ev.srcElement : ev.target;
        if(obj.tagName.toLocaleLowerCase()!="input"){
            $("input").blur();
            ev.preventDefault();
        }

        var endX, endY;
        endX = ev.changedTouches[0].pageX;
        endY = ev.changedTouches[0].pageY;
        var direction = GetSlideDirection(startX, startY, endX, endY);
        switch(direction) {
            case 0:
                break;
            case 1:
                slipeup(direction,ev.changedTouches[0].target);
                break;
            case 2:
                slipedown(direction,ev.changedTouches[0].target);
                break;
            case 3:
                break;
            case 4:
                break;
            default:
        }
    }, false);

    var width,height;
    width=screen.width;
    height=screen.height;
    function setWidthH(){

        var ele_page=document.getElementsByClassName("page");

        for(var i=0;i<ele_page.length;i++){
            ele_page[i].style.width=width;
            ele_page[i].style.height=height;
        }

    }
    var ele_page=document.getElementsByClassName("page");
    setWidthH();
    var slipeHh=$(document).height() < $('body').height() ? $(document).height() : $('body').height(),pageindex=0,oSpeed=30,transformflag=false,slipeflag=1;
    $(".page").css("height",slipeHh);
    $(".pagescont").css("height",slipeHh*2);
    function slipeup(direction,ev){
        //向下滑动
        if(direction==1){
            if(pageindex<2&&(slipeflag==1||transformflag)){
                /*$(".pagescont").animate({top:-slipeHh},oSpeed,"swing");*/
                var pagecont=document.getElementById("pagescont");
                pagecont.style.webkitTransform = 'translate3d(0px, -'+slipeHh+'px, 0px)';
                pagecont.style.webkitTransitionDuration = '300ms';
                transformflag=false;
                setTimeout(function(){
                    transformflag=true;
                },'300ms');
            }
        }



    }

    function slipedown(direction,ev){
        if(direction==2){//向上滑动
            if(pageindex<2 && transformflag ){
                var pagecont=document.getElementById("pagescont");
                pagecont.style.webkitTransform = 'translate3d(0px, 0px, 0px)';
                pagecont.style.webkitTransitionDuration = '300ms';
                transformflag=false;
                setTimeout(function(){
                    transformflag=true;
                },'300ms');
            }
        }

    }

}


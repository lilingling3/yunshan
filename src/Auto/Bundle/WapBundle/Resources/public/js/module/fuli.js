
window.onload=function(){
    alert("qqqqqqqqqqqqqqqqqqqq");
    var fuli=document.getElementById("fuli-button");
    console.log(fuli);
    fuli.onclick=function(){
        alert("aaaaaaaaaaaaa");
        var login=document.getElementById("fuli-login");
        var suss_cont=document.getElementById("fuli-suss-cont");
        var mobile =document.getElementById("phone").value;
        var workcard =document.getElementById("workcard").value;
        var name =document.getElementById("name").value;
        var re = /^\d{6}$/;
        var mobile_re = /^1[3|4|5|7|8]\d{9}$/;
        if(name==""){
            alert('请输入姓名');
            return false;
        }
        if(mobile==""){
            alert('请输入手机号');
            return false;
        }
        if(!mobile_re.test(mobile)){
            alert('手机号错误!');
            return false;
        }

        if(workcard==""){
            alert('请输入员工号');
            return false;
        }
        if (!re.test(workcard)) {
            alert('员工号错误！');
            return false;
        }
        var error_m={
            "-70001":"无效优惠券",
            "-70003":"该兑换码已被使用",
            "-70004":"已过期",
            "-70005":"优惠券已被领取过",
            "-70006":"无效优惠活动",
            "-70007":"优惠券已领完",
            "-70008":"无权领取"
    };

       jsAjax({
            url: "/api/coupon/get",              //请求地址
            type: "POST",                       //请求方式
            data: {  //请求参数
                mobile:mobile,
                couponActivityID:92
            },
            dataType: "json",
            success: function (data,status) {

                data=jsonData(data);

                if(data.errorCode==0){
                    login.style.display="none";
                    suss_cont.style.display="block";
                }else{
                    alert(error_m[data.errorCode]);
                }
            },
            fail: function (status) {
                alert(data.errorMessage);
            }
        });
    }
}



function jsonData(data) {
    var jsons = new Object();
    var str = data.substring(1,data.length-1);
    str=str.replace(/\"/g, "");
    str=str.replace(/\'/g, "");
    var  strs = str.split(",");
    for(var i = 0; i < strs.length; i ++) {
        jsons[strs[i].split(":")[0]]=(strs[i].split(":")[1]);
    }
    return jsons;
}






function jsAjax(options) {
    options = options || {};

    options.type = (options.type || "GET").toUpperCase();
    options.dataType = options.dataType || "json";
    var params = formatParams(options.data);
    var xhr;
    //创建 - 非IE6 - 第一步
    if (window.XMLHttpRequest) {
        xhr = new XMLHttpRequest();
    } else { //IE6及其以下版本浏览器
        xhr = new ActiveXObject('Microsoft.XMLHTTP');
    }

    //接收 - 第三步
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4) {
            var status = xhr.status;
            if (status >= 200 && status < 300) {
                options.success && options.success(xhr.responseText, xhr.responseXML);
            } else {
                options.fail && options.fail(status);
            }
        }
    }

    //连接 和 发送 - 第二步
    if (options.type == "GET") {
        xhr.open("GET", options.url + "?" + params, true);
        xhr.send(null);
    } else if (options.type == "POST") {
        xhr.open("POST", options.url, true);
        //设置表单提交时的内容类型
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send(params);
    }
}
//格式化参数
function formatParams(data) {
    var arr = [];
    for (var name in data) {
        arr.push(encodeURIComponent(name) + "=" + encodeURIComponent(data[name]));
    }
    arr.push(("v=" + Math.random()).replace(".",""));
    return arr.join("&");
}
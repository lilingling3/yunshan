// 页面加载完成的时间戳
var end_time = (new Date()).getTime();
// 定时器
var timer ;
// 开关 判断是连续还是暂停
var flag = true;
var TIME = 30000;
$('#cache_id').val(end_time);

$('#map-div').hide();
//setMap(113.371,23.05);
$('.top select,input').focus(function () {
    $(this).css({
        'outline':'1px solid #58b4db'
    })
}).blur(function () {
    $(this).css({
        'outline':'none'
    })
});

$('.top input').focus(function () {
    $(this).val('')
});
// 单次获取
$('#singleBtn').click(function () {
    $('#map-div').hide();
    if(!flag){
        $('#repeatBtn').html('连续获取').removeClass('btn-danger').addClass('btn-info');
        clearInterval(timer);
        flag = true;
    };
    singleBtn()
});

// 单次获取
function singleBtn() {
    getCarInfo(function(car_id) {
        $('#car_type').html('单次获取');
        var dateNow = dateFormat("hh:mm:ss");
        var cache_id = $('#cache_id').val();
        console.log('car_id: '+ car_id);
        console.log('cache_id: '+ cache_id);
        getRunningInfo(car_id, dateNow,cache_id, setRunningInfo,null);
    });
}

$('#repeatBtn').click(function(){
    repeatBtn()
});
// 连续获取
function repeatBtn() {
    if (flag) {
        $('#map-div').hide();
        flag = false;
        $('#repeatBtn').html('暂停获取').removeClass('btn-info').addClass('btn-danger');
        getCarInfo(function(car_id) {
            $('#car_type').html('连续获取');
            var dateNow = dateFormat("hh:mm:ss");
            var cache_id = $('#cache_id').val();
            timer = setInterval(function() {
                getRunningInfo(car_id, dateNow,cache_id, repeatCallback,null);
            }, TIME);

        });
    } else {
        stopBtn();
    }
}
// 暂停获取
function stopBtn() {
    clearInterval(timer);
    $('#repeatBtn').html('连续获取').removeClass('btn-danger').addClass('btn-info');
    flag = true;
}
function repeatCallback(data) {
    setHistory();
    setRunningInfo(data);
}

// 连续获取 显示历史
function setHistory() {
    var car_id = $('#car_id').val();
    var dateTime = dateFormat("hh:mm:ss");

    var $history= $('#history');

    var links = '';
    links +=   '<span  onclick="getRunningInfoByTime(\'' + car_id + '\',\''+ dateTime+ '\',this)">' + dateTime + '</span>';
    var length = $history.children('span').length;

    if (length >= 10) {
        $history.children('span').first().remove();
    }
    $history.append(links);

    $('#history span').click(function(){
        $(this).addClass('spanClick');
        $(this).siblings().removeClass('spanClick');
    })

}

// 时间btn 获取数据
function getRunningInfoByTime(car_id,dateTime,span) {
    console.log('span car_id' + car_id);
    var cache_id = $('#cache_id').val();
    console.log(span)
    console.log(span.innerHTML);
    // 先暂停获取
    stopBtn();
    // 再发送请求
    getRunningInfo(car_id, dateTime,cache_id,setRunningInfo,span);
}

// 获取基本信息
function getCarInfo(callback) {
    var dateNow = dateFormat("hh:mm:ss");
    var dateNowControl = dateFormat("yyyy/MM/dd hh:mm:ss");

    var plate_id = $('#plate_id').val();
    var plate_number = $('#plate_number').val();
    var company_id = $('#company_id').val();
    var device_number = $('#device_number').val();

    $.ajax({
        type: "post",
        url: '/admin/rentalcar/carBaseInfo',
        data: {
            plate_id: plate_id,
            plate_number: plate_number,
            company_id: company_id,
            device_number: device_number
        },
        dataType: "json",
        success: function(data) {
            if (data.errorCode == 0) {
                if(data.images == ''){
                    $('.car-img img').attr('src','/bundles/autoadmin/images/activity_car_info_car_up.png');
                }else {
                    $('.car-img img').attr('src',data.images)
                }

                // 车辆基本信息
                $('#car_brand').html(data.plate_place + "" + data.plate_number);

                $('#car_model').html(data.models);

                if (data.order_status == 300) {
                    $('#car_status').html('未出租');
                } else if (data.order_status == 301) {
                    $('#car_status').html('已出租');
                } else {
                    $('#car_status').html('未知').addClass('red');
                }

                $('#car_device').html(data.device);

                $('#car_deviceID').html(data.device_number);

                // 保存car_id
                $('#car_id').val(data.car_id);
                // 车辆控制初始化
                initCarControl(data.operate_fields);
                // 数据读取初始化
                initRunningField(data.running_fields);
                callback(data.car_id);
            } else {
                alert(data.errorMessage)

            }
        }
    });
}

// 车辆控制
function getCarControl(type) {

    var dateNow = dateFormat("hh:mm:ss");

    var car_id = $('#car_id').val();

    $.ajax({
        type: "post",
        url: '/admin/rentalcar/carControl',
        data: {
            car_id: car_id,
            operate: type
        },
        dataType: "json",
        success: function(data) {
            if (data.errorCode == 0) {
                var timeStart = timestamp(new Date(data.timeStart));
                var timeEnd = timestamp(new Date(data.timeEnd));

                var backTime =parseInt((data.timeEnd - data.timeStart)/1000);
                $('#car_control_' + type + '_time').html(timeStart);
                $('#car_control_' + type + '_back').html(backTime+'秒');

                if (data.data) {
                    $('#car_control_' + type + '_result').html('成功')
                } else {
                    $('#car_control_' + type + '_result').html('失败')
                }
            } else {
                console.log("获取失败");
            }
        }
    })

}

// 数据读取
function getRunningInfo(car_id, dateTime,cache_id, callback,isSpan) {
    $.ajax({
        type: "post",
        url: '/admin/rentalcar/carRunningInfo',
        data: {
            car_id: car_id,
            time: dateTime,
            cache_id:cache_id
        },
        dataType: "json",
        success: function(data) {
            callback(data,isSpan);
        }
    });
};

// 读取数据成功后的回调
function setRunningInfo(data,isSpan) {
    //console.log(isSpan.text());
    var result = data.data;
    if (data.errorCode == 0) {
        var lng = result.longitude;
        var lat = result.latitude;
        if(lng && lat){
            $('#map-div').show();
            setMap(lng,lat);
        }
        $('#location').html(lng + ',' + lat);
        $('#elevation').html(result.elevation);
        $('#speed').html(result.speed);
        $('#direction').html(result.direction);
        $('#distance').html(result.distance);
        $('#surplusDistance').html(result.surplusDistance);
        $('#surplusPercent').html(result.surplusPercent);
        $('#light').html(result.light);
        $('#door').html(result.door);
        $('#voltage').html(result.voltage);
        $('#acc').html(result.acc);
        $('#signal').html(result.signal);
        $('#status').html(result.status);
    } else if(data.errorCode == -1){
        console.log(isSpan.innerHTML)
        isSpan.style.color = 'red';
        alert(data.errorMessage);
    }
}
// 时间戳格式化
function timestamp(date){
    Y = date.getFullYear() + '/';
    M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1) + '/';
    D = date.getDate() + ' ';
    h = date.getHours() + ':';
    m = date.getMinutes() + ':';
    s = date.getSeconds();
    return Y+M+D+h+m+s
}
// 一般日期格式化
function dateFormat(dateFormatStr) {

    Date.prototype.Format = function(fmt) { //author: meizz
        var o = {
            "M+": this.getMonth() + 1, //月份
            "d+": this.getDate(), //日
            "h+": this.getHours(), //小时
            "m+": this.getMinutes(), //分
            "s+": this.getSeconds(), //秒
            "q+": Math.floor((this.getMonth() + 3) / 3), //季度
            "S": this.getMilliseconds() //毫秒
        };

        if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
        for (var k in o)
            if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
        return fmt;
    };

    return new Date().Format(dateFormatStr);
}

// 初始化车辆控制
function initCarControl(control_fields) {
    for (var k in control_fields) {
        if (control_fields[k] == 0) {
            $('#car_control_' + k).html('未提供').addClass('disabled');

        } else if (control_fields[k] == 1) {
            var dataValue = $('#car_control_' + k).attr('data-value');
            $('#car_control_' + k).html(dataValue);
        }
    }
}

// 初始化数据读取
function initRunningField(running_fields){
    for (var k in running_fields){
        if(running_fields[k] == 0){
            $('#' + k).html('未知').addClass('yellow');
        }else if(running_fields[k] == 1){
            $('#' + k).html('--')
        }
    }
}

// 地图
function setMap(lng,lat){
    var map = new AMap.Map('mapContainer', {
        // 设置中心点
        center:[lng,lat],
        // 设置缩放级别
        zoom: 16
    });
    //地图中添加地图操作ToolBar插件
    map.plugin(["AMap.ToolBar"],function() {
        var toolBar = new AMap.ToolBar();
        map.addControl(toolBar);
    });
    var markers = [];
    addMarker(lng,lat);
    function addMarker(lng,lat){
        marker = new AMap.Marker({
            icon:"/bundles/automobile/images/activity_car_info_map-icon.png",
            position:new AMap.LngLat(lng,lat)
        });
        marker.setMap(map);  //在地图上添加点
        markers.push(marker);
    }

}
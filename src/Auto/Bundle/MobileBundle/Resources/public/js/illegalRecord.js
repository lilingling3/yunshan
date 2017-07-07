
if(window.location.pathname=="/mobile/illegalRecord") {
    var illegalrecordpage = 2;
    $("#page.illegalRecord.list").delegate(".morebtn", "click", function () {

        var member = $(this).attr("user");

        $.post("/api/illegalRecord/list",
            {
                userID: member,
                page: illegalrecordpage
            },
            function (data, status) {
                if (status) {
                    if (data.errorCode == 0) {

                        addillegals(data["illegalRecords"]);

                        if (illegalrecordpage >= data["pageCount"]) {
                            $(".morebtn").addClass("hide");
                        }
                        illegalrecordpage++;
                    } else {
                        alert(data.errorMessage);
                    }
                }
            });
    });


    function addillegals(illegalrecords) {
        var list = (".rules_cont");

        for (var i = 0; i < illegalrecords.length; i++) {
            var illegaldom = createillegalrecord(illegalrecords[i]);
            $(illegaldom).appendTo($(list));
        }
    }


    function createillegalrecord(illegal, flag) {

        var a = $("<a href='/mobile/illegalRecord/show/" + illegal['illegalRecordID'] + "'></a>");
        var order_li = $("<div class='rules_li'></div>");
        $(order_li).appendTo($(a));
        var row = $("<div class='row row-top'></div>");
        $(row).appendTo($(order_li));
        var p = $("<p class='b'></p>");
        $(p).appendTo($(row));
        var bstr = illegal.createTime;
        if (!illegal.handleTime) {
            $(order_li).addClass("unhandle");
            bstr += "<span class='status-unhandle'><span class='triangle'></span>未处理</span>";
            $(row1).addClass("row-top");
        }
        else {
            bstr += "<span class='status-handle'>已处理</span>";
        }
        $(p).html(bstr);


        var row1 = $("<div class='row'></div>");
        $(row1).appendTo($(order_li));

        var column = $("<div class='column'></div>");
        $(column).appendTo($(row1));

        var item1 = $("<div class='item'></div>");
        var item1str = "<p>车牌</p>" + illegal.rentalCar.license;
        $(item1).html(item1str);
        $(item1).appendTo($(column));

        var item2 = $("<div class='item'></div>");
        var item2str = "<p>车型</p>" + illegal.rentalCar.license;
        $(item2).html(item2str);
        $(item2).appendTo($(column));

        var item3 = $("<div class='item'></div>");
        var item3str = "<p>积分/罚款</p>";
        if (illegal.illegalScore) {
            item3str += '<span class="color-red" >' + illegal.illegalScore + '</span>分&nbsp';
        }
        else {
            item3str += '<span class="color-red" >0</span>分&nbsp';
        }
        if (illegal.illegalAmount) {
            item3str += '<span class="color-red" >' + illegal.illegalAmount + '</span>分&nbsp';
        }
        else {
            item3str += '<span class="color-red" >0</span>分&nbsp';
        }

        $(item3).html(item3str);
        $(item3).appendTo($(column));


        return a;

    }
}
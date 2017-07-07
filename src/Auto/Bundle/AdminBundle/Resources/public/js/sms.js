YUI().use('node', 'event', 'anim', function(Y){

    Y.on('keyup', function (e) {

        Y.one('#word-count').set('text',this.get('value').length);

    }, '#admin.sms.send,#admin.sms.sendAll #form_content');

    Y.on('click', function (e) {
        e.preventDefault();

        alert('睁大眼!!!这是群发!');

        var r=confirm("确定给全部用户发送短信?");
        if (r==true){

            this.ancestor('form').submit();
        }

    }, '#admin.sms.sendAll button');

});

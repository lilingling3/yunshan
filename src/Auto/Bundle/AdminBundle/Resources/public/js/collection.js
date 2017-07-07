YUI().use('node', 'event', 'handlebars', 'mojomaja-photograph','austra-seed', 'sortable', function (Y) {
    Y.delegate('click', function (e) {
	e.preventDefault();

	var render = this.getData('render');

	if (!render)
	    this.setData('render', render = Y.Handlebars.compile(Y.one(this.getData('template') || this.next('script')).getHTML()));

	this.insert(render(Y.merge({name: Y.Austra.Seed.next()}, Y.JSON.parse(this.getData('context') || null))), 'before');
    }, document, '.photographs .add');

    Y.delegate('click', function (e) {
	e.preventDefault();

	this.ancestor(Y.Lang.sub('.{class}', { class: this.getData('dismiss') })).remove();
    }, document, '.close, .mj-dismiss');

    Y.delegate('click', function (e) {
	e.preventDefault();

	var uploader = this.getData('uploader');
	if (!uploader) {
	    this.setData('uploader', uploader = new Y.Mojomaja.Photograph.Uploader(!this.test('.scalar'), this.test('.watermark')));

	    var render = Y.Handlebars.compile(Y.one(this.getData('template') || this.next('script')).getHTML());
	    var context = Y.JSON.parse(this.getData('context') || null);
	    var that = this;
	    uploader.on({
		start: function () {

		},
		complete: function (e) {


		    if (that.test('.scalar'))
			that.siblings(Y.Lang.sub('.{class}', { class: that.getData('dismiss') })).remove();
		    Y.Array.each(e, function (f) {
			that.insert(render(Y.merge(f, { name: Y.Austra.Seed.next() }, context)), 'before');
		    });
		}
	    });
	}

	uploader.exec();
    }, document, '.photographs .add-file');

    Y.Array.each(
	[
	    {
		test     : '#admin.rentalstation.new, #admin.rentalstation.edit',
		container: '.photographs',
		nodes    : '.photograph'
	    }
	],
	function (s) {
	    if (Y.one(s.test))
		Y.all(s.container).each(function () {
		    new Y.Sortable({
			container: this,
			nodes    : s.nodes,
			opacity  : .35
		    });
		});
    });
});

YUI.add('austra-seed', function (Y) {
    var seed = (new Date()).getTime();

    Y.mix(
	Y.namespace('Austra.Seed'),
	{
	    next: function () {
		return seed++;
	    }
	}
    );
}, '0.0.1', {
    requires: []
});

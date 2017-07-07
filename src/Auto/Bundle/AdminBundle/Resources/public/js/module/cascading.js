YUI.add('auto-cascading', function (Y) {
    function sculpt(o) {
	return Y.Lang.isObject(o) ? o : Y.JSON.parse(o || null) || {};
    }

    Y.delegate('change', function (e) {
	e.preventDefault();

	var c = Y.one(this.getData('cascading')).empty(),
	    d = sculpt(this.one('option:checked').getData('value'));
	for (var i in d) {
	    if (Y.Lang.isObject(d[i])) {
		for (var j in d)
		    c.append(Y.Node.create('<option></option>').
			     set('text', j).
			     setData('value', d[j]));
	    }
	    else {
		for (var j in d)
		    c.append(Y.Node.create('<option></option>').
			     set('text', j).
			     set('value', d[j]));
	    }
	    c.simulate('change');
	    break;
	}
    }, document, 'select[data-cascading]');

    function illume(s, opt, val) {
	if (s) {
	    var c = Y.one(s.getData('cascading')),
		v = s.getData('value');
	    opt = sculpt(opt);
	    for (var k in opt) {
		if (illume(c, opt[k], v))
		    return true;
	    }
	}
	else
	    return opt == val;

	return false;
    }

    function boost(s) {
	function boost_one(s) {
	    var c = Y.one(s.getData('cascading')),
		v = s.getData('value'),
		o = s.all('option'), p;
	    while (p = o.pop()) {
		if (illume(c, p.getData('value') || p.get('value'), v))
		    break;
	    }

	    if (p)
		p.set('selected', true);

	    s.simulate('change');

	    return p && c;
	}

	while (s = boost_one(s))
	    ;
    }

    Y.all('select:not(:empty)').each(boost);
    Y.all('select[data-src]').each(function (s) {
	s.empty();

	Y.io(s.getData('src'), {
	    method: 'GET',
	    on: {
		success: function (_, resp) {
		    var r = Y.JSON.parse(resp.responseText);
		    for (var k in r)
			s.append(Y.Node.create('<option></option>').
                set('text', k).
				 setData('value', r[k]));

		    boost(s);
		}
	    }
	});
    });
}, '0.0.1', {
    requires: ['node', 'event', 'node-event-simulate', 'json-parse', 'io-base']
});

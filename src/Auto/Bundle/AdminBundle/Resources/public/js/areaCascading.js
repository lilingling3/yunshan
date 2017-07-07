$(document).ready(function(){
var lointwoCascadingurl=/^\/admin\/login/
	console.log(lointwoCascadingurl.test(window.location.pathname));
	if(! lointwoCascadingurl.test(window.location.pathname)){



	var AreaList;
	var node = $('.AreaFilter');
	var province = node.find('select[name="province"]');
	var city = node.find('select[name="city"]');

	$.get("/admin/area/twoCascading",
	    {},
	    function(data,status){

	    	AreaList = data;
	    	// console.log(data);
	    	// console.log(status);
	    	$.each(data, function(idx, obj){

	    		province.append('<option>' + idx + '</option>');
	    	});
	    }
    );



	province.on('change', function(){

			var sel = province.find('option:selected').text();

			// 省更新需清除之前的数据
			city.find('option:gt(0)').remove();

			$.each(AreaList, function(idx, obj){

				if ( idx === sel ) {

					$.each(obj, function(key, value){
						city.append('<option value=\'' + value + '\'>' + key + '</option>');
					});

					return false;
				}
			});
		});

		$("#auto_bundle_managerbundle_rentalstation_area").on('change', function(){
			$.get("/admin/area/threeCascading",
				{"id":$(this).val()},
				function(data,status){
					console.log(data);
					$("#auto_bundle_managerbundle_rentalstation_businessDistrict").empty();
					$.each(data["data"], function(idx, obj){
						$("#auto_bundle_managerbundle_rentalstation_businessDistrict").append('<option value=\'' + obj["id"] + '\'>' + obj["name"] + '</option>');
					})

				}
			);
		});
	}
});
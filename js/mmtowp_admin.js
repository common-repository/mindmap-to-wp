jQuery(function($) {
	$('#mmfile').change(function(){
		$('#mmimport_results').html('<b>Loading file ...</b>').css('display','block');
		
		var file = $('#mmfile')[0].files[0];
		var fd = new FormData();
		fd.append('action', 'mmtowp_mm_upload');
		fd.append('theFile', file);
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			processData: false,
			contentType: false,
			data: fd,
			success: function (data, status, jqxhr) {
				$('#mmimport_results').html(data);
				// console.log('Got this from the server: ' + data);
			},
			error: function (jqxhr, status, msg) {
				console.log('Got this from the server: ' + msg);
			}
		});
		
	});
});
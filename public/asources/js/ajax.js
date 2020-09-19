function ajax_post(data, callback_success, callback_fail, on_complete) {
	$.ajax({
		method: "POST",
		url: "ajax/v1.php",
		data: data
	})
	.done(function(response) {
		$('.fullscreen-loader-box-title').html('Procesando respuesta');
		$('.progress-bar-ciro').animate({width:'40%'}, {
			queue: false,
			duration: 300,
			easing: 'linear',
			complete: function(){
				if(response.done) {
					eval(callback_success + '(response);');
				}
				else {
					eval(callback_fail + '(response);');
				}
			}
		});		
	})
	.fail(function(){

	});
}
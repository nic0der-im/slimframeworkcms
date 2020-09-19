/*
$(document).ready(function(){

	sessionStorage.setItem('cantclose', 0);

	var timerInstance = new Timer();
	Date.prototype.toDateInputValue = (function() {
		var local = new Date(this);
		local.setMinutes(this.getMinutes() - this.getTimezoneOffset());
		return local.toJSON().slice(0,10);
	});
	document.getElementById('mpss_pb_cita_pactada').value = new Date().toDateInputValue();

	$('.timepicker').timepicker({
		minuteStep: 5,
		appendWidgetTo: 'body',
		showMeridian: false,
	  });

	$(document).on('click', '.btn-llamar', function(){

		$('#mpss_pb_id_prospecto').val($(this).data('id_prospecto'));

		var data = 'id_prospecto='+$('#mpss_pb_id_prospecto').val();
		console.log(data);

		$.ajax({
			url: $('.getinfoprospecto').val(),
			data: data,
			method: 'GET',
			cache: false,
			dataType: 'json',
			success: function(response) {
				if(response.success == true) {
					$('.mpfs_pb_nombre').html(response.nombre + ' ' + response.apellido);
					$('.mpfs_pb_observaciones').html(response.observaciones);
					$('.mpfs_pb_sniper').html(response.get_sniper_nombre.nombre + ' ' + response.get_sniper_nombre.apellido);
					$('.mpfs_pb_fecha').html(moment(response.created_at).format('DD/MM/YYYY [@] H:mm'));
					$('.mpfs_pb_localidad').html(response.localidad);
					$('.mpss_pb_telefono').html(response.telefono);

					if(response.estado == 0 || response.estado == 1)
					{
						$('.transferir_btn').show();
					}
					else
					{
						$('.transferir_btn').hide();
					}

					if(response.get_historial) {
						$('.table_llamados').html('');
						var nro = 1;
						$.each(response.get_historial, function(index, value){

							if(value.estado == 1) {
								var estado = "Llamar más tarde";
							}
							else if(value.estado == 2) {
								var estado = "Cita pactada";
							}
							else if(value.estado == 3) {
								var estado = "Cliente perdido";
							}

							var $tr = '<tr><td>' + nro + '</td><td>' + moment(value.created_at).format('DD/M/Y') + ' ' + moment(value.created_at).format('h:mm:ss') + '</td><td>' + estado + '</td><td>' + moment(value.valor_estado).format('DD/M/Y') + ' ' + moment(value.valor_estado).format('h:mm:ss')  + '</td><td>' + value.duracion_llamada + '</td><td>' + value.observaciones + '</td></tr>';

							$('.table_llamados').append($tr);
							nro++;
						});
					}
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				console.log(xhr);
				console.log(ajaxOptions);
				console.log(thrownError);
			}
		});
	});
});
*/

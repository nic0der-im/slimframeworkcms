/*
$(document).ready(function(){

	sessionStorage.setItem('cantclose', 0);

	$('[data-toggle2="focus_tooltip"]').tooltip({
		trigger: 'manual',
		placement: 'top'
	});

	var timerInstance = new Timer();
	Date.prototype.toDateInputValue = (function() {
    var local = new Date(this);
    local.setMinutes(this.getMinutes() - this.getTimezoneOffset());
    return local.toJSON().slice(0,10);
	});
	document.getElementById('mpss_pb_cita_pactada').value = new Date().toDateInputValue();
	document.getElementById('mpss_pb_fecha_mas_tarde').value = new Date().toDateInputValue();

	$('.timepicker').timepicker({
    minuteStep: 5,
    appendWidgetTo: 'body',
    showMeridian: false,
  });

	$('.mp-fs-next').click(function(){

		sessionStorage.setItem('cantclose', 1);
		$('.mp-nt-ss').hide().removeClass('disabled').fadeIn(300);
		$('.mp-nt-ss a').click();
		$('#informacion_del_prospecto .action-buttons').fadeOut();
		$('.mp-nt-fs a').tooltip('show');
		setTimeout(function(){
			$('.mp-nt-fs a').tooltip('hide');
		}, 3000);
		timerInstance.start();
		timerInstance.addEventListener('secondsUpdated', function (e) {
			$('.mpss_pb_tiempo_timer').html(timerInstance.getTimeValues().toString());
		});
	});

	$('.mpss_pb_llamada').on('change', function(e){
		$('.mpss_pb_llamada_input').val($(this).val());
		if($(this).val() == 1) {
			if(confirm('¿Quieres llamar otra vez?.', 'si', 'no') == false) {
				e.preventDefault();
				$('.mpss_pb_third input').prop('disabled', true);
				$('.mpss_pb_ta_observaciones').prop('disabled', true);

				$(this).prop('disabled', true);
				form.submit();
			}
			else {
				$('.mpss_pb_llamada option:eq(0)').prop('selected', true);
			}
		}

		if($(this).val() == 2) {
			$('.mpss_pb_llamada_box').slideDown();
			$(this).prop('disabled', true);
		}
	});

	$('.mpss_pb_estado').on('change', function(e){
		$('.mpss_pb_third').slideUp();

		$('.mpss_pb_third input').prop('disabled', true);

		if($(this).val() == 1) {
			$('.mpss_pb_mas_tarde_box').slideDown();
			$('.mpss_pb_mas_tarde_box input').prop('disabled', false);
		}

		if($(this).val() == 2) {
			$('.mpss_pb_cita_pactada_box').slideDown();
			$('.mpss_pb_cita_pactada_box input').prop('disabled', false);
		}

		$('.mpss_pb_observaciones').slideDown();
		$('.mpss_pb_fourth').slideDown();
	});

	var form = $('.form_prospecto');
	form.on('submit', function (e) {
		e.preventDefault();
		$('#mpss_pb_time').val(timerInstance.getTimeValues());
		timerInstance.pause();
		sessionStorage.setItem('cantclose', 0);

		$.ajax({
			url: form.attr('action'),
			data: form.serialize(),
			method: form.attr('method'),
			cache: false,
			dataType: 'json',
			success: function( data ) {
				console.log(form.serialize());
				if(data.success == true) {
					$('.form_prospecto select').prop('disabled', true);
					$('.form_prospecto input').prop('disabled', true);
					$('.form_prospecto textarea').prop('disabled', true);
					$('.mp-nt-ts').removeClass('disabled');
					$('.mp-nt-ts a').click();
				}
			},
			complete: function (jqXHR) {
				var csrfToken = jqXHR.getResponseHeader('X-CSRF-Token');
				if (csrfToken) {
					try {
						csrfToken = $.parseJSON(csrfToken);
						var csrfTokenKeys = Object.keys(csrfToken);
						var hiddenFields = srb_form.find('input.csrf[type="hidden"]');
						if (csrfTokenKeys.length === hiddenFields.length) {
							hiddenFields.each(function(i) {
								$(this).attr('name', csrfTokenKeys[i]);
								$(this).val(csrfToken[csrfTokenKeys[i]]);
							});
						}
					}
					catch (e) {

					}
				}
			}
		});
	});

	
		- Evita que el usuario pueda cerrar el menú de llamadas mientras
		se encuentra realizando una llamada.
		- Pregunta al usuario re realmente quiere cerrar el menú de llamadas.

	$('.modal-prospecto').on('hide.bs.modal', function(e){
		// Si no está realizando una llamada.
		if(sessionStorage.getItem('cantclose') == 0) {
			if(confirm('¿Deseas cerrar el menú de llamadas?', 'si', 'no') == false) {
				e.preventDefault();
			}
			else {
				var id = $('#mpss_pb_id_prospecto').val();
				$.ajax({
					url:"/administracion/prospectos/prospecto-liberar/"+id,
					method: 'GET',
					cache: false,
					dataType: 'json',
					success: function(response) {
						location.reload();
					}
				});
			}
		}
		// Si está realizando una llamada.
		else {
			e.preventDefault();
		}
	});

	$('.mpts_listo').on('click', function(){
		location.reload();
	});

		Enviar una advertencia cuando el usuario intenta cerrar o actualizar
		la página mientras realiza una llamada.

	$(window).on('beforeunload', function (e) {
		if(sessionStorage.getItem('cantclose') == 1) {
			return false;
		}
	});

});
*/

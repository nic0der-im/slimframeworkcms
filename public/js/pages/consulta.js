$(document).ready(function(){

	$('.consulta_whatsapp_btn_desktop').click(function(e){
		e.preventDefault();

		var nombre = $('.consulta_nombre').val();
		var apellido = $('.consulta_apellido').val();
		var consulta = $('.consulta_texto_desktop').val();

		var publicacion = location.href;

		var url = "https://api.whatsapp.com/send?phone=543874898080&text=Hola, mi nombre es " + nombre + " " + apellido + ". Tengo una consulta por esta publicación: " + publicacion + ". Consulta: " + consulta;

		$("<a>").attr("href", url).attr("target", "_blank")[0].click();
	});


	$('.consulta_whatsapp_btn_movil').click(function(e){
		e.preventDefault();

		var nombre = $('.consulta_nombre').val();
		var apellido = $('.consulta_apellido').val();
		var consulta = $('.consulta_texto_movil').val();

		var publicacion = location.href;

		var url = "https://api.whatsapp.com/send?phone=543874898080&text=Hola, mi nombre es " + nombre + " " + apellido + ". Tengo una consulta por esta publicación: " + publicacion + ". Consulta: " + consulta;

		$("<a>").attr("href", url).attr("target", "_blank")[0].click();
	});

	$('form[name=form_consulta]').submit(function(e){
		e.preventDefault();
		var form = this;

		console.log(form);

		$.ajax({
			url: $(form).attr('action'),
			method: $(form).attr('method'),
			data: $(form).serialize(),
			success: function(data){
				if(data.success == true) {
					$(form).children('.step0').fadeOut('slow', function(){
						$(form).children('.step1').fadeIn();
						setTimeout(function(){
							$(form).children('.step1').fadeOut();
						}, 3000);
					});
				}
			}
		});

	});	

});
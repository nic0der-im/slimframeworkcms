$(document).ready(function(){
	
	$('.ver_publicacion').on('click',function(){
		var route  = $(this).data('url');
		ga('send', 'event', 'pagina_inicio', 'publicaciones', 'btn_ver_pub');
		window.location.href = route;
	});

	$('.vehicle-card').click(function(){
		$(this).find('.btn-ver-publicacion')[0].click();
	});

});
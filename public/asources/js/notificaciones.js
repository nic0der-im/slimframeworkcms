function notificacion_abierta(id) {
	$.ajax({
		url:"/administracion/notificaciones/visto/"+id,
		method: 'GET',
		cache: false,
	});
	return 1;
}
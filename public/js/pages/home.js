$(document).ready(function(){

	window.setTimeout("functionnotes()",1000);
	window.setInterval("functionnotes()",22000);
		
	var x= true;	

	$(window).scroll(function() {
		if($('#vehicle-paginator').visible() && x)
		{
			x=false;

			$('#ventas').prop('number', 0).animateNumber({
				number: 240,
			}, 3000);

			$('#ventasanio').prop('number', 0).animateNumber({
				number: 80,
			}, 4000);

			$('#ventas0km').prop('number', 0).animateNumber({
				number: 50,
			}, 5000);

			$('#suscriptores').prop('number', 0).animateNumber({
				number: 1500,
			}, 6000);
		}
	});

});

function functionnotes(){
	var ran=Math.floor((Math.random() * 5) + 1);
	switch(ran){
	case 1: notif({
			msg: "<i style='font-size:20px;' class='fa fa-line-chart'></i> Sabías que llevamos más de 240 autos vendidos!",
			bgcolor: "#3286C6",
				color: "#fff",
				position: "bottom",
				opacity: 0.95,
				clickable: true,
		});
		break;
	case 2: notif({
			msg: "<i style='font-size:20px;' class='fa fa-group'></i> Suscribíte y obtené descuentos!<br><a class='btn btn-primary' href='#suscripcion_email'>Suscribíte</a>",
			bgcolor: "#103663",
				color: "#fff",
				position: "bottom",
				multiline: true,
				opacity: 0.95,
				clickable: true,
		});
		break;
	case 3: notif({
			msg: "<i style='font-size:20px;' class='fa fa-car'></i> Obtené descuentos en 0km. Enterate como <a href='#' style='color:#fff; text-decoration: underline;'>aquí</a>",
			bgcolor: "#3286C6",
				color: "#fff",
				position: "bottom",
				opacity: 0.95,
				clickable: true,
		});
		break;
	case 4: notif({
			msg: "<i style='font-size:20px;' class='fa fa-map-marker'></i> Visitános en España 1190 (Esquina Alvear) Salta Capital",
			bgcolor: "#3286C6",
				color: "#fff",
				position: "bottom",
				opacity: 0.95,
				clickable: true,
		});
		break;
	case 5: notif({
				height: 200,
			msg: "<i style='font-size:20px;' class='fa fa-check-square'></i> ¿Qué esperás para comunicarte con nosotros?<br><a class='btn btn-primary' target='_blank' href='{{path_for('contacto')}}'>Contactános</a>",
				bgcolor: "#103663",
				color: "#fff",
				position: "bottom",
				multiline: true,
				opacity: 0.95,
				clickable: true,
		});
		break;
	case 6: notif({
			msg: "<i style='font-size:20px;' class='fa fa-map-marker'></i> Visitános en Alvarado 569, Salta Oran",
			bgcolor: "#3286C6",
				color: "#fff",
				position: "bottom",
				opacity: 0.95,
				clickable: true,
		});
		break;
	}   
}
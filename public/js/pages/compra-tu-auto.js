$(document).ready(function(){
	/* Llevar al usuario al centro de la web */
	//$('#cta_simulator').animatescroll();

	$('#simulator').css('margin-top', '1em');
	$('#simulator').css('margin-bottom', '1em');

	$(document).on('click', '.tt-suggestion', function(e){
		e.preventDefault();		
	});

	$('#pills-paymethods-tab').click();
	
	$('.next-step').click(function(){
		$('#pills-used-cars-tab').click();
	});

	/* Boton para "ENTRAR" al simulador */
	$('#enter_simulator').click(function(){
		$('#simulator').css('height', 'auto');
		$('#simulator').css('margin-top', '1em');
		$('#simulator').css('margin-bottom', '1em');
		$('#simulator').animatescroll({scrollSpeed:2000,easing:'easeOutBack'});

		$('.input-dream')[0].focus();
	});

});
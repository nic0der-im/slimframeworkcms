$(document).ready(function() {
		sessionStorage.setItem('nowpp', 'false');

		setTimeout(function(){
			$('.flashmessage').slideUp();
		}, 3000);

		$('.btn-whatsapp').click(function(e){
			e.preventDefault();
			window.open($(this).attr('data-href'));			
		});

		$('[data-toggle="popover-whatsapp"]').popover({
			container: 'body',
			trigger: 'hover focus',
			placement: 'left',
			title: 'Obtén más información sobre este vehículo', 
			content: "",
			html: true,			
			template: '<div class="popover popover-whatsapp text-white" role="tooltip"><div class="arrow arrow-wpp"></div><h3 class="popover-title text-white">Obtén más información sobre este vehículo</h3><div class="popover-content">Hacé clic y comunicate instantáneamente por WhatsApp con uno de nuestros asesores. <button class="btn btn-sm btn-flat btn-block d-lg-none nowpp" id="nocapo">No gracias.</button></div></div>'
		}).on('hide.bs.popover', function () {
			$(this).data('hidden', false);
		  $(this).data('changing', true);
		}).on('hidden.bs.popover', function () {
		  $(this).data('changing', false);
		  $(this).data('hidden', true);
		}).on('shown.bs.popover', function () {
		  $(this).data('changing', false);
		  $(this).data('hidden', false);
		}).on('show.bs.popover', function () {
		  $(this).data('changing', true);
		  $(this).data('hidden', false);
		});

		$('[data-toggle="popover-link"]').popover({
			container: 'body',
			trigger: 'hover focus',
			placement: 'left',
			title: 'Copiar el enlace para compartirlo',
			content: "",
			html: true,			
			template: '<div class="popover popover-link" role="tooltip"><div class="arrow"></div><h3 class="popover-title">Copiar el enlace para compartirlo</h3></div>'
		});
		
		$(document).click('.nowpp', function(){
			sessionStorage.setItem('nowpp', 'true');
		});

		$('#subir').hide();

		$(window).scroll(function(){
			if($(this).scrollTop() > 200) {
				$('#subir').fadeIn();
			}
			else {
				$('#subir').fadeOut();
			}
		})

		$( "#btn-0km" ).on( "click", function() {
			$( "#mega-menu" ).toggle('blind','fast');
		});
		
});


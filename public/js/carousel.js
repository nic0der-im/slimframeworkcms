$(document).on('ready', function(){
	$('.carousel.carousel-slider').carousel({
		fullWidth: true,
		duration: 200,
		height: 300
	});
	
	var carousel;
	carousel = setInterval(function(){
		$('.carousel').carousel('next');
	}, 5000);

	$('.carousel .indicator-item').on('click', function() {
		clearInterval(carousel);
		carousel = setInterval(function(){
			$('.carousel').carousel('next');
		}, 5000);
	});
});
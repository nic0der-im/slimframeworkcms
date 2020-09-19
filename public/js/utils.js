$(document).ready(function () {

	$('#dismiss, .overlay').on('click', function () {
			$('#sidebar').removeClass('active');
			$('#sidebarCollapse').removeClass('active');
			$('.overlay').fadeOut();
			$('body').css('overflow', 'initial');
	 });

 	$('#sidebarCollapse').on('click', function () {
		if($(this).hasClass('active')) {
			$('#sidebar').removeClass('active');
			$('#sidebarCollapse').removeClass('active');
			$('.overlay').fadeOut();
			$('body').css('overflow', 'initial');
		}
		else 
		{
			$('#sidebar').addClass('active');
			$('body').css('overflow', 'hidden');
			$(this).addClass('active');
			$('.overlay').fadeIn();
			$('.collapse.in').toggleClass('in');
			$('a[aria-expanded=true]').attr('aria-expanded', 'false');
		}
	});
	 
});

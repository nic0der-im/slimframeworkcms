$(document).on('ready', function(){
	var path = $(location).attr('pathname');
	console.log(path);
	console.log($('.nav-treeview li a[href="' + path + '"]'));
	$('.nav-treeview li a[href="' + path + '"]').addClass('active');
	$('.nav-treeview li a[href="' + path + '"]').closest('.has-treeview').addClass('menu-open');
});

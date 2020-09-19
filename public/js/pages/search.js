$(document).ready(function() {


	var estado = 0;

	var srb_form = $('.sbr_form')[0];
	sessionStorage.setItem('sul', 'null');
	
	$('.searchbartxt').focus(function(){
		srb_form = $($(this).data('form'));
		console.log(srb_form);
		if(sessionStorage.getItem('sul') != 'null') {
		}
	});

	$('#form_search_mobile').submit(function(e){
		e.preventDefault();
	});

	$('#searchbar').submit(function(e){
		e.preventDefault();
	});



	$( "#input_search_mobile" ).autocomplete({
		open: function () {
			$( "#input_search_mobile" ).addClass('searching');
			$('ul.ui-autocomplete').addClass('opened');
		},
		close: function(event, ui) {
			$( "#input_search_mobile" ).removeClass('searching');
			$('ul.ui-autocomplete').removeClass('opened');
		},
		focus: function( event, ui ) {
      $( "#input_search_mobile" ).val( ui.item.year + ui.item.marca + ui.item.modelo );
      return false;
    },
    select: function( event, ui ) {
      $( "#input_search_mobile" ).val( ui.item.year + ui.item.marca + ui.item.modelo );
     	location.href = ui.item.url;
      return false;
    },
		source: function(request, response) {

			if(estado == 0) {
				estado = 1;
				$.ajax({
					url: $('#form_search_mobile').attr('action'),
					data: $('#form_search_mobile').serialize(),
					method: $('#form_search_mobile').attr('method'),
					cache: false,
					dataType: 'json',
					success: function( data ) {
						response( data.res);
						console.log(data);
					},
					complete: function (jqXHR) {
						var csrfToken = jqXHR.getResponseHeader('X-CSRF-Token');
						if (csrfToken) {
							try {
								csrfToken = $.parseJSON(csrfToken);
								var csrfTokenKeys = Object.keys(csrfToken);
								var hiddenFields = $('#form_search_mobile').find('input.csrf[type="hidden"]');
								if (csrfTokenKeys.length === hiddenFields.length) {
									hiddenFields.each(function(i) {
										$(this).attr('name', csrfTokenKeys[i]);
										$(this).val(csrfToken[csrfTokenKeys[i]]);
									});
								}
								estado = 0;
							}
							catch (e) {

							}
						}
					}
				});
			}
		},
		minLength: 2
	}).autocomplete( "instance" )._renderItem = function( ul, item ) {
		sessionStorage.setItem('sul', ul.attr('id'));
		console.log(item);
		var str = item.year + item.marca + item.modelo;
		var t = String(str).replace(
			new RegExp(this.term, "gi"),
			"<span class='srb-highlight'>$&</span>");
		return $("<li>").data("item.autocomplete", item)
			.append("<div class='srb-item' data-marca='"+item.marca+"' data-year='"+item.year+"' data-modelo='"+item.modelo+"'>" + t + "</div>")
			.appendTo(ul);
	};

	$("#input_search_desktop").on( "keydown", function(event) {
		if(event.which == 13) {
			alert('enter');
		}

	});

	$( "#input_search_desktop" ).autocomplete({
		open: function () {
			$( "#input_search_desktop" ).addClass('searching');
			$('ul.ui-autocomplete').addClass('opened');
		},
		close: function(event, ui) {			
			$(this).data().autocomplete.term = null; // Clear the cached search term, make every search new
			$( "#input_search_mobile" ).removeClass('searching');
			$('ul.ui-autocomplete').removeClass('opened'); 
		},
		focus: function( event, ui ) {
      $( "#input_search_desktop" ).val( ui.item.year + ui.item.marca + ui.item.modelo );
      return false;
    },
    select: function( event, ui ) {
      $( "#input_search_desktop" ).val( ui.item.year + ui.item.marca + ui.item.modelo );
     	location.href = ui.item.url;
      return false;
    },
		source: function(request, response) {
			if(estado == 0) {
				estado = 1;
				$.ajax({
					url: $('#searchbar').attr('action'),
					data: $('#searchbar').serialize(),
					method: $('#searchbar').attr('method'),
					cache: false,
					dataType: 'json',
					success: function( data ) {
						response( data.res);
						console.log(data);
					},
					complete: function (jqXHR) {
						var csrfToken = jqXHR.getResponseHeader('X-CSRF-Token');
						if (csrfToken) {
							try {
								csrfToken = $.parseJSON(csrfToken);
								var csrfTokenKeys = Object.keys(csrfToken);
								var hiddenFields = $('#searchbar').find('input.csrf[type="hidden"]');
								if (csrfTokenKeys.length === hiddenFields.length) {
									hiddenFields.each(function(i) {
										$(this).attr('name', csrfTokenKeys[i]);
										$(this).val(csrfToken[csrfTokenKeys[i]]);
									});
								}
								estado = 0;
							}
							catch (e) {

							}
						}
					}
				});
			}
		},
		minLength: 2
	}).autocomplete( "instance" )._renderItem = function( ul, item ) {
		sessionStorage.setItem('sul', ul.attr('id'));
		console.log(item);
		var str = item.year + item.marca + item.modelo;
		var t = String(str).replace(
			new RegExp(this.term, "gi"),
			"<span class='srb-highlight'>$&</span>");
		return $("<li>").data("item.autocomplete", item)
			.append("<div class='srb-item' data-marca='"+item.marca+"' data-year='"+item.year+"' data-modelo='"+item.modelo+"'>" + t + "</div>")
			.appendTo(ul);
	};

});
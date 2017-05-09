(function( $ ) {
	'use strict';
	$(function() {
		var project_options = {};
		if($('#available_languages').length ) {

			if($('#PID_dropdown').length ) {
				sdl_load_options();
				 $("#PID_dropdown").change(function () {
			    sdl_refresh_options(this.value);
					$("input, select").change(function () {
						sdl_on_change();
				  });
			   });
			}
			$("input, select").change(function () {
				sdl_on_change();
		  });
			function sdl_on_change(){
				if($('select[name="ProjectOptionsID"] :selected').val() != 'blank' && $('select[name="SrcLang"] :selected').val() != 'blank' && $('input[name="TargetLangs[]"]:checked').length >0 ) {
			 		$('button[type="submit"]').prop('disabled', false);
				} else {
				 	$('button[type="submit"]').prop('disabled', true);
				}
			}
			function sdl_load_options(){
				var data = {
					'action': 'sdl_get_options'
				};
				$.post(ajaxurl, data, function(response) {
					project_options = JSON.parse(response);
				});
			}
			function sdl_refresh_options(value) {
				var current_options = project_options[value];
				var available_languages = JSON.parse($('#available_languages').attr('data-langs'));
				console.log(available_languages);
				var src_output = '';
				var target_output = '';
				$.each(current_options.Source, function(key, value){
					if(jQuery.inArray( value.toLowerCase(), available_languages ) >= 0) {
						src_output += '<option value="'+ value + '">'+ value +'</option>';
					} else {
						src_output += '<option value="'+ value + '" disabled>'+ value +'</option>';
					}
				});
				$.each(current_options.Target, function(key, value){
					if(jQuery.inArray( value.toLowerCase(), available_languages ) >= 0) {
						target_output += '<input type="checkbox" name="TargetLangs[]" value="' + value +'">';
					} else {
						target_output += '<input type="checkbox" name="TargetLangs[]" value="' + value +'" disabled>';
					}
					target_output += '<label for="' + value +'">' + value +'</label>';
				});
				$('#src_langs').html(src_output);
				$('#TargetLangs').html(target_output);
			}
		};

	});
})( jQuery );

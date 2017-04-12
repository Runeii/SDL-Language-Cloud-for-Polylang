(function( $ ) {
	'use strict';
	$(function() { 
		var project_options = {};
		if($('#PID_dropdown').length ) {
			sdl_load_options();
			 $("#PID_dropdown").change(function () {
			 	$('#create_button').prop('disabled', function(i, v) { return !v; });
		        sdl_refresh_options(this.value);
		    });
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
			var src_output = '';
			var target_output = '';
			$.each(current_options.Source, function(key, value){
				src_output += '<option value="'+ value + '">'+ value +'</option>';
			});
			$.each(current_options.Target, function(key, value){
				target_output += '<input type="checkbox" name="TargetLangs[]" value="' + value +'">';
				target_output += '<label for="' + value +'">' + value +'</label>';
			});
			$('#src_langs').html(src_output);
			$('#TargetLangs').html(target_output);
		}
	});
})( jQuery );

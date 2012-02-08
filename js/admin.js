var farbtastic;
jQuery(function($){
	$('#pof-tabs-nav a').click(function(){
		if (!$(this).attr('href').match(/#nav\-tab\-\d+/))
			return true;
		$('#pof-tabs .tab').addClass('tab-hidden');
		$('#pof-tabs-nav a').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		$('#pof-tabs ' + $(this).attr('href')).removeClass('tab-hidden');
		return false;
	});
	$('*[data-show_if]').each(function(){
		if (!$("#" + $(this).attr('data-show_if')).is(':checked')){
			$(this).hide();
		}
	});
	$('#pof input[type=checkbox]').click(function(){
		if ($(this).is(':checked'))
			$('*[data-show_if=' + $(this).attr('id') + "]").fadeIn('fast');
		else
			$('*[data-show_if=' + $(this).attr('id') + "]").fadeOut('fast');
	});
	
	$.fn.pof_color_picker = function(){
		$(this).each(function(index, picker){
			picker.pickColor = function(a) {
				picker.farbtastic.setColor(a);
				$(picker).find('input[type=text]').val(a.toUpperCase());
			}
			picker.farbtastic = $.farbtastic($('div.picker', $(picker)), picker.pickColor);
			picker.pickColor($('input[type=text]', $(picker)).val());
			$('.pickcolor', $(picker)).click( function(e) {
				$('div.picker', $(picker)).show();
				e.preventDefault();
			});
			$('input[type=text]', $(picker)).keyup( function() {
				var a = $('input[type=text]', $(picker)).val(),
					b = a;

				a = a.replace(/[^a-fA-F0-9]/, '');
				if ( '#' + a !== b )
					$('input[type=text]', $(picker)).val(a);
				if ( a.length === 3 || a.length === 6 )
					picker.pickColor( '#' + a );
			});
		});
		$(document).mousedown( function() {
			$('div.picker').hide();
		});
	}

	$('.pof-color-picker').pof_color_picker();
	$('#button-reset').click(function(){
		$('#reset').val('1');
	});
});
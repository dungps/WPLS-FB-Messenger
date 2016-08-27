(function($){
	$(document).ready(function(){
		console.log($('.timepicker'))
		$('.timepicker').timepicker({
			step: 15,
			timeFormat: 'H:i',
			useSelect: true
		});
	})
})(jQuery);
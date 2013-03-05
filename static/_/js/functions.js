(function($){})(window.jQuery);

$(document).ready(function (){

	$('tbody.pointer tr').live('click', function () {
		$(this).find('input[type="radio"]').attr('checked', 'checked');
	});

});

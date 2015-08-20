(function($) {

	$(document).on('change','.klarna_pms_select', function() {
		var selectedOption = $(this).find('option:selected');
		// console.log( $(selectedOption).val());
		var pclass = $(selectedOption).val();

		$(this).closest('fieldset').find('div.klarna-pms-details').hide().removeClass('visible-pms');
		$('div.klarna-pms-details[data-pclass=' + pclass + ']').show().addClass('visible-pms');
	});

})(jQuery);
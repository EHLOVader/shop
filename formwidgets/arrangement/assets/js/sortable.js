$(function() {

	$('.arrangement').sortable().bind('sortupdate', function() {
		$('#Form-field-Category-arrangement_method').val('custom');
		$('*[data-field-name="arrangement_method"]').find('.select2-chosen').html('Custom');
	});

	$('#Form-field-Category-arrangement_columns').on('change', function() {
		$('ul.arrangement')[0].className = $('ul.arrangement')[0].className.replace(/\sspan[0-9]{1,2}/g, '');
		$('ul.arrangement').addClass( 'span' + $(this).val() );
	});

});
$(function() {

	$('.arrangement').sortable().bind('sortupdate', function() {
		$('#Form-field-Category-arrangement_method').val('custom');
		// $('.select2-chosen').html('Custom');

		$('*[data-field-name="arrangement_method"]').find('.select2-chosen').html('Custom');
	});

});
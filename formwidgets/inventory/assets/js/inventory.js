function attachSortable()
{
	$('.inventories').sortable({
		items: ':not(.disabled)',
		forcePlaceholderSize: true,
		handle: 'span'
	});
}

$(function() {

	// Fire up sortable
	attachSortable();

	// Append a new inventory to the list
	$('.new-inventory').on('click', function() {
		$('ul.inventories').append(
			'<li class="inventory">' + $('ul.inventories .template').html() + '</li>'
		);

		$('.inventories').sortable('destroy');
		attachSortable();

	});

	// Toggle is_active
	$('.toggle-is-active').on('click', function() {
		var checkbox = $(this).find('input[type="checkbox"]');
		checkbox.prop('checked', !checkbox.prop('checked'));

		if (checkbox.prop('checked'))
			$(this).find('i').removeClass('icon-times').addClass('icon-check');
		else
			$(this).find('i').removeClass('icon-check').addClass('icon-times');
	});

});
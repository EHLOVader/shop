function attachSortable()
{
	$('.inventories').sortable({
		items: ':not(.disabled)',
		forcePlaceholderSize: true,
		handle: 'span'
	});
}

function toggleIsActive(div)
{
	var isActive = $(div).find('input');
	var icon = $(div).find('i');
	if (isActive.val() == '0') {
		isActive.val('1');
		icon.removeClass('icon-times').addClass('icon-check');
	} else {
		isActive.val('0');
		icon.removeClass('icon-check').addClass('icon-times');
	}
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

});
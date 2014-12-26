/**
 * Fire up HTML5 Sortable
 */
function attachSortable()
{
	$('.inventories').sortable({
		items: ':not(.disabled)',
		forcePlaceholderSize: true,
		handle: 'span'
	});
}

/**
 * Toggle the "is_active" field on icon click
 */
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

/**
 * Show / Hides the "no inventories" div
 */
function toggleNoInventoriesSign()
{
	if ($('.inventories .inventory').length == 0) {
		$('.no-inventories').removeClass('hidden');
		$('.inventories').addClass('hidden');	
	} else {
		$('.no-inventories').addClass('hidden');
		$('.inventories').removeClass('hidden');
	}
}

/**
 * Delete a row from the inventory form
 */
var deleteTarget;
function setDeleteTarget( target )
{
	deleteTarget = target.closest('.inventory');
}
function removeDeleteTarget()
{
	deleteTarget.remove();
	deleteTarget = false;
	toggleNoInventoriesSign();
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

		toggleNoInventoriesSign();
		attachSortable();
	});

});
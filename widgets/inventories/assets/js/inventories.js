
/**
 * Fire up HTML5 Sortable
 */
function attachSortable()
{
    $('ul.inventories').sortable({
        items: ':not(.disabled)',
        forcePlaceholderSize: true,
        handle: 'span'
    }).bind('sortupdate', function() {
        // Update callback
    });
    $("#save-inventories").html("Save").removeClass("disabled");
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
    if ($('ul.inventories .inventory').length == 0) {
        $('.no-inventories').removeClass('hidden');
        $('ul.inventories').addClass('hidden'); 
    } else {
        $('.no-inventories').addClass('hidden');
        $('ul.inventories').removeClass('hidden');
    }
}

/**
 * Appends a new inventory to the list
 */
function addNewInventory()
{
    $('ul.inventories').append(
        '<li class="inventory">' + $('ul.inventories .template').html() + '</li>'
    );
    $('.inventories').sortable('destroy');

    toggleNoInventoriesSign();
    attachSortable();
}

/**
 * Delete an inventory
 */
function deleteInventory( button )
{
    var inventory = $(button).closest('.inventory');
    var id = inventory.find('input[name="Inventory[id][]"]').val();

    if (id == 0)
        inventory.remove();

    else
        $.request('onDeleteInventory', {
            confirm: "Are you sure?",
            data: { inventory_id: id },
            success: attachSortable()
        });

    toggleNoInventoriesSign();
}

                // data-request="onDeleteInventory"
                // data-request-data="inventory_id: <?= $inventory_id ?>"
                // data-request-confirm="Are you sure?"
                // data-request-success="attachSortable();"

$(function() {

    // Fire up sortable
    attachSortable();

});
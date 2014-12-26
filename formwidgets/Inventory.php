<?php namespace Bedard\Shop\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;
use Bedard\Shop\Models\Inventory as InventoryModel;
use Bedard\Shop\Models\Product;

use ValidationException;
use Flash;

class Inventory extends FormWidgetBase
{
    /**
     * Load arrangement assets
     */
    public function loadAssets()
    {
    	$this->addCss('/plugins/bedard/shop/formwidgets/inventory/assets/css/inventory.css');
        $this->addJs('/plugins/bedard/shop/formwidgets/inventory/assets/js/inventory.js');
    }

    /**
     * Widget Details
     * @return array
     */
    public function widgetDetails()
    {
        return [
            'name'        => 'Inventory',
            'description' => 'Manages a product\s inventories.'
        ];
    }

    /**
     * Prepare view variables
     */
    public function prepareVars()
    {
        $this->vars['inventories'] = $this->model->inventories;
    }

    /**
     * Render the widget
     */
    public function render()
    {
        $this->prepareVars();
    	return $this->makePartial('inventory');
    }

    /**
     * Prevents the widget from screwing up our form
     */
    public function getSaveData($value)
    {
    	// Get our inventory data
    	$inventories = $this->buildInventoryArrays();

    	// Validate our input
    	$this->validateInventories($inventories);

    	// Bind inventories to the product model
    	$this->bindInventories($inventories);

    	// Return no save data
        return FormField::NO_SAVE_DATA;
    }

    /**
     * Builds the inventory arrays from post data
     */
    private function buildInventoryArrays()
    {
    	// Build up our inventory arrays
    	$inventory['ids']		= post('Inventory')['id'];
    	$inventory['names']		= post('Inventory')['name'];
    	$inventory['quantities']= post('Inventory')['quantity'];
    	$inventory['modifiers']	= post('Inventory')['modifier'];
    	$inventory['is_active']	= post('Inventory')['is_active'];

    	// Shift the template info off the arrays
    	array_shift($inventory['ids']);
    	array_shift($inventory['names']);
    	array_shift($inventory['quantities']);
    	array_shift($inventory['modifiers']);
    	array_shift($inventory['is_active']);

    	// // Build the inventories array
    	$inventories = [];
    	for ($i = 0; $i < count($inventory['names']); $i++) {
    		$inventories[] = [
    			'id'		=> $inventory['ids'][$i],
    			'name'		=> $inventory['names'][$i],
    			'quantity'	=> $inventory['quantities'][$i],
    			'modifier'	=> $inventory['modifiers'][$i],
    			'is_active'	=> $inventory['is_active'][$i]
    		];
    	}

    	return $inventories;
    }

    private function validateInventories($inventories)
    {
    	// Only validate the names if we have more than one inventory
    	if (count($inventories) <= 1) return;

    	$takenNames = [];
    	foreach ($inventories as $inventory) {
    		// Ensure that all inventories are named
    		if (empty($inventory['name'])) {
    			Flash::error('Inventories must be named when more than one exists.');
    			throw new ValidationException('Inventories must be named when more than one exists.');
    		}

    		// Force inventory names to be unique
    		if (in_array($inventory['name'], $takenNames)) {
    			Flash::error('Inventory names must be unique.');
    			throw new ValidationException('Inventory names must be unique.');
    		}

    		// Everything passed, add the name to the $takenNames array
    		$takenNames[] = $inventory['name'];
    	}
    }

    /**
     * Create or find the inventory record, and bind it to the current product
     */
    private function bindInventories( $inventories )
    {
    	$product = $this->model->id ? $this->model : new Product;
    	foreach ($inventories as $i => $inventory) {
    		$inventoryModel = $inventory['id'] != 0
    			? InventoryModel::find($inventory['id'])
    			: new InventoryModel;

    		$inventoryModel->name		= $inventory['name'];
    		$inventoryModel->quantity	= $inventory['quantity'];
    		$inventoryModel->modifier	= $inventory['modifier'];
    		$inventoryModel->position	= $i;
    		$inventoryModel->is_active	= $inventory['is_active'];
    		$inventoryModel->save();

    		$product->inventories()->add($inventoryModel, $this->sessionKey);
    	}
    }

    /**
     * Deletes an inventory
     */
    public function onDeleteInventory()
    {
    	$id = intval(post('inventory_id'));

    	// If the inventory doesn't yet exist, return and jQuery will handle it.
    	if ($id == 0) return;

    	// Otherwise delete the inventory
    	$inventory = InventoryModel::find($id);
    	if (!$inventory) return Flash::error("Inventory #$id was not found.");
    	if (!$inventory->delete()) Flash::error("An unknown error occured while attempting to delete inventory #$id.");
    	Flash::success("Inventory #$id successfully deleted.");
    }
}
<?php namespace Bedard\Shop\FormWidgets;

use Backend\Classes\FormField;
use Backend\Classes\FormWidgetBase;

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
    	// Build up our inventory arrays
    	$inventory['names'] = post('Inventory')['name'];
    	$inventory['quantities'] = post('Inventory')['quantity'];
    	$inventory['modifiers'] = post('Inventory')['modifier'];

    	// Shift the template info off the arrays
    	array_shift($inventory['names']);
    	array_shift($inventory['quantities']);
    	array_shift($inventory['modifiers']);

    	print_r ($inventory);

    	print_r ($inventory);

    	// Return no save data
        return FormField::NO_SAVE_DATA;
    }
}
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
        
    }

    /**
     * Render the widget
     */
    public function render()
    {
        $this->prepareVars();

    	return $this->makePartial('inventory');
    }
}
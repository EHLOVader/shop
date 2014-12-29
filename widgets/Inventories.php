<?php namespace Bedard\Shop\Widgets;

use Backend\Classes\WidgetBase;
use Bedard\Shop\Models\Inventory;
use Bedard\Shop\Models\Product;
use Exception;
use Flash;
use ValidationException;

class Inventories extends WidgetBase
{
    /**
     * @var integer $productId
     */
    private $productId;

    /**
     * Widget Details
     * @return  array
     */
    public function widgetDetails()
    {
        return [
            'name'        => 'Inventories',
            'description' => 'Manages a product\'s inventories.'
        ];
    }

    /**
     * Load widget assets
     */
    public function loadAssets()
    {
        $this->addJs('/plugins/bedard/shop/assets/js/html5sortable.min.js');
        $this->addJs('/plugins/bedard/shop/widgets/inventories/assets/js/inventories.js');
        $this->addCss('/plugins/bedard/shop/widgets/inventories/assets/css/inventories.css');
    }

    /**
     * Load the product being managed
     * @param   Product $product
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
    }

    /**
     * Render the widget
     * @return  array
     */
    public function render()
    {
        // Throw an exception if we don't have a product
        if (!$this->productId)
            throw new Exception('Product ID must be set before the inventories widget may be rendered.');

        // Return the widget partial
        $this->prepareVars();
        return $this->makePartial('inventories');
    }

    /**
     * Pepares widget variables
     */
    private function prepareVars()
    {
        $this->vars['inventories'] = Inventory::where('product_id', $this->productId)
            ->orderBy('position', 'asc')
            ->get();
    }

    /**
     * Reloads product inventory, and returns the updated widget partial
     * @return  array
     */
    private function refreshPartial()
    {
        $this->prepareVars();
        return [
            '.widget-body' => $this->makePartial('inventories')
        ];
    }

    /**
     * Deletes an inventory
     * @return  array
     */
    public function onDeleteInventory()
    {
        if ($id = post('inventory_id')) {
            if (!$inventory = Inventory::find($id))
                return Flash::error("Inventory #$id was not found.");

            if (!$inventory->delete()) 
                return Flash::error("An unknown error occured while attempting to delete inventory #$id.");
        }

        Flash::success("Successfully deleted inventory.");
        return $this->refreshPartial();
    }

    /**
     * Save inventories
     * @return  array
     */
    public function onSaveInventories()
    {
        // Parse the inventory arrays and build something we can work with...
        $inventories = $this->buildInventoryArrays();

        // Since we have all the names in one place, we might as well validate
        // them here so we don't have to fire useless queries
        $inventoryNames = array_column($inventories, 'name');
        $this->validateInventoryNames($inventoryNames);

        // Attempt to create / update inventories
        if ($this->saveInventories($inventories) === FALSE)
            return Flash::error('An unknown error occured while attempting to update inventories.');

        // If we've made it this far, everything worked out
        Flash::success('Inventories have been updated.');
        return $this->refreshPartial();
    }

    /**
     * Builds the inventory arrays from post data
     * @return  array
     */
    private function buildInventoryArrays()
    {
        // Build up our inventory arrays
        $inventory['ids']       = post('Inventory')['id'];
        $inventory['names']     = post('Inventory')['name'];
        $inventory['quantities']= post('Inventory')['quantity'];
        $inventory['modifiers'] = post('Inventory')['modifier'];
        $inventory['is_active'] = post('Inventory')['is_active'];

        // Shift the template info off the arrays
        array_shift($inventory['ids']);
        array_shift($inventory['names']);
        array_shift($inventory['quantities']);
        array_shift($inventory['modifiers']);
        array_shift($inventory['is_active']);

        // Build the inventories array
        $inventories = [];
        for ($i = 0; $i < count($inventory['names']); $i++) {
            // Skip the row if it's new and everything is falsey
            if (!$inventory['ids'][$i] &&
                !$inventory['names'][$i] &&
                !$inventory['quantities'][$i] &&
                !$inventory['modifiers'][$i])
                continue;

            // Otherwise append it to the inventories array
            $inventories[] = [
                'id'        => $inventory['ids'][$i],
                'name'      => $inventory['names'][$i],
                'quantity'  => $inventory['quantities'][$i],
                'modifier'  => $inventory['modifiers'][$i],
                'is_active' => $inventory['is_active'][$i]
            ];
        }

        return $inventories;
    }

    /**
     * Validate the inventory names while they're all in one place
     * @param   array $names
     */
    private function validateInventoryNames($names)
    {
        // Only validate names if we have more than one inventory
        if (count($names) <= 1) return;

        // Multiple inventories must be named
        if (in_array('', $names)) {
            Flash::error('Inventories must be named when more than one exists.');
            throw new ValidationException('Inventories must be named when more than one exists.');
        }

        // Make sure there are no duplicate names
        if (count($names) !== count(array_unique($names))) {
            Flash::error('Inventory names must be unique.');
            throw new ValidationException('Inventory names must be unique.');
        }
    }

    /**
     * Creates / updates inventories
     * @param   array $inventories
     */
    private function saveInventories($inventories)
    {
        foreach ($inventories as $i => $inventory) {
            // Load the inventory model via Find, First, or New
            $inventoryModel = $inventory['id']
                ? Inventory::find($inventory['id'])
                : Inventory::firstOrNew(['product_id' => $this->productId, 'name' => $inventory['name']]);

            // Something went wrong finding / creating the model
            if (!$inventoryModel) return FALSE;

            // Update model values
            $inventoryModel->product_id = $this->productId;
            $inventoryModel->name       = $inventory['name'];
            $inventoryModel->quantity   = $inventory['quantity'];
            $inventoryModel->modifier   = $inventory['modifier'];
            $inventoryModel->is_active  = $inventory['is_active'];
            $inventoryModel->position   = $i;

            // Attempt to save the model
            if (!$inventoryModel->save()) return FALSE;
        }
    }
}
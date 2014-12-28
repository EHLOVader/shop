<?php namespace Bedard\Shop\Widgets;

use Backend\Classes\FormWidgetBase;

class Arrangement extends FormWidgetBase
{
    /**
     * Load arrangement assets
     */
    public function loadAssets()
    {
        $this->addCss('/plugins/bedard/shop/widgets/arrangement/assets/css/arrangement.css');
        $this->addJs('/plugins/bedard/shop/widgets/arrangement/assets/js/sortable.js');
    }

    public function widgetDetails()
    {
        return [
            'name'        => 'Product Arrangement',
            'description' => 'Allows for customizing the order of a category\'s products'
        ];
    }

    public function prepareVars()
    {
        $this->vars['arrangedProducts'] = $this->model->getArrangedProducts();
        $this->vars['cols'] = $this->model->arrangement_columns;
    }

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('arrangement');
    }
}
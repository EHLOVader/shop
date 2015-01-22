<?php namespace Bedard\Shop\ReportWidgets;

use Backend\Classes\ReportWidgetBase;
use Carbon\Carbon;
use DB;
use File;

/**
 * Google Analytics custom bar chart
 */
class HeatMap extends ReportWidgetBase
{
    /**
     * Renders the widget
     */
    public function render()
    {
        // Load assets
        $this->addJs('js/d3.v3.min.js');
        $this->addJs('js/cal-heatmap.js');
        $this->addCss('css/cal-heatmap.css');

        // Load properties
        $this->vars['title']                = $this->property('title');
        $this->vars['cellSize']             = $this->property('cellSize');
        $this->vars['cellPadding']          = $this->property('cellPadding');
        $this->vars['domain']               = $this->property('domain');
        $this->vars['subDomain']            = $this->property('subDomain');
        $this->vars['range']                = $this->property('range');
        $this->vars['start']                = $this->calculateStart();
        $this->vars['weekStartOnMonday']    = $this->property('weekStartOnMonday');
        $this->vars['showDomainLabel']      = $this->property('showDomainLabel');

        // Load data
        $this->loadData();

        // Make the widget
        return $this->makePartial('widget');
    }

    /**
     * Define widget properties
     * @return  array
     */
    public function defineProperties()
    {
        return [
            'title' => [
                'title'             => 'Widget title',
                'default'           => 'Shop Overview',
                'type'              => 'string',
                'validationPattern' => '^.+$',
                'validationMessage' => 'The widget title is required.'
            ],
            'cellSize' => [
                'title'             => 'Cell size',
                'default'           => 15,
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'The cell size must be a positive whole number.'
            ],
            'cellPadding' => [
                'title'             => 'Cell padding',
                'default'           => 2,
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'The cell padding must be a positive whole number.'
            ],
            'domain' => [
                'title'             => 'Domain',
                'default'           => 'week',
                'type'              => 'dropdown',
                'options' => [
                    'hour' => 'Hour',
                    'day' => 'Day',
                    'week' => 'Week',
                    'month' => 'Month',
                    'year' => 'Year'
                ]
            ],
            'subDomain' => [
                'title'             => 'Sub domain',
                'default'           => 'day',
                'type'              => 'dropdown',
                'options' => [
                    'min' => 'Min',
                    'x_min' => 'Min (rotated)',
                    'hour' => 'Hour',
                    'x_hour' => 'Hour (rotated)',
                    'day' => 'Day',
                    'x_day' => 'Day (rotated)',
                    'week' => 'Week',
                    'x_week' => 'Week (rotated)',
                    'month' => 'Month',
                    'x_month' => 'Month (rotated)',
                ]
            ],
            'range' => [
                'title'             => 'Range',
                'default'           => '53',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'The cell size must be a positive whole number.',
                'description'       => 'Number of domains to display'
            ],
            // There is a bug with cal-heatmap, this will not work correctly
            // 'minColor' => [
            //     'title'             => 'Low color',
            //     'default'           => '#dae289',
            //     'type'              => 'string',
            //     'validationPattern' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$',
            //     'validationMessage' => 'The low color must be a hexdecimal color value.'
            // ],
            // 'maxColor' => [
            //     'title'             => 'High color',
            //     'default'           => '#3b6427',
            //     'type'              => 'string',
            //     'validationPattern' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$',
            //     'validationMessage' => 'The high color must be a hexdecimal color value.'
            // ],
            // 'emptyColor' => [
            //     'title'             => 'Empty color',
            //     'default'           => '#fefefe',
            //     'type'              => 'string',
            //     'validationPattern' => '^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$',
            //     'validationMessage' => 'The empty color must be a hexdecimal color value.'
            // ],
            'weekStartOnMonday' => [
                'title'             => 'Week starts on',
                'default'           => 'false',
                'type'              => 'dropdown',
                'options' => [
                    'true' => 'Monday',
                    'false' => 'Sunday'
                ]
            ],
            'showDomainLabel' => [
                'title'                 => 'Show domain label',
                'default'               => false,
                'type'                  => 'checkbox'
            ]
        ];
    }

    /**
     * Calculates the start date for the heatmap
     * @return  string
     */
    private function calculateStart()
    {
        $startRange = $this->property('range') - 1;
        $domain = $this->property('domain');

        if ($domain == 'year') 
            return Carbon::now()->subYears($startRange);
        
        elseif ($domain == 'month') 
            return Carbon::now()->subMonths($startRange);
        
        elseif ($domain == 'week')
            return Carbon::now()->subWeeks($startRange);
        
        elseif ($domain == 'day')
            return Carbon::now()->subDays($startRange);
        
        elseif ($domain == 'hour')
            return Carbon::now()->subHours($startRange);

        return Carbon::now();
    }

    /**
     * Loads and saves data
     */
    private function loadData()
    {
        // Query the data
        $orders = DB::table('bedard_shop_orders')
            ->select(DB::raw('UNIX_TIMESTAMP(created_at) as timestamp'))
            ->where('created_at', '>=', $this->vars['start'])
            ->get();

        $data = [];
        $subDomainData = [];
        $subDomain = $this->vars['subDomain'];
        foreach ($orders as $order) {
            // Build our json file
            $key = $order->timestamp;
            $data[$key] = array_key_exists($key, $data)
                ? $data[$key] + 1
                : 1;

            // Sum the subdomain data
            if ($subDomain == 'month' || $subDomain == 'x_month')
                $subDomainKey = date('Y-m', $key);
            elseif ($subDomain == 'week' || $subDomain == 'x_week')
                $subDomainKey = date('Y-W', $key);
            elseif ($subDomain == 'day' || $subDomain == 'x_day')
                $subDomainKey = date('Y-m-d', $key);
            elseif ($subDomain == 'hour' || $subDomain == 'x_hour')
                $subDomainKey = date('Y-m-d H', $key);
            elseif ($subDomain == 'min' || $subDomain == 'x_min')
                $subDomainKey = date('Y-m-d H:i', $key);

            $subDomainData[$subDomainKey] = array_key_exists($subDomainKey, $subDomainData)
                ? $subDomainData[$subDomainKey] + 1
                : 1;
        }

        // Calculate the legend
        $step = count($subDomainData) > 0
            ? ceil(max($subDomainData) / 5)
            : 1;

        $legend = [];
        for ($i = 1; $i <= 4; $i++)
            $legend[] = $step * $i;
        $this->vars['legend'] = implode(', ', $legend);

        // Save the data
        $file = File::put('plugins/bedard/shop/reportwidgets/heatmap/assets/data/timestamps.json', json_encode($data));
    }

}
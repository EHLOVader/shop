<?php namespace Bedard\Shop;

use Backend;
use System\Classes\PluginBase;

/**
 * Shop Plugin Information File
 */
class Plugin extends PluginBase
{

    /**
     * Returns information about this plugin.
     * @return  array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Shop',
            'description' => 'An ecommerce platform for OctoberCMS.',
            'author'      => 'Scott Bedard',
            'icon'        => 'icon-shopping-cart'
        ];
    }

    /**
     * Returns backend navigation
     * @return  array
     */
    public function registerNavigation()
    {
        return [
            'shop' => [
                'label'         => 'Shop',
                'url'           => Backend::url('bedard/shop/products'),
                'icon'          => 'icon-shopping-cart',
                'permissions'   => ['bedard.shop.*'],
                'order'         => 500,

                'sideMenu' => [
                    // 'transactions' => [
                    //     'label'         => 'Orders',
                    //     'icon'          => 'icon-clipboard',
                    //     'url'           => Backend::url('bedard/shop/transactions'),
                    //     'permissions'   => ['bedard.shop.access_transactions']
                    // ],
                    // 'customers' => [
                    //     'label'         => 'Customers',
                    //     'icon'          => 'icon-users',
                    //     'url'           => Backend::url('bedard/shop/customers'),
                    //     'permissions'   => ['bedard.shop.access_customers']
                    // ],
                    'products' => [
                        'label'         => 'Products',
                        'icon'          => 'icon-cubes',
                        'url'           => Backend::url('bedard/shop/products'),
                        'permissions'   => ['bedard.shop.access_products']
                    ],
                    'categories' => [
                        'label'         => 'Categories',
                        'icon'          => 'icon-folder-o',
                        'url'           => Backend::url('bedard/shop/categories'),
                        'permissions'   => ['bedard.shop.access_categories']
                    ],
                    'discounts' => [
                        'label'         => 'Discounts',
                        'icon'          => 'icon-clock-o',
                        'url'           => Backend::url('bedard/shop/discounts'),
                        'permissions'   => ['bedard.shop.access_discounts']
                    ],
                    'codes' => [
                        'label'         => 'Codes',
                        'icon'          => 'icon-code',
                        'url'           => Backend::url('bedard/shop/codes'),
                        'permissions'   => ['bedard.shop.access_codes']
                    ],
                    // 'emails' => [
                    //     'label'         => 'Email',
                    //     'icon'          => 'icon-envelope-o',
                    //     'url'           => Backend::url('bedard/shop/emails'),
                    //     'permissions'   => ['bedard.shop.access_email']
                    // ],
                    'settings' => [
                        'label'         => 'Settings',
                        'icon'          => 'icon-cog',
                        'url'           => Backend::url('system/settings/update/bedard/shop/settings'),
                        'permissions'   => ['bedard.shop.access_settings']
                    ]
                ]
            ]
        ];
    }

    /**
     * Returns form widgets
     * @return  array
     */
    public function registerFormWidgets()
    {
        return [
            'Bedard\Shop\Widgets\Arrangement' => [
                'label' => 'Product Arrangement',
                'code'  => 'arrangement'
            ]
        ];
    }

    /**
     * Registers plugin settings
     * @return  array
     */
    public function registerSettings()
    {
        return [
            'settings' => [
                'label'         => 'Settings',
                'category'      => 'Shop',
                'icon'          => 'icon-shopping-cart',
                'description'   => 'Configure general shop settings.',
                'class'         => 'Bedard\Shop\Models\Settings',
                'order'         => 100,
                'keywords'      => 'shop'
            ],
            'paysettings' => [
                'label'         => 'Payment Settings',
                'category'      => 'Shop',
                'icon'          => 'icon-money',
                'description'   => 'Configure payment settings.',
                'class'         => 'Bedard\Shop\Models\PaySettings',
                'order'         => 200,
                'keywords'      => 'shop'
            ]
        ];
    }

    /**
     * Register plugin components
     * @return  array
     */
    public function registerComponents()
    {
        return [
            'Bedard\Shop\Components\CategoriesList' => 'categoriesList',
            'Bedard\Shop\Components\Category'       => 'category'
        ];
    }
}

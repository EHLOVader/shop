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
            'description' => 'A feature rich ecommerce platform.',
            'author'      => 'Scott Bedard',
            'icon'        => 'icon-cart'
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
                'url'           => Backend::url('bedard/shop/categories'),
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
                    // 'promotions' => [
                    //     'label'         => 'Promotions',
                    //     'icon'          => 'icon-code',
                    //     'url'           => Backend::url('bedard/shop/promotions'),
                    //     'permissions'   => ['bedard.shop.access_promotions']
                    // ],
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
}

<?php namespace Bedard\Shop\Models;

use Model;

/**
 * Settings Model
 */
class PaySettings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'bedard_shop_paysettings';

    public $settingsFields = 'fields.yaml';
}
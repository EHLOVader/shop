<?php namespace Bedard\Shop\Models;

use Model;

/**
 * Settings Model
 */
class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'bedard_shop_settings';

    public $settingsFields = 'fields.yaml';
}
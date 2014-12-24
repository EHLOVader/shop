<?php namespace Bedard\Shop\Models;

use Flash;
use Model;

/**
 * Category Model
 */
class Category extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'bedard_shop_categories';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    /*
     * Validation
     */
    public $rules = [
        'name' => 'required',
        'slug' => 'required|between:3,64|unique:bedard_shop_categories|regex:/^[a-z0-9\-]+$/i'
    ];

    /**
     * Prevent pseudo categories from being deleted
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($model) {
            if ($model->pseudo) {
                $model->is_active = 0;
                $model->save();
                Flash::warning('Pseudo categories cannot be deleted, '.$model->name.' has been set to inactive.');
                return FALSE;
            }
        });
    }

    /**
     * Query Scopes
     */
    public function scopeDefaultOrder($query)
    {
        $query->orderBy('is_active', 'desc')
              ->orderBy('is_visible', 'desc')
              ->orderBy('position', 'asc');
    }

    /**
     * Returns the number of products the category contains
     */
    public function getProductCountAttribute()
    {
        return 0;
    }

    
}
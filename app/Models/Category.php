<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $primaryKey = 'category_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['category_id', 'name', 'description'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->category_id) {
                $prefix = 'CT';
                $last = static::where('category_id', 'like', $prefix . '%')
                    ->orderBy('category_id', 'desc')
                    ->first();

                $number = $last ? intval(substr($last->category_id, strlen($prefix))) + 1 : 1;
                $model->category_id = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'category_id');
    }
}
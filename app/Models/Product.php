<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';
    protected $primaryKey = 'product_id';
    public $incrementing = false; // Karena menggunakan custom ID
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'description',
        'selling_price',
        'capital_price',
        'category_id',
        'stock',
        'image_data',
    ];

    protected $dates = ['deleted_at'];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'capital_price' => 'decimal:2',
        'stock' => 'integer',
    ];

    // Generate unique product ID
    public static function generateProductId()
    {
        do {
            $lastProduct = self::withTrashed()
                ->where('product_id', 'like', 'PR%')
                ->orderBy('product_id', 'desc')
                ->first();
            
            if ($lastProduct) {
                $lastNumber = intval(substr($lastProduct->product_id, 2));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            
            $newId = 'PR' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
            
            // Check if ID already exists (including soft deleted)
            $exists = self::withTrashed()->where('product_id', $newId)->exists();
            
        } while ($exists);
        
        return $newId;
    }

    // Override create method to auto-generate ID
    public static function create(array $attributes = [])
    {
        if (!isset($attributes['product_id'])) {
            $attributes['product_id'] = self::generateProductId();
        }
        
        return static::query()->create($attributes);
    }

    // Relationship dengan Category
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    // Accessor untuk image URL (base64 data URL)
    public function getImageUrlAttribute()
    {
        if ($this->image_data) {
            return $this->image_data; // Return base64 data URL directly
        }
        return null;
    }
protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        if (empty($model->product_id)) {
            $model->product_id = self::generateProductId();
        }
    });
}
    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Transaction;
use App\Models\Product;

class TransactionItem extends Model
{
    use HasFactory;

    protected $primaryKey = 'item_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'item_id',
        'transaction_id',
        'product_id',
        'quantity',
        'selling_price'
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->item_id) {
                $prefix = 'TI';
                $last = static::where('item_id', 'like', $prefix . '%')
                    ->orderBy('item_id', 'desc')
                    ->first();

                $number = 1;
                if ($last) {
                    $number = intval(substr($last->item_id, strlen($prefix))) + 1;
                }

                $model->item_id = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}

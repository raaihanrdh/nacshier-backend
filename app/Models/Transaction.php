<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cashflow;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $primaryKey = 'transaction_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'shift_id',
        'total_amount',
        'transaction_time',
        'payment_method',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'transaction_time' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id', 'transaction_id');
    }

    public function cashflow()
    {
        return $this->hasOne(Cashflow::class, 'transaction_id', 'transaction_id');
    }

    public function cashierShift()
    {
        return $this->belongsTo(CashierShift::class, 'shift_id', 'shift_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate transaction ID only if not provided
            if (empty($model->transaction_id)) {
                $model->transaction_id = static::generateTransactionId();
            }
        });
    }

    /**
     * Generate a new transaction ID
     * 
     * @return string
     */
 public static function generateTransactionId(): string
{
    $prefix = 'TRX';
    $lastTransaction = static::orderBy('transaction_id', 'desc')->first();
    
    if (!$lastTransaction) {
        return $prefix . str_pad(1, 5, '0', STR_PAD_LEFT); // TRX00001
    }
    
    // Extract number from last ID
    $lastNumber = (int) substr($lastTransaction->transaction_id, strlen($prefix));
    $newNumber = $lastNumber + 1;
    
    return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
}
}
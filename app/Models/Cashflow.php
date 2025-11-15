<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cashflow extends Model
{
    use HasFactory;

    protected $primaryKey = 'cashflow_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'cashflow_id',
        'transaction_id',
        'date',
        'type',
        'amount',
        'category',
        'description',
        'method',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'integer',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
    }

    // Auto generate ID seperti CF000001, CF000002, dst
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->cashflow_id) {
                $prefix = 'CF';
                $last = static::where('cashflow_id', 'like', $prefix . '%')
                    ->orderBy('cashflow_id', 'desc')
                    ->first();

                $number = 1;
                if ($last) {
                    $number = intval(substr($last->cashflow_id, strlen($prefix))) + 1;
                }

                $model->cashflow_id = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
            }
        });
    }
}

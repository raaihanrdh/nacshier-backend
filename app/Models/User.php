<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $primaryKey = 'user_id';
    
    public $incrementing = false; // Karena kita menggunakan UUID atau custom ID
    protected $keyType = 'string'; // Tipe data primary key

    protected $fillable = [
        'name',
        'username',
        'password',
        'level',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate user_id jika belum ada
            if (empty($model->user_id)) {
                $model->user_id = self::generateUserId();
            }
        });
    }

    /**
     * Generate custom user ID
     * Format: USR-YYYYMMDD-XXXXXX
     */
    public static function generateUserId()
    {

        $randomPart = strtoupper(Str::random(5));
        
        $userId = 'USR' . $randomPart;
        
        // Pastikan ID unik
        while (self::where('user_id', $userId)->exists()) {
            $randomPart = strtoupper(Str::random(5));
            $userId = 'USR' . $randomPart;
        }
        
        return $userId;
    }

    public function cashierShifts()
    {
        return $this->hasMany(CashierShift::class);
    }
}
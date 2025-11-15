<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CashierShift extends Model
{
    use HasFactory;

    protected $primaryKey = 'shift_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    // Auto-generate shift_id saat record dibuat
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->shift_id) {
                $prefix = 'SF';
                $lastId = static::where('shift_id', 'like', $prefix . '%')
                    ->orderBy('shift_id', 'desc')
                    ->first();

                if ($lastId) {
                    $number = intval(substr($lastId->shift_id, strlen($prefix))) + 1;
                } else {
                    $number = 1;
                }

                $model->shift_id = $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'shift_id', 'shift_id');
    }

    // Scopes untuk query yang sering dipakai
    public function scopeActive($query)
    {
        return $query->whereNull('end_time');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('end_time');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('start_time', Carbon::today());
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper methods
    public function isActive()
    {
        return is_null($this->end_time);
    }

    public function isCompleted()
    {
        return !is_null($this->end_time);
    }

    public function getDuration()
    {
        if ($this->isActive()) {
            return $this->start_time->diffInMinutes(Carbon::now());
        }
        
        return $this->start_time->diffInMinutes($this->end_time);
    }

    public function getDurationFormatted()
    {
        $minutes = $this->getDuration();
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        return sprintf('%d jam %d menit', $hours, $mins);
    }

    public function getTotalSales()
    {
        return $this->transactions()->sum('total_amount');
    }

    public function getTotalTransactions()
    {
        return $this->transactions()->count();
    }

    // Method untuk clock out
    public function clockOut()
    {
        if ($this->isActive()) {
            $this->update(['end_time' => Carbon::now()]);
            return true;
        }
        return false;
    }

    // Method untuk mendapatkan shift aktif user
    public static function getActiveShift($userId)
    {
        return static::where('user_id', $userId)
            ->whereNull('end_time')
            ->first();
    }

    // Method untuk start shift baru
    public static function startShift($userId)
    {
        // Cek apakah ada shift aktif
        $activeShift = static::getActiveShift($userId);
        
        if ($activeShift) {
            throw new \Exception('User masih memiliki shift aktif');
        }

        return static::create([
            'user_id' => $userId,
            'start_time' => Carbon::now(),
        ]);
    }

    // Accessor untuk format tanggal yang lebih readable
    public function getStartTimeFormattedAttribute()
    {
        return $this->start_time->format('d/m/Y H:i:s');
    }

    public function getEndTimeFormattedAttribute()
    {
        return $this->end_time ? $this->end_time->format('d/m/Y H:i:s') : '-';
    }

    public function getDateAttribute()
    {
        return $this->start_time->format('d/m/Y');
    }
}
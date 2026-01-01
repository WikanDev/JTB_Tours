<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkSchedule extends Model
{
    protected $fillable = [
        'user_id',
        'month',
        'year',
        'total_hours',
        'used_hours',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'total_hours' => 'integer',
        'used_hours' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    
    public function remainingHours(): int
    {
        return max(0, $this->total_hours - $this->used_hours);
    }

    
    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }
}

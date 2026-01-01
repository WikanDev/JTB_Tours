<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'brand',
        'type',
        'plate_number',
        'color',
        'status',
        'year',
        'capacity',
    ];

    protected $casts = [
        'year' => 'integer',
        'capacity' => 'integer',
    ];

    
    public function scopeAvailable($query, $start = null, $durationMinutes = 0)
    {
        if (!$start) {
            return $query->where('status', 'available');
        }

        $startTime = \Carbon\Carbon::parse($start);
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        
        
        return $query->where('status', '!=', 'maintenance')
                     ->whereDoesntHave('assignments', function ($q) use ($startTime, $endTime) {
            $q->whereIn('status', ['accepted', 'in_progress'])
              ->whereHas('order', function ($orderQ) use ($startTime, $endTime) {
                  $orderQ->where(function ($sub) use ($startTime, $endTime) {
                       
                       $sub->where('pickup_time', '<=', $endTime)
                           ->whereRaw("DATE_ADD(pickup_time, INTERVAL estimated_duration_minutes MINUTE) >= ?", [$startTime]);
                  });
              });
        });
    }

    public function isAvailableAt($start, $durationMinutes)
    {
        $startTime = \Carbon\Carbon::parse($start);
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        $conflicts = $this->assignments()
            ->whereIn('status', ['accepted', 'in_progress'])
            ->whereHas('order', function ($q) use ($startTime, $endTime) {
                
                 
                
                $q->where(function($sub) use ($startTime, $endTime) {
                    
                    
                    
                    
                });
            })->count();
            
        
        
        
        
        $conflicting = $this->assignments()
            ->whereIn('status', ['accepted', 'in_progress'])
            ->get()
            ->filter(function($assignment) use ($startTime, $endTime) {
                if (!$assignment->order) return false;
                
                $orderStart = $assignment->order->pickup_time;
                $orderEnd = $orderStart->copy()->addMinutes($assignment->order->estimated_duration_minutes);
                
                return $startTime->lt($orderEnd) && $endTime->gt($orderStart);
            });

        return $conflicting->isEmpty();
    }

    public function getCurrentDriver()
    {
        if ($this->status !== 'in_use') return null;

        return $this->assignments()
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->first()
            ->driver ?? null;
    }

    public function getUsageHistory()
    {
        return $this->assignments()
            ->with(['driver', 'order'])
            ->latest('assigned_at')
            ->get();
    }

    public function assignments() {
        return $this->hasMany(\App\Models\Assignment::class, 'vehicle_id');
    }
    public function orders() {
        return $this->belongsToMany(\App\Models\Order::class);
    }
    
}

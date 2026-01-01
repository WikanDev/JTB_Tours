<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'role',
        'email',
        'phone',
        'profile_photo',
        'join_date',
        'password',
        'monthly_work_limit',
        'used_hours',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'join_date' => 'date',
        'monthly_work_limit' => 'integer',
        'used_hours' => 'integer',
    ];

    
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => Hash::needsRehash($value) ? Hash::make($value) : $value
        );
    }

    
    public function assignmentsAsDriver(): HasMany
    {
        return $this->hasMany(Assignment::class, 'driver_id');
    }

    public function assignmentsAsGuide(): HasMany
    {
        return $this->hasMany(Assignment::class, 'guide_id');
    }

    public function workSchedules(): HasMany
    {
        return $this->hasMany(WorkSchedule::class);
    }

    public function ordersCreated(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function isGuide(): bool
    {
        return $this->role === 'guide';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'description',
        "hour",
        
        'is_exclusive',
        'snack',
        'water',
        'magazine',
        'custom_exclusive_benefits',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_exclusive' => 'boolean',
        'snack' => 'boolean',
        'water' => 'boolean',
        'magazine' => 'boolean',
        'custom_exclusive_benefits' => 'array',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(ProductBranch::class);
    }
}

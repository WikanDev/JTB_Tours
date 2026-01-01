<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBranch extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'origin_region',
        'destination_region',
        'duration_minutes',
        'price',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    
    public function getDurationHumanAttribute()
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        $parts = [];
        if ($hours > 0) $parts[] = "{$hours} Jam";
        if ($minutes > 0) $parts[] = "{$minutes} Menit";
        
        return implode(' ', $parts);
    }
}

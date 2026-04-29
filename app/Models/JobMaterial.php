<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobMaterial extends Model
{
    protected $fillable = [
        'job_schedule_id',
        'product_id',
        'quantity',
        'issued',
        'received',
        'returned',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'issued' => 'integer',
        'received' => 'integer',
        'returned' => 'integer'
    ];

    /**
     * Get the job schedule that owns the material.
     */
    public function jobSchedule(): BelongsTo
    {
        return $this->belongsTo(JobSchedule::class);
    }

    /**
     * Get the product for this material.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the remaining quantity (issued - received - returned).
     */
    public function getRemainingAttribute(): int
    {
        return $this->issued - $this->received - $this->returned;
    }

    /**
     * Get the usage percentage.
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->issued == 0) {
            return 0;
        }
        return ($this->received / $this->issued) * 100;
    }

    /**
     * Scope for materials with remaining quantity.
     */
    public function scopeWithRemaining($query)
    {
        return $query->whereRaw('issued > (received + returned)');
    }

    /**
     * Scope for fully used materials.
     */
    public function scopeFullyUsed($query)
    {
        return $query->whereRaw('issued = (received + returned)');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Http\Traits\AutoFilterable;

class InventoryReceiving extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'receiving_number',
        'reference_no',
        'issuing_id',
        'branch_id',
        'received_from',
        'received_by_old',
        'receive_date',
        'schedule_date',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'receive_date' => 'date',
        'schedule_date' => 'date',
    ];

    /**
     * Get the branch that owns the inventory receiving.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the inventory issuing that this receiving is based on.
     */
    public function issuing(): BelongsTo
    {
        return $this->belongsTo(InventoryIssuing::class, 'issuing_id');
    }

    /**
     * Get the user who sent the inventory (received_from).
     */
    public function receivedFrom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_from');
    }

    /**
     * Get the user who received the inventory.
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_old');
    }

    /**
     * Get the user who created the inventory receiving.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the inventory receiving.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get branch name with fallback to issuing branch or warehouse branch.
     */
    public function getBranchNameAttribute()
    {
        if ($this->branch) {
            return $this->branch->name;
        }
        if ($this->issuing && $this->issuing->branch) {
            return $this->issuing->branch->name;
        }
        if ($this->issuing && $this->issuing->warehouse && $this->issuing->warehouse->branch) {
            return $this->issuing->warehouse->branch->name;
        }
        return 'N/A';
    }

    /**
     * Get the inventory receiving items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryReceivingItem::class);
    }

    public function beritaAcara()
    {
        return $this->hasMany(BeritaAcara::class);
    }

    /**
     * Scope a query to only include pending receivings.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include received receivings.
     */
    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    /**
     * Scope a query to only include cancelled receivings.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Get the total quantity of items in this receiving.
     */
    public function getTotalQuantityAttribute(): int
    {
        return 0; // Disabled to prevent N+1 queries
    }

    /**
     * Get the total value of items in this receiving.
     */
    public function getTotalValueAttribute(): float
    {
        return 0; // Disabled to prevent N+1 queries
    }

    /**
     * Check if the receiving can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the receiving can be received.
     */
    public function canBeReceived(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the receiving can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status === 'pending';
    }
}

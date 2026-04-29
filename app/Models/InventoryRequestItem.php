<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_request_id',
        'master_product_id',
        'quantity',
        'approved_qty',
        'issued_qty',
        'received_qty',
        'returned_qty',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'approved_qty' => 'decimal:2',
        'issued_qty' => 'decimal:2',
        'received_qty' => 'decimal:2',
        'returned_qty' => 'decimal:2',
    ];

    /**
     * Get the inventory request that owns the item.
     */
    public function inventoryRequest(): BelongsTo
    {
        return $this->belongsTo(InventoryRequest::class);
    }

    /**
     * Get the product that owns the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class, 'master_product_id');
    }

    /**
     * Get the user who approved the item.
     */
    // removed approvedBy relationship to align with current DB schema

    /**
     * Scope a query to only include pending items.
     */
    // status-related scopes removed to align with current DB schema

    /**
     * Scope a query to only include approved items.
     */

    /**
     * Scope a query to only include rejected items.
     */

    /**
     * Scope a query to only include completed items.
     */

    /**
     * Check if the item can be edited.
     */
    // capability checks removed (no status column in DB)

    /**
     * Check if the item can be approved.
     */

    /**
     * Check if the item can be rejected.
     */

    /**
     * Check if the item can be completed.
     */

    /**
     * Get status badge class.
     */
    // status badge accessor removed (no status column)

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

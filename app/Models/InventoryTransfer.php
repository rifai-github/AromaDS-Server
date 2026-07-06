<?php

namespace App\Models;

use App\Http\Traits\AutoFilterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryTransfer extends Model
{
    use AutoFilterable, HasFactory, SoftDeletes;

    protected $table = 'inventory_transfers';

    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'transfer_date',
        'status',
        'is_direct_branch_transfer',
        'delivery_order_file',
        'central_approved_by',
        'central_approved_at',
        'central_approval_notes',
        'notes',
        'source_type',
        'source_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'is_direct_branch_transfer' => 'boolean',
        'central_approved_at' => 'datetime',
    ];

    // Relationships
    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Alias expected by InventoryController::index() eager-load and the index blade view.
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function transferItems()
    {
        return $this->hasMany(InventoryTransferItem::class);
    }

    /**
     * The MaterialReturn that auto-created this transfer, when this transfer
     * originated from a branch -> center material return (source_type = 'material_return').
     * Gate reads with isFromMaterialReturn() since source_id is polymorphic.
     */
    public function sourceMaterialReturn()
    {
        return $this->belongsTo(MaterialReturn::class, 'source_id');
    }

    public function isFromMaterialReturn()
    {
        return $this->source_type === 'material_return';
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where(function ($q) use ($warehouseId) {
            $q->where('from_warehouse_id', $warehouseId)
                ->orWhere('to_warehouse_id', $warehouseId);
        });
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transfer_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return ucfirst(str_replace('-', ' ', $this->status));
    }

    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            'received' => 'bg-green-100 text-green-800',
            'draft' => 'bg-yellow-100 text-yellow-800',
            'transferred' => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    // Boot method to auto-generate transfer number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transfer) {
            if (empty($transfer->transfer_number)) {
                $transfer->transfer_number = static::generateTransferNumber();
            }
        });
    }

    public static function generateTransferNumber()
    {
        $prefix = 'TR';
        $date = now()->format('Ymd');

        // Get the last transfer number for today (including soft deleted ones)
        $lastTransfer = static::withTrashed()
            ->whereDate('created_at', now()->toDateString())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastTransfer) {
            // Extract sequence number from transfer_number
            $lastSequence = (int) substr($lastTransfer->transfer_number, -4);
            $sequence = $lastSequence + 1;
        }

        // Ensure uniqueness by checking if the generated number already exists
        do {
            $transferNumber = $prefix.'-'.$date.'-'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $exists = static::withTrashed()->where('transfer_number', $transferNumber)->exists();
            if ($exists) {
                $sequence++;
            }
        } while ($exists);

        return $transferNumber;
    }
}

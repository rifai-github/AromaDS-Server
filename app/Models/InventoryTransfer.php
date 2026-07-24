<?php

namespace App\Models;

use App\Http\Traits\AutoFilterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryTransfer extends Model
{
    use AutoFilterable, HasFactory, SoftDeletes;

    public const MARK_TRANSFERRED_PERMISSIONS = [
        'warehouse.inventory-transfers.transfer',
        'warehouse.inventory-transfers.transfer.create',
        'warehouse.inventory-transfers.transfer.approve',
    ];

    public const MARK_RECEIVED_PERMISSIONS = [
        'warehouse.inventory-transfers.receive',
        'warehouse.inventory-transfers.receive.create',
        'warehouse.inventory-transfers.receive.approve',
    ];

    protected $table = 'inventory_transfers';

    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'transfer_date',
        'status',
        'approval_status',
        'is_direct_branch_transfer',
        'delivery_order_file',
        'delivery_order_uploaded_by',
        'delivery_order_uploaded_at',
        'central_approved_by',
        'central_approved_at',
        'central_approval_notes',
        'submitted_for_approval_by',
        'submitted_for_approval_at',
        'central_rejected_by',
        'central_rejected_at',
        'central_rejection_reason',
        'submission_letter_file',
        'submission_letter_uploaded_by',
        'submission_letter_uploaded_at',
        'delivery_note_file',
        'delivery_note_uploaded_by',
        'delivery_note_uploaded_at',
        'notes',
        'return_reason',
        'return_reason_category',
        'source_type',
        'source_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'is_direct_branch_transfer' => 'boolean',
        'delivery_order_uploaded_at' => 'datetime',
        'central_approved_at' => 'datetime',
        'submitted_for_approval_at' => 'datetime',
        'central_rejected_at' => 'datetime',
        'submission_letter_uploaded_at' => 'datetime',
        'delivery_note_uploaded_at' => 'datetime',
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

    public function approvalHistories()
    {
        return $this->hasMany(InventoryTransferApprovalHistory::class)->latest('id');
    }

    public function centralApprover()
    {
        return $this->belongsTo(User::class, 'central_approved_by');
    }

    public function approvalSubmitter()
    {
        return $this->belongsTo(User::class, 'submitted_for_approval_by');
    }

    public function centralRejector()
    {
        return $this->belongsTo(User::class, 'central_rejected_by');
    }

    public function requiresCentralApproval(): bool
    {
        return (bool) $this->is_direct_branch_transfer;
    }

    public function userCanMarkTransferredFromSource(?User $user): bool
    {
        return $this->userCanActForWarehouse($user, $this->fromWarehouse);
    }

    public function userCanMarkReceivedAtDestination(?User $user): bool
    {
        return $this->userCanActForWarehouse($user, $this->toWarehouse);
    }

    private function userCanActForWarehouse(?User $user, ?Warehouse $warehouse): bool
    {
        if (! $user || ! $warehouse) {
            return false;
        }

        if ($this->userHasTransferLocationOverride($user)) {
            return true;
        }

        if ($warehouse->manager && (int) $warehouse->manager === (int) $user->id) {
            return true;
        }

        if ($warehouse->branch_id && $user->branch_id && (int) $warehouse->branch_id === (int) $user->branch_id) {
            return true;
        }

        if ($warehouse->branch_id && $this->userAssignedToBranch($user, (int) $warehouse->branch_id)) {
            return true;
        }

        return $this->userAssignedAsWarehouseAdmin($user, (int) $warehouse->id);
    }

    private function userHasTransferLocationOverride(User $user): bool
    {
        try {
            if (
                $user->hasRole('Admin')
                || $user->hasRole('super_admin')
                || $user->hasRoleStartingWith('Management')
                || $user->hasRole('Warehouse Pusat Manager')
            ) {
                return true;
            }
        } catch (\Throwable) {
            // Some focused tests do not create the role pivot tables. Fall back
            // to the raw roles column / location data below.
        }

        foreach ($this->extractUserRoleNames($user) as $roleName) {
            $normalizedRoleName = strtolower($roleName);

            if (
                str_contains($normalizedRoleName, 'admin')
                || str_starts_with($normalizedRoleName, 'management')
                || str_contains($normalizedRoleName, 'pusat')
                || str_contains($normalizedRoleName, 'central')
            ) {
                return true;
            }
        }

        try {
            return Schema::hasTable('warehouses')
                && Schema::hasColumn('warehouses', 'manager')
                && Schema::hasColumn('warehouses', 'is_center')
                && Warehouse::query()
                    ->where('manager', $user->id)
                    ->where('is_center', true)
                    ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private function extractUserRoleNames(User $user): array
    {
        $rolesColumn = method_exists($user, 'getRolesColumnValue')
            ? $user->getRolesColumnValue()
            : ($user->getAttributes()['roles'] ?? null);

        if (! is_string($rolesColumn) || trim($rolesColumn) === '') {
            return [];
        }

        $decoded = json_decode($rolesColumn, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            if (isset($decoded['name'])) {
                return [(string) $decoded['name']];
            }

            return array_values(array_filter(array_map(
                static fn ($role) => is_array($role) ? ($role['name'] ?? null) : null,
                $decoded
            )));
        }

        return [$rolesColumn];
    }

    private function userAssignedToBranch(User $user, int $branchId): bool
    {
        try {
            return Schema::hasTable('branch_user')
                && $user->assignedBranches()->whereKey($branchId)->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private function userAssignedAsWarehouseAdmin(User $user, int $warehouseId): bool
    {
        try {
            return Schema::hasTable('warehouse_admins')
                && DB::table('warehouse_admins')
                    ->where('user_id', $user->id)
                    ->where('warehouse_id', $warehouseId)
                    ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public function getApprovalStatusTextAttribute(): string
    {
        return match ($this->approval_status) {
            'not_required' => 'Tidak diperlukan',
            'pending' => 'Menunggu approval pusat',
            'approved' => 'Disetujui pusat',
            'rejected' => 'Ditolak pusat',
            default => 'Draft approval',
        };
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

    // Surat pengajuan (branch's submission letter requesting the goods leave the branch).
    public function submissionLetterUploader()
    {
        return $this->belongsTo(User::class, 'submission_letter_uploaded_by');
    }

    // Surat jalan (center's dispatch/acknowledgement document for the branch).
    public function deliveryNoteUploader()
    {
        return $this->belongsTo(User::class, 'delivery_note_uploaded_by');
    }

    public function deliveryOrderUploader()
    {
        return $this->belongsTo(User::class, 'delivery_order_uploaded_by');
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

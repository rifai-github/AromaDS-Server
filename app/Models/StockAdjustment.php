<?php

namespace App\Models;

use App\Http\Traits\AutoFilterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class StockAdjustment extends Model
{
    use AutoFilterable, HasFactory, SoftDeletes;

    protected $fillable = [
        'adjustment_no',
        'warehouse_id',
        'reason',
        'notes',
        'adjustment_date',
        'approved_by',
        'approved_at',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'adjustment_qty' => 'integer',
        'adjustment_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function masterProduct()
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    // Scopes
    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByMasterProduct($query, $masterProductId)
    {
        return $query->where('master_product_id', $masterProductId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('adjustment_type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('adjustment_date', [$startDate, $endDate]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'waiting for approval');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByApprover($query, $approverId)
    {
        return $query->where('approved_by', $approverId);
    }

    public function scopeByCreator($query, $creatorId)
    {
        return $query->where('created_by', $creatorId);
    }

    public function scopeIncrease($query)
    {
        return $query->where('adjustment_type', 'increase');
    }

    public function scopeDecrease($query)
    {
        return $query->where('adjustment_type', 'decrease');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return ucfirst($this->status);
    }

    public function getAdjustmentTypeTextAttribute()
    {
        return ucfirst($this->adjustment_type);
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    public function getIsApprovedAttribute()
    {
        return $this->status === 'approved';
    }

    public function getIsRejectedAttribute()
    {
        return $this->status === 'rejected';
    }

    public function getIsIncreaseAttribute()
    {
        return $this->adjustment_type === 'increase';
    }

    public function getIsDecreaseAttribute()
    {
        return $this->adjustment_type === 'decrease';
    }

    public function getFormattedAdjustmentTotalsAttribute()
    {
        $increase = $this->items()->where('adjustment_type', 'increase')->sum('adjustment_qty');
        $decrease = $this->items()->where('adjustment_type', 'decrease')->sum('adjustment_qty');

        return [
            'increase' => number_format($increase, 0, ',', '.'),
            'decrease' => number_format($decrease, 0, ',', '.'),
        ];
    }

    // Business Logic Methods
    public function approve($userId)
    {
        $this->loadMissing(['items.masterProduct.productCategory', 'items.masterProduct.productType']);

        foreach ($this->items as $item) {
            $this->validateSerialNumbersForItem($item);
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        foreach ($this->items as $item) {
            // Update Inventory
            $warehouseProduct = WarehouseProduct::firstOrCreate(
                [
                    'warehouse_id' => $this->warehouse_id,
                    'master_product_id' => $item->master_product_id,
                ],
                [
                    'quantity' => 0,
                    'minimum_stock' => 0,
                    'maximum_stock' => 0,
                    'created_by' => $userId,
                ]
            );

            if ($item->adjustment_type === 'increase') {
                $warehouseProduct->increment('quantity', $item->adjustment_qty);
                $movementType = 'in';
            } else {
                $warehouseProduct->decrement('quantity', $item->adjustment_qty);
                $movementType = 'out';
            }

            $this->applySerialNumbersForItem($item, $userId);

            $serialNumbers = $this->normalizeSerialNumbers($item->serial_numbers ?? []);
            $serialNotes = empty($serialNumbers) ? '' : ' SN: '.implode(', ', $serialNumbers);

            // Create Inventory Movement
            InventoryMovement::create([
                'warehouse_id' => $this->warehouse_id,
                'master_product_id' => $item->master_product_id,
                'movement_type' => $movementType,
                'movement_no' => $this->adjustment_no,
                'movement_date' => $this->adjustment_date ?? now(),
                'quantity' => $item->adjustment_qty,
                'reference_no' => $this->adjustment_no,
                'reference_type' => 'Stock Adjustment',
                'notes' => $this->reason.($item->notes ? ' - '.$item->notes : '').$serialNotes,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }

    private function validateSerialNumbersForItem(StockAdjustmentItem $item): void
    {
        $product = $item->masterProduct;
        if (! $product?->requiresSerialNumber()) {
            return;
        }

        $quantity = (int) $item->adjustment_qty;
        $serialNumbers = $this->normalizeSerialNumbers($item->serial_numbers ?? []);

        if ($quantity < 1 || count($serialNumbers) !== $quantity) {
            throw ValidationException::withMessages([
                'serial_numbers' => "Produk {$product->name} wajib memiliki {$quantity} serial number untuk stock adjustment.",
            ]);
        }

        if ($product->requiresUniqueSerialNumber()
            && count($serialNumbers) !== count(array_unique($serialNumbers))) {
            throw ValidationException::withMessages([
                'serial_numbers' => "Serial number untuk produk {$product->name} tidak boleh duplikat.",
            ]);
        }

        if ($item->adjustment_type === 'increase') {
            if ($product->requiresUniqueSerialNumber()) {
                $existing = SerialNumber::withTrashed()
                    ->whereIn('serial_number', $serialNumbers)
                    ->pluck('serial_number')
                    ->unique()
                    ->values();

                if ($existing->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'serial_numbers' => 'Serial number sudah terdaftar: '.$existing->implode(', '),
                    ]);
                }
            }

            return;
        }

        $availableCounts = $this->availableWarehouseSerialNumbersQuery($item, $serialNumbers)
            ->pluck('serial_number')
            ->map(fn ($serialNumber) => strtoupper(trim((string) $serialNumber)))
            ->countBy();

        $missing = collect($serialNumbers)
            ->countBy()
            ->filter(fn ($requestedCount, $serialNumber) => ($availableCounts[$serialNumber] ?? 0) < $requestedCount)
            ->keys()
            ->values();
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'serial_numbers' => 'Serial number tidak tersedia di warehouse ini: '.$missing->implode(', '),
            ]);
        }
    }

    private function applySerialNumbersForItem(StockAdjustmentItem $item, int $userId): void
    {
        $product = $item->masterProduct;
        if (! $product?->requiresSerialNumber()) {
            return;
        }

        $serialNumbers = $this->normalizeSerialNumbers($item->serial_numbers ?? []);

        if ($item->adjustment_type === 'increase') {
            foreach ($serialNumbers as $serialNumber) {
                SerialNumber::create([
                    'serial_number' => $serialNumber,
                    'status' => 'ready',
                    'condition_status' => SerialNumber::CONDITION_NEW,
                    'location_type' => 'warehouse',
                    'location_id' => $this->warehouse_id,
                    'warehouse_id' => $this->warehouse_id,
                    'master_product_id' => $item->master_product_id,
                    'notes' => trim("Created from Stock Adjustment {$this->adjustment_no}. ".($item->notes ?? '')),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            return;
        }

        collect($serialNumbers)
            ->countBy()
            ->each(function (int $count, string $requestedSerialNumber) use ($item, $userId) {
                $this->availableWarehouseSerialNumbersQuery($item, [$requestedSerialNumber])
                    ->lockForUpdate()
                    ->orderBy('id')
                    ->limit($count)
                    ->get()
                    ->each(function (SerialNumber $serialNumber) use ($item, $userId) {
                        $notes = trim(($serialNumber->notes ? $serialNumber->notes."\n" : '')
                            ."Adjusted out via Stock Adjustment {$this->adjustment_no}. Reason: {$this->reason}"
                            .($item->notes ? " - {$item->notes}" : ''));

                        $serialNumber->update([
                            'status' => 'retired',
                            'location_type' => null,
                            'location_id' => null,
                            'notes' => $notes,
                            'updated_by' => $userId,
                        ]);
                    });
            });
    }

    private function availableWarehouseSerialNumbersQuery(StockAdjustmentItem $item, array $serialNumbers)
    {
        return SerialNumber::query()
            ->where('warehouse_id', $this->warehouse_id)
            ->where('master_product_id', $item->master_product_id)
            ->whereIn('serial_number', $serialNumbers)
            ->whereIn('status', ['ready', 'available'])
            ->where(function ($query) {
                $query->whereNull('location_type')
                    ->orWhere('location_type', 'warehouse');
            })
            ->whereDoesntHave('unitOnWalls', function ($query) {
                $query->whereIn('status', ['active', 'installed', 'on_wall', 'on wall', 'onwall']);
            });
    }

    private function normalizeSerialNumbers($serialNumbers): array
    {
        if (is_string($serialNumbers)) {
            $serialNumbers = preg_split('/[\s,;]+/', $serialNumbers) ?: [];
        }

        if (! is_array($serialNumbers)) {
            return [];
        }

        return collect($serialNumbers)
            ->map(fn ($serialNumber) => strtoupper(trim((string) $serialNumber)))
            ->filter()
            ->values()
            ->all();
    }

    public function reject($userId)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    // Auto-generate adjustment number
    public static function generateAdjustmentNo($warehouseId)
    {
        $warehouse = Warehouse::with('branch')->find($warehouseId);
        if (! $warehouse || ! $warehouse->branch) {
            $prefix = 'ADJ';
        } else {
            $branchCode = strtoupper(substr($warehouse->branch->name, 0, 3));
            $prefix = $branchCode.'-ADJ';
        }

        $yearMonth = date('y-m'); // 26-01 format

        $searchPattern = $prefix.'/'.$yearMonth.'/%';

        $lastAdjustment = self::where('adjustment_no', 'like', $searchPattern)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastAdjustment) {
            $parts = explode('/', $lastAdjustment->adjustment_no);
            $lastSequence = (int) end($parts);
            $sequence = $lastSequence + 1;
        } else {
            $sequence = 1;
        }

        return $prefix.'/'.$yearMonth.'/'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}

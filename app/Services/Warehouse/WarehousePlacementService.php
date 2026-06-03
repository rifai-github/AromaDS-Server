<?php

namespace App\Services\Warehouse;

use App\Models\InventoryReceiving;
use App\Models\MaterialReturn;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WarehousePlacementService
{
    public const CONDITION_NEW = 'new';
    public const CONDITION_USED = 'used';
    public const CONDITION_DAMAGED = 'damaged';

    public function resolveForNewStock(Warehouse $fallbackWarehouse): Warehouse
    {
        return $this->resolveByCondition($fallbackWarehouse, self::CONDITION_NEW);
    }

    public function resolveForDamagedStock(Warehouse $fallbackWarehouse): Warehouse
    {
        return $this->resolveByCondition($fallbackWarehouse, self::CONDITION_DAMAGED);
    }

    public function resolveForMaterialReturn(MaterialReturn $materialReturn, Warehouse $fallbackWarehouse): Warehouse
    {
        return $this->resolveByCondition($fallbackWarehouse, $this->classifyMaterialReturn($materialReturn));
    }

    public function resolveForReceiving(InventoryReceiving $inventoryReceiving, Warehouse $fallbackWarehouse): Warehouse
    {
        $materialReturn = $this->findMaterialReturnForReceiving($inventoryReceiving);

        if ($materialReturn) {
            return $this->resolveForMaterialReturn($materialReturn, $fallbackWarehouse);
        }

        if ($this->isReceivingFromRemoveJob($inventoryReceiving)) {
            return $this->resolveByCondition($fallbackWarehouse, self::CONDITION_USED);
        }

        if ($this->isReceivingFromIssuing($inventoryReceiving)) {
            return $fallbackWarehouse;
        }

        return $this->resolveForNewStock($fallbackWarehouse);
    }

    public function classifyMaterialReturn(MaterialReturn $materialReturn): string
    {
        $text = collect([
            $materialReturn->return_reason,
            $materialReturn->notes,
        ]);

        if ($materialReturn->relationLoaded('items')) {
            $text = $text->merge($materialReturn->items->flatMap(fn ($item) => [
                $item->return_reason,
                $item->notes,
            ]));
        }

        $needle = Str::lower($text->filter()->implode(' '));

        if (Str::contains($needle, ['damaged', 'damage', 'rusak', 'broken'])) {
            return self::CONDITION_DAMAGED;
        }

        return self::CONDITION_USED;
    }

    private function resolveByCondition(Warehouse $fallbackWarehouse, string $condition): Warehouse
    {
        $keywords = match ($condition) {
            self::CONDITION_DAMAGED => ['rusak', 'damaged', 'broken'],
            self::CONDITION_USED => ['bekas', 'used', 'return', 'retur'],
            default => ['baru', 'new'],
        };

        $query = Warehouse::query();

        if (Schema::hasColumn('warehouses', 'branch_id') && $fallbackWarehouse->branch_id) {
            $query->where('branch_id', $fallbackWarehouse->branch_id);
        }

        if (Schema::hasColumn('warehouses', 'is_active')) {
            $query->where('is_active', true);
        }

        $query->where(function ($nested) use ($keywords) {
            foreach ($keywords as $keyword) {
                $nested->orWhere('name', 'like', "%{$keyword}%");

                if (Schema::hasColumn('warehouses', 'warehouse_code')) {
                    $nested->orWhere('warehouse_code', 'like', "%{$keyword}%");
                }
            }
        });

        if (Schema::hasColumn('warehouses', 'is_center')) {
            $query->orderByDesc('is_center');
        }

        $warehouse = $query->orderBy('id')->first();

        return $warehouse ?: $fallbackWarehouse;
    }

    private function findMaterialReturnForReceiving(InventoryReceiving $inventoryReceiving): ?MaterialReturn
    {
        if (! $inventoryReceiving->reference_no) {
            return null;
        }

        if (! Schema::hasTable('material_returns') || ! Schema::hasTable('job_schedules')) {
            return null;
        }

        return MaterialReturn::with('items')
            ->whereHas('jobSchedule', function ($query) use ($inventoryReceiving) {
                $query->where('job_number', $inventoryReceiving->reference_no);
            })
            ->whereNotIn('status', [
                MaterialReturn::STATUS_CANCELLED,
                MaterialReturn::STATUS_REJECTED,
            ])
            ->latest('id')
            ->first();
    }

    private function isReceivingFromRemoveJob(InventoryReceiving $inventoryReceiving): bool
    {
        $text = Str::lower(collect([
            $inventoryReceiving->reference_no,
            $inventoryReceiving->notes,
        ])->filter()->implode(' '));

        return Str::contains($text, [
            '-rv/',
            '/rv/',
            'remove job',
            'auto-return dari remove',
        ]);
    }

    private function isReceivingFromIssuing(InventoryReceiving $inventoryReceiving): bool
    {
        if ($inventoryReceiving->issuing_id) {
            return true;
        }

        if (! $inventoryReceiving->reference_no || ! Schema::hasTable('inventory_issuings')) {
            return false;
        }

        return \App\Models\InventoryIssuing::where('issuing_number', $inventoryReceiving->reference_no)->exists();
    }
}

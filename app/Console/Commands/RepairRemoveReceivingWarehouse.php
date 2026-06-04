<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use App\Models\InventoryReceiving;
use App\Models\SerialNumber;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Services\Warehouse\WarehousePlacementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairRemoveReceivingWarehouse extends Command
{
    protected $signature = 'warehouse:repair-remove-receiving-warehouse
        {--receiving-number=* : Specific inventory receiving number, repeatable}
        {--serial-number=* : Specific serial number, repeatable}
        {--apply : Apply the repair. Default is dry-run}';

    protected $description = 'Move historical Remove/RV receiving stock and serial numbers from new-stock warehouse to the matching used-stock warehouse';

    public function handle(WarehousePlacementService $placementService): int
    {
        $receivingNumbers = collect($this->option('receiving-number'))->filter()->map(fn ($value) => trim((string) $value))->values();
        $serialNumbers = collect($this->option('serial-number'))->filter()->map(fn ($value) => trim((string) $value))->values();
        $apply = (bool) $this->option('apply');

        if ($receivingNumbers->isEmpty() && $serialNumbers->isEmpty()) {
            $this->error('Use --receiving-number or --serial-number. This repair intentionally does not run without a specific target.');

            return self::FAILURE;
        }

        if (! $apply) {
            $this->warn('DRY RUN mode. No database changes will be made. Re-run with --apply to persist repairs.');
        }

        $receivings = InventoryReceiving::query()
            ->where('status', 'received')
            ->where(function ($query) {
                $query->whereRaw("LOWER(COALESCE(reference_no, '')) LIKE ?", ['%/rv/%'])
                    ->orWhereRaw("LOWER(COALESCE(reference_no, '')) LIKE ?", ['%-rv/%'])
                    ->orWhereRaw("LOWER(COALESCE(notes, '')) LIKE ?", ['%remove job%'])
                    ->orWhereRaw("LOWER(COALESCE(notes, '')) LIKE ?", ['%auto-return dari remove%']);
            })
            ->when($receivingNumbers->isNotEmpty(), fn ($query) => $query->whereIn('receiving_number', $receivingNumbers->all()))
            ->when($serialNumbers->isNotEmpty(), function ($query) use ($serialNumbers) {
                $query->whereHas('items')
                    ->whereExists(function ($subquery) use ($serialNumbers) {
                        $subquery->selectRaw('1')
                            ->from('serial_numbers')
                            ->whereColumn('serial_numbers.inventory_receiving_id', 'inventory_receivings.id')
                            ->whereIn('serial_numbers.serial_number', $serialNumbers->all())
                            ->whereNull('serial_numbers.deleted_at');
                    });
            })
            ->orderBy('id')
            ->get();

        $rows = [];
        $scanned = 0;
        $planned = 0;
        $applied = 0;
        $skipped = 0;

        foreach ($receivings as $receiving) {
            $scanned++;
            $plan = $this->planRepair($receiving, $placementService, $serialNumbers);

            if ($plan['action'] !== 'repair') {
                $skipped++;
                $rows[] = $this->row('SKIP', $receiving, $plan);

                continue;
            }

            $planned++;

            if ($apply) {
                DB::transaction(function () use ($plan, &$applied) {
                    foreach ($plan['stock_moves'] as $move) {
                        $this->moveWarehouseStock(
                            (int) $move['source_warehouse_id'],
                            (int) $plan['target_warehouse']->id,
                            (int) $move['master_product_id'],
                            (float) $move['quantity']
                        );
                    }

                    InventoryMovement::whereIn('id', $plan['movement_ids'])->update([
                        'warehouse_id' => $plan['target_warehouse']->id,
                        'updated_by' => auth()->id(),
                        'updated_at' => now(),
                    ]);

                    SerialNumber::whereIn('id', $plan['serial_ids'])->update([
                        'warehouse_id' => $plan['target_warehouse']->id,
                        'location_type' => 'warehouse',
                        'location_id' => $plan['target_warehouse']->id,
                        'updated_by' => auth()->id(),
                        'updated_at' => now(),
                    ]);

                    $applied++;
                });
            }

            $rows[] = $this->row($apply ? 'FIXED' : 'PLAN', $receiving, $plan);
        }

        $this->table(
            ['Status', 'Receiving No', 'Reference', 'Current Warehouse(s)', 'Target Warehouse', 'Serials', 'Stock Qty', 'Note'],
            $rows
        );
        $this->line('Scanned receivings : '.$scanned);
        $this->line('Repair plans      : '.$planned);
        $this->line('Applied repairs   : '.($apply ? $applied : 'dry-run'));
        $this->line('Skipped           : '.$skipped);

        if (! $apply) {
            $this->line('Dry run only. Re-run with --apply after reviewing PLAN rows.');
        }

        return self::SUCCESS;
    }

    private function planRepair(InventoryReceiving $receiving, WarehousePlacementService $placementService, $serialNumberFilter): array
    {
        $serials = SerialNumber::with('warehouse')
            ->where('inventory_receiving_id', $receiving->id)
            ->when($serialNumberFilter->isNotEmpty(), fn ($query) => $query->whereIn('serial_number', $serialNumberFilter->all()))
            ->get();

        $movements = InventoryMovement::with('warehouse')
            ->where('reference_no', $receiving->receiving_number)
            ->where('reference_type', 'inventory_receiving')
            ->where('quantity', '>', 0)
            ->get();

        $currentWarehouseIds = $serials->pluck('warehouse_id')
            ->merge($movements->pluck('warehouse_id'))
            ->filter()
            ->unique()
            ->values();

        if ($currentWarehouseIds->isEmpty()) {
            return ['action' => 'skip', 'note' => 'current warehouse could not be determined'];
        }

        $currentWarehouses = Warehouse::whereIn('id', $currentWarehouseIds->all())->get()->keyBy('id');
        $targets = $currentWarehouses
            ->map(fn (Warehouse $warehouse) => $placementService->resolveForReceiving($receiving, $warehouse))
            ->unique('id')
            ->values();

        if ($targets->count() !== 1) {
            return [
                'action' => 'skip',
                'current_warehouses' => $currentWarehouses->pluck('name')->all(),
                'serial_count' => $serials->count(),
                'note' => 'current warehouses resolve to different used-stock warehouses',
            ];
        }

        $targetWarehouse = $targets->first();
        $serialsToMove = $serials->where('warehouse_id', '!=', $targetWarehouse->id)->values();
        $movementsToMove = $movements->where('warehouse_id', '!=', $targetWarehouse->id)->values();

        if ($serialsToMove->isEmpty() && $movementsToMove->isEmpty()) {
            return [
                'action' => 'skip',
                'target_warehouse' => $targetWarehouse,
                'current_warehouses' => $currentWarehouses->pluck('name')->all(),
                'serial_count' => $serials->count(),
                'note' => 'already uses the matching used-stock warehouse',
            ];
        }

        $unsafeSerial = $serialsToMove->first(fn (SerialNumber $serial) => ! in_array((string) $serial->status, ['ready', 'available'], true)
            || $serial->location_type !== 'warehouse');

        if ($unsafeSerial) {
            return [
                'action' => 'skip',
                'target_warehouse' => $targetWarehouse,
                'current_warehouses' => $currentWarehouses->pluck('name')->all(),
                'serial_count' => $serialsToMove->count(),
                'note' => "serial {$unsafeSerial->serial_number} is no longer ready in warehouse",
            ];
        }

        $stockMoves = $movementsToMove
            ->groupBy(fn (InventoryMovement $movement) => $movement->warehouse_id.'|'.$movement->master_product_id)
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'source_warehouse_id' => (int) $first->warehouse_id,
                    'master_product_id' => (int) $first->master_product_id,
                    'quantity' => (float) $group->sum('quantity'),
                ];
            })
            ->values();

        foreach ($stockMoves as $move) {
            $sourceStock = WarehouseProduct::where('warehouse_id', $move['source_warehouse_id'])
                ->where('master_product_id', $move['master_product_id'])
                ->first();

            if (! $sourceStock || (float) $sourceStock->quantity < (float) $move['quantity']) {
                return [
                    'action' => 'skip',
                    'target_warehouse' => $targetWarehouse,
                    'current_warehouses' => $currentWarehouses->pluck('name')->all(),
                    'serial_count' => $serialsToMove->count(),
                    'stock_quantity' => (float) $stockMoves->sum('quantity'),
                    'note' => 'source warehouse stock is not enough to move safely',
                ];
            }
        }

        return [
            'action' => 'repair',
            'target_warehouse' => $targetWarehouse,
            'current_warehouses' => $currentWarehouses->pluck('name')->all(),
            'serial_count' => $serialsToMove->count(),
            'serial_ids' => $serialsToMove->pluck('id')->all(),
            'movement_ids' => $movementsToMove->pluck('id')->all(),
            'stock_quantity' => (float) $stockMoves->sum('quantity'),
            'stock_moves' => $stockMoves->all(),
            'note' => 'move Remove/RV receiving stock and serials to used-stock warehouse',
        ];
    }

    private function moveWarehouseStock(int $sourceWarehouseId, int $targetWarehouseId, int $productId, float $quantity): void
    {
        $sourceStock = WarehouseProduct::where('warehouse_id', $sourceWarehouseId)
            ->where('master_product_id', $productId)
            ->lockForUpdate()
            ->firstOrFail();

        $targetStock = WarehouseProduct::firstOrCreate(
            ['warehouse_id' => $targetWarehouseId, 'master_product_id' => $productId],
            [
                'quantity' => 0,
                'minimum_stock' => 0,
                'maximum_stock' => 0,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        $sourceStock->update([
            'quantity' => max(0, (float) $sourceStock->quantity - $quantity),
            'updated_by' => auth()->id(),
        ]);
        $targetStock->update([
            'quantity' => (float) $targetStock->quantity + $quantity,
            'updated_by' => auth()->id(),
        ]);
    }

    private function row(string $status, InventoryReceiving $receiving, array $plan): array
    {
        return [
            $status,
            $receiving->receiving_number ?: '-',
            $receiving->reference_no ?: '-',
            implode(', ', $plan['current_warehouses'] ?? []) ?: '-',
            $plan['target_warehouse']->name ?? '-',
            $plan['serial_count'] ?? 0,
            $plan['stock_quantity'] ?? 0,
            $plan['note'] ?? '-',
        ];
    }
}

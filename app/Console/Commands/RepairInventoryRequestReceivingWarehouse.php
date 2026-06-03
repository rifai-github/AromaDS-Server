<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use App\Models\InventoryReceiving;
use App\Models\InventoryRequest;
use App\Models\SerialNumber;
use App\Models\WarehouseProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RepairInventoryRequestReceivingWarehouse extends Command
{
    protected $signature = 'warehouse:repair-inventory-request-receiving-warehouse
        {--receiving-number=* : Limit by inventory receiving number}
        {--request-number=* : Limit by inventory request number}
        {--apply : Apply the repair. Default is dry-run}';

    protected $description = 'Repair received inventory request stock/serials that were placed in a new-stock warehouse instead of the requested warehouse';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->warn('DRY RUN mode. No database changes will be made. Re-run with --apply to persist repairs.');
        }

        $rows = [];
        $scanned = 0;
        $planned = 0;
        $applied = 0;
        $skipped = 0;

        $receivings = $this->queryReceivings()->get();

        foreach ($receivings as $receiving) {
            $scanned++;
            $plan = $this->planRepair($receiving);

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
                            (int) $move['target_warehouse_id'],
                            (int) $move['master_product_id'],
                            (float) $move['quantity']
                        );
                    }

                    InventoryMovement::whereIn('id', $plan['movement_ids'])
                        ->update([
                            'warehouse_id' => $plan['target_warehouse']->id,
                            'updated_by' => Auth::id() ?: 1,
                            'updated_at' => now(),
                        ]);

                    SerialNumber::whereIn('id', $plan['serial_ids'])
                        ->update([
                            'warehouse_id' => $plan['target_warehouse']->id,
                            'location_id' => $plan['target_warehouse']->id,
                            'location_type' => 'warehouse',
                            'updated_by' => Auth::id() ?: 1,
                            'updated_at' => now(),
                        ]);

                    $applied++;
                });
            }

            $rows[] = $this->row($apply ? 'FIXED' : 'PLAN', $receiving, $plan);
        }

        $this->table([
            'Status',
            'Receiving No',
            'Request No',
            'Current Warehouse(s)',
            'Target Warehouse',
            'Serials',
            'Stock Qty',
            'Note',
        ], $rows);

        $this->line('Scanned receivings : '.$scanned);
        $this->line('Repair plans      : '.$planned);
        $this->line('Applied repairs   : '.($apply ? $applied : 'dry-run'));
        $this->line('Skipped           : '.$skipped);

        if (! $apply) {
            $this->line('Dry run only. Re-run with --apply after reviewing PLAN rows.');
        }

        return self::SUCCESS;
    }

    private function queryReceivings()
    {
        $query = InventoryReceiving::query()
            ->whereNotNull('reference_no')
            ->whereNull('issuing_id')
            ->whereHas('items')
            ->whereExists(function ($subquery) {
                $subquery->selectRaw('1')
                    ->from('inventory_requests')
                    ->whereColumn('inventory_requests.request_number', 'inventory_receivings.reference_no')
                    ->whereNotNull('inventory_requests.warehouse_id')
                    ->whereNull('inventory_requests.deleted_at');
            })
            ->orderBy('id');

        $receivingNumbers = collect($this->option('receiving-number'))
            ->filter()
            ->map(fn ($value) => trim((string) $value))
            ->values();

        if ($receivingNumbers->isNotEmpty()) {
            $query->whereIn('receiving_number', $receivingNumbers->all());
        }

        $requestNumbers = collect($this->option('request-number'))
            ->filter()
            ->map(fn ($value) => trim((string) $value))
            ->values();

        if ($requestNumbers->isNotEmpty()) {
            $query->whereIn('reference_no', $requestNumbers->all());
        }

        return $query;
    }

    private function planRepair(InventoryReceiving $receiving): array
    {
        $request = InventoryRequest::with('warehouse')
            ->where('request_number', $receiving->reference_no)
            ->first();

        if (! $request || ! $request->warehouse) {
            return ['action' => 'skip', 'note' => 'inventory request or target warehouse was not found'];
        }

        $targetWarehouse = $request->warehouse;

        if (! $targetWarehouse->is_active) {
            return [
                'action' => 'skip',
                'request' => $request,
                'target_warehouse' => $targetWarehouse,
                'note' => 'target warehouse is inactive',
            ];
        }

        $movements = InventoryMovement::query()
            ->with('warehouse')
            ->where('reference_no', $receiving->receiving_number)
            ->where('reference_type', 'inventory_receiving')
            ->where('warehouse_id', '!=', $targetWarehouse->id)
            ->where('quantity', '>', 0)
            ->get();

        $serials = SerialNumber::with('warehouse')
            ->where('inventory_receiving_id', $receiving->id)
            ->where('warehouse_id', '!=', $targetWarehouse->id)
            ->get();

        if ($movements->isEmpty() && $serials->isEmpty()) {
            return [
                'action' => 'skip',
                'request' => $request,
                'target_warehouse' => $targetWarehouse,
                'note' => 'already uses requested warehouse',
            ];
        }

        $unsafeSerial = $serials->first(function (SerialNumber $serial) {
            return ! in_array((string) $serial->status, ['ready', 'available'], true)
                || $serial->location_type !== 'warehouse';
        });

        if ($unsafeSerial) {
            return [
                'action' => 'skip',
                'request' => $request,
                'target_warehouse' => $targetWarehouse,
                'current_warehouses' => $serials->pluck('warehouse.name')->filter()->unique()->values()->all(),
                'serial_count' => $serials->count(),
                'note' => "serial {$unsafeSerial->serial_number} is no longer ready in warehouse",
            ];
        }

        $stockMoves = $movements
            ->groupBy(fn (InventoryMovement $movement) => $movement->warehouse_id.'|'.$movement->master_product_id)
            ->map(function ($group) use ($targetWarehouse) {
                $first = $group->first();

                return [
                    'source_warehouse_id' => (int) $first->warehouse_id,
                    'target_warehouse_id' => (int) $targetWarehouse->id,
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
                    'request' => $request,
                    'target_warehouse' => $targetWarehouse,
                    'current_warehouses' => $serials->pluck('warehouse.name')->filter()->unique()->values()->all(),
                    'serial_count' => $serials->count(),
                    'stock_quantity' => (float) $stockMoves->sum('quantity'),
                    'note' => 'source warehouse stock is not enough to move safely',
                ];
            }
        }

        $currentWarehouses = collect()
            ->merge($serials->pluck('warehouse.name')->filter())
            ->merge($movements->pluck('warehouse.name')->filter())
            ->unique()
            ->values()
            ->all();

        return [
            'action' => 'repair',
            'request' => $request,
            'target_warehouse' => $targetWarehouse,
            'current_warehouses' => $currentWarehouses,
            'serial_count' => $serials->count(),
            'serial_ids' => $serials->pluck('id')->all(),
            'movement_ids' => $movements->pluck('id')->all(),
            'stock_quantity' => (float) $stockMoves->sum('quantity'),
            'stock_moves' => $stockMoves->all(),
            'note' => 'move IRQ receiving stock/serials to requested warehouse',
        ];
    }

    private function moveWarehouseStock(int $sourceWarehouseId, int $targetWarehouseId, int $productId, float $quantity): void
    {
        $sourceStock = WarehouseProduct::where('warehouse_id', $sourceWarehouseId)
            ->where('master_product_id', $productId)
            ->lockForUpdate()
            ->firstOrFail();

        $targetStock = WarehouseProduct::firstOrCreate(
            [
                'warehouse_id' => $targetWarehouseId,
                'master_product_id' => $productId,
            ],
            [
                'quantity' => 0,
                'minimum_stock' => 0,
                'maximum_stock' => 0,
                'created_by' => Auth::id() ?: 1,
                'updated_by' => Auth::id() ?: 1,
            ]
        );

        $sourceStock->update([
            'quantity' => max(0, (float) $sourceStock->quantity - $quantity),
            'updated_by' => Auth::id() ?: 1,
        ]);

        $targetStock->update([
            'quantity' => (float) $targetStock->quantity + $quantity,
            'updated_by' => Auth::id() ?: 1,
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

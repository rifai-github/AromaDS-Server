<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\SerialNumber;
use App\Models\Warehouse;
use App\Models\MasterProduct;
use App\Models\WarehouseProduct;
use App\Models\InventoryMovement;
use App\Services\Warehouse\WarehousePlacementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\AccessControlFilterTrait;

class SerialNumberController extends Controller
{
    use \App\Http\Traits\ColumnFilterTrait;
    use AccessControlFilterTrait;

    public function index(Request $request)
    {
        $query = SerialNumber::with([
            'warehouse', 
            'masterProduct', 
            'createdBy', 
            'updatedBy',
            'unitOnWalls' => function($q) {
                $q->where('status', 'active'); // Only load active unit on walls for location type derivation
            }
        ]);

        // Apply Access Control
        // Uses 'created_by' as owner, 'warehouse.branch_id' for branch access, and 'warehouse_id' for manager access.
        $query = $this->applyAccessControlFilter($query, null, 'created_by', null, 'warehouse.branch_id', null, 'warehouse_id');

        $conditionView = $request->input('condition_view', 'all');
        match ($conditionView) {
            'new' => $query->where(function ($nested) {
                $nested->whereNull('condition_status')
                    ->orWhere('condition_status', SerialNumber::CONDITION_NEW);
            })->whereNotIn('status', ['broken', 'damaged', 'retired']),
            'second_ready' => $query->whereIn('condition_status', [
                SerialNumber::CONDITION_SECOND_READY,
                'used',
            ])->whereNotIn('status', ['broken', 'damaged', 'retired']),
            'damaged' => $query->where(function ($nested) {
                $nested->where('condition_status', SerialNumber::CONDITION_DAMAGED)
                    ->orWhereIn('status', ['broken', 'damaged']);
            }),
            'technician' => $query->where(function ($nested) {
                $nested->where('location_type', 'technician')
                    ->orWhereIn('status', ['on_hand', 'on_hand_remove']);
            }),
            'customer' => $query->where(function ($nested) {
                $nested->where('location_type', 'customer')
                    ->orWhere('status', 'in_use')
                    ->orWhereHas('unitOnWalls', fn ($unitQuery) => $unitQuery->where('status', 'active'));
            }),
            'retired' => $query->where('status', 'retired'),
            default => null,
        };

        // Normalize status filter (e.g., "On Hand" -> "on_hand")
        if ($request->has('filter.status')) {
            $status = $request->input('filter.status');
            if (is_string($status)) {
                 $normalizedStatus = str_replace([' ', '-'], '_', strtolower(trim($status)));
                 $request->merge(['filter' => array_merge($request->input('filter'), ['status' => $normalizedStatus])]);
            }
        }

        // Apply AutoFilterable
        $query->filter($request->all());

        $serialNumbers = $query->orderBy('updated_at', 'desc')->paginateStd(25)->withQueryString();

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $serialNumbers
            ]);
        }

        // Load products and warehouses for modal
        $products = \App\Models\MasterProduct::with('packagingSize')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'packaging_size_id']);
        
        $warehouses = \App\Models\Warehouse::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('warehouse.serial-numbers.index', compact('serialNumbers', 'products', 'warehouses', 'conditionView'));
    }

    public function create()
    {
        $warehouses = Warehouse::where('is_active', true)->get();
        $products = MasterProduct::where('is_active', true)->get();
        
        // Return JSON for AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'warehouses' => $warehouses,
                    'products' => $products
                ]
            ]);
        }
        
        return view('warehouse.serial-numbers.create', compact('warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'master_product_id' => 'required|exists:master_products,id',
            'serial_number' => 'required|string|max:100|unique:serial_numbers',
            'status' => 'required|in:ready,broken,on_service,in_use,retired,available,maintenance,damaged,on_hand,on_hand_remove', // Include legacy statuses for backward compatibility
            'condition_status' => 'nullable|in:new,second_ready,damaged',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $serialNumber = SerialNumber::create([
                'warehouse_id' => $request->warehouse_id,
                'master_product_id' => $request->master_product_id,
                'serial_number' => strtoupper($request->serial_number),
                'status' => $request->status,
                'condition_status' => $request->condition_status
                    ?: (in_array($request->status, ['broken', 'damaged', 'retired'], true)
                        ? SerialNumber::CONDITION_DAMAGED
                        : SerialNumber::CONDITION_NEW),
                'location_type' => null,
                'location_id' => null,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Serial Number created successfully.',
                    'data' => $serialNumber->load(['warehouse', 'masterProduct', 'createdBy', 'updatedBy'])
                ]);
            }

            return redirect()->route('warehouse.serial-numbers.show', $serialNumber)
                ->with('success', 'Serial Number berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create serial number: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(SerialNumber $serialNumber)
    {
        // 1. Loading basic relationships
        $serialNumber->load([
            'warehouse', 
            'masterProduct', 
            'createdBy', 
            'updatedBy',
            'inventoryReceiving',
        ]);

        // 2. Load Unit On Wall info
        $unitOnWalls = \App\Models\UnitOnWall::where('serial_number_id', $serialNumber->id)
            ->with(['customer', 'building', 'room', 'product'])
            ->get();
        
        // 3. Get Installation & Service Histories via UnitOnWallHistory
        $uowIds = $unitOnWalls->pluck('id');
        $allUowHistories = \App\Models\UnitOnWallHistory::whereIn('unit_on_wall_id', $uowIds)
            ->with(['technician', 'customer', 'jobSchedule'])
            ->orderBy('action_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
            
        $installHistories = collect();
        $serviceHistories = collect();
        
        // Add explicit histories from UnitOnWallHistory
        foreach ($allUowHistories as $h) {
            $historyObj = (object)[
                'action' => $h->action,
                'action_date' => $h->action_date,
                'customer_name' => $h->customer_name,
                'location' => $h->location,
                'technician_name' => $h->technician_name,
                'job_schedule_number' => $h->job_schedule_number,
                'job_schedule_id' => $h->job_schedule_id,
                'notes' => $h->notes,
                'badge' => $h->getActionBadgeClass(),
                'label' => $h->getActionLabel()
            ];
            
            if (in_array($h->action, ['install', 'remove'])) {
                $installHistories->push($historyObj);
            } elseif (in_array($h->action, ['service', 'service_first', 'service_extra', 'csr'])) {
                $serviceHistories->push($historyObj);
            } elseif ($h->action === 'repair') {
                // Already handled by repairHistories, but we can also push to a dedicated list if needed
            } else {
                // Default to service tab if it's some other non-install activity
                $serviceHistories->push($historyObj);
            }
        }
        
        // Fallback: Add virtual history from UnitOnWall records if no history exists for them
        foreach ($unitOnWalls as $uow) {
            $hasHistory = $installHistories->contains(fn($h) => $h->customer_name == $uow->customer_name && $h->action == 'install');
            if (!$hasHistory && $uow->install_date) {
                // Try to extract job number from notes
                $jobNo = null;
                if ($uow->notes && preg_match('/JKT-[A-Z]+\/\d{2}-\d{2}\/\d{4}/', $uow->notes, $matches)) {
                    $jobNo = $matches[0];
                }
                
                $installHistories->push((object)[
                    'action' => 'install',
                    'action_date' => $uow->install_date,
                    'customer_name' => $uow->customer_name,
                    'location' => $uow->full_location,
                    'technician_name' => $uow->createdBy->name ?? '-',
                    'job_schedule_number' => $jobNo,
                    'job_schedule_id' => null,
                    'notes' => $uow->notes,
                    'badge' => 'success',
                    'label' => 'Installed (System Record)'
                ]);

                // If currently active, it's installed. If removed, we might want to show removal too.
                if ($uow->status === 'removed' && $uow->updated_at) {
                    $installHistories->push((object)[
                        'action' => 'remove',
                        'action_date' => $uow->updated_at,
                        'customer_name' => $uow->customer_name,
                        'location' => $uow->full_location,
                        'technician_name' => $uow->updatedBy->name ?? '-',
                        'job_schedule_number' => null,
                        'job_schedule_id' => null,
                        'notes' => 'Auto-removed by system',
                        'badge' => 'danger',
                        'label' => 'Removed'
                    ]);
                }
            }
        }

        // Add from UnitInstallation table as another fallback
        $unitInstalls = \App\Models\UnitInstallation::where('serial_number', $serialNumber->serial_number)
            ->with(['jobSchedule', 'room.building'])
            ->get();
            
        foreach ($unitInstalls as $ui) {
            $installHistories->push((object)[
                'action' => $ui->status,
                'action_date' => $ui->install_date,
                'customer_name' => $ui->jobSchedule->company_name ?? '-',
                'location' => ($ui->room->building->nama_gedung ?? '-') . ' - ' . ($ui->room->room_name ?? '-'),
                'technician_name' => $ui->jobSchedule->assignedTechnician->name ?? '-',
                'job_schedule_number' => $ui->jobSchedule->job_number ?? '-',
                'job_schedule_id' => $ui->job_schedule_id,
                'notes' => $ui->installation_notes,
                'badge' => $ui->status == 'installed' ? 'success' : 'warning',
                'label' => ucfirst($ui->status)
            ]);
        }
        
        $installHistories = $installHistories->sortByDesc('action_date');
        $serviceHistories = $serviceHistories->sortByDesc('action_date');
        
        // 4. Get Repair Histories (linked directly to SerialNumber)
        $repairHistories = \App\Models\UnitRepair::where('unit_id', $serialNumber->id)
            ->with(['repairedBy', 'reportedBy'])
            ->orderBy('reported_at', 'desc')
            ->get();

        // 5. Get Scanning/Movement Histories (Receiving, Issuing, etc.)
        $movementHistories = collect();
        
        // Receiving
        if ($serialNumber->inventoryReceiving) {
            $movementHistories->push((object)[
                'date' => $serialNumber->inventoryReceiving->receive_date ?? $serialNumber->created_at,
                'action' => 'diterima',
                'label' => 'Inventory Receiving',
                'badge' => 'success',
                'notes' => 'Received at ' . ($serialNumber->warehouse->name ?? 'Warehouse'),
                'reference' => $serialNumber->inventoryReceiving->receiving_number,
                'user' => $serialNumber->createdBy->name ?? '-'
            ]);
        }
        
        // Issuing
        $issuingItems = \App\Models\InventoryIssuingItem::where('serial_number_id', $serialNumber->id)
            ->with(['inventoryIssuing', 'createdBy'])
            ->get();
            
        foreach($issuingItems as $item) {
            $movementHistories->push((object)[
                'date' => $item->inventoryIssuing->issue_date ?? $item->created_at,
                'action' => 'dikeluarkan',
                'label' => 'Inventory Issuing',
                'badge' => 'warning',
                'notes' => 'Issued for ' . ($item->inventoryIssuing->notes ?? 'Stock Movement'),
                'reference' => $item->inventoryIssuing->issuing_number,
                'user' => $item->createdBy->name ?? '-'
            ]);
        }
        
        // Stock Opname Scans
        $opnameDetails = \App\Models\StockOpnameDetail::whereJsonContains('scanned_serial_numbers', $serialNumber->serial_number)
            ->with(['stockOpname.warehouse', 'stockOpname.createdBy'])
            ->get();
            
        foreach($opnameDetails as $detail) {
            $movementHistories->push((object)[
                'date' => $detail->stockOpname->opname_date ?? $detail->created_at,
                'action' => 'di_scan',
                'label' => 'Stock Opname Scan',
                'badge' => 'info',
                'notes' => 'Scanned during opname at ' . ($detail->stockOpname->warehouse->name ?? 'Warehouse'),
                'reference' => $detail->stockOpname->opname_no,
                'user' => $detail->stockOpname->createdBy->name ?? '-'
            ]);
        }
        
        $movementHistories = $movementHistories->sortByDesc('date');

        // Check if has WiFi (from any active unit on wall)
        $activeUow = $unitOnWalls->where('status', 'active')->first();
        $hasWifi = $activeUow ? true : false;
        $unitOnWall = $activeUow;

        // Return JSON for AJAX requests (modal system)

        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $serialNumber
            ]);
        }
        
        // Return view for non-AJAX requests
        return view('warehouse.serial-numbers.show', compact(
            'serialNumber', 
            'unitOnWalls', 
            'installHistories', 
            'serviceHistories', 
            'repairHistories', 
            'movementHistories',
            'hasWifi',
            'unitOnWall'
        ));
    }

    public function edit(SerialNumber $serialNumber)
    {
        $serialNumber->load(['warehouse', 'masterProduct', 'createdBy', 'updatedBy']);
        $warehouses = Warehouse::where('is_active', true)->get();
        $products = MasterProduct::where('is_active', true)->get();
        
        // Return JSON for AJAX requests (modal system)
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'serialNumber' => $serialNumber,
                    'warehouses' => $warehouses,
                    'products' => $products
                ]
            ]);
        }
        
        // For non-AJAX requests, redirect to index with error message
        return redirect()->route('warehouse.serial-numbers.index')
            ->with('error', 'Please use the modal system to edit serial numbers.');
    }

    public function update(Request $request, SerialNumber $serialNumber)
    {
        // Allow partial updates (only status and notes for edit from show page)
        $rules = [];
        
        if ($request->has('status')) {
            $rules['status'] = 'required|in:ready,broken,on_service,in_use,retired,available,maintenance,damaged,on_hand,on_hand_remove'; // Include legacy statuses
        }

        if ($request->has('condition_status')) {
            $rules['condition_status'] = 'nullable|in:new,second_ready,damaged';
        }
        
        if ($request->has('notes')) {
            $rules['notes'] = 'nullable|string|max:1000';
        }
        
        // Full update (if all fields provided)
        if ($request->has('warehouse_id') && $request->has('master_product_id') && $request->has('serial_number')) {
            $rules = array_merge($rules, [
                'warehouse_id' => 'required|exists:warehouses,id',
                'master_product_id' => 'required|exists:master_products,id',
                'serial_number' => 'required|string|max:100|unique:serial_numbers,serial_number,' . $serialNumber->id,
                'location_type' => 'nullable|in:warehouse,customer,technician',
                'location_id' => 'nullable|integer',
            ]);
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $oldWarehouse = $serialNumber->warehouse;

            $updateData = [
                'updated_by' => Auth::id()
            ];

            // Only update fields that are provided
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }

            if ($request->has('condition_status')) {
                $updateData['condition_status'] = $request->condition_status;
            } elseif ($request->has('status') && in_array($request->status, ['broken', 'damaged', 'retired'], true)) {
                $updateData['condition_status'] = SerialNumber::CONDITION_DAMAGED;
            }
            
            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }

            // Full update fields
            if ($request->has('warehouse_id')) {
                $updateData['warehouse_id'] = $request->warehouse_id;
            }
            
            if ($request->has('master_product_id')) {
                $updateData['master_product_id'] = $request->master_product_id;
            }
            
            if ($request->has('serial_number')) {
                $updateData['serial_number'] = strtoupper($request->serial_number);
            }
            
            if ($request->has('location_type')) {
                $updateData['location_type'] = $request->location_type;
            }
            
            if ($request->has('location_id')) {
                $updateData['location_id'] = $request->location_id;
            }

            $serialNumber->update($updateData);

            if ($this->shouldMoveSerialToDamagedWarehouse($serialNumber->status)) {
                $this->moveSerialToDamagedWarehouse($serialNumber->fresh(), $oldWarehouse);
            }

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Serial Number updated successfully.',
                    'data' => $serialNumber->load(['warehouse', 'masterProduct', 'createdBy', 'updatedBy'])
                ]);
            }

            return redirect()->route('warehouse.serial-numbers.show', $serialNumber)
                ->with('success', 'Serial Number berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update serial number: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function shouldMoveSerialToDamagedWarehouse(?string $newStatus): bool
    {
        return in_array($newStatus, ['broken', 'damaged'], true);
    }

    private function moveSerialToDamagedWarehouse(SerialNumber $serialNumber, ?Warehouse $sourceWarehouse): void
    {
        if (! $sourceWarehouse || ! $serialNumber->master_product_id) {
            return;
        }

        $targetWarehouse = app(WarehousePlacementService::class)->resolveForDamagedStock($sourceWarehouse);

        if (! $targetWarehouse || (int) $targetWarehouse->id === (int) $sourceWarehouse->id) {
            return;
        }

        $actorId = Auth::id() ?? 1;
        $productId = (int) $serialNumber->master_product_id;

        $sourceStock = WarehouseProduct::where('warehouse_id', $sourceWarehouse->id)
            ->where('master_product_id', $productId)
            ->lockForUpdate()
            ->first();

        $deductedSourceStock = false;
        if ($sourceStock && $sourceStock->quantity > 0) {
            $sourceStock->decrement('quantity', 1, ['updated_by' => $actorId]);
            $deductedSourceStock = true;
        }

        $masterProduct = MasterProduct::find($productId);
        $targetStock = WarehouseProduct::firstOrCreate(
            [
                'warehouse_id' => $targetWarehouse->id,
                'master_product_id' => $productId,
            ],
            [
                'quantity' => 0,
                'minimum_stock' => $sourceStock->minimum_stock ?? $masterProduct->minimum_stock ?? 0,
                'maximum_stock' => $sourceStock->maximum_stock ?? $masterProduct->maximum_stock ?? 0,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]
        );

        $targetStock->increment('quantity', 1, ['updated_by' => $actorId]);

        $serialNumber->update([
            'warehouse_id' => $targetWarehouse->id,
            'location_type' => 'warehouse',
            'location_id' => $targetWarehouse->id,
            'updated_by' => $actorId,
        ]);

        $referenceNo = $serialNumber->serial_number;
        $movementNo = 'SN-DMG-' . $serialNumber->id . '-' . now()->format('YmdHis');
        $productName = $serialNumber->masterProduct?->name ?? "Product ID: {$productId}";

        if ($deductedSourceStock) {
            InventoryMovement::create([
                'warehouse_id' => $sourceWarehouse->id,
                'master_product_id' => $productId,
                'movement_type' => 'out',
                'quantity' => -1,
                'movement_date' => now()->toDateString(),
                'reference_no' => $referenceNo,
                'reference_type' => 'serial_number_status_update',
                'movement_no' => $movementNo . '-OUT',
                'notes' => "Serial Number marked broken. Moved {$referenceNo} ({$productName}) to {$targetWarehouse->name}.",
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }

        InventoryMovement::create([
            'warehouse_id' => $targetWarehouse->id,
            'master_product_id' => $productId,
            'movement_type' => 'in',
            'quantity' => 1,
            'movement_date' => now()->toDateString(),
            'reference_no' => $referenceNo,
            'reference_type' => 'serial_number_status_update',
            'movement_no' => $movementNo . '-IN',
            'notes' => "Serial Number marked broken. Received {$referenceNo} ({$productName}) from {$sourceWarehouse->name}.",
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);
    }

    public function destroy(SerialNumber $serialNumber)
    {
        try {
            $serialNumber->delete();

            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Serial Number deleted successfully.'
                ]);
            }

            return redirect()->route('warehouse.serial-numbers.index')
                ->with('success', 'Serial Number berhasil dihapus.');
        } catch (\Exception $e) {
            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete serial number: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete serial numbers.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:serial_numbers,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = SerialNumber::whereIn('id', $request->ids)->delete();

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Successfully deleted {$deletedCount} serial number(s)."
                ]);
            }

            return redirect()->route('warehouse.serial-numbers.index')
                ->with('success', "Successfully deleted {$deletedCount} serial number(s).");
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete serial numbers: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Failed to delete serial numbers: ' . $e->getMessage());
        }
    }

    public function checkSerialNumber(Request $request)
    {
        $request->validate([
            'serial_number' => 'required|string|max:100',
        ]);

        $serialNumber = SerialNumber::where('serial_number', $request->serial_number)
            ->with(['warehouse', 'masterProduct', 'createdBy', 'updatedBy'])
            ->first();

        if (!$serialNumber) {
            return response()->json([
                'found' => false,
                'message' => 'Serial number tidak ditemukan.',
            ]);
        }

        return response()->json([
            'found' => true,
            'serialNumber' => $serialNumber,
            'message' => 'Serial number ditemukan.',
        ]);
    }

    public function bulkCreate(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:master_products,id',
            'start_serial' => 'required|string|max:100',
            'count' => 'required|integer|min:1|max:100',
            'unit_status' => 'required|in:available,in_use,maintenance,damaged,retired',
            'transfer_status' => 'required|in:in_warehouse,transferred,installed',
        ]);

        try {
            DB::beginTransaction();

            // Check if product has serial number requirement
            $product = MasterProduct::find($request->product_id);
            if (!$product->has_serial_number) {
                throw new \Exception('Produk ini tidak memerlukan serial number.');
            }

            $startSerial = $request->start_serial;
            $count = $request->count;
            $createdCount = 0;

            for ($i = 0; $i < $count; $i++) {
                $serialNo = $startSerial . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
                
                // Check if serial number already exists
                $exists = SerialNumber::where('serial_no', $serialNo)->exists();
                
                if (!$exists) {
                    SerialNumber::create([
                        'warehouse_id' => $request->warehouse_id,
                        'product_id' => $request->product_id,
                        'serial_no' => $serialNo,
                        'unit_status' => $request->unit_status,
                        'transfer_status' => $request->transfer_status,
                        'notes' => 'Bulk created',
                        'created_by' => Auth::id(),
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();

            return back()->with('success', "Berhasil membuat {$createdCount} serial number.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function transfer(Request $request, SerialNumber $serialNumber)
    {
        $request->validate([
            'target_warehouse_id' => 'required|exists:warehouses,id',
            'transfer_status' => 'required|in:in_warehouse,transferred,installed',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $serialNumber->update([
                'warehouse_id' => $request->target_warehouse_id,
                'transfer_status' => $request->transfer_status,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return back()->with('success', 'Serial Number berhasil ditransfer.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, SerialNumber $serialNumber)
    {
        $request->validate([
            'unit_status' => 'required|in:available,in_use,maintenance,damaged,retired',
            'notes' => 'nullable|string',
        ]);

        try {
            $serialNumber->update([
                'unit_status' => $request->unit_status,
                'notes' => $request->notes,
            ]);

            return back()->with('success', 'Status Serial Number berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        try {
            DB::beginTransaction();

            // Here you would implement the actual file import logic
            // For now, we'll just return a success message
            $importedCount = 0;

            // Process the uploaded file
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                // Process CSV/Excel file and create serial numbers
                // This is a placeholder for the actual import logic
                $importedCount = 10; // Example count
            }

            DB::commit();

            return back()->with('success', "Berhasil mengimpor {$importedCount} serial number.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $query = SerialNumber::with(['warehouse', 'product']);

        // Apply filters
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('unit_status')) {
            $query->where('unit_status', $request->unit_status);
        }

        $serialNumbers = $query->get();

        // Here you would implement the actual Excel export logic
        // For now, we'll just return a success message

        return back()->with('success', "Berhasil mengekspor {$serialNumbers->count()} serial number.");
    }
}

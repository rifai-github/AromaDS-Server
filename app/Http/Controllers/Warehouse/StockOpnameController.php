<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\User;
use App\Services\Warehouse\BranchWarehouseResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StockOpnameController extends Controller
{
    use \App\Http\Traits\ColumnFilterTrait;
    use \App\Http\Traits\AccessControlFilterTrait;

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
            ->unique()
            ->values()
            ->all();
    }

    private function availableWarehouseSerialNumbers(int $warehouseId, int $productId)
    {
        return \App\Models\SerialNumber::query()
            ->where('warehouse_id', $warehouseId)
            ->where('master_product_id', $productId)
            ->whereIn('status', ['ready', 'available'])
            ->where(function ($query) {
                $query->whereNull('location_type')
                    ->orWhere('location_type', 'warehouse');
            })
            ->whereDoesntHave('unitOnWalls', function ($query) {
                $query->whereIn('status', ['active', 'installed', 'on_wall', 'on wall', 'onwall']);
            })
            ->pluck('serial_number')
            ->map(fn ($serialNumber) => strtoupper(trim((string) $serialNumber)))
            ->unique()
            ->values();
    }

    private function resolveAdjustmentSerialNumbersFromOpnameDetail($detail, int $warehouseId): array
    {
        $product = $detail->masterProduct;
        if (! $product?->requiresSerialNumber()) {
            return [];
        }

        $variance = (int) $detail->variance;
        $requiredQuantity = abs($variance);
        if ($requiredQuantity < 1) {
            return [];
        }

        $scanned = collect($this->normalizeSerialNumbers($detail->scanned_serial_numbers ?? []));
        if ($scanned->isEmpty()) {
            return [];
        }

        $available = $this->availableWarehouseSerialNumbers($warehouseId, (int) $detail->master_product_id);

        if ($variance > 0) {
            $newSerials = $scanned->diff($available)->values();

            if ($newSerials->count() === $requiredQuantity) {
                return $newSerials->all();
            }

            if ($scanned->count() === $requiredQuantity) {
                return $scanned->all();
            }

            return $newSerials->take($requiredQuantity)->all();
        }

        return $available->diff($scanned)->take($requiredQuantity)->values()->all();
    }

    private function resolveWarehouseFromRequest(Request $request): Warehouse
    {
        // The create form always submits a specific warehouse_id (a required
        // <select> populated from every active warehouse) - a branch can
        // legitimately have several (e.g. separate barang-baru/bekas/rusak/
        // spare-part/on-wall warehouses under one branch). Honor that exact
        // pick; only fall back to "the one active warehouse for this branch"
        // when the caller didn't name a warehouse at all - BranchWarehouseResolver
        // assumes exactly one, which does not hold once a branch has more than
        // one active warehouse. Mirrors the identical fix in
        // StockAdjustmentController::resolveWarehouseFromRequest().
        if ($request->filled('warehouse_id')) {
            $warehouse = Warehouse::where('id', $request->warehouse_id)
                ->where('is_active', true)
                ->first();

            if ($warehouse) {
                return $warehouse;
            }
        }

        $branchId = $request->branch_id ?? Auth::user()?->branch_id;

        return app(BranchWarehouseResolver::class)->resolveActiveForBranch($branchId);
    }

    public function index(Request $request)
    {
        $query = StockOpname::with(['branch', 'warehouse', 'personResponsible', 'createdBy', 'updatedBy']);
        
        // Fix for Hierarchy Access Leaks: Apply access control filter
        // Use 'person_responsible' instead of default 'marketing_id'
        // warehouse_id for Warehouse Manager access
        $query = $this->applyAccessControlFilter($query, null, 'created_by', 'person_responsible', 'branch_id', null, 'warehouse_id');

        // Filtering
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('person_responsible')) {
            $query->where('person_responsible', $request->person_responsible);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('opname_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('opname_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('opname_number', 'like', '%' . $search . '%')
                  ->orWhere('notes', 'like', '%' . $search . '%')
                  ->orWhere('completion_notes', 'like', '%' . $search . '%')
                  ->orWhereHas('branch', function ($branchQuery) use ($search) {
                      $branchQuery->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('warehouse', function ($warehouseQuery) use ($search) {
                      $warehouseQuery->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('personResponsible', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('createdBy', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        // Apply column filters
        $columnMap = [
            'opname_number' => ['column' => 'opname_number'],
            'branch.name' => ['relation' => 'branch', 'column' => 'name'],
            'warehouse.name' => ['relation' => 'warehouse', 'column' => 'name'],
            'personResponsible.name' => ['relation' => 'personResponsible', 'column' => 'name'],
            'opname_date' => ['column' => 'opname_date', 'type' => 'date'],
            'status' => ['column' => 'status'],
            'createdBy.name' => ['relation' => 'createdBy', 'column' => 'name'],
            'created_at' => ['column' => 'created_at', 'type' => 'date'],
            'updatedBy.name' => ['relation' => 'updatedBy', 'column' => 'name'],
            'updated_at' => ['column' => 'updated_at', 'type' => 'date'],
        ];
        $this->applyColumnFilters($query, 'stock_opnames', $columnMap);

        $stockOpnames = $query->orderBy('created_at', 'desc')->paginateStd(25);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $stockOpnames->items(),
                'pagination' => [
                    'total' => $stockOpnames->total(),
                    'per_page' => $stockOpnames->perPage(),
                    'current_page' => $stockOpnames->currentPage(),
                    'last_page' => $stockOpnames->lastPage(),
                    'from' => $stockOpnames->firstItem(),
                    'to' => $stockOpnames->lastItem(),
                ]
            ]);
        }

        $managedWarehouse = \App\Models\Warehouse::where('manager', Auth::id())->first();

        return view('warehouse.stock-opnames.index', compact('stockOpnames', 'managedWarehouse'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'person_responsible' => 'nullable|exists:users,id',
            'opname_date' => 'required|date',
            'status' => 'required|in:draft,in-progress,completed,waiting for approval,approved',
            'notes' => 'nullable|string|max:500',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all()),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $warehouse = $this->resolveWarehouseFromRequest($request);
            $branchId = $request->branch_id ?: $warehouse->branch_id;

            // 2. Generate Opname Number with new format: BRANCH-SO/YYYY-MM/0001
            $opnameNumber = StockOpname::generateOpnameNumber($branchId);

            $stockOpname = StockOpname::create([
                'opname_no' => $opnameNumber,
                'opname_number' => $opnameNumber, // Populate both to handle dual column schema
                'branch_id' => $branchId,
                'warehouse_id' => $warehouse->id,
                'person_responsible' => $request->person_responsible ?? Auth::id(),
                'opname_date' => $request->opname_date,
                'status' => 'draft',
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Optimized: Create Stock Opname only for products registered in this warehouse
            $products = DB::table('master_products')
                ->join('warehouse_products', function($join) use ($warehouse) {
                    $join->on('master_products.id', '=', 'warehouse_products.master_product_id')
                         ->where('warehouse_products.warehouse_id', '=', $warehouse->id);
                })
                ->where('master_products.deleted_at', null)
                ->select(
                    'master_products.id as master_product_id',
                    'warehouse_products.quantity as system_stock'
                )
                ->get();

            // Prepare data for bulk insert
            $detailsData = [];
            $now = now()->toDateTimeString(); // Ensure string format for insert
            
            foreach ($products as $product) {
                // Determine completion variance: 
                // Initially physical_stock is NULL, so variance = -system_stock (loss of everything)
                // However, logically variance should be 0 until counted, or -system if we assume 0 physical.
                // Based on previous code: variance = -systemStock.
                
                $systemStock = (float) $product->system_stock;

                $detailsData[] = [
                    'stock_opname_id' => $stockOpname->id,
                    'master_product_id' => $product->master_product_id,
                    'system_stock' => $systemStock,
                    'physical_stock' => null,
                    'variance' => -$systemStock, 
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Insert in chunks to avoid query size limits
            foreach (array_chunk($detailsData, 500) as $chunk) {
                \App\Models\StockOpnameDetail::insert($chunk);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock opname created successfully',
                'data' => $stockOpname->load(['branch', 'warehouse', 'personResponsible', 'createdBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create stock opname: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(StockOpname $stockOpname)
    {
        $stockOpname->load([
            'branch', 
            'warehouse', 
            'personResponsible', 
            'createdBy', 
            'updatedBy', 
            'stockOpnameDetails.masterProduct' // Fix: Eager load product for details
        ]);

        return view('warehouse.stock-opnames.show', compact('stockOpname'));
    }

    public function edit(StockOpname $stockOpname)
    {
        $stockOpname->load(['branch', 'warehouse', 'personResponsible', 'createdBy', 'updatedBy']);

        return response()->json([
            'status' => 'success',
            'data' => $stockOpname
        ]);
    }

    public function update(Request $request, StockOpname $stockOpname)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'person_responsible' => 'nullable|exists:users,id',
            'opname_date' => 'required|date',
            'status' => 'required|in:draft,in-progress,completed,waiting for approval,approved',
            'notes' => 'nullable|string|max:500',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $warehouse = $this->resolveWarehouseFromRequest($request);
            $branchId = $request->branch_id ?: $warehouse->branch_id;

            $stockOpname->update([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouse->id,
                'person_responsible' => $request->person_responsible,
                'opname_date' => $request->opname_date,
                'status' => $request->status,
                'notes' => $request->notes,
                'started_at' => $request->started_at,
                'completed_at' => $request->completed_at,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock opname updated successfully',
                'data' => $stockOpname->load(['branch', 'warehouse', 'personResponsible', 'createdBy', 'updatedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update stock opname: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(StockOpname $stockOpname)
    {
        try {
            DB::beginTransaction();
            $stockOpname->delete();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Stock opname deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete stock opname: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:stock_opnames,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            $deletedCount = StockOpname::whereIn('id', $request->ids)->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} stock opname(s).",
                'count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk delete stock opnames: ' . $e->getMessage()
            ], 500);
        }
    }

    public function start(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->status !== 'draft') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft opnames can be started.'
                ], 400);
            }
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('error', 'Only draft opnames can be started.');
        }

        try {
            DB::beginTransaction();
            $stockOpname->start();
            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Stock opname started successfully',
                    'data' => $stockOpname->load(['branch', 'warehouse', 'personResponsible', 'createdBy', 'updatedBy'])
                ]);
            }
            
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('success', 'Stock opname started successfully');
        } catch (\Exception $e) {
            DB::rollback();
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to start stock opname: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('error', 'Failed to start stock opname: ' . $e->getMessage());
        }
    }

    public function complete(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->status !== 'in-progress') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only in-progress opnames can be completed.'
                ], 400);
            }
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('error', 'Only in-progress opnames can be completed.');
        }

        $validator = Validator::make($request->all(), [
            'completion_notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();
            $stockOpname->complete($request->completion_notes);
            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Stock opname completed successfully',
                    'data' => $stockOpname->load(['branch', 'warehouse', 'personResponsible', 'createdBy', 'updatedBy'])
                ]);
            }
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('success', 'Stock opname completed successfully');
        } catch (\Exception $e) {
            DB::rollback();
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to complete stock opname: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('error', 'Failed to complete stock opname: ' . $e->getMessage());
        }
    }

    public function approve(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->status !== 'waiting for approval') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only opnames waiting for approval can be approved.'
                ], 400);
            }
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('error', 'Only opnames waiting for approval can be approved.');
        }

        try {
            DB::beginTransaction();
            $stockOpname->approve();
            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Stock opname approved successfully',
                    'data' => $stockOpname->load(['branch', 'warehouse', 'personResponsible', 'createdBy', 'updatedBy'])
                ]);
            }
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('success', 'Stock opname approved successfully');
        } catch (\Exception $e) {
            DB::rollback();
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to approve stock opname: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('error', 'Failed to approve stock opname: ' . $e->getMessage());
        }
    }

    public function submit(Request $request, StockOpname $stockOpname)
    {
        if (!in_array($stockOpname->status, ['draft', 'in-progress', 'completed'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft, in-progress, or completed opnames can be submitted for approval.'
            ], 400);
        }

        try {
            DB::beginTransaction();
            $stockOpname->submitForApproval();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock opname submitted for approval successfully',
                'data' => $stockOpname->load(['branch', 'warehouse', 'personResponsible', 'createdBy', 'updatedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit stock opname: ' . $e->getMessage()
            ], 500);
        }
    }

    public function unpost(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->status !== 'approved') {
            if (!$request->expectsJson()) {
                return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                    ->with('error', 'Only approved opnames can be unposted.');
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Only approved opnames can be unposted.'
            ], 400);
        }

        try {
            DB::beginTransaction();
            $stockOpname->unpost();
            DB::commit();

            if (!$request->expectsJson()) {
                return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                    ->with('success', 'Stock opname unposted successfully');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Stock opname unposted successfully',
                'data' => $stockOpname->load(['branch', 'warehouse', 'personResponsible', 'createdBy', 'updatedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            if (!$request->expectsJson()) {
                return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                    ->with('error', 'Failed to unpost stock opname: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to unpost stock opname: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateDetail(Request $request, \App\Models\StockOpnameDetail $detail)
    {
        $validator = Validator::make($request->all(), [
            'physical_stock' => 'nullable|numeric|min:0', // Optional - hanya update jika dikirim
            'notes' => 'nullable|string|max:255',
            'scanned_serial_numbers' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $updateData = [];
            
            // Hanya update physical_stock jika dikirim
            if ($request->has('physical_stock') && $request->physical_stock !== null) {
                $physicalStock = $request->physical_stock;
                $variance = $physicalStock - $detail->system_stock;
                $updateData['physical_stock'] = $physicalStock;
                $updateData['variance'] = $variance;
            }
            
            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }

            if ($request->has('scanned_serial_numbers')) {
                $updateData['scanned_serial_numbers'] = $request->scanned_serial_numbers;
            }
            
            if (!empty($updateData)) {
                $detail->update($updateData);
            }

            DB::commit();

            $detail->load(['masterProduct', 'stockOpname']);
            $user = Auth::user();
            $canViewSystemStock = $user?->hasPermission('warehouse.stock-opnames.view-system-stock')
                || $user?->hasRole('Admin')
                || $user?->hasRole('super_admin')
                || $user?->hasRoleStartingWith('Management');

            $detailData = $detail->toArray();
            if (!$canViewSystemStock) {
                $detailData['system_stock'] = null;
                $detailData['variance'] = null;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Detail updated successfully',
                'data' => $detailData
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update detail: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dashboard()
    {
        $totalOpnames = StockOpname::count();
        $draftOpnames = StockOpname::where('status', 'draft')->count();
        $inProgressOpnames = StockOpname::where('status', 'in-progress')->count();
        $completedOpnames = StockOpname::where('status', 'completed')->count();
        $approvedOpnames = StockOpname::where('status', 'approved')->count();

        $recentOpnames = StockOpname::with(['branch', 'warehouse', 'personResponsible', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $monthlyOpnames = StockOpname::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $warehouseOpnames = StockOpname::with(['warehouse'])
            ->selectRaw('warehouse_id, COUNT(*) as count')
            ->groupBy('warehouse_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_opnames' => $totalOpnames,
                'draft_opnames' => $draftOpnames,
                'in_progress_opnames' => $inProgressOpnames,
                'completed_opnames' => $completedOpnames,
                'approved_opnames' => $approvedOpnames,
                'recent_opnames' => $recentOpnames,
                'monthly_opnames' => $monthlyOpnames,
                'warehouse_opnames' => $warehouseOpnames
            ]
        ]);
    }
    public function createAdjustment(Request $request, StockOpname $stockOpname)
    {
        try {
            return Cache::lock('stock-opnames:create-adjustment:' . $stockOpname->id, 10)->block(5, function () use ($stockOpname) {
                return DB::transaction(function () use ($stockOpname) {
                    $stockOpname = StockOpname::whereKey($stockOpname->id)->lockForUpdate()->firstOrFail();
                    $adjustmentReason = 'Adjustment from Stock Opname ' . $stockOpname->opname_number;

                    $existingAdjustment = \App\Models\StockAdjustment::where('warehouse_id', $stockOpname->warehouse_id)
                        ->where('reason', $adjustmentReason)
                        ->latest('id')
                        ->first();

                    if ($existingAdjustment) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Stock adjustment for this opname already exists.',
                            'data' => [
                                'adjustment_id' => $existingAdjustment->id,
                                'adjustment_number' => $existingAdjustment->adjustment_no,
                            ],
                        ], 400);
                    }

                    if ($stockOpname->status !== 'approved') {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Only approved opnames can create adjustment.'
                        ], 400);
                    }

                    $detailsWithVariance = $stockOpname->stockOpnameDetails()
                        ->with(['masterProduct.productCategory', 'masterProduct.productType'])
                        ->where('variance', '!=', 0)
                        ->get();
                    if ($detailsWithVariance->count() === 0) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No variance found in this opname. No adjustment needed.'
                        ], 400);
                    }

                    $adjustment = \App\Models\StockAdjustment::create([
                        'adjustment_no' => \App\Models\StockAdjustment::generateAdjustmentNo($stockOpname->warehouse_id),
                        'warehouse_id' => $stockOpname->warehouse_id,
                        'reason' => $adjustmentReason,
                        'adjustment_date' => now(),
                        'status' => 'draft',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    foreach ($detailsWithVariance as $detail) {
                        \App\Models\StockAdjustmentItem::create([
                            'stock_adjustment_id' => $adjustment->id,
                            'master_product_id' => $detail->master_product_id,
                            'adjustment_qty' => abs($detail->variance),
                            'adjustment_type' => $detail->variance > 0 ? 'increase' : 'decrease',
                            'notes' => 'From SO ' . $stockOpname->opname_number,
                            'serial_numbers' => $this->resolveAdjustmentSerialNumbersFromOpnameDetail($detail, (int) $stockOpname->warehouse_id),
                        ]);
                    }

                    // Update Stock Opname status to completed
                    $stockOpname->update([
                        'status' => 'completed',
                        'updated_by' => Auth::id()
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Stock adjustment created successfully',
                        'data' => [
                            'adjustment_id' => $adjustment->id,
                            'adjustment_number' => $adjustment->adjustment_no
                        ]
                    ]);
                });
            });
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock adjustment is still being created. Please wait a moment.'
            ], 429);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import physical stock from Excel file
     */
    public function importStock(Request $request, StockOpname $stockOpname)
    {
        // Validate status
        if ($stockOpname->status !== 'in-progress') {
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('error', 'Import hanya bisa dilakukan saat status In Progress.');
        }

        // Validate file - check extension instead of MIME type
        // because HTML-based .xls files have text/html MIME type
        if (!$request->hasFile('import_file')) {
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('error', 'File tidak ditemukan. Silakan pilih file terlebih dahulu.');
        }
        
        $file = $request->file('import_file');
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, ['xlsx', 'xls'])) {
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('error', 'Format file tidak valid. Pastikan file berformat .xlsx atau .xls');
        }
        
        if ($file->getSize() > 5120 * 1024) { // 5MB
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('error', 'Ukuran file terlalu besar. Maksimum 5MB.');
        }

        try {
            $filePath = $file->getPathname();
            
            // Try to detect file type and use appropriate reader
            // The export is HTML-based XLS, so we need to handle that
            $spreadsheet = null;
            
            try {
                // First try with IOFactory auto-detect
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            } catch (\Exception $e) {
                // If failed, try Html reader (for HTML-based XLS files)
                try {
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
                    $spreadsheet = $reader->load($filePath);
                } catch (\Exception $e2) {
                    // If still failed, try reading as CSV
                    try {
                        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                        $spreadsheet = $reader->load($filePath);
                    } catch (\Exception $e3) {
                        throw new \Exception('Tidak dapat membaca file. Pastikan file adalah format Excel yang valid (.xlsx atau .xls)');
                    }
                }
            }
            
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            
            // Build lookup map: SKU => physical_stock
            // Export format: No, Product Code, Product Name, Stock Fisik
            // Data starts after header rows (skip first ~10 rows that contain warehouse info + header)
            $stockData = [];
            $dataStarted = false;
            
            foreach ($rows as $row) {
                // Detect when data rows start (header row: No, Product Code, Product Name, Stock Fisik)
                if (!$dataStarted) {
                    if (isset($row[0]) && strtolower(trim($row[0])) === 'no') {
                        $dataStarted = true;
                        continue; // Skip header row
                    }
                    continue;
                }
                
                // Process data rows
                $productCode = trim($row[1] ?? ''); // Column B: Product Code (SKU)
                $stockFisik = trim($row[3] ?? '');  // Column D: Stock Fisik
                
                if (!empty($productCode) && is_numeric($stockFisik)) {
                    $stockData[$productCode] = (int) $stockFisik;
                }
            }
            
            if (empty($stockData)) {
                return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                    ->with('error', 'Tidak ada data valid ditemukan dalam file. Pastikan kolom Stock Fisik sudah diisi.');
            }
            
            // Update stock opname details
            DB::beginTransaction();
            
            $updatedCount = 0;
            $skippedCount = 0;
            
            $details = $stockOpname->stockOpnameDetails()
                ->with('masterProduct')
                ->get();
            
            foreach ($details as $detail) {
                $sku = $detail->masterProduct->sku ?? $detail->masterProduct->sku_code ?? '';
                
                if (isset($stockData[$sku])) {
                    $newPhysicalStock = $stockData[$sku];
                    $variance = $newPhysicalStock - $detail->system_stock;
                    
                    $detail->update([
                        'physical_stock' => $newPhysicalStock,
                        'variance' => $variance,
                        'updated_by' => Auth::id(),
                    ]);
                    
                    $updatedCount++;
                } else {
                    $skippedCount++;
                }
            }
            
            DB::commit();
            
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('success', "Berhasil import {$updatedCount} produk. {$skippedCount} produk tidak ditemukan dalam file.");
                
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->route('warehouse.stock-opnames.show', $stockOpname->id)
                ->with('error', 'Gagal import file: ' . $e->getMessage());
        }
    }
    
    /**
     * Export stock for Stock Opname (blind count - no system stock)
     * Uses PhpSpreadsheet for proper .xlsx format
     */
    public function exportStock(StockOpname $stockOpname)
    {
        $stockOpname->load(['warehouse.branch', 'warehouse.managerUser']);
        $warehouse = $stockOpname->warehouse;
        
        // Get ONLY products that exist in this warehouse
        $warehouseProducts = \App\Models\WarehouseProduct::where('warehouse_id', $warehouse->id)
            ->with(['masterProduct' => function($q) {
                $q->where('is_active', true);
            }])
            ->whereHas('masterProduct', function($q) {
                $q->where('is_active', true);
            })
            ->get()
            ->sortBy(function($wp) {
                return $wp->masterProduct->name ?? '';
            });
        
        // Create spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Opname');
        
        // Header info
        $sheet->setCellValue('A1', 'FORM STOCK OPNAME');
        $sheet->setCellValue('A2', 'Opname Number');
        $sheet->setCellValue('B2', ': ' . ($stockOpname->opname_number ?? '-'));
        $sheet->setCellValue('A3', 'Warehouse Name');
        $sheet->setCellValue('B3', ': ' . ($warehouse->name ?? '-'));
        $sheet->setCellValue('A4', 'Warehouse Code');
        $sheet->setCellValue('B4', ': ' . ($warehouse->warehouse_code ?? '-'));
        $sheet->setCellValue('A5', 'Branch');
        $sheet->setCellValue('B5', ': ' . ($warehouse->branch->name ?? '-'));
        $sheet->setCellValue('A6', 'Date Export');
        $sheet->setCellValue('B6', ': ' . date('d M Y H:i'));
        $sheet->setCellValue('A7', 'Total Products');
        $sheet->setCellValue('B7', ': ' . $warehouseProducts->count() . ' produk');
        
        // Bold title
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        // Data header row at row 9
        $headerRow = 9;
        $sheet->setCellValue('A' . $headerRow, 'No');
        $sheet->setCellValue('B' . $headerRow, 'Product Code');
        $sheet->setCellValue('C' . $headerRow, 'Product Name');
        $sheet->setCellValue('D' . $headerRow, 'Stock Fisik');
        
        // Style header
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ];
        $sheet->getStyle('A' . $headerRow . ':D' . $headerRow)->applyFromArray($headerStyle);
        
        // Data rows
        $row = $headerRow + 1;
        $index = 1;
        foreach ($warehouseProducts as $wp) {
            $product = $wp->masterProduct;
            if (!$product) continue;
            
            $sheet->setCellValue('A' . $row, $index);
            $sheet->setCellValue('B' . $row, $product->sku ?? ($product->sku_code ?? '-'));
            $sheet->setCellValue('C' . $row, $product->name ?? '-');
            $sheet->setCellValue('D' . $row, ''); // Empty for user to fill
            
            // Add borders to data rows
            $sheet->getStyle('A' . $row . ':D' . $row)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            $row++;
            $index++;
        }
        
        // Auto-size columns
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Center the No column
        $sheet->getStyle('A' . $headerRow . ':A' . ($row - 1))->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        
        // Generate filename
        $filename = 'StockOpname_' . str_replace(['/', '\\'], '-', $stockOpname->opname_number) . '_' . date('Ymd_His') . '.xlsx';
        
        // Output to browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

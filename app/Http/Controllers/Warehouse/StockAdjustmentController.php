<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Models\MasterProduct;
use App\Models\User;
use App\Services\Warehouse\BranchWarehouseResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\AccessControlFilterTrait;

class StockAdjustmentController extends Controller
{
    use AccessControlFilterTrait;

    private function resolveWarehouseFromRequest(Request $request): Warehouse
    {
        $branchId = $request->branch_id;

        if (! $branchId && $request->warehouse_id) {
            $branchId = Warehouse::find($request->warehouse_id)?->branch_id;
        }

        if (! $branchId) {
            $branchId = Auth::user()?->branch_id;
        }

        return app(BranchWarehouseResolver::class)->resolveActiveForBranch($branchId);
    }

    public function index(Request $request)
    {
        $query = StockAdjustment::with(['warehouse', 'items.masterProduct', 'createdBy', 'updatedBy', 'approvedBy']);

        // Apply access control filter
        // Uses 'warehouse.branch_id' for branch hierarchy check
        // Created By is used for Hierarchy/Peer/None checks
        // warehouse_id is used for Warehouse Manager access
        $query = $this->applyAccessControlFilter($query, null, 'created_by', null, 'warehouse.branch_id', null, 'warehouse_id');

        // Apply AutoFilterable
        $query->filter($request->all());

        $adjustments = $query->orderBy('created_at', 'desc')->paginate(15);

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $adjustments
            ]);
        }

        $warehouses = Warehouse::all();
        $products = MasterProduct::all();
        $users = User::all();

        return view('warehouse.stock-adjustments.index', compact('adjustments', 'warehouses', 'products', 'users'));
    }

    public function create(Request $request)
    {
        // If warehouse_id is provided, return products linked to that warehouse
        if ($request->ajax() && $request->has('warehouse_id')) {
            $products = MasterProduct::whereHas('warehouseProducts', function($query) use ($request) {
                $query->where('warehouse_id', $request->warehouse_id);
            })
            ->select('id', 'name', 'sku', 'packaging_size_id', 'packaging_size')
            ->with('packagingSize:id,name')
            ->orderBy('name')
            ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'products' => $products
                ]
            ]);
        }

        $warehouses = Warehouse::where('is_active', true)
            ->select('id', 'name', 'warehouse_code')
            ->orderBy('name')
            ->get();

        // Return JSON for initial AJAX requests (warehouses only)
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'warehouses' => $warehouses
                ]
            ]);
        }

        return view('warehouse.stock-adjustments.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'reason' => 'required|string|max:500',
            'adjustment_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
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

            $adjustment = StockAdjustment::create([
                'adjustment_no' => StockAdjustment::generateAdjustmentNo($warehouse->id),
                'warehouse_id' => $warehouse->id,
                'reason' => $request->reason,
                'adjustment_date' => $request->adjustment_date,
                'status' => 'draft',
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock adjustment created successfully',
                'data' => $adjustment->load(['warehouse', 'masterProduct', 'createdBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create stock adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(StockAdjustment $stock_adjustment)
    {
        $adjustment = $stock_adjustment;
        $adjustment->load(['warehouse', 'items.masterProduct', 'createdBy', 'updatedBy', 'approvedBy']);

        // Return JSON for AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $adjustment
            ]);
        }

        return view('warehouse.stock-adjustments.show', compact('adjustment'));
    }

    public function edit(StockAdjustment $stock_adjustment)
    {
        $adjustment = $stock_adjustment;
        $adjustment->load(['warehouse', 'masterProduct', 'createdBy', 'approvedBy']);
        $warehouses = Warehouse::all();
        $products = MasterProduct::all();
        $users = User::all();

        // Return JSON for AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'adjustment' => $adjustment,
                    'warehouses' => $warehouses,
                    'products' => $products,
                    'users' => $users
                ]
            ]);
        }

        return view('warehouse.stock-adjustments.edit', compact('adjustment', 'warehouses', 'products', 'users'));
    }

    public function update(Request $request, StockAdjustment $stock_adjustment)
    {
        $adjustment = $stock_adjustment;
        $validator = Validator::make($request->all(), [
            'branch_id' => 'sometimes|nullable|exists:branches,id',
            'warehouse_id' => 'sometimes|nullable|exists:warehouses,id',
            'reason' => 'sometimes|required|string|max:500',
            'adjustment_date' => 'sometimes|required|date',
            'notes' => 'sometimes|nullable|string|max:1000',
            'status' => 'sometimes|required|in:draft,waiting for approval,approved,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updateData = $request->only(['reason', 'adjustment_date', 'notes', 'status']);
            if ($request->has('warehouse_id') || $request->has('branch_id')) {
                $updateData['warehouse_id'] = $this->resolveWarehouseFromRequest($request)->id;
            }
            $updateData['updated_by'] = Auth::id();

            $adjustment->update($updateData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock adjustment updated successfully',
                'data' => $adjustment->load(['warehouse', 'items.masterProduct', 'createdBy', 'updatedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update stock adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(StockAdjustment $stock_adjustment)
    {
        $adjustment = $stock_adjustment;
        try {
            $adjustment->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock adjustment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete stock adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:stock_adjustments,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = StockAdjustment::whereIn('id', $request->ids)->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Successfully deleted {$deletedCount} stock adjustment(s)."
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to bulk delete stock adjustments: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approve(Request $request, StockAdjustment $stock_adjustment)
    {
        $adjustment = $stock_adjustment;
        if ($adjustment->status === 'approved') {
            return response()->json(['status' => 'error', 'message' => 'Adjustment already approved'], 400);
        }

        try {
            DB::beginTransaction();

            $adjustment->approve(Auth::id());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock adjustment approved successfully',
                'data' => $adjustment->load(['warehouse', 'items.masterProduct', 'createdBy', 'approvedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve stock adjustment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addItem(Request $request, StockAdjustment $stock_adjustment)
    {
        $validator = Validator::make($request->all(), [
            'master_product_id' => 'required|exists:master_products,id',
            'adjustment_qty' => 'required|numeric|min:0.01',
            'adjustment_type' => 'required|in:increase,decrease',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Check if adjustment is still editable
            if ($stock_adjustment->status !== 'draft') {
                return response()->json(['status' => 'error', 'message' => 'Cannot add item to non-draft adjustment'], 403);
            }

            $item = \App\Models\StockAdjustmentItem::create([
                'stock_adjustment_id' => $stock_adjustment->id,
                'master_product_id' => $request->master_product_id,
                'adjustment_qty' => $request->adjustment_qty,
                'adjustment_type' => $request->adjustment_type,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Item added successfully',
                'data' => $item->load('masterProduct')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroyItem($itemId)
    {
        try {
            DB::beginTransaction();

            $item = \App\Models\StockAdjustmentItem::findOrFail($itemId);
            
            // Check if adjustment is still editable
            $adjustment = $item->stockAdjustment;
            if ($adjustment->status !== 'draft') {
                return response()->json(['status' => 'error', 'message' => 'Cannot delete item from non-draft adjustment'], 403);
            }

            $item->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Item deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => 'Failed to delete item: ' . $e->getMessage()], 500);
        }
    }

    public function reject(Request $request, StockAdjustment $stock_adjustment)
    {
        $adjustment = $stock_adjustment;
        $validator = Validator::make($request->all(), [
            'notes' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            $adjustment->reject(Auth::id());
            $adjustment->update(['notes' => $request->notes]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Stock adjustment rejected successfully',
                    'data' => $adjustment->load(['warehouse', 'masterProduct', 'createdBy', 'approvedBy'])
                ]);
            }

            return redirect()->route('warehouse.stock-adjustments.show', $adjustment->id)
                ->with('success', 'Stock adjustment rejected successfully');
        } catch (\Exception $e) {
            DB::rollback();
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to reject stock adjustment: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to reject stock adjustment: ' . $e->getMessage());
        }
    }

    public function dashboard()
    {
        $totalAdjustments = StockAdjustment::count();
        $pendingAdjustments = StockAdjustment::pending()->count();
        $approvedAdjustments = StockAdjustment::approved()->count();
        $rejectedAdjustments = StockAdjustment::rejected()->count();

        $increaseAdjustments = StockAdjustment::byType('increase')->count();
        $decreaseAdjustments = StockAdjustment::byType('decrease')->count();

        $recentAdjustments = StockAdjustment::with(['warehouse', 'masterProduct', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $monthlyAdjustments = StockAdjustment::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $warehouseAdjustments = StockAdjustment::with(['warehouse'])
            ->selectRaw('warehouse_id, COUNT(*) as count')
            ->groupBy('warehouse_id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_adjustments' => $totalAdjustments,
                'pending_adjustments' => $pendingAdjustments,
                'approved_adjustments' => $approvedAdjustments,
                'rejected_adjustments' => $rejectedAdjustments,
                'increase_adjustments' => $increaseAdjustments,
                'decrease_adjustments' => $decreaseAdjustments,
                'recent_adjustments' => $recentAdjustments,
                'monthly_adjustments' => $monthlyAdjustments,
                'warehouse_adjustments' => $warehouseAdjustments
            ]
        ]);
    }
}

<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\MasterProduct;
use App\Models\SerialNumber;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Warehouse\BranchWarehouseResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockAdjustmentController extends Controller
{
    use AccessControlFilterTrait;

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

    private function getAvailableSerialNumbers(int $warehouseId, int $productId)
    {
        return SerialNumber::query()
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
            ->orderBy('serial_number')
            ->orderBy('id')
            ->get(['id', 'serial_number'])
            ->map(fn (SerialNumber $serialNumber) => [
                'id' => $serialNumber->id,
                'serial_number' => strtoupper(trim((string) $serialNumber->serial_number)),
            ])
            ->values();
    }

    private function resolveWarehouseFromRequest(Request $request): Warehouse
    {
        // The create form always submits a specific warehouse_id (it's a
        // required <select>, populated from all active warehouses - a branch
        // can legitimately have several, e.g. separate "barang baru / bekas /
        // rusak / spare part / on wall" warehouses under one branch). Honor
        // that exact pick; only fall back to "the one active warehouse for
        // this branch" when the caller didn't name a warehouse at all -
        // BranchWarehouseResolver assumes exactly one, which does not hold
        // once a branch has more than one active warehouse.
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
        $query = StockAdjustment::with(['warehouse', 'items.masterProduct', 'createdBy', 'updatedBy', 'approvedBy']);

        // Apply access control filter
        // Uses 'warehouse.branch_id' for branch hierarchy check
        // Created By is used for Hierarchy/Peer/None checks
        // warehouse_id is used for Warehouse Manager access
        $query = $this->applyAccessControlFilter($query, null, 'created_by', null, 'warehouse.branch_id', null, 'warehouse_id');

        // Apply AutoFilterable
        $query->filter($request->all());

        $adjustments = $query->orderBy('created_at', 'desc')->paginateStd(25);

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $adjustments,
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
            // Hanya produk material — unit rental & paket sewa (ProductCategory
            // 'Rental'), fixed asset, dan item biaya tidak di-adjust dari sini.
            $products = MasterProduct::whereHas('warehouseProducts', function ($query) use ($request) {
                $query->where('warehouse_id', $request->warehouse_id);
            })
                ->materialOnly()
                ->select('id', 'name', 'sku', 'packaging_size_id', 'packaging_size', 'product_category_id', 'product_type_id')
                ->with(['packagingSize:id,name', 'productCategory:id,has_serial_number,is_unit', 'productType:id,has_serial_number,is_unit'])
                ->withCount('serialNumbers')
                ->orderBy('name')
                ->get()
                ->map(function (MasterProduct $product) use ($request) {
                    $requiresSerialNumber = $product->requiresSerialNumber();

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'packaging_size' => $product->packagingSize
                            ? ['id' => $product->packagingSize->id, 'name' => $product->packagingSize->name]
                            : $product->packaging_size,
                        'requires_serial_number' => $requiresSerialNumber,
                        'requires_unique_serial_number' => $product->requiresUniqueSerialNumber(),
                        'available_serial_numbers' => $requiresSerialNumber
                            ? $this->getAvailableSerialNumbers((int) $request->warehouse_id, (int) $product->id)
                            : [],
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'products' => $products,
                ],
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
                    'warehouses' => $warehouses,
                ],
            ]);
        }

        return redirect()->route('warehouse.stock-adjustments.index');
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
                'errors' => $validator->errors(),
            ], 422);
        }

        $warehouse = $this->resolveWarehouseFromRequest($request);
        $reason = trim((string) $request->reason);
        $notes = $request->filled('notes') ? trim((string) $request->notes) : null;
        $lockKey = 'stock-adjustments:create:'.sha1(implode('|', [
            Auth::id(),
            $warehouse->id,
            $request->adjustment_date,
            $reason,
            $notes ?? '',
        ]));

        try {
            return Cache::lock($lockKey, 10)->block(5, function () use ($request, $warehouse, $reason, $notes) {
                return DB::transaction(function () use ($request, $warehouse, $reason, $notes) {
                    $recentDuplicate = StockAdjustment::where('warehouse_id', $warehouse->id)
                        ->whereDate('adjustment_date', $request->adjustment_date)
                        ->where('reason', $reason)
                        ->where('status', 'draft')
                        ->where('created_by', Auth::id())
                        ->where('created_at', '>=', now()->subMinute())
                        ->whereDoesntHave('items')
                        ->where(function ($query) use ($notes) {
                            if ($notes === null) {
                                $query->whereNull('notes')->orWhere('notes', '');

                                return;
                            }

                            $query->where('notes', $notes);
                        })
                        ->latest('id')
                        ->first();

                    if ($recentDuplicate) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Stock adjustment already created from this submission',
                            'data' => $recentDuplicate->load(['warehouse', 'masterProduct', 'createdBy']),
                        ]);
                    }

                    $adjustment = StockAdjustment::create([
                        'adjustment_no' => StockAdjustment::generateAdjustmentNo($warehouse->id),
                        'warehouse_id' => $warehouse->id,
                        'reason' => $reason,
                        'adjustment_date' => $request->adjustment_date,
                        'status' => 'draft',
                        'notes' => $notes,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Stock adjustment created successfully',
                        'data' => $adjustment->load(['warehouse', 'masterProduct', 'createdBy']),
                    ]);
                });
            });
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock adjustment is still being created. Please wait a moment.',
            ], 429);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create stock adjustment: '.$e->getMessage(),
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
                'data' => $adjustment,
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
                    'users' => $users,
                ],
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
            'status' => 'sometimes|required|in:draft,waiting for approval,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
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
                'data' => $adjustment->load(['warehouse', 'items.masterProduct', 'createdBy', 'updatedBy']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update stock adjustment: '.$e->getMessage(),
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
                'message' => 'Stock adjustment deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete stock adjustment: '.$e->getMessage(),
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:stock_adjustments,id',
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = StockAdjustment::whereIn('id', $request->ids)->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Successfully deleted {$deletedCount} stock adjustment(s).",
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to bulk delete stock adjustments: '.$e->getMessage(),
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
                'data' => $adjustment->load(['warehouse', 'items.masterProduct', 'createdBy', 'approvedBy']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => collect($e->errors())->flatten()->first() ?: 'Serial number validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve stock adjustment: '.$e->getMessage(),
            ], 500);
        }
    }

    public function rollback(Request $request, StockAdjustment $stock_adjustment)
    {
        $adjustment = $stock_adjustment;
        if ($adjustment->status !== 'approved') {
            return response()->json(['status' => 'error', 'message' => 'Hanya stock adjustment yang sudah approved yang bisa di-rollback.'], 400);
        }

        try {
            DB::beginTransaction();

            $adjustment->rollback(Auth::id());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock adjustment berhasil di-rollback ke draft',
                'data' => $adjustment->load(['warehouse', 'items.masterProduct', 'createdBy', 'updatedBy', 'approvedBy']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => collect($e->errors())->flatten()->first() ?: 'Rollback gagal divalidasi.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to rollback stock adjustment: '.$e->getMessage(),
            ], 500);
        }
    }

    public function addItem(Request $request, StockAdjustment $stock_adjustment)
    {
        $validator = Validator::make($request->all(), [
            'master_product_id' => 'required|exists:master_products,id',
            'adjustment_qty' => 'required|integer|min:1',
            'adjustment_type' => 'required|in:increase,decrease',
            'notes' => 'nullable|string|max:255',
            'serial_numbers' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if adjustment is still editable
            if ($stock_adjustment->status !== 'draft') {
                return response()->json(['status' => 'error', 'message' => 'Cannot add item to non-draft adjustment'], 403);
            }

            $product = MasterProduct::with(['productCategory', 'productType'])->findOrFail($request->master_product_id);
            $serialNumbers = $this->normalizeSerialNumbers($request->input('serial_numbers', []));

            if ($product->requiresSerialNumber()) {
                $quantity = (int) $request->adjustment_qty;

                if (count($serialNumbers) !== $quantity) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 'error',
                        'message' => "Produk {$product->name} wajib memiliki {$quantity} serial number.",
                    ], 422);
                }

                if ($product->requiresUniqueSerialNumber()
                    && count($serialNumbers) !== count(array_unique($serialNumbers))) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Serial number tidak boleh duplikat dalam item adjustment.',
                    ], 422);
                }

                if ($request->adjustment_type === 'increase') {
                    if ($product->requiresUniqueSerialNumber()) {
                        // Soft-deleted serial numbers (e.g. removed via adjustment rollback)
                        // are gone for good and must be re-registrable.
                        $existing = SerialNumber::whereIn('serial_number', $serialNumbers)
                            ->pluck('serial_number')
                            ->unique()
                            ->values();

                        if ($existing->isNotEmpty()) {
                            DB::rollBack();

                            return response()->json([
                                'status' => 'error',
                                'message' => 'Serial number sudah terdaftar: '.$existing->implode(', '),
                            ], 422);
                        }
                    }
                } else {
                    $availableCounts = collect($this->getAvailableSerialNumbers((int) $stock_adjustment->warehouse_id, (int) $product->id))
                        ->pluck('serial_number')
                        ->countBy();
                    $missing = collect($serialNumbers)
                        ->countBy()
                        ->filter(fn ($requestedCount, $serialNumber) => ($availableCounts[$serialNumber] ?? 0) < $requestedCount)
                        ->keys()
                        ->values();

                    if ($missing->isNotEmpty()) {
                        DB::rollBack();

                        return response()->json([
                            'status' => 'error',
                            'message' => 'Serial number tidak tersedia di warehouse ini: '.$missing->implode(', '),
                        ], 422);
                    }
                }
            } else {
                $serialNumbers = [];
            }

            $item = \App\Models\StockAdjustmentItem::create([
                'stock_adjustment_id' => $stock_adjustment->id,
                'master_product_id' => $request->master_product_id,
                'adjustment_qty' => $request->adjustment_qty,
                'adjustment_type' => $request->adjustment_type,
                'notes' => $request->notes,
                'serial_numbers' => $serialNumbers,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Item added successfully',
                'data' => $item->load('masterProduct'),
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
                'message' => 'Item deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['status' => 'error', 'message' => 'Failed to delete item: '.$e->getMessage()], 500);
        }
    }

    public function reject(Request $request, StockAdjustment $stock_adjustment)
    {
        $adjustment = $stock_adjustment;
        $validator = Validator::make($request->all(), [
            'notes' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $adjustment->reject(Auth::id());
            $adjustment->update(['notes' => $request->notes]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Stock adjustment rejected successfully',
                'data' => $adjustment->load(['warehouse', 'masterProduct', 'createdBy', 'approvedBy']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject stock adjustment: '.$e->getMessage(),
            ], 500);
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
                'warehouse_adjustments' => $warehouseAdjustments,
            ],
        ]);
    }
}

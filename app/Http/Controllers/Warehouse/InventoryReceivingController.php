<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\InventoryMovement;
use App\Models\InventoryReceiving;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryReceivingController extends Controller
{
    use AccessControlFilterTrait;

    private function productRequiresSerialNumber($product): bool
    {
        return (bool) ($product?->requiresSerialNumber()
            ?? $product?->productCategory?->has_serial_number
            ?? $product?->productType?->has_serial_number
            ?? false);
    }

    private function syncReceivedQuantitiesForProduct(InventoryReceiving $inventoryReceiving, int $productId): void
    {
        $items = $inventoryReceiving->items()
            ->where('master_product_id', $productId)
            ->orderBy('id')
            ->get();

        $remainingScanned = \App\Models\SerialNumber::where('inventory_receiving_id', $inventoryReceiving->id)
            ->where('master_product_id', $productId)
            ->count();

        foreach ($items as $item) {
            $allocated = min((float) $item->quantity, $remainingScanned);
            $item->update([
                'quantity_received' => $allocated,
            ]);
            $remainingScanned -= $allocated;
        }
    }

    private function validateNoDuplicateReceivingProducts(array $items): void
    {
        $productIds = collect($items)
            ->map(fn ($item) => $item['master_product_id'] ?? null)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $duplicateIds = $productIds->duplicates()->unique()->values();
        if ($duplicateIds->isEmpty()) {
            return;
        }

        $productNames = \App\Models\MasterProduct::whereIn('id', $duplicateIds)->pluck('name')->implode(', ');
        throw \Illuminate\Validation\ValidationException::withMessages([
            'items' => 'Produk tidak boleh double dalam satu inventory receiving: '.($productNames ?: $duplicateIds->implode(', ')),
        ]);
    }

    private function buildReceivingItemsFromIssuing(?\App\Models\InventoryIssuing $issuing): array
    {
        if (! $issuing) {
            return [];
        }

        $issuing->loadMissing(['items.product']);

        return $issuing->items
            ->map(function ($item) use ($issuing) {
                if (! $item->product_id) {
                    return null;
                }

                $issuedQty = (float) $item->quantity_issued;
                $requestedQty = (float) $item->quantity_requested;
                $quantity = $issuedQty > 0 ? $issuedQty : $requestedQty;
                if ($quantity <= 0) {
                    return null;
                }

                $notes = "Copied from Inventory Issuing {$issuing->issuing_number}";
                if ($item->room_name) {
                    $notes .= "; Room: {$item->room_name}";
                }
                $notes .= "; WI Item: {$item->id}";
                if ($item->notes) {
                    $notes .= "; {$item->notes}";
                }

                return [
                    'master_product_id' => $item->product_id,
                    'quantity' => $quantity,
                    'quantity_received' => 0,
                    'notes' => $notes,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resolveReceivingItemsForStore(Request $request): array
    {
        if ($request->has('items') && is_array($request->items) && count($request->items) > 0) {
            return $request->items;
        }

        if (! $request->filled('issuing_id')) {
            return [];
        }

        $issuing = \App\Models\InventoryIssuing::with('items')->find($request->issuing_id);

        return $this->buildReceivingItemsFromIssuing($issuing);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Query with proper relationships according to BRD
        $query = InventoryReceiving::with(['branch', 'receivedFrom', 'receivedBy', 'updatedBy', 'createdBy', 'issuing.branch', 'issuing.warehouse.branch']);

        // Apply AutoFilterable
        $query->filter($request->all());

        // Apply Access Control
        $user = Auth::user();
        // Uses 'branch_id' for branch separation and 'issuing.warehouse_id' for warehouse manager access
        // 'received_from' added as marketing field to allow receivers to see the data
        $query = $this->applyAccessControlFilter($query, $user, 'created_by', 'received_from', 'branch_id', function ($q) use ($user) {
            // Allow if user is the receiver
            $q->orWhere('received_by_old', $user->id);
        }, 'issuing.warehouse_id');

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by search (receiving number or reference)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('receiving_number', 'like', "%{$searchTerm}%")
                    ->orWhere('reference_no', 'like', "%{$searchTerm}%")
                    ->orWhere('id', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by date range (only if provided in request)
        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from;
            $query->where(function ($q) use ($dateFrom) {
                // If received, check receive_date. If pending/other and receive_date is null, fallback to created_at
                $q->where('receive_date', '>=', $dateFrom)
                    ->orWhere(function ($subq) use ($dateFrom) {
                        $subq->whereNull('receive_date')
                            ->whereDate('created_at', '>=', $dateFrom);
                    });
            });
        }
        if ($request->filled('date_to')) {
            $dateTo = $request->date_to;
            $query->where(function ($q) use ($dateTo) {
                $q->where('receive_date', '<=', $dateTo)
                    ->orWhere(function ($subq) use ($dateTo) {
                        $subq->whereNull('receive_date')
                            ->whereDate('created_at', '<=', $dateTo);
                    });
            });
        }

        $receivings = $query->orderBy('created_at', 'desc')->paginate(5);

        // Get dropdown data
        $branches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $issuings = \App\Models\InventoryIssuing::orderBy('created_at', 'desc')->get();

        $statistics = [
            'total' => InventoryReceiving::count(),
            'pending' => InventoryReceiving::where('status', 'pending')->count(),
            'received' => InventoryReceiving::where('status', 'received')->count(),
            'cancelled' => InventoryReceiving::where('status', 'cancelled')->count(),
        ];

        return view('warehouse.inventory-receivings.index', compact('receivings', 'branches', 'users', 'issuings', 'statistics'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $branches = \App\Models\Branch::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $issuings = \App\Models\InventoryIssuing::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'branches' => $branches,
                'users' => $users,
                'issuings' => $issuings,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'reference_no' => 'required|string|max:100',
            'issuing_id' => 'nullable|exists:inventory_issuings,id',
            'branch_id' => 'required|exists:branches,id',
            'received_from' => 'required|exists:users,id',
            'receive_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.master_product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string',
        ]);
        if ($request->has('items') && is_array($request->items)) {
            $this->validateNoDuplicateReceivingProducts($request->items);
        }

        try {
            DB::beginTransaction();

            $receivingItems = $this->resolveReceivingItemsForStore($request);
            if ($request->filled('issuing_id') && empty($receivingItems)) {
                throw new \RuntimeException('Inventory issuing yang dipilih tidak memiliki item yang bisa dibuatkan receiving.');
            }

            // Generate unique receiving number
            $receivingNumber = $this->generateReceivingNumber();

            $receiving = InventoryReceiving::create([
                'receiving_number' => $receivingNumber,
                'reference_no' => $request->reference_no,
                'issuing_id' => $request->issuing_id,
                'branch_id' => $request->branch_id,
                'received_from' => $request->received_from,
                'receive_date' => $request->receive_date,
                'notes' => $request->notes,
                'status' => 'pending',
                'received_by_old' => Auth::id(),
                'created_by' => Auth::id(),
            ]);

            // Create receiving items if provided
            if (! empty($receivingItems)) {
                foreach ($receivingItems as $item) {
                    $receiving->items()->create([
                        'master_product_id' => $item['master_product_id'],
                        'quantity' => $item['quantity'],
                        'quantity_received' => $item['quantity_received'] ?? null,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            DB::commit();

            // Always return JSON for AJAX requests
            return response()->json([
                'status' => 'success',
                'message' => 'Inventory receiving created successfully.',
                'data' => $receiving->load(['branch', 'receivedFrom', 'receivedBy', 'items.product']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            // Always return JSON for AJAX requests
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create inventory receiving: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $inventoryReceiving = InventoryReceiving::with(['branch', 'receivedFrom', 'receivedBy', 'issuing', 'items.product.productType'])->find($id);

        if (! $inventoryReceiving) {
            if (request()->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Inventory receiving not found'], 404);
            }
            abort(404);
        }

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $inventoryReceiving->id,
                    'receiving_number' => $inventoryReceiving->receiving_number,
                    'reference_no' => $inventoryReceiving->reference_no,
                    'branch_name' => $inventoryReceiving->branch?->name,
                    'received_from_name' => $inventoryReceiving->receivedFrom?->name,
                    'received_by_name' => $inventoryReceiving->receivedBy?->name,
                    'receive_date' => $inventoryReceiving->receive_date?->format('Y-m-d'),
                    'status' => $inventoryReceiving->status,
                    'notes' => $inventoryReceiving->notes,
                    'issuing_number' => $inventoryReceiving->issuing?->issuing_number,
                ],
            ]);
        }

        // Load serial numbers for products in this receiving (only SNs linked to this receiving)
        $serialNumbers = [];
        $remainingQuantities = []; // Store remaining quantity for each product

        foreach ($inventoryReceiving->items->groupBy('master_product_id') as $productId => $items) {
            if ($productId) {
                $sns = \App\Models\SerialNumber::where('master_product_id', $productId)
                    ->where('inventory_receiving_id', $inventoryReceiving->id)
                    ->with('warehouse')
                    ->orderBy('created_at', 'desc')
                    ->get();
                $serialNumbers[$productId] = $sns;

                // Calculate remaining quantity across all rows for this product.
                $registeredSNCount = $sns->count();
                $requestedQty = (float) $items->sum('quantity');
                $remainingQuantities[$productId] = max(0, $requestedQty - $registeredSNCount);
            }
        }

        return view('warehouse.inventory-receivings.show', [
            'receiving' => $inventoryReceiving,
            'serialNumbers' => $serialNumbers,
            'remainingQuantities' => $remainingQuantities,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryReceiving $inventoryReceiving)
    {
        if ($inventoryReceiving->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot edit receiving that is not in pending status.',
            ], 400);
        }

        $inventoryReceiving->load(['branch', 'receivedFrom', 'receivedBy', 'issuing']);
        $branches = \App\Models\Branch::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $issuings = \App\Models\InventoryIssuing::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'receiving' => $inventoryReceiving,
                'branches' => $branches,
                'users' => $users,
                'issuings' => $issuings,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InventoryReceiving $inventoryReceiving)
    {
        // Support partial update (for modal edits)
        $validationRules = [];
        $updateData = ['updated_by' => Auth::id()];

        if ($request->has('branch_id')) {
            $validationRules['branch_id'] = 'nullable|exists:branches,id';
            $updateData['branch_id'] = $request->branch_id;
        }

        if ($request->has('receive_date')) {
            $validationRules['receive_date'] = 'nullable|date';
            $updateData['receive_date'] = $request->receive_date;
        }

        if ($request->has('schedule_date')) {
            $validationRules['schedule_date'] = 'nullable|date';
            $updateData['schedule_date'] = $request->schedule_date;
        }

        if ($request->has('received_from')) {
            $validationRules['received_from'] = 'nullable|exists:users,id';
            $updateData['received_from'] = $request->received_from;
        }

        if ($request->has('notes')) {
            $validationRules['notes'] = 'nullable|string';
            $updateData['notes'] = $request->notes;
        }

        // Full update validation (if status provided)
        if ($request->has('status')) {
            if ($inventoryReceiving->status !== 'pending' && $request->status !== $inventoryReceiving->status) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update receiving that is not in pending status.',
                ], 400);
            }

            $validationRules = array_merge($validationRules, [
                'reference_no' => 'required|string|max:100',
                'issuing_id' => 'nullable|exists:inventory_issuings,id',
                'branch_id' => 'required|exists:branches,id',
                'received_from' => 'required|exists:users,id',
                'receive_date' => 'required|date',
                'status' => 'required|in:pending,received,cancelled',
            ]);

            $updateData = array_merge($updateData, [
                'reference_no' => $request->reference_no,
                'issuing_id' => $request->issuing_id,
                'branch_id' => $request->branch_id,
                'received_from' => $request->received_from,
                'receive_date' => $request->receive_date,
                'status' => $request->status,
            ]);
        }

        $request->validate($validationRules);

        try {
            DB::beginTransaction();

            $inventoryReceiving->update($updateData);

            if (
                $inventoryReceiving->status === 'pending'
                && $request->filled('issuing_id')
                && $inventoryReceiving->items()->doesntExist()
            ) {
                $issuing = \App\Models\InventoryIssuing::with('items')->find($request->issuing_id);
                $receivingItems = $this->buildReceivingItemsFromIssuing($issuing);

                if (empty($receivingItems)) {
                    throw new \RuntimeException('Inventory issuing yang dipilih tidak memiliki item yang bisa dibuatkan receiving.');
                }

                foreach ($receivingItems as $item) {
                    $inventoryReceiving->items()->create([
                        'master_product_id' => $item['master_product_id'],
                        'quantity' => $item['quantity'],
                        'quantity_received' => $item['quantity_received'] ?? null,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            $inventoryReceiving->load(['branch', 'receivedFrom', 'receivedBy']);

            DB::commit();

            // Always return JSON for AJAX requests
            return response()->json([
                'status' => 'success',
                'message' => 'Inventory receiving updated successfully.',
                'data' => [
                    'id' => $inventoryReceiving->id,
                    'branch_id' => $inventoryReceiving->branch_id,
                    'branch_name' => $inventoryReceiving->branch?->name,
                    'receive_date' => $inventoryReceiving->receive_date?->format('Y-m-d'),
                    'schedule_date' => $inventoryReceiving->schedule_date?->format('Y-m-d'),
                    'received_from' => $inventoryReceiving->received_from,
                    'received_from_name' => $inventoryReceiving->receivedFrom?->name,
                    'notes' => $inventoryReceiving->notes,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            // Always return JSON for AJAX requests
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update inventory receiving: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryReceiving $inventoryReceiving)
    {
        if ($inventoryReceiving->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete receiving that is not in pending status.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $inventoryReceiving->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory receiving deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete inventory receiving: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Receive the inventory.
     */
    public function receive(InventoryReceiving $inventoryReceiving)
    {
        if ($inventoryReceiving->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only receive pending inventory.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Load items and issuing to get warehouse_id
            $inventoryReceiving->load(['items.product', 'issuing.warehouse']);

            // Get warehouse_id from issuing or branch
            $warehouseId = null;
            if ($inventoryReceiving->issuing && $inventoryReceiving->issuing->warehouse_id) {
                $warehouseId = $inventoryReceiving->issuing->warehouse_id;
            } elseif ($inventoryReceiving->branch_id) {
                // Get warehouse from branch (first active warehouse in that branch)
                $warehouse = \App\Models\Warehouse::where('branch_id', $inventoryReceiving->branch_id)
                    ->where('is_active', true)
                    ->first();
                if ($warehouse) {
                    $warehouseId = $warehouse->id;
                }
            }

            if (! $warehouseId) {
                DB::rollback();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot determine warehouse for stock update.',
                ], 400);
            }

            // Update inventory receiving status
            $inventoryReceiving->update([
                'status' => 'received',
                'received_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            // Auto-update stock in warehouse_products for each item
            $updatedProducts = [];
            foreach ($inventoryReceiving->items as $item) {
                if (! $item->master_product_id || ! $item->quantity) {
                    continue;
                }

                // Find or create warehouse_product record
                $warehouseProduct = \App\Models\WarehouseProduct::firstOrCreate(
                    [
                        'warehouse_id' => $warehouseId,
                        'master_product_id' => $item->master_product_id,
                    ],
                    [
                        'quantity' => 0,
                        'minimum_stock' => 0,
                        'maximum_stock' => 0,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]
                );

                // Increment stock quantity
                $oldQuantity = $warehouseProduct->quantity;
                $newQuantity = $oldQuantity + $item->quantity;

                $warehouseProduct->update([
                    'quantity' => $newQuantity,
                    'updated_by' => Auth::id(),
                ]);

                $updatedProducts[] = [
                    'product_name' => $item->product->name ?? 'Unknown',
                    'old_quantity' => $oldQuantity,
                    'received_quantity' => $item->quantity,
                    'new_quantity' => $newQuantity,
                ];

                \Log::info("Stock updated for product {$item->master_product_id} in warehouse {$warehouseId}: {$oldQuantity} + {$item->quantity} = {$newQuantity}");

                // Create inventory movement record for receiving (stock masuk)
                // Ensure all required data exists
                if (! $warehouseId || ! $item->master_product_id || ! $item->quantity || $item->quantity <= 0) {
                    \Log::warning("Skipping movement creation - missing required data: warehouse_id={$warehouseId}, product_id={$item->master_product_id}, quantity={$item->quantity}");

                    continue;
                }

                $productName = $item->product->name ?? ($item->masterProduct->name ?? 'Product ID: '.$item->master_product_id);
                $receivingNumber = $inventoryReceiving->receiving_number ?? "REC-{$inventoryReceiving->id}";

                $movementData = [
                    'warehouse_id' => $warehouseId,
                    'master_product_id' => $item->master_product_id,
                    'movement_type' => 'in', // Stock masuk
                    'quantity' => abs($item->quantity), // Pastikan positif
                    'notes' => "Inventory received. Receiving Number: {$receivingNumber}, Product: {$productName}",
                    'created_by' => Auth::id() ?? 1, // Fallback to system user
                    'updated_by' => Auth::id() ?? 1, // Fallback to system user
                ];

                // Fill movement data directly using new columns
                $movementData['movement_date'] = $inventoryReceiving->receive_date ?? now()->toDateString();
                $movementData['reference_no'] = $receivingNumber;
                $movementData['reference_type'] = 'inventory_receiving';
                $movementData['movement_no'] = 'REC-'.str_replace('REC-', '', $receivingNumber);

                if (isset($item->unit_price) && $item->unit_price > 0) {
                    $movementData['unit_price'] = $item->unit_price;
                    $movementData['total_value'] = abs($item->quantity) * $item->unit_price;
                }

                try {
                    InventoryMovement::create($movementData);
                    \Log::info("Inventory Movement created for receiving: Product {$item->master_product_id}, Quantity: {$item->quantity}");
                } catch (\Exception $e) {
                    \Log::error('Failed to create Inventory Movement for receiving: '.$e->getMessage());
                    // Don't throw, continue with other items
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory received successfully and stock updated.',
                'updated_products' => $updatedProducts,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Failed to receive inventory: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to receive inventory: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel the receiving.
     */
    public function cancel(InventoryReceiving $inventoryReceiving)
    {
        if ($inventoryReceiving->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only cancel pending receiving.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $inventoryReceiving->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory receiving cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel receiving: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finalize receiving: update stock and complete request.
     */
    public function finalize(InventoryReceiving $inventoryReceiving)
    {
        if ($inventoryReceiving->status !== 'pending') {
            return back()->with('error', 'Can only finalize pending receiving.');
        }

        try {
            DB::beginTransaction();

            // Load items and get warehouse_id
            $inventoryReceiving->load(['items.product', 'branch']);

            // Validate Serial Numbers for products that require them
            $serialNumbers = \App\Models\SerialNumber::where('inventory_receiving_id', $inventoryReceiving->id)
                ->get()
                ->groupBy('master_product_id');

            $snCountsByProduct = $serialNumbers->map(fn ($items) => $items->count());

            foreach ($inventoryReceiving->items->groupBy('master_product_id') as $productId => $items) {
                $firstItem = $items->first();
                $snCount = (int) ($snCountsByProduct[$productId] ?? 0);
                $isSerialTracked = $this->productRequiresSerialNumber($firstItem->product) || $snCount > 0;

                if (! $isSerialTracked) {
                    continue;
                }

                $expectedQty = (float) $items->sum('quantity');

                if ($snCount != $expectedQty) {
                    DB::rollback();

                    return back()->with('error', "Product '{$firstItem->product?->name}' requires Serial Number. Found {$snCount}, expected {$expectedQty}. Please input all serial numbers before finalizing.");
                }

                $this->syncReceivedQuantitiesForProduct($inventoryReceiving, (int) $productId);
            }

            $inventoryReceiving->load(['items.product', 'branch']);

            // Get warehouse_id from branch (first active warehouse in that branch)
            $warehouse = \App\Models\Warehouse::where('branch_id', $inventoryReceiving->branch_id)
                ->where('is_active', true)
                ->first();

            if (! $warehouse) {
                DB::rollback();

                return back()->with('error', 'Cannot find active warehouse for this branch.');
            }

            // Update stock in warehouse_products for each item
            $updatedProducts = [];
            foreach ($inventoryReceiving->items as $item) {
                if (! $item->master_product_id || ! $item->quantity) {
                    continue;
                }

                // Determine received quantity based on SN requirement
                $hasSerial = $this->productRequiresSerialNumber($item->product)
                    || (($snCountsByProduct[$item->master_product_id] ?? 0) > 0);

                if ($hasSerial) {
                    // For SN items, strict adherence to what was scanned/inputted. Default to 0 if null.
                    $receivedQty = (float) ($item->quantity_received ?? 0);
                } else {
                    // For Non-SN items, Auto-Receive Full Quantity if not explicitly set (NULL or 0)
                    // This fixes the issue where Refill/Cleaner items showed 0 Received
                    $receivedQty = (float) (($item->quantity_received === null || $item->quantity_received == 0) ? $item->quantity : $item->quantity_received);
                }

                // Persist the determined quantity to the item so it displays correctly in UI and Inventory Request
                if ($item->quantity_received != $receivedQty) {
                    $item->update(['quantity_received' => $receivedQty]);
                    $item->quantity_received = $receivedQty; // Update instance for later use in this request
                }

                // Find or create warehouse_product record
                $warehouseProduct = \App\Models\WarehouseProduct::firstOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'master_product_id' => $item->master_product_id,
                    ],
                    [
                        'quantity' => 0,
                        'minimum_stock' => 0,
                        'maximum_stock' => 0,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]
                );

                // Increment stock quantity using finalized received quantity
                $oldQuantity = $warehouseProduct->quantity;
                $newQuantity = $oldQuantity + $receivedQty;

                $warehouseProduct->update([
                    'quantity' => $newQuantity,
                    'updated_by' => Auth::id(),
                ]);

                $updatedProducts[] = [
                    'product_name' => $item->product->name ?? 'Unknown',
                    'old_quantity' => $oldQuantity,
                    'received_quantity' => $receivedQty,
                    'new_quantity' => $newQuantity,
                ];

                \Log::info("Stock updated for product {$item->master_product_id} in warehouse {$warehouse->id}: {$oldQuantity} + {$receivedQty} = {$newQuantity}");

                // Create inventory movement record for receiving (stock masuk)
                // Ensure all required data exists
                if (! $warehouse->id || ! $item->master_product_id || ! $item->quantity || $item->quantity <= 0) {
                    \Log::warning("Skipping movement creation - missing required data: warehouse_id={$warehouse->id}, product_id={$item->master_product_id}, quantity={$item->quantity}");

                    continue;
                }

                $productName = $item->product->name ?? ($item->masterProduct->name ?? 'Product ID: '.$item->master_product_id);
                $receivingNumber = $inventoryReceiving->receiving_number ?? "REC-{$inventoryReceiving->id}";

                $movementData = [
                    'warehouse_id' => $warehouse->id,
                    'master_product_id' => $item->master_product_id,
                    'movement_type' => 'in', // Stock masuk
                    'quantity' => abs($receivedQty), // Pastikan positif dan gunakan receivedQty
                    'notes' => "Inventory received. Receiving Number: {$receivingNumber}, Product: {$productName}",
                    'created_by' => Auth::id() ?? 1, // Fallback to system user
                    'updated_by' => Auth::id() ?? 1, // Fallback to system user
                ];

                // Fill movement data directly using new columns
                $movementData['movement_date'] = $inventoryReceiving->receive_date ?? now()->toDateString();
                $movementData['reference_no'] = $receivingNumber;
                $movementData['reference_type'] = 'inventory_receiving';
                $movementData['movement_no'] = 'REC-'.str_replace('REC-', '', $receivingNumber);

                if (isset($item->unit_price) && $item->unit_price > 0) {
                    $movementData['unit_price'] = $item->unit_price;
                    $movementData['total_value'] = abs($receivedQty) * $item->unit_price;
                }

                try {
                    InventoryMovement::create($movementData);
                    \Log::info("Inventory Movement created for receiving: Product {$item->master_product_id}, Quantity: {$receivedQty}");
                } catch (\Exception $e) {
                    \Log::error('Failed to create Inventory Movement for receiving: '.$e->getMessage());
                    // Don't throw, continue with other items
                }
            }

            // Update all Serial Numbers linked to this receiving to 'ready'
            $updatedSNCount = \App\Models\SerialNumber::where('inventory_receiving_id', $inventoryReceiving->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'ready',
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            if ($updatedSNCount > 0) {
                \Log::info("Updated {$updatedSNCount} Serial Numbers to 'ready' status for Receiving {$inventoryReceiving->receiving_number}");
            }

            // Update receiving status
            $inventoryReceiving->update([
                'status' => 'received',
                'receive_date' => now(),
                'updated_by' => Auth::id(),
            ]);

            // Update related inventory request status to completed and sync received_qty
            if ($inventoryReceiving->reference_no) {
                $request = \App\Models\InventoryRequest::where('request_number', $inventoryReceiving->reference_no)->first();
                if ($request && $request->status === 'shipped') {
                    // Update request status
                    $request->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);

                    // Auto-sync received_qty to inventory request items
                    // RELOAD items to ensure we get the latest quantity_received values from DB
                    // (especially for Non-SN items that were just updated in the previous loop)
                    $inventoryReceiving->load('items');

                    foreach ($inventoryReceiving->items as $receivingItem) {
                        if (! $receivingItem->master_product_id || ! $receivingItem->quantity) {
                            continue;
                        }

                        // Find matching request item by product_id
                        $requestItem = $request->items()
                            ->where('master_product_id', $receivingItem->master_product_id)
                            ->first();

                        if ($requestItem) {
                            // Get the received quantity
                            // If quantity_received is explicitly set (including 0), use it.
                            // If null, default to 0 (assume nothing received if not specified)
                            // This prevents "Auto-Receive" behavior which caused confusion
                            $receivedQty = (float) ($receivingItem->quantity_received ?? 0);

                            // Calculate returned_qty = issued_qty - received_qty
                            // issued_qty is what was sent from central warehouse
                            $issuedQty = (float) ($requestItem->issued_qty ?? $requestItem->quantity ?? 0);
                            $returnedQty = max(0, $issuedQty - $receivedQty);

                            // Update both received_qty and returned_qty
                            $requestItem->update([
                                'received_qty' => $receivedQty,
                                'returned_qty' => $returnedQty,
                                'updated_by' => Auth::id(),
                            ]);

                            \Log::info("Auto-synced for request item {$requestItem->id}: received_qty={$receivedQty}, returned_qty={$returnedQty} (issued_qty was {$issuedQty})");
                        }
                    }

                    \Log::info("Inventory Request {$request->request_number} marked as completed with received quantities synced");
                }
            }

            DB::commit();

            return back()->with('success', 'Inventory receiving finalized successfully! Stock updated and request completed.');
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Failed to finalize receiving: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return back()->with('error', 'Failed to finalize receiving: '.$e->getMessage());
        }
    }

    /**
     * Scan/Input Serial Number for Inventory Receiving
     * Validates if SN already exists, if not, creates new SN
     */
    public function scanSerialNumber(Request $request, InventoryReceiving $inventoryReceiving)
    {
        $request->validate([
            'master_product_id' => 'required|exists:master_products,id',
            'serial_number' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($inventoryReceiving->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya bisa input Serial Number untuk receiving dengan status pending.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $serialNumber = strtoupper(trim($request->serial_number));

            // Get warehouse from receiving branch
            $warehouse = \App\Models\Warehouse::where('branch_id', $inventoryReceiving->branch_id)
                ->where('is_active', true)
                ->first();

            if (! $warehouse) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Warehouse tidak ditemukan untuk branch ini.',
                ], 422);
            }

            // Validasi: Cek apakah SN sudah ada di serial_numbers table (termasuk yang soft deleted)
            // Gunakan withTrashed() untuk mengecek juga SN yang sudah dihapus
            $existingSN = \App\Models\SerialNumber::withTrashed()
                ->where('serial_number', $serialNumber)
                ->first();

            if ($existingSN) {
                // MOM16: Allow existing SN if status is on_hand or on_hand_remove (returning from technician)
                $allowedReturnStatuses = ['on_hand', 'on_hand_remove'];

                if (in_array($existingSN->status, $allowedReturnStatuses)) {
                    // Update existing SN to point to this receiving
                    $existingSN->update([
                        'inventory_receiving_id' => $inventoryReceiving->id,
                        'warehouse_id' => $warehouse->id,
                        'status' => 'pending', // Pending until receiving is finalized
                        'notes' => ($existingSN->notes ? $existingSN->notes."\n" : '')."Returned via Receiving: {$inventoryReceiving->receiving_number}. User Note: ".$request->notes,
                        'updated_by' => Auth::id(),
                    ]);

                    $newSerialNumber = $existingSN;
                    \Log::info("Serial Number {$serialNumber} (ID: {$existingSN->id}) is being RETURNED via Receiving {$inventoryReceiving->receiving_number}");
                } else {
                    DB::rollBack();

                    // Buat pesan error yang lebih informatif
                    $errorMessage = "Serial Number <strong>{$serialNumber}</strong> sudah terdaftar di sistem dengan status <strong>{$existingSN->status_text}</strong>. ";

                    if ($existingSN->trashed()) {
                        $errorMessage .= 'SN ini pernah terdaftar namun sudah dihapus.';
                    } else {
                        $productName = $existingSN->masterProduct->name ?? 'Unknown Product';
                        $warehouseName = $existingSN->warehouse->name ?? 'Unknown Warehouse';

                        if ($existingSN->inventory_receiving_id) {
                            $receiving = \App\Models\InventoryReceiving::find($existingSN->inventory_receiving_id);
                            $receivingNumber = $receiving ? $receiving->receiving_number : 'Unknown';
                            $errorMessage .= "SN ini sudah terdaftar untuk produk <strong>{$productName}</strong> di warehouse <strong>{$warehouseName}</strong> pada Receiving <strong>{$receivingNumber}</strong>. Hanya bisa mengembalikan SN yang berstatus On Hand Teknisi.";
                        } else {
                            $errorMessage .= "SN ini sudah terdaftar untuk produk <strong>{$productName}</strong> di warehouse <strong>{$warehouseName}</strong>. Hanya bisa mengembalikan SN yang berstatus On Hand Teknisi.";
                        }
                    }

                    return response()->json([
                        'status' => 'error',
                        'message' => $errorMessage,
                    ], 422);
                }
            } else {
                // Create new Serial Number
                $newSerialNumber = \App\Models\SerialNumber::create([
                    'serial_number' => $serialNumber,
                    'master_product_id' => $request->master_product_id,
                    'warehouse_id' => $warehouse->id,
                    'inventory_receiving_id' => $inventoryReceiving->id,
                    'status' => 'pending', // SN pending until receiving is finalized
                    'notes' => $request->notes,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            $requestedQty = (float) $inventoryReceiving->items()
                ->where('master_product_id', $request->master_product_id)
                ->sum('quantity');

            $currentSNCount = \App\Models\SerialNumber::where('inventory_receiving_id', $inventoryReceiving->id)
                ->where('master_product_id', $request->master_product_id)
                ->count();

            if ($currentSNCount > $requestedQty) {
                throw new \Exception("Serial Number untuk produk ini sudah memenuhi qty request ({$requestedQty}).");
            }

            $this->syncReceivedQuantitiesForProduct($inventoryReceiving, (int) $request->master_product_id);

            DB::commit();

            \Log::info("Serial Number {$serialNumber} created for Inventory Receiving {$inventoryReceiving->receiving_number}");

            return response()->json([
                'status' => 'success',
                'message' => "Serial Number {$serialNumber} berhasil disimpan!",
                'remaining_quantity' => max(0, $requestedQty - $currentSNCount),
                'data' => $newSerialNumber->load(['warehouse', 'masterProduct']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to scan serial number: '.$e->getMessage());
            \Log::error('Stack trace: '.$e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan Serial Number: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get receivings by warehouse for API.
     */
    public function getReceivingsByBranch(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $receivings = InventoryReceiving::where('branch_id', $request->branch_id)
            ->with(['branch', 'receivedFrom', 'receivedBy', 'issuing'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $receivings,
        ]);
    }

    /**
     * Get receiving statistics for API.
     */
    public function getReceivingStatistics()
    {
        $statistics = [
            'total' => InventoryReceiving::count(),
            'pending' => InventoryReceiving::where('status', 'pending')->count(),
            'received' => InventoryReceiving::where('status', 'received')->count(),
            'cancelled' => InventoryReceiving::where('status', 'cancelled')->count(),
            'recent_receivings' => InventoryReceiving::with(['branch', 'receivedFrom', 'receivedBy'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }

    /**
     * Get modal data for create/edit forms.
     */
    public function getModalData()
    {
        try {
            $branches = \App\Models\Branch::orderBy('name')->get();
            $users = User::orderBy('name')->get();
            $issuings = \App\Models\InventoryIssuing::with(['branch', 'requestedBy'])
                ->orderBy('created_at', 'desc')
                ->get();
            $products = \App\Models\MasterProduct::where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'branches' => $branches,
                    'users' => $users,
                    'issuings' => $issuings,
                    'products' => $products,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load modal data: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate unique receiving number
     */
    private function generateReceivingNumber()
    {
        $prefix = 'RC';
        $date = now()->format('Ymd');

        // Get the last receiving number for today (excluding soft-deleted)
        $lastReceiving = InventoryReceiving::withoutTrashed()
            ->whereDate('created_at', today())
            ->where('receiving_number', 'like', $prefix.'-'.$date.'-%')
            ->orderBy('receiving_number', 'desc')
            ->first();

        if ($lastReceiving) {
            // Extract the sequence number and increment
            $lastSequence = (int) substr($lastReceiving->receiving_number, -4);
            $sequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $sequence = '0001';
        }

        return $prefix.'-'.$date.'-'.$sequence;
    }

    /**
     * Bulk delete inventory receivings
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:inventory_receivings,id',
        ]);

        try {
            DB::beginTransaction();

            $count = InventoryReceiving::whereIn('id', $request->ids)
                ->where('status', 'pending')
                ->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Successfully deleted {$count} inventory receiving(s).",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete inventory receivings: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update quantity received for an item (AJAX).
     */
    public function updateItemQuantity(Request $request, InventoryReceiving $inventoryReceiving)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'quantity_received' => 'required|integer|min:0',
        ]);

        try {
            // Find the item
            $item = $inventoryReceiving->items()->where('id', $request->item_id)->first();

            if (! $item) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item tidak ditemukan.',
                ], 404);
            }

            // Validate quantity_received doesn't exceed quantity
            if ($request->quantity_received > $item->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Quantity Received tidak boleh melebihi Quantity ('.$item->quantity.').',
                ], 422);
            }

            // Update quantity_received
            $item->update([
                'quantity_received' => $request->quantity_received,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Quantity Received berhasil diupdate!',
                'data' => [
                    'item_id' => $item->id,
                    'quantity_received' => $item->quantity_received,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to update item quantity: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate Quantity Received: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a serial number from this receiving (AJAX).
     */
    public function deleteSerialNumber(Request $request, InventoryReceiving $inventoryReceiving)
    {
        $request->validate([
            'serial_number_id' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();

            // Find the serial number
            $serialNumber = \App\Models\SerialNumber::where('id', $request->serial_number_id)
                ->where('inventory_receiving_id', $inventoryReceiving->id)
                ->first();

            if (! $serialNumber) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Serial Number tidak ditemukan atau tidak terkait dengan receiving ini.',
                ], 404);
            }

            // Check if SN is still in pending state (can be deleted)
            if ($serialNumber->status !== 'pending' && $inventoryReceiving->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Serial Number tidak dapat dihapus karena sudah dalam status '.$serialNumber->status.'.',
                ], 422);
            }

            $productId = $serialNumber->master_product_id;
            $snCode = $serialNumber->serial_number;

            // Hard delete the serial number
            $serialNumber->forceDelete();

            // Update receiving item quantity_received
            $requestedQty = (float) $inventoryReceiving->items()
                ->where('master_product_id', $productId)
                ->sum('quantity');

            $currentSNCount = \App\Models\SerialNumber::where('inventory_receiving_id', $inventoryReceiving->id)
                ->where('master_product_id', $productId)
                ->count();

            $this->syncReceivedQuantitiesForProduct($inventoryReceiving, (int) $productId);

            DB::commit();

            \Log::info("Serial Number {$snCode} deleted from Inventory Receiving {$inventoryReceiving->receiving_number}");

            return response()->json([
                'status' => 'success',
                'message' => "Serial Number {$snCode} berhasil dihapus!",
                'data' => [
                    'product_id' => $productId,
                    'remaining_quantity' => max(0, $requestedQty - $currentSNCount),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete serial number: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus Serial Number: '.$e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\InventoryRequest;
use App\Models\Warehouse;
use App\Models\MasterProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Http\Traits\ColumnFilterTrait;
use App\Http\Traits\AccessControlFilterTrait;

class InventoryRequestController extends Controller
{
    use ColumnFilterTrait, AccessControlFilterTrait;

    private function normalizeRequestItems(array $items): array
    {
        return collect($items)
            ->reduce(function ($carry, $item) {
                $productId = (int) ($item['master_product_id'] ?? ($item['product_id'] ?? 0));
                if (!$productId) {
                    return $carry;
                }

                if (!isset($carry[$productId])) {
                    $carry[$productId] = [
                        'master_product_id' => $productId,
                        'quantity' => (float) ($item['quantity'] ?? 0),
                        'notes' => $item['notes'] ?? null,
                    ];

                    return $carry;
                }

                $carry[$productId]['quantity'] += (float) ($item['quantity'] ?? 0);

                if (!empty($item['notes'])) {
                    $notes = array_filter([$carry[$productId]['notes'] ?? null, $item['notes']]);
                    $carry[$productId]['notes'] = implode("\n", array_unique($notes));
                }

                return $carry;
            }, []);
    }

    private function validateRequestItemQuantities(InventoryRequest $inventoryRequest): ?string
    {
        $duplicates = $inventoryRequest->items()
            ->select('master_product_id', DB::raw('COUNT(*) as total'))
            ->groupBy('master_product_id')
            ->having('total', '>', 1)
            ->with('product:id,name')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $names = MasterProduct::whereIn('id', $duplicates->pluck('master_product_id'))->pluck('name')->implode(', ');
            return 'Inventory request masih memiliki produk double: ' . ($names ?: $duplicates->pluck('master_product_id')->implode(', ')) . '. Gabungkan/hapus salah satu baris dulu.';
        }

        foreach ($inventoryRequest->items as $item) {
            $requested = (float) $item->quantity;
            $approved = (float) ($item->approved_qty ?? 0);
            $issued = (float) ($item->issued_qty ?? 0);
            $received = (float) ($item->received_qty ?? 0);

            if ($requested <= 0) {
                return "Qty Request untuk {$item->product?->name} harus lebih dari 0.";
            }

            if ($item->approved_qty !== null && ($approved < 0 || $approved > $requested)) {
                return "Qty Approved untuk {$item->product?->name} harus 0 sampai Qty Request ({$requested}).";
            }

            if ($item->issued_qty !== null && ($issued < 0 || $issued > max($approved, 0))) {
                return "Qty Issued untuk {$item->product?->name} harus 0 sampai Qty Approved ({$approved}).";
            }

            if ($item->received_qty !== null && ($received < 0 || $received > max($issued, 0))) {
                return "Qty Received untuk {$item->product?->name} harus 0 sampai Qty Issued ({$issued}).";
            }
        }

        return null;
    }

    private function ensureCanApproveInventoryRequest()
    {
        if ($this->userCanApproveInventoryRequest()) {
            return null;
        }

        return back()->with('error', 'Anda tidak memiliki akses untuk approve Inventory Request.');
    }

    private function userCanApproveInventoryRequest(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->canApprove('inventory-requests')) {
            return true;
        }

        $roleIds = $user->roles()->pluck('roles.id');

        return DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->whereIn('role_permissions.role_id', $roleIds)
            ->where('permissions.name', 'warehouse.inventory-requests.approve')
            ->exists();
    }

    private function mergeDuplicateRequestItems(InventoryRequest $inventoryRequest): void
    {
        $inventoryRequest->loadMissing('items');

        $inventoryRequest->items
            ->groupBy('master_product_id')
            ->filter(fn ($items) => $items->count() > 1)
            ->each(function ($items) {
                $primary = $items->sortBy('id')->first();
                $duplicates = $items->where('id', '!=', $primary->id);
                $quantityFields = ['quantity', 'approved_qty', 'issued_qty', 'received_qty', 'returned_qty'];
                $updates = ['updated_by' => Auth::id()];

                foreach ($quantityFields as $field) {
                    $values = $items
                        ->pluck($field)
                        ->filter(fn ($value) => $value !== null);

                    if ($values->isNotEmpty()) {
                        $updates[$field] = $values->sum(fn ($value) => (float) $value);
                    }
                }

                $notes = $items
                    ->pluck('notes')
                    ->filter()
                    ->unique()
                    ->implode("\n");

                if ($notes !== '') {
                    $updates['notes'] = $notes;
                }

                $primary->update($updates);
                $duplicates->each->delete();
            });
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Query with proper relationships and filters
        $query = InventoryRequest::with([
            'warehouse.branch', // Load warehouse->branch for fallback
            'branch', 
            'requestedBy', 
            'approvedBy', 
            'updatedBy',
            'items.product',
            'inventoryIssuing.issuedBy',
            'inventoryIssuing.receivedBy',
            'inventoryReceivings.receivedBy',
            'createdBy'
        ]);

        // Apply column filters
        $this->applyColumnFilters($query, 'inventoryRequestsTable', [
            'request_number' => ['column' => 'request_number'],
            'id' => ['column' => 'id'],
            'branch__name' => ['relation' => 'branch', 'column' => 'name'],
            'status' => ['column' => 'status'],
            'required_date' => ['column' => 'required_date', 'type' => 'date'],
            'requestedBy__name' => ['relation' => 'requestedBy', 'column' => 'name'],
            'approved_at' => ['column' => 'approved_at', 'type' => 'date'],
            'approvedBy__name' => ['relation' => 'approvedBy', 'column' => 'name'],
            'processed_date' => ['column' => 'processed_date', 'type' => 'date'],
            'shipped_at' => ['column' => 'shipped_at', 'type' => 'date'],
            'createdBy__name' => ['relation' => 'createdBy', 'column' => 'name'],
            'created_at' => ['column' => 'created_at', 'type' => 'date'],
            'updatedBy__name' => ['relation' => 'updatedBy', 'column' => 'name'],
            'updated_at' => ['column' => 'updated_at', 'type' => 'date'],
        ]);

        // Apply Access Control
        // Uses 'requested_by' as owner, 'branch_id' for branch access, and 'warehouse_id' for manager access.
        $query = $this->applyAccessControlFilter($query, null, 'requested_by', null, 'branch_id', null, 'warehouse_id');


        // Additional legacy filters
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by search (request number, branch name, or ID)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('request_number', 'like', "%{$searchTerm}%")
                  ->orWhere('id', 'like', "%{$searchTerm}%")
                  ->orWhereHas('branch', function($bq) use ($searchTerm) {
                      $bq->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }
        
        // Filter by date range (only if provided in request)
        if ($request->filled('date_from')) {
            $query->where('required_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('required_date', '<=', $request->date_to);
        }
        
        if ($request->filled('status') && !str_starts_with($request->status, 'filter_')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('created_at', 'desc');

        $requests = $query->paginateStd(25);
        
        // Get dropdown data
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $branches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        $statistics = [
            'total' => InventoryRequest::count(),
            'pending' => InventoryRequest::where('status', 'pending')->count(),
            'approved' => InventoryRequest::where('status', 'approved')->count(),
            'rejected' => InventoryRequest::where('status', 'rejected')->count(),
            'issued' => InventoryRequest::where('status', 'issued')->count(),
        ];

        return view('warehouse.inventory-requests.index', compact('requests', 'warehouses', 'branches', 'users', 'statistics'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $warehouses = $this->getInventoryRequestFormWarehouses();
        [$branches, $userBranchId] = $this->getAvailableBranchesForUser(Auth::user());
        $products = $this->getInventoryRequestFormProducts();
        $users = $this->getInventoryRequestFormUsers();

        return response()->json([
            'status' => 'success',
            'data' => [
                'warehouses' => $warehouses,
                'branches' => $branches,
                'products' => $products,
                'users' => $users,
                'current_user_branch_id' => $userBranchId,
                'can_select_branch' => $branches->count() > 1,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'required_date' => 'required|date|after_or_equal:today',
            'reason' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.master_product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string',
        ]);
        $normalizedItems = $this->normalizeRequestItems($request->items);

        try {
            DB::beginTransaction();
            $branchId = $this->resolveAuthorizedBranchId($request);

            // Auto-generate request number using DocumentNumberService
            $documentNumberService = new \App\Services\DocumentNumberService();
            $requestNumber = $documentNumberService->generate(
                'inventory_request', // type
                null, // branch code will be determined from context
                null, // building_id
                null, // contract_id
                null, // quotation_id
                null, // survey_id
                null, // warehouse_id
                $branchId // branch_id
            );

            $inventoryRequest = InventoryRequest::create([
                'request_number' => $requestNumber,
                'warehouse_id' => null, // Warehouse removed from form
                'branch_id' => $branchId,
                'request_date' => now(),
                'required_date' => $request->required_date,
                'priority' => 'medium', // Default priority
                'reason' => $request->reason,
                'notes' => $request->notes ?? $request->reason,
                'status' => 'draft', // Start as draft, not pending
                'requested_by' => Auth::id(),
            ]);

            // Create request items
            foreach ($normalizedItems as $item) {
                $inventoryRequest->items()->create([
                    'master_product_id' => $item['master_product_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            DB::commit();

            // Return JSON for AJAX requests
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Inventory request created successfully.',
                    'data' => [
                        'id' => $inventoryRequest->id,
                        'request_number' => $inventoryRequest->request_number,
                    ]
                ]);
            }

            return redirect()->route('warehouse.inventory-requests.show', $inventoryRequest->id)
                ->with('success', 'Inventory request created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON error for AJAX requests
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create inventory request: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create inventory request: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryRequest $inventoryRequest)
    {
        $inventoryRequest->load(['warehouse', 'branch', 'requestedBy', 'approvedBy', 'items.product', 'items.createdBy', 'items.updatedBy']);
        $canApproveInventoryRequest = $this->userCanApproveInventoryRequest();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $inventoryRequest->id,
                    'request_number' => $inventoryRequest->request_number,
                    'warehouse_name' => $inventoryRequest->warehouse?->name,
                    'branch_name' => $inventoryRequest->branch?->name,
                    'requested_by_name' => $inventoryRequest->requestedBy?->name,
                    'request_date' => $inventoryRequest->request_date?->format('Y-m-d'),
                    'required_date' => $inventoryRequest->required_date?->format('Y-m-d'),
                    'priority' => $inventoryRequest->priority,
                    'status' => $inventoryRequest->status,
                    'reason' => $inventoryRequest->reason,
                    'notes' => $inventoryRequest->notes,
                    'approved_by_name' => $inventoryRequest->approvedBy?->name,
                    'approved_at' => $inventoryRequest->approved_at?->format('Y-m-d H:i:s'),
                    'rejection_reason' => $inventoryRequest->rejection_reason,
                    'completed_at' => $inventoryRequest->completed_at?->format('Y-m-d H:i:s'),
                    'processed_date' => $inventoryRequest->processed_date?->format('Y-m-d H:i:s'),
                    'items' => $inventoryRequest->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_name' => $item->product?->name,
                            'quantity' => $item->quantity,
                            'notes' => $item->notes,
                        ];
                    }),
                    'total_items' => $inventoryRequest->items->count(),
                    'total_quantity' => $inventoryRequest->items->sum('quantity'),
                ]
            ]);
        }

        return view('warehouse.inventory-requests.show', [
            'requestData' => $inventoryRequest,
            'canApproveInventoryRequest' => $canApproveInventoryRequest,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryRequest $inventoryRequest)
    {
        if ($inventoryRequest->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot edit request that is not in pending status.'
            ], 400);
        }

        $inventoryRequest->load(['warehouse', 'branch', 'requestedBy', 'items.product']);
        $warehouses = $this->getInventoryRequestFormWarehouses();
        [$branches, $userBranchId] = $this->getAvailableBranchesForUser(Auth::user());
        $products = $this->getInventoryRequestFormProducts();
        $users = $this->getInventoryRequestFormUsers();

        return response()->json([
            'status' => 'success',
            'data' => [
                'request' => [
                    'id' => $inventoryRequest->id,
                    'request_number' => $inventoryRequest->request_number,
                    'warehouse_id' => $inventoryRequest->warehouse_id,
                    'branch_id' => $inventoryRequest->branch_id,
                    'request_date' => $inventoryRequest->request_date?->format('Y-m-d'),
                    'required_date' => $inventoryRequest->required_date?->format('Y-m-d'),
                    'priority' => $inventoryRequest->priority,
                    'reason' => $inventoryRequest->reason,
                    'notes' => $inventoryRequest->notes,
                    'items' => $inventoryRequest->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'master_product_id' => $item->master_product_id ?? $item->product_id ?? null,
                            'quantity' => $item->quantity,
                            'notes' => $item->notes,
                        ];
                    }),
                ],
                'warehouses' => $warehouses,
                'branches' => $branches,
                'products' => $products,
                'users' => $users,
                'current_user_branch_id' => $userBranchId,
                'can_select_branch' => $branches->count() > 1,
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InventoryRequest $inventoryRequest)
    {
        // Support partial update (for inline edits)
        $validationRules = [];
        $updateData = [];
        
        if ($request->has('required_date')) {
            $validationRules['required_date'] = 'nullable|date|after_or_equal:today';
            $updateData['required_date'] = $request->required_date;
        }

        if ($request->has('reason')) {
            $validationRules['reason'] = 'nullable|string';
            $updateData['reason'] = $request->reason;
        }
        
        if ($request->has('branch_id')) {
            $validationRules['branch_id'] = 'nullable|exists:branches,id';
            $updateData['branch_id'] = $request->branch_id;
        }
        
        // Full update validation
        if ($request->has('request_number')) {
            if ($inventoryRequest->status !== 'pending') {
                if (request()->expectsJson() || request()->is('api/*')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot update request that is not in pending status.'
                    ], 400);
                }
                return back()->with('error', 'Cannot update request that is not in pending status.');
            }
            
            $validationRules = array_merge($validationRules, [
                'request_number' => 'required|string|unique:inventory_requests,request_number,' . $inventoryRequest->id,
                'warehouse_id' => 'required|exists:warehouses,id',
                'branch_id' => 'required|exists:branches,id',
                'request_date' => 'required|date',
                'required_date' => 'nullable|date|after_or_equal:today',
                'priority' => 'required|in:low,medium,high,urgent',
                'reason' => 'required|string',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.master_product_id' => 'required|exists:master_products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.notes' => 'nullable|string',
            ]);
            
            $updateData = array_merge($updateData, [
                'request_number' => $request->request_number,
                'warehouse_id' => $request->warehouse_id,
                'branch_id' => $request->branch_id,
                'request_date' => $request->request_date,
                'required_date' => $request->required_date,
                'priority' => $request->priority,
                'reason' => $request->reason,
                'notes' => $request->notes,
            ]);
        }
        
        $request->validate($validationRules);
        $normalizedItems = $request->has('items')
            ? $this->normalizeRequestItems($request->items)
            : [];

        try {
            DB::beginTransaction();

            if ($request->has('branch_id')) {
                $updateData['branch_id'] = $this->resolveAuthorizedBranchId($request);
            }

            if (!empty($updateData)) {
                $inventoryRequest->update($updateData);
            }

            // Update items only if items array is provided
            if ($request->has('items')) {
                // Delete existing items
                $inventoryRequest->items()->delete();

                // Create new items
                foreach ($normalizedItems as $item) {
                    $inventoryRequest->items()->create([
                        'master_product_id' => $item['master_product_id'],
                        'quantity' => $item['quantity'],
                        'notes' => $item['notes'] ?? null,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            // Return JSON for AJAX requests
            if (request()->expectsJson() || request()->ajax()) {
                $inventoryRequest->load('branch');
                return response()->json([
                    'status' => 'success',
                    'message' => 'Inventory request updated successfully.',
                    'data' => [
                        'id' => $inventoryRequest->id,
                        'required_date' => $inventoryRequest->required_date?->format('Y-m-d'),
                        'branch_id' => $inventoryRequest->branch_id,
                        'branch_name' => $inventoryRequest->branch?->name,
                        'reason' => $inventoryRequest->reason,
                    ]
                ]);
            }

            return redirect()->route('warehouse.inventory-requests.show', $inventoryRequest)
                ->with('success', 'Inventory request updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update inventory request: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update inventory request: ' . $e->getMessage());
        }
    }

    /**
     * Update item quantity fields
     */
    public function updateItemQty(Request $request, $itemId)
    {
        $request->validate([
            'field' => 'required|in:quantity,approved_qty,issued_qty,received_qty,returned_qty',
            'value' => 'required|numeric|min:0',
        ]);

        try {
            $item = \App\Models\InventoryRequestItem::with('inventoryRequest')->findOrFail($itemId);
            $requestStatus = $item->inventoryRequest->status;
            $field = $request->field;
            
            // Validasi: hanya bisa edit field sesuai status request
            $allowedFieldsByStatus = [
                'draft' => ['quantity'],
                'pending' => ['approved_qty'],
                'approved' => ['issued_qty'],
                'issued' => [], // Locked: auto-filled from inventory receiving
                'shipped' => [], // Locked as per user request
                'completed' => ['returned_qty'],
            ];
            
            $allowedFields = $allowedFieldsByStatus[$requestStatus] ?? [];
            if (!in_array($field, $allowedFields)) {
                $statusLabels = [
                    'draft' => 'Draft',
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'issued' => 'Issued',
                    'shipped' => 'Shipped',
                    'completed' => 'Completed'
                ];
                return response()->json([
                    'status' => 'error',
                    'message' => "Tidak dapat mengedit {$field} saat status {$statusLabels[$requestStatus]}."
                ], 422);
            }
            
            if (in_array($field, ['quantity', 'approved_qty', 'issued_qty', 'received_qty', 'returned_qty'])) {
                if ($field === 'quantity' && $request->value <= 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Qty Request harus lebih dari 0.'
                    ], 422);
                }

                if ($field === 'approved_qty' && $request->value > $item->quantity) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Qty Approved ({$request->value}) tidak boleh lebih dari Qty Request ({$item->quantity})"
                    ], 422);
                }

                // Validasi: Qty Issued tidak boleh lebih dari Qty Approved
                if ($field === 'issued_qty') {
                    $approvedQty = $item->approved_qty ?? 0;
                    if ($request->value > $approvedQty) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Qty Issued ({$request->value}) tidak boleh lebih dari Qty Approved ({$approvedQty})"
                        ], 422);
                    }
                }
                
                // Validasi: Qty Received tidak boleh lebih dari Qty Issued
                if ($field === 'received_qty') {
                    $issuedQty = $item->issued_qty ?? 0;
                    if ($request->value > $issuedQty) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Qty Received ({$request->value}) tidak boleh lebih dari Qty Issued ({$issuedQty})"
                        ], 422);
                    }
                }
                
                // Validasi: Qty Returned tidak boleh lebih dari Qty Received
                if ($field === 'returned_qty') {
                    $receivedQty = $item->received_qty ?? 0;
                    if ($request->value > $receivedQty) {
                        return response()->json([
                            'status' => 'error',
                            'message' => "Qty Returned ({$request->value}) tidak boleh lebih dari Qty Received ({$receivedQty})"
                        ], 422);
                    }
                }
                
                $item->{$field} = $request->value;
                $item->updated_by = Auth::id();
                $item->save();
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid field name'
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Quantity updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update quantity: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryRequest $inventoryRequest)
    {
        if ($inventoryRequest->status !== 'pending') {
            return back()->with('error', 'Cannot delete request that is not in pending status.');
        }

        try {
            DB::beginTransaction();

            $inventoryRequest->items()->delete();
            $inventoryRequest->delete();

            DB::commit();

            return redirect()->route('warehouse.inventory-requests.index')
                ->with('success', 'Inventory request deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to delete inventory request: ' . $e->getMessage());
        }
    }

    /**
     * Assign warehouse to request.
     */
    public function assignWarehouse(Request $request, InventoryRequest $inventoryRequest)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        if ($inventoryRequest->status !== 'approved') {
            return back()->with('error', 'Can only assign warehouse to approved requests.');
        }

        // Validate warehouse belongs to branch (as per user request)
        $warehouse = \App\Models\Warehouse::findOrFail($request->warehouse_id);
        if ($warehouse->branch_id !== $inventoryRequest->branch_id) {
            return back()->with('error', 'Warehouse must belong to the same branch as the request.');
        }

        try {
            $inventoryRequest->update([
                'warehouse_id' => $request->warehouse_id,
            ]);

            return back()->with('success', 'Warehouse assigned successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to assign warehouse: ' . $e->getMessage());
        }
    }

    /**
     * Approve the request.
     */
    public function approve(InventoryRequest $inventoryRequest)
    {
        // Jika draft, ubah ke pending (submit for approval)
        if ($inventoryRequest->status === 'draft') {
            try {
                DB::beginTransaction();

                $inventoryRequest->update([
                    'status' => 'pending',
                ]);

                DB::commit();

                return back()->with('success', 'Inventory request submitted for approval successfully.');
            } catch (\Exception $e) {
                DB::rollback();
                return back()->with('error', 'Failed to submit request: ' . $e->getMessage());
            }
        }
        
        // Jika pending, approve (admin/supervisor/manager only)
        if ($inventoryRequest->status !== 'pending') {
            return back()->with('error', 'Can only approve pending requests.');
        }

        if ($denied = $this->ensureCanApproveInventoryRequest()) {
            return $denied;
        }

        try {
            DB::beginTransaction();

            $this->mergeDuplicateRequestItems($inventoryRequest);
            $inventoryRequest->load(['items.product']);
            foreach ($inventoryRequest->items as $item) {
                if ($item->approved_qty === null || (float) $item->approved_qty <= 0) {
                    $item->update([
                        'approved_qty' => $item->quantity,
                        'updated_by' => Auth::id(),
                    ]);
                    $item->approved_qty = $item->quantity;
                }
            }

            if ($message = $this->validateRequestItemQuantities($inventoryRequest->fresh(['items.product']))) {
                DB::rollBack();
                return back()->with('error', $message);
            }

            $inventoryRequest->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Note: Item status tracking is done at request level, not item level per DB schema

            DB::commit();

            return back()->with('success', 'Inventory request approved successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to approve request: ' . $e->getMessage());
        }
    }

    /**
     * Reject the request.
     */
    public function reject(Request $request, InventoryRequest $inventoryRequest)
    {
        if ($inventoryRequest->status !== 'pending') {
            return back()->with('error', 'Can only reject pending requests.');
        }

        if ($denied = $this->ensureCanApproveInventoryRequest()) {
            return $denied;
        }

        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $inventoryRequest->update([
                'status' => 'draft', // Balik ke draft sesuai request
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_reason' => $request->rejection_reason,
            ]);

            // Note: Item status tracking is done at request level, not item level per DB schema

            DB::commit();

            return back()->with('success', 'Inventory request rejected successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to reject request: ' . $e->getMessage());
        }
    }

    /**
     * Complete the request.
     */
    public function complete(InventoryRequest $inventoryRequest)
    {
        if ($inventoryRequest->status !== 'approved') {
            return back()->with('error', 'Can only complete approved requests.');
        }

        try {
            DB::beginTransaction();

            // Note: DB enum status is 'pending','approved','rejected','issued', not 'completed'
            // Using 'issued' as final state per DB schema
            $inventoryRequest->update([
                'status' => 'issued',
                'completed_at' => now(),
                'processed_date' => now(),
            ]);

            // Note: Item status tracking is done at request level, not item level per DB schema

            DB::commit();

            return back()->with('success', 'Inventory request completed successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to complete request: ' . $e->getMessage());
        }
    }

    /**
     * Complete issue - Logistik Pusat selesai mengisi Qty Issued
     */
    public function completeIssue(InventoryRequest $inventoryRequest)
    {
        if ($inventoryRequest->status !== 'approved') {
            return back()->with('error', 'Hanya bisa complete issue untuk request yang sudah approved.');
        }

        try {
            DB::beginTransaction();

            $this->mergeDuplicateRequestItems($inventoryRequest);
            $inventoryRequest->load(['items.product']);
            foreach ($inventoryRequest->items as $item) {
                if ($item->approved_qty === null || (float) $item->approved_qty <= 0) {
                    $item->update([
                        'approved_qty' => $item->quantity,
                        'updated_by' => Auth::id(),
                    ]);
                    $item->approved_qty = $item->quantity;
                }

                if ($item->issued_qty === null || (float) $item->issued_qty <= 0) {
                    $item->update([
                        'issued_qty' => $item->approved_qty,
                        'updated_by' => Auth::id(),
                    ]);
                    $item->issued_qty = $item->approved_qty;
                }
            }

            if ($message = $this->validateRequestItemQuantities($inventoryRequest->fresh(['items.product']))) {
                DB::rollBack();
                return back()->with('error', $message);
            }

            // Validasi: semua item harus sudah ada issued_qty (unless we just auto-filled)
            $itemsWithoutIssued = $inventoryRequest->items()->where(function($query) {
                $query->whereNull('issued_qty')
                      ->orWhere('issued_qty', 0);
            })->count();
            
            if ($itemsWithoutIssued > 0) {
                DB::rollback();
                return back()->with('error', 'Semua item harus memiliki Qty Issued sebelum complete issue.');
            }

            $inventoryRequest->update([
                'status' => 'issued',
                'processed_date' => now(),
            ]);

            DB::commit();

            return back()->with('success', 'Issue completed! Request siap untuk shipping.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to complete issue: ' . $e->getMessage());
        }
    }

    /**
     * Complete shipping and auto-create inventory receiving.
     */
    public function completeShipping(Request $request, InventoryRequest $inventoryRequest)
    {
        // Update: hanya bisa ship dari status 'issued' (bukan 'approved')
        if ($inventoryRequest->status !== 'issued') {
            return back()->with('error', 'Hanya bisa shipping untuk request yang sudah complete issue.');
        }

        $request->validate([
            'shipping_tracking_number' => 'required|string|max:255|unique:inventory_requests,shipping_tracking_number',
            'shipping_date' => 'required|date',
        ], [
            'shipping_tracking_number.unique' => 'Nomor resi ini sudah pernah digunakan.',
        ]);

        try {
            DB::beginTransaction();

            // Update request dengan shipping info
            $inventoryRequest->update([
                'shipping_tracking_number' => $request->shipping_tracking_number,
                'shipping_date' => $request->shipping_date,
                'shipped_at' => now(),
                'status' => 'shipped', // Status jadi shipped
            ]);

            // Auto-create Inventory Receiving
            $documentNumberService = new \App\Services\DocumentNumberService();
            $receivingNumber = $documentNumberService->generate(
                'inventory_receiving', // type IRC
                null,
                null,
                null,
                null, // survey_id
                null, // warehouse_id
                $inventoryRequest->branch_id // branch_id
            );

            $receiving = \App\Models\InventoryReceiving::create([
                'receiving_number' => $receivingNumber,
                'reference_no' => $inventoryRequest->request_number,
                'issuing_id' => null, // Tidak ada issuing, langsung dari request
                'branch_id' => $inventoryRequest->branch_id,
                'received_from' => $inventoryRequest->requested_by, // Dari user yang request
                'schedule_date' => now(), // Tanggal transaksi (saat data dibuat)
                'receive_date' => $request->shipping_date, // Tanggal penerimaan barang
                'notes' => "Auto-created from request {$inventoryRequest->request_number}. Tracking: {$request->shipping_tracking_number}",
                'status' => 'pending', // Status awal pending
                'received_by_old' => null,
                'created_by' => Auth::id(),
            ]);

            // Copy items dari request ke receiving - gunakan issued_qty
            foreach ($inventoryRequest->items as $item) {
                // Gunakan issued_qty karena sudah pasti ada (validated di completeIssue)
                $qty = $item->issued_qty ?? $item->approved_qty ?? $item->quantity;
                
                $receiving->items()->create([
                    'master_product_id' => $item->master_product_id,
                    'quantity' => $qty,
                    'notes' => $item->notes,
                ]);
            }

            DB::commit();

            return back()->with('success', "Shipping completed! Inventory Receiving {$receivingNumber} created successfully.");
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Failed to complete shipping: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Failed to complete shipping: ' . $e->getMessage());
        }
    }

    /**
     * Get requests by warehouse for API.
     */
    public function getRequestsByWarehouse(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $requests = InventoryRequest::where('warehouse_id', $request->warehouse_id)
            ->with(['warehouse', 'requestedBy', 'approvedBy', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $requests,
        ]);
    }

    /**
     * Bulk delete inventory requests.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:inventory_requests,id',
        ]);

        try {
            DB::beginTransaction();

            $requests = InventoryRequest::whereIn('id', $request->ids)
                ->where('status', 'pending')
                ->get();

            if ($requests->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending requests found to delete.'
                ], 400);
            }

            $count = 0;
            foreach ($requests as $request) {
                $request->items()->delete();
                $request->delete();
                $count++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "Successfully deleted {$count} inventory request(s)."
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete inventory requests: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get request statistics for API.
     */
    public function getRequestStatistics()
    {
        $statistics = [
            'total' => InventoryRequest::count(),
            'pending' => InventoryRequest::where('status', 'pending')->count(),
            'approved' => InventoryRequest::where('status', 'approved')->count(),
            'rejected' => InventoryRequest::where('status', 'rejected')->count(),
            'issued' => InventoryRequest::where('status', 'issued')->count(),
            'recent_requests' => InventoryRequest::with(['warehouse', 'requestedBy'])
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
     * Bulk update item quantity fields
     */
    public function bulkUpdateItemQty(Request $request, InventoryRequest $inventoryRequest)
    {
        $request->validate([
            'source' => 'required|in:quantity,approved_qty,issued_qty,received_qty',
            'target' => 'required|in:approved_qty,issued_qty,received_qty,returned_qty',
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:inventory_request_items,id'
        ]);

        $sourceField = $request->source;
        $targetField = $request->target;
            $itemIds = $request->item_ids;

        try {
            DB::beginTransaction();
            
            $items = $inventoryRequest->items()->whereIn('id', $itemIds)->get();
            
            foreach ($items as $item) {
                $value = $item->{$sourceField} ?? 0;
                if ($value < 0) {
                    throw ValidationException::withMessages(['value' => 'Qty tidak boleh minus.']);
                }
                
                if ($targetField === 'approved_qty') {
                    $value = min($value, $item->quantity);
                }

                // Extra validation: Issued cannot exceed Approved
                if ($targetField === 'issued_qty') {
                    $approvedQty = $item->approved_qty ?? 0;
                    if ($value > $approvedQty) $value = $approvedQty; // Cap to approved
                }
                
                $item->{$targetField} = $value;
                $item->save();
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bulk update successful.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to bulk update: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revert status from approved to pending.
     */
    public function backToPending(InventoryRequest $inventoryRequest)
    {
        if ($inventoryRequest->status !== 'approved') {
            return back()->with('error', 'Only approved requests can be reverted to pending.');
        }

        try {
            $inventoryRequest->update(['status' => 'pending']);
            return back()->with('success', 'Request reverted to pending successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to revert request: ' . $e->getMessage());
        }
    }

    /**
     * Add item to a draft request.
     */
    public function addItem(Request $request, InventoryRequest $inventoryRequest)
    {
        $request->validate([
            'master_product_id' => 'required|exists:master_products,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        if ($inventoryRequest->status !== 'draft') {
            return response()->json(['status' => 'error', 'message' => 'Only draft requests can be edited.'], 422);
        }

        try {
            $item = $inventoryRequest->items()->where('master_product_id', $request->master_product_id)->first();

            if ($item) {
                $item->update([
                    'quantity' => (float) $item->quantity + (float) $request->quantity,
                    'updated_by' => Auth::id(),
                ]);
            } else {
                $inventoryRequest->items()->create([
                    'master_product_id' => $request->master_product_id,
                    'quantity' => $request->quantity,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
            }

            return response()->json(['status' => 'success', 'message' => 'Item added successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove item from a draft request.
     */
    public function removeItem(InventoryRequest $inventoryRequest, $itemId)
    {
        if ($inventoryRequest->status !== 'draft') {
            return response()->json(['status' => 'error', 'message' => 'Only draft requests can be edited.'], 422);
        }

        try {
            $inventoryRequest->items()->where('id', $itemId)->delete();
            return response()->json(['status' => 'success', 'message' => 'Item removed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get products not already in the request.
     */
    public function getAvailableProducts(InventoryRequest $inventoryRequest)
    {
        $existingProductIds = $inventoryRequest->items->pluck('master_product_id')->toArray();
        $products = MasterProduct::where('is_active', true)
            ->whereNotIn('id', $existingProductIds)
            ->orderBy('name')
            ->get();

        return response()->json(['status' => 'success', 'data' => $products]);
    }

    private function getInventoryRequestFormWarehouses()
    {
        return Warehouse::select('id', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function getInventoryRequestFormProducts()
    {
        return MasterProduct::query()
            ->select('id', 'name', 'sku', 'packaging_size_id', 'packaging_size')
            ->with(['packagingSize:id,name'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function getInventoryRequestFormUsers()
    {
        return User::select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function getAvailableBranchesForUser(?User $user): array
    {
        if (!$user) {
            return [collect(), null];
        }

        // Semua branch aktif (termasuk Pusat) dapat dipilih, konsisten dengan
        // filter branch di halaman index dan modul lain. Branch user tetap
        // dipakai sebagai default terpilih.
        $branches = Branch::select('id', 'name', 'code', 'is_active')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $primaryBranchId = $branches->firstWhere('id', $user->branch_id)?->id
            ?? $branches->first()?->id;

        return [$branches, $primaryBranchId];
    }

    private function resolveAuthorizedBranchId(Request $request): int
    {
        [$branches, $primaryBranchId] = $this->getAvailableBranchesForUser(Auth::user());
        $selectedBranchId = $request->filled('branch_id')
            ? (int) $request->input('branch_id')
            : (int) $primaryBranchId;

        if (!$selectedBranchId) {
            throw ValidationException::withMessages([
                'branch_id' => 'User belum memiliki branch aktif untuk membuat inventory request.',
            ]);
        }

        $allowedBranchIds = $branches->pluck('id')->filter()->values();
        if ($allowedBranchIds->isNotEmpty() && !$allowedBranchIds->contains($selectedBranchId)) {
            throw ValidationException::withMessages([
                'branch_id' => 'Branch yang dipilih tidak sesuai dengan branch user.',
            ]);
        }

        return $selectedBranchId;
    }
}

<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\InventoryIssuing;
use App\Models\InventoryMovement;
use App\Models\InventoryReceiving;
use App\Models\InventoryReceivingItem;
use App\Models\InventoryRequest;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferItem;
use App\Models\SerialNumber;
use App\Models\Warehouse;
use App\Models\WarehouseProduct;
use App\Models\MasterProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventoryController extends Controller
{
    use \App\Http\Traits\AccessControlFilterTrait;
    // Inventory Transfer Index
    public function index(Request $request)
    {
        // Apply access control filter
        $user = Auth::user();
        $query = InventoryTransfer::with(['fromWarehouse', 'toWarehouse', 'creator', 'updatedBy']);
        $query = $this->applyAccessControlFilter($query, null, 'created_by', null, 'fromWarehouse.branch_id', function($q) use ($user) {
             // Logic for Warehouse Manager on either from or to warehouse
             $managedWarehouseIds = \App\Models\Warehouse::where('manager', $user->id)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
             if (!empty($managedWarehouseIds)) {
                 $q->orWhereIn('from_warehouse_id', $managedWarehouseIds)
                   ->orWhereIn('to_warehouse_id', $managedWarehouseIds);
             }
        });

        $query->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('warehouse_id')) {
            $query->byWarehouse($request->warehouse_id);
        }

        if ($request->filled('date_from')) {
            $query->where('transfer_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('transfer_date', '<=', $request->date_to);
        }

        $paginatedTransfers = $query->paginateStd(25);

        return view('warehouse.inventory-transfers.index', compact('paginatedTransfers'));
    }

    // Inventory Issuing
    public function issuingIndex(Request $request)
    {
        $query = InventoryIssuing::with(['warehouse', 'requestedBy', 'handedBy', 'receivedBy', 'createdBy', 'updatedBy']);

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date
        if ($request->filled('start_date')) {
            $query->whereDate('issue_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('issue_date', '<=', $request->end_date);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by reference number
        if ($request->filled('reference_no')) {
            $query->where('reference_no', 'like', '%' . $request->reference_no . '%');
        }

        $issuings = $query->orderBy('issue_date', 'desc')->paginateStd(25);

        return view('warehouse.inventory.issuing.index', compact('issuings'));
    }

    public function issuingCreate()
    {
        $warehouses = Warehouse::where('status', 'active')->get();
        $users = User::where('status', 'active')->get();
        
        return view('warehouse.inventory.issuing.create', compact('warehouses', 'users'));
    }

    public function issuingStore(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:warehouses,id',
            'reference_no' => 'required|string|max:100',
            'requested_by' => 'required|exists:users,id',
            'handed_by' => 'required|exists:users,id',
            'received_by' => 'nullable|exists:users,id',
            'issue_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $issuing = InventoryIssuing::create([
                'issuing_no' => 'IS-' . date('Ymd') . '-' . str_pad(InventoryIssuing::count() + 1, 4, '0', STR_PAD_LEFT),
                'branch_id' => $request->branch_id,
                'reference_no' => $request->reference_no,
                'requested_by' => $request->requested_by,
                'handed_by' => $request->handed_by,
                'received_by' => $request->received_by,
                'issue_date' => $request->issue_date,
                'status' => 'pending',
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('warehouse.inventory.issuing.show', $issuing)
                ->with('success', 'Inventory Issuing berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function issuingShow(InventoryIssuing $issuing)
    {
        $issuing->load(['warehouse', 'requestedBy', 'handedBy', 'receivedBy']);
        
        return view('warehouse.inventory.issuing.show', compact('issuing'));
    }

    public function issuingEdit(InventoryIssuing $issuing)
    {
        $warehouses = Warehouse::where('status', 'active')->get();
        $users = User::where('status', 'active')->get();
        
        return view('warehouse.inventory.issuing.edit', compact('issuing', 'warehouses', 'users'));
    }

    public function issuingUpdate(Request $request, InventoryIssuing $issuing)
    {
        $request->validate([
            'branch_id' => 'required|exists:warehouses,id',
            'reference_no' => 'required|string|max:100',
            'requested_by' => 'required|exists:users,id',
            'handed_by' => 'required|exists:users,id',
            'received_by' => 'nullable|exists:users,id',
            'issue_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $issuing->update([
                'branch_id' => $request->branch_id,
                'reference_no' => $request->reference_no,
                'requested_by' => $request->requested_by,
                'handed_by' => $request->handed_by,
                'received_by' => $request->received_by,
                'issue_date' => $request->issue_date,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return redirect()->route('warehouse.inventory.issuing.show', $issuing)
                ->with('success', 'Inventory Issuing berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function issuingDestroy(InventoryIssuing $issuing)
    {
        try {
            $issuing->delete();
            return redirect()->route('warehouse.inventory.issuing.index')
                ->with('success', 'Inventory Issuing berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Inventory Receiving
    public function receivingIndex(Request $request)
    {
        $query = InventoryReceiving::with(['warehouse', 'receivedFrom', 'createdBy', 'updatedBy']);

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date
        if ($request->filled('start_date')) {
            $query->whereDate('receive_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('receive_date', '<=', $request->end_date);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by reference number
        if ($request->filled('reference_no')) {
            $query->where('reference_no', 'like', '%' . $request->reference_no . '%');
        }

        $receivings = $query->orderBy('receive_date', 'desc')->paginateStd(25);

        return view('warehouse.inventory.receiving.index', compact('receivings'));
    }

    public function receivingCreate()
    {
        $warehouses = Warehouse::where('status', 'active')->get();
        $users = User::where('status', 'active')->get();
        
        return view('warehouse.inventory.receiving.create', compact('warehouses', 'users'));
    }

    public function receivingStore(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:warehouses,id',
            'reference_no' => 'required|string|max:100',
            'received_from' => 'required|exists:users,id',
            'issue_date' => 'required|date',
            'receive_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $receiving = InventoryReceiving::create([
                'receiving_no' => 'RC-' . date('Ymd') . '-' . str_pad(InventoryReceiving::count() + 1, 4, '0', STR_PAD_LEFT),
                'branch_id' => $request->branch_id,
                'reference_no' => $request->reference_no,
                'received_from' => $request->received_from,
                'issue_date' => $request->issue_date,
                'receive_date' => $request->receive_date,
                'status' => 'pending',
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('warehouse.inventory.receiving.show', $receiving)
                ->with('success', 'Inventory Receiving berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function receivingShow(InventoryReceiving $receiving)
    {
        $receiving->load(['warehouse', 'receivedFrom']);
        
        return view('warehouse.inventory.receiving.show', compact('receiving'));
    }

    public function receivingEdit(InventoryReceiving $receiving)
    {
        $warehouses = Warehouse::where('status', 'active')->get();
        $users = User::where('status', 'active')->get();
        
        return view('warehouse.inventory.receiving.edit', compact('receiving', 'warehouses', 'users'));
    }

    public function receivingUpdate(Request $request, InventoryReceiving $receiving)
    {
        $request->validate([
            'branch_id' => 'required|exists:warehouses,id',
            'reference_no' => 'required|string|max:100',
            'received_from' => 'required|exists:users,id',
            'issue_date' => 'required|date',
            'receive_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $receiving->update([
                'branch_id' => $request->branch_id,
                'reference_no' => $request->reference_no,
                'received_from' => $request->received_from,
                'issue_date' => $request->issue_date,
                'receive_date' => $request->receive_date,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return redirect()->route('warehouse.inventory.receiving.show', $receiving)
                ->with('success', 'Inventory Receiving berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function receivingDestroy(InventoryReceiving $receiving)
    {
        try {
            $receiving->delete();
            return redirect()->route('warehouse.inventory.receiving.index')
                ->with('success', 'Inventory Receiving berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Inventory Request
    public function requestIndex(Request $request)
    {
        $query = InventoryRequest::with(['warehouse', 'requestedBy', 'approvedBy', 'issuedBy', 'receivedBy', 'createdBy', 'updatedBy']);

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by date
        if ($request->filled('start_date')) {
            $query->whereDate('needed_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('needed_date', '<=', $request->end_date);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderBy('needed_date', 'desc')->paginateStd(25);

        return view('warehouse.inventory.request.index', compact('requests'));
    }

    public function requestCreate()
    {
        $warehouses = Warehouse::where('status', 'active')->get();
        $users = User::where('status', 'active')->get();
        
        return view('warehouse.inventory.request.create', compact('warehouses', 'users'));
    }

    public function requestStore(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:warehouses,id',
            'needed_date' => 'required|date',
            'requested_by' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $inventoryRequest = InventoryRequest::create([
                'request_no' => 'IR-' . date('Ymd') . '-' . str_pad(InventoryRequest::count() + 1, 4, '0', STR_PAD_LEFT),
                'branch_id' => $request->branch_id,
                'needed_date' => $request->needed_date,
                'requested_by' => $request->requested_by,
                'status' => 'pending',
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('warehouse.inventory.request.show', $inventoryRequest)
                ->with('success', 'Inventory Request berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function requestShow(InventoryRequest $inventoryRequest)
    {
        $inventoryRequest->load(['warehouse', 'requestedBy', 'approvedBy', 'issuedBy', 'receivedBy']);
        
        return view('warehouse.inventory.request.show', compact('inventoryRequest'));
    }

    public function requestEdit(InventoryRequest $inventoryRequest)
    {
        $warehouses = Warehouse::where('status', 'active')->get();
        $users = User::where('status', 'active')->get();
        
        return view('warehouse.inventory.request.edit', compact('inventoryRequest', 'warehouses', 'users'));
    }

    public function requestUpdate(Request $request, InventoryRequest $inventoryRequest)
    {
        $request->validate([
            'branch_id' => 'required|exists:warehouses,id',
            'needed_date' => 'required|date',
            'requested_by' => 'required|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $inventoryRequest->update([
                'branch_id' => $request->branch_id,
                'needed_date' => $request->needed_date,
                'requested_by' => $request->requested_by,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return redirect()->route('warehouse.inventory.request.show', $inventoryRequest)
                ->with('success', 'Inventory Request berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function requestDestroy(InventoryRequest $inventoryRequest)
    {
        try {
            $inventoryRequest->delete();
            return redirect()->route('warehouse.inventory.request.index')
                ->with('success', 'Inventory Request berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function requestApprove(Request $request, InventoryRequest $inventoryRequest)
    {
        $request->validate([
            'approved_by' => 'required|exists:users,id',
        ]);

        try {
            $inventoryRequest->update([
                'status' => 'approved',
                'approved_by' => $request->approved_by,
                'approved_at' => now(),
            ]);

            return back()->with('success', 'Inventory Request berhasil disetujui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function requestIssue(Request $request, InventoryRequest $inventoryRequest)
    {
        $request->validate([
            'issued_by' => 'required|exists:users,id',
        ]);

        try {
            $inventoryRequest->update([
                'status' => 'issued',
                'issued_by' => $request->issued_by,
                'issued_at' => now(),
            ]);

            return back()->with('success', 'Inventory Request berhasil dikeluarkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function requestReceive(Request $request, InventoryRequest $inventoryRequest)
    {
        $request->validate([
            'received_by' => 'required|exists:users,id',
        ]);

        try {
            $inventoryRequest->update([
                'status' => 'received',
                'received_by' => $request->received_by,
                'received_at' => now(),
            ]);

            return back()->with('success', 'Inventory Request berhasil diterima.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // ==================== INVENTORY TRANSFER METHODS ====================

    // Get warehouses for dropdown
    public function getWarehouses()
    {
        $warehouses = Warehouse::active()
            ->select('id', 'name', 'warehouse_code', 'is_center')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $warehouses
        ]);
    }

    // Get warehouses filtered by transfer rules
    public function getTransferWarehouses($fromWarehouseId = null)
    {
        $warehouses = Warehouse::active()
            ->select('id', 'name', 'warehouse_code', 'branch_id', 'is_center')
            ->orderBy('name')
            ->get();

        // If from warehouse is specified, filter by transfer rules
        if ($fromWarehouseId) {
            $fromWarehouse = Warehouse::find($fromWarehouseId);
            if ($fromWarehouse) {
                $warehouses = $warehouses->filter(function($warehouse) use ($fromWarehouse) {
                    return $fromWarehouse->canTransferTo($warehouse);
                });
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $warehouses->values()
        ]);
    }

    // Get users for dropdown
    public function getUsers()
    {
        $users = User::active()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    // Get products with stock for a specific warehouse
    public function getProductsWithStock($warehouseId)
    {
        try {
            // Check if warehouse exists
            $warehouse = Warehouse::find($warehouseId);
            if (!$warehouse) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Warehouse not found'
                ], 404);
            }

            $products = WarehouseProduct::with(['masterProduct:id,name,sku'])
                ->where('warehouse_id', $warehouseId)
                ->where('quantity', '>', 0) // Only products with stock
                ->select('id', 'warehouse_id', 'master_product_id', 'quantity')
                ->orderBy('quantity', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $products
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting products with stock:', [
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get products: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get single inventory transfer with relationships
    public function getTransfer($id)
    {
        try {
            $transfer = InventoryTransfer::with([
                'fromWarehouse:id,name,warehouse_code',
                'toWarehouse:id,name,warehouse_code',
                'creator:id,name,email',
                'transferItems.product:id,name,sku'
            ])->findOrFail($id);

            // Format transfer_date for the HTML date input as a plain array value.
            // Assigning a 'Y-m-d' string directly on the model gets re-cast back to a
            // Carbon date (transfer_date is cast as 'date') and re-serializes as a
            // UTC ISO timestamp, shifting the displayed date back a day in timezones
            // ahead of UTC (e.g. WIB). Overwriting after toArray() avoids the re-cast.
            $data = $transfer->toArray();
            $data['transfer_date'] = $transfer->transfer_date ? $transfer->transfer_date->format('Y-m-d') : null;

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transfer not found'
            ], 404);
        }
    }

    // Inventory Transfer full-page detail (mirrors inventory-requests/show.blade.php layout)
    public function showTransfer($id)
    {
        $transfer = InventoryTransfer::with([
            'fromWarehouse',
            'toWarehouse',
            'creator',
            'updatedBy',
            'submissionLetterUploader',
            'deliveryNoteUploader',
            'sourceMaterialReturn',
            'transferItems.product',
        ])->findOrFail($id);

        // SN-tracked items get queued into an auto-created Inventory Receiving when
        // they leave the source warehouse (see queueSerialNumberItemsForTransfer());
        // surface it so the destination warehouse knows where to go verify/finalize.
        $serializedReceiving = InventoryReceiving::where('reference_no', $transfer->transfer_number)->first();

        return view('warehouse.inventory-transfers.show', ['transfer' => $transfer, 'serializedReceiving' => $serializedReceiving]);
    }

    // Store new inventory transfer
    public function storeTransfer(Request $request)
    {
        $request->validate([
            'transfer_date' => 'required|date',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'status' => 'required|in:draft,transferred,received',
            'is_direct_branch_transfer' => 'nullable|boolean',
            'delivery_order_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'central_approval_notes' => 'nullable|string|max:1000',
            'submission_letter_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'delivery_note_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'notes' => 'nullable|string|max:1000',
            'return_reason' => 'nullable|string|max:1000',
            'return_reason_category' => 'nullable|in:slow_moving,near_expired,customer_need_changed,damaged,other',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            // Validate transfer rules (Center ↔ Branch only)
            $fromWarehouse = Warehouse::findOrFail($request->from_warehouse_id);
            $toWarehouse = Warehouse::findOrFail($request->to_warehouse_id);
            
            $isBranchToBranch = $fromWarehouse->isBranch() && $toWarehouse->isBranch();
            $isDirectBranchTransfer = $request->boolean('is_direct_branch_transfer');

            if (!$fromWarehouse->canTransferTo($toWarehouse)) {
                if (! $isBranchToBranch || ! $isDirectBranchTransfer || ! $request->hasFile('delivery_order_file') || ! $request->filled('central_approval_notes')) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Transfer antar Branch hanya boleh dengan approval pusat dan upload DO.'
                    ], 422);
                }
            }

            if ($isDirectBranchTransfer && ! $isBranchToBranch) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Direct branch transfer hanya berlaku untuk transfer antar Branch.'
                ], 422);
            }

            // Validate stock availability for each item. SN-tracked products (unit
            // diffuser/aroma/refill) additionally need enough 'ready' SN units, not
            // just enough aggregate quantity - the actual SN records get queued into
            // an auto-created Inventory Receiving for the destination warehouse to
            // verify/finalize (see queueSerialNumberItemsForTransfer()), since
            // Inventory Transfer's own stock movement never touches SerialNumber rows.
            foreach ($request->items as $item) {
                $warehouseProduct = WarehouseProduct::where('warehouse_id', $request->from_warehouse_id)
                    ->where('master_product_id', $item['product_id'])
                    ->first();

                if (!$warehouseProduct || $warehouseProduct->quantity < $item['quantity']) {
                    $product = MasterProduct::find($item['product_id']);
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => "Stok tidak mencukupi untuk produk {$product->name}. Stok tersedia: " . ($warehouseProduct->quantity ?? 0)
                    ], 422);
                }

                $product = MasterProduct::with(['productCategory', 'productType'])->find($item['product_id']);
                if ($product && $product->requiresSerialNumber()) {
                    $availableSnCount = SerialNumber::where('warehouse_id', $request->from_warehouse_id)
                        ->where('master_product_id', $item['product_id'])
                        ->where('status', 'ready')
                        ->count();

                    if ($availableSnCount < $item['quantity']) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => "Serial Number tersedia untuk produk {$product->name} tidak mencukupi. Tersedia: {$availableSnCount}, dibutuhkan: {$item['quantity']}."
                        ], 422);
                    }
                }
            }

            $transfer = InventoryTransfer::create([
                'transfer_date' => $request->transfer_date,
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'status' => $request->status,
                'is_direct_branch_transfer' => $isDirectBranchTransfer,
                'delivery_order_file' => $request->hasFile('delivery_order_file')
                    ? $request->file('delivery_order_file')->store('inventory-transfers/do', 'public')
                    : null,
                'central_approved_by' => $isDirectBranchTransfer ? Auth::id() : null,
                'central_approved_at' => $isDirectBranchTransfer ? now() : null,
                'central_approval_notes' => $request->central_approval_notes,
                'submission_letter_file' => $request->hasFile('submission_letter_file')
                    ? $request->file('submission_letter_file')->store('inventory-transfers/submission-letter', 'public')
                    : null,
                'submission_letter_uploaded_by' => $request->hasFile('submission_letter_file') ? Auth::id() : null,
                'submission_letter_uploaded_at' => $request->hasFile('submission_letter_file') ? now() : null,
                'delivery_note_file' => $request->hasFile('delivery_note_file')
                    ? $request->file('delivery_note_file')->store('inventory-transfers/delivery-note', 'public')
                    : null,
                'delivery_note_uploaded_by' => $request->hasFile('delivery_note_file') ? Auth::id() : null,
                'delivery_note_uploaded_at' => $request->hasFile('delivery_note_file') ? now() : null,
                'notes' => $request->notes,
                'return_reason' => $request->return_reason,
                'return_reason_category' => $request->return_reason_category,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Create transfer items
            foreach ($request->items as $item) {
                InventoryTransferItem::create([
                    'inventory_transfer_id' => $transfer->id,
                    'master_product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            // A brand-new transfer has no prior state, so treat it as if it started
            // from 'draft' - creating one directly as transferred/received still
            // moves whichever stock that status implies.
            $this->applyStockForTransferStatusChange($transfer, 'draft', $request->status);

            $queuedReceiving = null;
            if ($request->status !== 'draft') {
                $queuedReceiving = $this->queueSerialNumberItemsForTransfer($transfer);
            }

            DB::commit();

            $message = 'Inventory transfer created successfully';
            if ($queuedReceiving) {
                $message .= ". Item ber-Serial Number di-queue ke Inventory Receiving {$queuedReceiving->receiving_number} (menunggu verifikasi & finalize gudang tujuan).";
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $transfer->load(['fromWarehouse', 'toWarehouse', 'creator', 'transferItems.product'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create transfer: ' . $e->getMessage()
            ], 500);
        }
    }

    // Update inventory transfer
    public function updateTransfer(Request $request, $id)
    {
        $request->validate([
            'transfer_date' => 'required|date',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'status' => 'required|in:draft,transferred,received',
            'is_direct_branch_transfer' => 'nullable|boolean',
            'delivery_order_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'central_approval_notes' => 'nullable|string|max:1000',
            'submission_letter_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'delivery_note_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            'notes' => 'nullable|string|max:1000',
            'return_reason' => 'nullable|string|max:1000',
            'return_reason_category' => 'nullable|in:slow_moving,near_expired,customer_need_changed,damaged,other'
        ]);

        try {
            $transfer = InventoryTransfer::findOrFail($id);
            $oldStatus = $transfer->status;

            DB::beginTransaction();

            $fromWarehouse = Warehouse::findOrFail($request->from_warehouse_id);
            $toWarehouse = Warehouse::findOrFail($request->to_warehouse_id);
            $isBranchToBranch = $fromWarehouse->isBranch() && $toWarehouse->isBranch();
            $isDirectBranchTransfer = $request->boolean('is_direct_branch_transfer');

            if (!$fromWarehouse->canTransferTo($toWarehouse)) {
                if (! $isBranchToBranch || ! $isDirectBranchTransfer || (! $request->hasFile('delivery_order_file') && ! $transfer->delivery_order_file) || ! $request->filled('central_approval_notes')) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Transfer antar Branch hanya boleh dengan approval pusat dan upload DO.'
                    ], 422);
                }
            }

            if ($isDirectBranchTransfer && ! $isBranchToBranch) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Direct branch transfer hanya berlaku untuk transfer antar Branch.'
                ], 422);
            }

            $deliveryOrderFile = $transfer->delivery_order_file;
            if ($request->hasFile('delivery_order_file')) {
                if ($deliveryOrderFile) {
                    Storage::disk('public')->delete($deliveryOrderFile);
                }
                $deliveryOrderFile = $request->file('delivery_order_file')->store('inventory-transfers/do', 'public');
            }

            // Surat pengajuan (branch's submission letter). Kept once uploaded; only
            // replaced (and the old file removed) when a new one is attached.
            $submissionLetterFile = $transfer->submission_letter_file;
            $submissionLetterUploadedBy = $transfer->submission_letter_uploaded_by;
            $submissionLetterUploadedAt = $transfer->submission_letter_uploaded_at;
            if ($request->hasFile('submission_letter_file')) {
                if ($submissionLetterFile) {
                    Storage::disk('public')->delete($submissionLetterFile);
                }
                $submissionLetterFile = $request->file('submission_letter_file')->store('inventory-transfers/submission-letter', 'public');
                $submissionLetterUploadedBy = Auth::id();
                $submissionLetterUploadedAt = now();
            }

            // Surat jalan (center's dispatch/acknowledgement document for the branch).
            $deliveryNoteFile = $transfer->delivery_note_file;
            $deliveryNoteUploadedBy = $transfer->delivery_note_uploaded_by;
            $deliveryNoteUploadedAt = $transfer->delivery_note_uploaded_at;
            if ($request->hasFile('delivery_note_file')) {
                if ($deliveryNoteFile) {
                    Storage::disk('public')->delete($deliveryNoteFile);
                }
                $deliveryNoteFile = $request->file('delivery_note_file')->store('inventory-transfers/delivery-note', 'public');
                $deliveryNoteUploadedBy = Auth::id();
                $deliveryNoteUploadedAt = now();
            }

            // Goods leave the source warehouse once a transfer is marked Transferred;
            // storeTransfer applies this at creation for non-draft transfers, drafts
            // created earlier (e.g. auto-created from a branch material return) commit
            // it here. Destination stock is only credited once marked Received - being
            // merely "Transferred" means the goods are still in transit. Validate
            // source stock before the source-deducting transition, mirroring storeTransfer.
            $shouldDeductSource = $oldStatus === 'draft' && in_array($request->status, ['transferred', 'received'], true);

            if ($shouldDeductSource) {
                $transfer->load(['transferItems.product.productCategory', 'transferItems.product.productType']);

                foreach ($transfer->transferItems as $item) {
                    $warehouseProduct = WarehouseProduct::where('warehouse_id', $request->from_warehouse_id)
                        ->where('master_product_id', $item->master_product_id)
                        ->first();

                    if (!$warehouseProduct || $warehouseProduct->quantity < $item->quantity) {
                        $product = MasterProduct::find($item->master_product_id);
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => "Stok tidak mencukupi untuk produk " . ($product->name ?? "#{$item->master_product_id}") . ". Stok tersedia: " . ($warehouseProduct->quantity ?? 0)
                        ], 422);
                    }

                    // SN-tracked products additionally need enough 'ready' SN units at
                    // the source (see queueSerialNumberItemsForTransfer() for where
                    // they actually get moved - not by this quantity check).
                    if ($item->product && $item->product->requiresSerialNumber()) {
                        $availableSnCount = SerialNumber::where('warehouse_id', $request->from_warehouse_id)
                            ->where('master_product_id', $item->master_product_id)
                            ->where('status', 'ready')
                            ->count();

                        if ($availableSnCount < $item->quantity) {
                            DB::rollBack();
                            return response()->json([
                                'status' => 'error',
                                'message' => "Serial Number tersedia untuk produk {$item->product->name} tidak mencukupi. Tersedia: {$availableSnCount}, dibutuhkan: {$item->quantity}."
                            ], 422);
                        }
                    }
                }
            }

            $transfer->update([
                'transfer_date' => $request->transfer_date,
                'from_warehouse_id' => $request->from_warehouse_id,
                'to_warehouse_id' => $request->to_warehouse_id,
                'status' => $request->status,
                'is_direct_branch_transfer' => $isDirectBranchTransfer,
                'delivery_order_file' => $deliveryOrderFile,
                'central_approved_by' => $isDirectBranchTransfer ? Auth::id() : null,
                'central_approved_at' => $isDirectBranchTransfer ? now() : null,
                'central_approval_notes' => $request->central_approval_notes,
                'submission_letter_file' => $submissionLetterFile,
                'submission_letter_uploaded_by' => $submissionLetterUploadedBy,
                'submission_letter_uploaded_at' => $submissionLetterUploadedAt,
                'delivery_note_file' => $deliveryNoteFile,
                'delivery_note_uploaded_by' => $deliveryNoteUploadedBy,
                'delivery_note_uploaded_at' => $deliveryNoteUploadedAt,
                'notes' => $request->notes,
                'return_reason' => $request->return_reason,
                'return_reason_category' => $request->return_reason_category,
                'updated_by' => Auth::id()
            ]);

            $this->applyStockForTransferStatusChange(
                $transfer->load(['transferItems', 'fromWarehouse', 'toWarehouse']),
                $oldStatus,
                $request->status
            );

            $queuedReceiving = null;
            if ($shouldDeductSource) {
                $queuedReceiving = $this->queueSerialNumberItemsForTransfer($transfer);
            }

            DB::commit();

            $message = 'Inventory transfer updated successfully';
            if ($queuedReceiving) {
                $message .= ". Item ber-Serial Number di-queue ke Inventory Receiving {$queuedReceiving->receiving_number} (menunggu verifikasi & finalize gudang tujuan).";
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $transfer->load(['fromWarehouse', 'toWarehouse', 'creator'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update transfer: ' . $e->getMessage()
            ], 500);
        }
    }

    // Delete inventory transfer (soft delete)
    public function deleteTransfer($id)
    {
        try {
            $transfer = InventoryTransfer::findOrFail($id);
            $transfer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inventory transfer hidden successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to hide transfer: ' . $e->getMessage()
            ], 500);
        }
    }

    // Bulk delete inventory transfers (soft delete)
    public function bulkDeleteTransfers(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:inventory_transfers,id'
        ]);

        // Debug: Log the received IDs
        \Log::info('Bulk delete request - IDs received:', $request->ids);

        try {
            DB::beginTransaction();

            $deletedCount = InventoryTransfer::whereIn('id', $request->ids)->delete();

            DB::commit();

            \Log::info('Bulk delete completed - Count:', ['deleted_count' => $deletedCount]);

            return response()->json([
                'success' => true,
                'count' => $deletedCount,
                'message' => "Successfully hidden {$deletedCount} inventory transfer(s)"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bulk delete failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to hide transfers: ' . $e->getMessage()
            ], 500);
        }
    }

    // Inventory Transfer's own stock movement (deductSourceStockForTransfer /
    // creditDestinationStockForTransfer) only ever touches the WarehouseProduct
    // quantity aggregate - it has no concept of which physical units move, so it
    // can't safely update SerialNumber rows itself. Instead, once a transfer's
    // SN-tracked items leave the source (see the $shouldDeductSource call site),
    // this auto-creates an Inventory Receiving at the destination warehouse and
    // queues the specific SN units into it - mirroring exactly how a branch
    // material return with SN items gets queued (queueSerialNumberReturnItem() in
    // JobScheduleController). The destination warehouse then verifies/finalizes
    // that Receiving through the existing, already-correct SN flow
    // (InventoryReceivingController::finalize() ->
    // releaseReceivedSerialNumbersToWarehouse()), which is what actually moves
    // SerialNumber.warehouse_id and credits WarehouseProduct there - Inventory
    // Transfer's own "Received" status does NOT credit destination stock for
    // these items (see creditDestinationStockForTransfer()'s SN skip).
    //
    // SN units are auto-picked FIFO (oldest 'ready' unit first) at the source
    // warehouse - no manual per-unit selection UI.
    //
    // Returns the created InventoryReceiving, or null if the transfer has no
    // SN-tracked items.
    private function queueSerialNumberItemsForTransfer(InventoryTransfer $transfer)
    {
        $transfer->loadMissing(['transferItems.product.productCategory', 'transferItems.product.productType', 'toWarehouse']);

        $serializedItems = $transfer->transferItems->filter(
            fn ($item) => $item->product && $item->product->requiresSerialNumber()
        );

        if ($serializedItems->isEmpty()) {
            return null;
        }

        $toWarehouse = $transfer->toWarehouse;

        $inventoryReceiving = InventoryReceiving::create([
            'receiving_number' => app(\App\Services\DocumentNumberService::class)->generate(
                'inventory_receiving',
                $toWarehouse->branch_id ? Branch::find($toWarehouse->branch_id)?->code : null
            ),
            'reference_no' => $transfer->transfer_number,
            'branch_id' => $toWarehouse->branch_id,
            'received_from' => Auth::id(),
            'received_by_old' => Auth::id(),
            'schedule_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => "Auto dari Inventory Transfer {$transfer->transfer_number} (item ber-Serial Number, menunggu verifikasi & finalize gudang tujuan).",
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        foreach ($serializedItems as $item) {
            InventoryReceivingItem::create([
                'inventory_receiving_id' => $inventoryReceiving->id,
                'master_product_id' => $item->master_product_id,
                'quantity' => $item->quantity,
                'quantity_received' => 0,
                'notes' => "Transfer item #{$item->id} dari {$transfer->transfer_number}",
            ]);

            $pickedSerialNumbers = SerialNumber::where('warehouse_id', $transfer->from_warehouse_id)
                ->where('master_product_id', $item->master_product_id)
                ->where('status', 'ready')
                ->orderBy('created_at')
                ->limit((int) $item->quantity)
                ->get();

            foreach ($pickedSerialNumbers as $serialNumber) {
                $serialNumber->update([
                    'inventory_receiving_id' => $inventoryReceiving->id,
                    'warehouse_id' => $toWarehouse->id,
                    'status' => 'pending',
                    'updated_by' => Auth::id(),
                ]);
            }
        }

        return $inventoryReceiving;
    }

    // Applies the stock movement(s) that correspond to a transfer's old -> new
    // status change. Goods leave the source warehouse once a transfer is marked
    // Transferred; they only credit the destination warehouse once marked
    // Received (i.e. physically confirmed arrived) - Transferred alone does not
    // add stock at the destination, since the goods are still in transit.
    // $oldStatus is treated as 'draft' for a brand-new transfer (nothing has
    // moved yet), so creating one directly as transferred/received still moves
    // the stock that status implies.
    private function applyStockForTransferStatusChange($transfer, $oldStatus, $newStatus)
    {
        if ($oldStatus === 'draft' && in_array($newStatus, ['transferred', 'received'], true)) {
            $this->deductSourceStockForTransfer($transfer);
        }

        if (in_array($oldStatus, ['draft', 'transferred'], true) && $newStatus === 'received') {
            $this->creditDestinationStockForTransfer($transfer);
        }
    }

    // Reduce stock from the source warehouse (goods have physically left).
    private function deductSourceStockForTransfer($transfer)
    {
        foreach ($transfer->transferItems as $item) {
            $fromWarehouseProduct = WarehouseProduct::where('warehouse_id', $transfer->from_warehouse_id)
                ->where('master_product_id', $item->master_product_id)
                ->first();

            if ($fromWarehouseProduct) {
                $fromWarehouseProduct->quantity -= $item->quantity;
                $fromWarehouseProduct->save();
            }

            InventoryMovement::create([
                'movement_no' => 'TRF-' . $transfer->transfer_number,
                'movement_type' => 'out',
                'warehouse_id' => $transfer->from_warehouse_id,
                'master_product_id' => $item->master_product_id,
                'quantity' => -$item->quantity,
                'movement_date' => $transfer->transfer_date,
                'reference_no' => $transfer->transfer_number,
                'reference_type' => 'inventory_transfer',
                'notes' => "Transfer keluar ke {$transfer->toWarehouse->name}",
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);
        }
    }

    // Add stock to the destination warehouse (goods confirmed received).
    private function creditDestinationStockForTransfer($transfer)
    {
        foreach ($transfer->transferItems as $item) {
            // SN-tracked items don't credit destination stock here - they were
            // queued into an Inventory Receiving (see
            // queueSerialNumberItemsForTransfer()) when the transfer left the
            // source, and only get credited when that Receiving is finalized by
            // the destination warehouse, so the SerialNumber rows move alongside
            // the quantity instead of the two silently going out of sync.
            if ($item->product && $item->product->requiresSerialNumber()) {
                continue;
            }

            $toWarehouseProduct = WarehouseProduct::where('warehouse_id', $transfer->to_warehouse_id)
                ->where('master_product_id', $item->master_product_id)
                ->first();

            if ($toWarehouseProduct) {
                $toWarehouseProduct->quantity += $item->quantity;
                $toWarehouseProduct->save();
            } else {
                // Create new warehouse product if doesn't exist
                $masterProduct = MasterProduct::find($item->master_product_id);
                $fromWarehouseProduct = WarehouseProduct::where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('master_product_id', $item->master_product_id)
                    ->first();
                WarehouseProduct::create([
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'master_product_id' => $item->master_product_id,
                    'quantity' => $item->quantity,
                    'minimum_stock' => $fromWarehouseProduct->minimum_stock ?? $masterProduct->minimum_stock ?? 0,
                    'maximum_stock' => $fromWarehouseProduct->maximum_stock ?? $masterProduct->maximum_stock ?? 0,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            InventoryMovement::create([
                'movement_no' => 'TRF-' . $transfer->transfer_number,
                'movement_type' => 'in',
                'warehouse_id' => $transfer->to_warehouse_id,
                'master_product_id' => $item->master_product_id,
                'quantity' => $item->quantity,
                'movement_date' => $transfer->transfer_date,
                'reference_no' => $transfer->transfer_number,
                'reference_type' => 'inventory_transfer',
                'notes' => "Transfer masuk dari {$transfer->fromWarehouse->name}",
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);
        }
    }
}

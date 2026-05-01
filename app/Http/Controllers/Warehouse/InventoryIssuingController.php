<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\InventoryIssuing;
use App\Models\InventoryMovement;
use App\Models\InventoryRequest;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\MasterProduct;
use App\Models\User;
use App\Services\Warehouse\SerialNumberIssuingLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Traits\AccessControlFilterTrait;

class InventoryIssuingController extends Controller
{
    use AccessControlFilterTrait;

    private function extractRoomNameFromItemNotes(?string $notes): ?string
    {
        if (!$notes) {
            return null;
        }

        if (preg_match('/Room:\s*([^,]+)/i', $notes, $matches)) {
            $roomName = trim($matches[1]);
            return $roomName !== '' ? $roomName : null;
        }

        return null;
    }

    public function index(Request $request)
    {
        $query = InventoryIssuing::with(['inventoryRequest', 'branch', 'warehouse.branch', 'requestedBy', 'issuedBy', 'receivedBy', 'createdBy', 'updatedBy']);
        
        // Access Control Logic
        $user = Auth::user();
        
        // Get user's teams for "Assigned Team" exception
        $userTeamIds = \DB::table('teams')
                ->where('team_head_id', $user->id)
                ->pluck('id')
                ->merge(
                    \DB::table('team_members')
                        ->where('user_id', $user->id)
                        ->pluck('team_id')
                )
                ->unique()
                ->toArray();

        // Apply Access Control with Team Exception
        // 'requested_by' added as marketing field to allow requestors to see the data
        $query = $this->applyAccessControlFilter($query, $user, 'created_by', 'requested_by', 'branch_id', function($q) use ($userTeamIds, $user) {
            // Check if user is assigned valid team
            if (!empty($userTeamIds)) {
                $q->orWhereIn('team_id', $userTeamIds);
            }
            // Also allow if user is the receiver/technician directly (just in case logic expands)
            $q->orWhere('received_by', $user->id);
        }, 'warehouse_id');

        // Filter by branch (Top-level filter)
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        // Filter by date range (Top-level filter)
        if ($request->filled('date_from')) {
            $query->where('issue_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        // Global Column Filters (from layout/app.blade.php)
        if ($request->filled('filter')) {
            $filters = $request->filter;

            // Direct column filters
            if (!empty($filters['issuing_number'])) {
                $query->where('issuing_number', 'like', '%' . $filters['issuing_number'] . '%');
            }

            if (!empty($filters['status'])) {
                $statusSearch = strtolower($filters['status']);
                $query->where(function($q) use ($statusSearch) {
                    $q->where('status', 'like', '%' . $statusSearch . '%');
                    
                    // Map user-friendly labels to database values
                    if (str_contains('material in prep', $statusSearch) || str_contains($statusSearch, 'prep')) {
                        $q->orWhere('status', 'pending');
                    }
                    if (str_contains('material ready', $statusSearch) || str_contains($statusSearch, 'ready')) {
                        $q->orWhere('status', 'processed');
                    }
                    if (str_contains('material issued', $statusSearch) || str_contains($statusSearch, 'issue')) {
                        $q->orWhere('status', 'sent');
                    }
                    if (str_contains('received', $statusSearch)) {
                        $q->orWhere('status', 'received');
                    }
                    if (str_contains('cancelled', $statusSearch)) {
                        $q->orWhere('status', 'cancelled');
                    }
                });
            }

            if (!empty($filters['reference_no'])) {
                $query->where('reference_no', 'like', '%' . $filters['reference_no'] . '%');
            }

            if (!empty($filters['id'])) {
                $query->where('id', 'like', '%' . $filters['id'] . '%');
            }

            if (!empty($filters['issue_date'])) {
                $query->where('issue_date', 'like', '%' . $filters['issue_date'] . '%');
            }

            // Relation filters (matching data-column names with double underscores)
            if (!empty($filters['branch__name'])) {
                $query->whereHas('branch', function($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['branch__name'] . '%');
                });
            }

            if (!empty($filters['requestedBy__name'])) {
                $query->whereHas('requestedBy', function($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['requestedBy__name'] . '%');
                });
            }

            if (!empty($filters['issuedBy__name'])) {
                $query->whereHas('issuedBy', function($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['issuedBy__name'] . '%');
                });
            }

            if (!empty($filters['receivedBy__name'])) {
                $query->whereHas('receivedBy', function($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['receivedBy__name'] . '%');
                });
            }

            if (!empty($filters['createdBy__name'])) {
                $query->whereHas('createdBy', function($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['createdBy__name'] . '%');
                });
            }

            if (!empty($filters['updatedBy__name'])) {
                $query->whereHas('updatedBy', function($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['updatedBy__name'] . '%');
                });
            }
        }
        
        $issuings = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Pass filter values back to view
        $branches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get();

        return view('warehouse.inventory-issuings.index', compact('issuings', 'branches'));
    }

    public function create()
    {
        $inventoryRequests = InventoryRequest::where('status', 'approved')->get();
        $branches = Branch::where('is_active', true)->get();
        $warehouses = Warehouse::where('status', 'active')->get();
        $products = MasterProduct::where('is_active', true)->get();
        $users = User::where('department_id', 4)->get(); // Warehouse department

        return view('warehouse.inventory-issuings.create', compact('inventoryRequests', 'branches', 'warehouses', 'products', 'users'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'inventory_request_id' => 'nullable|exists:inventory_requests,id',
                'branch_id' => 'required|exists:branches,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'issue_date' => 'required|date',
                'reference_no' => 'nullable|string|max:100',
                'requested_by' => 'required|exists:users,id',
                'issued_by' => 'nullable|exists:users,id',
                'received_by' => 'nullable|exists:users,id',
                'status' => 'required|in:pending,processed,sent,received,cancelled',
                'remarks' => 'nullable|string|max:500',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:master_products,id',
                'items.*.quantity_requested' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'nullable|numeric|min:0',
                'items.*.notes' => 'nullable|string|max:200',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Generate issuing number using DocumentNumberService
            // Format: [BRANCH]-WI/YY-MM/NNNN (e.g., JKT-WI/25-12/0001)
            $issuingNumber = app(\App\Services\DocumentNumberService::class)->generate(
                'inventory_issuing',
                null,
                null,
                null,
                null,
                null,
                $request->warehouse_id
            );

            $issuing = InventoryIssuing::create([
                'issuing_number' => $issuingNumber,
                'inventory_request_id' => $request->inventory_request_id,
                'branch_id' => $request->branch_id,
                'warehouse_id' => $request->warehouse_id,
                'issue_date' => $request->issue_date,
                'reference_no' => $request->reference_no,
                'requested_by' => $request->requested_by,
                'issued_by' => $request->issued_by,
                'received_by' => $request->received_by,
                'status' => $request->status,
                'remarks' => $request->remarks,
                'created_by' => Auth::id(),
            ]);

            // Create issuing items
            foreach ($request->items as $item) {
                $issuing->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_requested' => $item['quantity_requested'],
                    'unit_price' => $item['unit_price'] ?? 0,
                    'total_price' => ($item['unit_price'] ?? 0) * $item['quantity_requested'],
                    'notes' => $item['notes'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            DB::commit();

            // Always return JSON for AJAX requests
            return response()->json([
                'status' => 'success',
                'message' => 'Inventory issuing created successfully.',
                'data' => $issuing->load(['branch', 'warehouse', 'requestedBy', 'items.product'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create inventory issuing: ' . $e->getMessage()
            ], 422);
        }
    }

    public function show($id)
    {
        $issuing = InventoryIssuing::with(['inventoryRequest', 'branch', 'warehouse', 'requestedBy', 'issuedBy', 'receivedBy', 'items.product.productType', 'items.product.productCategory'])
            ->findOrFail($id);

        // Load issuing items with serial numbers
        $issuing->load(['items.serialNumber.warehouse', 'items.product.productType', 'items.product.productCategory']);

        // Check if can unpost (MOM16 logic)
        // Can unpost if status is 'sent' (Finish) AND work has not started/completed
        $canUnpost = false;
        if ($issuing->status === 'sent') {
            $materialIssue = \App\Models\MaterialIssue::where('issue_number', $issuing->reference_no)->first();
            $jobSchedule = null;
            if ($materialIssue) {
                // Get first related JobAssignMaterialIssue
                $jami = $materialIssue->jobAssignMaterialIssues()
                    ->with('jobAssignSchedule.jobSchedule')
                    ->first();
                
                if ($jami && $jami->jobAssignSchedule && $jami->jobAssignSchedule->jobSchedule) {
                    $jobSchedule = $jami->jobAssignSchedule->jobSchedule;
                }
            }

            if ($jobSchedule) {
                $workStartedStatuses = [
                    'teknisi_sedang_pengerjaan', 
                    'in_progress', 
                    'teknisi_selesai_pengerjaan', 
                    'done_job', 
                    'completed'
                ];
                $canUnpost = !in_array($jobSchedule->status, $workStartedStatuses);
            } else {
                // If manual issuing or no job found, allow unpost as long as it's 'sent'
                $canUnpost = true; 
            }
        }

        return view('warehouse.inventory-issuings.show', compact('issuing', 'canUnpost'));
    }

    public function edit($id)
    {
        $issuing = InventoryIssuing::with(['items'])->findOrFail($id);
        $inventoryRequests = InventoryRequest::where('status', 'approved')->get();
        $branches = Branch::where('is_active', true)->get();
        $warehouses = Warehouse::where('status', 'active')->get();
        $products = MasterProduct::where('is_active', true)->get();
        $users = User::where('department_id', 4)->get();

        return view('warehouse.inventory-issuings.edit', compact('issuing', 'inventoryRequests', 'branches', 'warehouses', 'products', 'users'));
    }

    public function update(Request $request, $id)
    {
        // Support partial update (for modal edits)
        $validationRules = [];
        $updateData = ['updated_by' => Auth::id()];
        
        if ($request->has('received_by')) {
            $validationRules['received_by'] = 'nullable|exists:users,id';
            $updateData['received_by'] = $request->received_by;
        }
        
        if ($request->has('remarks')) {
            $validationRules['remarks'] = 'nullable|string|max:500';
            $updateData['remarks'] = $request->remarks;
        }
        
        if ($request->has('team_id')) {
            $validationRules['team_id'] = 'nullable|exists:teams,id';
            $updateData['team_id'] = $request->team_id;
        }
        
        // Full update validation
        if ($request->has('inventory_request_id')) {
            $validationRules = array_merge($validationRules, [
                'inventory_request_id' => 'nullable|exists:inventory_requests,id',
                'branch_id' => 'required|exists:branches,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'issue_date' => 'required|date',
                'reference_no' => 'nullable|string|max:100',
                'requested_by' => 'required|exists:users,id',
                'issued_by' => 'nullable|exists:users,id',
                'status' => 'required|in:pending,processed,sent,received,cancelled',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:master_products,id',
                'items.*.quantity_requested' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'nullable|numeric|min:0',
                'items.*.notes' => 'nullable|string|max:200',
            ]);
            
            $updateData = array_merge($updateData, [
                'inventory_request_id' => $request->inventory_request_id,
                'branch_id' => $request->branch_id,
                'warehouse_id' => $request->warehouse_id,
                'issue_date' => $request->issue_date,
                'reference_no' => $request->reference_no,
                'requested_by' => $request->requested_by,
                'issued_by' => $request->issued_by,
                'status' => $request->status,
            ]);
        }
        
        $request->validate($validationRules);

        try {
            DB::beginTransaction();

            $issuing = InventoryIssuing::findOrFail($id);
            $issuing->update($updateData);

            // Update issuing items only if items array is provided
            if ($request->has('items')) {
                $issuing->items()->delete(); // Delete existing items
                foreach ($request->items as $item) {
                    $issuing->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity_requested' => $item['quantity_requested'],
                        'unit_price' => $item['unit_price'] ?? 0,
                        'total_price' => ($item['unit_price'] ?? 0) * $item['quantity_requested'],
                        'notes' => $item['notes'] ?? null,
                        'created_by' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            // MOM: Sync team/status to Job Schedule if it's already processed/sent
            // This ensures team changes via modal are reflected in Job Schedule detail/index
            try {
                $this->syncJobScheduleStatus($issuing);
            } catch (\Exception $e) {
                \Log::error("Sync Error in update: " . $e->getMessage());
            }

            $issuing->load(['branch', 'warehouse', 'requestedBy', 'receivedBy', 'items.product']);
            
            // Always return JSON for AJAX requests
            return response()->json([
                'status' => 'success',
                'message' => 'Inventory issuing updated successfully.',
                'data' => [
                    'id' => $issuing->id,
                    'remarks' => $issuing->remarks,
                    'received_by' => $issuing->received_by,
                    'received_by_name' => $issuing->receivedBy?->name,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            // Always return JSON for AJAX requests
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update inventory issuing: ' . $e->getMessage()
            ], 422);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $issuing = InventoryIssuing::with('items.product')->findOrFail($id);
            $syncService = new \App\Services\Warehouse\InventoryIssuingService();
            
            // Only allow deletion if status is pending
            if ($issuing->status !== 'pending') {
                return back()->with('error', 'Cannot delete issued inventory. Only pending (Un-prepared) issuings can be deleted.');
            }

            // 1. ROLLBACK STOCK (Revert movements created in Submit to Issue)
            // Get movements related to THIS Material Issue (via reference_no)
            $movements = \App\Models\InventoryMovement::where('reference_no', $issuing->reference_no)
                ->where('reference_type', 'material_issue')
                ->where('movement_type', 'out')
                ->get();

            foreach ($movements as $movement) {
                // Find current stock
                $warehouseProduct = \App\Models\WarehouseProduct::where('warehouse_id', $movement->warehouse_id)
                    ->where('master_product_id', $movement->master_product_id)
                    ->first();
                
                if ($warehouseProduct) {
                    // Re-add the quantity (movement->quantity is negative for 'out')
                    $revertQty = abs($movement->quantity);
                    $newQty = $warehouseProduct->quantity + $revertQty;
                    
                    $warehouseProduct->update([
                        'quantity' => $newQty,
                        'updated_by' => Auth::id()
                    ]);
                    
                    \Log::info("Rollback Stock: Reverted {$revertQty} units for Product ID {$movement->master_product_id} in Warehouse ID {$movement->warehouse_id}");
                }
                
                // Delete the movement record
                $movement->delete();
            }

            // 2. RESET MATERIAL ISSUE STATUS
            $materialIssue = \App\Models\MaterialIssue::where('issue_number', $issuing->reference_no)->first();
            if ($materialIssue) {
                $materialIssue->update([
                    'status' => 'approved', // Revert from 'issued' to 'approved'
                    'updated_by' => Auth::id()
                ]);
                \Log::info("Reset MaterialIssue {$materialIssue->issue_number} status to 'approved'");
            }

            // 4. CLEANUP WI RECORDS
            \App\Models\InventoryReceiving::where('issuing_id', $issuing->id)->delete();
            
            // Delete movements directly linked to THIS WI number (if any exist)
            \App\Models\InventoryMovement::where('reference_no', $issuing->issuing_number)
                ->where('reference_type', 'inventory_issuing')
                ->delete();

            $issuing->items()->delete();
            $issuing->delete();

            if ($materialIssue) {
                $syncService->syncGroupedJobMaterialLifecycleFromMaterialIssue($materialIssue);
            }

            DB::commit();

            return redirect()->route('warehouse.inventory-issuings.index')
                ->with('success', 'Inventory issuing deleted and records reverted successfully. Stock has been returned and Job status reset to Material Assign.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to delete and revert WI ID {$id}: " . $e->getMessage());
            return back()->with('error', 'Failed to delete inventory issuing: ' . $e->getMessage());
        }
    }

    public function process($id)
    {
        try {
            DB::beginTransaction();

            $issuing = InventoryIssuing::with(['items.product.productCategory', 'items.product.productType'])->findOrFail($id);
            
            if ($issuing->status !== 'pending') {
                DB::rollBack();
                return back()->with('error', 'Only pending (Un Prepare) issuings can be processed to Ready.');
            }

            // Validasi: Semua item yang butuh SN harus sudah di-scan/isi
            $missingSNItems = [];
            foreach ($issuing->items as $item) {
                $hasSerialReq = $item->product?->requiresSerialNumber() ?? false;
                if ($hasSerialReq && !$item->serial_number_id) {
                    $missingSNItems[] = $item->product->name ?? 'Unknown Product';
                }
            }

            if (!empty($missingSNItems)) {
                DB::rollBack();
                $itemNames = implode(', ', array_unique($missingSNItems));
                return back()->with('error', "Gagal! Item berikut membutuhkan Serial Number namun belum diisi: {$itemNames}. Harap isi SN terlebih dahulu di tab Serial Number.");
            }

            $issuing->update([
                'status' => 'processed', // Status: Ready
                'issued_by' => Auth::id(),
                'issued_at' => now(),
            ]);

            // MOM9: Sinkronisasi status ke Job Schedule
            $this->syncJobScheduleStatus($issuing);

            DB::commit();

            return redirect()->route('warehouse.inventory-issuings.show', $issuing->id)
                ->with('success', 'Inventory status berubah menjadi Ready.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to process inventory: ' . $e->getMessage());
        }
    }

    /**
     * Draft: Change status from Ready (processed) back to Un Prepare (pending)
     */
    public function draft($id)
    {
        try {
            DB::beginTransaction();

            $issuing = InventoryIssuing::findOrFail($id);
            
            if ($issuing->status !== 'processed') {
                return back()->with('error', 'Hanya bisa kembalikan status dari Ready (processed) ke Un Prepare (pending).');
            }

            // Fix: Delete associated auto-created InventoryReceiving if exists
            // Since we are reverting to draft/pending, the "receiving" (verification) is also invalid
            $receiving = \App\Models\InventoryReceiving::where('issuing_id', $issuing->id)->first();
            if ($receiving) {
                $receiving->delete();
                \Log::info("Deleted associated InventoryReceiving ID {$receiving->id} for Issuing ID {$issuing->id} (Reverted to Draft)");
            }

            // Fix: Delete associated InventoryMovement (stock out)
            // Since we are reverting to draft, the stock out should be reversed (deleted)
            \App\Models\InventoryMovement::where('reference_type', 'inventory_issuing')
                ->where('reference_no', $issuing->issuing_number)
                ->delete();
            
            \Log::info("Deleted associated InventoryMovement for Issuing ID {$issuing->id} (Reverted to Draft)");

            $issuing->update([
                'status' => 'pending', // Status: Un Prepare
                'team_id' => null,
                'received_by' => null,
                'received_at' => null, // Clear received_at as well
                'issued_at' => null,   // Clear issued_at as well
                'updated_by' => Auth::id(),
            ]);

            // Sync deletion to Job Schedule
            $this->syncJobScheduleStatus($issuing);

            DB::commit();

            return redirect()->route('warehouse.inventory-issuings.show', $issuing->id)
                ->with('success', 'Status berhasil dikembalikan ke Un Prepare dan penugasan tim telah dihapus.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to draft inventory: ' . $e->getMessage());
        }
    }

    /**
     * Finalize: Change status from Ready (processed) to Finish (sent)
     */
    public function finalize($id)
    {
        try {
            $service = new \App\Services\Warehouse\InventoryIssuingService();
            $service->finalize($id);

            return redirect()->route('warehouse.inventory-issuings.show', $id)
                ->with('success', 'Inventory berhasil di-finalize. Status: Finish.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to finalize inventory: ' . $e->getMessage());
        }
    }

    /**
     * Unpost: Revert status from Finish (sent) back to Ready (processed)
     */
    public function unpost($id)
    {
        try {
            DB::beginTransaction();

            $issuing = InventoryIssuing::findOrFail($id);
            $syncService = new \App\Services\Warehouse\InventoryIssuingService();
            $relatedJobs = $syncService->resolveRelatedJobSchedules($issuing);
            
            if ($issuing->status !== 'sent') {
                return back()->with('error', 'Hanya bisa unpost dari status Finish (sent).');
            }

            if ($relatedJobs->isNotEmpty()) {
                $workStartedStatuses = ['teknisi_sedang_pengerjaan', 'in_progress', 'teknisi_selesai_pengerjaan', 'done_job', 'completed'];

                if ($relatedJobs->contains(fn ($jobSchedule) => in_array($jobSchedule->status, $workStartedStatuses))) {
                    return back()->with('error', 'Tidak bisa unpost karena teknisi sudah mulai pengerjaan di aplikasi mobile.');
                }
            }

            // Delete associated InventoryReceiving (if exists)
            \App\Models\InventoryReceiving::where('issuing_id', $issuing->id)->delete();

            // Delete associated InventoryMovement (stock out)
            \App\Models\InventoryMovement::where('reference_type', 'inventory_issuing')
                ->where('reference_no', $issuing->issuing_number)
                ->delete();

            // Revert Issuing status to processed (Ready)
            $issuing->update([
                'status' => 'processed',
                'received_at' => null,
                'issued_at' => null,
                'updated_by' => Auth::id(),
            ]);

            $syncService->syncJobScheduleStatus($issuing);

            DB::commit();

            return redirect()->route('warehouse.inventory-issuings.show', $issuing->id)
                ->with('success', 'Inventory berhasil di-unpost kembali ke status Ready (Processed). Verifikasi material dan mutasi stok telah dibatalkan.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to unpost inventory: ' . $e->getMessage());
        }
    }

    public function send($id)
    {
        try {
            DB::beginTransaction();

            $issuing = InventoryIssuing::findOrFail($id);
            
            if ($issuing->status !== 'processed') {
                return back()->with('error', 'Only processed issuings can be sent.');
            }

            $issuing->update([
                'status' => 'sent',
            ]);

            $this->updateSerialNumberLifecycle($issuing);

            // Note: Inventory Receiving is NOT auto-created from Issuing
            // - Issuing = barang KELUAR (ke teknisi/customer)
            // - Receiving = barang MASUK (dari supplier/vendor)
            // They are separate, unrelated flows.

            DB::commit();

            return redirect()->route('warehouse.inventory-issuings.index')
                ->with('success', 'Inventory sent successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to send inventory: ' . $e->getMessage());
        }
    }

    private function updateSerialNumberLifecycle($issuing)
    {
        try {
            if ($issuing->status === 'sent') {
                $serialNumberIds = $issuing->items()
                    ->whereNotNull('serial_number_id')
                    ->pluck('serial_number_id')
                    ->toArray();
                
                if (!empty($serialNumberIds)) {
                    \App\Models\SerialNumber::whereIn('id', $serialNumberIds)->update([
                        'status' => 'on_hand',
                        'location_type' => 'technician',
                        'location_id' => $issuing->received_by,
                        'updated_by' => \Auth::id() ?? 1
                    ]);
                    
                    \Log::info("Serial Numbers updated to On Hand for Issuing ID: {$issuing->id}, Technician ID: {$issuing->received_by}");
                }
            }
        } catch (\Exception $e) {
            \Log::error("Failed to update Serial Number lifecycle for Issuing ID: {$issuing->id}: " . $e->getMessage());
        }
    }

    // generateReceivingNumberForIssuing() removed - Receiving is not auto-created from Issuing

    public function receive($id)
    {
        try {
            DB::beginTransaction();

            $issuing = InventoryIssuing::findOrFail($id);
            
            if ($issuing->status !== 'sent') {
                return back()->with('error', 'Only sent inventory can be received.');
            }

            $issuing->update([
                'status' => 'received',
                'received_by' => Auth::id(),
                'received_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('warehouse.inventory-issuings.index')
                ->with('success', 'Inventory received successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to receive inventory: ' . $e->getMessage());
        }
    }

    // API Methods
    public function getIssuingsByWarehouse($warehouseId)
    {
        $issuings = InventoryIssuing::with(['inventoryRequest', 'branch', 'warehouse', 'requestedBy', 'issuedBy'])
            ->where('warehouse_id', $warehouseId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $issuings
        ]);
    }

    public function getIssuingStatistics()
    {
        $stats = [
            'total_issuings' => InventoryIssuing::count(),
            'pending_issuings' => InventoryIssuing::where('status', 'pending')->count(),
            'processed_issuings' => InventoryIssuing::where('status', 'processed')->count(),
            'sent_issuings' => InventoryIssuing::where('status', 'sent')->count(),
            'received_issuings' => InventoryIssuing::where('status', 'received')->count(),
            'cancelled_issuings' => InventoryIssuing::where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    public function getIssuingsByDateRange(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $query = InventoryIssuing::with(['inventoryRequest', 'branch', 'warehouse', 'requestedBy', 'issuedBy'])
            ->whereBetween('issue_date', [$request->start_date, $request->end_date]);

        if ($request->warehouse_id) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $issuings = $query->orderBy('issue_date', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $issuings
        ]);
    }

    // API Methods for Modal Data
    public function getModalData()
    {
        try {
            $user = Auth::user();
            $inventoryRequests = InventoryRequest::where('status', 'approved')
                ->select('id', 'request_number', 'branch_id')
                ->with('branch:id,name')
                ->get();
            
            $branches = Branch::where('is_active', true)
                ->select('id', 'name')
                ->get();
            
            $warehousesQuery = Warehouse::where('is_active', true)
                ->select('id', 'name', 'branch_id', 'manager')
                ->with('branch:id,name');
            
            // Warehouse Manager / Admin Lock Logic
            $isWarehouseManager = $user->hasRole('Warehouse Manager');
            $isAdmin = $user->hasRole('Admin') || $user->hasRole('super_admin') || $user->hasRoleStartingWith('Management');
            $managedWarehouseId = null;

            // If it's a Warehouse Manager, find their assigned warehouse
            if ($isWarehouseManager && !$isAdmin) {
                $managedWarehouse = Warehouse::where('manager', $user->id)->first();
                if ($managedWarehouse) {
                    $managedWarehouseId = $managedWarehouse->id;
                }
            }

            $warehouses = $warehousesQuery->get();
            
            $users = User::where('is_active', true)
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();

            // Fetch all active teams for Team Selection dropdown
            $teams = \App\Models\Team::where('active_status', true)
                ->select('id', 'team_name', 'team_code')
                ->orderBy('team_name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'inventory_requests' => $inventoryRequests,
                    'branches' => $branches,
                    'warehouses' => $warehouses,
                    'users' => $users,
                    'teams' => $teams,
                    'user_context' => [
                        'is_warehouse_manager' => $isWarehouseManager && !$isAdmin,
                        'managed_warehouse_id' => $managedWarehouseId,
                        'is_admin' => $isAdmin
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load modal data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getIssuingDetails($id)
    {
        $issuing = InventoryIssuing::with([
            'inventoryRequest:id,request_number',
            'branch:id,name',
            'warehouse:id,name',
            'requestedBy:id,name',
            'issuedBy:id,name',
            'receivedBy:id,name',
            'items.product:id,name,sku'
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $issuing
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:inventory_issuings,id'
        ]);

        try {
            DB::beginTransaction();
            $countDeleted = 0;
            $skipped = 0;
            $syncService = new \App\Services\Warehouse\InventoryIssuingService();

            foreach (InventoryIssuing::whereIn('id', $request->ids)->get() as $issuing) {
                if ($issuing->status !== 'pending') {
                    $skipped++;
                    continue;
                }

                $materialIssue = \App\Models\MaterialIssue::where('issue_number', $issuing->reference_no)->first();
                if ($materialIssue) {
                    $materialIssue->update([
                        'status' => 'approved',
                        'updated_by' => Auth::id(),
                    ]);
                }

                \App\Models\InventoryReceiving::where('issuing_id', $issuing->id)->delete();
                \App\Models\InventoryMovement::where('reference_no', $issuing->reference_no)
                    ->where('reference_type', 'material_issue')
                    ->where('movement_type', 'out')
                    ->delete();
                \App\Models\InventoryMovement::where('reference_no', $issuing->issuing_number)
                    ->where('reference_type', 'inventory_issuing')
                    ->delete();

                $issuing->items()->delete();
                $issuing->delete();

                if ($materialIssue) {
                    $syncService->syncGroupedJobMaterialLifecycleFromMaterialIssue($materialIssue);
                }

                $countDeleted++;
            }

            DB::commit();
            
            $message = "Successfully deleted {$countDeleted} inventory issuing(s).";
            if ($skipped > 0) {
                $message .= " {$skipped} items were skipped because they are not in pending status.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $countDeleted
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete inventory issuings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scan/Validate Serial Number for Inventory Issuing
     * Validates SN: must exist, match product, not used in Unit On Wall, status available
     */
    public function scanSerialNumber(Request $request, $id)
    {
        $request->validate([
            'issuing_item_id' => 'required|exists:inventory_issuing_items,id',
            'serial_number' => 'required|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $issuing = InventoryIssuing::findOrFail($id);
            
            if ($issuing->status !== 'pending') {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya bisa scan Serial Number untuk issuing dengan status pending (Un Prepare).'
                ], 422);
            }

            $issuingItem = \App\Models\InventoryIssuingItem::with(['product.productCategory', 'product.productType'])
                ->findOrFail($request->issuing_item_id);
            
            if ($issuingItem->inventory_issuing_id != $issuing->id) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item tidak sesuai dengan issuing ini.'
                ], 422);
            }

            if (!($issuingItem->product?->requiresSerialNumber() ?? false)) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item ini tidak membutuhkan Serial Number.'
                ], 422);
            }

            $serialNumber = strtoupper(trim($request->serial_number));
            
            // Validasi 1: SN harus ada di serial_numbers table
            $sn = \App\Models\SerialNumber::where('serial_number', $serialNumber)
                ->lockForUpdate()
                ->first();
            
            if (!$sn) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => "Serial Number {$serialNumber} tidak ditemukan. Pastikan SN sudah diinput di Inventory Receiving."
                ], 422);
            }

            // Validasi 2: SN harus sesuai dengan produk yang di-issue
            if ($sn->master_product_id !== $issuingItem->product_id) {
                DB::rollBack();
                $productName = $issuingItem->product->name ?? 'Unknown';
                return response()->json([
                    'status' => 'error',
                    'message' => "Serial Number {$serialNumber} tidak sesuai dengan produk yang di-issue ({$productName})."
                ], 422);
            }

            // Validasi 3: SN tidak boleh sudah terpakai di Unit On Wall
            $unitOnWall = \App\Models\UnitOnWall::where('serial_number_id', $sn->id)
                ->where('status', 'active')
                ->first();
            
            if ($unitOnWall) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => "Serial Number {$serialNumber} sudah terpasang di Unit On Wall. Tidak bisa digunakan lagi."
                ], 422);
            }

            // Validasi 4: SN status harus ready (standardized)
            if (!in_array($sn->status, ['ready', 'available'])) { // Legacy support for 'available'
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => "Serial Number {$serialNumber} status tidak ready (Status: {$sn->status_text})."
                ], 422);
            }

            if ((int) $sn->warehouse_id !== (int) $issuing->warehouse_id) {
                DB::rollBack();
                $correctWarehouse = $issuing->warehouse?->name ?? 'warehouse issue';
                $sourceWarehouse = $sn->warehouse?->name ?? 'warehouse lain';
                return response()->json([
                    'status' => 'error',
                    'message' => "Serial Number {$serialNumber} berasal dari {$sourceWarehouse}. SN ini harus dari warehouse {$correctWarehouse}."
                ], 422);
            }

            app(SerialNumberIssuingLinkService::class)->releaseStaleLinks($sn, $issuingItem->id, Auth::id());

            $reservedItem = $this->findActiveIssuingItemUsingSerial($sn->id, $issuingItem->id);
            if ($reservedItem) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => "Serial Number {$serialNumber} masih dipakai di Inventory Issuing {$reservedItem->inventoryIssuing?->issuing_number}. Tidak bisa dipakai di dua WI yang masih disiapkan."
                ], 422);
            }

            // Pertahankan room yang sudah ada / tersimpan di notes agar tidak ketimpa
            // oleh item MI pertama ketika ada beberapa room dengan produk yang sama.
            $roomName = $issuingItem->room_name ?: $this->extractRoomNameFromItemNotes($issuingItem->notes);
            if ($issuing->reference_no) {
                $materialIssue = \App\Models\MaterialIssue::where('issue_number', $issuing->reference_no)->first();
                if ($materialIssue && !$roomName) {
                    $miQuery = \App\Models\MaterialIssueItem::where('material_issue_id', $materialIssue->id)
                        ->where('product_id', $issuingItem->product_id)
                        ->whereNotNull('room_name');

                    $noteRoomName = $this->extractRoomNameFromItemNotes($issuingItem->notes);
                    if ($noteRoomName) {
                        $miQuery->where('room_name', $noteRoomName);
                    }

                    $miItem = $miQuery->first();
                    if ($miItem) {
                        $roomName = $miItem->room_name;
                    }
                }
            }

            // Link SN ke issuing item (dan isi room_name jika ditemukan)
            $issuingItem->update([
                'serial_number_id' => $sn->id,
                'room_name'        => $roomName ?: $issuingItem->room_name,
                'updated_by'       => Auth::id()
            ]);


            // Update SN status to in_use (optional, bisa juga tetap available sampai benar-benar terpasang)
            // $sn->update(['status' => 'in_use']);

            DB::commit();

            \Log::info("Serial Number {$serialNumber} linked to Inventory Issuing Item {$issuingItem->id} for Issuing {$issuing->issuing_number}");

            return response()->json([
                'status' => 'success',
                'message' => "Serial Number {$serialNumber} berhasil divalidasi dan di-link ke item!",
                'data' => [
                    'serial_number' => $sn->serial_number,
                    'product_name' => $issuingItem->product->name ?? 'Unknown',
                    'status' => $sn->status
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to scan serial number: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memvalidasi Serial Number: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Print receipt for inventory issuing
     * Shows logo, QR code, items list, and signatures
     */
    public function printReceipt($id)
    {
        $issuing = InventoryIssuing::with([
            'inventoryRequest',
            'branch',
            'warehouse',
            'requestedBy',
            'issuedBy',
            'receivedBy',
            'items.product'
        ])->findOrFail($id);
        
        return view('warehouse.inventory-issuings.receipt', compact('issuing'));
    }

    /**
     * Get products from a rental (for manual issuing modal)
     * Returns all products in the rental with their details
     */
    public function getRentalProducts($rentalId)
    {
        try {
            $rental = \App\Models\MasterRental::with(['rentalDetails.masterProduct.productType'])
                ->findOrFail($rentalId);

            $products = $rental->rentalDetails
                ->filter(function($detail) {
                    return $detail->master_product_id !== null;
                })
                ->map(function($detail) {
                    return [
                        'id' => $detail->master_product_id,
                        'name' => $detail->masterProduct->name ?? 'Unknown',
                        'sku' => $detail->masterProduct->sku ?? '-',
                        'type' => $detail->masterProduct->productType->name ?? '-',
                        'is_unit' => $detail->masterProduct->productType->is_unit ?? false,
                        'quantity' => $detail->quantity ?? 1,
                    ];
                })->values();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'rental' => [
                        'id' => $rental->id,
                        'name' => $rental->rental_name,
                    ],
                    'products' => $products
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load rental products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available serial numbers for a product (status = ready)
     * For manual issuing modal - serial number dropdown
     * Accepts optional warehouse_id filter for warehouse lock feature
     */
    public function getAvailableSerials(Request $request, $productId)
    {
        try {
            $warehouseId = $request->input('warehouse_id');
            
            $query = \App\Models\SerialNumber::where('master_product_id', $productId)
                ->whereIn('status', ['ready', 'available']) // Support both statuses
                ->where('location_type', 'warehouse') // MUST be in warehouse
                ->whereNotIn('id', function ($subQuery) {
                    $subQuery->select('inventory_issuing_items.serial_number_id')
                        ->from('inventory_issuing_items')
                        ->join('inventory_issuings', 'inventory_issuings.id', '=', 'inventory_issuing_items.inventory_issuing_id')
                        ->whereNotNull('inventory_issuing_items.serial_number_id')
                        ->whereIn('inventory_issuings.status', ['pending', 'processed']);
                })
                ->with('warehouse:id,name')
                ->orderBy('serial_number');
            
            // Filter by warehouse if specified (for warehouse lock feature)
            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            $serials = $query->get()->map(function($sn) {
                return [
                    'id' => $sn->id,
                    'serial_number' => $sn->serial_number,
                    'warehouse_id' => $sn->warehouse_id,
                    'warehouse_name' => $sn->warehouse->name ?? '-',
                    'status' => $sn->status,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $serials
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load serial numbers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
 * Get teams for a user (for manual issuing - team selection)
 * Returns all teams that the user is a member of OR is the team head
 */
public function getUserTeams($userId)
{
    try {
        $user = User::findOrFail($userId);
        
        // Get teams where user is a member (via team_members pivot)
        $memberTeams = $user->teams()
            ->wherePivot('is_active', true)
            ->select('teams.id', 'teams.team_name', 'teams.team_code')
            ->get();
        
        // Get teams where user is the team head
        $headTeams = \App\Models\Team::where('team_head_id', $userId)
            ->where('active_status', true)
            ->select('id', 'team_name', 'team_code')
            ->get();
        
        // Merge and remove duplicates
        $allTeams = $memberTeams->merge($headTeams)->unique('id')->values();

        $teams = $allTeams->map(function($team) {
            return [
                'id' => $team->id,
                'team_name' => $team->team_name,
                'team_code' => $team->team_code,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                ],
                'teams' => $teams
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to load user teams: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Store manual inventory issuing (without Material Issue)
     * Creates issuing with selected products and serial numbers
     */
    public function storeManual(Request $request)
    {
        try {
            $request->validate([
                'received_by' => 'required|exists:users,id',
                'team_id' => 'required|exists:teams,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'remarks' => 'nullable|string|max:500',
            ]);

            DB::beginTransaction();

            // Get warehouse and branch
            $warehouse = Warehouse::findOrFail($request->warehouse_id);
            $branchId = $warehouse->branch_id ?? Auth::user()->branch_id ?? 1;

            // Generate issuing number
            $issuingNumber = app(\App\Services\DocumentNumberService::class)->generate(
                'inventory_issuing',
                null,
                null,
                null,
                null,
                null,
                $request->warehouse_id
            );

            // Create inventory issuing (manual = no reference_no)
            $issuing = InventoryIssuing::create([
                'issuing_number' => $issuingNumber,
                'branch_id' => $branchId,
                'warehouse_id' => $request->warehouse_id,
                'issue_date' => now(),
                'reference_no' => 'MI-' . strtoupper(\Illuminate\Support\Str::random(5)), // Marker for Manual Issuing
                'requested_by' => Auth::id(),
                'received_by' => $request->received_by,
                'team_id' => $request->team_id,
                'status' => 'pending',
                'remarks' => $request->remarks,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Products will be added later on show page via Add Item button

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Manual inventory issuing created successfully.',
                'data' => [
                    'id' => $issuing->id,
                    'issuing_number' => $issuing->issuing_number,
                    'redirect_url' => route('warehouse.inventory-issuings.show', $issuing->id)
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Failed to create manual inventory issuing: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create manual inventory issuing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active master products for manual issuing
     */
    public function getAllProducts()
    {
        try {
            $products = MasterProduct::where('is_active', true)
                ->with(['productType', 'productCategory'])
                ->orderBy('name')
                ->get()
                ->map(function($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->sku,
                        'type_name' => $p->productType?->name ?? 'N/A',
                        'has_serial' => $p->requiresSerialNumber()
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load products: ' . $e->getMessage()
            ], 500);
        }
    }

    private function findActiveIssuingItemUsingSerial(int $serialNumberId, ?int $exceptItemId = null): ?\App\Models\InventoryIssuingItem
    {
        return \App\Models\InventoryIssuingItem::with('inventoryIssuing')
            ->where('serial_number_id', $serialNumberId)
            ->when($exceptItemId, fn ($query) => $query->where('id', '!=', $exceptItemId))
            ->whereHas('inventoryIssuing', function ($query) {
                $query->whereIn('status', ['pending', 'processed']);
            })
            ->latest('id')
            ->first();
    }

    private function normalizeProductBrandLine(?string $brandLine): ?string
    {
        $normalized = trim(strtolower((string) $brandLine));

        return $normalized !== '' ? preg_replace('/\s+/', ' ', $normalized) : null;
    }


    /**
     * Get candidate products for replacement (same brand line, includes all sizes/stock status)
     */
    public function getReplacementCandidates(Request $request)
    {
        try {
            $packagingSizeId = $request->packaging_size_id;
            $brandLine = $request->brand_line;
            $warehouseId = $request->warehouse_id;

            if (!$brandLine || !$warehouseId) {
                return response()->json(['status' => 'error', 'message' => 'Missing required parameters.']);
            }

            $normalizedBrandLine = $this->normalizeProductBrandLine($brandLine);
            if (!$normalizedBrandLine) {
                return response()->json(['status' => 'error', 'message' => 'Brand line produk saat ini kosong. Change aroma tidak bisa diproses karena rawan cross brand line.']);
            }

            $products = \App\Models\MasterProduct::where('is_active', true)
                ->whereHas('productType', function($q) {
                    $q->where('is_unit', false);
                })
                ->whereRaw('LOWER(TRIM(brand_line)) = ?', [$normalizedBrandLine])
                ->with(['warehouseProducts' => function($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                }])
                ->get()
                ->map(function($product) use ($packagingSizeId) {
                    $quantity = $product->warehouseProducts->first()->quantity ?? 0;
                    
                    $isSelectable = true;
                    $reasons = [];
                    
                    if ($quantity <= 0) {
                        $isSelectable = false;
                        $reasons[] = 'Stok Kosong';
                    }
                    
                    if ($packagingSizeId && $product->packaging_size_id != $packagingSizeId) {
                        $isSelectable = false;
                        $reasons[] = 'Ukuran Berbeda';
                    }

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'stock' => $quantity,
                        'is_selectable' => $isSelectable,
                        'reason' => !empty($reasons) ? implode(', ', $reasons) : null
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Add an item to an existing inventory issuing
     */
    public function addItem(Request $request, $id)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:master_products,id',
                'quantity' => 'required|numeric|min:0.01',
                'serial_number_id' => 'nullable|exists:serial_numbers,id',
                'remarks' => 'nullable|string|max:255'
            ]);

            DB::beginTransaction();

            $issuing = InventoryIssuing::findOrFail($id);

            if ($issuing->status === 'sent' || $issuing->status === 'received') {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot add items to completed or sent issuing.'
                ], 403);
            }

            if ($request->serial_number_id) {
                $sn = \App\Models\SerialNumber::whereKey($request->serial_number_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $sn->master_product_id !== (int) $request->product_id) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Serial Number tidak sesuai dengan produk yang ditambahkan.'
                    ], 422);
                }

                if (!in_array($sn->status, ['ready', 'available'], true)) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => "Serial Number {$sn->serial_number} status tidak ready (Status: {$sn->status_text})."
                    ], 422);
                }

                app(SerialNumberIssuingLinkService::class)->releaseStaleLinks($sn, null, Auth::id());

                $reservedItem = $this->findActiveIssuingItemUsingSerial($sn->id);
                if ($reservedItem) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => "Serial Number {$sn->serial_number} masih dipakai di Inventory Issuing {$reservedItem->inventoryIssuing?->issuing_number}. Tidak bisa dipakai di dua WI yang masih disiapkan."
                    ], 422);
                }
            }

            $item = $issuing->items()->create([
                'product_id' => $request->product_id,
                'quantity_requested' => $request->quantity,
                'serial_number_id' => $request->serial_number_id,
                'notes' => $request->remarks,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Item added successfully.',
                'data' => $item->load('product')
            ]);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an item from an existing inventory issuing
     */
    public function deleteItem($issuingId, $itemId)
    {
        try {
            DB::beginTransaction();
            
            $issuing = InventoryIssuing::findOrFail($issuingId);
            
            // Validation: Only pending/draft can be modified
            if ($issuing->status !== 'pending' && $issuing->status !== 'draft') {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya item pada issuing dengan status draft/pending yang bisa dihapus.'
                ], 403);
            }

            $item = \App\Models\InventoryIssuingItem::where('inventory_issuing_id', $issuingId)
                ->findOrFail($itemId);
            
            // If item has a serial number associated, update that serial number status back to available
            if ($item->serial_number_id) {
                $sn = \App\Models\SerialNumber::find($item->serial_number_id);
                if ($sn && !$this->findActiveIssuingItemUsingSerial($sn->id, $item->id)) {
                    $sn->update(['status' => 'ready']);
                }
            }

            $item->delete();
            
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Item berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change aroma of an issuing item
     */
    public function changeAroma(Request $request, $itemId)
    {
        $request->validate([
            'new_product_id' => 'required|exists:master_products,id',
            'quantity' => 'required|numeric|min:0.01',
            'change_reason' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $item = \App\Models\InventoryIssuingItem::with('inventoryIssuing', 'product.productType')->findOrFail($itemId);
            $issuing = $item->inventoryIssuing;

            // 1. Validation
            if (in_array($issuing->status, ['sent', 'received', 'cancelled'], true)) {
                throw new \Exception("Cannot change aroma after issuing has been finalized/sent.");
            }

            // Check if Item is a Unit (Machine) - Restriction
            if ($item->product->productType && $item->product->productType->is_unit) {
                throw new \Exception("Cannot change aroma for Unit items (Machines). This feature is for Aromas/Refills only.");
            }

            $newProduct = MasterProduct::with('productType')->findOrFail($request->new_product_id);
            
            // Ensure New Product is also NOT a Unit
            if ($newProduct->productType && $newProduct->productType->is_unit) {
                throw new \Exception("Selected new product is a Unit. Please select an Aroma/Refill.");
            }

            // Check Brand Line & Size. Aroma/refill may change variant/stock source,
            // but it must stay in the same commercial brand line.
            $currentBrandLine = $this->normalizeProductBrandLine($item->product->brand_line ?? null);
            $newBrandLine = $this->normalizeProductBrandLine($newProduct->brand_line ?? null);
            if (!$currentBrandLine || !$newBrandLine) {
                throw new \Exception('Brand Line produk tidak lengkap. Aroma tidak boleh diganti sebelum brand line produk lama dan baru valid.');
            }

            if ($currentBrandLine !== $newBrandLine) {
                throw new \Exception(sprintf(
                    'Brand Line mismatch. Aroma tidak boleh pindah brand line dari %s ke %s.',
                    $item->product->brand_line ?: '-',
                    $newProduct->brand_line ?: '-'
                ));
            }

            if ($item->product->packaging_size_id != $newProduct->packaging_size_id) {
                throw new \Exception("Packaging Size mismatch.");
            }
            
            // Check Quantity
            if ($request->quantity > $item->quantity_requested) {
                throw new \Exception("Quantity cannot exceed current item quantity.");
            }

            $oldProductId = $item->product_id;
            $oldProductName = $item->product->name;

            // Check Stock
            $stock = \App\Models\WarehouseProduct::where('warehouse_id', $issuing->warehouse_id)
                ->where('master_product_id', $newProduct->id)
                ->value('quantity');
            
            if ($stock < $request->quantity) {
                throw new \Exception("Insufficient stock for new aroma.");
            }

            // 2. Resolve Contract
        $jobSchedule = \App\Models\JobSchedule::where('job_number', $issuing->reference_no)->first();
        
        // MOM: Fallback logic - if Reference No is a Material Issue number
        if (!$jobSchedule) {
            $materialIssue = \App\Models\MaterialIssue::where('issue_number', $issuing->reference_no)->first();
            if ($materialIssue) {
                // Get JobSchedule from the first related JAMI
                $jami = $materialIssue->jobAssignMaterialIssues->first();
                if ($jami) {
                    $jobSchedule = $jami->getJobSchedule();
                }
            }
        }

        if (!$jobSchedule) {
             throw new \Exception("Cannot verify Contract. Issuing Reference No ({$issuing->reference_no}) must match a Job Number or Material Issue Number.");
        }    
            
            $jobSchedule->loadMissing([
                'jobAdvice.contract',
                'jobAdvice.quotation',
                'jobAdvice.rooms.quotationRoom',
                'jobAdvice.rooms.contractRoom.room',
                'jobScheduleRooms',
            ]);

            $jobAdvice = $jobSchedule->jobAdvice;
            if (!$jobAdvice) {
                throw new \Exception("Job linked to this issuing has no valid Job Advice.");
            }

            $roomName = trim((string) ($item->room_name ?: $jobSchedule->room_name));
            $roomId = $jobSchedule->room_id;
            if (!$roomId && $roomName) {
                $roomId = $jobSchedule->jobScheduleRooms
                    ->first(fn ($room) => trim(strtolower((string) $room->room_name)) === trim(strtolower($roomName)))
                    ?->room_id;
            }

            $contract = $jobAdvice->contract;
            $contractRoom = null;
            if ($contract) {
                $contractRoomQuery = \App\Models\ContractRoom::where('contract_id', $contract->id);
                if ($roomId) {
                    $contractRoomQuery->where('room_id', $roomId);
                } elseif ($roomName) {
                    $contractRoomQuery->whereHas('room', function ($query) use ($roomName) {
                        $query->whereRaw('LOWER(TRIM(room_name)) = ?', [strtolower($roomName)]);
                    });
                }

                $contractRoom = $contractRoomQuery->first();

                if (!$contractRoom) {
                    throw new \Exception("Contract Room not found for this Job.");
                }
            }

            // 3. Perform Split
            // Reduce old item
            $remainQty = $item->quantity_requested - $request->quantity;
            if ($remainQty > 0) {
                // Sync quantity_issued as well
                $item->update([
                    'quantity_requested' => $remainQty,
                    'quantity_issued' => $remainQty
                ]);
            } else {
                $item->delete(); // Full swap
            }

            // Create new item
            $newItem = \App\Models\InventoryIssuingItem::create([
                'inventory_issuing_id' => $issuing->id,
                'job_assign_schedule_id' => $item->job_assign_schedule_id, // MOM: Carry over assignment ID for mobile sync
                'room_name' => $item->room_name,
                'product_id' => $newProduct->id,
                'quantity_requested' => $request->quantity,
                'quantity_issued' => $request->quantity, // Sync quantity_issued
                'unit_price' => $newProduct->unit_price ?? 0,
                'notes' => 'Aroma Changed from ' . $oldProductName . '. ' . ($request->change_reason ?? ''),
                'created_by' => Auth::id()
            ]);

            // Keep warehouse stock accurate: the old aroma was already deducted when MI was issued.
            // Changing it before final send means old stock returns and new stock is consumed.
            $oldWarehouseStock = \App\Models\WarehouseProduct::firstOrCreate(
                [
                    'warehouse_id' => $issuing->warehouse_id,
                    'master_product_id' => $oldProductId,
                ],
                [
                    'quantity' => 0,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]
            );
            $oldWarehouseStock->increment('quantity', $request->quantity);

            $newWarehouseStock = \App\Models\WarehouseProduct::where('warehouse_id', $issuing->warehouse_id)
                ->where('master_product_id', $newProduct->id)
                ->lockForUpdate()
                ->first();

            if (!$newWarehouseStock || $newWarehouseStock->quantity < $request->quantity) {
                throw new \Exception("Insufficient stock for new aroma.");
            }

            $newWarehouseStock->decrement('quantity', $request->quantity);

            if ($contract && $contractRoom) {
                // 4. Create and Approve Aroma Change for contract-based jobs.
                 $aromaChange = \App\Models\AromaChange::create([
                    'change_number' => \App\Models\AromaChange::generateChangeNumber($contract),
                    'contract_id' => $contract->id,
                    'building_id' => $contractRoom->room->building_id,
                    'unit_id' => $contract->unit_id ?? null, // MOM fixes 0 to null for FK constraint
                    'room_id' => $roomId,
                    'contract_room_id' => $contractRoom->id,
                    
                    'previous_aroma_code' => $item->product->product_code, 
                    'previous_aroma_name' => $item->product->name, 
                    'previous_product_type_id' => $item->product->product_type_id, 
                    'previous_product_category_id' => $item->product->product_category_id, 
                    'previous_product_id' => $item->product_id, // Added for precise product tracking
                    
                    'new_aroma' => $newProduct->variant_name ?? $newProduct->name,
                    'new_aroma_code' => $newProduct->variant ?? $newProduct->product_code,
                    'new_aroma_name' => $newProduct->variant_name ?? $newProduct->name,
                    'new_product_type_id' => $newProduct->product_type_id,
                    'new_product_category_id' => $newProduct->product_category_id,
                    'new_product_id' => $newProduct->id, // Added for precise product tracking
                    
                    'change_reason' => $request->change_reason ?? 'Warehouse Issuing Change',
                    'status' => \App\Models\AromaChange::STATUS_APPROVED, // Auto-approve
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                    'requested_by' => Auth::id(),
                    'created_by' => Auth::id(),
                    'approval_notes' => 'Auto-approved via Warehouse Issuing Change'
                ]);
                
                // 5. Apply Change (Update ContractRoom/Quotation/MaterialIssue)
                // Note: applyChange now handles synchronization to MaterialIssueItem automatically
                $aromaChange->applyChange(Auth::id());
            } else {
                // Install Free / quotation-based jobs may not have a contract yet.
                // Keep the operational documents in sync without forcing an AromaChange contract record.
                $quotationRoom = $jobAdvice->rooms
                    ->first(function ($jaRoom) use ($roomId, $roomName) {
                        $qr = $jaRoom->quotationRoom;
                        if (!$qr) {
                            return false;
                        }

                        if ($roomId && (int) $qr->room_id === (int) $roomId) {
                            return true;
                        }

                        return $roomName && trim(strtolower((string) $qr->room_name)) === trim(strtolower($roomName));
                    })
                    ?->quotationRoom;

                if ($quotationRoom) {
                    $quotationRoom->update([
                        'aroma_product_id' => $newProduct->id,
                        'aroma_variant' => $newProduct->variant_name ?? $newProduct->name,
                        'updated_by' => Auth::id(),
                    ]);
                }

                $materialIssueItem = \App\Models\MaterialIssueItem::where('job_assign_schedule_id', $item->job_assign_schedule_id)
                    ->where('product_id', $oldProductId)
                    ->when($roomName, function ($query) use ($roomName) {
                        $query->whereRaw('LOWER(TRIM(room_name)) = ?', [strtolower($roomName)]);
                    })
                    ->first();

                if ($materialIssueItem) {
                    if ($remainQty > 0) {
                        $materialIssueItem->update([
                            'quantity' => $remainQty,
                            'updated_by' => Auth::id(),
                        ]);

                        \App\Models\MaterialIssueItem::create([
                            'material_issue_id' => $materialIssueItem->material_issue_id,
                            'job_assign_schedule_id' => $materialIssueItem->job_assign_schedule_id,
                            'product_id' => $newProduct->id,
                            'room_name' => $materialIssueItem->room_name,
                            'quantity' => $request->quantity,
                            'convert' => $materialIssueItem->convert,
                            'bom_quantity' => $newProduct->bom_quantity ?? $materialIssueItem->bom_quantity,
                            'unit_price' => $newProduct->unit_price ?? $materialIssueItem->unit_price,
                            'total_price' => ($newProduct->unit_price ?? $materialIssueItem->unit_price ?? 0) * $request->quantity,
                            'notes' => trim(($materialIssueItem->notes ? $materialIssueItem->notes . "\n" : '') . "Aroma changed from {$oldProductName} to {$newProduct->name}."),
                            'is_copied' => $materialIssueItem->is_copied,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);
                    } else {
                        $materialIssueItem->update([
                            'product_id' => $newProduct->id,
                            'quantity' => $request->quantity,
                            'bom_quantity' => $newProduct->bom_quantity ?? $materialIssueItem->bom_quantity,
                            'unit_price' => $newProduct->unit_price ?? $materialIssueItem->unit_price,
                            'total_price' => ($newProduct->unit_price ?? $materialIssueItem->unit_price ?? 0) * $request->quantity,
                            'updated_by' => Auth::id(),
                        ]);
                    }
                }

                \Log::info('Change aroma applied for quotation/free-install job without contract.', [
                    'inventory_issuing_id' => $issuing->id,
                    'inventory_issuing_item_id' => $newItem->id,
                    'job_schedule_id' => $jobSchedule->id,
                    'job_number' => $jobSchedule->job_number,
                    'room_name' => $roomName,
                    'old_product_id' => $oldProductId,
                    'new_product_id' => $newProduct->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => $contract
                    ? 'Aroma changed, Contract updated, and Job Schedule synced.'
                    : 'Aroma changed for this free-install/quotation job and issuing synced.'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * MOM9: Sync JobSchedule status to 'barang_siap_diambil' when inventory is ready/issued
     */
    private function syncJobScheduleStatus($issuing)
    {
        try {
            (new \App\Services\Warehouse\InventoryIssuingService())->syncJobScheduleStatus($issuing);
        } catch (\Exception $e) {
            \Log::error("CRITICAL SYNC ERROR for Issuing ID {$issuing->id}: " . $e->getMessage());
        }
    }
    /**
     * Get Team Members for Dropdown (Includes Head Team)
     */
    public function getTeamMembers(\App\Models\Team $team)
    {
        // Get Head Team
        $head = $team->teamHead()->select('users.id', 'users.name')->first();
        
        // Get Members
        $members = $team->users()
            ->select('users.id', 'users.name')
            ->orderBy('users.name')
            ->get();
            
        // Combine and Unique
        $all = collect();
        if ($head) {
            $all->push($head);
        }
        
        foreach ($members as $member) {
            // Avoid duplicate if head is also a member
            if (!$all->contains('id', $member->id)) {
                $all->push($member);
            }
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $all->sortBy('name')->values()
        ]);
    }
}

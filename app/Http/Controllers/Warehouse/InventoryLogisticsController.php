<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\LogisticsTracking;
use App\Models\BeritaAcara;
use App\Models\PurchasingRequest;
use App\Models\Warehouse;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryLogisticsController extends Controller
{
    public function tracking(Request $request)
    {
        $query = LogisticsTracking::with(['inventoryRequest', 'fromWarehouse', 'toBranch', 'createdBy', 'updatedBy']);

        if ($request->filled('tracking_number')) {
            $query->where('tracking_number', 'like', '%' . $request->tracking_number . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_warehouse_id')) {
            $query->where('from_warehouse_id', $request->from_warehouse_id);
        }

        if ($request->filled('to_branch_id')) {
            $query->where('to_branch_id', $request->to_branch_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $trackings = $query->orderBy('created_at', 'desc')->paginateStd(25);
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $inventoryRequests = \App\Models\InventoryRequest::where('status', 'approved')->orderBy('request_number')->get();

        return view('warehouse.inventory-logistics.tracking', compact('trackings', 'warehouses', 'branches', 'inventoryRequests'));
    }

    public function showTracking(LogisticsTracking $tracking)
    {
        $tracking->load(['inventoryRequest', 'fromWarehouse', 'toBranch', 'createdBy', 'updatedBy']);
        
        return response()->json([
            'id' => $tracking->id,
            'tracking_number' => $tracking->tracking_number,
            'status' => $tracking->status,
            'resi_number' => $tracking->resi_number,
            'courier_name' => $tracking->courier_name,
            'notes' => $tracking->notes,
            'from_warehouse' => $tracking->fromWarehouse ? $tracking->fromWarehouse->name : 'N/A',
            'to_branch' => $tracking->toBranch ? $tracking->toBranch->name : 'N/A',
            'inventory_request' => $tracking->inventoryRequest ? $tracking->inventoryRequest->request_number : 'N/A',
            'requested_at' => $tracking->requested_at ? $tracking->requested_at->format('d/m/Y H:i') : 'N/A',
            'preparing_at' => $tracking->preparing_at ? $tracking->preparing_at->format('d/m/Y H:i') : 'N/A',
            'shipped_at' => $tracking->shipped_at ? $tracking->shipped_at->format('d/m/Y H:i') : 'N/A',
            'delivered_at' => $tracking->delivered_at ? $tracking->delivered_at->format('d/m/Y H:i') : 'N/A',
            'created_at' => $tracking->created_at->format('d/m/Y H:i'),
            'created_by' => $tracking->createdBy ? $tracking->createdBy->name : 'N/A',
        ]);
    }

    public function createTracking(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'inventory_request_id' => 'required|exists:inventory_requests,id',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_branch_id' => 'required|exists:branches,id',
            'courier_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $tracking = LogisticsTracking::createTracking(
                $request->inventory_request_id,
                $request->from_warehouse_id,
                $request->to_branch_id
            );

            if ($request->courier_name) {
                $tracking->update(['courier_name' => $request->courier_name]);
            }

            if ($request->notes) {
                $tracking->update(['notes' => $request->notes]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Logistics tracking created successfully.',
                'data' => $tracking->load(['inventoryRequest', 'fromWarehouse', 'toBranch', 'createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create logistics tracking: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateTrackingStatus(Request $request, LogisticsTracking $tracking)
    {
        $validator = \Validator::make($request->all(), [
            'status' => 'required|in:requested,preparing,shipped,delivered,returned,cancelled',
            'resi_number' => 'nullable|string|max:255',
            'courier_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $tracking->updateStatus($request->status, $request->notes);

            if ($request->resi_number) {
                $tracking->update(['resi_number' => $request->resi_number]);
            }

            if ($request->courier_name) {
                $tracking->update(['courier_name' => $request->courier_name]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Tracking status updated successfully.',
                'data' => $tracking->load(['inventoryRequest', 'fromWarehouse', 'toBranch', 'createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update tracking status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function beritaAcara(Request $request)
    {
        $query = BeritaAcara::with(['logisticsTracking', 'inventoryReceiving', 'reportedBy', 'approvedBy', 'createdBy', 'updatedBy']);

        if ($request->filled('berita_acara_number')) {
            $query->where('berita_acara_number', 'like', '%' . $request->berita_acara_number . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $beritaAcaras = $query->orderBy('created_at', 'desc')->paginateStd(25);
        $logisticsTrackings = LogisticsTracking::where('status', 'delivered')->orderBy('tracking_number')->get();
        $inventoryReceivings = \App\Models\InventoryReceiving::where('status', 'received')->orderBy('receiving_number')->get();

        return view('warehouse.inventory-logistics.berita-acara', compact('beritaAcaras', 'logisticsTrackings', 'inventoryReceivings'));
    }

    public function createBeritaAcara(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'logistics_tracking_id' => 'required|exists:logistics_tracking,id',
            'inventory_receiving_id' => 'required|exists:inventory_receivings,id',
            'type' => 'required|in:loss,damage,discrepancy',
            'description' => 'required|string',
            'action_taken' => 'required|string',
            'estimated_value' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $beritaAcara = BeritaAcara::createBeritaAcara(
                $request->logistics_tracking_id,
                $request->inventory_receiving_id,
                $request->type,
                $request->description,
                $request->action_taken,
                $request->estimated_value
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berita Acara created successfully.',
                'data' => $beritaAcara->load(['logisticsTracking', 'inventoryReceiving', 'reportedBy', 'createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Berita Acara: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approveBeritaAcara(Request $request, BeritaAcara $beritaAcara)
    {
        $validator = \Validator::make($request->all(), [
            'approval_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if (!$beritaAcara->canBeApproved()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Berita Acara cannot be approved in current status.'
                ], 422);
            }

            DB::beginTransaction();

            $beritaAcara->approve(Auth::id(), $request->approval_notes);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berita Acara approved successfully.',
                'data' => $beritaAcara->load(['logisticsTracking', 'inventoryReceiving', 'reportedBy', 'approvedBy', 'createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve Berita Acara: ' . $e->getMessage()
            ], 500);
        }
    }

    public function purchasingRequests(Request $request)
    {
        $query = PurchasingRequest::with(['logisticsTracking', 'requestedBy', 'approvedBy', 'createdBy', 'updatedBy']);

        if ($request->filled('request_number')) {
            $query->where('request_number', 'like', '%' . $request->request_number . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }


        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $purchasingRequests = $query->orderBy('created_at', 'desc')->paginateStd(25);
        $logisticsTrackings = LogisticsTracking::where('status', 'delivered')->orderBy('tracking_number')->get();

        return view('warehouse.inventory-logistics.purchasing-requests', compact('purchasingRequests', 'logisticsTrackings'));
    }

    public function createPurchasingRequest(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'logistics_tracking_id' => 'required|exists:logistics_tracking,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'reason' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'estimated_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $purchasingRequest = PurchasingRequest::createPurchasingRequest(
                $request->logistics_tracking_id,
                $request->warehouse_id,
                $request->reason,
                $request->priority,
                $request->estimated_cost ?? 0
            );

            if ($request->notes) {
                $purchasingRequest->update(['notes' => $request->notes]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchasing request created successfully.',
                'data' => $purchasingRequest->load(['logisticsTracking', 'warehouse', 'requestedBy', 'createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create purchasing request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approvePurchasingRequest(Request $request, PurchasingRequest $purchasingRequest)
    {
        $validator = \Validator::make($request->all(), [
            'approval_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if (!$purchasingRequest->canBeApproved()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchasing request cannot be approved in current status.'
                ], 422);
            }

            DB::beginTransaction();

            $purchasingRequest->approve(Auth::id(), $request->approval_notes);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchasing request approved successfully.',
                'data' => $purchasingRequest->load(['logisticsTracking', 'warehouse', 'requestedBy', 'approvedBy', 'createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve purchasing request: ' . $e->getMessage()
            ], 500);
        }
    }
}
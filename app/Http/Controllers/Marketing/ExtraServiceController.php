<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ExtraService;
use App\Models\Contract;
use App\Models\ContractRoom;
use App\Models\JobAdvice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExtraServiceController extends Controller
{
    /**
     * Display a listing of extra services
     */
    public function index(Request $request)
    {
        $query = ExtraService::with([
            'contract.customer',
            'contractRoom',
            'building',
            'room',
            'requestedBy',
            'approvedBy'
        ]);

        // Filters
        if ($request->filled('contract_id')) {
            $query->byContract($request->contract_id);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('with_invoice')) {
            $query->where('with_invoice', $request->with_invoice);
        }

        $services = $query->orderBy('created_at', 'desc')->paginate(15);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $services
            ]);
        }

        return view('marketing.extra-services.index', compact('services'));
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'contract_room_id' => 'required|exists:contract_rooms,id',
            'service_type' => 'required|in:cleaning,refill,maintenance,repair,other',
            'service_description' => 'required|string|max:1000',
            'service_reason' => 'nullable|string|max:1000',
            'with_invoice' => 'required|boolean',
            'service_fee' => 'required_if:with_invoice,true|nullable|numeric|min:0',
            'with_materials' => 'required|boolean',
            'materials_notes' => 'required_if:with_materials,true|nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            // Get contract room details
            $contractRoom = ContractRoom::with('room.building')->find($request->contract_room_id);

            $extraService = ExtraService::create([
                'service_number' => ExtraService::generateServiceNumber(),
                'contract_id' => $request->contract_id,
                'contract_room_id' => $request->contract_room_id,
                'building_id' => $contractRoom->room->building_id,
                'room_id' => $contractRoom->room_id,
                'service_type' => $request->service_type,
                'service_description' => $request->service_description,
                'service_reason' => $request->service_reason,
                'with_invoice' => $request->with_invoice,
                'service_fee' => $request->service_fee,
                'invoice_notes' => $request->invoice_notes,
                'with_materials' => $request->with_materials,
                'materials_notes' => $request->materials_notes,
                'status' => ExtraService::STATUS_DRAFT,
                'requested_by' => Auth::id(),
                'created_by' => Auth::id()
            ]);

            DB::commit();

            Log::info("Extra Service created: {$extraService->service_number}");

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Extra service created successfully',
                    'data' => $extraService->load(['contract', 'contractRoom', 'building', 'room'])
                ]);
            }

            return redirect()->route('marketing.extra-services.show', $extraService)
                ->with('success', 'Extra service created successfully');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Failed to create extra service: " . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create extra service: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed to create: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified extra service
     */
    public function show(ExtraService $extraService)
    {
        $extraService->load([
            'contract.customer',
            'contractRoom',
            'building',
            'room',
            'jobAdvice',
            'requestedBy',
            'approvedBy',
            'completedBy'
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $extraService
            ]);
        }

        return view('marketing.extra-services.show', compact('extraService'));
    }

    /**
     * Update the specified extra service
     */
    public function update(Request $request, ExtraService $extraService)
    {
        // Only allow update if draft or rejected
        if (!in_array($extraService->status, [ExtraService::STATUS_DRAFT, ExtraService::STATUS_REJECTED])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot update in current status'
            ], 403);
        }

        $request->validate([
            'service_type' => 'required|in:cleaning,refill,maintenance,repair,other',
            'service_description' => 'required|string|max:1000',
            'with_invoice' => 'required|boolean',
            'service_fee' => 'required_if:with_invoice,true|nullable|numeric|min:0',
            'with_materials' => 'required|boolean',
            'materials_notes' => 'required_if:with_materials,true|nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $extraService->update([
                'service_type' => $request->service_type,
                'service_description' => $request->service_description,
                'service_reason' => $request->service_reason,
                'with_invoice' => $request->with_invoice,
                'service_fee' => $request->service_fee,
                'with_materials' => $request->with_materials,
                'materials_notes' => $request->materials_notes,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Extra service updated',
                    'data' => $extraService->fresh()
                ]);
            }

            return back()->with('success', 'Extra service updated');

        } catch (\Exception $e) {
            DB::rollback();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    
    public function submitForApproval(ExtraService $extraService)
    {
        if ($extraService->status !== ExtraService::STATUS_DRAFT) {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only submit draft services'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $extraService->submitForApproval();

            DB::commit();

            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Extra service submitted for approval',
                    'data' => $extraService->fresh()
                ]);
            }

            return back()->with('success', 'Submitted for approval');

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit: ' . $e->getMessage()
            ], 500);
        }
    }

   
    public function approve(Request $request, ExtraService $extraService)
    {
        if ($extraService->status !== ExtraService::STATUS_PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only approve pending services'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $extraService->approve(Auth::id(), $request->approval_notes);

            $jobAdviceNumber = app(\App\Services\DocumentNumberService::class)->generate('job_advice', null, $extraService->building_id, $extraService->contract_id);

            $jobAdvice = JobAdvice::create([
                'job_advice_number' => $jobAdviceNumber,
                'contract_id' => $extraService->contract_id,
                'type' => 'service', // Extra service
                'reference_number' => $extraService->service_number,
                'expected_date' => now()->addDays(3),
                'status' => 'approved',
                'with_invoicing' => $extraService->with_invoice,
                'with_materials' => $extraService->with_materials,
                'notes' => "Auto-generated from Extra Service: {$extraService->service_number}. {$extraService->service_description}",
                'approved_by' => Auth::id(),
                'created_by' => Auth::id()
            ]);

            $extraService->update(['job_advice_id' => $jobAdvice->id]);

            DB::commit();

            Log::info("Extra Service approved and Job Advice created: JA#{$jobAdvice->job_advice_number}");

            return response()->json([
                'status' => 'success',
                'message' => 'Extra service approved and Job Advice created',
                'data' => [
                    'extra_service' => $extraService->fresh(),
                    'job_advice' => $jobAdvice
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Failed to approve extra service: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject extra service
     */
    public function reject(Request $request, ExtraService $extraService)
    {
        if ($extraService->status !== ExtraService::STATUS_PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only reject pending services'
            ], 403);
        }

        $request->validate([
            'approval_notes' => 'required|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $extraService->reject(Auth::id(), $request->approval_notes);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Extra service rejected',
                'data' => $extraService->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel extra service
     */
    public function cancel(ExtraService $extraService)
    {
        if (!in_array($extraService->status, [ExtraService::STATUS_DRAFT, ExtraService::STATUS_PENDING])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel in current status'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $extraService->cancel();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Extra service cancelled',
                'data' => $extraService->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete extra service
     */
    public function destroy(ExtraService $extraService)
    {
        if (!in_array($extraService->status, [ExtraService::STATUS_DRAFT, ExtraService::STATUS_CANCELLED])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete in current status'
            ], 403);
        }

        try {
            $extraService->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Extra service deleted'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete: ' . $e->getMessage()
            ], 500);
        }
    }
}


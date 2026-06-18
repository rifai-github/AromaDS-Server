<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\ContractRemoval;
use App\Models\Contract;
use App\Models\ContractRoom;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractRemovalController extends Controller
{
    /**
     * Display a listing of contract removals
     */
    public function index(Request $request)
    {
        $query = ContractRemoval::with([
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

        if ($request->filled('has_active_jobs')) {
            $query->where('has_active_jobs', $request->has_active_jobs);
        }

        $removals = $query->orderBy('created_at', 'desc')->paginateStd(25);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $removals
            ]);
        }

        return view('marketing.contract-removals.index', compact('removals'));
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'contract_room_id' => 'required|exists:contract_rooms,id',
            'removal_reason' => 'required|string|max:1000',
            'removal_notes' => 'nullable|string|max:2000',
            'removal_date' => 'nullable|date',
            'affect_room_rental' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            // Get contract room details
            $contractRoom = ContractRoom::with('room.building')->find($request->contract_room_id);

            if (!$contractRoom) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Contract room not found'
                ], 404);
            }

            // Create removal record
            $removal = ContractRemoval::create([
                'removal_number' => ContractRemoval::generateRemovalNumber(),
                'contract_id' => $request->contract_id,
                'contract_room_id' => $request->contract_room_id,
                'building_id' => $contractRoom->room->building_id,
                'room_id' => $contractRoom->room_id,
                'removal_reason' => $request->removal_reason,
                'removal_notes' => $request->removal_notes,
                'removal_date' => $request->removal_date,
                'affect_room_rental' => $request->affect_room_rental ?? true,
                'status' => ContractRemoval::STATUS_DRAFT,
                'requested_by' => Auth::id(),
                'created_by' => Auth::id()
            ]);

            
            $noActiveJobs = $removal->checkActiveJobs();

            DB::commit();

            Log::info("Contract Removal created: {$removal->removal_number}", [
                'has_active_jobs' => !$noActiveJobs
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Removal request created successfully',
                    'data' => $removal->load(['contract', 'contractRoom', 'building', 'room']),
                    'warning' => !$noActiveJobs ? 'Active jobs detected! Please review before approval.' : null
                ]);
            }

            return redirect()->route('marketing.contract-removals.show', $removal)
                ->with('success', 'Removal request created')
                ->with('warning', !$noActiveJobs ? 'Active jobs detected!' : null);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Failed to create removal: " . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified removal
     */
    public function show(ContractRemoval $contractRemoval)
    {
        $contractRemoval->load([
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
                'data' => $contractRemoval
            ]);
        }

        return view('marketing.contract-removals.show', compact('contractRemoval'));
    }

    /**
     * Update the specified removal
     */
    public function update(Request $request, ContractRemoval $contractRemoval)
    {
        // Only allow update if draft or rejected
        if (!in_array($contractRemoval->status, [ContractRemoval::STATUS_DRAFT, ContractRemoval::STATUS_REJECTED])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot update in current status'
            ], 403);
        }

        $request->validate([
            'removal_reason' => 'required|string|max:1000',
            'removal_notes' => 'nullable|string|max:2000',
            'removal_date' => 'nullable|date'
        ]);

        try {
            DB::beginTransaction();

            $contractRemoval->update([
                'removal_reason' => $request->removal_reason,
                'removal_notes' => $request->removal_notes,
                'removal_date' => $request->removal_date,
                'updated_by' => Auth::id()
            ]);

            // Re-check active jobs
            $contractRemoval->checkActiveJobs();

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Removal updated',
                    'data' => $contractRemoval->fresh()
                ]);
            }

            return back()->with('success', 'Removal updated');

        } catch (\Exception $e) {
            DB::rollback();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    
    public function submitForApproval(ContractRemoval $contractRemoval)
    {
        if ($contractRemoval->status !== ContractRemoval::STATUS_DRAFT) {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only submit draft removals'
            ], 403);
        }

        
        if ($contractRemoval->has_active_jobs) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot submit: Active jobs detected! Please complete or cancel active jobs first.',
                'active_jobs' => $contractRemoval->active_jobs_data
            ], 422);
        }

        try {
            DB::beginTransaction();

            $contractRemoval->submitForApproval();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Removal submitted for approval',
                'data' => $contractRemoval->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function approve(Request $request, ContractRemoval $contractRemoval)
    {
        if ($contractRemoval->status !== ContractRemoval::STATUS_PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only approve pending removals'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $contractRemoval->approve(Auth::id(), $request->approval_notes);

            
            $jobAdviceNumber = app(\App\Services\DocumentNumberService::class)->generate('job_advice', null, $contractRemoval->building_id, $contractRemoval->contract_id);

            $jobAdvice = JobAdvice::create([
                'job_advice_number' => $jobAdviceNumber,
                'contract_id' => $contractRemoval->contract_id,
                'type' => 'remove', // or 'removal'
                'reference_number' => $contractRemoval->removal_number,
                'expected_date' => $contractRemoval->removal_date ?? now()->addDays(7),
                'status' => 'approved',
                'with_invoicing' => false, // Removal typically no invoice
                'with_materials' => false,
                'notes' => "Auto-generated from Contract Removal: {$contractRemoval->removal_number}. {$contractRemoval->removal_reason}",
                'approved_by' => Auth::id(),
                'created_by' => Auth::id()
            ]);

            $contractRemoval->update(['job_advice_id' => $jobAdvice->id]);

            DB::commit();

            Log::info("Contract Removal approved and Job Advice created: JA#{$jobAdvice->job_advice_number}");

            return response()->json([
                'status' => 'success',
                'message' => 'Removal approved and Job Advice created',
                'data' => [
                    'removal' => $contractRemoval->fresh(),
                    'job_advice' => $jobAdvice
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Failed to approve removal: " . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject removal
     */
    public function reject(Request $request, ContractRemoval $contractRemoval)
    {
        if ($contractRemoval->status !== ContractRemoval::STATUS_PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only reject pending removals'
            ], 403);
        }

        $request->validate([
            'approval_notes' => 'required|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $contractRemoval->reject(Auth::id(), $request->approval_notes);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Removal rejected',
                'data' => $contractRemoval->fresh()
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
     * Cancel removal
     */
    public function cancel(ContractRemoval $contractRemoval)
    {
        if (!in_array($contractRemoval->status, [ContractRemoval::STATUS_DRAFT, ContractRemoval::STATUS_PENDING])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel in current status'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $contractRemoval->cancel();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Removal cancelled',
                'data' => $contractRemoval->fresh()
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
     * Delete removal
     */
    public function destroy(ContractRemoval $contractRemoval)
    {
        if (!in_array($contractRemoval->status, [ContractRemoval::STATUS_DRAFT, ContractRemoval::STATUS_CANCELLED])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete in current status'
            ], 403);
        }

        try {
            $contractRemoval->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Removal deleted'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete: ' . $e->getMessage()
            ], 500);
        }
    }
}


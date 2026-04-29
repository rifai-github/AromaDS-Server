<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\RentalChange;
use App\Models\Contract;
use App\Models\ContractRoom;
use App\Models\Building;
use App\Models\Room;
use App\Models\MasterRental;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RentalChangeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = RentalChange::with([
            'contract.customer',
            'contractRoom',
            'building',
            'room',
            'oldRental',
            'newRental',
            'requestedBy',
            'approvedBy',
            'completedBy'
        ]);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('change_number', 'like', "%{$search}%")
                    ->orWhere('change_reason', 'like', "%{$search}%")
                    ->orWhereHas('contract', function ($q2) use ($search) {
                        $q2->where('contract_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('contract.customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by contract
        if ($request->filled('contract_id')) {
            $query->where('contract_id', $request->contract_id);
        }

        // Filter by active jobs
        if ($request->filled('has_active_jobs')) {
            $query->where('has_active_jobs', $request->has_active_jobs);
        }

        $rentalChanges = $query->orderBy('created_at', 'desc')->paginate(15);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $rentalChanges
            ]);
        }

        return view('marketing.rental-changes.index', compact('rentalChanges'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $contracts = Contract::with('customer')->get();
        $rentals = MasterRental::all();
        $users = User::all();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'contracts' => $contracts,
                    'rentals' => $rentals,
                    'users' => $users
                ]
            ]);
        }

        return view('marketing.rental-changes.create', compact('contracts', 'rentals', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'contract_room_id' => 'required|exists:contract_rooms,id',
            'building_id' => 'required|exists:buildings,id',
            'room_id' => 'required|exists:rooms,id',
            'old_rental_id' => 'required|exists:master_rentals,id',
            'new_rental_id' => 'required|exists:master_rentals,id|different:old_rental_id',
            'change_reason' => 'nullable|string|max:2000',
            'change_notes' => 'nullable|string|max:2000',
            'effective_date' => 'nullable|date|after_or_equal:today',
            'affect_room_rental_config' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $rentalChange = RentalChange::create([
                'change_number' => RentalChange::generateChangeNumber(),
                'contract_id' => $request->contract_id,
                'contract_room_id' => $request->contract_room_id,
                'building_id' => $request->building_id,
                'room_id' => $request->room_id,
                'old_rental_id' => $request->old_rental_id,
                'new_rental_id' => $request->new_rental_id,
                'change_reason' => $request->change_reason,
                'change_notes' => $request->change_notes,
                'effective_date' => $request->effective_date ?? now()->addDays(7),
                'affect_room_rental_config' => $request->affect_room_rental_config ?? true,
                'status' => RentalChange::STATUS_DRAFT,
                'requested_by' => Auth::id(),
                'created_by' => Auth::id()
            ]);

            // Calculate price difference
            $rentalChange->calculatePriceDifference();


            $rentalChange->checkActiveJobs();

            DB::commit();

            Log::info("Rental Change created: {$rentalChange->change_number}", [
                'old_rental' => $request->old_rental_id,
                'new_rental' => $request->new_rental_id,
                'has_active_jobs' => $rentalChange->has_active_jobs
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Rental change request created successfully',
                'data' => $rentalChange->load([
                    'contract.customer',
                    'contractRoom',
                    'building',
                    'room',
                    'oldRental',
                    'newRental',
                    'requestedBy'
                ])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error creating rental change: " . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['status' => 'error', 'message' => 'Failed to create rental change: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RentalChange $rentalChange)
    {
        $rentalChange->load([
            'contract.customer',
            'contractRoom',
            'building',
            'room',
            'oldRental',
            'newRental',
            'jobAdvice',
            'requestedBy',
            'approvedBy',
            'completedBy'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $rentalChange
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RentalChange $rentalChange)
    {
        $rentalChange->load([
            'contract.customer',
            'contractRoom',
            'building',
            'room',
            'oldRental',
            'newRental',
            'requestedBy',
            'approvedBy',
            'completedBy'
        ]);

        $contracts = Contract::with('customer')->get();
        $rentals = MasterRental::all();
        $users = User::all();

        return response()->json([
            'status' => 'success',
            'data' => $rentalChange,
            'contracts' => $contracts,
            'rentals' => $rentals,
            'users' => $users
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RentalChange $rentalChange)
    {
        if (!$rentalChange->isDraft) {
            return response()->json(['status' => 'error', 'message' => 'Cannot update rental change in current status'], 403);
        }

        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'contract_room_id' => 'required|exists:contract_rooms,id',
            'building_id' => 'required|exists:buildings,id',
            'room_id' => 'required|exists:rooms,id',
            'old_rental_id' => 'required|exists:master_rentals,id',
            'new_rental_id' => 'required|exists:master_rentals,id|different:old_rental_id',
            'change_reason' => 'nullable|string|max:2000',
            'change_notes' => 'nullable|string|max:2000',
            'effective_date' => 'nullable|date|after_or_equal:today',
            'affect_room_rental_config' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $rentalChange->update([
                'contract_id' => $request->contract_id,
                'contract_room_id' => $request->contract_room_id,
                'building_id' => $request->building_id,
                'room_id' => $request->room_id,
                'old_rental_id' => $request->old_rental_id,
                'new_rental_id' => $request->new_rental_id,
                'change_reason' => $request->change_reason,
                'change_notes' => $request->change_notes,
                'effective_date' => $request->effective_date,
                'affect_room_rental_config' => $request->affect_room_rental_config ?? true,
                'updated_by' => Auth::id()
            ]);

            // Recalculate price difference
            $rentalChange->calculatePriceDifference();

            // Recheck active jobs
            $rentalChange->checkActiveJobs();

            DB::commit();

            Log::info("Rental Change updated: {$rentalChange->change_number}");

            return response()->json([
                'status' => 'success',
                'message' => 'Rental change request updated successfully',
                'data' => $rentalChange->load([
                    'contract.customer',
                    'contractRoom',
                    'building',
                    'room',
                    'oldRental',
                    'newRental',
                    'requestedBy'
                ])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error updating rental change: " . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['status' => 'error', 'message' => 'Failed to update rental change: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RentalChange $rentalChange)
    {
        if (!$rentalChange->isDraft && !$rentalChange->isCancelled && !$rentalChange->isRejected) {
            return response()->json(['status' => 'error', 'message' => 'Cannot delete rental change in current status'], 403);
        }

        try {
            $rentalChange->delete();
            Log::info("Rental Change deleted: {$rentalChange->change_number}");
            return response()->json(['status' => 'success', 'message' => 'Rental change deleted successfully']);
        } catch (\Exception $e) {
            Log::error("Error deleting rental change: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to delete rental change: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Submit for approval
     */
    public function submitForApproval(RentalChange $rentalChange)
    {
        if (!$rentalChange->isDraft) {
            return response()->json(['status' => 'error', 'message' => 'Rental change must be in draft status to submit for approval'], 403);
        }

        try {
            $rentalChange->submitForApproval();
            Log::info("Rental Change submitted for approval: {$rentalChange->change_number}");
            return response()->json([
                'status' => 'success',
                'message' => 'Rental change submitted for approval',
                'has_active_jobs' => $rentalChange->has_active_jobs,
                'active_jobs' => $rentalChange->active_jobs_list
            ]);
        } catch (\Exception $e) {
            Log::error("Error submitting rental change for approval: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to submit rental change for approval: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Approve
     */
    public function approve(Request $request, RentalChange $rentalChange)
    {
        if (!$rentalChange->isPending) {
            return response()->json(['status' => 'error', 'message' => 'Rental change must be in pending status to approve'], 403);
        }

        $validator = Validator::make($request->all(), [
            'approval_notes' => 'nullable|string|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $rentalChange->approve(Auth::id(), $request->approval_notes);
            Log::info("Rental Change approved: {$rentalChange->change_number}");
            return response()->json(['status' => 'success', 'message' => 'Rental change approved successfully']);
        } catch (\Exception $e) {
            Log::error("Error approving rental change: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to approve rental change: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reject
     */
    public function reject(Request $request, RentalChange $rentalChange)
    {
        if (!$rentalChange->isPending) {
            return response()->json(['status' => 'error', 'message' => 'Rental change must be in pending status to reject'], 403);
        }

        $validator = Validator::make($request->all(), [
            'approval_notes' => 'required|string|max:2000'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            $rentalChange->reject(Auth::id(), $request->approval_notes);
            Log::info("Rental Change rejected: {$rentalChange->change_number}");
            return response()->json(['status' => 'success', 'message' => 'Rental change rejected successfully']);
        } catch (\Exception $e) {
            Log::error("Error rejecting rental change: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to reject rental change: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Schedule
     */
    public function schedule(RentalChange $rentalChange)
    {
        if (!$rentalChange->isApproved) {
            return response()->json(['status' => 'error', 'message' => 'Rental change must be approved to schedule'], 403);
        }

        try {
            $rentalChange->schedule();
            Log::info("Rental Change scheduled: {$rentalChange->change_number}");
            return response()->json(['status' => 'success', 'message' => 'Rental change scheduled successfully']);
        } catch (\Exception $e) {
            Log::error("Error scheduling rental change: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to schedule rental change: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Complete
     */
    public function complete(RentalChange $rentalChange)
    {
        if (!$rentalChange->isApproved && !$rentalChange->isScheduled) {
            return response()->json(['status' => 'error', 'message' => 'Rental change must be approved or scheduled to complete'], 403);
        }

        try {
            $rentalChange->complete(Auth::id());
            Log::info("Rental Change completed: {$rentalChange->change_number}");
            return response()->json(['status' => 'success', 'message' => 'Rental change marked as completed successfully']);
        } catch (\Exception $e) {
            Log::error("Error marking rental change as completed: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to mark rental change as completed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancel
     */
    public function cancel(RentalChange $rentalChange)
    {
        if ($rentalChange->isCompleted || $rentalChange->isCancelled || $rentalChange->isRejected) {
            return response()->json(['status' => 'error', 'message' => 'Cannot cancel rental change in current status'], 403);
        }

        try {
            $rentalChange->cancel();
            Log::info("Rental Change cancelled: {$rentalChange->change_number}");
            return response()->json(['status' => 'success', 'message' => 'Rental change cancelled successfully']);
        } catch (\Exception $e) {
            Log::error("Error cancelling rental change: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to cancel rental change: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get contract rooms for a specific contract
     */
    public function getContractRooms(Request $request, $contractId)
    {
        try {
            $contractRooms = ContractRoom::where('contract_id', $contractId)
                ->with(['building', 'room', 'rental'])
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $contractRooms
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching contract rooms: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch contract rooms'], 500);
        }
    }
}


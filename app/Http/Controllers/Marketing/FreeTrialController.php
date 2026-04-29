<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\FreeTrial;
use App\Models\Quotation;
use App\Models\MasterRoom;
use App\Models\MasterRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FreeTrialController extends Controller
{
    public function index(Request $request)
    {
        $query = FreeTrial::with([
            'quotation.prospect',
            'room.building',
            'masterRental',
            'requestedBy',
            'approvedBy'
        ]);

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('quotation_id')) {
            $query->where('quotation_id', $request->quotation_id);
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('trial_start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('trial_start_date', '<=', $request->date_to);
        }

        $freeTrials = $query->orderBy('requested_at', 'desc')->paginate(15);

        return view('marketing.free-trials.index', compact('freeTrials'));
    }

    public function show($id)
    {
        $freeTrial = FreeTrial::with([
            'quotation.prospect',
            'room.building.customer',
            'masterRental',
            'requestedBy',
            'approvedBy'
        ])->findOrFail($id);

        return view('marketing.free-trials.show', compact('freeTrial'));
    }

    public function create($quotationId)
    {
        $quotation = Quotation::with(['prospect', 'quotationRooms.room.building'])->findOrFail($quotationId);
        $rooms = $quotation->quotationRooms->pluck('room');
        $rentals = MasterRental::active()->get();

        return view('marketing.free-trials.create', compact('quotation', 'rooms', 'rentals'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'quotation_id' => 'required|exists:quotations,id',
            'room_id' => 'required|exists:master_rooms,id',
            'master_rental_id' => 'required|exists:master_rentals,id',
            'trial_start_date' => 'required|date|after_or_equal:today',
            'trial_end_date' => 'required|date|after:trial_start_date',
            'trial_notes' => 'nullable|string|max:1000'
        ]);

        DB::beginTransaction();
        try {
            $freeTrial = FreeTrial::create([
                'quotation_id' => $request->quotation_id,
                'room_id' => $request->room_id,
                'master_rental_id' => $request->master_rental_id,
                'trial_number' => $this->generateTrialNumber(),
                'trial_start_date' => $request->trial_start_date,
                'trial_end_date' => $request->trial_end_date,
                'trial_notes' => $request->trial_notes,
                'status' => 'pending',
                'requested_by' => Auth::id(),
                'requested_at' => now(),
                'created_by' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Free trial request created successfully',
                'data' => $freeTrial
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create free trial request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'approval_notes' => 'nullable|string|max:1000'
        ]);

        $freeTrial = FreeTrial::findOrFail($id);
        
        if ($freeTrial->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Free trial has already been processed'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $freeTrial->approve(Auth::id(), $request->approval_notes);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Free trial approved successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve free trial: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        $freeTrial = FreeTrial::findOrFail($id);
        
        if ($freeTrial->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Free trial has already been processed'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $freeTrial->update([
                'status' => 'cancelled',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $request->rejection_reason
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Free trial rejected successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject free trial: ' . $e->getMessage()
            ], 500);
        }
    }

    public function start($id)
    {
        $freeTrial = FreeTrial::findOrFail($id);
        
        if ($freeTrial->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Free trial must be approved before starting'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $freeTrial->start();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Free trial started successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start free trial: ' . $e->getMessage()
            ], 500);
        }
    }

    public function complete(Request $request, $id)
    {
        $request->validate([
            'completion_notes' => 'nullable|string|max:1000'
        ]);

        $freeTrial = FreeTrial::findOrFail($id);
        
        if ($freeTrial->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Free trial must be active to complete'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $freeTrial->complete($request->completion_notes);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Free trial completed successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete free trial: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancel($id)
    {
        $freeTrial = FreeTrial::findOrFail($id);
        
        if (!in_array($freeTrial->status, ['pending', 'approved', 'active'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Free trial cannot be cancelled in current status'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $freeTrial->cancel();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Free trial cancelled successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel free trial: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getQuotationFreeTrials($quotationId)
    {
        $quotation = Quotation::findOrFail($quotationId);
        $freeTrials = $quotation->freeTrials()->with(['room.building', 'masterRental', 'requestedBy', 'approvedBy'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $freeTrials
        ]);
    }

    private function generateTrialNumber()
    {
        $count = FreeTrial::count() + 1;
        return 'TRIAL-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\QuotationApproval;
use App\Models\QuotationRental;
use App\Models\MasterPriceSlab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $query = QuotationApproval::with([
            'quotation.prospect',
            'quotationRevision',
            'requestedBy',
            'approvedBy'
        ]);

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('approval_type')) {
            $query->where('approval_type', $request->approval_type);
        }

        if ($request->filled('requested_by')) {
            $query->where('requested_by', $request->requested_by);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('requested_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('requested_at', '<=', $request->date_to);
        }

        $approvals = $query->orderBy('requested_at', 'desc')->paginate(15);

        return view('marketing.approvals.index', compact('approvals'));
    }

    public function show($id)
    {
        $approval = QuotationApproval::with([
            'quotation.prospect',
            'quotation.quotationRentals.masterRental',
            'quotationRevision',
            'requestedBy',
            'approvedBy'
        ])->findOrFail($id);

        return view('marketing.approvals.show', compact('approval'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'approval_notes' => 'nullable|string|max:1000'
        ]);

        $approval = QuotationApproval::findOrFail($id);
        
        if ($approval->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Approval has already been processed'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $approval->approve(Auth::id(), $request->approval_notes);

            // If this is a quotation approval, update quotation status
            if ($approval->approval_type === 'general') {
                $quotation = $approval->quotation;
                $quotation->approveQuotation(Auth::id(), $request->approval_notes);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Approval processed successfully'
            ]);
        } catch (ValidationException $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => $e->validator->errors()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process approval: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        $approval = QuotationApproval::findOrFail($id);
        
        if ($approval->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Approval has already been processed'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $approval->reject(Auth::id(), $request->rejection_reason);

            // If this is a quotation approval, update quotation status
            if ($approval->approval_type === 'general') {
                $quotation = $approval->quotation;
                $quotation->rejectQuotation(Auth::id(), $request->rejection_reason);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Approval rejected successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject approval: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getQuotationApprovalSummary($quotationId)
    {
        $quotation = Quotation::with(['quotationRentals.masterRental'])->findOrFail($quotationId);
        
        $summary = $quotation->getApprovalSummary();
        $priceSlabSummary = $quotation->getPriceSlabSummary();

        return response()->json([
            'status' => 'success',
            'data' => [
                'approval_summary' => $summary,
                'price_slab_summary' => $priceSlabSummary
            ]
        ]);
    }

    public function createApproval(Request $request, $quotationId)
    {
        $request->validate([
            'approval_type' => 'required|in:bottom_price,term_payment,free_trial,general',
            'approval_data' => 'nullable|array'
        ]);

        $quotation = Quotation::findOrFail($quotationId);

        DB::beginTransaction();
        try {
            $approval = $quotation->createApproval(
                $request->approval_type,
                Auth::id(),
                $request->approval_data ?? []
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Approval request created successfully',
                'data' => $approval
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create approval request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPriceSlabInfo($rentalId, $quantity)
    {
        $priceSlab = MasterPriceSlab::getApplicableSlab($rentalId, $quantity);
        
        if (!$priceSlab) {
            return response()->json([
                'status' => 'error',
                'message' => 'No applicable price slab found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'slab_name' => $priceSlab->slab_name,
                'discount_percentage' => $priceSlab->discount_percentage,
                'quantity_range' => $priceSlab->quantity_range,
                'is_applicable' => $priceSlab->isApplicableForQuantity($quantity)
            ]
        ]);
    }

    public function validateQuotationApproval($quotationId)
    {
        $quotation = Quotation::with(['quotationRentals.masterRental'])->findOrFail($quotationId);
        
        // Check all rentals for bottom price validation
        foreach ($quotation->quotationRentals as $rental) {
            $rental->checkBottomPrice();
        }

        $summary = $quotation->getApprovalSummary();

        return response()->json([
            'status' => 'success',
            'data' => $summary
        ]);
    }
}

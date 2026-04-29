<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\CommissionPayment;
use App\Models\Finance\CommissionCalculation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CommissionPaymentController extends Controller
{
    /**
     * Display a listing of commission payments
     */
    public function index(Request $request)
    {
        $payments = CommissionPayment::with(['user', 'commissionCalculation', 'processedBy', 'createdBy', 'updatedBy'])
            ->filter($request->all())
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        $users = User::all();

        return view('finance.commission-payments.index', compact('payments', 'users'))->with('commissionPayments', $payments);
    }

    /**
     * Show the form for creating a new commission payment
     */
    public function create()
    {
        $users = User::all();
        $calculations = CommissionCalculation::where('status', 'approved')->get();
        
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'users' => $users,
                'calculations' => $calculations
            ]);
        }
        
        return view('finance.commission-payments.create', compact('users', 'calculations'));
    }

    /**
     * Store a newly created commission payment
     */
    public function store(Request $request)
    {
        $request->validate([
            'commission_calculation_id' => 'required|exists:commission_calculations,id',
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:bank_transfer,cash,check,other',
            'payment_reference' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
            'payment_notes' => 'nullable|string|max:1000',
            'bank_account' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255'
        ]);

        try {
            CommissionPayment::create([
                'commission_calculation_id' => $request->commission_calculation_id,
                'user_id' => $request->user_id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'payment_date' => $request->payment_date,
                'status' => 'pending',
                'payment_notes' => $request->payment_notes,
                'bank_account' => $request->bank_account,
                'bank_name' => $request->bank_name,
                'created_by' => Auth::id()
            ]);

            return redirect()->route('commission-payments.index')
                ->with('success', 'Commission payment created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create commission payment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified commission payment
     */
    public function show(CommissionPayment $commissionPayment)
    {
        $commissionPayment->load(['user', 'commissionCalculation', 'processedBy', 'createdBy']);
        
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'payment' => $commissionPayment
            ]);
        }
        
        return view('finance.commission-payments.show', compact('commissionPayment'));
    }

    /**
     * Show the form for editing the specified commission payment
     */
    public function edit(CommissionPayment $commissionPayment)
    {
        $users = User::all();
        $calculations = CommissionCalculation::where('status', 'approved')->get();
        
        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'payment' => $commissionPayment,
                'users' => $users,
                'calculations' => $calculations
            ]);
        }
        
        return view('finance.commission-payments.edit', compact('commissionPayment', 'users', 'calculations'));
    }

    /**
     * Update the specified commission payment
     */
    public function update(Request $request, CommissionPayment $commissionPayment)
    {
        $request->validate([
            'commission_calculation_id' => 'required|exists:commission_calculations,id',
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:bank_transfer,cash,check,other',
            'payment_reference' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
            'payment_notes' => 'nullable|string|max:1000',
            'bank_account' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255'
        ]);

        try {
            $commissionPayment->update([
                'commission_calculation_id' => $request->commission_calculation_id,
                'user_id' => $request->user_id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'payment_date' => $request->payment_date,
                'payment_notes' => $request->payment_notes,
                'bank_account' => $request->bank_account,
                'bank_name' => $request->bank_name,
                'updated_by' => Auth::id()
            ]);

            return redirect()->route('commission-payments.index')
                ->with('success', 'Commission payment updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update commission payment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified commission payment
     */
    public function destroy(CommissionPayment $commissionPayment)
    {
        try {
            $commissionPayment->delete();
            return redirect()->route('commission-payments.index')
                ->with('success', 'Commission payment deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete commission payment: ' . $e->getMessage());
        }
    }

    /**
     * Mark payment as processing
     */
    public function markAsProcessing(CommissionPayment $commissionPayment)
    {
        try {
            $commissionPayment->markAsProcessing(Auth::id());
            return redirect()->back()
                ->with('success', 'Payment marked as processing successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to mark payment as processing: ' . $e->getMessage());
        }
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(CommissionPayment $commissionPayment)
    {
        try {
            $commissionPayment->markAsCompleted(Auth::id());
            return redirect()->back()
                ->with('success', 'Payment marked as completed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to mark payment as completed: ' . $e->getMessage());
        }
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(Request $request, CommissionPayment $commissionPayment)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            $commissionPayment->markAsFailed($request->reason);
            return redirect()->back()
                ->with('success', 'Payment marked as failed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to mark payment as failed: ' . $e->getMessage());
        }
    }

    /**
     * Cancel payment
     */
    public function cancel(Request $request, CommissionPayment $commissionPayment)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            $commissionPayment->cancel($request->reason);
            return redirect()->back()
                ->with('success', 'Payment cancelled successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to cancel payment: ' . $e->getMessage());
        }
    }
}

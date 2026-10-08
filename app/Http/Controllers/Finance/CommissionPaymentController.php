<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\CommissionPayment;
use App\Models\Finance\CommissionCalculation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommissionPaymentController extends Controller
{
    /**
     * Payment yang masih "memegang" kalkulasinya. Failed/cancelled melepasnya lagi supaya
     * komisi yang gagal ditransfer bisa dibayar ulang.
     */
    private const ACTIVE_PAYMENT_STATUSES = ['pending', 'processing', 'completed'];

    /**
     * Satu kalkulasi komisi hanya boleh punya satu payment aktif. Dulu kalkulasi yang sudah
     * dibayar tetap "approved" dan tetap muncul di dropdown, jadi bisa dibayar berkali-kali.
     */
    private function calculationAlreadyPaidBy(int $calculationId, ?int $exceptPaymentId = null): ?CommissionPayment
    {
        return CommissionPayment::where('commission_calculation_id', $calculationId)
            ->whereIn('status', self::ACTIVE_PAYMENT_STATUSES)
            ->when($exceptPaymentId, fn ($query) => $query->where('id', '!=', $exceptPaymentId))
            ->first();
    }

    /**
     * Display a listing of commission payments
     */
    public function index(Request $request)
    {
        $payments = CommissionPayment::with(['user', 'commissionCalculation', 'processedBy', 'createdBy', 'updatedBy'])
            ->filter($request->all())
            // Form filter mengirim parameter datar (user_id, status, ...), bukan filter[kolom] yang
            // dibaca trait filter, jadi kondisi ini harus diterapkan sendiri.
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->payment_method))
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('payment_date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('payment_date', '<=', $request->end_date))
            ->orderBy('payment_date', 'desc')
            ->paginateStd(25);

        $users = User::all();

        return view('finance.commission-payments.index', compact('payments', 'users'))->with('commissionPayments', $payments);
    }

    /**
     * Show the form for creating a new commission payment
     */
    public function create()
    {
        $users = User::all();
        $calculations = CommissionCalculation::with(['user', 'achievementPeriod'])
            ->where('status', 'approved')
            ->whereDoesntHave('commissionPayments', fn ($query) => $query->whereIn('status', self::ACTIVE_PAYMENT_STATUSES))
            ->get();
        
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

        $calculation = CommissionCalculation::findOrFail($request->commission_calculation_id);
        $blockReason = $calculation->status !== 'approved'
            ? "Komisi #{$calculation->id} berstatus {$calculation->status}, hanya komisi Approved yang bisa dibayar."
            : (($existing = $this->calculationAlreadyPaidBy($calculation->id))
                ? "Komisi #{$calculation->id} sudah punya pembayaran #{$existing->id} ({$existing->status})."
                : null);

        if ($blockReason) {
            return redirect()->back()->with('error', $blockReason)->withInput();
        }

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

            return redirect()->route('finance.commission-payments.index')
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
        // Keep the payment's own calculation selectable even after it has moved past "approved".
        $calculations = CommissionCalculation::with(['user', 'achievementPeriod'])
            ->where(function ($query) use ($commissionPayment) {
                $query->where(function ($approved) use ($commissionPayment) {
                    $approved->where('status', 'approved')
                        ->whereDoesntHave('commissionPayments', fn ($payments) => $payments
                            ->whereIn('status', self::ACTIVE_PAYMENT_STATUSES)
                            ->where('id', '!=', $commissionPayment->id));
                })
                    ->orWhere('id', $commissionPayment->commission_calculation_id);
            })
            ->get();
        
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

        if ((int) $request->commission_calculation_id !== (int) $commissionPayment->commission_calculation_id) {
            $calculation = CommissionCalculation::findOrFail($request->commission_calculation_id);
            $existing = $this->calculationAlreadyPaidBy($calculation->id, $commissionPayment->id);
            $blockReason = $commissionPayment->status === 'completed'
                ? 'Pembayaran yang sudah Completed tidak bisa dipindah ke komisi lain.'
                : ($calculation->status !== 'approved'
                    ? "Komisi #{$calculation->id} berstatus {$calculation->status}, hanya komisi Approved yang bisa dibayar."
                    : ($existing ? "Komisi #{$calculation->id} sudah punya pembayaran #{$existing->id} ({$existing->status})." : null));

            if ($blockReason) {
                return redirect()->back()->with('error', $blockReason)->withInput();
            }
        }

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

            return redirect()->route('finance.commission-payments.index')
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
            return redirect()->route('finance.commission-payments.index')
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
        if (! in_array($commissionPayment->status, ['pending', 'processing'], true)) {
            return redirect()->back()
                ->with('error', "Pembayaran berstatus {$commissionPayment->status} tidak bisa ditandai Completed.");
        }

        try {
            DB::transaction(function () use ($commissionPayment) {
                $commissionPayment->markAsCompleted(Auth::id());

                // Komisinya sudah benar-benar dibayarkan ke marketing: tandai Paid supaya tidak
                // muncul lagi sebagai komisi yang menunggu pembayaran.
                $calculation = $commissionPayment->commissionCalculation;
                if ($calculation && $calculation->status === 'approved') {
                    $calculation->markAsPaid($commissionPayment->payment_date ?? now());
                }
            });

            return redirect()->back()
                ->with('success', 'Payment marked as completed successfully. Komisi ditandai Paid.');
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

        // Payment yang sudah Completed berarti uangnya sudah keluar dan komisinya sudah Paid;
        // membatalkannya di sini akan membuat data keduanya tidak sejalan.
        if (! in_array($commissionPayment->status, ['pending', 'processing'], true)) {
            return redirect()->back()
                ->with('error', "Pembayaran berstatus {$commissionPayment->status} tidak bisa dibatalkan.");
        }

        try {
            $commissionPayment->cancel($request->reason);
            return redirect()->back()
                ->with('success', 'Pembayaran dibatalkan. Komisinya bisa dibayar ulang atau ditransfer.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to cancel payment: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Finance\CommissionTransfer;
use App\Models\Contract;
use App\Models\User;
use App\Models\Finance\CommissionCalculation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommissionTransferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transfers = CommissionTransfer::with(['fromUser', 'toUser', 'contract', 'approvedBy', 'commissionCalculation', 'createdBy', 'updatedBy'])
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $transfers
            ]);
        }

        return view('finance.commission-transfers.index', compact('transfers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::commissionEligible()->get();
        
        $contracts = collect(); // Start with empty collection, will be populated via AJAX based on from_user_id

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'users' => $users,
                'contracts' => $contracts
            ]);
        }

        return view('finance.commission-transfers.create', compact('users', 'contracts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'from_user_id' => 'required|exists:users,id',
            'to_user_id' => 'required|exists:users,id|different:from_user_id',
            'commission_calculation_id' => 'required|exists:commission_calculations,id',
            'commission_amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:1000'
        ]);

        try {
            $transfer = CommissionTransfer::create([
                'contract_id' => $request->contract_id,
                'from_user_id' => $request->from_user_id,
                'to_user_id' => $request->to_user_id,
                'commission_calculation_id' => $request->commission_calculation_id,
                'commission_amount' => $request->commission_amount,
                'reason' => $request->reason,
                'status' => 'pending',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Commission transfer request created successfully',
                    'data' => $transfer
                ], 201);
            }

            return redirect()->route('finance.commission-transfers.index')
                ->with('success', 'Commission transfer request created successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create commission transfer: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create commission transfer: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CommissionTransfer $commissionTransfer)
    {
        $commissionTransfer->load([
            'fromUser', 
            'toUser', 
            'contract.customer', 
            'approvedBy', 
            'commissionCalculation',
            'createdBy',
            'updatedBy'
        ]);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $commissionTransfer
            ]);
        }

        return view('finance.commission-transfers.show', compact('commissionTransfer'));
    }

    /**
     * Approve commission transfer
     */
    public function approve(Request $request, CommissionTransfer $commissionTransfer)
    {
        $request->validate([
            'approval_notes' => 'nullable|string|max:1000'
        ]);

        $blockReason = $this->transferBlockReason($commissionTransfer);
        if ($blockReason) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json(['status' => 'error', 'message' => $blockReason], 422);
            }

            return redirect()->back()->with('error', $blockReason);
        }

        try {
            DB::transaction(function () use ($commissionTransfer, $request) {
                $commissionTransfer->approve(Auth::id(), $request->approval_notes);
                $this->applyTransferToCalculation($commissionTransfer);
            });

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Commission transfer approved successfully'
                ]);
            }

            return redirect()->back()
                ->with('success', 'Commission transfer approved successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to approve commission transfer: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to approve commission transfer: ' . $e->getMessage());
        }
    }

    /**
     * Approve dulu hanya mengganti status transfer; komisi yang sudah terhitung tetap atas
     * nama marketing asal (QA 8 Okt, SMG-AG/26-01/0028). Transfer baru berlaku hanya untuk
     * komisi kontrak yang belum dihitung. Sekarang approve ikut memindahkan komisinya.
     */
    private function transferBlockReason(CommissionTransfer $transfer): ?string
    {
        if ($transfer->status !== 'pending') {
            return "Transfer ini sudah berstatus {$transfer->status}.";
        }

        $calculation = $transfer->commissionCalculation;
        if (! $calculation) {
            return "Komisi #{$transfer->commission_calculation_id} sudah dihapus dari Commission System, jadi transfer ini tidak bisa di-approve. Silakan Reject transfer ini.";
        }

        if (! in_array($calculation->status, ['calculated', 'approved'], true)) {
            return "Komisi #{$calculation->id} berstatus {$calculation->status}; hanya komisi Calculated/Approved yang bisa ditransfer.";
        }

        if ((int) $calculation->user_id !== (int) $transfer->from_user_id) {
            return "Komisi #{$calculation->id} bukan milik {$transfer->fromUser?->name}.";
        }

        $activePayment = \App\Models\Finance\CommissionPayment::where('commission_calculation_id', $calculation->id)
            ->whereIn('status', ['pending', 'processing', 'completed'])
            ->first();
        if ($activePayment) {
            $hint = $activePayment->status === 'completed'
                ? 'Komisi yang sudah dibayar tidak bisa ditransfer.'
                : "Batalkan dulu di menu Commission Payment (ID {$activePayment->id}, tombol ✕).";

            return "Komisi #{$calculation->id} sudah punya pembayaran ID {$activePayment->id} berstatus {$activePayment->status}. {$hint}";
        }

        $amount = round((float) $transfer->commission_amount, 2);
        $available = round((float) $calculation->final_amount, 2);
        if ($amount <= 0 || $amount > $available) {
            return 'Nominal transfer Rp '.number_format($amount, 0, ',', '.')
                .' harus lebih dari 0 dan tidak melebihi komisi Rp '.number_format($available, 0, ',', '.').'.';
        }

        return null;
    }

    /**
     * Nominal penuh: komisi dipindah ke penerima. Sebagian: komisi asal dikurangi dan
     * dibuat komisi baru untuk penerima dengan status, periode, dan tanggal cash receipt
     * yang sama. Target/achievement tetap milik marketing asal (dia yang menjual).
     */
    private function applyTransferToCalculation(CommissionTransfer $transfer): void
    {
        $calculation = CommissionCalculation::whereKey($transfer->commission_calculation_id)->lockForUpdate()->firstOrFail();
        $amount = round((float) $transfer->commission_amount, 2);
        $note = "Transfer #{$transfer->id} dari {$transfer->fromUser?->name} ke {$transfer->toUser?->name}";

        if ($amount >= round((float) $calculation->final_amount, 2)) {
            $calculation->update([
                'user_id' => $transfer->to_user_id,
                'commission_transfer_id' => $transfer->id,
                'calculation_notes' => trim(($calculation->calculation_notes ?? '')."\n{$note} (penuh)"),
                'updated_by' => Auth::id(),
            ]);

            return;
        }

        $split = $calculation->replicate();
        $split->fill([
            'user_id' => $transfer->to_user_id,
            'commission_transfer_id' => $transfer->id,
            'commission_amount' => $amount,
            'bonus_amount' => 0,
            'penalty_amount' => 0,
            'final_amount' => $amount,
            'calculation_notes' => "{$note} (sebagian dari komisi #{$calculation->id})",
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
        $split->save();

        $calculation->update([
            'commission_amount' => round((float) $calculation->commission_amount - $amount, 2),
            'final_amount' => round((float) $calculation->final_amount - $amount, 2),
            'calculation_notes' => trim(($calculation->calculation_notes ?? '')."\n{$note}: dikurangi Rp ".number_format($amount, 0, ',', '.')),
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * Reject commission transfer
     */
    public function reject(Request $request, CommissionTransfer $commissionTransfer)
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000'
        ]);

        try {
            $commissionTransfer->reject($request->reason);

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Commission transfer rejected successfully'
                ]);
            }

            return redirect()->back()
                ->with('success', 'Commission transfer rejected successfully.');
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to reject commission transfer: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to reject commission transfer: ' . $e->getMessage());
        }
    }

    /**
     * Get commission calculations by contract ID (AJAX)
     */
    public function getCalculationsByContract(Request $request, $contractId)
    {
        try {
            $userId = $request->get('user_id');
            // Komisi yang sudah punya pembayaran aktif tidak bisa ditransfer (approve akan
            // menolaknya), jadi tidak ditawarkan sejak awal.
            $query = CommissionCalculation::where('contract_id', $contractId)
                ->whereIn('status', ['calculated', 'approved'])
                ->whereDoesntHave('commissionPayments', fn ($payments) => $payments->whereIn('status', ['pending', 'processing', 'completed']));
            
            if ($userId) {
                $query->where('user_id', $userId);
            }

            $calculations = $query->orderBy('calculation_date', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'data' => $calculations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contracts by marketing user (AJAX)
     */
    public function getContractsByUser(Request $request, $userId)
    {
        try {
            $contracts = Contract::where('marketing_id', $userId)
                ->where('contract_status', 'active')
                ->with('customer')
                ->orderBy('contract_number', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $contracts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

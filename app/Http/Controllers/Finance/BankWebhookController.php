<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankReceipt;
use App\Models\Invoice;
use App\Models\VirtualAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BankWebhookController extends Controller
{
    /**
     * Handle bank webhook notification for Virtual Account payment (Berdasarkan BRD)
     */
    public function handleVirtualAccountPayment(Request $request)
    {
        try {
            // Validate webhook data
            $validator = Validator::make($request->all(), [
                'virtual_account_number' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'payment_date' => 'required|date',
                'transaction_id' => 'required|string',
                'bank_reference' => 'nullable|string',
                'customer_name' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid webhook data',
                    'errors' => $validator->errors()
                ], 400);
            }

            DB::beginTransaction();

            // Find virtual account
            $virtualAccount = VirtualAccount::where('va_number', $request->virtual_account_number)->first();
            
            if (!$virtualAccount) {
                Log::warning("Virtual Account not found: {$request->virtual_account_number}");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Virtual Account not found'
                ], 404);
            }

            // Find invoice by virtual account
            $invoice = Invoice::where('virtual_account_number', $request->virtual_account_number)
                ->where('invoice_status', '!=', 'paid')
                ->first();

            if (!$invoice) {
                Log::warning("Invoice not found for VA: {$request->virtual_account_number}");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invoice not found'
                ], 404);
            }

            // Validate payment amount
            if ($request->amount != $invoice->total_amount) {
                Log::warning("Payment amount mismatch for VA {$request->virtual_account_number}: Expected {$invoice->total_amount}, Received {$request->amount}");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment amount mismatch'
                ], 400);
            }

            // Create bank receipt record
            $bankReceipt = BankReceipt::create([
                'receipt_number' => 'BR-' . date('Ymd') . '-' . str_pad(BankReceipt::count() + 1, 4, '0', STR_PAD_LEFT),
                'receipt_date' => now(),
                'customer_id' => $invoice->customer_id,
                'invoice_reference' => $invoice->invoice_number,
                'bank_id' => $virtualAccount->bank_id,
                'account_number' => $request->virtual_account_number,
                'account_holder_name' => $request->customer_name ?? $invoice->customer->name,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => 'transfer',
                'status' => 'verified', // Auto-verify from bank webhook
                'notes' => "Auto-generated from bank webhook. Transaction ID: {$request->transaction_id}",
                'created_by' => 1, // System user
                'updated_by' => 1,
            ]);

            // Update invoice status to paid
            $invoice->update([
                'invoice_status' => 'paid',
                'total_paid' => $request->amount,
                'outstanding' => 0,
                'payment_date' => $request->payment_date,
                'updated_by' => 1, // System user
            ]);

            // Create invoice activity log
            $invoice->logActivity(
                'paid',
                "Invoice paid via Virtual Account. Bank Receipt: {$bankReceipt->receipt_number}",
                1
            );

            // AUTO-CALCULATE COMMISSION (Berdasarkan BRD)
            try {
                $commissionService = new \App\Services\Finance\CommissionCalculationService();
                $cashReceiptDate = $request->payment_date ?? now()->toDateString();
                $result = $commissionService->calculateCommissionOnCashReceipt($invoice, $cashReceiptDate);
                
                if ($result['success']) {
                    $amount = isset($result['commission']) ? ($result['commission']->final_amount ?? 'N/A') : 'N/A';
                    Log::info("Commission calculated for invoice {$invoice->invoice_number} via bank webhook: {$amount}");
                } else {
                    Log::warning("Commission calculation skipped for invoice {$invoice->invoice_number}: {$result['message']}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to calculate commission for invoice {$invoice->invoice_number}: " . $e->getMessage());
                // Don't fail the transaction if commission calculation fails
            }

            DB::commit();

            Log::info("Bank webhook processed successfully: VA {$request->virtual_account_number}, Amount {$request->amount}, Invoice {$invoice->invoice_number}");

            return response()->json([
                'status' => 'success',
                'message' => 'Payment processed successfully',
                'data' => [
                    'invoice_number' => $invoice->invoice_number,
                    'receipt_number' => $bankReceipt->receipt_number,
                    'amount' => $request->amount,
                    'status' => 'paid'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Bank webhook processing failed: " . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle manual bank receipt verification
     */
    public function verifyBankReceipt(Request $request, BankReceipt $bankReceipt)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:verified,rejected',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $bankReceipt->update([
                'status' => $request->status,
                'notes' => $request->notes,
                'updated_by' => auth()->id(),
            ]);

            // If verified, update invoice status
            if ($request->status === 'verified') {
                $invoice = Invoice::where('invoice_number', $bankReceipt->invoice_reference)->first();
                
                if ($invoice && $invoice->invoice_status !== 'paid') {
                    $invoice->update([
                        'invoice_status' => 'paid',
                        'total_paid' => $bankReceipt->amount,
                        'outstanding' => 0,
                        'payment_date' => $bankReceipt->payment_date,
                        'updated_by' => auth()->id(),
                    ]);

                    // Create invoice activity log
                    $invoice->logActivity(
                        'paid',
                        "Invoice paid via Bank Receipt verification. Receipt: {$bankReceipt->receipt_number}",
                        auth()->id()
                    );

                    // AUTO-CALCULATE COMMISSION on cash receipt
                    try {
                        $commissionService = new \App\Services\Finance\CommissionCalculationService();
                        $cashReceiptDate = $bankReceipt->payment_date ?? now()->toDateString();
                        $result = $commissionService->calculateCommissionOnCashReceipt($invoice, $cashReceiptDate);
                        
                        if ($result['success']) {
                            $amount = isset($result['commission']) ? ($result['commission']->final_amount ?? 'N/A') : 'N/A';
                            Log::info("Commission calculated for invoice {$invoice->invoice_number} via bank receipt verification: {$amount}");
                        } else {
                            Log::warning("Commission calculation skipped for invoice {$invoice->invoice_number}: {$result['message']}");
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to calculate commission for invoice {$invoice->invoice_number}: " . $e->getMessage());
                        // Don't fail the transaction if commission calculation fails
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bank receipt verification completed successfully',
                'data' => $bankReceipt->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify bank receipt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger auto commission calculation when invoice is paid (Berdasarkan BRD)
     * @deprecated Use CommissionCalculationService::calculateCommissionOnCashReceipt instead
     */
    private function triggerAutoCommissionCalculation(Invoice $invoice)
    {
        // This method is kept for backward compatibility but logic has been moved to service
        // Use CommissionCalculationService::calculateCommissionOnCashReceipt instead
        try {
            $commissionService = new \App\Services\Finance\CommissionCalculationService();
            $cashReceiptDate = $invoice->payment_date ?? now()->toDateString();
            $result = $commissionService->calculateCommissionOnCashReceipt($invoice, $cashReceiptDate);
            
            if ($result['success']) {
                $amount = isset($result['commission']) ? ($result['commission']->final_amount ?? 'N/A') : 'N/A';
                Log::info("Commission calculated for invoice {$invoice->invoice_number}: {$amount}");
            } else {
                Log::warning("Commission calculation skipped for invoice {$invoice->invoice_number}: {$result['message']}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to trigger auto commission calculation for invoice {$invoice->invoice_number}: " . $e->getMessage());
        }
    }

    /**
     * Get webhook status for testing
     */
    public function status()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Bank webhook endpoint is active',
            'timestamp' => now()->toISOString()
        ]);
    }
}

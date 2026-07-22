<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\BankReceipt;
use App\Models\CompanyVirtualAccount;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $transactionLock = null;
        $transactionLockAcquired = false;
        $receiptNumberLock = null;
        $receiptNumberLockAcquired = false;

        try {
            // Validate webhook data
            $validator = Validator::make($request->all(), [
                'virtual_account_number' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'payment_date' => 'required|date',
                'transaction_id' => 'required|string|max:255',
                'bank_reference' => 'nullable|string',
                'customer_name' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid webhook data',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $transactionId = trim((string) $request->transaction_id);
            $transactionLock = Cache::lock(
                'bank-webhook:va-payment:'.hash('sha256', $transactionId),
                30
            );
            $transactionLockAcquired = $transactionLock->block(5);

            $webhookNotes = "Auto-generated from bank webhook. Transaction ID: {$transactionId}";
            $existingReceipt = BankReceipt::where('notes', $webhookNotes)->first();

            if ($existingReceipt) {
                Log::info('Duplicate bank webhook acknowledged without reprocessing.', [
                    'transaction_id' => $transactionId,
                    'receipt_number' => $existingReceipt->receipt_number,
                    'invoice_reference' => $existingReceipt->invoice_reference,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment already processed',
                    'data' => [
                        'invoice_number' => $existingReceipt->invoice_reference,
                        'receipt_number' => $existingReceipt->receipt_number,
                        'amount' => $existingReceipt->amount,
                        'status' => 'paid',
                        'duplicate' => true,
                    ],
                ]);
            }

            // Resolve the VA against company_virtual_accounts, the master that
            // actually holds VA numbers. The legacy `virtual_accounts` table is
            // empty, so looking there could never match a real payment.
            $virtualAccount = CompanyVirtualAccount::resolveByAccountNumber($request->virtual_account_number);

            if (! $virtualAccount) {
                // Log the raw value: stored numbers vary in width, so an unmatched
                // payment is the evidence needed to reconcile formats with the bank.
                Log::warning('Virtual Account not found for incoming bank payment.', [
                    'virtual_account_number' => $request->virtual_account_number,
                    'transaction_id' => $request->transaction_id,
                    'amount' => $request->amount,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Virtual Account not found',
                ], 404);
            }

            // Find the outstanding invoice for the customer this VA belongs to.
            // Matching on invoices.virtual_account_number alone is not viable:
            // that column is never populated by invoice generation.
            $invoice = Invoice::where('customer_id', $virtualAccount->customer_id)
                ->where('invoice_status', '!=', 'paid')
                ->orderBy('invoice_date')
                ->first();

            if (! $invoice) {
                Log::warning('No outstanding invoice for resolved Virtual Account.', [
                    'virtual_account_number' => $request->virtual_account_number,
                    'resolved_account_number' => $virtualAccount->account_number,
                    'customer_id' => $virtualAccount->customer_id,
                    'transaction_id' => $request->transaction_id,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Invoice not found',
                ], 404);
            }

            // Validate payment amount
            if ($request->amount != $invoice->total_amount) {
                Log::warning("Payment amount mismatch for VA {$request->virtual_account_number}: Expected {$invoice->total_amount}, Received {$request->amount}");

                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment amount mismatch',
                ], 400);
            }

            $receiptNumberLock = Cache::lock('bank-receipts:webhook-number', 30);
            $receiptNumberLockAcquired = $receiptNumberLock->block(5);

            DB::beginTransaction();

            // Create bank receipt record
            $bankReceipt = BankReceipt::create([
                'receipt_number' => BankReceipt::generateWebhookReceiptNumber(),
                'receipt_date' => now(),
                'customer_id' => $invoice->customer_id,
                'invoice_reference' => $invoice->invoice_number,
                // CompanyVirtualAccount points at a bank_payment, which carries the bank_id.
                'bank_id' => optional($virtualAccount->bankPayment)->bank_id,
                'account_number' => $request->virtual_account_number,
                'account_holder_name' => $request->customer_name ?? $invoice->customer->name,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => 'transfer',
                'status' => 'verified', // Auto-verify from bank webhook
                'notes' => $webhookNotes,
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
                $commissionService = new \App\Services\Finance\CommissionCalculationService;
                $cashReceiptDate = $request->payment_date ?? now()->toDateString();
                $result = $commissionService->calculateCommissionOnCashReceipt($invoice, $cashReceiptDate);

                if ($result['success']) {
                    $amount = isset($result['commission']) ? ($result['commission']->final_amount ?? 'N/A') : 'N/A';
                    Log::info("Commission calculated for invoice {$invoice->invoice_number} via bank webhook: {$amount}");
                } else {
                    Log::warning("Commission calculation skipped for invoice {$invoice->invoice_number}: {$result['message']}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to calculate commission for invoice {$invoice->invoice_number}: ".$e->getMessage());
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
                    'status' => 'paid',
                ],
            ]);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Bank webhook processing failed: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process payment: '.$e->getMessage(),
            ], 500);
        } finally {
            if ($transactionLockAcquired && $transactionLock) {
                $transactionLock->release();
            }

            if ($receiptNumberLockAcquired && $receiptNumberLock) {
                $receiptNumberLock->release();
            }
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
                    'errors' => $validator->errors(),
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
                        $commissionService = new \App\Services\Finance\CommissionCalculationService;
                        $cashReceiptDate = $bankReceipt->payment_date ?? now()->toDateString();
                        $result = $commissionService->calculateCommissionOnCashReceipt($invoice, $cashReceiptDate);

                        if ($result['success']) {
                            $amount = isset($result['commission']) ? ($result['commission']->final_amount ?? 'N/A') : 'N/A';
                            Log::info("Commission calculated for invoice {$invoice->invoice_number} via bank receipt verification: {$amount}");
                        } else {
                            Log::warning("Commission calculation skipped for invoice {$invoice->invoice_number}: {$result['message']}");
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to calculate commission for invoice {$invoice->invoice_number}: ".$e->getMessage());
                        // Don't fail the transaction if commission calculation fails
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bank receipt verification completed successfully',
                'data' => $bankReceipt->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify bank receipt: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger auto commission calculation when invoice is paid (Berdasarkan BRD)
     *
     * @deprecated Use CommissionCalculationService::calculateCommissionOnCashReceipt instead
     */
    private function triggerAutoCommissionCalculation(Invoice $invoice)
    {
        // This method is kept for backward compatibility but logic has been moved to service
        // Use CommissionCalculationService::calculateCommissionOnCashReceipt instead
        try {
            $commissionService = new \App\Services\Finance\CommissionCalculationService;
            $cashReceiptDate = $invoice->payment_date ?? now()->toDateString();
            $result = $commissionService->calculateCommissionOnCashReceipt($invoice, $cashReceiptDate);

            if ($result['success']) {
                $amount = isset($result['commission']) ? ($result['commission']->final_amount ?? 'N/A') : 'N/A';
                Log::info("Commission calculated for invoice {$invoice->invoice_number}: {$amount}");
            } else {
                Log::warning("Commission calculation skipped for invoice {$invoice->invoice_number}: {$result['message']}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to trigger auto commission calculation for invoice {$invoice->invoice_number}: ".$e->getMessage());
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
            'timestamp' => now()->toISOString(),
        ]);
    }
}

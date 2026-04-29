<?php

namespace App\Services\Finance;

use App\Models\Finance\BankReceipt;
use App\Models\Finance\Invoice;
use App\Models\Customer;
use App\Models\Finance\Bank;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankReceiptService
{
    /**
     * Auto-populate bank receipt data from invoice
     */
    public function autoPopulateFromInvoice($invoiceNumber, $additionalData = [])
    {
        try {
            DB::beginTransaction();

            $invoice = Invoice::with(['customer', 'contract.customer'])->where('invoice_number', $invoiceNumber)->first();
            
            if (!$invoice) {
                throw new \Exception("Invoice not found with number: {$invoiceNumber}");
            }

            // Get customer from invoice
            $customer = $invoice->customer ?? $invoice->contract->customer;
            
            if (!$customer) {
                throw new \Exception("Customer not found for invoice: {$invoiceNumber}");
            }

            // Auto-populate bank receipt data
            $bankReceiptData = [
                'receipt_number' => BankReceipt::generateReceiptNumber(),
                'receipt_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'invoice_reference' => $invoiceNumber,
                'amount' => $invoice->grand_total,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'bank_transfer',
                'status' => 'pending',
                'notes' => "Auto-generated from invoice {$invoiceNumber}",
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ];

            // Merge additional data if provided
            $bankReceiptData = array_merge($bankReceiptData, $additionalData);

            $bankReceipt = BankReceipt::create($bankReceiptData);

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Bank receipt auto-populated from invoice successfully',
                'data' => $bankReceipt->load(['customer', 'invoice', 'bank'])
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to auto-populate bank receipt from invoice: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get invoice data for bank receipt
     */
    public function getInvoiceDataForBankReceipt($invoiceNumber)
    {
        $invoice = Invoice::with([
            'customer',
            'contract.customer',
            'invoiceRentals.masterRental',
            'invoiceRentals.contractRental'
        ])->where('invoice_number', $invoiceNumber)->first();

        if (!$invoice) {
            return [
                'status' => 'error',
                'message' => "Invoice not found with number: {$invoiceNumber}"
            ];
        }

        $customer = $invoice->customer ?? $invoice->contract->customer;
        
        if (!$customer) {
            return [
                'status' => 'error',
                'message' => "Customer not found for invoice: {$invoiceNumber}"
            ];
        }

        // Get customer's default bank if available
        $defaultBank = Bank::where('customer_id', $customer->id)->first();

        $invoiceData = [
            'invoice' => $invoice,
            'customer' => $customer,
            'default_bank' => $defaultBank,
            'invoice_amount' => $invoice->grand_total,
            'invoice_date' => $invoice->invoice_date,
            'due_date' => $invoice->due_date,
            'outstanding_amount' => $invoice->outstanding,
            'payment_terms' => $invoice->payment_terms,
            'billing_address' => $customer->address,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email
        ];

        return [
            'status' => 'success',
            'data' => $invoiceData
        ];
    }

    /**
     * Validate invoice for bank receipt creation
     */
    public function validateInvoiceForBankReceipt($invoiceNumber)
    {
        $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

        if (!$invoice) {
            return [
                'is_valid' => false,
                'errors' => ["Invoice not found with number: {$invoiceNumber}"]
            ];
        }

        $errors = [];
        $warnings = [];

        // Check if invoice is paid
        if ($invoice->invoice_status === 'paid') {
            $errors[] = "Invoice is already paid";
        }

        // Check if invoice is cancelled
        if ($invoice->invoice_status === 'cancelled') {
            $errors[] = "Invoice is cancelled";
        }

        // Check if invoice has outstanding amount
        if ($invoice->outstanding <= 0) {
            $warnings[] = "Invoice has no outstanding amount";
        }

        // Check if customer exists
        $customer = $invoice->customer ?? $invoice->contract->customer;
        if (!$customer) {
            $errors[] = "Customer not found for invoice";
        }

        // Check if invoice is overdue
        if ($invoice->due_date && $invoice->due_date < now()) {
            $warnings[] = "Invoice is overdue";
        }

        return [
            'is_valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
            'invoice' => $invoice,
            'customer' => $customer
        ];
    }

    /**
     * Create bank receipt with auto-populated data
     */
    public function createBankReceiptFromInvoice($invoiceNumber, $additionalData = [])
    {
        try {
            DB::beginTransaction();

            // Validate invoice first
            $validation = $this->validateInvoiceForBankReceipt($invoiceNumber);
            
            if (!$validation['is_valid']) {
                throw new \Exception("Invoice validation failed: " . implode(', ', $validation['errors']));
            }

            $invoice = $validation['invoice'];
            $customer = $validation['customer'];

            // Check if bank receipt already exists for this invoice
            $existingReceipt = BankReceipt::where('invoice_reference', $invoiceNumber)->first();
            
            if ($existingReceipt) {
                throw new \Exception("Bank receipt already exists for invoice: {$invoiceNumber}");
            }

            // Auto-populate data
            $bankReceiptData = [
                'receipt_number' => BankReceipt::generateReceiptNumber(),
                'receipt_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'invoice_reference' => $invoiceNumber,
                'amount' => $invoice->grand_total,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'bank_transfer',
                'status' => 'pending',
                'notes' => "Auto-generated from invoice {$invoiceNumber}",
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ];

            // Merge additional data
            $bankReceiptData = array_merge($bankReceiptData, $additionalData);

            $bankReceipt = BankReceipt::create($bankReceiptData);

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Bank receipt created from invoice successfully',
                'data' => $bankReceipt->load(['customer', 'invoice', 'bank']),
                'warnings' => $validation['warnings']
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create bank receipt from invoice: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get available invoices for bank receipt creation
     */
    public function getAvailableInvoicesForBankReceipt($search = null, $customerId = null)
    {
        $query = Invoice::with(['customer', 'contract.customer'])
            ->where('invoice_status', '!=', 'paid')
            ->where('invoice_status', '!=', 'cancelled')
            ->where('outstanding', '>', 0);

        if ($customerId) {
            $query->where(function($q) use ($customerId) {
                $q->where('customer_id', $customerId)
                  ->orWhereHas('contract', function($contractQuery) use ($customerId) {
                      $contractQuery->where('customer_id', $customerId);
                  });
            });
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', function($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate(20);

        return [
            'status' => 'success',
            'data' => $invoices->items(),
            'pagination' => [
                'total' => $invoices->total(),
                'per_page' => $invoices->perPage(),
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'from' => $invoices->firstItem(),
                'to' => $invoices->lastItem(),
            ]
        ];
    }

    /**
     * Get bank receipt statistics
     */
    public function getBankReceiptStatistics($dateRange = null)
    {
        $query = BankReceipt::with(['customer', 'invoice']);

        if ($dateRange) {
            $query->whereBetween('receipt_date', $dateRange);
        }

        $receipts = $query->get();

        $totalReceipts = $receipts->count();
        $totalAmount = $receipts->sum('amount');
        $pendingReceipts = $receipts->where('status', 'pending')->count();
        $verifiedReceipts = $receipts->where('status', 'verified')->count();
        $processedReceipts = $receipts->where('status', 'processed')->count();
        $failedReceipts = $receipts->where('status', 'failed')->count();

        $averageAmount = $totalReceipts > 0 ? $totalAmount / $totalReceipts : 0;

        return [
            'total_receipts' => $totalReceipts,
            'total_amount' => $totalAmount,
            'average_amount' => $averageAmount,
            'pending_receipts' => $pendingReceipts,
            'verified_receipts' => $verifiedReceipts,
            'processed_receipts' => $processedReceipts,
            'failed_receipts' => $failedReceipts,
            'success_rate' => $totalReceipts > 0 ? round((($verifiedReceipts + $processedReceipts) / $totalReceipts) * 100, 2) : 0
        ];
    }

    /**
     * Auto-match bank receipt with invoice
     */
    public function autoMatchBankReceiptWithInvoice($receiptId)
    {
        try {
            DB::beginTransaction();

            $bankReceipt = BankReceipt::findOrFail($receiptId);
            
            if (!$bankReceipt->invoice_reference) {
                throw new \Exception("Bank receipt does not have invoice reference");
            }

            $invoice = Invoice::where('invoice_number', $bankReceipt->invoice_reference)->first();
            
            if (!$invoice) {
                throw new \Exception("Invoice not found for reference: {$bankReceipt->invoice_reference}");
            }

            // Check if amounts match
            if ($bankReceipt->amount != $invoice->grand_total) {
                throw new \Exception("Bank receipt amount does not match invoice amount");
            }

            // Update invoice status
            $invoice->update([
                'invoice_status' => 'paid',
                'total_paid' => $invoice->grand_total,
                'outstanding' => 0,
                'payment_date' => $bankReceipt->payment_date ?? now()
            ]);

            // Update bank receipt status
            $bankReceipt->update([
                'status' => 'processed'
            ]);

            // Trigger commission calculation on cash receipt
            try {
                $commissionService = new \App\Services\Finance\CommissionCalculationService();
                $cashReceiptDate = $bankReceipt->payment_date ?? now()->toDateString();
                $result = $commissionService->calculateCommissionOnCashReceipt($invoice, $cashReceiptDate);
                
                if ($result['success']) {
                    Log::info("Commission calculated for invoice {$invoice->invoice_number} via bank receipt: {$result['commission']->final_amount ?? 'N/A'}");
                } else {
                    Log::warning("Commission calculation skipped for invoice {$invoice->invoice_number}: {$result['message']}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to calculate commission for invoice {$invoice->invoice_number}: " . $e->getMessage());
                // Don't fail the transaction if commission calculation fails
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Bank receipt matched with invoice successfully',
                'data' => [
                    'bank_receipt' => $bankReceipt,
                    'invoice' => $invoice
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to auto-match bank receipt with invoice: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get bank receipt analytics
     */
    public function getBankReceiptAnalytics($dateRange = null)
    {
        $query = BankReceipt::with(['customer', 'invoice']);

        if ($dateRange) {
            $query->whereBetween('receipt_date', $dateRange);
        }

        $receipts = $query->get();

        $analytics = [
            'total_receipts' => $receipts->count(),
            'total_amount' => $receipts->sum('amount'),
            'average_amount' => $receipts->count() > 0 ? $receipts->sum('amount') / $receipts->count() : 0,
            'status_distribution' => $receipts->groupBy('status')->map->count(),
            'payment_method_distribution' => $receipts->groupBy('payment_method')->map->count(),
            'monthly_trend' => $receipts->groupBy(function($receipt) {
                return $receipt->receipt_date->format('Y-m');
            })->map(function($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount')
                ];
            }),
            'top_customers' => $receipts->groupBy('customer_id')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'amount' => $group->sum('amount')
                ];
            })->sortByDesc('amount')->take(10)
        ];

        return [
            'status' => 'success',
            'data' => $analytics
        ];
    }
}
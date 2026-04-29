<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\Finance\BankReceipt;
use App\Models\Customer;
use App\Models\Finance\Bank;
use App\Models\User;
use App\Services\Finance\BankReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BankReceiptController extends Controller
{
    use AccessControlFilterTrait;
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BankReceipt::with(['customer:id,name', 'bank:id,bank_name', 'creator:id,name', 'updater:id,name']);

        // Apply access control filter (hierarchical data)
        // Default: Jika tidak set hirarki, hanya bisa lihat data sendiri
        $query = $this->applyAccessControlFilter($query, null, 'created_by');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhere('invoice_reference', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('account_holder_name', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('bank', function($bankQuery) use ($search) {
                      $bankQuery->where('bank_name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by bank
        if ($request->filled('bank_id')) {
            $query->where('bank_id', $request->bank_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('receipt_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('receipt_date', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $bankReceipts = $query->paginate(15);

        // Get data for filters - optimized queries
        $customers = Customer::select('id', 'name')->where('is_active', true)->orderBy('name')->get();
        $banks = Bank::select('id', 'bank_name')->orderBy('bank_name')->get();

        return view('finance.bank-receipts.index', compact('bankReceipts', 'customers', 'banks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $banks = Bank::orderBy('bank_name')->get();

        return view('finance.bank-receipts.create', compact('customers', 'banks'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receipt_date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'invoice_reference' => 'nullable|string|max:255',
            'bank_id' => 'required|exists:banks,id',
            'account_number' => 'required|string|max:255',
            'account_holder_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:transfer,cash,check,giro,bank_transfer,credit_card',
            'status' => 'required|in:pending,verified,rejected,processed,failed',
            'receipt_image' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120', // 5MB max
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            // Handle file upload
            if ($request->hasFile('receipt_image')) {
                $file = $request->file('receipt_image');
                $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('bank-receipts', $fileName, 'public');
                $data['receipt_image'] = $path;
            }

            $bankReceipt = BankReceipt::create($data);

            DB::commit();

            // Check if request is AJAX
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Bank receipt created successfully.',
                    'data' => $bankReceipt->load(['customer', 'bank'])
                ]);
            }

            return redirect()
                ->route('finance.bank-receipts.show', $bankReceipt->id)
                ->with('success', 'Bank receipt created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Check if request is AJAX
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create bank receipt. ' . $e->getMessage()
                ], 500);
            }
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create bank receipt. ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(BankReceipt $bankReceipt)
    {
        $bankReceipt->load(['customer', 'bank', 'creator', 'updater']);
        
        // Check if request is AJAX
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $bankReceipt
            ]);
        }
        
        return view('finance.bank-receipts.show', compact('bankReceipt'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BankReceipt $bankReceipt)
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $banks = Bank::orderBy('bank_name')->get();

        // Check if request is AJAX
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $bankReceipt->load(['customer', 'bank']),
                'customers' => $customers,
                'banks' => $banks
            ]);
        }

        return view('finance.bank-receipts.edit', compact('bankReceipt', 'customers', 'banks'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BankReceipt $bankReceipt)
    {
        $request->validate([
            'receipt_date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'invoice_reference' => 'nullable|string|max:255',
            'bank_id' => 'required|exists:banks,id',
            'account_number' => 'required|string|max:255',
            'account_holder_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:transfer,cash,check,giro,bank_transfer,credit_card',
            'status' => 'required|in:pending,verified,rejected,processed,failed',
            'receipt_image' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120', // 5MB max
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $data = $request->all();
            $data['updated_by'] = Auth::id();

            // Handle file upload
            if ($request->hasFile('receipt_image')) {
                // Delete old file if exists
                if ($bankReceipt->receipt_image) {
                    Storage::disk('public')->delete($bankReceipt->receipt_image);
                }

                $file = $request->file('receipt_image');
                $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('bank-receipts', $fileName, 'public');
                $data['receipt_image'] = $path;
            }

            $bankReceipt->update($data);

            DB::commit();

            return redirect()
                ->route('finance.bank-receipts.show', $bankReceipt->id)
                ->with('success', 'Bank receipt updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to update bank receipt. ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BankReceipt $bankReceipt)
    {
        try {
            DB::beginTransaction();

            // Delete file if exists
            if ($bankReceipt->receipt_image) {
                Storage::disk('public')->delete($bankReceipt->receipt_image);
            }

            $bankReceipt->delete();

            DB::commit();

            return redirect()
                ->route('finance.bank-receipts.index')
                ->with('success', 'Bank receipt deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete bank receipt. ' . $e->getMessage());
        }
    }

    /**
     * Verify a bank receipt
     */
    public function verify(BankReceipt $bankReceipt)
    {
        try {
            if ($bankReceipt->status !== 'pending') {
                return back()->with('error', 'Only pending receipts can be verified.');
            }

            $bankReceipt->update([
                'status' => 'verified',
                'updated_by' => Auth::id(),
            ]);

            return back()->with('success', 'Bank receipt verified successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to verify bank receipt. ' . $e->getMessage());
        }
    }

    /**
     * Reject a bank receipt
     */
    public function reject(BankReceipt $bankReceipt)
    {
        try {
            if ($bankReceipt->status !== 'pending') {
                return back()->with('error', 'Only pending receipts can be rejected.');
            }

            $bankReceipt->update([
                'status' => 'rejected',
                'updated_by' => Auth::id(),
            ]);

            return back()->with('success', 'Bank receipt rejected successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject bank receipt. ' . $e->getMessage());
        }
    }

    /**
     * Process a bank receipt
     */
    public function process(BankReceipt $bankReceipt)
    {
        try {
            if ($bankReceipt->status !== 'verified') {
                return back()->with('error', 'Only verified receipts can be processed.');
            }

            $bankReceipt->update([
                'status' => 'processed',
                'updated_by' => Auth::id(),
            ]);

            return back()->with('success', 'Bank receipt processed successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to process bank receipt. ' . $e->getMessage());
        }
    }

    /**
     * Bulk verify receipts
     */
    public function bulkVerify(Request $request)
    {
        $request->validate([
            'receipt_ids' => 'required|array',
            'receipt_ids.*' => 'exists:bank_receipts,id'
        ]);

        try {
            DB::beginTransaction();

            $receipts = BankReceipt::whereIn('id', $request->receipt_ids)
                                  ->where('status', 'pending')
                                  ->get();

            foreach ($receipts as $receipt) {
                $receipt->update([
                    'status' => 'verified',
                    'updated_by' => Auth::id(),
                ]);
            }

            DB::commit();

            return back()->with('success', count($receipts) . ' receipts verified successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to verify receipts. ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete receipts
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'receipt_ids' => 'required|array',
            'receipt_ids.*' => 'exists:bank_receipts,id'
        ]);

        try {
            DB::beginTransaction();

            $receipts = BankReceipt::whereIn('id', $request->receipt_ids)->get();

            foreach ($receipts as $receipt) {
                // Delete file if exists
                if ($receipt->receipt_image) {
                    Storage::disk('public')->delete($receipt->receipt_image);
                }
                $receipt->delete();
            }

            DB::commit();

            return back()->with('success', count($receipts) . ' receipts deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete receipts. ' . $e->getMessage());
        }
    }

    /**
     * Export receipts to Excel/CSV
     */
    public function export(Request $request)
    {
        $query = BankReceipt::with(['customer', 'bank']);

        // Apply filters
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('bank_id')) {
            $query->where('bank_id', $request->bank_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('receipt_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('receipt_date', '<=', $request->date_to);
        }

        $receipts = $query->get();

        $filename = 'bank_receipts_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($receipts) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Receipt Number',
                'Receipt Date',
                'Customer',
                'Bank',
                'Account Number',
                'Account Holder',
                'Amount',
                'Payment Date',
                'Payment Method',
                'Status',
                'Invoice Reference',
                'Notes',
                'Created At'
            ]);

            // CSV data
            foreach ($receipts as $receipt) {
                fputcsv($file, [
                    $receipt->receipt_number,
                    $receipt->receipt_date ? date('Y-m-d', strtotime($receipt->receipt_date)) : '',
                    $receipt->customer->name ?? '',
                    $receipt->bank->bank_name ?? '',
                    $receipt->account_number,
                    $receipt->account_holder_name,
                    $receipt->amount,
                    $receipt->payment_date ? date('Y-m-d', strtotime($receipt->payment_date)) : '',
                    $receipt->payment_method,
                    $receipt->status,
                    $receipt->invoice_reference,
                    $receipt->notes,
                    $receipt->created_at ? date('Y-m-d H:i:s', strtotime($receipt->created_at)) : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get receipt statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => BankReceipt::count(),
            'pending' => BankReceipt::where('status', 'pending')->count(),
            'verified' => BankReceipt::where('status', 'verified')->count(),
            'rejected' => BankReceipt::where('status', 'rejected')->count(),
            'processed' => BankReceipt::where('status', 'processed')->count(),
            'total_amount' => BankReceipt::sum('amount'),
            'this_month' => BankReceipt::whereMonth('created_at', now()->month)->count(),
            'this_month_amount' => BankReceipt::whereMonth('created_at', now()->month)->sum('amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Auto-populate bank receipt from invoice
     */
    public function autoPopulateFromInvoice(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required|string',
            'additional_data' => 'nullable|array'
        ]);

        $service = new BankReceiptService();
        $result = $service->autoPopulateFromInvoice(
            $request->invoice_number,
            $request->additional_data ?? []
        );

        return response()->json($result);
    }

    /**
     * Get invoice data for bank receipt
     */
    public function getInvoiceData(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required|string'
        ]);

        $service = new BankReceiptService();
        $result = $service->getInvoiceDataForBankReceipt($request->invoice_number);

        return response()->json($result);
    }

    /**
     * Validate invoice for bank receipt
     */
    public function validateInvoice(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required|string'
        ]);

        $service = new BankReceiptService();
        $result = $service->validateInvoiceForBankReceipt($request->invoice_number);

        return response()->json($result);
    }

    /**
     * Create bank receipt from invoice
     */
    public function createFromInvoice(Request $request)
    {
        $request->validate([
            'invoice_number' => 'required|string',
            'additional_data' => 'nullable|array'
        ]);

        $service = new BankReceiptService();
        $result = $service->createBankReceiptFromInvoice(
            $request->invoice_number,
            $request->additional_data ?? []
        );

        return response()->json($result);
    }

    /**
     * Get available invoices for bank receipt
     */
    public function getAvailableInvoices(Request $request)
    {
        $service = new BankReceiptService();
        $result = $service->getAvailableInvoicesForBankReceipt(
            $request->search,
            $request->customer_id
        );

        return response()->json($result);
    }

    /**
     * Get bank receipt statistics with analytics
     */
    public function getAnalytics(Request $request)
    {
        $dateRange = null;
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $dateRange = [$request->date_from, $request->date_to];
        }

        $service = new BankReceiptService();
        $result = $service->getBankReceiptAnalytics($dateRange);

        return response()->json($result);
    }

    /**
     * Auto-match bank receipt with invoice
     */
    public function autoMatchWithInvoice(BankReceipt $bankReceipt)
    {
        $service = new BankReceiptService();
        $result = $service->autoMatchBankReceiptWithInvoice($bankReceipt->id);

        if ($result['status'] === 'success') {
            return back()->with('success', $result['message']);
        } else {
            return back()->with('error', $result['message']);
        }
    }

    /**
     * Get enhanced statistics
     */
    public function getEnhancedStatistics(Request $request)
    {
        $dateRange = null;
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $dateRange = [$request->date_from, $request->date_to];
        }

        $service = new BankReceiptService();
        $result = $service->getBankReceiptStatistics($dateRange);

        return response()->json($result);
    }
}

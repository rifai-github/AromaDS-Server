<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\EMateraiTransaction;
use App\Models\TaxInvoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EMateraiTransactionController extends Controller
{
    /**
     * Display a listing of e-Materai transactions
     */
    public function index(Request $request)
    {
        $query = EMateraiTransaction::with(['taxInvoice', 'invoice', 'createdBy']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('peruri_reference_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        // Sort
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $eMateraiTransactions = $query->paginateStd(25)->withQueryString();

        // Get filter options
        $statuses = ['pending', 'applied', 'failed', 'cancelled'];

        return view('tax.e-materai.index', compact('eMateraiTransactions', 'statuses'));
    }

    /**
     * Show the form for creating a new e-Materai transaction
     */
    public function create()
    {
        $taxInvoices = TaxInvoice::where('status', 'approved')
            ->where('is_e_materai_applied', false)
            ->get();
        
        return view('tax.e-materai.create', compact('taxInvoices'));
    }

    /**
     * Store a newly created e-Materai transaction
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tax_invoice_id' => 'required|exists:tax_invoices,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'document_path' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $eMateraiTransaction = EMateraiTransaction::create([
                'tax_invoice_id' => $request->tax_invoice_id,
                'invoice_id' => $request->invoice_id,
                'transaction_id' => 'EMT-' . time(),
                'status' => 'pending',
                'document_path' => $request->document_path,
                'notes' => $request->notes,
                'created_by' => Auth::id()
            ]);

            DB::commit();

            return redirect()->route('e-materai-transactions.index')
                ->with('success', 'e-Materai transaction created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error creating e-Materai transaction: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified e-Materai transaction
     */
    public function show(EMateraiTransaction $eMateraiTransaction)
    {
        $eMateraiTransaction->load(['taxInvoice', 'invoice', 'createdBy', 'updatedBy']);
        
        return view('tax.e-materai.show', compact('eMateraiTransaction'));
    }

    /**
     * Show the form for editing the specified e-Materai transaction
     */
    public function edit(EMateraiTransaction $eMateraiTransaction)
    {
        $taxInvoices = TaxInvoice::where('status', 'approved')->get();
        
        return view('tax.e-materai.edit', compact('eMateraiTransaction', 'taxInvoices'));
    }

    /**
     * Update the specified e-Materai transaction
     */
    public function update(Request $request, EMateraiTransaction $eMateraiTransaction)
    {
        $validator = Validator::make($request->all(), [
            'tax_invoice_id' => 'required|exists:tax_invoices,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'document_path' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $eMateraiTransaction->update([
                'tax_invoice_id' => $request->tax_invoice_id,
                'invoice_id' => $request->invoice_id,
                'document_path' => $request->document_path,
                'notes' => $request->notes,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return redirect()->route('e-materai-transactions.index')
                ->with('success', 'e-Materai transaction updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error updating e-Materai transaction: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified e-Materai transaction
     */
    public function destroy(EMateraiTransaction $eMateraiTransaction)
    {
        try {
            $eMateraiTransaction->delete();
            return redirect()->route('e-materai-transactions.index')
                ->with('success', 'e-Materai transaction deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting e-Materai transaction: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete e-Materai transactions
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:e_materai_transactions,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid selection'], 400);
        }

        try {
            $count = EMateraiTransaction::whereIn('id', $request->ids)->delete();
            return response()->json(['message' => "{$count} e-Materai transactions deleted successfully"]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting e-Materai transactions: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Retry e-Materai transaction
     */
    public function retry(Request $request, EMateraiTransaction $eMateraiTransaction)
    {
        try {
            DB::beginTransaction();

            // Reset status to pending for retry
            $eMateraiTransaction->update([
                'status' => 'pending',
                'response_data' => null,
                'updated_by' => Auth::id()
            ]);

            // Here you would typically call the Peruri API to retry the transaction
            // For now, we'll just update the status
            $eMateraiTransaction->update([
                'status' => 'applied',
                'applied_at' => now(),
                'peruri_reference_number' => 'PERURI-' . time(),
                'response_data' => [
                    'status' => 'success',
                    'message' => 'e-Materai applied successfully',
                    'timestamp' => now()->toISOString()
                ],
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return redirect()->route('e-materai-transactions.show', $eMateraiTransaction)
                ->with('success', 'e-Materai transaction retried successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error retrying e-Materai transaction: ' . $e->getMessage());
        }
    }
}

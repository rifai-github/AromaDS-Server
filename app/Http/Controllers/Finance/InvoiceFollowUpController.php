<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\Finance\InvoiceFollowUp;
use App\Models\Finance\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceFollowUpController extends Controller
{
    use ColumnFilterTrait;
    public function index(Request $request)
    {
        $query = InvoiceFollowUp::with(['invoice:id,invoice_number,customer_id', 'invoice.customer:id,name', 'creator:id,name', 'updater:id,name']);

        // Apply per-column filters (table id: invoiceFollowUpsTable)
        try {
            // Capture flat structure filters
            $customFilters = [];
            if ($request->has('invoice_number')) $customFilters['invoice_number'] = $request->invoice_number; // Assuming you might want to filter by invoice number directly if needed, though relation handles it
            if ($request->has('customer_name')) $customFilters['customer_name'] = $request->customer_name;
            if ($request->has('follow_up_date')) $customFilters['follow_up_date'] = $request->follow_up_date;
            if ($request->has('follow_up_type')) $customFilters['follow_up_type'] = $request->follow_up_type;
            if ($request->has('status')) $customFilters['status'] = $request->status;
            if ($request->has('created_at')) $customFilters['created_at'] = $request->created_at;

            $this->applyColumnFilters($query, 'invoiceFollowUpsTable', [
                // 0 => checkbox
                1 => ['relation' => 'invoice', 'column' => 'invoice_number'],
                'invoice__invoice_number' => ['relation' => 'invoice', 'column' => 'invoice_number'],
                
                2 => ['relation' => 'invoice.customer', 'column' => 'name'],
                'invoice__customer__name' => ['relation' => 'invoice.customer', 'column' => 'name'],
                
                3 => ['column' => 'follow_up_date', 'type' => 'date'],
                'follow_up_date' => ['column' => 'follow_up_date', 'type' => 'date'],
                
                4 => ['column' => 'follow_up_type'],
                'follow_up_type' => ['column' => 'follow_up_type'],
                
                5 => ['column' => 'status'],
                'status' => ['column' => 'status'],
                
                6 => ['column' => 'created_at', 'type' => 'date'],
                'created_at' => ['column' => 'created_at', 'type' => 'date'],
            ], $customFilters);
        } catch (\Exception $e) {
            \Log::error('Error applying column filters in InvoiceFollowUpController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Filter by invoice
        if ($request->filled('invoice_id') && !$request->has('filter')) {
            $query->where('invoice_id', $request->invoice_id);
        }

        // Filter by follow up type
        if ($request->filled('follow_up_type') && !$request->has('filter')) {
            $query->where('follow_up_type', $request->follow_up_type);
        }

        // Filter by status
        if ($request->filled('status') && !$request->has('filter')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date') && !$request->has('filter')) {
            $query->whereBetween('follow_up_date', [$request->start_date, $request->end_date]);
        }

        // Search
        if ($request->filled('search') && !$request->has('filter')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('invoice', function($invoiceQuery) use ($search) {
                    $invoiceQuery->where('invoice_number', 'like', '%' . $search . '%')
                                 ->orWhereHas('customer', function($customerQuery) use ($search) {
                                     $customerQuery->where('name', 'like', '%' . $search . '%');
                                 });
                })->orWhere('notes', 'like', '%' . $search . '%');
            });
        }

        $followUps = $query->orderBy('follow_up_date', 'desc')->paginate(15);

        // Get filter options - optimized queries
        $invoices = Invoice::select('id', 'invoice_number', 'customer_id')
            ->with('customer:id,name')
            ->whereIn('invoice_status', ['draft', 'sent', 'overdue', 'approved', 'tax_approved'])
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name ?? 'N/A'
                ];
            });
        $followUpTypes = ['email', 'phone', 'visit', 'letter'];
        $statuses = ['pending', 'completed', 'cancelled'];

        return view('finance.invoice-follow-ups.index', compact('followUps', 'invoices', 'followUpTypes', 'statuses'));
    }

    public function create()
    {
        $invoices = Invoice::select('id', 'invoice_number', 'customer_id')
            ->with('customer:id,name')
            ->whereIn('invoice_status', ['draft', 'sent', 'overdue', 'approved', 'tax_approved'])
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name ?? 'N/A'
                ];
            });
        $followUpTypes = ['email', 'phone', 'visit', 'letter'];
        $statuses = ['pending', 'completed', 'cancelled'];

        return view('finance.invoice-follow-ups.create', compact('invoices', 'followUpTypes', 'statuses'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'follow_up_date' => 'required|date',
            'follow_up_type' => 'required|in:email,phone,visit,letter',
            'notes' => 'required|string',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $followUp = InvoiceFollowUp::create([
                'invoice_id' => $request->invoice_id,
                'follow_up_date' => $request->follow_up_date,
                'follow_up_type' => $request->follow_up_type,
                'notes' => $request->notes,
                'status' => $request->status,
                'created_by' => auth()->id(),
            ]);

            $followUp->invoice?->logActivity(
                'updated',
                'Invoice follow up created' . "\n" . $this->formatFollowUpLogNotes($followUp),
                auth()->id()
            );

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice follow up created successfully.'
                ]);
            }

            return redirect()->route('finance.invoice-follow-ups.index')
                ->with('success', 'Invoice follow up created successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating invoice follow up: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()
                ->with('error', 'Error creating invoice follow up: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        try {
            $followUp = InvoiceFollowUp::with(['invoice.customer', 'creator', 'updater'])
                ->findOrFail($id);

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $followUp
                ]);
            }

            return view('finance.invoice-follow-ups.show', compact('followUp'));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error loading follow up data: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error loading follow up data');
        }
    }

    public function edit($id)
    {
        $followUp = InvoiceFollowUp::with(['invoice.customer'])->findOrFail($id);
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $followUp
            ]);
        }

        $invoices = Invoice::select('id', 'invoice_number', 'customer_id')
            ->with('customer:id,name')
            ->whereIn('invoice_status', ['draft', 'sent', 'overdue', 'approved', 'tax_approved'])
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name ?? 'N/A'
                ];
            });
        $followUpTypes = ['email', 'phone', 'visit', 'letter'];
        $statuses = ['pending', 'completed', 'cancelled'];

        return view('finance.invoice-follow-ups.edit', compact('followUp', 'invoices', 'followUpTypes', 'statuses'));
    }

    public function update(Request $request, $id)
    {
        $followUp = InvoiceFollowUp::findOrFail($id);


        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'follow_up_date' => 'required|date',
            'follow_up_type' => 'required|in:email,phone,visit,letter',
            'notes' => 'required|string',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $oldNotes = $this->formatFollowUpLogNotes($followUp);

            $followUp->update([
                'invoice_id' => $request->invoice_id,
                'follow_up_date' => $request->follow_up_date,
                'follow_up_type' => $request->follow_up_type,
                'notes' => $request->notes,
                'status' => $request->status,
                'updated_by' => auth()->id(),
            ]);

            $followUp->refresh();
            $followUp->invoice?->logActivity(
                'updated',
                "Invoice follow up updated\nBefore:\n{$oldNotes}\nAfter:\n" . $this->formatFollowUpLogNotes($followUp),
                auth()->id()
            );

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice follow up updated successfully.'
                ]);
            }

            return redirect()->route('finance.invoice-follow-ups.index')
                ->with('success', 'Invoice follow up updated successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating invoice follow up: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()
                ->with('error', 'Error updating invoice follow up: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $followUp = InvoiceFollowUp::findOrFail($id);
            $followUp->delete(); // Soft delete

            return redirect()->route('finance.invoice-follow-ups.index')
                ->with('success', 'Invoice follow up deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting invoice follow up: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:invoice_follow_ups,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = InvoiceFollowUp::whereIn('id', $request->ids)->delete(); // Soft delete
            
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} record(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting records: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getInvoiceFollowUps($invoiceId)
    {
        $followUps = InvoiceFollowUp::with(['creator', 'updater'])
            ->where('invoice_id', $invoiceId)
            ->orderBy('follow_up_date', 'desc')
            ->get();

        return response()->json($followUps);
    }

    public function createForInvoice($invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $followUpTypes = ['email', 'phone', 'visit', 'letter'];
        $statuses = ['pending', 'completed', 'cancelled'];

        return view('finance.invoice-follow-ups.create-for-invoice', compact('invoice', 'followUpTypes', 'statuses'));
    }

    public function storeForInvoice(Request $request, $invoiceId)
    {
        $validator = Validator::make($request->all(), [
            'follow_up_date' => 'required|date',
            'follow_up_type' => 'required|in:email,phone,visit,letter',
            'notes' => 'required|string',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $followUp = InvoiceFollowUp::create([
                'invoice_id' => $invoiceId,
                'follow_up_date' => $request->follow_up_date,
                'follow_up_type' => $request->follow_up_type,
                'notes' => $request->notes,
                'status' => $request->status,
                'created_by' => auth()->id(),
            ]);

            $followUp->invoice?->logActivity(
                'updated',
                'Invoice follow up created' . "\n" . $this->formatFollowUpLogNotes($followUp),
                auth()->id()
            );

            return redirect()->route('finance.invoices.show', $invoiceId)
                ->with('success', 'Invoice follow up created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creating invoice follow up: ' . $e->getMessage())
                ->withInput();
        }
    }

    private function formatFollowUpLogNotes(InvoiceFollowUp $followUp): string
    {
        return collect([
            'Date: ' . optional($followUp->follow_up_date)->format('d/M/Y'),
            'Type: ' . $followUp->follow_up_type_label,
            'Status: ' . ucfirst((string) $followUp->status),
            'Notes: ' . $followUp->notes,
        ])->filter()->implode("\n");
    }
}

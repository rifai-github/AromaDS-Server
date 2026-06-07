<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\Finance\InvoiceForm;
use App\Models\Finance\InvoiceFormDetail;
use App\Models\Finance\InvoiceFormRental;
use App\Models\Finance\InvoiceFormFile;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class InvoiceFormController extends Controller
{
    use AccessControlFilterTrait;

    public function index(Request $request)
    {
        $query = InvoiceForm::with(['contract:id,contract_number,customer_id', 'creator:id,name', 'updater:id,name']);
        $this->applyContractRelatedAccessControlFilter($query, Auth::user());

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('form_number', 'like', '%' . $search . '%')
                  ->orWhere('invoice_number', 'like', '%' . $search . '%')
                  ->orWhere('po_number', 'like', '%' . $search . '%')
                  ->orWhere('contract_number', 'like', '%' . $search . '%')
                  ->orWhere('company_name', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('form_type')) {
            $query->where('form_type', $request->form_type);
        }

        if ($request->filled('invoice_status')) {
            $query->where('invoice_status', $request->invoice_status);
        }

        if ($request->filled('company')) {
            $query->where('company_name', 'like', '%' . $request->company . '%');
        }

        if ($request->filled('contract')) {
            $query->where('contract_number', $request->contract);
        }

        if ($request->filled('date_from')) {
            $query->where('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('invoice_date', '<=', $request->date_to);
        }

        $invoiceForms = $query->orderBy('created_at', 'desc')->paginate(25);

        // Get filter options - optimized queries
        $contracts = $this->applyContractAccessControlFilter(
            Contract::select('id', 'contract_number', 'customer_id', 'created_by', 'marketing_id')
            ->where('contract_status', 'active')
            ->with('customer:id,company_name'),
            Auth::user()
        )->get();
        $customers = Customer::select('id', 'company_name')->get();
        $users = User::select('id', 'name')->get();

        return view('finance.invoice-forms.index', compact('invoiceForms', 'contracts', 'customers', 'users'));
    }

    public function create()
    {
        $contracts = $this->applyContractAccessControlFilter(
            Contract::select('id', 'contract_number', 'customer_id', 'created_by', 'marketing_id')
            ->where('contract_status', 'active')
            ->with('customer:id,company_name'),
            Auth::user()
        )->get();
        $customers = Customer::select('id', 'company_name')->get();
        $users = User::select('id', 'name')->get();

        return view('finance.invoice-forms.create', compact('contracts', 'customers', 'users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_number' => 'nullable|string|max:255',
            'po_number' => 'nullable|string|max:255',
            'contract_number' => 'nullable|string|max:255',
            'company_name' => 'required|string|max:255',
            'billing_address' => 'nullable|string',
            'period_invoice' => 'nullable|string|max:255',
            'invoice_status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'tax_obligation' => 'boolean',
            'tax_code' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'subtotal_after_discount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'total_paid' => 'nullable|numeric|min:0',
            'outstanding' => 'nullable|numeric|min:0',
            'npwp_number' => 'nullable|string|max:255',
            'tax_address' => 'nullable|string',
            'province_name' => 'nullable|string|max:255',
            'city_name' => 'nullable|string|max:255',
            'district_name' => 'nullable|string|max:255',
            'village_name' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'internal_notes' => 'nullable|string',
            'additional_notes' => 'nullable|string',
            'form_type' => 'required|in:invoice,credit_note,debit_note',
            'status' => 'required|in:draft,submitted,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $invoiceForm = InvoiceForm::create([
                'invoice_number' => $request->invoice_number,
                'po_number' => $request->po_number,
                'contract_number' => $request->contract_number,
                'company_name' => $request->company_name,
                'billing_address' => $request->billing_address,
                'period_invoice' => $request->period_invoice,
                'invoice_status' => $request->invoice_status,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'tax_obligation' => $request->tax_obligation ?? false,
                'tax_code' => $request->tax_code,
                'tax_number' => $request->tax_number,
                'subtotal' => $request->subtotal,
                'discount_amount' => $request->discount_amount ?? 0,
                'subtotal_after_discount' => $request->subtotal_after_discount ?? $request->subtotal,
                'tax_amount' => $request->tax_amount ?? 0,
                'grand_total' => $request->grand_total,
                'total_paid' => $request->total_paid ?? 0,
                'outstanding' => $request->outstanding ?? $request->grand_total,
                'npwp_number' => $request->npwp_number,
                'tax_address' => $request->tax_address,
                'province_name' => $request->province_name,
                'city_name' => $request->city_name,
                'district_name' => $request->district_name,
                'village_name' => $request->village_name,
                'postal_code' => $request->postal_code,
                'internal_notes' => $request->internal_notes,
                'additional_notes' => $request->additional_notes,
                'form_type' => $request->form_type,
                'status' => $request->status,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice form created successfully',
                'data' => $invoiceForm
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating invoice form: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(InvoiceForm $invoiceForm)
    {
        $invoiceForm = $this->applyContractRelatedAccessControlFilter(
            InvoiceForm::whereKey($invoiceForm->id),
            Auth::user()
        )->firstOrFail();

        $invoiceForm->load(['contract.customer', 'creator', 'updater', 'invoiceFormDetails', 'invoiceFormRentals', 'invoiceFormFiles.uploader']);

        return view('finance.invoice-forms.show', compact('invoiceForm'));
    }

    public function edit(InvoiceForm $invoiceForm)
    {
        $invoiceForm = $this->applyContractRelatedAccessControlFilter(
            InvoiceForm::whereKey($invoiceForm->id),
            Auth::user()
        )->firstOrFail();

        $contracts = $this->applyContractAccessControlFilter(
            Contract::select('id', 'contract_number', 'customer_id', 'created_by', 'marketing_id')
            ->where('contract_status', 'active')
            ->with('customer:id,company_name'),
            Auth::user()
        )->get();
        $customers = Customer::select('id', 'company_name')->get();
        $users = User::select('id', 'name')->get();

        return view('finance.invoice-forms.edit', compact('invoiceForm', 'contracts', 'customers', 'users'));
    }

    public function update(Request $request, InvoiceForm $invoiceForm)
    {
        $validator = Validator::make($request->all(), [
            'invoice_number' => 'nullable|string|max:255',
            'po_number' => 'nullable|string|max:255',
            'contract_number' => 'nullable|string|max:255',
            'company_name' => 'required|string|max:255',
            'billing_address' => 'nullable|string',
            'period_invoice' => 'nullable|string|max:255',
            'invoice_status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'tax_obligation' => 'boolean',
            'tax_code' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'subtotal' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'subtotal_after_discount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'total_paid' => 'nullable|numeric|min:0',
            'outstanding' => 'nullable|numeric|min:0',
            'npwp_number' => 'nullable|string|max:255',
            'tax_address' => 'nullable|string',
            'province_name' => 'nullable|string|max:255',
            'city_name' => 'nullable|string|max:255',
            'district_name' => 'nullable|string|max:255',
            'village_name' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'internal_notes' => 'nullable|string',
            'additional_notes' => 'nullable|string',
            'form_type' => 'required|in:invoice,credit_note,debit_note',
            'status' => 'required|in:draft,submitted,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $invoiceForm->update([
                'invoice_number' => $request->invoice_number,
                'po_number' => $request->po_number,
                'contract_number' => $request->contract_number,
                'company_name' => $request->company_name,
                'billing_address' => $request->billing_address,
                'period_invoice' => $request->period_invoice,
                'invoice_status' => $request->invoice_status,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'tax_obligation' => $request->tax_obligation ?? false,
                'tax_code' => $request->tax_code,
                'tax_number' => $request->tax_number,
                'subtotal' => $request->subtotal,
                'discount_amount' => $request->discount_amount ?? 0,
                'subtotal_after_discount' => $request->subtotal_after_discount ?? $request->subtotal,
                'tax_amount' => $request->tax_amount ?? 0,
                'grand_total' => $request->grand_total,
                'total_paid' => $request->total_paid ?? 0,
                'outstanding' => $request->outstanding ?? $request->grand_total,
                'npwp_number' => $request->npwp_number,
                'tax_address' => $request->tax_address,
                'province_name' => $request->province_name,
                'city_name' => $request->city_name,
                'district_name' => $request->district_name,
                'village_name' => $request->village_name,
                'postal_code' => $request->postal_code,
                'internal_notes' => $request->internal_notes,
                'additional_notes' => $request->additional_notes,
                'form_type' => $request->form_type,
                'status' => $request->status,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice form updated successfully',
                'data' => $invoiceForm
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating invoice form: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(InvoiceForm $invoiceForm)
    {
        try {
            $invoiceForm->delete(); // Soft delete

            return response()->json([
                'status' => 'success',
                'message' => 'Invoice form deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting invoice form: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:invoice_forms,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = InvoiceForm::whereIn('id', $request->ids)->delete(); // Soft delete

            return response()->json([
                'status' => 'success',
                'message' => "Successfully deleted {$count} record(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting records: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkApprove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:invoice_forms,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = InvoiceForm::whereIn('id', $request->ids)->update([
                'status' => 'approved',
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Successfully approved {$count} record(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error approving records: ' . $e->getMessage()
            ], 500);
        }
    }

    public function submit(InvoiceForm $invoiceForm)
    {
        try {
            $invoiceForm->submit();
            return response()->json([
                'status' => 'success',
                'message' => 'Invoice form submitted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error submitting invoice form: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approve(InvoiceForm $invoiceForm)
    {
        try {
            $invoiceForm->approve();
            return response()->json([
                'status' => 'success',
                'message' => 'Invoice form approved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error approving invoice form: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject(InvoiceForm $invoiceForm)
    {
        try {
            $invoiceForm->reject();
            return response()->json([
                'status' => 'success',
                'message' => 'Invoice form rejected successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error rejecting invoice form: ' . $e->getMessage()
            ], 500);
        }
    }

    public function draft(InvoiceForm $invoiceForm)
    {
        try {
            $invoiceForm->draft();
            return response()->json([
                'status' => 'success',
                'message' => 'Invoice form returned to draft successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error returning invoice form to draft: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $query = InvoiceForm::with(['contract', 'creator']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('form_number', 'like', '%' . $search . '%')
                  ->orWhere('invoice_number', 'like', '%' . $search . '%')
                  ->orWhere('company_name', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('form_type')) {
            $query->where('form_type', $request->form_type);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('invoice_date', [$request->date_from, $request->date_to]);
        }

        $invoiceForms = $query->orderBy('created_at', 'desc')->get();

        // Generate CSV
        $filename = 'invoice_forms_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($invoiceForms) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Form Number',
                'Invoice Number',
                'PO Number',
                'Contract Number',
                'Company Name',
                'Invoice Status',
                'Form Type',
                'Status',
                'Invoice Date',
                'Due Date',
                'Subtotal',
                'Tax Amount',
                'Grand Total',
                'Outstanding',
                'Created By',
                'Created Date'
            ]);

            foreach ($invoiceForms as $form) {
                fputcsv($file, [
                    $form->form_number,
                    $form->invoice_number ?? '',
                    $form->po_number ?? '',
                    $form->contract_number ?? '',
                    $form->company_name,
                    $form->invoice_status,
                    $form->form_type,
                    $form->status,
                    $form->invoice_date->format('Y-m-d'),
                    $form->due_date->format('Y-m-d'),
                    $form->subtotal,
                    $form->tax_amount,
                    $form->grand_total,
                    $form->outstanding,
                    $form->creator->name ?? '',
                    $form->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

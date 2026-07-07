<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\TaxInvoice;
use App\Models\TaxSetting;
use App\Models\Customer;
use App\Models\Contract;
use App\Models\BillingGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaxInvoiceController extends Controller
{
    use AccessControlFilterTrait;
    
    /**
     * Display a listing of tax invoices
     */
    public function index(Request $request)
    {
        $query = TaxInvoice::with(['customer', 'contract', 'billingGroup', 'createdBy']);

        // Apply access control filter (hierarchical data)
        // Default: Jika tidak set hirarki, hanya bisa lihat data sendiri
        // Filter by created_by and also by contract.created_by
        $user = Auth::user();
        if (!$this->hasUnrestrictedAccessControlData($user)) {
            $accessibleUserIds = $this->getAccessibleUserIds($user);
            $query->where(function($q) use ($accessibleUserIds) {
                $q->whereIn('created_by', $accessibleUserIds)
                  ->orWhereHas('contract', function($subQ) use ($accessibleUserIds) {
                      $subQ->whereIn('created_by', $accessibleUserIds)
                           ->orWhereIn('marketing_id', $accessibleUserIds);
                  });
            });
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by tax status
        if ($request->filled('tax_status')) {
            $query->where('tax_status', $request->tax_status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('invoice_date', [$request->start_date, $request->end_date]);
        }

        // Sort
        $sortField = $request->get('sort_field', 'invoice_date');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $taxInvoices = $query->paginateStd(25)->withQueryString();

        // Get filter options
        $customers = Customer::where('is_active', true)->get();
        $contracts = $this->applyContractAccessControlFilter(
            Contract::where('status', 'active'),
            Auth::user()
        )->get();
        $billingGroups = $this->applyContractRelatedAccessControlFilter(
            BillingGroup::where('is_active', true),
            Auth::user()
        )->get();
        $statuses = ['draft', 'pending', 'approved', 'rejected', 'cancelled'];
        $taxStatuses = ['exempt', 'applied', 'pending'];

        return view('tax.invoices.index', compact('taxInvoices', 'customers', 'contracts', 'billingGroups', 'statuses', 'taxStatuses'));
    }

    /**
     * Show the form for creating a new tax invoice
     */
    public function create()
    {
        $customers = Customer::where('is_active', true)->get();
        $contracts = $this->applyContractAccessControlFilter(
            Contract::where('status', 'active'),
            Auth::user()
        )->get();
        $billingGroups = $this->applyContractRelatedAccessControlFilter(
            BillingGroup::where('is_active', true),
            Auth::user()
        )->get();
        $taxSettings = TaxSetting::where('status', 'active')->get();

        return view('tax.invoices.create', compact('customers', 'contracts', 'billingGroups', 'taxSettings'));
    }

    /**
     * Store a newly created tax invoice
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_number' => 'required|string|max:255|unique:tax_invoices',
            'customer_id' => 'required|exists:customers,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'billing_group_id' => 'nullable|exists:billing_groups,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after:invoice_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'required|in:draft,pending,approved,rejected,cancelled',
            'tax_status' => 'required|in:exempt,applied,pending',
            'tax_code' => 'nullable|string|max:50',
            'tax_notes' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        if ($request->contract_id) {
            $canUseContract = $this->applyContractAccessControlFilter(Contract::query(), Auth::user())
                ->whereKey($request->contract_id)
                ->exists();

            if (!$canUseContract) {
                return redirect()->back()
                    ->with('error', 'Contract is outside your accessible data scope.')
                    ->withInput();
            }
        }

        if ($request->billing_group_id) {
            $canUseBillingGroup = $this->applyContractRelatedAccessControlFilter(BillingGroup::query(), Auth::user())
                ->whereKey($request->billing_group_id)
                ->exists();

            if (!$canUseBillingGroup) {
                return redirect()->back()
                    ->with('error', 'Billing group is outside your accessible data scope.')
                    ->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $taxInvoice = TaxInvoice::create([
                'invoice_number' => $request->invoice_number,
                'customer_id' => $request->customer_id,
                'contract_id' => $request->contract_id,
                'billing_group_id' => $request->billing_group_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'subtotal' => $request->subtotal,
                'tax_amount' => $request->tax_amount,
                'total_amount' => $request->total_amount,
                'status' => $request->status,
                'tax_status' => $request->tax_status,
                'tax_code' => $request->tax_code,
                'tax_notes' => $request->tax_notes,
                'notes' => $request->notes,
                'created_by' => Auth::id()
            ]);

            DB::commit();

            return redirect()->route('tax-invoices.index')
                ->with('success', 'Tax invoice created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error creating tax invoice: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified tax invoice
     */
    public function show(TaxInvoice $taxInvoice)
    {
        $taxInvoice = $this->applyContractRelatedAccessControlFilter(
            TaxInvoice::whereKey($taxInvoice->id),
            Auth::user()
        )->firstOrFail();

        $taxInvoice->load(['customer', 'contract', 'billingGroup', 'createdBy', 'updatedBy']);
        
        return view('tax.invoices.show', compact('taxInvoice'));
    }

    /**
     * Show the form for editing the specified tax invoice
     */
    public function edit(TaxInvoice $taxInvoice)
    {
        $customers = Customer::where('is_active', true)->get();
        $taxInvoice = $this->applyContractRelatedAccessControlFilter(
            TaxInvoice::whereKey($taxInvoice->id),
            Auth::user()
        )->firstOrFail();
        $contracts = $this->applyContractAccessControlFilter(
            Contract::where('status', 'active'),
            Auth::user()
        )->get();
        $billingGroups = $this->applyContractRelatedAccessControlFilter(
            BillingGroup::where('is_active', true),
            Auth::user()
        )->get();
        $taxSettings = TaxSetting::where('status', 'active')->get();

        return view('tax.invoices.edit', compact('taxInvoice', 'customers', 'contracts', 'billingGroups', 'taxSettings'));
    }

    /**
     * Update the specified tax invoice
     */
    public function update(Request $request, TaxInvoice $taxInvoice)
    {
        $validator = Validator::make($request->all(), [
            'invoice_number' => 'required|string|max:255|unique:tax_invoices,invoice_number,' . $taxInvoice->id,
            'customer_id' => 'required|exists:customers,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'billing_group_id' => 'nullable|exists:billing_groups,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after:invoice_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'required|in:draft,pending,approved,rejected,cancelled',
            'tax_status' => 'required|in:exempt,applied,pending',
            'tax_code' => 'nullable|string|max:50',
            'tax_notes' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $taxInvoice = $this->applyContractRelatedAccessControlFilter(
            TaxInvoice::whereKey($taxInvoice->id),
            Auth::user()
        )->firstOrFail();

        if ($request->contract_id) {
            $canUseContract = $this->applyContractAccessControlFilter(Contract::query(), Auth::user())
                ->whereKey($request->contract_id)
                ->exists();

            if (!$canUseContract) {
                return redirect()->back()
                    ->with('error', 'Contract is outside your accessible data scope.')
                    ->withInput();
            }
        }

        if ($request->billing_group_id) {
            $canUseBillingGroup = $this->applyContractRelatedAccessControlFilter(BillingGroup::query(), Auth::user())
                ->whereKey($request->billing_group_id)
                ->exists();

            if (!$canUseBillingGroup) {
                return redirect()->back()
                    ->with('error', 'Billing group is outside your accessible data scope.')
                    ->withInput();
            }
        }

        try {
            DB::beginTransaction();

            $taxInvoice->update([
                'invoice_number' => $request->invoice_number,
                'customer_id' => $request->customer_id,
                'contract_id' => $request->contract_id,
                'billing_group_id' => $request->billing_group_id,
                'invoice_date' => $request->invoice_date,
                'due_date' => $request->due_date,
                'subtotal' => $request->subtotal,
                'tax_amount' => $request->tax_amount,
                'total_amount' => $request->total_amount,
                'status' => $request->status,
                'tax_status' => $request->tax_status,
                'tax_code' => $request->tax_code,
                'tax_notes' => $request->tax_notes,
                'notes' => $request->notes,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return redirect()->route('tax-invoices.index')
                ->with('success', 'Tax invoice updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error updating tax invoice: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified tax invoice
     */
    public function destroy(TaxInvoice $taxInvoice)
    {
        try {
            $taxInvoice->delete();
            return redirect()->route('tax-invoices.index')
                ->with('success', 'Tax invoice deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting tax invoice: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete tax invoices
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:tax_invoices,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid selection'], 400);
        }

        try {
            $count = TaxInvoice::whereIn('id', $request->ids)->delete();
            return response()->json(['message' => "{$count} tax invoices deleted successfully"]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting tax invoices: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Apply e-Materai to tax invoice
     */
    public function applyEMaterai(Request $request, TaxInvoice $taxInvoice)
    {
        $validator = Validator::make($request->all(), [
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

            // Create e-Materai transaction
            $eMateraiTransaction = \App\Models\EMateraiTransaction::create([
                'tax_invoice_id' => $taxInvoice->id,
                'invoice_id' => $taxInvoice->id,
                'transaction_id' => 'EMT-' . time(),
                'status' => 'pending',
                'document_path' => $request->document_path,
                'notes' => $request->notes,
                'created_by' => Auth::id()
            ]);

            // Update tax invoice
            $taxInvoice->update([
                'is_e_materai_applied' => true,
                'e_materai_reference' => $eMateraiTransaction->transaction_id,
                'e_materai_applied_at' => now(),
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return redirect()->route('tax-invoices.show', $taxInvoice)
                ->with('success', 'e-Materai applied successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error applying e-Materai: ' . $e->getMessage());
        }
    }
}

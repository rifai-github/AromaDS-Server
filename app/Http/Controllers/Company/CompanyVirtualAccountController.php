<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyVirtualAccount;
use App\Models\BankPayment;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Http\Traits\ColumnFilterTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompanyVirtualAccountController extends Controller
{
    use ColumnFilterTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CompanyVirtualAccount::with(['bankPayment', 'company', 'createdBy', 'updatedBy']);

        // Apply Global Column Filters from UI
        $this->applyColumnFilters($query, null, [
            'account_number' => ['column' => 'account_number'],
            'account_name' => ['column' => 'account_name'],
            'customer__name' => ['relation' => 'customer', 'column' => 'name'],
            'bank__name' => ['relation' => 'bankPayment', 'column' => 'account_name'],
            'is_active' => ['column' => 'is_active', 'boolean' => true],
            'createdBy__name' => ['relation' => 'createdBy', 'column' => 'name'],
            'created_at' => ['column' => 'created_at', 'type' => 'date'],
            'updatedBy__name' => ['relation' => 'updatedBy', 'column' => 'name'],
            'updated_at' => ['column' => 'updated_at', 'type' => 'date'],
        ]);

        // Filter by company (Custom Filter)
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by bank (Custom Filter)
        if ($request->filled('bank_payment_id')) {
            $query->where('bank_payment_id', $request->bank_payment_id);
        }

        // Filter by status (Custom Filter or compatibility)
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by search (Global Search)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('company', function ($companyQuery) use ($search) {
                      $companyQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $virtualAccounts = $query->orderBy('created_at', 'desc')->paginateStd(25);
        $companies = Company::orderBy('name')->get(); 
        $customers = \App\Models\Customer::orderBy('name')->where('is_active', true)->get();
        // Only show ACTIVE bank payments in the dropdown
        $banks = BankPayment::with('bank')->where('is_active', true)->orderBy('account_name')->get();
        $defaultBank = BankPayment::where('is_default_va', true)->first();

        $statistics = [
            'total' => CompanyVirtualAccount::count(),
            'active' => CompanyVirtualAccount::where('is_active', true)->count(),
            'inactive' => CompanyVirtualAccount::where('is_active', false)->count(),
        ];

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $virtualAccounts,
                'statistics' => $statistics
            ]);
        }

        return view('company.company-virtual-accounts.index', compact('virtualAccounts', 'companies', 'banks', 'statistics', 'defaultBank', 'customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = \App\Models\Customer::orderBy('name')->where('is_active', true)->get();
        $companies = Company::orderBy('name')->get(); // Keep for legacy if needed
        $banks = BankPayment::with('bank')->orderBy('account_name')->get();

        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'customers' => $customers,
                'companies' => $companies,
                'banks' => $banks
            ]);
        }

        return redirect()->route('company.company-virtual-accounts.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'bank_payment_id' => 'required|exists:bank_payments,id',
            'account_number' => 'required|string|max:100', // Validate uniqueness manually after construction
            'account_name' => 'nullable|string|max:255',
            'daily_limit' => 'nullable|numeric|min:0',
            'monthly_limit' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Use the selected Bank Payment from the dropdown
            $bankPayment = BankPayment::findOrFail($request->bank_payment_id);
            
            if (!$bankPayment->is_active) {
                throw new \Exception("Bank Payment yang Anda pilih sedang tidak aktif.");
            }

            $customer = \App\Models\Customer::findOrFail($request->customer_id);

            // Construct Full VA Number: Prefix (from Bank) + Suffix (User Input)
            $prefix = $bankPayment->bank_va_number ?? '';
            // User input in 'account_number' is treated as suffix if prefix exists
            $suffix = $request->account_number;
            
            // Clean inputs to be safe
            $prefix = preg_replace('/\D/', '', $prefix);
            $suffix = preg_replace('/\D/', '', $suffix);

            $fullAccountNumber = $prefix . $suffix;
            // $totalLength = strlen($fullAccountNumber); // Not used for validation anymore
            
            // Expected length from DB is treated as SUFFIX LENGTH based on user requirement
            $expectedSuffixLength = $bankPayment->length ?? 0;
            $currentSuffixLength = strlen($suffix);

            // Validate Length
            if ($expectedSuffixLength > 0 && $currentSuffixLength != $expectedSuffixLength) {
                throw new \Exception("Validasi Gagal: Panjang Suffix VA harus {$expectedSuffixLength} digit. (Input Anda: {$currentSuffixLength} digit)");
            }

            // Validate Uniqueness manually
            $exists = CompanyVirtualAccount::where('account_number', $fullAccountNumber)
                ->where('bank_payment_id', $bankPayment->id)
                ->exists();
            
            if ($exists) {
                throw new \Exception("Validasi Gagal: VA Number {$fullAccountNumber} sudah digunakan untuk bank ini.");
            }

            $virtualAccount = CompanyVirtualAccount::create([
                'company_id' => null, // Deprecated/Optional
                'customer_id' => $request->customer_id,
                'bank_payment_id' => $bankPayment->id, // Use Default Bank ID
                'account_number' => $fullAccountNumber,
                'account_name' => $request->account_name ?: $customer->name, // Use Alias Name if provided
                'description' => $customer->customer_code . ' - ' . $customer->name,
                'daily_limit' => $request->daily_limit,
                'monthly_limit' => $request->monthly_limit,
                'is_active' => $request->is_active,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Company virtual account created successfully.',
                    'data' => $virtualAccount->load(['bankPayment', 'customer', 'createdBy'])
                ]);
            }

            return redirect()->route('company.company-virtual-accounts.index')
                ->with('success', 'Company virtual account created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create company virtual account: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Failed to create company virtual account: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CompanyVirtualAccount $companyVirtualAccount)
    {
        $companyVirtualAccount->load(['bankPayment', 'customer', 'createdBy', 'updatedBy']);

        $statistics = [
            'total_transactions' => 0, // Placeholder for transaction count
            'total_amount' => 0, // Placeholder for total amount
            'last_transaction_date' => null, // Placeholder for last transaction date
        ];

        // Return JSON for AJAX requests or as fallback
        if (request()->ajax() || !view()->exists('company.company-virtual-accounts.show')) {
            return response()->json([
                'status' => 'success',
                'data' => $companyVirtualAccount,
                'statistics' => $statistics
            ]);
        }

        return view('company.company-virtual-accounts.show', compact('companyVirtualAccount', 'statistics'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CompanyVirtualAccount $companyVirtualAccount)
    {
        $companyVirtualAccount->load(['bankPayment', 'customer', 'createdBy', 'updatedBy']);
        $customers = \App\Models\Customer::orderBy('name')->where('is_active', true)->get();
        $banks = BankPayment::with('bank')->where('is_active', true)->orderBy('account_name')->get();

        // Return JSON for AJAX requests or as fallback
        if (request()->ajax() || !view()->exists('company.company-virtual-accounts.edit')) {
            return response()->json([
                'status' => 'success',
                'data' => $companyVirtualAccount,
                'customers' => $customers,
                'banks' => $banks
            ]);
        }

        return view('company.company-virtual-accounts.edit', compact('companyVirtualAccount', 'companies', 'banks'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CompanyVirtualAccount $companyVirtualAccount)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'bank_payment_id' => 'required|exists:bank_payments,id',
            'account_number' => 'required|string|max:100|unique:company_virtual_accounts,account_number,' . $companyVirtualAccount->id,
            'account_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'daily_limit' => 'nullable|numeric|min:0',
            'monthly_limit' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $companyVirtualAccount->update([
                'customer_id' => $request->customer_id,
                'bank_payment_id' => $request->bank_payment_id,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name ?: $companyVirtualAccount->account_name,
                'description' => $request->description,
                'daily_limit' => $request->daily_limit,
                'monthly_limit' => $request->monthly_limit,
                'is_active' => $request->is_active,
                'notes' => $request->notes,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Company virtual account updated successfully.',
                    'data' => $companyVirtualAccount->load(['bankPayment', 'company', 'createdBy', 'updatedBy'])
                ]);
            }

            return redirect()->route('company.company-virtual-accounts.show', $companyVirtualAccount)
                ->with('success', 'Company virtual account updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update company virtual account: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Failed to update company virtual account: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CompanyVirtualAccount $companyVirtualAccount)
    {
        try {
            DB::beginTransaction();

            $companyVirtualAccount->delete();

            DB::commit();

            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Company virtual account deleted successfully.'
                ]);
            }

            return redirect()->route('company.company-virtual-accounts.index')
                ->with('success', 'Company virtual account deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete company virtual account: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Failed to delete company virtual account: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete virtual accounts.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:company_virtual_accounts,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = CompanyVirtualAccount::whereIn('id', $request->ids)->delete();

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Successfully deleted {$deletedCount} virtual account(s)."
                ]);
            }

            return redirect()->route('company.company-virtual-accounts.index')
                ->with('success', "Successfully deleted {$deletedCount} virtual account(s).");
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete virtual accounts: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Failed to delete virtual accounts: ' . $e->getMessage());
        }
    }

    /**
     * Toggle account status.
     */
    public function toggleStatus(CompanyVirtualAccount $companyVirtualAccount)
    {
        try {
            $companyVirtualAccount->update([
                'is_active' => !$companyVirtualAccount->is_active,
                'updated_by' => Auth::id(),
            ]);

            return back()->with('success', 'Account status updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update account status: ' . $e->getMessage());
        }
    }

    /**
     * Suspend account.
     */
    public function suspend(CompanyVirtualAccount $companyVirtualAccount)
    {
        try {
            $companyVirtualAccount->update([
                'is_active' => false,
                'updated_by' => Auth::id(),
            ]);

            return back()->with('success', 'Account suspended successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to suspend account: ' . $e->getMessage());
        }
    }

    /**
     * Activate account.
     */
    public function activate(CompanyVirtualAccount $companyVirtualAccount)
    {
        try {
            $companyVirtualAccount->update([
                'is_active' => true,
                'updated_by' => Auth::id(),
            ]);

            return back()->with('success', 'Account activated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to activate account: ' . $e->getMessage());
        }
    }

    /**
     * Get virtual accounts by company for API.
     */
    public function getVirtualAccountsByCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $virtualAccounts = CompanyVirtualAccount::where('company_id', $request->company_id)
            ->where('is_active', true)
            ->with(['bankPayment', 'company'])
            ->orderBy('account_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $virtualAccounts,
        ]);
    }

    /**
     * Get virtual accounts by bank for API.
     */
    public function getVirtualAccountsByBank(Request $request)
    {
        $request->validate([
            'bank_payment_id' => 'required|exists:bank_payments,id',
        ]);

        $virtualAccounts = CompanyVirtualAccount::where('bank_payment_id', $request->bank_payment_id)
            ->where('is_active', true)
            ->with(['bankPayment', 'company'])
            ->orderBy('account_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $virtualAccounts,
        ]);
    }

    /**
     * Get virtual account statistics for API.
     */
    public function getVirtualAccountStatistics()
    {
        $statistics = [
            'total' => CompanyVirtualAccount::count(),
            'active' => CompanyVirtualAccount::where('is_active', true)->count(),
            'inactive' => CompanyVirtualAccount::where('is_active', false)->count(),
            'accounts_by_company' => CompanyVirtualAccount::select('company_id', DB::raw('count(*) as count'))
                ->with('company')
                ->groupBy('company_id')
                ->get(),
            'accounts_by_bank' => CompanyVirtualAccount::select('bank_payment_id', DB::raw('count(*) as count'))
                ->with('bankPayment')
                ->groupBy('bank_payment_id')
                ->get(),
            'recent_accounts' => CompanyVirtualAccount::with(['bankPayment', 'company', 'createdBy'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }

    /**
     * Search virtual accounts for API.
     */
    public function searchVirtualAccounts(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2',
        ]);

        $virtualAccounts = CompanyVirtualAccount::where('is_active', true)
            ->where(function ($q) use ($request) {
                $q->where('account_number', 'like', "%{$request->search}%")
                  ->orWhere('account_name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            })
            ->with(['bankPayment', 'company'])
            ->orderBy('account_name')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $virtualAccounts,
        ]);
    }

    /**
     * Get current VA company code
     */
    public function getCompanyCode()
    {
        try {
            $companyCode = CompanyVirtualAccount::getCompanyCode();
            
            \Log::info('CompanyVirtualAccountController: getCompanyCode called', [
                'company_code' => $companyCode,
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'status' => 'success',
                'company_code' => $companyCode
            ]);
        } catch (\Exception $e) {
            \Log::error('CompanyVirtualAccountController: getCompanyCode error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get company code: ' . $e->getMessage(),
                'company_code' => '88997' // Fallback to default
            ], 500);
        }
    }

    /**
     * Set VA company code
     */
    public function setCompanyCode(Request $request)
    {
        $request->validate([
            'company_code' => 'required|string|size:5|regex:/^[0-9]{5}$/'
        ]);

        try {
            $companyId = 7; // Default company ID
            $companyCode = $request->company_code;
            
            // Save to CompanySetting
            CompanySetting::set(
                $companyId,
                'va_company_code',
                $companyCode,
                'Virtual Account Company Code (5 digits)'
            );
            
            \Log::info("VA Company Code updated to: {$companyCode} by user " . Auth::id());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Company code updated successfully',
                'company_code' => $companyCode
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to update VA company code: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update company code: ' . $e->getMessage()
            ], 500);
        }
    }
}

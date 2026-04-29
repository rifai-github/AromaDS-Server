<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CustomerTax;
use App\Models\Customer;
use App\Models\FinanceTaxCode;
use App\Models\TaxSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

use App\Http\Traits\ColumnFilterTrait;

class CustomerTaxController extends Controller
{
    use ColumnFilterTrait;

    public function getCustomerTaxInfo($customerId)
    {
        $tax = CustomerTax::where('customer_id', $customerId)
            ->where('tax_name', 'NPWP')
            ->latest()
            ->first();

        if (!$tax) {
            // Fallback to Master Customer profile (npwp column)
            $customer = Customer::find($customerId);
            if ($customer && !empty($customer->npwp)) {
                $tax = (object)[
                    'tax_name' => 'NPWP',
                    'tax_number' => $customer->npwp,
                    'tax_address' => $customer->npwp_address ?? $customer->address,
                    'nitku' => $customer->nitku ?? '000000',
                    'ppn_code' => $customer->ppn_code ?? '01',
                    'tax_rate' => $this->resolveTaxRateForCode($customer->ppn_code ?? '01'),
                ];
            } else {
                // Last fallback to any tax record
                $tax = CustomerTax::where('customer_id', $customerId)
                    ->latest()
                    ->first();
            }
        }

        return response()->json([
            'success' => true,
            'data' => $tax
        ]);
    }

    public function index(Request $request)
    {
        $query = CustomerTax::with(['customer', 'createdBy', 'updatedBy']);

        // Apply column filters via global filter row (table id: customerTaxesTable)
        $this->applyColumnFilters($query, 'customerTaxesTable', [
            // 0 => Checkbox (skip)
            1 => ['relation' => 'customer', 'column' => 'name'],
            2 => ['column' => 'tax_number'],
            3 => ['column' => 'tax_name'],
            4 => ['column' => 'tax_type'],
            5 => ['column' => 'tax_rate'],
            6 => ['column' => 'effective_date'],
            7 => ['column' => 'is_active', 'boolean' => true],
            8 => ['relation' => 'createdBy', 'column' => 'name'],
            9 => ['column' => 'updated_at'],
        ]);

        // Filter by customer (legacy or specific usage)
        if ($request->filled('customer_id') && !$request->has('filter.customer__name')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by tax type (legacy)
        if ($request->filled('tax_type') && !$request->has('filter.tax_type')) {
            $query->where('tax_type', $request->tax_type);
        }

        // Filter by status (legacy)
        if ($request->filled('status') && !$request->has('filter.is_active')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('effective_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('effective_date', '<=', $request->end_date);
        }

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tax_number', 'like', "%{$search}%")
                  ->orWhere('tax_name', 'like', "%{$search}%")
                  ->orWhere('label', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $customerTaxes = $query->orderBy('created_at', 'desc')->paginate(15);
        $customers = Customer::orderBy('name')->get();

        $statistics = [
            'total' => CustomerTax::count(),
            'active' => CustomerTax::where('status', 'active')->count(),
            'inactive' => CustomerTax::where('status', 'inactive')->count(),
        ];

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'customerTaxes' => $customerTaxes->items(),
                'customers' => $customers,
                'statistics' => $statistics
            ]);
        }

        return view('company.customer-taxes.index', compact('customerTaxes', 'customers', 'statistics'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->get();
        
        return view('company.customer-taxes.create', compact('customers'));
    }

    public function store(Request $request)
    {
        // 1. Determine Validation Rules based on Tax Name (Identity Type)
        $taxName = $request->tax_name;
        
        $rules = [
            'customer_id' => 'required|exists:customers,id',
            'label' => 'nullable|string|max:255',
            'tax_name' => 'required|in:NPWP,NIK,NITKU,KITAS/PASSPORT/KTP WNA,OTHER',
            'tax_type' => [
                'required',
                'string',
                'max:50',
                Rule::exists('finance_tax_codes', 'code')->where('is_active', true),
            ],
            'ppn_code' => 'nullable|string|max:10', // New field
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'tax_address' => 'nullable|string',
        ];

        // Specific rules for Tax Number & NITKU
        if ($taxName === 'NIK') {
            // NIK: 16 digits, NITKU auto 000000
            $rules['tax_number'] = ['required', 'string', 'regex:/^[0-9]+$/', 'size:16'];
        } elseif ($taxName === 'NPWP') {
            // NPWP: 15 or 16 digits
            $rules['tax_number'] = ['required', 'string', 'regex:/^[0-9]+$/', 'min:15', 'max:16'];
            // NPWP must have NITKU (Default 000000)
            $rules['nitku'] = ['nullable', 'string', 'regex:/^[0-9]+$/', 'size:6'];
        } else {
            // Others
            $rules['tax_number'] = ['required', 'string', 'max:30'];
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();
            
            $taxNumber = $request->tax_number;
            $taxRate = $this->resolveTaxRateForCode($request->tax_type);
            
            // NITKU Logic:
            // 1. If NIK -> auto 000000
            // 2. If NPWP and NITKU empty -> auto 000000
            $nitku = $request->nitku;
            if ($taxName === 'NIK') {
                $nitku = '000000';
            } elseif ($taxName === 'NPWP' || $taxName === 'NITKU') {
                if (empty($nitku)) {
                    $nitku = '000000';
                }
            } else {
                // For others, if empty, default to 000000 for consistency or keep null
                if (empty($nitku)) {
                    $nitku = '000000';
                }
            }

            // 2. Uniqueness Check (22 Digits: TaxNumber + NITKU) for Standard IDs
            if (in_array($taxName, ['NPWP', 'NIK', 'NITKU'])) {
                // Check in customer_tax_settings
                $exists = CustomerTax::where('tax_number', $taxNumber)
                    ->where('nitku', $nitku)
                    ->whereNull('deleted_at')
                    ->exists();
                
                if ($exists) {
                    // Get who owns it
                    $owner = CustomerTax::with('customer')
                        ->where('tax_number', $taxNumber)
                        ->where('nitku', $nitku)
                        ->first();
                    $ownerName = $owner && $owner->customer ? $owner->customer->name : 'Unknown';
                    
                    throw new \Exception("NPWP/NITKU ini sudah ada atas nama customer: $ownerName.");
                }
            }

            // 3. Check for Overlapping Periods (Same Customer + Same Tax Type + Same Tax Number + Same NITKU)
            $overlapping = CustomerTax::where('customer_id', $request->customer_id)
                ->where('tax_type', $request->tax_type)
                ->where('tax_number', $taxNumber)
                ->where('nitku', $nitku)
                ->where(function ($query) use ($request) {
                    $query->where(function ($q) use ($request) {
                        $q->where('effective_date', '<=', $request->effective_date)
                          ->where(function ($q2) use ($request) {
                              $q2->whereNull('expiry_date')
                                 ->orWhere('expiry_date', '>=', $request->effective_date);
                          });
                    })->orWhere(function ($q) use ($request) {
                        $q->where('effective_date', '<=', $request->expiry_date ?? '9999-12-31')
                          ->where(function ($q2) use ($request) {
                              $q2->whereNull('expiry_date')
                                 ->orWhere('expiry_date', '>=', $request->effective_date);
                          });
                    });
                })
                ->exists();

            if ($overlapping) {
                throw new \Exception('Sudah ada pengaturan pajak yang tumpang tindih untuk periode yang sama.');
            }

            $customerTax = CustomerTax::create([
                'customer_id' => $request->customer_id,
                'label' => $request->label,
                'tax_number' => $taxNumber,
                'nitku' => $nitku,
                'tax_name' => $taxName,
                'tax_address' => $request->tax_address,
                'tax_type' => $request->tax_type,
                'ppn_code' => $request->ppn_code ?: $request->tax_type,
                'tax_rate' => $taxRate,
                'effective_date' => $request->effective_date,
                'expiry_date' => $request->expiry_date,
                'description' => $request->description,
                'status' => $request->status,
                'is_active' => $request->status === 'active',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer tax settings created successfully.',
                    'data' => $customerTax->load(['customer', 'createdBy'])
                ]);
            }

            return redirect()->route('company.customer-taxes.show', $customerTax)
                ->with('success', 'Pengaturan pajak pelanggan berhasil dibuat.');
                
        } catch (\Exception $e) {
            DB::rollback();
            if ($request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(CustomerTax $customerTax)
    {
        $customerTax->load(['customer', 'createdBy', 'updatedBy']);
        
        // Return JSON for AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $customerTax
            ]);
        }
        
        return view('company.customer-taxes.show', compact('customerTax'));
    }

    public function edit(CustomerTax $customerTax)
    {
        $customerTax->load(['customer', 'createdBy', 'updatedBy']);
        $customers = Customer::orderBy('name')->get();
        
        // Return JSON for AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $customerTax,
                'customers' => $customers
            ]);
        }
        
        return view('company.customer-taxes.edit', compact('customerTax', 'customers'));
    }

    public function update(Request $request, CustomerTax $customerTax)
    {
        // 1. Determine Validation Rules
        $taxName = $request->tax_name;
        
        $rules = [
            'customer_id' => 'required|exists:customers,id',
            'tax_name' => 'required|in:NPWP,NIK,NITKU,KITAS/PASSPORT/KTP WNA,OTHER',
            'tax_type' => [
                'required',
                'string',
                'max:50',
                Rule::exists('finance_tax_codes', 'code')->where('is_active', true),
            ],
            'ppn_code' => 'nullable|string|max:10',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'tax_address' => 'nullable|string',
        ];

        if ($taxName === 'NIK') {
             $rules['tax_number'] = ['required', 'string', 'regex:/^[0-9]+$/', 'size:16'];
        } elseif ($taxName === 'NPWP' || $taxName === 'NITKU') {
             $rules['tax_number'] = ['required', 'string', 'regex:/^[0-9]+$/', 'min:15', 'max:16'];
             $rules['nitku'] = ['nullable', 'string', 'regex:/^[0-9]+$/', 'size:6'];
        } else {
             $rules['tax_number'] = ['required', 'string', 'max:30'];
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $taxNumber = $request->tax_number;
            $taxRate = $this->resolveTaxRateForCode($request->tax_type);
            $nitku = $request->nitku;
            if ($taxName === 'NIK') {
                $nitku = '000000';
            } elseif ($taxName === 'NPWP' || $taxName === 'NITKU') {
                if (empty($nitku)) {
                    $nitku = '000000';
                }
            } else {
                if (empty($nitku)) {
                    $nitku = '000000';
                }
            }

            // 2. Uniqueness Check (ignoring self)
            if (in_array($taxName, ['NPWP', 'NIK', 'NITKU'])) {
                // Check in customer_taxes
                $exists = CustomerTax::where('tax_number', $taxNumber)
                    ->where('nitku', $nitku)
                    ->where('id', '!=', $customerTax->id)
                    ->whereNull('deleted_at')
                    ->exists();
                
                if ($exists) {
                     $owner = CustomerTax::with('customer')
                        ->where('tax_number', $taxNumber)
                        ->where('nitku', $nitku)
                        ->first();
                    $ownerName = $owner && $owner->customer ? $owner->customer->name : 'Unknown';
                    throw new \Exception("NPWP/NITKU ini sudah ada atas nama customer: $ownerName.");
                }
            }

            // 3. Overlap Check
            $periodChanged = (
                $customerTax->customer_id != $request->customer_id ||
                $customerTax->tax_type != $request->tax_type ||
                $customerTax->effective_date != $request->effective_date ||
                $customerTax->expiry_date != $request->expiry_date
            );

            if ($periodChanged) {
                $overlapping = CustomerTax::where('customer_id', $request->customer_id)
                    ->where('tax_type', $request->tax_type)
                    ->where('tax_number', $taxNumber)
                    ->where('nitku', $nitku)
                    ->where('id', '!=', $customerTax->id)
                    ->where('is_active', true)
                    ->where(function ($query) use ($request) {
                        $query->where(function ($q) use ($request) {
                            $q->where('effective_date', '<', $request->expiry_date ?? '9999-12-31')
                              ->where(function ($q2) use ($request) {
                                  $q2->whereNull('expiry_date')
                                     ->orWhere('expiry_date', '>', $request->effective_date);
                              });
                        });
                    })
                    ->exists();

                if ($overlapping) {
                    throw new \Exception('Sudah ada pengaturan pajak yang tumpang tindih untuk periode yang sama.');
                }
            }

            $customerTax->update([
                'customer_id' => $request->customer_id,
                'label' => $request->label,
                'tax_number' => $taxNumber,
                'nitku' => $nitku,
                'tax_name' => $taxName,
                'tax_address' => $request->tax_address,
                'tax_type' => $request->tax_type,
                'ppn_code' => $request->ppn_code ?: $request->tax_type,
                'tax_rate' => $taxRate,
                'effective_date' => $request->effective_date,
                'expiry_date' => $request->expiry_date,
                'description' => $request->description,
                'status' => $request->status,
                'is_active' => $request->status === 'active',
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer tax settings updated successfully.',
                    'data' => $customerTax->load(['customer', 'createdBy', 'updatedBy'])
                ]);
            }

            return redirect()->route('company.customer-taxes.show', $customerTax)
                ->with('success', 'Pengaturan pajak pelanggan berhasil diperbarui.');
                
        } catch (\Exception $e) {
            DB::rollback();
            if ($request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(CustomerTax $customerTax)
    {
        try {
            $customerTax->delete();
            
            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer tax setting deleted successfully.'
                ]);
            }
            
            return redirect()->route('company.customer-taxes.index')
                ->with('success', 'Pengaturan pajak pelanggan berhasil dihapus.');
        } catch (\Exception $e) {
            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete customer tax setting: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getCustomerTaxes(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'tax_type' => 'nullable|in:01,02,03,04,05,06,07,08,09',
        ]);

        $query = CustomerTax::where('customer_id', $request->customer_id)
            ->where('status', 'active')
            ->where('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now());
            });

        if ($request->filled('tax_type')) {
            $query->where('tax_type', $request->tax_type);
        }

        $customerTaxes = $query->orderBy('effective_date', 'desc')->get();

        return response()->json($customerTaxes);
    }

    public function getActiveTaxRate(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'tax_type' => 'required|in:01,02,03,04,05,06,07,08,09',
        ]);

        $customerTax = CustomerTax::where('customer_id', $request->customer_id)
            ->where('tax_type', $request->tax_type)
            ->where('status', 'active')
            ->where('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now());
            })
            ->orderBy('effective_date', 'desc')
            ->first();

        return response()->json($customerTax);
    }

    public function bulkCreate(Request $request)
    {
        $request->validate([
            'customer_taxes' => 'required|array|min:1',
            'customer_taxes.*.customer_id' => 'required|exists:customers,id',
            'customer_taxes.*.tax_type' => 'required|in:ppn,pph,ppnbm',
            'customer_taxes.*.tax_rate' => 'required|numeric|min:0|max:100',
            'customer_taxes.*.effective_date' => 'required|date|before_or_equal:today',
            'customer_taxes.*.expiry_date' => 'nullable|date|after:effective_date',
            'customer_taxes.*.description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $createdCount = 0;

            foreach ($request->customer_taxes as $taxData) {
                // Check for overlapping tax settings
                $overlapping = CustomerTax::where('customer_id', $taxData['customer_id'])
                    ->where('tax_type', $taxData['tax_type'])
                    ->where(function ($query) use ($taxData) {
                        $query->where(function ($q) use ($taxData) {
                            $q->where('effective_date', '<=', $taxData['effective_date'])
                              ->where(function ($q2) use ($taxData) {
                                  $q2->whereNull('expiry_date')
                                     ->orWhere('expiry_date', '>=', $taxData['effective_date']);
                              });
                        })->orWhere(function ($q) use ($taxData) {
                            $q->where('effective_date', '<=', $taxData['expiry_date'] ?? '9999-12-31')
                              ->where(function ($q2) use ($taxData) {
                                  $q2->whereNull('expiry_date')
                                     ->orWhere('expiry_date', '>=', $taxData['effective_date']);
                              });
                        });
                    })
                    ->exists();

                if (!$overlapping) {
                    CustomerTax::create([
                        'customer_id' => $taxData['customer_id'],
                        'tax_type' => $taxData['tax_type'],
                        'tax_rate' => $taxData['tax_rate'],
                        'effective_date' => $taxData['effective_date'],
                        'expiry_date' => $taxData['expiry_date'] ?? null,
                        'description' => $taxData['description'] ?? null,
                        'status' => 'active',
                        'created_by' => Auth::id(),
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();

            return back()->with('success', "Berhasil membuat {$createdCount} pengaturan pajak pelanggan.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $customerTaxes = CustomerTax::with(['customer'])
            ->orderBy('effective_date', 'desc')
            ->get();

        // Here you would implement the actual Excel export logic
        // For now, we'll just return a success message

        return back()->with('success', "Berhasil mengekspor {$customerTaxes->count()} pengaturan pajak pelanggan.");
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Here you would implement the actual file import logic
            // For now, we'll just return a success message
            $importedCount = 0;

            // Process the uploaded file
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                // Process CSV/Excel file and create customer taxes
                // This is a placeholder for the actual import logic
                $importedCount = 10; // Example count
            }

            DB::commit();

            return back()->with('success', "Berhasil mengimpor {$importedCount} pengaturan pajak pelanggan.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getStatistics()
    {
        $totalCustomerTaxes = CustomerTax::count();
        $activeCustomerTaxes = CustomerTax::where('status', 'active')->count();
        $expiredCustomerTaxes = CustomerTax::where('expiry_date', '<', now())->count();
        $ppnTaxes = CustomerTax::where('tax_type', 'ppn')->count();
        $pphTaxes = CustomerTax::where('tax_type', 'pph')->count();
        $ppnbmTaxes = CustomerTax::where('tax_type', 'ppnbm')->count();

        return response()->json([
            'total_customer_taxes' => $totalCustomerTaxes,
            'active_customer_taxes' => $activeCustomerTaxes,
            'expired_customer_taxes' => $expiredCustomerTaxes,
            'ppn_taxes' => $ppnTaxes,
            'pph_taxes' => $pphTaxes,
            'ppnbm_taxes' => $ppnbmTaxes,
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:customer_tax_settings,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = CustomerTax::whereIn('id', $request->ids)->delete();

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Successfully deleted {$deletedCount} customer tax setting(s)."
                ]);
            }

            return redirect()->route('company.customer-taxes.index')
                ->with('success', "Successfully deleted {$deletedCount} customer tax setting(s).");
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete customer tax settings: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Failed to delete customer tax settings: ' . $e->getMessage());
        }
    }

    public function toggleStatus(CustomerTax $customerTax)
    {
        try {
            $newStatus = $customerTax->status === 'active' ? 'inactive' : 'active';
            
            $customerTax->update(['status' => $newStatus]);

            return back()->with('success', "Status pengaturan pajak berhasil diubah menjadi {$newStatus}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function resolveTaxRateForCode(?string $taxCode): float
    {
        $defaultVatSetting = TaxSetting::getDefaultPpnSetting();
        $defaultRate = (float) ($defaultVatSetting?->tax_rate ?? 0);

        if (! $taxCode) {
            return $defaultRate;
        }

        $financeTaxCode = FinanceTaxCode::query()
            ->where('code', $taxCode)
            ->where('is_active', true)
            ->first();

        if ($financeTaxCode && $financeTaxCode->hasZeroTaxPrint()) {
            return 0.0;
        }

        return $defaultRate;
    }

    /**
     * Get active tax number for a customer (for invoice snapshot)
     * Per report-mom5.md line 102-108: Historical data capture
     */
    public function getActiveTaxNumber($customerId)
    {
        try {
            $today = now();
            $customer = Customer::findOrFail($customerId);
            
            $activeTax = CustomerTax::where('customer_id', $customerId)
                ->where('is_active', true)
                ->where('effective_date', '<=', $today)
                ->where(function ($query) use ($today) {
                    $query->whereNull('expiry_date')
                        ->orWhere('expiry_date', '>=', $today);
                })
                ->orderBy('effective_date', 'desc')
                ->first();
            
            if ($activeTax) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'tax_number' => $activeTax->tax_number,
                        'npwp_number' => $activeTax->tax_number,
                        'tax_name' => $activeTax->tax_name,
                        'tax_type' => $activeTax->tax_type,
                        'tax_address' => $activeTax->tax_address,
                        'ppn_code' => $activeTax->ppn_code ?: ($customer->ppn_code ?: '01'),
                        'effective_date' => $activeTax->effective_date,
                        'end_date' => $activeTax->expiry_date
                    ]
                ]);
            }

            if (!empty($customer->npwp) || !empty($customer->ppn_code)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'tax_number' => $customer->npwp,
                        'npwp_number' => $customer->npwp,
                        'tax_name' => 'NPWP',
                        'tax_type' => $customer->tax_code,
                        'tax_address' => $customer->npwp_address ?: $customer->address,
                        'ppn_code' => $customer->ppn_code ?: '01',
                        'effective_date' => null,
                        'end_date' => null,
                    ]
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No active tax number found for this customer'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\Customer;
use App\Models\CustomerTax;
use App\Models\FinanceTaxCode;
use App\Models\TaxSetting;
use App\Services\Company\CustomerIdentifierUniquenessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerTaxController extends Controller
{
    use ColumnFilterTrait;

    public function __construct(private CustomerIdentifierUniquenessService $customerIdentifierUniqueness) {}

    public function getCustomerTaxInfo($customerId)
    {
        $tax = CustomerTax::where('customer_id', $customerId)
            ->where('tax_name', 'NPWP')
            ->latest()
            ->first();

        if (! $tax) {
            // Fallback to Master Customer profile (npwp column)
            $customer = Customer::find($customerId);
            if ($customer && ! empty($customer->npwp)) {
                $tax = (object) [
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
            'data' => $tax,
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
        if ($request->filled('customer_id') && ! $request->has('filter.customer__name')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by tax type (legacy)
        if ($request->filled('tax_type') && ! $request->has('filter.tax_type')) {
            $query->where('tax_type', $request->tax_type);
        }

        // Filter by status (legacy)
        if ($request->filled('status') && ! $request->has('filter.is_active')) {
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

        // UAT 17 Nov: sort header global (?sort=&direction=) dulu diabaikan karena
        // urutan di-hardcode created_at desc. Kolom di-whitelist sesuai data-column view.
        $this->applyCustomerTaxSort($query, $request);

        $customerTaxes = $query->paginateStd(25);
        // Dropdown create hanya butuh id + nama; dulu semua kolom semua customer ikut di-embed.
        $customers = Customer::orderBy('name')->get(['id', 'name']);
        $defaultVatSetting = TaxSetting::getDefaultPpnSetting();
        $financeTaxCodes = FinanceTaxCode::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
        $financeTaxCodeOptions = $financeTaxCodes->map(function (FinanceTaxCode $taxCode) {
            return [
                'code' => $taxCode->code,
                'description' => $taxCode->description,
                'customer_status' => $taxCode->customer_status,
                'zero_tax' => $taxCode->hasZeroTaxPrint(),
            ];
        })->values();

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
                'statistics' => $statistics,
            ]);
        }

        return view('company.customer-taxes.index', compact(
            'customerTaxes',
            'customers',
            'statistics',
            'defaultVatSetting',
            'financeTaxCodes',
            'financeTaxCodeOptions'
        ));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->get();
        $defaultVatSetting = TaxSetting::getDefaultPpnSetting();
        $financeTaxCodes = FinanceTaxCode::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        return view('company.customer-taxes.create', compact('customers', 'defaultVatSetting', 'financeTaxCodes'));
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
            'contract_date' => 'nullable|date',
        ];

        // Specific rules for Tax Number & NITKU
        if ($taxName === 'NIK') {
            // NIK: 16 digits, NITKU auto 000000
            $rules['tax_number'] = ['required', 'string', 'regex:/^[0-9]+$/', 'size:16'];
        } elseif ($taxName === 'NPWP') {
            $rules['tax_number'] = ['required', 'string', 'regex:/^[0-9]+$/', 'min:15', 'max:16'];
            $rules['nitku'] = ['nullable', 'string', 'regex:/^[0-9]+$/', 'size:6'];
            // MOM 19 Nov 2026 p5: satu NPWP bisa punya beberapa NITKU (alamat berbeda); tiap
            // NITKU tambahan wajib beralamat sendiri walau alamat NPWP sudah diisi.
            $rules['additional_nitkus'] = ['nullable', 'array'];
            $rules['additional_nitkus.*.nitku'] = ['required', 'string', 'regex:/^[0-9]{6}$/'];
            $rules['additional_nitkus.*.address'] = ['required', 'string'];
        } elseif ($taxName === 'NITKU') {
            // NITKU stores the full 22-digit identifier: 16-digit parent NPWP + 6-digit branch code.
            $rules['tax_number'] = ['required', 'string', 'regex:/^[0-9]+$/', 'size:22'];
        } else {
            // Others
            $rules['tax_number'] = ['required', 'string', 'max:30'];
        }

        $request->validate($rules);
        $this->validateNitkuParentNpwp($taxName, $request->tax_number, (int) $request->customer_id);

        if ($request->filled('contract_date') && $request->effective_date > $request->contract_date) {
            return $this->respondWithError($request, 'Effective Date pajak tidak boleh melewati Contract Date.', 'effective_date');
        }

        try {
            DB::beginTransaction();

            $taxNumber = $request->tax_number;
            $taxRate = $this->resolveTaxRateForCode($request->tax_type);

            $nitku = $this->resolveNitkuBranchCode($taxName, $taxNumber, $request->nitku);

            if ($taxName === 'NPWP') {
                $this->customerIdentifierUniqueness->validateUniqueNpwp($taxNumber, (int) $request->customer_id);
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

            $additionalCount = $this->storeAdditionalNitkus($request, $customerTax);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer tax settings created successfully.'.($additionalCount ? " {$additionalCount} NITKU tambahan ikut disimpan." : ''),
                    'data' => $customerTax->load(['customer', 'createdBy']),
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
                'data' => $customerTax,
            ]);
        }

        return view('company.customer-taxes.show', compact('customerTax'));
    }

    public function edit(CustomerTax $customerTax)
    {
        $customerTax->load(['customer', 'createdBy', 'updatedBy']);
        // Customer tidak bisa diganti saat edit (UAT 17 Nov): cukup kirim customer milik baris ini.
        // Dulu seluruh customer beserta semua kolomnya dikirim, sehingga modal edit lambat terbuka.
        $customers = Customer::whereKey($customerTax->customer_id)->get(['id', 'name']);

        // Return JSON for AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $customerTax,
                'customers' => $customers,
            ]);
        }

        return view('company.customer-taxes.edit', compact('customerTax', 'customers'));
    }

    public function update(Request $request, CustomerTax $customerTax)
    {
        // Customer pemilik setting pajak dikunci: edit tidak boleh memindahkan setting ini ke
        // customer lain (dulu dropdown customer tetap bisa diganti saat edit).
        $request->merge(['customer_id' => $customerTax->customer_id]);

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
        } elseif ($taxName === 'NPWP') {
            $rules['tax_number'] = ['required', 'string', 'regex:/^[0-9]+$/', 'min:15', 'max:16'];
            $rules['nitku'] = ['nullable', 'string', 'regex:/^[0-9]+$/', 'size:6'];
            // MOM 19 Nov 2026 p5: satu NPWP bisa punya beberapa NITKU (alamat berbeda); tiap
            // NITKU tambahan wajib beralamat sendiri walau alamat NPWP sudah diisi.
            $rules['additional_nitkus'] = ['nullable', 'array'];
            $rules['additional_nitkus.*.nitku'] = ['required', 'string', 'regex:/^[0-9]{6}$/'];
            $rules['additional_nitkus.*.address'] = ['required', 'string'];
        } elseif ($taxName === 'NITKU') {
            $rules['tax_number'] = ['required', 'string', 'regex:/^[0-9]+$/', 'size:22'];
        } else {
            $rules['tax_number'] = ['required', 'string', 'max:30'];
        }

        $request->validate($rules);
        $this->validateNitkuParentNpwp($taxName, $request->tax_number, (int) $request->customer_id);

        try {
            DB::beginTransaction();

            $taxNumber = $request->tax_number;
            $taxRate = $this->resolveTaxRateForCode($request->tax_type);
            $nitku = $this->resolveNitkuBranchCode($taxName, $taxNumber, $request->nitku);

            if ($taxName === 'NPWP') {
                $this->customerIdentifierUniqueness->validateUniqueNpwp($taxNumber, (int) $request->customer_id);
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

            $additionalCount = $this->storeAdditionalNitkus($request, $customerTax->fresh());

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer tax settings updated successfully.'.($additionalCount ? " {$additionalCount} NITKU tambahan ikut disimpan." : ''),
                    'data' => $customerTax->load(['customer', 'createdBy', 'updatedBy']),
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

    /**
     * MOM 19 Nov 2026 p5: NITKU tambahan untuk NPWP yang sama disimpan sebagai baris Customer Tax
     * tersendiri (kunci unik tetap NPWP + NITKU), mewarisi kode PPN/tanggal/status baris utama.
     *
     * @return int jumlah NITKU tambahan yang dibuat
     */
    private function storeAdditionalNitkus(Request $request, CustomerTax $base): int
    {
        if ($base->tax_name !== 'NPWP') {
            return 0;
        }

        $rows = collect($request->input('additional_nitkus', []))
            ->map(fn ($row) => [
                'nitku' => preg_replace('/\D/', '', (string) ($row['nitku'] ?? '')),
                'address' => trim((string) ($row['address'] ?? '')),
            ])
            ->filter(fn ($row) => $row['nitku'] !== '');

        $seen = [(string) $base->nitku];

        foreach ($rows as $row) {
            if (in_array($row['nitku'], $seen, true)) {
                throw new \Exception("NITKU {$row['nitku']} dimasukkan lebih dari sekali untuk NPWP {$base->tax_number}.");
            }
            $seen[] = $row['nitku'];

            $owner = CustomerTax::with('customer')
                ->where('tax_number', $base->tax_number)
                ->where('nitku', $row['nitku'])
                ->whereNull('deleted_at')
                ->first();

            if ($owner) {
                throw new \Exception("NPWP {$base->tax_number} NITKU {$row['nitku']} sudah ada atas nama customer: ".($owner->customer->name ?? 'Unknown').'.');
            }

            CustomerTax::create([
                'customer_id' => $base->customer_id,
                'label' => $base->label,
                'tax_number' => $base->tax_number,
                'nitku' => $row['nitku'],
                'tax_name' => $base->tax_name,
                'tax_address' => $row['address'],
                'tax_type' => $base->tax_type,
                'ppn_code' => $base->ppn_code,
                'tax_rate' => $base->tax_rate,
                'effective_date' => $base->effective_date,
                'expiry_date' => $base->expiry_date,
                'description' => $base->description,
                'status' => $base->status,
                'is_active' => $base->status === 'active',
                'created_by' => Auth::id(),
            ]);
        }

        return count($seen) - 1;
    }

    public function destroy(CustomerTax $customerTax)
    {
        try {
            // UAT 17 Nov: hapus = nonaktifkan (status inactive), bukan soft delete,
            // supaya baris tetap tampil di list dengan status Inactive.
            $customerTax->update([
                'status' => 'inactive',
                'is_active' => false,
                'updated_by' => Auth::id(),
            ]);

            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pengaturan pajak customer berhasil dinonaktifkan.',
                ]);
            }

            return redirect()->route('company.customer-taxes.index')
                ->with('success', 'Pengaturan pajak customer berhasil dinonaktifkan.');
        } catch (\Exception $e) {
            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menonaktifkan pengaturan pajak customer: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
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

                if (! $overlapping) {
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

            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
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

            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
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
            'ids.*' => 'exists:customer_tax_settings,id',
        ]);

        try {
            DB::beginTransaction();

            // UAT 17 Nov: bulk hapus = nonaktifkan (status inactive), bukan soft delete.
            $deletedCount = CustomerTax::whereIn('id', $request->ids)->update([
                'status' => 'inactive',
                'is_active' => false,
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "{$deletedCount} pengaturan pajak customer berhasil dinonaktifkan.",
                ]);
            }

            return redirect()->route('company.customer-taxes.index')
                ->with('success', "{$deletedCount} pengaturan pajak customer berhasil dinonaktifkan.");
        } catch (\Exception $e) {
            DB::rollback();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal menonaktifkan pengaturan pajak customer: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Gagal menonaktifkan pengaturan pajak customer: '.$e->getMessage());
        }
    }

    public function toggleStatus(CustomerTax $customerTax)
    {
        try {
            $newStatus = $customerTax->status === 'active' ? 'inactive' : 'active';

            $customerTax->update(['status' => $newStatus]);

            return back()->with('success', "Status pengaturan pajak berhasil diubah menjadi {$newStatus}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * UAT 17 Nov: urutan list Customer Tax dari header global (?sort=<data-column>&direction=).
     * Kolom relasi (customer/createdBy/updatedBy) diurutkan lewat subquery nama.
     */
    private function applyCustomerTaxSort($query, Request $request): void
    {
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sort = (string) $request->input('sort', '');

        $plainColumns = [
            'label' => 'label',
            'tax_name' => 'tax_name',
            'tax_number' => 'tax_number',
            'nitku' => 'nitku',
            'tax_type' => 'tax_type',
            'ppn_code' => 'ppn_code',
            'tax_rate' => 'tax_rate',
            'effective_date' => 'effective_date',
            // Kolom Status menampilkan `status` (active/inactive).
            'is_active' => 'status',
            'status' => 'status',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];

        $relationColumns = [
            'customer__name' => ['customers', 'customer_id'],
            'createdBy__name' => ['users', 'created_by'],
            'updatedBy__name' => ['users', 'updated_by'],
        ];

        if (isset($plainColumns[$sort])) {
            $query->orderBy('customer_tax_settings.'.$plainColumns[$sort], $direction);
        } elseif (isset($relationColumns[$sort])) {
            [$table, $foreignKey] = $relationColumns[$sort];
            $query->orderBy(
                DB::table($table)
                    ->select('name')
                    ->whereColumn($table.'.id', 'customer_tax_settings.'.$foreignKey)
                    ->limit(1),
                $direction
            );
        } else {
            $query->orderBy('customer_tax_settings.created_at', 'desc');
        }

        $query->orderBy('customer_tax_settings.id', 'desc');
    }

    private function respondWithError(Request $request, string $message, ?string $field = null)
    {
        if ($request->ajax()) {
            return response()->json([
                'status' => 'error',
                'message' => $message,
                'errors' => $field ? [$field => [$message]] : null,
            ], 422);
        }

        return back()->withInput()->with('error', $message);
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

    private function resolveNitkuBranchCode(string $taxName, string $taxNumber, ?string $requestNitku): string
    {
        if ($taxName === 'NITKU') {
            return substr($taxNumber, 16, 6);
        }

        if ($taxName === 'NIK') {
            return '000000';
        }

        $nitku = preg_replace('/\D/', '', (string) $requestNitku);

        return $nitku !== '' ? $nitku : '000000';
    }

    private function validateNitkuParentNpwp(string $taxName, string $taxNumber, int $customerId): void
    {
        if ($taxName !== 'NITKU') {
            return;
        }

        $parentNpwp = substr($taxNumber, 0, 16);
        $validParentNpwp = $this->getCustomerNpwpCandidates($customerId)
            ->contains($parentNpwp);

        if (! $validParentNpwp) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'tax_number' => '16 digit pertama NITKU harus sama dengan NPWP customer yang bersangkutan.',
            ]);
        }
    }

    private function getCustomerNpwpCandidates(int $customerId): \Illuminate\Support\Collection
    {
        $customer = Customer::find($customerId);
        $customerNpwp = preg_replace('/\D/', '', (string) ($customer?->npwp ?? ''));

        $taxNpwp = CustomerTax::where('customer_id', $customerId)
            ->where('tax_name', 'NPWP')
            ->pluck('tax_number')
            ->map(fn ($taxNumber) => preg_replace('/\D/', '', (string) $taxNumber));

        return $taxNpwp
            ->push($customerNpwp)
            ->filter(fn ($taxNumber) => strlen($taxNumber) === 16)
            ->unique()
            ->values();
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
                        'end_date' => $activeTax->expiry_date,
                    ],
                ]);
            }

            if (! empty($customer->npwp) || ! empty($customer->ppn_code)) {
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
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No active tax number found for this customer',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }
}

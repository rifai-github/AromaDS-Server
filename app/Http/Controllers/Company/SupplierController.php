<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Company;
use App\Models\SupplierCategory;
use App\Models\SupplierCreditLimit;
use App\Models\SupplierPaymentTerm;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Subdistrict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::with(['company', 'supplierCategory']);

        // Filter by name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by company
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by category
        if ($request->filled('supplier_category_id')) {
            $query->where('supplier_category_id', $request->supplier_category_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by credit limit range
        if ($request->filled('credit_limit_min')) {
            $query->where('credit_limit', '>=', $request->credit_limit_min);
        }
        if ($request->filled('credit_limit_max')) {
            $query->where('credit_limit', '<=', $request->credit_limit_max);
        }

        // Filter by payment terms
        if ($request->filled('payment_terms')) {
            $query->where('payment_terms', $request->payment_terms);
        }

        // Filter by created date range
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['name', 'email', 'phone', 'status', 'credit_limit', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        $suppliers = $query->paginateStd(25);
        $companies = Company::where('status', 'active')->orderBy('name')->get();
        $categories = SupplierCategory::active()->orderBy('name')->get();

        return view('company.suppliers.index', compact('suppliers', 'companies', 'categories'));
    }

    public function create()
    {
        $companies = Company::where('status', 'active')->orderBy('name')->get();
        $categories = SupplierCategory::active()->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        
        return view('company.suppliers.create', compact('companies', 'categories', 'provinces'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'supplier_category_id' => 'nullable|exists:supplier_categories,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:suppliers',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'nullable|exists:districts,id',
            'subdistrict_id' => 'nullable|exists:subdistricts,id',
            'postal_code' => 'nullable|string|max:10',
            'tax_number' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|integer|min:0|max:365',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            DB::beginTransaction();

            $supplier = Supplier::create([
                'company_id' => $request->company_id,
                'supplier_category_id' => $request->supplier_category_id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'subdistrict_id' => $request->subdistrict_id,
                'postal_code' => $request->postal_code,
                'tax_number' => $request->tax_number,
                'credit_limit' => $request->credit_limit ?? 0,
                'payment_terms' => $request->payment_terms ?? 30,
                'notes' => $request->notes,
                'status' => $request->status,
                'created_by' => Auth::id()
            ]);

            // Create default credit limit if specified
            if ($request->credit_limit > 0) {
                $supplier->creditLimits()->create([
                    'credit_limit' => $request->credit_limit,
                    'is_active' => true,
                    'created_by' => Auth::id()
                ]);
            }

            // Create default payment terms if specified
            if ($request->payment_terms > 0) {
                $supplier->paymentTerms()->create([
                    'payment_terms' => $request->payment_terms,
                    'is_active' => true,
                    'created_by' => Auth::id()
                ]);
            }

            DB::commit();

            return redirect()->route('company.suppliers.show', $supplier)
                ->with('success', 'Supplier berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(Supplier $supplier)
    {
        $supplier->load([
            'company', 
            'supplierCategory',
            'province',
            'city',
            'district',
            'subdistrict',
            'creditLimits',
            'paymentTerms'
        ]);
        
        return view('company.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        $companies = Company::where('status', 'active')->orderBy('name')->get();
        $categories = SupplierCategory::active()->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        $cities = City::where('province_id', $supplier->province_id)->orderBy('name')->get();
        $districts = District::where('city_id', $supplier->city_id)->orderBy('name')->get();
        $subdistricts = Subdistrict::where('district_id', $supplier->district_id)->orderBy('name')->get();
        
        return view('company.suppliers.edit', compact('supplier', 'companies', 'categories', 'provinces', 'cities', 'districts', 'subdistricts'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'supplier_category_id' => 'nullable|exists:supplier_categories,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:suppliers,email,' . $supplier->id,
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'nullable|exists:districts,id',
            'subdistrict_id' => 'nullable|exists:subdistricts,id',
            'postal_code' => 'nullable|string|max:10',
            'tax_number' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|integer|min:0|max:365',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            DB::beginTransaction();

            $supplier->update([
                'company_id' => $request->company_id,
                'supplier_category_id' => $request->supplier_category_id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'subdistrict_id' => $request->subdistrict_id,
                'postal_code' => $request->postal_code,
                'tax_number' => $request->tax_number,
                'credit_limit' => $request->credit_limit ?? 0,
                'payment_terms' => $request->payment_terms ?? 30,
                'notes' => $request->notes,
                'status' => $request->status,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return redirect()->route('company.suppliers.show', $supplier)
                ->with('success', 'Supplier berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(Supplier $supplier)
    {
        try {
            // Check if supplier has related data
            // Add checks for purchase orders, invoices, etc. when those modules are implemented
            
            $supplier->delete();
            return redirect()->route('company.suppliers.index')
                ->with('success', 'Supplier berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Credit Limit Management
    public function creditLimits(Supplier $supplier)
    {
        $creditLimits = $supplier->creditLimits()
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        return view('company.suppliers.credit-limits', compact('supplier', 'creditLimits'));
    }

    public function storeCreditLimit(Request $request, Supplier $supplier)
    {
        $request->validate([
            'credit_limit' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        try {
            $supplier->creditLimits()->create([
                'credit_limit' => $request->credit_limit,
                'is_active' => $request->boolean('is_active'),
                'created_by' => Auth::id()
            ]);

            // Update supplier's current credit limit
            $supplier->update(['credit_limit' => $request->credit_limit]);

            return back()->with('success', 'Batas kredit berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updateCreditLimit(Request $request, Supplier $supplier, SupplierCreditLimit $creditLimit)
    {
        $request->validate([
            'credit_limit' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        try {
            if ($creditLimit->supplier_id !== $supplier->id) {
                throw new \Exception('Batas kredit tidak ditemukan.');
            }

            $creditLimit->update([
                'credit_limit' => $request->credit_limit,
                'is_active' => $request->boolean('is_active'),
                'updated_by' => Auth::id()
            ]);

            // Update supplier's current credit limit if this is the active one
            if ($creditLimit->is_active) {
                $supplier->update(['credit_limit' => $request->credit_limit]);
            }

            return back()->with('success', 'Batas kredit berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function deleteCreditLimit(Supplier $supplier, SupplierCreditLimit $creditLimit)
    {
        try {
            if ($creditLimit->supplier_id !== $supplier->id) {
                throw new \Exception('Batas kredit tidak ditemukan.');
            }

            $creditLimit->delete();

            return back()->with('success', 'Batas kredit berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Payment Terms Management
    public function paymentTerms(Supplier $supplier)
    {
        $paymentTerms = $supplier->paymentTerms()
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        return view('company.suppliers.payment-terms', compact('supplier', 'paymentTerms'));
    }

    public function storePaymentTerm(Request $request, Supplier $supplier)
    {
        $request->validate([
            'payment_terms' => 'required|integer|min:0|max:365',
            'is_active' => 'boolean'
        ]);

        try {
            $supplier->paymentTerms()->create([
                'payment_terms' => $request->payment_terms,
                'is_active' => $request->boolean('is_active'),
                'created_by' => Auth::id()
            ]);

            // Update supplier's current payment terms
            $supplier->update(['payment_terms' => $request->payment_terms]);

            return back()->with('success', 'Syarat pembayaran berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function updatePaymentTerm(Request $request, Supplier $supplier, SupplierPaymentTerm $paymentTerm)
    {
        $request->validate([
            'payment_terms' => 'required|integer|min:0|max:365',
            'is_active' => 'boolean'
        ]);

        try {
            if ($paymentTerm->supplier_id !== $supplier->id) {
                throw new \Exception('Syarat pembayaran tidak ditemukan.');
            }

            $paymentTerm->update([
                'payment_terms' => $request->payment_terms,
                'is_active' => $request->boolean('is_active'),
                'updated_by' => Auth::id()
            ]);

            // Update supplier's current payment terms if this is the active one
            if ($paymentTerm->is_active) {
                $supplier->update(['payment_terms' => $request->payment_terms]);
            }

            return back()->with('success', 'Syarat pembayaran berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function deletePaymentTerm(Supplier $supplier, SupplierPaymentTerm $paymentTerm)
    {
        try {
            if ($paymentTerm->supplier_id !== $supplier->id) {
                throw new \Exception('Syarat pembayaran tidak ditemukan.');
            }

            $paymentTerm->delete();

            return back()->with('success', 'Syarat pembayaran berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // API Methods
    public function getSuppliers(Request $request)
    {
        $suppliers = Supplier::where('status', 'active')
            ->with(['company', 'supplierCategory'])
            ->orderBy('name')
            ->get();

        return response()->json($suppliers);
    }

    public function getSuppliersByCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $suppliers = Supplier::where('company_id', $request->company_id)
            ->where('status', 'active')
            ->with(['supplierCategory'])
            ->orderBy('name')
            ->get();

        return response()->json($suppliers);
    }

    public function searchSuppliers(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2',
        ]);

        $suppliers = Supplier::where('name', 'like', '%' . $request->search . '%')
            ->orWhere('email', 'like', '%' . $request->search . '%')
            ->orWhere('phone', 'like', '%' . $request->search . '%')
            ->where('status', 'active')
            ->with(['company', 'supplierCategory'])
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($suppliers);
    }

    // Bulk Operations
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'supplier_ids' => 'required|array|min:1',
            'supplier_ids.*' => 'exists:suppliers,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            $errors = [];

            foreach ($request->supplier_ids as $supplierId) {
                $supplier = Supplier::find($supplierId);
                
                if ($supplier) {
                    // Check if supplier can be deleted
                    // Add checks for purchase orders, invoices, etc. when those modules are implemented
                    
                    $supplier->delete();
                    $deletedCount++;
                }
            }

            DB::commit();

            $message = "Berhasil menghapus {$deletedCount} supplier.";
            if (!empty($errors)) {
                $message .= " " . implode(' ', $errors);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'supplier_ids' => 'required|array|min:1',
            'supplier_ids.*' => 'exists:suppliers,id',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            DB::beginTransaction();

            $updatedCount = Supplier::whereIn('id', $request->supplier_ids)
                ->update(['status' => $request->status]);

            DB::commit();

            return back()->with('success', "Berhasil memperbarui status {$updatedCount} supplier.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function toggleStatus(Supplier $supplier)
    {
        try {
            $newStatus = $supplier->status === 'active' ? 'inactive' : 'active';
            
            $supplier->update(['status' => $newStatus]);

            return back()->with('success', "Status supplier berhasil diubah menjadi {$newStatus}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getStatistics()
    {
        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::where('status', 'active')->count();
        $suppliersWithCreditLimit = Supplier::where('credit_limit', '>', 0)->count();
        $suppliersByCompany = Supplier::selectRaw('company_id, COUNT(*) as count')
            ->with('company')
            ->groupBy('company_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total_suppliers' => $totalSuppliers,
            'active_suppliers' => $activeSuppliers,
            'suppliers_with_credit_limit' => $suppliersWithCreditLimit,
            'suppliers_by_company' => $suppliersByCompany
        ]);
    }
}

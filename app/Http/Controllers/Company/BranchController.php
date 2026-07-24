<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\Branch;
use App\Models\Company;
use App\Models\BranchSetting;
use App\Models\BranchWarehouse;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use App\Models\OperationalArea;
use App\Models\BranchPic;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Subdistrict;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BranchController extends Controller
{
    use ColumnFilterTrait;
    public function index(Request $request)
    {
        $query = Branch::with(['company', 'province', 'city', 'district', 'subdistrict', 'createdBy', 'updatedBy', 'invoiceAuthorizedByUser'])
            ->withCount('users');

        // Apply AutoFilterable
        $query->filter($request->all());

        // Sort options
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSortFields = ['name', 'code', 'is_active', 'address_type', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        $branches = $query->paginateStd(25);
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        $provinces = Province::orderBy('province_name')->get();
        $invoiceSignatoryUsers = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'position_name']);

        return view('company.branches.index', compact('branches', 'companies', 'provinces', 'invoiceSignatoryUsers'));
    }

    public function create()
    {
        $companies = Company::where('status', 'active')->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();
        
        return view('company.branches.create', compact('companies', 'provinces'));
    }

    public function store(Request $request)
    {
        // Set default company ID to PT Pink Services Indonesia (ID: 7)
        $request->merge(['company_id' => 7]);
        
        // Normalize boolean fields to integer
        $hasWarehouse = $request->has_warehouse;
        if (is_string($hasWarehouse)) {
            $hasWarehouse = in_array(strtolower($hasWarehouse), ['1', 'true', 'yes', 'on']) ? 1 : 0;
        } elseif (is_bool($hasWarehouse)) {
            $hasWarehouse = $hasWarehouse ? 1 : 0;
        } else {
            $hasWarehouse = (int)$hasWarehouse;
        }
        
        $isTaxable = $request->is_taxable;
        if (is_string($isTaxable)) {
            $isTaxable = in_array(strtolower($isTaxable), ['1', 'true', 'yes', 'on']) ? 1 : 0;
        } elseif (is_bool($isTaxable)) {
            $isTaxable = $isTaxable ? 1 : 0;
        } else {
            $isTaxable = (int)$isTaxable;
        }
        
        $isActive = $request->is_active;
        if (is_string($isActive)) {
            $isActive = in_array(strtolower($isActive), ['1', 'true', 'yes', 'on']) ? 1 : 0;
        } elseif (is_bool($isActive)) {
            $isActive = $isActive ? 1 : 0;
        } else {
            $isActive = (int)$isActive;
        }

        $isHeadOffice = $request->is_head_office;
        if (is_string($isHeadOffice)) {
            $isHeadOffice = in_array(strtolower($isHeadOffice), ['1', 'true', 'yes', 'on']) ? 1 : 0;
        } elseif (is_bool($isHeadOffice)) {
            $isHeadOffice = $isHeadOffice ? 1 : 0;
        } else {
            $isHeadOffice = (int)$isHeadOffice;
        }

        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code',
            'address_type' => 'required|in:office,warehouse,both',
            'address_1' => 'required|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'phone_1' => 'required|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'fax' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'nullable|exists:districts,id',
            'subdistrict_id' => 'nullable|exists:subdistricts,id',
            'postal_code' => 'nullable|string|max:10',
            'has_warehouse' => 'nullable',
            'is_taxable' => 'nullable',
            'is_head_office' => 'nullable',
            'invoice_authorized_by_user_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable'
        ]);

        // Custom Validation: Check if city is already used by another branch.
        // A warehouse-only branch is allowed to share a city with an office/both branch
        // (a branch and its warehouse can legitimately sit in the same city) — duplicates
        // are only blocked when both branches are non-warehouse-only (office/both).
        // A head office (Branch Pusat) is also exempt: it may share a city with a regular
        // branch cabang, since a head office legitimately co-locates with a local branch.
        // Two regular (non-head-office) branches still cannot share the same city.
        if (! $isHeadOffice && $request->address_type !== 'warehouse') {
            $existingBranch = Branch::where('city_id', $request->city_id)
                ->where('address_type', '!=', 'warehouse')
                ->where('is_head_office', false)
                ->first();
            if ($existingBranch) {
                $cityName = \App\Models\City::find($request->city_id)->name ?? 'Unknown City';
                return response()->json([
                    'status' => 'error',
                    'message' => "Kota {$cityName} sudah digunakan pada branch {$existingBranch->name}"
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $branchData = [
                'company_id' => $request->company_id,
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'address_type' => $request->address_type,
                'address_1' => $request->address_1,
                'address_2' => $request->address_2,
                'phone_1' => $request->phone_1,
                'phone_2' => $request->phone_2,
                'fax' => $request->fax,
                'email' => $request->email,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'subdistrict_id' => $request->subdistrict_id,
                'postal_code' => $request->postal_code,
                'has_warehouse' => $hasWarehouse,
                'is_taxable' => $isTaxable,
                'is_head_office' => $isHeadOffice,
                'is_active' => $isActive,
                'created_by' => Auth::id()
            ];

            if (Schema::hasColumn('branches', 'invoice_authorized_by_user_id')) {
                $branchData['invoice_authorized_by_user_id'] = $request->invoice_authorized_by_user_id;
            }

            $branch = Branch::create($branchData);

            // Auto-create a warehouse for this branch when "Has Warehouse" is checked.
            if ($hasWarehouse) {
                $this->createWarehouseForBranch($branch);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Branch created successfully',
                'data' => $branch->load(['company', 'province', 'city', 'district', 'subdistrict', 'createdBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create branch: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Branch $branch)
    {
        $branch->load([
            'company', 
            'province',
            'city',
            'district',
            'subdistrict',
            'createdBy',
            'updatedBy',
            'invoiceAuthorizedByUser',
            'branchWarehouses',
            'teams',
            'pics.user',
            'operationalAreas'
        ])->loadCount(['users', 'users as active_users_count' => function($query) {
            $query->where('is_active', true);
        }]);
        
        return response()->json([
            'status' => 'success',
            'data' => $branch
        ]);
    }

    public function edit(Branch $branch)
    {
        $branch->load([
            'company', 
            'province',
            'city',
            'district',
            'subdistrict',
            'createdBy',
            'updatedBy',
            'invoiceAuthorizedByUser'
        ]);
        
        return response()->json([
            'status' => 'success',
            'data' => $branch
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        // Set default company ID to PT Pink Services Indonesia (ID: 7)
        $request->merge(['company_id' => 7]);
        
        // Normalize boolean fields to integer
        $hasWarehouse = $request->has_warehouse;
        if (is_string($hasWarehouse)) {
            $hasWarehouse = in_array(strtolower($hasWarehouse), ['1', 'true', 'yes', 'on']) ? 1 : 0;
        } elseif (is_bool($hasWarehouse)) {
            $hasWarehouse = $hasWarehouse ? 1 : 0;
        } else {
            $hasWarehouse = (int)$hasWarehouse;
        }
        
        $isTaxable = $request->is_taxable;
        if (is_string($isTaxable)) {
            $isTaxable = in_array(strtolower($isTaxable), ['1', 'true', 'yes', 'on']) ? 1 : 0;
        } elseif (is_bool($isTaxable)) {
            $isTaxable = $isTaxable ? 1 : 0;
        } else {
            $isTaxable = (int)$isTaxable;
        }
        
        $isActive = $request->is_active;
        if (is_string($isActive)) {
            $isActive = in_array(strtolower($isActive), ['1', 'true', 'yes', 'on']) ? 1 : 0;
        } elseif (is_bool($isActive)) {
            $isActive = $isActive ? 1 : 0;
        } else {
            $isActive = (int)$isActive;
        }

        $isHeadOffice = $request->is_head_office;
        if (is_string($isHeadOffice)) {
            $isHeadOffice = in_array(strtolower($isHeadOffice), ['1', 'true', 'yes', 'on']) ? 1 : 0;
        } elseif (is_bool($isHeadOffice)) {
            $isHeadOffice = $isHeadOffice ? 1 : 0;
        } else {
            $isHeadOffice = (int)$isHeadOffice;
        }

        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code,' . $branch->id,
            'address_type' => 'required|in:office,warehouse,both',
            'address_1' => 'required|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'phone_1' => 'required|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'fax' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'nullable|exists:districts,id',
            'subdistrict_id' => 'nullable|exists:subdistricts,id',
            'postal_code' => 'nullable|string|max:10',
            'has_warehouse' => 'nullable',
            'is_taxable' => 'nullable',
            'is_head_office' => 'nullable',
            'invoice_authorized_by_user_id' => 'nullable|exists:users,id',
            'is_active' => 'nullable'
        ]);

        // Custom Validation: Check if city is already used by another branch (Only if city changed).
        // Same exemption as store(): warehouse-only branches may share a city with an
        // office/both branch, and a head office (Branch Pusat) may share a city with a
        // regular branch cabang; duplicates are only blocked between two regular
        // (non-warehouse, non-head-office) branches.
        if ($request->city_id != $branch->city_id && ! $isHeadOffice && $request->address_type !== 'warehouse') {
            $existingBranch = Branch::where('city_id', $request->city_id)
                ->where('id', '!=', $branch->id)
                ->where('address_type', '!=', 'warehouse')
                ->where('is_head_office', false)
                ->first();
            if ($existingBranch) {
                $cityName = \App\Models\City::find($request->city_id)->name ?? 'Unknown City';
                return response()->json([
                    'status' => 'error',
                    'message' => "Kota {$cityName} sudah digunakan pada branch {$existingBranch->name}"
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $branchData = [
                'company_id' => $request->company_id,
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'address_type' => $request->address_type,
                'address_1' => $request->address_1,
                'address_2' => $request->address_2,
                'phone_1' => $request->phone_1,
                'phone_2' => $request->phone_2,
                'fax' => $request->fax,
                'email' => $request->email,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'subdistrict_id' => $request->subdistrict_id,
                'postal_code' => $request->postal_code,
                'has_warehouse' => $hasWarehouse,
                'is_taxable' => $isTaxable,
                'is_head_office' => $isHeadOffice,
                'is_active' => $isActive,
                'updated_by' => Auth::id()
            ];

            if (Schema::hasColumn('branches', 'invoice_authorized_by_user_id')) {
                $branchData['invoice_authorized_by_user_id'] = $request->invoice_authorized_by_user_id;
            }

            $branch->update($branchData);

            // Auto-create a warehouse when "Has Warehouse" is (now) enabled and
            // the branch does not have one yet. No-op if it already has a warehouse.
            if ($hasWarehouse) {
                $this->createWarehouseForBranch($branch);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Branch updated successfully',
                'data' => $branch->load(['company', 'province', 'city', 'district', 'subdistrict', 'createdBy', 'updatedBy', 'invoiceAuthorizedByUser'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update branch: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Branch $branch)
    {
        try {
            $branch->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Branch deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete branch: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:branches,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = Branch::whereIn('id', $request->ids)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'count' => $deletedCount,
                'message' => $deletedCount === 1 
                    ? '1 branch has been successfully hidden.'
                    : "{$deletedCount} branches have been successfully hidden."
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete branches: ' . $e->getMessage()
            ], 500);
        }
    }

    // Branch Settings Management
    public function settings(Branch $branch)
    {
        $settings = $branch->settings()->first();
        if (!$settings) {
            $settings = $branch->settings()->create([
                'default_currency' => 'IDR',
                'default_language' => 'id',
                'timezone' => 'Asia/Jakarta',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'number_format' => '0,0.00',
                'tax_calculation_method' => 'inclusive',
                'auto_generate_code' => true,
                'code_length' => 6,
                'send_email_notifications' => true,
                'send_sms_notifications' => false,
                'allow_negative_stock' => false,
                'require_approval_for_purchase' => true,
                'require_approval_for_sale' => false,
                'default_payment_terms' => 30,
                'default_credit_limit' => 0,
                'backup_frequency' => 'daily',
                'data_retention_days' => 2555, // 7 years
                'is_active' => true
            ]);
        }

        return view('company.branches.settings', compact('branch', 'settings'));
    }

    public function updateSettings(Request $request, Branch $branch)
    {
        $request->validate([
            'default_currency' => 'required|string|max:3',
            'default_language' => 'required|string|max:5',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'time_format' => 'required|string|max:10',
            'number_format' => 'required|string|max:20',
            'tax_calculation_method' => 'required|in:inclusive,exclusive',
            'auto_generate_code' => 'boolean',
            'code_length' => 'required|integer|min:3|max:10',
            'send_email_notifications' => 'boolean',
            'send_sms_notifications' => 'boolean',
            'allow_negative_stock' => 'boolean',
            'require_approval_for_purchase' => 'boolean',
            'require_approval_for_sale' => 'boolean',
            'default_payment_terms' => 'required|integer|min:0|max:365',
            'default_credit_limit' => 'required|numeric|min:0',
            'backup_frequency' => 'required|in:daily,weekly,monthly',
            'data_retention_days' => 'required|integer|min:30|max:3650',
            'is_active' => 'nullable|in:1,0,true,false'
        ]);

        try {
            $settings = $branch->settings()->first();
            if ($settings) {
                $settings->update($request->all());
            } else {
                $branch->settings()->create($request->all());
            }

            return back()->with('success', 'Pengaturan cabang berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Auto-create a default warehouse for a branch when "Has Warehouse" is enabled.
     * Skips creation if the branch already has at least one warehouse.
     */
    private function createWarehouseForBranch(Branch $branch): ?Warehouse
    {
        // Avoid duplicates (e.g. branch toggled has_warehouse off then on again).
        if ($branch->warehouses()->exists()) {
            return null;
        }

        return Warehouse::create([
            'warehouse_code' => $this->generateWarehouseCode(),
            'name' => 'Gudang ' . $branch->name,
            'branch_id' => $branch->id,
            'warehouse_type_id' => $this->resolveBranchWarehouseTypeId(),
            'address' => $branch->address_1,
            'phone' => $branch->phone_1,
            'is_active' => true,
            'is_center' => false,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    /**
     * Generate a unique warehouse code (mirrors WarehouseController::generateWarehouseCode).
     */
    private function generateWarehouseCode(): string
    {
        $prefix = 'WH';
        $year = date('Y');
        $month = date('m');

        $lastWarehouse = Warehouse::where('warehouse_code', 'like', $prefix . $year . $month . '%')
            ->orderBy('warehouse_code', 'desc')
            ->first();

        $newNumber = $lastWarehouse
            ? intval(substr($lastWarehouse->warehouse_code, -3)) + 1
            : 1;

        return $prefix . $year . $month . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Resolve (or create) the default "Branch Warehouse" type id.
     * Mirrors WarehouseController::resolveDefaultWarehouseTypeId for the non-center case.
     */
    private function resolveBranchWarehouseTypeId(): int
    {
        $type = WarehouseType::where('code', 'BRANCH')->first()
            ?: WarehouseType::where('name', 'Branch Warehouse')->first();

        if (! $type) {
            $type = WarehouseType::create([
                'code' => 'BRANCH',
                'name' => 'Branch Warehouse',
                'description' => 'Default type for single warehouse per branch flow.',
                'is_active' => true,
            ]);
        }

        return $type->id;
    }

    // Branch Warehouse Management
    public function warehouses(Branch $branch)
    {
        $warehouses = $branch->warehouses()
            ->with('warehouse')
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        return view('company.branches.warehouses', compact('branch', 'warehouses'));
    }

    public function assignWarehouse(Request $request, Branch $branch)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'is_primary' => 'boolean'
        ]);

        try {
            // Check if warehouse is already assigned to this branch
            $existingAssignment = $branch->warehouses()
                ->where('warehouse_id', $request->warehouse_id)
                ->exists();

            if ($existingAssignment) {
                throw new \Exception('Gudang sudah ditugaskan ke cabang ini.');
            }

            // If this is set as primary, unset other primary warehouses
            if ($request->boolean('is_primary')) {
                $branch->warehouses()->update(['is_primary' => false]);
            }

            $branch->warehouses()->create([
                'warehouse_id' => $request->warehouse_id,
                'is_primary' => $request->boolean('is_primary'),
                'assigned_by' => Auth::id(),
                'assigned_at' => now()
            ]);

            return back()->with('success', 'Gudang berhasil ditugaskan ke cabang.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function removeWarehouse(Branch $branch, BranchWarehouse $branchWarehouse)
    {
        try {
            if ($branchWarehouse->branch_id !== $branch->id) {
                throw new \Exception('Penugasan gudang tidak ditemukan.');
            }

            $branchWarehouse->delete();

            return back()->with('success', 'Penugasan gudang berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function setPrimaryWarehouse(Branch $branch, BranchWarehouse $branchWarehouse)
    {
        try {
            if ($branchWarehouse->branch_id !== $branch->id) {
                throw new \Exception('Penugasan gudang tidak ditemukan.');
            }

            // Unset other primary warehouses
            $branch->warehouses()->update(['is_primary' => false]);

            // Set this warehouse as primary
            $branchWarehouse->update(['is_primary' => true]);

            return back()->with('success', 'Gudang utama berhasil diubah.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // API Methods
    public function getBranches(Request $request)
    {
        $branches = Branch::where('is_active', true)
            ->with(['company', 'province', 'city'])
            ->orderBy('name')
            ->get();

        return response()->json($branches);
    }

    public function getBranchesByCompany(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $branches = Branch::where('company_id', $request->company_id)
            ->where('status', 'active')
            ->with(['province', 'city'])
            ->orderBy('name')
            ->get();

        return response()->json($branches);
    }

    public function searchBranches(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2',
        ]);

        $branches = Branch::where('name', 'like', '%' . $request->search . '%')
            ->orWhere('code', 'like', '%' . $request->search . '%')
            ->orWhere('email', 'like', '%' . $request->search . '%')
            ->where('status', 'active')
            ->with(['company', 'province', 'city'])
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($branches);
    }


    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'branch_ids' => 'required|array|min:1',
            'branch_ids.*' => 'exists:branches,id',
            'status' => 'required|in:active,inactive'
        ]);

        try {
            DB::beginTransaction();

            $updatedCount = Branch::whereIn('id', $request->branch_ids)
                ->update(['status' => $request->status]);

            DB::commit();

            return back()->with('success', "Berhasil memperbarui status {$updatedCount} cabang.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function toggleStatus(Branch $branch)
    {
        try {
            $newStatus = $branch->status === 'active' ? 'inactive' : 'active';
            
            $branch->update(['status' => $newStatus]);

            return back()->with('success', "Status cabang berhasil diubah menjadi {$newStatus}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getStatistics()
    {
        $totalBranches = Branch::count();
        $activeBranches = Branch::where('status', 'active')->count();
        $branchesByType = Branch::selectRaw('branch_type, COUNT(*) as count')
            ->groupBy('branch_type')
            ->pluck('count', 'branch_type')
            ->toArray();
        $branchesByCompany = Branch::selectRaw('company_id, COUNT(*) as count')
            ->with('company')
            ->groupBy('company_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total_branches' => $totalBranches,
            'active_branches' => $activeBranches,
            'branches_by_type' => $branchesByType,
            'branches_by_company' => $branchesByCompany
        ]);
    }

    // Operational Areas Management
    public function operationalAreas(Branch $branch)
    {
        // Load branch with province relation
        $branch->load('province');
        
        // Get all cities in branch's province (for Card 1)
        $citiesInProvince = [];
        if ($branch->province_id) {
            $citiesInProvince = City::where('province_id', $branch->province_id)
                ->orderBy('name')
                ->get();
        }
        
        // Get assigned city IDs for this branch
        $assignedCityIds = $branch->operationalAreas()
            ->whereNotNull('city_id')
            ->pluck('city_id')
            ->toArray();
        
        // Get city IDs assigned to OTHER branches (to disable them)
        $assignedToOtherBranches = OperationalArea::where('branch_id', '!=', $branch->id)
            ->whereNotNull('city_id')
            ->pluck('city_id')
            ->toArray();
        
        // Get all provinces except branch's province (for Card 2 dropdown)
        $otherProvinces = Province::when($branch->province_id, function($query) use ($branch) {
                return $query->where('id', '!=', $branch->province_id);
            })
            ->orderBy('name')
            ->get();
        
        // Get cities from other provinces that are assigned to this branch (for Card 2 display)
        $citiesOutsideProvince = $branch->operationalAreas()
            ->when($branch->province_id, function($query) use ($branch) {
                return $query->where('province_id', '!=', $branch->province_id);
            })
            ->with(['cityRelation', 'provinceRelation'])
            ->get();
        
        // Get branch description from operational areas (use first one's description or empty)
        $branchDescription = $branch->operationalAreas()->first()?->description ?? '';

        return view('company.branches.operational-areas', compact(
            'branch', 
            'citiesInProvince',
            'assignedCityIds',
            'assignedToOtherBranches',
            'otherProvinces',
            'citiesOutsideProvince',
            'branchDescription'
        ));
    }
    
    /**
     * Sync operational areas for a branch (bulk save city selections)
     */
    public function syncOperationalAreas(Request $request, Branch $branch)
    {
        $request->validate([
            'city_ids' => 'nullable|array',
            'city_ids.*' => 'exists:cities,id',
            'description' => 'nullable|string|max:1000'
        ]);
        
        try {
            DB::beginTransaction();
            
            // Get submitted city IDs (only numeric values)
            $cityIds = array_map('intval', $request->input('city_ids', []));

            // Get currently assigned city IDs (to allow keeping legacy duplicates)
            $currentCityIds = $branch->operationalAreas()->pluck('city_id')->toArray();
            
            // Only validate NEWLY added cities
            $newCityIds = array_diff($cityIds, $currentCityIds);
            
            // Validation: Check if any NEW city is already assigned to ANOTHER branch
            if (!empty($newCityIds)) {
                $existingAssignment = OperationalArea::whereIn('city_id', $newCityIds)
                    ->where('branch_id', '!=', $branch->id)
                    ->with(['branch', 'cityRelation'])
                    ->first();
                
                if ($existingAssignment) {
                    $cityName = $existingAssignment->cityRelation->name ?? $existingAssignment->city;
                    $branchName = $existingAssignment->branch->name ?? 'Unknown Branch';
                    
                    return redirect()
                        ->back()
                        ->with('swal_error', "Kota {$cityName} sudah digunakan pada branch {$branchName}");
                }
            }
            
            // Delete existing operational areas for this branch
            $branch->operationalAreas()->delete();
            
            // Create new records for each selected city
            foreach ($cityIds as $cityId) {
                $city = City::with('province')->find($cityId);
                if (!$city) continue;
                
                $branch->operationalAreas()->create([
                    'name' => $city->name,
                    'city_id' => $cityId,
                    'province_id' => $city->province_id,
                    'city' => $city->name,
                    'province' => $city->province->name ?? '',
                    'area_type' => 'city',
                    'description' => $request->input('description'),
                    'is_active' => true,
                    'created_by' => Auth::id()
                ]);
            }
            
            DB::commit();
            
            return redirect()
                ->route('company.branches.operational-areas', $branch)
                ->with('success', 'Operational areas berhasil diperbarui. ' . count($cityIds) . ' kota telah disimpan.');
                
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()
                ->back()
                ->with('error', 'Gagal menyimpan operational areas: ' . $e->getMessage());
        }
    }
    
    /**
     * API: Get cities for a province (for Card 2 dynamic loading)
     */
    public function getOperationalAreaCities(Request $request, Branch $branch)
    {
        $request->validate([
            'province_id' => 'required|exists:provinces,id'
        ]);
        
        $provinceId = $request->input('province_id');
        $province = Province::find($provinceId);
        
        // Get all cities in the selected province
        $cities = City::where('province_id', $provinceId)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        // Get city IDs already assigned to this branch
        $assignedCityIds = $branch->operationalAreas()
            ->whereNotNull('city_id')
            ->pluck('city_id')
            ->toArray();
        
        // Get city IDs assigned to OTHER branches
        $assignedToOtherBranches = OperationalArea::where('branch_id', '!=', $branch->id)
            ->whereNotNull('city_id')
            ->pluck('city_id')
            ->toArray();
        
        // Mark each city's status
        $citiesWithStatus = $cities->map(function($city) use ($assignedCityIds, $assignedToOtherBranches) {
            return [
                'id' => $city->id,
                'name' => $city->name,
                'checked' => in_array($city->id, $assignedCityIds),
                'disabled' => in_array($city->id, $assignedToOtherBranches),
                'assigned_to_other' => in_array($city->id, $assignedToOtherBranches)
            ];
        });
        
        return response()->json([
            'status' => 'success',
            'province_id' => (int) $provinceId,
            'province_name' => $province?->name ?? '',
            'data' => $citiesWithStatus
        ]);
    }

    public function showOperationalArea(Branch $branch, OperationalArea $operationalArea)
    {
        try {
            $operationalArea->load(['createdBy', 'updatedBy']);
            
            return response()->json([
                'success' => true,
                'data' => $operationalArea
            ]);
        } catch (\Exception $e) {
            \Log::error('Error showing operational area: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve operational area details'
            ], 500);
        }
    }

    public function storeOperationalArea(Request $request, Branch $branch)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'description' => 'nullable|string',
            'area_type' => 'required|in:city,district,province,region',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'nullable|exists:districts,id',
            'subdistrict_id' => 'nullable|exists:subdistricts,id',
            'subdistrict_id' => 'nullable|exists:subdistricts,id',
            'is_active' => 'nullable|in:1,0,true,false'
        ]);

        try {
            // Get location names from IDs
            $province = \App\Models\Province::find($request->province_id);
            $city = \App\Models\City::find($request->city_id);
            $district = $request->district_id ? \App\Models\District::find($request->district_id) : null;
            $subdistrict = $request->subdistrict_id ? \App\Models\Subdistrict::find($request->subdistrict_id) : null;
            $user = \App\Models\User::find($request->user_id);
            
            $operationalArea = $branch->operationalAreas()->create([
                'name' => $user->name, // Use user name as area name
                'description' => $request->description,
                'area_type' => $request->area_type,
                'city' => $city->name,
                'province' => $province->name,
                'district' => $district ? $district->name : null,
                'subdistrict' => $subdistrict ? $subdistrict->name : null,
                'postal_code' => $request->postal_code,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'radius_km' => $request->radius_km,
                'is_active' => $request->boolean('is_active'),
                'user_id' => $request->user_id,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'subdistrict_id' => $request->subdistrict_id,
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Operational area created successfully',
                'data' => $operationalArea->load(['createdBy'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create operational area: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateOperationalArea(Request $request, Branch $branch, OperationalArea $operationalArea)
    {
        if ($operationalArea->branch_id !== $branch->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Operational area not found for this branch'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'area_type' => 'required|in:city,district,province,region',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'nullable|exists:districts,id',
            'subdistrict_id' => 'nullable|exists:subdistricts,id',
            'subdistrict_id' => 'nullable|exists:subdistricts,id',
            'is_active' => 'nullable|in:1,0,true,false'
        ]);

        try {
            // Get location names from IDs
            $province = \App\Models\Province::find($request->province_id);
            $city = \App\Models\City::find($request->city_id);
            $district = $request->district_id ? \App\Models\District::find($request->district_id) : null;
            $subdistrict = $request->subdistrict_id ? \App\Models\Subdistrict::find($request->subdistrict_id) : null;
            
            $operationalArea->update([
                'name' => $request->name,
                'description' => $request->description,
                'area_type' => $request->area_type,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'subdistrict_id' => $request->subdistrict_id,
                'city' => $city->name,
                'province' => $province->name,
                'district' => $district ? $district->name : null,
                'subdistrict' => $subdistrict ? $subdistrict->name : null,
                'postal_code' => $request->postal_code,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'radius_km' => $request->radius_km,
                'is_active' => $request->boolean('is_active'),
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Operational area updated successfully',
                'data' => $operationalArea->load(['createdBy', 'updatedBy'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update operational area: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyOperationalArea(Branch $branch, OperationalArea $operationalArea)
    {
        if ($operationalArea->branch_id !== $branch->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Operational area not found for this branch'
            ], 404);
        }

        try {
            $operationalArea->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Operational area deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete operational area: ' . $e->getMessage()
            ], 500);
        }
    }

    // Branch PICs Management
    public function pics(Branch $branch)
    {
        $pics = $branch->pics()
            ->with(['user', 'assignedBy'])
            ->orderBy('is_primary', 'desc')
            ->orderBy('position')
            ->paginateStd(25);

        $users = \App\Models\User::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('company.branches.pics', compact('branch', 'pics', 'users'));
    }

    public function showPic(Branch $branch, BranchPic $pic)
    {
        if ($pic->branch_id !== $branch->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'PIC not found for this branch'
            ], 404);
        }

        $pic->load(['user', 'assignedBy']);

        return response()->json([
            'status' => 'success',
            'data' => $pic
        ]);
    }

    public function storePic(Request $request, Branch $branch)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'is_primary' => 'nullable|in:1,0,true,false',
            'is_active' => 'nullable|in:1,0,true,false',
            'assigned_date' => 'required|date',
            'end_date' => 'nullable|date|after:assigned_date',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // If this is set as primary, unset other primary PICs
            if ($request->boolean('is_primary')) {
                $branch->pics()->update(['is_primary' => false]);
            }

            $pic = $branch->pics()->create([
                'user_id' => $request->user_id,
                'is_primary' => $request->boolean('is_primary'),
                'is_active' => $request->boolean('is_active'),
                'assigned_date' => \Carbon\Carbon::parse($request->assigned_date)->format('Y-m-d'),
                'end_date' => $request->end_date ? \Carbon\Carbon::parse($request->end_date)->format('Y-m-d') : null,
                'notes' => $request->notes,
                'assigned_by' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'PIC assigned successfully',
                'data' => $pic->load(['user', 'assignedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign PIC: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updatePic(Request $request, Branch $branch, BranchPic $pic)
    {
        if ($pic->branch_id !== $branch->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'PIC not found for this branch'
            ], 404);
        }

        $request->validate([
            'is_primary' => 'boolean',
            'is_active' => 'nullable|in:1,0,true,false',
            'assigned_date' => 'required|date',
            'end_date' => 'nullable|date|after:assigned_date',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // If this is set as primary, unset other primary PICs
            if ($request->boolean('is_primary')) {
                $branch->pics()
                    ->where('id', '!=', $pic->id)
                    ->update(['is_primary' => false]);
            }

            $pic->update([
                'is_primary' => $request->boolean('is_primary'),
                'is_active' => $request->boolean('is_active'),
                'assigned_date' => \Carbon\Carbon::parse($request->assigned_date)->format('Y-m-d'),
                'end_date' => $request->end_date ? \Carbon\Carbon::parse($request->end_date)->format('Y-m-d') : null,
                'notes' => $request->notes
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'PIC updated successfully',
                'data' => $pic->load(['user', 'assignedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update PIC: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyPic(Branch $branch, BranchPic $pic)
    {
        if ($pic->branch_id !== $branch->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'PIC not found for this branch'
            ], 404);
        }

        try {
            $pic->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'PIC removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove PIC: ' . $e->getMessage()
            ], 500);
        }
    }

    public function setPrimaryPic(Branch $branch, BranchPic $pic)
    {
        if ($pic->branch_id !== $branch->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'PIC not found for this branch'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Unset other primary PICs
            $branch->pics()->update(['is_primary' => false]);

            // Set this PIC as primary
            $pic->update(['is_primary' => true]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Primary PIC updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update primary PIC: ' . $e->getMessage()
            ], 500);
        }
    }
}

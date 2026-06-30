<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\Branch;
use App\Models\MasterOption;
use App\Models\MasterProduct;
use App\Models\MasterRental;
use App\Models\ProductCategory;
use App\Models\RentalDetail;
use App\Models\RentalPrice;
use App\Models\RentalServiceFrequency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterRentalController extends Controller
{
    use \App\Http\Traits\ColumnFilterTrait;
    use DataTableTrait;

    /**
     * DataTables server-side processing endpoint
     */
    public function datatable(Request $request)
    {
        $query = MasterRental::with(['createdBy', 'updatedBy', 'serviceFrequency'])
            ->select('master_rentals.*');

        // Define searchable columns
        $searchableColumns = [
            'rental_code',
            'rental_name',
            'category',
            'daily_price',
            'monthly_price',
            'is_active',
        ];

        // Column mapping for ordering and searching
        $columnMapping = [
            0 => 'id',  // checkbox column
            1 => 'rental_code',
            2 => 'rental_name',
            3 => 'category',
            4 => 'service_frequency_id',
            5 => 'daily_price',
            6 => 'monthly_price',
            7 => 'is_active',
            8 => 'creator.name',
            9 => 'created_at',
            10 => 'updated_at',
            11 => 'updater.name',
        ];

        // Use the DataTableTrait to process the request
        return $this->dataTableResponse($query, $searchableColumns, $columnMapping);
    }

    public function index(Request $request)
    {
        $query = MasterRental::with(['createdBy', 'updatedBy', 'serviceFrequency']);

        // Filter by rental code
        if ($request->filled('rental_code') && ! $request->has('filter')) {
            $query->where('rental_code', 'like', '%'.$request->rental_code.'%');
        }

        // Filter by rental name
        if ($request->filled('rental_name') && ! $request->has('filter')) {
            $query->where('rental_name', 'like', '%'.$request->rental_name.'%');
        }

        // Filter by category
        if ($request->filled('category') && ! $request->has('filter')) {
            $query->where('category', $request->category);
        }

        // Filter by status
        if ($request->filled('is_active') && ! $request->has('filter')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by search
        if ($request->filled('search') && ! $request->has('filter')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('rental_name', 'like', "%{$search}%")
                    ->orWhere('rental_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Apply column filters from header table
        if ($request->has('filter')) {
            $columnMap = [
                'rental_code' => ['column' => 'rental_code'],
                'rental_name' => ['column' => 'rental_name'],
                'category' => ['column' => 'category'],
                'daily_price' => ['column' => 'daily_price'],
                'monthly_price' => ['column' => 'monthly_price'],
                'is_active' => ['column' => 'is_active', 'boolean' => true],
                'service_frequency_id' => ['relation' => 'serviceFrequency', 'column' => 'name'],
                'creator.name' => ['relation' => 'createdBy', 'column' => 'name'],
                'created_at' => ['column' => 'created_at', 'type' => 'date'],
                'updater.name' => ['relation' => 'updatedBy', 'column' => 'name'],
                'updated_at' => ['column' => 'updated_at', 'type' => 'date'],
            ];
            $this->applyColumnFilters($query, null, $columnMap);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortFields = ['rental_name', 'rental_code', 'category', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $rentals = $query->paginateStd(25);

        // Return JSON for AJAX requests or API requests
        if ($request->ajax() || request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $rentals->items(),
                'pagination' => [
                    'total' => $rentals->total(),
                    'per_page' => $rentals->perPage(),
                    'current_page' => $rentals->currentPage(),
                    'last_page' => $rentals->lastPage(),
                    'from' => $rentals->firstItem(),
                    'to' => $rentals->lastItem(),
                ],
            ]);
        }

        // Load service frequencies for dropdowns
        $serviceFrequencies = RentalServiceFrequency::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $productCategories = ProductCategory::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        // Load unit options from master options
        $unitOptions = MasterOption::where('name', 'Product Units')
            ->where('is_active', true)
            ->with('optionDetails')
            ->first();

        return view('warehouse.master-rentals.index', compact('rentals', 'serviceFrequencies', 'productCategories', 'unitOptions'));
    }

    public function create()
    {
        // Load service frequencies for dropdowns
        $serviceFrequencies = RentalServiceFrequency::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $productCategories = ProductCategory::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        // Load unit options from master options
        $unitOptions = MasterOption::where('name', 'Product Units')
            ->where('is_active', true)
            ->with('optionDetails')
            ->first();

        // Return JSON for AJAX requests
        return response()->json([
            'status' => 'success',
            'message' => 'Create form ready',
            'serviceFrequencies' => $serviceFrequencies,
            'productCategories' => $productCategories,
            'unitOptions' => $unitOptions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'rental_code' => 'nullable|string|max:50|unique:master_rentals',
            'rental_name' => 'required|string|max:255',
            'service_frequency_id' => 'required|exists:rental_service_frequencies,id',
            'category' => 'required|string|max:100',
            'rental_type' => 'required|in:unit_only,refill_only,unit_refill',
            'daily_price' => 'nullable|numeric|min:0',
            'monthly_price' => 'nullable|numeric|min:0',
            'lost_unit_price' => 'nullable|numeric|min:0',
            'install_duration' => 'nullable|integer|min:0',
            'service_duration' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // Determine initial rental code
            $providedCode = trim((string) $request->rental_code);
            $rentalCode = $providedCode !== '' ? $providedCode : $this->generateRentalCode();

            // Try to create with retry on duplicate code (only when auto-generated)
            $maxAttempts = $providedCode === '' ? 5 : 1;
            $attempt = 0;
            do {
                try {
                    $rental = MasterRental::create([
                        'rental_code' => $rentalCode,
                        'rental_name' => $request->rental_name,
                        'description' => null,
                        'service_frequency_id' => $request->service_frequency_id,
                        'category' => $request->category,
                        'rental_type' => $request->rental_type,
                        'daily_price' => $request->daily_price ?? 0,
                        'monthly_price' => $request->monthly_price ?? 0,
                        'lost_unit_price' => $request->lost_unit_price ?? 0,
                        'install_duration' => $request->install_duration,
                        'service_duration' => $request->service_duration,
                        'unit' => null,
                        'is_active' => $request->is_active ?? true,
                        'created_by' => Auth::id(),
                    ]);
                    // success
                    break;
                } catch (\Illuminate\Database\QueryException $qe) {
                    $duplicate = str_contains($qe->getMessage(), 'master_rentals_rental_code_unique') || str_contains($qe->getMessage(), 'Duplicate entry') && str_contains($qe->getMessage(), 'rental_code');
                    $attempt += 1;
                    if ($duplicate && $providedCode === '' && $attempt < $maxAttempts) {
                        // Regenerate and retry
                        $rentalCode = $this->generateRentalCode();

                        continue;
                    }
                    throw $qe; // rethrow for outer catch
                }
            } while ($attempt < $maxAttempts);

            DB::commit();

            // Always return JSON for AJAX requests
            return response()->json([
                'status' => 'success',
                'message' => 'Master Rental berhasil dibuat.',
                'data' => $rental->load(['createdBy', 'updatedBy']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            // Always return JSON for AJAX requests
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 422);
        }
    }

    public function show(MasterRental $masterRental)
    {
        $masterRental->load([
            'rentalDetails.productType',
            'rentalDetails.masterProduct.packagingSize',
            'rentalDetails.allowedProducts',
            'rentalDetails.creator',
            'rentalDetails.updater',
            'rentalPrices.branch',
            'rentalPrices.creator',
            'rentalPrices.updater',
            'createdBy',
            'updatedBy',
            'serviceFrequency',
        ]);

        // Return JSON for AJAX requests
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $masterRental,
            ]);
        }

        // Load data for dropdowns
        $serviceFrequencies = RentalServiceFrequency::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $productTypes = \App\Models\ProductType::where('is_active', true)
            ->orderBy('name')
            ->get();

        $masterProducts = MasterProduct::with(['productCategory', 'productType', 'packagingSize'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $branches = Branch::where('is_active', true)
            ->orderBy('name')
            ->get();

        $productCategories = ProductCategory::where('is_active', true)
            ->whereNotNull('parent_id')
            ->orderBy('name')
            ->get();

        // Return view for web requests
        return view('warehouse.master-rentals.show', compact(
            'masterRental',
            'serviceFrequencies',
            'productTypes',
            'masterProducts',
            'branches',
            'productCategories'
        ));
    }

    public function edit(MasterRental $masterRental)
    {
        $masterRental->load(['createdBy', 'updatedBy']);

        // Load service frequencies for dropdowns
        $serviceFrequencies = RentalServiceFrequency::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $productCategories = ProductCategory::where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        // Load unit options from master options
        $unitOptions = MasterOption::where('name', 'Product Units')
            ->where('is_active', true)
            ->with('optionDetails')
            ->first();

        // Always return JSON for AJAX requests
        return response()->json([
            'status' => 'success',
            'data' => $masterRental,
            'serviceFrequencies' => $serviceFrequencies,
            'productCategories' => $productCategories,
            'unitOptions' => $unitOptions,
        ]);
    }

    public function update(Request $request, MasterRental $masterRental)
    {
        $request->validate([
            'rental_code' => 'required|string|max:50|unique:master_rentals,rental_code,'.$masterRental->id,
            'rental_name' => 'required|string|max:255',
            'service_frequency_id' => 'required|exists:rental_service_frequencies,id',
            'category' => 'required|string|max:100',
            'rental_type' => 'required|in:unit_only,refill_only,unit_refill',
            'daily_price' => 'nullable|numeric|min:0',
            'monthly_price' => 'nullable|numeric|min:0',
            'lost_unit_price' => 'nullable|numeric|min:0',
            'install_duration' => 'nullable|integer|min:0',
            'service_duration' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $masterRental->update([
                'rental_code' => $request->rental_code,
                'rental_name' => $request->rental_name,
                'description' => null,
                'service_frequency_id' => $request->service_frequency_id,
                'category' => $request->category,
                'rental_type' => $request->rental_type,
                'daily_price' => $request->daily_price ?? 0,
                'monthly_price' => $request->monthly_price ?? 0,
                'lost_unit_price' => $request->lost_unit_price ?? 0,
                'install_duration' => $request->install_duration,
                'service_duration' => $request->service_duration,
                'unit' => null,
                'is_active' => $request->is_active ?? true,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            // Always return JSON for AJAX requests
            return response()->json([
                'status' => 'success',
                'message' => 'Master Rental berhasil diperbarui.',
                'data' => $masterRental->load(['createdBy', 'updatedBy']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            // Always return JSON for AJAX requests
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 422);
        }
    }

    public function destroy(MasterRental $masterRental)
    {
        try {
            // Check if rental is used by any contracts
            $hasContracts = $masterRental->contractRentals()->exists();

            if ($hasContracts) {
                throw new \Exception('Tidak dapat menghapus master rental yang sudah digunakan dalam kontrak.');
            }

            $masterRental->delete();

            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Master Rental berhasil dihapus.',
                ]);
            }

            return redirect()->route('warehouse.master-rentals.index')
                ->with('success', 'Master Rental berhasil dihapus.');
        } catch (\Exception $e) {
            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Generate unique rental code
     */
    private function generateRentalCode()
    {
        $prefix = 'RTL';
        $date = now()->format('Ymd');
        $base = $prefix.'-'.$date.'-';

        // Find the highest existing sequence for this date including soft-deleted rows
        $maxSequence = MasterRental::withTrashed()
            ->where('rental_code', 'like', $base.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(rental_code, -4) AS UNSIGNED)) as max_seq')
            ->value('max_seq');

        $next = (int) $maxSequence + 1; // if null, becomes 1

        // Ensure uniqueness in case of race conditions or backfilled data
        do {
            $sequence = str_pad($next, 4, '0', STR_PAD_LEFT);
            $code = $base.$sequence;
            // Check existence including soft-deleted rows
            $exists = MasterRental::withTrashed()->where('rental_code', $code)->exists();
            if ($exists) {
                $next += 1;
            }
        } while ($exists);

        return $code;
    }

    /**
     * Bulk delete rentals
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:master_rentals,id',
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = MasterRental::whereIn('id', $request->ids)->delete();

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Successfully deleted {$deletedCount} rental(s).",
                ]);
            }

            return redirect()->route('warehouse.master-rentals.index')
                ->with('success', "Successfully deleted {$deletedCount} rental(s).");
        } catch (\Exception $e) {
            DB::rollback();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete rentals: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Failed to delete rentals: '.$e->getMessage());
        }
    }

    /**
     * Toggle rental status
     */
    public function toggleStatus(Request $request, MasterRental $masterRental)
    {
        try {
            $masterRental->is_active = ! $masterRental->is_active;
            $masterRental->updated_by = Auth::id();
            $masterRental->save();

            // Always return JSON for AJAX requests
            return response()->json([
                'status' => 'success',
                'message' => 'Status rental berhasil diubah menjadi '.($masterRental->is_active ? 'aktif' : 'tidak aktif').'.',
                'data' => $masterRental,
            ]);
        } catch (\Exception $e) {
            // Always return JSON for AJAX requests
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get rental statistics
     */
    public function getRentalStatistics()
    {
        try {
            $statistics = [
                'total_rentals' => MasterRental::count(),
                'active_rentals' => MasterRental::where('is_active', true)->count(),
                'inactive_rentals' => MasterRental::where('is_active', false)->count(),
                'categories' => MasterRental::selectRaw('category, COUNT(*) as count')
                    ->groupBy('category')
                    ->get(),
                'service_frequencies' => MasterRental::selectRaw('service_frequency, COUNT(*) as count')
                    ->groupBy('service_frequency')
                    ->get(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get statistics: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get rentals by category
     */
    public function getRentalsByCategory(Request $request)
    {
        try {
            $category = $request->get('category');
            $query = MasterRental::with(['createdBy', 'updatedBy']);

            if ($category) {
                $query->where('category', $category);
            }

            $rentals = $query->orderBy('rental_name')->get();

            return response()->json([
                'status' => 'success',
                'data' => $rentals,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get rentals: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search rentals
     */
    public function searchRentals(Request $request)
    {
        try {
            $search = $request->get('q');
            $query = MasterRental::with(['createdBy', 'updatedBy']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('rental_name', 'like', "%{$search}%")
                        ->orWhere('rental_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            }

            $rentals = $query->orderBy('rental_name')->limit(20)->get();

            return response()->json([
                'status' => 'success',
                'data' => $rentals,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to search rentals: '.$e->getMessage(),
            ], 500);
        }
    }

    // Rental Details
    public function detailsIndex(MasterRental $masterRental)
    {
        $details = $masterRental->details()->with('product')->orderBy('created_at')->paginateStd(25);

        return view('warehouse.master-rentals.details.index', compact('masterRental', 'details'));
    }

    public function detailsShow(MasterRental $masterRental, RentalDetail $detail)
    {
        $detail->load(['productType', 'masterProduct', 'creator', 'updater']);

        // Return JSON for AJAX requests
        return response()->json([
            'status' => 'success',
            'data' => $detail,
        ]);
    }

    public function detailsCreate(MasterRental $masterRental)
    {
        $products = MasterProduct::where('is_active', true)->get();

        return view('warehouse.master-rentals.details.create', compact('masterRental', 'products'));
    }

    public function detailsStore(Request $request, MasterRental $masterRental)
    {
        $request->validate([
            'product_category_id' => 'required|exists:product_categories,id',
            'product_type_id' => 'nullable|exists:product_types,id',
            'master_product_id' => 'nullable|exists:master_products,id',
            'master_product_ids' => 'nullable|array',
            'master_product_ids.*' => 'exists:master_products,id',
            'auto_expand' => 'nullable|boolean',
            'service_frequency_multiplier' => 'required|integer|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'bom_rental_qty' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $masterProductId = $request->master_product_id;
            $multiProductIds = $request->master_product_ids ?? [];
            $autoExpand = $request->boolean('auto_expand');

            if ($autoExpand && empty($multiProductIds)) {
                $multiProductIds = $this->getProductsForRentalDetailScope(
                    (int) $request->product_category_id,
                    $request->product_type_id ? (int) $request->product_type_id : null
                )->pluck('id')->toArray();
            }

            // If multi-select is used but single isn't, use first from multi as primary
            if (! $masterProductId && ! empty($multiProductIds)) {
                $masterProductId = $multiProductIds[0];
            }

            $detail = RentalDetail::create([
                'master_rental_id' => $masterRental->id,
                'product_category_id' => $request->product_category_id,
                'product_type_id' => $request->product_type_id,
                'master_product_id' => $masterProductId,
                'item_type' => $masterProductId ? 'product' : null,
                'item_id' => $masterProductId,
                'auto_expand' => $autoExpand,
                'service_frequency_multiplier' => $request->service_frequency_multiplier,
                'quantity' => $request->quantity ?? 1,
                'bom_rental_qty' => $request->bom_rental_qty ?? 1,
                'unit' => null,
                'created_by' => Auth::id(),
            ]);

            // Sync multi-products to allowedProducts if provided
            if (! empty($multiProductIds)) {
                $syncData = [];
                foreach ($multiProductIds as $index => $pid) {
                    $syncData[$pid] = [
                        'is_selected' => true,
                        'sort_order' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $detail->allowedProducts()->sync($syncData);
            } elseif ($masterProductId) {
                // If only single product, sync it as the only allowed product
                $detail->allowedProducts()->sync([$masterProductId => [
                    'is_selected' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]]);
            }

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Detail Rental berhasil ditambahkan.',
                    'data' => $detail->load(['productType', 'productCategory', 'masterProduct', 'creator', 'updater']),
                ]);
            }

            return redirect()->route('warehouse.master-rentals.show', $masterRental)
                ->with('success', 'Detail Rental berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollback();

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: '.$e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function detailsEdit(MasterRental $masterRental, RentalDetail $detail)
    {
        $products = MasterProduct::where('is_active', true)->get();

        return view('warehouse.master-rentals.details.edit', compact('masterRental', 'detail', 'products'));
    }

    public function detailsUpdate(Request $request, MasterRental $masterRental, RentalDetail $detail)
    {
        $request->validate([
            'product_category_id' => 'required|exists:product_categories,id',
            'product_type_id' => 'nullable|exists:product_types,id',
            'master_product_id' => 'nullable|exists:master_products,id',
            'master_product_ids' => 'nullable|array',
            'master_product_ids.*' => 'exists:master_products,id',
            'auto_expand' => 'nullable|boolean',
            'service_frequency_multiplier' => 'required|integer|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'bom_rental_qty' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $masterProductId = $request->master_product_id;
            $multiProductIds = $request->master_product_ids ?? [];
            $autoExpand = $request->boolean('auto_expand');

            if ($autoExpand && empty($multiProductIds)) {
                $multiProductIds = $this->getProductsForRentalDetailScope(
                    (int) $request->product_category_id,
                    $request->product_type_id ? (int) $request->product_type_id : null
                )->pluck('id')->toArray();
            }

            // If multi-select is used but single isn't, use first from multi as primary
            if (! $masterProductId && ! empty($multiProductIds)) {
                $masterProductId = $multiProductIds[0];
            }

            $detail->update([
                'product_category_id' => $request->product_category_id,
                'product_type_id' => $request->product_type_id,
                'master_product_id' => $masterProductId,
                'item_type' => $masterProductId ? 'product' : null,
                'item_id' => $masterProductId,
                'auto_expand' => $autoExpand,
                'service_frequency_multiplier' => $request->service_frequency_multiplier,
                'quantity' => $request->quantity ?? 1,
                'bom_rental_qty' => $request->bom_rental_qty ?? 1,
                'unit' => null,
                'updated_by' => Auth::id(),
            ]);

            // Sync multi-products to allowedProducts if provided
            if (! empty($multiProductIds)) {
                $syncData = [];
                foreach ($multiProductIds as $index => $pid) {
                    $syncData[$pid] = [
                        'is_selected' => true,
                        'sort_order' => $index,
                    ];
                }
                $detail->allowedProducts()->sync($syncData);
            } elseif ($masterProductId) {
                // If only single product, sync it as the only allowed product
                $detail->allowedProducts()->sync([$masterProductId => [
                    'is_selected' => true,
                    'sort_order' => 0,
                ]]);
            } else {
                // Product selection is optional. Clear stale allowed products when the
                // component/category is changed and no product is selected yet.
                $detail->allowedProducts()->sync([]);
            }

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Detail Rental berhasil diperbarui.',
                    'data' => $detail->load(['productType', 'productCategory', 'masterProduct', 'creator', 'updater']),
                ]);
            }

            return redirect()->route('warehouse.master-rentals.show', $masterRental)
                ->with('success', 'Detail Rental berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: '.$e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function detailsDestroy(Request $request, MasterRental $masterRental, RentalDetail $detail)
    {
        try {
            $detail->delete();

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Detail Rental berhasil dihapus.',
                ]);
            }

            return redirect()->route('warehouse.master-rentals.show', $masterRental)
                ->with('success', 'Detail Rental berhasil dihapus.');
        } catch (\Exception $e) {
            // Return JSON for AJAX requests
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    // Material List Management
    public function getMaterialList(Request $request, MasterRental $masterRental, RentalDetail $detail)
    {
        try {
            // Load allowed products with packaging size and product category for dropdown display
            $detail->load([
                'allowedProducts.packagingSize',
                'allowedProducts.productCategory',
                'allowedProducts.productType',
            ]);
            $scopedProductCategoryId = $detail->product_category_id;
            $scopedProductTypeId = $detail->product_type_id;
            if ($detail->auto_expand) {
                $this->syncAutoExpandedProducts($detail);
                $detail->load([
                    'allowedProducts.packagingSize',
                    'allowedProducts.productCategory',
                    'allowedProducts.productType',
                ]);
            }

            // Map allowed products with packaging size for dropdown
            $allowedProducts = $detail->allowedProducts->map(function ($product) {
                // Ensure packaging_size is a string, not an object
                $packagingSize = null;
                if ($product->packagingSize) {
                    $packagingSize = is_string($product->packagingSize->name)
                        ? $product->packagingSize->name
                        : (string) $product->packagingSize->name;
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku ?? '',
                    'packaging_size' => $packagingSize,
                    'packaging_size_id' => $product->packaging_size_id,
                    'is_unit' => $this->productIsUnit($product),
                    'bom_quantity' => $product->bom_quantity ?? 0,
                ];
            });

            // Check if we only need the selected/allowed products (optimization for Edit Detail modal)
            if ($request->has('mode') && $request->mode === 'selected_only') {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'detail' => $detail,
                        'allowed_product_ids' => $detail->allowedProducts->pluck('id')->toArray(),
                        'allowed_products' => $allowedProducts,
                        'auto_expand' => (bool) $detail->auto_expand,
                        'product_types' => [], // Empty optimized
                        'all_products' => [],   // Empty optimized
                    ],
                ]);
            }

            // Scope selectable products to the detail context so a rental component only sees relevant products.
            $productTypesQuery = \App\Models\ProductCategory::with(['masterProducts' => function ($query) use ($scopedProductCategoryId, $scopedProductTypeId) {
                $query->with(['packagingSize', 'productCategory', 'productType'])
                    ->where('is_active', true)
                    ->when($scopedProductTypeId, fn ($q) => $q->where('product_type_id', $scopedProductTypeId))
                    ->when(! $scopedProductTypeId && $scopedProductCategoryId, fn ($q) => $q->where('product_category_id', $scopedProductCategoryId))
                    ->orderBy('name');
            }])
                ->whereNotNull('sku_prefix')
                ->where('is_active', true)
                ->when($scopedProductCategoryId, fn ($query) => $query->where('id', $scopedProductCategoryId))
                ->orderBy('name');

            $productTypes = $productTypesQuery
                ->get()
                ->filter(fn ($cat) => $cat->masterProducts->isNotEmpty())
                ->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'code' => $cat->code ?? '',
                        'name' => $cat->name,
                        'is_unit' => $cat->is_unit,
                        'products' => $cat->masterProducts->map(function ($product) {
                            return [
                                'id' => $product->id,
                                'sku' => $product->sku ?? '',
                                'name' => $product->name,
                                'packaging_size' => $product->packagingSize ? $product->packagingSize->name : null,
                                'packaging_size_id' => $product->packaging_size_id,
                                'is_unit' => $this->productIsUnit($product),
                                'bom_quantity' => $product->bom_quantity ?? 0,
                            ];
                        })->toArray(),
                        'product_ids' => $cat->masterProducts->pluck('id')->toArray(),
                    ];
                });

            // Get all products only from the same category/type context as the rental detail.
            $allProducts = \App\Models\MasterProduct::with(['packagingSize', 'productCategory', 'productType'])
                ->where('is_active', true)
                ->when($scopedProductTypeId, fn ($query) => $query->where('product_type_id', $scopedProductTypeId))
                ->when(! $scopedProductTypeId && $scopedProductCategoryId, fn ($query) => $query->where('product_category_id', $scopedProductCategoryId))
                ->orderBy('name')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'sku' => $product->sku ?? '',
                        'name' => $product->name,
                        'product_type_id' => $product->product_type_id,
                        'product_category_id' => $product->product_category_id,
                        'packaging_size' => $product->packagingSize ? $product->packagingSize->name : null,
                        'packaging_size_id' => $product->packaging_size_id,
                        'is_unit' => $this->productIsUnit($product),
                        'bom_quantity' => $product->bom_quantity ?? 0,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'detail' => $detail,
                    'allowed_product_ids' => $detail->allowedProducts->pluck('id')->toArray(),
                    'allowed_products' => $allowedProducts, // Include full product details with packaging size
                    'auto_expand' => (bool) $detail->auto_expand,
                    'product_types' => $productTypes,
                    'all_products' => $allProducts,
                    'scope' => [
                        'product_category_id' => $scopedProductCategoryId,
                        'product_type_id' => $scopedProductTypeId,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting material list: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load material list: '.$e->getMessage(),
            ], 500);
        }
    }

    public function saveMaterialList(Request $request, MasterRental $masterRental, RentalDetail $detail)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:master_products,id',
            'auto_expand' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $productIds = collect($request->product_ids)->map(fn ($id) => (int) $id)->unique()->values();
            $allScopedProductIds = $this->getProductsForRentalDetailScope(
                (int) $detail->product_category_id,
                $detail->product_type_id ? (int) $detail->product_type_id : null
            )->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
            $selectedAllScopedProducts = $allScopedProductIds->isNotEmpty()
                && $productIds->sort()->values()->all() === $allScopedProductIds->all();
            $autoExpand = $request->boolean('auto_expand') || $selectedAllScopedProducts;

            $detail->update([
                'item_type' => $productIds->isNotEmpty() ? 'product' : null,
                'item_id' => $productIds->first(),
                'master_product_id' => $productIds->first(),
                'auto_expand' => $autoExpand,
                'updated_by' => Auth::id(),
            ]);

            // Sync products with pivot data
            $syncData = [];
            foreach ($productIds as $index => $productId) {
                $syncData[$productId] = [
                    'is_selected' => true,
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $detail->allowedProducts()->sync($syncData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Material list berhasil disimpan.',
                'data' => $detail->load('allowedProducts'),
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
            ], 422);
        }
    }

    private function productIsUnit(MasterProduct $product): bool
    {
        if ($product->productCategory && $product->productCategory->is_unit !== null) {
            return (bool) $product->productCategory->is_unit;
        }

        if ($product->productType && $product->productType->is_unit !== null) {
            return (bool) $product->productType->is_unit;
        }

        return false;
    }

    private function getProductsForRentalDetailScope(int $productCategoryId, ?int $productTypeId = null)
    {
        return MasterProduct::with(['packagingSize', 'productCategory', 'productType'])
            ->where('is_active', true)
            ->where('product_category_id', $productCategoryId)
            ->when($productTypeId, fn ($query) => $query->where('product_type_id', $productTypeId))
            ->orderBy('name')
            ->get();
    }

    private function syncAutoExpandedProducts(RentalDetail $detail): void
    {
        if (! $detail->product_category_id) {
            return;
        }

        $products = $this->getProductsForRentalDetailScope(
            (int) $detail->product_category_id,
            $detail->product_type_id ? (int) $detail->product_type_id : null
        );

        $syncData = [];
        foreach ($products as $index => $product) {
            $syncData[$product->id] = [
                'is_selected' => true,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $detail->allowedProducts()->sync($syncData);
    }

    // Rental Prices
    public function pricesIndex(MasterRental $masterRental)
    {
        $prices = $masterRental->prices()->with('branch')->orderBy('created_at')->paginateStd(25);

        return view('warehouse.master-rentals.prices.index', compact('masterRental', 'prices'));
    }

    public function pricesCreate(MasterRental $masterRental)
    {
        $branches = Branch::where('is_active', true)->get();

        return view('warehouse.master-rentals.prices.create', compact('masterRental', 'branches'));
    }

    public function pricesStore(Request $request, MasterRental $masterRental)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'daily_price' => 'nullable|numeric|min:0',
            'monthly_price' => 'nullable|numeric|min:0',
            'lost_unit_price' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Check if price already exists for this branch
            $existingPrice = RentalPrice::where('master_rental_id', $masterRental->id)
                ->where('branch_id', $request->branch_id)
                ->first();

            if ($existingPrice) {
                throw new \Exception('Harga untuk cabang ini sudah ada.');
            }

            $price = RentalPrice::create([
                'master_rental_id' => $masterRental->id,
                'branch_id' => $request->branch_id,
                'daily_price' => $request->daily_price ?? $masterRental->daily_price ?? 0,
                'monthly_price' => $request->monthly_price ?? $masterRental->monthly_price ?? 0,
                'lost_unit_price' => $request->lost_unit_price ?? $masterRental->lost_unit_price ?? 0,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Harga Rental berhasil ditambahkan.',
                    'data' => $price->load(['branch', 'creator', 'updater']),
                ]);
            }

            return redirect()->route('warehouse.master-rentals.show', $masterRental)
                ->with('success', 'Harga Rental berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollback();

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: '.$e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function pricesEdit(MasterRental $masterRental, RentalPrice $price)
    {
        $branches = Branch::where('is_active', true)->get();

        return view('warehouse.master-rentals.prices.edit', compact('masterRental', 'price', 'branches'));
    }

    public function pricesUpdate(Request $request, MasterRental $masterRental, RentalPrice $price)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'daily_price' => 'nullable|numeric|min:0',
            'monthly_price' => 'nullable|numeric|min:0',
            'lost_unit_price' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Check if price already exists for this branch (excluding current record)
            $existingPrice = RentalPrice::where('master_rental_id', $masterRental->id)
                ->where('branch_id', $request->branch_id)
                ->where('id', '!=', $price->id)
                ->first();

            if ($existingPrice) {
                throw new \Exception('Harga untuk cabang ini sudah ada.');
            }

            $price->update([
                'branch_id' => $request->branch_id,
                'daily_price' => $request->has('daily_price') ? ($request->daily_price ?? 0) : $price->daily_price,
                'monthly_price' => $request->has('monthly_price') ? ($request->monthly_price ?? 0) : $price->monthly_price,
                'lost_unit_price' => $request->has('lost_unit_price') ? ($request->lost_unit_price ?? 0) : $price->lost_unit_price,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Harga Rental berhasil diperbarui.',
                    'data' => $price->load(['branch', 'creator', 'updater']),
                ]);
            }

            return redirect()->route('warehouse.master-rentals.show', $masterRental)
                ->with('success', 'Harga Rental berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: '.$e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function pricesDestroy(Request $request, MasterRental $masterRental, RentalPrice $price)
    {
        try {
            $price->delete();

            // Return JSON for AJAX requests
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Harga Rental berhasil dihapus.',
                ]);
            }

            return redirect()->route('warehouse.master-rentals.show', $masterRental)
                ->with('success', 'Harga Rental berhasil dihapus.');
        } catch (\Exception $e) {
            // Return JSON for AJAX requests
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }
}

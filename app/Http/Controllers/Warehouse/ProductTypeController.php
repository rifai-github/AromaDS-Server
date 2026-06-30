<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\ProductType;
use App\Models\ProductCategory;
use App\Models\ProductTypeAttribute;
use App\Models\MasterProduct;
use App\Models\MasterOption;
use App\Helpers\UnitHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductTypeController extends Controller
{
    use ColumnFilterTrait;
    public function index(Request $request)
    {
        $query = ProductType::with(['createdBy', 'updatedBy', 'productCategory']);

        // Apply per-column filters (table id: productTypesTable)
        // Apply per-column filters (table id: productTypesTable)
        $this->applyColumnFilters($query, 'productTypesTable', [
            'name' => ['column' => 'name'],
            'product_category__name' => ['relation' => 'productCategory', 'column' => 'name'],
            'sku_prefix' => ['column' => 'sku_prefix'],
            'unit' => ['column' => 'unit'],
            'has_serial_number' => ['column' => 'has_serial_number', 'boolean' => true],
            'is_unit' => ['column' => 'is_unit', 'boolean' => true],
            'is_active' => ['column' => 'is_active', 'boolean' => true],
            'createdBy__name' => ['relation' => 'createdBy', 'column' => 'name'],
            'created_at' => ['column' => 'created_at', 'type' => 'date'],
            'updatedBy__name' => ['relation' => 'updatedBy', 'column' => 'name'],
            'updated_at' => ['column' => 'updated_at', 'type' => 'date'],
        ]);

        // Filter by name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by SKU prefix
        if ($request->filled('sku_prefix')) {
            $query->where('sku_prefix', 'like', '%' . $request->sku_prefix . '%');
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by has serial number
        if ($request->filled('has_serial_number')) {
            $query->where('has_serial_number', $request->has_serial_number);
        }

        // Filter by is unit
        if ($request->filled('is_unit')) {
            $query->where('is_unit', $request->is_unit);
        }

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku_prefix', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['name', 'sku_prefix', 'unit', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $productTypes = $query->paginateStd(25);

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $productTypes->items(),
                'pagination' => [
                    'total' => $productTypes->total(),
                    'per_page' => $productTypes->perPage(),
                    'current_page' => $productTypes->currentPage(),
                    'last_page' => $productTypes->lastPage(),
                    'from' => $productTypes->firstItem(),
                    'to' => $productTypes->lastItem(),
                ]
            ]);
        }

        return view('warehouse.product-types.index', compact('productTypes'));
    }

    public function create()
    {
        // Load product categories for dropdown
        $productCategories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Load unit options from master option 'Product Units'
        $unitOptions = MasterOption::where('name', 'Product Units')
            ->where('is_active', true)
            ->with('optionDetails')
            ->first();
        
        // Return JSON for AJAX requests
        return response()->json([
            'status' => 'success',
            'message' => 'Create form ready',
            'productCategories' => $productCategories,
            'unitOptions' => $unitOptions
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_category_id' => 'required|exists:product_categories,id',
                'name' => 'required|string|max:255',
                'sku_prefix' => 'required|string|max:10|unique:product_types',
                'unit' => ['required', 'string', 'max:50', function ($attribute, $value, $fail) {
                    // Check standard units first
                    if (UnitHelper::isValidUnit($value) || UnitHelper::isValidUnit(strtolower($value))) {
                        return;
                    }

                    // Fallback: Check if it exists in MasterOption 'Product Units'
                    $existsInMasterOption = DB::table('option_details')
                        ->join('master_options', 'option_details.master_option_id', '=', 'master_options.id')
                        ->where('master_options.name', 'Product Units')
                        ->where('option_details.option_name', $value)
                        ->exists();

                    if (!$existsInMasterOption) {
                        $fail('The selected unit is invalid. Please select from the list or standard units.');
                    }
                }],
                'has_serial_number' => 'boolean',
                'is_unit' => 'boolean',
                'is_active' => 'boolean',
                'description' => 'nullable|string|max:1000',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $productType = ProductType::create([
                'product_category_id' => $request->product_category_id,
                'name' => $request->name,
                'sku_prefix' => strtoupper($request->sku_prefix),
                'unit' => $request->unit,
                'has_serial_number' => $request->has_serial_number ?? false,
                'is_unit' => $request->is_unit ?? false,
                'is_active' => $request->is_active ?? true,
                'description' => $request->description,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Product type created successfully.',
                    'data' => $productType->load(['createdBy', 'updatedBy'])
                ]);
            }

            return redirect()->route('warehouse.product-types.show', $productType)
                ->with('success', 'Product type created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create product type: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->withInput()->with('error', 'Failed to create product type: ' . $e->getMessage());
        }
    }

    public function show(Request $request, ProductType $productType)
    {
        $productType->load(['createdBy', 'updatedBy', 'productTypeAttributes', 'productCategory']);
        
        // Always return JSON for AJAX requests
        return response()->json([
            'status' => 'success',
            'data' => $productType
        ]);
    }

    public function edit(Request $request, ProductType $productType)
    {
        $productType->load(['createdBy', 'updatedBy', 'productTypeAttributes', 'productCategory']);
        
        // Load product categories for dropdown
        $productCategories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Load unit options from master option 'Product Units'
        $unitOptions = MasterOption::where('name', 'Product Units')
            ->where('is_active', true)
            ->with('optionDetails')
            ->first();
        
        // Always return JSON for AJAX requests
        return response()->json([
            'status' => 'success',
            'data' => $productType,
            'productCategories' => $productCategories,
            'unitOptions' => $unitOptions
        ]);
    }

    public function update(Request $request, ProductType $productType)
    {
        try {
            $request->validate([
                'product_category_id' => 'required|exists:product_categories,id',
                'name' => 'required|string|max:255',
                'sku_prefix' => 'required|string|max:10|unique:product_types,sku_prefix,' . $productType->id,
                'unit' => ['required', 'string', 'max:50', function ($attribute, $value, $fail) {
                    // Check standard units first
                    if (UnitHelper::isValidUnit($value) || UnitHelper::isValidUnit(strtolower($value))) {
                        return;
                    }

                    // Fallback: Check if it exists in MasterOption 'Product Units'
                    $existsInMasterOption = DB::table('option_details')
                        ->join('master_options', 'option_details.master_option_id', '=', 'master_options.id')
                        ->where('master_options.name', 'Product Units')
                        ->where('option_details.option_name', $value)
                        ->exists();

                    if (!$existsInMasterOption) {
                        $fail('The selected unit is invalid. Please select from the list or standard units.');
                    }
                }],
                'has_serial_number' => 'boolean',
                'is_unit' => 'boolean',
                'is_active' => 'boolean',
                'description' => 'nullable|string|max:1000',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $productType->update([
                'product_category_id' => $request->product_category_id,
                'name' => $request->name,
                'sku_prefix' => strtoupper($request->sku_prefix),
                'unit' => $request->unit,
                'has_serial_number' => $request->has_serial_number ?? false,
                'is_unit' => $request->is_unit ?? false,
                'is_active' => $request->is_active ?? true,
                'description' => $request->description,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Product type updated successfully.',
                    'data' => $productType->load(['createdBy', 'updatedBy'])
                ]);
            }

            return redirect()->route('warehouse.product-types.show', $productType)
                ->with('success', 'Product type updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update product type: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->withInput()->with('error', 'Failed to update product type: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, ProductType $productType)
    {
        try {
            DB::beginTransaction();

            // Check if product type is used by any products
            $hasProducts = $productType->masterProducts()->exists();
            
            if ($hasProducts) {
                throw new \Exception('Cannot delete product type that is used by products.');
            }

            $productType->delete();

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Product type deleted successfully.'
                ]);
            }

            return redirect()->route('warehouse.product-types.index')
                ->with('success', 'Product type deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete product type: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Failed to delete product type: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete product types.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:product_types,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = ProductType::whereIn('id', $request->ids)->delete();

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Successfully deleted {$deletedCount} product type(s)."
                ]);
            }

            return redirect()->route('warehouse.product-types.index')
                ->with('success', "Successfully deleted {$deletedCount} product type(s).");
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to bulk delete product types: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Failed to bulk delete product types: ' . $e->getMessage());
        }
    }

    /**
     * Toggle product type status.
     */
    public function toggleStatus(Request $request, ProductType $productType)
    {
        try {
            $productType->update([
                'is_active' => !$productType->is_active,
                'updated_by' => Auth::id()
            ]);

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Product type status updated successfully.',
                    'data' => $productType
                ]);
            }

            return back()->with('success', 'Product type status updated successfully.');
        } catch (\Exception $e) {
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update product type status: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Failed to update product type status: ' . $e->getMessage());
        }
    }

    /**
     * Get product type statistics.
     */
    public function getProductTypeStatistics(Request $request)
    {
        try {
            $stats = [
                'total' => ProductType::count(),
                'active' => ProductType::where('is_active', true)->count(),
                'inactive' => ProductType::where('is_active', false)->count(),
                'with_serial_number' => ProductType::where('has_serial_number', true)->count(),
                'units' => ProductType::where('is_unit', true)->count(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get product type statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search product types.
     */
    public function searchProductTypes(Request $request)
    {
        try {
            $query = ProductType::with(['createdBy', 'updatedBy']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku_prefix', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('unit', 'like', "%{$search}%");
                });
            }

            $productTypes = $query->orderBy('name')->limit(20)->get();

            return response()->json([
                'status' => 'success',
                'data' => $productTypes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to search product types: ' . $e->getMessage()
            ], 500);
        }
    }

    // Product Type Attributes
    public function attributesIndex(ProductType $productType)
    {
        $attributes = $productType->productTypeAttributes()->orderBy('created_at')->paginateStd(25);
        
        return view('warehouse.product-types.attributes.index', compact('productType', 'attributes'));
    }

    public function attributesCreate(ProductType $productType)
    {
        return view('warehouse.product-types.attributes.create', compact('productType'));
    }

    public function attributesStore(Request $request, ProductType $productType)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'input_type' => 'required|in:text,number,select,textarea,date,boolean',
            'default_value' => 'nullable|string|max:500',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $attribute = ProductTypeAttribute::create([
                'product_type_id' => $productType->id,
                'label' => $request->label,
                'input_type' => $request->input_type,
                'default_value' => $request->default_value,
                'is_required' => $request->is_required ?? false,
                'is_active' => $request->is_active ?? true,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('warehouse.product-types.attributes.index', $productType)
                ->with('success', 'Atribut berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function attributesEdit(ProductType $productType, ProductTypeAttribute $attribute)
    {
        return view('warehouse.product-types.attributes.edit', compact('productType', 'attribute'));
    }

    public function attributesUpdate(Request $request, ProductType $productType, ProductTypeAttribute $attribute)
    {
        $request->validate([
            'label' => 'required|string|max:255',
            'input_type' => 'required|in:text,number,select,textarea,date,boolean',
            'default_value' => 'nullable|string|max:500',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $attribute->update([
                'label' => $request->label,
                'input_type' => $request->input_type,
                'default_value' => $request->default_value,
                'is_required' => $request->is_required ?? false,
                'is_active' => $request->is_active ?? true,
            ]);

            DB::commit();

            return redirect()->route('warehouse.product-types.attributes.index', $productType)
                ->with('success', 'Atribut berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function attributesDestroy(ProductType $productType, ProductTypeAttribute $attribute)
    {
        try {
            $attribute->delete();
            return redirect()->route('warehouse.product-types.attributes.index', $productType)
                ->with('success', 'Atribut berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Get product categories for API
     */
    public function getProductCategories()
    {
        $categories = ProductCategory::where('is_active', true)
            ->whereNotNull('parent_id')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    /**
     * Update ProductTypes without category
     */
    public function updateCategories(Request $request)
    {
        try {
            DB::beginTransaction();

            $stats = [
                'updated' => 0,
                'skipped' => 0,
                'created_default' => 0,
            ];

            // Get ProductTypes without category
            $productTypesWithoutCategory = ProductType::whereNull('product_category_id')->get();

            foreach ($productTypesWithoutCategory as $productType) {
                // Strategy 1: Find category from MasterProduct that uses this ProductType
                $masterProduct = MasterProduct::where('product_type_id', $productType->id)
                    ->whereNotNull('product_category_id')
                    ->first();

                if ($masterProduct && $masterProduct->product_category_id) {
                    $categoryId = $masterProduct->product_category_id;
                    $category = ProductCategory::find($categoryId);
                    
                    if ($category) {
                        $productType->product_category_id = $categoryId;
                        $productType->save();
                        $stats['updated']++;
                        continue;
                    }
                }

                // Strategy 2: Try to find category by name matching
                $categoryName = $this->guessCategoryName($productType);
                if ($categoryName) {
                    $category = ProductCategory::where('name', 'like', "%{$categoryName}%")
                        ->where('is_active', true)
                        ->first();

                    if ($category) {
                        $productType->product_category_id = $category->id;
                        $productType->save();
                        $stats['updated']++;
                        continue;
                    }
                }

                // Strategy 3: Create or use default "Uncategorized" category
                $defaultCategory = ProductCategory::where('name', 'Uncategorized')
                    ->where('is_active', true)
                    ->first();

                if (!$defaultCategory) {
                    $defaultCategory = ProductCategory::create([
                        'code' => 'UNC',
                        'name' => 'Uncategorized',
                        'description' => 'Default category for ProductTypes without category',
                        'sort_order' => 9999,
                        'is_active' => true,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    $stats['created_default']++;
                }

                $productType->product_category_id = $defaultCategory->id;
                $productType->save();
                $stats['updated']++;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Updated {$stats['updated']} ProductTypes. Created {$stats['created_default']} default category.",
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update categories: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guess category name from ProductType
     */
    private function guessCategoryName(ProductType $productType)
    {
        $name = strtolower($productType->name);
        
        $patterns = [
            'rental' => 'Rental',
            'material' => 'Material',
            'refill' => 'Refill',
            'cleaner' => 'Cleaner',
            'dispenser' => 'Rental',
            'unit' => 'Rental',
        ];

        foreach ($patterns as $pattern => $category) {
            if (strpos($name, $pattern) !== false) {
                return $category;
            }
        }

        return null;
    }
}

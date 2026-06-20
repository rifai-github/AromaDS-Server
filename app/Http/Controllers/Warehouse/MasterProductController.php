<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\ProductType;
use App\Models\ProductCategory;
use App\Models\PackagingSize;
use App\Models\Warehouse;
use App\Models\MasterOption;
use App\Models\OptionDetail;
use App\Models\RentalServiceFrequency;
use App\Helpers\UnitHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterProductController extends Controller
{
    use ColumnFilterTrait;

    private function normalizeBrandLineName(?string $value): ?string
    {
        $normalized = trim(strtolower((string) $value));

        return $normalized !== '' ? preg_replace('/\s+/', ' ', $normalized) : null;
    }

    private function getBrandLineNames(): array
    {
        return MasterOption::where('name', 'Brand Lines')
            ->with(['optionDetails' => fn ($query) => $query->where('is_active', true)])
            ->first()
            ?->optionDetails
            ->pluck('option_name')
            ->filter()
            ->mapWithKeys(fn ($name) => [$this->normalizeBrandLineName($name) => $name])
            ->all() ?? [];
    }

    private function resolveCategoryBrandLine(?int $productCategoryId): ?string
    {
        if (!$productCategoryId) {
            return null;
        }

        $brandLines = $this->getBrandLineNames();
        if (empty($brandLines)) {
            return null;
        }

        $category = ProductCategory::with('parent.parent.parent')->find($productCategoryId);
        while ($category) {
            $normalizedCategoryName = $this->normalizeBrandLineName($category->name);
            if ($normalizedCategoryName && isset($brandLines[$normalizedCategoryName])) {
                return $brandLines[$normalizedCategoryName];
            }

            $category = $category->parent;
        }

        return null;
    }

    private function validateBrandLineAgainstCategory(Request $request): void
    {
        $categoryBrandLine = $this->resolveCategoryBrandLine((int) $request->product_category_id);
        if (!$categoryBrandLine) {
            return;
        }

        $selectedBrandLine = $request->brand_line;
        if ($this->normalizeBrandLineName($selectedBrandLine) !== $this->normalizeBrandLineName($categoryBrandLine)) {
            abort(response()->json([
                'status' => 'error',
                'message' => "Brand Line harus {$categoryBrandLine} karena Product Category yang dipilih berada di struktur {$categoryBrandLine}.",
            ], 422));
        }
    }

    /**
     * Get product types (sub-categories) by category
     * Returns leaf categories under the selected parent as 'product types'
     */
    public function getProductTypesByCategory(Request $request)
    {
        $categoryId = $request->get('category_id');
        
        if (!$categoryId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Category ID is required'
            ], 400);
        }
        
        // Get child categories (sub-categories) of the selected category
        $subCategories = ProductCategory::where('parent_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Also keep backward compatibility: try ProductType if no sub-categories found
        if ($subCategories->isEmpty()) {
            $productTypes = ProductType::where('product_category_id', $categoryId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'productTypes' => $productTypes,
                'source' => 'product_types'
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'productTypes' => $subCategories,
            'source' => 'product_categories'
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MasterProduct::with(['productCategory', 'packagingSize', 'createdBy', 'updatedBy', 'warehouses.branch']);

        // Filter by specific top-level parameters (if needed for backward compatibility or special logic)
        if ($request->filled('product_category_id') && !$request->has('filter')) {
            $query->where('product_category_id', $request->product_category_id);
        }

        // Apply standardized filter
        $query->filter($request->all());
        
        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['name', 'variant_name', 'brand_line', 'sku', 'sku_code', 'unit', 'minimum_stock', 'maximum_stock', 'is_active', 'created_at', 'updated_at'];
        
        if ($sortBy === 'packaging_size') {
            $query->orderByRaw("(SELECT name FROM packaging_sizes WHERE packaging_sizes.id = master_products.packaging_size_id) {$sortOrder}");
        } elseif (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginateStd(25);
        
        $productTypes = ProductType::orderBy('name')->get();
        $packagingSizes = \App\Models\PackagingSize::active()->ordered()->get();
        
        // Load Brand Lines and Product Variants
        $brandLines = MasterOption::where('name', 'Brand Lines')
            ->with(['optionDetails' => function($query) {
                $query->where('is_active', true)->orderBy('option_name');
            }])
            ->first();
            
        // Use the new BrandVariant model
        $brandVariants = \App\Models\BrandVariant::where('is_active', true)
            ->with('brandLine')
            ->orderBy('name')
            ->get()
            ->map(function($variant) {
                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'brand_line_name' => $variant->brandLine ? $variant->brandLine->option_name : null
                ];
            });

        $statistics = [
            'total' => MasterProduct::count(),
            'active' => MasterProduct::where('is_active', true)->count(),
            'inactive' => MasterProduct::where('is_active', false)->count(),
            'low_stock' => MasterProduct::whereRaw('minimum_stock > 0')->count(),
        ];

        // Return JSON for AJAX requests or API requests
        if ($request->ajax() || request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'status' => 'success',
                'data' => $products->items(),
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ]
            ]);
        }

        return view('warehouse.master-products.index', compact('products', 'productTypes', 'packagingSizes', 'brandLines', 'brandVariants', 'statistics'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $productTypes = ProductType::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $productCategories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $packagingSizes = PackagingSize::where('is_active', true)->orderBy('sort_order')->get();
        
        // Load brand lines and product variants from master options
        $brandLines = MasterOption::where('name', 'Brand Lines')
            ->where('is_active', true)
            ->with('optionDetails')
            ->first();
            
        // Use the new BrandVariant model
        // Initialize as empty - will be populated via AJAX when Brand Line is selected
        $brandVariants = [];

        // Load unit options
        $unitOptions = MasterOption::where('name', 'Product Units')
            ->where('is_active', true)
            ->with('optionDetails')
            ->first();

        // Load service frequencies
        $serviceFrequencies = RentalServiceFrequency::active()
            ->ordered()
            ->get();

        // Load unit options from UnitHelper
        $unitHelperOptions = UnitHelper::getUnitOptions();

        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Create form ready',
                'productTypes' => $productTypes,
                'warehouses' => $warehouses,
                'productCategories' => $productCategories,
                'packagingSizes' => $packagingSizes,
                'brandLines' => $brandLines,
                'brandVariants' => $brandVariants,
                'unitOptions' => $unitOptions,
                'serviceFrequencies' => $serviceFrequencies,
                'unitHelperOptions' => $unitHelperOptions
            ]);
        }

        return view('warehouse.master-products.create', compact('productTypes', 'warehouses', 'productCategories', 'packagingSizes', 'brandLines', 'brandVariants', 'unitOptions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'variant_name' => 'nullable|string|max:255',
            'brand_line' => 'nullable|string|max:255',
            'packaging_size_id' => 'nullable|exists:packaging_sizes,id',
            'sku' => 'nullable|string|max:100|unique:master_products,sku',
            'sku_code' => 'nullable|string|max:100|unique:master_products,sku_code',
            'product_type_id' => 'nullable|exists:product_types,id',
            'product_category_id' => 'required|exists:product_categories,id',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'minimum_stock' => 'required|integer|min:0',
            'maximum_stock' => 'required|integer|min:0|gte:minimum_stock',
            'universal_code_type' => 'nullable|string|max:50',
            'universal_code' => 'nullable|string|max:100',
            'bom_quantity' => 'required|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'last_unit_price' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
            'part_no' => 'nullable|string|max:100',
            'net_weight' => 'nullable|numeric|min:0',
            'gross_weight' => 'nullable|numeric|min:0',
            'lifetime' => 'nullable|integer|min:0',
            'unit_order' => 'nullable|string|max:50',
            'frequency_service' => 'nullable|string|max:50',
            'is_trading' => 'nullable|boolean',
            'is_stock_substitute' => 'nullable|boolean',
            'dimensions' => 'nullable|string|max:255',
        ]);

        $isUnitCategory = (bool) ProductCategory::whereKey($request->product_category_id)->value('is_unit');
        if (!$isUnitCategory) {
            $this->validateBrandLineAgainstCategory($request);
        }

        try {
            DB::beginTransaction();

            // Auto-generate SKU if not provided
            $sku = $request->sku;
            if (empty($sku)) {
                $lastProduct = MasterProduct::withTrashed()
                    ->where('sku', 'like', 'PRD%')
                    ->orderByRaw('CAST(SUBSTRING(sku, 4) AS UNSIGNED) DESC')
                    ->first();
                
                if ($lastProduct) {
                    $lastNumber = (int) substr($lastProduct->sku, 3);
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 1;
                }
                
                do {
                    $sku = 'PRD' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                    $exists = MasterProduct::withTrashed()->where('sku', $sku)->exists();
                    if ($exists) {
                        $nextNumber++;
                    }
                } while ($exists);
            }

            $product = MasterProduct::create([
                'name' => $request->name,
                'packaging_size_id' => $isUnitCategory ? null : $request->packaging_size_id,
                'variant_name' => $isUnitCategory ? null : $request->variant_name,
                'brand_line' => $isUnitCategory ? null : $request->brand_line,
                'sku' => $sku,
                'sku_code' => $request->sku_code ?? $sku,
                'product_type_id' => $request->product_type_id,
                'product_category_id' => $request->product_category_id,
                'description' => $request->description,
                'unit' => $request->unit,
                'minimum_stock' => $request->minimum_stock ?? 0,
                'maximum_stock' => $request->maximum_stock ?? 0,
                'universal_code_type' => $request->universal_code_type,
                'universal_code' => $request->universal_code,
                'bom_quantity' => $request->bom_quantity ?? null,
                'unit_price' => $request->unit_price,
                'last_unit_price' => $request->last_unit_price ?? 0,
                'is_active' => $request->is_active,
                'part_no' => $request->part_no,
                'dimensions' => $request->dimensions,
                'net_weight' => $request->net_weight,
                'gross_weight' => $request->gross_weight,
                'lifetime' => $request->lifetime,
                'unit_order' => $request->unit_order,
                'frequency_service' => $request->frequency_service,
                'is_trading' => $request->is_trading ?? false,
                'is_stock_substitute' => $request->is_stock_substitute ?? false,
                'description_2' => $request->description_2,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            if ($request->hasFile('product_photo')) {
                $file = $request->file('product_photo');
                $filename = 'product_' . $product->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/products'), $filename);
                $product->product_photo = '/uploads/products/' . $filename;
                $product->save();
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Master product created successfully.',
                    'data' => $product
                ]);
            }

            return redirect()->route('warehouse.master-products.index')
                ->with('success', 'Master product created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create master product: ' . $e->getMessage()
                ], 422);
            }
            return back()->with('error', 'Failed to create master product: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $masterProduct = MasterProduct::withTrashed()->find($id);
        if (!$masterProduct) {
            if (request()->ajax()) return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
            return abort(404);
        }
        
        $masterProduct->load(['productType', 'productCategory', 'packagingSize', 'createdBy', 'updatedBy', 'warehouseProducts.warehouse.branch']);

        $statistics = [
            'total_warehouses' => $masterProduct->warehouseProducts->count(),
            'total_stock' => $masterProduct->warehouseProducts->sum('quantity'),
            'low_stock_warehouses' => $masterProduct->warehouseProducts->where('quantity', '<=', 'minimum_stock')->count(),
            'out_of_stock_warehouses' => $masterProduct->warehouseProducts->where('quantity', 0)->count(),
            'is_low_stock' => $masterProduct->minimum_stock > 0,
            'stock_status' => $masterProduct->minimum_stock > 0 ? 'Low Stock' : 'Normal',
        ];

        if (request()->ajax()) {
            return response()->json(['status' => 'success', 'data' => $masterProduct, 'statistics' => $statistics]);
        }
        return view('warehouse.master-products.show', compact('masterProduct', 'statistics'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $masterProduct = MasterProduct::withTrashed()->find($id);
        if (!$masterProduct) {
            if (request()->ajax()) return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
            return abort(404);
        }
        
        $masterProduct->load(['productType', 'createdBy', 'updatedBy']);
        $productTypes = ProductType::orderBy('name')->get();
        $productCategories = ProductCategory::where('is_active', true)->orderBy('name')->get();
        $packagingSizes = PackagingSize::where('is_active', true)->orderBy('sort_order')->get();
        
        $brandLines = MasterOption::where('name', 'Brand Lines')->where('is_active', true)->with('optionDetails')->first();
        $brandVariants = [];
        $brandLineName = $masterProduct->brand_line;
        
        if ($brandLineName) {
            $brandLineDetail = OptionDetail::whereHas('masterOption', fn($q) => $q->where('name', 'Brand Lines'))->where('option_name', $brandLineName)->first();
            if ($brandLineDetail) {
                $brandVariants = \App\Models\BrandVariant::where('brand_line_id', $brandLineDetail->id)->where('is_active', true)->orderBy('name')->with('brandLine')->get();
            }
        }

        if ($brandVariants instanceof \Illuminate\Database\Eloquent\Collection) {
            $brandVariants = $brandVariants->map(fn($v) => ['id' => $v->id, 'name' => $v->name, 'brand_line_name' => $v->brandLine ? $v->brandLine->option_name : null]);
        }

        $unitOptions = MasterOption::where('name', 'Product Units')->where('is_active', true)->with('optionDetails')->first();
        $serviceFrequencies = RentalServiceFrequency::active()->ordered()->get();
        $unitHelperOptions = UnitHelper::getUnitOptions();

        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $masterProduct,
                'productTypes' => $productTypes,
                'productCategories' => $productCategories,
                'packagingSizes' => $packagingSizes,
                'brandLines' => $brandLines,
                'brandVariants' => $brandVariants,
                'unitOptions' => $unitOptions,
                'serviceFrequencies' => $serviceFrequencies,
                'unitHelperOptions' => $unitHelperOptions
            ]);
        }

        return view('warehouse.master-products.edit', compact('masterProduct', 'productTypes', 'productCategories', 'packagingSizes', 'brandLines', 'brandVariants', 'unitOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $masterProduct = MasterProduct::withTrashed()->find($id);
        if (!$masterProduct) {
            if (request()->ajax()) return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
            return abort(404);
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'variant_name' => 'nullable|string|max:255',
            'brand_line' => 'nullable|string|max:255',
            'packaging_size_id' => 'nullable|exists:packaging_sizes,id',
            'sku' => 'nullable|string|max:100|unique:master_products,sku,' . $masterProduct->id,
            'sku_code' => 'nullable|string|max:100|unique:master_products,sku_code,' . $masterProduct->id,
            'product_type_id' => 'nullable|exists:product_types,id',
            'product_category_id' => 'required|exists:product_categories,id',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'minimum_stock' => 'required|integer|min:0',
            'maximum_stock' => 'required|integer|min:0|gte:minimum_stock',
            'universal_code_type' => 'nullable|string|max:50',
            'universal_code' => 'nullable|string|max:100',
            'bom_quantity' => 'required|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'last_unit_price' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
            'part_no' => 'nullable|string|max:100',
            'net_weight' => 'nullable|numeric|min:0',
            'gross_weight' => 'nullable|numeric|min:0',
            'lifetime' => 'nullable|integer|min:0',
            'unit_order' => 'nullable|string|max:50',
            'frequency_service' => 'nullable|string|max:50',
            'is_trading' => 'nullable|boolean',
            'is_stock_substitute' => 'nullable|boolean',
            'dimensions' => 'nullable|string|max:255',
        ]);

        $isUnitCategory = (bool) ProductCategory::whereKey($request->product_category_id)->value('is_unit');
        if (!$isUnitCategory) {
            $this->validateBrandLineAgainstCategory($request);
        }

        try {
            DB::beginTransaction();

            $masterProduct->update([
                'name' => $request->name,
                'variant_name' => $isUnitCategory ? null : $request->variant_name,
                'brand_line' => $isUnitCategory ? null : $request->brand_line,
                'packaging_size_id' => $isUnitCategory ? null : $request->packaging_size_id,
                'sku' => $request->sku ?? $masterProduct->sku,
                'product_type_id' => $request->product_type_id,
                'product_category_id' => $request->product_category_id,
                'description' => $request->description,
                'unit' => $request->unit,
                'minimum_stock' => $request->minimum_stock ?? 0,
                'maximum_stock' => $request->maximum_stock ?? 0,
                'universal_code_type' => $request->universal_code_type,
                'universal_code' => $request->universal_code,
                'bom_quantity' => $request->bom_quantity ?? $masterProduct->bom_quantity,
                'unit_price' => $request->unit_price ?? $masterProduct->unit_price,
                'last_unit_price' => $request->last_unit_price ?? $masterProduct->last_unit_price,
                'is_active' => $request->is_active,
                'part_no' => $request->part_no,
                'dimensions' => $request->dimensions,
                'net_weight' => $request->net_weight,
                'gross_weight' => $request->gross_weight,
                'lifetime' => $request->lifetime,
                'unit_order' => $request->unit_order,
                'frequency_service' => $request->frequency_service,
                'is_trading' => $request->is_trading ?? false,
                'is_stock_substitute' => $request->is_stock_substitute ?? false,
                'description_2' => $request->description_2,
                'updated_by' => Auth::id(),
            ]);

            // Keep every warehouse's min/max stock in sync with the master product's standard.
            $masterProduct->warehouseProducts()->update([
                'minimum_stock' => $masterProduct->minimum_stock,
                'maximum_stock' => $masterProduct->maximum_stock,
                'updated_by' => Auth::id(),
            ]);

            if ($request->hasFile('product_photo')) {
                $file = $request->file('product_photo');
                if ($masterProduct->product_photo && file_exists(public_path($masterProduct->product_photo))) {
                    unlink(public_path($masterProduct->product_photo));
                }
                $uploadPath = public_path('uploads/products');
                if (!file_exists($uploadPath)) mkdir($uploadPath, 0755, true);
                $filename = 'product_' . $masterProduct->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move($uploadPath, $filename);
                $masterProduct->product_photo = '/uploads/products/' . $filename;
                $masterProduct->save();
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Master product updated successfully.',
                    'data' => $masterProduct
                ]);
            }

            return redirect()->route('warehouse.master-products.show', $masterProduct)
                ->with('success', 'Master product updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update master product: ' . $e->getMessage()
                ], 422);
            }
            return back()->with('error', 'Failed to update master product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $masterProduct = MasterProduct::withTrashed()->find($id);
        if (!$masterProduct) {
            if (request()->ajax()) return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
            return abort(404);
        }
        
        try {
            DB::beginTransaction();
            $hasTransactions = $masterProduct->warehouseProducts()->where('quantity', '>', 0)->exists();
            if ($hasTransactions) {
                $errorMessage = 'Cannot delete product that has stock in warehouses.';
                if (request()->ajax()) return response()->json(['status' => 'error', 'message' => $errorMessage], 422);
                return back()->with('error', $errorMessage);
            }
            $masterProduct->update(['is_active' => false]);
            DB::commit();
            if (request()->ajax()) return response()->json(['status' => 'success', 'message' => 'Product deactivated successfully.']);
            return redirect()->route('warehouse.master-products.index')->with('success', 'Product deactivated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            if (request()->ajax()) return response()->json(['status' => 'error', 'message' => 'Failed to deactivate product: ' . $e->getMessage()], 500);
            return back()->with('error', 'Failed to deactivate product: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete products.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        if (empty($ids)) return response()->json(['status' => 'error', 'message' => 'No products selected.'], 422);

        try {
            DB::beginTransaction();
            $products = MasterProduct::whereIn('id', $ids)->get();
            $deletedCount = 0;
            $errors = [];

            foreach ($products as $product) {
                if ($product->warehouseProducts()->where('quantity', '>', 0)->exists()) {
                    $errors[] = "Product '{$product->name}' cannot be deleted because it has stock.";
                    continue;
                }
                $product->update(['is_active' => false]);
                $deletedCount++;
            }

            DB::commit();
            return response()->json([
                'status' => $deletedCount > 0 ? 'success' : 'error',
                'message' => "Successfully deactivated {$deletedCount} product(s)." . (count($errors) > 0 ? " " . implode(' ', $errors) : ""),
                'count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => 'Bulk delete failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Toggle product status.
     */
    public function toggleStatus(MasterProduct $masterProduct)
    {
        try {
            $masterProduct->update([
                'is_active' => !$masterProduct->is_active,
                'updated_by' => Auth::id(),
            ]);
            return back()->with('success', 'Product status updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update product status: ' . $e->getMessage());
        }
    }

    /**
     * Get products by type for API.
     */
    public function getProductsByType(Request $request)
    {
        $request->validate(['product_type_id' => 'required|exists:product_types,id']);
        $products = MasterProduct::where('product_type_id', $request->product_type_id)->active()->with(['productType', 'warehouses.warehouse'])->orderBy('name')->get();
        return response()->json(['status' => 'success', 'data' => $products]);
    }

    /**
     * Get product statistics for API.
     */
    public function getProductStatistics()
    {
        $statistics = [
            'total' => MasterProduct::count(),
            'active' => MasterProduct::where('is_active', true)->count(),
            'inactive' => MasterProduct::where('is_active', false)->count(),
            'low_stock' => MasterProduct::whereHas('warehouses', fn($q) => $q->whereRaw('quantity <= minimum_stock'))->count(),
            'out_of_stock' => MasterProduct::whereHas('warehouses', fn($q) => $q->where('quantity', 0))->count(),
        ];
        return response()->json(['status' => 'success', 'data' => $statistics]);
    }

    /**
     * Search products for API.
     */
    public function searchProducts(Request $request)
    {
        $request->validate(['search' => 'required|string|min:2']);
        $products = MasterProduct::active()
            ->where(fn($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('sku', 'like', "%{$request->search}%"))
            ->with(['productType', 'warehouses.warehouse'])
            ->orderBy('name')
            ->limit(10)
            ->get();
        return response()->json(['status' => 'success', 'data' => $products]);
    }
}

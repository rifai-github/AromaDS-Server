<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\OptionDetail;
use App\Models\MasterProduct;
use App\Models\ProductPhoto;
use App\Services\PhotoUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductStructureController extends Controller
{
    use ColumnFilterTrait;

    protected $photoUploadService;

    public function __construct(PhotoUploadService $photoUploadService)
    {
        $this->photoUploadService = $photoUploadService;
    }

    // Product Categories Management
    public function categories(Request $request)
    {
        $query = ProductCategory::with(['parent', 'children', 'createdBy', 'updatedBy'])
            ->withCount('masterProducts')
            ->withExists([
                'masterProducts as serial_required_products_exists' => function ($query) {
                    $query->whereHas('serialNumbers')
                        ->orWhereHas('productType', fn ($typeQuery) => $typeQuery->where('has_serial_number', true));
                },
            ]);

        // Apply column filters
        $this->applyColumnFilters($query, 'productCategoriesTable', [
            'name' => ['column' => 'name'],
            'code' => ['column' => 'code'],
            'parent__name' => ['relation' => 'parent', 'column' => 'name'],
            'sort_order' => ['column' => 'sort_order'],
            'is_active' => ['column' => 'is_active', 'boolean' => true],
            'sku_prefix' => ['column' => 'sku_prefix'],
            'unit' => ['column' => 'unit'],
            'has_serial_number' => ['column' => 'has_serial_number', 'boolean' => true],
            'is_unit' => ['column' => 'is_unit', 'boolean' => true],
            'createdBy__name' => ['relation' => 'createdBy', 'column' => 'name'],
            'created_at' => ['column' => 'created_at', 'type' => 'date'],
            'updatedBy__name' => ['relation' => 'updatedBy', 'column' => 'name'],
            'updated_at' => ['column' => 'updated_at', 'type' => 'date'],
        ]);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $sortBy = $request->get('sort_by', 'sort_order');
        $sortDirection = $request->get('sort_direction', 'asc');
        
        // Validate sort direction to prevent SQL injection
        if (!in_array(strtolower($sortDirection), ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }
        
        $allowedSortFields = ['name', 'code', 'sort_order', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('sort_order')->orderBy('name');
        }

        $categories = $query->paginateStd(25);
        $parentCategories = ProductCategory::whereNull('parent_id')->orderBy('name')->get();
        $unitOptions = $this->getUnitOptions();

        return view('warehouse.product-structure.categories', compact('categories', 'parentCategories', 'unitOptions'));
    }

    private function normalizeSerialPolicyData(array $data): array
    {
        if (ProductCategory::hasMandatorySerialPolicy($data['code'] ?? null, $data['name'] ?? null)) {
            $data['has_serial_number'] = true;
        }

        return $data;
    }

    private function getUnitOptions()
    {
        return OptionDetail::where('master_option_id', 46)
            ->where('is_active', true)
            ->orderBy('option_name')
            ->get();
    }

    private function validUnitValues(): array
    {
        return $this->getUnitOptions()
            ->pluck('option_name')
            ->map(fn ($unit) => trim((string) $unit))
            ->filter()
            ->values()
            ->all();
    }

    public function showCategory($id)
    {
        try {
            // Find category including soft deleted ones
            $category = ProductCategory::withTrashed()->find($id);
            
            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found'
                ], 404);
            }
            
            // Check if category is soft deleted
            if ($category->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category has been deleted'
                ], 404);
            }
            
            $category->load(['parent', 'children', 'createdBy', 'updatedBy'])
                ->loadCount('masterProducts')
                ->loadExists([
                    'masterProducts as serial_required_products_exists' => function ($query) {
                        $query->whereHas('serialNumbers')
                            ->orWhereHas('productType', fn ($typeQuery) => $typeQuery->where('has_serial_number', true));
                    },
                ]);
            
            // Count products in this category
            $productsCount = $category->master_products_count;
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'description' => $category->description,
                    'parent_id' => $category->parent_id,
                    'parent' => $category->parent,
                    'sort_order' => $category->sort_order,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'sku_prefix' => $category->sku_prefix,
                    'unit' => $category->unit,
                    'has_serial_number' => $category->effective_has_serial_number,
                    'raw_has_serial_number' => $category->has_serial_number,
                    'mandatory_serial_policy' => ProductCategory::hasMandatorySerialPolicy($category->code, $category->name),
                    'is_unit' => $category->is_unit,
                    'is_active' => $category->is_active,
                    'products_count' => $productsCount,
                    'created_by' => $category->createdBy,
                    'updated_by' => $category->updatedBy,
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeCategory(Request $request)
    {
        $validUnits = $this->validUnitValues();

        $validator = \Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:product_categories,code,NULL,id,deleted_at,NULL',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_categories,id,deleted_at,NULL',
            'sort_order' => 'required|integer|min:0',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7|regex:/^#?[0-9A-Fa-f]{6}$/',
            'is_active' => 'nullable|boolean',
            // Technical fields (Product Type merged)
            'sku_prefix' => 'nullable|string|max:50',
            'unit' => ['nullable', 'string', 'max:50', \Illuminate\Validation\Rule::in($validUnits)],
            'has_serial_number' => 'nullable|boolean',
            'is_unit' => 'nullable|boolean',
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

            // Process form data
            $data = [
                'code' => strtoupper(trim($request->code)),
                'name' => trim($request->name),
                'description' => $request->description ? trim($request->description) : null,
                'parent_id' => $request->parent_id ?: null,
                'sort_order' => (int) $request->sort_order,
                'icon' => $request->icon ? trim($request->icon) : null,
                'color' => $request->color ? (str_starts_with($request->color, '#') ? $request->color : '#' . $request->color) : null,
                'is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN),
                // Technical fields (only meaningful for child categories)
                'sku_prefix' => $request->sku_prefix ? trim($request->sku_prefix) : null,
                'unit' => $request->unit ? trim($request->unit) : null,
                'has_serial_number' => filter_var($request->has_serial_number, FILTER_VALIDATE_BOOLEAN),
                'is_unit' => filter_var($request->is_unit, FILTER_VALIDATE_BOOLEAN),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ];

            $category = ProductCategory::create($this->normalizeSerialPolicyData($data));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Product category created successfully.',
                'data' => $category->load(['parent', 'createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create product category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateCategory(Request $request, $id)
    {
        try {
            // Find category including soft deleted ones
            $category = ProductCategory::withTrashed()->find($id);
            
            if (!$category) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category not found'
                ], 404);
            }
            
            // Check if category is soft deleted
            if ($category->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Category has been deleted'
                ], 404);
            }
            
            $validUnits = $this->validUnitValues();

            $validator = \Validator::make($request->all(), [
                'code' => 'required|string|max:50|unique:product_categories,code,' . $category->id . ',id,deleted_at,NULL',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:product_categories,id,deleted_at,NULL|not_in:' . $category->id,
                'sort_order' => 'required|integer|min:0',
                'icon' => 'nullable|string|max:255',
                'color' => 'nullable|string|max:7|regex:/^#?[0-9A-Fa-f]{6}$/',
                'is_active' => 'nullable|boolean',
                // Technical fields (Product Type merged)
                'sku_prefix' => 'nullable|string|max:50',
                'unit' => ['nullable', 'string', 'max:50', \Illuminate\Validation\Rule::in($validUnits)],
                'has_serial_number' => 'nullable|boolean',
                'is_unit' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Process form data
            $data = [
                'code' => strtoupper(trim($request->code)),
                'name' => trim($request->name),
                'description' => $request->description ? trim($request->description) : null,
                'parent_id' => $request->parent_id ?: null,
                'sort_order' => (int) $request->sort_order,
                'icon' => $request->icon ? trim($request->icon) : null,
                'color' => $request->color ? (str_starts_with($request->color, '#') ? $request->color : '#' . $request->color) : null,
                'is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN),
                // Technical fields (only meaningful for child categories)
                'sku_prefix' => $request->sku_prefix ? trim($request->sku_prefix) : null,
                'unit' => $request->unit ? trim($request->unit) : null,
                'has_serial_number' => filter_var($request->has_serial_number, FILTER_VALIDATE_BOOLEAN),
                'is_unit' => filter_var($request->is_unit, FILTER_VALIDATE_BOOLEAN),
                'updated_by' => Auth::id()
            ];

            $category->update($this->normalizeSerialPolicyData($data));

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Product category updated successfully.',
                'data' => $category->load(['parent', 'createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update product category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyCategory(ProductCategory $category)
    {
        try {
            // Check if category has subcategories
            if ($category->children()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete category with subcategories. Please delete subcategories first.',
                    'code' => 'HAS_SUBCATEGORIES'
                ], 422);
            }

            // Check if category has products
            if ($category->masterProducts()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete category with products. Please move or delete products first.',
                    'code' => 'HAS_PRODUCTS'
                ], 422);
            }

            // Perform soft delete
            $category->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product category deleted successfully.',
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete product category: ' . $e->getMessage(), [
                'category_id' => $category->id,
                'user_id' => Auth::id(),
                'exception' => $e
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete product category: ' . $e->getMessage(),
                'code' => 'DELETE_FAILED'
            ], 500);
        }
    }

    // Product Photos Management
    public function photos(MasterProduct $product)
    {
        $photos = $product->photos()->orderBy('sort_order')->orderBy('created_at')->get();
        return view('warehouse.product-structure.photos', compact('product', 'photos'));
    }

    public function storePhoto(Request $request, MasterProduct $product)
    {
        $validator = \Validator::make($request->all(), [
            'photo' => 'required|file|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'alt_text' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'required|integer|min:0',
            'is_primary' => 'boolean'
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

            $uploadResult = $this->photoUploadService->uploadPhoto(
                $request->file('photo'),
                'product_photo'
            );

            if (!$uploadResult['success']) {
                throw new \Exception($uploadResult['message']);
            }

            if ($request->is_primary) {
                $product->photos()->update(['is_primary' => false]);
            }

            $photo = ProductPhoto::create([
                'master_product_id' => $product->id,
                'file_name' => $uploadResult['file_name'],
                'file_path' => $uploadResult['file_path'],
                'file_url' => $uploadResult['file_url'],
                'file_type' => $uploadResult['file_type'],
                'file_size' => $uploadResult['file_size'],
                'alt_text' => $request->alt_text,
                'description' => $request->description,
                'sort_order' => $request->sort_order,
                'is_primary' => $request->is_primary ?? false,
                'is_active' => true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Product photo uploaded successfully.',
                'data' => $photo
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload product photo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyPhoto(ProductPhoto $photo)
    {
        try {
            if (\Storage::exists($photo->file_path)) {
                \Storage::delete($photo->file_path);
            }

            $photo->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Product photo deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete product photo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function setPrimaryPhoto(ProductPhoto $photo)
    {
        try {
            DB::beginTransaction();

            $photo->masterProduct->photos()->update(['is_primary' => false]);
            $photo->update(['is_primary' => true, 'updated_by' => Auth::id()]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Primary photo set successfully.',
                'data' => $photo
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to set primary photo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function structure()
    {
        $rootCategories = ProductCategory::whereNull('parent_id')
            ->with(['allChildren', 'masterProducts'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('warehouse.product-structure.structure', compact('rootCategories'));
    }
}

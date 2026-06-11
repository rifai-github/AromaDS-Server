<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\PackagingSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PackagingSizeController extends Controller
{
    use ColumnFilterTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PackagingSize::with(['createdBy', 'updatedBy']);

        // Apply per-column filters (table id: packagingSizesTable)
        $this->applyColumnFilters($query, 'packagingSizesTable', [
            'name' => ['column' => 'name'],
            'code' => ['column' => 'code'],
            'description' => ['column' => 'description'],
            'sort_order' => ['column' => 'sort_order'],
            'master_product__name' => ['relation' => 'masterProducts', 'column' => 'name'],
            'is_active' => ['column' => 'is_active', 'boolean' => true],
        ]);

        // Filter by name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by code
        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'asc';
        
        $allowedSortFields = ['name', 'code', 'sort_order', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('sort_order')->orderBy('name');
        }

        $packagingSizes = $query->paginate(15);

        return view('warehouse.packaging-sizes.index', compact('packagingSizes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('warehouse.packaging-sizes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('PackagingSize store request (pre-validation):', $request->all());

        if ($request->is_active === 'on') {
            $request->merge(['is_active' => true]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:packaging_sizes,code',
            'description' => 'nullable|string',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean'
        ]);

        try {
            \Log::info('PackagingSize store request:', $request->all());
            DB::beginTransaction();

            $packagingSize = PackagingSize::create([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'description' => $request->description,
                'sort_order' => $request->sort_order,
                'is_active' => $request->is_active ?? true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);
            \Log::info('PackagingSize created:', $packagingSize->toArray());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Packaging size created successfully.',
                'data' => $packagingSize->load(['createdBy', 'updatedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create packaging size: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PackagingSize $packagingSize)
    {
        $packagingSize->load(['createdBy', 'updatedBy']);
        
        // Count products using this packaging size
        $productsCount = $packagingSize->products()->count();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $packagingSize->id,
                'name' => $packagingSize->name,
                'code' => $packagingSize->code,
                'description' => $packagingSize->description,
                'sort_order' => $packagingSize->sort_order,
                'is_active' => $packagingSize->is_active,
                'products_count' => $productsCount,
                'created_by' => $packagingSize->createdBy,
                'updated_by' => $packagingSize->updatedBy,
                'created_at' => $packagingSize->created_at,
                'updated_at' => $packagingSize->updated_at
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PackagingSize $packagingSize)
    {
        $packagingSize->load(['createdBy', 'updatedBy']);
        
        // Return JSON for AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $packagingSize
            ]);
        }
        
        return view('warehouse.packaging-sizes.edit', compact('packagingSize'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PackagingSize $packagingSize)
    {
        \Log::info('PackagingSize update request (pre-validation):', $request->all());

        if ($request->is_active === 'on') {
            $request->merge(['is_active' => true]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:packaging_sizes,code,' . $packagingSize->id,
            'description' => 'nullable|string',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean'
        ]);

        try {
            \Log::info('PackagingSize update request:', $request->all());
            DB::beginTransaction();

            $packagingSize->update([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'description' => $request->description,
                'sort_order' => $request->sort_order,
                'is_active' => $request->has('is_active') ? (bool)$request->is_active : true,
                'updated_by' => Auth::id()
            ]);
            \Log::info('PackagingSize updated:', $packagingSize->toArray());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Packaging size updated successfully.',
                'data' => $packagingSize->load(['createdBy', 'updatedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update packaging size: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PackagingSize $packagingSize)
    {
        try {
            DB::beginTransaction();

            // Check if packaging size is used by any products
            $productsCount = $packagingSize->products()->count();
            if ($productsCount > 0) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => "Cannot delete packaging size. It is used by {$productsCount} product(s)."
                ], 422);
            }

            if (Auth::id()) {
                $packagingSize->updated_by = Auth::id();
            }

            $packagingSize->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Packaging size deleted successfully.'
            ]);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete packaging size: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active packaging sizes for dropdown
     */
    public function getActivePackagingSizes()
    {
        $packagingSizes = PackagingSize::active()
            ->ordered()
            ->select('id', 'name', 'code')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $packagingSizes
        ]);
    }
}

<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\BrandVariant;
use App\Models\MasterOption;
use App\Models\OptionDetail;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BrandVariantController extends Controller
{
    use \App\Http\Traits\ColumnFilterTrait;

    public function index(Request $request)
    {
        // Get Brand Lines options (Master Option ID 42)
        $brandLinesOption = MasterOption::find(42);
        $brandLines = $brandLinesOption ? $brandLinesOption->optionDetails : collect([]);
        
        $query = BrandVariant::with(['brandLine', 'createdBy', 'updatedBy']);

        // Apply filters
        $this->applyColumnFilters($query, 'brand-variants-table');

        // Apply sorting
        if ($request->has('sort')) {
            $sortColumn = $request->get('sort');
            $sortDirection = $request->get('direction', 'asc');
            
            // Map frontend column names to database columns/relations
            switch ($sortColumn) {
                case 'brandLine.option_name':
                    $query->join('option_details', 'product_brand_variants.brand_line_id', '=', 'option_details.id')
                          ->orderBy('option_details.option_name', $sortDirection)
                          ->select('product_brand_variants.*'); // Avoid column collision
                    break;
                case 'createdBy.name':
                     $query->leftJoin('users as creator', 'product_brand_variants.created_by', '=', 'creator.id')
                          ->orderBy('creator.name', $sortDirection)
                          ->select('product_brand_variants.*');
                    break;
                case 'updatedBy.name':
                     $query->leftJoin('users as updater', 'product_brand_variants.updated_by', '=', 'updater.id')
                          ->orderBy('updater.name', $sortDirection)
                          ->select('product_brand_variants.*');
                    break;
                default:
                    // Check if column exists in main table to prevent SQL errors
                    if (\Schema::hasColumn('product_brand_variants', $sortColumn)) {
                        $query->orderBy($sortColumn, $sortDirection);
                    } else {
                        $query->orderBy('brand_line_id')->orderBy('name');
                    }
                    break;
            }
        } else {
            $query->orderBy('brand_line_id')->orderBy('name');
        }

        $brandVariants = $query->get();

        return view('warehouse.brand-variants.index', compact('brandLines', 'brandVariants'));
    }

    /**
     * Get variants by brand line for cascading dropdown
     * Can accept brand_line_id (integer) or brand_line (string name)
     */
    public function getByBrandLine(Request $request)
    {
        $brandLineId = $request->get('brand_line_id');
        $brandLineName = $request->get('brand_line');

        // If brand_line_id is provided but is not numeric (e.g. "Luxo"), treat it as a name
        if ($brandLineId && !is_numeric($brandLineId)) {
            $brandLineName = $brandLineId;
            $brandLineId = null;
        }

        // If brand_line name provided (or derived from non-numeric ID), find the ID
        if (!$brandLineId && $brandLineName) {
            $brandLineDetail = OptionDetail::whereHas('masterOption', function($q) {
                    $q->where('name', 'Brand Lines');
                })
                ->where('option_name', $brandLineName)
                ->first();
            
            if ($brandLineDetail) {
                $brandLineId = $brandLineDetail->id;
            }
        }

        if (!$brandLineId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Brand line ID or name is required',
                'variants' => []
            ], 400);
        }

        $variants = BrandVariant::where('brand_line_id', $brandLineId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'brand_line_id']);

        return response()->json([
            'status' => 'success',
            'variants' => $variants
        ]);
    }

    public function data()
    {
        $query = BrandVariant::with(['brandLine', 'createdBy', 'updatedBy'])->select('product_brand_variants.*');

        // Apply column filters (search inputs below headers)
        $this->applyColumnFilters($query, 'brand-variants-table');

        return DataTables::of($query)
            ->addColumn('brand_line_name', function ($row) {
                return $row->brandLine ? $row->brandLine->option_name : '-';
            })
            ->addColumn('created_by_name', function ($row) {
                return $row->createdBy ? $row->createdBy->name : '-';
            })
            ->addColumn('updated_by_name', function ($row) {
                return $row->updatedBy ? $row->updatedBy->name : '-';
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '-';
            })
            ->editColumn('updated_at', function ($row) {
                return $row->updated_at ? $row->updated_at->format('Y-m-d H:i:s') : '-';
            })
            ->editColumn('is_active', function ($row) {
                return $row->is_active ? true : false; // Send boolean for frontend handling
            })
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'brand_line_id' => 'required|exists:option_details,id',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            // Ensure unique name per brand line
            'name' => 'unique:product_brand_variants,name,NULL,id,brand_line_id,' . $request->brand_line_id . ',deleted_at,NULL'
        ]);

        $variant = BrandVariant::create([
            'brand_line_id' => $request->brand_line_id,
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
            'created_by' => auth()->id(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Brand Variant created successfully.', 'data' => $variant]);
    }

    public function show($id)
    {
        $variant = BrandVariant::with(['brandLine', 'createdBy', 'updatedBy'])->findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $variant]);
    }

    public function edit($id)
    {
        $variant = BrandVariant::findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $variant]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'brand_line_id' => 'required|exists:option_details,id',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            // Unique check ignoring current record
            'name' => 'unique:product_brand_variants,name,' . $id . ',id,brand_line_id,' . $request->brand_line_id . ',deleted_at,NULL'
        ]);

        $variant = BrandVariant::with('brandLine')->findOrFail($id);
        
        try {
            DB::beginTransaction();

            $oldName = $variant->name;
            $oldBrandName = $variant->brandLine->option_name ?? null;

            $variant->update([
                'brand_line_id' => $request->brand_line_id,
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? $request->is_active : $variant->is_active,
                'updated_by' => auth()->id(),
            ]);

            // Refresh to get new brand line name if it changed
            $variant->load('brandLine');
            $newBrandName = $variant->brandLine->option_name ?? null;
            $newName = $variant->name;

            // Sync with MasterProduct if brand or name changed
            if ($oldBrandName && ($oldName !== $newName || $oldBrandName !== $newBrandName)) {
                $products = MasterProduct::where('brand_line', $oldBrandName)
                    ->where('variant_name', $oldName)
                    ->get();

                foreach ($products as $product) {
                    $updateData = [
                        'brand_line' => $newBrandName,
                        'variant_name' => $newName
                    ];

                    // Also update product name if it contains the old variant name
                    // Case-insensitive check and replacement to be robust
                    if (stripos($product->name, $oldName) !== false) {
                        $updateData['name'] = str_ireplace($oldName, $newName, $product->name);
                    }

                    $product->update($updateData);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Brand Variant updated and synchronized successfully.', 'data' => $variant]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to update brand variant: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $variant = BrandVariant::with('brandLine')->findOrFail($id);
        
        // Check if variant is used in Master Products
        // Master Product uses 'brand_line' (string name) and 'variant_name' (string name)
        $brandLineName = $variant->brandLine ? $variant->brandLine->option_name : null;
        
        if ($brandLineName) {
            $isUsed = MasterProduct::where('brand_line', $brandLineName)
                ->where('variant_name', $variant->name)
                ->exists();
                
            if ($isUsed) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'variant ini sudah memiliki product'
                ], 422);
            }
        }

        $variant->delete();

        return response()->json(['status' => 'success', 'message' => 'Brand Variant deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        
        if (!$ids || !is_array($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No variants selected for deletion.'
            ], 400);
        }

        $variants = BrandVariant::with('brandLine')->whereIn('id', $ids)->get();
        $deletedCount = 0;
        $skippedCount = 0;
        
        foreach ($variants as $variant) {
            $brandLineName = $variant->brandLine ? $variant->brandLine->option_name : null;
            $isUsed = false;
            
            if ($brandLineName) {
                $isUsed = MasterProduct::where('brand_line', $brandLineName)
                    ->where('variant_name', $variant->name)
                    ->exists();
            }
            
            if (!$isUsed) {
                $variant->delete();
                $deletedCount++;
            } else {
                $skippedCount++;
            }
        }
        
        $message = "{$deletedCount} brand variants deleted successfully.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} variant tidak dapat dihapus karena sudah memiliki product.";
        }

        return response()->json([
            'success' => true,
            'count' => $deletedCount,
            'skipped' => $skippedCount,
            'message' => $message
        ]);
    }
}

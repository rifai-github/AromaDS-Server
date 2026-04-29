<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\MasterPriceSlab;
use App\Models\MasterRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterPriceSlabController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MasterPriceSlab::with(['masterRental', 'createdBy', 'updatedBy']);

        // Filter by rental
        if ($request->filled('master_rental_id')) {
            $query->where('master_rental_id', $request->master_rental_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('slab_code', 'like', "%{$search}%")
                  ->orWhere('slab_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('masterRental', function ($qr) use ($search) {
                      $qr->where('rental_name', 'like', "%{$search}%");
                  });
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['slab_name', 'unit_price', 'discount_percentage', 'status', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $priceSlabs = $query->paginate(15);
        $rentals = MasterRental::orderBy('rental_name')->get();

        $statistics = [
            'total' => MasterPriceSlab::count(),
            'active' => MasterPriceSlab::where('status', 'active')->count(),
            'inactive' => MasterPriceSlab::where('status', 'inactive')->count(),
            'effective' => MasterPriceSlab::where('status', 'active')->where('is_active', true)->count(),
            'expired' => MasterPriceSlab::where('status', 'inactive')->count(),
        ];

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $priceSlabs->items(),
                'pagination' => [
                    'total' => $priceSlabs->total(),
                    'per_page' => $priceSlabs->perPage(),
                    'current_page' => $priceSlabs->currentPage(),
                    'last_page' => $priceSlabs->lastPage(),
                    'from' => $priceSlabs->firstItem(),
                    'to' => $priceSlabs->lastItem(),
                ],
                'statistics' => $statistics
            ]);
        }

        return view('company.master-price-slabs.index', compact('priceSlabs', 'rentals', 'statistics'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $rentals = MasterRental::orderBy('rental_name')->get();
        
        // Return JSON for AJAX requests
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'rentals' => $rentals
                ]
            ]);
        }

        return view('company.master-price-slabs.create', compact('rentals'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'master_rental_id' => 'required|exists:master_rentals,id',
            'slab_code' => 'nullable|string|max:50|unique:master_price_slabs',
            'slab_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'min_quantity' => 'required|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0|gte:min_quantity',
            'unit_price' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Auto-generate slab_code if not provided
            $slabCode = $request->slab_code;
            if (empty($slabCode)) {
                $slabCode = 'PS' . str_pad(MasterPriceSlab::count() + 1, 4, '0', STR_PAD_LEFT);
            }

            $priceSlab = MasterPriceSlab::create([
                'master_rental_id' => $request->master_rental_id,
                'slab_code' => $slabCode,
                'slab_name' => $request->slab_name,
                'description' => $request->description,
                'min_quantity' => $request->min_quantity,
                'max_quantity' => $request->max_quantity,
                'unit_price' => $request->unit_price,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'status' => $request->status,
                'is_active' => $request->status === 'active',
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Master price slab created successfully.',
                    'data' => $priceSlab->load(['masterRental', 'createdBy'])
                ]);
            }

            return redirect()->route('company.master-price-slabs.index')
                ->with('success', 'Master price slab created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create master price slab: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Failed to create master price slab: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MasterPriceSlab $masterPriceSlab)
    {
        $masterPriceSlab->load(['masterRental', 'createdBy', 'updatedBy']);

        $statistics = [
            'total_range' => $masterPriceSlab->max_quantity ? $masterPriceSlab->max_quantity - $masterPriceSlab->min_quantity : 'Unlimited',
            'discounted_price' => $masterPriceSlab->unit_price * (1 - ($masterPriceSlab->discount_percentage / 100)),
            'related_slabs' => MasterPriceSlab::where('master_rental_id', $masterPriceSlab->master_rental_id)
                ->where('id', '!=', $masterPriceSlab->id)
                ->count(),
            'is_effective' => $masterPriceSlab->isEffective(),
            'is_expired' => $masterPriceSlab->isExpired(),
        ];

        // Return JSON for AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $masterPriceSlab,
                'statistics' => $statistics
            ]);
        }

        return view('company.master-price-slabs.show', compact('masterPriceSlab', 'statistics'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MasterPriceSlab $masterPriceSlab)
    {
        $masterPriceSlab->load(['masterRental', 'createdBy', 'updatedBy']);
        $rentals = MasterRental::orderBy('rental_name')->get();

        // Return JSON for AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $masterPriceSlab,
                'rentals' => $rentals
            ]);
        }

        return view('company.master-price-slabs.edit', compact('masterPriceSlab', 'rentals'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MasterPriceSlab $masterPriceSlab)
    {
        $request->validate([
            'master_rental_id' => 'required|exists:master_rentals,id',
            'slab_code' => 'nullable|string|max:50|unique:master_price_slabs,slab_code,' . $masterPriceSlab->id,
            'slab_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'min_quantity' => 'required|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0|gte:min_quantity',
            'unit_price' => 'required|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $masterPriceSlab->update([
                'master_rental_id' => $request->master_rental_id,
                'slab_code' => $request->slab_code,
                'slab_name' => $request->slab_name,
                'description' => $request->description,
                'min_quantity' => $request->min_quantity,
                'max_quantity' => $request->max_quantity,
                'unit_price' => $request->unit_price,
                'discount_percentage' => $request->discount_percentage ?? 0,
                'status' => $request->status,
                'is_active' => $request->status === 'active',
                'notes' => $request->notes,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Master price slab updated successfully.',
                    'data' => $masterPriceSlab->load(['masterRental', 'createdBy', 'updatedBy'])
                ]);
            }

            return redirect()->route('company.master-price-slabs.show', $masterPriceSlab)
                ->with('success', 'Master price slab updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update master price slab: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Failed to update master price slab: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MasterPriceSlab $masterPriceSlab)
    {
        try {
            $masterPriceSlab->delete();
            
            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Master price slab deleted successfully.'
                ]);
            }
            
            return redirect()->route('company.master-price-slabs.index')
                ->with('success', 'Master price slab deleted successfully.');
        } catch (\Exception $e) {
            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete master price slab: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Failed to delete master price slab: ' . $e->getMessage());
        }
    }

    /**
     * Toggle price slab status.
     */
    public function toggleStatus(MasterPriceSlab $masterPriceSlab)
    {
        try {
            $newStatus = $masterPriceSlab->status === 'active' ? 'inactive' : 'active';
            $masterPriceSlab->update([
                'status' => $newStatus,
                'is_active' => $newStatus === 'active',
                'updated_by' => Auth::id(),
            ]);

            return back()->with('success', 'Price slab status updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update price slab status: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete price slabs.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:master_price_slabs,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = MasterPriceSlab::whereIn('id', $request->ids)->delete();

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Successfully deleted {$deletedCount} price slab(s)."
                ]);
            }

            return redirect()->route('company.master-price-slabs.index')
                ->with('success', "Successfully deleted {$deletedCount} price slab(s).");
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to bulk delete price slabs: ' . $e->getMessage()
                ], 422);
            }
            
            return back()->with('error', 'Failed to bulk delete price slabs: ' . $e->getMessage());
        }
    }

    /**
     * Get price slabs by rental for API.
     */
    public function getPriceSlabsByRental(Request $request)
    {
        $request->validate([
            'master_rental_id' => 'required|exists:master_rentals,id',
        ]);

        $priceSlabs = MasterPriceSlab::where('master_rental_id', $request->master_rental_id)
            ->where('status', 'active')
            ->where('is_active', true)
            ->with(['masterRental'])
            ->orderBy('min_quantity', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $priceSlabs,
        ]);
    }

    /**
     * Get price slab statistics for API.
     */
    public function getPriceSlabStatistics()
    {
        $statistics = [
            'total' => MasterPriceSlab::count(),
            'active' => MasterPriceSlab::where('status', 'active')->count(),
            'inactive' => MasterPriceSlab::where('status', 'inactive')->count(),
            'expired' => MasterPriceSlab::where('status', 'inactive')->count(),
            'effective' => MasterPriceSlab::where('status', 'active')->where('is_active', true)->count(),
            'slabs_by_rental' => MasterPriceSlab::select('master_rental_id', DB::raw('count(*) as count'))
                ->with('masterRental')
                ->groupBy('master_rental_id')
                ->get(),
            'recent_slabs' => MasterPriceSlab::with(['masterRental', 'createdBy'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }

    /**
     * Search price slabs for API.
     */
    public function searchPriceSlabs(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2',
        ]);

        $priceSlabs = MasterPriceSlab::where('status', 'active')
            ->where(function ($q) use ($request) {
                $q->where('slab_name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            })
            ->with(['masterRental'])
            ->orderBy('slab_name')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $priceSlabs,
        ]);
    }

    /**
     * Calculate price for quantity.
     */
    public function calculatePrice(Request $request)
    {
        $request->validate([
            'master_rental_id' => 'required|exists:master_rentals,id',
            'quantity' => 'required|numeric|min:1',
        ]);

        $priceSlab = MasterPriceSlab::where('master_rental_id', $request->master_rental_id)
            ->where('status', 'active')
            ->where('is_active', true)
            ->where('min_quantity', '<=', $request->quantity)
            ->where('max_quantity', '>=', $request->quantity)
            ->first();

        if (!$priceSlab) {
            return response()->json([
                'status' => 'error',
                'message' => 'No applicable price slab found for the given quantity.',
            ], 404);
        }

        $unitPrice = $priceSlab->unit_price;
        $discountPercentage = $priceSlab->discount_percentage ?? 0;
        $discountedPrice = $unitPrice * (1 - ($discountPercentage / 100));
        $totalPrice = $discountedPrice * $request->quantity;

        return response()->json([
            'status' => 'success',
            'data' => [
                'price_slab' => $priceSlab,
                'unit_price' => $unitPrice,
                'discount_percentage' => $discountPercentage,
                'discounted_price' => $discountedPrice,
                'total_price' => $totalPrice,
                'quantity' => $request->quantity,
            ],
        ]);
    }
}

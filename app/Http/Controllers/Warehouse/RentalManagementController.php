<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\MasterRental;
use App\Models\RentalComponent;
use App\Models\RentalServiceFrequency;
use App\Models\RentalBottomPrice;
use App\Models\MasterProduct;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RentalManagementController extends Controller
{
    // Service Frequencies Management
    public function serviceFrequencies(Request $request)
    {
        $query = RentalServiceFrequency::with(['createdBy', 'updatedBy'])
            ->whereNull('deleted_at'); // Only show non-soft-deleted records

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $sortBy = $request->get('sort_by', 'sort_order');
        $sortDir = $request->get('sort_dir', 'asc');  // Changed from 'sort_order' to avoid conflict with form field
        
        // Validate sort direction
        if (!in_array(strtolower($sortDir), ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        $allowedSortFields = ['name', 'code', 'frequency_months', 'sort_order', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('sort_order')->orderBy('name');
        }

        $frequencies = $query->paginateStd(25);

        return view('warehouse.rental-management.service-frequencies', compact('frequencies'));
    }

    public function showServiceFrequency($id)
    {
        try {
            // Find the frequency including soft deleted ones
            $frequency = RentalServiceFrequency::withTrashed()->find($id);
            
            if (!$frequency) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service frequency not found.'
                ], 404);
            }
            
            $frequency->load(['createdBy', 'updatedBy']);
            
            return response()->json([
                'status' => 'success',
                'data' => $frequency
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load service frequency: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeServiceFrequency(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    // Check if code exists in active records only
                    $code = strtoupper($value);
                    
                    // First, let's check all records with this code (including soft deleted)
                    $allRecords = RentalServiceFrequency::withTrashed()
                        ->where('code', $code)
                        ->get(['id', 'code', 'deleted_at']);
                    
                    \Log::info('All records with code ' . $code . ': ' . $allRecords->toJson());
                    
                    // Then check only active records
                    $activeRecords = RentalServiceFrequency::where('code', $code)
                        ->whereNull('deleted_at')
                        ->get(['id', 'code', 'deleted_at']);
                    
                    \Log::info('Active records with code ' . $code . ': ' . $activeRecords->toJson());
                    
                    if ($activeRecords->isNotEmpty()) {
                        \Log::info('Code ' . $code . ' already exists in active records');
                        $fail('The code has already been taken.');
                    } else {
                        \Log::info('Code ' . $code . ' is available for use');
                    }
                }
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency_months' => 'required|integer|min:1',
            'frequency_times_per_month' => 'required|integer|min:1',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean'
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

            $frequency = RentalServiceFrequency::create([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'description' => $request->description,
                'frequency_months' => $request->frequency_months,
                'frequency_times_per_month' => $request->frequency_times_per_month,
                'sort_order' => $request->sort_order,
                'is_active' => $request->has('is_active') && $request->is_active == '1',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Service frequency created successfully.',
                'data' => $frequency->load(['createdBy', 'updatedBy'])
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            \Log::error('Database error during service frequency creation: ' . $e->getMessage());
            \Log::error('Error code: ' . $e->getCode());
            \Log::error('SQL State: ' . $e->errorInfo[0] ?? 'Unknown');
            
            // Handle specific database errors
            if ($e->getCode() == 23000 || ($e->errorInfo[0] ?? '') == '23000') { // Integrity constraint violation
                \Log::error('Integrity constraint violation detected');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service frequency with this code already exists. Please use a different code.',
                    'errors' => ['code' => ['The code has already been taken.']]
                ], 422);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create service frequency: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('General error during service frequency creation: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create service frequency: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateServiceFrequency(Request $request, $id)
    {
        // Find the frequency including soft deleted ones
        $frequency = RentalServiceFrequency::withTrashed()->find($id);
        
        if (!$frequency) {
            return response()->json([
                'status' => 'error',
                'message' => 'Service frequency not found.'
            ], 404);
        }
        
        $validator = \Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) use ($frequency) {
                    $exists = RentalServiceFrequency::where('code', strtoupper($value))
                        ->whereNull('deleted_at')
                        ->where('id', '!=', $frequency->id)
                        ->exists();
                    if ($exists) {
                        $fail('The code has already been taken.');
                    }
                }
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'frequency_months' => 'required|integer|min:1',
            'frequency_times_per_month' => 'required|integer|min:1',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean'
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

            $frequency->update([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'description' => $request->description,
                'frequency_months' => $request->frequency_months,
                'frequency_times_per_month' => $request->frequency_times_per_month,
                'sort_order' => $request->sort_order,
                'is_active' => $request->has('is_active') && $request->is_active == '1',
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Service frequency updated successfully.',
                'data' => $frequency->load(['createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update service frequency: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyServiceFrequency($id)
    {
        try {
            // Find the frequency including soft deleted ones
            $frequency = RentalServiceFrequency::withTrashed()->find($id);
            
            if (!$frequency) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service frequency not found.'
                ], 404);
            }
            
            // Check if frequency is used by any master rentals
            if ($frequency->masterRentals()->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete service frequency that is used by rentals.'
                ], 422);
            }

            $frequencyName = $frequency->name;
            $frequencyId = $frequency->id;
            
            $frequency->delete();

            // Log successful deletion
            \Log::info('Service frequency deleted successfully', [
                'frequency_id' => $frequencyId,
                'frequency_name' => $frequencyName,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Service frequency deleted successfully.',
                'data' => [
                    'id' => $frequencyId,
                    'name' => $frequencyName
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error deleting service frequency: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete service frequency: ' . $e->getMessage()
            ], 500);
        }
    }

    // Rental Components Management
    public function components(MasterRental $rental)
    {
        $components = $rental->rentalComponents()->with(['componentProducts.masterProduct', 'createdBy', 'updatedBy'])->orderBy('sort_order')->get();
        $products = MasterProduct::where('is_active', true)->orderBy('name')->get();
        
        return view('warehouse.rental-management.components', compact('rental', 'components', 'products'));
    }

    public function storeComponent(Request $request, MasterRental $rental)
    {
        $validator = \Validator::make($request->all(), [
            'component_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            'unit' => 'required|string|max:50',
            'replacement_frequency_months' => 'required|integer|min:1',
            'replacement_price' => 'required|numeric|min:0',
            'is_activation_component' => 'boolean',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean'
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

            $component = RentalComponent::create([
                'master_rental_id' => $rental->id,
                'component_name' => $request->component_name,
                'description' => $request->description,
                'quantity' => $request->quantity,
                'unit' => $request->unit,
                'replacement_frequency_months' => $request->replacement_frequency_months,
                'replacement_price' => $request->replacement_price,
                'is_activation_component' => $request->is_activation_component ?? false,
                'sort_order' => $request->sort_order,
                'is_active' => $request->is_active ?? true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            if ($request->is_activation_component) {
                $rental->rentalComponents()
                    ->where('id', '!=', $component->id)
                    ->update(['is_activation_component' => false, 'updated_by' => Auth::id()]);
                
                $rental->update(['has_activation_component' => true, 'updated_by' => Auth::id()]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Rental component created successfully.',
                'data' => $component->load(['componentProducts.masterProduct', 'createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create rental component: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyComponent(RentalComponent $component)
    {
        try {
            $component->delete();

            $hasActivation = $component->masterRental->activationComponents()->exists();
            $component->masterRental->update([
                'has_activation_component' => $hasActivation,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Rental component deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete rental component: ' . $e->getMessage()
            ], 500);
        }
    }

    // Bottom Prices Management
    public function bottomPrices(MasterRental $rental)
    {
        $bottomPrices = $rental->bottomPrices()
            ->with(['branch', 'createdBy', 'updatedBy'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
        $existingBranchIds = $bottomPrices->pluck('branch_id')->filter()->unique();
        $branches = Branch::where('is_active', true)
            ->when($existingBranchIds->isNotEmpty(), function ($query) use ($existingBranchIds) {
                $query->orWhereIn('id', $existingBranchIds);
            })
            ->orderBy('name')
            ->get();
        
        return view('warehouse.rental-management.bottom-prices', compact('rental', 'bottomPrices', 'branches'));
    }

    public function storeBottomPrice(Request $request, MasterRental $rental)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'offer_type' => 'required|in:hari,bulan',
            'bottom_price' => 'required|numeric|min:0',
            'replacement_price' => 'required|numeric|min:0',
            'is_active' => 'boolean'
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

            $bottomPrice = RentalBottomPrice::create([
                'master_rental_id' => $rental->id,
                'branch_id' => $request->branch_id,
                'offer_type' => $request->offer_type,
                'bottom_price' => $request->bottom_price,
                'replacement_price' => $request->replacement_price,
                'is_active' => true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Newest submission for this rental/branch/offer_type wins.
            RentalBottomPrice::refreshActiveFlagForGroup($rental->id, $request->branch_id, $request->offer_type);
            $bottomPrice->refresh();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bottom price created successfully.',
                'data' => $bottomPrice->load(['branch', 'createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create bottom price: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateBottomPrice(Request $request, RentalBottomPrice $bottomPrice)
    {
        $validator = \Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'offer_type' => 'required|in:hari,bulan',
            'bottom_price' => 'required|numeric|min:0',
            'replacement_price' => 'required|numeric|min:0',
            'is_active' => 'boolean'
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

            $originalMasterRentalId = $bottomPrice->master_rental_id;
            $originalBranchId = $bottomPrice->branch_id;
            $originalOfferType = $bottomPrice->offer_type;

            $bottomPrice->update([
                'branch_id' => $request->branch_id,
                'offer_type' => $request->offer_type,
                'bottom_price' => $request->bottom_price,
                'replacement_price' => $request->replacement_price,
                'is_active' => true,
                'updated_by' => Auth::id()
            ]);

            // Recompute both the group this row left and the group it now
            // belongs to, so exactly one row per group stays active — the
            // most recently submitted one.
            RentalBottomPrice::refreshActiveFlagForGroup($originalMasterRentalId, $originalBranchId, $originalOfferType);
            RentalBottomPrice::refreshActiveFlagForGroup($bottomPrice->master_rental_id, $bottomPrice->branch_id, $bottomPrice->offer_type);
            $bottomPrice->refresh();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bottom price updated successfully.',
                'data' => $bottomPrice->load(['branch', 'createdBy', 'updatedBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update bottom price: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyBottomPrice(RentalBottomPrice $bottomPrice)
    {
        try {
            $masterRentalId = $bottomPrice->master_rental_id;
            $branchId = $bottomPrice->branch_id;
            $offerType = $bottomPrice->offer_type;

            $bottomPrice->delete();

            // Whatever remains the newest in this group becomes active.
            RentalBottomPrice::refreshActiveFlagForGroup($masterRentalId, $branchId, $offerType);

            return response()->json([
                'status' => 'success',
                'message' => 'Bottom price deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete bottom price: ' . $e->getMessage()
            ], 500);
        }
    }
}

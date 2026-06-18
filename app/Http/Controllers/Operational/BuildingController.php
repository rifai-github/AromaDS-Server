<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\Branch;
use App\Models\Building;
use App\Models\Customer;
use App\Models\MasterRoom;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\OperationalArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BuildingController extends Controller
{
    use ColumnFilterTrait;
    public function index(Request $request)
    {
        // Increase execution time limit for large data loading
        set_time_limit(120); // 2 minutes
        
        try {
            // Building index with full features and error handling
            
            // Step 1: Query with relationships (safely)
            $query = Building::query();
            $this->applyBuildingBranchVisibility($query);
            
            // Eager load relationships safely
            try {
                $query->with(['province', 'city', 'district', 'subdistrict', 'createdBy', 'updatedBy']);
            } catch (\Exception $e) {
                Log::warning('Error loading some relationships, continuing without them', ['error' => $e->getMessage()]);
            }
            
            // Load customers relationship separately (many-to-many)
            try {
                $query->with('customers');
            } catch (\Exception $e) {
                Log::warning('Error loading customers relationship', ['error' => $e->getMessage()]);
            }
            
            // Apply per-column filters (table id: buildingsTable) - WITH ERROR HANDLING
            try {
                // Capture flat structure filters
                $customFilters = [];
                if ($request->has('nama_gedung')) $customFilters['nama_gedung'] = $request->nama_gedung;
                if ($request->has('alamat_1')) $customFilters['alamat_1'] = $request->alamat_1;
                if ($request->has('city')) $customFilters['city'] = $request->city;
                if ($request->has('province')) $customFilters['province'] = $request->province;
                if ($request->has('total_floors')) $customFilters['total_floors'] = $request->total_floors;
                if ($request->has('total_area')) $customFilters['total_area'] = $request->total_area;
                if ($request->has('status_update')) $customFilters['status_update'] = $request->status_update;
                if ($request->has('created_at')) $customFilters['created_at'] = $request->created_at;

                $this->applyColumnFilters($query, 'buildingsTable', [
                    // 0 => checkbox
                    1 => ['column' => 'nama_gedung'],
                    'nama_gedung' => ['column' => 'nama_gedung'],
                    
                    2 => ['column' => 'alamat_1'],
                    'alamat_1' => ['column' => 'alamat_1'],
                    
                    3 => ['relation' => 'city', 'column' => 'name'],
                    'city' => ['relation' => 'city', 'column' => 'name'],
                    
                    4 => ['relation' => 'province', 'column' => 'name'],
                    'province' => ['relation' => 'province', 'column' => 'name'],
                    
                    5 => ['column' => 'total_floors'],
                    'total_floors' => ['column' => 'total_floors'],
                    
                    6 => ['column' => 'total_area'],
                    'total_area' => ['column' => 'total_area'],
                    
                    7 => ['column' => 'status_update', 'boolean' => true],
                    'status_update' => ['column' => 'status_update', 'boolean' => true],
                    
                    8 => ['column' => 'created_at', 'type' => 'date'],
                    'created_at' => ['column' => 'created_at', 'type' => 'date'],
                ], $customFilters);
            } catch (\Exception $e) {
                Log::error('Error applying column filters in BuildingController', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue without column filters if there's an error
            }
            
            // Apply basic filters
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('nama_gedung', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('alamat_1', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('alamat_2', 'like', "%{$search}%");
                      
                    // Search in customers relationship
                    try {
                        $q->orWhereHas('customers', function($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        });
                    } catch (\Exception $e) {
                        Log::warning('Error searching in customers relationship', ['error' => $e->getMessage()]);
                    }
                });
            }
            
            if ($request->filled('province')) {
                $query->where('province_id', $request->province);
            }
            
            if ($request->filled('city')) {
                $query->where('city_id', $request->city);
            }
            
            if ($request->filled('district')) {
                $query->where('district_id', $request->district);
            }
            
            if ($request->filled('subdistrict')) {
                $query->where('subdistrict_id', $request->subdistrict);
            }
            
            if ($request->filled('status')) {
                $query->where('status_update', $request->status === 'active');
            }
            
            // Filter by customer (many-to-many relationship)
            if ($request->filled('customer')) {
                try {
                    $query->whereHas('customers', function($q) use ($request) {
                        $q->where('customers.id', $request->customer);
                    });
                } catch (\Exception $e) {
                    Log::warning('Error filtering by customer', ['error' => $e->getMessage()]);
                }
            }
            
            // Execute query
            try {
                $buildings = $query->orderBy('created_at', 'desc')->paginateStd(25);
            } catch (\Exception $e) {
                Log::error('Error executing buildings query', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Fallback: simple query without filters
                $fallbackQuery = Building::orderBy('created_at', 'desc');
                $this->applyBuildingBranchVisibility($fallbackQuery);
                $buildings = $fallbackQuery->paginateStd(25);
            }
            
            // Step 2: Load dropdowns with empty fallback and optimized queries
            $provinces = collect();
            $cities = collect();
            $districts = collect();
            $subdistricts = collect();
            $customers = collect();
            
            try {
                $provinces = Province::select('id', 'name')->orderBy('name')->get();
            } catch (\Exception $e) {
                Log::error('Error loading provinces', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $provinces = collect();
            }
            
            try {
                $cities = City::select('id', 'name', 'province_id')->orderBy('name')->get();
            } catch (\Exception $e) {
                Log::error('Error loading cities', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $cities = collect();
            }
            
            try {
                // Districts can be very large (6996+), so we'll load them on-demand via AJAX instead
                // For now, return empty collection to avoid timeout/memory issues
                $districts = collect();
                
                // Alternative: If you really need all districts, uncomment below but beware of performance
                // $districts = District::select('id', 'name', 'city_id')->orderBy('name')->get();
            } catch (\Exception $e) {
                Log::error('Error loading districts', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $districts = collect();
            } catch (\Throwable $e) {
                Log::error('Fatal error loading districts', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $districts = collect();
            }
            
            try {
                // Subdistricts can be very large, so we'll load them on-demand via AJAX instead
                // For now, return empty collection to avoid timeout/memory issues
                $subdistricts = collect();
                
                // Alternative: If you really need all subdistricts, uncomment below but beware of performance
                // $subdistricts = Subdistrict::select('id', 'name', 'district_id')->orderBy('name')->limit(10000)->get();
            } catch (\Exception $e) {
                Log::error('Error loading subdistricts', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $subdistricts = collect();
            }
            
            try {
                $customers = Customer::active()->select('id', 'name')->orderBy('name')->get();
            } catch (\Exception $e) {
                Log::error('Error loading customers', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $customers = collect();
            }

            // Check if this is an API request
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'success',
                    'data' => $buildings,
                    'meta' => [
                        'provinces' => $provinces,
                        'cities' => $cities,
                        'districts' => $districts,
                        'subdistricts' => $subdistricts,
                        'customers' => $customers
                    ]
                ]);
            }

            // Use output buffering to catch view errors
            ob_start();
            try {
                $view = view('operational.buildings.index', compact('buildings', 'provinces', 'cities', 'districts', 'subdistricts', 'customers'));
                $content = $view->render();
                ob_end_clean();
                return response($content);
            } catch (\Throwable $viewError) {
                ob_end_clean();
                Log::error('Error rendering view in BuildingController@index', [
                    'error' => $viewError->getMessage(),
                    'file' => $viewError->getFile(),
                    'line' => $viewError->getLine(),
                    'trace' => $viewError->getTraceAsString()
                ]);
                throw $viewError;
            }
            
        } catch (\Throwable $e) {
            Log::error('Error in BuildingController@index', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'class' => get_class($e)
            ]);
            
            // Return error response
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'An error occurred while loading buildings',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                    'file' => config('app.debug') ? $e->getFile() : null,
                    'line' => config('app.debug') ? $e->getLine() : null
                ], 500);
            }
            
            // For web requests, show error page
            if (config('app.debug')) {
                throw $e;
            }
            
            return redirect()->back()->with('error', 'An error occurred while loading buildings. Please try again.');
        }
    }

    public function create()
    {
        $provinces = Province::all();
        $cities = City::all();
        $districts = District::all();
        $subdistricts = Subdistrict::all();
        $customers = Customer::active()->get();
        $users = User::all();

        return view('operational.buildings.create', compact('provinces', 'cities', 'districts', 'subdistricts', 'customers', 'users'));
    }

    public function store(Request $request)
{
    // Accept either 'nama_gedung' or 'name' as the building name (for compatibility with pipeline form)
    $validator = Validator::make($request->all(), [
        'customer_id' => 'nullable|exists:customers,id',
        'nama_gedung' => 'required_without:name|nullable|string|max:255',
        'name' => 'required_without:nama_gedung|nullable|string|max:255',
        'building_type' => 'nullable|string|max:100',
        'alamat_1' => 'nullable|string',
        'address_1' => 'nullable|string',
        'address' => 'nullable|string',
        'alamat_2' => 'nullable|string',
        'province_id' => 'nullable|exists:provinces,id',
        'city_id' => 'nullable|exists:cities,id',
        'district_id' => 'nullable|exists:districts,id',
        'subdistrict_id' => 'nullable|exists:subdistricts,id',
        'kode_pos' => 'nullable|string|max:10',
        'postal_code' => 'nullable|string|max:10',
        'phone_1' => 'nullable|string|max:20',
        'phone_2' => 'nullable|string|max:20',
        'fax' => 'nullable|string|max:20',
        'email' => 'nullable|email|max:100',
        'status_update' => 'boolean',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        // Use 'nama_gedung' if provided, otherwise use 'name'
        $buildingName = $request->nama_gedung ?? $request->name;
        
        $building = Building::create([
            'nama_gedung' => $buildingName,
            'name' => $buildingName,
            'building_type' => $request->building_type,
            'alamat_1' => $request->alamat_1 ?? $request->address_1,
            'address' => $request->address ?? $request->address_1,
            'alamat_2' => $request->alamat_2,
            'province_id' => $request->province_id,
            'city_id' => $request->city_id,
            'district_id' => $request->district_id,
            'subdistrict_id' => $request->subdistrict_id,
            'kode_pos' => $request->kode_pos ?? $request->postal_code,
            'postal_code' => $request->postal_code ?? $request->kode_pos,
            'phone_1' => $request->phone_1,
            'phone_2' => $request->phone_2,
            'fax' => $request->fax,
            'email' => $request->email,
            'status_update' => filter_var($request->status_update, FILTER_VALIDATE_BOOLEAN),
            'created_by' => Auth::id(),
        ]);

        // Attach customer if provided (many-to-many relationship)
        if ($request->customer_id) {
            $building->customers()->attach($request->customer_id, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        Cache::forget('survey-wizard:buildings:all-active');
        Cache::forget('survey-wizard:buildings:all-active:v2');

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => 'Building created successfully',
            'data' => $building
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Error creating building: ' . $e->getMessage()
        ], 500);
    }
}

    public function show(Building $building)
    {
        try {
            $this->authorizeBuildingBranchVisibility($building);

            // Load relationships safely - try each one separately
            $relationships = ['province', 'city', 'district', 'subdistrict', 'createdBy', 'updatedBy'];
            
            foreach ($relationships as $relation) {
                try {
                    $building->load($relation);
                } catch (\Exception $e) {
                    Log::warning("Error loading relationship '{$relation}' for building {$building->id}", [
                        'error' => $e->getMessage()
                    ]);
                    // Continue without this relationship
                }
            }
            
            // Load optional relationships separately
            $optionalRelations = ['customers', 'jobSchedules', 'masterRooms', 'rooms', 'contractBuildings'];
            foreach ($optionalRelations as $relation) {
                try {
                    $building->load($relation);
                } catch (\Exception $e) {
                    Log::warning("Error loading optional relationship '{$relation}' for building {$building->id}", [
                        'error' => $e->getMessage()
                    ]);
                    // Continue without this relationship
                }
            }
            
            // Return JSON for AJAX requests (modal system)
            if (request()->ajax()) {
                Log::info('BuildingController::show returning data:', $building->toArray());
                return response()->json([
                    'status' => 'success',
                    'data' => $building
                ]);
            }
            
            $provinces = Province::orderBy('name')->get();
            $cities = City::where('province_id', $building->province_id)->orderBy('name')->get();
            $districts = District::where('city_id', $building->city_id)->orderBy('name')->get();
            $subdistricts = Subdistrict::where('district_id', $building->district_id)->orderBy('name')->get();
            
            return view('operational.buildings.show', compact('building', 'provinces', 'cities', 'districts', 'subdistricts'));
                
        } catch (\Exception $e) {
            Log::error('Error in BuildingController@show', [
                'building_id' => $building->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error loading building data',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
                ], 500);
            }
            
            return redirect()->route('operational.buildings.index')
                ->with('error', 'Error loading building data. Please try again.');
        }
    }

    public function edit(Building $building)
    {
        $this->authorizeBuildingBranchVisibility($building);

        $provinces = Province::all();
        $cities = City::all();
        $districts = District::all();
        $subdistricts = Subdistrict::all();
        $customers = Customer::active()->get();
        $users = User::all();

        // Return JSON for AJAX requests (modal system)
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'building' => $building,
                    'provinces' => $provinces,
                    'cities' => $cities,
                    'districts' => $districts,
                    'subdistricts' => $subdistricts,
                    'customers' => $customers,
                    'users' => $users
                ]
            ]);
        }

        // For non-AJAX requests, redirect to index with error message
        return redirect()->route('operational.buildings.index')
            ->with('error', 'Please use the modal system to edit buildings.');
    }

    public function update(Request $request, Building $building)
    {
        $this->authorizeBuildingBranchVisibility($building);

        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'nama_gedung' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'building_type' => 'nullable|string|max:100',
            'alamat_1' => 'required|string',
            'address' => 'nullable|string',
            'alamat_2' => 'nullable|string',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'district_id' => 'required|exists:districts,id',
            'subdistrict_id' => 'required|exists:subdistricts,id',
            'total_floors' => 'nullable|integer|min:1',
            'total_area' => 'nullable|numeric|min:0',
            'kode_pos' => 'nullable|string|max:10',
            'postal_code' => 'nullable|string|max:10',
            'phone_1' => 'nullable|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'fax' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'status_update' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $building->update([
                'nama_gedung' => $request->nama_gedung,
                'name' => $request->name,
                'building_type' => $request->building_type,
                'alamat_1' => $request->alamat_1,
                'address' => $request->address,
                'alamat_2' => $request->alamat_2,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'subdistrict_id' => $request->subdistrict_id,
                'total_floors' => $request->total_floors,
                'total_area' => $request->total_area,
                'kode_pos' => $request->kode_pos,
                'postal_code' => $request->postal_code,
                'phone_1' => $request->phone_1,
                'phone_2' => $request->phone_2,
                'fax' => $request->fax,
                'email' => $request->email,
                'status_update' => filter_var($request->status_update, FILTER_VALIDATE_BOOLEAN),
                'updated_by' => Auth::id(),
            ]);

            // Sync customer if provided (many-to-many relationship)
            if ($request->filled('customer_id')) {
                // Detach all existing customers and attach the new one
                $building->customers()->sync([
                    $request->customer_id => [
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                ]);
            } elseif ($request->has('customer_id') && is_null($request->customer_id)) {
                // If customer_id is explicitly null, detach all customers
                $building->customers()->detach();
            }

            Cache::forget('survey-wizard:buildings:all-active');
            Cache::forget('survey-wizard:buildings:all-active:v2');

            return response()->json([
                'status' => 'success',
                'message' => 'Building updated successfully',
                'data' => $building
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating building: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Building $building)
    {
        $this->authorizeBuildingBranchVisibility($building);

        try {
            $building->delete();
            Cache::forget('survey-wizard:buildings:all-active');
            Cache::forget('survey-wizard:buildings:all-active:v2');
            return response()->json([
                'success' => true,
                'message' => 'Building deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting building: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dashboard()
    {
        $baseQuery = Building::query();
        $this->applyBuildingBranchVisibility($baseQuery);

        $total_buildings = (clone $baseQuery)->count();
        $active_buildings = (clone $baseQuery)->where('status_update', true)->count();
        $inactive_buildings = (clone $baseQuery)->where('status_update', false)->count();

        $buildings_by_province = (clone $baseQuery)->with('province')
            ->selectRaw('province_id, count(*) as count')
            ->groupBy('province_id')
            ->get();

        $buildings_by_city = (clone $baseQuery)->with('city')
            ->selectRaw('city_id, count(*) as count')
            ->groupBy('city_id')
            ->limit(10)
            ->get();

        $recent_buildings = (clone $baseQuery)->with(['province', 'city', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('operational.buildings.dashboard', compact(
            'total_buildings',
            'active_buildings',
            'inactive_buildings',
            'buildings_by_province',
            'buildings_by_city',
            'recent_buildings'
        ));
    }

    public function getCitiesByProvince($provinceId)
    {
        $cities = City::where('province_id', $provinceId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $cities
        ]);
    }

    public function getDistrictsByCity($cityId)
    {
        $districts = District::where('city_id', $cityId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $districts
        ]);
    }

    public function getSubdistrictsByDistrict($districtId)
    {
        $subdistricts = Subdistrict::where('district_id', $districtId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $subdistricts
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:buildings,id'
        ]);

        try {
            $deleteQuery = Building::whereIn('id', $request->ids);
            $this->applyBuildingBranchVisibility($deleteQuery);
            $deletedCount = $deleteQuery->count();
            $deleteQuery->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Buildings deleted successfully',
                'count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting buildings: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getFloors($buildingId)
    {
        try {
            $buildingQuery = Building::whereKey($buildingId);
            $this->applyBuildingBranchVisibility($buildingQuery);
            $building = $buildingQuery->firstOrFail();
            $floors = $building->floors()->active()->orderBy('floor_number')->get();

            return response()->json([
                'status' => 'success',
                'data' => $floors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load floors: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRooms($buildingId)
    {
        try {
            $buildingQuery = Building::whereKey($buildingId);
            $this->applyBuildingBranchVisibility($buildingQuery);
            $buildingQuery->firstOrFail();

            $rooms = MasterRoom::where('building_id', $buildingId)
                ->where('is_active', true)
                ->select('id', 'room_name', 'room_code')
                ->get();

            return response()->json($rooms);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch rooms',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if building's city is registered in any operational area
     * Used for finalize validation in Survey, Quotation, Contract
     * 
     * @param Building $building
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkOperationalArea(Building $building)
    {
        $this->authorizeBuildingBranchVisibility($building);

        $data = \App\Services\OperationalAreaService::getValidationData($building);
        $data['branches_url'] = route('company.branches.index');
        
        return response()->json($data);
    }

    /**
     * Check operational area by survey ID
     * Gets building from survey and validates operational area
     * 
     * @param int $surveyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkOperationalAreaBySurvey($surveyId)
    {
        try {
            $survey = \App\Models\Survey::with('building')->findOrFail($surveyId);
            
            if (!$survey->building) {
                return response()->json([
                    'is_valid' => false,
                    'city_id' => null,
                    'city_name' => 'Unknown',
                    'message' => 'Survey tidak memiliki building',
                    'branches_url' => route('company.branches.index')
                ]);
            }

            $this->authorizeBuildingBranchVisibility($survey->building);
            
            $data = \App\Services\OperationalAreaService::getValidationData($survey->building);
            $data['branches_url'] = route('company.branches.index');
            $data['building_id'] = $survey->building->id;
            $data['building_name'] = $survey->building->nama_gedung ?? $survey->building->name;
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'is_valid' => false,
                'city_id' => null,
                'city_name' => 'Unknown',
                'message' => 'Error: ' . $e->getMessage(),
                'branches_url' => route('company.branches.index')
            ], 500);
        }
    }

    /**
     * Check operational area by city ID
     * 
     * @param int $cityId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkOperationalAreaByCity($cityId)
    {
        $data = \App\Services\OperationalAreaService::getValidationDataByCity($cityId);
        $data['branches_url'] = route('company.branches.index');
        
        return response()->json($data);
    }

    protected function applyBuildingBranchVisibility($query): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        if ($user->hasRoleStartingWith('Management') || $user->hasPermission('admin.view')) {
            return;
        }

        $companyAccess = $user->accessLevels()
            ->where('access_type', 'company')
            ->where('is_active', true)
            ->exists();

        if ($companyAccess) {
            return;
        }

        $branchIds = $this->getBuildingVisibleBranchIds($user);

        if ($branchIds->isEmpty()) {
            $query->where('created_by', $user->id);
            return;
        }

        $branchLocations = Branch::whereIn('id', $branchIds)
            ->where('is_active', true)
            ->get(['id', 'province_id', 'city_id', 'district_id', 'subdistrict_id']);

        $areas = OperationalArea::active()
            ->whereIn('branch_id', $branchIds)
            ->get(['province_id', 'city_id', 'district_id', 'subdistrict_id']);

        $provinceIds = $branchLocations->pluck('province_id')->merge($areas->pluck('province_id'))->filter()->unique()->values();
        $cityIds = $branchLocations->pluck('city_id')->merge($areas->pluck('city_id'))->filter()->unique()->values();
        $districtIds = $branchLocations->pluck('district_id')->merge($areas->pluck('district_id'))->filter()->unique()->values();
        $subdistrictIds = $branchLocations->pluck('subdistrict_id')->merge($areas->pluck('subdistrict_id'))->filter()->unique()->values();

        $query->where(function ($q) use ($user, $provinceIds, $cityIds, $districtIds, $subdistrictIds) {
            $q->where('created_by', $user->id);

            if ($provinceIds->isNotEmpty()) {
                $q->orWhereIn('province_id', $provinceIds->all());
            }

            if ($cityIds->isNotEmpty()) {
                $q->orWhereIn('city_id', $cityIds->all());
            }

            if ($districtIds->isNotEmpty()) {
                $q->orWhereIn('district_id', $districtIds->all());
            }

            if ($subdistrictIds->isNotEmpty()) {
                $q->orWhereIn('subdistrict_id', $subdistrictIds->all());
            }
        });
    }

    protected function getBuildingVisibleBranchIds(User $user)
    {
        $branchIds = collect();

        $branchAccess = $user->accessLevels()
            ->where('access_type', 'branch')
            ->where('is_active', true)
            ->first();

        if ($branchAccess) {
            $branchIds = $branchIds->merge($branchAccess->access_config['allowed_branches'] ?? []);
        }

        $assignedBranchIds = $user->assignedBranches()
            ->where('branches.is_active', true)
            ->pluck('branches.id');

        $branchIds = $branchIds->merge($assignedBranchIds);

        if ($user->branch_id) {
            $branchIds->push($user->branch_id);
        }

        return $branchIds
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    protected function authorizeBuildingBranchVisibility(Building $building): void
    {
        $query = Building::whereKey($building->getKey());
        $this->applyBuildingBranchVisibility($query);

        abort_unless($query->exists(), 403, 'Anda tidak memiliki akses ke building ini.');
    }
}

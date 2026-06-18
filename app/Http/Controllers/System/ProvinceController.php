<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Subdistrict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProvinceController extends Controller
{
    public function index(Request $request)
    {
        $query = Province::withCount(['cities', 'customers', 'branches']);

        // Filter by name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by code
        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        // Filter by country
        if ($request->filled('country')) {
            $query->where('country', 'like', '%' . $request->country . '%');
        }

        $provinces = $query->orderBy('name')->paginateStd(25);
        
        // Get total counts for display
        $totalProvinces = Province::count();
        $totalCities = \App\Models\City::count();
        $totalDistricts = \App\Models\District::count();
        $totalSubdistricts = \App\Models\Subdistrict::count();
        $totalPostalCodes = \App\Models\Subdistrict::whereNotNull('postal_code')->distinct('postal_code')->count('postal_code');

        return view('system.provinces.index', compact('provinces', 'totalProvinces', 'totalCities', 'totalDistricts', 'totalSubdistricts', 'totalPostalCodes'));
    }

    public function create()
    {
        return view('system.provinces.create');
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:provinces,name,NULL,id,deleted_at,NULL',
            'code' => 'required|string|max:50|unique:provinces,code,NULL,id,deleted_at,NULL',
            'country' => 'required|string|max:255',
            'description' => 'nullable|string',
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

            $province = Province::create([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'country' => $request->country,
                'description' => $request->description,
                'created_by' => Auth::id(),
            ]);

            DB::commit();
            $this->forgetProvinceLookupCache();

            return response()->json([
                'status' => 'success',
                'message' => 'Province created successfully',
                'data' => $province
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating province: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Province $province)
    {
        try {
            $province->load(['cities', 'customers', 'branches', 'createdBy', 'updatedBy']);
            
            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $province
                ]);
            }
            
            return view('system.provinces.show', compact('province'));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to load province: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to load province: ' . $e->getMessage());
        }
    }

    public function edit(Province $province)
    {
        try {
            $province->load(['cities', 'customers', 'branches', 'createdBy', 'updatedBy']);
            
            // Return JSON for AJAX requests
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $province
                ]);
            }
            
            return view('system.provinces.edit', compact('province'));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to load province: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to load province: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Province $province)
    {
        // Debug: Log the incoming request data
        \Log::info('Update Province Request Data:', [
            'province_id' => $province->id,
            'all_data' => $request->all(),
            'json_data' => $request->json()->all(),
            'name' => $request->name,
            'code' => $request->code,
            'country' => $request->country,
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'is_json' => $request->isJson(),
        ]);

        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:provinces,name,' . $province->id . ',id,deleted_at,NULL',
            'code' => 'required|string|max:50|unique:provinces,code,' . $province->id . ',id,deleted_at,NULL',
            'country' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            \Log::error('Province validation failed:', [
                'errors' => $validator->errors(),
                'input' => $request->all(),
                'json_input' => $request->json()->all(),
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $province->update([
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'country' => $request->country,
                'description' => $request->description,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();
            $this->forgetProvinceLookupCache();

            return response()->json([
                'status' => 'success',
                'message' => 'Province updated successfully',
                'data' => $province
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating province: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Province $province)
    {
        try {
            // Check if province is used by any customers
            $hasCustomers = $province->customers()->exists();
            
            if ($hasCustomers) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete province with existing customers.'
                ], 400);
            }

            // Check if province is used by any branches
            $hasBranches = $province->branches()->exists();
            
            if ($hasBranches) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete province with existing branches.'
                ], 400);
            }

            // Check if province has cities
            $hasCities = $province->cities()->exists();
            
            if ($hasCities) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete province with existing cities.'
                ], 400);
            }

            $province->delete();
            $this->forgetProvinceLookupCache();
            return response()->json([
                'status' => 'success',
                'message' => 'Province deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete province: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProvinces(Request $request)
    {
        $provinces = Cache::remember('location:provinces:v1', now()->addMinutes(30), function () {
            return Province::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray();
        });

        return response()
            ->json($provinces)
            ->header('Cache-Control', 'public, max-age=1800');
    }

    public function getProvincesByCountry(Request $request)
    {
        $request->validate([
            'country' => 'required|string',
        ]);

        $provinces = Province::where('country', $request->country)
            ->orderBy('name')
            ->get();

        return response()->json($provinces);
    }

    public function searchProvinces(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2',
        ]);

        $provinces = Province::where('name', 'like', '%' . $request->search . '%')
            ->orWhere('code', 'like', '%' . $request->search . '%')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($provinces);
    }

    public function bulkCreate(Request $request)
    {
        $request->validate([
            'provinces' => 'required|array|min:1',
            'provinces.*.name' => 'required|string|max:255',
            'provinces.*.code' => 'required|string|max:50',
            'provinces.*.country' => 'required|string|max:255',
            'provinces.*.description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $createdCount = 0;

            foreach ($request->provinces as $provinceData) {
                // Check if province name or code already exists
                $exists = Province::where('name', $provinceData['name'])
                    ->orWhere('code', strtoupper($provinceData['code']))
                    ->exists();
                
                if (!$exists) {
                    Province::create([
                        'name' => $provinceData['name'],
                        'code' => strtoupper($provinceData['code']),
                        'country' => $provinceData['country'],
                        'description' => $provinceData['description'] ?? null,
                        'created_by' => Auth::id(),
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();

            return back()->with('success', "Berhasil membuat {$createdCount} provinsi.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $provinces = Province::with('cities')
            ->orderBy('country')
            ->orderBy('name')
            ->get();

        // Here you would implement the actual Excel export logic
        // For now, we'll just return a success message

        return back()->with('success', "Berhasil mengekspor {$provinces->count()} provinsi.");
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Here you would implement the actual file import logic
            // For now, we'll just return a success message
            $importedCount = 0;

            // Process the uploaded file
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                // Process CSV/Excel file and create provinces
                // This is a placeholder for the actual import logic
                $importedCount = 10; // Example count
            }

            DB::commit();

            return back()->with('success', "Berhasil mengimpor {$importedCount} provinsi.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getStatistics()
    {
        $totalProvinces = Province::count();
        $totalCountries = Province::distinct('country')->count();
        $provincesWithCities = Province::has('cities')->count();
        $provincesWithCustomers = Province::has('customers')->count();

        return response()->json([
            'total_provinces' => $totalProvinces,
            'total_countries' => $totalCountries,
            'provinces_with_cities' => $provincesWithCities,
            'provinces_with_customers' => $provincesWithCustomers,
        ]);
    }

    public function getProvincesByUsage()
    {
        $provinces = Province::withCount(['cities', 'customers', 'branches'])
            ->orderBy('customers_count', 'desc')
            ->orderBy('cities_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json($provinces);
    }

    // Get detailed information for delete confirmation
    public function getDeleteInfo($id, $type = 'province')
    {
        try {
            switch ($type) {
                case 'province':
                    $province = Province::findOrFail($id);
                    $hasCustomers = $province->customers()->exists();
                    $hasBranches = $province->branches()->exists();
                    $cities = $province->cities()->with(['districts.subdistricts'])->get();
                    
                    $cityCount = $cities->count();
                    $districtCount = $cities->sum(function($city) {
                        return $city->districts->count();
                    });
                    $subdistrictCount = $cities->sum(function($city) {
                        return $city->districts->sum(function($district) {
                            return $district->subdistricts->count();
                        });
                    });
                    
                    $relatedData = [];
                    if ($cityCount > 0) $relatedData[] = "{$cityCount} cities";
                    if ($districtCount > 0) $relatedData[] = "{$districtCount} districts";
                    if ($subdistrictCount > 0) $relatedData[] = "{$subdistrictCount} subdistricts";
                    if ($hasCustomers) $relatedData[] = "customers";
                    if ($hasBranches) $relatedData[] = "branches";
                    
                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'name' => $province->name,
                            'type' => 'province',
                            'related_data' => $relatedData,
                            'can_delete' => !$hasCustomers && !$hasBranches,
                            'cascade_delete' => $cityCount > 0 || $districtCount > 0 || $subdistrictCount > 0
                        ]
                    ]);
                    
                case 'city':
                    $city = City::findOrFail($id);
                    $hasBranches = $city->branches()->exists();
                    $districts = $city->districts()->with('subdistricts')->get();
                    
                    $districtCount = $districts->count();
                    $subdistrictCount = $districts->sum(function($district) {
                        return $district->subdistricts->count();
                    });
                    
                    $relatedData = [];
                    if ($districtCount > 0) $relatedData[] = "{$districtCount} districts";
                    if ($subdistrictCount > 0) $relatedData[] = "{$subdistrictCount} subdistricts";
                    if ($hasBranches) $relatedData[] = "branches";
                    
                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'name' => $city->name,
                            'type' => 'city',
                            'related_data' => $relatedData,
                            'can_delete' => !$hasBranches,
                            'cascade_delete' => $districtCount > 0 || $subdistrictCount > 0
                        ]
                    ]);
                    
                case 'district':
                    $district = District::findOrFail($id);
                    $hasBranches = $district->branches()->exists();
                    $subdistricts = $district->subdistricts()->get();
                    
                    $subdistrictCount = $subdistricts->count();
                    
                    $relatedData = [];
                    if ($subdistrictCount > 0) $relatedData[] = "{$subdistrictCount} subdistricts";
                    if ($hasBranches) $relatedData[] = "branches";
                    
                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'name' => $district->name,
                            'type' => 'district',
                            'related_data' => $relatedData,
                            'can_delete' => !$hasBranches,
                            'cascade_delete' => $subdistrictCount > 0
                        ]
                    ]);
                    
                case 'subdistrict':
                    $subdistrict = Subdistrict::findOrFail($id);
                    
                    return response()->json([
                        'status' => 'success',
                        'data' => [
                            'name' => $subdistrict->name,
                            'type' => 'subdistrict',
                            'related_data' => [],
                            'can_delete' => true,
                            'cascade_delete' => false
                        ]
                    ]);
                    
                default:
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid type specified'
                    ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get delete info: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:provinces,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            $errors = [];
            $cascadeMessages = [];

            foreach ($request->ids as $id) {
                $province = Province::find($id);
                
                // Check if province is used by customers or branches (these should prevent delete)
                $hasCustomers = $province->customers()->exists();
                $hasBranches = $province->branches()->exists();
                
                if ($hasCustomers || $hasBranches) {
                    $errors[] = "Province '{$province->name}' cannot be deleted because it has customers or branches.";
                    continue;
                }
                
                // Check if province has cities (we'll cascade delete these)
                $hasCities = $province->cities()->exists();
                $cascadeDeleted = [];
                
                if ($hasCities) {
                    // Get all cities to show in message
                    $cities = $province->cities()->get();
                    foreach ($cities as $city) {
                        // Cascade delete districts and subdistricts
                        $districts = $city->districts()->get();
                        foreach ($districts as $district) {
                            $district->subdistricts()->delete();
                            $cascadeDeleted[] = "Subdistricts in {$district->name}";
                        }
                        $city->districts()->delete();
                        $cascadeDeleted[] = "Districts in {$city->name}";
                    }
                    $province->cities()->delete();
                    $cascadeDeleted[] = "Cities in {$province->name}";
                }
                
                $province->delete();
                $deletedCount++;
                
                // Add cascade delete info to success message
                if (!empty($cascadeDeleted)) {
                    $cascadeMessages[] = "Province '{$province->name}' and its related data deleted: " . implode(', ', $cascadeDeleted);
                }
            }

            DB::commit();

            if ($deletedCount > 0) {
                $message = $deletedCount === 1 
                    ? '1 province has been successfully hidden.'
                    : "{$deletedCount} provinces have been successfully hidden.";
                
                // Add cascade delete info if any
                if (!empty($cascadeMessages)) {
                    $message .= "\n\nCascade deleted: " . implode('; ', $cascadeMessages);
                }
                
                return response()->json([
                    'success' => true,
                    'count' => $deletedCount,
                    'message' => $message,
                    'errors' => $errors,
                    'cascade_messages' => $cascadeMessages
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No provinces could be deleted.',
                    'errors' => $errors
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting provinces: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== CITY METHODS ====================
    
    public function getCities(Request $request, $provinceId = null)
    {
        // Get province_id from route parameter or query parameter
        $provinceId = $provinceId ?: $request->get('province_id');
        
        if (!$provinceId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Province ID is required'
            ], 400);
        }

        if (!Province::query()->whereKey($provinceId)->exists()) {
            return response()
                ->json([
                    'status' => 'success',
                    'data' => []
                ])
                ->header('Cache-Control', 'no-store');
        }

        $cacheKey = "location:cities:province:{$provinceId}:v2";
        $cities = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($provinceId) {
            return City::query()
                ->where('province_id', $provinceId)
                ->orderBy('name')
                ->get(['id', 'province_id', 'name', 'type'])
                ->toArray();
        });

        return response()
            ->json([
                'status' => 'success',
                'data' => $cities
            ])
            ->header('Cache-Control', 'public, max-age=1800');
    }

    public function storeCity(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'province_id' => 'required|exists:provinces,id,deleted_at,NULL',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:50',
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

            if ($this->activeLocationNameExists('cities', 'province_id', (int) $request->province_id, $request->name)) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'City already exists in this province.',
                    'errors' => ['name' => ['City already exists in this province.']]
                ], 422);
            }

            $city = City::create([
                'province_id' => $request->province_id,
                'name' => trim($request->name),
                'type' => $request->type ?? 'Kota',
            ]);

            DB::commit();
            $this->forgetCityLookupCache((int) $request->province_id);

            return response()->json([
                'status' => 'success',
                'message' => 'City created successfully.',
                'data' => $city
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create city: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateCity(Request $request, $cityId)
    {
        try {
            // Debug: Log the request data
            \Log::info('UpdateCity Request:', [
                'cityId' => $cityId,
                'request_data' => $request->all(),
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'headers' => $request->headers->all(),
                'raw_content' => $request->getContent()
            ]);

            // Get data from request (now JSON)
            $data = $request->all();

            $validator = \Validator::make($data, [
                'name' => 'required|string|max:255',
                'type' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                \Log::error('UpdateCity Validation Failed:', [
                    'errors' => $validator->errors(),
                    'request_data' => $data,
                    'raw_request' => $request->all()
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $city = City::findOrFail($cityId);
            $originalProvinceId = (int) $city->province_id;

            if (!Province::query()->whereKey($originalProvinceId)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update city because its province is inactive.'
                ], 422);
            }

            if ($this->activeLocationNameExists('cities', 'province_id', $originalProvinceId, $data['name'], (int) $city->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'City already exists in this province.',
                    'errors' => ['name' => ['City already exists in this province.']]
                ], 422);
            }
            
            $city->update([
                'name' => trim($data['name']),
                'type' => $data['type'] ?? 'Kota',
            ]);

            $this->forgetCityLookupCache($originalProvinceId);

            \Log::info('UpdateCity Success:', [
                'cityId' => $cityId,
                'updated_data' => $city->toArray()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'City updated successfully.',
                'data' => $city->load(['province'])
            ]);
        } catch (\Exception $e) {
            \Log::error('UpdateCity Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cityId' => $cityId,
                'request_data' => $data ?? $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update city: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyCity($cityId)
    {
        try {
            $city = City::findOrFail($cityId);
            $provinceId = (int) $city->province_id;

            // Check if city is used by branches (these should prevent delete)
            $hasBranches = $city->branches()->exists();
            if ($hasBranches) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete city with existing branches.'
                ], 400);
            }

            // Check if city has districts (we'll cascade delete these)
            $hasDistricts = $city->districts()->exists();
            $cascadeDeleted = [];
            
            if ($hasDistricts) {
                $districts = $city->districts()->get();
                foreach ($districts as $district) {
                    // Cascade delete subdistricts
                    $district->subdistricts()->delete();
                    $cascadeDeleted[] = "Subdistricts in {$district->name}";
                }
                $city->districts()->delete();
                $cascadeDeleted[] = "Districts in {$city->name}";
            }

            $city->delete();
            $this->forgetCityLookupCache($provinceId);
            
            $message = 'City deleted successfully.';
            if (!empty($cascadeDeleted)) {
                $message .= ' Cascade deleted: ' . implode(', ', $cascadeDeleted);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'cascade_deleted' => $cascadeDeleted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete city: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== DISTRICT METHODS ====================
    
    public function getDistricts(Request $request, $cityId = null)
    {
        // Get city_id from route parameter or query parameter
        $cityId = $cityId ?: $request->get('city_id');
        
        if (!$cityId) {
            return response()->json([
                'status' => 'error',
                'message' => 'City ID is required'
            ], 400);
        }

        if (!City::query()->whereKey($cityId)->whereHas('province')->exists()) {
            return response()
                ->json([
                    'status' => 'success',
                    'data' => []
                ])
                ->header('Cache-Control', 'no-store');
        }

        $cacheKey = "location:districts:city:{$cityId}:v2";
        $districts = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($cityId) {
            return District::query()
                ->where('city_id', $cityId)
                ->orderBy('name')
                ->get(['id', 'city_id', 'name'])
                ->toArray();
        });

        return response()
            ->json([
                'status' => 'success',
                'data' => $districts
            ])
            ->header('Cache-Control', 'public, max-age=1800');
    }

    public function storeDistrict(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'city_id' => 'required|exists:cities,id,deleted_at,NULL',
            'name' => 'required|string|max:255',
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

            if (!City::query()->whereKey($request->city_id)->whereHas('province')->exists()) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot create district because its city or province is inactive.'
                ], 422);
            }

            if ($this->activeLocationNameExists('districts', 'city_id', (int) $request->city_id, $request->name)) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'District already exists in this city.',
                    'errors' => ['name' => ['District already exists in this city.']]
                ], 422);
            }

            $district = District::create([
                'city_id' => $request->city_id,
                'name' => trim($request->name),
            ]);

            DB::commit();
            $this->forgetDistrictLookupCache((int) $request->city_id);

            return response()->json([
                'status' => 'success',
                'message' => 'District created successfully.',
                'data' => $district
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create district: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateDistrict(Request $request, $districtId)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $district = District::findOrFail($districtId);
            $cityId = (int) $district->city_id;

            if (!City::query()->whereKey($cityId)->whereHas('province')->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update district because its city or province is inactive.'
                ], 422);
            }

            if ($this->activeLocationNameExists('districts', 'city_id', $cityId, $request->name, (int) $district->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'District already exists in this city.',
                    'errors' => ['name' => ['District already exists in this city.']]
                ], 422);
            }
            
            $district->update([
                'name' => trim($request->name),
            ]);

            $this->forgetDistrictLookupCache($cityId);

            return response()->json([
                'status' => 'success',
                'message' => 'District updated successfully.',
                'data' => $district->load(['city', 'city.province'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update district: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyDistrict($districtId)
    {
        try {
            $district = District::findOrFail($districtId);
            $cityId = (int) $district->city_id;

            // Check if district is used by branches (these should prevent delete)
            $hasBranches = $district->branches()->exists();
            if ($hasBranches) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete district with existing branches.'
                ], 400);
            }

            // Check if district has subdistricts (we'll cascade delete these)
            $hasSubdistricts = $district->subdistricts()->exists();
            $cascadeDeleted = [];
            
            if ($hasSubdistricts) {
                $subdistricts = $district->subdistricts()->get();
                $district->subdistricts()->delete();
                $cascadeDeleted[] = "Subdistricts in {$district->name}";
            }

            $district->delete();
            $this->forgetDistrictLookupCache($cityId);
            
            $message = 'District deleted successfully.';
            if (!empty($cascadeDeleted)) {
                $message .= ' Cascade deleted: ' . implode(', ', $cascadeDeleted);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'cascade_deleted' => $cascadeDeleted
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete district: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== SUBDISTRICT METHODS ====================
    
    public function getSubdistricts(Request $request, $districtId = null)
    {
        // Get district_id from route parameter or query parameter
        $districtId = $districtId ?: $request->get('district_id');
        
        if (!$districtId) {
            return response()->json([
                'status' => 'error',
                'message' => 'District ID is required'
            ], 400);
        }

        if (!District::query()->whereKey($districtId)->whereHas('city.province')->exists()) {
            return response()
                ->json([
                    'status' => 'success',
                    'data' => []
                ])
                ->header('Cache-Control', 'no-store');
        }

        $cacheKey = "location:subdistricts:district:{$districtId}:v2";
        $subdistricts = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($districtId) {
            return Subdistrict::query()
                ->where('district_id', $districtId)
                ->orderBy('name')
                ->get(['id', 'district_id', 'name', 'postal_code'])
                ->toArray();
        });

        return response()
            ->json([
                'status' => 'success',
                'data' => $subdistricts
            ])
            ->header('Cache-Control', 'public, max-age=1800');
    }

    public function storeSubdistrict(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'district_id' => 'required|exists:districts,id,deleted_at,NULL',
            'name' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:10',
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

            if (!District::query()->whereKey($request->district_id)->whereHas('city.province')->exists()) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot create subdistrict because its district, city, or province is inactive.'
                ], 422);
            }

            if ($this->activeLocationNameExists('subdistricts', 'district_id', (int) $request->district_id, $request->name)) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Subdistrict already exists in this district.',
                    'errors' => ['name' => ['Subdistrict already exists in this district.']]
                ], 422);
            }

            $subdistrict = Subdistrict::create([
                'district_id' => $request->district_id,
                'name' => trim($request->name),
                'postal_code' => $request->postal_code,
            ]);

            DB::commit();
            $this->forgetSubdistrictLookupCache((int) $request->district_id);

            return response()->json([
                'status' => 'success',
                'message' => 'Subdistrict created successfully.',
                'data' => $subdistrict
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create subdistrict: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateSubdistrict(Request $request, $subdistrictId)
    {
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subdistrict = Subdistrict::findOrFail($subdistrictId);
            $districtId = (int) $subdistrict->district_id;

            if (!District::query()->whereKey($districtId)->whereHas('city.province')->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update subdistrict because its district, city, or province is inactive.'
                ], 422);
            }

            if ($this->activeLocationNameExists('subdistricts', 'district_id', $districtId, $request->name, (int) $subdistrict->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Subdistrict already exists in this district.',
                    'errors' => ['name' => ['Subdistrict already exists in this district.']]
                ], 422);
            }
            
            $subdistrict->update([
                'name' => trim($request->name),
                'postal_code' => $request->postal_code,
            ]);

            $this->forgetSubdistrictLookupCache($districtId);

            return response()->json([
                'status' => 'success',
                'message' => 'Subdistrict updated successfully.',
                'data' => $subdistrict->load(['district', 'district.city', 'district.city.province'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update subdistrict: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroySubdistrict($subdistrictId)
    {
        try {
            $subdistrict = Subdistrict::findOrFail($subdistrictId);
            $districtId = (int) $subdistrict->district_id;

            // Check if subdistrict is used by branches
            $hasBranches = $subdistrict->branches()->exists();
            if ($hasBranches) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete subdistrict with existing branches.'
                ], 400);
            }

            $subdistrict->delete();
            $this->forgetSubdistrictLookupCache($districtId);
            return response()->json([
                'status' => 'success',
                'message' => 'Subdistrict deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete subdistrict: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== SHOW METHODS FOR CITY, DISTRICT, SUBDISTRICT ====================
    
    public function showCity($cityId)
    {
        try {
            $city = City::with(['province', 'districts', 'branches'])
                ->withCount(['districts', 'branches'])
                ->findOrFail($cityId);
            
            return response()->json([
                'status' => 'success',
                'data' => $city
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load city: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showDistrict($districtId)
    {
        try {
            $district = District::with(['city.province', 'subdistricts', 'branches'])
                ->withCount(['subdistricts', 'branches'])
                ->findOrFail($districtId);
            
            return response()->json([
                'status' => 'success',
                'data' => $district
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load district: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showSubdistrict($subdistrictId)
    {
        try {
            $subdistrict = Subdistrict::with(['district.city.province', 'branches'])
                ->withCount(['branches'])
                ->findOrFail($subdistrictId);
            
            return response()->json([
                'status' => 'success',
                'data' => $subdistrict
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load subdistrict: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== EDIT METHODS FOR CITY, DISTRICT, SUBDISTRICT ====================
    
    public function editCity($cityId)
    {
        try {
            $city = City::with(['province'])
                ->findOrFail($cityId);
            
            return response()->json([
                'status' => 'success',
                'data' => $city
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load city: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editDistrict($districtId)
    {
        try {
            $district = District::with(['city.province'])
                ->findOrFail($districtId);
            
            return response()->json([
                'status' => 'success',
                'data' => $district
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load district: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editSubdistrict($subdistrictId)
    {
        try {
            $subdistrict = Subdistrict::with(['district.city.province'])
                ->findOrFail($subdistrictId);
            
            return response()->json([
                'status' => 'success',
                'data' => $subdistrict
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load subdistrict: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== BULK DELETE METHODS ====================
    
    public function bulkDeleteCities(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:cities,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            $errors = [];

            foreach ($request->ids as $id) {
                $city = City::find($id);
                
                // Check if city is used
                $hasDistricts = $city->districts()->exists();
                $hasBranches = $city->branches()->exists();
                
                if ($hasDistricts || $hasBranches) {
                    $errors[] = "City '{$city->name}' cannot be deleted because it has related data.";
                    continue;
                }

                $city->delete();
                $deletedCount++;
            }

            DB::commit();

            if ($deletedCount > 0) {
                return response()->json([
                    'success' => true,
                    'count' => $deletedCount,
                    'message' => $deletedCount === 1 
                        ? '1 city has been successfully hidden.'
                        : "{$deletedCount} cities have been successfully hidden.",
                    'errors' => $errors
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No cities could be deleted.',
                    'errors' => $errors
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting cities: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDeleteDistricts(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:districts,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            $errors = [];

            foreach ($request->ids as $id) {
                $district = District::find($id);
                
                // Check if district is used
                $hasSubdistricts = $district->subdistricts()->exists();
                $hasBranches = $district->branches()->exists();
                
                if ($hasSubdistricts || $hasBranches) {
                    $errors[] = "District '{$district->name}' cannot be deleted because it has related data.";
                    continue;
                }

                $district->delete();
                $deletedCount++;
            }

            DB::commit();

            if ($deletedCount > 0) {
                return response()->json([
                    'success' => true,
                    'count' => $deletedCount,
                    'message' => $deletedCount === 1 
                        ? '1 district has been successfully hidden.'
                        : "{$deletedCount} districts have been successfully hidden.",
                    'errors' => $errors
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No districts could be deleted.',
                    'errors' => $errors
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting districts: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDeleteSubdistricts(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:subdistricts,id'
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            $errors = [];

            foreach ($request->ids as $id) {
                $subdistrict = Subdistrict::find($id);
                
                // Check if subdistrict is used
                $hasBranches = $subdistrict->branches()->exists();
                
                if ($hasBranches) {
                    $errors[] = "Subdistrict '{$subdistrict->name}' cannot be deleted because it has related data.";
                    continue;
                }

                $subdistrict->delete();
                $deletedCount++;
            }

            DB::commit();

            if ($deletedCount > 0) {
                return response()->json([
                    'success' => true,
                    'count' => $deletedCount,
                    'message' => $deletedCount === 1 
                        ? '1 subdistrict has been successfully hidden.'
                        : "{$deletedCount} subdistricts have been successfully hidden.",
                    'errors' => $errors
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No subdistricts could be deleted.',
                    'errors' => $errors
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting subdistricts: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function forgetProvinceLookupCache(): void
    {
        Cache::forget('location:provinces:v1');
    }

    protected function forgetCityLookupCache(int $provinceId): void
    {
        Cache::forget("location:cities:province:{$provinceId}:v1");
        Cache::forget("location:cities:province:{$provinceId}:v2");
    }

    protected function forgetDistrictLookupCache(int $cityId): void
    {
        Cache::forget("location:districts:city:{$cityId}:v1");
        Cache::forget("location:districts:city:{$cityId}:v2");
    }

    protected function forgetSubdistrictLookupCache(int $districtId): void
    {
        Cache::forget("location:subdistricts:district:{$districtId}:v1");
        Cache::forget("location:subdistricts:district:{$districtId}:v2");
    }

    protected function activeLocationNameExists(string $table, string $parentColumn, int $parentId, string $name, ?int $exceptId = null): bool
    {
        $query = DB::table($table)
            ->where($parentColumn, $parentId)
            ->whereNull('deleted_at')
            ->whereRaw("LOWER(REPLACE(TRIM(name), ' ', '')) = ?", [
                $this->normalizeLocationName($name)
            ]);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    protected function normalizeLocationName(string $name): string
    {
        return strtolower(str_replace(' ', '', trim($name)));
    }
}

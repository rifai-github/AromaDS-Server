<?php

namespace App\Http\Controllers\Operational;

use App\Http\Controllers\Controller;
use App\Models\TechnicianLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class TechnicianLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TechnicianLocation::with(['technician', 'jobSchedule']);

        // Apply filters
        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->technician_id);
        }

        if ($request->filled('job_schedule_id')) {
            $query->where('job_schedule_id', $request->job_schedule_id);
        }

        if ($request->filled('activity_type')) {
            $query->where('activity_type', $request->activity_type);
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'online':
                    $query->online();
                    break;
                case 'offline':
                    $query->offline();
                    break;
                case 'moving':
                    $query->moving();
                    break;
                case 'stationary':
                    $query->stationary();
                    break;
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('timestamp', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('timestamp', '<=', $request->date_to);
        }

        if ($request->filled('recent')) {
            $query->recent();
        }

        $locations = $query->orderBy('timestamp', 'desc')->paginateStd(25);
        $technicians = User::where('is_active', true)->where('roles', 'technician')->get();

        return view('operational.technician-locations.index', compact('locations', 'technicians'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $technicians = User::where('is_active', true)->get();
        return view('operational.technician-locations.create', compact('technicians'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'technician_id' => 'required|exists:users,id',
            'job_schedule_id' => 'nullable|exists:job_schedules,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_address' => 'nullable|string|max:255',
            'accuracy' => 'nullable|numeric|min:0|max:1000',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'network_type' => 'nullable|string|max:50',
            'speed' => 'nullable|numeric|min:0|max:200',
            'heading' => 'nullable|numeric|min:0|max:360',
            'altitude' => 'nullable|numeric|min:-1000|max:10000',
            'is_moving' => 'nullable|boolean',
            'activity_type' => 'nullable|string|max:50',
            'device_info' => 'nullable|array',
            'metadata' => 'nullable|array',
            'timestamp' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            
            // Set timestamp to now if not provided
            if (!$request->filled('timestamp')) {
                $data['timestamp'] = now();
            }

            // Set default values
            $data['is_moving'] = $request->boolean('is_moving', false);
            $data['activity_type'] = $request->activity_type ?? 'location_update';

            $location = TechnicianLocation::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Location recorded successfully',
                'data' => $location->load(['technician', 'jobSchedule'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error recording location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $location = TechnicianLocation::with(['technician'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json($location);
        }

        return view('operational.technician-locations.show', compact('location'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $location = TechnicianLocation::findOrFail($id);
        $technicians = User::where('is_active', true)->get();

        if (request()->ajax()) {
            return response()->json($location);
        }

        return view('operational.technician-locations.edit', compact('location', 'technicians'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $location = TechnicianLocation::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'technician_id' => 'required|exists:users,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:1000',
            'timestamp' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $location->update($request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Location updated successfully',
                'data' => $location
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $location = TechnicianLocation::findOrFail($id);
            $location->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Location deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get latest location for a technician.
     */
    public function getLatestLocation($technicianId)
    {
        try {
            $location = TechnicianLocation::getLatestForTechnician($technicianId);

            return response()->json([
                'status' => 'success',
                'data' => $location
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error getting latest location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get locations within radius.
     */
    public function getLocationsWithinRadius(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $radius = $request->radius ?? 1; // Default 1km radius
            $locations = TechnicianLocation::withinRadius(
                $request->latitude, 
                $request->longitude, 
                $radius
            )->with(['technician'])->get();

            return response()->json([
                'status' => 'success',
                'data' => $locations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error getting locations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get technician tracking history.
     */
    public function getTechnicianTracking($technicianId, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = TechnicianLocation::where('technician_id', $technicianId);

            if ($request->filled('date_from')) {
                $query->whereDate('timestamp', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('timestamp', '<=', $request->date_to);
            }

            $locations = $query->orderBy('timestamp', 'asc')->get();

            return response()->json([
                'status' => 'success',
                'data' => $locations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error getting tracking history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete locations.
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:technician_location_logs,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = TechnicianLocation::whereIn('id', $request->ids)->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => "Successfully deleted {$count} location(s)",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting locations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mobile API: Update technician location (as per BRD)
     */
    public function mobileUpdateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'technician_id' => 'required|exists:users,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:1000',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'network_type' => 'nullable|string|max:50',
            'speed' => 'nullable|numeric|min:0|max:200',
            'heading' => 'nullable|numeric|min:0|max:360',
            'altitude' => 'nullable|numeric|min:-1000|max:10000',
            'is_moving' => 'nullable|boolean',
            'activity_type' => 'nullable|string|max:50',
            'device_info' => 'nullable|array',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['timestamp'] = now();
            $data['is_moving'] = $request->boolean('is_moving', false);
            $data['activity_type'] = $request->activity_type ?? 'location_update';

            $location = TechnicianLocation::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Location updated successfully',
                'data' => [
                    'id' => $location->id,
                    'timestamp' => $location->timestamp->toISOString(),
                    'coordinates' => [
                        'latitude' => $location->latitude,
                        'longitude' => $location->longitude
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get technicians for dropdown
     */
    public function getTechnicians()
    {
        try {
            $technicians = User::where('is_active', true)
                ->whereHas('roles', function($query) {
                    $query->where('name', 'technician');
                })
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $technicians
            ]);

        } catch (\Exception $e) {
            // Fallback: get all active users if role relationship fails
            $technicians = User::where('is_active', true)
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $technicians
            ]);
        }
    }

    /**
     * Get real-time technician locations for dashboard
     */
    public function getRealTimeLocations(Request $request)
    {
        try {
            $query = TechnicianLocation::with(['technician', 'jobSchedule'])
                ->selectRaw('technician_id, latitude, longitude, timestamp, activity_type, is_moving, accuracy')
                ->whereIn('technician_id', function($q) {
                    $q->select('id')
                      ->from('users')
                      ->where('roles', 'technician')
                      ->where('is_active', true);
                });

            // Get latest location for each technician
            $locations = $query->whereIn('id', function($subQuery) {
                $subQuery->selectRaw('MAX(id)')
                         ->from('technician_location_logs')
                         ->groupBy('technician_id');
            })->get();

            return response()->json([
                'status' => 'success',
                'data' => $locations->map(function($location) {
                    return [
                        'technician_id' => $location->technician_id,
                        'technician_name' => $location->technician->name ?? 'Unknown',
                        'coordinates' => [
                            'latitude' => $location->latitude,
                            'longitude' => $location->longitude
                        ],
                        'timestamp' => $location->timestamp->toISOString(),
                        'activity_type' => $location->activity_type,
                        'is_moving' => $location->is_moving,
                        'accuracy' => $location->accuracy,
                        'status' => $location->timestamp->diffInMinutes(now()) <= 5 ? 'online' : 'offline'
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error getting real-time locations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get technician tracking statistics
     */
    public function getTrackingStats(Request $request)
    {
        try {
            $stats = [
                'total_technicians' => User::where('is_active', true)->where('roles', 'technician')->count(),
                
                'online_technicians' => TechnicianLocation::online()
                    ->distinct('technician_id')
                    ->count('technician_id'),
                
                'moving_technicians' => TechnicianLocation::moving()
                    ->where('timestamp', '>=', now()->subMinutes(5))
                    ->distinct('technician_id')
                    ->count('technician_id'),
                
                'locations_today' => TechnicianLocation::whereDate('timestamp', today())->count(),
                
                'avg_accuracy' => TechnicianLocation::whereDate('timestamp', today())
                    ->whereNotNull('accuracy')
                    ->avg('accuracy')
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error getting tracking stats: ' . $e->getMessage()
            ], 500);
        }
    }
}

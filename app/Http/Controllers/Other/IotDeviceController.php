<?php

namespace App\Http\Controllers\Other;

use App\Http\Controllers\Controller;
use App\Models\IotDevice;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IotDeviceController extends Controller
{
    public function index(Request $request)
    {
        $query = IotDevice::with(['room']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('device_name', 'like', "%{$search}%")
                  ->orWhere('device_id', 'like', "%{$search}%")
                  ->orWhereHas('room', function($q) use ($search) {
                      $q->where('room_name', 'like', "%{$search}%");
                  });
        }

        // Filter by device type
        if ($request->filled('type')) {
            $query->where('device_type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'online') {
                $query->online();
            } elseif ($request->status === 'offline') {
                $query->offline();
            }
        }

        // Filter by room
        if ($request->filled('room')) {
            $query->where('room_id', $request->room);
        }

        $devices = $query->orderBy('created_at', 'desc')->paginateStd(25);

        // Calculate pagination data
        $pagination = [
            'current_page' => $devices->currentPage(),
            'last_page' => $devices->lastPage(),
            'per_page' => $devices->perPage(),
            'total' => $devices->total()
        ];

        // Get filter options
        $rooms = Room::where('is_active', true)->get();
        $deviceTypes = IotDevice::getDeviceTypes();

        if ($request->ajax()) {
            return response()->json([
                'devices' => $devices->items(),
                'pagination' => $pagination
            ]);
        }

        return view('other.iot-devices.index', compact(
            'devices', 
            'pagination', 
            'rooms', 
            'deviceTypes'
        ));
    }

    public function show($id)
    {
        $device = IotDevice::with(['room', 'data' => function($query) {
            $query->latest()->limit(10);
        }, 'alerts' => function($query) {
            $query->latest()->limit(5);
        }])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json($device);
        }

        return view('other.iot-devices.show', compact('device'));
    }

    public function create()
    {
        $rooms = Room::where('is_active', true)->get();
        $deviceTypes = IotDevice::getDeviceTypes();

        if (request()->ajax()) {
            return response()->json([
                'rooms' => $rooms,
                'deviceTypes' => $deviceTypes
            ]);
        }

        return view('other.iot-devices.create', compact('rooms', 'deviceTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string|max:255|unique:iot_devices,device_id',
            'device_name' => 'required|string|max:255',
            'device_type' => 'required|string|in:diffuser,sensor,controller',
            'room_id' => 'required|exists:rooms,id',
            'is_active' => 'boolean'
        ]);

        $device = IotDevice::create([
            'device_id' => $request->device_id,
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
            'room_id' => $request->room_id,
            'is_active' => $request->boolean('is_active', true)
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'IoT device created successfully',
                'device' => $device
            ]);
        }

        return redirect()->route('other.iot-devices.index')
                        ->with('success', 'IoT device created successfully');
    }

    public function edit($id)
    {
        $device = IotDevice::with('room')->findOrFail($id);
        $rooms = Room::where('is_active', true)->get();
        $deviceTypes = IotDevice::getDeviceTypes();

        if (request()->ajax()) {
            return response()->json([
                'device' => $device,
                'rooms' => $rooms,
                'deviceTypes' => $deviceTypes
            ]);
        }

        return view('other.iot-devices.edit', compact('device', 'rooms', 'deviceTypes'));
    }

    public function update(Request $request, $id)
    {
        $device = IotDevice::findOrFail($id);

        $request->validate([
            'device_id' => 'required|string|max:255|unique:iot_devices,device_id,' . $id,
            'device_name' => 'required|string|max:255',
            'device_type' => 'required|string|in:diffuser,sensor,controller',
            'room_id' => 'required|exists:rooms,id',
            'is_active' => 'boolean'
        ]);

        $device->update([
            'device_id' => $request->device_id,
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
            'room_id' => $request->room_id,
            'is_active' => $request->boolean('is_active', true)
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'IoT device updated successfully',
                'device' => $device
            ]);
        }

        return redirect()->route('other.iot-devices.index')
                        ->with('success', 'IoT device updated successfully');
    }

    public function destroy($id)
    {
        $device = IotDevice::findOrFail($id);
        $device->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'IoT device deleted successfully'
            ]);
        }

        return redirect()->route('other.iot-devices.index')
                        ->with('success', 'IoT device deleted successfully');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:iot_devices,id'
        ]);

        $count = IotDevice::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "Successfully deleted {$count} IoT device(s)",
            'count' => $count
        ]);
    }

    public function toggleStatus($id)
    {
        $device = IotDevice::findOrFail($id);
        $device->update(['is_active' => !$device->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Device status updated successfully',
            'is_active' => $device->is_active
        ]);
    }

    public function getDeviceData($id, Request $request)
    {
        $device = IotDevice::findOrFail($id);
        
        $query = $device->data();
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('recorded_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('recorded_at', '<=', $request->date_to);
        }
        
        $data = $query->orderBy('recorded_at', 'desc')
                     ->limit($request->get('limit', 100))
                     ->get();

        return response()->json($data);
    }

    public function getDeviceAlerts($id)
    {
        $device = IotDevice::findOrFail($id);
        $alerts = $device->alerts()
                         ->orderBy('created_at', 'desc')
                         ->limit(20)
                         ->get();

        return response()->json($alerts);
    }

    public function getStatistics()
    {
        $stats = [
            'total' => IotDevice::count(),
            'active' => IotDevice::where('is_active', true)->count(),
            'online' => IotDevice::online()->count(),
            'offline' => IotDevice::offline()->count(),
            'by_type' => IotDevice::selectRaw('device_type, COUNT(*) as count')
                               ->groupBy('device_type')
                               ->pluck('count', 'device_type'),
            'recent_alerts' => \App\Models\IotAlert::unresolved()->count()
        ];

        return response()->json($stats);
    }
}

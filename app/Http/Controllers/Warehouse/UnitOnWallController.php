<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\UnitOnWall;
use App\Models\Customer;
use App\Models\Building;
use App\Models\MasterRoom;
use App\Models\MasterRental;
use App\Models\MasterProduct;
use App\Models\SerialNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UnitOnWallController extends Controller
{
    use ColumnFilterTrait;

    public function index(Request $request)
    {
        $query = UnitOnWall::with(['customer', 'building', 'room', 'rental', 'product', 'serialNumber', 'createdBy', 'updatedBy']);

        $this->applyColumnFilters($query, null, [
            'serialNumber.serial_number' => ['relation' => 'serialNumber', 'column' => 'serial_number'],
            'customer.name' => ['relation' => 'customer', 'column' => 'name'],
            'building.nama_gedung' => ['relation' => 'building', 'column' => 'nama_gedung'],
            'room.room_name' => ['relation' => 'room', 'column' => 'room_name'],
            'rental.rental_name' => ['relation' => 'rental', 'column' => 'rental_name'],
            'product.name' => ['relation' => 'product', 'column' => 'name'],
            'status' => ['column' => 'status'],
            'install_date' => ['column' => 'install_date', 'type' => 'date'],
            'last_service_date' => ['column' => 'last_service_date', 'type' => 'date'],
            'temperature' => ['column' => 'temperature'],
            'created_at' => ['column' => 'created_at', 'type' => 'date'],
            'createdBy.name' => ['relation' => 'createdBy', 'column' => 'name'],
            'updated_at' => ['column' => 'updated_at', 'type' => 'date'],
            'updatedBy.name' => ['relation' => 'updatedBy', 'column' => 'name'],
        ]);

        // Filtering
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('rental_id')) {
            $query->where('rental_id', $request->rental_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('serial_number')) {
            $query->where('serial_number', 'like', '%' . $request->serial_number . '%');
        }

        if ($request->filled('install_date_from')) {
            $query->whereDate('install_date', '>=', $request->install_date_from);
        }

        if ($request->filled('install_date_to')) {
            $query->whereDate('install_date', '<=', $request->install_date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('serial_number', 'like', '%' . $search . '%')
                  ->orWhere('notes', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('building', function ($buildingQuery) use ($search) {
                      $buildingQuery->where('nama_gedung', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('room', function ($roomQuery) use ($search) {
                      $roomQuery->where('room_name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('product', function ($productQuery) use ($search) {
                      $productQuery->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        $units = $query->orderBy('created_at', 'desc')->paginate(15);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $units->items(),
                'pagination' => [
                    'total' => $units->total(),
                    'per_page' => $units->perPage(),
                    'current_page' => $units->currentPage(),
                    'last_page' => $units->lastPage(),
                    'from' => $units->firstItem(),
                    'to' => $units->lastItem(),
                ]
            ]);
        }

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $buildings = Building::orderBy('nama_gedung')->get();
        $rooms = MasterRoom::orderBy('room_name')->get();
        $rentals = MasterRental::where('is_active', true)->orderBy('rental_name')->get();
        $products = MasterProduct::where('is_active', true)->orderBy('name')->get();

        return view('warehouse.unit-on-walls.index', compact('units', 'customers', 'buildings', 'rooms', 'rentals', 'products'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'building_id' => 'required|exists:buildings,id',
            'room_id' => 'nullable|exists:master_rooms,id',
            'rental_id' => 'nullable|exists:master_rentals,id',
            'product_id' => 'nullable|exists:master_products,id',
            'serial_number_id' => 'nullable|exists:serial_numbers,id',
            'serial_number' => 'nullable|string|max:100',
            'install_date' => 'required|date',
            'last_service_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,maintenance,removed',
            'notes' => 'nullable|string|max:500',
            'temperature' => 'nullable|numeric|between:-10,60',
            'warranty_expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $unit = UnitOnWall::create([
                'customer_id' => $request->customer_id,
                'building_id' => $request->building_id,
                'room_id' => $request->room_id,
                'rental_id' => $request->rental_id,
                'product_id' => $request->product_id,
                'serial_number_id' => $request->serial_number_id,
                'serial_number' => $request->serial_number,
                'install_date' => $request->install_date,
                'last_service_date' => $request->last_service_date,
                'status' => $request->status,
                'notes' => $request->notes,
                'temperature' => $request->temperature,
                'warranty_expires_at' => $request->warranty_expires_at,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Unit on wall created successfully',
                'data' => $unit->load(['customer', 'building', 'room', 'rental', 'product', 'serialNumber', 'createdBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create unit on wall: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(UnitOnWall $unitOnWall)
    {
        // Load relationships
        $unitOnWall->load([
            'customer', 
            'building', 
            'room', 
            'rental', 
            'product', 
            'serialNumber', 
            'createdBy', 
            'updatedBy',
            'installHistories.customer',
            'installHistories.technician',
            'installHistories.jobSchedule',
            'serviceHistories.technician',
            'serviceHistories.jobSchedule',
            'repairHistories.technician',
            'repairHistories.jobSchedule'
        ]);
        
        // Load service history (from job schedules related to this unit)
        $serviceHistories = \App\Models\ServiceHistory::whereHas('jobSchedule', function($q) use ($unitOnWall) {
            $q->where('room_id', $unitOnWall->room_id)
              ->where('type', 'service');
        })
        ->orderBy('service_date', 'desc')
        ->with(['jobSchedule', 'technician'])
        ->get();
        
        // Load repair history (from unit repairs related to this unit)
        $repairHistories = \App\Models\UnitRepair::where('unit_id', $unitOnWall->serial_number_id ?? $unitOnWall->id)
            ->orWhere('room_id', $unitOnWall->room_id)
            ->orderBy('created_at', 'desc')
            ->with(['room', 'building', 'unit'])
            ->get();
        
        // Load installation history log (track where unit has been installed)
        // Track by serial_number to see all installations of this unit
        $installationLogs = [];
        if ($unitOnWall->serial_number) {
            // Get all UnitOnWall records with the same serial_number (unit has been moved/reinstalled)
            $installationLogs = \App\Models\UnitOnWall::where('serial_number', $unitOnWall->serial_number)
                ->where('id', '!=', $unitOnWall->id) // Exclude current record
                ->orderBy('install_date', 'desc')
                ->with(['customer', 'building', 'room', 'rental', 'product', 'serialNumber'])
                ->get();
        }
        
        // Also include current installation in the log
        $currentInstallation = [
            'id' => $unitOnWall->id,
            'serial_number' => $unitOnWall->serial_number,
            'serial_number_id' => $unitOnWall->serial_number_id,
            'has_serial_number' => !empty($unitOnWall->serial_number),
            'install_date' => $unitOnWall->install_date,
            'customer' => $unitOnWall->customer,
            'building' => $unitOnWall->building,
            'room' => $unitOnWall->room,
            'rental' => $unitOnWall->rental,
            'product' => $unitOnWall->product,
            'status' => $unitOnWall->status,
            'notes' => $unitOnWall->notes,
            'is_current' => true
        ];
        
        // Combine current and historical installations
        $allInstallationLogs = collect([$currentInstallation])->merge(
            collect($installationLogs)->map(function($log) {
                return [
                    'id' => $log->id,
                    'serial_number' => $log->serial_number,
                    'serial_number_id' => $log->serial_number_id,
                    'has_serial_number' => !empty($log->serial_number),
                    'install_date' => $log->install_date,
                    'customer' => $log->customer,
                    'building' => $log->building,
                    'room' => $log->room,
                    'rental' => $log->rental,
                    'product' => $log->product,
                    'status' => $log->status,
                    'notes' => $log->notes,
                    'is_current' => false
                ];
            })
        )->sortByDesc('install_date')->values();
        
        // Check if unit has wifi (based on product or serial number)
        $hasWifi = false;
        if ($unitOnWall->product) {
            // Check if product has wifi capability (you may need to add this field to products table)
            $hasWifi = $unitOnWall->product->has_wifi ?? false;
        }
        
        // Get unit logs (for wifi units - real-time monitoring)
        $unitLogs = [];
        if ($hasWifi && $unitOnWall->serial_number) {
            // TODO: Implement API call to get unit logs from IoT device
            // For now, we'll use mock data or empty array
            $unitLogs = [];
        }
        
        // Get global settings and unit-specific settings
        // TODO: Create UnitSetting model if needed, for now use null
        $globalSettings = null; // \App\Models\UnitSetting::where('is_global', true)->first();
        $unitSettings = null; // \App\Models\UnitSetting::where('unit_on_wall_id', $unitOnWall->id)->first();
        
        // Get last service date from job schedules
        // Find service jobs related to this unit that have been completed
        $lastServiceJob = \App\Models\JobSchedule::where('type', 'service')
            ->where('building_id', $unitOnWall->building_id)
            ->where('room_id', $unitOnWall->room_id)
            ->whereIn('status', ['completed', 'done_job'])
            ->where(function($query) use ($unitOnWall) {
                // Match by customer to ensure it's the right unit
                $query->whereHas('jobAdvice', function($q) use ($unitOnWall) {
                    $q->where('customer_id', $unitOnWall->customer_id);
                });
            })
            ->orderBy('completed_at', 'desc')
            ->orderBy('schedule_date', 'desc')
            ->first();
        
        $lastServiceDate = null;
        if ($lastServiceJob) {
            // Use completed_at if available, otherwise use schedule_date
            $lastServiceDate = $lastServiceJob->completed_at 
                ? \Carbon\Carbon::parse($lastServiceJob->completed_at)->toDateString()
                : ($lastServiceJob->schedule_date 
                    ? $lastServiceJob->schedule_date->toDateString() 
                    : null);
            
            // Update last_service_date in UnitOnWall if it's different
            if ($lastServiceDate && (!$unitOnWall->last_service_date || $lastServiceDate > $unitOnWall->last_service_date)) {
                $unitOnWall->update([
                    'last_service_date' => $lastServiceDate,
                    'updated_by' => Auth::id()
                ]);
                // Reload to get updated value
                $unitOnWall->refresh();
            }
        }
        
        // Pass lastServiceDate to view for display
        $lastServiceDateDisplay = $lastServiceDate ? \Carbon\Carbon::parse($lastServiceDate)->format('d M Y') : null;

        return view('warehouse.unit-on-walls.show', compact(
            'unitOnWall',
            'serviceHistories',
            'repairHistories',
            'hasWifi',
            'unitLogs',
            'globalSettings',
            'unitSettings',
            'allInstallationLogs',
            'lastServiceDateDisplay',
            'lastServiceJob'
        ));
    }

    public function edit(UnitOnWall $unitOnWall)
    {
        $unitOnWall->load(['customer', 'building', 'room', 'rental', 'product', 'serialNumber', 'createdBy', 'updatedBy']);

        return response()->json([
            'status' => 'success',
            'data' => $unitOnWall
        ]);
    }

    public function update(Request $request, UnitOnWall $unitOnWall)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'building_id' => 'required|exists:buildings,id',
            'room_id' => 'nullable|exists:master_rooms,id',
            'rental_id' => 'nullable|exists:master_rentals,id',
            'product_id' => 'nullable|exists:master_products,id',
            'serial_number_id' => 'nullable|exists:serial_numbers,id',
            'serial_number' => 'nullable|string|max:100',
            'install_date' => 'required|date',
            'last_service_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,maintenance,removed',
            'notes' => 'nullable|string|max:500',
            'temperature' => 'nullable|numeric|between:-10,60',
            'warranty_expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $unitOnWall->update([
                'customer_id' => $request->customer_id,
                'building_id' => $request->building_id,
                'room_id' => $request->room_id,
                'rental_id' => $request->rental_id,
                'product_id' => $request->product_id,
                'serial_number_id' => $request->serial_number_id,
                'serial_number' => $request->serial_number,
                'install_date' => $request->install_date,
                'last_service_date' => $request->last_service_date,
                'status' => $request->status,
                'notes' => $request->notes,
                'temperature' => $request->temperature,
                'warranty_expires_at' => $request->warranty_expires_at,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Unit on wall updated successfully',
                'data' => $unitOnWall->load(['customer', 'building', 'room', 'rental', 'product', 'serialNumber', 'createdBy', 'updatedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update unit on wall: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(UnitOnWall $unitOnWall)
    {
        try {
            DB::beginTransaction();
            $unitOnWall->delete();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Unit on wall deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete unit on wall: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_ids' => 'required|array',
            'unit_ids.*' => 'exists:unit_on_walls,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            $deletedCount = UnitOnWall::whereIn('id', $request->unit_ids)->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} unit(s).",
                'count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk delete units: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, UnitOnWall $unitOnWall)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,maintenance,removed',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $oldStatus = $unitOnWall->status;
            
            $unitOnWall->update([
                'status' => $request->status,
                'notes' => $request->notes,
                'updated_by' => Auth::id(),
            ]);
            
            // Auto-create history when status changes to active (install) or removed
            if ($oldStatus !== $request->status) {
                if ($request->status === 'active' && $oldStatus !== 'active') {
                    // Unit installed
                    \App\Models\UnitOnWallHistory::create([
                        'unit_on_wall_id' => $unitOnWall->id,
                        'action' => 'install',
                        'customer_id' => $unitOnWall->customer_id,
                        'customer_name' => $unitOnWall->customer?->name,
                        'location' => $unitOnWall->full_location,
                        'action_date' => now(),
                        'notes' => $request->notes ?? 'Unit installed',
                        'metadata' => json_encode(['old_status' => $oldStatus, 'new_status' => $request->status]),
                        'created_by' => Auth::id(),
                    ]);
                } elseif ($request->status === 'removed' && $oldStatus === 'active') {
                    // Unit removed
                    \App\Models\UnitOnWallHistory::create([
                        'unit_on_wall_id' => $unitOnWall->id,
                        'action' => 'remove',
                        'customer_id' => $unitOnWall->customer_id,
                        'customer_name' => $unitOnWall->customer?->name,
                        'location' => $unitOnWall->full_location,
                        'action_date' => now(),
                        'notes' => $request->notes ?? 'Unit removed',
                        'metadata' => json_encode(['old_status' => $oldStatus, 'new_status' => $request->status]),
                        'created_by' => Auth::id(),
                    ]);
                }
            }
            
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Unit status updated successfully',
                'data' => $unitOnWall->load(['customer', 'building', 'room', 'rental', 'product', 'serialNumber', 'createdBy', 'updatedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update unit status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateTemperature(Request $request, UnitOnWall $unitOnWall)
    {
        $validator = Validator::make($request->all(), [
            'temperature' => 'required|numeric|between:-10,60',
            'last_seen_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            $unitOnWall->update([
                'temperature' => $request->temperature,
                'last_seen_at' => $request->last_seen_at ?? now(),
                'updated_by' => Auth::id(),
            ]);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Unit temperature updated successfully',
                'data' => $unitOnWall->load(['customer', 'building', 'room', 'rental', 'product', 'serialNumber', 'createdBy', 'updatedBy'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update unit temperature: ' . $e->getMessage()
            ], 500);
        }
    }
}

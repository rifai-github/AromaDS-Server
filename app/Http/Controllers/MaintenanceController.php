<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceRecord;
use App\Models\User;
use App\Models\SerialNumber;
use App\Models\Room;
use App\Models\Building;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaintenanceController extends Controller
{
    public function index()
    {
        $schedules = MaintenanceSchedule::with(['assignedTo', 'createdBy'])
            ->orderBy('scheduled_date', 'desc')
            ->paginateStd(25);

        return view('maintenance.index', compact('schedules'));
    }

    public function create()
    {
        $users = User::where('is_active', true)->get();
        $units = SerialNumber::with(['product', 'room', 'building'])->get();
        $rooms = Room::with('building')->get();
        $buildings = Building::with('customers')->get();

        return view('maintenance.create', compact('users', 'units', 'rooms', 'buildings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'maintenance_type' => 'required|in:preventive,corrective,predictive,emergency',
            'priority' => 'required|in:low,medium,high,critical',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'due_date' => 'nullable|date|after_or_equal:scheduled_date',
            'estimated_duration' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'checklist' => 'nullable|array',
            'attachments' => 'nullable|array'
        ]);

        $schedule = MaintenanceSchedule::create([
            'title' => $request->title,
            'description' => $request->description,
            'maintenance_type' => $request->maintenance_type,
            'priority' => $request->priority,
            'status' => MaintenanceSchedule::STATUS_SCHEDULED,
            'scheduled_date' => $request->scheduled_date,
            'scheduled_time' => $request->scheduled_time,
            'due_date' => $request->due_date,
            'estimated_duration' => $request->estimated_duration,
            'notes' => $request->notes,
            'assigned_to' => $request->assigned_to,
            'checklist' => $request->checklist,
            'attachments' => $request->attachments,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);

        return redirect()->route('maintenance.index')
            ->with('success', 'Maintenance schedule created successfully.');
    }

    public function show(MaintenanceSchedule $maintenance)
    {
        $maintenance->load(['assignedTo', 'createdBy', 'maintenanceRecords.performedBy']);
        
        return view('maintenance.show', compact('maintenance'));
    }

    public function edit(MaintenanceSchedule $maintenance)
    {
        $users = User::where('is_active', true)->get();
        $units = SerialNumber::with(['product', 'room', 'building'])->get();
        $rooms = Room::with('building')->get();
        $buildings = Building::with('customers')->get();

        return view('maintenance.edit', compact('maintenance', 'users', 'units', 'rooms', 'buildings'));
    }

    public function update(Request $request, MaintenanceSchedule $maintenance)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'maintenance_type' => 'required|in:preventive,corrective,predictive,emergency',
            'priority' => 'required|in:low,medium,high,critical',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|date_format:H:i',
            'due_date' => 'nullable|date|after_or_equal:scheduled_date',
            'estimated_duration' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'checklist' => 'nullable|array',
            'attachments' => 'nullable|array'
        ]);

        $maintenance->update([
            'title' => $request->title,
            'description' => $request->description,
            'maintenance_type' => $request->maintenance_type,
            'priority' => $request->priority,
            'scheduled_date' => $request->scheduled_date,
            'scheduled_time' => $request->scheduled_time,
            'due_date' => $request->due_date,
            'estimated_duration' => $request->estimated_duration,
            'notes' => $request->notes,
            'assigned_to' => $request->assigned_to,
            'checklist' => $request->checklist,
            'attachments' => $request->attachments,
            'updated_by' => Auth::id()
        ]);

        return redirect()->route('maintenance.index')
            ->with('success', 'Maintenance schedule updated successfully.');
    }

    public function destroy(MaintenanceSchedule $maintenance)
    {
        $maintenance->update([
            'deleted_by' => Auth::id(),
            'deleted_at' => now()
        ]);

        return redirect()->route('maintenance.index')
            ->with('success', 'Maintenance schedule deleted successfully.');
    }

    public function start(MaintenanceSchedule $maintenance)
    {
        $maintenance->markAsStarted();

        return redirect()->back()
            ->with('success', 'Maintenance started successfully.');
    }

    public function complete(MaintenanceSchedule $maintenance)
    {
        $maintenance->markAsCompleted();

        return redirect()->back()
            ->with('success', 'Maintenance completed successfully.');
    }

    public function cancel(MaintenanceSchedule $maintenance)
    {
        $maintenance->cancel();

        return redirect()->back()
            ->with('success', 'Maintenance cancelled successfully.');
    }

    // Maintenance Records
    public function records(MaintenanceSchedule $maintenance)
    {
        $records = $maintenance->maintenanceRecords()
            ->with(['performedBy', 'unit', 'room', 'building'])
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        return view('maintenance.records', compact('maintenance', 'records'));
    }

    public function createRecord(MaintenanceSchedule $maintenance)
    {
        $users = User::where('is_active', true)->get();
        $units = SerialNumber::with(['product', 'room', 'building'])->get();
        $rooms = Room::with('building')->get();
        $buildings = Building::with('customers')->get();

        return view('maintenance.create-record', compact('maintenance', 'users', 'units', 'rooms', 'buildings'));
    }

    public function storeRecord(Request $request, MaintenanceSchedule $maintenance)
    {
        $request->validate([
            'unit_id' => 'nullable|exists:serial_numbers,id',
            'room_id' => 'nullable|exists:rooms,id',
            'building_id' => 'nullable|exists:buildings,id',
            'maintenance_type' => 'required|string',
            'description' => 'required|string',
            'work_performed' => 'nullable|string',
            'findings' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'checklist_results' => 'nullable|array',
            'photos' => 'nullable|array',
            'attachments' => 'nullable|array',
            'cost' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'performed_by' => 'required|exists:users,id'
        ]);

        $record = MaintenanceRecord::create([
            'maintenance_schedule_id' => $maintenance->id,
            'unit_id' => $request->unit_id,
            'room_id' => $request->room_id,
            'building_id' => $request->building_id,
            'maintenance_type' => $request->maintenance_type,
            'description' => $request->description,
            'work_performed' => $request->work_performed,
            'findings' => $request->findings,
            'recommendations' => $request->recommendations,
            'checklist_results' => $request->checklist_results,
            'photos' => $request->photos,
            'attachments' => $request->attachments,
            'cost' => $request->cost,
            'duration_minutes' => $request->duration_minutes,
            'status' => MaintenanceRecord::STATUS_COMPLETED,
            'started_at' => now(),
            'completed_at' => now(),
            'performed_by' => $request->performed_by,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);

        return redirect()->route('maintenance.records', $maintenance)
            ->with('success', 'Maintenance record created successfully.');
    }

    public function showRecord(MaintenanceRecord $record)
    {
        $record->load(['maintenanceSchedule', 'performedBy', 'unit', 'room', 'building']);

        return view('maintenance.show-record', compact('record'));
    }

    // Dashboard and Statistics
    public function dashboard()
    {
        $stats = [
            'total_schedules' => MaintenanceSchedule::count(),
            'scheduled' => MaintenanceSchedule::scheduled()->count(),
            'in_progress' => MaintenanceSchedule::inProgress()->count(),
            'completed' => MaintenanceSchedule::completed()->count(),
            'overdue' => MaintenanceSchedule::overdue()->count(),
            'upcoming' => MaintenanceSchedule::upcoming()->count()
        ];

        $recentSchedules = MaintenanceSchedule::with(['assignedTo', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $upcomingSchedules = MaintenanceSchedule::upcoming()
            ->with(['assignedTo'])
            ->orderBy('scheduled_date')
            ->limit(5)
            ->get();

        return view('maintenance.dashboard', compact('stats', 'recentSchedules', 'upcomingSchedules'));
    }

    // API endpoints for AJAX
    public function getUnitsByBuilding(Request $request)
    {
        $buildingId = $request->building_id;
        $units = SerialNumber::whereHas('room', function($query) use ($buildingId) {
            $query->where('building_id', $buildingId);
        })->with(['product', 'room'])->get();

        return response()->json($units);
    }

    public function getRoomsByBuilding(Request $request)
    {
        $buildingId = $request->building_id;
        $rooms = Room::where('building_id', $buildingId)->get();

        return response()->json($rooms);
    }

    public function getMaintenanceTypes()
    {
        return response()->json([
            MaintenanceSchedule::TYPE_PREVENTIVE => 'Preventive',
            MaintenanceSchedule::TYPE_CORRECTIVE => 'Corrective',
            MaintenanceSchedule::TYPE_PREDICTIVE => 'Predictive',
            MaintenanceSchedule::TYPE_EMERGENCY => 'Emergency'
        ]);
    }

    public function getPriorities()
    {
        return response()->json([
            MaintenanceSchedule::PRIORITY_LOW => 'Low',
            MaintenanceSchedule::PRIORITY_MEDIUM => 'Medium',
            MaintenanceSchedule::PRIORITY_HIGH => 'High',
            MaintenanceSchedule::PRIORITY_CRITICAL => 'Critical'
        ]);
    }
}

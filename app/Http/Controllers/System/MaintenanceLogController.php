<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class MaintenanceLogController extends Controller
{
    public function index(Request $request)
    {
        $query = MaintenanceLog::with(['createdBy']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('maintenance_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('maintenance_type')) {
            $query->where('maintenance_type', $request->maintenance_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('end_time', '<=', $request->date_to);
        }

        $maintenanceLogs = $query->orderBy('start_time', 'desc')->paginate(25);

        // Get filter options
        $types = MaintenanceLog::getTypes();
        $statuses = MaintenanceLog::getStatuses();

        return view('system.maintenance-logs.index', compact('maintenanceLogs', 'types', 'statuses'));
    }

    public function create()
    {
        $types = MaintenanceLog::getTypes();
        $statuses = MaintenanceLog::getStatuses();

        return view('system.maintenance-logs.create', compact('types', 'statuses'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'maintenance_type' => 'required|string',
            'description' => 'required|string|max:1000',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'status' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $maintenanceLog = MaintenanceLog::create([
                'maintenance_type' => $request->maintenance_type,
                'description' => $request->description,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => $request->status,
                'created_by' => Auth::id()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Maintenance log created successfully',
                    'data' => $maintenanceLog
                ]);
            }

            return redirect()->route('system.maintenance-logs.index')
                           ->with('success', 'Maintenance log created successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating maintenance log: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error creating maintenance log: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $maintenanceLog = MaintenanceLog::with(['createdBy'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $maintenanceLog
            ]);
        }

        return view('system.maintenance-logs.show', compact('maintenanceLog'));
    }

    public function edit($id)
    {
        $maintenanceLog = MaintenanceLog::findOrFail($id);
        $types = MaintenanceLog::getTypes();
        $statuses = MaintenanceLog::getStatuses();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $maintenanceLog,
                'types' => $types,
                'statuses' => $statuses
            ]);
        }

        return view('system.maintenance-logs.edit', compact('maintenanceLog', 'types', 'statuses'));
    }

    public function update(Request $request, $id)
    {
        $maintenanceLog = MaintenanceLog::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'maintenance_type' => 'required|string',
            'description' => 'required|string|max:1000',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'status' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $maintenanceLog->update([
                'maintenance_type' => $request->maintenance_type,
                'description' => $request->description,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => $request->status
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Maintenance log updated successfully',
                    'data' => $maintenanceLog
                ]);
            }

            return redirect()->route('system.maintenance-logs.index')
                           ->with('success', 'Maintenance log updated successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating maintenance log: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error updating maintenance log: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $maintenanceLog = MaintenanceLog::findOrFail($id);

        try {
            $maintenanceLog->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Maintenance log deleted successfully'
                ]);
            }

            return redirect()->route('system.maintenance-logs.index')
                           ->with('success', 'Maintenance log deleted successfully');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting maintenance log: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Error deleting maintenance log: ' . $e->getMessage());
        }
    }

    public function getUpcomingMaintenance()
    {
        $maintenanceLogs = MaintenanceLog::upcoming()
                                       ->with(['createdBy'])
                                       ->orderBy('start_time')
                                       ->get();

        return response()->json([
            'success' => true,
            'data' => $maintenanceLogs
        ]);
    }

    public function getMaintenanceStatistics()
    {
        $statistics = [
            'total_maintenance' => MaintenanceLog::count(),
            'completed' => MaintenanceLog::completed()->count(),
            'in_progress' => MaintenanceLog::inProgress()->count(),
            'scheduled' => MaintenanceLog::scheduled()->count(),
            'failed' => MaintenanceLog::failed()->count(),
            'upcoming' => MaintenanceLog::upcoming()->count(),
            'average_duration' => MaintenanceLog::completed()
                                               ->whereNotNull('start_time')
                                               ->whereNotNull('end_time')
                                               ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as avg_duration')
                                               ->value('avg_duration')
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    public function export()
    {
        $maintenanceLogs = MaintenanceLog::with(['createdBy'])->get();

        $csvData = [];
        $csvData[] = ['Type', 'Description', 'Start Time', 'End Time', 'Duration', 'Status', 'Created By', 'Created At'];

        foreach ($maintenanceLogs as $log) {
            $csvData[] = [
                $log->type_text,
                $log->description,
                $log->start_time->format('Y-m-d H:i:s'),
                $log->end_time ? $log->end_time->format('Y-m-d H:i:s') : 'N/A',
                $log->formatted_duration,
                $log->status_text,
                $log->createdBy->name ?? 'N/A',
                $log->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'maintenance_logs_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}

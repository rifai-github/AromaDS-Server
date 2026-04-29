<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\WorkingHour;
use App\Models\WorkingHoursException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkingHoursController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkingHour::with(['user']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $workingHours = $query->orderBy('user_id')->orderBy('day_of_week')->paginate(25);

        // Get filter options
        $users = User::where('is_active', true)->get();
        $daysOfWeek = WorkingHour::getDayNames();

        return view('system.working-hours.index', compact('workingHours', 'users', 'daysOfWeek'));
    }

    public function create()
    {
        $users = User::where('is_active', true)->get();
        $daysOfWeek = WorkingHour::getDayNames();

        return view('system.working-hours.create', compact('users', 'daysOfWeek'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if working hours already exist for this user and day
            $existing = WorkingHour::where('user_id', $request->user_id)
                                 ->where('day_of_week', $request->day_of_week)
                                 ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Working hours already exist for this user and day'
                ], 422);
            }

            $workingHour = WorkingHour::create([
                'user_id' => $request->user_id,
                'day_of_week' => $request->day_of_week,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'is_active' => $request->boolean('is_active', true)
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Working hours created successfully',
                    'data' => $workingHour
                ]);
            }

            return redirect()->route('system.working-hours.index')
                           ->with('success', 'Working hours created successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating working hours: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error creating working hours: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $workingHour = WorkingHour::with(['user', 'exceptions'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $workingHour
            ]);
        }

        return view('system.working-hours.show', compact('workingHour'));
    }

    public function edit($id)
    {
        $workingHour = WorkingHour::with(['user'])->findOrFail($id);
        $users = User::where('is_active', true)->get();
        $daysOfWeek = WorkingHour::getDayNames();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $workingHour,
                'users' => $users,
                'daysOfWeek' => $daysOfWeek
            ]);
        }

        return view('system.working-hours.edit', compact('workingHour', 'users', 'daysOfWeek'));
    }

    public function update(Request $request, $id)
    {
        $workingHour = WorkingHour::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if working hours already exist for this user and day (excluding current record)
            $existing = WorkingHour::where('user_id', $request->user_id)
                                 ->where('day_of_week', $request->day_of_week)
                                 ->where('id', '!=', $id)
                                 ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Working hours already exist for this user and day'
                ], 422);
            }

            $workingHour->update([
                'user_id' => $request->user_id,
                'day_of_week' => $request->day_of_week,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'is_active' => $request->boolean('is_active', true)
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Working hours updated successfully',
                    'data' => $workingHour
                ]);
            }

            return redirect()->route('system.working-hours.index')
                           ->with('success', 'Working hours updated successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating working hours: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error updating working hours: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $workingHour = WorkingHour::findOrFail($id);

        try {
            $workingHour->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Working hours deleted successfully'
                ]);
            }

            return redirect()->route('system.working-hours.index')
                           ->with('success', 'Working hours deleted successfully');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting working hours: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Error deleting working hours: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:working_hours,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = WorkingHour::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} working hour(s)",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting working hours: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserWorkingHours($userId)
    {
        $workingHours = WorkingHour::where('user_id', $userId)
                                 ->orderBy('day_of_week')
                                 ->get();

        return response()->json([
            'success' => true,
            'data' => $workingHours
        ]);
    }

    public function getWorkingHoursForDate($userId, $date)
    {
        $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;
        
        $workingHour = WorkingHour::where('user_id', $userId)
                                ->where('day_of_week', $dayOfWeek)
                                ->where('is_active', true)
                                ->first();

        $exception = WorkingHoursException::where('user_id', $userId)
                                        ->whereDate('exception_date', $date)
                                        ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'regular_hours' => $workingHour,
                'exception' => $exception,
                'is_working_day' => $workingHour ? true : false,
                'has_exception' => $exception ? true : false
            ]
        ]);
    }

    public function createException(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'exception_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if exception already exists for this user and date
            $existing = WorkingHoursException::where('user_id', $request->user_id)
                                           ->whereDate('exception_date', $request->exception_date)
                                           ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exception already exists for this user and date'
                ], 422);
            }

            $exception = WorkingHoursException::create([
                'user_id' => $request->user_id,
                'exception_date' => $request->exception_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'reason' => $request->reason
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Working hours exception created successfully',
                    'data' => $exception
                ]);
            }

            return redirect()->back()
                           ->with('success', 'Working hours exception created successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating exception: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Error creating exception: ' . $e->getMessage());
        }
    }

    public function export()
    {
        $workingHours = WorkingHour::with(['user'])->get();

        $csvData = [];
        $csvData[] = ['User', 'Day', 'Start Time', 'End Time', 'Status', 'Created At'];

        foreach ($workingHours as $workingHour) {
            $csvData[] = [
                $workingHour->user->name ?? 'N/A',
                $workingHour->day_name,
                $workingHour->start_time->format('H:i'),
                $workingHour->end_time->format('H:i'),
                $workingHour->is_active ? 'Active' : 'Inactive',
                $workingHour->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'working_hours_export_' . date('Y-m-d_H-i-s') . '.csv';
        
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

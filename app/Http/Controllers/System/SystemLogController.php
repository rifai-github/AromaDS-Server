<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemLog::query();

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('message', 'like', "%{$search}%");
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('time_from')) {
            $query->whereTime('created_at', '>=', $request->time_from);
        }

        if ($request->filled('time_to')) {
            $query->whereTime('created_at', '<=', $request->time_to);
        }

        $systemLogs = $query->orderBy('created_at', 'desc')->paginateStd(25);

        // Get filter options
        $levels = SystemLog::getLevels();

        return view('system.system-logs.index', compact('systemLogs', 'levels'));
    }

    public function show($id)
    {
        $systemLog = SystemLog::findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $systemLog
            ]);
        }

        return view('system.system-logs.show', compact('systemLog'));
    }

    public function export(Request $request)
    {
        $query = SystemLog::query();

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('message', 'like', "%{$search}%");
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $systemLogs = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = ['Date', 'Level', 'Message', 'Context'];

        foreach ($systemLogs as $log) {
            $csvData[] = [
                $log->created_at->format('Y-m-d H:i:s'),
                $log->level,
                $log->message,
                $log->context ? json_encode($log->context) : ''
            ];
        }

        $filename = 'system_logs_export_' . date('Y-m-d_H-i-s') . '.csv';
        
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

    public function getLogSummary(Request $request)
    {
        $query = SystemLog::query();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $summary = [
            'total_logs' => $query->count(),
            'levels_breakdown' => $query->select('level', DB::raw('count(*) as count'))
                                      ->groupBy('level')
                                      ->orderBy('count', 'desc')
                                      ->get(),
            'hourly_breakdown' => $query->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
                                      ->groupBy('hour')
                                      ->orderBy('hour')
                                      ->get(),
            'daily_breakdown' => $query->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                                     ->groupBy('date')
                                     ->orderBy('date', 'desc')
                                     ->limit(30)
                                     ->get(),
            'error_count' => $query->whereIn('level', [SystemLog::EMERGENCY, SystemLog::ALERT, SystemLog::CRITICAL, SystemLog::ERROR])->count(),
            'warning_count' => $query->where('level', SystemLog::WARNING)->count(),
            'info_count' => $query->whereIn('level', [SystemLog::NOTICE, SystemLog::INFO])->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    public function getRecentErrors($limit = 50)
    {
        $errors = SystemLog::whereIn('level', [SystemLog::EMERGENCY, SystemLog::ALERT, SystemLog::CRITICAL, SystemLog::ERROR])
                          ->orderBy('created_at', 'desc')
                          ->limit($limit)
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $errors
        ]);
    }

    public function getRecentWarnings($limit = 50)
    {
        $warnings = SystemLog::where('level', SystemLog::WARNING)
                            ->orderBy('created_at', 'desc')
                            ->limit($limit)
                            ->get();

        return response()->json([
            'success' => true,
            'data' => $warnings
        ]);
    }

    public function getLogsByLevel($level, $limit = 100)
    {
        $logs = SystemLog::where('level', $level)
                        ->orderBy('created_at', 'desc')
                        ->limit($limit)
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function getLogsByTimeRange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $logs = SystemLog::whereBetween('created_at', [$request->start_time, $request->end_time])
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function cleanup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'days' => 'required|integer|min:7|max:365',
            'level' => 'nullable|in:' . implode(',', SystemLog::getLevelNames())
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cutoffDate = now()->subDays($request->days);
            $query = SystemLog::where('created_at', '<', $cutoffDate);

            if ($request->filled('level')) {
                $query->where('level', $request->level);
            }

            $deletedCount = $query->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully cleaned up {$deletedCount} system log entries"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cleaning up system logs: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLogStatistics(Request $request)
    {
        $query = SystemLog::query();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $statistics = [
            'total_logs' => $query->count(),
            'error_rate' => $query->whereIn('level', [SystemLog::EMERGENCY, SystemLog::ALERT, SystemLog::CRITICAL, SystemLog::ERROR])->count(),
            'warning_rate' => $query->where('level', SystemLog::WARNING)->count(),
            'info_rate' => $query->whereIn('level', [SystemLog::NOTICE, SystemLog::INFO])->count(),
            'debug_rate' => $query->where('level', SystemLog::DEBUG)->count(),
            'peak_hour' => $query->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
                               ->groupBy('hour')
                               ->orderBy('count', 'desc')
                               ->first(),
            'most_common_level' => $query->select('level', DB::raw('count(*) as count'))
                                       ->groupBy('level')
                                       ->orderBy('count', 'desc')
                                       ->first()
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }
}

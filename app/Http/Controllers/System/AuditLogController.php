<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::select([
                'id',
                'user_id',
                'action',
                'model_type',
                'model_id',
                'ip_address',
                'page_name',
                'module_name',
                'created_at',
            ])
            ->with(['user:id,name,email']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('model_type', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', "%{$request->ip_address}%");
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->paginateStd(25);

        // Get filter options
        $users = Cache::remember('audit-log:index:users', 300, function () {
            return User::where('is_active', true)->select('id', 'name', 'email')->orderBy('name')->get();
        });
        $actions = Cache::remember('audit-log:index:actions', 300, function () {
            return AuditLog::distinct()->pluck('action')->filter()->sort()->values();
        });
        $modelTypes = Cache::remember('audit-log:index:model-types', 300, function () {
            return AuditLog::distinct()->pluck('model_type')->filter()->sort()->values();
        });

        return view('system.audit-logs.index', compact('auditLogs', 'users', 'actions', 'modelTypes'));
    }

    public function show($id)
    {
        $auditLog = AuditLog::with(['user:id,name,email'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $auditLog
            ]);
        }

        return view('system.audit-logs.show', compact('auditLog'));
    }

    public function export(Request $request)
    {
        $query = AuditLog::select([
                'id',
                'user_id',
                'action',
                'model_type',
                'model_id',
                'ip_address',
                'old_values',
                'new_values',
                'changed_fields',
                'created_at',
            ])
            ->with(['user:id,name,email']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('model_type', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->get();

        $csvData = [];
        $csvData[] = ['Date', 'User', 'Action', 'Model', 'Model ID', 'IP Address', 'Changes'];

        foreach ($auditLogs as $log) {
            $csvData[] = [
                $log->created_at->format('Y-m-d H:i:s'),
                $log->user->name ?? 'N/A',
                $log->action_description,
                $log->model_name,
                $log->model_id ?? 'N/A',
                $log->ip_address,
                $log->formatted_changes
            ];
        }

        $filename = 'audit_logs_export_' . date('Y-m-d_H-i-s') . '.csv';
        
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

    public function getUserActivity($userId)
    {
        $auditLogs = AuditLog::select([
                            'id',
                            'user_id',
                            'action',
                            'model_type',
                            'model_id',
                            'ip_address',
                            'page_name',
                            'module_name',
                            'created_at',
                        ])
                           ->where('user_id', $userId)
                           ->orderBy('created_at', 'desc')
                           ->limit(50)
                           ->get();

        return response()->json([
            'success' => true,
            'data' => $auditLogs
        ]);
    }

    public function getModelHistory($modelType, $modelId)
    {
        $auditLogs = AuditLog::select([
                            'id',
                            'user_id',
                            'action',
                            'model_type',
                            'model_id',
                            'ip_address',
                            'page_name',
                            'module_name',
                            'created_at',
                        ])
                           ->where('model_type', $modelType)
                           ->where('model_id', $modelId)
                           ->orderBy('created_at', 'desc')
                           ->get();

        return response()->json([
            'success' => true,
            'data' => $auditLogs
        ]);
    }

    public function getActivitySummary(Request $request)
    {
        $query = AuditLog::query();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $cacheKey = 'audit-log:summary:' . md5(json_encode($request->only(['date_from', 'date_to'])));

        $summary = Cache::remember($cacheKey, 120, function () use ($query) {
            return [
                'total_activities' => (clone $query)->count(),
                'unique_users' => (clone $query)->distinct('user_id')->count('user_id'),
                'actions_breakdown' => (clone $query)->select('action', DB::raw('count(*) as count'))
                                           ->groupBy('action')
                                           ->orderBy('count', 'desc')
                                           ->get(),
                'models_breakdown' => (clone $query)->select('model_type', DB::raw('count(*) as count'))
                                          ->whereNotNull('model_type')
                                          ->groupBy('model_type')
                                          ->orderBy('count', 'desc')
                                          ->get(),
                'daily_activities' => (clone $query)->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                                          ->groupBy('date')
                                          ->orderBy('date', 'desc')
                                          ->limit(30)
                                          ->get(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    public function getTopUsers(Request $request)
    {
        $query = AuditLog::query();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $cacheKey = 'audit-log:top-users:' . md5(json_encode($request->only(['date_from', 'date_to'])));

        $topUsers = Cache::remember($cacheKey, 120, function () use ($query) {
            return $query->select('user_id', DB::raw('count(*) as activity_count'))
                         ->with('user:id,name,email')
                         ->groupBy('user_id')
                         ->orderBy('activity_count', 'desc')
                         ->limit(10)
                         ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $topUsers
        ]);
    }

    public function getRecentActivity($limit = 20)
    {
        $auditLogs = AuditLog::select([
                            'id',
                            'user_id',
                            'action',
                            'model_type',
                            'model_id',
                            'ip_address',
                            'page_name',
                            'module_name',
                            'created_at',
                        ])
                           ->with(['user:id,name,email'])
                           ->orderBy('created_at', 'desc')
                           ->limit($limit)
                           ->get();

        return response()->json([
            'success' => true,
            'data' => $auditLogs
        ]);
    }

    public function cleanup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'days' => 'required|integer|min:30|max:365'
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
            $deletedCount = AuditLog::where('created_at', '<', $cutoffDate)->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully cleaned up {$deletedCount} audit log entries older than {$request->days} days"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cleaning up audit logs: ' . $e->getMessage()
            ], 500);
        }
    }
}

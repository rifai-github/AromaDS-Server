<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\AccessControlService;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AuditTrailController extends Controller
{
    use ColumnFilterTrait;

    protected $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->accessControlService = $accessControlService;
    }

    /**
     * Display audit trails
     */
    public function index(Request $request)
    {
        $query = AuditLog::select([
                'id',
                'user_id',
                'action',
                'model_type',
                'model_id',
                'page_name',
                'ip_address',
                'user_agent',
                'created_at',
            ])
            ->with('user:id,name,email,username');

        // Filters
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('table_name')) {
            $query->where('model_type', 'like', '%' . $request->table_name . '%');
        }

        if ($request->filled('module_name')) {
            $moduleName = $request->module_name;
            $query->where(function ($q) use ($moduleName) {
                $q->where('module_name', 'like', '%' . $moduleName . '%')
                  ->orWhere('page_name', 'like', '%' . $moduleName . '%');
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Action filter from header
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Header search (generic)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('model_type', 'like', "%{$search}%")
                  ->orWhere('page_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhereHas('user', function($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $this->applyColumnFilters($query, null, [
            'id' => ['column' => 'id'],
            'model_type' => ['column' => 'model_type'],
            'model_id' => ['column' => 'model_id'],
            'action' => ['column' => 'action'],
            'user.name' => ['relation' => 'user', 'column' => 'name'],
            'ip_address' => ['column' => 'ip_address'],
            'user_agent' => ['column' => 'user_agent'],
            'created_at' => ['column' => 'created_at', 'type' => 'date'],
        ]);

        $perPage = $request->input('per_page', 10);
        $auditTrails = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $users = Cache::remember('audit-trail:index:users', 300, function () {
            return User::select('id', 'name', 'email')->orderBy('name')->get();
        }); // For filter dropdown

        return view('audit-trails.index', compact('auditTrails', 'users'));
    }

    /**
     * Display login history
     */
    public function loginHistory(Request $request)
    {
        $query = LoginHistory::select([
                'id',
                'user_id',
                'attempted_identifier',
                'ip_address',
                'login_at',
                'logout_at',
                'is_successful',
                'failure_reason',
            ])
            ->with('user:id,name,email');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by IP
        if ($request->filled('ip_address')) {
            $query->where('ip_address', $request->ip_address);
        }

        // Filter by success/failure (auto-filter mapping)
        if ($request->filled('status')) {
            $isSuccessful = $request->status === 'success';
            $query->where('is_successful', $isSuccessful);
        }

        // Filter by date range (independent start/end)
        if ($request->filled('start_date')) {
            $query->whereDate('login_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('login_at', '<=', $request->end_date);
        }

        $loginHistories = $query->orderBy('login_at', 'desc')->paginate(20);
        $users = Cache::remember('audit-trail:login-history:users', 300, function () {
            return User::select('id', 'name', 'email')->orderBy('name')->get();
        }); // For filter dropdown

        return view('audit-trails.login-history', compact('loginHistories', 'users'));
    }

    /**
     * Get audit trail for specific record
     */
    public function show(Request $request, $tableName, $recordId)
    {
        // If audit_id is provided, prioritize fetching that specific record
        if ($request->has('audit_id')) {
            $auditLog = AuditLog::with('user:id,name,email')->find($request->audit_id);
            return response()->json($auditLog ? [$auditLog] : []);
        }

        // Try exact match or relative match for model_type
        $query = AuditLog::where(function($q) use ($tableName) {
            $q->where('model_type', 'like', '%' . $tableName)
              ->orWhere('page_name', 'like', '%' . $tableName . '%');
        })
        ->select([
            'id',
            'user_id',
            'action',
            'model_type',
            'model_id',
            'page_name',
            'ip_address',
            'user_agent',
            'created_at',
        ])
        ->where('model_id', $recordId)
        ->with('user:id,name,email')
        ->orderBy('created_at', 'desc');

        $auditLogs = $query->get();

        return response()->json($auditLogs);
    }

    /**
     * Export audit trails
     */
    public function export(Request $request)
    {
        $query = AuditLog::select([
                'id',
                'user_id',
                'action',
                'model_type',
                'model_id',
                'page_name',
                'ip_address',
                'user_agent',
                'created_at',
            ])
            ->with('user:id,name,email');

        // Apply same filters as index
        if ($request->has('table_name') && $request->table_name != '') {
            $query->where('model_type', 'like', '%' . $request->table_name . '%');
        }
        if ($request->has('action') && $request->action != '') {
            $query->where('action', $request->action);
        }
        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $auditTrails = $query->orderBy('created_at', 'desc')->get();

        // Generate CSV
        $filename = 'audit_trails_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($auditTrails) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Table', 'Record ID', 'Action', 'User', 
                'IP Address', 'User Agent', 'Created At'
            ]);

            // CSV data
            foreach ($auditTrails as $auditTrail) {
                fputcsv($file, [
                    $auditTrail->id,
                    class_basename($auditTrail->model_type),
                    $auditTrail->model_id,
                    $auditTrail->action,
                    $auditTrail->user ? $auditTrail->user->name : 'N/A',
                    $auditTrail->ip_address,
                    $auditTrail->user_agent,
                    $auditTrail->created_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

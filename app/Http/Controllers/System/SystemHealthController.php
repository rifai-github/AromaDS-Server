<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\SystemHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SystemHealthController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemHealth::query();

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('component', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('component')) {
            $query->where('component', $request->component);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $systemHealths = $query->orderBy('created_at', 'desc')->paginate(25);

        // Get filter options
        $components = SystemHealth::getComponents();
        $statuses = SystemHealth::getStatuses();

        return view('system.system-health.index', compact('systemHealths', 'components', 'statuses'));
    }

    public function show($id)
    {
        $systemHealth = SystemHealth::findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $systemHealth
            ]);
        }

        return view('system.system-health.show', compact('systemHealth'));
    }

    public function getOverallHealth()
    {
        $overallStatus = SystemHealth::getOverallStatus();
        $latestChecks = SystemHealth::orderBy('created_at', 'desc')
                                  ->limit(10)
                                  ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'overall_status' => $overallStatus,
                'latest_checks' => $latestChecks
            ]
        ]);
    }

    public function getComponentHealth($component)
    {
        $health = SystemHealth::where('component', $component)
                             ->orderBy('created_at', 'desc')
                             ->limit(10)
                             ->get();

        return response()->json([
            'success' => true,
            'data' => $health
        ]);
    }

    public function getHealthStatistics()
    {
        $statistics = [
            'total_checks' => SystemHealth::count(),
            'healthy' => SystemHealth::healthy()->count(),
            'warning' => SystemHealth::warning()->count(),
            'critical' => SystemHealth::critical()->count(),
            'unknown' => SystemHealth::where('status', SystemHealth::UNKNOWN)->count(),
            'overall_status' => SystemHealth::getOverallStatus(),
            'recent_checks' => SystemHealth::recent(60)->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    public function getCriticalIssues()
    {
        $criticalIssues = SystemHealth::critical()
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        return response()->json([
            'success' => true,
            'data' => $criticalIssues
        ]);
    }

    public function getWarnings()
    {
        $warnings = SystemHealth::warning()
                              ->orderBy('created_at', 'desc')
                              ->get();

        return response()->json([
            'success' => true,
            'data' => $warnings
        ]);
    }

    public function export()
    {
        $systemHealths = SystemHealth::all();

        $csvData = [];
        $csvData[] = ['Component', 'Status', 'Message', 'Created At'];

        foreach ($systemHealths as $health) {
            $csvData[] = [
                $health->component_text,
                $health->status_text,
                $health->message,
                $health->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'system_health_export_' . date('Y-m-d_H-i-s') . '.csv';
        
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

<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BackupLogController extends Controller
{
    public function index(Request $request)
    {
        $query = BackupLog::query();

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('file_path', 'like', "%{$search}%")
                  ->orWhere('backup_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('backup_type')) {
            $query->where('backup_type', $request->backup_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $backupLogs = $query->orderBy('created_at', 'desc')->paginate(25);

        // Get filter options
        $types = BackupLog::getTypes();
        $statuses = BackupLog::getStatuses();

        return view('system.backup-logs.index', compact('backupLogs', 'types', 'statuses'));
    }

    public function show($id)
    {
        $backupLog = BackupLog::findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $backupLog
            ]);
        }

        return view('system.backup-logs.show', compact('backupLog'));
    }

    public function getBackupStatistics()
    {
        $statistics = [
            'total_backups' => BackupLog::count(),
            'completed' => BackupLog::completed()->count(),
            'failed' => BackupLog::failed()->count(),
            'in_progress' => BackupLog::where('status', BackupLog::IN_PROGRESS)->count(),
            'total_size' => BackupLog::completed()->sum('file_size'),
            'average_size' => BackupLog::completed()->avg('file_size'),
            'success_rate' => BackupLog::count() > 0 ? 
                round((BackupLog::completed()->count() / BackupLog::count()) * 100, 2) : 0
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    public function getRecentBackups($limit = 10)
    {
        $backupLogs = BackupLog::orderBy('created_at', 'desc')
                              ->limit($limit)
                              ->get();

        return response()->json([
            'success' => true,
            'data' => $backupLogs
        ]);
    }

    public function getBackupsByType($type)
    {
        $backupLogs = BackupLog::where('backup_type', $type)
                              ->orderBy('created_at', 'desc')
                              ->get();

        return response()->json([
            'success' => true,
            'data' => $backupLogs
        ]);
    }

    public function export()
    {
        $backupLogs = BackupLog::all();

        $csvData = [];
        $csvData[] = ['Type', 'File Path', 'File Size', 'Status', 'Created At'];

        foreach ($backupLogs as $log) {
            $csvData[] = [
                $log->type_text,
                $log->file_path,
                $log->formatted_file_size,
                $log->status_text,
                $log->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'backup_logs_export_' . date('Y-m-d_H-i-s') . '.csv';
        
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

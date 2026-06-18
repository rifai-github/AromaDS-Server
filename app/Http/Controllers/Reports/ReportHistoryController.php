<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ReportHistory;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Response;

class ReportHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('reports.history.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'report_id' => 'required|exists:reports,id',
            'parameters' => 'nullable|array',
            'file_path' => 'nullable|string|max:500',
            'file_size' => 'nullable|integer',
            'execution_time' => 'nullable|numeric',
            'status' => 'required|string|in:success,failed,running'
        ]);

        $history = ReportHistory::create([
            'user_id' => auth()->id(),
            'report_id' => $request->report_id,
            'parameters' => $request->parameters,
            'file_path' => $request->file_path,
            'file_size' => $request->file_size,
            'execution_time' => $request->execution_time,
            'status' => $request->status,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Report history created successfully',
            'data' => $history->load('report', 'user')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ReportHistory $history): View
    {
        return view('reports.history.show', compact('history'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReportHistory $history): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:success,failed,running',
            'file_path' => 'nullable|string|max:500',
            'file_size' => 'nullable|integer',
            'execution_time' => 'nullable|numeric',
            'error_message' => 'nullable|string|max:1000'
        ]);

        $history->update([
            'status' => $request->status,
            'file_path' => $request->file_path,
            'file_size' => $request->file_size,
            'execution_time' => $request->execution_time,
            'error_message' => $request->error_message,
            'updated_by' => auth()->id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Report history updated successfully',
            'data' => $history->load('report', 'user')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReportHistory $history): JsonResponse
    {
        $history->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Report history deleted successfully'
        ]);
    }

    /**
     * Get report history with pagination and filters
     */
    public function getHistory(Request $request): JsonResponse
    {
        $query = ReportHistory::with(['report', 'user'])
            ->when($request->user_id, function ($q, $userId) {
                $q->where('user_id', $userId);
            })
            ->when($request->report_id, function ($q, $reportId) {
                $q->where('report_id', $reportId);
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->date_from, function ($q, $dateFrom) {
                $q->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($q, $dateTo) {
                $q->whereDate('created_at', '<=', $dateTo);
            })
            ->when($request->search, function ($q, $search) {
                $q->whereHas('report', function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                });
            });

        $history = $query->orderBy('created_at', 'desc')->paginateStd(25);

        return response()->json([
            'status' => 'success',
            'data' => $history->items(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
                'from' => $history->firstItem(),
                'to' => $history->lastItem()
            ]
        ]);
    }

    /**
     * Download report file
     */
    public function download(ReportHistory $history): Response
    {
        if (!$history->file_path || !file_exists(storage_path('app/' . $history->file_path))) {
            abort(404, 'File not found');
        }

        return response()->download(
            storage_path('app/' . $history->file_path),
            $history->report->name . '_' . $history->created_at->format('Y-m-d_H-i-s') . '.pdf'
        );
    }

    /**
     * Get history statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $query = ReportHistory::query();

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $statistics = [
            'total_reports' => $query->count(),
            'successful_reports' => $query->where('status', 'success')->count(),
            'failed_reports' => $query->where('status', 'failed')->count(),
            'running_reports' => $query->where('status', 'running')->count(),
            'average_execution_time' => $query->where('status', 'success')->avg('execution_time'),
            'total_file_size' => $query->where('status', 'success')->sum('file_size'),
            'most_used_reports' => $query->with('report')
                ->selectRaw('report_id, COUNT(*) as count')
                ->groupBy('report_id')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get()
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statistics
        ]);
    }
}

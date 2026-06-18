<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsMetric;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $query = AnalyticsEvent::with(['user']);

        // Apply filters
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $events = $query->orderBy('created_at', 'desc')->paginateStd(25);

        return view('reports.analytics.index', compact('events'));
    }

    public function metrics(Request $request)
    {
        $query = AnalyticsMetric::query();

        // Apply filters
        if ($request->filled('metric_name')) {
            $query->where('metric_name', $request->metric_name);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('metric_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('metric_date', '<=', $request->date_to);
        }

        $metrics = $query->orderBy('metric_date', 'desc')->paginateStd(25);

        return view('reports.analytics.metrics', compact('metrics'));
    }

    public function dashboard(Request $request)
    {
        // Get analytics summary for dashboard
        $summary = $this->getAnalyticsSummary($request);
        
        // Get recent events
        $recentEvents = AnalyticsEvent::with(['user'])
                                    ->orderBy('created_at', 'desc')
                                    ->limit(10)
                                    ->get();

        // Get metrics trends
        $metricsTrends = $this->getMetricsTrends($request);

        return view('reports.analytics.dashboard', compact('summary', 'recentEvents', 'metricsTrends'));
    }

    public function trackEvent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_type' => 'required|string|max:255',
            'event_data' => 'nullable|array',
            'user_id' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $event = AnalyticsEvent::create([
                'event_type' => $request->event_type,
                'event_data' => $request->event_data ?? [],
                'user_id' => $request->user_id ?? Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event tracked successfully',
                'data' => $event
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error tracking event: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addMetric(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'metric_name' => 'required|string|max:255',
            'metric_value' => 'required|numeric',
            'metric_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $metric = AnalyticsMetric::create([
                'metric_name' => $request->metric_name,
                'metric_value' => $request->metric_value,
                'metric_date' => $request->metric_date
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Metric added successfully',
                'data' => $metric
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding metric: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStatistics(Request $request)
    {
        try {
            $period = $request->get('period', '30'); // days
            $startDate = Carbon::now()->subDays($period);
            $endDate = Carbon::now();

            $statistics = [
                'total_events' => AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])->count(),
                'unique_users' => AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])
                                               ->distinct('user_id')
                                               ->count('user_id'),
                'page_views' => AnalyticsEvent::where('event_type', 'page_view')
                                             ->whereBetween('created_at', [$startDate, $endDate])
                                             ->count(),
                'reports_generated' => AnalyticsEvent::where('event_type', 'report_generated')
                                                    ->whereBetween('created_at', [$startDate, $endDate])
                                                    ->count(),
                'dashboard_views' => AnalyticsEvent::where('event_type', 'dashboard_view')
                                                  ->whereBetween('created_at', [$startDate, $endDate])
                                                  ->count(),
                'exports_downloaded' => AnalyticsEvent::where('event_type', 'export_download')
                                                     ->whereBetween('created_at', [$startDate, $endDate])
                                                     ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getEventTypes()
    {
        try {
            $eventTypes = AnalyticsEvent::select('event_type')
                                      ->distinct()
                                      ->pluck('event_type')
                                      ->toArray();

            return response()->json([
                'success' => true,
                'data' => $eventTypes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting event types: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMetricNames()
    {
        try {
            $metricNames = AnalyticsMetric::select('metric_name')
                                        ->distinct()
                                        ->pluck('metric_name')
                                        ->toArray();

            return response()->json([
                'success' => true,
                'data' => $metricNames
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting metric names: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportEvents(Request $request)
    {
        try {
            $query = AnalyticsEvent::with(['user']);

            // Apply same filters as index
            if ($request->filled('event_type')) {
                $query->where('event_type', $request->event_type);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $events = $query->orderBy('created_at', 'desc')->get();

            $csvData = "Event Type,User,Event Data,Created At\n";
            foreach ($events as $event) {
                $csvData .= sprintf(
                    "%s,%s,%s,%s\n",
                    $event->event_type,
                    $event->user ? $event->user->name : 'N/A',
                    json_encode($event->event_data),
                    $event->created_at->format('Y-m-d H:i:s')
                );
            }

            $fileName = 'analytics_events_' . date('Y-m-d_H-i-s') . '.csv';
            
            return response($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting events: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getAnalyticsSummary($request)
    {
        $period = $request->get('period', '30');
        $startDate = Carbon::now()->subDays($period);
        $endDate = Carbon::now();

        return [
            'total_events' => AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])->count(),
            'unique_users' => AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])
                                           ->distinct('user_id')
                                           ->count('user_id'),
            'top_event_types' => AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])
                                              ->select('event_type', DB::raw('count(*) as count'))
                                              ->groupBy('event_type')
                                              ->orderBy('count', 'desc')
                                              ->limit(5)
                                              ->get(),
            'active_users' => AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])
                                           ->with('user')
                                           ->select('user_id', DB::raw('count(*) as event_count'))
                                           ->groupBy('user_id')
                                           ->orderBy('event_count', 'desc')
                                           ->limit(10)
                                           ->get()
        ];
    }

    private function getMetricsTrends($request)
    {
        $period = $request->get('period', '30');
        $startDate = Carbon::now()->subDays($period);
        $endDate = Carbon::now();

        return AnalyticsMetric::whereBetween('metric_date', [$startDate, $endDate])
                             ->select('metric_name', 'metric_value', 'metric_date')
                             ->orderBy('metric_date', 'asc')
                             ->get()
                             ->groupBy('metric_name');
    }
}

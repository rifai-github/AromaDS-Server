<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\JobSchedule;
use App\Models\JobAssignSchedule;
use App\Models\JobAssignMaterialIssue;
use App\Models\Team;
use App\Models\Customer;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OperationalReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statistics = $this->getCachedStatistics();

        return view('reports.operational.index', compact('statistics'));
    }

    /**
     * Job Schedule Report.
     */
    public function jobScheduleReport(Request $request)
    {
        $query = JobSchedule::with(['team', 'customer']);

        // Filter by team
        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
        }

        $jobs = $query->orderBy('schedule_date', 'desc')->paginate(15);
        $teams = $this->getCachedTeams();

        $jobStatusCounts = Cache::remember('reports:operational:job-status-counts:v1', now()->addMinutes(2), function () {
            return JobSchedule::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');
        });

        $statistics = [
            'total' => (int) $jobStatusCounts->sum(),
            'completed' => (int) ($jobStatusCounts['completed'] ?? 0),
            'pending' => (int) ($jobStatusCounts['pending'] ?? 0),
            'in_progress' => (int) ($jobStatusCounts['in_progress'] ?? 0),
            'cancelled' => (int) ($jobStatusCounts['cancelled'] ?? 0),
        ];

        return view('reports.operational.job-schedule', compact('jobs', 'teams', 'statistics'));
    }

    /**
     * Job Assignment Report.
     */
    public function jobAssignmentReport(Request $request)
    {
        $query = JobAssignSchedule::with(['quotation', 'contract', 'building']);

        // Filter by assigned status
        if ($request->filled('assigned')) {
            $query->where('status', $request->assigned ? 'assigned' : 'pending');
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
        }

        // Filter by room type
        if ($request->filled('room_type')) {
            $query->where('room_type', $request->room_type);
        }

        $assignments = $query->orderBy('schedule_date', 'desc')->paginate(15);

        $statistics = [
            'total' => JobAssignSchedule::count(),
            'assigned' => JobAssignSchedule::where('status', 'assigned')->count(),
            'not_assigned' => JobAssignSchedule::where('status', 'pending')->count(),
            'today' => JobAssignSchedule::whereDate('schedule_date', today())->count(),
            'this_week' => JobAssignSchedule::whereBetween('schedule_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        return view('reports.operational.job-assignment', compact('assignments', 'statistics'));
    }

    /**
     * Material Issue Report.
     */
    public function materialIssueReport(Request $request)
    {
        $query = JobAssignMaterialIssue::with(['jobSchedule', 'customer', 'building', 'team', 'masterRental']);

        // Filter by issued status
        if ($request->filled('issued')) {
            $query->whereHas('materialIssue', function($q) use ($request) {
                $q->where('status', $request->issued ? 'issued' : 'draft');
            });
        }

        // Filter by team
        if ($request->filled('team_name')) {
            $query->where('team_name', 'like', "%{$request->team_name}%");
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('job_date', [$request->start_date, $request->end_date]);
        }

        $materials = $query->orderBy('job_date', 'desc')->paginate(15);

        $statistics = [
            'total' => JobAssignMaterialIssue::count(),
            'issued' => JobAssignMaterialIssue::whereHas('materialIssue', function($q) {
                $q->where('status', 'issued');
            })->count(),
            'not_issued' => JobAssignMaterialIssue::whereHas('materialIssue', function($q) {
                $q->where('status', 'draft');
            })->count(),
            'total_quantity' => JobAssignMaterialIssue::sum('quantity'),
            'issued_quantity' => JobAssignMaterialIssue::whereHas('materialIssue', function($q) {
                $q->where('status', 'issued');
            })->sum('quantity'),
        ];

        return view('reports.operational.material-issue', compact('materials', 'statistics'));
    }

    /**
     * Team Performance Report.
     */
    public function teamPerformanceReport(Request $request)
    {
        $query = JobSchedule::with(['team', 'customer']);

        // Filter by team
        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
        }

        $jobs = $query->orderBy('schedule_date', 'desc')->get();
        $teams = $this->getCachedTeams();

        // Calculate team performance
        $teamPerformance = $jobs->groupBy('team_id')->map(function ($teamJobs, $teamId) {
            $team = $teams->firstWhere('id', $teamId);
            return [
                'team' => $team,
                'total_jobs' => $teamJobs->count(),
                'completed_jobs' => $teamJobs->where('status', 'completed')->count(),
                'pending_jobs' => $teamJobs->where('status', 'pending')->count(),
                'in_progress_jobs' => $teamJobs->where('status', 'in_progress')->count(),
                'completion_rate' => $teamJobs->count() > 0 ? round(($teamJobs->where('status', 'completed')->count() / $teamJobs->count()) * 100, 2) : 0,
            ];
        });

        $statistics = [
            'total_teams' => $teams->count(),
            'active_teams' => $teamPerformance->count(),
            'average_completion_rate' => $teamPerformance->avg('completion_rate'),
        ];

        return view('reports.operational.team-performance', compact('teamPerformance', 'teams', 'statistics'));
    }

    /**
     * Customer Service Report.
     */
    public function customerServiceReport(Request $request)
    {
        $query = JobSchedule::with(['team', 'customer']);

        // Filter by customer
        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
        }

        $jobs = $query->orderBy('schedule_date', 'desc')->get();

        // Calculate customer service metrics
        $customerService = $jobs->groupBy('customer_name')->map(function ($customerJobs, $customerName) {
            return [
                'customer_name' => $customerName,
                'total_jobs' => $customerJobs->count(),
                'completed_jobs' => $customerJobs->where('status', 'completed')->count(),
                'pending_jobs' => $customerJobs->where('status', 'pending')->count(),
                'in_progress_jobs' => $customerJobs->where('status', 'in_progress')->count(),
                'completion_rate' => $customerJobs->count() > 0 ? round(($customerJobs->where('status', 'completed')->count() / $customerJobs->count()) * 100, 2) : 0,
                'last_service_date' => $customerJobs->max('schedule_date'),
            ];
        });

        $statistics = [
            'total_customers' => $customerService->count(),
            'active_customers' => $customerService->where('total_jobs', '>', 0)->count(),
            'average_completion_rate' => $customerService->avg('completion_rate'),
        ];

        return view('reports.operational.customer-service', compact('customerService', 'statistics'));
    }

    /**
     * Export Job Schedule Report.
     */
    public function exportJobScheduleReport(Request $request)
    {
        $query = JobSchedule::with(['team', 'customer']);

        // Apply filters
        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
        }

        $jobs = $query->orderBy('schedule_date', 'desc')->get();

        // Generate Excel/CSV export
        $fileName = 'job_schedule_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        // This is a placeholder for actual export logic
        // In a real application, you would use Laravel Excel or similar package
        
        return response()->json([
            'status' => 'success',
            'message' => 'Export generated successfully',
            'file_name' => $fileName,
            'data' => $jobs,
        ]);
    }

    /**
     * Export Job Assignment Report.
     */
    public function exportJobAssignmentReport(Request $request)
    {
        $query = JobAssignSchedule::with(['quotation', 'contract', 'building']);

        // Apply filters
        if ($request->filled('assigned')) {
            $query->where('status', $request->assigned ? 'assigned' : 'pending');
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('room_type')) {
            $query->where('room_type', $request->room_type);
        }

        $assignments = $query->orderBy('schedule_date', 'desc')->get();

        $fileName = 'job_assignment_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return response()->json([
            'status' => 'success',
            'message' => 'Export generated successfully',
            'file_name' => $fileName,
            'data' => $assignments,
        ]);
    }

    /**
     * Export Material Issue Report.
     */
    public function exportMaterialIssueReport(Request $request)
    {
        $query = JobAssignMaterialIssue::with(['jobSchedule', 'customer', 'building', 'team', 'masterRental']);

        // Apply filters
        if ($request->filled('issued')) {
            $query->whereHas('materialIssue', function($q) use ($request) {
                $q->where('status', $request->issued ? 'issued' : 'draft');
            });
        }

        if ($request->filled('team_name')) {
            $query->where('team_name', 'like', "%{$request->team_name}%");
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('job_date', [$request->start_date, $request->end_date]);
        }

        $materials = $query->orderBy('job_date', 'desc')->get();

        $fileName = 'material_issue_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return response()->json([
            'status' => 'success',
            'message' => 'Export generated successfully',
            'file_name' => $fileName,
            'data' => $materials,
        ]);
    }

    /**
     * Get operational statistics for API.
     */
    public function getOperationalStatistics()
    {
        $statistics = Cache::remember('reports:operational:api-statistics:v1', now()->addMinutes(2), function () {
            $jobStatusCounts = JobSchedule::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $assignmentStatusCounts = JobAssignSchedule::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'job_schedule' => [
                    'total' => (int) $jobStatusCounts->sum(),
                    'completed' => (int) ($jobStatusCounts['completed'] ?? 0),
                    'pending' => (int) ($jobStatusCounts['pending'] ?? 0),
                    'in_progress' => (int) ($jobStatusCounts['in_progress'] ?? 0),
                    'cancelled' => (int) ($jobStatusCounts['cancelled'] ?? 0),
                ],
                'job_assignment' => [
                    'total' => (int) $assignmentStatusCounts->sum(),
                    'assigned' => (int) ($assignmentStatusCounts['assigned'] ?? 0),
                    'not_assigned' => (int) ($assignmentStatusCounts['pending'] ?? 0),
                    'today' => JobAssignSchedule::whereDate('schedule_date', today())->count(),
                    'this_week' => JobAssignSchedule::whereBetween('schedule_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                ],
                'material_issue' => [
                    'total' => JobAssignMaterialIssue::count(),
                    'issued' => JobAssignMaterialIssue::whereHas('materialIssue', function($q) {
                        $q->where('status', 'issued');
                    })->count(),
                    'not_issued' => JobAssignMaterialIssue::whereHas('materialIssue', function($q) {
                        $q->where('status', 'draft');
                    })->count(),
                    'total_quantity' => JobAssignMaterialIssue::sum('quantity'),
                    'issued_quantity' => JobAssignMaterialIssue::whereHas('materialIssue', function($q) {
                        $q->where('status', 'issued');
                    })->sum('quantity'),
                ],
                'team_performance' => [
                    'total_teams' => Team::count(),
                    'active_teams' => JobAssignSchedule::whereNotNull('team_id')->distinct()->count('team_id'),
                ],
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }

    private function getCachedStatistics(): array
    {
        return Cache::remember('reports:operational:index-statistics:v1', now()->addMinutes(2), function () {
            $jobStatusCounts = JobSchedule::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $assignmentStatusCounts = JobAssignSchedule::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'total_jobs' => (int) $jobStatusCounts->sum(),
                'completed_jobs' => (int) ($jobStatusCounts['completed'] ?? 0),
                'pending_jobs' => (int) ($jobStatusCounts['pending'] ?? 0),
                'in_progress_jobs' => (int) ($jobStatusCounts['in_progress'] ?? 0),
                'total_assignments' => (int) $assignmentStatusCounts->sum(),
                'assigned_jobs' => (int) ($assignmentStatusCounts['assigned'] ?? 0),
                'unassigned_jobs' => (int) ($assignmentStatusCounts['pending'] ?? 0),
                'total_materials' => JobAssignMaterialIssue::count(),
                'issued_materials' => JobAssignMaterialIssue::whereHas('materialIssue', function($q) {
                    $q->where('status', 'issued');
                })->count(),
                'unissued_materials' => JobAssignMaterialIssue::whereHas('materialIssue', function($q) {
                    $q->where('status', 'draft');
                })->count(),
            ];
        });
    }

    private function getCachedTeams()
    {
        return Cache::remember('reports:operational:teams:v1', now()->addMinutes(10), function () {
            return Team::orderBy('team_name')->get();
        });
    }
}

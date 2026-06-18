<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\JobSchedule;
use App\Models\JobAssignSchedule;
use App\Models\JobAssignMaterialIssue;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statistics = [
            'total_teams' => Team::count(),
            'active_teams' => Team::where('status', 'active')->count(),
            'inactive_teams' => Team::where('status', 'inactive')->count(),
            'total_jobs' => JobSchedule::count(),
            'completed_jobs' => JobSchedule::where('status', 'completed')->count(),
            'pending_jobs' => JobSchedule::where('status', 'pending')->count(),
            'in_progress_jobs' => JobSchedule::where('status', 'in_progress')->count(),
            'total_assignments' => JobAssignSchedule::count(),
            'assigned_jobs' => JobAssignSchedule::where('assigned', true)->count(),
            'unassigned_jobs' => JobAssignSchedule::where('assigned', false)->count(),
        ];

        return view('reports.hr.index', compact('statistics'));
    }

    /**
     * Team Performance Report.
     */
    public function teamPerformanceReport(Request $request)
    {
        $query = Team::with(['jobSchedules']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by search
        if ($request->filled('search')) {
            $query->where('team_name', 'like', "%{$request->search}%");
        }

        $teams = $query->orderBy('team_name')->get();

        // Calculate team performance metrics
        $teamPerformance = $teams->map(function ($team) {
            $totalJobs = $team->jobSchedules->count();
            $completedJobs = $team->jobSchedules->where('status', 'completed')->count();
            $pendingJobs = $team->jobSchedules->where('status', 'pending')->count();
            $inProgressJobs = $team->jobSchedules->where('status', 'in_progress')->count();
            $cancelledJobs = $team->jobSchedules->where('status', 'cancelled')->count();

            // Calculate average completion time (if completion_date is available)
            $completedJobsWithDates = $team->jobSchedules->where('status', 'completed')->filter(function ($job) {
                return $job->completion_date && $job->schedule_date;
            });

            $averageCompletionTime = 0;
            if ($completedJobsWithDates->count() > 0) {
                $totalDays = $completedJobsWithDates->sum(function ($job) {
                    return $job->completion_date->diffInDays($job->schedule_date);
                });
                $averageCompletionTime = round($totalDays / $completedJobsWithDates->count(), 1);
            }

            return [
                'team' => $team,
                'jobs' => [
                    'total' => $totalJobs,
                    'completed' => $completedJobs,
                    'pending' => $pendingJobs,
                    'in_progress' => $inProgressJobs,
                    'cancelled' => $cancelledJobs,
                ],
                'performance' => [
                    'completion_rate' => $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 2) : 0,
                    'cancellation_rate' => $totalJobs > 0 ? round(($cancelledJobs / $totalJobs) * 100, 2) : 0,
                    'average_completion_time' => $averageCompletionTime,
                    'efficiency_score' => $totalJobs > 0 ? round((($completedJobs * 0.7) + (($totalJobs - $cancelledJobs) * 0.3)) / $totalJobs * 100, 2) : 0,
                ],
            ];
        });

        $statistics = [
            'total_teams' => $teams->count(),
            'active_teams' => $teams->where('status', 'active')->count(),
            'average_completion_rate' => $teamPerformance->avg('performance.completion_rate'),
            'average_efficiency_score' => $teamPerformance->avg('performance.efficiency_score'),
            'best_performing_team' => $teamPerformance->sortByDesc('performance.efficiency_score')->first(),
        ];

        return view('reports.hr.team-performance', compact('teamPerformance', 'statistics'));
    }

    /**
     * Team Workload Report.
     */
    public function teamWorkloadReport(Request $request)
    {
        $query = Team::with(['jobSchedules']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereHas('jobSchedules', function ($q) use ($request) {
                $q->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
            });
        }

        $teams = $query->orderBy('team_name')->get();

        // Calculate team workload metrics
        $teamWorkload = $teams->map(function ($team) use ($request) {
            $jobSchedules = $team->jobSchedules;
            
            // Filter by date range if provided
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $jobSchedules = $jobSchedules->filter(function ($job) use ($request) {
                    return $job->schedule_date >= $request->start_date && $job->schedule_date <= $request->end_date;
                });
            }

            $totalJobs = $jobSchedules->count();
            $completedJobs = $jobSchedules->where('status', 'completed')->count();
            $pendingJobs = $jobSchedules->where('status', 'pending')->count();
            $inProgressJobs = $jobSchedules->where('status', 'in_progress')->count();

            // Calculate workload distribution by month
            $monthlyWorkload = $jobSchedules->groupBy(function ($job) {
                return $job->schedule_date->format('Y-m');
            })->map(function ($monthJobs) {
                return [
                    'total' => $monthJobs->count(),
                    'completed' => $monthJobs->where('status', 'completed')->count(),
                    'pending' => $monthJobs->where('status', 'pending')->count(),
                    'in_progress' => $monthJobs->where('status', 'in_progress')->count(),
                ];
            });

            return [
                'team' => $team,
                'workload' => [
                    'total_jobs' => $totalJobs,
                    'completed_jobs' => $completedJobs,
                    'pending_jobs' => $pendingJobs,
                    'in_progress_jobs' => $inProgressJobs,
                    'completion_rate' => $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 2) : 0,
                    'workload_score' => $totalJobs > 0 ? round(($totalJobs * 0.4) + ($completedJobs * 0.6), 2) : 0,
                ],
                'monthly_distribution' => $monthlyWorkload,
            ];
        });

        $statistics = [
            'total_teams' => $teams->count(),
            'total_jobs' => $teamWorkload->sum('workload.total_jobs'),
            'completed_jobs' => $teamWorkload->sum('workload.completed_jobs'),
            'average_completion_rate' => $teamWorkload->avg('workload.completion_rate'),
            'average_workload_score' => $teamWorkload->avg('workload.workload_score'),
        ];

        return view('reports.hr.team-workload', compact('teamWorkload', 'statistics'));
    }

    /**
     * Job Assignment Report.
     */
    public function jobAssignmentReport(Request $request)
    {
        $query = JobAssignSchedule::with(['quotation', 'contract', 'building']);

        // Filter by assigned status
        if ($request->filled('assigned')) {
            $query->where('assigned', $request->assigned);
        }

        // Filter by room type
        if ($request->filled('room_type')) {
            $query->where('room_type', $request->room_type);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
        }

        $assignments = $query->orderBy('schedule_date', 'desc')->paginateStd(25);

        $statistics = [
            'total' => JobAssignSchedule::count(),
            'assigned' => JobAssignSchedule::where('assigned', true)->count(),
            'not_assigned' => JobAssignSchedule::where('assigned', false)->count(),
            'today' => JobAssignSchedule::whereDate('schedule_date', today())->count(),
            'this_week' => JobAssignSchedule::whereBetween('schedule_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => JobAssignSchedule::whereBetween('schedule_date', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ];

        return view('reports.hr.job-assignment', compact('assignments', 'statistics'));
    }

    /**
     * Material Issue Report.
     */
    public function materialIssueReport(Request $request)
    {
        $query = JobAssignMaterialIssue::with(['jobSchedule', 'customer', 'building', 'team', 'masterRental']);

        // Filter by issued status
        if ($request->filled('issued')) {
            $query->where('issued', $request->issued);
        }

        // Filter by team
        if ($request->filled('team_name')) {
            $query->where('team_name', 'like', "%{$request->team_name}%");
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('job_date', [$request->start_date, $request->end_date]);
        }

        $issues = $query->orderBy('job_date', 'desc')->paginateStd(25);

        $statistics = [
            'total' => JobAssignMaterialIssue::count(),
            'issued' => JobAssignMaterialIssue::where('issued', true)->count(),
            'not_issued' => JobAssignMaterialIssue::where('issued', false)->count(),
            'total_quantity' => JobAssignMaterialIssue::sum('quantity'),
            'issued_quantity' => JobAssignMaterialIssue::where('issued', true)->sum('quantity'),
            'today' => JobAssignMaterialIssue::whereDate('job_date', today())->count(),
            'this_week' => JobAssignMaterialIssue::whereBetween('job_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        return view('reports.hr.material-issue', compact('issues', 'statistics'));
    }

    /**
     * Team Efficiency Report.
     */
    public function teamEfficiencyReport(Request $request)
    {
        $query = Team::with(['jobSchedules']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereHas('jobSchedules', function ($q) use ($request) {
                $q->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
            });
        }

        $teams = $query->orderBy('team_name')->get();

        // Calculate team efficiency metrics
        $teamEfficiency = $teams->map(function ($team) use ($request) {
            $jobSchedules = $team->jobSchedules;
            
            // Filter by date range if provided
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $jobSchedules = $jobSchedules->filter(function ($job) use ($request) {
                    return $job->schedule_date >= $request->start_date && $job->schedule_date <= $request->end_date;
                });
            }

            $totalJobs = $jobSchedules->count();
            $completedJobs = $jobSchedules->where('status', 'completed')->count();
            $onTimeJobs = $jobSchedules->where('status', 'completed')->filter(function ($job) {
                // Assuming there's a completion_date field to compare with schedule_date
                return $job->completion_date && $job->completion_date <= $job->schedule_date;
            })->count();

            // Calculate efficiency metrics
            $completionRate = $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 2) : 0;
            $onTimeRate = $completedJobs > 0 ? round(($onTimeJobs / $completedJobs) * 100, 2) : 0;
            $efficiencyScore = $totalJobs > 0 ? round((($completedJobs * 0.6) + ($onTimeJobs * 0.4)) / $totalJobs * 100, 2) : 0;

            return [
                'team' => $team,
                'efficiency' => [
                    'total_jobs' => $totalJobs,
                    'completed_jobs' => $completedJobs,
                    'on_time_jobs' => $onTimeJobs,
                    'completion_rate' => $completionRate,
                    'on_time_rate' => $onTimeRate,
                    'efficiency_score' => $efficiencyScore,
                ],
            ];
        });

        $statistics = [
            'total_teams' => $teams->count(),
            'average_completion_rate' => $teamEfficiency->avg('efficiency.completion_rate'),
            'average_on_time_rate' => $teamEfficiency->avg('efficiency.on_time_rate'),
            'average_efficiency_score' => $teamEfficiency->avg('efficiency.efficiency_score'),
            'most_efficient_team' => $teamEfficiency->sortByDesc('efficiency.efficiency_score')->first(),
        ];

        return view('reports.hr.team-efficiency', compact('teamEfficiency', 'statistics'));
    }

    /**
     * Export Team Performance Report.
     */
    public function exportTeamPerformanceReport(Request $request)
    {
        $query = Team::with(['jobSchedules']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('team_name', 'like', "%{$request->search}%");
        }

        $teams = $query->orderBy('team_name')->get();

        $fileName = 'team_performance_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return response()->json([
            'status' => 'success',
            'message' => 'Export generated successfully',
            'file_name' => $fileName,
            'data' => $teams,
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
            $query->where('assigned', $request->assigned);
        }

        if ($request->filled('room_type')) {
            $query->where('room_type', $request->room_type);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
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
     * Get HR statistics for API.
     */
    public function getHrStatistics()
    {
        $statistics = [
            'teams' => [
                'total' => Team::count(),
                'active' => Team::where('status', 'active')->count(),
                'inactive' => Team::where('status', 'inactive')->count(),
            ],
            'job_schedules' => [
                'total' => JobSchedule::count(),
                'completed' => JobSchedule::where('status', 'completed')->count(),
                'pending' => JobSchedule::where('status', 'pending')->count(),
                'in_progress' => JobSchedule::where('status', 'in_progress')->count(),
                'cancelled' => JobSchedule::where('status', 'cancelled')->count(),
            ],
            'job_assignments' => [
                'total' => JobAssignSchedule::count(),
                'assigned' => JobAssignSchedule::where('assigned', true)->count(),
                'not_assigned' => JobAssignSchedule::where('assigned', false)->count(),
                'today' => JobAssignSchedule::whereDate('schedule_date', today())->count(),
                'this_week' => JobAssignSchedule::whereBetween('schedule_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            ],
            'material_issues' => [
                'total' => JobAssignMaterialIssue::count(),
                'issued' => JobAssignMaterialIssue::where('issued', true)->count(),
                'not_issued' => JobAssignMaterialIssue::where('issued', false)->count(),
                'total_quantity' => JobAssignMaterialIssue::sum('quantity'),
                'issued_quantity' => JobAssignMaterialIssue::where('issued', true)->sum('quantity'),
            ],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }
}

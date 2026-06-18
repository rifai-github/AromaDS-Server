<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prospect;
use App\Models\SalesActivity;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Customer;
use App\Models\JobSchedule;
use App\Models\Survey;
use App\Models\Quotation;
use App\Models\Building;
use App\Models\WarehouseProduct;
use App\Models\MarketingPipeline;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $currentMonth = (int) $now->month;
        $currentYear = (int) $now->year;
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        $next7Days = $now->copy()->addDays(7);
        $next14Days = $now->copy()->addDays(14);
        $next30Days = $now->copy()->addDays(30);

        $dashboardData = Cache::remember('dashboard:index:v2', now()->addSeconds(60), function () use (
            $today,
            $currentMonth,
            $currentYear,
            $startOfWeek,
            $endOfWeek,
            $next7Days,
            $next14Days,
            $next30Days
        ) {
            $jobStatusCounts = JobSchedule::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $surveyStatusCounts = Survey::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $prospectStatusCounts = Prospect::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $quotationStatusCounts = Quotation::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $pipelineStatusCounts = MarketingPipeline::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            $contractStatusCounts = Contract::select('contract_status', DB::raw('COUNT(*) as total'))
                ->groupBy('contract_status')
                ->pluck('total', 'contract_status');

            $customerActiveCounts = Customer::select('is_active', DB::raw('COUNT(*) as total'))
                ->groupBy('is_active')
                ->pluck('total', 'is_active');

            $scheduledJobsByDate = JobSchedule::selectRaw('DATE(schedule_date) as job_date, COUNT(*) as total')
                ->whereBetween('schedule_date', [$startOfWeek->copy()->startOfDay(), $endOfWeek->copy()->endOfDay()])
                ->groupBy(DB::raw('DATE(schedule_date)'))
                ->pluck('total', 'job_date');

            $completedJobsByDate = JobSchedule::selectRaw('DATE(completed_at) as job_date, COUNT(*) as total')
                ->whereIn('status', ['completed', 'done_job'])
                ->whereBetween('completed_at', [$startOfWeek->copy()->startOfDay(), $endOfWeek->copy()->endOfDay()])
                ->groupBy(DB::raw('DATE(completed_at)'))
                ->pluck('total', 'job_date');

            $jobsByDay = [];
            for ($i = 0; $i < 7; $i++) {
                $date = $startOfWeek->copy()->addDays($i);
                $dateKey = $date->toDateString();
                $jobsByDay[] = [
                    'day' => $date->format('D'),
                    'date' => $date->format('d M'),
                    'count' => (int) ($scheduledJobsByDate[$dateKey] ?? 0),
                    'completed' => (int) ($completedJobsByDate[$dateKey] ?? 0),
                ];
            }

            $jobStats = [
                'total' => (int) $jobStatusCounts->sum(),
                'in_progress' => (int) $jobStatusCounts->except(['completed', 'done_job', 'cancelled'])->sum(),
                'pending' => (int) (($jobStatusCounts['scheduled'] ?? 0) + ($jobStatusCounts['new_job'] ?? 0)),
                'completed_today' => JobSchedule::whereIn('status', ['completed', 'done_job'])
                    ->whereDate('completed_at', $today)
                    ->count(),
                'completed_this_month' => JobSchedule::whereIn('status', ['completed', 'done_job'])
                    ->whereMonth('completed_at', $currentMonth)
                    ->whereYear('completed_at', $currentYear)
                    ->count(),
                'suspended' => (int) ($jobStatusCounts['suspend'] ?? 0),
                'force_majeure' => (int) ($jobStatusCounts['force_majeure'] ?? 0),
                'today' => JobSchedule::whereDate('schedule_date', $today)->count(),
                'this_week' => JobSchedule::whereBetween('schedule_date', [$startOfWeek->copy()->startOfDay(), $endOfWeek->copy()->endOfDay()])->count(),
            ];

            // One row per job_number (a job spans multiple room rows; the dashboard has no
            // room column, so without de-duping the same job appears as identical rows).
            $jobsInProgress = JobSchedule::with(['building', 'assignedTechnician.department', 'jobAssignments.team'])
                ->whereNotIn('status', ['completed', 'done_job', 'cancelled'])
                ->orderBy('updated_at', 'desc')
                ->limit(40)
                ->get()
                ->unique(fn ($job) => $job->job_number ?: 'id-' . $job->id)
                ->take(10)
                ->values();

            $todaysJobs = JobSchedule::with(['building', 'assignedTechnician.department', 'jobAssignments.team'])
                ->whereDate('schedule_date', $today)
                ->orderBy('updated_at', 'desc')
                ->get();

            $upcomingJobs = JobSchedule::with(['building', 'assignedTechnician.department', 'jobAssignments.team'])
                ->where('schedule_date', '>', $today)
                ->where('schedule_date', '<=', $next7Days)
                ->whereNotIn('status', ['completed', 'done_job', 'cancelled'])
                ->orderBy('schedule_date', 'asc')
                ->limit(10)
                ->get();

            $surveyStats = [
                'total' => (int) $surveyStatusCounts->sum(),
                'pending' => (int) ($surveyStatusCounts['pending'] ?? 0),
                'scheduled' => (int) ($surveyStatusCounts['scheduled'] ?? 0),
                'completed' => (int) ($surveyStatusCounts['completed'] ?? 0),
                'this_month' => Survey::whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', $currentYear)
                    ->count(),
            ];

            $upcomingSurveys = Survey::with(['customer', 'assignedTo'])
                ->where('survey_date', '>=', $today)
                ->whereIn('status', ['pending', 'scheduled'])
                ->orderBy('survey_date', 'asc')
                ->limit(5)
                ->get();

            $prospectStats = [
                'total' => (int) $prospectStatusCounts->sum(),
                'new_this_month' => Prospect::whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', $currentYear)
                    ->count(),
                'hot' => (int) ($prospectStatusCounts['hot'] ?? 0),
                'warm' => (int) ($prospectStatusCounts['warm'] ?? 0),
                'cold' => (int) ($prospectStatusCounts['cold'] ?? 0),
            ];

            $recentProspects = Prospect::with(['assignedTo'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $quotationStats = [
                'total' => (int) $quotationStatusCounts->sum(),
                'pending' => (int) ($quotationStatusCounts['pending'] ?? 0),
                'approved' => (int) ($quotationStatusCounts['approved'] ?? 0),
                'this_month' => Quotation::whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', $currentYear)
                    ->count(),
            ];

            $recentQuotations = Quotation::with(['customer'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $pipelineStats = [
                'total' => (int) $pipelineStatusCounts->sum(),
                'prospect' => (int) ($pipelineStatusCounts['prospect'] ?? 0),
                'qualified' => (int) ($pipelineStatusCounts['qualified'] ?? 0),
                'converted' => (int) ($pipelineStatusCounts['converted'] ?? 0),
                'needs_followup' => MarketingPipeline::whereNotNull('follow_up_date')
                    ->where('follow_up_date', '<=', $next7Days)
                    ->whereNotIn('status', ['converted', 'unqualified'])
                    ->count(),
            ];

            $recentPipelines = MarketingPipeline::with(['customer', 'createdBy'])
                ->orderBy('follow_up_date', 'asc')
                ->orderBy('visit_date', 'desc')
                ->limit(8)
                ->get();

            $upcomingFollowups = MarketingPipeline::with(['customer'])
                ->whereNotNull('follow_up_date')
                ->where('follow_up_date', '>=', $today)
                ->where('follow_up_date', '<=', $next14Days)
                ->whereNotIn('status', ['converted', 'unqualified'])
                ->orderBy('follow_up_date', 'asc')
                ->limit(5)
                ->get();

            $contractStats = [
                'total' => (int) $contractStatusCounts->sum(),
                'active' => (int) ($contractStatusCounts['active'] ?? 0),
                'expiring_soon' => Contract::where('contract_status', 'active')
                    ->where('end_date', '<=', $next30Days)
                    ->where('end_date', '>=', $today)
                    ->count(),
                'new_this_month' => Contract::whereMonth('created_at', $currentMonth)
                    ->whereYear('created_at', $currentYear)
                    ->count(),
            ];

            $recentContracts = Contract::with(['customer'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $recentInvoices = Invoice::with(['customer'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $generalStats = [
                'total_customers' => (int) $customerActiveCounts->sum(),
                'active_customers' => (int) ($customerActiveCounts[1] ?? 0),
                'total_buildings' => Building::count(),
                'total_users' => User::where('is_active', true)->count(),
                'technicians_active' => User::where('is_active', true)
                    ->whereHas('roles', function ($q) {
                        $q->where('name', 'like', '%technician%');
                    })
                    ->count(),
            ];

            $jobStatusDistribution = [
                'labels' => ['In Progress', 'Scheduled', 'Completed', 'Suspended', 'Force Majeure'],
                'data' => [
                    (int) $jobStatusCounts->except(['completed', 'done_job', 'cancelled', 'scheduled', 'new_job', 'suspend', 'force_majeure'])->sum(),
                    (int) (($jobStatusCounts['scheduled'] ?? 0) + ($jobStatusCounts['new_job'] ?? 0)),
                    (int) (($jobStatusCounts['completed'] ?? 0) + ($jobStatusCounts['done_job'] ?? 0)),
                    (int) ($jobStatusCounts['suspend'] ?? 0),
                    (int) ($jobStatusCounts['force_majeure'] ?? 0),
                ],
                'colors' => [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(107, 114, 128, 0.8)',
                ],
            ];

            return [
                'jobStats' => $jobStats,
                'jobsInProgress' => $jobsInProgress,
                'todaysJobs' => $todaysJobs,
                'upcomingJobs' => $upcomingJobs,
                'jobsByDay' => $jobsByDay,
                'surveyStats' => $surveyStats,
                'upcomingSurveys' => $upcomingSurveys,
                'prospectStats' => $prospectStats,
                'recentProspects' => $recentProspects,
                'quotationStats' => $quotationStats,
                'recentQuotations' => $recentQuotations,
                'contractStats' => $contractStats,
                'recentContracts' => $recentContracts,
                'recentInvoices' => $recentInvoices,
                'generalStats' => $generalStats,
                'lowStockCount' => WarehouseProduct::whereRaw('quantity <= minimum_stock')->count(),
                'jobStatusDistribution' => $jobStatusDistribution,
                'recentActivities' => $this->getRecentActivities(),
                'pipelineStats' => $pipelineStats,
                'recentPipelines' => $recentPipelines,
                'upcomingFollowups' => $upcomingFollowups,
            ];
        });

        return view('dashboard.index', $dashboardData);
    }

    private function getRecentActivities()
    {
        $activities = [];

        // Recent jobs
        $recentJobs = JobSchedule::with(['building'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentJobs as $job) {
            $activities[] = [
                'type' => 'job',
                'icon' => 'fas fa-tools',
                'description' => "Job {$job->job_schedule_number} - " . ($job->building->name ?? 'N/A'),
                'status' => $job->status,
                'time_ago' => $job->updated_at->diffForHumans(),
                'color' => 'blue',
                'created_at' => $job->updated_at,
            ];
        }

        // Recent surveys
        $recentSurveys = Survey::with(['customer'])
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($recentSurveys as $survey) {
            $activities[] = [
                'type' => 'survey',
                'icon' => 'fas fa-clipboard-check',
                'description' => "Survey for " . ($survey->customer->customer_name ?? 'N/A'),
                'status' => $survey->status,
                'time_ago' => $survey->updated_at->diffForHumans(),
                'color' => 'green',
                'created_at' => $survey->updated_at,
            ];
        }

        // Recent contracts
        $recentContracts = Contract::with(['customer'])
            ->orderBy('updated_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($recentContracts as $contract) {
            $activities[] = [
                'type' => 'contract',
                'icon' => 'fas fa-file-contract',
                'description' => "Contract {$contract->contract_number} - " . ($contract->customer->customer_name ?? 'N/A'),
                'status' => $contract->status,
                'time_ago' => $contract->updated_at->diffForHumans(),
                'color' => 'purple',
                'created_at' => $contract->updated_at,
            ];
        }

        // Sort by time
        usort($activities, function($a, $b) {
            return $b['created_at'] <=> $a['created_at'];
        });

        return array_slice($activities, 0, 10);
    }
}

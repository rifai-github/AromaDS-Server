<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\JobSchedule;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statistics = [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('status', 'active')->count(),
            'inactive_customers' => Customer::where('status', 'inactive')->count(),
            'new_customers_this_month' => Customer::whereMonth('created_at', now()->month)->count(),
            'total_quotations' => Quotation::count(),
            'total_contracts' => Contract::count(),
            'total_invoices' => Invoice::count(),
            'total_payments' => Payment::sum('amount'),
            'total_buildings' => Building::count(),
        ];

        return view('reports.customer.index', compact('statistics'));
    }

    /**
     * Customer List Report.
     */
    public function customerListReport(Request $request)
    {
        $query = Customer::with(['quotations', 'contracts', 'invoices', 'payments', 'buildings']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer type
        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Filter by date range (registration date)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $customers = $query->orderBy('customer_name')->paginateStd(25);

        $statistics = [
            'total' => Customer::count(),
            'active' => Customer::where('status', 'active')->count(),
            'inactive' => Customer::where('status', 'inactive')->count(),
            'new_this_month' => Customer::whereMonth('created_at', now()->month)->count(),
            'new_this_year' => Customer::whereYear('created_at', now()->year)->count(),
            'with_contracts' => Customer::has('contracts')->count(),
            'with_quotations' => Customer::has('quotations')->count(),
        ];

        return view('reports.customer.list', compact('customers', 'statistics'));
    }

    /**
     * Customer Activity Report.
     */
    public function customerActivityReport(Request $request)
    {
        $query = Customer::with(['quotations', 'contracts', 'invoices', 'payments', 'jobSchedules']);

        // Filter by customer
        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        // Filter by activity type
        if ($request->filled('activity_type')) {
            switch ($request->activity_type) {
                case 'quotations':
                    $query->has('quotations');
                    break;
                case 'contracts':
                    $query->has('contracts');
                    break;
                case 'invoices':
                    $query->has('invoices');
                    break;
                case 'payments':
                    $query->has('payments');
                    break;
                case 'jobs':
                    $query->has('jobSchedules');
                    break;
            }
        }

        $customers = $query->orderBy('customer_name')->get();

        // Calculate customer activity metrics
        $customerActivities = $customers->map(function ($customer) {
            $lastQuotation = $customer->quotations->sortByDesc('quotation_date')->first();
            $lastContract = $customer->contracts->sortByDesc('contract_date')->first();
            $lastInvoice = $customer->invoices->sortByDesc('invoice_date')->first();
            $lastPayment = $customer->payments->sortByDesc('payment_date')->first();
            $lastJob = $customer->jobSchedules->sortByDesc('schedule_date')->first();

            return [
                'customer' => $customer,
                'quotations' => [
                    'total' => $customer->quotations->count(),
                    'approved' => $customer->quotations->where('status', 'approved')->count(),
                    'last_date' => $lastQuotation ? $lastQuotation->quotation_date : null,
                ],
                'contracts' => [
                    'total' => $customer->contracts->count(),
                    'active' => $customer->contracts->where('status', 'active')->count(),
                    'last_date' => $lastContract ? $lastContract->contract_date : null,
                ],
                'invoices' => [
                    'total' => $customer->invoices->count(),
                    'paid' => $customer->invoices->where('status', 'paid')->count(),
                    'last_date' => $lastInvoice ? $lastInvoice->invoice_date : null,
                ],
                'payments' => [
                    'total' => $customer->payments->sum('amount'),
                    'count' => $customer->payments->count(),
                    'last_date' => $lastPayment ? $lastPayment->payment_date : null,
                ],
                'jobs' => [
                    'total' => $customer->jobSchedules->count(),
                    'completed' => $customer->jobSchedules->where('status', 'completed')->count(),
                    'last_date' => $lastJob ? $lastJob->schedule_date : null,
                ],
            ];
        });

        $statistics = [
            'total_customers' => $customers->count(),
            'active_customers' => $customerActivities->where('contracts.active', '>', 0)->count(),
            'customers_with_quotations' => $customerActivities->where('quotations.total', '>', 0)->count(),
            'customers_with_contracts' => $customerActivities->where('contracts.total', '>', 0)->count(),
            'customers_with_payments' => $customerActivities->where('payments.count', '>', 0)->count(),
        ];

        return view('reports.customer.activity', compact('customerActivities', 'statistics'));
    }

    /**
     * Customer Financial Report.
     */
    public function customerFinancialReport(Request $request)
    {
        $query = Customer::with(['quotations', 'contracts', 'invoices', 'payments']);

        // Filter by customer
        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $customers = $query->orderBy('customer_name')->get();

        // Calculate customer financial metrics
        $customerFinancials = $customers->map(function ($customer) {
            $totalQuotations = $customer->quotations->sum('total_amount');
            $approvedQuotations = $customer->quotations->where('status', 'approved')->sum('total_amount');
            $totalContracts = $customer->contracts->sum('contract_value');
            $activeContracts = $customer->contracts->where('status', 'active')->sum('contract_value');
            $totalInvoices = $customer->invoices->sum('total_amount');
            $paidInvoices = $customer->invoices->where('status', 'paid')->sum('total_amount');
            $totalPayments = $customer->payments->sum('amount');

            return [
                'customer' => $customer,
                'quotations' => [
                    'total_amount' => $totalQuotations,
                    'approved_amount' => $approvedQuotations,
                    'approval_rate' => $totalQuotations > 0 ? round(($approvedQuotations / $totalQuotations) * 100, 2) : 0,
                ],
                'contracts' => [
                    'total_value' => $totalContracts,
                    'active_value' => $activeContracts,
                    'completion_rate' => $totalContracts > 0 ? round(($activeContracts / $totalContracts) * 100, 2) : 0,
                ],
                'invoices' => [
                    'total_amount' => $totalInvoices,
                    'paid_amount' => $paidInvoices,
                    'payment_rate' => $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 2) : 0,
                ],
                'payments' => [
                    'total_amount' => $totalPayments,
                ],
                'profitability' => [
                    'total_revenue' => $totalPayments,
                    'total_contracts' => $totalContracts,
                    'profit_margin' => $totalContracts > 0 ? round((($totalPayments - $totalContracts) / $totalContracts) * 100, 2) : 0,
                ],
            ];
        });

        $statistics = [
            'total_customers' => $customers->count(),
            'total_revenue' => $customerFinancials->sum('payments.total_amount'),
            'total_contracts' => $customerFinancials->sum('contracts.total_value'),
            'average_approval_rate' => $customerFinancials->avg('quotations.approval_rate'),
            'average_payment_rate' => $customerFinancials->avg('invoices.payment_rate'),
            'average_profit_margin' => $customerFinancials->avg('profitability.profit_margin'),
        ];

        return view('reports.customer.financial', compact('customerFinancials', 'statistics'));
    }

    /**
     * Customer Building Report.
     */
    public function customerBuildingReport(Request $request)
    {
        $query = Building::with(['customers', 'quotations', 'contracts', 'jobSchedules']);

        // Filter by customer
        if ($request->filled('customer_name')) {
            $query->whereHas('customers', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->customer_name}%");
            });
        }

        // Filter by building type
        if ($request->filled('building_type')) {
            $query->where('building_type', $request->building_type);
        }

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('building_name', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('nama_gedung')->paginateStd(25);

        // Calculate building statistics
        $buildingStats = $customers->getCollection()->map(function ($building) {
            $totalQuotations = $building->quotations->count();
            $totalContracts = $building->contracts->count();
            $totalJobs = $building->jobSchedules->count();
            $completedJobs = $building->jobSchedules->where('status', 'completed')->count();

            return [
                'building' => $building,
                'quotations' => [
                    'total' => $totalQuotations,
                ],
                'contracts' => [
                    'total' => $totalContracts,
                ],
                'jobs' => [
                    'total' => $totalJobs,
                    'completed' => $completedJobs,
                    'completion_rate' => $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 2) : 0,
                ],
            ];
        });

        $statistics = [
            'total_buildings' => Building::count(),
            'total_customers' => Building::has('customers')->distinct()->count('id'),
            'buildings_with_contracts' => Building::has('contracts')->count(),
            'buildings_with_jobs' => Building::has('jobSchedules')->count(),
            'average_jobs_per_building' => Building::withCount('jobSchedules')->get()->avg('job_schedules_count'),
        ];

        return view('reports.customer.building', compact('customers', 'buildingStats', 'statistics'));
    }

    /**
     * Customer Retention Report.
     */
    public function customerRetentionReport(Request $request)
    {
        $query = Customer::with(['quotations', 'contracts', 'invoices', 'payments']);

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $customers = $query->orderBy('created_at')->get();

        // Group customers by registration period
        $period = $request->get('period', 'month'); // day, week, month, year

        $customerRetention = $customers->groupBy(function ($customer) use ($period) {
            switch ($period) {
                case 'day':
                    return $customer->created_at->format('Y-m-d');
                case 'week':
                    return $customer->created_at->format('Y-W');
                case 'month':
                    return $customer->created_at->format('Y-m');
                case 'year':
                    return $customer->created_at->format('Y');
                default:
                    return $customer->created_at->format('Y-m');
            }
        })->map(function ($periodCustomers, $periodKey) {
            $totalCustomers = $periodCustomers->count();
            $customersWithContracts = $periodCustomers->filter(function ($customer) {
                return $customer->contracts->count() > 0;
            })->count();
            $customersWithPayments = $periodCustomers->filter(function ($customer) {
                return $customer->payments->count() > 0;
            })->count();

            return [
                'period' => $periodKey,
                'total_customers' => $totalCustomers,
                'customers_with_contracts' => $customersWithContracts,
                'customers_with_payments' => $customersWithPayments,
                'contract_rate' => $totalCustomers > 0 ? round(($customersWithContracts / $totalCustomers) * 100, 2) : 0,
                'payment_rate' => $totalCustomers > 0 ? round(($customersWithPayments / $totalCustomers) * 100, 2) : 0,
            ];
        });

        $statistics = [
            'total_customers' => $customers->count(),
            'customers_with_contracts' => $customers->filter(function ($customer) {
                return $customer->contracts->count() > 0;
            })->count(),
            'customers_with_payments' => $customers->filter(function ($customer) {
                return $customer->payments->count() > 0;
            })->count(),
            'average_contract_rate' => $customerRetention->avg('contract_rate'),
            'average_payment_rate' => $customerRetention->avg('payment_rate'),
        ];

        return view('reports.customer.retention', compact('customerRetention', 'statistics', 'period'));
    }

    /**
     * Customer Satisfaction Report.
     */
    public function customerSatisfactionReport(Request $request)
    {
        // This would typically involve customer feedback/satisfaction data
        // For now, we'll use job completion rates as a proxy for satisfaction
        
        $query = JobSchedule::with(['customer', 'team']);

        // Filter by customer
        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('schedule_date', [$request->start_date, $request->end_date]);
        }

        $jobs = $query->orderBy('schedule_date', 'desc')->get();

        // Calculate customer satisfaction metrics
        $customerSatisfaction = $jobs->groupBy('customer_name')->map(function ($customerJobs, $customerName) {
            $totalJobs = $customerJobs->count();
            $completedJobs = $customerJobs->where('status', 'completed')->count();
            $cancelledJobs = $customerJobs->where('status', 'cancelled')->count();
            $onTimeJobs = $customerJobs->where('status', 'completed')->filter(function ($job) {
                // Assuming there's a completion_date field to compare with schedule_date
                return $job->completion_date <= $job->schedule_date;
            })->count();

            return [
                'customer_name' => $customerName,
                'total_jobs' => $totalJobs,
                'completed_jobs' => $completedJobs,
                'cancelled_jobs' => $cancelledJobs,
                'on_time_jobs' => $onTimeJobs,
                'completion_rate' => $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 2) : 0,
                'on_time_rate' => $completedJobs > 0 ? round(($onTimeJobs / $completedJobs) * 100, 2) : 0,
                'satisfaction_score' => $totalJobs > 0 ? round((($completedJobs * 0.6) + ($onTimeJobs * 0.4)) / $totalJobs * 100, 2) : 0,
            ];
        });

        $statistics = [
            'total_customers' => $customerSatisfaction->count(),
            'average_completion_rate' => $customerSatisfaction->avg('completion_rate'),
            'average_on_time_rate' => $customerSatisfaction->avg('on_time_rate'),
            'average_satisfaction_score' => $customerSatisfaction->avg('satisfaction_score'),
            'highly_satisfied_customers' => $customerSatisfaction->where('satisfaction_score', '>=', 80)->count(),
        ];

        return view('reports.customer.satisfaction', compact('customerSatisfaction', 'statistics'));
    }

    /**
     * Export Customer List Report.
     */
    public function exportCustomerListReport(Request $request)
    {
        $query = Customer::with(['quotations', 'contracts', 'invoices', 'payments', 'buildings']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $customers = $query->orderBy('customer_name')->get();

        $fileName = 'customer_list_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return response()->json([
            'status' => 'success',
            'message' => 'Export generated successfully',
            'file_name' => $fileName,
            'data' => $customers,
        ]);
    }

    /**
     * Export Customer Financial Report.
     */
    public function exportCustomerFinancialReport(Request $request)
    {
        $query = Customer::with(['quotations', 'contracts', 'invoices', 'payments']);

        // Apply filters
        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $customers = $query->orderBy('customer_name')->get();

        $fileName = 'customer_financial_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return response()->json([
            'status' => 'success',
            'message' => 'Export generated successfully',
            'file_name' => $fileName,
            'data' => $customers,
        ]);
    }

    /**
     * Get customer statistics for API.
     */
    public function getCustomerStatistics()
    {
        $statistics = [
            'customers' => [
                'total' => Customer::count(),
                'active' => Customer::where('status', 'active')->count(),
                'inactive' => Customer::where('status', 'inactive')->count(),
                'new_this_month' => Customer::whereMonth('created_at', now()->month)->count(),
                'new_this_year' => Customer::whereYear('created_at', now()->year)->count(),
            ],
            'quotations' => [
                'total' => Quotation::count(),
                'approved' => Quotation::where('status', 'approved')->count(),
                'pending' => Quotation::where('status', 'pending')->count(),
                'rejected' => Quotation::where('status', 'rejected')->count(),
            ],
            'contracts' => [
                'total' => Contract::count(),
                'active' => Contract::where('status', 'active')->count(),
                'completed' => Contract::where('status', 'completed')->count(),
                'cancelled' => Contract::where('status', 'cancelled')->count(),
            ],
            'invoices' => [
                'total' => Invoice::count(),
                'paid' => Invoice::where('status', 'paid')->count(),
                'unpaid' => Invoice::where('status', 'unpaid')->count(),
                'overdue' => Invoice::where('status', 'overdue')->count(),
            ],
            'payments' => [
                'total' => Payment::count(),
                'total_amount' => Payment::sum('amount'),
                'today' => Payment::whereDate('payment_date', today())->sum('amount'),
                'this_month' => Payment::whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            ],
            'buildings' => [
                'total' => Building::count(),
                'with_contracts' => Building::has('contracts')->count(),
                'with_jobs' => Building::has('jobSchedules')->count(),
            ],
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }
}

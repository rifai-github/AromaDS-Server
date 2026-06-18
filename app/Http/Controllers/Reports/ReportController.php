<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Models\Customer;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Survey;
use App\Models\Prospect;
use App\Models\JobSchedule;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with(['createdBy']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('report_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginateStd(25);

        return view('reports.index', compact('reports'));
    }

    public function create()
    {
        $users = User::all();
        return view('reports.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_name' => 'required|string|max:255|unique:reports',
            'type' => 'required|in:summary,detailed,analytical,custom',
            'module' => 'required|in:marketing,operational,finance,warehouse,company,system',
            'description' => 'nullable|string',
            'parameters' => 'nullable|json',
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $report = Report::create([
                'report_name' => $request->report_name,
                'type' => $request->type,
                'module' => $request->module,
                'description' => $request->description,
                'parameters' => $request->parameters,
                'is_active' => $request->is_active,
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Report created successfully',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating report: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Report $report)
    {
        $report->load(['createdBy']);
        return view('reports.show', compact('report'));
    }

    public function edit(Report $report)
    {
        $users = User::all();
        return view('reports.edit', compact('report', 'users'));
    }

    public function update(Request $request, Report $report)
    {
        $validator = Validator::make($request->all(), [
            'report_name' => 'required|string|max:255|unique:reports,report_name,' . $report->id,
            'type' => 'required|in:summary,detailed,analytical,custom',
            'module' => 'required|in:marketing,operational,finance,warehouse,company,system',
            'description' => 'nullable|string',
            'parameters' => 'nullable|json',
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $report->update([
                'report_name' => $request->report_name,
                'type' => $request->type,
                'module' => $request->module,
                'description' => $request->description,
                'parameters' => $request->parameters,
                'is_active' => $request->is_active,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Report updated successfully',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating report: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Report $report)
    {
        try {
            $report->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Report deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting report: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dashboard()
    {
        return view('reports.dashboard');
    }

    // Marketing Reports
    public function marketingReport(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth();
        $dateTo = $request->date_to ?? now()->endOfMonth();

        $data = [
            'prospects' => [
                'total' => Prospect::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'by_status' => Prospect::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->get(),
                'by_staff' => Prospect::with('staff')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('staff_id, count(*) as count')
                    ->groupBy('staff_id')
                    ->get()
            ],
            'surveys' => [
                'total' => Survey::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
                'by_status' => Survey::whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->get(),
                'by_marketing' => Survey::with('marketingStaff')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('marketing_staff_id, count(*) as count')
                    ->groupBy('marketing_staff_id')
                    ->get()
            ]
        ];

        return view('reports.marketing', compact('data', 'dateFrom', 'dateTo'));
    }

    // Finance Reports
    public function financeReport(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth();
        $dateTo = $request->date_to ?? now()->endOfMonth();

        $data = [
            'invoices' => [
                'total' => Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])->count(),
                'total_amount' => Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])->sum('total_amount'),
                'by_status' => Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
                    ->selectRaw('status, count(*) as count, sum(total_amount) as amount')
                    ->groupBy('status')
                    ->get(),
                'monthly_trend' => Invoice::whereBetween('invoice_date', [$dateFrom, $dateTo])
                    ->selectRaw('MONTH(invoice_date) as month, count(*) as count, sum(total_amount) as amount')
                    ->groupBy('month')
                    ->get()
            ],
            'contracts' => [
                'total' => Contract::whereBetween('contract_date', [$dateFrom, $dateTo])->count(),
                'total_value' => Contract::whereBetween('contract_date', [$dateFrom, $dateTo])->sum('total_value'),
                'by_status' => Contract::whereBetween('contract_date', [$dateFrom, $dateTo])
                    ->selectRaw('status, count(*) as count, sum(total_value) as value')
                    ->groupBy('status')
                    ->get()
            ]
        ];

        return view('reports.finance', compact('data', 'dateFrom', 'dateTo'));
    }

    // Operational Reports
    public function operationalReport(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth();
        $dateTo = $request->date_to ?? now()->endOfMonth();

        $data = [
            'job_schedules' => [
                'total' => JobSchedule::whereBetween('schedule_date', [$dateFrom, $dateTo])->count(),
                'by_type' => JobSchedule::whereBetween('schedule_date', [$dateFrom, $dateTo])
                    ->selectRaw('type, count(*) as count')
                    ->groupBy('type')
                    ->get(),
                'by_status' => JobSchedule::whereBetween('schedule_date', [$dateFrom, $dateTo])
                    ->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->get(),
                'by_team' => JobSchedule::with('team')
                    ->whereBetween('schedule_date', [$dateFrom, $dateTo])
                    ->selectRaw('team_id, count(*) as count')
                    ->groupBy('team_id')
                    ->get()
            ]
        ];

        return view('reports.operational', compact('data', 'dateFrom', 'dateTo'));
    }

    // Warehouse Reports
    public function warehouseReport(Request $request)
    {
        $data = [
            'products' => [
                'total' => MasterProduct::count(),
                'active' => MasterProduct::where('status', 'active')->count(),
                'inactive' => MasterProduct::where('status', 'inactive')->count(),
                'by_type' => MasterProduct::with('productType')
                    ->selectRaw('product_type_id, count(*) as count')
                    ->groupBy('product_type_id')
                    ->get()
            ],
            'inventory' => [
                'total_stock' => DB::table('warehouse_products')->sum('quantity'),
                'low_stock' => DB::table('warehouse_products')->where('quantity', '<', 10)->count(),
                'out_of_stock' => DB::table('warehouse_products')->where('quantity', 0)->count(),
                'by_warehouse' => DB::table('warehouse_products')
                    ->join('warehouses', 'warehouse_products.warehouse_id', '=', 'warehouses.id')
                    ->selectRaw('warehouses.warehouse_name, sum(warehouse_products.quantity) as total_quantity')
                    ->groupBy('warehouses.id', 'warehouses.warehouse_name')
                    ->get()
            ]
        ];

        return view('reports.warehouse', compact('data'));
    }

    // Company Reports
    public function companyReport(Request $request)
    {
        $data = [
            'customers' => [
                'total' => Customer::count(),
                'active' => Customer::where('status', 'active')->count(),
                'inactive' => Customer::where('status', 'inactive')->count(),
                'pkp' => Customer::where('pkp_status', true)->count(),
                'non_pkp' => Customer::where('pkp_status', false)->count(),
                'by_type' => Customer::selectRaw('company_type, count(*) as count')
                    ->groupBy('company_type')
                    ->get()
            ],
            'users' => [
                'total' => User::count(),
                'active' => User::where('status', 'active')->count(),
                'inactive' => User::where('status', 'inactive')->count(),
                'by_department' => User::with('department')
                    ->selectRaw('department_id, count(*) as count')
                    ->groupBy('department_id')
                    ->get(),
                'by_branch' => User::with('branch')
                    ->selectRaw('branch_id, count(*) as count')
                    ->groupBy('branch_id')
                    ->get()
            ]
        ];

        return view('reports.company', compact('data'));
    }

    // Generate custom report
    public function generateReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:marketing,finance,operational,warehouse,company',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'format' => 'required|in:html,pdf,excel'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reportType = $request->report_type;
            $format = $request->format;
            
            switch ($reportType) {
                case 'marketing':
                    $data = $this->getMarketingReportData($request);
                    break;
                case 'finance':
                    $data = $this->getFinanceReportData($request);
                    break;
                case 'operational':
                    $data = $this->getOperationalReportData($request);
                    break;
                case 'warehouse':
                    $data = $this->getWarehouseReportData($request);
                    break;
                case 'company':
                    $data = $this->getCompanyReportData($request);
                    break;
                default:
                    throw new \Exception('Invalid report type');
            }

            if ($format === 'html') {
                return view("reports.generated.{$reportType}", compact('data'));
            } elseif ($format === 'pdf') {
                // PDF generation logic would go here
                return response()->json([
                    'status' => 'success',
                    'message' => 'PDF report generated',
                    'download_url' => route('reports.download', ['type' => $reportType, 'format' => 'pdf'])
                ]);
            } elseif ($format === 'excel') {
                // Excel generation logic would go here
                return response()->json([
                    'status' => 'success',
                    'message' => 'Excel report generated',
                    'download_url' => route('reports.download', ['type' => $reportType, 'format' => 'excel'])
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating report: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getMarketingReportData($request)
    {
        // Implementation for marketing report data
        return [];
    }

    private function getFinanceReportData($request)
    {
        // Implementation for finance report data
        return [];
    }

    private function getOperationalReportData($request)
    {
        // Implementation for operational report data
        return [];
    }

    private function getWarehouseReportData($request)
    {
        // Implementation for warehouse report data
        return [];
    }

    private function getCompanyReportData($request)
    {
        // Implementation for company report data
        return [];
    }
}

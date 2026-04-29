<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Finance\Payment;
use App\Models\Customer;
use App\Models\JobSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FinancialReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $statistics = $this->getCachedIndexStatistics();

        return view('reports.financial.index', compact('statistics'));
    }

    /**
     * Quotation Report.
     */
    public function quotationReport(Request $request)
    {
        $query = Quotation::with(['customer', 'building']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('quotation_date', [$request->start_date, $request->end_date]);
        }

        // Filter by amount range
        if ($request->filled('min_amount')) {
            $query->where('total_amount', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('total_amount', '<=', $request->max_amount);
        }

        $quotations = $query->orderBy('quotation_date', 'desc')->paginate(15);

        $statistics = $this->getCachedQuotationStatistics();

        return view('reports.financial.quotation', compact('quotations', 'statistics'));
    }

    /**
     * Contract Report.
     */
    public function contractReport(Request $request)
    {
        $query = Contract::with(['quotation', 'customer', 'building']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('contract_date', [$request->start_date, $request->end_date]);
        }

        // Filter by amount range
        if ($request->filled('min_amount')) {
            $query->where('contract_value', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('contract_value', '<=', $request->max_amount);
        }

        $contracts = $query->orderBy('contract_date', 'desc')->paginate(15);

        $statistics = $this->getCachedContractStatistics();

        return view('reports.financial.contract', compact('contracts', 'statistics'));
    }

    /**
     * Invoice Report.
     */
    public function invoiceReport(Request $request)
    {
        $query = Invoice::with(['contract', 'customer']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('invoice_date', [$request->start_date, $request->end_date]);
        }

        // Filter by amount range
        if ($request->filled('min_amount')) {
            $query->where('total_amount', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('total_amount', '<=', $request->max_amount);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->paginate(15);

        $statistics = $this->getCachedInvoiceStatistics();

        return view('reports.financial.invoice', compact('invoices', 'statistics'));
    }

    /**
     * Payment Report.
     */
    public function paymentReport(Request $request)
    {
        $query = Payment::with(['invoice', 'customer']);

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by customer
        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        // Filter by amount range
        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        $payments = $query->orderBy('payment_date', 'desc')->paginate(15);

        $statistics = $this->getCachedPaymentStatistics();

        return view('reports.financial.payment', compact('payments', 'statistics'));
    }

    /**
     * Revenue Report.
     */
    public function revenueReport(Request $request)
    {
        $query = Payment::with(['invoice', 'customer']);

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        // Group by period
        $period = $request->get('period', 'month'); // day, week, month, year

        $revenueData = $payments->groupBy(function ($payment) use ($period) {
            switch ($period) {
                case 'day':
                    return $payment->payment_date->format('Y-m-d');
                case 'week':
                    return $payment->payment_date->format('Y-W');
                case 'month':
                    return $payment->payment_date->format('Y-m');
                case 'year':
                    return $payment->payment_date->format('Y');
                default:
                    return $payment->payment_date->format('Y-m');
            }
        })->map(function ($periodPayments) {
            return [
                'count' => $periodPayments->count(),
                'amount' => $periodPayments->sum('amount'),
                'average' => $periodPayments->avg('amount'),
            ];
        });

        $statistics = [
            'total_revenue' => $payments->sum('amount'),
            'total_payments' => $payments->count(),
            'average_payment' => $payments->avg('amount'),
            'periods' => $revenueData->count(),
        ];

        return view('reports.financial.revenue', compact('revenueData', 'statistics', 'period'));
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

        $customers = $query->orderBy('customer_name')->get();

        // Calculate customer financial metrics
        $customerFinancials = $customers->map(function ($customer) {
            $totalQuotations = $customer->quotations->count();
            $approvedQuotations = $customer->quotations->where('status', 'approved')->count();
            $totalContracts = $customer->contracts->count();
            $activeContracts = $customer->contracts->where('status', 'active')->count();
            $totalInvoices = $customer->invoices->count();
            $paidInvoices = $customer->invoices->where('status', 'paid')->count();
            $totalPayments = $customer->payments->sum('amount');

            return [
                'customer' => $customer,
                'quotations' => [
                    'total' => $totalQuotations,
                    'approved' => $approvedQuotations,
                    'approval_rate' => $totalQuotations > 0 ? round(($approvedQuotations / $totalQuotations) * 100, 2) : 0,
                ],
                'contracts' => [
                    'total' => $totalContracts,
                    'active' => $activeContracts,
                ],
                'invoices' => [
                    'total' => $totalInvoices,
                    'paid' => $paidInvoices,
                    'payment_rate' => $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100, 2) : 0,
                ],
                'payments' => [
                    'total' => $totalPayments,
                ],
            ];
        });

        $statistics = [
            'total_customers' => $customers->count(),
            'active_customers' => $customerFinancials->where('contracts.active', '>', 0)->count(),
            'average_approval_rate' => $customerFinancials->avg('quotations.approval_rate'),
            'average_payment_rate' => $customerFinancials->avg('invoices.payment_rate'),
            'total_revenue' => $customerFinancials->sum('payments.total'),
        ];

        return view('reports.financial.customer', compact('customerFinancials', 'statistics'));
    }

    /**
     * Export Quotation Report.
     */
    public function exportQuotationReport(Request $request)
    {
        $query = Quotation::with(['customer', 'building']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('quotation_date', [$request->start_date, $request->end_date]);
        }

        $quotations = $query->orderBy('quotation_date', 'desc')->get();

        $fileName = 'quotation_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return response()->json([
            'status' => 'success',
            'message' => 'Export generated successfully',
            'file_name' => $fileName,
            'data' => $quotations,
        ]);
    }

    /**
     * Export Contract Report.
     */
    public function exportContractReport(Request $request)
    {
        $query = Contract::with(['quotation', 'customer', 'building']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('contract_date', [$request->start_date, $request->end_date]);
        }

        $contracts = $query->orderBy('contract_date', 'desc')->get();

        $fileName = 'contract_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return response()->json([
            'status' => 'success',
            'message' => 'Export generated successfully',
            'file_name' => $fileName,
            'data' => $contracts,
        ]);
    }

    /**
     * Export Invoice Report.
     */
    public function exportInvoiceReport(Request $request)
    {
        $query = Invoice::with(['contract', 'customer']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('invoice_date', [$request->start_date, $request->end_date]);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        $fileName = 'invoice_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return response()->json([
            'status' => 'success',
            'message' => 'Export generated successfully',
            'file_name' => $fileName,
            'data' => $invoices,
        ]);
    }

    /**
     * Export Payment Report.
     */
    public function exportPaymentReport(Request $request)
    {
        $query = Payment::with(['invoice', 'customer']);

        // Apply filters
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        $fileName = 'payment_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        return response()->json([
            'status' => 'success',
            'message' => 'Export generated successfully',
            'file_name' => $fileName,
            'data' => $payments,
        ]);
    }

    /**
     * Get financial statistics for API.
     */
    public function getFinancialStatistics()
    {
        $statistics = Cache::remember('reports:financial:api-statistics:v1', now()->addMinutes(2), function () {
            return [
                'quotations' => $this->getCachedQuotationStatistics(),
                'contracts' => $this->getCachedContractStatistics(),
                'invoices' => $this->getCachedInvoiceStatistics(),
                'payments' => $this->getCachedPaymentStatistics(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }

    private function getCachedIndexStatistics(): array
    {
        return Cache::remember('reports:financial:index-statistics:v1', now()->addMinutes(2), function () {
            $quotationStats = $this->getCachedQuotationStatistics();
            $contractStats = $this->getCachedContractStatistics();
            $invoiceStats = $this->getCachedInvoiceStatistics();
            $paymentStats = $this->getCachedPaymentStatistics();

            return [
                'total_quotations' => $quotationStats['total'],
                'approved_quotations' => $quotationStats['approved'],
                'pending_quotations' => $quotationStats['pending'],
                'rejected_quotations' => $quotationStats['rejected'],
                'total_contracts' => $contractStats['total'],
                'active_contracts' => $contractStats['active'],
                'completed_contracts' => $contractStats['completed'],
                'total_invoices' => $invoiceStats['total'],
                'paid_invoices' => $invoiceStats['paid'],
                'unpaid_invoices' => $invoiceStats['unpaid'],
                'total_payments' => $paymentStats['total'],
                'total_revenue' => $paymentStats['total_amount'],
            ];
        });
    }

    private function getCachedQuotationStatistics(): array
    {
        return Cache::remember('reports:financial:quotation-statistics:v1', now()->addMinutes(2), function () {
            $statusCounts = Quotation::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'total' => (int) $statusCounts->sum(),
                'approved' => (int) ($statusCounts['approved'] ?? 0),
                'pending' => (int) ($statusCounts['pending'] ?? 0),
                'rejected' => (int) ($statusCounts['rejected'] ?? 0),
                'total_amount' => (float) Quotation::sum('total_amount'),
                'approved_amount' => (float) Quotation::where('status', 'approved')->sum('total_amount'),
                'pending_amount' => (float) Quotation::where('status', 'pending')->sum('total_amount'),
                'average_amount' => (float) Quotation::avg('total_amount'),
            ];
        });
    }

    private function getCachedContractStatistics(): array
    {
        return Cache::remember('reports:financial:contract-statistics:v1', now()->addMinutes(2), function () {
            $statusCounts = Contract::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'total' => (int) $statusCounts->sum(),
                'active' => (int) ($statusCounts['active'] ?? 0),
                'completed' => (int) ($statusCounts['completed'] ?? 0),
                'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
                'total_value' => (float) Contract::sum('contract_value'),
                'active_value' => (float) Contract::where('status', 'active')->sum('contract_value'),
                'completed_value' => (float) Contract::where('status', 'completed')->sum('contract_value'),
                'average_value' => (float) Contract::avg('contract_value'),
            ];
        });
    }

    private function getCachedInvoiceStatistics(): array
    {
        return Cache::remember('reports:financial:invoice-statistics:v1', now()->addMinutes(2), function () {
            $statusCounts = Invoice::select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'total' => (int) $statusCounts->sum(),
                'paid' => (int) ($statusCounts['paid'] ?? 0),
                'unpaid' => (int) ($statusCounts['unpaid'] ?? 0),
                'overdue' => (int) ($statusCounts['overdue'] ?? 0),
                'total_amount' => (float) Invoice::sum('total_amount'),
                'paid_amount' => (float) Invoice::where('status', 'paid')->sum('total_amount'),
                'unpaid_amount' => (float) Invoice::where('status', 'unpaid')->sum('total_amount'),
                'overdue_amount' => (float) Invoice::where('status', 'overdue')->sum('total_amount'),
                'average_amount' => (float) Invoice::avg('total_amount'),
            ];
        });
    }

    private function getCachedPaymentStatistics(): array
    {
        return Cache::remember('reports:financial:payment-statistics:v1', now()->addMinutes(2), function () {
            return [
                'total' => Payment::count(),
                'total_amount' => (float) Payment::sum('amount'),
                'average_amount' => (float) Payment::avg('amount'),
                'today' => (float) Payment::whereDate('payment_date', today())->sum('amount'),
                'this_week' => (float) Payment::whereBetween('payment_date', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount'),
                'this_month' => (float) Payment::whereBetween('payment_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
                'this_year' => (float) Payment::whereBetween('payment_date', [now()->startOfYear(), now()->endOfYear()])->sum('amount'),
            ];
        });
    }
}

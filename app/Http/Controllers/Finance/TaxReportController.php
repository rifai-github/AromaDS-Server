<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\TaxReport;
use App\Models\TaxInvoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TaxReportController extends Controller
{
    /**
     * Display a listing of tax reports
     */
    public function index(Request $request)
    {
        $query = TaxReport::with(['createdBy']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('report_number', 'like', "%{$search}%")
                  ->orWhere('report_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Filter by report type
        if ($request->filled('report_type')) {
            $query->where('report_type', $request->report_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by period
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('period_start', [$request->start_date, $request->end_date]);
        }

        // Sort
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $taxReports = $query->paginateStd(25)->withQueryString();

        // Get filter options
        $reportTypes = ['monthly', 'quarterly', 'annual', 'custom'];
        $statuses = ['draft', 'generated', 'submitted', 'approved'];

        return view('tax.reports.index', compact('taxReports', 'reportTypes', 'statuses'));
    }

    /**
     * Show the form for creating a new tax report
     */
    public function create()
    {
        $reportTypes = ['monthly', 'quarterly', 'annual', 'custom'];
        
        return view('tax.reports.create', compact('reportTypes'));
    }

    /**
     * Store a newly created tax report
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:monthly,quarterly,annual,custom',
            'report_name' => 'required|string|max:255',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $taxReport = TaxReport::create([
                'report_number' => 'TR-' . time(),
                'report_type' => $request->report_type,
                'report_name' => $request->report_name,
                'period_start' => $request->period_start,
                'period_end' => $request->period_end,
                'status' => 'draft',
                'notes' => $request->notes,
                'created_by' => Auth::id()
            ]);

            DB::commit();

            return redirect()->route('tax-reports.index')
                ->with('success', 'Tax report created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error creating tax report: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified tax report
     */
    public function show(TaxReport $taxReport)
    {
        $taxReport->load(['createdBy', 'updatedBy']);
        
        return view('tax.reports.show', compact('taxReport'));
    }

    /**
     * Show the form for editing the specified tax report
     */
    public function edit(TaxReport $taxReport)
    {
        $reportTypes = ['monthly', 'quarterly', 'annual', 'custom'];
        
        return view('tax.reports.edit', compact('taxReport', 'reportTypes'));
    }

    /**
     * Update the specified tax report
     */
    public function update(Request $request, TaxReport $taxReport)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:monthly,quarterly,annual,custom',
            'report_name' => 'required|string|max:255',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $taxReport->update([
                'report_type' => $request->report_type,
                'report_name' => $request->report_name,
                'period_start' => $request->period_start,
                'period_end' => $request->period_end,
                'notes' => $request->notes,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return redirect()->route('tax-reports.index')
                ->with('success', 'Tax report updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error updating tax report: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified tax report
     */
    public function destroy(TaxReport $taxReport)
    {
        try {
            $taxReport->delete();
            return redirect()->route('tax-reports.index')
                ->with('success', 'Tax report deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting tax report: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete tax reports
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:tax_reports,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid selection'], 400);
        }

        try {
            $count = TaxReport::whereIn('id', $request->ids)->delete();
            return response()->json(['message' => "{$count} tax reports deleted successfully"]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting tax reports: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate tax report
     */
    public function generateReport(Request $request, TaxReport $taxReport)
    {
        try {
            DB::beginTransaction();

            // Generate report data
            $reportData = $taxReport->generateReportData();
            
            // Update report with generated data
            $taxReport->update([
                'report_data' => $reportData,
                'total_ppn' => $reportData['summary']['total_ppn'] ?? 0,
                'total_pph' => $reportData['summary']['total_pph'] ?? 0,
                'total_tax' => $reportData['summary']['total_tax'] ?? 0,
                'total_invoices' => $reportData['summary']['total_invoices'] ?? 0,
                'status' => 'generated',
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return redirect()->route('tax-reports.show', $taxReport)
                ->with('success', 'Tax report generated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error generating tax report: ' . $e->getMessage());
        }
    }

    /**
     * Export tax report to e-SPT format
     */
    public function exportESPT(TaxReport $taxReport)
    {
        try {
            // Generate e-SPT data
            $eSPTData = $taxReport->generateESPTData();
            
            // Update report with e-SPT data
            $taxReport->update([
                'e_spt_data' => $eSPTData,
                'e_spt_file_path' => 'reports/e-spt-' . $taxReport->report_number . '.xml',
                'e_spt_submitted_at' => now(),
                'e_spt_reference' => 'ESPT-' . time(),
                'updated_by' => Auth::id()
            ]);

            return redirect()->route('tax-reports.show', $taxReport)
                ->with('success', 'e-SPT export generated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error exporting to e-SPT: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\TaxFileExport;
use App\Models\TaxInvoice;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TaxFileExportController extends Controller
{
    use AccessControlFilterTrait;
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TaxFileExport::with(['createdBy', 'updatedBy']);

        // Apply access control filter (hierarchical data)
        // Default: Jika tidak set hirarki, hanya bisa lihat data sendiri
        $query = $this->applyAccessControlFilter($query, null, 'created_by');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('export_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('createdBy', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by file format
        if ($request->filled('file_format')) {
            $query->where('file_format', $request->file_format);
        }

        // Filter by export type
        if ($request->filled('export_type')) {
            $query->where('export_type', $request->export_type);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('export_date', [$request->start_date, $request->end_date]);
        }

        // Filter by period range
        if ($request->filled('period_from') && $request->filled('period_to')) {
            $query->whereBetween('period_from', [$request->period_from, $request->period_to])
                  ->orWhereBetween('period_to', [$request->period_from, $request->period_to]);
        }

        // Filter by created by
        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        $exports = $query->orderBy('created_at', 'desc')->paginate(15);

        $statistics = [
            'total' => TaxFileExport::count(),
            'pending' => TaxFileExport::where('status', 'pending')->count(),
            'processing' => TaxFileExport::where('status', 'processing')->count(),
            'completed' => TaxFileExport::where('status', 'completed')->count(),
            'failed' => TaxFileExport::where('status', 'failed')->count(),
        ];

        // Get users for filter dropdown
        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('finance.tax-file-exports.index', compact('exports', 'statistics', 'users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get available tax invoices for selection
        $taxInvoices = TaxInvoice::with('customer')
            ->orderBy('invoice_date', 'desc')
            ->get()
            ->map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date ?  $invoice->invoice_date->format('Y-m-d') : null,
                    'customer_name' => $invoice->customer ? $invoice->customer->customer_name : null,
                    'formatted_total_amount' => $invoice->formatted_total_amount ?? null,
                ];
            });
        
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'invoices' => $taxInvoices
            ]);
        }
        
        return view('finance.tax-file-exports.create', compact('taxInvoices'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'export_date' => 'required|date',
            'export_type' => 'required|in:monthly,quarterly,yearly,custom',
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'file_format' => 'required|in:csv,xlsx,pdf',
            'include_details' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'filter_parameters' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // Prepare filter parameters
            $filterParameters = [];
            $periodFrom = $request->period_from;
            $periodTo = $request->period_to;

            // Handle specific invoice selection
            if ($request->selection_mode === 'specific_invoices' && $request->filled('invoice_ids')) {
                $filterParameters['invoice_ids'] = $request->invoice_ids;
                
                // Auto-calculate period from selected invoices
                $invoices = TaxInvoice::whereIn('id', $request->invoice_ids)
                    ->select('invoice_date')
                    ->get();
                
                if ($invoices->isNotEmpty()) {
                    $periodFrom = $invoices->min('invoice_date');
                    $periodTo = $invoices->max('invoice_date');
                }
            }

            $export = TaxFileExport::create([
                'export_number' => TaxFileExport::generateExportNumber(),
                'export_date' => $request->export_date,
                'export_type' => $request->export_type,
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'file_format' => $request->file_format,
                'include_details' => $request->boolean('include_details'),
                'notes' => $request->notes,
                'filter_parameters' => $filterParameters,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tax file export created successfully.',
                    'data' => $export->load('createdBy')
                ]);
            }

            return redirect()->route('finance.tax-file-exports.index')
                ->with('success', 'Tax file export created successfully. Processing will start shortly.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create tax file export: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create tax file export: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TaxFileExport $taxFileExport)
    {
        $taxFileExport->load('createdBy');
        
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $taxFileExport
            ]);
        }
        
        $statistics = [
            'total_records' => $taxFileExport->total_records,
            'file_size' => $taxFileExport->formatted_file_size,
            'exported_at' => $taxFileExport->formatted_exported_at,
        ];

        return view('finance.tax-file-exports.show', compact('taxFileExport', 'statistics'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TaxFileExport $taxFileExport)
    {
        if ($taxFileExport->status !== 'pending') {
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot edit export that is not in pending status.'
                ], 400);
            }
            return back()->with('error', 'Cannot edit export that is not in pending status.');
        }

        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $taxFileExport
            ]);
        }

        $customers = Customer::orderBy('customer_name')->get();
        $taxInvoices = TaxInvoice::orderBy('created_at', 'desc')->get();

        return view('finance.tax-file-exports.edit', compact('taxFileExport', 'customers', 'taxInvoices'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TaxFileExport $taxFileExport)
    {
        if ($taxFileExport->status !== 'pending') {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update export that is not in pending status.'
                ], 400);
            }
            return back()->with('error', 'Cannot update export that is not in pending status.');
        }

        $request->validate([
            'export_date' => 'required|date',
            'export_type' => 'required|in:monthly,quarterly,yearly,custom',
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'file_format' => 'required|in:csv,xlsx,pdf',
            'include_details' => 'boolean',
            'notes' => 'nullable|string|max:1000',
            'filter_parameters' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $taxFileExport->update([
                'export_date' => $request->export_date,
                'export_type' => $request->export_type,
                'period_from' => $request->period_from,
                'period_to' => $request->period_to,
                'file_format' => $request->file_format,
                'include_details' => $request->boolean('include_details'),
                'notes' => $request->notes,
                'filter_parameters' => $request->filter_parameters,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tax file export updated successfully.',
                    'data' => $taxFileExport->load('createdBy')
                ]);
            }

            return redirect()->route('finance.tax-file-exports.show', $taxFileExport)
                ->with('success', 'Tax file export updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update tax file export: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update tax file export: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TaxFileExport $taxFileExport)
    {
        if (!$taxFileExport->canDelete()) {
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete export that is not in pending or failed status.'
                ], 400);
            }
            return back()->with('error', 'Cannot delete export that is not in pending or failed status.');
        }

        try {
            DB::beginTransaction();

            // Delete file if exists
            if ($taxFileExport->file_path && file_exists(public_path('uploads/' . $taxFileExport->file_path))) {
                unlink(public_path('uploads/' . $taxFileExport->file_path));
            }

            $taxFileExport->delete();

            DB::commit();

            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tax file export deleted successfully.'
                ]);
            }

            return redirect()->route('finance.tax-file-exports.index')
                ->with('success', 'Tax file export deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete tax file export: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to delete tax file export: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete multiple exports
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'export_ids' => 'required|array|min:1',
            'export_ids.*' => 'exists:tax_file_exports,id'
        ]);

        try {
            DB::beginTransaction();

            $exports = TaxFileExport::whereIn('id', $request->export_ids)
                ->whereIn('status', ['pending', 'failed'])
                ->get();

            if ($exports->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No exports found that can be deleted.'
                ], 400);
            }

            $deletedCount = 0;
            foreach ($exports as $export) {
                // Delete file if exists
                if ($export->file_path && file_exists(public_path('uploads/' . $export->file_path))) {
                    unlink(public_path('uploads/' . $export->file_path));
                }
                $export->delete();
                $deletedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} export(s).",
                'count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete exports: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download the exported file.
     */
    public function download(TaxFileExport $taxFileExport)
    {
        if (!$taxFileExport->canDownload()) {
            return back()->with('error', 'Export file is not available for download.');
        }

        $fileName = $taxFileExport->export_number . '.' . $taxFileExport->file_format;
        $filePath = public_path('uploads/' . $taxFileExport->file_path);

        return response()->download($filePath, $fileName);
    }

    /**
     * Generate e-SPT compatible CSV export
     */
    public function generateESPTExport(TaxFileExport $taxFileExport)
    {
        if ($taxFileExport->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Export is not in pending status.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Update status to processing
            $taxFileExport->update(['status' => 'processing']);

            // Generate e-SPT compatible CSV
            $csvData = $this->generateESPTData($taxFileExport);
            
            // Create file
            $fileName = $taxFileExport->export_number . '.csv';
            $filePath = 'exports/tax-files/' . $fileName;
            $fullPath = public_path('uploads/' . $filePath);
            
            // Create directory if not exists
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            // Write CSV file
            $file = fopen($fullPath, 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);

            // Update export record
            $taxFileExport->update([
                'status' => 'completed',
                'file_path' => $filePath,
                'file_size' => filesize($fullPath),
                'total_records' => count($csvData) - 1, // Exclude header
                'exported_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'e-SPT export generated successfully.',
                'data' => $taxFileExport->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            $taxFileExport->update(['status' => 'failed']);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate e-SPT export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate e-SPT compatible data
     */
    private function generateESPTData(TaxFileExport $taxFileExport)
    {
        // This is a placeholder for actual e-SPT data generation
        // In real implementation, you would query invoices and format according to e-SPT requirements
        
        $data = [];
        
        // Header row for e-SPT CSV
        $data[] = [
            'NPWP', 'Nama', 'Alamat', 'Tanggal Faktur', 'Nomor Faktur', 
            'DPP', 'PPN', 'Keterangan'
        ];

        // Sample data - replace with actual invoice data
        $data[] = [
            '123456789012345', 'PT Sample Customer', 'Jl. Sample No. 1', 
            '2025-01-01', 'INV-001', '1000000', '110000', 'Sample Invoice'
        ];

        return $data;
    }

    /**
     * Regenerate the export.
     */
    public function regenerate(TaxFileExport $taxFileExport)
    {
        if ($taxFileExport->status !== 'failed') {
            return back()->with('error', 'Can only regenerate failed exports.');
        }

        try {
            DB::beginTransaction();

            $taxFileExport->update([
                'status' => 'pending',
                'exported_at' => null,
            ]);

            // Trigger export processing (you can use jobs here)
            $this->processExport($taxFileExport);

            DB::commit();

            return back()->with('success', 'Export regeneration started.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to regenerate export: ' . $e->getMessage());
        }
    }

    /**
     * Get export statistics.
     */
    public function statistics()
    {
        $statistics = [
            'total_exports' => TaxFileExport::count(),
            'pending_exports' => TaxFileExport::where('status', 'pending')->count(),
            'processing_exports' => TaxFileExport::where('status', 'processing')->count(),
            'completed_exports' => TaxFileExport::where('status', 'completed')->count(),
            'failed_exports' => TaxFileExport::where('status', 'failed')->count(),
            'total_files_size' => TaxFileExport::where('status', 'completed')->sum(DB::raw('LENGTH(file_path)')),
            'exports_by_format' => TaxFileExport::select('format', DB::raw('count(*) as count'))
                ->groupBy('format')
                ->get(),
            'exports_by_month' => TaxFileExport::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('count(*) as count')
            )
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
        ];

        return response()->json($statistics);
    }

    /**
     * Process the export (placeholder for actual processing logic).
     */
    private function processExport(TaxFileExport $export)
    {
        // This is a placeholder for actual export processing
        // In a real application, you would:
        // 1. Mark as processing
        // 2. Generate the file based on filter parameters
        // 3. Save the file
        // 4. Mark as completed

        $export->update(['status' => 'processing']);

        // Simulate processing time
        sleep(2);

        // Generate file path
        $fileName = $export->export_number . '.' . $export->format;
        $filePath = 'exports/tax-files/' . $fileName;

        // Create dummy file (replace with actual export logic)
        $fullPath = public_path('uploads/' . $filePath);
        $dir = dirname($fullPath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($fullPath, 'Tax file export content for ' . $export->export_number);

        $export->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'exported_at' => now(),
        ]);
    }

    /**
     * Calculate export statistics.
     */
    private function calculateExportStatistics(TaxFileExport $export)
    {
        // This is a placeholder for actual statistics calculation
        // In a real application, you would calculate based on the exported data

        return [
            'total_records' => rand(100, 1000),
            'valid_records' => rand(90, 950),
            'invalid_records' => rand(0, 50),
            'total_amount' => rand(10000000, 100000000),
        ];
    }

    /**
     * Get exports by date range for API.
     */
    public function getExportsByDateRange(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $exports = TaxFileExport::whereBetween('export_date', [$request->start_date, $request->end_date])
            ->with('exportedBy')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $exports,
        ]);
    }

    /**
     * Get export statistics for API.
     */
    public function getExportStatistics()
    {
        $statistics = [
            'total' => TaxFileExport::count(),
            'pending' => TaxFileExport::where('status', 'pending')->count(),
            'processing' => TaxFileExport::where('status', 'processing')->count(),
            'completed' => TaxFileExport::where('status', 'completed')->count(),
            'failed' => TaxFileExport::where('status', 'failed')->count(),
            'recent_exports' => TaxFileExport::with('exportedBy')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statistics,
        ]);
    }
}

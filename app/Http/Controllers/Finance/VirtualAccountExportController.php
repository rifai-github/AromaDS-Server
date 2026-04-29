<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\VirtualAccountExport;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VirtualAccountExportController extends Controller
{
    /**
     * Display a listing of virtual account exports
     */
    public function index(Request $request): View
    {
        $query = VirtualAccountExport::with(['bank', 'createdBy', 'updatedBy']);

        // Apply search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Apply bank filter
        if ($request->filled('bank_id')) {
            $query->byBank($request->bank_id);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply file type filter
        if ($request->filled('file_type')) {
            $query->byFileType($request->file_type);
        }

        // Apply date range filter
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        }

        $exports = $query->orderBy('created_at', 'desc')->paginate(20);
        $banks = Bank::where('is_active', true)->orderBy('bank_name')->get();

        return view('finance.virtual-account-exports.index', compact('exports', 'banks'));
    }

    /**
     * Store a newly created virtual account export
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'export_number' => 'nullable|string|max:255|unique:virtual_account_exports,export_number',
            'export_date' => 'required|date',
            'bank_id' => 'required|exists:banks,id',
            'file_type' => 'required|in:csv,xlsx,txt',
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status_filter' => 'nullable|in:active,inactive,expired,all',
            'limit_records' => 'nullable|integer|min:1|max:10000',
            'include_header' => 'boolean',
            'delimiter' => 'nullable|in:,,;,|,\t',
            'include_columns' => 'required|array|min:1',
            'include_columns.*' => 'in:va_number,customer_name,amount,due_date,status,created_at,updated_at,notes',
            'auto_process' => 'boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            // Generate export number if not provided
            $exportNumber = $request->export_number ?: 'VAE-' . date('Ymd') . '-' . Str::random(6);

            // Generate file name
            $fileName = 'virtual_account_export_' . date('Ymd_His') . '.' . $request->file_type;

            $export = VirtualAccountExport::create([
                'export_number' => $exportNumber,
                'export_date' => $request->export_date,
                'bank_id' => $request->bank_id,
                'file_name' => $fileName,
                'file_type' => $request->file_type,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'status_filter' => $request->status_filter ?? 'all',
                'limit_records' => $request->limit_records,
                'include_header' => $request->boolean('include_header'),
                'delimiter' => $request->delimiter ?? ',',
                'include_columns' => $request->include_columns,
                'status' => 'pending',
                'auto_process' => $request->boolean('auto_process'),
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Virtual account export created successfully.',
                    'data' => $export->load(['bank', 'createdBy'])
                ]);
            }

            return redirect()->route('virtual-account-exports.index')
                ->with('success', 'Virtual account export created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create virtual account export: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create virtual account export: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified virtual account export
     */
    public function show(Request $request, VirtualAccountExport $virtualAccountExport)
    {
        $export = $virtualAccountExport->load(['bank', 'createdBy', 'updatedBy']);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $export
            ]);
        }

        return view('finance.virtual-account-exports.show', compact('export'));
    }

    /**
     * Show the form for editing the specified virtual account export
     */
    public function edit(Request $request, VirtualAccountExport $virtualAccountExport)
    {
        if (!$virtualAccountExport->canBeDeleted()) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This export cannot be edited in its current status.'
                ], 422);
            }
            return redirect()
                ->route('virtual-account-exports.show', $virtualAccountExport)
                ->with('error', 'This export cannot be edited in its current status.');
        }

        $export = $virtualAccountExport->load(['bank', 'createdBy', 'updatedBy']);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $export
            ]);
        }

        $banks = Bank::where('is_active', true)->orderBy('bank_name')->get();
        return view('finance.virtual-account-exports.edit', compact('export', 'banks'));
    }

    /**
     * Update the specified virtual account export
     */
    public function update(Request $request, VirtualAccountExport $virtualAccountExport)
    {
        if (!$virtualAccountExport->canBeDeleted()) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This export cannot be edited in its current status.'
                ], 422);
            }
            return redirect()
                ->route('virtual-account-exports.show', $virtualAccountExport)
                ->with('error', 'This export cannot be edited in its current status.');
        }

        $validator = Validator::make($request->all(), [
            'export_number' => 'nullable|string|max:255|unique:virtual_account_exports,export_number,' . $virtualAccountExport->id,
            'export_date' => 'required|date',
            'bank_id' => 'required|exists:banks,id',
            'file_type' => 'required|in:csv,xlsx,txt',
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'status_filter' => 'nullable|in:active,inactive,expired,all',
            'limit_records' => 'nullable|integer|min:1|max:10000',
            'include_header' => 'boolean',
            'delimiter' => 'nullable|in:,,;,|,\t',
            'include_columns' => 'required|array|min:1',
            'include_columns.*' => 'in:va_number,customer_name,amount,due_date,status,created_at,updated_at,notes',
            'auto_process' => 'boolean',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $virtualAccountExport->update([
                'export_number' => $request->export_number,
                'export_date' => $request->export_date,
                'bank_id' => $request->bank_id,
                'file_type' => $request->file_type,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'status_filter' => $request->status_filter ?? 'all',
                'limit_records' => $request->limit_records,
                'include_header' => $request->boolean('include_header'),
                'delimiter' => $request->delimiter ?? ',',
                'include_columns' => $request->include_columns,
                'auto_process' => $request->boolean('auto_process'),
                'notes' => $request->notes,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Virtual account export updated successfully.',
                    'data' => $virtualAccountExport->load(['bank', 'createdBy', 'updatedBy'])
                ]);
            }

            return redirect()->route('virtual-account-exports.index')
                ->with('success', 'Virtual account export updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update virtual account export: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update virtual account export: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified virtual account export
     */
    public function destroy(Request $request, VirtualAccountExport $virtualAccountExport)
    {
        if (!$virtualAccountExport->canBeDeleted()) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This export cannot be deleted in its current status.'
                ], 422);
            }
            return redirect()
                ->route('virtual-account-exports.show', $virtualAccountExport)
                ->with('error', 'This export cannot be deleted in its current status.');
        }

        try {
            DB::beginTransaction();

            // Delete associated file if exists
            if ($virtualAccountExport->file_path && file_exists(storage_path('app/' . $virtualAccountExport->file_path))) {
                unlink(storage_path('app/' . $virtualAccountExport->file_path));
            }

            $virtualAccountExport->delete();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Virtual account export deleted successfully.'
                ]);
            }

            return redirect()->route('virtual-account-exports.index')
                ->with('success', 'Virtual account export deleted successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete virtual account export: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to delete virtual account export: ' . $e->getMessage());
        }
    }

    /**
     * Process the specified virtual account export
     */
    public function process(Request $request, VirtualAccountExport $virtualAccountExport)
    {
        if (!$virtualAccountExport->canBeProcessed()) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This export cannot be processed in its current status.'
                ], 422);
            }
            return redirect()
                ->route('virtual-account-exports.show', $virtualAccountExport)
                ->with('error', 'This export cannot be processed in its current status.');
        }

        try {
            DB::beginTransaction();

            $virtualAccountExport->update([
                'status' => 'processing',
                'updated_by' => Auth::id(),
            ]);

            // TODO: Implement actual export processing logic here
            // This would typically involve:
            // 1. Querying virtual account data based on filters
            // 2. Generating the file in the specified format
            // 3. Updating the export record with file path and size
            // 4. Setting status to 'completed' or 'failed'

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Virtual account export processing started.',
                    'data' => $virtualAccountExport->fresh()
                ]);
            }

            return redirect()
                ->route('virtual-account-exports.show', $virtualAccountExport)
                ->with('success', 'Virtual account export processing started.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to process virtual account export: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to process virtual account export: ' . $e->getMessage());
        }
    }

    /**
     * Retry the specified virtual account export
     */
    public function retry(Request $request, VirtualAccountExport $virtualAccountExport)
    {
        if (!$virtualAccountExport->canBeProcessed()) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This export cannot be retried in its current status.'
                ], 422);
            }
            return redirect()
                ->route('virtual-account-exports.show', $virtualAccountExport)
                ->with('error', 'This export cannot be retried in its current status.');
        }

        try {
            DB::beginTransaction();

            $virtualAccountExport->update([
                'status' => 'pending',
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Virtual account export retry started.',
                    'data' => $virtualAccountExport->fresh()
                ]);
            }

            return redirect()
                ->route('virtual-account-exports.show', $virtualAccountExport)
                ->with('success', 'Virtual account export retry started.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to retry virtual account export: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to retry virtual account export: ' . $e->getMessage());
        }
    }

    /**
     * Bulk process multiple virtual account exports
     */
    public function bulkProcess(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:virtual_account_exports,id'
        ]);

        try {
            DB::beginTransaction();

            $exports = VirtualAccountExport::whereIn('id', $request->ids)
                ->whereIn('status', ['pending', 'failed'])
                ->get();

            $processedCount = 0;
            foreach ($exports as $export) {
                if ($export->canBeProcessed()) {
                    $export->update([
                        'status' => 'processing',
                        'updated_by' => Auth::id(),
                    ]);
                    $processedCount++;
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Successfully processed {$processedCount} exports."
                ]);
            }

            return redirect()
                ->route('virtual-account-exports.index')
                ->with('success', "Successfully processed {$processedCount} exports.");
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to process exports: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to process exports: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete multiple virtual account exports
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:virtual_account_exports,id'
        ]);

        try {
            DB::beginTransaction();

            $exports = VirtualAccountExport::whereIn('id', $request->ids)
                ->where('status', '!=', 'processing')
                ->get();

            $deletedCount = 0;
            foreach ($exports as $export) {
                if ($export->canBeDeleted()) {
                    // Delete associated file if exists
                    if ($export->file_path && file_exists(storage_path('app/' . $export->file_path))) {
                        unlink(storage_path('app/' . $export->file_path));
                    }
                    $export->delete();
                    $deletedCount++;
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Successfully deleted {$deletedCount} exports."
                ]);
            }

            return redirect()
                ->route('virtual-account-exports.index')
                ->with('success', "Successfully deleted {$deletedCount} exports.");
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete exports: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to delete exports: ' . $e->getMessage());
        }
    }

    /**
     * Export virtual account exports to CSV
     */
    public function export(Request $request)
    {
        $query = VirtualAccountExport::with(['bank', 'createdBy']);

        // Apply filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('bank_id')) {
            $query->byBank($request->bank_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('file_type')) {
            $query->byFileType($request->file_type);
        }

        $exports = $query->orderBy('created_at', 'desc')->get();

        $filename = 'virtual_account_exports_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($exports) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Export Number',
                'Export Date',
                'Bank',
                'File Name',
                'File Type',
                'Status',
                'Total Records',
                'Date From',
                'Date To',
                'Status Filter',
                'Created By',
                'Created At'
            ]);

            // CSV Data
            foreach ($exports as $export) {
                fputcsv($file, [
                    $export->export_number,
                    $export->formatted_export_date,
                    $export->bank ? $export->bank->name : '-',
                    $export->file_name,
                    $export->file_type_label,
                    $export->status_label,
                    $export->total_records,
                    $export->formatted_date_from,
                    $export->formatted_date_to,
                    $export->status_filter_label,
                    $export->createdBy ? $export->createdBy->name : '-',
                    $export->formatted_created_at
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

    /**
     * Get virtual account export statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = [
                'total_exports' => VirtualAccountExport::count(),
                'pending_exports' => VirtualAccountExport::pending()->count(),
                'processing_exports' => VirtualAccountExport::processing()->count(),
                'completed_exports' => VirtualAccountExport::completed()->count(),
                'failed_exports' => VirtualAccountExport::failed()->count(),
                'total_records_exported' => VirtualAccountExport::sum('total_records'),
                'exports_by_type' => VirtualAccountExport::selectRaw('file_type, COUNT(*) as count')
                    ->groupBy('file_type')
                    ->get(),
                'exports_by_status' => VirtualAccountExport::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get(),
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download the exported file
     */
    public function download(VirtualAccountExport $virtualAccountExport)
    {
        if (!$virtualAccountExport->canBeDownloaded()) {
            abort(404, 'File not found or export not completed.');
        }

        $filePath = storage_path('app/' . $virtualAccountExport->file_path);
        
        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath, $virtualAccountExport->file_name);
    }

    /**
     * Get export trends
     */
    public function trends(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        
        try {
            $trends = VirtualAccountExport::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $trends
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get trends: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary by status
     */
    public function summaryByStatus(): JsonResponse
    {
        try {
            $summary = VirtualAccountExport::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary by bank
     */
    public function summaryByBank(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        try {
            $summary = VirtualAccountExport::with('bank')
                ->selectRaw('bank_id, COUNT(*) as count')
                ->groupBy('bank_id')
                ->orderBy('count', 'desc')
                ->limit($limit)
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get summary: ' . $e->getMessage()
            ], 500);
        }
    }
}

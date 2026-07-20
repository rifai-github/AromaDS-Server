<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\Finance\TaxFileImportDetail;
use App\Models\TaxFileImport;
use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TaxFileImportController extends Controller
{
    use AccessControlFilterTrait;
    
    public function index(Request $request)
    {
        $query = TaxFileImport::with(['bank', 'createdBy', 'updatedBy']);

        // Apply access control filter (hierarchical data)
        // Default: Jika tidak set hirarki, hanya bisa lihat data sendiri
        $query = $this->applyAccessControlFilter($query, null, 'created_by');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('import_number', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('createdBy', function ($userQuery) use ($search) {
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

        // Filter by bank
        if ($request->filled('bank_id')) {
            $query->where('bank_id', $request->bank_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('import_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('import_date', '<=', $request->date_to);
        }

        $imports = $query->orderBy('created_at', 'desc')->paginateStd(25);

        // Get banks for filter dropdown
        $banks = Bank::where('is_active', true)->orderBy('bank_name')->get();

        return view('finance.tax-file-imports.index', compact('imports', 'banks'));
    }

    public function create()
    {
        return view('finance.tax-file-imports.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'extensions:csv,xlsx,xls',
                'mimetypes:text/plain,text/csv,application/csv,text/comma-separated-values,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/x-ole-storage,application/octet-stream',
                'max:10240', // 10MB max
            ],
            'auto_process' => 'boolean',
            'skip_header' => 'boolean',
            'delimiter' => ['required', Rule::in(TaxFileImport::DELIMITERS)],
            'notes' => 'nullable|string|max:1000',
        ], [
            'file.extensions' => 'The file field must be a file of type: csv, xlsx, xls.',
            'file.mimetypes' => 'The file field must be a valid CSV, XLSX, or XLS file.',
        ]);

        $uploadedFilePath = null;
        $transactionCommitted = false;

        try {
            DB::beginTransaction();

            // Handle file upload
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $fileFormat = $file->getClientOriginalExtension();
            $uploadPath = 'tax-file-imports';
            $filePath = $uploadPath . '/' . $fileName;
            
            // Ensure directory exists
            $fullPath = public_path('uploads/' . $uploadPath);
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
            
            // Move file to public/uploads directory
            $uploadedFilePath = $fullPath.DIRECTORY_SEPARATOR.$fileName;
            $file->move($fullPath, $fileName);

            $import = TaxFileImport::create([
                'import_number' => TaxFileImport::generateImportNumber(),
                'file_name' => $fileName,
                'import_date' => now()->toDateString(),
                'bank_id' => null,
                'file_format' => $fileFormat,
                'total_records' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'success_rate' => 0,
                'auto_process' => $request->boolean('auto_process'),
                'skip_header' => $request->boolean('skip_header'),
                'delimiter' => $request->delimiter,
                'notes' => $request->notes,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            // Process the file if auto_process is enabled
            if ($import->auto_process) {
                $this->processImportFile($import, $filePath);
            }

            DB::commit();
            $transactionCommitted = true;

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tax file import created successfully.',
                    'data' => $import->load('bank', 'createdBy')
                ]);
            }

            return redirect()->route('tax-file-imports.index')
                ->with('success', 'Tax file import created successfully.');
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if (! $transactionCommitted && $uploadedFilePath && file_exists($uploadedFilePath)) {
                unlink($uploadedFilePath);
            }
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create import: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to create import: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $import = TaxFileImport::with(['bank', 'createdBy', 'updatedBy', 'details'])->findOrFail($id);
        
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $import
            ]);
        }
        
        return view('finance.tax-file-imports.show', compact('import'));
    }

    public function edit($id)
    {
        $import = TaxFileImport::with(['bank'])->findOrFail($id);
        $banks = Bank::where('is_active', true)->orderBy('bank_name')->get();
        
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $import,
                'banks' => $banks
            ]);
        }
        
        return view('finance.tax-file-imports.edit', compact('import', 'banks'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,failed',
            'auto_process' => 'boolean',
            'skip_header' => 'boolean',
            'delimiter' => ['required', Rule::in(TaxFileImport::DELIMITERS)],
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $import = TaxFileImport::findOrFail($id);
            $import->update([
                'status' => $request->status,
                'auto_process' => $request->boolean('auto_process'),
                'skip_header' => $request->boolean('skip_header'),
                'delimiter' => $request->delimiter,
                'notes' => $request->notes,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tax file import updated successfully.',
                    'data' => $import->load('bank', 'updatedBy')
                ]);
            }

            return redirect()->route('tax-file-imports.index')
                ->with('success', 'Tax file import updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update import: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Failed to update import: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $import = TaxFileImport::findOrFail($id);
            
            if (!$import->canDelete()) {
                if (request()->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot delete import with status: ' . $import->status
                    ], 400);
                }
                return back()->with('error', 'Cannot delete import with status: ' . $import->status);
            }
            
            // Delete associated file
            if ($import->file_name) {
                $filePath = public_path('uploads/tax-file-imports/' . $import->file_name);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $import->delete();

            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tax file import deleted successfully.'
                ]);
            }

            return redirect()->route('tax-file-imports.index')
                ->with('success', 'Tax file import deleted successfully.');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete import: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Failed to delete import: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'import_ids' => 'required|array|min:1',
            'import_ids.*' => 'exists:tax_file_imports,id'
        ]);

        try {
            DB::beginTransaction();

            $imports = TaxFileImport::whereIn('id', $request->import_ids)->get();
            $deletedCount = 0;

            foreach ($imports as $import) {
                if ($import->canDelete()) {
                    // Delete associated file
                    if ($import->file_name) {
                        $filePath = public_path('uploads/tax-file-imports/' . $import->file_name);
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    
                    $import->delete();
                    $deletedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} imports.",
                'count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete imports: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadFile($id)
    {
        $import = TaxFileImport::findOrFail($id);
        
        if (!$import->canDownload()) {
            return back()->with('error', 'File cannot be downloaded.');
        }
        
        $filePath = public_path('uploads/tax-file-imports/' . $import->file_name);
        
        if (!file_exists($filePath)) {
            return back()->with('error', 'File not found.');
        }

        return response()->download($filePath, $import->file_name);
    }

    public function downloadErrorLog($id)
    {
        $import = TaxFileImport::findOrFail($id);
        
        if (!$import->error_log) {
            return back()->with('error', 'No error log available.');
        }

        $fileName = 'error_log_' . $import->import_number . '.txt';
        
        return response($import->error_log)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    public function processImport($id)
    {
        try {
            DB::beginTransaction();

            $import = TaxFileImport::findOrFail($id);

            if (!$import->canProcess()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Import cannot be processed in current status: ' . $import->status
                ], 400);
            }

            // Update status to processing
            $import->update(['status' => 'processing']);

            // Process the import file
            $this->processImportFile($import, 'tax-file-imports/' . $import->file_name);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Import processing started successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            // Update status to failed on error
            $import = TaxFileImport::findOrFail($id);
            $import->update([
                'status' => 'failed',
                'error_log' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process import: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processImportFile($import, $filePath)
    {
        try {
            $fullPath = public_path('uploads/' . $filePath);
            
            if (!file_exists($fullPath)) {
                throw new \Exception('File not found: ' . $filePath);
            }

            $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
            $data = [];

            if (in_array($extension, ['xlsx', 'xls'])) {
                $data = \Maatwebsite\Excel\Facades\Excel::toArray([], $fullPath)[0];
            } else {
                // CSV manual parsing based on delimiter
                $handle = fopen($fullPath, 'r');
                $delimiter = $import->delimiter ?: ',';
                if ($delimiter === TaxFileImport::DELIMITER_TAB) {
                    $delimiter = "\t";
                }
                
                while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
                    $data[] = $row;
                }
                fclose($handle);
            }

            if (empty($data)) {
                throw new \Exception('File is empty or could not be read.');
            }

            // Skip header if requested
            if ($import->skip_header && count($data) > 0) {
                array_shift($data);
            }

            $totalRecords = count($data);
            $successCount = 0;
            $failedCount = 0;

            foreach ($data as $row) {
                $foundInvoice = null;
                $isApprovedInFile = false;
                $statusFound = '';

                // Keywords for approval status
                $approvalKeywords = ['approved', 'success', 'sukses', 'disetujui', 'diterima'];

                foreach ($row as $cell) {
                    if (empty($cell)) continue;
                    
                    $cellValue = trim($cell);
                    $cellLower = strtolower($cellValue);

                    // 1. Check for Invoice MATCH if not already found
                    if (!$foundInvoice) {
                        $foundInvoice = \App\Models\Invoice::where('invoice_number', $cellValue)
                            ->orWhere('faktur_pajak', $cellValue)
                            ->orWhere('tax_number', $cellValue)
                            ->first();
                        
                    }

                    // 2. Check for APPROVAL keyword
                    if (!$isApprovedInFile) {
                        foreach ($approvalKeywords as $keyword) {
                            if (str_contains($cellLower, $keyword)) {
                                $isApprovedInFile = true;
                                $statusFound = $cellValue;
                                break;
                            }
                        }
                    }
                }

                if ($foundInvoice) {
                    $status = $isApprovedInFile ? 'approved' : 'warning';
                    $notes = 'Matched with Invoice: ' . $foundInvoice->invoice_number;
                    
                    if ($isApprovedInFile) {
                        $notes .= ' | CoreTax Status: ' . $statusFound;
                        
                        // Auto-approve the TaxInvoice if it exists
                        if ($foundInvoice->taxInvoice) {
                            $foundInvoice->taxInvoice->approve();
                            $notes .= ' (System Auto-Approved)';
                        }
                    }

                    TaxFileImportDetail::create([
                        'tax_file_import_id' => $import->id,
                        'invoice_number' => $foundInvoice->invoice_number,
                        'tax_number' => $foundInvoice->tax_number
                            ?? $foundInvoice->faktur_pajak
                            ?? $foundInvoice->npwp_number
                            ?? 'N/A',
                        'tax_date' => $foundInvoice->invoice_date ?? $import->import_date ?? now(),
                        'tax_amount' => $foundInvoice->tax_amount ?? 0,
                        'status' => $status,
                        'remarks' => $notes,
                        'created_by' => Auth::id(),
                    ]);
                    $successCount++;
                } else {
                    // Log fail row
                    TaxFileImportDetail::create([
                        'tax_file_import_id' => $import->id,
                        'invoice_number' => 'N/A',
                        'tax_number' => 'N/A',
                        'tax_date' => $import->import_date ?? now(),
                        'tax_amount' => 0,
                        'status' => 'rejected',
                        'remarks' => 'No matching invoice found for row. Raw data: ' . implode('|', $row)
                            . ($isApprovedInFile ? ' [File says: ' . $statusFound . ']' : ''),
                        'created_by' => Auth::id(),
                    ]);
                    $failedCount++;
                }
            }

            $successRate = $totalRecords > 0 ? round(($successCount / $totalRecords) * 100, 2) : 0;

            $import->update([
                'total_records' => $totalRecords,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'success_rate' => $successRate,
                'status' => 'completed',
                'processed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
                'processed_at' => now(),
            ]);
            throw $e;
        }
    }

    // API Methods
    public function getImportStatistics()
    {
        $stats = [
            'total_imports' => TaxFileImport::count(),
            'total_records' => TaxFileImport::sum('total_records'),
            'success_count' => TaxFileImport::sum('success_count'),
            'failed_count' => TaxFileImport::sum('failed_count'),
            'pending_imports' => TaxFileImport::pending()->count(),
            'processing_imports' => TaxFileImport::processing()->count(),
            'completed_imports' => TaxFileImport::completed()->count(),
            'failed_imports' => TaxFileImport::failed()->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    public function getImportsByDateRange(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $imports = TaxFileImport::with(['bank', 'createdBy'])
            ->whereBetween('import_date', [$request->start_date, $request->end_date])
            ->orderBy('import_date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $imports
        ]);
    }
}

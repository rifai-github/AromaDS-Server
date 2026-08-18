<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\Bank;
use App\Models\TaxFileImport;
use App\Services\Finance\CoreTaxFakturPdfImportService;
use App\Services\Finance\CoreTaxImportService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    /**
     * Accepts one-or-more PDF "Output Tax Invoice" files — the individually
     * downloaded faktur pajak DJP issues per invoice. There is no Auto
     * Process choice: every import is processed immediately, in the same
     * request, so the button really does what it says.
     *
     * The older batched CSV/XLSX result-file flow is no longer offered here —
     * PDF supersedes it, since a matched PDF is both the data AND the
     * document, archived straight onto the invoice. CoreTaxImportService and
     * processImportFile() still exist and are unaffected: existing CSV/XLSX
     * import records already in the list keep working (view, download,
     * retry via the Process button).
     */
    public function store(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1|max:200',
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'notes' => 'nullable|string|max:1000',
        ], [
            'files.*.mimes' => 'Semua file harus berupa PDF Faktur Pajak.',
        ]);

        /** @var UploadedFile[] $files */
        $files = $request->file('files');

        $importNumber = TaxFileImport::generateImportNumber();
        $folder = 'tax-file-imports/'.$importNumber;
        $fullFolder = public_path('uploads/'.$folder);

        if (! is_dir($fullFolder)) {
            mkdir($fullFolder, 0755, true);
        }

        $stored = [];
        foreach ($files as $file) {
            /** @var UploadedFile $file */
            $originalName = $file->getClientOriginalName();
            $storedName = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $file->move($fullFolder, $storedName);

            $stored[] = [
                'original_name' => $originalName,
                'full_path' => $fullFolder.DIRECTORY_SEPARATOR.$storedName,
            ];
        }

        try {
            $import = TaxFileImport::create([
                'import_number' => $importNumber,
                // `file_name` is NOT NULL in the DB. The zip's name is
                // predictable up front (see archivePdfImportFolder()), so
                // there is no need to leave this blank until processing
                // finishes — that would violate the column's constraint.
                'file_name' => $importNumber.'.zip',
                'import_date' => now()->toDateString(),
                'bank_id' => null,
                'file_format' => 'pdf',
                'total_records' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'success_rate' => 0,
                'auto_process' => true,
                'delimiter' => TaxFileImport::DELIMITER_COMMA,
                'notes' => $request->notes,
                'status' => 'processing',
                'created_by' => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            $this->deleteDirectory($fullFolder);

            return $this->storeFailure($request, 'Failed to create import: '.$e->getMessage());
        }

        try {
            app(CoreTaxFakturPdfImportService::class)->process($import, $stored);
            $this->archivePdfImportFolder($import, $fullFolder);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
                'processed_at' => now(),
            ]);

            return $this->storeFailure($request, 'Failed to process import: '.$e->getMessage());
        }

        return $this->storeSuccess($request, $import);
    }

    /**
     * Zips the uploaded PDFs into a single archive so the existing "Download"
     * button on the list — built for one file per import — keeps working
     * unmodified for a PDF-batch import too. `file_name` is already set to
     * this exact name at creation (see store()); throwing here, rather than
     * silently skipping a failed zip::open(), stops the import from being
     * marked 'completed' with a Download button that points at a file that
     * was never actually written.
     */
    private function archivePdfImportFolder(TaxFileImport $import, string $folder): void
    {
        $zipPath = public_path('uploads/tax-file-imports/'.$import->file_name);

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create archive for the uploaded PDFs.');
        }

        foreach (glob($folder.'/*') ?: [] as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        $this->deleteDirectory($folder);
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (glob($path.'/*') ?: [] as $file) {
            is_dir($file) ? $this->deleteDirectory($file) : @unlink($file);
        }

        @rmdir($path);
    }

    private function storeSuccess(Request $request, TaxFileImport $import)
    {
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Tax file import created successfully.',
                'data' => $import->load('bank', 'createdBy'),
            ]);
        }

        return redirect()->route('tax-file-imports.index')
            ->with('success', 'Tax file import created successfully.');
    }

    /**
     * Every call site here is reacting to an unexpected exception (a bad
     * upload got past `mimes:pdf`, a DB write failed) — the request's shape
     * is already rejected by `$request->validate()` itself, which returns
     * Laravel's own validation-error JSON, not this.
     */
    private function storeFailure(Request $request, string $message, int $status = 500)
    {
        if ($request->ajax()) {
            return response()->json([
                'status' => 'error',
                'message' => $message,
            ], $status);
        }

        return back()->with('error', $message);
    }

    public function show($id)
    {
        $import = TaxFileImport::with(['bank', 'createdBy', 'updatedBy', 'details'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $import,
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
                'banks' => $banks,
            ]);
        }

        return view('finance.tax-file-imports.edit', compact('import', 'banks'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,failed',
            'auto_process' => 'boolean',
            // Delimiter is no longer asked for — the parser detects it — but older
            // clients may still submit one, so accept it when it is valid.
            'delimiter' => ['nullable', Rule::in(TaxFileImport::DELIMITERS)],
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $import = TaxFileImport::findOrFail($id);

            $payload = [
                'status' => $request->status,
                'auto_process' => $request->boolean('auto_process'),
                'notes' => $request->notes,
                'updated_by' => Auth::id(),
            ];

            if (filled($request->delimiter)) {
                $payload['delimiter'] = $request->delimiter;
            }

            $import->update($payload);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tax file import updated successfully.',
                    'data' => $import->load('bank', 'updatedBy'),
                ]);
            }

            return redirect()->route('tax-file-imports.index')
                ->with('success', 'Tax file import updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update import: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to update import: '.$e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $import = TaxFileImport::findOrFail($id);

            if (! $import->canDelete()) {
                if (request()->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot delete import with status: '.$import->status,
                    ], 400);
                }

                return back()->with('error', 'Cannot delete import with status: '.$import->status);
            }

            // Delete associated file
            if ($import->file_name) {
                $filePath = public_path('uploads/tax-file-imports/'.$import->file_name);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $import->delete();

            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Tax file import deleted successfully.',
                ]);
            }

            return redirect()->route('tax-file-imports.index')
                ->with('success', 'Tax file import deleted successfully.');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete import: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to delete import: '.$e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'import_ids' => 'required|array|min:1',
            'import_ids.*' => 'exists:tax_file_imports,id',
        ]);

        try {
            DB::beginTransaction();

            $imports = TaxFileImport::whereIn('id', $request->import_ids)->get();
            $deletedCount = 0;

            foreach ($imports as $import) {
                if ($import->canDelete()) {
                    // Delete associated file
                    if ($import->file_name) {
                        $filePath = public_path('uploads/tax-file-imports/'.$import->file_name);
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
                'count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete imports: '.$e->getMessage(),
            ], 500);
        }
    }

    public function downloadFile($id)
    {
        $import = TaxFileImport::findOrFail($id);

        if (! $import->canDownload()) {
            return back()->with('error', 'File cannot be downloaded.');
        }

        $filePath = public_path('uploads/tax-file-imports/'.$import->file_name);

        if (! file_exists($filePath)) {
            return back()->with('error', 'File not found.');
        }

        return response()->download($filePath, $import->file_name);
    }

    public function downloadErrorLog($id)
    {
        $import = TaxFileImport::findOrFail($id);

        if (! $import->error_log) {
            return back()->with('error', 'No error log available.');
        }

        $fileName = 'error_log_'.$import->import_number.'.txt';

        return response($import->error_log)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
    }

    public function processImport($id)
    {
        try {
            DB::beginTransaction();

            $import = TaxFileImport::findOrFail($id);

            if (! $import->canProcess()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Import cannot be processed in current status: '.$import->status,
                ], 400);
            }

            // Update status to processing
            $import->update(['status' => 'processing']);

            // Process the import file
            $this->processImportFile($import, 'tax-file-imports/'.$import->file_name);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Import processing started successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            // Update status to failed on error
            $import = TaxFileImport::findOrFail($id);
            $import->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process import: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Read the CoreTax result file and stamp the issued faktur pajak onto the
     * matching invoices. Delegated to CoreTaxImportService, which matches on the
     * named "Reference" / "TaxInvoiceStatus" columns.
     */
    private function processImportFile(TaxFileImport $import, string $filePath): void
    {
        try {
            app(CoreTaxImportService::class)->process($import, $filePath);
        } catch (\Throwable $e) {
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
            'data' => $stats,
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
            'data' => $imports,
        ]);
    }
}

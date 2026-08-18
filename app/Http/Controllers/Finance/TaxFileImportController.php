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
use Illuminate\Support\Facades\Validator;
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
     * Accepts either ONE CSV/XLSX/XLS result file (the batched CoreTax export)
     * or one-or-more PDF "Output Tax Invoice" files (individually downloaded
     * fakturs) — never a mix. There is no longer an Auto Process choice: every
     * import is processed immediately, in the same request, so the button
     * really does what it says.
     */
    public function store(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1|max:200',
            'files.*' => ['required', 'file', 'max:10240'],
            'notes' => 'nullable|string|max:1000',
        ]);

        /** @var UploadedFile[] $files */
        $files = $request->file('files');

        $extensions = collect($files)
            ->map(fn (UploadedFile $f) => strtolower((string) $f->getClientOriginalExtension()))
            ->unique()
            ->values();

        if ($extensions->count() !== 1 || ! in_array($extensions->first(), ['csv', 'xlsx', 'xls', 'pdf'], true)) {
            return $this->storeFailure($request,
                'Upload satu file CSV/Excel, atau satu atau lebih file PDF Faktur Pajak — jangan dicampur dengan tipe lain.', 422);
        }

        $isPdfBatch = $extensions->first() === 'pdf';

        if (! $isPdfBatch && count($files) > 1) {
            return $this->storeFailure($request, 'File CSV/Excel hanya bisa diupload satu per satu.', 422);
        }

        // The CSV branch checks the FILENAME extension, not the sniffed content
        // type: a plain CSV exported from Excel often gets sniffed as text/plain,
        // not text/csv, and Laravel's stricter `mimes` rule rejects that even with
        // text/plain explicitly allow-listed below. `extensions` + a generous
        // content allowlist is what actually accepts real-world CSV exports.
        $mimeRules = $isPdfBatch
            ? ['files.*' => 'mimes:pdf']
            : ['files.*' => [
                'extensions:csv,xlsx,xls',
                'mimetypes:text/plain,text/csv,application/csv,text/comma-separated-values,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip,application/x-ole-storage,application/octet-stream',
            ]];

        $mimeValidator = Validator::make($request->all(), $mimeRules, [
            'files.*.extensions' => 'File harus berupa CSV, XLSX, atau XLS.',
            'files.*.mimes' => 'Semua file harus berupa PDF.',
            'files.*.mimetypes' => 'File harus berupa CSV, XLSX, atau XLS yang valid.',
        ]);

        if ($mimeValidator->fails()) {
            return $this->storeFailure($request, $mimeValidator->errors()->first(), 422);
        }

        return $isPdfBatch
            ? $this->storePdfImport($request, $files)
            : $this->storeSpreadsheetImport($request, $files[0]);
    }

    /**
     * The existing batched CSV/XLSX result flow, unchanged except that
     * processing is no longer gated behind an Auto Process choice, and is no
     * longer wrapped in a transaction that could roll the import record
     * itself back out of existence if processing failed — a landmine that
     * mattered a lot more once processing became mandatory on every submit.
     */
    private function storeSpreadsheetImport(Request $request, UploadedFile $file)
    {
        $fileName = time().'_'.$file->getClientOriginalName();
        $fileFormat = $file->getClientOriginalExtension();
        $uploadPath = 'tax-file-imports';
        $filePath = $uploadPath.'/'.$fileName;

        $fullPath = public_path('uploads/'.$uploadPath);
        if (! file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $uploadedFilePath = $fullPath.DIRECTORY_SEPARATOR.$fileName;
        $file->move($fullPath, $fileName);

        try {
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
                'auto_process' => true,
                'delimiter' => TaxFileImport::DELIMITER_COMMA,
                'notes' => $request->notes,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            if (file_exists($uploadedFilePath)) {
                unlink($uploadedFilePath);
            }

            return $this->storeFailure($request, 'Failed to create import: '.$e->getMessage());
        }

        try {
            $this->processImportFile($import, $filePath);
        } catch (\Throwable $e) {
            // processImportFile() has already marked the import 'failed' and
            // logged the error; the record and the original file stay so this
            // can be inspected, or retried later via the Process button.
            return $this->storeFailure($request, 'Failed to process import: '.$e->getMessage());
        }

        return $this->storeSuccess($request, $import);
    }

    /**
     * Each uploaded PDF is parsed and matched to its invoice individually, so
     * one bad file does not abort the batch. Matched PDFs are archived onto
     * their invoice's FILE(S) tab by the service; the uploads themselves are
     * kept as a zip on the import record for the existing Download button.
     */
    private function storePdfImport(Request $request, array $files)
    {
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
                'file_name' => null,
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
     * unmodified for a PDF-batch import too.
     */
    private function archivePdfImportFolder(TaxFileImport $import, string $folder): void
    {
        $zipName = $import->import_number.'.zip';
        $zipPath = public_path('uploads/tax-file-imports/'.$zipName);

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach (glob($folder.'/*') ?: [] as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
        }

        $this->deleteDirectory($folder);

        $import->update(['file_name' => $zipName]);
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
     * Defaults to 500: most call sites are reacting to an unexpected
     * exception (a bad upload made it past validation, a DB write failed),
     * not a mistake in the request's shape. The 3 checks in store() that ARE
     * rejecting the shape of the request (mixed file types, wrong count,
     * wrong mimetype) pass 422 explicitly.
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

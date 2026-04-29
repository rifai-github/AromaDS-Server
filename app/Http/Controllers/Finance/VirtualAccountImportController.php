<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\VirtualAccountImport;
use App\Http\Requests\Finance\VirtualAccountImportRequest;
use App\Services\Finance\VirtualAccountImportService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VirtualAccountImportController extends Controller
{
    protected $virtualAccountImportService;

    public function __construct(VirtualAccountImportService $virtualAccountImportService)
    {
        $this->virtualAccountImportService = $virtualAccountImportService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'bank_id', 'status', 'import_date']);
        
        $virtualAccountImports = $this->virtualAccountImportService->getVirtualAccountImports($filters);
        $banks = $this->virtualAccountImportService->getBanks();
        
        return view('finance.virtual-account-imports.index', compact('virtualAccountImports', 'banks'))->with('imports', $virtualAccountImports);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $banks = $this->virtualAccountImportService->getBanks();
        
        return view('finance.virtual-account-imports.create', compact('banks'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VirtualAccountImportRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            
            $virtualAccountImport = $this->virtualAccountImportService->createVirtualAccountImport($data);
            
            return redirect()
                ->route('finance.virtual-account-imports.show', $virtualAccountImport->id)
                ->with('success', 'Virtual Account Import created successfully.');
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create Virtual Account Import: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created resource via API/Modal
     */
    public function storeApi(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
                'description' => 'nullable|string|max:1000',
            ]);

            $data = [
                'import_file' => $request->file('file'),
                'description' => $request->input('description'),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ];

            $virtualAccountImport = $this->virtualAccountImportService->createVirtualAccountImport($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Virtual Account Import created successfully.',
                'data' => [
                    'id' => $virtualAccountImport->id,
                    'import_number' => $virtualAccountImport->import_number,
                ]
            ]);
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create Virtual Account Import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(VirtualAccountImport $virtualAccountImport): View
    {
        $virtualAccountImport->load(['bank', 'createdBy', 'updatedBy']);
        
        return view('finance.virtual-account-imports.show', compact('virtualAccountImport'));
    }

    /**
     * Get import data for API/Modal
     */
    public function getImportData(VirtualAccountImport $virtualAccountImport)
    {
        $virtualAccountImport->load(['bank', 'createdBy', 'updatedBy']);
        
        return response()->json([
            'id' => $virtualAccountImport->id,
            'import_number' => $virtualAccountImport->import_number,
            'import_date' => $virtualAccountImport->import_date_formatted,
            'bank_id' => $virtualAccountImport->bank_id,
            'bank_name' => $virtualAccountImport->bank ? $virtualAccountImport->bank->bank_name : 'N/A',
            'file_name' => $virtualAccountImport->file_name,
            'file_size' => $virtualAccountImport->formatted_file_size,
            'file_type' => $virtualAccountImport->file_type,
            'total_records' => $virtualAccountImport->total_records,
            'processed_records' => $virtualAccountImport->processed_records,
            'success_count' => $virtualAccountImport->success_count,
            'failed_count' => $virtualAccountImport->failed_count,
            'status' => $virtualAccountImport->status,
            'status_text' => $virtualAccountImport->status_text,
            'description' => $virtualAccountImport->description,
            'notes' => $virtualAccountImport->notes,
            'created_at' => $virtualAccountImport->created_at_formatted,
            'created_by' => $virtualAccountImport->createdBy ? $virtualAccountImport->createdBy->name : 'N/A',
            'updated_at' => $virtualAccountImport->updated_at ? $virtualAccountImport->updated_at->format('d/M/Y H:i') : 'N/A',
            'updated_by' => $virtualAccountImport->updatedBy ? $virtualAccountImport->updatedBy->name : 'N/A',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VirtualAccountImport $virtualAccountImport): View
    {
        $banks = $this->virtualAccountImportService->getBanks();
        
        return view('finance.virtual-account-imports.edit', compact('virtualAccountImport', 'banks'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VirtualAccountImportRequest $request, VirtualAccountImport $virtualAccountImport): RedirectResponse
    {
        try {
            $data = $request->validated();
            
            $this->virtualAccountImportService->updateVirtualAccountImport($virtualAccountImport, $data);
            
            return redirect()
                ->route('finance.virtual-account-imports.show', $virtualAccountImport->id)
                ->with('success', 'Virtual Account Import updated successfully.');
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update Virtual Account Import: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource via API/Modal
     */
    public function updateApi(Request $request, VirtualAccountImport $virtualAccountImport)
    {
        try {
            $request->validate([
                'description' => 'nullable|string|max:1000',
            ]);

            $data = [
                'description' => $request->input('description'),
                'updated_by' => auth()->id(),
            ];

            $this->virtualAccountImportService->updateVirtualAccountImport($virtualAccountImport, $data);
            
            return response()->json([
                'success' => true,
                'message' => 'Virtual Account Import updated successfully.',
            ]);
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Virtual Account Import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VirtualAccountImport $virtualAccountImport): RedirectResponse
    {
        try {
            $this->virtualAccountImportService->deleteVirtualAccountImport($virtualAccountImport);
            
            return redirect()
                ->route('finance.virtual-account-imports.index')
                ->with('success', 'Virtual Account Import deleted successfully.');
                
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete Virtual Account Import: ' . $e->getMessage());
        }
    }

    /**
     * Process the virtual account import.
     */
    public function process(VirtualAccountImport $virtualAccountImport)
    {
        try {
            $this->virtualAccountImportService->processVirtualAccountImport($virtualAccountImport);
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Virtual Account Import processed successfully.'
                ]);
            }
            
            return back()
                ->with('success', 'Virtual Account Import processed successfully.');
                
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process Virtual Account Import: ' . $e->getMessage()
                ], 500);
            }
            
            return back()
                ->with('error', 'Failed to process Virtual Account Import: ' . $e->getMessage());
        }
    }

    /**
     * Retry failed import.
     */
    public function retry(VirtualAccountImport $virtualAccountImport)
    {
        try {
            $this->virtualAccountImportService->retryVirtualAccountImport($virtualAccountImport);
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Virtual Account Import retry initiated successfully.'
                ]);
            }
            
            return back()
                ->with('success', 'Virtual Account Import retry initiated successfully.');
                
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retry Virtual Account Import: ' . $e->getMessage()
                ], 500);
            }
            
            return back()
                ->with('error', 'Failed to retry Virtual Account Import: ' . $e->getMessage());
        }
    }

    /**
     * Bulk process imports.
     */
    public function bulkProcess(Request $request): RedirectResponse
    {
        $request->validate([
            'import_ids' => 'required|array',
            'import_ids.*' => 'exists:virtual_account_imports,id'
        ]);

        try {
            $importIds = $request->input('import_ids');
            $this->virtualAccountImportService->bulkProcessImports($importIds);
            
            return back()
                ->with('success', count($importIds) . ' imports processed successfully.');
                
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to process imports: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete imports.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'import_ids' => 'required|array',
            'import_ids.*' => 'exists:virtual_account_imports,id'
        ]);

        try {
            $importIds = $request->input('import_ids');
            $this->virtualAccountImportService->bulkDeleteImports($importIds);
            
            return back()
                ->with('success', count($importIds) . ' imports deleted successfully.');
                
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to delete imports: ' . $e->getMessage());
        }
    }

    /**
     * Export imports to CSV.
     */
    public function export(Request $request)
    {
        try {
            $filters = $request->only(['search', 'bank_id', 'status', 'import_date']);
            
            return $this->virtualAccountImportService->exportToCsv($filters);
            
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to export data: ' . $e->getMessage());
        }
    }

    /**
     * Get import statistics.
     */
    public function statistics(): View
    {
        $statistics = $this->virtualAccountImportService->getStatistics();
        $trends = $this->virtualAccountImportService->getTrends();
        $summaryByStatus = $this->virtualAccountImportService->getSummaryByStatus();
        $summaryByBank = $this->virtualAccountImportService->getSummaryByBank();
        
        return view('finance.virtual-account-imports.statistics', compact(
            'statistics', 
            'trends', 
            'summaryByStatus', 
            'summaryByBank'
        ));
    }

    /**
     * Download import file.
     */
    public function download(VirtualAccountImport $virtualAccountImport)
    {
        try {
            return $this->virtualAccountImportService->downloadFile($virtualAccountImport);
            
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to download file: ' . $e->getMessage());
        }
    }

    /**
     * Preview import data.
     */
    public function preview(VirtualAccountImport $virtualAccountImport): View
    {
        try {
            $previewData = $this->virtualAccountImportService->getPreviewData($virtualAccountImport);
            
            return view('finance.virtual-account-imports.preview', compact('virtualAccountImport', 'previewData'));
            
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to load preview: ' . $e->getMessage());
        }
    }

    /**
     * Get import errors.
     */
    public function errors(VirtualAccountImport $virtualAccountImport): View
    {
        try {
            $errors = $this->virtualAccountImportService->getImportErrors($virtualAccountImport);
            
            return view('finance.virtual-account-imports.errors', compact('virtualAccountImport', 'errors'));
            
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Failed to load errors: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Services\Finance;

use App\Models\Finance\VirtualAccountImport;
use App\Models\Finance\Bank;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VirtualAccountImportService
{
    /**
     * Get virtual account imports with filters
     */
    public function getVirtualAccountImports(array $filters = [])
    {
        $query = VirtualAccountImport::with(['bank', 'createdBy']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('import_number', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Apply bank filter
        if (!empty($filters['bank_id'])) {
            $query->byBank($filters['bank_id']);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply date filter
        if (!empty($filters['import_date'])) {
            $query->whereDate('import_date', $filters['import_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Get banks for dropdown
     */
    public function getBanks()
    {
        return Bank::active()->orderBy('bank_name')->get();
    }

    /**
     * Create a new virtual account import
     */
    public function createVirtualAccountImport(array $data)
    {
        DB::beginTransaction();
        
        try {
            // Handle file upload
            if (isset($data['import_file'])) {
                $fileData = $this->uploadImportFile($data['import_file']);
                $data = array_merge($data, $fileData);
            }

            // Set default values
            $data['total_records'] = 0;
            $data['processed_records'] = 0;
            $data['success_count'] = 0;
            $data['failed_count'] = 0;
            $data['status'] = 'pending';
            $data['auto_process'] = false;

            $virtualAccountImport = VirtualAccountImport::create($data);

            // Auto process if enabled
            if ($data['auto_process'] ?? false) {
                $this->processVirtualAccountImport($virtualAccountImport);
            }

            DB::commit();
            return $virtualAccountImport;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update virtual account import
     */
    public function updateVirtualAccountImport(VirtualAccountImport $virtualAccountImport, array $data)
    {
        DB::beginTransaction();
        
        try {
            // Handle file upload if new file is provided
            if (isset($data['import_file'])) {
                // Delete old file
                if ($virtualAccountImport->file_path) {
                    $this->deleteImportFile($virtualAccountImport->file_path);
                }
                
                $fileData = $this->uploadImportFile($data['import_file']);
                $data = array_merge($data, $fileData);
            }

            $virtualAccountImport->update($data);

            DB::commit();
            return $virtualAccountImport;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete virtual account import
     */
    public function deleteVirtualAccountImport(VirtualAccountImport $virtualAccountImport)
    {
        DB::beginTransaction();
        
        try {
            // Delete file
            if ($virtualAccountImport->file_path) {
                $this->deleteImportFile($virtualAccountImport->file_path);
            }

            $virtualAccountImport->delete();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process virtual account import
     */
    public function processVirtualAccountImport(VirtualAccountImport $virtualAccountImport)
    {
        if (!$virtualAccountImport->canBeProcessed()) {
            throw new \Exception('Import cannot be processed in its current status.');
        }

        DB::beginTransaction();
        
        try {
            // Update status to processing
            $virtualAccountImport->update(['status' => 'processing']);

            // Simulate processing (in real implementation, this would parse the file)
            $this->simulateFileProcessing($virtualAccountImport);

            // Update status to completed
            $virtualAccountImport->update(['status' => 'completed']);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Update status to failed
            $virtualAccountImport->update(['status' => 'failed']);
            
            throw $e;
        }
    }

    /**
     * Retry failed import
     */
    public function retryVirtualAccountImport(VirtualAccountImport $virtualAccountImport)
    {
        if (!$virtualAccountImport->canBeRetried()) {
            throw new \Exception('Import cannot be retried.');
        }

        return $this->processVirtualAccountImport($virtualAccountImport);
    }

    /**
     * Bulk process imports
     */
    public function bulkProcessImports(array $importIds)
    {
        $imports = VirtualAccountImport::whereIn('id', $importIds)
                                     ->whereIn('status', ['pending', 'failed'])
                                     ->get();

        foreach ($imports as $import) {
            try {
                $this->processVirtualAccountImport($import);
            } catch (\Exception $e) {
                // Log error but continue with other imports
                \Log::error("Failed to process import {$import->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Bulk delete imports
     */
    public function bulkDeleteImports(array $importIds)
    {
        $imports = VirtualAccountImport::whereIn('id', $importIds)->get();

        foreach ($imports as $import) {
            $this->deleteVirtualAccountImport($import);
        }
    }

    /**
     * Upload import file
     */
    private function uploadImportFile($file)
    {
        $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $filePath = 'virtual-account-imports/' . $fileName;
        
        Storage::disk('public')->put($filePath, file_get_contents($file));
        
        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
        ];
    }

    /**
     * Delete import file
     */
    private function deleteImportFile($filePath)
    {
        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
    }

    /**
     * Simulate file processing (for demo purposes)
     */
    private function simulateFileProcessing(VirtualAccountImport $virtualAccountImport)
    {
        // Simulate processing time
        sleep(2);
        
        // Generate random statistics
        $totalRecords = rand(100, 5000);
        $successCount = rand(80, 100) * $totalRecords / 100;
        $failedCount = $totalRecords - $successCount;
        
        $virtualAccountImport->update([
            'total_records' => $totalRecords,
            'success_count' => (int) $successCount,
            'failed_count' => (int) $failedCount,
        ]);
    }


    /**
     * Export to CSV
     */
    public function exportToCsv(array $filters = [])
    {
        $imports = $this->getVirtualAccountImports($filters);
        
        $filename = 'virtual_account_imports_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($imports) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, [
                'Import Number',
                'Import Date',
                'Bank',
                'File Name',
                'File Type',
                'Total Records',
                'Success Count',
                'Failed Count',
                'Status',
                'Created By',
                'Created At'
            ]);
            
            // Add data
            foreach ($imports as $import) {
                fputcsv($file, [
                    $import->import_number,
                    $import->import_date ? $import->import_date->format('Y-m-d') : '',
                    $import->bank->bank_name ?? '',
                    $import->file_name,
                    $import->file_type,
                    $import->total_records,
                    $import->success_count,
                    $import->failed_count,
                    $import->status,
                    $import->createdBy->name ?? '',
                    $import->created_at ? $import->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get statistics
     */
    public function getStatistics()
    {
        return [
            'total_imports' => VirtualAccountImport::count(),
            'pending_imports' => VirtualAccountImport::pending()->count(),
            'processing_imports' => VirtualAccountImport::processing()->count(),
            'completed_imports' => VirtualAccountImport::completed()->count(),
            'failed_imports' => VirtualAccountImport::failed()->count(),
            'total_records' => VirtualAccountImport::sum('total_records'),
            'success_records' => VirtualAccountImport::sum('success_count'),
            'failed_records' => VirtualAccountImport::sum('failed_count'),
        ];
    }

    /**
     * Get trends
     */
    public function getTrends($days = 30)
    {
        $trends = [];
        $startDate = Carbon::now()->subDays($days);
        
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'imports' => VirtualAccountImport::whereDate('created_at', $date)->count(),
                'records' => VirtualAccountImport::whereDate('created_at', $date)->sum('total_records'),
            ];
        }
        
        return $trends;
    }

    /**
     * Get summary by status
     */
    public function getSummaryByStatus()
    {
        return VirtualAccountImport::selectRaw('status, COUNT(*) as count')
                                  ->groupBy('status')
                                  ->get();
    }

    /**
     * Get summary by bank
     */
    public function getSummaryByBank($limit = 10)
    {
        return VirtualAccountImport::with('bank')
                                  ->selectRaw('bank_id, COUNT(*) as count')
                                  ->groupBy('bank_id')
                                  ->orderBy('count', 'desc')
                                  ->limit($limit)
                                  ->get();
    }

    /**
     * Download file
     */
    public function downloadFile(VirtualAccountImport $virtualAccountImport)
    {
        if (!$virtualAccountImport->file_path) {
            throw new \Exception('No file available for download.');
        }

        if (!Storage::disk('public')->exists($virtualAccountImport->file_path)) {
            throw new \Exception('File not found.');
        }

        return Storage::disk('public')->download(
            $virtualAccountImport->file_path,
            $virtualAccountImport->file_name
        );
    }

    /**
     * Get preview data
     */
    public function getPreviewData(VirtualAccountImport $virtualAccountImport)
    {
        if (!$virtualAccountImport->file_path) {
            throw new \Exception('No file available for preview.');
        }

        if (!Storage::disk('public')->exists($virtualAccountImport->file_path)) {
            throw new \Exception('File not found.');
        }

        $content = Storage::disk('public')->get($virtualAccountImport->file_path);
        $lines = explode("\n", $content);
        
        // Return first 10 lines for preview
        return array_slice($lines, 0, 10);
    }

    /**
     * Get import errors
     */
    public function getImportErrors(VirtualAccountImport $virtualAccountImport)
    {
        // In a real implementation, this would return actual import errors
        // For now, return empty array
        return [];
    }
}

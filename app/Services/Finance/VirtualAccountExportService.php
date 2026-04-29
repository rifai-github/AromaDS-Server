<?php

namespace App\Services\Finance;

use App\Models\Finance\VirtualAccountExport;
use App\Models\Finance\Bank;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VirtualAccountExportService
{
    /**
     * Get virtual account exports with filters
     */
    public function getVirtualAccountExports(array $filters = [])
    {
        $query = VirtualAccountExport::with(['bank', 'createdBy', 'updatedBy']);

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply bank filter
        if (!empty($filters['bank_id'])) {
            $query->byBank($filters['bank_id']);
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply export date filter
        if (!empty($filters['export_date'])) {
            $query->whereDate('export_date', $filters['export_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Create a new virtual account export
     */
    public function createVirtualAccountExport(array $data)
    {
        DB::beginTransaction();
        try {
            $virtualAccountExport = VirtualAccountExport::create($data);

            // Auto process if enabled
            if ($virtualAccountExport->auto_process) {
                $this->processVirtualAccountExport($virtualAccountExport);
            }

            DB::commit();
            return $virtualAccountExport;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update virtual account export
     */
    public function updateVirtualAccountExport(VirtualAccountExport $virtualAccountExport, array $data)
    {
        DB::beginTransaction();
        try {
            $virtualAccountExport->update($data);

            // Auto process if enabled and status is pending
            if ($virtualAccountExport->auto_process && $virtualAccountExport->status === 'pending') {
                $this->processVirtualAccountExport($virtualAccountExport);
            }

            DB::commit();
            return $virtualAccountExport;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete virtual account export
     */
    public function deleteVirtualAccountExport(VirtualAccountExport $virtualAccountExport)
    {
        DB::beginTransaction();
        try {
            // Delete associated file if exists
            if ($virtualAccountExport->file_path && Storage::disk('public')->exists($virtualAccountExport->file_path)) {
                Storage::disk('public')->delete($virtualAccountExport->file_path);
            }

            $virtualAccountExport->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Process virtual account export
     */
    public function processVirtualAccountExport(VirtualAccountExport $virtualAccountExport)
    {
        if (!$virtualAccountExport->canBeProcessed()) {
            throw new \Exception('Export cannot be processed in its current status.');
        }

        DB::beginTransaction();
        try {
            // Update status to processing
            $virtualAccountExport->update(['status' => 'processing']);

            // Simulate export processing
            $this->simulateExportProcessing($virtualAccountExport);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Update status to failed
            $virtualAccountExport->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Retry virtual account export
     */
    public function retryVirtualAccountExport(VirtualAccountExport $virtualAccountExport)
    {
        if (!$virtualAccountExport->canBeRetried()) {
            throw new \Exception('Export cannot be retried in its current status.');
        }

        return $this->processVirtualAccountExport($virtualAccountExport);
    }

    /**
     * Bulk process exports
     */
    public function bulkProcessExports(array $exportIds)
    {
        $exports = VirtualAccountExport::whereIn('id', $exportIds)
            ->whereIn('status', ['pending', 'failed'])
            ->get();

        $processedCount = 0;
        foreach ($exports as $export) {
            try {
                $this->processVirtualAccountExport($export);
                $processedCount++;
            } catch (\Exception $e) {
                // Log error but continue with other exports
                \Log::error("Failed to process export {$export->id}: " . $e->getMessage());
            }
        }

        return $processedCount;
    }

    /**
     * Bulk delete exports
     */
    public function bulkDeleteExports(array $exportIds)
    {
        $exports = VirtualAccountExport::whereIn('id', $exportIds)
            ->whereIn('status', ['pending', 'failed', 'completed'])
            ->get();

        $deletedCount = 0;
        foreach ($exports as $export) {
            try {
                $this->deleteVirtualAccountExport($export);
                $deletedCount++;
            } catch (\Exception $e) {
                // Log error but continue with other exports
                \Log::error("Failed to delete export {$export->id}: " . $e->getMessage());
            }
        }

        return $deletedCount;
    }

    /**
     * Simulate export processing
     */
    private function simulateExportProcessing(VirtualAccountExport $virtualAccountExport)
    {
        // Simulate processing time
        sleep(2);

        // Generate sample data based on export settings
        $totalRecords = rand(100, $virtualAccountExport->limit_records);
        
        // Generate export file
        $fileName = 'virtual_accounts_export_' . $virtualAccountExport->export_date->format('Y-m-d') . '.' . $virtualAccountExport->file_type;
        $filePath = 'virtual-account-exports/' . $fileName;
        
        $fileContent = $this->generateExportFile($virtualAccountExport, $totalRecords);
        
        // Save file
        Storage::disk('public')->put($filePath, $fileContent);
        
        // Update export record
        $virtualAccountExport->update([
            'file_name' => $fileName,
            'file_path' => $filePath,
            'file_size' => Storage::disk('public')->size($filePath),
            'total_records' => $totalRecords,
            'status' => 'completed',
        ]);
    }

    /**
     * Generate export file content
     */
    private function generateExportFile(VirtualAccountExport $virtualAccountExport, int $totalRecords)
    {
        $columns = $virtualAccountExport->include_columns ?? ['va_number', 'customer_name', 'amount', 'due_date', 'status'];
        $delimiter = $virtualAccountExport->delimiter ?? ',';
        
        $content = '';
        
        // Add header if enabled
        if ($virtualAccountExport->include_header) {
            $headers = array_map(function($column) {
                return ucfirst(str_replace('_', ' ', $column));
            }, $columns);
            $content .= implode($delimiter, $headers) . "\n";
        }
        
        // Generate sample data
        for ($i = 1; $i <= $totalRecords; $i++) {
            $row = [];
            foreach ($columns as $column) {
                $row[] = $this->generateSampleData($column, $i);
            }
            $content .= implode($delimiter, $row) . "\n";
        }
        
        return $content;
    }

    /**
     * Generate sample data for export
     */
    private function generateSampleData(string $column, int $index)
    {
        return match($column) {
            'va_number' => 'VA' . str_pad($index, 8, '0', STR_PAD_LEFT),
            'customer_name' => 'Customer ' . $index,
            'amount' => number_format(rand(100000, 10000000), 2),
            'due_date' => Carbon::now()->addDays(rand(1, 30))->format('Y-m-d'),
            'status' => ['active', 'inactive', 'expired'][array_rand(['active', 'inactive', 'expired'])],
            'created_at' => Carbon::now()->subDays(rand(1, 365))->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->subDays(rand(1, 365))->format('Y-m-d H:i:s'),
            'notes' => 'Sample note for record ' . $index,
            default => 'N/A',
        };
    }

    /**
     * Get banks for dropdown
     */
    public function getBanks()
    {
        return Bank::orderBy('bank_name')->get();
    }

    /**
     * Export to CSV
     */
    public function exportToCsv(array $filters = [])
    {
        $exports = $this->getVirtualAccountExports($filters);
        
        $filename = 'virtual_account_exports_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($exports) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, [
                'Export Number',
                'Export Date',
                'Bank',
                'File Type',
                'Total Records',
                'Status',
                'Created By',
                'Created At'
            ]);
            
            // Add data
            foreach ($exports as $export) {
                fputcsv($file, [
                    $export->export_number,
                    $export->export_date->format('Y-m-d'),
                    $export->bank->bank_name ?? '-',
                    strtoupper($export->file_type),
                    $export->total_records,
                    ucfirst($export->status),
                    $export->createdBy->name ?? '-',
                    $export->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Get statistics
     */
    public function getStatistics()
    {
        $totalExports = VirtualAccountExport::count();
        $pendingExports = VirtualAccountExport::pending()->count();
        $processingExports = VirtualAccountExport::processing()->count();
        $completedExports = VirtualAccountExport::completed()->count();
        $failedExports = VirtualAccountExport::failed()->count();
        
        $totalRecords = VirtualAccountExport::sum('total_records');
        $avgRecordsPerExport = $totalExports > 0 ? round($totalRecords / $totalExports, 2) : 0;
        
        $recentExports = VirtualAccountExport::with(['bank', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'total_exports' => $totalExports,
            'pending_exports' => $pendingExports,
            'processing_exports' => $processingExports,
            'completed_exports' => $completedExports,
            'failed_exports' => $failedExports,
            'total_records' => $totalRecords,
            'avg_records_per_export' => $avgRecordsPerExport,
            'recent_exports' => $recentExports,
        ];
    }

    /**
     * Get trends
     */
    public function getTrends($days = 30)
    {
        $startDate = Carbon::now()->subDays($days);
        
        $trends = VirtualAccountExport::selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total_exports,
                SUM(total_records) as total_records,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_exports,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_exports
            ')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $trends;
    }

    /**
     * Get summary by status
     */
    public function getSummaryByStatus()
    {
        return VirtualAccountExport::selectRaw('
                status,
                COUNT(*) as count,
                SUM(total_records) as total_records
            ')
            ->groupBy('status')
            ->get();
    }

    /**
     * Get summary by bank
     */
    public function getSummaryByBank($limit = 10)
    {
        return VirtualAccountExport::with('bank')
            ->selectRaw('
                bank_id,
                COUNT(*) as count,
                SUM(total_records) as total_records
            ')
            ->groupBy('bank_id')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Download file
     */
    public function downloadFile(VirtualAccountExport $virtualAccountExport)
    {
        if (!$virtualAccountExport->file_path || !Storage::disk('public')->exists($virtualAccountExport->file_path)) {
            throw new \Exception('File not found.');
        }

        return Storage::disk('public')->download($virtualAccountExport->file_path, $virtualAccountExport->file_name);
    }
}

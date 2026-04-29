<?php

namespace App\Services\Reports;

use App\Models\DataExport;
use App\Models\ReportTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DataExportService
{
    /**
     * Create a new data export
     */
    public function createExport(array $data): DataExport
    {
        return DB::transaction(function () use ($data) {
            $export = DataExport::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'template_id' => $data['template_id'] ?? null,
                'data_source' => $data['data_source'],
                'query' => $data['query'],
                'parameters' => $data['parameters'] ?? '{}',
                'export_format' => $data['export_format'] ?? 'csv',
                'file_path' => null,
                'file_size' => null,
                'status' => 'pending',
                'scheduled_at' => isset($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            return $export;
        });
    }

    /**
     * Update data export
     */
    public function updateExport(DataExport $export, array $data): DataExport
    {
        $export->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $export->description,
            'template_id' => $data['template_id'] ?? $export->template_id,
            'data_source' => $data['data_source'] ?? $export->data_source,
            'query' => $data['query'] ?? $export->query,
            'parameters' => $data['parameters'] ?? $export->parameters,
            'export_format' => $data['export_format'] ?? $export->export_format,
            'scheduled_at' => isset($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : $export->scheduled_at,
            'updated_by' => Auth::id()
        ]);

        return $export;
    }

    /**
     * Execute data export
     */
    public function executeExport(DataExport $export): DataExport
    {
        return DB::transaction(function () use ($export) {
            try {
                // Update status to running
                $export->update([
                    'status' => 'running',
                    'started_at' => now(),
                    'updated_by' => Auth::id()
                ]);

                // Execute query
                $data = $this->executeQuery($export);

                // Generate file
                $filePath = $this->generateFile($export, $data);

                // Update export with file info
                $export->update([
                    'status' => 'completed',
                    'file_path' => $filePath,
                    'file_size' => Storage::size($filePath),
                    'completed_at' => now(),
                    'updated_by' => Auth::id()
                ]);

                return $export;

            } catch (\Exception $e) {
                // Update status to failed
                $export->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                    'updated_by' => Auth::id()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Execute query for export
     */
    private function executeQuery(DataExport $export): array
    {
        $query = $export->query;
        $parameters = json_decode($export->parameters, true) ?? [];

        // Replace parameters in query
        foreach ($parameters as $key => $value) {
            $query = str_replace(":{$key}", "'{$value}'", $query);
        }

        try {
            return DB::select($query);
        } catch (\Exception $e) {
            throw new \RuntimeException("Query execution failed: " . $e->getMessage());
        }
    }

    /**
     * Generate file based on export format
     */
    private function generateFile(DataExport $export, array $data): string
    {
        $fileName = $export->name . '_' . now()->format('Y-m-d_H-i-s') . '.' . $export->export_format;
        $filePath = 'exports/' . $fileName;

        switch ($export->export_format) {
            case 'csv':
                $this->generateCsvFile($filePath, $data);
                break;
            case 'xlsx':
                $this->generateXlsxFile($filePath, $data);
                break;
            case 'json':
                $this->generateJsonFile($filePath, $data);
                break;
            case 'pdf':
                $this->generatePdfFile($filePath, $data, $export);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$export->export_format}");
        }

        return $filePath;
    }

    /**
     * Generate CSV file
     */
    private function generateCsvFile(string $filePath, array $data): void
    {
        $csvContent = '';
        
        if (!empty($data)) {
            // Add headers
            $headers = array_keys((array) $data[0]);
            $csvContent .= implode(',', $headers) . "\n";

            // Add data rows
            foreach ($data as $row) {
                $csvContent .= implode(',', array_map(function ($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, (array) $row)) . "\n";
            }
        }

        Storage::put($filePath, $csvContent);
    }

    /**
     * Generate XLSX file
     */
    private function generateXlsxFile(string $filePath, array $data): void
    {
        // This would require a library like PhpSpreadsheet
        // For now, we'll create a simple CSV-like format
        $this->generateCsvFile($filePath, $data);
    }

    /**
     * Generate JSON file
     */
    private function generateJsonFile(string $filePath, array $data): void
    {
        $jsonContent = json_encode($data, JSON_PRETTY_PRINT);
        Storage::put($filePath, $jsonContent);
    }

    /**
     * Generate PDF file
     */
    private function generatePdfFile(string $filePath, array $data, DataExport $export): void
    {
        // This would require a PDF generation library like DomPDF or TCPDF
        // For now, we'll create a simple text file
        $content = "Export: {$export->name}\n";
        $content .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";
        
        if (!empty($data)) {
            $headers = array_keys((array) $data[0]);
            $content .= implode("\t", $headers) . "\n";
            
            foreach ($data as $row) {
                $content .= implode("\t", (array) $row) . "\n";
            }
        }

        Storage::put($filePath, $content);
    }

    /**
     * Schedule export
     */
    public function scheduleExport(DataExport $export, Carbon $scheduledAt): DataExport
    {
        $export->update([
            'scheduled_at' => $scheduledAt,
            'status' => 'scheduled',
            'updated_by' => Auth::id()
        ]);

        return $export;
    }

    /**
     * Get export file download URL
     */
    public function getDownloadUrl(DataExport $export): ?string
    {
        if (!$export->file_path || !Storage::exists($export->file_path)) {
            return null;
        }

        return Storage::url($export->file_path);
    }

    /**
     * Get export statistics
     */
    public function getStatistics(): array
    {
        $total = DataExport::count();
        $completed = DataExport::where('status', 'completed')->count();
        $failed = DataExport::where('status', 'failed')->count();
        $running = DataExport::where('status', 'running')->count();
        $scheduled = DataExport::where('status', 'scheduled')->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'running' => $running,
            'scheduled' => $scheduled,
            'success_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
        ];
    }

    /**
     * Get exports by status
     */
    public function getExportsByStatus(string $status): \Illuminate\Database\Eloquent\Collection
    {
        return DataExport::where('status', $status)
            ->with('template')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get scheduled exports ready for execution
     */
    public function getScheduledExports(): \Illuminate\Database\Eloquent\Collection
    {
        return DataExport::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();
    }

    /**
     * Clean up old export files
     */
    public function cleanupOldFiles(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        
        $oldExports = DataExport::where('created_at', '<', $cutoffDate)
            ->whereNotNull('file_path')
            ->get();

        $deletedCount = 0;
        foreach ($oldExports as $export) {
            if (Storage::exists($export->file_path)) {
                Storage::delete($export->file_path);
                $deletedCount++;
            }
            
            $export->update([
                'file_path' => null,
                'file_size' => null
            ]);
        }

        return $deletedCount;
    }

    /**
     * Duplicate export
     */
    public function duplicateExport(DataExport $export, string $newName): DataExport
    {
        return DataExport::create([
            'name' => $newName,
            'description' => $export->description,
            'template_id' => $export->template_id,
            'data_source' => $export->data_source,
            'query' => $export->query,
            'parameters' => $export->parameters,
            'export_format' => $export->export_format,
            'file_path' => null,
            'file_size' => null,
            'status' => 'pending',
            'scheduled_at' => null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);
    }
}

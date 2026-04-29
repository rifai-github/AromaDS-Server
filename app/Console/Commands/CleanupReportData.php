<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportHistory;
use App\Models\AnalyticsEvent;
use App\Models\AlertNotification;
use App\Models\DataExport;
use App\Models\KpiValue;
use Illuminate\Support\Facades\Storage;

class CleanupReportData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reports:cleanup 
                            {--report-history-days=90 : Days to keep report history}
                            {--analytics-days=90 : Days to keep analytics events}
                            {--alerts-days=30 : Days to keep alert notifications}
                            {--exports-days=30 : Days to keep export files}
                            {--kpi-values-days=365 : Days to keep KPI values}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old report data to maintain performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
        }
        
        $this->info('Starting report data cleanup...');
        
        // Clean up report history
        $this->cleanupReportHistory($dryRun);
        
        // Clean up analytics events
        $this->cleanupAnalyticsEvents($dryRun);
        
        // Clean up alert notifications
        $this->cleanupAlertNotifications($dryRun);
        
        // Clean up export files
        $this->cleanupExportFiles($dryRun);
        
        // Clean up KPI values
        $this->cleanupKpiValues($dryRun);
        
        // Clean up orphaned files
        $this->cleanupOrphanedFiles($dryRun);
        
        $this->info('Report data cleanup completed!');
    }
    
    /**
     * Clean up report history
     */
    private function cleanupReportHistory(bool $dryRun): void
    {
        $days = (int) $this->option('report-history-days');
        $cutoffDate = now()->subDays($days);
        
        $this->line("Cleaning up report history older than {$days} days...");
        
        $query = ReportHistory::where('created_at', '<', $cutoffDate);
        $count = $query->count();
        
        if ($count > 0) {
            if ($dryRun) {
                $this->info("  Would delete {$count} report history records");
            } else {
                $deleted = $query->delete();
                $this->info("  Deleted {$deleted} report history records");
            }
        } else {
            $this->line("  No report history records to delete");
        }
    }
    
    /**
     * Clean up analytics events
     */
    private function cleanupAnalyticsEvents(bool $dryRun): void
    {
        $days = (int) $this->option('analytics-days');
        $cutoffDate = now()->subDays($days);
        
        $this->line("Cleaning up analytics events older than {$days} days...");
        
        $query = AnalyticsEvent::where('created_at', '<', $cutoffDate);
        $count = $query->count();
        
        if ($count > 0) {
            if ($dryRun) {
                $this->info("  Would delete {$count} analytics events");
            } else {
                $deleted = $query->delete();
                $this->info("  Deleted {$deleted} analytics events");
            }
        } else {
            $this->line("  No analytics events to delete");
        }
    }
    
    /**
     * Clean up alert notifications
     */
    private function cleanupAlertNotifications(bool $dryRun): void
    {
        $days = (int) $this->option('alerts-days');
        $cutoffDate = now()->subDays($days);
        
        $this->line("Cleaning up alert notifications older than {$days} days...");
        
        $query = AlertNotification::where('sent_at', '<', $cutoffDate);
        $count = $query->count();
        
        if ($count > 0) {
            if ($dryRun) {
                $this->info("  Would delete {$count} alert notifications");
            } else {
                $deleted = $query->delete();
                $this->info("  Deleted {$deleted} alert notifications");
            }
        } else {
            $this->line("  No alert notifications to delete");
        }
    }
    
    /**
     * Clean up export files
     */
    private function cleanupExportFiles(bool $dryRun): void
    {
        $days = (int) $this->option('exports-days');
        $cutoffDate = now()->subDays($days);
        
        $this->line("Cleaning up export files older than {$days} days...");
        
        $exports = DataExport::where('created_at', '<', $cutoffDate)
                           ->whereNotNull('file_path')
                           ->get();
        
        $deletedFiles = 0;
        $deletedRecords = 0;
        
        foreach ($exports as $export) {
            if ($export->file_path && Storage::exists($export->file_path)) {
                if ($dryRun) {
                    $deletedFiles++;
                } else {
                    Storage::delete($export->file_path);
                    $deletedFiles++;
                }
            }
            
            if ($dryRun) {
                $deletedRecords++;
            } else {
                $export->update([
                    'file_path' => null,
                    'file_size' => null
                ]);
                $deletedRecords++;
            }
        }
        
        if ($deletedFiles > 0) {
            $this->info("  " . ($dryRun ? 'Would delete' : 'Deleted') . " {$deletedFiles} export files");
        }
        
        if ($deletedRecords > 0) {
            $this->info("  " . ($dryRun ? 'Would update' : 'Updated') . " {$deletedRecords} export records");
        }
        
        if ($deletedFiles == 0 && $deletedRecords == 0) {
            $this->line("  No export files to clean up");
        }
    }
    
    /**
     * Clean up KPI values
     */
    private function cleanupKpiValues(bool $dryRun): void
    {
        $days = (int) $this->option('kpi-values-days');
        $cutoffDate = now()->subDays($days);
        
        $this->line("Cleaning up KPI values older than {$days} days...");
        
        $query = KpiValue::where('date', '<', $cutoffDate->toDateString());
        $count = $query->count();
        
        if ($count > 0) {
            if ($dryRun) {
                $this->info("  Would delete {$count} KPI values");
            } else {
                $deleted = $query->delete();
                $this->info("  Deleted {$deleted} KPI values");
            }
        } else {
            $this->line("  No KPI values to delete");
        }
    }
    
    /**
     * Clean up orphaned files
     */
    private function cleanupOrphanedFiles(bool $dryRun): void
    {
        $this->line("Checking for orphaned files...");
        
        $exportFiles = Storage::files('exports');
        $orphanedFiles = 0;
        
        foreach ($exportFiles as $file) {
            $fileName = basename($file);
            
            // Check if file is referenced in database
            $exists = DataExport::where('file_path', $file)->exists();
            
            if (!$exists) {
                if ($dryRun) {
                    $this->line("  Would delete orphaned file: {$fileName}");
                    $orphanedFiles++;
                } else {
                    Storage::delete($file);
                    $this->line("  Deleted orphaned file: {$fileName}");
                    $orphanedFiles++;
                }
            }
        }
        
        if ($orphanedFiles == 0) {
            $this->line("  No orphaned files found");
        } else {
            $this->info("  " . ($dryRun ? 'Would delete' : 'Deleted') . " {$orphanedFiles} orphaned files");
        }
    }
}

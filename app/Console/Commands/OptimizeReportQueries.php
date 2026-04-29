<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Reports\ReportQueryOptimizer;
use App\Services\Reports\ReportPerformanceService;
use App\Models\Report;

class OptimizeReportQueries extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reports:optimize-queries {--report-id= : Specific report ID to optimize} {--dry-run : Show optimizations without applying them}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize report queries for better performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reportId = $this->option('report-id');
        $dryRun = $this->option('dry-run');
        
        $this->info('Starting report query optimization...');
        
        $queryOptimizer = new ReportQueryOptimizer();
        $performanceService = new ReportPerformanceService();
        
        // Get reports to optimize
        if ($reportId) {
            $reports = Report::where('id', $reportId)->get();
        } else {
            $reports = Report::where('is_active', true)->get();
        }
        
        if ($reports->isEmpty()) {
            $this->warn('No reports found to optimize.');
            return;
        }
        
        $this->info("Found {$reports->count()} report(s) to analyze.");
        
        $optimizations = [];
        $totalImprovement = 0;
        
        foreach ($reports as $report) {
            $this->line("Analyzing report: {$report->name} (ID: {$report->id})");
            
            // Validate query
            $validation = $queryOptimizer->validateQuery($report->query);
            if (!$validation['valid']) {
                $this->error("  ❌ Invalid query: {$validation['error']}");
                continue;
            }
            
            // Optimize query
            $optimization = $queryOptimizer->optimizeQuery($report->query);
            
            if (!empty($optimization['optimizations'])) {
                $this->info("  ✅ Found " . count($optimization['optimizations']) . " optimization(s)");
                
                foreach ($optimization['optimizations'] as $opt) {
                    $this->line("    - {$opt}");
                }
                
                $optimizations[] = [
                    'report' => $report,
                    'optimization' => $optimization
                ];
                
                $totalImprovement += $optimization['performance_improvement'];
                
                // Apply optimization if not dry run
                if (!$dryRun) {
                    $report->update(['query' => $optimization['optimized_query']]);
                    $this->info("  🔄 Applied optimizations");
                }
            } else {
                $this->line("  ℹ️  No optimizations needed");
            }
            
            // Get performance metrics
            $metrics = $queryOptimizer->getQueryPerformanceMetrics($report->query);
            if ($metrics['success']) {
                $this->line("  📊 Execution time: {$metrics['execution_time_ms']}ms, Rating: {$metrics['performance_rating']}");
            }
        }
        
        // Summary
        $this->newLine();
        $this->info('Optimization Summary:');
        $this->line("Reports analyzed: {$reports->count()}");
        $this->line("Reports optimized: " . count($optimizations));
        $this->line("Total performance improvement: {$totalImprovement}%");
        
        if ($dryRun) {
            $this->warn('This was a dry run. Use --no-dry-run to apply optimizations.');
        }
        
        // Show detailed results
        if (!empty($optimizations) && $this->confirm('Show detailed optimization results?')) {
            $this->showDetailedResults($optimizations);
        }
    }
    
    /**
     * Show detailed optimization results
     */
    private function showDetailedResults(array $optimizations): void
    {
        $this->newLine();
        $this->info('Detailed Optimization Results:');
        
        foreach ($optimizations as $item) {
            $report = $item['report'];
            $optimization = $item['optimization'];
            
            $this->newLine();
            $this->line("Report: {$report->name} (ID: {$report->id})");
            $this->line("Performance Improvement: {$optimization['performance_improvement']}%");
            $this->line("Optimizations:");
            
            foreach ($optimization['optimizations'] as $opt) {
                $this->line("  - {$opt}");
            }
            
            if ($this->confirm("Show original vs optimized query for {$report->name}?")) {
                $this->line("Original Query:");
                $this->line($optimization['original_query']);
                $this->newLine();
                $this->line("Optimized Query:");
                $this->line($optimization['optimized_query']);
            }
        }
    }
}

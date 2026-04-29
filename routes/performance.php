<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Performance\PerformanceDashboardController;

/*
|--------------------------------------------------------------------------
| Performance Routes
|--------------------------------------------------------------------------
|
| Here are the routes for performance monitoring and optimization
|
*/

Route::middleware(['auth', 'permission:view_performance'])->group(function () {
    
    // Performance Dashboard
    Route::get('/performance/dashboard', [PerformanceDashboardController::class, 'index'])
        ->name('performance.dashboard');
    
    // API Performance Routes
    Route::prefix('api/performance')->group(function () {
        Route::get('/api-performance', [PerformanceDashboardController::class, 'getApiPerformance'])
            ->name('performance.api-performance');
        
        Route::get('/database-performance', [PerformanceDashboardController::class, 'getDatabasePerformance'])
            ->name('performance.database-performance');
        
        Route::get('/cache-performance', [PerformanceDashboardController::class, 'getCachePerformance'])
            ->name('performance.cache-performance');
        
        Route::get('/system-overview', [PerformanceDashboardController::class, 'getSystemOverview'])
            ->name('performance.system-overview');
        
        Route::get('/alerts', [PerformanceDashboardController::class, 'getPerformanceAlerts'])
            ->name('performance.alerts');
        
        Route::get('/trends', [PerformanceDashboardController::class, 'getPerformanceTrends'])
            ->name('performance.trends');
    });
    
    // Performance Optimization Routes
    Route::middleware(['permission:manage_performance'])->group(function () {
        Route::post('/performance/optimize', [PerformanceDashboardController::class, 'runOptimization'])
            ->name('performance.optimize');
    });
});

// API Routes for Performance Monitoring
Route::prefix('api/v1/performance')->middleware(['auth:sanctum'])->group(function () {
    
    // Performance Metrics
    Route::get('/metrics', [PerformanceDashboardController::class, 'getSystemOverview'])
        ->name('api.performance.metrics');
    
    Route::get('/health', [PerformanceDashboardController::class, 'getSystemOverview'])
        ->name('api.performance.health');
    
    // Performance Alerts
    Route::get('/alerts', [PerformanceDashboardController::class, 'getPerformanceAlerts'])
        ->name('api.performance.alerts');
    
    // Performance Trends
    Route::get('/trends', [PerformanceDashboardController::class, 'getPerformanceTrends'])
        ->name('api.performance.trends');
    
    // Performance Optimization (Admin only)
    Route::middleware(['permission:manage_performance'])->group(function () {
        Route::post('/optimize', [PerformanceDashboardController::class, 'runOptimization'])
            ->name('api.performance.optimize');
    });
});

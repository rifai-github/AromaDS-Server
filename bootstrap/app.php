<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
        ->withMiddleware(function (Middleware $middleware): void {
            // Register custom middleware
            $middleware->alias([
                'role' => \App\Http\Middleware\CheckRole::class,
                'permission' => \App\Http\Middleware\CheckPermission::class,
                'module.access' => \App\Http\Middleware\CheckModuleAccess::class,
                'data.restriction' => \App\Http\Middleware\DataRestriction::class,
                'login.logging' => \App\Http\Middleware\LoginLogging::class,
                'download.logging' => \App\Http\Middleware\DownloadLogging::class,
                'upload.logging' => \App\Http\Middleware\UploadLogging::class,
                'pageview.logging' => \App\Http\Middleware\PageViewLogging::class,
                'report.logging' => \App\Http\Middleware\ReportGenerationLogging::class,
                'frozen.account' => \App\Http\Middleware\CheckFrozenAccount::class,
                ...(class_exists(\App\Http\Middleware\CheckLoginRestriction::class)
                    ? ['login.restriction' => \App\Http\Middleware\CheckLoginRestriction::class]
                    : []),
            ]);
            
            // Add CheckFrozenAccount middleware to web group (runs after auth middleware)
            $middleware->appendToGroup('web', \App\Http\Middleware\CheckFrozenAccount::class);
            
            // Add CheckMultiLogin middleware to web group for single session enforcement
            $middleware->appendToGroup('web', \App\Http\Middleware\CheckMultiLogin::class);
            
            // Add CheckLoginRestriction middleware to web group to enforce operational hours.
            if (class_exists(\App\Http\Middleware\CheckLoginRestriction::class)) {
                $middleware->appendToGroup('web', \App\Http\Middleware\CheckLoginRestriction::class);
            }

            // Track request duration only for inventory and mobile endpoints.
            $middleware->appendToGroup('web', \App\Http\Middleware\ApiPerformanceMiddleware::class);
            $middleware->appendToGroup('api', \App\Http\Middleware\ApiPerformanceMiddleware::class);
            
            // Add Sanctum middleware for API authentication
            $middleware->statefulApi();
            
            // Use custom VerifyCsrfToken middleware
            $middleware->validateCsrfTokens(except: [
                'logout',
            ]);
        })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Illuminate\Console\Scheduling\Schedule $schedule): void {
        // Update job schedule expected dates daily at midnight
        $schedule->command('job-schedules:update-expected-dates')
            ->daily()
            ->at('00:00')
            ->description('Update expected dates for job schedules based on service frequency');
        
        // Auto-create contract renewals (dynamic window based on contract duration)
        $schedule->command('contracts:auto-renew')
            ->daily()
            ->at('01:00')
            ->description('Auto-create renewal quotations for contracts approaching expiry');

        // Auto-generate invoices for completed rental periods
        $schedule->command('finance:auto-generate-invoices')
            ->daily()
            ->at('01:00')
            ->description('Auto-generate invoices for rental periods where all jobs are completed');
    })
    ->create();

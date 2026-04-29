<?php

use Illuminate\Support\Facades\Schedule;

// Update job schedule expected dates daily at midnight
Schedule::command('job-schedules:update-expected-dates')
    ->daily()
    ->at('00:00')
    ->description('Update expected dates for job schedules based on service frequency');

// Alternative: Run every hour for more frequent updates
// Schedule::command('job-schedules:update-expected-dates')
//     ->hourly()
//     ->description('Update expected dates for job schedules based on service frequency');

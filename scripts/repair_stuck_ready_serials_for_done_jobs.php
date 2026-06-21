<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\JobSchedule;
use App\Services\Operational\JobMaterialCompletionService;

$apply = in_array('--apply', $argv, true);
$onlyJobs = [];

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--job=')) {
        $onlyJobs = array_values(array_filter(array_map('trim', explode(',', substr($arg, 6)))));
    }
}

if (empty($onlyJobs)) {
    echo "Usage:\n";
    echo "  php scripts/repair_stuck_ready_serials_for_done_jobs.php --job=SBY-CSR/26-07/0006,SBY-CSR/26-08/0004\n";
    echo "  php scripts/repair_stuck_ready_serials_for_done_jobs.php --job=SBY-CSR/26-07/0006,SBY-CSR/26-08/0004 --apply\n";
    exit(1);
}

$dryRunPrefix = $apply ? '[APPLY]' : '[DRY RUN]';
echo "{$dryRunPrefix} Re-finalize completed jobs so issued serial numbers still stuck at ready/on_hand are moved to in_use.\n";
echo "This relies on the batch-serial-resolution fix in InventoryIssuingService (quantity<=1 uses the linked serial_number_id directly).\n\n";

$service = app(JobMaterialCompletionService::class);

foreach ($onlyJobs as $jobNumber) {
    $jobs = JobSchedule::where('job_number', $jobNumber)
        ->whereIn('status', ['done_job', 'completed', 'selesai'])
        ->get();

    if ($jobs->isEmpty()) {
        echo "- {$jobNumber}: no completed JobSchedule found, skipping\n";
        continue;
    }

    foreach ($jobs as $job) {
        echo "- {$jobNumber} (#{$job->id}) status={$job->status}\n";

        if ($apply) {
            $service->finalizeForCompletedJob($job);
        }
    }
}

echo "\n";

if (!$apply) {
    echo "Dry-run complete. No data changed. Add --apply to update server data.\n";
} else {
    echo "Repair applied.\n";
}

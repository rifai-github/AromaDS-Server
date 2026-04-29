<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InventoryIssuingItem;
use App\Models\JobAssignSchedule;
use App\Models\JobSchedule;
use App\Models\MaterialIssue;
use App\Services\Operational\JobMaterialCompletionService;
use Illuminate\Support\Facades\DB;

$dryRun = in_array('--dry-run', $argv, true);
$jobNumber = null;

foreach ($argv as $index => $arg) {
    if ($arg === '--job-number' && isset($argv[$index + 1])) {
        $jobNumber = $argv[$index + 1];
    }
}

$completedStatuses = ['done_job', 'completed', 'selesai'];
$installFreeTypes = ['install_free', 'install free'];

$jobs = JobSchedule::query()
    ->with('jobAdvice')
    ->whereIn('status', $completedStatuses)
    ->when($jobNumber, fn ($query) => $query->where('job_number', $jobNumber))
    ->where(function ($query) use ($installFreeTypes) {
        $query->whereIn(DB::raw('LOWER(COALESCE(type, ""))'), $installFreeTypes)
            ->orWhereHas('jobAdvice', function ($jobAdviceQuery) use ($installFreeTypes) {
                $jobAdviceQuery->whereIn(DB::raw('LOWER(COALESCE(type, ""))'), $installFreeTypes);
            });
    })
    ->orderBy('id')
    ->get()
    ->unique(fn (JobSchedule $job) => $job->job_number ?: 'job-' . $job->id)
    ->values();

if ($jobs->isEmpty()) {
    echo "No completed install_free jobs found.\n";
    exit(0);
}

$service = app(JobMaterialCompletionService::class);
$totalCandidates = 0;
$processedJobs = 0;

echo ($dryRun ? '[DRY RUN] ' : '') . "Found {$jobs->count()} completed install_free job group(s).\n";

foreach ($jobs as $job) {
    $relatedJobs = $job->job_number
        ? JobSchedule::where('job_number', $job->job_number)->get()
        : collect([$job]);

    $jobIds = $relatedJobs->pluck('id')->all();

    $assignmentIds = JobAssignSchedule::withTrashed()
        ->whereIn('job_schedule_id', $jobIds)
        ->pluck('id')
        ->all();

    $issueNumbers = MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function ($query) use ($jobIds) {
        $query->whereIn('job_schedule_id', $jobIds);
    })
        ->pluck('issue_number')
        ->filter()
        ->unique()
        ->all();

    $candidateSerials = InventoryIssuingItem::query()
        ->with(['serialNumber.masterProduct', 'inventoryIssuing'])
        ->whereNotNull('serial_number_id')
        ->where(function ($query) use ($assignmentIds, $issueNumbers) {
            if (!empty($assignmentIds)) {
                $query->whereIn('job_assign_schedule_id', $assignmentIds);
            }

            if (!empty($issueNumbers)) {
                $query->orWhereHas('inventoryIssuing', function ($issuingQuery) use ($issueNumbers) {
                    $issuingQuery->whereIn('reference_no', $issueNumbers);
                });
            }
        })
        ->get()
        ->filter(fn (InventoryIssuingItem $item) => $item->serialNumber
            && in_array($item->serialNumber->status, ['on_hand', 'ready', 'available'], true))
        ->unique('serial_number_id')
        ->values();

    $candidateCount = $candidateSerials->count();
    $totalCandidates += $candidateCount;

    echo "- {$job->job_number} (#{$job->id}): {$candidateCount} serial(s) need update";

    if ($dryRun && $candidateCount > 0) {
        $serialList = $candidateSerials
            ->map(fn (InventoryIssuingItem $item) => $item->serialNumber->serial_number . ' [' . ($item->serialNumber->masterProduct->name ?? '-') . ']')
            ->implode(', ');

        echo " => {$serialList}";
    }

    echo "\n";

    if (!$dryRun) {
        $service->finalizeForCompletedJob($job);
        $processedJobs++;
    }
}

if ($dryRun) {
    echo "Dry run complete. {$totalCandidates} serial(s) would be updated.\n";
    echo "Run without --dry-run to apply changes.\n";
} else {
    echo "Backfill complete. Processed {$processedJobs} job group(s).\n";
}

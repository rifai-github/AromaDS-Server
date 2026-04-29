<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\JobSchedule;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;

$dryRun = in_array('--dry-run', $argv, true);
$onlyJobNumber = null;

foreach ($argv as $index => $arg) {
    if ($arg === '--job-number' && isset($argv[$index + 1])) {
        $onlyJobNumber = $argv[$index + 1];
    }
}

$jobs = JobSchedule::query()
    ->when($onlyJobNumber, fn ($query) => $query->where('job_number', $onlyJobNumber))
    ->where(function ($query) {
        $query->where('job_number', 'like', '%-RE/%')
            ->orWhere(function ($removeFreeQuery) {
                $removeFreeQuery->whereIn(DB::raw('LOWER(COALESCE(type, ""))'), ['remove_free', 'remove free'])
                    ->where('job_number', 'not like', '%-RF/%');
            })
            ->orWhere(function ($removeQuery) {
                $removeQuery->whereIn(DB::raw('LOWER(COALESCE(type, ""))'), ['remove', 'removal'])
                    ->where('job_number', 'not like', '%-RV/%');
            });
    })
    ->orderBy('job_number')
    ->orderBy('type')
    ->orderBy('id')
    ->get();

if ($jobs->isEmpty()) {
    echo "No remove job number prefixes need fixing.\n";
    exit(0);
}

$groups = $jobs->groupBy(function (JobSchedule $job) {
    $type = strtolower(trim((string) $job->type));
    $documentType = in_array($type, ['remove_free', 'remove free'], true) ? 'remove_free' : 'remove';

    return ($job->job_number ?: 'job-' . $job->id) . '|' . $documentType;
});

$documentNumberService = app(DocumentNumberService::class);

echo ($dryRun ? '[DRY RUN] ' : '') . "Found {$jobs->count()} row(s) in {$groups->count()} group(s).\n";

foreach ($groups as $groupKey => $groupJobs) {
    /** @var JobSchedule $representative */
    $representative = $groupJobs->first();
    $type = strtolower(trim((string) $representative->type));
    $documentType = in_array($type, ['remove_free', 'remove free'], true) ? 'remove_free' : 'remove';
    $newNumber = $documentNumberService->generate(
        $documentType,
        null,
        $representative->building_id,
        $representative->contract_id,
        $representative->jobAdvice?->quotation_id
    );

    $ids = $groupJobs->pluck('id')->all();
    echo "- {$representative->job_number} ({$documentType}) ids=[" . implode(',', $ids) . "] => {$newNumber}\n";

    if (!$dryRun) {
        JobSchedule::whereIn('id', $ids)->update([
            'job_number' => $newNumber,
            'updated_at' => now(),
        ]);
    }
}

echo $dryRun
    ? "Dry run complete. Run without --dry-run to apply changes.\n"
    : "Remove job number prefix fix complete.\n";

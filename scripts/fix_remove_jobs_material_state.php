<?php

use App\Models\JobAssignSchedule;
use App\Models\JobSchedule;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dryRun = in_array('--dry-run', $argv, true);
$jobNumber = null;

foreach ($argv as $index => $arg) {
    if ($arg === '--job-number' && isset($argv[$index + 1])) {
        $jobNumber = $argv[$index + 1];
    } elseif (str_starts_with($arg, '--job-number=')) {
        $jobNumber = substr($arg, strlen('--job-number='));
    }
}

$query = JobSchedule::query()
    ->whereIn('type', ['remove', 'remove_free', 'remove free'])
    ->where(function ($statusQuery) {
        $statusQuery->where('material_checked', false)
            ->orWhereNull('material_checked')
            ->orWhereIn('status', ['assign_material', 'barang_dipersiapkan']);
    });

if ($jobNumber) {
    $query->where('job_number', $jobNumber);
}

$jobs = $query->orderBy('id')->get();

echo ($dryRun ? '[DRY-RUN] ' : '[APPLY] ') . "Found {$jobs->count()} remove job(s) to repair.\n";

foreach ($jobs as $job) {
    $targetStatus = in_array($job->status, ['assign_material', 'barang_dipersiapkan'], true)
        ? 'scheduled'
        : $job->status;

    echo "- {$job->job_number} id={$job->id} type={$job->type} status {$job->status} => {$targetStatus}, material_checked=true\n";

    if ($dryRun) {
        continue;
    }

    DB::transaction(function () use ($job, $targetStatus) {
        $job->update([
            'status' => $targetStatus,
            'material_checked' => true,
            'material_checked_at' => $job->material_checked_at ?: now(),
            'updated_by' => 1,
        ]);

        JobAssignSchedule::where('job_schedule_id', $job->id)
            ->whereNull('team_id')
            ->where('notes', 'like', '%Material Assign%')
            ->whereDoesntHave('jobAssignMaterialIssues')
            ->update([
                'status' => 'cancelled',
                'updated_by' => 1,
            ]);
    });
}

echo $dryRun
    ? "Dry-run selesai. Jalankan tanpa --dry-run untuk apply.\n"
    : "Apply selesai.\n";


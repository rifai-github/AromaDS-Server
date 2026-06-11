<?php

use App\Models\InventoryIssuing;
use App\Models\JobSchedule;
use App\Models\MaterialIssue;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$options = getopt('', [
    'job::',
    'origin::',
    'apply',
    'include-advanced',
    'limit::',
]);

$jobNumber = $options['job'] ?? null;
$originJobNumber = $options['origin'] ?? null;
$apply = array_key_exists('apply', $options);
$includeAdvanced = array_key_exists('include-advanced', $options);
$limit = isset($options['limit']) ? max(1, (int) $options['limit']) : 100;

if (!$jobNumber && !$originJobNumber) {
    echo "Usage:\n";
    echo "  php scripts/repair_partial_followup_material_state.php --job=SBY-CSR/26-06/0003\n";
    echo "  php scripts/repair_partial_followup_material_state.php --origin=SBY-CSR/26-06/0001\n";
    echo "  php scripts/repair_partial_followup_material_state.php --origin=SBY-CSR/26-06/0001 --apply\n";
    echo "  php scripts/repair_partial_followup_material_state.php --job=SBY-CSR/26-06/0003 --include-advanced --apply\n\n";
    echo "Default mode is dry-run. --apply only resets stale material_checked/material_checked_at.\n";
    echo "Advanced/completed jobs are skipped unless --include-advanced is passed; status/rooms/reports are never rolled back by this script.\n";
    exit(1);
}

$safeStatuses = [
    'new_job',
    'scheduled',
    'assign_team',
    'assign_material',
    'barang_dipersiapkan',
    'barang_siap_diambil',
];

$query = JobSchedule::query()
    ->where('internal_notes', 'like', 'Lanjutan dari Job %')
    ->where(function ($materialQuery) {
        $materialQuery->where('material_checked', true)
            ->orWhereNotNull('material_checked_at');
    })
    ->orderBy('id')
    ->limit($limit);

if ($jobNumber) {
    $query->where('job_number', $jobNumber);
}

if ($originJobNumber) {
    $query->where('internal_notes', 'like', 'Lanjutan dari Job ' . $originJobNumber . '%');
}

$jobs = $query->get();

echo ($apply ? '[APPLY]' : '[DRY RUN]') . " Partial follow-up material-state repair\n";
echo "Found {$jobs->count()} stale-flag candidate(s).\n\n";

$repairable = 0;
$skipped = 0;
$applied = 0;

foreach ($jobs as $job) {
    $materialIssues = materialIssuesForJob($job);
    $issueNumbers = $materialIssues->pluck('issue_number')->filter()->unique()->values();
    $issuings = $issueNumbers->isEmpty()
        ? collect()
        : InventoryIssuing::whereIn('reference_no', $issueNumbers)->get();

    $hasMaterialIssue = $materialIssues->isNotEmpty();
    $pickupVerified = $issuings->contains(fn (InventoryIssuing $issuing) => in_array($issuing->status, ['sent', 'received'], true));
    $isAdvanced = !in_array($job->status, $safeStatuses, true);
    $needsRepair = $hasMaterialIssue && !$pickupVerified && ((bool) $job->material_checked || $job->material_checked_at);

    $issueText = $issueNumbers->isEmpty() ? '-' : $issueNumbers->implode(', ');
    $issuingText = $issuings->isEmpty()
        ? '-'
        : $issuings
            ->map(fn (InventoryIssuing $issuing) => "{$issuing->issuing_number}:{$issuing->status}")
            ->implode(', ');

    echo "- #{$job->id} {$job->job_number} status={$job->status} material_checked=" . (int) (bool) $job->material_checked;
    echo " material_checked_at=" . ($job->material_checked_at ?: '-') . "\n";
    echo "  origin=" . originJobNumber($job) . " issues={$issueText} issuings={$issuingText}\n";

    if (!$hasMaterialIssue) {
        $skipped++;
        echo "  skip: no material issue linked to this follow-up job.\n";
        continue;
    }

    if ($pickupVerified) {
        $skipped++;
        echo "  skip: pickup already verified by WI sent/received.\n";
        continue;
    }

    if (!$needsRepair) {
        $skipped++;
        echo "  skip: material flag already clean.\n";
        continue;
    }

    if ($isAdvanced && !$includeAdvanced) {
        $skipped++;
        echo "  manual_review: job already advanced/completed. Re-run with --include-advanced to reset only material flag, or inspect status/rooms/reports manually.\n";
        continue;
    }

    $repairable++;
    echo "  repair: material_checked true/stale but WI has not reached sent/received; set material_checked=false, material_checked_at=null.\n";

    if (!$apply) {
        continue;
    }

    DB::transaction(function () use ($job) {
        $job->update([
            'material_checked' => false,
            'material_checked_at' => null,
            'updated_by' => auth()->id() ?: $job->updated_by,
        ]);
    });

    $applied++;
    echo "  applied.\n";
}

echo "\nSummary: repairable={$repairable}, skipped={$skipped}, applied={$applied}\n";
echo $apply
    ? "Apply complete. Status/rooms/reports were not rolled back.\n"
    : "Dry-run complete. Add --apply to reset repairable material flags.\n";

function materialIssuesForJob(JobSchedule $job)
{
    return MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function ($query) use ($job) {
        $query->where('job_schedule_id', $job->id);
    })->get();
}

function originJobNumber(JobSchedule $job): string
{
    if (preg_match('/Lanjutan dari Job\s+([^\s(]+)/', (string) $job->internal_notes, $matches)) {
        return $matches[1];
    }

    return '-';
}

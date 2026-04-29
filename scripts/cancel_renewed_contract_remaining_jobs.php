<?php

use App\Models\Contract;
use App\Models\JobSchedule;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$options = getopt('', ['old:', 'new:', 'all', 'apply', 'help']);

if (isset($options['help'])) {
    echo <<<TXT
Cancel remaining non-final job schedules from renewed source contracts.

Usage:
  php scripts/cancel_renewed_contract_remaining_jobs.php --old=MKS-CA/26-03/0002 --new=MKS-CA/26-03/0003
  php scripts/cancel_renewed_contract_remaining_jobs.php --old=MKS-CA/26-03/0002 --new=MKS-CA/26-03/0003 --apply
  php scripts/cancel_renewed_contract_remaining_jobs.php --all
  php scripts/cancel_renewed_contract_remaining_jobs.php --all --apply

Options:
  --old    Old/source contract number.
  --new    New/renewal contract number.
  --all    Scan all renewal contracts with quotation.existing_contract_id.
  --apply  Persist changes. Without this option, the script is dry-run.

TXT;
    exit(0);
}

$apply = isset($options['apply']);
$pairs = [];

if (isset($options['all'])) {
    $renewalContracts = Contract::query()
        ->with('quotation')
        ->whereHas('quotation', function ($query) {
            $query->where('quotation_type', 'renewal')
                ->whereNotNull('existing_contract_id');
        })
        ->orderBy('id')
        ->get();

    foreach ($renewalContracts as $newContract) {
        $oldContract = Contract::find($newContract->quotation->existing_contract_id);
        if ($oldContract) {
            $pairs[] = [$oldContract, $newContract];
        }
    }
} else {
    $oldNumber = $options['old'] ?? null;
    $newNumber = $options['new'] ?? null;

    if (!$oldNumber || !$newNumber) {
        fwrite(STDERR, "Missing --old/--new. Use --help for examples.\n");
        exit(1);
    }

    $oldContract = Contract::where('contract_number', $oldNumber)->first();
    $newContract = Contract::with('quotation')->where('contract_number', $newNumber)->first();

    if (!$oldContract || !$newContract) {
        fwrite(STDERR, "Contract not found. old={$oldNumber}, new={$newNumber}\n");
        exit(1);
    }

    $pairs[] = [$oldContract, $newContract];
}

if (empty($pairs)) {
    echo "No renewal contract pairs found.\n";
    exit(0);
}

$mode = $apply ? 'APPLY' : 'DRY-RUN';
echo "[{$mode}] Found " . count($pairs) . " renewal pair(s).\n";

$totalAffected = 0;

foreach ($pairs as [$oldContract, $newContract]) {
    $quotation = $newContract->quotation;
    $isExpectedRenewal = $quotation
        && $quotation->quotation_type === 'renewal'
        && (int) $quotation->existing_contract_id === (int) $oldContract->id;

    if (!$isExpectedRenewal) {
        echo "\nSKIP {$oldContract->contract_number} -> {$newContract->contract_number}: renewal link does not match.\n";
        continue;
    }

    $jobs = remainingJobsForContract($oldContract);
    $count = $jobs->count();
    $totalAffected += $count;

    echo "\n{$oldContract->contract_number} -> {$newContract->contract_number}: {$count} job(s) to cancel.\n";

    foreach ($jobs as $job) {
        $jobNumber = $job->job_number ?: "(no job number / id {$job->id})";
        $date = optional($job->schedule_date)->format('Y-m-d') ?: '-';
        echo "  - {$jobNumber} | id={$job->id} | type={$job->type} | status={$job->status} | date={$date}\n";
    }

    if ($apply && $count > 0) {
        $cancelled = $oldContract->cancelRemainingJobSchedules(
            "Contract di-renewal ke {$newContract->contract_number}"
        );
        echo "  Applied: cancelled {$cancelled} job(s).\n";
    }
}

echo "\nDone. {$mode} total target job(s): {$totalAffected}.\n";

if (!$apply) {
    echo "No data changed. Re-run with --apply to persist.\n";
}

function remainingJobsForContract(Contract $contract)
{
    $contractNumber = $contract->contract_number;
    $finalStatuses = array_unique(array_merge(Contract::finalJobScheduleStatuses(), ['suspend', 'dpf']));

    return JobSchedule::query()
        ->where(function ($query) use ($contract, $contractNumber) {
            $query->whereHas('jobAdvice', function ($jobAdviceQuery) use ($contract) {
                $jobAdviceQuery->where('contract_id', $contract->id);
            });

            if ($contractNumber) {
                $query->orWhere('contract_number', $contractNumber);
            }
        })
        ->where(function ($query) use ($finalStatuses) {
            $query->whereNull('status')
                ->orWhereNotIn('status', $finalStatuses);
        })
        ->orderBy('schedule_date')
        ->orderBy('id')
        ->get(['id', 'job_number', 'type', 'status', 'schedule_date', 'contract_number', 'job_advice_id']);
}

<?php

namespace App\Console\Commands;

use App\Models\JobSchedule;
use Illuminate\Console\Command;

class RepairStalePartialCompletionSourceJobs extends Command
{
    protected $signature = 'jobs:repair-stale-partial-source {contractNumber? : Limit to a single contract number} {--apply : Persist the repair}';

    protected $description = "Reconcile 'meninggalkan_lokasi' jobs whose partial-completion follow-up jobs already reached a terminal status, so they stop blocking MOM14 unfinished-job validation.";

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $contractNumber = $this->argument('contractNumber');

        $query = JobSchedule::where('status', 'meninggalkan_lokasi');
        if ($contractNumber) {
            $query->where('contract_number', $contractNumber);
        }

        $staleJobs = $query->get(['id', 'job_number', 'contract_number']);

        if ($staleJobs->isEmpty()) {
            $this->info('No meninggalkan_lokasi jobs found.');

            return self::SUCCESS;
        }

        $this->info(($apply ? 'APPLY' : 'DRY RUN').' reconciliation for '.$staleJobs->count().' candidate job(s)');

        $contractNumbers = $staleJobs->pluck('contract_number')->unique()->filter();

        foreach ($contractNumbers as $number) {
            if ($apply) {
                JobSchedule::reconcilePartialCompletionSourceJobs($number);
            } else {
                $this->line("- would check contract {$number}");
            }
        }

        if ($apply) {
            $stillStale = JobSchedule::where('status', 'meninggalkan_lokasi')
                ->when($contractNumber, fn ($q) => $q->where('contract_number', $contractNumber))
                ->get(['job_number']);

            $resolvedCount = $staleJobs->count() - $stillStale->count();
            $this->info("Resolved {$resolvedCount} job(s). Still stale: ".$stillStale->pluck('job_number')->implode(', '));
        } else {
            $this->warn('Dry run only. Re-run with --apply to persist changes.');
        }

        return self::SUCCESS;
    }
}

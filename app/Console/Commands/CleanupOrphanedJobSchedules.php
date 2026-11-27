<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use App\Services\Operational\InstallFreeContractRoomScope;
use App\Services\Operational\OrphanedJobScheduleCleaner;
use Illuminate\Console\Command;

/**
 * QA 27 Nov 2026 (Customer Testing Indo): job yang belum dimulai tertinggal setelah JA/kontrak
 * di-unpost/dihapus. Kode sudah diperbaiki (OrphanedJobScheduleCleaner); perintah ini membersihkan
 * data lama dengan aturan yang sama:
 *
 *  - ja_removed       : JA-nya sudah dihapus atau kembali draft/cancelled/rejected.
 *  - contract_removed : membawa nomor kontrak yang sudah dihapus/di-draft dan bukan milik JA aktif
 *                       kontrak itu (Job Check aktivasi kontrak di JA trial).
 *  - trial_taken_over : job lanjutan trial (Install Free "Lanjutan dari Job ...") yang semua
 *                       ruangannya sudah masuk kontrak dari nomor SQ yang sama.
 *
 * Hanya job yang belum dimulai (scheduled/new_job, tanpa BA, tanpa penugasan tim, tanpa ruangan
 * selesai). Hapus = soft delete, sama seperti unpost/hapus JA. Dry-run default.
 */
class CleanupOrphanedJobSchedules extends Command
{
    protected $signature = 'job-schedules:cleanup-orphaned
        {--apply : Tulis perubahan (default dry-run)}';

    protected $description = 'Hapus (soft delete) job belum dimulai yang JA/kontraknya sudah di-unpost/dihapus (dry-run default)';

    public function handle(OrphanedJobScheduleCleaner $cleaner, InstallFreeContractRoomScope $roomScope): int
    {
        $apply = (bool) $this->option('apply');

        /** @var array<int, array{job: JobSchedule, reason: string, note: string}> $candidates */
        $candidates = [];
        $add = function (JobSchedule $job, string $reason, string $note) use (&$candidates, $cleaner) {
            if (isset($candidates[$job->id]) || ! $cleaner->isNotStarted($job)) {
                return;
            }
            $candidates[$job->id] = ['job' => $job, 'reason' => $reason, 'note' => $note];
        };

        // 1. JA sudah dihapus / kembali draft-cancelled-rejected.
        $deadJobAdvices = JobAdvice::withTrashed()
            ->where(fn ($q) => $q->whereNotNull('deleted_at')->orWhereIn('status', ['draft', 'cancelled', 'rejected']))
            ->whereIn('id', JobSchedule::whereIn('status', OrphanedJobScheduleCleaner::NOT_STARTED_STATUSES)->select('job_advice_id'))
            ->get(['id', 'job_advice_number', 'status', 'deleted_at']);
        foreach ($deadJobAdvices as $jobAdvice) {
            $state = $jobAdvice->deleted_at ? 'dihapus' : $jobAdvice->status;
            JobSchedule::where('job_advice_id', $jobAdvice->id)
                ->whereIn('status', OrphanedJobScheduleCleaner::NOT_STARTED_STATUSES)
                ->get()
                ->each(fn ($job) => $add($job, 'ja_removed', "Job Advice {$jobAdvice->job_advice_number} {$state}"));
        }

        // 2. Nomor kontrak yang tidak lagi punya kontrak hidup non-draft.
        $contractNumbers = JobSchedule::whereIn('status', OrphanedJobScheduleCleaner::NOT_STARTED_STATUSES)
            ->whereNotNull('contract_number')
            ->where('contract_number', '!=', '')
            ->distinct()
            ->pluck('contract_number');
        foreach ($contractNumbers as $contractNumber) {
            $liveContract = Contract::where('contract_number', $contractNumber)
                ->where(fn ($q) => $q->whereNull('contract_status')->orWhere('contract_status', '!=', 'draft'))
                ->exists();
            if ($liveContract) {
                continue;
            }

            $contract = Contract::where('contract_number', $contractNumber)->latest('id')->first()
                ?? Contract::onlyTrashed()->where('contract_number', $contractNumber)->latest('id')->first();
            if (! $contract) {
                continue; // nomor tanpa kontrak sama sekali (mis. data impor) — bukan urusan perintah ini
            }

            $state = $contract->trashed() ? 'dihapus' : 'Draft';
            foreach ($cleaner->contractDependentJobs($contract) as $job) {
                $add($job, 'contract_removed', "Contract {$contractNumber} {$state}");
            }
        }

        // 3. Job lanjutan trial yang ruangannya sudah diambil alih kontrak.
        $followUpQuotationNumbers = JobSchedule::whereIn('type', ['install_free', 'install free'])
            ->whereIn('status', OrphanedJobScheduleCleaner::NOT_STARTED_STATUSES)
            ->where('internal_notes', 'like', OrphanedJobScheduleCleaner::FOLLOW_UP_NOTE_PREFIX.'%')
            ->whereNotNull('quotation_number')
            ->distinct()
            ->pluck('quotation_number');
        foreach ($followUpQuotationNumbers as $quotationNumber) {
            $contractedRoomIds = $roomScope->contractedRoomIdsForQuotationNumber($quotationNumber);
            foreach ($cleaner->trialFollowUpsTakenOverByContract($quotationNumber, $contractedRoomIds) as $job) {
                $add($job, 'trial_taken_over', "Ruangan trial {$quotationNumber} sudah masuk kontrak");
            }
        }

        if (empty($candidates)) {
            $this->info('Tidak ada job yatim yang perlu dibersihkan.');

            return self::SUCCESS;
        }

        ksort($candidates);
        $rows = [];
        foreach ($candidates as $candidate) {
            $job = $candidate['job'];
            $rows[] = [
                $job->id,
                $job->job_number ?: '-',
                $job->type,
                $job->status,
                $job->period ?? '-',
                $job->job_advice_id ?? '-',
                $job->contract_number ?: '-',
                $job->quotation_number ?: '-',
                $job->getRawOriginal('room_name') ?: ($job->getRawOriginal('room_id') ?? '-'),
                $candidate['reason'],
            ];

            if ($apply) {
                $cleaner->removeJob($job, $candidate['note'].' (job-schedules:cleanup-orphaned)');
            }
        }

        $this->table(['Job ID', 'No. Job', 'Tipe', 'Status', 'Periode', 'JA ID', 'Kontrak', 'SQ', 'Ruangan', 'Alasan'], $rows);

        $byReason = collect($candidates)->countBy('reason')->map(fn ($n, $reason) => "{$reason}={$n}")->implode(', ');
        $this->info(($apply ? 'Dihapus (soft delete)' : 'Akan dihapus (soft delete)').': '.count($candidates)." job ({$byReason}).");
        if (! $apply) {
            $this->warn('Dry-run: belum ada yang ditulis. Jalankan dengan --apply.');
        }

        return self::SUCCESS;
    }
}

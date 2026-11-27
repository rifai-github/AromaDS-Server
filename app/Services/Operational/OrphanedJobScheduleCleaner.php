<?php

namespace App\Services\Operational;

use App\Models\Contract;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * QA 27 Nov 2026 (Customer Testing Indo, BLP-SQ/26-11/0133): job yang lahir KARENA sebuah kontrak
 * tetapi tidak menempel ke JA milik kontrak itu tertinggal setelah JA di-unpost/dihapus dan kontrak
 * di-unpost/dihapus.
 *
 *  - Job Check Unit Only yang dibuat saat kontrak disetujui (ContractOnWallCsrService →
 *    generateUnitOnlyChecksForCompletedInstalls) menempel ke JA TRIAL (contract_id NULL) dengan
 *    contract_number kontrak. Unpost/hapus JA kontrak tidak menyentuhnya, dan unpost kontrak hanya
 *    memeriksa JA ber-contract_id, jadi 46 Job Check BLP-CA/26-11/0005 (job 59939-59984) tertinggal.
 *  - Job lanjutan trial ("Lanjutan dari Job ..., Pekerjaan tidak selesai") untuk ruangan yang sudah
 *    diambil alih kontrak: pemasangannya kini lewat JA Install kontrak, jadi job lanjutan trial itu
 *    tidak terpakai lagi dan membuat ruangan menggantung (job 59937, ADS-IF/26-11/0002 Ruang-4).
 *
 * Hanya job yang BELUM DIMULAI yang dihapus (soft delete, sama seperti unpost/hapus JA): status
 * scheduled/new_job, tanpa BA, tanpa penugasan tim aktif, tanpa ruangan selesai. Job yang sudah
 * berjalan tidak pernah disentuh; pemanggil melaporkannya.
 */
class OrphanedJobScheduleCleaner
{
    public const NOT_STARTED_STATUSES = ['scheduled', 'new_job'];

    public const FOLLOW_UP_NOTE_PREFIX = 'Lanjutan dari Job ';

    private const TRIAL_INSTALL_TYPES = ['install_free', 'install free'];

    /** @var array<string, bool> */
    private array $tableCache = [];

    public function __construct(private InstallFreeContractRoomScope $roomScope)
    {
    }

    public function isNotStarted(JobSchedule $job): bool
    {
        if (! in_array(strtolower(trim((string) $job->status)), self::NOT_STARTED_STATUSES, true)) {
            return false;
        }

        if ($job->getAttribute('ba_date') || $job->getAttribute('started_at') || $job->getAttribute('completed_at')) {
            return false;
        }

        if ($this->hasTable('job_assign_schedules')
            && DB::table('job_assign_schedules')
                ->where('job_schedule_id', $job->id)
                ->when(Schema::hasColumn('job_assign_schedules', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->where(fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'cancelled'))
                ->exists()) {
            return false;
        }

        if ($this->hasTable('job_schedule_rooms')
            && JobScheduleRoom::where('job_schedule_id', $job->id)
                ->where('status', JobScheduleRoom::STATUS_COMPLETED)
                ->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Job yang masih membawa nomor kontrak ini tetapi bukan milik JA aktif kontrak itu (Job Check
     * dari aktivasi kontrak di JA trial, atau sisa JA kontrak yang sudah dihapus/di-draft).
     *
     * @return Collection<int, JobSchedule>
     */
    public function contractDependentJobs(Contract $contract): Collection
    {
        $contractNumber = trim((string) $contract->contract_number);
        if ($contractNumber === '' || ! $this->hasTable('job_schedules')) {
            return collect();
        }

        // Nomor yang sama masih dipakai kontrak hidup lain → job-nya milik kontrak itu.
        if (Contract::where('contract_number', $contractNumber)->where('id', '!=', $contract->id)->exists()) {
            return collect();
        }

        $activeOwnJobAdviceIds = $this->hasTable('job_advices')
            ? JobAdvice::where('contract_id', $contract->id)
                ->whereNotIn('status', ['draft', 'cancelled', 'rejected'])
                ->pluck('id')
                ->all()
            : [];

        return JobSchedule::where('contract_number', $contractNumber)
            ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', ['cancelled', 'canceled']))
            ->when(! empty($activeOwnJobAdviceIds), function ($query) use ($activeOwnJobAdviceIds) {
                $query->where(fn ($q) => $q->whereNull('job_advice_id')->orWhereNotIn('job_advice_id', $activeOwnJobAdviceIds));
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * Dipanggil saat kontrak di-unpost ke draft atau dihapus.
     *
     * @return array{removed: array<int, int>, kept: array<int, int>}
     */
    public function cleanupForContract(Contract $contract, string $reason): array
    {
        $removed = [];
        $kept = [];

        foreach ($this->contractDependentJobs($contract) as $job) {
            if ($this->isNotStarted($job)) {
                $this->removeJob($job, $reason);
                $removed[] = (int) $job->id;
            } else {
                $kept[] = (int) $job->id;
            }
        }

        if (! empty($removed) || ! empty($kept)) {
            Log::info('Orphaned job schedules cleaned for contract', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'removed' => $removed,
                'kept_started' => $kept,
            ]);
        }

        return ['removed' => $removed, 'kept' => $kept];
    }

    /**
     * Job lanjutan trial (Install Free "Lanjutan dari Job ...") yang semua ruangannya sudah masuk
     * kontrak dari nomor SQ yang sama. Kontrak menggantikan pemasangan trial yang belum selesai.
     *
     * @param  Collection<int, int>  $contractedRoomIds  room_id fisik yang sudah masuk kontrak
     * @return Collection<int, JobSchedule>
     */
    public function trialFollowUpsTakenOverByContract(?string $quotationNumber, Collection $contractedRoomIds): Collection
    {
        if (! $quotationNumber || $contractedRoomIds->isEmpty() || ! $this->hasTable('job_schedules')) {
            return collect();
        }

        $contractedRoomIds = $contractedRoomIds->map(fn ($id) => (int) $id);

        return JobSchedule::whereIn('type', self::TRIAL_INSTALL_TYPES)
            ->where('quotation_number', $quotationNumber)
            ->whereIn('status', self::NOT_STARTED_STATUSES)
            ->where('internal_notes', 'like', self::FOLLOW_UP_NOTE_PREFIX.'%')
            ->orderBy('id')
            ->get()
            ->filter(function (JobSchedule $job) use ($contractedRoomIds) {
                $roomIds = $this->physicalRoomIds($job);

                return $roomIds->isNotEmpty()
                    && $roomIds->every(fn ($id) => $id !== null && $contractedRoomIds->contains($id));
            })
            ->values();
    }

    /**
     * Dipanggil saat kontrak dibuat dari SQ (sejajar pembatalan Remove Free per ruangan kontrak).
     *
     * @return array<int, int> id job yang dihapus
     */
    public function removeTrialFollowUpsTakenOverByContract(Contract $contract, string $reason): array
    {
        $contract->loadMissing('quotation');

        $removed = [];
        $jobs = $this->trialFollowUpsTakenOverByContract(
            $contract->quotation?->quotation_number,
            $this->roomScope->contractRoomIds($contract)
        );

        foreach ($jobs as $job) {
            if (! $this->isNotStarted($job)) {
                continue;
            }

            $this->removeJob($job, $reason);
            $removed[] = (int) $job->id;
        }

        if (! empty($removed)) {
            Log::info('Trial follow-up jobs removed because their rooms were taken over by a contract', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'removed' => $removed,
            ]);
        }

        return $removed;
    }

    /**
     * Soft delete job beserta baris ruangan/rental/penugasan ruangannya (konvensi unpost/hapus JA),
     * dan lepas penunjuk JA room yang masih mengarah ke job ini.
     */
    public function removeJob(JobSchedule $job, string $reason): void
    {
        DB::transaction(function () use ($job, $reason) {
            $userId = Auth::id();
            $roomIds = $this->hasTable('job_schedule_rooms')
                ? JobScheduleRoom::where('job_schedule_id', $job->id)->pluck('id')->all()
                : [];

            if (! empty($roomIds) && $this->hasTable('job_schedule_room_rentals')) {
                \App\Models\JobScheduleRoomRental::whereIn('job_schedule_room_id', $roomIds)->delete();
            }
            if (! empty($roomIds)) {
                JobScheduleRoom::whereIn('id', $roomIds)->delete();
            }
            if ($this->hasTable('job_schedule_room_assignments')) {
                \App\Models\JobScheduleRoomAssignment::where('job_schedule_id', $job->id)->delete();
            }

            if ($this->hasTable('job_advice_rooms')) {
                foreach (['install_job_schedule_id', 'service_job_schedule_id', 'remove_job_schedule_id'] as $column) {
                    if (Schema::hasColumn('job_advice_rooms', $column)) {
                        DB::table('job_advice_rooms')->where($column, $job->id)->update([$column => null]);
                    }
                }
            }

            $job->internal_notes = trim(($job->internal_notes ? $job->internal_notes."\n" : '')
                ."[AUTO-REMOVED] {$reason} pada ".now()->format('d/m/Y H:i'));
            if ($userId && Schema::hasColumn('job_schedules', 'updated_by')) {
                $job->updated_by = $userId;
            }
            $job->save();
            $job->delete();
        });
    }

    /**
     * @return Collection<int, int|null>
     */
    private function physicalRoomIds(JobSchedule $job): Collection
    {
        $rooms = JobScheduleRoom::with('jobAdviceRoom.contractRoom', 'jobAdviceRoom.quotationRoom')
            ->where('job_schedule_id', $job->id)
            ->get();

        if ($rooms->isEmpty()) {
            return $job->room_id ? collect([(int) $job->room_id]) : collect();
        }

        return $rooms->map(fn ($room) => $this->roomScope->physicalRoomId($room))->values();
    }

    private function hasTable(string $table): bool
    {
        return $this->tableCache[$table] ??= Schema::hasTable($table);
    }
}

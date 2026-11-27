<?php

namespace App\Services\Finance;

use App\Models\Building;
use App\Models\Contract;
use App\Models\ContractFile;
use App\Models\Finance\BillingGroup;
use App\Models\Invoice;
use App\Models\InvoiceFile;
use App\Models\JobSchedule;
use App\Models\JobScheduleBaFile;
use App\Models\JobScheduleRoom;
use App\Services\DocumentNumberService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class InvoiceGenerationService
{
    private const BILLABLE_COMPLETED_STATUSES = ['completed', 'done_job', 'dpf'];

    private const NON_BILLABLE_JOB_STATUSES = ['cancelled', 'suspend', 'meninggalkan_lokasi', 'undone'];

    private const NON_BILLABLE_ROOM_STATUSES = ['cancelled', 'suspend', 'undone'];

    private const NON_BILLABLE_JOB_TYPES = ['install_free', 'install free', 'remove_free', 'remove free'];

    // Job types whose invoice-period membership is decided by their own sequence counter
    // (job_schedules.period, i.e. the "P.Invoice" value in the Job Schedule list / see
    // JobSchedule::getInvoicePeriodAttribute()) rather than the calendar schedule_date
    // window — see resolveInvoiceTriggerJobIds().
    private const RECURRING_INVOICE_JOB_TYPES = ['service', 'service_routine', 'service_first'];

    protected $documentNumberService;

    public function __construct(DocumentNumberService $documentNumberService)
    {
        $this->documentNumberService = $documentNumberService;
    }

    /**
     * Auto-generate invoice for completed jobs in rental period
     */
    public function autoGenerateInvoiceForRentalPeriod(int $contractId, string $rentalPeriod, Carbon $periodStart, Carbon $periodEnd): array
    {
        try {
            DB::beginTransaction();

            $contract = Contract::with(['customer', 'contractRentals', 'billingGroup', 'billingGroups.buildings', 'contractRooms.room', 'quotation'])
                ->whereKey($contractId)
                ->lockForUpdate()
                ->firstOrFail();

            // NEW: Check if invoice generation is on hold for this contract
            if ($contract->hold_invoice) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Invoice generation is on hold for this contract (Hold Invoice is active).',
                    'hold_invoice' => true,
                ];
            }

            $periodNumber = $this->parsePeriodNumber($rentalPeriod);
            $activeBillingGroups = $contract->billingGroups->where('is_active', true)->values();

            // Check if the invoice trigger jobs for this rental period are completed.
            // Before Service: IR/install BA triggers invoice.
            // After Service: CSR/service BA triggers invoice.
            // Klien 27 Nov 2026 (kondisi 4c): dengan lebih dari satu Billing Group, kesiapan
            // dinilai per Billing Group (generateInvoicesPerBillingGroup); di sini cukup ada
            // job yang siap.
            $allJobsCompleted = $activeBillingGroups->count() > 1
                ? $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber)->isNotEmpty()
                : $this->checkInvoiceTriggerJobsCompletedInPeriod($contract, $periodStart, $periodEnd, $periodNumber);

            if (! $allJobsCompleted) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Invoice trigger jobs in the rental period are not completed with BA date yet',
                    'completed_jobs' => $this->getCompletedJobsCount($contract, $periodStart, $periodEnd, $periodNumber),
                    'total_jobs' => $this->getTotalJobsCount($contract, $periodStart, $periodEnd, $periodNumber),
                    'invoice_timing' => $this->getInvoiceTiming($contract),
                ];
            }

            // NEW: Check BA Files Supported
            if ($contract->ba_files_supported) {
                if (! $this->checkAllJobsHaveVerifiedBaFiles($contract, $periodStart, $periodEnd, $periodNumber)) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => 'Invoice cannot be generated: BA Files are required (BA Files Supported is ON) but some jobs do not have verified BA files.',
                    ];
                }
            }

            // A contract can legitimately have more than one active BillingGroup (each
            // scoped to different buildings/rooms). When that's the case, split into
            // one invoice per billing group instead of a single contract-wide invoice.
            // Zero or exactly one active billing group keeps the original single-invoice
            // behavior below untouched.
            if ($activeBillingGroups->count() > 1) {
                $result = $this->generateInvoicesPerBillingGroup($contract, $activeBillingGroups, $rentalPeriod, $periodStart, $periodEnd);

                DB::commit();

                return $result;
            }

            // Check if invoice already exists for this period
            $existingInvoice = $this->checkExistingInvoice($contractId, $contract->contract_number, $rentalPeriod, $periodStart, $periodEnd);
            if ($existingInvoice) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Invoice already exists for this rental period',
                    'existing_invoice' => $existingInvoice,
                ];
            }

            // Generate invoice
            $invoice = $this->createInvoiceForRentalPeriod($contract, $rentalPeriod, $periodStart, $periodEnd);

            // Create invoice details based on completed jobs
            $completedJobs = $this->createInvoiceDetailsFromJobs($invoice, $contract, $periodStart, $periodEnd, [], $periodNumber);

            // Attach approved BA files as supporting documents
            $this->attachApprovedBaFiles($invoice, $completedJobs);

            // Attach verified contract files as supporting documents
            $this->attachVerifiedContractFiles($invoice, $contract);

            // Update invoice totals
            $this->updateInvoiceTotals($invoice);

            DB::commit();

            Log::info("Auto-generated invoice for contract {$contract->contract_number} rental period {$rentalPeriod}: {$invoice->invoice_number}");

            return [
                'success' => true,
                'invoice' => $invoice,
                'message' => 'Invoice auto-generated successfully for rental period',
                'rental_period' => $rentalPeriod,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto invoice generation failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to auto-generate invoice: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check if invoice trigger jobs in the rental period are completed.
     */
    private function checkInvoiceTriggerJobsCompletedInPeriod(Contract $contract, Carbon $periodStart, Carbon $periodEnd, ?int $periodNumber = null): bool
    {
        $assignedJobs = $this->getAssignedInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber);
        $readyJobs = $assignedJobs->filter(fn (JobSchedule $jobSchedule) => $this->isReadyTriggerJob($contract, $jobSchedule));

        return $readyJobs->isNotEmpty() && $readyJobs->count() === $assignedJobs->count();
    }

    /**
     * Get count of completed jobs in period
     */
    private function getCompletedJobsCount(Contract $contract, Carbon $periodStart, Carbon $periodEnd, ?int $periodNumber = null): int
    {
        return $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber)->count();
    }

    /**
     * Get total jobs count in period
     */
    private function getTotalJobsCount(Contract $contract, Carbon $periodStart, Carbon $periodEnd, ?int $periodNumber = null): int
    {
        return $this->getAssignedInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber)->count();
    }

    /**
     * Check if all completed jobs in period have verified BA files
     */
    private function checkAllJobsHaveVerifiedBaFiles(Contract $contract, Carbon $periodStart, Carbon $periodEnd, ?int $periodNumber = null): bool
    {
        $completedJobs = $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber);

        foreach ($completedJobs as $job) {
            // Check if job has at least one verified BA file
            $hasVerifiedBa = JobScheduleBaFile::where('job_schedule_id', $job->id)
                ->where('verification_status', 'verified')
                ->exists();

            if (! $hasVerifiedBa) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if invoice already exists for this period.
     *
     * When $scopeByBillingGroup is true, the lookup is additionally scoped to
     * $billingGroupId (including explicitly matching NULL for the "default"/leftover
     * bucket) so that generating one billing group's invoice for a period does not
     * block another billing group's invoice for the same period, and so that
     * per-billing-group generation stays idempotent on re-run. Existing callers that
     * don't pass these two params keep the original contract+period-only behavior.
     */
    private function checkExistingInvoice(int $contractId, string $contractNumber, string $rentalPeriod, Carbon $periodStart, Carbon $periodEnd, ?int $billingGroupId = null, bool $scopeByBillingGroup = false): ?Invoice
    {
        $query = Invoice::where(function ($query) use ($contractId, $contractNumber) {
            $query->where('contract_id', $contractId)
                ->orWhere('contract_number', $contractNumber);
        })
            ->where('invoice_status', '!=', Invoice::STATUS_CANCELLED)
            ->where('period_invoice', $rentalPeriod);

        if ($scopeByBillingGroup) {
            $query->where(function ($query) use ($billingGroupId) {
                if ($billingGroupId === null) {
                    $query->whereNull('billing_group_id');
                } else {
                    $query->where('billing_group_id', $billingGroupId);
                }
            });
        }

        return $query->first();
    }

    private function getInvoiceTiming(Contract $contract): string
    {
        $paymentMethod = strtolower(trim((string) (
            $contract->quotation?->payment_method
            ?? $contract->quotation?->billing_methods
            ?? ''
        )));

        if (str_contains($paymentMethod, 'before')) {
            return 'before_service';
        }

        if (str_contains($paymentMethod, 'after')) {
            return 'after_service';
        }

        return 'legacy_all_jobs';
    }

    private function getInvoiceTriggerJobTypes(Contract $contract): ?array
    {
        return match ($this->getInvoiceTiming($contract)) {
            'before_service' => ['install', 'installation', 'installation_report'],
            'after_service' => ['service', 'service_first', 'service_routine', 'csr', 'customer_service_report'],
            default => null,
        };
    }

    /**
     * Parse the numeric period counter out of a "Period N" label (as produced by
     * getRentalPeriodsForContract()). Returns null for anything that doesn't match, so
     * callers that can't resolve a period number safely fall back to the original
     * calendar-window-only behavior everywhere below.
     */
    private function parsePeriodNumber(?string $rentalPeriod): ?int
    {
        if ($rentalPeriod && preg_match('/(\d+)/', $rentalPeriod, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Mirrors JobSchedule::getInvoicePeriodAttribute()'s formula using the Contract
     * instance already loaded/locked by the caller (avoids re-fetching the same contract
     * through the job's jobAdvice.contract relation for every candidate job). Keep the two
     * formulas in sync if either changes.
     */
    private function computeInvoicePeriodForJob(JobSchedule $job, Contract $contract): ?int
    {
        if (! in_array($this->normalizeJobType($job->type), self::RECURRING_INVOICE_JOB_TYPES, true)) {
            return null;
        }

        if (! is_numeric($job->period)) {
            return null;
        }

        $topInterval = max(1, (int) ($contract->top_interval_months ?? 1));
        $freqTimes = is_numeric($job->service_frequency) ? max(1, (int) $job->service_frequency) : 1;

        // Klien 27 Nov 2026 ("bedakan periode service dengan periode invoice"): rental yang
        // service-nya tiap N bulan (mis. 3 bulan 1x) menyimpan service_frequency = 1 (kali per
        // bulan), sehingga service ke-2 (bulan ke-4) dulu dihitung milik invoice periode 2.
        // Service ke-n jatuh di bulan ke-((n-1) x N + 1) → periode invoice sesuai TOP.
        $intervalMonths = $freqTimes <= 1 ? $this->serviceIntervalMonthsForJob($job) : 1;
        if ($intervalMonths > 1) {
            return intdiv(((int) $job->period - 1) * $intervalMonths, $topInterval) + 1;
        }

        $divisor = max(1, $freqTimes * $topInterval);

        return (int) ceil(((int) $job->period) / $divisor);
    }

    /** @var array<int, int> job_schedule_id => interval bulan service */
    private array $serviceIntervalMonthsCache = [];

    /**
     * Interval bulan service rental job ini (RentalServiceFrequency.frequency_months), 1 bila
     * bulanan / tak diketahui. Dibaca lewat job_schedule_rooms → job_advice_rooms → rental.
     */
    private function serviceIntervalMonthsForJob(JobSchedule $job): int
    {
        $jobId = (int) $job->id;
        if (! $jobId) {
            return 1;
        }

        if (! array_key_exists($jobId, $this->serviceIntervalMonthsCache)) {
            $months = 1;
            try {
                $months = (int) (DB::table('job_schedule_rooms as jsr')
                    ->join('job_advice_rooms as jar', 'jar.id', '=', 'jsr.job_advice_room_id')
                    ->join('master_rentals as mr', 'mr.id', '=', 'jar.rental_product_id')
                    ->join('rental_service_frequencies as rsf', 'rsf.id', '=', 'mr.service_frequency_id')
                    ->where('jsr.job_schedule_id', $jobId)
                    ->whereNull('jsr.deleted_at')
                    ->where(fn ($q) => $q->whereNull('rsf.frequency_times_per_month')->orWhere('rsf.frequency_times_per_month', '<=', 1))
                    ->max('rsf.frequency_months') ?? 1);
            } catch (\Throwable $e) {
                $months = 1;
            }
            $this->serviceIntervalMonthsCache[$jobId] = max(1, $months);
        }

        return $this->serviceIntervalMonthsCache[$jobId];
    }

    /**
     * Resolve the job IDs that belong to this invoice period.
     *
     * Non-recurring jobs (install/remove/free/legacy) and recurring jobs with no numeric
     * `period` set are matched purely by calendar schedule_date window, same as before.
     *
     * Recurring service/CSR jobs with a numeric `period` are matched by their own
     * sequence-derived invoice period instead: the service-generation cadence (e.g. 3x per
     * month) doesn't necessarily tile a calendar TOP-interval window evenly, so a job's
     * actual schedule_date can drift onto the "wrong" side of the calendar boundary while
     * the period it's meant to belong to (and the "P.Invoice" value shown in the Job
     * Schedule list) does not move. Confirmed live on contract SBY-CA/26-08/0006 (17 Aug
     * 2026): a 3x/month service inside a 1-month TOP put the 4th service's schedule_date on
     * Period 1's last calendar day even though its own sequence number puts it in Period 2,
     * which silently delayed Period 1's invoice until that unrelated Period-2 job finished.
     *
     * $periodNumber === null (callers that don't know their period number, e.g. direct
     * legacy calls) preserves the original calendar-window-only behavior for every job.
     */
    private function resolveInvoiceTriggerJobIds(Contract $contract, Carbon $periodStart, Carbon $periodEnd, ?int $periodNumber, bool $includeLeftLocation = false): array
    {
        // $includeLeftLocation: job yang sudah dikerjakan tetapi teknisi meninggalkan lokasi
        // (BA belum keluar) tetap dihitung sebagai job ter-assign yang belum selesai.
        $excludedStatuses = $includeLeftLocation
            ? array_values(array_diff(self::NON_BILLABLE_JOB_STATUSES, ['meninggalkan_lokasi']))
            : self::NON_BILLABLE_JOB_STATUSES;

        $baseQuery = fn () => JobSchedule::whereHas('jobAdvice', function ($query) use ($contract) {
            $query->where('contract_id', $contract->id);
        })
            ->whereNull('catalyst_backfill_at')
            ->whereNotIn('status', $excludedStatuses)
            ->whereRaw("LOWER(COALESCE(type, '')) NOT IN (?, ?, ?, ?)", self::NON_BILLABLE_JOB_TYPES);

        $windowJobs = $baseQuery()
            ->whereBetween('schedule_date', [$periodStart, $periodEnd])
            ->get(['id', 'type', 'period', 'service_frequency']);

        $ids = $windowJobs->pluck('id')->all();

        if ($periodNumber === null) {
            return $ids;
        }

        // Drop window-matched recurring jobs whose sequence-derived period disagrees.
        foreach ($windowJobs as $job) {
            $invoicePeriod = $this->computeInvoicePeriodForJob($job, $contract);
            if ($invoicePeriod !== null && $invoicePeriod !== $periodNumber) {
                $ids = array_diff($ids, [$job->id]);
            }
        }

        // Add recurring jobs whose sequence-derived period matches this period but whose
        // schedule_date drifted outside the calendar window above.
        $recurringContractJobs = $baseQuery()
            ->whereRaw("LOWER(COALESCE(type, '')) IN (?, ?, ?)", self::RECURRING_INVOICE_JOB_TYPES)
            ->whereNotNull('period')
            ->get(['id', 'type', 'period', 'service_frequency']);

        foreach ($recurringContractJobs as $job) {
            if ($this->computeInvoicePeriodForJob($job, $contract) === $periodNumber) {
                $ids[] = $job->id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function getCandidateInvoiceJobsQuery(Contract $contract, Carbon $periodStart, Carbon $periodEnd, ?int $periodNumber = null, bool $includeLeftLocation = false)
    {
        $jobIds = $this->resolveInvoiceTriggerJobIds($contract, $periodStart, $periodEnd, $periodNumber, $includeLeftLocation);

        return JobSchedule::with([
            'jobAdvice.rooms.rentalProduct',
            'jobAdvice.rooms.contractRoom.room',
            'jobAdvice.contract.contractRentals.masterRental',
            'jobAdvice.contract.contractRooms.room',
            'jobScheduleRooms.jobAdviceRoom.rentalProduct',
            'jobScheduleRooms.jobAdviceRoom.contractRoom.room',
            'jobScheduleRooms.jobAdviceRoom.quotationRoom.room',
            'jobScheduleRooms.room',
        ])
            ->whereIn('id', $jobIds)
            ->orderBy('schedule_date')
            ->orderBy('id');
    }

    private function getInvoiceTriggerJobsInPeriod(Contract $contract, Carbon $periodStart, Carbon $periodEnd, ?int $periodNumber = null, bool $includeLeftLocation = false): \Illuminate\Support\Collection
    {
        $candidates = $this->getCandidateInvoiceJobsQuery($contract, $periodStart, $periodEnd, $periodNumber, $includeLeftLocation)->get();

        // Whether this period contains an actual Install (IR) job. When a free-trial install
        // (IF) continues straight into the contract with the unit still on the wall, no IR job
        // is generated for the first period, so the first CSR/service job must become the
        // Before Service invoice trigger instead (see jobTypeCanInvoiceRentalType()).
        $periodHasInstallTrigger = $candidates->contains(
            fn (JobSchedule $jobSchedule) => in_array(
                $this->normalizeJobType($jobSchedule->type),
                ['install', 'installation', 'installation_report', 'ir'],
                true
            )
        );

        return $candidates
            ->filter(fn (JobSchedule $jobSchedule) => $this->jobMatchesRentalInvoiceTrigger($contract, $jobSchedule, $periodHasInstallTrigger))
            ->values();
    }

    private function getCompletedInvoiceTriggerJobsInPeriod(Contract $contract, Carbon $periodStart, Carbon $periodEnd, ?int $periodNumber = null): \Illuminate\Support\Collection
    {
        return $this->getInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber)
            ->filter(function (JobSchedule $jobSchedule) {
                return in_array($jobSchedule->status, self::BILLABLE_COMPLETED_STATUSES, true)
                    && ! empty($jobSchedule->ba_date);
            })
            ->values();
    }

    /**
     * Jobs that make an invoice period ready and provide its rental-detail scope.
     *
     * A first CSR created by ContractOnWallCsrService is a special Before Service
     * continuation: that service only creates the CSR after finding a completed
     * Install Free/installation source for the same quotation and room. The CSR is
     * therefore intentionally invoice-ready while it is still unstarted.
     */
    private function getReadyInvoiceTriggerJobsInPeriod(Contract $contract, Carbon $periodStart, Carbon $periodEnd, ?int $periodNumber = null): \Illuminate\Support\Collection
    {
        return $this->getInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber)
            ->filter(fn (JobSchedule $jobSchedule) => $this->isReadyTriggerJob($contract, $jobSchedule))
            ->values();
    }

    private function isReadyTriggerJob(Contract $contract, JobSchedule $jobSchedule): bool
    {
        $isCompletedWithBa = in_array($jobSchedule->status, self::BILLABLE_COMPLETED_STATUSES, true)
            && ! empty($jobSchedule->ba_date);

        return $isCompletedWithBa
            || $this->isPendingBeforeServiceTrialContinuation($contract, $jobSchedule);
    }

    /**
     * Klien 27 Nov 2026 (Before & After Service sama): invoice terbit bila job yang SUDAH
     * DI-ASSIGN (lewat Material Assign/Team Assign) sudah selesai dan ada BA Date. Job yang
     * belum di-assign (masih new_job/scheduled, mis. ruangan yang dikerjakan nanti atau job
     * susulan "tidak dapat menyelesaikan semua ruangan") tidak ditunggu.
     */
    private function isAssignedTriggerJob(JobSchedule $jobSchedule): bool
    {
        return ! in_array(strtolower(trim((string) $jobSchedule->status)), ['new_job', 'scheduled'], true);
    }

    /**
     * Job pemicu periode ini yang wajib selesai sebelum invoice terbit: sudah di-assign
     * (termasuk yang sudah meninggalkan lokasi tapi BA belum keluar) atau sudah siap.
     */
    private function getAssignedInvoiceTriggerJobsInPeriod(Contract $contract, Carbon $periodStart, Carbon $periodEnd, ?int $periodNumber = null): \Illuminate\Support\Collection
    {
        return $this->getInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber, true)
            ->filter(fn (JobSchedule $jobSchedule) => $this->isReadyTriggerJob($contract, $jobSchedule)
                || $this->isAssignedTriggerJob($jobSchedule))
            ->values();
    }

    /**
     * Satu bucket Billing Group siap ditagih bila ada job siap yang menyentuh ruangannya dan
     * SEMUA job ter-assign yang menyentuh ruangannya sudah siap. $roomIds null = seluruh kontrak.
     */
    private function isBillingBucketReady(Contract $contract, Carbon $periodStart, Carbon $periodEnd, ?int $periodNumber, ?array $roomIds): bool
    {
        $assigned = $this->getAssignedInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber);
        if ($roomIds !== null) {
            $assigned = $assigned->filter(fn (JobSchedule $jobSchedule) => $this->jobTouchesContractRooms($jobSchedule, $roomIds));
        }

        $ready = $assigned->filter(fn (JobSchedule $jobSchedule) => $this->isReadyTriggerJob($contract, $jobSchedule));

        return $ready->isNotEmpty() && $ready->count() === $assigned->count();
    }

    private function jobTouchesContractRooms(JobSchedule $jobSchedule, array $roomIds): bool
    {
        $roomIds = array_map('intval', $roomIds);

        foreach ($this->getRentalDetailsForJob($jobSchedule) as $rental) {
            if (in_array((int) ($rental['contract_room_id'] ?? 0), $roomIds, true)) {
                return true;
            }
        }

        return false;
    }

    private function isPendingBeforeServiceTrialContinuation(Contract $contract, JobSchedule $jobSchedule): bool
    {
        $contractStatus = strtolower(trim((string) ($contract->contract_status ?? $contract->status ?? '')));
        if ($contractStatus !== '' && $contractStatus !== 'active') {
            return false;
        }

        return $this->isBeforeServiceTrialContinuation($contract, $jobSchedule)
            && in_array(strtolower(trim((string) $jobSchedule->status)), ['scheduled', 'new_job'], true);
    }

    private function isBeforeServiceTrialContinuation(Contract $contract, JobSchedule $jobSchedule): bool
    {
        if ($this->getInvoiceTiming($contract) !== 'before_service') {
            return false;
        }

        if (! in_array($this->normalizeJobType($jobSchedule->type), [
            'service',
            'service_first',
            'service_routine',
            'csr',
            'customer_service_report',
        ], true)) {
            return false;
        }

        if ((int) ($jobSchedule->period ?? 0) !== 1) {
            return false;
        }

        return str_contains(
            strtolower((string) $jobSchedule->internal_notes),
            'auto-generated first csr from contract activation'
        );
    }

    /**
     * Report Generate Invoice (spec 24 Nov 2026): apakah job selesai ini menurut aturan rental-nya
     * memang memicu invoice. Contoh: IR Unit Only menagih; IR Unit + Refill (After Service) tidak,
     * tagihannya ikut CSR; job free (Install/Remove Free) tidak pernah ditagih. Memakai aturan yang
     * sama dengan pembuatan invoice (jobMatchesRentalInvoiceTrigger).
     */
    public function jobShouldTriggerInvoice(JobSchedule $jobSchedule): bool
    {
        if (in_array($this->normalizeJobType($jobSchedule->type), self::NON_BILLABLE_JOB_TYPES, true)) {
            return false;
        }

        $contract = $jobSchedule->jobAdvice?->contract;
        if (! $contract) {
            return false;
        }

        return $this->jobMatchesRentalInvoiceTrigger($contract, $jobSchedule);
    }

    private function jobMatchesRentalInvoiceTrigger(Contract $contract, JobSchedule $jobSchedule, bool $periodHasInstallTrigger = true): bool
    {
        $jobType = $this->normalizeJobType($jobSchedule->type);
        $rentalTypes = $this->getRentalTypesForJob($contract, $jobSchedule);

        if ($rentalTypes->isEmpty()) {
            $legacyTriggerTypes = $this->getInvoiceTriggerJobTypes($contract);

            return $legacyTriggerTypes === null || in_array($jobType, $legacyTriggerTypes, true);
        }

        return $rentalTypes->contains(fn (string $rentalType) => $this->jobTypeCanInvoiceRentalType($jobType, $rentalType, $contract, $jobSchedule, $periodHasInstallTrigger));
    }

    private function getRentalTypesForJob(Contract $contract, JobSchedule $jobSchedule): \Illuminate\Support\Collection
    {
        $jobAdviceRooms = $jobSchedule->jobAdvice?->rooms ?? collect();

        $matchedJobAdviceRooms = $jobAdviceRooms->filter(function ($jobAdviceRoom) use ($jobSchedule) {
            $jobScheduleId = (int) $jobSchedule->id;

            return in_array($jobScheduleId, [
                (int) $jobAdviceRoom->install_job_schedule_id,
                (int) $jobAdviceRoom->service_job_schedule_id,
                (int) $jobAdviceRoom->remove_job_schedule_id,
            ], true);
        });

        $rentalTypes = $matchedJobAdviceRooms
            ->map(fn ($jobAdviceRoom) => $this->normalizeRentalType($jobAdviceRoom->rentalProduct ?? $jobAdviceRoom->contractRoom?->rental_product))
            ->filter();

        if ($rentalTypes->isNotEmpty()) {
            return $rentalTypes->unique()->values();
        }

        $scheduleRoomRentalTypes = $this->getJobAdviceRoomsFromScheduleRooms($jobSchedule)
            ->map(fn ($jobAdviceRoom) => $this->normalizeRentalType($jobAdviceRoom->rentalProduct ?? $jobAdviceRoom->contractRoom?->rental_product))
            ->filter();

        if ($scheduleRoomRentalTypes->isNotEmpty()) {
            return $scheduleRoomRentalTypes->unique()->values();
        }

        if ($jobSchedule->room_id) {
            return $contract->contractRooms
                ->where('room_id', $jobSchedule->room_id)
                ->map(fn ($contractRoom) => $this->normalizeRentalType($contractRoom->rental_product))
                ->filter()
                ->unique()
                ->values();
        }

        return $contract->contractRentals
            ->map(fn ($contractRental) => $this->normalizeRentalType($contractRental->masterRental))
            ->filter()
            ->unique()
            ->values();
    }

    private function jobTypeCanInvoiceRentalType(string $jobType, string $rentalType, Contract $contract, ?JobSchedule $jobSchedule = null, bool $periodHasInstallTrigger = true): bool
    {
        $installTypes = ['install', 'installation', 'installation_report', 'ir'];
        $serviceTypes = ['service', 'service_first', 'service_routine', 'servis', 'csr', 'customer_service_report', 'customer service report'];

        $timing = $this->getInvoiceTiming($contract);
        $isUnitOnly = $rentalType === 'unit_only';
        $isUnitRefillBeforeService = $rentalType === 'unit_refill'
            && $timing === 'before_service';

        if ($isUnitOnly || $isUnitRefillBeforeService) {
            if (in_array($jobType, $installTypes, true)) {
                return true;
            }

            if (! in_array($jobType, $serviceTypes, true) || ! $jobSchedule) {
                return false;
            }

            $period = is_numeric($jobSchedule->period) ? (int) $jobSchedule->period : null;

            // Subsequent periods are always billed off their own service/CSR job.
            if ($period !== null && $period > 1) {
                return true;
            }

            // First period is normally billed off the Install (IR) job. But when a free-trial
            // install (IF) continued into the contract and the unit stayed on the wall, no IR
            // job exists in this period — so the first CSR/service job becomes the Before
            // Service trigger. Gated on Before Service + no IR job to avoid double invoicing.
            if ($timing === 'before_service' && ! $periodHasInstallTrigger) {
                return true;
            }

            // QA 26 Nov 2026 (BLP-CA/26-11/0004 Ruang-1): unit Unit Only hasil trial yang lanjut
            // kontrak tidak punya IR, jadi periode 1-nya hanya punya job check/service. Dulu
            // periode itu tak pernah ditagih pada After Service (dan pada Before Service bila
            // ruangan LAIN di periode itu punya IR). Dicek per ruangan supaya tak dobel dengan IR.
            return $isUnitOnly
                && $period === 1
                && $this->unitOnlyJobRoomsHaveNoInstallJob($contract, $jobSchedule);
        }

        if (in_array($rentalType, ['unit_refill', 'refill_only'], true)) {
            return in_array($jobType, $serviceTypes, true);
        }

        $legacyTriggerTypes = $this->getInvoiceTriggerJobTypes($contract);

        return $legacyTriggerTypes === null || in_array($jobType, $legacyTriggerTypes, true);
    }

    /**
     * True bila ruangan job ini (room_id) tidak pernah punya job Install (IR) yang aktif di kontrak
     * yang sama. Job yang belum tersimpan / tanpa data ruangan → false (perilaku lama).
     */
    private function unitOnlyJobRoomsHaveNoInstallJob(Contract $contract, JobSchedule $jobSchedule): bool
    {
        if (! $jobSchedule->exists || ! $contract->exists) {
            return false;
        }

        $roomIds = JobScheduleRoom::where('job_schedule_id', $jobSchedule->id)
            ->whereNotNull('room_id')
            ->pluck('room_id')
            ->unique()
            ->values();

        if ($roomIds->isEmpty()) {
            return false;
        }

        return ! JobSchedule::whereIn('type', ['install', 'installation', 'installation_report', 'ir'])
            ->whereNotIn('status', ['cancelled', 'undone'])
            ->where('id', '!=', $jobSchedule->id)
            ->whereHas('jobAdvice', fn ($query) => $query->where('contract_id', $contract->id))
            ->whereHas('jobScheduleRooms', fn ($query) => $query->whereIn('room_id', $roomIds))
            ->exists();
    }

    private function normalizeJobType(?string $type): string
    {
        return strtolower(trim(str_replace('-', '_', (string) $type)));
    }

    private function normalizeRentalType($rental): string
    {
        if (! $rental) {
            return '';
        }

        if (is_string($rental)) {
            return strtolower(trim(str_replace('-', '_', $rental)));
        }

        return strtolower(trim(str_replace('-', '_', (string) ($rental->rental_type ?? ''))));
    }

    /**
     * Create invoice for rental period.
     *
     * $billingGroupOverride is used by the per-billing-group split
     * (generateInvoicesPerBillingGroup()) to stamp a specific billing group's
     * PIC/tax/address/billing_group_id on the invoice. When omitted, this falls
     * back to $contract->billingGroup (latest), which is the original single-invoice
     * behavior — unchanged for contracts with zero or one billing group.
     */
    private function createInvoiceForRentalPeriod(Contract $contract, string $rentalPeriod, Carbon $periodStart, Carbon $periodEnd, ?BillingGroup $billingGroupOverride = null, ?Carbon $invoiceDateOverride = null, ?string $internalNote = null): Invoice
    {
        $invoiceNumber = $this->documentNumberService->generate(
            'invoice',
            null, // branchCode will be auto-detected
            null, // buildingId
            $contract->id // Context for branch detection
        );

        // Rule 46: TOP pertama manual (dari contract), selanjutnya mengikuti TOP terakhir
        $previousInvoice = Invoice::where('contract_number', $contract->contract_number)
            ->orderBy('id', 'desc')
            ->first();

        // Determine Payment Terms (TOP in days)
        $rawTerms = $contract->term_of_payment ?? $contract->payment_terms;
        $paymentTerms = 0;

        if (is_numeric($rawTerms)) {
            $paymentTerms = (int) $rawTerms;
        } else {
            // Use accessor from Contract model which handles "X bulan 1x" etc.
            // and convert to days (X * 30)
            $intervalMonths = $contract->top_interval_months ?? 1;
            $paymentTerms = $intervalMonths * 30;

            // Optional: Special case for cash/tunai to keep it 0 days
            $termLower = strtolower(trim($rawTerms ?? ''));
            if (strpos($termLower, 'cash') !== false || strpos($termLower, 'tunai') !== false || strpos($termLower, 'transfer') !== false) {
                $paymentTerms = 0;
            }
        }

        if ($previousInvoice) {
            // Smart TOP: follow last invoice's actual behavior
            $paymentTerms = $previousInvoice->due_date->diffInDays($previousInvoice->invoice_date);
        }

        // Pull ba_date from the job that triggers invoice generation for this contract timing.
        $firstJob = $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $this->parsePeriodNumber($rentalPeriod))
            ->sortBy([
                ['schedule_date', 'asc'],
                ['id', 'asc'],
            ])
            ->first();

        // Determine Invoice Date
        $invoiceDate = $periodEnd; // Default: End of period

        if ($firstJob && $firstJob->ba_date) {
            $invoiceDate = Carbon::parse($firstJob->ba_date);
        }

        // Smart Invoice Date Preference (Overrides above if set and not manual)
        if ($contract->invoice_date_preference && $contract->invoice_date_preference !== 'manual') {
            $smartDate = $contract->calculateInvoiceDate($contract->invoice_date_preference, null, $periodEnd);
            if ($smartDate) {
                $invoiceDate = $smartDate;
            }
        }

        // Klien 27 Nov 2026: invoice susulan bertanggal sama dengan invoice pertama periode itu,
        // dan invoice periode berikutnya mengikuti tanggal invoice sebelumnya (TOP).
        if ($invoiceDateOverride) {
            $invoiceDate = $invoiceDateOverride->copy();
        }

        // Recalculate Due Date based on final Invoice Date
        $dueDate = $this->calculateDueDate($invoiceDate, $paymentTerms);

        // Helper to get Billing Group
        $billingGroup = $billingGroupOverride ?? $contract->billingGroup;

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'contract_number' => $contract->contract_number,
            'contract_id' => $contract->id,
            'po_number' => $contract->po_number,
            'customer_id' => $contract->customer_id,
            'billing_address' => $billingGroup->pic_address ?? $contract->customer->address ?? '',
            'period_invoice' => $rentalPeriod,
            'invoice_status' => Invoice::STATUS_DRAFT,
            'status' => Invoice::STATUS_DRAFT,
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'ba_date' => $firstJob ? $firstJob->ba_date : null,
            // Seed only — updateInvoiceTotals() resolves the authoritative value
            // from the customer's tax code via InvoiceTaxResolver.
            'tax_obligation' => false,
            'tax_code' => $contract->ppn_code,
            // `npwp_number` kolom warisan dan tidak lagi diisi form Billing Group —
            // yang diisi sekarang `npwp` + `nitku`. Membacanya langsung membuat
            // npwp_number invoice selalu kosong.
            'npwp_number' => $billingGroup?->tax_identity_number,
            'tax_number' => $billingGroup->tax_number ?? null,
            'tax_address' => $billingGroup?->tax_identity_address,
            'kirim' => $billingGroup->invoice_type ?? 'manual',
            'gedung' => $this->getBuildingInfo($contract),
            'alamat_1' => $contract->customer->address ?? '',
            'alamat_2' => $contract->customer->city ?? '',
            'catatan_internal' => "Auto-generated for rental period: {$rentalPeriod}. ".($internalNote ? $internalNote.' ' : '').($contract->internal_notes ?? ''),
            'catatan_customer' => "Invoice for services completed in period {$periodStart->format('d/m/Y')} - {$periodEnd->format('d/m/Y')}",
            'pic_finance' => $billingGroup->pic_name ?? $contract->billingGroup->pic_name ?? '',
            'email' => $billingGroup->pic_email ?? $contract->billingGroup->pic_email ?? $contract->customer->email ?? '',
            'billing_group_id' => $billingGroup->id ?? $contract->billing_group_id ?? null,
            'umur_invoice' => 0,
            'outstanding' => 0,
            'total_amount' => 0,
            'grand_total' => 0,
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_paid' => 0,
            'notes' => "Auto-generated invoice for rental period {$rentalPeriod}",
            'terms_conditions' => $contract->terms_conditions ?? '',
            'created_by' => auth()->id(),
        ]);

        $invoice->logActivity(
            'created',
            'Invoice auto-generated for rental period '.$rentalPeriod,
            auth()->id()
        );

        return $invoice;
    }

    /**
     * Create invoice details from completed jobs
     */
    private function createInvoiceDetailsFromJobs(Invoice $invoice, Contract $contract, Carbon $periodStart, Carbon $periodEnd, array $billedRentals = [], ?int $periodNumber = null): \Illuminate\Database\Eloquent\Collection
    {
        $pairs = $this->computeBillableRentalPairs($contract, $periodStart, $periodEnd, $billedRentals, null, $periodNumber);

        foreach ($pairs as $pair) {
            $this->createInvoiceDetail($invoice, $pair['job'], $pair['rental']);
        }

        // Unfiltered (legacy/single-invoice) callers keep returning every ready job in the
        // period, regardless of whether it actually contributed a rental line — this matches
        // the original behavior relied on by attachApprovedBaFiles() for the single-invoice path.
        return $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber);
    }

    /**
     * Compute the (job, rental-detail) pairs that should be billed for this contract/period.
     *
     * $billedRentals is a list of billing keys (see invoiceRentalBillingKey()) to treat as
     * already billed and therefore skip — used both for cross-invoice dedup within a single
     * generation pass and for backfilling an already-drafted invoice.
     *
     * $allowedContractRoomIds, when not null, restricts results to rentals whose
     * contract_room_id is in the given list — this is how per-billing-group invoice
     * splitting scopes rental lines to only the rooms that belong to that billing group.
     * Passing null (the default) bills every eligible room, i.e. the original behavior.
     *
     * @return array<int, array{job: JobSchedule, rental: array}>
     */
    private function computeBillableRentalPairs(Contract $contract, Carbon $periodStart, Carbon $periodEnd, array $billedRentals = [], ?array $allowedContractRoomIds = null, ?int $periodNumber = null): array
    {
        $completedJobs = $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber);
        $pairs = [];

        foreach ($completedJobs as $jobSchedule) {
            $rentalDetails = $this->getRentalDetailsForJob($jobSchedule);

            foreach ($rentalDetails as $rental) {
                if ($allowedContractRoomIds !== null && ! in_array($rental['contract_room_id'] ?? null, $allowedContractRoomIds, true)) {
                    continue;
                }

                // Create a unique key for this rental: building + room + rental product
                $billingKey = $this->invoiceRentalBillingKey($rental, $this->invoiceBuildingNameForJob($jobSchedule));

                if (in_array($billingKey, $billedRentals, true)) {
                    Log::debug('Skipping duplicate billing for rental unit', [
                        'job_no' => $jobSchedule->job_number,
                        'room' => $rental['room_name'],
                        'rental' => $rental['rental_name'],
                    ]);

                    continue;
                }

                $billedRentals[] = $billingKey;
                $pairs[] = ['job' => $jobSchedule, 'rental' => $rental];
            }
        }

        return $pairs;
    }

    /**
     * Resolve which active billing group (if any) each of the contract's rooms belongs to.
     *
     * Mirrors the precedence BillingGroupService already uses elsewhere in the app:
     * 1. An explicit ContractRoom::billing_group_id wins when it points at one of the
     *    contract's currently-active billing groups.
     * 2. Otherwise, fall back to the billing group whose BillingGroup::buildings() list
     *    includes the room's building.
     * Rooms matching neither are left out of the map entirely — callers treat those as the
     * "leftover"/default bucket (constraint: rooms not covered by any billing group must
     * still be billed).
     *
     * @return array<int, int> contract_room_id => billing_group_id
     */
    private function resolveContractRoomBillingGroupMap(Contract $contract, \Illuminate\Support\Collection $activeBillingGroups): array
    {
        $contract->loadMissing('contractRooms.room');

        $buildingToGroup = [];
        foreach ($activeBillingGroups as $billingGroup) {
            foreach ($billingGroup->buildings as $building) {
                // First active billing group claiming a building wins if a building was
                // (incorrectly) assigned to more than one active billing group.
                if (! isset($buildingToGroup[$building->id])) {
                    $buildingToGroup[$building->id] = $billingGroup->id;
                }
            }
        }

        $activeBillingGroupIds = $activeBillingGroups->pluck('id')->all();
        $map = [];

        foreach ($contract->contractRooms as $contractRoom) {
            $billingGroupId = null;

            if ($contractRoom->billing_group_id && in_array((int) $contractRoom->billing_group_id, $activeBillingGroupIds, true)) {
                $billingGroupId = (int) $contractRoom->billing_group_id;
            } elseif ($contractRoom->room?->building_id && isset($buildingToGroup[$contractRoom->room->building_id])) {
                $billingGroupId = $buildingToGroup[$contractRoom->room->building_id];
            }

            if ($billingGroupId !== null) {
                $map[$contractRoom->id] = $billingGroupId;
            }
        }

        return $map;
    }

    /**
     * Generate one invoice per active billing group for this rental period (plus a "default"
     * invoice, without a billing_group_id, for any rooms not covered by any active billing
     * group). Each invoice only contains rental line items for rooms belonging to its bucket.
     *
     * Called from within autoGenerateInvoiceForRentalPeriod()'s existing transaction, so all
     * billing groups for this period either all succeed or all roll back together — matching
     * the current all-or-nothing contract-level semantics.
     */
    private function generateInvoicesPerBillingGroup(Contract $contract, \Illuminate\Support\Collection $activeBillingGroups, string $rentalPeriod, Carbon $periodStart, Carbon $periodEnd): array
    {
        $periodNumber = $this->parsePeriodNumber($rentalPeriod);
        $roomBucketMap = $this->resolveContractRoomBillingGroupMap($contract, $activeBillingGroups);
        $allRoomIds = $contract->contractRooms->pluck('id')->all();
        $leftoverRoomIds = array_values(array_diff($allRoomIds, array_keys($roomBucketMap)));

        $buckets = $activeBillingGroups
            ->map(fn (BillingGroup $billingGroup) => [
                'billing_group' => $billingGroup,
                'room_ids' => array_keys(array_filter($roomBucketMap, fn ($bgId) => $bgId === $billingGroup->id)),
            ])
            ->values()
            ->all();

        // Default/leftover bucket: rooms not covered by any active billing group must still
        // be billed somehow, using the same "no billing_group_id" behavior as before this fix.
        $buckets[] = ['billing_group' => null, 'room_ids' => $leftoverRoomIds];

        $generatedInvoices = [];
        $existingInvoices = [];

        foreach ($buckets as $bucket) {
            $billingGroup = $bucket['billing_group'];
            $billingGroupId = $billingGroup?->id;

            // Klien 27 Nov 2026 (kondisi 4c): Billing Group ini baru ditagih bila semua job
            // ter-assign yang menyentuh ruangannya (lintas gedung sekalipun) sudah selesai + BA.
            if (! $this->isBillingBucketReady($contract, $periodStart, $periodEnd, $periodNumber, $bucket['room_ids'])) {
                continue;
            }

            // Compute (without persisting) whether this bucket has anything to bill before
            // creating an invoice header/number for it, so an empty bucket doesn't burn a
            // document number or leave a zero-amount invoice behind.
            $pairs = $this->computeBillableRentalPairs($contract, $periodStart, $periodEnd, [], $bucket['room_ids'], $periodNumber);
            if (empty($pairs)) {
                continue;
            }

            $existingInvoice = $this->checkExistingInvoice(
                $contract->id,
                $contract->contract_number,
                $rentalPeriod,
                $periodStart,
                $periodEnd,
                $billingGroupId,
                true
            );

            if ($existingInvoice) {
                $existingInvoices[] = $existingInvoice;

                continue;
            }

            $invoice = $this->createInvoiceForRentalPeriod($contract, $rentalPeriod, $periodStart, $periodEnd, $billingGroup);

            $jobIds = [];
            foreach ($pairs as $pair) {
                $this->createInvoiceDetail($invoice, $pair['job'], $pair['rental']);
                $jobIds[] = $pair['job']->id;
            }

            // Scope BA-file/contract-file attachment to only the jobs that actually
            // contributed a line to THIS invoice, so the same BA file isn't attached to
            // more than one billing group's invoice for the same period.
            $contributingJobs = $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber)
                ->whereIn('id', array_unique($jobIds))
                ->values();

            $this->attachApprovedBaFiles($invoice, $contributingJobs);
            $this->attachVerifiedContractFiles($invoice, $contract);
            $this->updateInvoiceTotals($invoice);

            $generatedInvoices[] = $invoice;

            Log::info('Auto-generated invoice for contract '.$contract->contract_number
                .' billing group '.($billingGroupId ?? 'default')
                ." rental period {$rentalPeriod}: {$invoice->invoice_number}");
        }

        if (empty($generatedInvoices)) {
            if (! empty($existingInvoices)) {
                return [
                    'success' => false,
                    'message' => 'Invoice already exists for this rental period',
                    'existing_invoice' => $existingInvoices[0],
                    'existing_invoices' => $existingInvoices,
                ];
            }

            return [
                'success' => false,
                'message' => 'No completed billable rooms found for this billing period',
            ];
        }

        return [
            'success' => true,
            'invoice' => $generatedInvoices[0],
            'invoices' => $generatedInvoices,
            'message' => 'Invoice(s) auto-generated successfully for rental period',
            'rental_period' => $rentalPeriod,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
        ];
    }

    /**
     * Build the rental rows that should exist on an already generated monthly invoice.
     * This is intentionally read-only so repair commands can preview before applying.
     */
    public function expectedRentalDetailsForInvoice(Invoice $invoice): array
    {
        $contract = $invoice->contract()
            ->with([
                'contractRentals.masterRental',
                'contractRooms.room',
                'customer',
                'quotation',
                'billingGroups.buildings',
            ])
            ->first();

        if (! $contract || ! $invoice->invoice_date) {
            return [];
        }

        $periodStart = Carbon::parse($invoice->invoice_date)->startOfMonth();
        $periodEnd = Carbon::parse($invoice->invoice_date)->endOfMonth();

        // If the contract has more than one active billing group, restrict the preview to
        // just this invoice's own bucket (its billing group's rooms, or the leftover/default
        // bucket when billing_group_id is null) — otherwise a repair run against one billing
        // group's invoice would suggest rows that actually belong to a sibling invoice.
        $allowedContractRoomIds = null;
        $activeBillingGroups = $contract->billingGroups->where('is_active', true)->values();

        if ($activeBillingGroups->count() > 1) {
            $roomBucketMap = $this->resolveContractRoomBillingGroupMap($contract, $activeBillingGroups);

            if ($invoice->billing_group_id) {
                $allowedContractRoomIds = array_keys(array_filter(
                    $roomBucketMap,
                    fn ($billingGroupId) => $billingGroupId === (int) $invoice->billing_group_id
                ));
            } else {
                $allRoomIds = $contract->contractRooms->pluck('id')->all();
                $allowedContractRoomIds = array_values(array_diff($allRoomIds, array_keys($roomBucketMap)));
            }
        }

        $completedJobs = $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd);
        $expected = [];
        $billedRentals = [];

        foreach ($completedJobs as $jobSchedule) {
            foreach ($this->getRentalDetailsForJob($jobSchedule) as $rental) {
                if ($allowedContractRoomIds !== null && ! in_array($rental['contract_room_id'] ?? null, $allowedContractRoomIds, true)) {
                    continue;
                }

                $buildingName = $this->invoiceBuildingNameForJob($jobSchedule);
                $billingKey = $this->invoiceRentalBillingKey($rental, $buildingName);

                if (isset($billedRentals[$billingKey])) {
                    continue;
                }

                $expected[] = [
                    'master_rental_id' => $rental['master_rental_id'],
                    'job_no' => $jobSchedule->job_number,
                    'building_name' => $buildingName,
                    'room_name' => $rental['room_name'] ?: ($jobSchedule->room?->room_name ?? $jobSchedule->room_name ?? ''),
                    'rental_name' => $rental['rental_name'] ?? 'Service',
                    'quantity' => $rental['quantity'],
                    'qty_free' => $rental['qty_free'] ?? 0,
                    'unit_price' => $rental['unit_price'],
                    'total_price' => $rental['total_price'],
                ];
                $billedRentals[$billingKey] = true;
            }
        }

        return $expected;
    }

    public function refreshInvoiceTotals(Invoice $invoice): void
    {
        $this->updateInvoiceTotals($invoice);
    }

    /**
     * Attach approved BA files from jobs to the invoice
     */
    private function attachApprovedBaFiles(Invoice $invoice, $completedJobs): void
    {
        foreach ($completedJobs as $jobSchedule) {
            $baFiles = JobScheduleBaFile::where('job_schedule_id', $jobSchedule->id)
                ->where('verification_status', 'verified')
                ->get();

            foreach ($baFiles as $baFile) {
                // Ensure no double attachment
                $exists = InvoiceFile::where('invoice_id', $invoice->id)
                    ->where('file_path', str_replace('uploads/', '', $baFile->file_path))
                    ->exists();

                if (! $exists) {
                    InvoiceFile::create([
                        'invoice_id' => $invoice->id,
                        'file_type' => 'attachment',
                        'file_name' => 'BA File - '.($baFile->room_name ?: 'Unknown Room').' - '.$baFile->file_name,
                        'file_path' => str_replace('uploads/', '', $baFile->file_path),
                        'description' => "BA File dari Job #{$jobSchedule->job_number} Ruangan {$baFile->room_name}",
                        'created_by' => auth()->id(),
                    ]);
                }
            }
        }
    }

    /**
     * Get rental details for a specific job
     */
    private function getRentalDetailsForJob(JobSchedule $jobSchedule): array
    {
        $rentalDetails = [];

        // Get contract rentals related to this job
        $contract = $jobSchedule->jobAdvice->contract ?? null;
        if (! $contract) {
            return [];
        }

        $detailsFromScheduleRooms = $this->getRentalDetailsFromJobScheduleRooms($jobSchedule, $contract);
        if ($detailsFromScheduleRooms !== null) {
            return $detailsFromScheduleRooms;
        }

        $matchedJobAdviceRooms = $this->getMatchedJobAdviceRoomsForJob($jobSchedule);
        if ($matchedJobAdviceRooms->isNotEmpty()) {
            foreach ($matchedJobAdviceRooms as $jobAdviceRoom) {
                $detail = $this->buildRentalDetailFromJobAdviceRoom($jobSchedule, $contract, $jobAdviceRoom);
                if ($detail) {
                    $rentalDetails[] = $detail;
                }
            }

            return $rentalDetails;
        }

        // Determine which rooms to bill for this job
        // If job has specific room, only use that. Otherwise use all rooms in contract.
        $contractRooms = $jobSchedule->room_id
            ? $contract->contractRooms()->where('room_id', $jobSchedule->room_id)->with(['room'])->get()
            : $contract->contractRooms()->with(['room'])->get();

        foreach ($contractRooms as $contractRoom) {
            if ($this->isContractRoomNonBillableForJob($jobSchedule, $contractRoom)) {
                Log::debug('Skipping non-billable suspended/cancelled room for invoice rental detail', [
                    'job_no' => $jobSchedule->job_number,
                    'contract_room_id' => $contractRoom->id,
                    'room_id' => $contractRoom->room_id,
                ]);

                continue;
            }

            // Use accessor from ContractRoom model to get associated rental unit
            $detail = $this->buildRentalDetailFromContractRoom($jobSchedule, $contract, $contractRoom);
            if ($detail) {
                $rentalDetails[] = $detail;
            }
        }

        return $rentalDetails;
    }

    private function getRentalDetailsFromJobScheduleRooms(JobSchedule $jobSchedule, Contract $contract): ?array
    {
        $jobSchedule->loadMissing([
            'jobScheduleRooms.jobAdviceRoom.rentalProduct',
            'jobScheduleRooms.jobAdviceRoom.contractRoom.room',
            'jobScheduleRooms.jobAdviceRoom.quotationRoom.room',
            'jobScheduleRooms.room',
        ]);

        $scheduleRooms = $jobSchedule->jobScheduleRooms ?? collect();
        if ($scheduleRooms->isEmpty()) {
            return null;
        }

        $rentalDetails = [];

        foreach ($scheduleRooms as $scheduleRoom) {
            if ($this->isScheduleRoomNonBillable($scheduleRoom)) {
                Log::debug('Skipping non-billable suspended/cancelled schedule room for invoice rental detail', [
                    'job_no' => $jobSchedule->job_number,
                    'job_schedule_room_id' => $scheduleRoom->id,
                    'room_id' => $scheduleRoom->room_id,
                ]);

                continue;
            }

            $adviceRooms = $this->getJobAdviceRoomsForScheduleRoom($scheduleRoom);
            if ($adviceRooms->isNotEmpty()) {
                foreach ($adviceRooms as $jobAdviceRoom) {
                    $detail = $this->buildRentalDetailFromJobAdviceRoom($jobSchedule, $contract, $jobAdviceRoom, $scheduleRoom);
                    if ($detail) {
                        $rentalDetails[] = $detail;
                    }
                }

                continue;
            }

            foreach ($this->getContractRoomsForScheduleRoom($contract, $scheduleRoom) as $contractRoom) {
                $detail = $this->buildRentalDetailFromContractRoom($jobSchedule, $contract, $contractRoom, $scheduleRoom);
                if ($detail) {
                    $rentalDetails[] = $detail;
                }
            }
        }

        return $rentalDetails;
    }

    private function getJobAdviceRoomsFromScheduleRooms(JobSchedule $jobSchedule): \Illuminate\Support\Collection
    {
        $jobSchedule->loadMissing([
            'jobScheduleRooms.jobAdviceRoom.rentalProduct',
            'jobScheduleRooms.jobAdviceRoom.contractRoom.room',
            'jobScheduleRooms.jobAdviceRoom.quotationRoom.room',
        ]);

        return ($jobSchedule->jobScheduleRooms ?? collect())
            ->flatMap(fn (JobScheduleRoom $scheduleRoom) => $this->getJobAdviceRoomsForScheduleRoom($scheduleRoom))
            ->unique('id')
            ->values();
    }

    private function getJobAdviceRoomsForScheduleRoom(JobScheduleRoom $scheduleRoom): \Illuminate\Support\Collection
    {
        $adviceRooms = collect();

        if ($scheduleRoom->jobAdviceRoom) {
            $adviceRooms->push($scheduleRoom->jobAdviceRoom);
        }

        if (Schema::hasTable('job_schedule_room_rentals')) {
            $scheduleRoom->loadMissing([
                'rentals.jobAdviceRoom.rentalProduct',
                'rentals.jobAdviceRoom.contractRoom.room',
                'rentals.jobAdviceRoom.quotationRoom.room',
            ]);

            $adviceRooms = $adviceRooms->merge(
                $scheduleRoom->rentals
                    ->pluck('jobAdviceRoom')
                    ->filter()
            );
        }

        return $adviceRooms
            ->filter()
            ->unique('id')
            ->values();
    }

    private function getContractRoomsForScheduleRoom(Contract $contract, JobScheduleRoom $scheduleRoom): \Illuminate\Support\Collection
    {
        if ($scheduleRoom->room_id) {
            return $contract->contractRooms()
                ->where('room_id', $scheduleRoom->room_id)
                ->with('room')
                ->get();
        }

        $roomName = strtolower(trim((string) $scheduleRoom->room_name));
        if ($roomName === '') {
            return collect();
        }

        return $contract->contractRooms()
            ->with('room')
            ->get()
            ->filter(fn ($contractRoom) => strtolower(trim((string) $contractRoom->room?->room_name)) === $roomName)
            ->values();
    }

    private function buildRentalDetailFromJobAdviceRoom(JobSchedule $jobSchedule, Contract $contract, $jobAdviceRoom, ?JobScheduleRoom $scheduleRoom = null): ?array
    {
        if ($this->isJobAdviceRoomNonBillableForJob($jobSchedule, $jobAdviceRoom)) {
            Log::debug('Skipping non-billable suspended/cancelled JA room for invoice rental detail', [
                'job_no' => $jobSchedule->job_number,
                'job_advice_room_id' => $jobAdviceRoom->id,
                'contract_room_id' => $jobAdviceRoom->contract_room_id,
            ]);

            return null;
        }

        $masterRental = $jobAdviceRoom->rentalProduct ?? $jobAdviceRoom->contractRoom?->rental_product;
        if (! $masterRental) {
            return null;
        }

        if (! $this->jobTypeCanInvoiceRentalType(
            $this->normalizeJobType($jobSchedule->type),
            $this->normalizeRentalType($masterRental),
            $contract,
            $jobSchedule,
            ! $this->isBeforeServiceTrialContinuation($contract, $jobSchedule)
        )) {
            Log::debug('Skipping JA room rental detail because job type is not the invoice trigger for this rental type', [
                'job_no' => $jobSchedule->job_number,
                'job_type' => $jobSchedule->type,
                'job_advice_room_id' => $jobAdviceRoom->id,
                'rental' => $masterRental->rental_name ?? null,
                'rental_type' => $masterRental->rental_type ?? null,
            ]);

            return null;
        }

        $roomId = $scheduleRoom?->room_id
            ?? $jobAdviceRoom->contractRoom?->room_id
            ?? $jobAdviceRoom->quotationRoom?->room_id
            ?? null;

        $contractRental = $this->findContractRentalForJobAdviceRoom($contract, $jobAdviceRoom, $masterRental->id, $roomId);
        if (! $contractRental) {
            return null;
        }

        return [
            'contract_rental_id' => $contractRental->id,
            'contract_room_id' => $jobAdviceRoom->contract_room_id,
            'room_id' => $roomId,
            'master_rental_id' => $masterRental->id,
            'rental_name' => $masterRental->rental_name ?? $jobAdviceRoom->rental_name ?? 'Service',
            'room_name' => $scheduleRoom?->room_name
                ?? $jobAdviceRoom->contractRoom?->room?->room_name
                ?? $jobAdviceRoom->quotationRoom?->room?->room_name
                ?? $jobAdviceRoom->room_name
                ?? '',
            'quantity' => $contractRental->quantity ?? 1,
            'qty_free' => $contractRental->qty_free ?? 0,
            'unit_price' => $contractRental->unit_price ?? 0,
            'total_price' => $contractRental->total_price ?? (($contractRental->quantity ?? 1) * ($contractRental->unit_price ?? 0)),
        ];
    }

    private function buildRentalDetailFromContractRoom(JobSchedule $jobSchedule, Contract $contract, $contractRoom, ?JobScheduleRoom $scheduleRoom = null): ?array
    {
        $masterRental = $contractRoom->rental_product;
        if (! $masterRental) {
            return null;
        }

        if (! $this->jobTypeCanInvoiceRentalType(
            $this->normalizeJobType($jobSchedule->type),
            $this->normalizeRentalType($masterRental),
            $contract,
            $jobSchedule,
            ! $this->isBeforeServiceTrialContinuation($contract, $jobSchedule)
        )) {
            Log::debug('Skipping rental detail because job type is not the invoice trigger for this rental type', [
                'job_no' => $jobSchedule->job_number,
                'job_type' => $jobSchedule->type,
                'contract_room_id' => $contractRoom->id,
                'room_id' => $contractRoom->room_id,
                'rental' => $masterRental->rental_name ?? null,
                'rental_type' => $masterRental->rental_type ?? null,
            ]);

            return null;
        }

        $contractRental = $this->findContractRentalForRoomAndRental($contract, $masterRental->id, $contractRoom->room_id);
        if (! $contractRental) {
            return null;
        }

        return [
            'contract_rental_id' => $contractRental->id,
            'contract_room_id' => $contractRoom->id,
            'room_id' => $contractRoom->room_id,
            'master_rental_id' => $masterRental->id,
            'rental_name' => $masterRental->rental_name ?? 'Service',
            'room_name' => $scheduleRoom?->room_name ?? $contractRoom->room->room_name ?? '',
            'quantity' => $contractRental->quantity ?? 1,
            'qty_free' => $contractRental->qty_free ?? 0,
            'unit_price' => $contractRental->unit_price ?? 0,
            'total_price' => $contractRental->total_price ?? (($contractRental->quantity ?? 1) * ($contractRental->unit_price ?? 0)),
        ];
    }

    private function getMatchedJobAdviceRoomsForJob(JobSchedule $jobSchedule): \Illuminate\Support\Collection
    {
        $jobAdviceRooms = $jobSchedule->jobAdvice?->rooms ?? collect();
        $jobScheduleId = (int) $jobSchedule->id;

        return $jobAdviceRooms
            ->filter(function ($jobAdviceRoom) use ($jobScheduleId) {
                return in_array($jobScheduleId, [
                    (int) $jobAdviceRoom->install_job_schedule_id,
                    (int) $jobAdviceRoom->service_job_schedule_id,
                    (int) $jobAdviceRoom->remove_job_schedule_id,
                ], true);
            })
            ->values();
    }

    private function invoiceRentalBillingKey(array $rental, ?string $buildingName): string
    {
        // Keyed on building_name + room_name + master_rental_id (not contract_rental_id/room_id)
        // because that is all invoice_rental_details persists — a key built any other way here
        // would not match what refreshDraftInvoiceRentalDetails() derives from saved rows,
        // breaking dedup when backfilling a room into an already-drafted invoice.
        // The building is part of the key because room names repeat across buildings: a
        // contract with "Lobby" in three buildings on the same rental billed only one of
        // them before (QA 10 Oct 2026, JKT-INV/26-10/0004).
        return 'building:'.strtolower(trim((string) $buildingName))
            .'|room-name:'.strtolower(trim((string) ($rental['room_name'] ?? '')))
            .'|rental:'.($rental['master_rental_id'] ?? '');
    }

    private function invoiceBuildingNameForJob(JobSchedule $jobSchedule): string
    {
        return $jobSchedule->building?->building_name ?? $jobSchedule->building_name ?? '';
    }

    private function findContractRentalForJobAdviceRoom(Contract $contract, $jobAdviceRoom, int $masterRentalId, ?int $roomId)
    {
        if (! empty($jobAdviceRoom->contract_rental_id)) {
            $contractRental = $contract->contractRentals()
                ->whereKey($jobAdviceRoom->contract_rental_id)
                ->first();

            if ($contractRental) {
                return $contractRental;
            }
        }

        return $this->findContractRentalForRoomAndRental($contract, $masterRentalId, $roomId);
    }

    private function findContractRentalForRoomAndRental(Contract $contract, int $masterRentalId, ?int $roomId)
    {
        return $contract->contractRentals()
            ->where('master_rental_id', $masterRentalId)
            ->where(function ($query) use ($roomId) {
                if ($roomId) {
                    $query->where('room_id', $roomId);
                }

                $query->orWhereNull('room_id');
            })
            ->orderByRaw($roomId ? 'CASE WHEN room_id = ? THEN 0 ELSE 1 END' : 'CASE WHEN room_id IS NULL THEN 0 ELSE 1 END', $roomId ? [$roomId] : [])
            ->first();
    }

    private function isJobAdviceRoomNonBillableForJob(JobSchedule $jobSchedule, $jobAdviceRoom): bool
    {
        if (in_array(strtolower((string) $jobAdviceRoom->status), self::NON_BILLABLE_ROOM_STATUSES, true)) {
            return true;
        }

        $roomQuery = JobScheduleRoom::query()
            ->where('job_schedule_id', $jobSchedule->id)
            ->where(function ($query) use ($jobAdviceRoom) {
                $query->orWhere('job_advice_room_id', $jobAdviceRoom->id);

                $roomId = $jobAdviceRoom->contractRoom?->room_id ?? $jobAdviceRoom->quotationRoom?->room_id ?? null;
                if ($roomId) {
                    $query->orWhere('room_id', $roomId);
                }

                $roomName = $jobAdviceRoom->contractRoom?->room?->room_name
                    ?? $jobAdviceRoom->quotationRoom?->room?->room_name
                    ?? $jobAdviceRoom->room_name
                    ?? null;
                if ($roomName) {
                    $query->orWhere('room_name', $roomName);
                }
            });

        return $roomQuery
            ->get()
            ->contains(function (JobScheduleRoom $room) {
                return $this->isScheduleRoomNonBillable($room);
            });
    }

    private function isScheduleRoomNonBillable(JobScheduleRoom $room): bool
    {
        $status = strtolower((string) $room->status);
        $notes = strtolower((string) $room->notes);

        return in_array($status, self::NON_BILLABLE_ROOM_STATUSES, true)
            || str_contains($notes, '[suspend]');
    }

    private function isContractRoomNonBillableForJob(JobSchedule $jobSchedule, $contractRoom): bool
    {
        $matchingJobAdviceRooms = $jobSchedule->jobAdvice?->rooms
            ?->filter(function ($jobAdviceRoom) use ($jobSchedule, $contractRoom) {
                if ((int) $jobAdviceRoom->contract_room_id !== (int) $contractRoom->id) {
                    return false;
                }

                return in_array((int) $jobSchedule->id, [
                    (int) $jobAdviceRoom->install_job_schedule_id,
                    (int) $jobAdviceRoom->service_job_schedule_id,
                    (int) $jobAdviceRoom->remove_job_schedule_id,
                ], true);
            }) ?? collect();

        if ($matchingJobAdviceRooms->contains(function ($jobAdviceRoom) {
            return in_array(strtolower((string) $jobAdviceRoom->status), self::NON_BILLABLE_ROOM_STATUSES, true);
        })) {
            return true;
        }

        $matchingJobAdviceRoomIds = $matchingJobAdviceRooms->pluck('id')->filter()->values();

        $roomQuery = JobScheduleRoom::query()
            ->where('job_schedule_id', $jobSchedule->id)
            ->where(function ($query) use ($contractRoom, $matchingJobAdviceRoomIds) {
                if ($contractRoom->room_id) {
                    $query->orWhere('room_id', $contractRoom->room_id);
                }

                if ($matchingJobAdviceRoomIds->isNotEmpty()) {
                    $query->orWhereIn('job_advice_room_id', $matchingJobAdviceRoomIds->all());
                }

                $roomName = $contractRoom->room?->room_name;
                if ($roomName) {
                    $query->orWhere('room_name', $roomName);
                }
            });

        return $roomQuery
            ->get()
            ->contains(function (JobScheduleRoom $room) {
                $status = strtolower((string) $room->status);
                $notes = strtolower((string) $room->notes);

                return in_array($status, self::NON_BILLABLE_ROOM_STATUSES, true)
                    || str_contains($notes, '[suspend]');
            });
    }

    /**
     * Create invoice detail for a job and rental
     */
    private function createInvoiceDetail(Invoice $invoice, JobSchedule $jobSchedule, array $rental): void
    {
        // NEW: We only create rental-specific detail to avoid duplication in printing.
        // invoiceRentalDetails represents the breakdown.
        // Standard invoiceDetails is for generic items (non-job etc.)

        // Price on the SQ/contract is per TOP installment, not per calendar month —
        // it is billed as-is on each invoice regardless of how many months the TOP
        // period spans (client rule, 1 Jul 2026: "harga di SQ adalah harga per TOP,
        // tidak terpengaruh dengan frekuensi rental").
        $quantity = $rental['quantity'] ?? 1;
        $unitPrice = $rental['unit_price'] ?? 0;
        $totalPrice = $rental['total_price'] ?? ($quantity * $unitPrice);

        $rentalName = $rental['rental_name'] ?? 'Service';

        $payload = [
            'master_rental_id' => $rental['master_rental_id'],
            'job_no' => $jobSchedule->job_number,
            'building_name' => $this->invoiceBuildingNameForJob($jobSchedule),
            'room_name' => $rental['room_name'] ?:($jobSchedule->room->room_name ?? $jobSchedule->room_name ?? ''),
            'rental_name' => $rentalName,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'created_by' => auth()->id(),
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('invoice_rental_details', 'qty_free')) {
            $payload['qty_free'] = $rental['qty_free'] ?? 0;
        }

        $invoice->invoiceRentalDetails()->create($payload);
    }

    /**
     * Attach verified contract files to the invoice
     */
    private function attachVerifiedContractFiles(Invoice $invoice, Contract $contract): void
    {
        // Spec Report 24 Nov 2026 poin 1: hanya file yang dipilih "Ya" sebagai pelengkap invoice.
        $contractFiles = ContractFile::where('contract_id', $contract->id)
            ->where('verification_status', 'verified')
            ->where('is_invoice_attachment', true)
            ->get();

        foreach ($contractFiles as $contractFile) {
            $cleanPath = str_replace('uploads/', '', $contractFile->file_path);

            // Ensure no double attachment
            $exists = InvoiceFile::where('invoice_id', $invoice->id)
                ->where('file_path', $cleanPath)
                ->exists();

            if (! $exists) {
                InvoiceFile::create([
                    'invoice_id' => $invoice->id,
                    'file_type' => 'attachment',
                    'file_name' => 'Contract File - '.$contractFile->file_name,
                    'file_path' => $cleanPath,
                    'description' => "File Kontrak #{$contract->contract_number}: {$contractFile->file_type}",
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }

    /**
     * Update invoice totals
     */
    private function updateInvoiceTotals(Invoice $invoice): void
    {
        $subtotal = $invoice->invoiceDetails()->sum('total_price') + $invoice->invoiceRentalDetails()->sum('total_price');

        $resolver = app(InvoiceTaxResolver::class);
        $invoice->loadMissing('customer');
        $context = $resolver->resolve($invoice->customer, $invoice->tax_code, $invoice->invoice_date);
        $taxAmount = $resolver->taxAmount((float) $subtotal, $context);
        $totalAmount = round($subtotal + $taxAmount, 2);

        $invoice->forceFill([
            'subtotal' => $subtotal,
            'tax_setting_id' => $context['default_vat_setting']?->id,
            'tax_code' => $context['tax_code'],
            'tax_obligation' => $context['applies_ppn'],
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'grand_total' => $totalAmount,
            'outstanding' => $totalAmount,
            'total_paid' => 0,
        ])->save();
    }

    /**
     * Calculate due date
     */
    private function calculateDueDate(Carbon $invoiceDate, int $paymentTerms): Carbon
    {
        return $invoiceDate->copy()->addDays($paymentTerms);
    }

    /**
     * Get building information for invoice
     */
    private function getBuildingInfo(Contract $contract): string
    {
        $buildings = $contract->contractRooms->pluck('roomRentalUnit.building.building_name')->filter()->unique();

        return $buildings->implode(', ');
    }

    /**
     * Generate invoice for multiple rental periods
     */
    public function generateInvoicesForMultiplePeriods(int $contractId, array $rentalPeriods): array
    {
        $results = [];

        foreach ($rentalPeriods as $period) {
            $result = $this->autoGenerateInvoiceForRentalPeriod(
                $contractId,
                $period['rental_period'],
                Carbon::parse($period['period_start']),
                Carbon::parse($period['period_end'])
            );

            $results[] = $result;
        }

        return [
            'success' => true,
            'results' => $results,
            'total_periods' => count($rentalPeriods),
            'successful_generations' => collect($results)->where('success', true)->count(),
        ];
    }

    /**
     * Get rental periods for contract
     */
    public function getRentalPeriodsForContract(int $contractId): array
    {
        $contract = Contract::with('quotation')->findOrFail($contractId);
        $periods = [];

        $currentDate = $contract->start_date;
        $endDate = $contract->end_date;
        $periodCounter = 1;
        $periodType = $contract->invoice_period_type ?? 'contract_date';
        $topIntervalMonths = max(1, (int) ($contract->top_interval_months ?? 1));

        // Handle "contract_date" & "service" logic (iterate by TOP interval from Start Date)
        // Note: For "service", the period calculation is same as contract_date,
        // but the invoice DATE logic in createInvoiceForRentalPeriod differs.
        if ($periodType === 'contract_date' || $periodType === 'service') {
            while ($currentDate <= $endDate) {
                $periodEnd = $currentDate->copy()->addMonths($topIntervalMonths)->subDay();
                if ($periodEnd > $endDate) {
                    $periodEnd = $endDate;
                }

                $periods[] = [
                    'rental_period' => "Period {$periodCounter}",
                    'period_start' => $currentDate->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'period_number' => $periodCounter,
                    'status' => $this->getPeriodStatus($contract, $currentDate, $periodEnd, $periodCounter),
                ];

                $currentDate = $currentDate->copy()->addMonths($topIntervalMonths);
                $periodCounter++;
            }
        }
        // Handle "monthly" logic (Calendar Month: 1st to End of Month)
        elseif ($periodType === 'monthly') {
            while ($currentDate <= $endDate) {
                // Determine end of this calendar month
                $endOfMonth = $currentDate->copy()->endOfMonth();
                $periodEnd = ($endOfMonth > $endDate) ? $endDate : $endOfMonth;

                $periods[] = [
                    'rental_period' => "Period {$periodCounter}",
                    'period_start' => $currentDate->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    // Deliberately no 'period_number' here: the sequence-derived invoice_period
                    // formula (computeInvoicePeriodForJob) assumes TOP-interval-sized buckets,
                    // which doesn't hold for this per-calendar-month period type. Downstream
                    // callers reading `$period['period_number'] ?? null` fall back to the
                    // original calendar-window-only matching for 'monthly' contracts.
                    'status' => $this->getPeriodStatus($contract, $currentDate, $periodEnd),
                ];

                // Next period starts at the beginning of the next month
                $currentDate = $currentDate->copy()->addMonth()->startOfMonth();
                $periodCounter++;
            }
        }

        return $periods;
    }

    /**
     * Get status of rental period
     */
    private function getPeriodStatus(Contract $contract, Carbon $periodStart, Carbon $periodEnd, ?int $periodNumber = null): string
    {
        $totalJobs = $this->getTotalJobsCount($contract, $periodStart, $periodEnd, $periodNumber);
        $completedJobs = $this->getCompletedJobsCount($contract, $periodStart, $periodEnd, $periodNumber);

        if ($totalJobs === 0) {
            return 'no_jobs';
        } elseif ($completedJobs === $totalJobs) {
            return 'completed';
        } elseif ($completedJobs > 0) {
            return 'partial';
        } else {
            return 'pending';
        }
    }

    /**
     * Bulk eligibility filter for the "Regenerate Invoice" contract picker: one indexed
     * query returning contract IDs that have at least one billable-completed job in the
     * given lookback window. On staging there are ~5,000 active contracts; the vast
     * majority have no completed job in the last 12 months at all, so this single query
     * (job_schedules join job_advices, ~0.3s on staging) is what the picker's contract
     * list is scoped to.
     *
     * A precise per-contract check (correct period-boundary math + rental-trigger-type
     * matching, mirroring getRentalPeriodsForContract()) was tried and removed: even
     * limited to a 12-month lookback with early-exit, it still took 3s+ PER CONTRACT
     * (getPeriodStatus()'s heavily-eager-loaded getCandidateInvoiceJobsQuery(), run up to
     * ~12x for a contract with no qualifying period) — confirmed via direct timing on
     * staging, it still hadn't finished for just 37 candidate contracts after several
     * minutes. "Has a billable-completed job in the window" is a good enough signal for
     * this manual admin picker: the exact per-period trigger/duplicate check still runs
     * correctly when the operator actually proceeds with generation (checkExistingInvoice
     * in autoGenerateInvoiceForRentalPeriod), so an occasional over-inclusive candidate
     * here just no-ops there instead of corrupting anything.
     */
    public function contractIdsWithRecentCompletedJobs(Carbon $sinceDate): \Illuminate\Support\Collection
    {
        return JobSchedule::query()
            ->join('job_advices', 'job_advices.id', '=', 'job_schedules.job_advice_id')
            ->whereIn('job_schedules.status', self::BILLABLE_COMPLETED_STATUSES)
            ->whereNotNull('job_schedules.ba_date')
            ->where('job_schedules.schedule_date', '>=', $sinceDate)
            ->whereNull('job_schedules.catalyst_backfill_at')
            ->whereNotNull('job_advices.contract_id')
            ->distinct()
            ->pluck('job_advices.contract_id');
    }

    /**
     * Attempt to auto-generate invoice for a contract if conditions are met
     * (Real-time trigger for Job Completion)
     */
    public function attemptAutoInvoiceForContract(int $contractId): void
    {
        try {
            $contract = Contract::with(['billingGroups.buildings', 'contractRooms.room'])->find($contractId);
            if (! $contract) {
                return;
            }

            $activeBillingGroups = $contract->billingGroups->where('is_active', true)->values();

            // Get all rental periods for this contract
            $periods = $this->getRentalPeriodsForContract($contractId);

            foreach ($periods as $period) {
                $periodStart = Carbon::parse($period['period_start']);
                $periodEnd = Carbon::parse($period['period_end']);

                // Klien 27 Nov 2026: periode ke-2 dst mengikuti TOP penagihan (tanggal invoice
                // sebelumnya + interval TOP), bukan jadwal service. Hanya bila periode
                // sebelumnya sudah punya invoice di ERP; selain itu tetap logika lama.
                if (($period['period_number'] ?? null) !== null
                    && $period['period_number'] >= 2
                    && $this->attemptTopAnchoredPeriodInvoice($contract, $activeBillingGroups, $period, $periodStart, $periodEnd)) {
                    continue;
                }

                // Klien 27 Nov 2026 (kondisi 4c): lebih dari satu Billing Group dinilai per
                // Billing Group, jadi cukup ada job yang sudah siap di periode ini.
                if ($activeBillingGroups->count() > 1) {
                    if (in_array($period['status'], ['completed', 'partial'], true)) {
                        $this->attemptAutoInvoiceForMultiBillingGroupPeriod($contract, $activeBillingGroups, $period, $periodStart, $periodEnd);
                    }

                    continue;
                }

                // We only care about periods whose ASSIGNED trigger jobs are all completed
                if ($period['status'] !== 'completed') {
                    continue;
                }

                // Check if invoice already exists to avoid redundant processing
                $exists = $this->checkExistingInvoice(
                    $contractId,
                    $contract->contract_number ?? '',
                    $period['rental_period'],
                    $periodStart,
                    $periodEnd
                );

                if (! $exists) {
                    Log::info("Real-time Invoice Trigger: Attempting to generate invoice for Contract {$contractId}, Period {$period['rental_period']}");

                    $result = $this->autoGenerateInvoiceForRentalPeriod(
                        $contractId,
                        $period['rental_period'],
                        $periodStart,
                        $periodEnd
                    );

                    if ($result['success']) {
                        Log::info("Real-time Invoice Trigger: [SUCCESS] Generated Invoice {$result['invoice']->invoice_number}");
                    } else {
                        Log::warning('Real-time Invoice Trigger: [FAILED] '.$result['message']);
                    }
                } else {
                    // A room/rental whose job completed after this period's invoice was
                    // first drafted would otherwise never get billed. Draft → baris
                    // ditambahkan; sudah tidak Draft → invoice susulan bertanggal sama.
                    $this->supplementPeriodInvoice($contract, $period['rental_period'], $periodStart, $periodEnd, $period['period_number'] ?? null, null, null, false);
                }
            }

        } catch (\Exception $e) {
            Log::error('Real-time Invoice Trigger Error: '.$e->getMessage());
        }
    }

    /**
     * Real-time trigger handling for a completed period on a contract with more than one
     * active billing group. Checks each billing-group bucket (plus the default/leftover
     * bucket) independently: if any bucket is missing its invoice, delegates to
     * autoGenerateInvoiceForRentalPeriod() (which internally creates whichever buckets are
     * still missing for the period in one pass); buckets that already have a draft invoice
     * get backfilled with any newly-completed room's rental lines, scoped to that bucket's
     * own rooms only.
     */
    private function attemptAutoInvoiceForMultiBillingGroupPeriod(Contract $contract, \Illuminate\Support\Collection $activeBillingGroups, array $period, Carbon $periodStart, Carbon $periodEnd): void
    {
        $roomBucketMap = $this->resolveContractRoomBillingGroupMap($contract, $activeBillingGroups);
        $allRoomIds = $contract->contractRooms->pluck('id')->all();
        $leftoverRoomIds = array_values(array_diff($allRoomIds, array_keys($roomBucketMap)));

        $buckets = $activeBillingGroups
            ->map(fn (BillingGroup $billingGroup) => [
                'billing_group_id' => $billingGroup->id,
                'room_ids' => array_keys(array_filter($roomBucketMap, fn ($bgId) => $bgId === $billingGroup->id)),
            ])
            ->values()
            ->all();
        $buckets[] = ['billing_group_id' => null, 'room_ids' => $leftoverRoomIds];

        $anyMissing = false;

        foreach ($buckets as $bucket) {
            $existing = $this->checkExistingInvoice(
                $contract->id,
                $contract->contract_number ?? '',
                $period['rental_period'],
                $periodStart,
                $periodEnd,
                $bucket['billing_group_id'],
                true
            );

            if (! $this->isBillingBucketReady($contract, $periodStart, $periodEnd, $period['period_number'] ?? null, $bucket['room_ids'])) {
                continue;
            }

            if (! $existing) {
                $anyMissing = true;
            } else {
                $this->supplementPeriodInvoice(
                    $contract,
                    $period['rental_period'],
                    $periodStart,
                    $periodEnd,
                    $period['period_number'] ?? null,
                    $bucket['room_ids'],
                    $bucket['billing_group_id'] ? $activeBillingGroups->firstWhere('id', $bucket['billing_group_id']) : null,
                    true
                );
            }
        }

        if (! $anyMissing) {
            return;
        }

        Log::info("Real-time Invoice Trigger: Attempting to generate invoice(s) for Contract {$contract->id}, Period {$period['rental_period']} (multi billing group)");

        $result = $this->autoGenerateInvoiceForRentalPeriod(
            $contract->id,
            $period['rental_period'],
            $periodStart,
            $periodEnd
        );

        if ($result['success']) {
            Log::info('Real-time Invoice Trigger: [SUCCESS] Generated invoice(s): '.
                collect($result['invoices'] ?? [$result['invoice']])->pluck('invoice_number')->implode(', '));
        } else {
            Log::warning('Real-time Invoice Trigger: [FAILED] '.($result['message'] ?? ''));
        }
    }

    /**
     * Add any rental lines for completed jobs in the period that are missing from an
     * already-drafted invoice (e.g. a second room's job completed after the invoice for
     * this period was first generated from the first room's job).
     *
     * $allowedContractRoomIds scopes the backfill to a specific billing-group bucket's rooms
     * (see attemptAutoInvoiceForMultiBillingGroupPeriod()); null bills every eligible room,
     * which is the original single-invoice behavior.
     */
    /**
     * Klien 27 Nov 2026 (poin 3 & jawaban 4): periode invoice ke-2 dst mengikuti TOP penagihan.
     *  - Tanggal yang diharapkan = tanggal invoice pertama periode sebelumnya + interval TOP.
     *  - Before Service: terbit pada tanggal itu, tanpa menunggu service.
     *  - After Service: job service ter-assign di periode ini harus sudah selesai + BA; tanggal
     *    invoice = yang lebih akhir antara tanggal harapan dan BA Date service terakhirnya
     *    (service 3 Feb terlaksana 5 Feb → invoice 5 Feb, berikutnya ikut 5 Maret). Periode
     *    tanpa jadwal service (rental 3 bulan 1x di bulan ke-2/3) dianggap sudah service.
     *  - Baris: rental dari job yang selesai di periode ini + rental periode sebelumnya yang
     *    masih aktif di kontrak (tidak di-suspend), harga dari kontrak.
     *
     * @return bool true bila periode ini diatur aturan TOP (ada invoice periode sebelumnya),
     *              false agar pemanggil memakai logika lama.
     */
    private function attemptTopAnchoredPeriodInvoice(Contract $contract, \Illuminate\Support\Collection $activeBillingGroups, array $period, Carbon $periodStart, Carbon $periodEnd): bool
    {
        $timing = $this->getInvoiceTiming($contract);
        if (! in_array($timing, ['before_service', 'after_service'], true)) {
            return false;
        }

        $periodNumber = (int) $period['period_number'];
        $previousLabel = 'Period '.($periodNumber - 1);
        $topMonths = max(1, (int) ($contract->top_interval_months ?? 1));

        if ($activeBillingGroups->count() > 1) {
            $roomBucketMap = $this->resolveContractRoomBillingGroupMap($contract, $activeBillingGroups);
            $leftoverRoomIds = array_values(array_diff($contract->contractRooms->pluck('id')->all(), array_keys($roomBucketMap)));
            $buckets = $activeBillingGroups
                ->map(fn (BillingGroup $billingGroup) => [
                    'billing_group' => $billingGroup,
                    'room_ids' => array_keys(array_filter($roomBucketMap, fn ($bgId) => $bgId === $billingGroup->id)),
                    'scoped' => true,
                ])
                ->values()
                ->all();
            $buckets[] = ['billing_group' => null, 'room_ids' => $leftoverRoomIds, 'scoped' => true];
        } else {
            $buckets = [['billing_group' => $contract->billingGroup, 'room_ids' => null, 'scoped' => false]];
        }

        $anyAnchor = false;

        foreach ($buckets as $bucket) {
            $previousInvoices = $this->periodInvoices($contract, $previousLabel, $bucket['billing_group'], $bucket['scoped']);
            if ($previousInvoices->isEmpty()) {
                continue;
            }
            $anyAnchor = true;

            if ($this->periodInvoices($contract, $period['rental_period'], $bucket['billing_group'], $bucket['scoped'])->isNotEmpty()) {
                $this->supplementPeriodInvoice($contract, $period['rental_period'], $periodStart, $periodEnd, $periodNumber, $bucket['room_ids'], $bucket['billing_group'], $bucket['scoped']);

                continue;
            }

            if ($contract->hold_invoice) {
                continue;
            }

            $expectedDate = Carbon::parse($previousInvoices->first()->invoice_date)->addMonthsNoOverflow($topMonths)->startOfDay();
            if (now()->startOfDay()->lt($expectedDate)) {
                continue;
            }

            $invoiceDate = $expectedDate->copy();
            if ($timing === 'after_service') {
                $assigned = $this->getAssignedInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber);
                if ($bucket['room_ids'] !== null) {
                    $assigned = $assigned->filter(fn (JobSchedule $jobSchedule) => $this->jobTouchesContractRooms($jobSchedule, $bucket['room_ids']));
                }
                $ready = $assigned->filter(fn (JobSchedule $jobSchedule) => $this->isReadyTriggerJob($contract, $jobSchedule));
                if ($ready->count() !== $assigned->count()) {
                    continue;
                }

                $lastBaDate = $ready->pluck('ba_date')->filter()->map(fn ($date) => Carbon::parse($date))->max();
                if ($lastBaDate && $lastBaDate->gt($invoiceDate)) {
                    $invoiceDate = $lastBaDate->copy()->startOfDay();
                }
            }

            $this->createTopAnchoredInvoice($contract, $period['rental_period'], $periodStart, $periodEnd, $periodNumber, $bucket, $previousInvoices, $invoiceDate);
        }

        return $anyAnchor;
    }

    private function periodInvoices(Contract $contract, string $rentalPeriod, ?BillingGroup $billingGroup, bool $scopeByBillingGroup): \Illuminate\Support\Collection
    {
        return Invoice::where(function ($query) use ($contract) {
            $query->where('contract_id', $contract->id)
                ->orWhere('contract_number', $contract->contract_number ?? '');
        })
            ->where('invoice_status', '!=', Invoice::STATUS_CANCELLED)
            ->where('period_invoice', $rentalPeriod)
            ->when($scopeByBillingGroup, function ($query) use ($billingGroup) {
                $billingGroup
                    ? $query->where('billing_group_id', $billingGroup->id)
                    : $query->whereNull('billing_group_id');
            })
            ->with('invoiceRentalDetails')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();
    }

    private function createTopAnchoredInvoice(Contract $contract, string $rentalPeriod, Carbon $periodStart, Carbon $periodEnd, int $periodNumber, array $bucket, \Illuminate\Support\Collection $previousInvoices, Carbon $invoiceDate): ?Invoice
    {
        $jobPairs = $this->computeBillableRentalPairs($contract, $periodStart, $periodEnd, [], $bucket['room_ids'], $periodNumber);
        $billedKeys = array_map(fn ($pair) => $this->invoiceRentalBillingKey($pair['rental'], $this->invoiceBuildingNameForJob($pair['job'])), $jobPairs);
        $carried = $this->carriedForwardRentalLines($contract, $previousInvoices, $billedKeys, $invoiceDate);

        if (empty($jobPairs) && empty($carried)) {
            return null;
        }

        return DB::transaction(function () use ($contract, $rentalPeriod, $periodStart, $periodEnd, $periodNumber, $bucket, $jobPairs, $carried, $invoiceDate) {
            $invoice = $this->createInvoiceForRentalPeriod(
                $contract,
                $rentalPeriod,
                $periodStart,
                $periodEnd,
                $bucket['billing_group'],
                $invoiceDate,
                "Tanggal mengikuti TOP penagihan (invoice periode sebelumnya + interval TOP)."
            );

            $jobIds = [];
            foreach ($jobPairs as $pair) {
                $this->createInvoiceDetail($invoice, $pair['job'], $pair['rental']);
                $jobIds[] = $pair['job']->id;
            }

            foreach ($carried as $line) {
                $payload = [
                    'master_rental_id' => $line['master_rental_id'],
                    'job_no' => null,
                    'building_name' => $line['building_name'],
                    'room_name' => $line['room_name'],
                    'rental_name' => $line['rental_name'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total_price' => $line['total_price'],
                    'created_by' => auth()->id(),
                ];
                if (Schema::hasColumn('invoice_rental_details', 'qty_free')) {
                    $payload['qty_free'] = $line['qty_free'];
                }
                $invoice->invoiceRentalDetails()->create($payload);
            }

            if (! empty($jobIds)) {
                $this->attachApprovedBaFiles($invoice, $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber)
                    ->whereIn('id', array_unique($jobIds))
                    ->values());
            }
            $this->attachVerifiedContractFiles($invoice, $contract);
            $invoice->refresh();
            $this->updateInvoiceTotals($invoice);

            Log::info("Real-time Invoice Trigger: TOP-anchored invoice {$invoice->invoice_number} for contract {$contract->contract_number} {$rentalPeriod} dated {$invoiceDate->toDateString()}");

            return $invoice;
        });
    }

    /**
     * Rental yang ditagih di periode sebelumnya dan masih aktif di kontrak (ruangan masih ada,
     * tidak di-suspend pada tanggal invoice, rental masih terdaftar). Harga diambil dari kontrak.
     *
     * @return array<int, array<string, mixed>>
     */
    private function carriedForwardRentalLines(Contract $contract, \Illuminate\Support\Collection $previousInvoices, array $billedKeys, Carbon $invoiceDate): array
    {
        $contract->loadMissing('contractRooms.room.building');
        $roomsByKey = [];
        foreach ($contract->contractRooms as $contractRoom) {
            $buildingName = $contractRoom->room?->building?->building_name ?? '';
            $key = strtolower(trim((string) $buildingName)).'|'.strtolower(trim((string) ($contractRoom->room?->room_name ?? '')));
            $roomsByKey[$key] ??= $contractRoom;
        }

        $suspensions = app(\App\Services\Operational\ContractRoomSuspensionService::class);
        $lines = [];

        foreach ($previousInvoices->flatMap(fn (Invoice $invoice) => $invoice->invoiceRentalDetails) as $detail) {
            $billingKey = $this->invoiceRentalBillingKey([
                'room_name' => $detail->room_name,
                'master_rental_id' => $detail->master_rental_id,
            ], $detail->building_name);
            if (in_array($billingKey, $billedKeys, true)) {
                continue;
            }

            $contractRoom = $roomsByKey[strtolower(trim((string) $detail->building_name)).'|'.strtolower(trim((string) $detail->room_name))] ?? null;
            if (! $contractRoom) {
                continue; // ruangan sudah tidak ada di kontrak
            }

            if ($suspensions->isContractRoomSuspended((int) $contractRoom->id, $invoiceDate->toDateString())) {
                continue;
            }

            $contractRental = $this->findContractRentalForRoomAndRental($contract, (int) $detail->master_rental_id, $contractRoom->room_id ? (int) $contractRoom->room_id : null);
            if (! $contractRental) {
                continue; // rental sudah diganti / tidak terdaftar lagi
            }

            $billedKeys[] = $billingKey;
            $lines[] = [
                'master_rental_id' => (int) $detail->master_rental_id,
                'building_name' => $detail->building_name,
                'room_name' => $detail->room_name,
                'rental_name' => $detail->rental_name,
                'quantity' => $contractRental->quantity ?? $detail->quantity ?? 1,
                'qty_free' => $contractRental->qty_free ?? 0,
                'unit_price' => $contractRental->unit_price ?? $detail->unit_price ?? 0,
                'total_price' => $contractRental->total_price
                    ?? (($contractRental->quantity ?? 1) * ($contractRental->unit_price ?? 0)),
            ];
        }

        return $lines;
    }

    /**
     * Klien 27 Nov 2026 (kondisi 4a/4b): ruangan yang selesai belakangan (job susulan / baru
     * di-assign) masuk ke invoice periode yang sama bila masih Draft; bila invoice periode itu
     * sudah tidak Draft (approved / terkirim), dibuat invoice susulan dengan tanggal invoice
     * pertama periode itu ("invoice mengacu pada kontrak, bukan selesainya job").
     *
     * Dedup memakai semua invoice (non-cancelled) periode itu di bucket yang sama.
     *
     * @return Invoice|null invoice yang menerima baris baru
     */
    private function supplementPeriodInvoice(Contract $contract, string $rentalPeriod, Carbon $periodStart, Carbon $periodEnd, ?int $periodNumber, ?array $allowedContractRoomIds, ?BillingGroup $billingGroup, bool $scopeByBillingGroup): ?Invoice
    {
        $invoices = Invoice::where(function ($query) use ($contract) {
            $query->where('contract_id', $contract->id)
                ->orWhere('contract_number', $contract->contract_number ?? '');
        })
            ->where('invoice_status', '!=', Invoice::STATUS_CANCELLED)
            ->where('period_invoice', $rentalPeriod)
            ->when($scopeByBillingGroup, function ($query) use ($billingGroup) {
                $billingGroup
                    ? $query->where('billing_group_id', $billingGroup->id)
                    : $query->whereNull('billing_group_id');
            })
            ->with('invoiceRentalDetails')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        if ($invoices->isEmpty()) {
            return null;
        }

        $billedRentals = $invoices
            ->flatMap(fn (Invoice $invoice) => $invoice->invoiceRentalDetails)
            ->map(fn ($detail) => $this->invoiceRentalBillingKey([
                'room_name' => $detail->room_name,
                'master_rental_id' => $detail->master_rental_id,
            ], $detail->building_name))
            ->all();

        $pairs = $this->computeBillableRentalPairs($contract, $periodStart, $periodEnd, $billedRentals, $allowedContractRoomIds, $periodNumber);
        if (empty($pairs)) {
            return null;
        }

        $primary = $invoices->first();
        $target = $invoices->first(fn (Invoice $invoice) => $invoice->invoice_status === Invoice::STATUS_DRAFT);

        return DB::transaction(function () use ($contract, $rentalPeriod, $periodStart, $periodEnd, $periodNumber, $pairs, $primary, $target, $billingGroup) {
            $isNew = ! $target;
            if ($isNew) {
                $target = $this->createInvoiceForRentalPeriod(
                    $contract,
                    $rentalPeriod,
                    $periodStart,
                    $periodEnd,
                    $billingGroup ?? ($primary->billing_group_id ? BillingGroup::find($primary->billing_group_id) : null),
                    Carbon::parse($primary->invoice_date),
                    "Invoice susulan untuk {$rentalPeriod} (invoice pertama {$primary->invoice_number})."
                );
            }

            $jobIds = [];
            foreach ($pairs as $pair) {
                $this->createInvoiceDetail($target, $pair['job'], $pair['rental']);
                $jobIds[] = $pair['job']->id;
            }

            $contributingJobs = $this->getReadyInvoiceTriggerJobsInPeriod($contract, $periodStart, $periodEnd, $periodNumber)
                ->whereIn('id', array_unique($jobIds))
                ->values();
            $this->attachApprovedBaFiles($target, $contributingJobs);
            if ($isNew) {
                $this->attachVerifiedContractFiles($target, $contract);
            }

            $target->refresh();
            $this->updateInvoiceTotals($target);

            Log::info(($isNew ? 'Real-time Invoice Trigger: created supplementary invoice ' : 'Real-time Invoice Trigger: added late room line(s) into draft invoice ')
                .$target->invoice_number." for contract {$contract->contract_number} {$rentalPeriod}");

            return $target;
        });
    }

    private function refreshDraftInvoiceRentalDetails(Invoice $invoice, Carbon $periodStart, Carbon $periodEnd, ?array $allowedContractRoomIds = null, ?int $periodNumber = null): void
    {
        $contract = $invoice->contract_id ? Contract::find($invoice->contract_id) : null;
        if (! $contract) {
            return;
        }

        $invoice->loadMissing('invoiceRentalDetails');
        $billedRentals = $invoice->invoiceRentalDetails
            ->map(fn ($detail) => $this->invoiceRentalBillingKey([
                'room_name' => $detail->room_name,
                'master_rental_id' => $detail->master_rental_id,
            ], $detail->building_name))
            ->all();

        DB::transaction(function () use ($invoice, $contract, $periodStart, $periodEnd, $billedRentals, $allowedContractRoomIds, $periodNumber) {
            $existingCount = $invoice->invoiceRentalDetails()->count();

            $pairs = $this->computeBillableRentalPairs($contract, $periodStart, $periodEnd, $billedRentals, $allowedContractRoomIds, $periodNumber);
            foreach ($pairs as $pair) {
                $this->createInvoiceDetail($invoice, $pair['job'], $pair['rental']);
            }

            $invoice->refresh();
            if ($invoice->invoiceRentalDetails()->count() > $existingCount) {
                $this->updateInvoiceTotals($invoice);
                Log::info("Real-time Invoice Trigger: backfilled missing rental line(s) into existing draft invoice {$invoice->invoice_number}");
            }
        });
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceRentalDetail;
use App\Models\JobSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairInvoiceRentalJobTriggers extends Command
{
    protected $signature = 'finance:repair-invoice-rental-job-triggers
                            {--invoice-id=* : Specific invoice id, repeatable}
                            {--invoice-number=* : Specific invoice number, repeatable}
                            {--contract-number= : Limit by contract number}
                            {--include-finalized : Allow approved/sent/tax approved invoices}
                            {--apply : Apply the repair. Default is dry-run}';

    protected $description = 'Repair invoice rental detail job numbers so Unit+Refill/Refill Only use CSR and Unit Only uses IR';

    private const FINAL_STATUSES = ['approved', 'sent', 'tax_approved', 'paid'];
    private const COMPLETED_STATUSES = ['completed', 'done_job', 'dpf'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $includeFinalized = (bool) $this->option('include-finalized');

        if (! $apply) {
            $this->warn('DRY RUN mode. No database changes will be made. Re-run with --apply to persist repairs.');
        }

        $query = Invoice::with(['invoiceRentalDetails.masterRental'])
            ->whereHas('invoiceRentalDetails')
            ->where('invoice_status', '!=', Invoice::STATUS_CANCELLED);

        $invoiceIds = collect($this->option('invoice-id'))->filter()->map(fn ($id) => (int) $id)->values();
        if ($invoiceIds->isNotEmpty()) {
            $query->whereIn('id', $invoiceIds->all());
        }

        $invoiceNumbers = collect($this->option('invoice-number'))->filter()->values();
        if ($invoiceNumbers->isNotEmpty()) {
            $query->whereIn('invoice_number', $invoiceNumbers->all());
        }

        if ($contractNumber = $this->option('contract-number')) {
            $query->where('contract_number', $contractNumber);
        }

        $invoices = $query->orderBy('id')->get();

        if ($invoices->isEmpty()) {
            $this->warn('No matching invoices found.');
            return self::SUCCESS;
        }

        $rows = [];
        $totals = [
            'scanned' => 0,
            'planned' => 0,
            'applied' => 0,
            'skipped' => 0,
        ];

        DB::transaction(function () use ($invoices, $apply, $includeFinalized, &$rows, &$totals) {
            foreach ($invoices as $invoice) {
                if (! $includeFinalized && in_array((string) $invoice->invoice_status, self::FINAL_STATUSES, true)) {
                    $totals['skipped']++;
                    $rows[] = $this->row($invoice, null, null, null, null, 'SKIP', 'finalized invoice; use --include-finalized if approved by finance');
                    continue;
                }

                foreach ($invoice->invoiceRentalDetails as $detail) {
                    $totals['scanned']++;
                    $analysis = $this->analyzeDetail($invoice, $detail);

                    if ($analysis['action'] !== 'repair') {
                        $totals['skipped']++;
                        $rows[] = $this->row(
                            $invoice,
                            $detail,
                            $analysis['current_job_no'] ?? $detail->job_no,
                            $analysis['target_job_no'] ?? null,
                            $analysis['rental_type'] ?? null,
                            'SKIP',
                            $analysis['reason']
                        );
                        continue;
                    }

                    $totals['planned']++;

                    if ($apply) {
                        $payload = [
                            'job_no' => $analysis['target_job_no'],
                        ];

                        if ($analysis['target_building_name'] && Schema::hasColumn('invoice_rental_details', 'building_name')) {
                            $payload['building_name'] = $analysis['target_building_name'];
                        }

                        if (Schema::hasColumn('invoice_rental_details', 'updated_by')) {
                            $payload['updated_by'] = auth()->id();
                        }

                        $detail->update($payload);
                        $totals['applied']++;
                    }

                    $rows[] = $this->row(
                        $invoice,
                        $detail,
                        $analysis['current_job_no'],
                        $analysis['target_job_no'],
                        $analysis['rental_type'],
                        $apply ? 'FIXED' : 'PLAN',
                        $analysis['reason']
                    );
                }
            }

            if (! $apply) {
                DB::rollBack();
            }
        });

        $this->table(
            ['Status', 'Invoice ID', 'Invoice No', 'Detail ID', 'Rental Type', 'Current Job', 'Target Job', 'Note'],
            $rows
        );

        $this->newLine();
        $this->line('Scanned details : ' . $totals['scanned']);
        $this->line('Repair plans    : ' . $totals['planned']);
        $this->line('Applied repairs : ' . ($apply ? $totals['applied'] : 'dry-run'));
        $this->line('Skipped         : ' . $totals['skipped']);

        if (! $apply) {
            $this->warn('Dry run only. Re-run with --apply after reviewing the PLAN rows.');
        }

        return self::SUCCESS;
    }

    private function analyzeDetail(Invoice $invoice, InvoiceRentalDetail $detail): array
    {
        $rentalType = $this->normalize($detail->masterRental?->rental_type);

        if (! in_array($rentalType, ['unit_refill', 'refill_only', 'unit_only'], true)) {
            return [
                'action' => 'skip',
                'reason' => 'rental type is not handled by this repair',
                'rental_type' => $rentalType ?: '-',
            ];
        }

        $currentJob = JobSchedule::with(['jobAdvice.rooms.rentalProduct'])
            ->where('job_number', $detail->job_no)
            ->first();

        if (! $currentJob) {
            return [
                'action' => 'skip',
                'reason' => 'current job number not found',
                'rental_type' => $rentalType,
                'current_job_no' => $detail->job_no,
            ];
        }

        $expectedSlot = $rentalType === 'unit_only' ? 'install' : 'service';
        $currentSlot = $this->jobSlot($currentJob);

        if ($currentSlot === $expectedSlot) {
            return [
                'action' => 'skip',
                'reason' => 'already uses the correct job type',
                'rental_type' => $rentalType,
                'current_job_no' => $currentJob->job_number,
            ];
        }

        $targetJob = $this->findTargetJob($invoice, $detail, $currentJob, $expectedSlot);

        if (! $targetJob) {
            return [
                'action' => 'skip',
                'reason' => 'matching target job was not found',
                'rental_type' => $rentalType,
                'current_job_no' => $currentJob->job_number,
            ];
        }

        if (! in_array((string) $targetJob->status, self::COMPLETED_STATUSES, true) || empty($targetJob->ba_date)) {
            return [
                'action' => 'skip',
                'reason' => 'target job is not completed with BA date',
                'rental_type' => $rentalType,
                'current_job_no' => $currentJob->job_number,
                'target_job_no' => $targetJob->job_number,
            ];
        }

        return [
            'action' => 'repair',
            'reason' => $rentalType === 'unit_only'
                ? 'Unit Only invoice must use IR/install job'
                : 'Unit+Refill/Refill Only invoice must use CSR/service job',
            'rental_type' => $rentalType,
            'current_job_no' => $currentJob->job_number,
            'target_job_no' => $targetJob->job_number,
            'target_building_name' => $targetJob->building_name,
        ];
    }

    private function findTargetJob(Invoice $invoice, InvoiceRentalDetail $detail, JobSchedule $currentJob, string $expectedSlot): ?JobSchedule
    {
        $linkedJob = $this->findTargetJobFromAdviceRooms($detail, $currentJob, $expectedSlot);

        if ($linkedJob) {
            return $linkedJob;
        }

        $query = JobSchedule::query()
            ->where('contract_number', $invoice->contract_number)
            ->where('job_advice_id', $currentJob->job_advice_id);

        if ($currentJob->room_id) {
            $query->where('room_id', $currentJob->room_id);
        }

        $types = $expectedSlot === 'install'
            ? ['install', 'installation', 'installation_report']
            : ['service', 'service_first', 'service_routine', 'csr', 'customer_service_report'];

        return $query
            ->whereIn(DB::raw("LOWER(COALESCE(type, ''))"), $types)
            ->orderBy('schedule_date')
            ->orderBy('id')
            ->first();
    }

    private function findTargetJobFromAdviceRooms(InvoiceRentalDetail $detail, JobSchedule $currentJob, string $expectedSlot): ?JobSchedule
    {
        $rooms = $currentJob->jobAdvice?->rooms;

        if (! $rooms instanceof Collection || $rooms->isEmpty()) {
            return null;
        }

        $currentJobId = (int) $currentJob->id;

        foreach ($rooms as $room) {
            $isCurrentRoom = in_array($currentJobId, [
                (int) $room->install_job_schedule_id,
                (int) $room->service_job_schedule_id,
                (int) $room->remove_job_schedule_id,
            ], true);

            if (! $isCurrentRoom) {
                continue;
            }

            if ($detail->master_rental_id && $room->rental_product_id && (int) $room->rental_product_id !== (int) $detail->master_rental_id) {
                continue;
            }

            $targetJobId = $expectedSlot === 'install'
                ? $room->install_job_schedule_id
                : $room->service_job_schedule_id;

            if ($targetJobId) {
                return JobSchedule::find($targetJobId);
            }
        }

        return null;
    }

    private function jobSlot(JobSchedule $jobSchedule): string
    {
        $type = $this->normalize($jobSchedule->type);

        if (in_array($type, ['install', 'installation', 'installation_report', 'ir'], true)) {
            return 'install';
        }

        if (in_array($type, ['service', 'service_first', 'service_routine', 'csr', 'customer_service_report'], true)) {
            return 'service';
        }

        return $type;
    }

    private function normalize(?string $value): string
    {
        return strtolower(trim(str_replace('-', '_', (string) $value)));
    }

    private function row(Invoice $invoice, ?InvoiceRentalDetail $detail, ?string $currentJobNo, ?string $targetJobNo, ?string $rentalType, string $status, string $note): array
    {
        return [
            $status,
            $invoice->id,
            $invoice->invoice_number,
            $detail?->id ?? '-',
            $rentalType ?: '-',
            $currentJobNo ?: '-',
            $targetJobNo ?: '-',
            $note,
        ];
    }
}

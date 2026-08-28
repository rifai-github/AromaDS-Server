<?php

namespace App\Services\Operational;

use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceRentalDetail;
use App\Models\JobAdviceRoom;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoomRental;
use App\Models\MasterRental;
use App\Models\RentalPrice;
use App\Services\DocumentNumberService;
use App\Services\Finance\InvoiceTaxResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Raises the invoice for a completed Extra job.
 *
 * QA 24 Aug 2026: an Extra Job Advice flagged "With Invoicing: Yes" produced no invoice at
 * all once its job finished. InvoiceGenerationService only ever bills contract rental
 * PERIODS - it matches install/service job types against the contract's rental flow - so an
 * ad-hoc Extra was never a trigger and had no line to bill even if it had been.
 *
 * Client decision (25 Aug 2026): an Extra is billed on an invoice OF ITS OWN, raised when the
 * job completes, covering only the rooms that actually received the extra, priced from the
 * rental master. That is deliberately NOT the periodic invoice: folding an ad-hoc charge into
 * a rental period would re-open a period that may already be drafted, approved or exported to
 * CoreTax.
 *
 * Price resolution mirrors LostUnitReportController::calculateItemPrice(): a branch-specific
 * rental price wins when one exists, otherwise the rental master's own price. Keeping the two
 * the same means "the price of this rental" means one thing across the app.
 *
 * NOTE: only 19 of 345 rentals currently carry a price - the client's Master Product.xlsx did
 * not include them - so most Extras will invoice at 0 until that master data is filled in. The
 * invoice is still raised (Finance can price it by hand) rather than silently skipped, because
 * "no invoice appeared" is the exact complaint this service exists to answer.
 */
class ExtraJobInvoiceService
{
    private const COMPLETED_STATUSES = ['done_job', 'completed', 'selesai', 'dpf'];

    public function __construct(
        private DocumentNumberService $documentNumberService,
        private InvoiceTaxResolver $taxResolver,
    ) {}

    /**
     * Is this schedule the field job of an Extra Job Advice that should be billed?
     */
    public static function isBillableExtraJob(?JobSchedule $job, $jobAdvice = null): bool
    {
        if (! $job) {
            return false;
        }

        if (self::normalize($job->type) !== 'extra') {
            return false;
        }

        $jobAdvice = $jobAdvice ?: $job->jobAdvice;

        if (! $jobAdvice || self::normalize($jobAdvice->type) !== 'extra') {
            return false;
        }

        return (bool) $jobAdvice->with_invoicing && (bool) $jobAdvice->contract_id;
    }

    /**
     * Entry point: safe to call for ANY completed job - it no-ops unless the job is a
     * completed, billable Extra job, and it is idempotent per job number.
     */
    public function handleCompletedJob(?JobSchedule $job, $jobAdvice = null): ?Invoice
    {
        if (! $job) {
            return null;
        }

        $jobAdvice = $jobAdvice ?: $job->jobAdvice;

        if (! self::isBillableExtraJob($job, $jobAdvice)) {
            return null;
        }

        if (! in_array(self::normalize($job->status), self::COMPLETED_STATUSES, true)) {
            return null;
        }

        // The job number is what ties the invoice back to the Extra. Without one there is
        // nothing to make the invoice idempotent against, and a job that never reached
        // Assign Team has no business raising a charge.
        if (! $job->job_number) {
            Log::warning("Extra invoice skipped for job schedule {$job->id}: job has no job number yet.");

            return null;
        }

        $existing = Invoice::where('reference_number', $job->job_number)->first();
        if ($existing) {
            return $existing;
        }

        $contract = $jobAdvice->contract;
        if (! $contract) {
            Log::warning("Extra invoice skipped for job {$job->job_number}: Job Advice {$jobAdvice->job_advice_number} has no contract.");

            return null;
        }

        $lines = $this->buildLines($job, $jobAdvice);
        if (empty($lines)) {
            Log::warning("Extra invoice skipped for job {$job->job_number}: no Job Advice rooms resolved for this schedule.");

            return null;
        }

        try {
            return DB::transaction(function () use ($job, $jobAdvice, $contract, $lines) {
                $subtotal = round(array_sum(array_column($lines, 'total_price')), 2);

                $invoiceDate = now();
                $taxContext = $this->taxResolver->resolve($contract->customer, $contract->ppn_code, $invoiceDate);
                $taxAmount = $this->taxResolver->taxAmount($subtotal, $taxContext);
                $grandTotal = round($subtotal + $taxAmount, 2);

                $billingGroup = $contract->billingGroup;

                $invoice = Invoice::create([
                    'invoice_number' => $this->documentNumberService->generate('invoice', null, null, $contract->id),
                    'customer_id' => $contract->customer_id,
                    'billing_group_id' => $billingGroup->id ?? null,
                    'contract_id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'po_number' => $contract->po_number,
                    'billing_address' => $billingGroup->pic_address ?? $contract->customer->address ?? '',
                    'pic_finance' => $billingGroup->pic_name ?? '',
                    'email' => $billingGroup->pic_email ?? $contract->customer->email ?? '',
                    'invoice_date' => $invoiceDate,
                    'due_date' => $invoiceDate->copy()->addDays(30),
                    'ba_date' => $job->ba_date,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'grand_total' => $grandTotal,
                    'total_amount' => $grandTotal,
                    'tax_setting_id' => $taxContext['default_vat_setting']?->id,
                    'tax_code' => $taxContext['tax_code'],
                    'tax_obligation' => $taxContext['applies_ppn'],
                    'kirim' => $billingGroup->invoice_type ?? 'manual',
                    'invoice_status' => Invoice::STATUS_DRAFT,
                    'status' => Invoice::STATUS_DRAFT,
                    'notes' => "Invoice pekerjaan Extra berdasarkan Job {$job->job_number} (JA: {$jobAdvice->job_advice_number}).",
                    'reference_number' => $job->job_number,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                foreach ($lines as $line) {
                    InvoiceRentalDetail::create($line + [
                        'invoice_id' => $invoice->id,
                        'created_by' => Auth::id(),
                    ]);
                }

                if ($subtotal <= 0) {
                    Log::warning("Extra invoice {$invoice->invoice_number} raised at 0 for job {$job->job_number}: no price is set on the rentals involved.");
                }

                Log::info("Extra invoice {$invoice->invoice_number} auto-generated for job {$job->job_number}.");

                return $invoice;
            });
        } catch (\Throwable $e) {
            // Never let billing take the field job down with it - the work is done either way.
            Log::error("Failed to raise Extra invoice for job {$job->job_number}: " . $e->getMessage());

            return null;
        }
    }

    /**
     * One invoice line per Job Advice room that this schedule actually covered.
     *
     * QA asked for "invoice Job Extra yg hanya room yg dikasih extra saja yg muncul", so the
     * rooms are taken from the schedule's own room links rather than from the whole Job
     * Advice, which may span rooms handled by sibling schedules.
     */
    private function buildLines(JobSchedule $job, $jobAdvice): array
    {
        $branchId = $job->building?->branch_id;

        $lines = [];

        foreach ($this->resolveRooms($job, $jobAdvice) as $room) {
            $rental = $room->rentalProduct ?: MasterRental::find($room->rental_product_id);
            if (! $rental) {
                Log::warning("Extra invoice line skipped for job {$job->job_number}: JA room {$room->id} has no rental.");

                continue;
            }

            $quantity = max(1, (int) ($room->quantity ?: 1));
            $unitPrice = $this->resolveUnitPrice($rental, $branchId);

            $lines[] = [
                'master_rental_id' => $rental->id,
                'job_no' => $job->job_number,
                'building_name' => $job->building?->building_name ?? $job->building_name ?? '',
                'room_name' => $room->contractRoom?->room?->room_name ?? $room->room_name ?? '',
                'rental_name' => $rental->rental_name ?? $room->rental_name ?? 'Extra',
                'quantity' => $quantity,
                'qty_free' => (int) ($room->qty_free ?? 0),
                'unit_price' => $unitPrice,
                'total_price' => round($unitPrice * $quantity, 2),
            ];
        }

        return $lines;
    }

    /**
     * The Job Advice rooms this schedule covers, via the multi-rental pivot first and the
     * direct column second - the same order autoCreateUnitOnWall() uses. Falls back to the
     * Job Advice's own rooms so a schedule created before room links existed still bills.
     */
    private function resolveRooms(JobSchedule $job, $jobAdvice)
    {
        $scheduleRoomIds = $job->jobScheduleRooms()->pluck('id');

        $roomIds = JobScheduleRoomRental::whereIn('job_schedule_room_id', $scheduleRoomIds)
            ->pluck('job_advice_room_id')
            ->filter()
            ->unique();

        if ($roomIds->isEmpty()) {
            $roomIds = $job->jobScheduleRooms()->pluck('job_advice_room_id')->filter()->unique();
        }

        if ($roomIds->isNotEmpty()) {
            return JobAdviceRoom::with(['rentalProduct', 'contractRoom.room'])
                ->whereIn('id', $roomIds->all())
                ->get();
        }

        return $jobAdvice->rooms()->with(['rentalProduct', 'contractRoom.room'])->get();
    }

    /**
     * A branch-specific rental price wins over the master's, matching how a lost unit is
     * priced. Extras are billed as a one-off service, so the monthly rate is the rate.
     */
    private function resolveUnitPrice(MasterRental $rental, ?int $branchId): float
    {
        if ($branchId) {
            $branchPrice = RentalPrice::where('master_rental_id', $rental->id)
                ->where('branch_id', $branchId)
                ->value('monthly_price');

            if ($branchPrice > 0) {
                return (float) $branchPrice;
            }
        }

        return (float) ($rental->monthly_price ?? 0);
    }

    private static function normalize(?string $value): string
    {
        return strtolower(trim(str_replace([' ', '-'], '_', (string) $value)));
    }
}

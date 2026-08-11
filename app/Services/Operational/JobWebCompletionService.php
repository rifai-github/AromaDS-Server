<?php

namespace App\Services\Operational;

use App\Models\InventoryIssuing;
use App\Models\InventoryIssuingItem;
use App\Models\JobPhoto;
use App\Models\JobReport;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\JobTeamLocation;
use App\Models\MaterialIssue;
use App\Models\SerialNumber;
use App\Services\DocumentNumberService;
use App\Services\Warehouse\InventoryIssuingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Shared completion logic for the WEB dashboard fallback (operator finishes a
 * technician's job when the mobile APK is unusable).
 *
 * This is the canonical home for the per-action "delta" that the mobile
 * technician flow performs (room photos, BERITA ACARA capture, JobReport).
 * The downstream state-machine automation (invoice/unit-on-wall/remove-job/
 * follow-up generation) intentionally stays in
 * JobScheduleController::runCompletionAutomation() so web and mobile share one
 * state machine instead of diverging copies.
 *
 * Mirrors the relevant private helpers of
 * App\Http\Controllers\Api\Mobile\JobController:
 *  - saveRoomCompletionPhotos() / syncJobPhotoRecord()  (before/after photos)
 *  - verifyJob() JobReport + BA block                   (PIC name/signature/BA)
 */
class JobWebCompletionService
{
    /** Folder (under public/) where field documentation photos are stored. */
    private const UPLOAD_DIR = 'uploads/job-verifications';

    /** Path prefix stored on JobPhoto/JobReport rows (without the public/ root). */
    private const PATH_PREFIX = 'job-verifications/';

    /**
     * Persist before/after work photos for a manually-completed room and create
     * the matching JobPhoto rows ('Before Work' / 'After Work').
     *
     * @param  UploadedFile[]  $beforePhotos
     * @param  UploadedFile[]  $afterPhotos
     */
    public function completeRoomWithPhotos(
        JobSchedule $jobSchedule,
        JobScheduleRoom $jobScheduleRoom,
        array $beforePhotos,
        array $afterPhotos,
        ?int $userId
    ): void {
        $this->savePhotos($jobSchedule, $jobScheduleRoom, $beforePhotos, 'Before Work', 'before', 'Foto sebelum pekerjaan (web)', $userId);
        $this->savePhotos($jobSchedule, $jobScheduleRoom, $afterPhotos, 'After Work', 'after', 'Foto sesudah pekerjaan (web)', $userId);
    }

    /**
     * Capture BERITA ACARA verification (PIC name, signature, photos), create or
     * update the JobReport, and stamp BA date/number on the job. Does NOT run the
     * downstream automation or move job status — the caller (controller) owns the
     * status transition + runCompletionAutomation() so the state machine stays
     * single-sourced.
     *
     * @param  array{pic_name:string, notes?:?string, signature_base64?:?string, pic_photo?:?UploadedFile, work_photos?:UploadedFile[]}  $verification
     * @return array{ba_date:?string, ba_number:?string, completed_at:\Illuminate\Support\Carbon}
     */
    public function finalizeWithBa(JobSchedule $jobSchedule, array $verification, ?int $userId): array
    {
        $jobScheduleId = $jobSchedule->id;

        $picPhotoPath = isset($verification['pic_photo']) && $verification['pic_photo'] instanceof UploadedFile
            ? $this->storeFile($verification['pic_photo'], 'pic')
            : null;

        $signaturePath = ! empty($verification['signature_base64'])
            ? $this->storeSignature($verification['signature_base64'], $jobScheduleId)
            : null;

        $workPhotoPaths = [];
        foreach (($verification['work_photos'] ?? []) as $photo) {
            if ($photo instanceof UploadedFile && $photo->isValid()) {
                $workPhotoPaths[] = $this->storeFile($photo, 'work');
            }
        }

        // BA stamp (idempotent: only fill if empty), mirroring verifyJob:4156-4162.
        $now = now();
        if (! $jobSchedule->ba_date) {
            $jobSchedule->ba_date = $now->toDateString();
        }
        if (! $jobSchedule->ba_number) {
            $jobSchedule->ba_number = JobSchedule::resolveBaNumberForGroup($jobSchedule);
        }
        $jobSchedule->completed_at = $now;
        $jobSchedule->updated_by = $userId;
        $jobSchedule->save();

        // Create/update the JobReport, mirroring verifyJob:4167-4195.
        $existingJobReport = JobReport::where('job_schedule_id', $jobScheduleId)->first();

        $beforeWorkPhoto = JobPhoto::where('job_schedule_id', $jobScheduleId)
            ->where('photo_type', 'Before Work')
            ->latest('id')
            ->value('photo_path');

        $afterWorkPhoto = JobPhoto::where('job_schedule_id', $jobScheduleId)
            ->where('photo_type', 'After Work')
            ->latest('id')
            ->value('photo_path');

        JobReport::updateOrCreate(
            ['job_schedule_id' => $jobScheduleId],
            [
                'technician_id' => $userId,
                'job_type' => $jobSchedule->type,
                'notes' => $verification['notes'] ?? $jobSchedule->internal_notes,
                'photo_pic' => $picPhotoPath ?: $existingJobReport?->photo_pic,
                'signature_file' => $signaturePath ?: $existingJobReport?->signature_file,
                'signature_data' => $verification['signature_base64'] ?: $existingJobReport?->signature_data,
                'pic_name' => $verification['pic_name'] ?: $existingJobReport?->pic_name,
                'photos' => ! empty($workPhotoPaths) ? $workPhotoPaths : ($existingJobReport?->photos ?: null),
                'photo_before' => $beforeWorkPhoto ?: $existingJobReport?->photo_before,
                'photo_after' => $afterWorkPhoto ?: $existingJobReport?->photo_after,
                'completed_at' => $now,
                'signature_at' => $signaturePath ? $now : $existingJobReport?->signature_at,
            ]
        );

        // Mirror the Photos-tab sync for PIC photo + signature (verifyJob:4248-4268).
        $verificationRoomId = $this->latestCompletedRoomId($jobScheduleId);

        foreach ($workPhotoPaths as $index => $path) {
            $photoType = count($workPhotoPaths) > 1
                ? ($index === 0 ? 'Before Work' : 'After Work')
                : 'Work Photo';
            $this->syncJobPhotoRecord($jobScheduleId, $photoType, $path, 'Foto dokumentasi pekerjaan (web)', $verificationRoomId, $userId);
        }
        if ($picPhotoPath) {
            $this->syncJobPhotoRecord($jobScheduleId, 'PIC Photo', $picPhotoPath, 'Foto PIC Lapangan (web)', $verificationRoomId, $userId);
        }
        if ($signaturePath) {
            $this->syncJobPhotoRecord($jobScheduleId, 'Digital Signature', $signaturePath, 'Tanda tangan digital PIC Lapangan (web)', $verificationRoomId, $userId);
        }

        return [
            'ba_date' => $jobSchedule->ba_date,
            'ba_number' => $jobSchedule->ba_number,
            'completed_at' => $now,
        ];
    }

    // ----- Phase 1b: partial completion ("cannot complete all rooms" / outstanding) -----

    /**
     * Move every incomplete room of a job (and its same-job_number siblings) into
     * a follow-up "new_job" record, cancel the source rooms, and queue an
     * auto-return of their issued material. This is the canonical "outstanding"
     * trigger, shared by mobile verifyJob() (App\Http\Controllers\Api\Mobile\JobController)
     * and the web BA flow — both call into this one implementation so the two
     * surfaces don't grow divergent copies of the state machine.
     *
     * Extracted verbatim from JobController::handleCannotCompleteAllRooms() and
     * its private helpers (auth()->id()/Auth::id() calls replaced by the explicit
     * $userId parameter so the logic works outside an HTTP auth context too).
     */
    public function handlePartialCompletion(JobSchedule $job, $now, ?int $userId): void
    {
        $affectedJobs = $this->getPartialCompletionAffectedJobs($job);
        $processedAnyRoom = false;

        foreach ($affectedJobs as $sourceJob) {
            $sourceJob->loadMissing([
                'jobScheduleRooms.rentals',
                'jobAssignSchedules.team',
                'building',
            ]);

            $roomsToMove = $sourceJob->jobScheduleRooms
                ->filter(fn ($room) => ! in_array($room->status, [
                    JobScheduleRoom::STATUS_COMPLETED,
                    JobScheduleRoom::STATUS_CANCELLED,
                ], true))
                ->values();

            if ($roomsToMove->isEmpty()) {
                continue;
            }

            $processedAnyRoom = true;
            $returnContext = $this->preparePartialCompletionReturnContext($sourceJob, $roomsToMove, $now, $userId);
            $jobAssignScheduleIds = $sourceJob->jobAssignSchedules()->pluck('id');

            foreach ($roomsToMove as $room) {
                $newJob = $this->findOrCreatePartialCompletionFollowUpJob($sourceJob, $room, $now, $userId);
                $newRoom = $this->findOrCreatePartialCompletionFollowUpRoom($sourceJob, $newJob, $room, $userId);
                $this->syncPartialCompletionRoomRentals($room, $newRoom);
                $this->processPartialCompletionMaterialReturnItems($sourceJob, $room, $jobAssignScheduleIds, $returnContext, $userId);

                $room->update([
                    'status' => JobScheduleRoom::STATUS_CANCELLED,
                    'material_return_status' => $returnContext['material_return']
                        ? ($returnContext['material_return']->status === \App\Models\MaterialReturn::STATUS_RETURNED
                            ? JobScheduleRoom::MATERIAL_RETURN_RETURNED
                            : JobScheduleRoom::MATERIAL_RETURN_PENDING)
                        : JobScheduleRoom::MATERIAL_RETURN_NOT_REQUIRED,
                    'material_return_id' => $returnContext['material_return']?->id,
                    'material_return_at' => $returnContext['material_return']?->returned_at,
                    'material_return_by' => $returnContext['material_return']?->returned_by,
                    'notes' => 'Pekerjaan tidak selesai, dipindahkan ke Job baru.',
                    'updated_by' => $userId,
                ]);
            }

            $sourceJob->status = 'meninggalkan_lokasi';
            $sourceJob->updated_by = $userId;
            $sourceJob->save();
        }

        if (! $processedAnyRoom) {
            $job->status = 'meninggalkan_lokasi';
            $job->updated_by = $userId;
        }
    }

    private function getPartialCompletionAffectedJobs(JobSchedule $job)
    {
        if (! $job->job_number) {
            return collect([$job]);
        }

        return JobSchedule::where('job_number', $job->job_number)
            ->where('job_advice_id', $job->job_advice_id)
            ->where('building_id', $job->building_id)
            ->where('type', $job->type)
            ->whereNotIn('status', ['done_job', 'completed', 'selesai', 'undone'])
            ->whereNotIn('type', ['remove', 'remove_free', 'remove free'])
            ->lockForUpdate()
            ->get()
            ->whenEmpty(fn () => collect([$job]));
    }

    private function preparePartialCompletionReturnContext(JobSchedule $job, $roomsToMove, $now, ?int $userId): array
    {
        $team = $job->jobAssignSchedules()->first()?->team;
        $warehouse = $this->resolvePartialCompletionWarehouse($job, $team);
        $materialReturn = null;
        $inventoryReceiving = null;

        if (! $warehouse) {
            Log::warning("handlePartialCompletion: No warehouse resolved for incomplete job {$job->job_number}. Outstanding job will still be created without auto material return.");

            return [
                'warehouse' => null,
                'material_return' => null,
                'inventory_receiving' => null,
            ];
        }

        $roomNames = $roomsToMove->pluck('room_name')->implode(', ');
        $receivingNote = "Auto-return dari Job {$job->job_number} (Pekerjaan tidak selesai). Room: {$roomNames}";

        // Match both the mobile-authored note ("...via Job {n}...") and the
        // web-authored one ("...dari Job {n}...") so re-running this from either
        // surface for the same job stays idempotent instead of creating duplicates.
        $materialReturn = \App\Models\MaterialReturn::where('job_schedule_id', $job->id)
            ->whereIn('status', [
                \App\Models\MaterialReturn::STATUS_PENDING,
                \App\Models\MaterialReturn::STATUS_APPROVED,
                \App\Models\MaterialReturn::STATUS_RETURNED,
            ])
            ->where('notes', 'like', '%Job '.$job->job_number.' (Pekerjaan tidak selesai)%')
            ->lockForUpdate()
            ->latest('id')
            ->first();

        if (! $materialReturn) {
            $returnNumber = \App\Models\MaterialReturn::generateReturnNumber($job->id);

            $materialReturn = \App\Models\MaterialReturn::create([
                'return_number' => $returnNumber,
                'job_schedule_id' => $job->id,
                'warehouse_id' => $warehouse->id,
                'team_id' => $team?->id,
                'status' => \App\Models\MaterialReturn::STATUS_PENDING,
                'return_date' => $now->toDateString(),
                'return_reason' => 'Pekerjaan tidak selesai (Auto-return)',
                'notes' => $receivingNote,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        $inventoryReceiving = \App\Models\InventoryReceiving::where('reference_no', $job->job_number)
            ->where('notes', 'like', '%Job '.$job->job_number.' (Pekerjaan tidak selesai)%')
            ->lockForUpdate()
            ->latest('id')
            ->first();

        if (! $inventoryReceiving) {
            $receivingNumber = app(DocumentNumberService::class)
                ->generate('inventory_receiving', warehouseId: $warehouse->id);

            $inventoryReceiving = \App\Models\InventoryReceiving::create([
                'receiving_number' => $receivingNumber,
                'reference_no' => $job->job_number,
                'branch_id' => $warehouse->branch_id ?? $job->branch_id,
                'received_from' => $userId,
                'received_by_old' => $userId,
                'schedule_date' => $now->toDateString(),
                'status' => 'pending',
                'notes' => $receivingNote,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        return [
            'warehouse' => $warehouse,
            'material_return' => $materialReturn,
            'inventory_receiving' => $inventoryReceiving,
        ];
    }

    private function resolvePartialCompletionWarehouse(JobSchedule $job, $team)
    {
        $materialIssue = MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function ($query) use ($job) {
            $query->where('job_schedule_id', $job->id);
        })->latest('id')->first();

        if ($materialIssue && $materialIssue->warehouse_id) {
            return \App\Models\Warehouse::find($materialIssue->warehouse_id);
        }

        if ($team?->branch_office) {
            $warehouse = \App\Models\Warehouse::where('branch_id', $team->branch_office)
                ->where('is_active', true)
                ->orderByDesc('is_center')
                ->orderBy('id')
                ->first();

            if ($warehouse) {
                return $warehouse;
            }
        }

        if ($job->building?->branch_id) {
            return \App\Models\Warehouse::where('branch_id', $job->building->branch_id)
                ->where('is_active', true)
                ->orderByDesc('is_center')
                ->orderBy('id')
                ->first();
        }

        return null;
    }

    private function findOrCreatePartialCompletionFollowUpJob(JobSchedule $sourceJob, $room, $now, ?int $userId): JobSchedule
    {
        $jobAdviceRoomIds = $this->getJobScheduleRoomAdviceRoomIds($room);

        $existingJob = JobSchedule::where('job_advice_id', $sourceJob->job_advice_id)
            ->where('building_id', $sourceJob->building_id)
            ->where('type', $sourceJob->type)
            ->where('internal_notes', 'like', "Lanjutan dari Job {$sourceJob->job_number}%")
            ->whereNotIn('status', ['cancelled', 'done_job', 'completed', 'selesai'])
            ->whereHas('jobScheduleRooms', function ($query) use ($jobAdviceRoomIds) {
                $query->whereIn('job_advice_room_id', $jobAdviceRoomIds)
                    ->orWhereHas('rentals', function ($rentalQuery) use ($jobAdviceRoomIds) {
                        $rentalQuery->whereIn('job_advice_room_id', $jobAdviceRoomIds);
                    });
            })
            ->latest('id')
            ->first();

        if ($existingJob) {
            $this->syncPartialCompletionFollowUpScheduleContext($sourceJob, $existingJob, true, $userId);
            if (! app(\App\Http\Controllers\Api\Mobile\JobController::class)->materialPickupVerifiedForJob($existingJob)) {
                $this->resetPartialCompletionFollowUpMaterialState($existingJob, true, $userId);
            }

            return $existingJob;
        }

        $newJob = new JobSchedule;
        if (\Illuminate\Support\Facades\Schema::hasColumn('job_schedules', 'customer_id')) {
            $newJob->customer_id = $sourceJob->customer_id;
        }
        $newJob->building_id = $sourceJob->building_id;
        $newJob->building_name = $sourceJob->building_name;
        $newJob->company_name = $sourceJob->company_name;
        $newJob->job_advice_id = $sourceJob->job_advice_id;
        $newJob->contract_number = $sourceJob->contract_number;
        $newJob->quotation_number = $sourceJob->quotation_number;
        $newJob->type = $sourceJob->type;
        $newJob->status = 'new_job';
        $newJob->schedule_date = $sourceJob->schedule_date;
        $newJob->expected_date = $sourceJob->expected_date;
        $newJob->job_number = null;
        $newJob->internal_notes = "Lanjutan dari Job {$sourceJob->job_number} (Pekerjaan tidak selesai). Room: {$room->room_name}.";
        $newJob->created_by = $userId;
        $newJob->updated_by = $userId;
        $this->syncPartialCompletionFollowUpScheduleContext($sourceJob, $newJob, false, $userId);
        $this->resetPartialCompletionFollowUpMaterialState($newJob, false, $userId);
        $newJob->save();

        return $newJob;
    }

    private function syncPartialCompletionFollowUpScheduleContext(JobSchedule $sourceJob, JobSchedule $followUpJob, bool $save, ?int $userId): void
    {
        $columnsToCopy = [
            'schedule_date',
            'period',
            'service_frequency',
            'service_period_type',
            'service_interval_days',
            'next_service_date',
            'reference_number',
            'job_reference_number',
            'day',
        ];

        foreach ($columnsToCopy as $column) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('job_schedules', $column)) {
                $followUpJob->{$column} = $sourceJob->{$column};
            }
        }

        if ($save && $followUpJob->isDirty()) {
            $followUpJob->updated_by = $userId;
            $followUpJob->save();
        }
    }

    private function resetPartialCompletionFollowUpMaterialState(JobSchedule $followUpJob, bool $save, ?int $userId): void
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('job_schedules', 'material_checked')) {
            $followUpJob->material_checked = false;
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('job_schedules', 'material_checked_at')) {
            $followUpJob->material_checked_at = null;
        }

        if ($save && $followUpJob->isDirty()) {
            $followUpJob->updated_by = $userId;
            $followUpJob->save();
        }
    }

    private function findOrCreatePartialCompletionFollowUpRoom(JobSchedule $sourceJob, JobSchedule $newJob, $room, ?int $userId)
    {
        return JobScheduleRoom::firstOrCreate(
            [
                'job_schedule_id' => $newJob->id,
                'job_advice_room_id' => $room->job_advice_room_id,
            ],
            [
                'room_name' => $room->room_name,
                'room_id' => $room->room_id,
                'status' => JobScheduleRoom::STATUS_PENDING,
                'material_return_status' => JobScheduleRoom::MATERIAL_RETURN_NOT_REQUIRED,
                'notes' => "Pindahan dari Job {$sourceJob->job_number}",
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function syncPartialCompletionRoomRentals($sourceRoom, $targetRoom): void
    {
        $sourceRoom->loadMissing('rentals');
        $rentals = $sourceRoom->rentals;

        if ($rentals->isEmpty() && $sourceRoom->job_advice_room_id) {
            $rentals = collect([(object) [
                'job_advice_room_id' => $sourceRoom->job_advice_room_id,
                'is_primary' => true,
            ]]);
        }

        foreach ($rentals as $rental) {
            $link = \App\Models\JobScheduleRoomRental::withTrashed()->firstOrNew([
                'job_schedule_room_id' => $targetRoom->id,
                'job_advice_room_id' => $rental->job_advice_room_id,
            ]);
            $link->is_primary = (bool) $rental->is_primary;
            $link->save();

            if (method_exists($link, 'trashed') && $link->trashed()) {
                $link->restore();
            }
        }
    }

    private function getJobScheduleRoomAdviceRoomIds($room): array
    {
        $room->loadMissing('rentals');

        return $room->rentals
            ->pluck('job_advice_room_id')
            ->push($room->job_advice_room_id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function processPartialCompletionMaterialReturnItems(JobSchedule $job, $room, $jobAssignScheduleIds, array $returnContext, ?int $userId): void
    {
        $materialReturn = $returnContext['material_return'];
        $inventoryReceiving = $returnContext['inventory_receiving'];
        $warehouse = $returnContext['warehouse'];

        if (! $materialReturn || ! $inventoryReceiving || ! $warehouse) {
            return;
        }

        $materialIssueItems = \App\Models\MaterialIssueItem::whereIn('job_assign_schedule_id', $jobAssignScheduleIds)
            ->whereRaw('LOWER(TRIM(room_name)) = ?', [strtolower(trim((string) $room->room_name))])
            ->get();

        if ($materialIssueItems->isEmpty()) {
            $materialIssueItems = \App\Models\MaterialIssueItem::whereHas('materialIssue.jobAssignMaterialIssues.jobAssignSchedule', function ($query) use ($job) {
                $query->where('job_schedule_id', $job->id);
            })
                ->whereRaw('LOWER(TRIM(room_name)) = ?', [strtolower(trim((string) $room->room_name))])
                ->get();
        }

        foreach ($materialIssueItems as $issueItem) {
            if (! $issueItem->product_id) {
                continue;
            }

            $issuedItem = InventoryIssuingItem::whereIn('job_assign_schedule_id', $jobAssignScheduleIds)
                ->where('product_id', $issueItem->product_id)
                ->whereRaw('LOWER(TRIM(room_name)) = ?', [strtolower(trim((string) $room->room_name))])
                ->whereHas('inventoryIssuing', function ($query) {
                    $query->whereIn('status', ['processed', 'sent', 'received']);
                })
                ->latest('id')
                ->first();

            $quantityToReturn = $issuedItem && (float) $issuedItem->quantity_issued > 0
                ? (float) $issuedItem->quantity_issued
                : (float) ($issueItem->quantity ?? 0);

            if ($issuedItem?->serial_number_id) {
                $sn = SerialNumber::find($issuedItem->serial_number_id);
                $hasActiveUnitOnWall = $sn
                    ? \App\Models\UnitOnWall::where('serial_number_id', $sn->id)
                        ->whereIn('status', ['active', 'installed', 'on_wall', 'on wall', 'onwall'])
                        ->exists()
                    : false;

                if ($sn && ($sn->status === 'in_use' || $sn->location_type === 'customer' || $hasActiveUnitOnWall)) {
                    Log::warning('Skipping partial completion material return for installed serial number', [
                        'job_schedule_id' => $job->id,
                        'job_number' => $job->job_number,
                        'room_name' => $room->room_name,
                        'serial_number' => $sn->serial_number,
                        'serial_status' => $sn->status,
                    ]);

                    continue;
                }
            }

            \App\Models\MaterialReturnItem::firstOrCreate(
                [
                    'material_return_id' => $materialReturn->id,
                    'material_issue_item_id' => $issueItem->id,
                ],
                [
                    'product_id' => $issueItem->product_id,
                    'room_name' => $room->room_name,
                    'room_id' => $room->room_id,
                    'quantity' => $quantityToReturn,
                    'notes' => "Auto-return dari Room {$room->room_name}",
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]
            );

            \App\Models\InventoryReceivingItem::firstOrCreate(
                [
                    'inventory_receiving_id' => $inventoryReceiving->id,
                    'master_product_id' => $issueItem->product_id,
                    'notes' => "Auto-return dari Room {$room->room_name} (MI Item {$issueItem->id})",
                ],
                [
                    'quantity' => $quantityToReturn,
                    'quantity_received' => 0,
                ]
            );

            if ($issuedItem?->serial_number_id) {
                $sn = SerialNumber::find($issuedItem->serial_number_id);

                if ($sn) {
                    $existingNotes = trim((string) ($sn->notes ?? ''));
                    $returnNote = "Queued to RR {$inventoryReceiving->receiving_number} from incomplete Job {$job->job_number}.";

                    $sn->update([
                        'inventory_receiving_id' => $inventoryReceiving->id,
                        'warehouse_id' => $warehouse->id,
                        'status' => 'pending',
                        'location_type' => 'technician',
                        'location_id' => $userId,
                        'notes' => $existingNotes === '' ? $returnNote : $existingNotes."\n".$returnNote,
                        'updated_by' => $userId,
                    ]);
                }
            }
        }
    }

    // ----- Phase 2: location lifecycle (arrived / start work / leave) -----

    /**
     * Mark technician arrival from the web. Mirrors mobile arrivedAtLocation():
     * records a JobTeamLocation (action 'arrived') and moves the job (and its
     * same-job_number siblings) to 'teknisi_tiba_dilokasi' when in an allowed
     * pre-work status. Lat/lng are optional (an office operator has no GPS).
     *
     * @return array{updated:int}
     */
    public function arrivedAtLocation(JobSchedule $job, ?float $latitude, ?float $longitude, ?int $userId, ?string $notes = null): array
    {
        $allowedStatuses = ['barang_diambil', 'barang_dipersiapkan', 'assign_material', 'assign_team', 'scheduled', 'new_job', 'meninggalkan_lokasi'];

        $this->recordLocation($job, 'arrived', $latitude, $longitude, $userId, $notes);

        $arrivalJobs = collect([$job]);
        if ($job->job_number) {
            $arrivalJobs = JobSchedule::where('job_number', $job->job_number)
                ->where('job_advice_id', $job->job_advice_id)
                ->where('type', $job->type)
                ->get();
        }

        $updated = 0;
        foreach ($arrivalJobs as $arrivalJob) {
            if (in_array($arrivalJob->status, $allowedStatuses, true)) {
                $arrivalJob->update(['status' => 'teknisi_tiba_dilokasi', 'updated_by' => $userId]);
                $updated++;
            }
        }

        return ['updated' => $updated];
    }

    /**
     * Start work from the web. Mirrors mobile startWork(): teknisi_tiba_dilokasi
     * or barang_diambil -> in_progress, stamping started_at.
     *
     * @return array{ok:bool, message:?string}
     */
    public function startWork(JobSchedule $job, ?int $userId): array
    {
        $allowedStatuses = ['teknisi_tiba_dilokasi', 'barang_diambil'];
        if (! in_array($job->status, $allowedStatuses, true)) {
            return ['ok' => false, 'message' => 'Job harus berstatus "Tiba di Lokasi" atau "Barang Diambil" sebelum pekerjaan dapat dimulai.'];
        }

        $job->status = 'in_progress';
        if (! $job->started_at) {
            $job->started_at = now();
        }
        $job->updated_by = $userId;
        $job->save();

        return ['ok' => true, 'message' => null];
    }

    /**
     * Leave location from the web. Mirrors mobile leaveLocation(): records a
     * JobTeamLocation (action 'left') and sets status 'meninggalkan_lokasi'.
     */
    public function leaveLocation(JobSchedule $job, ?float $latitude, ?float $longitude, ?int $userId, ?string $notes = null): void
    {
        $this->recordLocation($job, 'left', $latitude, $longitude, $userId, $notes);

        $job->status = 'meninggalkan_lokasi';
        $job->updated_by = $userId;
        $job->save();
    }

    // ----- Phase 3: material confirm / verify, scanned-unit aroma schedule -----

    /**
     * Confirm materials from the web: flags material_checked only. This is the
     * gudang/operator "I see the materials are ready" acknowledgement and is
     * intentionally a no-op on the Inventory Issuing and job status — it must
     * NOT finalize the issuing or advance the job to 'barang_diambil'. That
     * transition (and recording who physically received the goods) belongs
     * exclusively to verifyMaterials() ("Ambil Barang"), so the two actions
     * stop being redundant and the issuing's "Diberikan kepada" stays accurate.
     * Only valid from 'barang_siap_diambil'.
     *
     * @return array{ok:bool, code:int, message:string}
     */
    public function confirmMaterials(JobSchedule $job, ?int $userId): array
    {
        if ($job->status === 'undone') {
            return ['ok' => false, 'code' => 423, 'message' => 'Job sedang dalam proses koreksi BA Date oleh admin dan tidak dapat dikonfirmasi.'];
        }

        if (strtolower((string) $job->status) !== 'barang_siap_diambil') {
            return ['ok' => false, 'code' => 400, 'message' => 'Material belum siap diambil. Harap tunggu status "Barang Siap Diambil".'];
        }

        $job->material_checked = true;
        $job->material_checked_at = now();
        $job->updated_by = $userId;
        $job->save();

        return ['ok' => true, 'code' => 200, 'message' => 'Material berhasil dikonfirmasi. Klik "Ambil Barang" saat teknisi benar-benar mengambil barang.'];
    }

    /**
     * Verify material pickup from the web (technician "ambil barang"). Mirrors the
     * effective end-state of mobile MaterialVerificationController::verifyMaterials:
     * marks the Inventory Issuing as picked up ('sent'), moves linked serial
     * numbers to on_hand/technician, and advances the related jobs to
     * 'barang_diambil'. The web operator confirms the whole prepared issuing
     * rather than ticking individual lines, so we accept the resolved issuing and
     * its already-prepared items as-is.
     *
     * @return array{ok:bool, code:int, message:string}
     */
    public function verifyMaterials(JobSchedule $job, ?int $userId): array
    {
        if ($job->status === 'undone') {
            return ['ok' => false, 'code' => 423, 'message' => 'Job sedang dalam proses koreksi BA Date oleh admin.'];
        }

        if (in_array(strtolower(trim((string) $job->type)), ['remove', 'remove_free', 'remove free'], true)) {
            return ['ok' => false, 'code' => 422, 'message' => 'Job remove tidak memerlukan verifikasi material. Unit diambil dari Unit On Wall.'];
        }

        $issuing = $this->resolveInventoryIssuingForJob($job);
        if (! $issuing) {
            return ['ok' => false, 'code' => 404, 'message' => 'Inventory Issuing tidak ditemukan untuk job ini. Pastikan material sudah disiapkan gudang.'];
        }

        // Already picked up: just make sure the job status is consistent.
        if (in_array($issuing->status, ['sent', 'received'], true)) {
            $this->advanceRelatedJobsToBarangDiambil($issuing, $job, $userId);

            return ['ok' => true, 'code' => 200, 'message' => 'Material sudah diverifikasi sebelumnya. Status job disinkronkan.'];
        }

        if ($issuing->status !== 'processed') {
            return ['ok' => false, 'code' => 400, 'message' => 'Material belum Ready to Issue. Harap tunggu gudang memproses Inventory Issuing terlebih dahulu.'];
        }

        DB::beginTransaction();
        try {
            $issuing->update([
                'received_by' => $userId,
                'received_at' => now(),
                'status' => 'processed',
                'updated_by' => $userId,
            ]);

            // Post the ready stock if it has not been posted yet, then mark issued.
            $issuing->load(['items.product', 'warehouse', 'branch']);
            app(InventoryIssuingService::class)->postReadyStockIfMissing($issuing);

            $issuing->update([
                'status' => 'sent',
                'updated_by' => $userId,
            ]);

            // Move any linked serial numbers to on_hand / technician (mirror mobile verification).
            app(InventoryIssuingService::class)->moveSerialNumbersToTechnician(
                $issuing,
                (int) ($issuing->received_by ?? $userId),
                $userId
            );

            $this->advanceRelatedJobsToBarangDiambil($issuing, $job, $userId);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Web verifyMaterials error for Job {$job->id}: ".$e->getMessage());

            return ['ok' => false, 'code' => 500, 'message' => 'Terjadi kesalahan saat verifikasi material: '.$e->getMessage()];
        }

        return ['ok' => true, 'code' => 200, 'message' => 'Material berhasil diverifikasi (barang diambil). Teknisi dapat melanjutkan ke lokasi.'];
    }

    /**
     * Save a scanned/selected unit and (optionally) its aroma schedule from the
     * web. Mirrors mobile saveScannedUnit() (JobController:3709) DB writes into
     * job_schedule_units, storing the aroma schedule under
     * device_snapshot.schedule. DB-record-only: this does NOT push to the
     * physical SmartScent device (controlDevice() is not wired anywhere).
     *
     * @param  array{schedule?:array}  $extra
     * @return array{ok:bool, code:int, message:string, job_schedule_unit_id:?int}
     */
    public function saveScannedUnit(
        JobSchedule $job,
        int $roomId,
        string $mac,
        string $deviceType,
        ?string $deviceName,
        ?array $schedule,
        ?string $notes,
        ?int $userId
    ): array {
        if ($job->status === 'undone') {
            return ['ok' => false, 'code' => 423, 'message' => 'Job sedang dalam proses koreksi BA Date oleh admin.', 'job_schedule_unit_id' => null];
        }

        $mac = trim($mac);
        if ($mac === '') {
            return ['ok' => false, 'code' => 422, 'message' => 'Serial Number / MAC unit wajib diisi.', 'job_schedule_unit_id' => null];
        }

        // Guard: same SN may not be reused for a different room on this job
        // (mirror saveScannedUnit:3798-3810).
        $existingScanForSn = DB::table('job_schedule_units')
            ->where('job_schedule_id', $job->id)
            ->whereRaw('UPPER(TRIM(mac)) = ?', [strtoupper($mac)])
            ->first();

        if ($existingScanForSn && (int) ($existingScanForSn->job_advice_room_id ?? 0) !== $roomId) {
            return ['ok' => false, 'code' => 409, 'message' => "Serial Number {$mac} sudah dipakai untuk room lain pada job ini.", 'job_schedule_unit_id' => null];
        }

        DB::beginTransaction();
        try {
            $snapshot = [];
            if (! empty($schedule)) {
                $snapshot['schedule'] = $schedule;
            }

            $jobUnitData = [
                'job_schedule_id' => $job->id,
                'job_advice_room_id' => $roomId,
                'unit_id' => null,
                'mac' => $mac,
                'device_type' => $deviceType,
                'device_name' => $deviceName,
                'device_snapshot' => json_encode($snapshot),
                'scanned_at' => now(),
                'notes' => $notes,
                'updated_at' => now(),
            ];

            $updated = DB::table('job_schedule_units')
                ->where('job_schedule_id', $job->id)
                ->where('mac', $mac)
                ->update($jobUnitData);

            if ($updated > 0) {
                $jobScheduleUnitId = DB::table('job_schedule_units')
                    ->where('job_schedule_id', $job->id)
                    ->where('mac', $mac)
                    ->value('id');

                // Merge schedule into existing snapshot instead of clobbering it.
                if (! empty($schedule)) {
                    $current = DB::table('job_schedule_units')->where('id', $jobScheduleUnitId)->value('device_snapshot');
                    $currentSnapshot = $current ? (json_decode($current, true) ?: []) : [];
                    $currentSnapshot['schedule'] = $schedule;
                    DB::table('job_schedule_units')->where('id', $jobScheduleUnitId)->update([
                        'device_snapshot' => json_encode($currentSnapshot),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                $jobUnitData['created_at'] = now();
                $jobScheduleUnitId = DB::table('job_schedule_units')->insertGetId($jobUnitData);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Web saveScannedUnit error for Job {$job->id}: ".$e->getMessage());

            return ['ok' => false, 'code' => 500, 'message' => 'Gagal menyimpan data unit: '.$e->getMessage(), 'job_schedule_unit_id' => null];
        }

        return ['ok' => true, 'code' => 200, 'message' => 'Data unit & jadwal aroma tersimpan.', 'job_schedule_unit_id' => (int) $jobScheduleUnitId];
    }

    /**
     * Resolve the Inventory Issuing prepared for a job (via its material issue).
     */
    private function resolveInventoryIssuingForJob(JobSchedule $job): ?InventoryIssuing
    {
        $materialIssue = MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function ($q) use ($job) {
            $q->where('job_schedule_id', $job->id);
        })->first();

        if (! $materialIssue) {
            return null;
        }

        return InventoryIssuing::with('items')
            ->where('reference_no', $materialIssue->issue_number)
            ->first();
    }

    /**
     * Advance every job that shares this issuing's material reference to
     * 'barang_diambil' unless already in a more advanced status
     * (mirror verifyMaterials:304-330).
     */
    private function advanceRelatedJobsToBarangDiambil(InventoryIssuing $issuing, JobSchedule $fallbackJob, ?int $userId): void
    {
        $relatedJobs = JobSchedule::whereHas('jobAssignSchedules.jobAssignMaterialIssues.materialIssue', function ($q) use ($issuing) {
            $q->where('issue_number', $issuing->reference_no);
        })->get();

        if ($relatedJobs->isEmpty()) {
            $relatedJobs = collect([$fallbackJob]);
        }

        $advanced = ['teknisi_tiba_dilokasi', 'in_progress', 'teknisi_sedang_pengerjaan', 'teknisi_selesai_pengerjaan', 'done_job', 'completed'];

        foreach ($relatedJobs as $relatedJob) {
            if (! in_array($relatedJob->status, $advanced, true)) {
                $relatedJob->update([
                    'status' => 'barang_diambil',
                    'material_checked' => true,
                    'material_checked_at' => now(),
                    'updated_by' => $userId,
                ]);
            }
        }
    }

    /**
     * Create a JobTeamLocation row for a web-driven lifecycle action. Resolves
     * the team from the job's active assignment (an office operator usually has
     * no team membership of their own).
     */
    private function recordLocation(JobSchedule $job, string $action, ?float $latitude, ?float $longitude, ?int $userId, ?string $notes): void
    {
        // job_team_locations.latitude/longitude are NOT NULL. An office operator
        // usually has no GPS for the site, so skip the location row when missing
        // (the status transition still happens) — mirrors mobile leaveLocation().
        if ($latitude === null || $longitude === null) {
            return;
        }

        $teamId = DB::table('team_members')->where('user_id', $userId)->value('team_id')
            ?? DB::table('job_assign_schedules')
                ->where('job_schedule_id', $job->id)
                ->where('status', '!=', 'cancelled')
                ->orderByDesc('id')
                ->value('team_id');

        JobTeamLocation::create([
            'job_schedule_id' => $job->id,
            'user_id' => $userId,
            'team_id' => $teamId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'device_info' => 'web-dashboard',
            'action' => $action,
            'notes' => $notes,
            'recorded_at' => now(),
        ]);
    }

    /**
     * @param  UploadedFile[]  $files
     */
    private function savePhotos(
        JobSchedule $jobSchedule,
        JobScheduleRoom $jobScheduleRoom,
        array $files,
        string $photoType,
        string $filenameSuffix,
        string $description,
        ?int $userId
    ): void {
        foreach ($files as $photo) {
            if (! $photo instanceof UploadedFile || ! $photo->isValid()) {
                continue;
            }

            $path = $this->storeFile($photo, $filenameSuffix);
            $this->syncJobPhotoRecord($jobSchedule->id, $photoType, $path, $description, $jobScheduleRoom->id, $userId);
        }
    }

    /**
     * Move an uploaded file into the verifications folder and return its stored
     * relative path (mirrors saveRoomCompletionPhotos:2588-2613).
     */
    private function storeFile(UploadedFile $photo, string $suffix): string
    {
        $uploadPath = public_path(self::UPLOAD_DIR);
        if (! is_dir($uploadPath) && ! mkdir($uploadPath, 0775, true) && ! is_dir($uploadPath)) {
            throw new \RuntimeException('Folder upload foto pekerjaan tidak bisa dibuat.');
        }
        if (! is_writable($uploadPath)) {
            throw new \RuntimeException('Folder upload foto pekerjaan tidak writable.');
        }

        $filename = time().'_'.uniqid().'_'.$suffix.'.'.$photo->getClientOriginalExtension();
        if (! $photo->move($uploadPath, $filename)) {
            throw new \RuntimeException('Gagal menyimpan foto pekerjaan.');
        }

        return self::PATH_PREFIX.$filename;
    }

    /**
     * Decode a base64 signature payload and store it as a PNG, returning the
     * stored relative path (mirrors verifyJob signature save).
     */
    private function storeSignature(string $signatureBase64, int $jobScheduleId): string
    {
        $uploadPath = public_path(self::UPLOAD_DIR);
        if (! is_dir($uploadPath) && ! mkdir($uploadPath, 0775, true) && ! is_dir($uploadPath)) {
            throw new \RuntimeException('Folder upload tanda tangan tidak bisa dibuat.');
        }

        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureBase64));
        if ($data === false) {
            throw new \RuntimeException('Data tanda tangan tidak valid.');
        }

        $filename = 'signature_'.$jobScheduleId.'_'.time().'.png';
        if (file_put_contents($uploadPath.DIRECTORY_SEPARATOR.$filename, $data) === false) {
            throw new \RuntimeException('Gagal menyimpan tanda tangan.');
        }

        return self::PATH_PREFIX.$filename;
    }

    /**
     * Upsert a JobPhoto row keyed by (job_schedule_id, photo_type[, room]).
     * Copied from JobController::syncJobPhotoRecord with an explicit $userId so it
     * works outside the Auth context too.
     */
    private function syncJobPhotoRecord(
        int $jobScheduleId,
        string $photoType,
        string $photoPath,
        string $description,
        ?int $jobScheduleRoomId,
        ?int $userId
    ): void {
        $jobPhoto = JobPhoto::where('job_schedule_id', $jobScheduleId)
            ->where('photo_type', $photoType)
            ->when($jobScheduleRoomId, function ($query) use ($jobScheduleRoomId) {
                $query->where('job_schedule_room_id', $jobScheduleRoomId);
            })
            ->latest('id')
            ->first();

        if ($jobPhoto) {
            $jobPhoto->update([
                'photo_path' => $photoPath,
                'description' => $description,
                'job_schedule_room_id' => $jobScheduleRoomId ?: $jobPhoto->job_schedule_room_id,
                'uploaded_by' => $userId,
                'updated_at' => now(),
            ]);

            return;
        }

        JobPhoto::create([
            'job_schedule_id' => $jobScheduleId,
            'job_schedule_room_id' => $jobScheduleRoomId,
            'photo_path' => $photoPath,
            'photo_type' => $photoType,
            'description' => $description,
            'uploaded_by' => $userId,
        ]);
    }

    private function latestCompletedRoomId(int $jobScheduleId): ?int
    {
        return JobScheduleRoom::where('job_schedule_id', $jobScheduleId)
            ->where('status', JobScheduleRoom::STATUS_COMPLETED)
            ->orderByDesc('completed_at')
            ->orderByDesc('updated_at')
            ->value('id');
    }
}

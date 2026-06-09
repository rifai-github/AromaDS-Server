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

        $signaturePath = !empty($verification['signature_base64'])
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
        if (!$jobSchedule->ba_date) {
            $jobSchedule->ba_date = $now->toDateString();
        }
        if (!$jobSchedule->ba_number) {
            $jobSchedule->ba_number = (new DocumentNumberService())
                ->generate('berita_acara', null, null, null, $jobScheduleId);
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
                'photos' => !empty($workPhotoPaths) ? $workPhotoPaths : ($existingJobReport?->photos ?: null),
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
        if (!in_array($job->status, $allowedStatuses, true)) {
            return ['ok' => false, 'message' => 'Job harus berstatus "Tiba di Lokasi" atau "Barang Diambil" sebelum pekerjaan dapat dimulai.'];
        }

        $job->status = 'in_progress';
        if (!$job->started_at) {
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
     * Confirm materials from the web. Mirrors mobile confirmMaterials()
     * (JobController:1340): flags material_checked and auto-finalizes the
     * related Inventory Issuings. Only valid from 'barang_siap_diambil'.
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

        // Auto-finalize related Inventory Issuings (mirror confirmMaterials:1372-1395).
        try {
            $materialIssueItems = \App\Models\MaterialIssueItem::whereHas('materialIssue.jobAssignMaterialIssues.jobAssignSchedule', function ($q) use ($job) {
                $q->where('job_schedule_id', $job->id);
            })->with('materialIssue')->get();

            $issueNumbers = $materialIssueItems->map(fn ($item) => $item->materialIssue?->issue_number)->unique()->filter()->toArray();

            if (!empty($issueNumbers)) {
                $issuings = InventoryIssuing::whereIn('reference_no', $issueNumbers)
                    ->where('status', 'processed')
                    ->get();

                $service = app(InventoryIssuingService::class);
                foreach ($issuings as $issuing) {
                    $service->finalize($issuing);
                }
            }
        } catch (\Throwable $e) {
            Log::error("Web confirmMaterials: failed to auto-finalize issuings for Job {$job->id}: " . $e->getMessage());
            // material_checked is already saved; do not fail the whole request.
        }

        return ['ok' => true, 'code' => 200, 'message' => 'Material berhasil dikonfirmasi dan inventory difinalisasi.'];
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
        if (!$issuing) {
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

            // Move any linked serial numbers to on_hand / technician (mirror :285-299).
            $serialNumberIds = $issuing->items()
                ->whereNotNull('serial_number_id')
                ->pluck('serial_number_id')
                ->toArray();

            if (!empty($serialNumberIds)) {
                SerialNumber::whereIn('id', $serialNumberIds)->update([
                    'status' => 'on_hand',
                    'location_type' => 'technician',
                    'location_id' => $issuing->received_by ?? $userId,
                    'updated_by' => $userId,
                ]);
            }

            $this->advanceRelatedJobsToBarangDiambil($issuing, $job, $userId);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Web verifyMaterials error for Job {$job->id}: " . $e->getMessage());

            return ['ok' => false, 'code' => 500, 'message' => 'Terjadi kesalahan saat verifikasi material: ' . $e->getMessage()];
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
            if (!empty($schedule)) {
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
                if (!empty($schedule)) {
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
            Log::error("Web saveScannedUnit error for Job {$job->id}: " . $e->getMessage());

            return ['ok' => false, 'code' => 500, 'message' => 'Gagal menyimpan data unit: ' . $e->getMessage(), 'job_schedule_unit_id' => null];
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

        if (!$materialIssue) {
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
            if (!in_array($relatedJob->status, $advanced, true)) {
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
            if (!$photo instanceof UploadedFile || !$photo->isValid()) {
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
        if (!is_dir($uploadPath) && !mkdir($uploadPath, 0775, true) && !is_dir($uploadPath)) {
            throw new \RuntimeException('Folder upload foto pekerjaan tidak bisa dibuat.');
        }
        if (!is_writable($uploadPath)) {
            throw new \RuntimeException('Folder upload foto pekerjaan tidak writable.');
        }

        $filename = time() . '_' . uniqid() . '_' . $suffix . '.' . $photo->getClientOriginalExtension();
        if (!$photo->move($uploadPath, $filename)) {
            throw new \RuntimeException('Gagal menyimpan foto pekerjaan.');
        }

        return self::PATH_PREFIX . $filename;
    }

    /**
     * Decode a base64 signature payload and store it as a PNG, returning the
     * stored relative path (mirrors verifyJob signature save).
     */
    private function storeSignature(string $signatureBase64, int $jobScheduleId): string
    {
        $uploadPath = public_path(self::UPLOAD_DIR);
        if (!is_dir($uploadPath) && !mkdir($uploadPath, 0775, true) && !is_dir($uploadPath)) {
            throw new \RuntimeException('Folder upload tanda tangan tidak bisa dibuat.');
        }

        $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureBase64));
        if ($data === false) {
            throw new \RuntimeException('Data tanda tangan tidak valid.');
        }

        $filename = 'signature_' . $jobScheduleId . '_' . time() . '.png';
        if (file_put_contents($uploadPath . DIRECTORY_SEPARATOR . $filename, $data) === false) {
            throw new \RuntimeException('Gagal menyimpan tanda tangan.');
        }

        return self::PATH_PREFIX . $filename;
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

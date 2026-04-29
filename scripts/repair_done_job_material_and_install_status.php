<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\InventoryIssuingItem;
use App\Models\JobAssignSchedule;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\MaterialIssue;
use App\Models\SerialNumber;
use App\Models\UnitOnWall;
use App\Services\Operational\JobMaterialCompletionService;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv, true);
$onlyJobs = [];

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--job=')) {
        $onlyJobs = array_values(array_filter(array_map('trim', explode(',', substr($arg, 6)))));
    }
}

$dryRunPrefix = $apply ? '[APPLY]' : '[DRY RUN]';

echo "{$dryRunPrefix} Repair completed-job serial status and install room status\n";
echo "Usage:\n";
echo "  php scripts/repair_done_job_material_and_install_status.php --job=JKT-CSR/26-04/0010\n";
echo "  php scripts/repair_done_job_material_and_install_status.php --job=JKT-IF/26-04/0013 --apply\n";
echo "  php scripts/repair_done_job_material_and_install_status.php --apply\n\n";

$completedStatuses = ['done_job', 'completed', 'selesai'];
$installTypes = ['install', 'install_free', 'install free'];
$serialStatusesToMove = ['on_hand', 'ready', 'available'];

$materialCompletionService = app(JobMaterialCompletionService::class);

function jobQuery(array $onlyJobs)
{
    return JobSchedule::query()
        ->when(!empty($onlyJobs), fn ($query) => $query->whereIn('job_number', $onlyJobs))
        ->orderBy('id');
}

function collectAssignmentIdsForJobs(array $jobIds): array
{
    return JobAssignSchedule::withTrashed()
        ->whereIn('job_schedule_id', $jobIds)
        ->pluck('id')
        ->all();
}

function collectIssueNumbersForJobs(array $jobIds): array
{
    return MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function ($query) use ($jobIds) {
        $query->whereIn('job_schedule_id', $jobIds);
    })
        ->pluck('issue_number')
        ->filter()
        ->unique()
        ->values()
        ->all();
}

function issuedItemsFor(array $assignmentIds, array $issueNumbers)
{
    return InventoryIssuingItem::query()
        ->with(['inventoryIssuing', 'product', 'serialNumber.masterProduct'])
        ->whereNotNull('serial_number_id')
        ->where(function ($query) use ($assignmentIds, $issueNumbers) {
            if (!empty($assignmentIds)) {
                $query->whereIn('job_assign_schedule_id', $assignmentIds);
            }

            if (!empty($issueNumbers)) {
                $query->orWhereHas('inventoryIssuing', function ($issuingQuery) use ($issueNumbers) {
                    $issuingQuery->whereIn('reference_no', $issueNumbers);
                });
            }
        })
        ->get();
}

function generateBaNumber(): string
{
    $branchCode = 'JKT';
    $typeCode = 'BA';
    $yearMonth = date('y-m');

    $count = JobSchedule::withTrashed()
        ->where('ba_number', 'like', "{$branchCode}-{$typeCode}/{$yearMonth}/%")
        ->count();

    return "{$branchCode}-{$typeCode}/{$yearMonth}/" . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

echo "Step 1: serial number status for completed jobs\n";

$completedJobs = jobQuery($onlyJobs)
    ->with('jobAdvice')
    ->whereIn('status', $completedStatuses)
    ->get()
    ->unique(fn (JobSchedule $job) => $job->job_number ?: 'job-' . $job->id)
    ->values();

$serialCandidatesTotal = 0;

foreach ($completedJobs as $job) {
    $relatedJobs = $job->job_number
        ? JobSchedule::where('job_number', $job->job_number)->get()
        : collect([$job]);

    $jobIds = $relatedJobs->pluck('id')->all();
    $assignmentIds = collectAssignmentIdsForJobs($jobIds);
    $issueNumbers = collectIssueNumbersForJobs($jobIds);

    $candidates = issuedItemsFor($assignmentIds, $issueNumbers)
        ->filter(fn (InventoryIssuingItem $item) => $item->serialNumber
            && in_array($item->serialNumber->status, ['on_hand', 'ready', 'available'], true))
        ->unique('serial_number_id')
        ->values();

    if ($candidates->isEmpty()) {
        continue;
    }

    $serialCandidatesTotal += $candidates->count();
    $serialList = $candidates
        ->map(fn (InventoryIssuingItem $item) => ($item->serialNumber->serial_number ?? '-') . ' [' . ($item->product->name ?? $item->serialNumber?->masterProduct?->name ?? '-') . ':' . ($item->serialNumber->status ?? '-') . ']')
        ->implode(', ');

    echo "- {$job->job_number} (#{$job->id}) {$candidates->count()} SN => {$serialList}\n";

    if ($apply) {
        $materialCompletionService->finalizeForCompletedJob($job);
    }
}

echo "Step 1 total candidate SN: {$serialCandidatesTotal}\n\n";

echo "Step 2: install jobs with active Unit On Wall but unfinished room/status\n";

$installJobs = jobQuery($onlyJobs)
    ->with(['jobAdvice', 'jobScheduleRooms'])
    ->whereIn(DB::raw('LOWER(COALESCE(type, ""))'), $installTypes)
    ->whereNotIn('status', $completedStatuses)
    ->get();

$installRepairs = 0;

foreach ($installJobs as $job) {
    if (!$job->jobAdvice || !$job->jobAdvice->customer_id || !$job->building_id) {
        continue;
    }

    $unfinishedRooms = $job->jobScheduleRooms
        ->where('status', '!=', JobScheduleRoom::STATUS_COMPLETED);

    if ($unfinishedRooms->isEmpty()) {
        continue;
    }

    $assignmentIds = collectAssignmentIdsForJobs([$job->id]);
    if (empty($assignmentIds)) {
        continue;
    }

    $issuedSerialIds = InventoryIssuingItem::whereIn('job_assign_schedule_id', $assignmentIds)
        ->whereNotNull('serial_number_id')
        ->pluck('serial_number_id')
        ->filter()
        ->unique()
        ->values()
        ->all();

    if (empty($issuedSerialIds)) {
        continue;
    }

    $roomsToComplete = collect();

    foreach ($unfinishedRooms as $room) {
        $activeUnit = UnitOnWall::whereIn('serial_number_id', $issuedSerialIds)
            ->where('status', 'active')
            ->where('customer_id', $job->jobAdvice->customer_id)
            ->where('building_id', $job->building_id)
            ->where(function ($query) use ($room) {
                if ($room->room_id) {
                    $query->where('room_id', $room->room_id);
                }

                if ($room->room_name) {
                    $query->orWhereRaw('LOWER(TRIM(room_name)) = ?', [strtolower(trim($room->room_name))]);
                }
            })
            ->first();

        if ($activeUnit) {
            $roomsToComplete->push([$room, $activeUnit]);
        }
    }

    if ($roomsToComplete->isEmpty()) {
        continue;
    }

    $installRepairs++;
    echo "- {$job->job_number} (#{$job->id}) status={$job->status}: {$roomsToComplete->count()} room(s) have active UOW\n";

    foreach ($roomsToComplete as [$room, $unit]) {
        echo "  room {$room->id} {$room->room_name} => UOW {$unit->id} SN {$unit->serial_number}\n";
    }

    if ($apply) {
        DB::transaction(function () use ($job, $roomsToComplete, $materialCompletionService) {
            foreach ($roomsToComplete as [$room]) {
                $room->markAsCompleted(null, 'Server repair: active Unit On Wall already exists for issued serial number.');
            }

            $job->refresh();

            if ($job->areAllRoomsCompleted()) {
                $updates = [
                    'status' => 'done_job',
                    'work_status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ];

                if (!$job->ba_date) {
                    $updates['ba_date'] = now()->toDateString();
                }

                if (!$job->ba_number) {
                    $updates['ba_number'] = generateBaNumber();
                }

                $job->update($updates);

                JobAssignSchedule::where('job_schedule_id', $job->id)
                    ->where('status', '!=', 'cancelled')
                    ->update([
                        'status' => 'completed',
                        'updated_at' => now(),
                    ]);

                $materialCompletionService->finalizeForCompletedJob($job);
            }
        });
    }
}

echo "Step 2 candidate install job(s): {$installRepairs}\n\n";

if (!$apply) {
    echo "Dry-run complete. No data changed. Add --apply to update server data.\n";
} else {
    echo "Repair applied.\n";
}


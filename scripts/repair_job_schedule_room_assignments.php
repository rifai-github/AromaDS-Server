<?php

use App\Models\JobAssignSchedule;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoomAssignment;
use App\Services\Warehouse\InventoryIssuingService;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apply = in_array('--apply', $argv, true);
$jobNumbers = [];

foreach ($argv as $index => $arg) {
    if ($arg === '--job-number' && isset($argv[$index + 1])) {
        $jobNumbers[] = $argv[$index + 1];
    } elseif (str_starts_with($arg, '--job-number=')) {
        $jobNumbers[] = substr($arg, strlen('--job-number='));
    } elseif (str_starts_with($arg, '--job-numbers=')) {
        $jobNumbers = array_merge($jobNumbers, explode(',', substr($arg, strlen('--job-numbers='))));
    }
}

$jobNumbers = collect($jobNumbers)
    ->map(fn ($value) => trim((string) $value))
    ->filter()
    ->unique()
    ->values()
    ->all();

$query = JobSchedule::query()
    ->with([
        'jobScheduleRooms.roomAssignment.team',
        'jobAssignSchedules' => fn ($query) => $query
            ->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at')
            ->whereNotNull('team_id')
            ->with('team')
            ->orderByDesc('id'),
    ])
    ->whereHas('jobScheduleRooms')
    ->whereHas('jobAssignSchedules', function ($query) {
        $query->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at')
            ->whereNotNull('team_id');
    });

if (!empty($jobNumbers)) {
    $query->whereIn('job_number', $jobNumbers);
}

$jobs = $query->orderBy('job_number')->orderBy('id')->get();
$mode = $apply ? 'APPLY' : 'DRY-RUN';
$service = app(InventoryIssuingService::class);
$repairCount = 0;
$skipCount = 0;

echo "[{$mode}] Scan {$jobs->count()} job schedule(s)";
if (!empty($jobNumbers)) {
    echo ' for job_number: ' . implode(', ', $jobNumbers);
}
echo "\n";

foreach ($jobs as $job) {
    $activeAssignments = $job->jobAssignSchedules;
    $activeTeamIds = $activeAssignments->pluck('team_id')->filter()->unique()->values();

    if ($activeTeamIds->count() > 1) {
        $skipCount++;
        echo "- SKIP {$job->job_number} id={$job->id}: multiple active teams (" . $activeTeamIds->implode(', ') . ")\n";
        continue;
    }

    $activeAssignment = $activeAssignments->first();
    if (!$activeAssignment) {
        continue;
    }

    $staleRooms = [];
    foreach ($job->jobScheduleRooms as $room) {
        $roomAssignment = JobScheduleRoomAssignment::withTrashed()
            ->where('job_schedule_id', $job->id)
            ->where('job_schedule_room_id', $room->id)
            ->where('status', '!=', 'cancelled')
            ->latest('id')
            ->first();

        if ($roomAssignment && (bool) $roomAssignment->is_custom) {
            continue;
        }

        $isStale = !$roomAssignment
            || $roomAssignment->trashed()
            || (int) $roomAssignment->team_id !== (int) $activeAssignment->team_id
            || (int) ($roomAssignment->job_assign_schedule_id ?? 0) !== (int) $activeAssignment->id;

        if ($isStale) {
            $staleRooms[] = [
                'room_id' => $room->id,
                'room_name' => $room->room_name,
                'old_team_id' => $roomAssignment?->team_id,
                'old_assignment_id' => $roomAssignment?->id,
            ];
        }
    }

    if (empty($staleRooms)) {
        continue;
    }

    $repairCount++;
    echo "- REPAIR {$job->job_number} id={$job->id} room={$job->room_name} ";
    echo "=> team={$activeAssignment->team?->team_name} (#{$activeAssignment->team_id}), ";
    echo 'stale_rooms=' . count($staleRooms) . "\n";

    foreach ($staleRooms as $staleRoom) {
        echo "  * JSR #{$staleRoom['room_id']} {$staleRoom['room_name']} ";
        echo "old_assignment={$staleRoom['old_assignment_id']} old_team={$staleRoom['old_team_id']}\n";
    }

    if (!$apply) {
        continue;
    }

    DB::transaction(function () use ($service, $job, $activeAssignment) {
        $service->syncRoomAssignmentsForJobSchedule(
            $job,
            (int) $activeAssignment->team_id,
            (int) $activeAssignment->id,
            $activeAssignment->assigned_date?->toDateString()
        );
    });
}

echo "[{$mode}] Done. repair_candidates={$repairCount}, skipped={$skipCount}.\n";
echo $apply
    ? "Apply selesai.\n"
    : "Dry-run saja. Jalankan dengan --apply untuk update data.\n";


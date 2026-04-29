<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use Illuminate\Support\Facades\DB;

$jobNumber = null;
$apply = in_array('--apply', $argv, true);

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--job=')) {
        $jobNumber = strtoupper(trim(substr($arg, 6)));
    }
}

if (!$jobNumber) {
    echo "Usage: php scripts/audit_install_remove_room_status.php --job=JKT-IF/26-04/0015\n";
    echo "Repair: php scripts/audit_install_remove_room_status.php --job=JKT-IF/26-04/0015 --apply\n";
    exit(1);
}

$installJob = JobSchedule::with(['jobAdvice.rooms'])
    ->whereRaw('UPPER(job_number) = ?', [$jobNumber])
    ->first();

if (!$installJob) {
    echo "Job {$jobNumber} tidak ditemukan.\n";
    exit(1);
}

echo "Install job: {$installJob->job_number}\n";
echo "Type/status: {$installJob->type} / {$installJob->status}\n";
echo "JA: " . ($installJob->jobAdvice?->job_advice_number ?? '-') . "\n\n";

$installRooms = JobScheduleRoom::where('job_schedule_id', $installJob->id)
    ->orderBy('room_name')
    ->get();

echo "Rooms pada install job:\n";
foreach ($installRooms as $room) {
    echo "- JSR {$room->id}, JA room {$room->job_advice_room_id}, {$room->room_name}, status {$room->status}, completed_at " . ($room->completed_at ?: '-') . "\n";
}

echo "\nRemove links dari JA rooms:\n";
$rooms = $installJob->jobAdvice?->rooms ?? collect();
foreach ($rooms->sortBy('room_name') as $jaRoom) {
    $sourceRoom = $installRooms->firstWhere('job_advice_room_id', $jaRoom->id);
    $removeJob = $jaRoom->remove_job_schedule_id
        ? JobSchedule::find($jaRoom->remove_job_schedule_id)
        : null;

    echo "- JA room {$jaRoom->id}, {$jaRoom->room_name}, source status " . ($sourceRoom?->status ?? '-') . ", remove job " . ($removeJob?->job_number ?? '-') . " (" . ($removeJob?->status ?? '-') . ")\n";
}

echo "\nRemove job rooms yang terkait JA ini:\n";
$removeRows = DB::table('job_schedule_rooms as jsr')
    ->join('job_schedules as js', 'js.id', '=', 'jsr.job_schedule_id')
    ->where('js.job_advice_id', $installJob->job_advice_id)
    ->whereIn('js.type', ['remove', 'remove_free'])
    ->whereNull('js.deleted_at')
    ->whereNull('jsr.deleted_at')
    ->orderBy('js.job_number')
    ->orderBy('jsr.room_name')
    ->select([
        'js.job_number',
        'js.status as job_status',
        'js.type',
        'jsr.id as jsr_id',
        'jsr.job_advice_room_id',
        'jsr.room_name',
        'jsr.status as room_status',
    ])
    ->get();

if ($removeRows->isEmpty()) {
    echo "- Tidak ada remove job untuk JA ini.\n";
    exit(0);
}

$anomalies = collect();
foreach ($removeRows as $row) {
    $sourceRoom = $installRooms->firstWhere('job_advice_room_id', $row->job_advice_room_id);
    $sourceStatus = $sourceRoom?->status ?? '-';
    $isAnomaly = $sourceStatus !== JobScheduleRoom::STATUS_COMPLETED;
    $flag = $isAnomaly ? ' [ANOMALI: source belum completed]' : '';

    if ($isAnomaly) {
        $anomalies->push($row);
    }

    echo "- {$row->job_number} {$row->type}/{$row->job_status}, remove JSR {$row->jsr_id}, {$row->room_name}, source status {$sourceStatus}{$flag}\n";
}

if ($anomalies->isEmpty()) {
    exit(0);
}

echo "\n" . ($apply ? '[APPLY]' : '[DRY RUN]') . " Repair room remove yang source install-nya belum completed:\n";
foreach ($anomalies as $row) {
    echo "- Clear JA room {$row->job_advice_room_id} ({$row->room_name}) dari remove job {$row->job_number}, delete remove JSR {$row->jsr_id}\n";
}

if (!$apply) {
    echo "Belum mengubah data. Tambahkan --apply untuk eksekusi.\n";
    exit(0);
}

DB::transaction(function () use ($anomalies) {
    foreach ($anomalies as $row) {
        DB::table('job_advice_rooms')
            ->where('id', $row->job_advice_room_id)
            ->update([
                'remove_job_schedule_id' => null,
                'updated_at' => now(),
            ]);

        DB::table('job_schedule_rooms')
            ->where('id', $row->jsr_id)
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }
});

echo "Repair selesai. Remove job valid untuk room yang sudah completed tetap dipertahankan.\n";

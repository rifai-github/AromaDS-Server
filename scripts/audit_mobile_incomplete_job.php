<?php

use App\Models\InventoryReceiving;
use App\Models\JobSchedule;
use App\Models\JobScheduleRoom;
use App\Models\MaterialReturn;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$options = getopt('', ['job::', 'customer::', 'room::', 'apply']);
$jobNumber = $options['job'] ?? null;
$customer = $options['customer'] ?? null;
$room = $options['room'] ?? null;
$apply = array_key_exists('apply', $options);

if (!$jobNumber && !$customer && !$room) {
    fwrite(STDERR, "Usage:\n");
    fwrite(STDERR, "  php scripts/audit_mobile_incomplete_job.php --job=JKT-CSR/26-04/0001\n");
    fwrite(STDERR, "  php scripts/audit_mobile_incomplete_job.php --customer=Aeon --room=\"Lobby Utama\"\n");
    fwrite(STDERR, "  add --apply to cancel duplicate follow-up rooms/jobs and hide old partial jobs\n");
    exit(1);
}

$query = JobSchedule::with([
    'jobAdvice.customer',
    'jobScheduleRooms',
]);

if ($jobNumber) {
    $query->where('job_number', $jobNumber);
}

if ($customer) {
    $query->whereHas('jobAdvice.customer', function ($q) use ($customer) {
        $q->where('name', 'like', '%' . $customer . '%');
    });
}

if ($room) {
    $query->whereHas('jobScheduleRooms', function ($q) use ($room) {
        $q->where('room_name', 'like', '%' . $room . '%');
    });
}

$jobs = $query->latest('id')->limit(25)->get();

if ($jobs->isEmpty()) {
    echo "No jobs found.\n";
    exit(0);
}

foreach ($jobs as $job) {
    $customerName = $job->jobAdvice?->customer?->name ?? '-';
    echo "\nJob #{$job->id} {$job->job_number} | {$job->type} | {$job->status} | {$customerName}\n";

    $rooms = $job->jobScheduleRooms->map(function ($room) {
        return "#{$room->id} {$room->room_name} [{$room->status}] return={$room->material_return_status}";
    })->implode("\n  - ");
    echo $rooms ? "  - {$rooms}\n" : "  - no job_schedule_rooms\n";

    $originPrefix = 'Lanjutan dari Job ' . $job->job_number;
    $followUps = JobSchedule::with('jobScheduleRooms')
        ->where('job_advice_id', $job->job_advice_id)
        ->where('building_id', $job->building_id)
        ->where('type', $job->type)
        ->where('internal_notes', 'like', $originPrefix . '%')
        ->whereNotIn('status', ['cancelled', 'done_job', 'completed', 'selesai'])
        ->orderBy('id')
        ->get();

    echo "  Follow-up active: {$followUps->count()}\n";

    $seenRoomKeys = [];
    foreach ($followUps as $followUp) {
        echo "    - #{$followUp->id} {$followUp->job_number} | {$followUp->status} | rooms={$followUp->jobScheduleRooms->count()}\n";

        foreach ($followUp->jobScheduleRooms as $followUpRoom) {
            $key = $followUpRoom->job_advice_room_id ?: strtolower(trim((string) $followUpRoom->room_name));
            if (isset($seenRoomKeys[$key])) {
                echo "      duplicate room {$followUpRoom->room_name} also exists in follow-up #{$seenRoomKeys[$key]}\n";

                if ($apply) {
                    $followUpRoom->update([
                        'status' => JobScheduleRoom::STATUS_CANCELLED,
                        'notes' => trim(($followUpRoom->notes ? $followUpRoom->notes . "\n" : '') . 'Cancelled by audit_mobile_incomplete_job duplicate cleanup.'),
                    ]);
                    echo "      applied: room cancelled\n";
                }
            } else {
                $seenRoomKeys[$key] = $followUp->id;
            }
        }

        if ($apply && $followUp->jobScheduleRooms()
            ->whereNotIn('status', [JobScheduleRoom::STATUS_CANCELLED])
            ->doesntExist()) {
            $followUp->update([
                'status' => 'cancelled',
                'internal_notes' => trim(($followUp->internal_notes ? $followUp->internal_notes . "\n" : '') . 'Cancelled by audit_mobile_incomplete_job because all follow-up rooms were duplicates.'),
            ]);
            echo "      applied: empty duplicate follow-up job cancelled\n";
        }
    }

    $materialReturns = MaterialReturn::where('job_schedule_id', $job->id)
        ->where('notes', 'like', 'Auto-return dari Aplikasi teknisi via Job ' . $job->job_number . '%')
        ->get();
    $receivings = InventoryReceiving::where('reference_no', $job->job_number)
        ->where('notes', 'like', 'Auto-return dari Aplikasi teknisi via Job ' . $job->job_number . '%')
        ->get();

    echo "  Auto Material Return: {$materialReturns->count()}\n";
    foreach ($materialReturns as $return) {
        echo "    - {$return->return_number} | {$return->status} | items={$return->items()->count()}\n";
    }

    echo "  Auto Inventory Receiving: {$receivings->count()}\n";
    foreach ($receivings as $receiving) {
        echo "    - {$receiving->receiving_number} | {$receiving->status} | items={$receiving->items()->count()}\n";
    }

    $hasMovedRooms = $job->jobScheduleRooms
        ->where('status', JobScheduleRoom::STATUS_CANCELLED)
        ->where('material_return_status', JobScheduleRoom::MATERIAL_RETURN_RETURNED)
        ->isNotEmpty();

    if ($apply && $hasMovedRooms && $job->status !== 'meninggalkan_lokasi') {
        $job->update([
            'status' => 'meninggalkan_lokasi',
            'completed_at' => null,
        ]);
        echo "  applied: original partial job hidden from technician app as meninggalkan_lokasi\n";
    }
}

echo "\nMode: " . ($apply ? "APPLY\n" : "DRY RUN\n");

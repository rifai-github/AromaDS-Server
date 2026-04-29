<?php

/**
 * Script untuk sinkronisasi massal:
 * 1. Status unit (ON HAND -> IN USED)
 * 2. Auto-generate Invoice (Mendukung New & Renewal)
 * 
 * Cara jalankan: php artisan tinker database/scripts/sync_unit_status_fix.php
 */

use App\Models\JobSchedule;
use App\Models\JobAdviceRoom;
use App\Models\UnitOnWall;
use App\Models\SerialNumber;
use App\Models\Contract;
use App\Http\Controllers\Operational\JobScheduleController;

echo "--- Memulai Proses Sinkronisasi Server ---\n";

// 1. Ambil semua Kontrak Aktif yang mungkin butuh Invoice
$activeContracts = Contract::whereHas('jobSchedules', function($q) {
    $q->whereIn('job_schedules.status', ['done_job', 'completed']);
})->get();

echo "Memeriksa " . $activeContracts->count() . " kontrak untuk pengecekan invoice...\n";

$controller = new JobScheduleController();
$reflection = new ReflectionClass($controller);
$triggerInvoiceMethod = $reflection->getMethod('triggerAutoInvoiceGeneration');
$triggerInvoiceMethod->setAccessible(true);

$syncUnitMethod = $reflection->getMethod('autoCreateUnitOnWall');
$syncUnitMethod->setAccessible(true);

$invoiceCreated = 0;
$unitSynced = 0;

foreach ($activeContracts as $contract) {
    // Debug Job Status & Billing Group
    $bg = \App\Models\Finance\BillingGroup::where('contract_id', $contract->id)->first();
    if ($bg) {
        echo "Processing Contract: {$contract->contract_number} (ID: {$contract->id}) ... \n";
        echo "  BG: ID {$bg->id} | Name: {$bg->billing_group_name} | Start: {$bg->billing_start_date} | Hold: " . ($bg->hold_invoice ? 'YES' : 'NO') . " | BA Req: " . ($bg->ba_files_supported ? 'YES' : 'NO') . "\n";
    } else {
        echo "Processing Contract: {$contract->contract_number} (ID: {$contract->id}) ... (BG NOT FOUND)\n";
    }

    $jobStats = $contract->jobSchedules()
        ->get(['job_schedules.status'])
        ->groupBy('status')
        ->map(function ($items) {
            return count($items);
        });

    echo "  Jobs: ";
    foreach($jobStats as $status => $total) echo "{$status}: {$total}, ";
    echo "\n";

    // A. PEMICU INVOICE
    try {
        $result = $triggerInvoiceMethod->invoke($controller, $contract->id);
        if (is_array($result)) {
            echo "  [Invoice Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED - ' . $result['message']) . "]\n";
        } else {
            echo "  [Invoice Result: Attempted]\n";
        }
    } catch (\Exception $e) {
        echo "  [Invoice Error: " . $e->getMessage() . "]\n";
    }

    // B. SINKRONISASI UNIT (ON HAND -> IN USED)
    $jobs = $contract->jobSchedules()
        ->whereIn('job_schedules.status', ['done_job', 'completed'])
        ->whereIn('job_schedules.type', ['install', 'install_free', 'service', 'change_rental'])
        ->get();

    foreach ($jobs as $job) {
        // Reset flag JA Room jika Unit On Wall belum ada (untuk memicu sync ulang)
        foreach ($job->jobScheduleRooms as $room) {
            $jaRoom = JobAdviceRoom::find($room->job_advice_room_id);
            if ($jaRoom && $jaRoom->unit_already_installed) {
                $hasUOW = UnitOnWall::where('building_id', $job->building_id)
                    ->where('room_id', $room->room_id)
                    ->where('status', 'active')
                    ->exists();
                
                if (!$hasUOW) {
                    $jaRoom->update(['unit_already_installed' => 0]);
                }
            }
        }

        $jobAdvice = $job->jobAdvice;
        if ($jobAdvice) {
            $res = $syncUnitMethod->invoke($controller, $job, $jobAdvice);
            if ($res) $unitSynced++;
        }
    }
    echo "[Unit Sync DONE]\n";
}

// 2. Perbaikan status SN yang "nyangkut" secara massal
echo "\nMemeriksa konsistensi Serial Number...\n";
$serialNumbers = SerialNumber::where('status', 'on_hand')->get();
foreach ($serialNumbers as $sn) {
    $uow = UnitOnWall::where('serial_number_id', $sn->id)->where('status', 'active')->first();
    if ($uow) {
        echo "Memperbaiki SN {$sn->serial_number} -> IN USED\n";
        $sn->update([
            'status' => 'in_use',
            'location_type' => 'customer',
            'location_id' => $uow->customer_id
        ]);
    }
}

echo "\n--- Sinkronisasi Selesai! ---\n";

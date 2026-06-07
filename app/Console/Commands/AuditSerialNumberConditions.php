<?php

namespace App\Console\Commands;

use App\Models\SerialNumber;
use Illuminate\Console\Command;

class AuditSerialNumberConditions extends Command
{
    protected $signature = 'warehouse:audit-serial-conditions {--apply : Update condition_status for legacy serial numbers}';

    protected $description = 'Audit and optionally backfill legacy serial number condition_status values.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $counts = [
            SerialNumber::CONDITION_NEW => 0,
            SerialNumber::CONDITION_SECOND_READY => 0,
            SerialNumber::CONDITION_DAMAGED => 0,
        ];

        SerialNumber::with(['unitOnWalls'])
            ->whereNull('condition_status')
            ->orderBy('id')
            ->chunkById(200, function ($serialNumbers) use ($apply, &$counts) {
                foreach ($serialNumbers as $serialNumber) {
                    $condition = $this->inferCondition($serialNumber);
                    $counts[$condition]++;

                    if ($apply) {
                        $serialNumber->update(['condition_status' => $condition]);
                    }
                }
            });

        $this->table(['Condition', 'Count'], [
            ['Baru', $counts[SerialNumber::CONDITION_NEW]],
            ['Bekas / Siap Pakai', $counts[SerialNumber::CONDITION_SECOND_READY]],
            ['Rusak', $counts[SerialNumber::CONDITION_DAMAGED]],
        ]);

        $this->info($apply
            ? 'Backfill condition_status selesai.'
            : 'Dry-run selesai. Jalankan dengan --apply untuk menyimpan perubahan.');

        return self::SUCCESS;
    }

    private function inferCondition(SerialNumber $serialNumber): string
    {
        if (in_array($serialNumber->status, ['broken', 'damaged', 'retired'], true)) {
            return SerialNumber::CONDITION_DAMAGED;
        }

        if ($serialNumber->unitOnWalls->isNotEmpty() || in_array($serialNumber->location_type, ['customer', 'technician'], true)) {
            return SerialNumber::CONDITION_SECOND_READY;
        }

        if (in_array($serialNumber->status, ['in_use', 'on_hand', 'on_hand_remove'], true)) {
            return SerialNumber::CONDITION_SECOND_READY;
        }

        return SerialNumber::CONDITION_NEW;
    }
}

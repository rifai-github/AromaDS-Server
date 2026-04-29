<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$activeStatuses = ['pending', 'processed', 'sent'];
$serialFilter = null;
$clearItemId = null;
$apply = in_array('--apply', $argv, true);

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--serial=')) {
        $serialFilter = strtoupper(trim(substr($arg, 9)));
    }

    if (str_starts_with($arg, '--clear-item=')) {
        $clearItemId = (int) substr($arg, 13);
    }
}

echo "Audit duplicate Serial Number in active Inventory Issuing\n";
echo "Active statuses: " . implode(', ', $activeStatuses) . "\n";
echo "Usage: php scripts/audit_duplicate_active_issuing_serials.php --serial=CL1604003\n\n";
echo "Repair usage: php scripts/audit_duplicate_active_issuing_serials.php --serial=CL1604003 --clear-item=496 --apply\n\n";

$duplicateSerialIds = DB::table('inventory_issuing_items as iii')
    ->join('inventory_issuings as ii', 'ii.id', '=', 'iii.inventory_issuing_id')
    ->join('serial_numbers as sn', 'sn.id', '=', 'iii.serial_number_id')
    ->whereNotNull('iii.serial_number_id')
    ->whereIn('ii.status', $activeStatuses)
    ->when($serialFilter, function ($query) use ($serialFilter) {
        $query->whereRaw('UPPER(sn.serial_number) = ?', [$serialFilter]);
    })
    ->groupBy('iii.serial_number_id')
    ->havingRaw('COUNT(*) > 1')
    ->pluck('iii.serial_number_id');

if ($duplicateSerialIds->isEmpty()) {
    echo $serialFilter
        ? "Tidak ada duplikasi aktif untuk SN {$serialFilter}.\n"
        : "Tidak ada duplikasi SN pada Inventory Issuing aktif.\n";
    exit(0);
}

foreach ($duplicateSerialIds as $serialId) {
    $rows = DB::table('inventory_issuing_items as iii')
        ->join('inventory_issuings as ii', 'ii.id', '=', 'iii.inventory_issuing_id')
        ->join('serial_numbers as sn', 'sn.id', '=', 'iii.serial_number_id')
        ->leftJoin('master_products as mp', 'mp.id', '=', 'iii.product_id')
        ->where('iii.serial_number_id', $serialId)
        ->whereIn('ii.status', $activeStatuses)
        ->orderBy('ii.issuing_number')
        ->select([
            'sn.serial_number',
            'sn.status as serial_status',
            'ii.issuing_number',
            'ii.status as issuing_status',
            'iii.id as item_id',
            'iii.room_name',
            'mp.name as product_name',
            'iii.created_at',
        ])
        ->get();

    $first = $rows->first();
    echo "SN {$first->serial_number} (status SN: {$first->serial_status}) dipakai {$rows->count()} kali:\n";

    foreach ($rows as $row) {
        echo "- WI {$row->issuing_number} ({$row->issuing_status}), item_id {$row->item_id}, product {$row->product_name}, room " . ($row->room_name ?: '-') . ", created {$row->created_at}\n";
    }

    echo "\n";
}

if ($clearItemId) {
    $item = DB::table('inventory_issuing_items as iii')
        ->join('inventory_issuings as ii', 'ii.id', '=', 'iii.inventory_issuing_id')
        ->join('serial_numbers as sn', 'sn.id', '=', 'iii.serial_number_id')
        ->where('iii.id', $clearItemId)
        ->whereIn('ii.status', $activeStatuses)
        ->select([
            'iii.id',
            'iii.serial_number_id',
            'sn.serial_number',
            'ii.issuing_number',
            'ii.status as issuing_status',
        ])
        ->first();

    if (!$item) {
        echo "Tidak bisa repair: item_id {$clearItemId} tidak ditemukan sebagai item WI aktif dengan SN.\n";
        exit(1);
    }

    if (!$duplicateSerialIds->contains($item->serial_number_id)) {
        echo "Tidak bisa repair: SN {$item->serial_number} pada item_id {$clearItemId} tidak termasuk duplikasi hasil audit.\n";
        exit(1);
    }

    echo ($apply ? '[APPLY]' : '[DRY RUN]') . " Clear SN {$item->serial_number} dari WI {$item->issuing_number}, item_id {$item->id}.\n";

    if ($apply) {
        DB::table('inventory_issuing_items')
            ->where('id', $clearItemId)
            ->update([
                'serial_number_id' => null,
                'updated_at' => now(),
            ]);

        echo "Berhasil. Silakan assign SN pengganti yang benar untuk item tersebut.\n";
    } else {
        echo "Belum mengubah data. Tambahkan --apply untuk eksekusi.\n";
    }
}

<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';

use App\Models\Finance\BillingGroup;
use Illuminate\Support\Facades\DB;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Memulai migrasi data pajak untuk Billing Groups...\n";

$billingGroups = BillingGroup::whereNotNull('tax_number')->get();
$count = 0;

foreach ($billingGroups as $bg) {
    if ($bg->npwp || $bg->nitku || $bg->nik) {
        // Lewati jika sudah terisi salah satu kolom baru
        continue;
    }

    $taxNumber = $bg->tax_number;
    $parts = explode(', ', $taxNumber);
    
    $npwp = null;
    $nitku = null;
    $nik = null;

    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part)) continue;

        // Logika penebakan jenis pajak berdasarkan panjang karakter (secara umum di Indonesia)
        $length = strlen($part);

        if ($length === 22) {
            // NITKU (16 digit NPWP + 6 digit sequence)
            $nitku = $part;
        } elseif ($length === 15 || $length === 16) {
            // NPWP (15 atau 16 digit)
            if (!$npwp) $npwp = $part;
            else $nik = $part; // Jika sudah ada NPWP, mungkin NIK?
        } elseif ($length > 16) {
            // Mungkin NITKU tapi tidak standar 22?
            if (!$nitku) $nitku = $part;
        } else {
            // Mungkin NIK atau lainnya
            if (!$nik) $nik = $part;
        }
    }

    if ($npwp || $nitku || $nik) {
        $bg->update([
            'npwp' => $npwp,
            'nitku' => $nitku,
            'nik' => $nik
        ]);
        $count++;
    }
}

echo "Migrasi selesai! Berhasil memperbarui {$count} record.\n";

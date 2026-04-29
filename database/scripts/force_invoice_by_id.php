<?php

/**
 * Script untuk FORCE pemicu invoice berdasarkan ID Kontrak tertentu.
 * Sangat berguna untuk data lama yang tertunda di server.
 * 
 * Cara jalankan: php artisan tinker database/scripts/force_invoice_by_id.php -- --id=71
 * (Atau ubah variabel $contractId di bawah)
 */

use App\Models\Contract;
use App\Models\Finance\BillingGroup;
use App\Models\Finance\Invoice;
use App\Http\Controllers\Operational\JobScheduleController;
use Carbon\Carbon;

$contractId = 71; // GANTI ID DISINI

echo "--- FORCE TRIGGER INVOICE UNTUK KONTRAK ID: $contractId ---\n";

$contract = Contract::find($contractId);
if (!$contract) {
    die("ERROR: Kontrak ID $contractId tidak ditemukan.\n");
}

echo "Kontrak: {$contract->contract_number}\n";

// 1. Cek Billing Group
$bg = BillingGroup::where('contract_id', $contractId)->first();
if (!$bg) {
    echo "ERROR: Billing Group TIDAK DITEMUKAN untuk kontrak ini.\n";
    echo "Mencoba mencari Billing Group berdasarkan relasi...\n";
    $bg = $contract->billingGroups()->first();
}

if ($bg) {
    echo "Billing Group Found: ID {$bg->id} | Name: {$bg->billing_group_name}\n";
    echo "  - Start Date: {$bg->billing_start_date}\n";
    echo "  - Hold Invoice: " . ($bg->hold_invoice ? 'YES' : 'NO') . "\n";
    echo "  - BA Required: " . ($bg->ba_files_supported ? 'YES' : 'NO') . "\n";
} else {
    die("FATAL: Kontrak ini tidak punya Billing Group. Invoice tidak bisa dibuat otomatis.\n");
}

// 2. Cek Pekerjaan (Jobs)
$jobs = $contract->jobSchedules()->get();
echo "Total Jobs: " . $jobs->count() . "\n";
$doneJobs = 0;
foreach ($jobs as $job) {
    echo "  - Job ID: {$job->id} | Type: {$job->type} | Status: {$job->status} | Date: {$job->start_date}\n";
    if (in_array($job->status, ['done_job', 'completed', 'dpf'])) {
        $doneJobs++;
    }
}
echo "Jobs Selesai: $doneJobs / " . $jobs->count() . "\n";

// 3. Cek Invoice yang Sudah Ada
$existing = Invoice::where('billing_group_id', $bg->id)
    ->whereMonth('invoice_date', Carbon::now()->month)
    ->whereYear('invoice_date', Carbon::now()->year)
    ->first();

if ($existing) {
    echo "PERINGATAN: Invoice SUDAH ADA untuk periode ini (ID: {$existing->id}, Num: {$existing->invoice_number}).\n";
}

// 4. EKSEKUSI TRIGGER
echo "Mencoba memanggil triggerAutoInvoiceGeneration...\n";
$controller = new JobScheduleController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('triggerAutoInvoiceGeneration');
$method->setAccessible(true);

$result = $method->invoke($controller, $contractId);

if (is_array($result)) {
    echo "HASIL: " . ($result['success'] ? 'BERHASIL' : 'GAGAL') . "\n";
    echo "PESAN: " . ($result['message'] ?? '-') . "\n";
} else {
    echo "HASIL: Selesai dijalankan (cek daftar invoice).\n";
}

// 5. Verifikasi Akhir
$finalCheck = Invoice::where('billing_group_id', $bg->id)->latest()->first();
if ($finalCheck && $finalCheck->created_at->isToday()) {
    echo "SUKSES: Invoice baru terdeteksi: {$finalCheck->invoice_number} (ID: {$finalCheck->id})\n";
} else {
    echo "INFO: Tidak ada invoice baru yang dibuat hari ini untuk BG ini.\n";
}

echo "--- Selesai ---\n";

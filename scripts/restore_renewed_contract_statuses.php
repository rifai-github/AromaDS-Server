<?php

use App\Models\Contract;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dryRun = in_array('--dry-run', $argv, true);
$contractNumber = null;

foreach ($argv as $index => $arg) {
    if ($arg === '--contract' && isset($argv[$index + 1])) {
        $contractNumber = $argv[$index + 1];
    } elseif (str_starts_with($arg, '--contract=')) {
        $contractNumber = substr($arg, strlen('--contract='));
    }
}

$query = Contract::withoutGlobalScopes()
    ->where('contract_status', 'renewed');

if ($contractNumber) {
    $query->where('contract_number', $contractNumber);
}

$contracts = $query->orderBy('id')->get();

echo ($dryRun ? '[DRY-RUN] ' : '[APPLY] ') . "Found {$contracts->count()} contract(s) with status renewed to restore.\n";

foreach ($contracts as $contract) {
    $newContract = $contract->renewedByContract;
    $targetStatus = 'active';

    echo "- {$contract->contract_number}: renewed => {$targetStatus}; successor=" . ($newContract?->contract_number ?? '-') . "\n";

    if ($dryRun) {
        continue;
    }

    DB::transaction(function () use ($contract, $targetStatus) {
        $contract->update([
            'contract_status' => $targetStatus,
            'updated_by' => 1,
        ]);
    });
}

echo $dryRun
    ? "Dry-run selesai. Jalankan tanpa --dry-run untuk apply.\n"
    : "Apply selesai.\n";

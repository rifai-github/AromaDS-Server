<?php

use App\Models\MaterialIssue;
use App\Models\MaterialIssueItem;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dryRun = in_array('--dry-run', $argv, true);
$includeIssued = in_array('--include-issued', $argv, true);
$jobNumber = null;

foreach ($argv as $index => $arg) {
    if ($arg === '--job-number' && isset($argv[$index + 1])) {
        $jobNumber = $argv[$index + 1];
    } elseif (str_starts_with($arg, '--job-number=')) {
        $jobNumber = substr($arg, strlen('--job-number='));
    }
}

$itemsQuery = MaterialIssueItem::query()
    ->with(['product', 'materialIssue'])
    ->whereNull('material_issue_items.deleted_at')
    ->where('material_issue_items.notes', 'like', '%Aroma:%')
    ->where('material_issue_items.notes', 'not like', '%RentalDetailID:%')
    ->whereHas('materialIssue', function ($query) use ($includeIssued) {
        if (!$includeIssued) {
            $query->whereIn('status', ['pending', 'draft', 'approved']);
        }
    })
    ->whereHas('materialIssue.jobAssignMaterialIssues.jobAssignSchedule.jobSchedule', function ($query) use ($jobNumber) {
        $query->where(function ($typeQuery) {
            $typeQuery->whereIn(DB::raw('LOWER(job_schedules.type)'), ['install_free', 'install free'])
                ->orWhereHas('jobAdvice', function ($jaQuery) {
                    $jaQuery->whereIn(DB::raw('LOWER(job_advices.type)'), ['install_free', 'install free']);
                });
        });

        if ($jobNumber) {
            $query->where('job_schedules.job_number', $jobNumber);
        }
    })
    ->orderBy('material_issue_items.material_issue_id')
    ->orderBy('material_issue_items.id');

$items = $itemsQuery->get();

echo ($dryRun ? '[DRY-RUN] ' : '[APPLY] ') . "Found {$items->count()} appended install-free material item(s) to remove.\n";

foreach ($items as $item) {
    $issueNumber = $item->materialIssue->issue_number ?? '-';
    $productName = $item->product->name ?? '-';

    echo "- MI {$issueNumber} item_id={$item->id} product={$productName} qty={$item->quantity} room={$item->room_name} notes=\"{$item->notes}\"\n";
}

if ($dryRun || $items->isEmpty()) {
    echo $dryRun
        ? "Dry-run selesai. Jalankan tanpa --dry-run untuk apply.\n"
        : "Tidak ada data yang perlu diubah.\n";
    exit(0);
}

DB::transaction(function () use ($items) {
    $materialIssueIds = $items->pluck('material_issue_id')->unique()->values();

    MaterialIssueItem::whereIn('id', $items->pluck('id'))->delete();

    foreach ($materialIssueIds as $materialIssueId) {
        $totalQuantity = MaterialIssueItem::where('material_issue_id', $materialIssueId)
            ->whereNull('deleted_at')
            ->sum('quantity');

        MaterialIssue::where('id', $materialIssueId)->update([
            'quantity' => $totalQuantity,
            'updated_by' => 1,
            'updated_at' => now(),
        ]);
    }
});

echo "Apply selesai.\n";


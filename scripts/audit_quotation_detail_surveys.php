<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Quotation;
use App\Models\SurveyDetail;

$quotationNumber = null;
$apply = in_array('--apply', $argv, true);
$all = in_array('--all', $argv, true);

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--quotation=')) {
        $quotationNumber = strtoupper(trim(substr($arg, 12)));
    }
}

if (!$quotationNumber && !$all) {
    echo "Usage: php scripts/audit_quotation_detail_surveys.php --quotation=JKT-SQ/26-04/0020\n";
    echo "Repair: php scripts/audit_quotation_detail_surveys.php --quotation=JKT-SQ/26-04/0020 --apply\n";
    echo "Global audit: php scripts/audit_quotation_detail_surveys.php --all\n";
    echo "Global repair: php scripts/audit_quotation_detail_surveys.php --all --apply\n";
    exit(1);
}

$query = Quotation::with([
    'quotationDetails.survey',
    'quotationDetails.room.survey',
    'quotationDetails.masterRental',
    'quotationSurveys.survey',
]);

if ($quotationNumber) {
    $query->whereRaw('UPPER(quotation_number) = ?', [$quotationNumber]);
}

$quotations = $query->get();

if ($quotations->isEmpty()) {
    echo $quotationNumber ? "Quotation {$quotationNumber} tidak ditemukan.\n" : "Tidak ada quotation.\n";
    exit(1);
}

$totalMissing = 0;
$totalSafe = 0;
$totalSkipped = 0;
$totalUpdated = 0;

foreach ($quotations as $quotation) {
    $candidateSurveyIds = $quotation->quotationSurveys
        ->pluck('survey_id')
        ->filter()
        ->when($quotation->survey_id, fn ($ids) => $ids->push($quotation->survey_id))
        ->unique()
        ->values();

    $missingDetails = $quotation->quotationDetails->filter(function ($detail) {
        return !$detail->survey_id || !$detail->room_id;
    });

    if ($missingDetails->isEmpty()) {
        if (!$all) {
            echo "Quotation {$quotation->quotation_number}: semua detail sudah punya survey link.\n";
        }
        continue;
    }

    $totalMissing += $missingDetails->count();

    echo "\nQuotation {$quotation->quotation_number}\n";
    echo "Global surveys: ";
    echo $quotation->quotationSurveys
        ->map(fn ($qs) => $qs->survey?->survey_number)
        ->filter()
        ->unique()
        ->implode(', ') ?: '-';
    echo "\n";
    echo ($apply ? '[APPLY]' : '[DRY RUN]') . " Repair detail tanpa survey link:\n";

    foreach ($missingDetails as $detail) {
        $roomName = trim((string) $detail->room_name);
        $matches = SurveyDetail::with('survey')
            ->whereIn('survey_id', $candidateSurveyIds)
            ->whereRaw('LOWER(TRIM(room_name)) = ?', [mb_strtolower($roomName)])
            ->get();

        if ($matches->count() !== 1) {
            $totalSkipped++;
            echo "- Detail {$detail->id} {$roomName}: skip, match survey detail {$matches->count()} data.\n";
            continue;
        }

        $match = $matches->first();
        $totalSafe++;
        echo "- Detail {$detail->id} {$roomName}: set survey {$match->survey?->survey_number}, survey_detail_id {$match->id}\n";

        if ($apply) {
            $detail->update([
                'survey_id' => $match->survey_id,
                'room_id' => $match->id,
                'updated_at' => now(),
            ]);
            $totalUpdated++;
        }
    }
}

echo "\nSummary:\n";
echo "- Missing detail links: {$totalMissing}\n";
echo "- Safe matches: {$totalSafe}\n";
echo "- Skipped ambiguous/not found: {$totalSkipped}\n";
echo "- Updated: {$totalUpdated}\n";

if (!$apply) {
    echo "Belum mengubah data. Tambahkan --apply untuk eksekusi.\n";
}

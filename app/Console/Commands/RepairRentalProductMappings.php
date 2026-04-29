<?php

namespace App\Console\Commands;

use App\Models\InventoryIssuing;
use App\Models\InventoryIssuingItem;
use App\Models\MaterialIssue;
use App\Models\MaterialIssueItem;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RepairRentalProductMappings extends Command
{
    protected $signature = 'materials:repair-rental-product-mapping
                            {--material-issue=* : Specific material issue number(s)}
                            {--issuing=* : Specific inventory issuing number(s)}
                            {--job-number=* : Specific job number(s)}
                            {--apply : Apply repair (default is dry-run)}';

    protected $description = 'Audit and repair material issue / inventory issuing item mappings generated from rental details';

    public function handle()
    {
        $apply = (bool) $this->option('apply');
        $materialIssueNumbers = collect((array) $this->option('material-issue'))->filter()->unique()->values();
        $issuingNumbers = collect((array) $this->option('issuing'))->filter()->unique()->values();
        $jobNumbers = collect((array) $this->option('job-number'))->filter()->unique()->values();

        if ($materialIssueNumbers->isEmpty() && $issuingNumbers->isEmpty() && $jobNumbers->isEmpty()) {
            $this->error('Provide at least one filter: --material-issue, --issuing, or --job-number.');
            return self::FAILURE;
        }

        if (!$apply) {
            $this->info('DRY RUN mode active. No database changes will be made.');
            $this->newLine();
        }

        $materialIssues = $this->loadMaterialIssues($materialIssueNumbers, $issuingNumbers, $jobNumbers);
        if ($materialIssues->isEmpty()) {
            $this->warn('No material issues matched the provided filters.');
            return self::SUCCESS;
        }

        $totals = [
            'checked' => 0,
            'planned_updates' => 0,
            'applied_updates' => 0,
            'warnings' => 0,
        ];

        foreach ($materialIssues as $materialIssue) {
            $analysis = $this->analyzeMaterialIssue($materialIssue);
            $totals['checked']++;
            $totals['planned_updates'] += count($analysis['updates']);
            $totals['warnings'] += count($analysis['warnings']);

            $this->line(sprintf(
                'MI %s [%s]%s',
                $materialIssue->issue_number,
                $materialIssue->status,
                $analysis['issuing']
                    ? sprintf(' | WI %s [%s]', $analysis['issuing']->issuing_number, $analysis['issuing']->status)
                    : ''
            ));

            foreach ($analysis['updates'] as $update) {
                $this->line(sprintf(
                    '  [PLAN] %s #%d | %s',
                    $update['label'],
                    $update['model']->id,
                    $update['summary']
                ));
            }

            foreach ($analysis['warnings'] as $warning) {
                $this->line('  [WARN] ' . $warning);
            }

            if ($apply && !empty($analysis['updates'])) {
                DB::transaction(function () use ($analysis, &$totals) {
                    foreach ($analysis['updates'] as $update) {
                        $changes = $update['changes'];
                        $auditUserId = $this->resolveAuditUserId($update['model']);
                        if ($auditUserId) {
                            $changes['updated_by'] = $auditUserId;
                        }
                        $update['model']->update($changes);
                        $totals['applied_updates']++;
                    }
                });
            }

            $this->newLine();
        }

        $this->info('Summary');
        $this->line('Material issues checked: ' . $totals['checked']);
        $this->line('Planned updates: ' . $totals['planned_updates']);
        $this->line('Applied updates: ' . ($apply ? $totals['applied_updates'] : 'dry-run'));
        $this->line('Warnings: ' . $totals['warnings']);

        return self::SUCCESS;
    }

    private function loadMaterialIssues(Collection $materialIssueNumbers, Collection $issuingNumbers, Collection $jobNumbers): Collection
    {
        if ($issuingNumbers->isNotEmpty()) {
            $referenceNumbers = InventoryIssuing::query()
                ->whereIn('issuing_number', $issuingNumbers->all())
                ->pluck('reference_no')
                ->filter()
                ->values();

            $materialIssueNumbers = $materialIssueNumbers
                ->merge($referenceNumbers)
                ->filter()
                ->unique()
                ->values();
        }

        $query = MaterialIssue::query()->with([
            'items.product.productType',
            'jobAssignMaterialIssues.jobAssignSchedule.jobSchedule',
            'jobAssignMaterialIssues.jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.masterProduct.productType',
            'jobAssignMaterialIssues.jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.masterProduct.productCategory',
            'jobAssignMaterialIssues.jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.productType',
            'jobAssignMaterialIssues.jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.productCategory',
            'jobAssignMaterialIssues.jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.allowedProducts.productType',
            'jobAssignMaterialIssues.jobAssignSchedule.jobSchedule.jobAdvice.rooms.rentalProduct.rentalDetails.allowedProducts.productCategory',
        ]);

        if ($materialIssueNumbers->isNotEmpty()) {
            $query->whereIn('issue_number', $materialIssueNumbers->all());
        }

        if ($jobNumbers->isNotEmpty()) {
            $query->whereHas('jobAssignMaterialIssues.jobAssignSchedule.jobSchedule', function ($subQuery) use ($jobNumbers) {
                $subQuery->whereIn('job_number', $jobNumbers->all());
            });
        }

        return $query->orderBy('issue_number')->get();
    }

    private function analyzeMaterialIssue(MaterialIssue $materialIssue): array
    {
        $issuing = InventoryIssuing::with(['items.product', 'items.serialNumber.masterProduct'])
            ->where('reference_no', $materialIssue->issue_number)
            ->first();

        $jobAdvice = $materialIssue->jobAssignMaterialIssues->first()?->jobAssignSchedule?->jobSchedule?->jobAdvice;
        if (!$jobAdvice) {
            return [
                'issuing' => $issuing,
                'updates' => [],
                'warnings' => ['Job advice not found. Nothing to repair automatically.'],
            ];
        }

        $jobSchedule = $materialIssue->jobAssignMaterialIssues->first()?->jobAssignSchedule?->jobSchedule;
        $expectedItems = $this->buildExpectedItems($jobAdvice->rooms ?? collect(), $jobSchedule, $jobAdvice);
        $updates = [];
        $warnings = [];
        $usedMaterialIssueItemIds = [];
        $usedIssuingItemIds = [];

        foreach ($expectedItems as $expected) {
            $materialIssueItem = $this->findMatchingMaterialIssueItem($materialIssue->items, $expected, $usedMaterialIssueItemIds);
            if (!$materialIssueItem) {
                $warnings[] = sprintf(
                    'Expected MI item missing for room %s / rental detail %d (%s).',
                    $expected['room_name'],
                    $expected['detail_id'],
                    $expected['product_name']
                );
                continue;
            }

            $usedMaterialIssueItemIds[] = $materialIssueItem->id;

            $materialIssueChanges = $this->buildMaterialIssueItemChanges($materialIssueItem, $expected);
            if (!empty($materialIssueChanges)) {
                if ($this->canUpdateMaterialIssueProduct($materialIssue, $issuing, $materialIssueItem, $expected)) {
                    $updates[] = [
                        'label' => 'material_issue_items',
                        'model' => $materialIssueItem,
                        'changes' => $materialIssueChanges,
                        'summary' => $this->formatChangeSummary($materialIssueItem, $materialIssueChanges, $expected),
                    ];
                } else {
                    $warnings[] = sprintf(
                        'Skipped MI item #%d for room %s because issuing already advanced to %s and product swap needs rollback first.',
                        $materialIssueItem->id,
                        $expected['room_name'],
                        $issuing?->status ?? 'no issuing'
                    );

                    if (array_key_exists('room_name', $materialIssueChanges)) {
                        $updates[] = [
                            'label' => 'material_issue_items',
                            'model' => $materialIssueItem,
                            'changes' => ['room_name' => $materialIssueChanges['room_name']],
                            'summary' => $this->formatChangeSummary($materialIssueItem, ['room_name' => $materialIssueChanges['room_name']], $expected),
                        ];
                    }
                }
            }

            if (!$issuing) {
                continue;
            }

            $issuingItem = $this->findMatchingIssuingItem($issuing->items, $expected, $usedIssuingItemIds);
            if (!$issuingItem) {
                $warnings[] = sprintf(
                    'Expected WI item missing for room %s (%s).',
                    $expected['room_name'],
                    $expected['product_name']
                );
                continue;
            }

            $usedIssuingItemIds[] = $issuingItem->id;

            $issuingChanges = $this->buildIssuingItemChanges($issuing, $issuingItem, $expected);
            if (!empty($issuingChanges)) {
                if ($this->canUpdateIssuingItem($issuing, $issuingItem, $issuingChanges, $expected)) {
                    $updates[] = [
                        'label' => 'inventory_issuing_items',
                        'model' => $issuingItem,
                        'changes' => $issuingChanges,
                        'summary' => $this->formatChangeSummary($issuingItem, $issuingChanges, $expected),
                    ];
                } else {
                    $warnings[] = sprintf(
                        'Skipped WI item #%d for room %s because status %s / serial %s requires rollback before product correction.',
                        $issuingItem->id,
                        $expected['room_name'],
                        $issuing->status,
                        $issuingItem->serialNumber?->serial_number ?? '-'
                    );

                    // Room metadata can still be normalized even when product swap is blocked.
                    if (array_key_exists('room_name', $issuingChanges)) {
                        $updates[] = [
                            'label' => 'inventory_issuing_items',
                            'model' => $issuingItem,
                            'changes' => ['room_name' => $issuingChanges['room_name']],
                            'summary' => $this->formatChangeSummary($issuingItem, ['room_name' => $issuingChanges['room_name']], $expected),
                        ];
                    }
                }
            }
        }

        return [
            'issuing' => $issuing,
            'updates' => $updates,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function buildExpectedItems($rooms, $jobSchedule = null, $jobAdvice = null): Collection
    {
        $expectedItems = collect();
        $jobType = strtolower(trim((string) ($jobSchedule?->type ?? '')));
        $jobAdviceType = strtolower(trim((string) ($jobAdvice?->type ?? '')));
        $isInstallFree = in_array($jobAdviceType, ['install_free', 'install free'], true);
        $isChangeRental = str_contains($jobAdviceType, 'change');
        $needUnits = ($jobType === 'install' && !$isInstallFree);
        $needNonUnits = in_array($jobType, ['service', 'servis'], true);
        $filterByUnitType = ($needUnits || $needNonUnits) && !$isChangeRental;

        foreach ($rooms as $jobAdviceRoom) {
            $rental = $jobAdviceRoom->rentalProduct;
            if (!$rental) {
                continue;
            }

            foreach ($rental->rentalDetails as $detail) {
                $product = $this->resolvePreferredRentalDetailProduct($detail, $rental, $detail->masterProduct);
                $quantity = (float) ($detail->quantity ?? 0);

                if (!$product || $quantity <= 0) {
                    continue;
                }

                if ($filterByUnitType) {
                    $isUnit = $this->isUnitProduct($product, $detail);
                    if ($needUnits && !$isUnit) {
                        continue;
                    }
                    if ($needNonUnits && $isUnit) {
                        continue;
                    }
                }

                $expectedItems->push([
                    'detail_id' => $detail->id,
                    'room_name' => $jobAdviceRoom->room_name,
                    'rental_name' => $rental->rental_name,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'convert' => (float) ($detail->convert ?? 1),
                    'bom_quantity' => (float) ($product->bom_quantity ?? 0),
                    'unit_price' => (float) ($product->last_unit_price ?? 0),
                    'total_price' => (float) (($product->last_unit_price ?? 0) * $quantity),
                    'material_issue_notes' => sprintf(
                        'Room: %s, Rental: %s, RentalDetailID: %d',
                        $jobAdviceRoom->room_name,
                        $rental->rental_name,
                        $detail->id
                    ),
                    'issuing_notes' => sprintf(
                        'Room: %s, Product: %s',
                        $jobAdviceRoom->room_name,
                        $product->name
                    ),
                ]);
            }
        }

        return $expectedItems->values();
    }

    private function findMatchingMaterialIssueItem(Collection $items, array $expected, array $usedIds): ?MaterialIssueItem
    {
        $availableItems = $items->filter(fn ($item) => !in_array($item->id, $usedIds, true))->values();

        $byDetailId = $availableItems
            ->first(function ($item) use ($expected) {
                return $this->extractRentalDetailIdFromNotes($item->notes) === $expected['detail_id'];
            });

        if ($byDetailId) {
            return $byDetailId;
        }

        $roomAndProduct = $availableItems
            ->first(function ($item) use ($expected) {
                return trim(strtolower((string) $item->room_name)) === trim(strtolower($expected['room_name']))
                    && (int) $item->product_id === (int) $expected['product_id'];
            });

        if ($roomAndProduct) {
            return $roomAndProduct;
        }

        $hasDetailIdentifiers = $availableItems->contains(fn ($item) => $this->extractRentalDetailIdFromNotes($item->notes) !== null);
        if ($hasDetailIdentifiers) {
            return null;
        }

        return $availableItems->first(function ($item) use ($expected) {
            return trim(strtolower((string) $item->room_name)) === trim(strtolower($expected['room_name']));
        });
    }

    private function findMatchingIssuingItem(Collection $items, array $expected, array $usedIds): ?InventoryIssuingItem
    {
        $matched = $items
            ->filter(fn ($item) => !in_array($item->id, $usedIds, true))
            ->first(function ($item) use ($expected) {
                $noteRoom = $this->extractRoomNameFromNotes($item->notes);
                return trim(strtolower((string) $noteRoom)) === trim(strtolower($expected['room_name']));
            });

        if ($matched) {
            return $matched;
        }

        return $items
            ->filter(fn ($item) => !in_array($item->id, $usedIds, true))
            ->first(function ($item) use ($expected) {
                return trim(strtolower((string) $item->room_name)) === trim(strtolower($expected['room_name']));
            });
    }

    private function buildMaterialIssueItemChanges(MaterialIssueItem $item, array $expected): array
    {
        $changes = [];

        if ((int) $item->product_id !== (int) $expected['product_id']) {
            $changes['product_id'] = $expected['product_id'];
            $changes['bom_quantity'] = $expected['bom_quantity'];
            $changes['unit_price'] = $expected['unit_price'];
            $changes['total_price'] = $expected['total_price'];
            $changes['notes'] = $expected['material_issue_notes'];
        }

        if (($item->room_name ?? null) !== $expected['room_name']) {
            $changes['room_name'] = $expected['room_name'];
        }

        return $changes;
    }

    private function buildIssuingItemChanges(InventoryIssuing $issuing, InventoryIssuingItem $item, array $expected): array
    {
        $changes = [];

        if (($item->room_name ?? null) !== $expected['room_name']) {
            $changes['room_name'] = $expected['room_name'];
        }

        if ((int) $item->product_id !== (int) $expected['product_id']) {
            $changes['product_id'] = $expected['product_id'];
            $changes['unit_price'] = $expected['unit_price'];
            $changes['total_price'] = $expected['total_price'];
            $changes['notes'] = $expected['issuing_notes'];
        } elseif (($this->extractRoomNameFromNotes($item->notes) ?: $item->room_name) !== $expected['room_name']) {
            $changes['notes'] = $expected['issuing_notes'];
        }

        return $changes;
    }

    private function canUpdateMaterialIssueProduct(MaterialIssue $materialIssue, ?InventoryIssuing $issuing, MaterialIssueItem $item, array $expected): bool
    {
        if ((int) $item->product_id === (int) $expected['product_id']) {
            return true;
        }

        if (!$issuing) {
            return in_array($materialIssue->status, ['pending', 'approved'], true);
        }

        return $this->issuingAllowsProductSwap($issuing);
    }

    private function canUpdateIssuingItem(InventoryIssuing $issuing, InventoryIssuingItem $item, array $changes, array $expected): bool
    {
        if (!array_key_exists('product_id', $changes)) {
            return true;
        }

        if (!$this->issuingAllowsProductSwap($issuing)) {
            return false;
        }

        if ($item->serial_number_id) {
            return false;
        }

        return (float) $item->quantity_received <= 0;
    }

    private function issuingAllowsProductSwap(InventoryIssuing $issuing): bool
    {
        return in_array($issuing->status, ['pending', 'processed'], true);
    }

    private function formatChangeSummary($model, array $changes, array $expected): string
    {
        $parts = [];

        if (array_key_exists('room_name', $changes)) {
            $parts[] = sprintf('room `%s` -> `%s`', $model->room_name ?? '-', $changes['room_name']);
        }

        if (array_key_exists('product_id', $changes)) {
            $currentProductName = $model->product?->name ?? ('Product #' . $model->product_id);
            $parts[] = sprintf('product `%s` -> `%s`', $currentProductName, $expected['product_name']);
        }

        if (array_key_exists('notes', $changes) && !array_key_exists('product_id', $changes)) {
            $parts[] = 'notes sync';
        }

        return implode(', ', $parts);
    }

    private function extractRoomNameFromNotes(?string $notes): ?string
    {
        if (!$notes) {
            return null;
        }

        if (preg_match('/Room:\s*([^,]+)/i', $notes, $matches)) {
            $roomName = trim($matches[1]);
            return $roomName !== '' ? $roomName : null;
        }

        return null;
    }

    private function extractRentalDetailIdFromNotes(?string $notes): ?int
    {
        if (!$notes) {
            return null;
        }

        if (preg_match('/(?:RentalDetailID|ComponentID):\s*(\d+)/i', $notes, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractRentalModelTokens(?string $value): array
    {
        if (!$value) {
            return [];
        }

        preg_match_all('/[A-Z]+\s*-?\d+[A-Z0-9-]*/i', strtoupper($value), $matches);

        return collect($matches[0] ?? [])
            ->map(fn ($token) => preg_replace('/[^A-Z0-9]/', '', $token))
            ->filter(fn ($token) => preg_match('/[A-Z]/', $token) && preg_match('/\d/', $token))
            ->unique()
            ->values()
            ->all();
    }

    private function resolvePreferredRentalDetailProduct($detail, $rental, $fallbackProduct = null)
    {
        $selectedProducts = $detail->allowedProducts
            ? $detail->allowedProducts->where('pivot.is_selected', true)->values()
            : collect();

        if ($selectedProducts->isEmpty()) {
            return $fallbackProduct;
        }

        $tokens = array_values(array_unique(array_merge(
            $this->extractRentalModelTokens($rental->rental_name ?? null),
            $this->extractRentalModelTokens($rental->rental_code ?? null)
        )));

        $scoredCandidates = $selectedProducts->map(function ($candidate) use ($tokens, $detail, $fallbackProduct) {
            $haystack = strtoupper(implode(' ', array_filter([
                $candidate->name ?? null,
                $candidate->sku ?? null,
                $candidate->variant_name ?? null,
            ])));
            $normalizedHaystack = preg_replace('/[^A-Z0-9]/', '', $haystack);

            $score = 0;
            foreach ($tokens as $token) {
                if ($token && str_contains($normalizedHaystack, $token)) {
                    $score += 100;
                }
            }

            if ($fallbackProduct && $candidate->id === $fallbackProduct->id) {
                $score += 25;
            }

            if ($detail->product_type_id && $candidate->product_type_id === $detail->product_type_id) {
                $score += 10;
            }

            if ($detail->product_category_id && $candidate->product_category_id === $detail->product_category_id) {
                $score += 5;
            }

            return [
                'product' => $candidate,
                'score' => $score,
                'sort_order' => $candidate->pivot->sort_order ?? 9999,
            ];
        })->sortBy([
            ['score', 'desc'],
            ['sort_order', 'asc'],
        ])->values();

        $bestCandidate = $scoredCandidates->first();
        if (!$bestCandidate) {
            return $fallbackProduct;
        }

        if (($bestCandidate['score'] ?? 0) > 0) {
            return $bestCandidate['product'];
        }

        if ($fallbackProduct && $selectedProducts->contains('id', $fallbackProduct->id)) {
            return $fallbackProduct;
        }

        return $selectedProducts->sortBy(fn ($product) => $product->pivot->sort_order ?? 9999)->first();
    }

    private function isUnitProduct($product, $detail = null): bool
    {
        if ($product && $product->productCategory && $product->productCategory->is_unit !== null) {
            return (bool) $product->productCategory->is_unit;
        }

        if ($product && $product->productType && $product->productType->is_unit !== null) {
            return (bool) $product->productType->is_unit;
        }

        if ($detail && $detail->productCategory && $detail->productCategory->is_unit !== null) {
            return (bool) $detail->productCategory->is_unit;
        }

        if ($detail && $detail->productType && $detail->productType->is_unit !== null) {
            return (bool) $detail->productType->is_unit;
        }

        return false;
    }

    private function resolveAuditUserId($model): ?int
    {
        foreach (['updated_by', 'created_by'] as $column) {
            $value = $model->{$column} ?? null;
            if ($value && User::whereKey($value)->exists()) {
                return (int) $value;
            }
        }

        $fallbackUserId = User::query()->orderBy('id')->value('id');
        return $fallbackUserId ? (int) $fallbackUserId : null;
    }
}

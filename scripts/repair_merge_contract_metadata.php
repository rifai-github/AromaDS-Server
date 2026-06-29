<?php

use App\Models\Contract;
use App\Models\ContractRoom;
use App\Models\Finance\BillingGroup;
use App\Services\ContractMergeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apply = in_array('--apply', $argv, true);
$dryRun = !$apply;
$contractNumber = null;

foreach ($argv as $index => $arg) {
    if ($arg === '--contract' && isset($argv[$index + 1])) {
        $contractNumber = $argv[$index + 1];
    } elseif (str_starts_with($arg, '--contract=')) {
        $contractNumber = substr($arg, strlen('--contract='));
    }
}

$query = Contract::with([
    'mergedSources.contractRooms',
    'mergedSources.contractRentals',
    'mergedSources.billingGroups.billingGroupBuildings',
    'contractRooms',
    'billingGroups',
])
    ->where(function ($q) {
        $q->where('contract_type', 'merge')
            ->orWhereNotNull('merged_from_ids')
            ->orWhereHas('mergedSources');
    });

if ($contractNumber) {
    $query->where('contract_number', $contractNumber);
}

$contracts = $query->orderBy('id')->get();

echo ($dryRun ? '[DRY-RUN]' : '[APPLY]') . " Found {$contracts->count()} merge contract(s) to inspect.\n";

$service = app(ContractMergeService::class);
$actorId = DB::table('users')->orderBy('id')->value('id');
Auth::loginUsingId($actorId);

foreach ($contracts as $contract) {
    $sourceContracts = $contract->mergedSources->values()->all();

    if (empty($sourceContracts)) {
        echo "- {$contract->contract_number}: skipped, no source contracts in contract_merges.\n";
        continue;
    }

    $needsMetadata = $contract->term_of_payment === null
        || $contract->ppn_code === null
        || $contract->install_date === null
        || $contract->first_service_date === null
        || $contract->customer_signing_1_id === null
        || $contract->internal_signing_id === null;

    $needsBillingGroups = !$contract->billingGroups()->exists()
        && collect($sourceContracts)->contains(fn ($source) => $source->billingGroups->isNotEmpty());

    $roomsNeedingBilling = $contract->contractRooms()
        ->whereNull('billing_group_id')
        ->whereNotNull('source_contract_room_id')
        ->count();

    echo "- {$contract->contract_number}: sources="
        . collect($sourceContracts)->pluck('contract_number')->implode(', ')
        . "; metadata=" . ($needsMetadata ? 'repair' : 'ok')
        . "; billing_groups=" . ($needsBillingGroups ? 'copy' : 'ok')
        . "; room_billing_null={$roomsNeedingBilling}\n";

    if ($dryRun) {
        continue;
    }

    DB::transaction(function () use ($service, $contract, $sourceContracts) {
        $service->syncMergeContractMetadata($contract, $sourceContracts);

        $billingGroupMap = [];
        if (!$contract->billingGroups()->exists()) {
            $billingGroupMap = $service->copyBillingGroups($contract, $sourceContracts);
        } else {
            $billingGroupMap = resolveExistingBillingGroupMap($contract, $sourceContracts);
        }

        remapContractRoomBillingGroups($contract, $billingGroupMap);
    });
}

echo $dryRun
    ? "Dry-run complete. Add --apply to write repairs.\n"
    : "Apply complete.\n";

function resolveExistingBillingGroupMap(Contract $contract, array $sourceContracts): array
{
    $map = [];
    $targetGroups = $contract->billingGroups()->get();

    foreach ($sourceContracts as $sourceContract) {
        foreach ($sourceContract->billingGroups as $sourceGroup) {
            $targetGroup = $targetGroups
                ->first(function ($group) use ($sourceGroup) {
                    return $group->billing_group_name === $sourceGroup->billing_group_name
                        && (float) $group->billing_amount === (float) $sourceGroup->billing_amount
                        && $group->pic_email === $sourceGroup->pic_email;
                });

            if ($targetGroup) {
                $map[$sourceGroup->id] = $targetGroup->id;
            }
        }
    }

    return $map;
}

function remapContractRoomBillingGroups(Contract $contract, array $billingGroupMap): void
{
    if (empty($billingGroupMap)) {
        return;
    }

    $contract->contractRooms()
        ->whereNull('billing_group_id')
        ->whereNotNull('source_contract_room_id')
        ->chunkById(100, function ($rooms) use ($billingGroupMap) {
            $sourceRoomIds = $rooms->pluck('source_contract_room_id')->filter()->unique();
            $sourceRooms = ContractRoom::whereIn('id', $sourceRoomIds)
                ->get(['id', 'billing_group_id'])
                ->keyBy('id');

            foreach ($rooms as $room) {
                $sourceBillingGroupId = $sourceRooms->get($room->source_contract_room_id)?->billing_group_id;
                $targetBillingGroupId = $billingGroupMap[$sourceBillingGroupId] ?? null;

                if ($targetBillingGroupId) {
                    $room->update([
                        'billing_group_id' => $targetBillingGroupId,
                        'updated_by' => Auth::id(),
                    ]);
                }
            }
        });
}

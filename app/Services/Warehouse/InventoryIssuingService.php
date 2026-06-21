<?php

namespace App\Services\Warehouse;

use App\Models\InventoryIssuing;
use App\Models\InventoryMovement;
use App\Models\JobSchedule;
use App\Models\JobAssignSchedule;
use App\Models\JobScheduleRoom;
use App\Models\JobScheduleRoomAssignment;
use App\Models\MaterialIssue;
use App\Models\MaterialIssueItem;
use App\Models\MasterProduct;
use App\Models\SerialNumber;
use App\Models\WarehouseProduct;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class InventoryIssuingService
{
    /**
     * Finalize an inventory issuing: change status to sent, update stock and SN status.
     * 
     * @param InventoryIssuing|int $issuing
     * @return bool
     * @throws Exception
     */
    public function finalize($issuing)
    {
        if (is_numeric($issuing)) {
            $issuing = InventoryIssuing::findOrFail($issuing);
        }

        if ($issuing->status === 'sent' || $issuing->status === 'received') {
            return true; // Already finalized
        }

        if ($issuing->status !== 'processed') {
            throw new Exception("Hanya bisa finalize dari status Ready (processed).");
        }

        DB::beginTransaction();
        try {
            // Ensure stock was posted when the issuing became Ready. This is mainly
            // for older processed records that were created before the Ready-step
            // stock posting was centralized here.
            $this->postReadyStockIfMissing($issuing);

            // 1. Update status to sent (Finish)
            $issuing->update([
                'status' => 'sent',
                'updated_by' => Auth::id() ?? 1,
            ]);

            // 2. Update Serial Number Lifecycle
            $this->updateSerialNumberLifecycle($issuing);

            // 3. Sync Job Schedule Status if related
            $this->syncJobScheduleStatus($issuing);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function postReadyStockIfMissing(InventoryIssuing $issuing): bool
    {
        $issuing->loadMissing(['items.product', 'warehouse', 'branch']);

        if ($this->hasInventoryIssuingStockMovement($issuing)) {
            return false;
        }

        if ($this->hasLegacyMaterialIssueStockMovement($issuing)) {
            $this->convertLegacyMaterialIssueMovements($issuing);
            return false;
        }

        $this->createInventoryMovements($issuing);
        return true;
    }

    public function rollbackPostedStock(InventoryIssuing $issuing): int
    {
        $legacyMovements = $this->legacyMaterialIssueStockMovements($issuing)->get();
        if ($legacyMovements->isNotEmpty()) {
            $rolledBack = $this->rollbackMovements($legacyMovements);

            $this->inventoryIssuingStockMovements($issuing)->delete();

            return $rolledBack;
        }

        return $this->rollbackMovements($this->inventoryIssuingStockMovements($issuing)->get());
    }

    public function rollbackMaterialIssueStock(MaterialIssue $materialIssue): int
    {
        $movements = InventoryMovement::where('reference_no', $materialIssue->issue_number)
            ->where('reference_type', 'material_issue')
            ->where('movement_type', 'out')
            ->get();

        return $this->rollbackMovements($movements);
    }

    public function reopenMaterialIssuesForPendingIssuingDeletion(InventoryIssuing $issuing): Collection
    {
        $materialIssues = $this->resolveMaterialIssuesForPendingIssuingDeletion($issuing);

        foreach ($materialIssues as $materialIssue) {
            $materialIssue->update([
                'status' => 'approved',
                'updated_by' => Auth::id(),
            ]);
        }

        return $materialIssues;
    }

    public function resolveMaterialIssuesForPendingIssuingDeletion(InventoryIssuing $issuing): Collection
    {
        if (!empty($issuing->reference_no)) {
            $byReference = MaterialIssue::where('issue_number', $issuing->reference_no)->get();
            if ($byReference->isNotEmpty()) {
                return $byReference;
            }
        }

        $jobAssignScheduleIds = $issuing->items()
            ->whereNotNull('job_assign_schedule_id')
            ->pluck('job_assign_schedule_id')
            ->unique()
            ->values();

        if ($jobAssignScheduleIds->isEmpty()) {
            return collect();
        }

        return MaterialIssue::where('status', 'issued')
            ->whereHas('jobAssignMaterialIssues', function ($query) use ($jobAssignScheduleIds) {
                $query->whereIn('job_assign_schedule_id', $jobAssignScheduleIds->all());
            })
            ->get()
            ->filter(function (MaterialIssue $materialIssue) use ($issuing) {
                return !InventoryIssuing::where('id', '!=', $issuing->id)
                    ->where('reference_no', $materialIssue->issue_number)
                    ->whereIn('status', ['pending', 'processed', 'sent', 'received'])
                    ->exists();
            })
            ->values();
    }

    /**
     * Update SN status to 'on_hand' for technician.
     */
    protected function updateSerialNumberLifecycle(InventoryIssuing $issuing)
    {
        $updated = $this->moveSerialNumbersToTechnician($issuing, (int) $issuing->received_by, Auth::id() ?? 1);

        if ($updated > 0) {
            \Log::info("Serial Numbers updated to On Hand for Issuing ID: {$issuing->id}, Technician ID: {$issuing->received_by}");
        }
    }

    public function moveSerialNumbersToTechnician(InventoryIssuing $issuing, int $technicianId, ?int $actorId = null): int
    {
        $issuing->loadMissing(['items.product.productCategory', 'items.product.productType', 'items.serialNumber']);

        $serialNumberIds = $this->resolveSerialNumberIdsForItems(
            $issuing->items,
            ['ready', 'available', 'on_hand']
        );

        if (empty($serialNumberIds)) {
            return 0;
        }

        return SerialNumber::whereIn('id', $serialNumberIds)->update([
            'status' => 'on_hand',
            'location_type' => 'technician',
            'location_id' => $technicianId,
            'updated_by' => $actorId ?? Auth::id() ?? 1,
            'updated_at' => now(),
        ]);
    }

    public function moveSerialNumbersToCustomerForItems(Collection $items, ?int $customerId, ?int $actorId = null, ?string $reissuedToJobNumber = null): int
    {
        $items->load(['product.productCategory', 'product.productType', 'serialNumber']);

        $serialNumberIds = $this->resolveSerialNumberIdsForItems(
            $items,
            ['on_hand', 'ready', 'available', 'in_use']
        );

        if (empty($serialNumberIds)) {
            return 0;
        }

        if ($reissuedToJobNumber) {
            $this->appendReissueNote($serialNumberIds, $reissuedToJobNumber);
        }

        return SerialNumber::whereIn('id', $serialNumberIds)
            ->whereIn('status', ['on_hand', 'ready', 'available', 'in_use'])
            ->update([
                'status' => 'in_use',
                'location_type' => 'customer',
                'location_id' => $customerId,
                'updated_by' => $actorId ?? Auth::id(),
                'updated_at' => now(),
            ]);
    }

    private function appendReissueNote(array $serialNumberIds, string $jobNumber): void
    {
        $reissueNote = "Reissued to Job {$jobNumber} on " . now()->toDateString() . '.';

        SerialNumber::whereIn('id', $serialNumberIds)
            ->whereIn('status', ['on_hand', 'ready', 'available', 'in_use'])
            ->get(['id', 'notes'])
            ->each(function (SerialNumber $serialNumber) use ($reissueNote) {
                $existingNotes = trim((string) ($serialNumber->notes ?? ''));

                if (str_ends_with($existingNotes, $reissueNote)) {
                    return;
                }

                $serialNumber->notes = $existingNotes === '' ? $reissueNote : "{$existingNotes}\n{$reissueNote}";
                $serialNumber->save();
            });
    }

    private function resolveSerialNumberIdsForItems(Collection $items, array $allowedStatuses): array
    {
        $resolvedIds = [];

        foreach ($items as $item) {
            if (!$item->serial_number_id || !$item->serialNumber) {
                continue;
            }

            $quantity = $this->resolveSerialQuantity($item);
            $isUniqueSerial = $item->product?->requiresUniqueSerialNumber() ?? true;

            if ($isUniqueSerial || $quantity <= 1) {
                $resolvedIds[] = (int) $item->serial_number_id;

                continue;
            }

            $batchIds = $this->resolveBatchSerialNumberIdsForItem($item, $quantity, $allowedStatuses, $resolvedIds);

            $resolvedIds = array_merge($resolvedIds, $batchIds);
        }

        return array_values(array_unique($resolvedIds));
    }

    private function resolveBatchSerialNumberIdsForItem($item, int $quantity, array $allowedStatuses, array $excludedIds): array
    {
        $serialNumber = $item->serialNumber?->serial_number;
        if (!$serialNumber || !$item->product_id) {
            return [];
        }

        $candidateIds = SerialNumber::query()
            ->where('master_product_id', $item->product_id)
            ->where('serial_number', $serialNumber)
            ->whereIn('status', ['ready', 'available', 'on_hand', 'in_use'])
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($candidateIds)) {
            return [];
        }

        $offset = $this->resolveBatchSerialOffset($item, $serialNumber);
        $allocatedIds = array_slice($candidateIds, $offset, $quantity);

        $allowedIds = SerialNumber::query()
            ->whereIn('id', $allocatedIds)
            ->whereIn('status', $allowedStatuses)
            ->when(!empty($excludedIds), fn ($query) => $query->whereNotIn('id', $excludedIds))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $allowedIdSet = array_flip($allowedIds);

        return array_values(array_filter(
            $allocatedIds,
            fn ($id) => isset($allowedIdSet[(int) $id])
        ));
    }

    private function resolveBatchSerialOffset($item, string $serialNumber): int
    {
        if (!$item->id || !$item->product_id) {
            return 0;
        }

        $previousItems = \App\Models\InventoryIssuingItem::query()
            ->where('id', '<', $item->id)
            ->where('product_id', $item->product_id)
            ->whereNotNull('serial_number_id')
            ->whereHas('serialNumber', function ($query) use ($serialNumber) {
                $query->where('serial_number', $serialNumber);
            })
            ->whereHas('inventoryIssuing', function ($query) {
                $query->whereIn('status', ['pending', 'processed', 'sent', 'received']);
            })
            ->get();

        return $previousItems->sum(fn ($previousItem) => $this->resolveSerialQuantity($previousItem));
    }

    private function resolveSerialQuantity($item): int
    {
        foreach (['quantity_received', 'quantity_issued', 'quantity_requested'] as $field) {
            $quantity = (float) ($item->{$field} ?? 0);
            if ($quantity > 0) {
                return max(1, (int) ceil($quantity));
            }
        }

        return 1;
    }

    /**
     * Create Stock Out movements.
     */
    protected function createInventoryMovements(InventoryIssuing $issuing)
    {
        $issuing->load(['items.product', 'warehouse', 'branch']);

        foreach ($issuing->items as $item) {
            if (!$item->product_id || !$item->quantity_requested || $item->quantity_requested <= 0) {
                continue;
            }

            $warehouseProduct = WarehouseProduct::where('warehouse_id', $issuing->warehouse_id)
                ->where('master_product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if (!$warehouseProduct || $warehouseProduct->quantity < $item->quantity_requested) {
                $productName = $item->product->name ?? "Product ID: {$item->product_id}";
                $available = $warehouseProduct?->quantity ?? 0;
                throw new Exception("Stock {$productName} tidak cukup untuk Ready to Issue. Butuh {$item->quantity_requested}, tersedia {$available}.");
            }

            $warehouseProduct->decrement('quantity', $item->quantity_requested, [
                'updated_by' => Auth::id() ?? 1,
            ]);

            $issuingNumber = $issuing->issuing_number ?? "ISU-{$issuing->id}";
            $productName = $item->product->name ?? "Product ID: {$item->product_id}";
            $branchName = $issuing->branch->name ?? "Branch ID: {$issuing->branch_id}";

            InventoryMovement::create([
                'warehouse_id' => $issuing->warehouse_id,
                'master_product_id' => $item->product_id,
                'movement_type' => 'out',
                'quantity' => -abs($item->quantity_requested),
                'notes' => "Inventory issued. Issuing Number: {$issuingNumber}, Product: {$productName}, Branch: {$branchName}",
                'movement_date' => $issuing->issue_date ?? now()->toDateString(),
                'reference_no' => $issuingNumber,
                'reference_type' => 'inventory_issuing',
                'movement_no' => 'ISU-' . str_replace('ISU-', '', $issuingNumber),
                'created_by' => Auth::id() ?? 1,
                'updated_by' => Auth::id() ?? 1,
            ]);
        }
    }

    protected function rollbackMovements(Collection $movements): int
    {
        $rolledBack = 0;

        foreach ($movements as $movement) {
            $quantity = abs((float) $movement->quantity);
            if ($quantity <= 0) {
                $movement->delete();
                continue;
            }

            $rollbackMasterProduct = MasterProduct::find($movement->master_product_id);
            $warehouseProduct = WarehouseProduct::firstOrCreate(
                [
                    'warehouse_id' => $movement->warehouse_id,
                    'master_product_id' => $movement->master_product_id,
                ],
                [
                    'quantity' => 0,
                    'minimum_stock' => $rollbackMasterProduct->minimum_stock ?? 0,
                    'maximum_stock' => $rollbackMasterProduct->maximum_stock ?? 0,
                    'created_by' => Auth::id() ?? 1,
                    'updated_by' => Auth::id() ?? 1,
                ]
            );

            $warehouseProduct->increment('quantity', $quantity, [
                'updated_by' => Auth::id() ?? 1,
            ]);

            $movement->delete();
            $rolledBack++;
        }

        return $rolledBack;
    }

    protected function hasInventoryIssuingStockMovement(InventoryIssuing $issuing): bool
    {
        return $this->inventoryIssuingStockMovements($issuing)->exists();
    }

    protected function hasLegacyMaterialIssueStockMovement(InventoryIssuing $issuing): bool
    {
        return $this->legacyMaterialIssueStockMovements($issuing)->exists();
    }

    protected function inventoryIssuingStockMovements(InventoryIssuing $issuing)
    {
        return InventoryMovement::where('reference_no', $issuing->issuing_number)
            ->where('reference_type', 'inventory_issuing')
            ->where('movement_type', 'out');
    }

    protected function legacyMaterialIssueStockMovements(InventoryIssuing $issuing)
    {
        return InventoryMovement::where('reference_no', $issuing->reference_no)
            ->where('reference_type', 'material_issue')
            ->where('movement_type', 'out');
    }

    protected function convertLegacyMaterialIssueMovements(InventoryIssuing $issuing): int
    {
        $converted = 0;

        foreach ($this->legacyMaterialIssueStockMovements($issuing)->get() as $movement) {
            $movement->update([
                'reference_no' => $issuing->issuing_number,
                'reference_type' => 'inventory_issuing',
                'notes' => trim(($movement->notes ?: '') . ' [Converted from legacy material issue stock posting]'),
                'updated_by' => Auth::id() ?? 1,
            ]);
            $converted++;
        }

        return $converted;
    }

    /**
     * Sync status to Job Schedule.
     */
    public function syncJobScheduleStatus(InventoryIssuing $issuing)
    {
        // Find related JobSchedule via MaterialIssue
        $materialIssue = \App\Models\MaterialIssue::where('issue_number', $issuing->reference_no)->first();
        if (!$materialIssue) return;

        // Sync to ALL related assignments (for grouped rooms)
        $jamis = $materialIssue->jobAssignMaterialIssues()
            ->with(['jobAssignSchedule.jobSchedule'])
            ->get();
        
        foreach ($jamis as $jami) {
            $assignment = $jami->jobAssignSchedule;
            if (!$assignment) continue;

            $jobSchedule = $assignment->jobSchedule;
            if (!$jobSchedule) continue;

            // Update Team if present in Issuing
            if ($issuing->team_id) {
                // Update assignment team
                $assignment->update([
                    'team_id' => $issuing->team_id,
                    'updated_by' => Auth::id() ?? 1
                ]);

                $this->syncRoomAssignmentsForJobSchedule(
                    $jobSchedule,
                    (int) $issuing->team_id,
                    (int) $assignment->id,
                    $assignment->assigned_date?->toDateString()
                );
                
                // Also update job team_id (legacy field)
                $jobSchedule->update([
                    'team_id' => $issuing->team_id,
                    'updated_by' => Auth::id() ?? 1
                ]);
            }

        }

        $relatedJobs = $this->resolveRelatedJobSchedules($issuing, $jamis);
        $advancedStatuses = ['in_progress', 'teknisi_sedang_pengerjaan', 'teknisi_selesai_pengerjaan', 'done_job', 'completed'];

        foreach ($relatedJobs as $relatedJob) {
            if (in_array($relatedJob->status, $advancedStatuses)) {
                continue;
            }

            $targetStatus = $this->determineTargetStatus($relatedJob, $issuing);
            if (!$targetStatus || $relatedJob->status === $targetStatus) {
                continue;
            }

            $updateData = [
                'status' => $targetStatus,
                'updated_by' => Auth::id() ?? 1,
            ];

            if ($issuing->team_id && $relatedJob->team_id !== $issuing->team_id) {
                $updateData['team_id'] = $issuing->team_id;
            }

            $relatedJob->update($updateData);

            if ($issuing->team_id) {
                $activeAssignment = JobAssignSchedule::where('job_schedule_id', $relatedJob->id)
                    ->where('team_id', $issuing->team_id)
                    ->where('status', '!=', 'cancelled')
                    ->whereNull('deleted_at')
                    ->latest('id')
                    ->first();

                if ($activeAssignment) {
                    $this->syncRoomAssignmentsForJobSchedule(
                        $relatedJob,
                        (int) $issuing->team_id,
                        (int) $activeAssignment->id,
                        $activeAssignment->assigned_date?->toDateString()
                    );
                }
            }

            \Log::info("Sync Service: JobSchedule {$relatedJob->job_number}#{$relatedJob->id} status updated to '{$targetStatus}' via grouped issuing sync");
        }
    }

    public function resolveRelatedJobSchedules(InventoryIssuing $issuing, $jamis = null)
    {
        if ($jamis === null) {
            $materialIssue = \App\Models\MaterialIssue::where('issue_number', $issuing->reference_no)->first();
            if (!$materialIssue) {
                return collect();
            }

            $jamis = $materialIssue->jobAssignMaterialIssues()
                ->with(['jobAssignSchedule.jobSchedule'])
                ->get();
        }

        $baseJobs = collect($jamis)
            ->map(fn ($jami) => $jami->jobAssignSchedule?->jobSchedule)
            ->filter()
            ->unique('id')
            ->values();

        return $this->expandGroupedJobSchedules($baseJobs);
    }

    public function resolveRelatedJobSchedulesFromMaterialIssue(MaterialIssue $materialIssue)
    {
        $jamis = $materialIssue->jobAssignMaterialIssues()
            ->with(['jobAssignSchedule.jobSchedule'])
            ->get();

        $baseJobs = collect($jamis)
            ->map(fn ($jami) => $jami->jobAssignSchedule?->jobSchedule)
            ->filter()
            ->unique('id')
            ->values();

        return $this->expandGroupedJobSchedules($baseJobs);
    }

    public function syncGroupedJobMaterialLifecycleFromMaterialIssue(MaterialIssue $materialIssue): ?string
    {
        $relatedJobs = $this->resolveRelatedJobSchedulesFromMaterialIssue($materialIssue);
        return $this->syncGroupedJobMaterialLifecycle($relatedJobs);
    }

    public function syncGroupedJobMaterialLifecycleFromJob(JobSchedule $jobSchedule): ?string
    {
        $relatedJobs = $this->expandGroupedJobSchedules(collect([$jobSchedule]));
        return $this->syncGroupedJobMaterialLifecycle($relatedJobs);
    }

    protected function expandGroupedJobSchedules(Collection $baseJobs): Collection
    {
        if ($baseJobs->isEmpty()) {
            return collect();
        }

        $relatedJobs = collect();

        foreach ($baseJobs as $baseJob) {
            $query = JobSchedule::query()->whereNull('deleted_at');

            if (!empty($baseJob->job_number)) {
                $query->where('job_number', $baseJob->job_number);
            } elseif ($baseJob->job_advice_id) {
                $query->where('job_advice_id', $baseJob->job_advice_id)
                    ->where('building_id', $baseJob->building_id)
                    ->where('type', $baseJob->type);

                if ($baseJob->period !== null) {
                    $query->where('period', $baseJob->period);
                } else {
                    $query->whereNull('period');
                }
            } else {
                $query->where('id', $baseJob->id);
            }

            $relatedJobs = $relatedJobs->merge($query->get());
        }

        return $relatedJobs->unique('id')->values();
    }

    protected function syncGroupedJobMaterialLifecycle(Collection $relatedJobs): ?string
    {
        if ($relatedJobs->isEmpty()) {
            return null;
        }

        $jobIds = $relatedJobs->pluck('id')->all();
        $materialIssues = MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function ($query) use ($jobIds) {
            $query->whereIn('job_schedule_id', $jobIds);
        })->get();

        $inventoryIssuings = collect();
        $issueNumbers = $materialIssues->pluck('issue_number')->filter()->unique()->values();
        if ($issueNumbers->isNotEmpty()) {
            $inventoryIssuings = InventoryIssuing::whereIn('reference_no', $issueNumbers->all())->get();
        }

        $assignmentIds = JobAssignSchedule::whereIn('job_schedule_id', $jobIds)
            ->where('status', '!=', 'cancelled')
            ->pluck('id');

        $hasPreparedMaterialItems = $materialIssues->isNotEmpty()
            && $assignmentIds->isNotEmpty()
            && MaterialIssueItem::whereIn('material_issue_id', $materialIssues->pluck('id')->all())
                ->whereIn('job_assign_schedule_id', $assignmentIds->all())
                ->exists();

        $hasTeamAssignment = JobAssignSchedule::whereIn('id', $assignmentIds)
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('team_id')
            ->exists();

        $targetStatus = null;
        $statusSourceIssuing = null;

        if ($inventoryIssuings->contains(fn ($issuing) => in_array($issuing->status, ['sent', 'received'], true))) {
            $targetStatus = 'issued';
            $statusSourceIssuing = $inventoryIssuings->first(fn ($issuing) => in_array($issuing->status, ['sent', 'received'], true));
        } elseif ($inventoryIssuings->contains(fn ($issuing) => $issuing->status === 'processed')) {
            $targetStatus = 'barang_siap_diambil';
        } elseif ($inventoryIssuings->contains(fn ($issuing) => $issuing->status === 'pending')) {
            $targetStatus = 'barang_dipersiapkan';
        } elseif ($hasPreparedMaterialItems) {
            $targetStatus = 'barang_dipersiapkan';
        } elseif ($materialIssues->isNotEmpty()) {
            $targetStatus = 'assign_material';
        } elseif ($hasTeamAssignment) {
            $targetStatus = 'assign_team';
        } else {
            $targetStatus = 'new_job';
        }

        $lockedStatuses = ['in_progress', 'teknisi_sedang_pengerjaan', 'teknisi_selesai_pengerjaan', 'done_job', 'completed'];

        foreach ($relatedJobs as $relatedJob) {
            if (in_array($relatedJob->status, $lockedStatuses, true)) {
                continue;
            }

            $resolvedStatus = $targetStatus === 'issued'
                ? $this->determineTargetStatus($relatedJob, $statusSourceIssuing)
                : $targetStatus;

            if (!$resolvedStatus) {
                continue;
            }

            $updateData = [
                'status' => $resolvedStatus,
                'updated_by' => Auth::id() ?? $relatedJob->updated_by,
            ];

            if (!in_array($resolvedStatus, ['barang_diambil', 'teknisi_tiba_dilokasi'], true)) {
                $updateData['material_checked'] = false;
                $updateData['material_checked_at'] = null;
            }

            if ($resolvedStatus === 'new_job') {
                $updateData['job_number'] = null;
                $updateData['assign_date'] = null;
            }

            $relatedJob->update($updateData);
        }

        return $targetStatus;
    }

    protected function determineTargetStatus(JobSchedule $jobSchedule, InventoryIssuing $issuing): ?string
    {
        return match ($issuing->status) {
            'pending' => 'barang_dipersiapkan',
            'processed' => 'barang_siap_diambil',
            'sent' => in_array(strtolower($jobSchedule->type), ['install', 'install_free', 'pemasangan'])
                ? 'teknisi_tiba_dilokasi'
                : 'barang_diambil',
            default => null,
        };
    }

    /**
     * Sync team from Job Schedule back to Inventory Issuing.
     * MOM: Bidirectional sync requirement.
     * 
     * @param int|int[] $jobScheduleIds
     * @param int|null $teamId
     */
    public function syncTeamFromJobSchedule($jobScheduleIds, $teamId)
    {
        try {
            if (!is_array($jobScheduleIds)) {
                $jobScheduleIds = [$jobScheduleIds];
            }

            if ($teamId) {
                $jobSchedules = JobSchedule::whereIn('id', $jobScheduleIds)->get();
                foreach ($jobSchedules as $jobSchedule) {
                    $activeAssignment = JobAssignSchedule::where('job_schedule_id', $jobSchedule->id)
                        ->where('team_id', $teamId)
                        ->where('status', '!=', 'cancelled')
                        ->whereNull('deleted_at')
                        ->latest('id')
                        ->first();

                    if ($activeAssignment) {
                        $this->syncRoomAssignmentsForJobSchedule(
                            $jobSchedule,
                            (int) $teamId,
                            (int) $activeAssignment->id,
                            $activeAssignment->assigned_date?->toDateString()
                        );
                    }
                }
            }

            // Find all Material Issues related to these Job Schedules
            $materialIssues = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule', function($q) use ($jobScheduleIds) {
                $q->whereIn('job_schedule_id', $jobScheduleIds);
            })->get();

            foreach ($materialIssues as $mi) {
                // Find corresponding Inventory Issuing via reference_no
                $issuing = InventoryIssuing::where('reference_no', $mi->issue_number)->first();
                if ($issuing) {
                    // Only update if team different to avoid circular trigger if we added one (though we haven't yet)
                    if ($issuing->team_id != $teamId) {
                        $issuing->update([
                            'team_id' => $teamId,
                            'updated_by' => Auth::id() ?? 1
                        ]);
                        \Log::info("Sync Service (Job->Issuing): Updated Issuing ID {$issuing->id} with Team ID " . ($teamId ?? 'NULL'));
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error("Sync Team From Job Error: " . $e->getMessage());
        }
    }

    public function syncRoomAssignmentsForJobSchedule(JobSchedule $jobSchedule, ?int $teamId, ?int $jobAssignScheduleId = null, ?string $assignedDate = null): int
    {
        if (!$teamId) {
            return 0;
        }

        $rooms = JobScheduleRoom::where('job_schedule_id', $jobSchedule->id)->get();
        if ($rooms->isEmpty()) {
            return 0;
        }

        $updated = 0;
        $assignedDate = $assignedDate ?: now()->toDateString();

        foreach ($rooms as $room) {
            $assignment = JobScheduleRoomAssignment::withTrashed()
                ->where('job_schedule_id', $jobSchedule->id)
                ->where('job_schedule_room_id', $room->id)
                ->first();

            if ($assignment && (bool) $assignment->is_custom) {
                continue;
            }

            $payload = [
                'job_advice_room_id' => $room->job_advice_room_id,
                'team_id' => $teamId,
                'job_assign_schedule_id' => $jobAssignScheduleId,
                'is_custom' => false,
                'assigned_by' => Auth::id() ?? $assignment?->assigned_by,
                'assigned_date' => $assignedDate,
                'status' => 'assigned',
                'notes' => 'Synced from active job team assignment',
                'updated_by' => Auth::id() ?? 1,
            ];

            if ($assignment) {
                if ($assignment->trashed()) {
                    $assignment->restore();
                }
                $assignment->update($payload);
            } else {
                JobScheduleRoomAssignment::create(array_merge($payload, [
                    'job_schedule_id' => $jobSchedule->id,
                    'job_schedule_room_id' => $room->id,
                    'created_by' => Auth::id() ?? 1,
                ]));
            }

            $updated++;
        }

        return $updated;
    }
}

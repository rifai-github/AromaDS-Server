<?php

namespace App\Services\Operational;

use App\Models\JobAssignSchedule;
use App\Models\JobSchedule;
use App\Models\InventoryIssuingItem;
use App\Models\MaterialIssue;
use App\Models\MaterialIssueItem;
use App\Services\Warehouse\InventoryIssuingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class JobMaterialCompletionService
{
    public function finalizeForCompletedJob(JobSchedule $jobSchedule): void
    {
        if (!in_array($jobSchedule->status, ['done_job', 'completed', 'selesai'], true)) {
            return;
        }

        $jobs = $this->resolveRelatedJobs($jobSchedule)
            ->filter(fn (JobSchedule $job) => in_array($job->status, ['done_job', 'completed', 'selesai'], true))
            ->values();
        if ($jobs->isEmpty()) {
            return;
        }

        $jobIds = $jobs->pluck('id')->all();

        JobAssignSchedule::whereIn('job_schedule_id', $jobIds)
            ->where('status', '!=', 'cancelled')
            ->update([
                'status' => 'completed',
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]);

        $assignmentIds = JobAssignSchedule::withTrashed()
            ->whereIn('job_schedule_id', $jobIds)
            ->pluck('id')
            ->all();

        if (empty($assignmentIds)) {
            return;
        }

        $assignments = JobAssignSchedule::withTrashed()
            ->whereIn('id', $assignmentIds)
            ->with('jobAssignMaterialIssues.materialIssue')
            ->get();

        $issueNumbers = $assignments
            ->flatMap(function (JobAssignSchedule $assignment) {
                return $assignment->jobAssignMaterialIssues
                    ->map(fn ($link) => $link->materialIssue?->issue_number);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $linkedIssueIds = $assignments
            ->flatMap(function (JobAssignSchedule $assignment) {
                return $assignment->jobAssignMaterialIssues
                    ->map(fn ($link) => $link->material_issue_id);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->moveIssuedSerialNumbersToCustomer($jobSchedule, $assignmentIds, $issueNumbers);

        if (!$this->isInstallFree($jobSchedule)) {
            return;
        }

        $items = MaterialIssueItem::with(['product.productType', 'materialIssue'])
            ->where(function ($query) use ($assignmentIds, $linkedIssueIds) {
                $query->whereIn('job_assign_schedule_id', $assignmentIds);

                if (!empty($linkedIssueIds)) {
                    $query->orWhereIn('material_issue_id', $linkedIssueIds);
                }
            })
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $hasUsageStatus = Schema::hasColumn('material_issue_items', 'usage_status');
        $hasUsedAt = Schema::hasColumn('material_issue_items', 'used_at');

        foreach ($items as $item) {
            $status = $this->resolveUsageStatus($item);
            $updates = ['updated_by' => Auth::id()];

            if ($hasUsageStatus) {
                $updates['usage_status'] = $status;
            }

            if ($hasUsedAt) {
                $updates['used_at'] = now();
            }

            if (count($updates) > 1) {
                $item->update($updates);
            }
        }

        $issueIds = $items->pluck('material_issue_id')->filter()->unique()->all();

        if (!empty($issueIds)) {
            $materialIssues = MaterialIssue::whereIn('id', $issueIds)->get();

            $materialIssues->each(function (MaterialIssue $materialIssue) use ($jobSchedule) {
                $metadata = is_array($materialIssue->metadata) ? $materialIssue->metadata : [];
                $metadata['usage_finalized'] = true;
                $metadata['usage_finalized_at'] = now()->toDateTimeString();
                $metadata['usage_finalized_by_job_id'] = $jobSchedule->id;
                $metadata['usage_finalized_by_job_number'] = $jobSchedule->job_number;

                $materialIssue->update([
                    'metadata' => $metadata,
                    'updated_by' => Auth::id(),
                ]);
            });
        }
    }

    public function getEffectiveItemStatus(MaterialIssueItem $item, ?JobSchedule $jobSchedule = null): string
    {
        if (Schema::hasColumn('material_issue_items', 'usage_status') && !empty($item->usage_status)) {
            return $item->usage_status;
        }

        if ($jobSchedule && $this->isInstallFree($jobSchedule) && in_array($jobSchedule->status, ['done_job', 'completed', 'selesai'], true)) {
            return $this->resolveUsageStatus($item);
        }

        return $item->materialIssue?->status ?? 'pending';
    }

    protected function resolveRelatedJobs(JobSchedule $jobSchedule): Collection
    {
        if ($jobSchedule->job_number) {
            return JobSchedule::where('job_number', $jobSchedule->job_number)
                ->where('type', $jobSchedule->type)
                ->get();
        }

        $query = JobSchedule::where('id', $jobSchedule->id);

        if ($jobSchedule->job_advice_id) {
            $query = JobSchedule::where('job_advice_id', $jobSchedule->job_advice_id)
                ->where('building_id', $jobSchedule->building_id)
                ->where('type', $jobSchedule->type);

            if ($jobSchedule->period !== null) {
                $query->where('period', $jobSchedule->period);
            } else {
                $query->whereNull('period');
            }
        }

        return $query->get()->unique('id')->values();
    }

    protected function isInstallFree(JobSchedule $jobSchedule): bool
    {
        $type = strtolower($jobSchedule->type ?? '');
        $jaType = strtolower($jobSchedule->jobAdvice?->type ?? '');

        return in_array($type, ['install_free', 'install free'], true)
            || in_array($jaType, ['install_free', 'install free'], true);
    }

    protected function resolveUsageStatus(MaterialIssueItem $item): string
    {
        $productName = strtolower($item->product?->name ?? '');
        $typeName = strtolower($item->product?->productType?->name ?? '');
        $haystack = "{$productName} {$typeName}";

        if (str_contains($haystack, 'diffuser')
            || str_contains($haystack, 'dispenser')
            || str_contains($haystack, 'unit')
            || str_contains($haystack, 'device')
            || str_contains($haystack, 'machine')) {
            return 'installed';
        }

        return 'used';
    }

    protected function moveIssuedSerialNumbersToCustomer(JobSchedule $jobSchedule, array $assignmentIds, array $issueNumbers): void
    {
        $serialNumberQuery = InventoryIssuingItem::query()
            ->whereNotNull('serial_number_id');

        if (!empty($assignmentIds)) {
            $serialNumberQuery->where(function ($query) use ($assignmentIds, $issueNumbers) {
                $query->whereIn('job_assign_schedule_id', $assignmentIds);

                if (!empty($issueNumbers)) {
                    $query->orWhere(function ($legacyQuery) use ($issueNumbers) {
                        $legacyQuery->whereNull('job_assign_schedule_id')
                            ->whereHas('inventoryIssuing', function ($issuingQuery) use ($issueNumbers) {
                                $issuingQuery->whereIn('reference_no', $issueNumbers);
                            });
                    });
                }
            });
        } elseif (!empty($issueNumbers)) {
            $serialNumberQuery->whereHas('inventoryIssuing', function ($issuingQuery) use ($issueNumbers) {
                $issuingQuery->whereIn('reference_no', $issueNumbers);
            });
        } else {
            return;
        }

        $items = $serialNumberQuery
            ->with(['product.productCategory', 'product.productType', 'serialNumber'])
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        app(InventoryIssuingService::class)->moveSerialNumbersToCustomerForItems(
            $items,
            $jobSchedule->jobAdvice?->customer_id,
            Auth::id(),
            $jobSchedule->job_number
        );
    }
}

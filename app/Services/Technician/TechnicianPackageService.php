<?php

namespace App\Services\Technician;

use App\Models\JobSchedule;
use App\Models\JobReport;
use App\Models\TechnicianActivity;
use App\Models\TechnicianPackage;
use App\Models\TechnicianPackageItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TechnicianPackageService
{
    /**
     * Create technician package for job schedule
     */
    public function createTechnicianPackage($jobScheduleId, $packageData, $userId = null)
    {
        try {
            DB::beginTransaction();

            $jobSchedule = JobSchedule::findOrFail($jobScheduleId);

            // Create technician package
            $package = TechnicianPackage::create([
                'job_schedule_id' => $jobScheduleId,
                'technician_id' => $jobSchedule->assigned_technician_id,
                'package_name' => $packageData['package_name'],
                'package_description' => $packageData['package_description'] ?? null,
                'total_items' => count($packageData['items']),
                'completed_items' => 0,
                'status' => 'pending',
                'created_by' => $userId ?? auth()->id()
            ]);

            // Create package items
            foreach ($packageData['items'] as $index => $item) {
                TechnicianPackageItem::create([
                    'package_id' => $package->id,
                    'item_name' => $item['item_name'],
                    'item_description' => $item['item_description'] ?? null,
                    'item_order' => $index + 1,
                    'is_required' => $item['is_required'] ?? true,
                    'is_completed' => false,
                    'completed_at' => null,
                    'completed_by' => null
                ]);
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Technician package created successfully',
                'data' => $package->load('items')
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create technician package: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Complete package item
     */
    public function completePackageItem($packageId, $itemId, $userId = null)
    {
        try {
            DB::beginTransaction();

            $package = TechnicianPackage::findOrFail($packageId);
            $item = TechnicianPackageItem::where('package_id', $packageId)
                ->where('id', $itemId)
                ->firstOrFail();

            // Check if item is already completed
            if ($item->is_completed) {
                throw new \Exception("Item is already completed");
            }

            // Complete the item
            $item->update([
                'is_completed' => true,
                'completed_at' => now(),
                'completed_by' => $userId ?? auth()->id()
            ]);

            // Update package completion status
            $this->updatePackageCompletionStatus($package);

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Package item completed successfully',
                'data' => $item
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to complete package item: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Uncomplete package item
     */
    public function uncompletePackageItem($packageId, $itemId, $userId = null)
    {
        try {
            DB::beginTransaction();

            $package = TechnicianPackage::findOrFail($packageId);
            $item = TechnicianPackageItem::where('package_id', $packageId)
                ->where('id', $itemId)
                ->firstOrFail();

            // Uncomplete the item
            $item->update([
                'is_completed' => false,
                'completed_at' => null,
                'completed_by' => null
            ]);

            // Update package completion status
            $this->updatePackageCompletionStatus($package);

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Package item uncompleted successfully',
                'data' => $item
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to uncomplete package item: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Complete entire package
     */
    public function completePackage($packageId, $userId = null)
    {
        try {
            DB::beginTransaction();

            $package = TechnicianPackage::with('items')->findOrFail($packageId);

            // Check if all required items are completed
            $requiredItems = $package->items->where('is_required', true);
            $completedRequiredItems = $requiredItems->where('is_completed', true);

            if ($completedRequiredItems->count() !== $requiredItems->count()) {
                throw new \Exception("All required items must be completed before completing the package");
            }

            // Complete the package
            $package->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => $userId ?? auth()->id()
            ]);

            // Create technician activity
            TechnicianActivity::create([
                'technician_id' => $package->technician_id,
                'job_schedule_id' => $package->job_schedule_id,
                'activity_type' => 'complete_work',
                'activity_time' => now(),
                'notes' => 'Package completed: ' . $package->package_name
            ]);

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Package completed successfully',
                'data' => $package
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to complete package: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get technician package
     */
    public function getTechnicianPackage($packageId)
    {
        $package = TechnicianPackage::with(['items', 'technician', 'jobSchedule'])->findOrFail($packageId);
        
        return $package;
    }

    /**
     * Get technician packages for job schedule
     */
    public function getTechnicianPackagesForJobSchedule($jobScheduleId)
    {
        $packages = TechnicianPackage::where('job_schedule_id', $jobScheduleId)
            ->with(['items', 'technician'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return $packages;
    }

    /**
     * Get technician packages for technician
     */
    public function getTechnicianPackagesForTechnician($technicianId, $status = null)
    {
        $query = TechnicianPackage::where('technician_id', $technicianId)
            ->with(['items', 'jobSchedule.building', 'jobSchedule.prospect']);

        if ($status) {
            $query->where('status', $status);
        }

        $packages = $query->orderBy('created_at', 'desc')->get();
        
        return $packages;
    }

    /**
     * Update package completion status
     */
    public function updatePackageCompletionStatus(TechnicianPackage $package)
    {
        $totalItems = $package->items->count();
        $completedItems = $package->items->where('is_completed', true)->count();
        
        $package->update([
            'completed_items' => $completedItems,
            'completion_percentage' => $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 2) : 0
        ]);

        // Update status based on completion
        if ($completedItems === $totalItems) {
            $package->update(['status' => 'ready_to_complete']);
        } elseif ($completedItems > 0) {
            $package->update(['status' => 'in_progress']);
        } else {
            $package->update(['status' => 'pending']);
        }

        return $package;
    }

    /**
     * Get package statistics
     */
    public function getPackageStatistics($packageId)
    {
        $package = TechnicianPackage::with('items')->findOrFail($packageId);
        
        $totalItems = $package->items->count();
        $completedItems = $package->items->where('is_completed', true)->count();
        $requiredItems = $package->items->where('is_required', true)->count();
        $completedRequiredItems = $package->items->where('is_required', true)->where('is_completed', true)->count();
        
        return [
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'required_items' => $requiredItems,
            'completed_required_items' => $completedRequiredItems,
            'completion_percentage' => $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 2) : 0,
            'can_complete' => $completedRequiredItems === $requiredItems,
            'status' => $package->status
        ];
    }

    /**
     * Get technician package analytics
     */
    public function getTechnicianPackageAnalytics($technicianId, $dateRange = null)
    {
        $query = TechnicianPackage::where('technician_id', $technicianId);

        if ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        }

        $packages = $query->with('items')->get();
        
        $totalPackages = $packages->count();
        $completedPackages = $packages->where('status', 'completed')->count();
        $inProgressPackages = $packages->where('status', 'in_progress')->count();
        $pendingPackages = $packages->where('status', 'pending')->count();
        
        $totalItems = $packages->sum('total_items');
        $completedItems = $packages->sum('completed_items');
        
        $averageCompletionTime = $packages->where('status', 'completed')
            ->map(function($package) {
                return $package->created_at->diffInMinutes($package->completed_at);
            })->avg();

        return [
            'total_packages' => $totalPackages,
            'completed_packages' => $completedPackages,
            'in_progress_packages' => $inProgressPackages,
            'pending_packages' => $pendingPackages,
            'completion_rate' => $totalPackages > 0 ? round(($completedPackages / $totalPackages) * 100, 2) : 0,
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'item_completion_rate' => $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 2) : 0,
            'average_completion_time_minutes' => round($averageCompletionTime, 2)
        ];
    }

    /**
     * Validate package completion
     */
    public function validatePackageCompletion($packageId)
    {
        $package = TechnicianPackage::with('items')->findOrFail($packageId);
        
        $errors = [];
        $warnings = [];
        
        // Check if all required items are completed
        $requiredItems = $package->items->where('is_required', true);
        $completedRequiredItems = $requiredItems->where('is_completed', true);
        
        if ($completedRequiredItems->count() !== $requiredItems->count()) {
            $errors[] = "All required items must be completed";
        }
        
        // Check if package is already completed
        if ($package->status === 'completed') {
            $errors[] = "Package is already completed";
        }
        
        // Check if package has items
        if ($package->items->count() === 0) {
            $warnings[] = "Package has no items";
        }
        
        return [
            'is_valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Get package progress
     */
    public function getPackageProgress($packageId)
    {
        $package = TechnicianPackage::with('items')->findOrFail($packageId);
        
        $items = $package->items->map(function($item) {
            return [
                'id' => $item->id,
                'name' => $item->item_name,
                'description' => $item->item_description,
                'order' => $item->item_order,
                'is_required' => $item->is_required,
                'is_completed' => $item->is_completed,
                'completed_at' => $item->completed_at,
                'completed_by' => $item->completed_by
            ];
        })->sortBy('order');
        
        return [
            'package' => $package,
            'items' => $items,
            'progress' => [
                'total_items' => $package->items->count(),
                'completed_items' => $package->items->where('is_completed', true)->count(),
                'completion_percentage' => $package->completion_percentage
            ]
        ];
    }
}

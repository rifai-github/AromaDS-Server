<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Services\Technician\TechnicianPackageService;
use App\Models\TechnicianPackage;
use App\Models\JobSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TechnicianPackageController extends Controller
{
    protected $technicianPackageService;

    public function __construct(TechnicianPackageService $technicianPackageService)
    {
        $this->technicianPackageService = $technicianPackageService;
    }

    /**
     * Create technician package
     */
    public function createPackage(Request $request)
    {
        $request->validate([
            'job_schedule_id' => 'required|exists:job_schedules,id',
            'package_name' => 'required|string|max:255',
            'package_description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.item_description' => 'nullable|string',
            'items.*.is_required' => 'boolean'
        ]);

        $result = $this->technicianPackageService->createTechnicianPackage(
            $request->job_schedule_id,
            $request->all(),
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Complete package item
     */
    public function completeItem(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:technician_packages,id',
            'item_id' => 'required|exists:technician_package_items,id'
        ]);

        $result = $this->technicianPackageService->completePackageItem(
            $request->package_id,
            $request->item_id,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Uncomplete package item
     */
    public function uncompleteItem(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:technician_packages,id',
            'item_id' => 'required|exists:technician_package_items,id'
        ]);

        $result = $this->technicianPackageService->uncompletePackageItem(
            $request->package_id,
            $request->item_id,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Complete entire package
     */
    public function completePackage(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:technician_packages,id'
        ]);

        $result = $this->technicianPackageService->completePackage(
            $request->package_id,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Get technician package
     */
    public function getPackage($packageId)
    {
        $package = $this->technicianPackageService->getTechnicianPackage($packageId);

        return response()->json([
            'status' => 'success',
            'data' => $package
        ]);
    }

    /**
     * Get technician packages for job schedule
     */
    public function getPackagesForJobSchedule($jobScheduleId)
    {
        $packages = $this->technicianPackageService->getTechnicianPackagesForJobSchedule($jobScheduleId);

        return response()->json([
            'status' => 'success',
            'data' => $packages
        ]);
    }

    /**
     * Get technician packages for technician
     */
    public function getPackagesForTechnician(Request $request, $technicianId)
    {
        $status = $request->get('status');
        $packages = $this->technicianPackageService->getTechnicianPackagesForTechnician($technicianId, $status);

        return response()->json([
            'status' => 'success',
            'data' => $packages
        ]);
    }

    /**
     * Get package statistics
     */
    public function getPackageStatistics($packageId)
    {
        $statistics = $this->technicianPackageService->getPackageStatistics($packageId);

        return response()->json([
            'status' => 'success',
            'data' => $statistics
        ]);
    }

    /**
     * Get technician package analytics
     */
    public function getTechnicianAnalytics(Request $request, $technicianId)
    {
        $dateRange = $request->get('date_range');
        $analytics = $this->technicianPackageService->getTechnicianPackageAnalytics($technicianId, $dateRange);

        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ]);
    }

    /**
     * Validate package completion
     */
    public function validatePackageCompletion($packageId)
    {
        $validation = $this->technicianPackageService->validatePackageCompletion($packageId);

        return response()->json([
            'status' => 'success',
            'data' => $validation
        ]);
    }

    /**
     * Get package progress
     */
    public function getPackageProgress($packageId)
    {
        $progress = $this->technicianPackageService->getPackageProgress($packageId);

        return response()->json([
            'status' => 'success',
            'data' => $progress
        ]);
    }

    /**
     * Get technician dashboard data
     */
    public function getTechnicianDashboard($technicianId)
    {
        $packages = $this->technicianPackageService->getTechnicianPackagesForTechnician($technicianId);
        
        $dashboard = [
            'total_packages' => $packages->count(),
            'completed_packages' => $packages->where('status', 'completed')->count(),
            'in_progress_packages' => $packages->where('status', 'in_progress')->count(),
            'ready_to_complete_packages' => $packages->where('status', 'ready_to_complete')->count(),
            'pending_packages' => $packages->where('status', 'pending')->count(),
            'recent_packages' => $packages->take(5),
            'analytics' => $this->technicianPackageService->getTechnicianPackageAnalytics($technicianId)
        ];

        return response()->json([
            'status' => 'success',
            'data' => $dashboard
        ]);
    }

    /**
     * Search technician packages
     */
    public function searchPackages(Request $request, $technicianId)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        $query = TechnicianPackage::where('technician_id', $technicianId)
            ->with(['items', 'jobSchedule.building', 'jobSchedule.prospect']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('package_name', 'like', '%' . $search . '%')
                  ->orWhere('package_description', 'like', '%' . $search . '%');
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $packages = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $packages->items(),
            'pagination' => [
                'total' => $packages->total(),
                'per_page' => $packages->perPage(),
                'current_page' => $packages->currentPage(),
                'last_page' => $packages->lastPage(),
                'from' => $packages->firstItem(),
                'to' => $packages->lastItem(),
            ]
        ]);
    }

    /**
     * Get package items
     */
    public function getPackageItems($packageId)
    {
        $package = TechnicianPackage::with('items')->findOrFail($packageId);
        
        return response()->json([
            'status' => 'success',
            'data' => $package->items->sortBy('item_order')
        ]);
    }

    /**
     * Update package item
     */
    public function updatePackageItem(Request $request, $packageId, $itemId)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'item_description' => 'nullable|string',
            'is_required' => 'boolean'
        ]);

        $package = TechnicianPackage::findOrFail($packageId);
        $item = $package->items()->findOrFail($itemId);

        $item->update($request->only(['item_name', 'item_description', 'is_required']));

        return response()->json([
            'status' => 'success',
            'message' => 'Package item updated successfully',
            'data' => $item
        ]);
    }

    /**
     * Delete package item
     */
    public function deletePackageItem($packageId, $itemId)
    {
        $package = TechnicianPackage::findOrFail($packageId);
        $item = $package->items()->findOrFail($itemId);

        $item->delete();

        // Update package completion status
        $package->updateCompletionStatus();

        return response()->json([
            'status' => 'success',
            'message' => 'Package item deleted successfully'
        ]);
    }
}

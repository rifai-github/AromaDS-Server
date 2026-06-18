<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Services\Marketing\BuildingMarketingService;
use App\Models\MarketingPipeline;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuildingMarketingController extends Controller
{
    protected $buildingMarketingService;

    public function __construct(BuildingMarketingService $buildingMarketingService)
    {
        $this->buildingMarketingService = $buildingMarketingService;
    }

    /**
     * Assign building to marketing pipeline
     */
    public function assignBuilding(Request $request)
    {
        $request->validate([
            'pipeline_id' => 'required|exists:marketing_pipelines,id',
            'building_id' => 'required|exists:buildings,id'
        ]);

        $result = $this->buildingMarketingService->assignBuildingToPipeline(
            $request->pipeline_id,
            $request->building_id,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Remove building from marketing pipeline
     */
    public function removeBuilding(Request $request)
    {
        $request->validate([
            'pipeline_id' => 'required|exists:marketing_pipelines,id',
            'building_id' => 'required|exists:buildings,id'
        ]);

        $result = $this->buildingMarketingService->removeBuildingFromPipeline(
            $request->pipeline_id,
            $request->building_id,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Get buildings for marketing pipeline
     */
    public function getPipelineBuildings($pipelineId)
    {
        $buildings = $this->buildingMarketingService->getPipelineBuildings($pipelineId);

        return response()->json([
            'status' => 'success',
            'data' => $buildings
        ]);
    }

    /**
     * Get available buildings for assignment
     */
    public function getAvailableBuildings(Request $request, $pipelineId)
    {
        $search = $request->get('search');
        $buildings = $this->buildingMarketingService->getAvailableBuildings($pipelineId, $search);

        return response()->json([
            'status' => 'success',
            'data' => $buildings
        ]);
    }

    /**
     * Auto-assign buildings from surveys
     */
    public function autoAssignFromSurveys(Request $request)
    {
        $request->validate([
            'pipeline_id' => 'required|exists:marketing_pipelines,id'
        ]);

        $result = $this->buildingMarketingService->autoAssignBuildingsFromSurveys(
            $request->pipeline_id,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Get building marketing statistics
     */
    public function getStatistics()
    {
        $statistics = $this->buildingMarketingService->getBuildingMarketingStatistics();

        return response()->json([
            'status' => 'success',
            'data' => $statistics
        ]);
    }

    /**
     * Get buildings by marketing status
     */
    public function getBuildingsByStatus(Request $request)
    {
        $status = $request->get('status', 'all');
        $buildings = $this->buildingMarketingService->getBuildingsByMarketingStatus($status);

        return response()->json([
            'status' => 'success',
            'data' => $buildings
        ]);
    }

    /**
     * Bulk assign buildings to pipeline
     */
    public function bulkAssignBuildings(Request $request)
    {
        $request->validate([
            'pipeline_id' => 'required|exists:marketing_pipelines,id',
            'building_ids' => 'required|array',
            'building_ids.*' => 'exists:buildings,id'
        ]);

        $result = $this->buildingMarketingService->bulkAssignBuildings(
            $request->pipeline_id,
            $request->building_ids,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Get building details for marketing
     */
    public function getBuildingDetails($buildingId)
    {
        $building = Building::with([
            'customer',
            'province',
            'city',
            'district',
            'subdistrict',
            'marketingPipelines',
            'rooms',
            'floors'
        ])->findOrFail($buildingId);

        return response()->json([
            'status' => 'success',
            'data' => $building
        ]);
    }

    /**
     * Search buildings for marketing
     */
    public function searchBuildings(Request $request)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        
        $query = Building::with(['customers', 'province', 'city', 'district', 'subdistrict']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_gedung', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%')
                  ->orWhere('alamat_1', 'like', '%' . $search . '%')
                  ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        switch ($status) {
            case 'in_marketing':
                $query->whereHas('marketingPipelines');
                break;
            case 'not_in_marketing':
                $query->whereDoesntHave('marketingPipelines');
                break;
            case 'with_customer':
                $query->whereHas('customers');
                break;
            case 'without_customer':
                $query->whereDoesntHave('customers');
                break;
        }

        $buildings = $query->orderBy('nama_gedung')->paginateStd(25);

        return response()->json([
            'status' => 'success',
            'data' => $buildings->items(),
            'pagination' => [
                'total' => $buildings->total(),
                'per_page' => $buildings->perPage(),
                'current_page' => $buildings->currentPage(),
                'last_page' => $buildings->lastPage(),
                'from' => $buildings->firstItem(),
                'to' => $buildings->lastItem(),
            ]
        ]);
    }
}

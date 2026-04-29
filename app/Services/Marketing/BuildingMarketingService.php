<?php

namespace App\Services\Marketing;

use App\Models\Building;
use App\Models\MarketingPipeline;
use App\Models\Customer;
use App\Models\Survey;
use App\Models\Prospect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BuildingMarketingService
{
    /**
     * Assign building to marketing pipeline
     */
    public function assignBuildingToPipeline($pipelineId, $buildingId, $userId = null)
    {
        try {
            DB::beginTransaction();

            $pipeline = MarketingPipeline::findOrFail($pipelineId);
            $building = Building::findOrFail($buildingId);

            // Check if building is already assigned to another customer
            if ($building->customer_id && $building->customer_id != $pipeline->customer_id) {
                throw new \Exception("Building is already assigned to another customer");
            }

            // Assign building to customer if not already assigned
            if (!$building->customer_id) {
                $building->update([
                    'customer_id' => $pipeline->customer_id,
                    'updated_by' => $userId ?? auth()->id()
                ]);
            }

            // Create building-pipeline relationship
            $pipeline->buildings()->syncWithoutDetaching([$buildingId => [
                'assigned_at' => now(),
                'assigned_by' => $userId ?? auth()->id()
            ]]);

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Building assigned to marketing pipeline successfully',
                'data' => [
                    'pipeline_id' => $pipelineId,
                    'building_id' => $buildingId,
                    'building_name' => $building->nama_gedung
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to assign building to pipeline: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove building from marketing pipeline
     */
    public function removeBuildingFromPipeline($pipelineId, $buildingId, $userId = null)
    {
        try {
            DB::beginTransaction();

            $pipeline = MarketingPipeline::findOrFail($pipelineId);
            $building = Building::findOrFail($buildingId);

            // Remove building-pipeline relationship
            $pipeline->buildings()->detach($buildingId);

            // Optionally unassign building from customer if no other pipelines use it
            $otherPipelines = MarketingPipeline::whereHas('buildings', function($query) use ($buildingId) {
                $query->where('building_id', $buildingId);
            })->where('id', '!=', $pipelineId)->count();

            if ($otherPipelines == 0) {
                $building->update([
                    'customer_id' => null,
                    'updated_by' => $userId ?? auth()->id()
                ]);
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Building removed from marketing pipeline successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to remove building from pipeline: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get buildings for marketing pipeline
     */
    public function getPipelineBuildings($pipelineId)
    {
        $pipeline = MarketingPipeline::with(['buildings' => function($query) {
            $query->with(['customer', 'province', 'city', 'district', 'subdistrict']);
        }])->findOrFail($pipelineId);

        return $pipeline->buildings;
    }

    /**
     * Get available buildings for assignment
     */
    public function getAvailableBuildings($pipelineId, $search = null)
    {
        $pipeline = MarketingPipeline::findOrFail($pipelineId);
        
        $query = Building::whereDoesntHave('marketingPipelines', function($q) use ($pipelineId) {
            $q->where('marketing_pipeline_id', $pipelineId);
        });

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nama_gedung', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%')
                  ->orWhere('alamat_1', 'like', '%' . $search . '%')
                  ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        return $query->with(['customer', 'province', 'city', 'district', 'subdistrict'])
                    ->orderBy('nama_gedung')
                    ->get();
    }

    /**
     * Auto-assign buildings from surveys
     */
    public function autoAssignBuildingsFromSurveys($pipelineId, $userId = null)
    {
        try {
            DB::beginTransaction();

            $pipeline = MarketingPipeline::findOrFail($pipelineId);
            
            // Find surveys related to this pipeline's company
            $surveys = Survey::whereHas('prospect', function($query) use ($pipeline) {
                $query->where('company_name', $pipeline->company_name);
            })->whereNotNull('building_id')->get();

            $assignedBuildings = [];

            foreach ($surveys as $survey) {
                $building = Building::find($survey->building_id);
                
                if ($building) {
                    // Check if building is already assigned to another customer
                    if ($building->customer_id && $building->customer_id != $pipeline->customer_id) {
                        Log::warning("Building {$building->id} already assigned to customer {$building->customer_id}, skipping assignment to pipeline {$pipelineId}");
                        continue;
                    }

                    // Assign building to customer if not already assigned
                    if (!$building->customer_id) {
                        $building->update([
                            'customer_id' => $pipeline->customer_id,
                            'updated_by' => $userId ?? auth()->id()
                        ]);
                    }

                    // Create building-pipeline relationship
                    $pipeline->buildings()->syncWithoutDetaching([$building->id => [
                        'assigned_at' => now(),
                        'assigned_by' => $userId ?? auth()->id()
                    ]]);

                    $assignedBuildings[] = $building->id;
                }
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Buildings auto-assigned from surveys successfully',
                'data' => [
                    'assigned_buildings' => $assignedBuildings,
                    'count' => count($assignedBuildings)
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to auto-assign buildings from surveys: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get building marketing statistics
     */
    public function getBuildingMarketingStatistics()
    {
        $totalBuildings = Building::count();
        $buildingsInMarketing = Building::whereHas('marketingPipelines')->count();
        $buildingsWithCustomer = Building::whereNotNull('customer_id')->count();
        $buildingsWithoutCustomer = Building::whereNull('customer_id')->count();

        return [
            'total_buildings' => $totalBuildings,
            'buildings_in_marketing' => $buildingsInMarketing,
            'buildings_with_customer' => $buildingsWithCustomer,
            'buildings_without_customer' => $buildingsWithoutCustomer,
            'marketing_coverage_percentage' => $totalBuildings > 0 ? round(($buildingsInMarketing / $totalBuildings) * 100, 2) : 0
        ];
    }

    /**
     * Get buildings by marketing status
     */
    public function getBuildingsByMarketingStatus($status = 'all')
    {
        $query = Building::with(['customer', 'marketingPipelines', 'province', 'city']);

        switch ($status) {
            case 'in_marketing':
                $query->whereHas('marketingPipelines');
                break;
            case 'not_in_marketing':
                $query->whereDoesntHave('marketingPipelines');
                break;
            case 'with_customer':
                $query->whereNotNull('customer_id');
                break;
            case 'without_customer':
                $query->whereNull('customer_id');
                break;
        }

        return $query->orderBy('nama_gedung')->get();
    }

    /**
     * Bulk assign buildings to pipeline
     */
    public function bulkAssignBuildings($pipelineId, $buildingIds, $userId = null)
    {
        try {
            DB::beginTransaction();

            $pipeline = MarketingPipeline::findOrFail($pipelineId);
            $assignedBuildings = [];
            $errors = [];

            foreach ($buildingIds as $buildingId) {
                $result = $this->assignBuildingToPipeline($pipelineId, $buildingId, $userId);
                
                if ($result['status'] === 'success') {
                    $assignedBuildings[] = $buildingId;
                } else {
                    $errors[] = [
                        'building_id' => $buildingId,
                        'error' => $result['message']
                    ];
                }
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Bulk assignment completed',
                'data' => [
                    'assigned_buildings' => $assignedBuildings,
                    'errors' => $errors,
                    'success_count' => count($assignedBuildings),
                    'error_count' => count($errors)
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to bulk assign buildings: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}

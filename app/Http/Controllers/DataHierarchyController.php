<?php

namespace App\Http\Controllers;

use App\Services\DataHierarchyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DataHierarchyController extends Controller
{
    protected $dataHierarchyService;

    public function __construct(DataHierarchyService $dataHierarchyService)
    {
        $this->dataHierarchyService = $dataHierarchyService;
    }

    /**
     * Get customer hierarchy
     */
    public function getCustomerHierarchy(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'required|exists:customers,id'
            ]);

            $result = $this->dataHierarchyService->getCustomerHierarchy($request->customer_id);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get customer hierarchy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contract hierarchy
     */
    public function getContractHierarchy(Request $request)
    {
        try {
            $request->validate([
                'contract_id' => 'required|exists:contracts,id'
            ]);

            $result = $this->dataHierarchyService->getContractHierarchy($request->contract_id);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get contract hierarchy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get building hierarchy
     */
    public function getBuildingHierarchy(Request $request)
    {
        try {
            $request->validate([
                'building_id' => 'required|exists:buildings,id'
            ]);

            $result = $this->dataHierarchyService->getBuildingHierarchy($request->building_id);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get building hierarchy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get hierarchy statistics
     */
    public function getHierarchyStatistics()
    {
        try {
            $result = $this->dataHierarchyService->getHierarchyStatistics();

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get hierarchy statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search within hierarchy
     */
    public function searchHierarchy(Request $request)
    {
        try {
            $request->validate([
                'search_term' => 'required|string|min:2',
                'filters' => 'nullable|array',
                'filters.exclude_customers' => 'boolean',
                'filters.exclude_buildings' => 'boolean',
                'filters.exclude_contracts' => 'boolean'
            ]);

            $result = $this->dataHierarchyService->searchHierarchy(
                $request->search_term,
                $request->filters ?? []
            );

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data'],
                    'search_term' => $result['search_term']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to search hierarchy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get hierarchy tree structure
     */
    public function getHierarchyTree(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'nullable|exists:customers,id'
            ]);

            $result = $this->dataHierarchyService->getHierarchyTree($request->customer_id);

            if ($result['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get hierarchy tree: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get hierarchy visualization data
     */
    public function getHierarchyVisualization(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'nullable|exists:customers,id',
                'format' => 'nullable|in:tree,graph,list'
            ]);

            $format = $request->format ?? 'tree';
            $customerId = $request->customer_id;

            $result = $this->dataHierarchyService->getHierarchyTree($customerId);

            if ($result['success']) {
                $visualizationData = $this->formatVisualizationData($result['data'], $format);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Hierarchy visualization data retrieved successfully',
                    'data' => $visualizationData,
                    'format' => $format
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get hierarchy visualization: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get hierarchy export data
     */
    public function exportHierarchy(Request $request)
    {
        try {
            $request->validate([
                'customer_id' => 'nullable|exists:customers,id',
                'format' => 'required|in:json,csv,xml',
                'include_details' => 'boolean'
            ]);

            $customerId = $request->customer_id;
            $format = $request->format;
            $includeDetails = $request->include_details ?? false;

            $result = $this->dataHierarchyService->getHierarchyTree($customerId);

            if ($result['success']) {
                $exportData = $this->prepareExportData($result['data'], $includeDetails);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Hierarchy export data prepared successfully',
                    'data' => $exportData,
                    'format' => $format,
                    'include_details' => $includeDetails
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export hierarchy: ' . $e->getMessage()
            ], 500);
        }
    }

    // Private helper methods

    private function formatVisualizationData($data, $format): array
    {
        switch ($format) {
            case 'graph':
                return $this->formatGraphData($data);
            case 'list':
                return $this->formatListData($data);
            default:
                return $data; // Tree format
        }
    }

    private function formatGraphData($data): array
    {
        $nodes = [];
        $edges = [];

        $this->buildGraphNodes($data, $nodes, $edges);

        return [
            'nodes' => $nodes,
            'edges' => $edges
        ];
    }

    private function buildGraphNodes($data, &$nodes, &$edges, $parentId = null)
    {
        if (is_array($data)) {
            foreach ($data as $item) {
                $nodeId = $item['id'] ?? uniqid();
                $nodes[] = [
                    'id' => $nodeId,
                    'label' => $item['name'] ?? 'Unknown',
                    'type' => $item['type'] ?? 'unknown',
                    'data' => $item
                ];

                if ($parentId) {
                    $edges[] = [
                        'from' => $parentId,
                        'to' => $nodeId
                    ];
                }

                if (isset($item['children']) && is_array($item['children'])) {
                    $this->buildGraphNodes($item['children'], $nodes, $edges, $nodeId);
                }
            }
        }
    }

    private function formatListData($data): array
    {
        $list = [];
        $this->buildListItems($data, $list, 0);
        return $list;
    }

    private function buildListItems($data, &$list, $level)
    {
        if (is_array($data)) {
            foreach ($data as $item) {
                $list[] = [
                    'id' => $item['id'] ?? uniqid(),
                    'name' => $item['name'] ?? 'Unknown',
                    'type' => $item['type'] ?? 'unknown',
                    'level' => $level,
                    'data' => $item
                ];

                if (isset($item['children']) && is_array($item['children'])) {
                    $this->buildListItems($item['children'], $list, $level + 1);
                }
            }
        }
    }

    private function prepareExportData($data, $includeDetails): array
    {
        $exportData = [
            'exported_at' => now()->toISOString(),
            'hierarchy' => $data
        ];

        if ($includeDetails) {
            $exportData['statistics'] = $this->dataHierarchyService->getHierarchyStatistics()['data'] ?? [];
        }

        return $exportData;
    }
}

<?php

namespace App\Http\Controllers\Api\Other;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\Other\CustomerPortalService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerPortalApiController extends BaseApiController
{
    protected $customerPortalService;

    public function __construct(CustomerPortalService $customerPortalService)
    {
        $this->customerPortalService = $customerPortalService;
    }

    /**
     * Get all customer portals
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['search', 'status', 'created_from', 'created_to']);
            
            $portals = $this->customerPortalService->getAllPortals($perPage, $filters);
            
            return $this->successResponse([
                'portals' => $portals->items(),
                'pagination' => $this->getPaginationMeta($portals),
            ], 'Customer portals retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Create new customer portal
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'url' => 'required|url|max:255',
                'is_active' => 'boolean',
                'settings' => 'nullable|array',
            ];
            
            $data = $this->validateRequest($request, $rules);
            
            $portal = $this->customerPortalService->createPortal($data);
            
            return $this->successResponse($portal, 'Customer portal created successfully', 201);
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get customer portal by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $portal = $this->customerPortalService->getPortalById($id);
            
            return $this->successResponse($portal, 'Customer portal retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update customer portal
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $rules = [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'url' => 'sometimes|required|url|max:255',
                'is_active' => 'boolean',
                'settings' => 'nullable|array',
            ];
            
            $data = $this->validateRequest($request, $rules);
            
            $portal = $this->customerPortalService->updatePortal($id, $data);
            
            return $this->successResponse($portal, 'Customer portal updated successfully');
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Delete customer portal
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->customerPortalService->deletePortal($id);
            
            return $this->successResponse(null, 'Customer portal deleted successfully');
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get portal sessions
     */
    public function getSessions($id): JsonResponse
    {
        try {
            $sessions = $this->customerPortalService->getPortalSessions($id);
            
            return $this->successResponse($sessions, 'Portal sessions retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get portal activities
     */
    public function getActivities($id): JsonResponse
    {
        try {
            $activities = $this->customerPortalService->getPortalActivities($id);
            
            return $this->successResponse($activities, 'Portal activities retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get portal statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->customerPortalService->getStatistics();
            
            return $this->successResponse($statistics, 'Portal statistics retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}

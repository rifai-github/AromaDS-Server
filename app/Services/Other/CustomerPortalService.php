<?php

namespace App\Services\Other;

use App\Models\CustomerPortal;
use App\Repositories\Other\CustomerPortalRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerPortalService
{
    protected $customerPortalRepository;

    public function __construct(CustomerPortalRepository $customerPortalRepository)
    {
        $this->customerPortalRepository = $customerPortalRepository;
    }

    /**
     * Get all customer portals with pagination
     */
    public function getAllPortals($perPage = 15, $filters = [])
    {
        return $this->customerPortalRepository->getAllWithFilters($perPage, $filters);
    }

    /**
     * Create new customer portal
     */
    public function createPortal($data)
    {
        DB::beginTransaction();
        try {
            // Validate business rules
            $this->validatePortalCreation($data);
            
            // Create portal
            $portal = $this->customerPortalRepository->create($data);
            
            // Create default preferences
            $this->createDefaultPreferences($portal->id);
            
            DB::commit();
            return $portal;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error creating customer portal: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update customer portal
     */
    public function updatePortal($id, $data)
    {
        DB::beginTransaction();
        try {
            $portal = $this->customerPortalRepository->findOrFail($id);
            
            // Validate business rules
            $this->validatePortalUpdate($portal, $data);
            
            // Update portal
            $updatedPortal = $this->customerPortalRepository->update($id, $data);
            
            DB::commit();
            return $updatedPortal;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating customer portal: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Soft delete customer portal
     */
    public function deletePortal($id)
    {
        DB::beginTransaction();
        try {
            $portal = $this->customerPortalRepository->findOrFail($id);
            
            // Check if portal has active sessions
            if ($this->hasActiveSessions($id)) {
                throw new \Exception('Cannot delete portal with active sessions');
            }
            
            // Soft delete portal
            $this->customerPortalRepository->delete($id);
            
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error deleting customer portal: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle portal status
     */
    public function toggleStatus($id)
    {
        $portal = $this->customerPortalRepository->findOrFail($id);
        $newStatus = $portal->is_active ? false : true;
        
        return $this->customerPortalRepository->update($id, ['is_active' => $newStatus]);
    }

    /**
     * Get portal statistics
     */
    public function getStatistics()
    {
        return [
            'total_portals' => $this->customerPortalRepository->count(),
            'active_portals' => $this->customerPortalRepository->count(['is_active' => true]),
            'inactive_portals' => $this->customerPortalRepository->count(['is_active' => false]),
            'total_sessions' => $this->getTotalSessions(),
            'active_sessions' => $this->getActiveSessions(),
        ];
    }

    /**
     * Validate portal creation
     */
    private function validatePortalCreation($data)
    {
        // Check if portal name is unique
        if ($this->customerPortalRepository->exists(['name' => $data['name']])) {
            throw new \Exception('Portal name already exists');
        }
        
        // Validate URL format
        if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            throw new \Exception('Invalid URL format');
        }
    }

    /**
     * Validate portal update
     */
    private function validatePortalUpdate($portal, $data)
    {
        // Check if portal name is unique (excluding current portal)
        if (isset($data['name']) && $this->customerPortalRepository->exists(['name' => $data['name']], $portal->id)) {
            throw new \Exception('Portal name already exists');
        }
        
        // Validate URL format
        if (isset($data['url']) && !filter_var($data['url'], FILTER_VALIDATE_URL)) {
            throw new \Exception('Invalid URL format');
        }
    }

    /**
     * Create default preferences for portal
     */
    private function createDefaultPreferences($portalId)
    {
        $defaultPreferences = [
            'portal_id' => $portalId,
            'theme' => 'default',
            'language' => 'en',
            'timezone' => 'UTC',
            'notifications_enabled' => true,
            'email_notifications' => true,
            'sms_notifications' => false,
        ];
        
        // Create default preferences (assuming you have a PortalPreference model)
        // PortalPreference::create($defaultPreferences);
    }

    /**
     * Check if portal has active sessions
     */
    private function hasActiveSessions($portalId)
    {
        // Check for active sessions (assuming you have a PortalSession model)
        // return PortalSession::where('portal_id', $portalId)->where('is_active', true)->exists();
        return false; // Placeholder
    }

    /**
     * Get total sessions count
     */
    private function getTotalSessions()
    {
        // return PortalSession::count();
        return 0; // Placeholder
    }

    /**
     * Get active sessions count
     */
    private function getActiveSessions()
    {
        // return PortalSession::where('is_active', true)->count();
        return 0; // Placeholder
    }
}
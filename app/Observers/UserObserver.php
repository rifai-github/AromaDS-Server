<?php

namespace App\Observers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        $this->setAuditFields($user, 'create');
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->logAuditTrail($user, 'created', null, $user->getAttributes());
        
        // Auto-set multi_login for Administrator or Management Manager
        $this->ensureMultiLoginForAdminManager($user);
    }

    /**
     * Handle the User "updating" event.
     */
    public function updating(User $user): void
    {
        $this->setAuditFields($user, 'update');
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $this->logAuditTrail($user, 'updated', $user->getOriginal(), $user->getAttributes());
        
        // Auto-set multi_login for Administrator or Management Manager
        // Reload roles relationship to check after sync
        $user->load('roles');
        $this->ensureMultiLoginForAdminManager($user);
    }

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(User $user): void
    {
        $this->setAuditFields($user, 'delete');
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $this->logAuditTrail($user, 'deleted', $user->getOriginal(), null);
    }

    /**
     * Set audit fields based on action
     */
    protected function setAuditFields(User $user, $action)
    {
        $authUser = Auth::user();
        $userId = $authUser ? $authUser->id : null;
        $now = now();

        switch ($action) {
            case 'create':
                $user->update_by_1 = $userId;
                $user->update_at_1 = $now;
                $user->created_by = $userId;
                $user->updated_by = $userId;
                break;

            case 'update':
                // If this is the first update after creation
                if (!$user->update_by_2) {
                    $user->update_by_2 = $userId;
                    $user->update_at_2 = $now;
                } else {
                    // Subsequent updates - keep the second update info
                    $user->updated_by = $userId;
                }
                break;

            case 'delete':
                $user->delete_by = $userId;
                $user->delete_at = $now;
                break;
        }
    }

    /**
     * Log audit trail
     */
    protected function logAuditTrail(User $user, $action, $oldValues = null, $newValues = null)
    {
        $authUser = Auth::user();
        
        if (!$authUser) {
            return;
        }

        $changedFields = [];
        if ($oldValues && $newValues) {
            $changedFields = array_keys(array_diff_assoc($newValues, $oldValues));
        }

        AuditLog::create([
            'model_type' => get_class($user),
            'model_id' => $user->getKey(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'user_id' => $authUser->id,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Ensure multi_login is set to true for Administrator or Management Manager
     */
    protected function ensureMultiLoginForAdminManager(User $user)
    {
        if ($user->requiresMultiLogin() && !$user->multi_login) {
            // Use updateQuietly to avoid triggering observer again
            $user->updateQuietly(['multi_login' => true]);
        }
    }
}

<?php

namespace App\Traits;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

trait HasNotifications
{
    /**
     * Create a notification
     *
     * @param string $title
     * @param string $message
     * @param string $type
     * @param int|null $userId
     * @param array|null $data
     * @param string|null $actionUrl
     * @return Notification
     */
    public function createNotification(
        string $title,
        string $message,
        string $type = 'info',
        ?int $userId = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId ?? Auth::id(),
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
            'action_url' => $actionUrl,
            'is_read' => false,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Create info notification
     *
     * @param string $title
     * @param string $message
     * @param int|null $userId
     * @param array|null $data
     * @param string|null $actionUrl
     * @return Notification
     */
    public function createInfoNotification(
        string $title,
        string $message,
        ?int $userId = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): Notification {
        return $this->createNotification($title, $message, 'info', $userId, $data, $actionUrl);
    }

    /**
     * Create success notification
     *
     * @param string $title
     * @param string $message
     * @param int|null $userId
     * @param array|null $data
     * @param string|null $actionUrl
     * @return Notification
     */
    public function createSuccessNotification(
        string $title,
        string $message,
        ?int $userId = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): Notification {
        return $this->createNotification($title, $message, 'success', $userId, $data, $actionUrl);
    }

    /**
     * Create warning notification
     *
     * @param string $title
     * @param string $message
     * @param int|null $userId
     * @param array|null $data
     * @param string|null $actionUrl
     * @return Notification
     */
    public function createWarningNotification(
        string $title,
        string $message,
        ?int $userId = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): Notification {
        return $this->createNotification($title, $message, 'warning', $userId, $data, $actionUrl);
    }

    /**
     * Create error notification
     *
     * @param string $title
     * @param string $message
     * @param int|null $userId
     * @param array|null $data
     * @param string|null $actionUrl
     * @return Notification
     */
    public function createErrorNotification(
        string $title,
        string $message,
        ?int $userId = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): Notification {
        return $this->createNotification($title, $message, 'error', $userId, $data, $actionUrl);
    }

    /**
     * Create bulk notification for multiple users
     *
     * @param string $title
     * @param string $message
     * @param array $userIds
     * @param string $type
     * @param array|null $data
     * @param string|null $actionUrl
     * @return array
     */
    public function createBulkNotification(
        string $title,
        string $message,
        array $userIds,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null
    ): array {
        $notifications = [];
        
        foreach ($userIds as $userId) {
            $notifications[] = $this->createNotification(
                $title,
                $message,
                $type,
                $userId,
                $data,
                $actionUrl
            );
        }
        
        return $notifications;
    }

    /**
     * Create notification for department
     *
     * @param string $title
     * @param string $message
     * @param int $departmentId
     * @param string $type
     * @param array|null $data
     * @param string|null $actionUrl
     * @return array
     */
    public function createDepartmentNotification(
        string $title,
        string $message,
        int $departmentId,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null
    ): array {
        $userIds = \App\Models\User::where('department_id', $departmentId)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
            
        return $this->createBulkNotification($title, $message, $userIds, $type, $data, $actionUrl);
    }

    /**
     * Create notification for branch
     *
     * @param string $title
     * @param string $message
     * @param int $branchId
     * @param string $type
     * @param array|null $data
     * @param string|null $actionUrl
     * @return array
     */
    public function createBranchNotification(
        string $title,
        string $message,
        int $branchId,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null
    ): array {
        $userIds = \App\Models\User::where('branch_id', $branchId)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
            
        return $this->createBulkNotification($title, $message, $userIds, $type, $data, $actionUrl);
    }

    /**
     * Create notification for role
     *
     * @param string $title
     * @param string $message
     * @param string $roleName
     * @param string $type
     * @param array|null $data
     * @param string|null $actionUrl
     * @return array
     */
    public function createRoleNotification(
        string $title,
        string $message,
        string $roleName,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null
    ): array {
        $userIds = \App\Models\User::whereHas('roles', function ($query) use ($roleName) {
            $query->where('name', $roleName);
        })->where('is_active', true)->pluck('id')->toArray();
            
        return $this->createBulkNotification($title, $message, $userIds, $type, $data, $actionUrl);
    }

    /**
     * Create system-wide notification
     *
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array|null $data
     * @param string|null $actionUrl
     * @return array
     */
    public function createSystemNotification(
        string $title,
        string $message,
        string $type = 'info',
        ?array $data = null,
        ?string $actionUrl = null
    ): array {
        $userIds = \App\Models\User::where('is_active', true)->pluck('id')->toArray();
            
        return $this->createBulkNotification($title, $message, $userIds, $type, $data, $actionUrl);
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @param int|null $userId
     * @return bool
     */
    public function markNotificationAsRead(int $notificationId, ?int $userId = null): bool
    {
        $query = Notification::where('id', $notificationId);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->update(['is_read' => true, 'read_at' => now()]) > 0;
    }

    /**
     * Mark all notifications as read for user
     *
     * @param int|null $userId
     * @return int
     */
    public function markAllNotificationsAsRead(?int $userId = null): int
    {
        $query = Notification::where('is_read', false);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->update(['is_read' => true, 'read_at' => now()]);
    }
}

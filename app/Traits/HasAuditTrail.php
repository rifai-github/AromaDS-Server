<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasAuditTrail
{
    /**
     * Log an audit event
     *
     * @param string $event
     * @param Model|null $model
     * @param array|null $oldValues
     * @param array|null $newValues
     * @param string|null $description
     * @return void
     */
    public function logAuditEvent(
        string $event,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): void {
        $auditData = [
            'user_id' => Auth::id(),
            'action' => $event,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->id : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        AuditLog::create($auditData);
    }

    /**
     * Log model creation
     *
     * @param Model $model
     * @param string|null $description
     * @return void
     */
    public function logModelCreated(Model $model, ?string $description = null): void
    {
        $this->logAuditEvent(
            'created',
            $model,
            null,
            $model->toArray(),
            $description ?? "Created {$this->getModelName($model)}"
        );
    }

    /**
     * Log model update
     *
     * @param Model $model
     * @param array $oldValues
     * @param string|null $description
     * @return void
     */
    public function logModelUpdated(Model $model, array $oldValues, ?string $description = null): void
    {
        $this->logAuditEvent(
            'updated',
            $model,
            $oldValues,
            $model->toArray(),
            $description ?? "Updated {$this->getModelName($model)}"
        );
    }

    /**
     * Log model deletion
     *
     * @param Model $model
     * @param string|null $description
     * @return void
     */
    public function logModelDeleted(Model $model, ?string $description = null): void
    {
        $this->logAuditEvent(
            'deleted',
            $model,
            $model->toArray(),
            null,
            $description ?? "Deleted {$this->getModelName($model)}"
        );
    }

    /**
     * Log model restoration
     *
     * @param Model $model
     * @param string|null $description
     * @return void
     */
    public function logModelRestored(Model $model, ?string $description = null): void
    {
        $this->logAuditEvent(
            'restored',
            $model,
            null,
            $model->toArray(),
            $description ?? "Restored {$this->getModelName($model)}"
        );
    }

    /**
     * Log bulk operation
     *
     * @param string $event
     * @param string $modelType
     * @param array $modelIds
     * @param array|null $additionalData
     * @param string|null $description
     * @return void
     */
    public function logBulkOperation(
        string $event,
        string $modelType,
        array $modelIds,
        ?array $additionalData = null,
        ?string $description = null
    ): void {
        $this->logAuditEvent(
            $event,
            null,
            null,
            array_merge([
                'model_type' => $modelType,
                'model_ids' => $modelIds,
                'count' => count($modelIds),
            ], $additionalData ?? []),
            $description ?? "Bulk {$event} on {$modelType} ({count($modelIds)} items)"
        );
    }

    /**
     * Log login event
     *
     * @param string $event
     * @param int $userId
     * @param bool $success
     * @param string|null $failureReason
     * @return void
     */
    public function logLoginEvent(
        string $event,
        int $userId,
        bool $success = true,
        ?string $failureReason = null
    ): void {
        $this->logAuditEvent(
            $event,
            null,
            null,
            [
                'target_user_id' => $userId,
                'success' => $success,
                'failure_reason' => $failureReason,
            ],
            $success ? "User login successful" : "User login failed: {$failureReason}"
        );
    }

    /**
     * Log permission change
     *
     * @param int $targetUserId
     * @param array $oldPermissions
     * @param array $newPermissions
     * @param string|null $description
     * @return void
     */
    public function logPermissionChange(
        int $targetUserId,
        array $oldPermissions,
        array $newPermissions,
        ?string $description = null
    ): void {
        $this->logAuditEvent(
            'permission_changed',
            null,
            ['permissions' => $oldPermissions],
            [
                'target_user_id' => $targetUserId,
                'permissions' => $newPermissions,
            ],
            $description ?? "Permission changed for user ID {$targetUserId}"
        );
    }

    /**
     * Log role assignment
     *
     * @param int $targetUserId
     * @param array $oldRoles
     * @param array $newRoles
     * @param string|null $description
     * @return void
     */
    public function logRoleAssignment(
        int $targetUserId,
        array $oldRoles,
        array $newRoles,
        ?string $description = null
    ): void {
        $this->logAuditEvent(
            'role_assigned',
            null,
            ['roles' => $oldRoles],
            [
                'target_user_id' => $targetUserId,
                'roles' => $newRoles,
            ],
            $description ?? "Role assigned to user ID {$targetUserId}"
        );
    }

    /**
     * Get model name for audit log
     *
     * @param Model $model
     * @return string
     */
    private function getModelName(Model $model): string
    {
        return class_basename($model);
    }
}

<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use App\Models\AuditLog;

trait HasComprehensiveAuditTrail
{
    /**
     * Boot the trait
     */
    protected static function bootHasComprehensiveAuditTrail()
    {
        static::creating(function ($model) {
            $model->setAuditFields('create');
        });

        static::created(function ($model) {
            $model->logAuditTrail('created', null, $model->getAttributes());
        });

        static::updating(function ($model) {
            $model->setAuditFields('update');
            $model->logAuditTrail('updated', $model->getOriginal(), $model->getAttributes());
        });

        static::deleting(function ($model) {
            $model->setAuditFields('delete');
            $model->logAuditTrail('deleted', $model->getOriginal(), null);
        });
    }

    /**
     * Set audit fields based on action
     */
    protected function setAuditFields($action)
    {
        $user = Auth::user();
        $userId = $user ? $user->id : null;
        $now = now();

        switch ($action) {
            case 'create':
                $this->created_by = $userId;
                $this->updated_by = $userId;
                break;

            case 'update':
                $this->updated_by = $userId;
                break;

            case 'delete':
                $this->delete_by = $userId;
                $this->delete_at = $now;
                break;
        }
    }

    /**
     * Log audit trail
     */
    protected function logAuditTrail($action, $oldValues = null, $newValues = null)
    {
        $user = Auth::user();
        
        if (!$user) {
            return;
        }

        $changedFields = [];
        if ($oldValues && $newValues) {
            // Handle array comparison properly by converting arrays to JSON strings for comparison
            $oldValuesForComparison = $this->prepareValuesForComparison($oldValues);
            $newValuesForComparison = $this->prepareValuesForComparison($newValues);
            $changedFields = array_keys(array_diff_assoc($newValuesForComparison, $oldValuesForComparison));
        }

        AuditLog::create([
            'model_type' => get_class($this),
            'model_id' => $this->getKey(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'user_id' => $user->id,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'page_name' => $this->getCurrentPageName(),
            'module_name' => $this->getCurrentModuleName(),
        ]);
    }

    /**
     * Get audit trail for this record
     */
    public function auditTrails()
    {
        return $this->hasMany(AuditLog::class, 'model_id')
                    ->where('model_type', get_class($this))
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Get creator
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get updater
     */
    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Get first updater
     */
    public function firstUpdater()
    {
        return $this->belongsTo(\App\Models\User::class, 'update_by_1');
    }

    /**
     * Get second updater
     */
    public function secondUpdater()
    {
        return $this->belongsTo(\App\Models\User::class, 'update_by_2');
    }

    /**
     * Get deleter
     */
    public function deleter()
    {
        return $this->belongsTo(\App\Models\User::class, 'delete_by');
    }

    /**
     * Get current page name from request
     */
    public function getCurrentPageName()
    {
        $request = request();
        $route = $request->route();
        
        if ($route) {
            $routeName = $route->getName();
            if ($routeName) {
                // Extract page name from route name
                $parts = explode('.', $routeName);
                
                // If the last part is "index" and we have more parts, use the second to last part
                if (end($parts) === 'index' && count($parts) >= 2) {
                    return $parts[count($parts) - 2];
                }
                
                return end($parts);
            }
        }
        
        // Fallback to URL path
        $path = $request->path();
        $segments = explode('/', $path);
        
        // Remove empty segments and get the last segment
        $segments = array_filter($segments);
        
        if (empty($segments)) {
            return 'dashboard';
        }
        
        return end($segments);
    }

    /**
     * Get current module name from request
     */
    public function getCurrentModuleName()
    {
        $request = request();
        $route = $request->route();
        
        if ($route) {
            $routeName = $route->getName();
            if ($routeName) {
                // Extract module name from route name
                $parts = explode('.', $routeName);
                if (count($parts) >= 2) {
                    return $parts[0]; // First part is usually the module
                }
            }
        }
        
        // Fallback to URL path
        $path = $request->path();
        $segments = explode('/', $path);
        
        // Remove empty segments
        $segments = array_filter($segments);
        
        if (empty($segments)) {
            return 'dashboard';
        }
        
        // Map common paths to modules
        $moduleMap = [
            'dashboard' => 'dashboard',
            'users' => 'system',
            'roles' => 'system',
            'permissions' => 'system',
            'audit-trails' => 'system',
            'customers' => 'marketing',
            'prospects' => 'marketing',
            'surveys' => 'marketing',
            'quotations' => 'marketing',
            'contracts' => 'marketing',
            'invoices' => 'finance',
            'reports' => 'reports',
            'settings' => 'system',
            'pipeline' => 'marketing',
            'job-advices' => 'marketing',
        ];
        
        $firstSegment = $segments[0];
        return $moduleMap[$firstSegment] ?? $firstSegment;
    }

    /**
     * Prepare values for comparison by converting arrays to JSON strings
     */
    protected function prepareValuesForComparison($values)
    {
        $prepared = [];
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $prepared[$key] = json_encode($value);
            } else {
                $prepared[$key] = $value;
            }
        }
        return $prepared;
    }
}

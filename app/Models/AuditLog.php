<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\AutoFilterable;

class AuditLog extends Model
{
    use HasFactory, AutoFilterable;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'changed_fields',
        'ip_address',
        'user_agent',
        'page_name',
        'module_name',
        'reference_number',
    ];

    /**
     * QA 25 Nov 2026: log hanya menulis "job advice action delete" tanpa nomor dokumen, jadi tidak
     * ketahuan dokumen mana yang dihapus/diubah/dibuka. Nomor dokumen diambil dari isi record
     * (atribut pertama yang terisi dari daftar ini) saat baris log dibuat.
     */
    public const REFERENCE_KEYS = [
        'reference', // dipakai PageViewLogging untuk halaman detail
        'job_advice_number', 'job_number', 'invoice_number', 'contract_number', 'quotation_number',
        'survey_number', 'issue_number', 'issuing_number', 'receiving_number', 'request_number',
        'return_number', 'transfer_number', 'adjustment_number', 'opname_number', 'termination_number',
        'document_number', 'transaction_number', 'receipt_number', 'faktur_number', 'billing_group_name',
        'customer_code', 'serial_number', 'rental_code', 'sku',
    ];

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log) {
            if (! self::supportsReferenceNumber() || filled($log->reference_number)) {
                return;
            }

            $reference = self::referenceFromValues($log->new_values) ?? self::referenceFromValues($log->old_values);
            if ($reference !== null) {
                $log->reference_number = $reference;
            }
        });
    }

    public static function supportsReferenceNumber(): bool
    {
        static $supported = null;

        // Fixture test membuat ulang tabel audit_logs dengan/ tanpa kolom ini; jangan di-cache di sana.
        if (app()->runningUnitTests()) {
            return \Illuminate\Support\Facades\Schema::hasColumn('audit_logs', 'reference_number');
        }

        return $supported ??= \Illuminate\Support\Facades\Schema::hasColumn('audit_logs', 'reference_number');
    }

    public static function referenceFromValues($values): ?string
    {
        if (is_string($values)) {
            $values = json_decode($values, true);
        }

        if (! is_array($values)) {
            return null;
        }

        foreach (self::REFERENCE_KEYS as $key) {
            $value = $values[$key] ?? null;
            if (is_scalar($value) && trim((string) $value) !== '') {
                return mb_substr(trim((string) $value), 0, 100);
            }
        }

        return null;
    }

    /** Nomor transaksi untuk tampilan; baris lama tanpa kolom dibaca dari isi log-nya. */
    public function getDisplayReferenceNumberAttribute(): ?string
    {
        return ($this->attributes['reference_number'] ?? null)
            ?: (self::referenceFromValues($this->old_values) ?? self::referenceFromValues($this->new_values));
    }

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function model()
    {
        return $this->morphTo('model', 'model_type', 'model_id');
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForModel($query, $modelType, $modelId = null)
    {
        $query = $query->where('model_type', $modelType);
        
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        
        return $query;
    }

    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function getActionDescriptionAttribute()
    {
        $descriptions = [
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            'login' => 'Logged In',
            'logout' => 'Logged Out',
            'password_changed' => 'Password Changed',
            'role_assigned' => 'Role Assigned',
            'permission_granted' => 'Permission Granted',
            'file_uploaded' => 'File Uploaded',
            'file_downloaded' => 'File Downloaded',
            'export' => 'Data Exported',
            'import' => 'Data Imported',
            'page_view' => 'Page Viewed',
            'report_generated' => 'Report Generated',
            'print' => 'Document Printed',
            'email_sent' => 'Email Sent',
            'sms_sent' => 'SMS Sent',
            'notification_sent' => 'Notification Sent'
        ];

        return $descriptions[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    public function getModelNameAttribute()
    {
        if (!$this->model_type) {
            return 'System';
        }

        $modelNames = [
            'App\\Models\\User' => 'User',
            'App\\Models\\Role' => 'Role',
            'App\\Models\\Permission' => 'Permission',
            'App\\Models\\Department' => 'Department',
            'App\\Models\\Branch' => 'Branch',
            'App\\Models\\Customer' => 'Customer',
            'App\\Models\\Product' => 'Product',
            'App\\Models\\Invoice' => 'Invoice',
            'App\\Models\\Contract' => 'Contract',
            'App\\Models\\Prospect' => 'Prospect',
            'App\\Models\\Survey' => 'Survey',
            'App\\Models\\Quotation' => 'Quotation'
        ];

        return $modelNames[$this->model_type] ?? class_basename($this->model_type);
    }

    public function getMenuLabelAttribute()
    {
        // Custom mapping for page names (mostly from Middleware)
        $pageMappings = [
            'pipeline' => 'Marketing Pipeline',
            'prospects' => 'Marketing Prospect',
            'surveys' => 'Marketing Survey',
            'job-advices' => 'Job Advice',
            'quotations' => 'Marketing Quotation',
            'contracts' => 'Marketing Contract',
            'invoices' => 'Finance Invoice',
            'inventory-receivings' => 'Inventory Receiving',
            'inventory-issuings' => 'Inventory Issuing',
            'inventory-requests' => 'Inventory Request',
            'stock-opnames' => 'Stock Opname',
            'stock-adjustments' => 'Stock Adjustment',
            'users' => 'User Management',
            'roles' => 'Role Management',
            'audit-trails' => 'Audit Trail',
            'login-history' => 'Login History',
            'dashboard' => 'Dashboard',
            'profile' => 'User Profile',
        ];

        if ($this->page_name) {
            $normalizedPage = str_replace(['/', '_'], '-', strtolower($this->page_name));
            if (isset($pageMappings[$normalizedPage])) {
                return $pageMappings[$normalizedPage];
            }
            
            // Handle generic names like "index" if they slip through
            if ($this->page_name === 'index' && $this->module_name) {
                return ucfirst($this->module_name);
            }
            
            // If action is page_view but we have a nice label, it's already handled above
            return ucfirst($this->page_name);
        }

        if (!$this->model_type || $this->model_type === 'PageView') {
            if ($this->action === 'page_view') {
                return 'Page View';
            }
            return 'System';
        }

        $menuLabels = [
            'App\\Models\\JobSchedule' => 'Job Schedule',
            'App\\Models\\JobAdvice' => 'Job Advice',
            'App\\Models\\Customer' => 'Customer',
            'App\\Models\\Building' => 'Building',
            'App\\Models\\Prospect' => 'Prospect',
            'App\\Models\\Quotation' => 'Quotation',
            'App\\Models\\Contract' => 'Contract',
            'App\\Models\\Invoice' => 'Invoice',
            'App\\Models\\Survey' => 'Survey',
            'App\\Models\\MasterOption' => 'Master Option',
            'App\\Models\\User' => 'User Management',
            'App\\Models\\Role' => 'Role Management',
            'App\\Models\\Product' => 'Product Inventory',
            'App\\Models\\Warehouse' => 'Warehouse',
            'App\\Models\\MaterialIssue' => 'Material Issue',
        ];

        return $menuLabels[$this->model_type] ?? $this->model_name;
    }

    public function getChangesAttribute()
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        return $changes;
    }

    public function hasChanges($changes = null, $attributes = null)
    {
        if ($changes !== null) {
            return parent::hasChanges($changes, $attributes);
        }
        return !empty($this->changes);
    }

    public function getFormattedChangesAttribute()
    {
        $changes = $this->changes;
        $formatted = [];

        foreach ($changes as $field => $change) {
            $old = $change['old'];
            $new = $change['new'];
            
            // Format dates if they look like timestamp strings
            if (is_string($old) && preg_match('/^\d{4}-\d{2}-\d{2}/', $old)) {
                $old = formatIndonesianDateTime($old);
            }
            if (is_string($new) && preg_match('/^\d{4}-\d{2}-\d{2}/', $new)) {
                $new = formatIndonesianDateTime($new);
            }
            
            $formatted[] = "{$field}: '{$old}' → '{$new}'";
        }

        return implode(', ', $formatted);
    }

    // Static methods for logging
    public static function log($action, $model = null, $oldValues = null, $newValues = null, $userId = null)
    {
        $userId = $userId ?: auth()->id();
        
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->id : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    public static function logUserAction($action, $userId = null)
    {
        return self::log($action, null, null, null, $userId);
    }

    public static function logModelChange($action, $model, $oldValues = null, $newValues = null)
    {
        return self::log($action, $model, $oldValues, $newValues);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'report_name',
        'report_type',
        'module',
        'description',
        'parameters',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function reportDetails()
    {
        return $this->hasMany(ReportDetail::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('report_type', $type);
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('report_name', 'like', "%{$name}%");
    }

    // Accessors
    public function getTypeTextAttribute()
    {
        $types = [
            'warehouse' => 'Warehouse',
            'operational' => 'Operational',
            'finance' => 'Finance',
            'marketing' => 'Marketing',
            'system' => 'System'
        ];
        return $types[$this->report_type] ?? $this->report_type;
    }

    public function getModuleTextAttribute()
    {
        $modules = [
            'marketing' => 'Marketing',
            'operational' => 'Operational',
            'finance' => 'Finance',
            'warehouse' => 'Warehouse',
            'company' => 'Company',
            'system' => 'System',
            'report' => 'Report',
            'setting' => 'Setting'
        ];
        return $modules[$this->module] ?? $this->module;
    }

    public function getIsActiveAttribute()
    {
        return $this->is_active;
    }

    // Methods
    public function getReportData($parameters = [])
    {
        // This method would contain the logic to generate report data
        // based on the report type and parameters
        switch ($this->report_type) {
            case 'warehouse':
                return $this->getWarehouseReportData($parameters);
            case 'operational':
                return $this->getOperationalReportData($parameters);
            case 'finance':
                return $this->getFinanceReportData($parameters);
            case 'marketing':
                return $this->getMarketingReportData($parameters);
            default:
                return [];
        }
    }

    private function getWarehouseReportData($parameters)
    {
        // Implementation for warehouse reports
        return [];
    }

    private function getOperationalReportData($parameters)
    {
        // Implementation for operational reports
        return [];
    }

    private function getFinanceReportData($parameters)
    {
        // Implementation for finance reports
        return [];
    }

    private function getMarketingReportData($parameters)
    {
        // Implementation for marketing reports
        return [];
    }
}

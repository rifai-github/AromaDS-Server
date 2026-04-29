<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;
use App\Http\Traits\AutoFilterable;

class MarketingPipeline extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail, AutoFilterable;

    protected $fillable = [
        'pipeline_number',
        'visit_date',
        'follow_up_date', // Field yang diminta - hidden saat create, muncul saat edit
        'company_name', // Nama perusahaan (customer)
        'company_address', // Alamat perusahaan
        'pic_name', // Nama PIC
        'pic_phone', // Nomor kontak
        'pic_email', // Email kontak
        'visit_result', // Kegiatan/agenda
        'agenda_list', // List agenda/kegiatan
        'contract_list', // List contract untuk follow up
        'quotation_list', // List quotation untuk follow up
        'survey_list', // List survey untuk follow up
        'job_advice_list', // List job advice untuk follow up
        'status',
        'notes',
        'customer_id', // Building Marketing Enhancement
        'building_id', // Building ID for address
        'reference', // Reference manual input (replaces reference_user_id)
        'reference_user_id', // User referensi prospek (kept for backwards compatibility)
        'created_by',
        'updated_by',
        'update_by_1',
        'update_at_1',
        'update_by_2',
        'update_at_2',
        'delete_by',
        'delete_at'
    ];

    protected $casts = [
        'visit_date' => 'datetime',
        'follow_up_date' => 'datetime',
        'agenda_list' => 'array',
        'contract_list' => 'array',
        'quotation_list' => 'array',
        'survey_list' => 'array',
        'job_advice_list' => 'array'
    ];

    // Relationships
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function referenceUser()
    {
        return $this->belongsTo(User::class, 'reference_user_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function buildings()
    {
        return $this->belongsToMany(Building::class, 'marketing_pipeline_buildings')
            ->withPivot('assigned_at', 'assigned_by')
            ->withTimestamps();
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByAssignedTo($query, $assignedToId)
    {
        return $query->where('assigned_to', $assignedToId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('visit_date', [$startDate, $endDate]);
    }

    public function scopeProspect($query)
    {
        return $query->where('status', 'prospect');
    }

    public function scopeQualified($query)
    {
        return $query->where('status', 'qualified');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'prospect' => 'Prospect',
            'qualified' => 'Qualified',
            'unqualified' => 'Unqualified',
            'converted' => 'Converted',
            default => 'Unknown'
        };
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'prospect' => 'badge-warning',
            'qualified' => 'badge-info',
            'unqualified' => 'badge-danger',
            'converted' => 'badge-success',
            default => 'badge-light'
        };
    }

    public function getFormattedVisitDateAttribute()
    {
        return $this->visit_date->format('d M Y H:i');
    }

    public function getDaysSinceVisitAttribute()
    {
        return $this->visit_date->diffInDays(now());
    }

    // Helper methods
    public function isProspect()
    {
        return $this->status === 'prospect';
    }

    public function isQualified()
    {
        return $this->status === 'qualified';
    }

    public function isConverted()
    {
        return $this->status === 'converted';
    }

    public function isUnqualified()
    {
        return $this->status === 'unqualified';
    }

    // Auto-generate pipeline number (if not provided by controller)
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($pipeline) {
            // Only generate if pipeline_number is not already set by controller
            if (empty($pipeline->pipeline_number)) {
                $year = date('Y');
                $lastPipeline = static::withTrashed()
                    ->whereYear('created_at', $year)
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($lastPipeline && $lastPipeline->pipeline_number) {
                    $lastNumber = (int) substr($lastPipeline->pipeline_number, -4);
                    $newNumber = $lastNumber + 1;
                } else {
                    $newNumber = 1;
                }
                
                $pipeline->pipeline_number = 'PL' . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}

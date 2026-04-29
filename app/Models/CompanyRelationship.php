<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyRelationship extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'related_company_id',
        'relationship_type',
        'description',
        'is_active',
        'start_date',
        'end_date',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function relatedCompany()
    {
        return $this->belongsTo(Company::class, 'related_company_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByRelatedCompany($query, $relatedCompanyId)
    {
        return $query->where('related_company_id', $relatedCompanyId);
    }

    public function scopeByRelationshipType($query, $relationshipType)
    {
        return $query->where('relationship_type', $relationshipType);
    }

    public function scopeCurrent($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('end_date', '>=', now())
                    ->where('end_date', '<=', now()->addDays($days));
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where(function ($q3) use ($endDate) {
                         $q3->whereNull('end_date')
                            ->orWhere('end_date', '>=', $endDate);
                     });
              });
        });
    }

    // Accessors
    public function getRelationshipTypeTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->relationship_type));
    }

    public function getIsActiveTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getFormattedStartDateAttribute()
    {
        return $this->start_date ? $this->start_date->format('d M Y') : null;
    }

    public function getFormattedEndDateAttribute()
    {
        return $this->end_date ? $this->end_date->format('d M Y') : null;
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d M Y H:i');
    }

    public function getDurationAttribute()
    {
        if (!$this->start_date) {
            return null;
        }

        $endDate = $this->end_date ?? now();
        return $this->start_date->diffInDays($endDate);
    }

    public function getDurationInMonthsAttribute()
    {
        if (!$this->start_date) {
            return null;
        }

        $endDate = $this->end_date ?? now();
        return $this->start_date->diffInMonths($endDate);
    }

    public function getDurationInYearsAttribute()
    {
        if (!$this->start_date) {
            return null;
        }

        $endDate = $this->end_date ?? now();
        return $this->start_date->diffInYears($endDate);
    }

    public function getStatusAttribute()
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return 'Expired';
        }

        if ($this->end_date && $this->end_date->isFuture() && $this->end_date->diffInDays(now()) <= 30) {
            return 'Expiring Soon';
        }

        return 'Active';
    }

    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'Active':
                return 'success';
            case 'Expiring Soon':
                return 'warning';
            case 'Expired':
                return 'danger';
            case 'Inactive':
                return 'secondary';
            default:
                return 'primary';
        }
    }

    // Business Logic Methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function isCurrent()
    {
        return $this->is_active && 
               (!$this->end_date || $this->end_date->isFuture());
    }

    public function isExpired()
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function isExpiringSoon($days = 30)
    {
        return $this->end_date && 
               $this->end_date->isFuture() && 
               $this->end_date->diffInDays(now()) <= $days;
    }

    public function activate()
    {
        $this->is_active = true;
        $this->save();
    }

    public function deactivate()
    {
        $this->is_active = false;
        $this->save();
    }

    public function extend($newEndDate)
    {
        $this->end_date = $newEndDate;
        $this->save();
    }

    public function terminate()
    {
        $this->end_date = now();
        $this->save();
    }

    public function getDaysUntilExpiry()
    {
        if (!$this->end_date) {
            return null;
        }

        return $this->end_date->diffInDays(now());
    }

    public function getDaysSinceStart()
    {
        if (!$this->start_date) {
            return null;
        }

        return $this->start_date->diffInDays(now());
    }

    // Static Methods
    public static function getRelationshipTypes()
    {
        return [
            'parent' => 'Parent Company',
            'subsidiary' => 'Subsidiary',
            'partner' => 'Business Partner',
            'supplier' => 'Supplier',
            'customer' => 'Customer',
            'competitor' => 'Competitor',
            'affiliate' => 'Affiliate',
            'joint_venture' => 'Joint Venture',
            'franchise' => 'Franchise',
            'distributor' => 'Distributor',
            'other' => 'Other'
        ];
    }

    public static function getCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)->count();
    }

    public static function getCountByRelationshipType($companyId, $relationshipType)
    {
        return self::where('company_id', $companyId)
                  ->where('relationship_type', $relationshipType)
                  ->count();
    }

    public static function getActiveCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->where('is_active', true)
                  ->count();
    }

    public static function getExpiringSoonCount($days = 30)
    {
        return self::where('is_active', true)
                  ->where('end_date', '>=', now())
                  ->where('end_date', '<=', now()->addDays($days))
                  ->count();
    }

    public static function getExpiredCount()
    {
        return self::where('end_date', '<', now())->count();
    }

    public static function getCurrentCount()
    {
        return self::where('is_active', true)
                  ->where(function ($q) {
                      $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                  })
                  ->count();
    }

    public static function getRelationshipStatistics()
    {
        return [
            'total' => self::count(),
            'active' => self::where('is_active', true)->count(),
            'inactive' => self::where('is_active', false)->count(),
            'current' => self::getCurrentCount(),
            'expired' => self::getExpiredCount(),
            'expiring_soon' => self::getExpiringSoonCount(30)
        ];
    }

    public static function getCompanyRelationshipStatistics($companyId)
    {
        return [
            'total_relationships' => self::getCountByCompany($companyId),
            'active_relationships' => self::getActiveCountByCompany($companyId),
            'expiring_soon' => self::where('company_id', $companyId)
                               ->where('is_active', true)
                               ->where('end_date', '>=', now())
                               ->where('end_date', '<=', now()->addDays(30))
                               ->count(),
            'by_type' => self::where('company_id', $companyId)
                            ->selectRaw('relationship_type, COUNT(*) as count')
                            ->groupBy('relationship_type')
                            ->pluck('count', 'relationship_type')
                            ->toArray()
        ];
    }

    public static function getExpiringRelationships($days = 30)
    {
        return self::where('is_active', true)
                  ->where('end_date', '>=', now())
                  ->where('end_date', '<=', now()->addDays($days))
                  ->with(['company', 'relatedCompany'])
                  ->orderBy('end_date', 'asc')
                  ->get();
    }

    public static function getExpiredRelationships()
    {
        return self::where('end_date', '<', now())
                  ->with(['company', 'relatedCompany'])
                  ->orderBy('end_date', 'desc')
                  ->get();
    }

    public static function getRecentRelationships($days = 30)
    {
        return self::where('created_at', '>=', now()->subDays($days))
                  ->with(['company', 'relatedCompany'])
                  ->orderBy('created_at', 'desc')
                  ->get();
    }
}

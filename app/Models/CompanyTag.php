<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyTag extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'color',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function companyTagAssignments()
    {
        return $this->hasMany(CompanyTagAssignment::class);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_tag_assignments', 'tag_id', 'company_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByColor($query, $color)
    {
        return $query->where('color', $color);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopePopular($query)
    {
        return $query->withCount('companyTagAssignments')
                    ->orderBy('company_tag_assignments_count', 'desc');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getIsActiveTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d M Y H:i');
    }

    public function getUsageCountAttribute()
    {
        return $this->companyTagAssignments()->count();
    }

    public function getColorHexAttribute()
    {
        return $this->color ? '#' . $this->color : '#6c757d';
    }

    public function getColorRgbAttribute()
    {
        if (!$this->color) {
            return 'rgb(108, 117, 125)';
        }
        
        $hex = $this->color;
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "rgb({$r}, {$g}, {$b})";
    }

    // Business Logic Methods
    public function isActive()
    {
        return $this->is_active;
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

    public function getUsageCount()
    {
        return $this->companyTagAssignments()->count();
    }

    public function isUsed()
    {
        return $this->getUsageCount() > 0;
    }

    public function canDelete()
    {
        return !$this->isUsed();
    }

    public function assignToCompany($companyId, $assignedBy = null)
    {
        return $this->companyTagAssignments()->create([
            'company_id' => $companyId,
            'assigned_by' => $assignedBy,
            'assigned_at' => now()
        ]);
    }

    public function removeFromCompany($companyId)
    {
        return $this->companyTagAssignments()
                   ->where('company_id', $companyId)
                   ->delete();
    }

    public function isAssignedToCompany($companyId)
    {
        return $this->companyTagAssignments()
                   ->where('company_id', $companyId)
                   ->exists();
    }

    // Static Methods
    public static function getDefaultColors()
    {
        return [
            '007bff' => 'Blue',
            '28a745' => 'Green',
            'ffc107' => 'Yellow',
            'dc3545' => 'Red',
            '6f42c1' => 'Purple',
            'fd7e14' => 'Orange',
            '20c997' => 'Teal',
            '6c757d' => 'Gray',
            'e83e8c' => 'Pink',
            '17a2b8' => 'Cyan'
        ];
    }

    public static function getPopularTags($limit = 10)
    {
        return self::active()
                  ->withCount('companyTagAssignments')
                  ->orderBy('company_tag_assignments_count', 'desc')
                  ->limit($limit)
                  ->get();
    }

    public static function getCountByColor($color)
    {
        return self::where('color', $color)->count();
    }

    public static function getActiveCount()
    {
        return self::where('is_active', true)->count();
    }

    public static function getInactiveCount()
    {
        return self::where('is_active', false)->count();
    }

    public static function getTotalCount()
    {
        return self::count();
    }

    public static function getUnusedTags()
    {
        return self::whereDoesntHave('companyTagAssignments')->get();
    }

    public static function getMostUsedTags($limit = 5)
    {
        return self::withCount('companyTagAssignments')
                  ->orderBy('company_tag_assignments_count', 'desc')
                  ->limit($limit)
                  ->get();
    }

    public static function getRecentTags($days = 7)
    {
        return self::where('created_at', '>=', now()->subDays($days))
                  ->orderBy('created_at', 'desc')
                  ->get();
    }

    public static function searchTags($search, $limit = 10)
    {
        return self::active()
                  ->search($search)
                  ->limit($limit)
                  ->get();
    }

    public static function getTagsByCompany($companyId)
    {
        return self::whereHas('companyTagAssignments', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->get();
    }

    public static function getTagStatistics()
    {
        return [
            'total' => self::count(),
            'active' => self::where('is_active', true)->count(),
            'inactive' => self::where('is_active', false)->count(),
            'unused' => self::whereDoesntHave('companyTagAssignments')->count(),
            'most_used' => self::withCount('companyTagAssignments')
                              ->orderBy('company_tag_assignments_count', 'desc')
                              ->first()
        ];
    }
}

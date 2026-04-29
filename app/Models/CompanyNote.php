<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'note_type',
        'title',
        'content',
        'is_private',
        'is_important',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_important' => 'boolean'
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
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
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByNoteType($query, $noteType)
    {
        return $query->where('note_type', $noteType);
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%");
        });
    }

    // Accessors
    public function getNoteTypeTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->note_type));
    }

    public function getIsPrivateTextAttribute()
    {
        return $this->is_private ? 'Private' : 'Public';
    }

    public function getIsImportantTextAttribute()
    {
        return $this->is_important ? 'Important' : 'Normal';
    }

    public function getShortContentAttribute()
    {
        return strlen($this->content) > 100 
            ? substr($this->content, 0, 100) . '...' 
            : $this->content;
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d M Y H:i');
    }

    // Business Logic Methods
    public function isPrivate()
    {
        return $this->is_private;
    }

    public function isImportant()
    {
        return $this->is_important;
    }

    public function makePrivate()
    {
        $this->is_private = true;
        $this->save();
    }

    public function makePublic()
    {
        $this->is_private = false;
        $this->save();
    }

    public function markAsImportant()
    {
        $this->is_important = true;
        $this->save();
    }

    public function markAsNormal()
    {
        $this->is_important = false;
        $this->save();
    }

    public function canView($userId)
    {
        if (!$this->is_private) {
            return true;
        }
        
        return $this->created_by == $userId;
    }

    public function canEdit($userId)
    {
        return $this->created_by == $userId;
    }

    public function canDelete($userId)
    {
        return $this->created_by == $userId;
    }

    // Static Methods
    public static function getNoteTypes()
    {
        return [
            'general' => 'General',
            'meeting' => 'Meeting',
            'call' => 'Call',
            'email' => 'Email',
            'task' => 'Task',
            'reminder' => 'Reminder',
            'follow_up' => 'Follow Up',
            'other' => 'Other'
        ];
    }

    public static function getCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)->count();
    }

    public static function getCountByNoteType($companyId, $noteType)
    {
        return self::where('company_id', $companyId)
                  ->where('note_type', $noteType)
                  ->count();
    }

    public static function getImportantCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->where('is_important', true)
                  ->count();
    }

    public static function getRecentCountByCompany($companyId, $days = 7)
    {
        return self::where('company_id', $companyId)
                  ->where('created_at', '>=', now()->subDays($days))
                  ->count();
    }

    public static function getCountByUser($userId)
    {
        return self::where('created_by', $userId)->count();
    }

    public static function getPublicCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->where('is_private', false)
                  ->count();
    }

    public static function getPrivateCountByCompany($companyId)
    {
        return self::where('company_id', $companyId)
                  ->where('is_private', true)
                  ->count();
    }
}

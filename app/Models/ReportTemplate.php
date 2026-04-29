<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'template_name',
        'template_type',
        'template_config',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'template_config' => 'array',
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

    public function fields()
    {
        return $this->hasMany(ReportTemplateField::class, 'template_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('template_type', $type);
    }

    public function scopeByName($query, $name)
    {
        return $query->where('template_name', 'like', "%{$name}%");
    }

    // Accessors
    public function getTypeTextAttribute()
    {
        $types = [
            'pdf' => 'PDF',
            'excel' => 'Excel',
            'csv' => 'CSV',
            'html' => 'HTML'
        ];
        return $types[$this->template_type] ?? $this->template_type;
    }

    public function getFieldCountAttribute()
    {
        return $this->fields()->count();
    }
}

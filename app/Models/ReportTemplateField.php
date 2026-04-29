<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportTemplateField extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'field_name',
        'field_type',
        'field_config',
        'is_required'
    ];

    protected $casts = [
        'field_config' => 'array',
        'is_required' => 'boolean'
    ];

    // Relationships
    public function template()
    {
        return $this->belongsTo(ReportTemplate::class, 'template_id');
    }

    // Scopes
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('field_type', $type);
    }

    // Accessors
    public function getTypeTextAttribute()
    {
        $types = [
            'text' => 'Text',
            'number' => 'Number',
            'date' => 'Date',
            'boolean' => 'Boolean'
        ];
        return $types[$this->field_type] ?? $this->field_type;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractRemark extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_id',
        'remark_type',
        'remark_content',
        'is_editable_after_approval',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_editable_after_approval' => 'boolean',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('remark_type', $type);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEditableAfterApproval($query)
    {
        return $query->where('is_editable_after_approval', true);
    }

    // Accessors
    public function getRemarkTypeLabelAttribute()
    {
        $types = [
            'contract' => 'Contract Remark',
            'operation' => 'Operation Remark',
            'finance' => 'Finance Remark',
            'marketing' => 'Marketing Remark' // Sales = Marketing di PT Pink (MOM6)
        ];

        return $types[$this->remark_type] ?? $this->remark_type;
    }

    public function getRemarkTypeBadgeAttribute()
    {
        $badges = [
            'contract' => 'bg-blue-100 text-blue-800',
            'operation' => 'bg-green-100 text-green-800',
            'finance' => 'bg-yellow-100 text-yellow-800',
            'marketing' => 'bg-purple-100 text-purple-800' // Purple badge untuk Marketing
        ];

        return $badges[$this->remark_type] ?? 'bg-gray-100 text-gray-800';
    }
}
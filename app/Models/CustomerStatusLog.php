<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'field_changed',
        'old_value',
        'new_value',
        'reason',
        'changed_by',
        'changed_at'
    ];

    protected $casts = [
        'old_value' => 'boolean',
        'new_value' => 'boolean',
        'changed_at' => 'datetime'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // Accessors
    public function getOldValueTextAttribute()
    {
        return $this->old_value ? 'Yes' : 'No';
    }

    public function getNewValueTextAttribute()
    {
        return $this->new_value ? 'Yes' : 'No';
    }

    public function getFieldChangedTextAttribute()
    {
        return match($this->field_changed) {
            'is_pkp' => 'PKP Status',
            'is_active' => 'Active Status',
            default => ucfirst(str_replace('_', ' ', $this->field_changed))
        };
    }
}
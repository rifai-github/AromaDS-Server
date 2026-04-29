<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuotationPic extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quotation_id',
        'customer_contact_id',
        'role',
        'is_primary',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'is_primary' => 'boolean'
    ];

    // Relationships
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customerContact()
    {
        return $this->belongsTo(CustomerContact::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    // Methods
    public function setAsPrimary()
    {
        // Remove primary from other PICs
        self::where('quotation_id', $this->quotation_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this as primary
        $this->update(['is_primary' => true]);
    }

    // Accessors
    public function getRoleBadgeAttribute()
    {
        $badges = [
            'Primary' => 'primary',
            'Secondary' => 'secondary',
            'Finance' => 'success',
            'Technical' => 'info',
            'Decision Maker' => 'warning'
        ];

        $class = $badges[$this->role] ?? 'secondary';
        return '<span class="badge bg-' . $class . '">' . $this->role . '</span>';
    }
}


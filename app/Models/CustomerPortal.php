<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditTrail;

class CustomerPortal extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $fillable = [
        'customer_id',
        'portal_url',
        'username',
        'password',
        'is_active'
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sessions()
    {
        return $this->hasMany(PortalSession::class, 'customer_id', 'customer_id');
    }

    public function activities()
    {
        return $this->hasMany(PortalActivity::class, 'customer_id', 'customer_id');
    }

    public function documents()
    {
        return $this->hasMany(PortalDocument::class, 'customer_id', 'customer_id');
    }

    public function notifications()
    {
        return $this->hasMany(PortalNotification::class, 'customer_id', 'customer_id');
    }

    public function preferences()
    {
        return $this->hasMany(PortalPreference::class, 'customer_id', 'customer_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getPortalUrlAttribute($value)
    {
        return $value ? url($value) : null;
    }
}

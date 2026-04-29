<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'activity_type',
        'activity_data'
    ];

    protected $casts = [
        'activity_data' => 'array'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getActivityDescriptionAttribute()
    {
        $type = $this->activity_type;
        $data = $this->activity_data;
        
        switch ($type) {
            case 'login':
                return 'Customer logged in to portal';
            case 'logout':
                return 'Customer logged out of portal';
            case 'view_document':
                return 'Customer viewed document: ' . ($data['document_name'] ?? 'Unknown');
            case 'download_document':
                return 'Customer downloaded document: ' . ($data['document_name'] ?? 'Unknown');
            case 'update_profile':
                return 'Customer updated profile information';
            case 'submit_feedback':
                return 'Customer submitted feedback';
            default:
                return 'Customer performed ' . $type;
        }
    }
}

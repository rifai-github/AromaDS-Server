<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'action',
        'method',
        'status',
        'user_id',
        'activity_date',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('method', $method);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'Pending' => 'badge-warning',
            'In Progress' => 'badge-info',
            'Completed' => 'badge-success',
            'Failed' => 'badge-danger',
            'No Response' => 'badge-secondary',
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getMethodIconAttribute()
    {
        $icons = [
            'Phone' => 'fas fa-phone',
            'Email' => 'fas fa-envelope',
            'SMS' => 'fas fa-sms',
            'WhatsApp' => 'fab fa-whatsapp',
            'Visit' => 'fas fa-user-tie',
            'Letter' => 'fas fa-envelope-open-text',
        ];

        return $icons[$this->method] ?? 'fas fa-question';
    }

    public function getActionIconAttribute()
    {
        $icons = [
            'Call' => 'fas fa-phone',
            'Email' => 'fas fa-envelope',
            'SMS' => 'fas fa-sms',
            'Visit' => 'fas fa-user-tie',
            'WhatsApp' => 'fab fa-whatsapp',
        ];

        return $icons[$this->action] ?? 'fas fa-question';
    }

    // Methods
    public function isCompleted()
    {
        return $this->status === 'Completed';
    }

    public function isPending()
    {
        return $this->status === 'Pending';
    }

    public function isFailed()
    {
        return $this->status === 'Failed';
    }

    public function getFormattedActivityDateAttribute()
    {
        return $this->activity_date->format('d M Y H:i');
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }

    public function getFormattedUpdatedAtAttribute()
    {
        return $this->updated_at->format('d M Y H:i');
    }
}

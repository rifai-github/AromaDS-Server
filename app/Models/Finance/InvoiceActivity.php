<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class InvoiceActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'activity_type',
        'notes',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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
    public function scopeCreated($query)
    {
        return $query->where('activity_type', 'created');
    }

    public function scopeSent($query)
    {
        return $query->where('activity_type', 'sent');
    }

    public function scopePaid($query)
    {
        return $query->where('activity_type', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('activity_type', 'overdue');
    }

    public function scopeCancelled($query)
    {
        return $query->where('activity_type', 'cancelled');
    }

    public function scopeUpdated($query)
    {
        return $query->where('activity_type', 'updated');
    }

    // Accessors & Mutators
    public function getActivityTypeBadgeAttribute()
    {
        $badges = [
            'created' => 'bg-blue-100 text-blue-800',
            'sent' => 'bg-green-100 text-green-800',
            'paid' => 'bg-green-100 text-green-800',
            'overdue' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-yellow-100 text-yellow-800',
            'updated' => 'bg-purple-100 text-purple-800',
        ];

        return $badges[$this->activity_type] ?? 'bg-gray-100 text-gray-800';
    }

    public function getActivityTypeLabelAttribute()
    {
        $labels = [
            'created' => 'Created',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
            'updated' => 'Updated',
        ];

        return $labels[$this->activity_type] ?? ucfirst($this->activity_type);
    }

    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d M Y H:i');
    }
}

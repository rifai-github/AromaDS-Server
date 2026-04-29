<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\User;
use App\Models\Finance\Invoice;
use Carbon\Carbon;

class InvoiceFollowUp extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'invoice_id',
        'follow_up_date',
        'follow_up_type',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
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
    public function scopeEmail($query)
    {
        return $query->where('follow_up_type', 'email');
    }

    public function scopePhone($query)
    {
        return $query->where('follow_up_type', 'phone');
    }

    public function scopeVisit($query)
    {
        return $query->where('follow_up_type', 'visit');
    }

    public function scopeLetter($query)
    {
        return $query->where('follow_up_type', 'letter');
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('follow_up_date', $date);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('follow_up_date', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getFollowUpTypeBadgeAttribute()
    {
        $badges = [
            'email' => 'bg-blue-100 text-blue-800',
            'phone' => 'bg-green-100 text-green-800',
            'visit' => 'bg-purple-100 text-purple-800',
            'letter' => 'bg-yellow-100 text-yellow-800',
        ];

        return $badges[$this->follow_up_type] ?? 'bg-gray-100 text-gray-800';
    }

    public function getFormattedFollowUpDateAttribute()
    {
        return $this->follow_up_date ? $this->follow_up_date->format('d/m/Y') : '-';
    }

    public function getFormattedFollowUpDateWithMonthAttribute()
    {
        return $this->follow_up_date ? $this->follow_up_date->format('d/m/Y') : '-';
    }

    public function getFollowUpTypeLabelAttribute()
    {
        $labels = [
            'email' => 'Email',
            'phone' => 'Phone Call',
            'visit' => 'Site Visit',
            'letter' => 'Letter',
        ];

        return $labels[$this->follow_up_type] ?? ucfirst($this->follow_up_type);
    }

    public function getFormattedDateAttribute()
    {
        return $this->follow_up_date->format('d M Y');
    }

    public function getFormattedDateTimeAttribute()
    {
        return $this->follow_up_date->format('d M Y H:i');
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($followUp) {
            $followUp->created_by = auth()->id();
        });
        
        static::updating(function ($followUp) {
            $followUp->updated_by = auth()->id();
        });
    }
}

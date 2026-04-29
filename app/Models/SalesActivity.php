<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'activity_number',
        'staff_id',
        'prospect_id',
        'location',
        'activity_date',
        'start_hour',
        'end_hour',
        'company_name',
        'company_address',
        'pic_name',
        'company_email',
        'activity',
        'contact_person',
        'contact_phone',
        'activity_result',
        'follow_up_plan',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'activity_date' => 'date',
        'start_hour' => 'datetime:H:i',
        'end_hour' => 'datetime:H:i'
    ];

    // Relationships
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }



    // Scopes
    public function scopeByStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    public function scopeByCompany($query, $companyName)
    {
        return $query->where('company_name', 'like', "%{$companyName}%");
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', 'like', "%{$location}%");
    }

    public function scopeToday($query)
    {
        return $query->whereDate('activity_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('activity_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('activity_date', now()->month);
    }

    // Accessors
    public function getFormattedActivityDateAttribute()
    {
        return $this->activity_date ? $this->activity_date->format('d/m/Y') : '-';
    }

    public function getFormattedStartHourAttribute()
    {
        return $this->start_hour ? $this->start_hour->format('H:i') : '-';
    }

    public function getFormattedEndHourAttribute()
    {
        return $this->end_hour ? $this->end_hour->format('H:i') : '-';
    }

    public function getDurationAttribute()
    {
        if ($this->start_hour && $this->end_hour) {
            return $this->start_hour->diffInHours($this->end_hour);
        }
        return 0;
    }
}

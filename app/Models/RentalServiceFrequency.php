<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalServiceFrequency extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'frequency_months',
        'frequency_times_per_month',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'frequency_months' => 'integer',
        'frequency_times_per_month' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Relationships
    public function masterRentals()
    {
        return $this->hasMany(MasterRental::class, 'service_frequency_id');
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
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Helper methods
    public function getFrequencyDescriptionAttribute()
    {
        if ($this->frequency_times_per_month > 1) {
            return "{$this->frequency_times_per_month} times per month";
        } elseif ($this->frequency_months > 1) {
            return "Every {$this->frequency_months} months";
        } else {
            return "Monthly";
        }
    }

    public function getTotalServicesPerYearAttribute()
    {
        return (12 / $this->frequency_months) * $this->frequency_times_per_month;
    }

    public function getFormattedFrequencyAttribute()
    {
        $description = $this->frequency_description;
        return "{$this->name} ({$description})";
    }

    // Static methods for common frequencies
    public static function getCommonFrequencies()
    {
        return [
            ['code' => '1M1X', 'name' => 'Monthly', 'frequency_months' => 1, 'frequency_times_per_month' => 1],
            ['code' => '1M2X', 'name' => 'Bi-Monthly', 'frequency_months' => 1, 'frequency_times_per_month' => 2],
            ['code' => '1M3X', 'name' => 'Tri-Monthly', 'frequency_months' => 1, 'frequency_times_per_month' => 3],
            ['code' => '2M1X', 'name' => 'Bi-Monthly', 'frequency_months' => 2, 'frequency_times_per_month' => 1],
            ['code' => '3M1X', 'name' => 'Quarterly', 'frequency_months' => 3, 'frequency_times_per_month' => 1],
        ];
    }
}
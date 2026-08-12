<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class PeriodicJob extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'contract_id',
        'building_id',
        'master_rental_id',
        'job_type',
        'service_frequency_months',
        'start_date',
        'end_date',
        'next_job_date',
        'is_active',
        'auto_generate',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_job_date' => 'date',
        'is_active' => 'boolean',
        'auto_generate' => 'boolean'
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function generatedJobs()
    {
        return $this->hasMany(JobSchedule::class, 'periodic_job_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeByBuilding($query, $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    public function scopeByJobType($query, $jobType)
    {
        return $query->where('job_type', $jobType);
    }

    public function scopeByMasterRental($query, $masterRentalId)
    {
        return $query->where('master_rental_id', $masterRentalId);
    }

    public function scopeAutoGenerate($query)
    {
        return $query->where('auto_generate', true);
    }

    public function scopeDueForGeneration($query)
    {
        return $query->where('is_active', true)
                    ->where('auto_generate', true)
                    ->where('next_job_date', '<=', now()->toDateString());
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('next_job_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getJobTypeTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->job_type));
    }

    public function getFormattedStartDateAttribute()
    {
        return $this->start_date ? $this->start_date->format('d M Y') : '-';
    }

    public function getFormattedEndDateAttribute()
    {
        return $this->end_date ? $this->end_date->format('d M Y') : '-';
    }

    public function getFormattedNextJobDateAttribute()
    {
        return $this->next_job_date ? $this->next_job_date->format('d M Y') : '-';
    }

    public function getIsDueAttribute()
    {
        return $this->is_active && $this->auto_generate && $this->next_job_date <= now()->toDateString();
    }

    public function getDaysUntilNextJobAttribute()
    {
        if (!$this->next_job_date) {
            return null;
        }

        return now()->diffInDays($this->next_job_date, false);
    }

    // Methods
    public function canGenerateJob()
    {
        return $this->is_active && 
               $this->auto_generate && 
               $this->next_job_date <= now()->toDateString() &&
               (!$this->end_date || $this->end_date >= now()->toDateString());
    }

    public function generateNextJob()
    {
        if (!$this->canGenerateJob()) {
            return false;
        }

        // Create new job schedule
        $jobSchedule = JobSchedule::create([
            'job_number' => $this->generateJobNumber(),
            'type' => $this->job_type,
            'status' => 'scheduled',
            'building_id' => $this->building_id,
            'building_name' => $this->building->building_name,
            'company_name' => $this->building->customer->name,
            'contract_number' => $this->contract->contract_number,
            'schedule_date' => $this->next_job_date,
            'expected_date' => $this->next_job_date,
            'period' => $this->generatedJobs()->count() + 1, // Add period number
            'periodic_job_id' => $this->id,
            'created_by' => $this->created_by,
            'updated_by' => $this->created_by
        ]);

        // Update next job date
        $this->update([
            'next_job_date' => $this->next_job_date->addMonths($this->service_frequency_months),
            'updated_by' => $this->created_by
        ]);

        return $jobSchedule;
    }

    public function generateJobNumber()
    {
        $documentTypeMap = [
            'install' => 'installation_report',
            'install_free' => 'installation_free',
            'service' => 'customer_service_report',
            'service_first' => 'customer_service_report',
            'service_routine' => 'customer_service_report',
            'remove' => 'remove',
            'removal' => 'remove',
            'remove_free' => 'remove_free',
            'remove free' => 'remove_free',
            'maintenance' => 'job_schedule',
            'extra' => 'job_schedule_extra',
            'complain' => 'job_schedule_complain',
            'suspend' => 'job_schedule_suspend',
            'dpf' => 'job_schedule_dpf',
        ];

        $jobType = strtolower($this->job_type ?? '');
        $documentType = $documentTypeMap[$jobType] ?? 'job_schedule';

        return app(\App\Services\DocumentNumberService::class)->generate(
            $documentType,
            null,
            $this->building_id,
            $this->contract_id,
            null,
            null,
            null,
            null,
            $this->next_job_date
        );
    }

    public function pause()
    {
        $this->update([
            'is_active' => false,
            'updated_by' => auth()->id()
        ]);
    }

    public function resume()
    {
        $this->update([
            'is_active' => true,
            'updated_by' => auth()->id()
        ]);
    }

    public function stop()
    {
        $this->update([
            'is_active' => false,
            'auto_generate' => false,
            'updated_by' => auth()->id()
        ]);
    }
}

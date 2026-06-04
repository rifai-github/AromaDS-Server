<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class JobSchedule extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $appends = [
        'completed_rooms_count',
        'total_rooms_count',
        'status_text',
        'status_badge',
        'display_type',
    ];

    protected $fillable = [
        'job_number',
        'job_reference_number', // Job Reference Enhancement
        'type',
        'status',
        'job_advice_id',
        'periodic_job_id',
        'building_id',
        'building_name',
        'company_name',
        'quotation_number',
        'contract_number',
        'period',
        'reference_number',
        'day',
        'room_id',
        'room_name',
        'schedule_date',
        'expected_date',
        'assign_date',
        'issue_date',
        'ba_date',
        'ba_number',
        'postal_code',
        'district',
        'sub_district',
        'internal_notes',
        'latitude',
        'longitude',
        'technician_location',
        'location_updated_at',
        'started_at',
        'completed_at',
        'assigned_technician_id',
        'technician_notes',
        'work_status',
        'notes',
        'service_frequency',
        'service_period_type',
        'service_interval_days',
        'next_service_date',
        'created_by',
        'updated_by',
        // Force Majeure Fields
        'force_majeure_status',
        'force_majeure_reason',
        'force_majeure_at',
        'backup_technician_id',
        'reassigned_by',
        'reassigned_at',
        'reschedule_date',
        'reschedule_time',
        'reschedule_reason',
        'material_status',
        'material_return_notes',
        'material_return_at',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_notes',
        'resolution_status',
        'resolution_notes',
        'resolved_at',
        'material_checked',
        'material_checked_at',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'assign_date' => 'date',
        'issue_date' => 'date',
        'ba_date' => 'date',
        'expected_date' => 'date',
        'reschedule_date' => 'date',
        'reschedule_time' => 'datetime',
        'next_service_date' => 'date',
        'force_majeure_at' => 'datetime',
        'reassigned_at' => 'datetime',
        'material_return_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'material_checked' => 'boolean',
        'material_checked_at' => 'datetime',
    ];

    // Relationships
    public function jobAdvice()
    {
        return $this->belongsTo(JobAdvice::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function room()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function jobAssignSchedules()
    {
        return $this->hasMany(JobAssignSchedule::class);
    }

    public function jobAssignments()
    {
        return $this->hasMany(JobAssignment::class, 'job_number', 'job_number');
    }

    public function jobMaterials()
    {
        return $this->hasMany(JobMaterial::class);
    }

    public function periodicJob()
    {
        return $this->belongsTo(PeriodicJob::class);
    }

    public function jobReports()
    {
        return $this->hasMany(JobReport::class);
    }

    public function jobPhotos()
    {
        return $this->hasMany(JobPhoto::class);
    }

    public function baFiles()
    {
        return $this->hasMany(JobScheduleBaFile::class);
    }

    /**
     * STUDY CASE B1: Relationship with JobScheduleRoom for tracking per-room completion
     */
    public function jobScheduleRooms()
    {
        return $this->hasMany(JobScheduleRoom::class);
    }

    public function jobScheduleRoomAssignments()
    {
        return $this->hasMany(JobScheduleRoomAssignment::class);
    }

    /**
     * STUDY CASE B1: Get completed rooms count
     */
    public function getCompletedRoomsCountAttribute()
    {
        return $this->jobScheduleRooms()->where('status', JobScheduleRoom::STATUS_COMPLETED)->count();
    }

    /**
     * STUDY CASE B1: Get total rooms count
     */
    public function getTotalRoomsCountAttribute()
    {
        return $this->jobScheduleRooms()->count();
    }

    /**
     * STUDY CASE B1: Check if all rooms are completed
     */
    public function areAllRoomsCompleted()
    {
        $totalRooms = $this->jobScheduleRooms()->count();
        if ($totalRooms === 0) {
            return true; // No rooms to track, consider completed
        }
        
        $completedRooms = $this->jobScheduleRooms()->where('status', JobScheduleRoom::STATUS_COMPLETED)->count();
        return $completedRooms === $totalRooms;
    }

    /**
     * STUDY CASE B1: Alias for checkAllRoomsCompleted (for consistency)
     */
    public function checkAllRoomsCompleted(): bool
    {
        return $this->areAllRoomsCompleted();
    }

    /**
     * Get the location logs for the job schedule.
     */
    public function locationLogs()
    {
        return $this->hasMany(TechnicianLocation::class);
    }

    /**
     * Get the favorites for this job.
     */
    public function favorites()
    {
        return $this->hasMany(JobFavorite::class);
    }

    public function assignedTechnician()
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function backupTechnician()
    {
        return $this->belongsTo(User::class, 'backup_technician_id');
    }

    public function reassignedBy()
    {
        return $this->belongsTo(User::class, 'reassigned_by');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('schedule_date', [$startDate, $endDate]);
    }

    public function scopeByBuilding($query, $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    public function scopeScheduled($query)
    {
        return $query->whereIn('status', ['scheduled', 'new_job']);
    }

    public function scopeInProgress($query)
    {
        // Include all active statuses (not completed/cancelled)
        return $query->whereNotIn('status', ['completed', 'done_job', 'cancelled']);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'done_job']);
    }

    public function scopeForceMajeure($query)
    {
        return $query->where('force_majeure_status', '!=', 'none');
    }

    public function scopeByForceMajeureStatus($query, $status)
    {
        return $query->where('force_majeure_status', $status);
    }

    public function scopePendingResolution($query)
    {
        return $query->where('resolution_status', 'pending');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspend');
    }

    public function scopeDpf($query)
    {
        return $query->where('status', 'dpf');
    }

    public function scopeTerminated($query)
    {
        return $query->where('status', 'terminated');
    }

    public function scopeNotTerminated($query)
    {
        return $query->where('status', '!=', 'terminated');
    }

    /**
     * Check if job can be edited/processed
     */
    public function getIsLockedAttribute()
    {
        return in_array($this->status, ['terminated', 'completed', 'cancelled']);
    }

    public function getIsTerminatedAttribute()
    {
        return $this->status === 'terminated';
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'scheduled' => 'badge-info',
            'in_progress' => 'badge-warning',
            'completed' => 'badge-success',
            'cancelled' => 'badge-danger',
            'pending' => 'badge-secondary',
            'force_majeure' => 'badge-danger',
            'rescheduled' => 'badge-warning',
            'suspend' => 'badge-dark',      // Suspend: Job diselesaikan tapi TIDAK ditagih (MOM6)
            'dpf' => 'badge-warning',       // DPF: Done but Force-charged - Job diselesaikan tapi TETAP ditagih (MOM6)
            'terminated' => 'badge-dark',   // Terminated: Contract terminated, job locked (hitam)
            
            // Refactored Statuses
            'assign_material' => 'badge-primary',   // Blue (Confirmed/Taken) - Replaces material_assign
            'material_issue' => 'badge-warning',    // Orange/Yellow
            'barang_dipersiapkan' => 'badge-warning',
            'barang_siap_diambil' => 'badge-info',
            'barang_diambil' => 'badge-primary',
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    /**
     * MOM: Status Badge Class for index.blade.php styling
     */
    public function getStatusBadgeClassAttribute()
    {
        return 'status-' . str_replace('_', '-', $this->status);
    }

    public function getForceMajeureBadgeAttribute()
    {
        $badges = [
            'none' => 'badge-success',
            'technician_unavailable' => 'badge-danger',
            'material_shortage' => 'badge-warning',
            'weather' => 'badge-info',
            'emergency' => 'badge-danger',
            'equipment_failure' => 'badge-warning',
            'other' => 'badge-secondary',
        ];

        return $badges[$this->force_majeure_status] ?? 'badge-secondary';
    }

    public function getStatusTextAttribute()
    {
        $statusTexts = [
            'scheduled' => 'New Job',
            'new_job' => 'New Job',
            'assign_team' => 'Assign Team',
            'assign_material' => 'Material Assign', // User requested label
            
            // Refactored Statuses
            'material_issue' => 'Material Prepare',     // Permintaan harmonisasi
            
            // Legacy Statuses
            'barang_dipersiapkan' => 'Material Prepare',
            'barang_siap_diambil' => 'Material Ready',
            'barang_diambil' => 'Material Issued',     // Sesuai permintaan: "material telah di issue"
            
            'teknisi_tiba_dilokasi' => 'Teknisi tiba dilokasi',
            'teknisi_sedang_pengerjaan' => 'Teknisi sedang pengerjaan',
            'teknisi_selesai_pengerjaan' => 'Teknisi selesai pengerjaan',
            'meninggalkan_lokasi' => 'Meninggalkan lokasi',
            'done_job' => 'Done Job',
            'completed' => 'Done Job',
            'in_progress' => 'In Progress', 
            'cancelled' => 'Cancelled',
            'pending' => 'Pending',
            'force_majeure' => 'Force Majeure',
            'rescheduled' => 'Rescheduled',
            'suspend' => 'Suspend (No Invoice)',
            'dpf' => 'DPF (Done but Force-charged)',
        ];

        return $statusTexts[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Get calculated invoice period based on service period and TOP
     * Formula: ceil(period / TOP_interval)
     */
    public function getInvoicePeriodAttribute()
    {
        // For non-service/check jobs, invoice period usually doesn't apply 
        // or coincides with its period if set.
        if (!in_array($this->type, ['service', 'service_routine', 'service_first', 'check', 'service routine', 'service first'])) {
            return $this->period ?? '-';
        }

        if (empty($this->period)) {
            return 1;
        }

        if (!is_numeric($this->period)) {
            return '-';
        }

        // Get TOP interval from contract
        $contract = $this->jobAdvice?->contract ?? null;
        $topInterval = $contract ? $contract->top_interval_months : 1;
        
        // Get Service Frequency Times (e.g. 4x per month)
        $freqTimes = 1;
        if ($this->service_frequency && is_numeric($this->service_frequency)) {
            $freqTimes = (int) $this->service_frequency;
        }
        
        // Calculate Divisor
        // Example: 4x service per month, Monthly Invoice (TOP=1) -> Divisor = 4
        // PS 1,2,3,4 / 4 -> ceil -> 1.
        $divisor = $freqTimes * $topInterval;
        if ($divisor < 1) $divisor = 1;

        // Formula: ceil(current_period / divisor)
        return ceil((int) $this->period / $divisor);
    }

    public function getScheduleDateFormattedAttribute()
    {
        return $this->schedule_date ? $this->schedule_date->format('d M Y') : '-';
    }

    public function getExpectedDateFormattedAttribute()
    {
        return $this->expected_date ? $this->expected_date->format('d M Y') : '-';
    }

    // MOM10 UPDATE: Removed getJobNumberAttribute() accessor
    // job_number should be accessed directly from database field (with IR/CSR/RR codes)
    // Job Advice Number can be accessed via: $jobSchedule->jobAdvice->job_advice_number

    public function getJobReferenceNumberAttribute()
    {
        return $this->attributes['job_reference_number'] ?? '-';
    }

    public function getCustomerNameAttribute()
    {
        return $this->jobAdvice ? $this->jobAdvice->customer->name : '-';
    }

    public function getBuildingNameAttribute()
    {
        return $this->building ? $this->building->nama_gedung : '-';
    }

    public function getRoomNameAttribute()
    {
        return $this->room ? $this->room->room_name : '-';
    }

    /**
     * Get display type label for human-readable display
     * Maps internal type values to user-friendly labels
     */
    public function getDisplayTypeAttribute()
    {
        $typeLabels = [
            'install' => 'Install (IR)',
            'install_free' => 'Install Free',
            'service' => 'Service',
            'service_first' => 'Service Pertama (CSR)',
            'service_routine' => 'Service Routine',
            'check' => 'Check (CHK)',
            'remove' => 'Remove',
            'remove_free' => 'Remove Free',
            'maintenance' => 'Maintenance',
        ];
        
        $type = strtolower($this->type ?? '');
        if (in_array($type, ['service', 'service_first', 'service_routine'], true)
            && $this->material_checked
            && $this->hasOnlyRentalFlow(['unit_only'])
        ) {
            return 'Check (CHK)';
        }
        
        return $typeLabels[$type] ?? ucfirst(str_replace('_', ' ', $this->type ?? '-'));
    }

    private function hasOnlyRentalFlow(array $allowedRentalTypes): bool
    {
        $rooms = $this->relationLoaded('jobScheduleRooms')
            ? $this->jobScheduleRooms
            : $this->jobScheduleRooms()
                ->with(['rentals.jobAdviceRoom.rentalProduct', 'jobAdviceRoom.rentalProduct'])
                ->get();

        $rentalTypes = collect();

        foreach ($rooms as $room) {
            $rentals = $room->relationLoaded('rentals') ? $room->rentals : collect();

            if ($rentals->isNotEmpty()) {
                foreach ($rentals as $rentalLink) {
                    $rentalTypes->push($this->resolveRentalFlowType($rentalLink->jobAdviceRoom));
                }

                continue;
            }

            $rentalTypes->push($this->resolveRentalFlowType($room->jobAdviceRoom));
        }

        $rentalTypes = $rentalTypes
            ->filter()
            ->map(fn ($type) => strtolower(trim((string) $type)))
            ->values();

        return $rentalTypes->isNotEmpty()
            && $rentalTypes->every(fn ($type) => in_array($type, $allowedRentalTypes, true));
    }

    private function resolveRentalFlowType($jobAdviceRoom): ?string
    {
        $rental = $jobAdviceRoom?->rentalProduct;
        $rentalType = strtolower(trim((string) ($rental?->rental_type ?? '')));

        if (in_array($rentalType, ['unit_only', 'refill_only'], true)) {
            return $rentalType;
        }

        $composition = $this->detectRentalMaterialComposition($rental);

        if ($composition['has_unit'] && !$composition['has_non_unit']) {
            return 'unit_only';
        }

        if (!$composition['has_unit'] && $composition['has_non_unit']) {
            return 'refill_only';
        }

        return $rentalType ?: null;
    }

    private function detectRentalMaterialComposition($rental): array
    {
        $hasUnit = false;
        $hasNonUnit = false;

        if (!$rental) {
            return ['has_unit' => false, 'has_non_unit' => false];
        }

        try {
            $rental->loadMissing([
                'rentalDetails.productCategory',
                'rentalDetails.productType',
                'rentalDetails.masterProduct.productCategory',
                'rentalDetails.masterProduct.productType',
                'rentalDetails.allowedProducts.productCategory',
                'rentalDetails.allowedProducts.productType',
            ]);
        } catch (\Throwable $e) {
            return ['has_unit' => false, 'has_non_unit' => false];
        }

        foreach ($rental->rentalDetails as $detail) {
            $isUnit = $this->rentalDetailIsUnit($detail);

            if ($isUnit === true) {
                $hasUnit = true;
            } elseif ($isUnit === false) {
                $hasNonUnit = true;
            }

            if ($hasUnit && $hasNonUnit) {
                break;
            }
        }

        return ['has_unit' => $hasUnit, 'has_non_unit' => $hasNonUnit];
    }

    private function rentalDetailIsUnit($detail): ?bool
    {
        if ($detail->productCategory && $detail->productCategory->is_unit !== null) {
            return (bool) $detail->productCategory->is_unit;
        }

        if ($detail->productType && $detail->productType->is_unit !== null) {
            return (bool) $detail->productType->is_unit;
        }

        $product = $detail->masterProduct;
        if ($product) {
            if ($product->productCategory && $product->productCategory->is_unit !== null) {
                return (bool) $product->productCategory->is_unit;
            }

            if ($product->productType && $product->productType->is_unit !== null) {
                return (bool) $product->productType->is_unit;
            }
        }

        $allowedProduct = $detail->allowedProducts->first();
        if ($allowedProduct) {
            if ($allowedProduct->productCategory && $allowedProduct->productCategory->is_unit !== null) {
                return (bool) $allowedProduct->productCategory->is_unit;
            }

            if ($allowedProduct->productType && $allowedProduct->productType->is_unit !== null) {
                return (bool) $allowedProduct->productType->is_unit;
            }
        }

        return null;
    }

    // Methods
    public function canAssign()
    {
        return in_array($this->status, ['scheduled', 'new_job']);
    }

    public function canStart()
    {
        return in_array($this->status, ['scheduled', 'new_job']);
    }

    public function canComplete()
    {
        // Can complete from any active status (not already completed/cancelled)
        return !in_array($this->status, ['completed', 'done_job', 'cancelled']);
    }

    public function canCancel()
    {
        // Can cancel from any active status
        return !in_array($this->status, ['completed', 'done_job', 'cancelled']);
    }

    public function canReportForceMajeure()
    {
        // Can report force majeure from any active status
        return !in_array($this->status, ['completed', 'done_job', 'cancelled']);
    }

    public function canReassign()
    {
        return $this->force_majeure_status !== 'none' && $this->resolution_status === 'pending';
    }

    public function canReschedule()
    {
        return $this->force_majeure_status !== 'none' && $this->resolution_status === 'pending';
    }

    public function start()
    {
        $this->update(['status' => 'in_progress']);
    }

    public function complete()
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    public function suspend()
    {
        // Suspend: Job diselesaikan tapi TIDAK ditagih (MOM6)
        $this->update(['status' => 'suspend']);
    }

    public function markAsDpf()
    {
        // DPF (Done but Force-charged): Job diselesaikan tapi TETAP ditagih (MOM6)
        $this->update(['status' => 'dpf']);
    }

    public function isSuspended()
    {
        return $this->status === 'suspend';
    }

    public function isDpf()
    {
        return $this->status === 'dpf';
    }

    public function shouldBeInvoiced()
    {
        // Job yang harus ditagih: completed dan dpf
        // Suspend TIDAK ditagih
        return in_array($this->status, ['completed', 'dpf']);
    }

    public function getRelatedCustomer()
    {
        return $this->jobAdvice ? $this->jobAdvice->customer : null;
    }

    public function getRelatedContract()
    {
        return $this->jobAdvice ? $this->jobAdvice->contract : null;
    }

    // Force Majeure Methods
    public function reportForceMajeure($status, $reason, $userId = null)
    {
        $this->update([
            'force_majeure_status' => $status,
            'force_majeure_reason' => $reason,
            'force_majeure_at' => now(),
            'resolution_status' => 'pending',
            'updated_by' => $userId ?? auth()->id()
        ]);

        // Update main status if needed
        if ($status !== 'none') {
            $this->update(['status' => 'force_majeure']);
        }
    }

    public function reassignToBackupTechnician($backupTechnicianId, $userId = null)
    {
        $this->update([
            'backup_technician_id' => $backupTechnicianId,
            'reassigned_by' => $userId ?? auth()->id(),
            'reassigned_at' => now(),
            'assigned_technician_id' => $backupTechnicianId,
            'resolution_status' => 'resolved',
            'resolved_at' => now()
        ]);
    }

    public function rescheduleJob($newDate, $newTime = null, $reason = null, $userId = null)
    {
        $this->update([
            'reschedule_date' => $newDate,
            'reschedule_time' => $newTime,
            'reschedule_reason' => $reason,
            'schedule_date' => $newDate,
            'status' => 'rescheduled',
            'resolution_status' => 'resolved',
            'resolved_at' => now(),
            'updated_by' => $userId ?? auth()->id()
        ]);
    }

    public function handleMaterialReturn($status, $notes = null, $userId = null)
    {
        $this->update([
            'material_status' => $status,
            'material_return_notes' => $notes,
            'material_return_at' => now(),
            'updated_by' => $userId ?? auth()->id()
        ]);
    }

    public function resolveForceMajeure($notes = null, $userId = null)
    {
        $this->update([
            'resolution_status' => 'resolved',
            'resolution_notes' => $notes,
            'resolved_at' => now(),
            'updated_by' => $userId ?? auth()->id()
        ]);
    }

    // Service Frequency Methods
    public function calculateServiceInterval()
    {
        if (!$this->service_frequency || !$this->schedule_date) {
            return null;
        }

        // Formula from CLIENT-FEEDBACK-ENHANCEMENT.md
        // 1. Add one month to start date
        $endOfMonth = $this->schedule_date->copy()->addMonth();
        
        // 2. Calculate difference in days
        $daysDifference = $this->schedule_date->diffInDays($endOfMonth);
        
        // 3. Divide by service frequency
        $interval = $daysDifference / $this->service_frequency;
        
        // 4. Round up the result
        $intervalDays = ceil($interval);
        
        $this->update([
            'service_interval_days' => $intervalDays,
            'next_service_date' => $this->schedule_date->copy()->addDays($intervalDays)
        ]);
        
        return $intervalDays;
    }

    public function generateNextServiceDates($months = 6)
    {
        if (!$this->service_interval_days || !$this->schedule_date) {
            return [];
        }

        $dates = [];
        $currentDate = $this->schedule_date->copy();
        
        for ($i = 0; $i < $months; $i++) {
            $currentDate->addDays($this->service_interval_days);
            $dates[] = $currentDate->copy();
        }
        
        return $dates;
    }

    public function getServiceFrequencyLabel()
    {
        if (!$this->service_frequency) {
            return 'Not Set';
        }

        $labels = [
            1 => 'Once per month',
            2 => 'Twice per month', 
            3 => 'Three times per month',
            4 => 'Four times per month',
            6 => 'Every 2 months',
            9 => 'Every 3 months',
            12 => 'Every 4 months',
            15 => 'Every 5 months',
            18 => 'Every 6 months',
            36 => 'Once per year'
        ];

        return $labels[$this->service_frequency] ?? "{$this->service_frequency} times per month";
    }

    public function getServicePeriodTypeLabel()
    {
        $labels = [
            'monthly' => 'Monthly',
            'bi_monthly' => 'Bi-Monthly',
            'quarterly' => 'Quarterly',
            'semi_annually' => 'Semi-Annually',
            'annually' => 'Annually'
        ];

        return $labels[$this->service_period_type] ?? ucfirst($this->service_period_type);
    }

    /**
     * Auto-update expected date based on service frequency
     * This method should be called daily via cron job
     */
    public function updateExpectedDateBasedOnFrequency()
    {
        if (!$this->service_frequency || !$this->schedule_date) {
            return false;
        }

        $today = now()->startOfDay();
        $scheduleDate = $this->schedule_date->startOfDay();
        
        // Calculate days since schedule date
        $daysSinceSchedule = $today->diffInDays($scheduleDate);
        
        // Calculate service interval in days
        $serviceIntervalDays = $this->calculateServiceIntervalDays();
        
        if (!$serviceIntervalDays) {
            return false;
        }

        // Find the next service date based on frequency
        $nextServiceDate = $this->calculateNextServiceDate($today, $scheduleDate, $serviceIntervalDays);
        
        if ($nextServiceDate && $nextServiceDate != $this->expected_date) {
            $this->update([
                'expected_date' => $nextServiceDate,
                'updated_by' => 1 // System update
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Calculate service interval in days based on frequency
     */
    private function calculateServiceIntervalDays()
    {
        if (!$this->service_frequency) {
            return null;
        }

        // For monthly frequency, calculate days per service
        $daysInMonth = 30; // Approximate
        $intervalDays = $daysInMonth / $this->service_frequency;
        
        return ceil($intervalDays);
    }

    /**
     * Calculate next service date based on current date and frequency
     */
    private function calculateNextServiceDate($today, $scheduleDate, $intervalDays)
    {
        $currentDate = $scheduleDate->copy();
        $serviceDates = [];
        
        // Generate service dates for the next 3 months
        for ($i = 0; $i < 12; $i++) { // 12 iterations to cover 3 months with 3x/month
            $currentDate->addDays($intervalDays);
            $serviceDates[] = $currentDate->copy();
        }
        
        // Find the next service date that is >= today
        foreach ($serviceDates as $serviceDate) {
            if ($serviceDate->gte($today)) {
                return $serviceDate;
            }
        }
        
        return null;
    }

    /**
     * Static method to update all job schedules' expected dates
     * This should be called daily via cron job
     */
    public static function updateAllExpectedDates()
    {
        $updatedCount = 0;
        
        $jobSchedules = self::whereNotNull('service_frequency')
            ->whereNotNull('schedule_date')
            ->where('status', '!=', 'completed')
            ->get();
            
        foreach ($jobSchedules as $jobSchedule) {
            if ($jobSchedule->updateExpectedDateBasedOnFrequency()) {
                $updatedCount++;
            }
        }
        
        return $updatedCount;
    }

    // Job Reference Enhancement Methods
    public function assignJobReference($referenceNumber, $userId = null)
    {
        $this->update([
            'job_reference_number' => $referenceNumber,
            'updated_by' => $userId ?? auth()->id()
        ]);
    }

    public function hasJobReference()
    {
        return !empty($this->job_reference_number);
    }

    public function getJobReferenceDisplayAttribute()
    {
        return $this->job_reference_number ?: 'Not Assigned';
    }

    public function scopeWithJobReference($query)
    {
        return $query->whereNotNull('job_reference_number');
    }

    public function scopeWithoutJobReference($query)
    {
        return $query->whereNull('job_reference_number');
    }

    public function scopeByJobReference($query, $referenceNumber)
    {
        return $query->where('job_reference_number', $referenceNumber);
    }
}

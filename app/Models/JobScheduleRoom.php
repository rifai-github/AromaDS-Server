<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobScheduleRoom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_schedule_id',
        'job_advice_room_id',
        'room_name',
        'room_id',
        'status',
        'completed_at',
        'completed_by',
        'material_return_status',
        'material_return_at',
        'material_return_by',
        'material_return_id',
        'notes',
        'completion_notes',
        'material_return_notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'material_return_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Material return status constants
    const MATERIAL_RETURN_NOT_REQUIRED = 'not_required';
    const MATERIAL_RETURN_PENDING = 'pending';
    const MATERIAL_RETURN_RETURNED = 'returned';
    const MATERIAL_RETURN_CANCELLED = 'cancelled';

    /**
     * Relationships
     */
    
    public function jobSchedule()
    {
        return $this->belongsTo(JobSchedule::class);
    }

    public function jobAdviceRoom()
    {
        return $this->belongsTo(JobAdviceRoom::class);
    }

    public function room()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function materialReturnBy()
    {
        return $this->belongsTo(User::class, 'material_return_by');
    }

    public function materialReturn()
    {
        return $this->belongsTo(MaterialReturn::class, 'material_return_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * STUDY CASE B2: Relationship with room assignment
     */
    public function roomAssignment()
    {
        return $this->hasOne(JobScheduleRoomAssignment::class);
    }

    /**
     * Multi-rental support: Relationship to all rentals linked to this room
     * Via pivot table job_schedule_room_rentals
     */
    public function rentals()
    {
        return $this->hasMany(JobScheduleRoomRental::class);
    }

    /**
     * Get all JobAdviceRooms linked to this JobScheduleRoom via pivot
     */
    public function jobAdviceRooms()
    {
        return $this->hasManyThrough(
            JobAdviceRoom::class,
            JobScheduleRoomRental::class,
            'job_schedule_room_id', // FK on pivot
            'id', // PK on JobAdviceRoom
            'id', // PK on this model
            'job_advice_room_id' // FK on pivot pointing to JobAdviceRoom
        );
    }

    /**
     * Scopes
     */
    
    public function scopeByJobSchedule($query, $jobScheduleId)
    {
        return $query->where('job_schedule_id', $jobScheduleId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeNotCompleted($query)
    {
        return $query->where('status', '!=', self::STATUS_COMPLETED);
    }

    /**
     * Methods
     */
    
    /**
     * Mark room as completed
     */
    public function markAsCompleted($userId = null, $notes = null)
    {
        $updateData = [
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'completed_by' => $userId ?? auth()->id(),
        ];

        // DUPLICATION FIX: Smart Note Appending
        if ($notes) {
            $newNote = trim($notes);
            $currentNotes = $this->completion_notes ?? '';
            
            // Only append if the note is not already present
            if (strpos($currentNotes, $newNote) === false) {
                $updateData['completion_notes'] = ($currentNotes ? $currentNotes . "\n" : '') . $newNote;
            }
        }
        
        $this->update($updateData);
    }

    /**
     * Mark room as in progress
     */
    public function markAsInProgress()
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS
        ]);
    }

    /**
     * Check if room is completed
     */
    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if material return is required
     */
    public function requiresMaterialReturn()
    {
        return $this->material_return_status === self::MATERIAL_RETURN_PENDING;
    }

    /**
     * Get display rental name attribute
     * Used for display in Job Schedule view
     * priority: rental_alias -> rental_name
     */
    public function getDisplayRentalNameAttribute()
    {
        $rentalLinks = $this->relationLoaded('rentals')
            ? $this->rentals
            : $this->rentals()->with([
                'jobAdviceRoom.rentalProduct',
            ])->get();

        if ($rentalLinks->isNotEmpty()) {
            $names = $rentalLinks
                ->map(function ($link) {
                    $jaRoom = $link->jobAdviceRoom;
                    if (! $jaRoom) {
                        return null;
                    }

                    if ($jaRoom->contract_rental_id) {
                        $contractRental = \App\Models\ContractRental::find($jaRoom->contract_rental_id);
                        if ($contractRental && ! empty($contractRental->rental_alias)) {
                            return $contractRental->rental_alias;
                        }
                    }

                    if ($jaRoom->quotation_detail_id) {
                        $quotationDetail = \App\Models\QuotationDetail::find($jaRoom->quotation_detail_id);
                        if ($quotationDetail && ! empty($quotationDetail->rental_alias)) {
                            return $quotationDetail->rental_alias;
                        }
                    }

                    return $jaRoom->rentalProduct?->rental_name
                        ?? $jaRoom->rental_name
                        ?? null;
                })
                ->filter()
                ->unique()
                ->values();

            if ($names->isNotEmpty()) {
                return $names->implode(', ');
            }
        }

        // Try getting the rental_alias first
        if ($this->jobAdviceRoom) {
            // 1. From Job Advice Room IDs directly
            if ($this->jobAdviceRoom->contract_rental_id) {
                $cr = \App\Models\ContractRental::find($this->jobAdviceRoom->contract_rental_id);
                if ($cr && !empty($cr->rental_alias)) {
                    return $cr->rental_alias;
                }
            } elseif ($this->jobAdviceRoom->quotation_detail_id) {
                $qd = \App\Models\QuotationDetail::find($this->jobAdviceRoom->quotation_detail_id);
                if ($qd && !empty($qd->rental_alias)) {
                    return $qd->rental_alias;
                }
            }
            
            // 2. Fallback to ContractRoom if ids not available
            if ($this->jobAdviceRoom->contractRoom) {
                $crQuery = \App\Models\ContractRental::where('contract_id', $this->jobAdviceRoom->contractRoom->contract_id);
                
                // Try to match by master_rental_id if available on contractRoom's rentalProduct
                if ($this->jobAdviceRoom->contractRoom->rentalProduct) {
                    $crQuery->where('master_rental_id', $this->jobAdviceRoom->contractRoom->rentalProduct->id);
                } else {
                    // Otherwise try to match by room_id or null
                    $crQuery->where(function($q) {
                        $q->where('room_id', $this->jobAdviceRoom->contractRoom->room_id)->orWhereNull('room_id');
                    });
                }
                
                $cr = $crQuery->first();
                if ($cr && !empty($cr->rental_alias)) {
                    return $cr->rental_alias;
                }
            }
            
            // 3. Fallback to QuotationDetail if ids not available
            if ($this->jobAdviceRoom->quotationRoom) {
                $qdQuery = \App\Models\QuotationDetail::where('quotation_id', $this->jobAdviceRoom->quotationRoom->quotation_id);
                
                if ($this->jobAdviceRoom->quotationRoom->room_id) {
                    $qdQuery->where('room_id', $this->jobAdviceRoom->quotationRoom->room_id);
                }
                
                if ($this->jobAdviceRoom->quotationRoom->rentalProduct) {
                    $qdQuery->where('master_rental_id', $this->jobAdviceRoom->quotationRoom->rentalProduct->id);
                }
                
                $qd = $qdQuery->first();
                if ($qd && !empty($qd->rental_alias)) {
                    return $qd->rental_alias;
                }
            }
        }

        // Check if this is a Change Rental job
        $isChangeRental = false;
        if ($this->jobAdviceRoom && $this->jobAdviceRoom->jobAdvice) {
            $type = strtolower($this->jobAdviceRoom->jobAdvice->type ?? '');
            $isChangeRental = in_array($type, ['change rental', 'change_rental', 'change', 'change unit', 'change_unit']);
        }

        // STUDY CASE B3: For Change Rental, ALWAYS prioritize the rentalProduct (New Rental)
        // because the snapshot (rental_name) might still hold the Old Rental name
        if ($isChangeRental) {
            if ($this->jobAdviceRoom && $this->jobAdviceRoom->rentalProduct) {
                return $this->jobAdviceRoom->rentalProduct->rental_name;
            }
        }

        // 3. Try from JobAdviceRoom snapshot (if available and NOT change rental)
        if ($this->jobAdviceRoom && $this->jobAdviceRoom->rental_name) {
            return $this->jobAdviceRoom->rental_name;
        }

        // 4. Try from JobAdviceRoom relationship to MasterRental
        if ($this->jobAdviceRoom && $this->jobAdviceRoom->rentalProduct) {
            return $this->jobAdviceRoom->rentalProduct->rental_name;
        }

        // 5. Try from ContractRoom relationship (Contract fallback)
        if ($this->jobAdviceRoom && $this->jobAdviceRoom->contractRoom && $this->jobAdviceRoom->contractRoom->rentalProduct) {
            return $this->jobAdviceRoom->contractRoom->rentalProduct->rental_name;
        }

        return '-';
    }

    /**
     * MOM: Status Text Accessor with Granular Logic
     * Standardize status labels and colors across the application
     * Implementation follows logic from JobScheduleController to ensure consistency
     */
    public function getStatusTextAttribute()
    {
        $job = $this->jobSchedule;
        if (!$job) return ucfirst($this->status ?? 'pending');

        $status = $this->resolveStatusLogicKey();

        $statusMap = [
            'scheduled' => 'New Job',
            'new_job' => 'New Job',
            'assign_team' => 'Assign Team',
            'assign_material' => 'Material Assign',
            'barang_dipersiapkan' => 'Material in Prep',
            'material_issue' => 'Material in Prep',
            'barang_siap_diambil' => 'Material Ready',
            'barang_diambil' => 'Material Issued',
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

        return $statusMap[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public function getStatusBadgeClassAttribute()
    {
        return 'status-' . str_replace('_', '-', $this->resolveStatusLogicKey());
    }

    /**
     * Internal helper to get the status key for badge class calculation
     * without duplicating the heavy logic
     */
    public function getStatusLogicKeyAttribute()
    {
        return $this->resolveStatusLogicKey();
    }

    private function resolveStatusLogicKey(): string
    {
        $job = $this->jobSchedule;
        if (!$job) return $this->status ?? 'pending';

        $status = $job->status;
        if (in_array($status, ['assign_material', 'barang_dipersiapkan', 'barang_siap_diambil', 'barang_diambil', 'material_issue'])) {
            $materialIssuesByRoom = $this->materialIssuesByRoomForStatus($job);
            $roomKey = $this->normalizeRoomNameForStatus($this->room_name);
            $roomMaterialIssues = $materialIssuesByRoom->get($roomKey, collect());
            $thisRoomHasMaterial = $roomMaterialIssues->isNotEmpty();

            if ($thisRoomHasMaterial) {
                $issueNumbers = $roomMaterialIssues
                    ->pluck('issue_number')
                    ->filter()
                    ->unique()
                    ->values();

                if ($issueNumbers->isNotEmpty()) {
                    $inventoryIssuingStatuses = \App\Models\InventoryIssuing::whereIn('reference_no', $issueNumbers->all())
                        ->whereNull('deleted_at')
                        ->pluck('status');

                    if ($inventoryIssuingStatuses->contains(fn ($status) => in_array($status, ['sent', 'received'], true))) {
                        return 'barang_diambil';
                    }

                    if ($inventoryIssuingStatuses->contains('processed')) {
                        return 'barang_siap_diambil';
                    }

                    if ($inventoryIssuingStatuses->contains('pending')) {
                        return 'barang_dipersiapkan';
                    }
                }

                if ($roomMaterialIssues->contains(fn ($issue) => $issue->status === 'issued')) {
                    return 'barang_dipersiapkan';
                }

                return 'assign_material';
            }

            if (!$thisRoomHasMaterial) {
                if ($materialIssuesByRoom->isNotEmpty()) {
                    return 'scheduled';
                }
            }
        }
        return $status;
    }

    private function materialIssuesByRoomForStatus(JobSchedule $job)
    {
        $jobs = collect([$job]);

        if (!empty($job->job_number)) {
            $jobs = JobSchedule::with([
                    'jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items',
                ])
                ->where('job_number', $job->job_number)
                ->whereNull('deleted_at')
                ->where('status', '!=', 'cancelled')
                ->get();
        } elseif (!$job->relationLoaded('jobAssignSchedules')) {
            $job->loadMissing('jobAssignSchedules.jobAssignMaterialIssues.materialIssue.items');
        }

        return $jobs->flatMap(function ($groupJob) {
            return $groupJob->jobAssignSchedules
                ->flatMap(fn ($jas) => $jas->jobAssignMaterialIssues)
                ->map(fn ($jami) => $jami->materialIssue)
                ->filter()
                ->flatMap(function ($materialIssue) {
                    return $materialIssue->items->map(function ($item) use ($materialIssue) {
                        return [
                            'room_key' => $this->normalizeRoomNameForStatus($item->room_name),
                            'material_issue' => $materialIssue,
                        ];
                    });
                });
        })
        ->filter(fn ($row) => $row['room_key'] !== '')
        ->groupBy('room_key')
        ->map(fn ($rows) => $rows->pluck('material_issue')->unique('id')->values());
    }

    private function normalizeRoomNameForStatus(?string $roomName): string
    {
        return strtolower(trim((string) $roomName));
    }
}

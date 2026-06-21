<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class AromaChange extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        // Reference Information
        'change_number',
        'contract_id',
        'building_id',
        'unit_id',
        'room_id',
        'contract_room_id',
        
        // Aroma Information
        'previous_aroma_code',
        'previous_aroma_name',
        'new_aroma',
        'effective_schedule_id',
        'new_aroma_code',
        'new_aroma_name',
        
        // Product Type/Category (MOM6: Luxo, Artisan, Signature are ProductTypes → now ProductCategories)
        'previous_product_type_id', // Legacy
        'previous_product_category_id',
        'previous_product_id', // Added for precise product tracking
        'new_product_type_id', // Legacy
        'new_product_category_id',
        'new_product_id', // Added for precise product tracking
        
        // Change Details
        'change_reason',
        'change_description',
        'change_notes',
        
        // Approval Flow
        'status',
        'approval_notes',
        
        // Dates
        'requested_at',
        'approved_at',
        'applied_at',
        
        // User Tracking
        'requested_by',
        'approved_by',
        'applied_by',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'applied_at' => 'datetime'
    ];

    // Constants for status (MOM6 Flow)
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Grade order for brand line, client-confirmed: Luxo (1, lowest) < Artisan (2) < Signature (3, highest).
     * Switching to an equal or higher grade is auto-approved; switching down requires manager approval.
     */
    const BRAND_LINE_GRADE = [
        'luxo' => 1,
        'artisan' => 2,
        'signature' => 3,
    ];

    /**
     * Resolve the numeric grade for a brand line name. Returns null when the brand line
     * is unrecognized (unknown brand lines are treated as requiring approval, not auto-approved).
     */
    public static function brandLineGrade(?string $brandLine): ?int
    {
        $key = strtolower(trim((string) $brandLine));

        return self::BRAND_LINE_GRADE[$key] ?? null;
    }

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function room()
    {
        return $this->belongsTo(MasterRoom::class);
    }

    public function contractRoom()
    {
        return $this->belongsTo(ContractRoom::class);
    }

    public function previousProductType()
    {
        return $this->belongsTo(ProductType::class, 'previous_product_type_id');
    }

    public function newProductType()
    {
        return $this->belongsTo(ProductType::class, 'new_product_type_id');
    }

    public function previousProductCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'previous_product_category_id');
    }

    public function newProductCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'new_product_category_id');
    }

    public function previousProduct()
    {
        return $this->belongsTo(MasterProduct::class, 'previous_product_id');
    }

    public function newProduct()
    {
        return $this->belongsTo(MasterProduct::class, 'new_product_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
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

    public function scopeByProductType($query, $productTypeId)
    {
        return $query->where(function($q) use ($productTypeId) {
            $q->where('previous_product_type_id', $productTypeId)
              ->orWhere('new_product_type_id', $productTypeId);
        });
    }

    public function scopeByProductCategory($query, $productCategoryId)
    {
        return $query->where(function($q) use ($productCategoryId) {
            $q->where('previous_product_category_id', $productCategoryId)
              ->orWhere('new_product_category_id', $productCategoryId);
        });
    }

    public function scopeByBuilding($query, $buildingId)
    {
        return $query->where('building_id', $buildingId);
    }

    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }


    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled'
        ];
        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_DRAFT => 'badge-secondary',
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
            self::STATUS_COMPLETED => 'badge-primary',
            self::STATUS_CANCELLED => 'badge-dark'
        ];
        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getPreviousProductTypeNameAttribute()
    {
        return $this->previousProductType ? $this->previousProductType->name : '-';
    }

    public function getNewProductTypeNameAttribute()
    {
        return $this->newProductType ? $this->newProductType->name : '-';
    }

    // Boolean Accessors
    public function getIsDraftAttribute()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function getIsPendingAttribute()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsApprovedAttribute()
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getIsRejectedAttribute()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsCancelledAttribute()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    // Methods (MOM6 Flow)
    
    /**
     * Submit for approval
     */
    public function submitForApproval()
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'requested_at' => now()
        ]);
    }

    /**
     * Approve aroma change (MOM6 Flow)
     */
    public function approve($approvedBy, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approval_notes' => $notes,
            'approved_at' => now()
        ]);
        
        \Log::info("Aroma Change approved: {$this->change_number} by User ID: {$approvedBy}");
    }

    /**
     * Reject aroma change
     */
    public function reject($approvedBy, $notes = null)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approvedBy,
            'approval_notes' => $notes,
            'approved_at' => now()
        ]);
        
        \Log::info("Aroma Change rejected: {$this->change_number} by User ID: {$approvedBy}");
    }

    /**
     * Apply aroma change (MOM6: After approved, apply the change)
     */
    public function applyChange($appliedBy)
    {
        $resolvedNewProductId = $this->resolveAromaMaterialProductId($this->new_product_id ?: $this->new_product_type_id);

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'new_product_id' => $resolvedNewProductId ?: $this->new_product_id,
            'applied_by' => $appliedBy,
            'applied_at' => now()
        ]);
        
        // Handle Scheduled Change (Advanced)
        // NOTE: QuotationRoom is the source of truth for ALL future material generation
        // (see JobAssignMaterialIssueController::aroma_product_id usage), so it must always
        // be updated below regardless of the effective date. Only the *retroactive* sync of
        // already-existing MaterialIssue/MaterialIssueItem rows is gated by the effective
        // date below — we must not rewrite material already issued for periods before it.
        $effectiveDate = null;
        if ($this->effective_schedule_id) {
            $schedule = \App\Models\JobSchedule::find($this->effective_schedule_id);
            if ($schedule) {
                $effectiveDate = $schedule->schedule_date;

                // Find all schedules for this room from the effective date onwards
                // Logic: Same Contract, Same Room, Date >= Effective Date
                $futureSchedules = \App\Models\JobSchedule::whereHas('jobAdvice', function($q) {
                        $q->where('contract_id', $this->contract_id);
                    })
                    ->where('room_id', $this->room_id)
                    ->whereDate('schedule_date', '>=', $effectiveDate)
                    ->whereIn('status', ['scheduled', 'new_job', 'assign_team', 'assign_material'])
                    ->get();

                foreach ($futureSchedules as $jobSchedule) {
                    // Update the JobSchedule remark or specific field with new aroma
                    // Since JobSchedule doesn't have aroma_id, we append to internal_notes or notes
                    $newNote = "[Aroma Change Applied] New Aroma: {$this->new_aroma_name} (Code: {$this->new_aroma_code})";

                    // If we want to be more robust, we would need a pivot table or dedicated field on JobSchedule.
                    // For now, appending to notes is the safest minimally invasive change.
                    $currentNotes = $jobSchedule->internal_notes ?? '';
                    if (!str_contains($currentNotes, "New Aroma: {$this->new_aroma_name}")) {
                         $jobSchedule->update([
                            'internal_notes' => trim($currentNotes . "\n" . $newNote)
                        ]);
                    }
                }

                \Log::info("Scheduled Aroma Change applied to " . $futureSchedules->count() . " schedules starting " . $effectiveDate);
            }
        }

        // Update QuotationRoom with new aroma (Source of Truth for Dropdown in Create Request)
        if ($this->contract && $this->contract->quotation) {
            $quotationRoom = \App\Models\QuotationRoom::where('quotation_id', $this->contract->quotation_id)
                ->where('room_id', $this->room_id)
                ->first();
                
            if ($quotationRoom) {
                // Determine variant string - if code equals variant, use it, else null or empty
                // Assuming new_aroma_code holds the variant if applicable or the product code
                $quotationRoom->update([
                    'aroma_product_id' => $resolvedNewProductId ?: $this->new_product_id ?: $this->new_product_type_id,
                    // If the aroma code is just the variant name, we can use it. 
                    // To be safe, we just update the ID which is the main link.
                    // But for display consistency, let's update variant too if it seems like a variant name.
                    'aroma_variant' => $this->new_aroma_name ?: $this->new_aroma_code
                ]);
                 \Log::info("QuotationRoom aroma updated: Room ID {$this->room_id}, New Product ID: {$this->new_product_id}");
            }
        }

        // -------------------------------------------------------------------
        // MOM: SYNC TO MATERIAL ISSUES (Reflect in Job Schedule Detail)
        // -------------------------------------------------------------------
        try {
            $oldProdId = $this->previous_product_id;
            $newProdId = $resolvedNewProductId ?: $this->new_product_id ?: $this->new_product_type_id;

            if ($oldProdId && $newProdId && $oldProdId != $newProdId) {
                // Find active MaterialIssues for this contract.
                // If an effective date was set, only resync issues for job schedules on/after
                // that date — material already issued for earlier periods must stay untouched.
                $materialIssues = \App\Models\MaterialIssue::whereHas('jobAssignMaterialIssues.jobAssignSchedule.jobSchedule.jobAdvice', function($q) {
                        $q->where('contract_id', $this->contract_id);
                    })
                    ->when($effectiveDate, function ($query) use ($effectiveDate) {
                        $query->whereHas('jobAssignMaterialIssues.jobAssignSchedule.jobSchedule', function ($q) use ($effectiveDate) {
                            $q->whereDate('schedule_date', '>=', $effectiveDate);
                        });
                    })
                    ->whereIn('status', ['pending', 'approved', 'issued']) // Include 'issued' for visible sync
                    ->get();

                foreach ($materialIssues as $mi) {
                    // Update header if it matches (for legacy direct product tracking)
                    if ($mi->product_id == $oldProdId) {
                        $mi->update([
                            'previous_product_id' => $mi->product_id,
                            'product_id' => $newProdId,
                            'product_changed_at' => now(),
                            'product_changed_by' => $appliedBy,
                            'product_change_note' => "Aroma changed via Aroma Change: {$this->change_number}"
                        ]);
                    }

                    // Update individual items
                    $updatedCount = \App\Models\MaterialIssueItem::where('material_issue_id', $mi->id)
                        ->where('product_id', $oldProdId)
                        ->where('room_name', $this->room->room_name) // MOM: Filter by specific room name to avoid overwriting other rooms
                        ->update([
                            'product_id' => $newProdId,
                            'updated_by' => $appliedBy,
                            'notes' => \DB::raw("CONCAT(COALESCE(notes, ''), '\n[SYSTEM] Aroma changed via Aroma Change Module: {$this->change_number}')")
                        ]);
                    
                    if ($updatedCount > 0) {
                        \Log::info("Aroma Sync SUCCESS: MaterialIssue ID {$mi->id}, updated {$updatedCount} items to Product ID {$newProdId}");
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error("AromaChange Sync Logic Error: " . $e->getMessage());
        }
        
        \Log::info("Aroma Change applied and completed: {$this->change_number} by User ID: {$appliedBy}");
    }

    private function resolveAromaMaterialProductId($productId): ?int
    {
        $product = $productId ? MasterProduct::with(['productCategory', 'productType', 'packagingSize'])->find($productId) : null;
        if (!$product) {
            return null;
        }

        $isValidAromaMaterial = function ($candidate): bool {
            $haystack = strtolower(implode(' ', array_filter([
                $candidate->name ?? null,
                $candidate->variant_name ?? null,
                $candidate->brand_line ?? null,
                $candidate->productCategory?->name ?? null,
                $candidate->productType?->name ?? null,
            ])));

            $isUnit = (bool) ($candidate->productCategory?->is_unit ?? $candidate->productType?->is_unit ?? false);
            $hasSerialNumber = (bool) ($candidate->productCategory?->has_serial_number ?? $candidate->productType?->has_serial_number ?? false);

            return !$isUnit
                && !$hasSerialNumber
                && !str_contains(strtolower($candidate->name ?? ''), 'test')
                && (str_contains($haystack, 'refill')
                    || str_contains($haystack, 'aroma')
                    || str_contains($haystack, 'fragrance')
                    || str_contains($haystack, 'scent')
                    || str_contains($haystack, 'luxo')
                    || str_contains($haystack, 'artisan')
                    || str_contains($haystack, 'signature'));
        };

        if ($isValidAromaMaterial($product)) {
            return $product->id;
        }

        $variantName = trim((string) $product->variant_name);
        if ($variantName === '') {
            return $product->id;
        }

        $brandLine = trim(strtolower((string) $product->brand_line));

        $replacement = MasterProduct::with(['productCategory', 'productType', 'packagingSize'])
            ->where('is_active', true)
            ->where('variant_name', $variantName)
            ->when($brandLine !== '', fn ($query) => $query->whereRaw('LOWER(TRIM(brand_line)) = ?', [$brandLine]))
            ->get()
            ->filter($isValidAromaMaterial)
            ->sortBy(function ($candidate) {
                $categoryName = strtolower($candidate->productCategory?->name ?? '');
                $packageName = strtolower($candidate->packagingSize?->name ?? '');

                return [
                    $packageName === '100ml' ? 0 : 1,
                    str_contains($categoryName, 'refill') ? 0 : 1,
                    $candidate->id,
                ];
            })
            ->first();

        return $replacement?->id ?? $product->id;
    }

    /**
     * Cancel aroma change
     */
    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED
        ]);
        
        \Log::info("Aroma Change cancelled: {$this->change_number}");
    }

    /**
     * Generate unique change number (Format: [BRANCH]-AC/YY-MM/XXXX)
     * @param Contract|null $contract
     * @return string
     */
    public static function generateChangeNumber($contract = null)
    {
        // Get branch code from contract, default to 'JKT'
        $branchCode = 'JKT';
        if ($contract && $contract->branch) {
            $branchCode = strtoupper($contract->branch->code ?? 'JKT');
        } elseif ($contract && $contract->branch_code) {
            $branchCode = strtoupper($contract->branch_code);
        }
        
        $prefix = $branchCode . '-AC';
        $yearMonth = date('y-m');
        
        // Get last active record to determine next sequence
        $lastRecord = self::where('change_number', 'like', $prefix . '/' . $yearMonth . '/%')
            ->orderBy('change_number', 'desc')
            ->first();

        if ($lastRecord) {
            // Extract number from last active record (e.g. 0001)
            $parts = explode('/', $lastRecord->change_number);
            $lastCount = intval(end($parts));
            $count = $lastCount + 1;
        } else {
            $count = 1;
        }
        
        $candidateNumber = $prefix . '/' . $yearMonth . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);

        // Check if this number is taken by a soft-deleted record and force delete it to allow reuse
        $trashCollision = self::onlyTrashed()->where('change_number', $candidateNumber)->first();
        if ($trashCollision) {
            $trashCollision->forceDelete();
        }

        return $candidateNumber;
    }

}

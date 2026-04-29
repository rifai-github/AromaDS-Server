<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Models\Branch;
use App\Models\RentalPrice;

class LostUnitReport extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'report_number',
        'contract_id',
        'contract_number',
        'company_name',
        'master_rental_id',
        'room_id',
        'building_id',
        'branch_id',
        'lost_unit_price',
        'original_price',
        'is_price_manual',
        'rental_name',
        'room_name',
        'report_by',
        'approve_by',
        'finalized_at',
        'finalized_by',
        'approved_at',
        'invoice_id',
        'status',
        'remark',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'status' => 'string',
        'lost_unit_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'is_price_manual' => 'boolean',
        'finalized_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function items()
    {
        return $this->hasMany(LostUnitReportItem::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
    }

    public function room()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'report_by', 'id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approve_by', 'id');
    }

    public function finalizer()
    {
        return $this->belongsTo(User::class, 'finalized_by', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function createdBy()
    {
        return $this->creator();
    }

    public function updatedBy()
    {
        return $this->updater();
    }

    // Relationship to Job Advice (auto-generated)
    public function jobAdvice()
    {
        return $this->hasOne(JobAdvice::class, 'reference_number', 'report_number');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeWaitingForApproval($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByReporter($query, $reporterId)
    {
        return $query->where('report_by', $reporterId);
    }

    public function scopeByApprover($query, $approverId)
    {
        return $query->where('approve_by', $approverId);
    }

    public function scopeByCompany($query, $companyName)
    {
        return $query->where('company_name', 'like', "%{$companyName}%");
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'draft' => 'Draft',
            'submitted' => 'Waiting for Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
        return $statuses[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getIsApprovedAttribute()
    {
        return $this->status === 'approved';
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    public function getIsRejectedAttribute()
    {
        return $this->status === 'rejected';
    }

    public function getIsDraftAttribute()
    {
        return $this->status === 'draft';
    }

    public function getIsWaitingForApprovalAttribute()
    {
        return $this->status === 'submitted';
    }

    public function getFormattedLostUnitPriceAttribute()
    {
        return 'Rp ' . number_format($this->lost_unit_price, 0, ',', '.');
    }

    public function getFormattedOriginalPriceAttribute()
    {
        return 'Rp ' . number_format($this->original_price, 0, ',', '.');
    }

    /**
     * Get lost unit price from master rental based on branch
     */
    public function getLostUnitPriceFromMaster()
    {
        if (!$this->master_rental_id) {
            return 0;
        }

        $masterRental = $this->masterRental;
        if (!$masterRental) {
            return 0;
        }

        // Try to get price from RentalPrice based on branch
        $branchId = $this->branch_id;
        
        // If branch_id not set on model, try to derive it from building
        if (!$branchId && $this->building_id) {
            $building = $this->building;
            if ($building) {
                $branch = null;
                if ($building->city_id) {
                    $branch = Branch::where('city_id', $building->city_id)->where('is_active', true)->first();
                }
                if (!$branch && $building->province_id) {
                    $branch = Branch::where('province_id', $building->province_id)->where('is_active', true)->first();
                }
                $branchId = $branch ? $branch->id : null;
            }
        }

        if ($branchId) {
            $rentalPrice = RentalPrice::where('master_rental_id', $this->master_rental_id)
                ->where('branch_id', $branchId)
                ->first();
            
            if ($rentalPrice && $rentalPrice->lost_unit_price > 0) {
                return $rentalPrice->lost_unit_price;
            }
        }

        // Fallback to master rental's default lost_unit_price
        return $masterRental->lost_unit_price ?? 0;
    }

    /**
     * Check if can be unposted
     */
    public function canUnpost()
    {
        if ($this->status === 'submitted') {
            return true;
        }

        if ($this->status === 'approved') {
            // Can only unpost if no invoice or invoice is deleted
            return !$this->invoice_id || !$this->invoice;
        }

        return false;
    }

    /**
     * Check if has invoice
     */
    public function hasInvoice()
    {
        return $this->invoice_id && $this->invoice;
    }
}

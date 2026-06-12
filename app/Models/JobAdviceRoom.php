<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobAdviceRoom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_advice_id',
        'contract_room_id',
        'quotation_room_id', 
        'contract_rental_id', // Add source rental ID
        'quotation_rental_id', // Add source rental ID
        'quotation_detail_id', // Add source detail ID (fallback)
        'rental_product_id',
        'room_name',
        'rental_name',
        'quantity',
        'qty_free',
        'rental_specification_ml',
        'rental_has_installation',
        'rental_has_service',
        'status',
        'install_job_schedule_id',
        'service_job_schedule_id',
        'remove_job_schedule_id',
        'is_trial',
        'unit_already_installed',
        'existing_unit_on_wall_id',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'qty_free' => 'decimal:2',
        'rental_specification_ml' => 'decimal:2',
        'rental_has_installation' => 'boolean',
        'rental_has_service' => 'boolean',
        'is_trial' => 'boolean',
        'unit_already_installed' => 'boolean',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Relationships
     */
    
    public function jobAdvice()
    {
        return $this->belongsTo(JobAdvice::class);
    }

    public function contractRoom()
    {
        return $this->belongsTo(ContractRoom::class);
    }

    public function quotationRoom()
    {
        return $this->belongsTo(QuotationRoom::class);
    }

    public function rentalProduct()
    {
        return $this->belongsTo(MasterRental::class, 'rental_product_id');
    }

    public function getOperationalQuantityAttribute()
    {
        return (float) ($this->quantity ?? 0);
    }

    public function installJobSchedule()
    {
        return $this->belongsTo(JobSchedule::class, 'install_job_schedule_id');
    }

    public function serviceJobSchedule()
    {
        return $this->belongsTo(JobSchedule::class, 'service_job_schedule_id');
    }

    public function removeJobSchedule()
    {
        return $this->belongsTo(JobSchedule::class, 'remove_job_schedule_id');
    }

    public function existingUnitOnWall()
    {
        return $this->belongsTo(UnitOnWall::class, 'existing_unit_on_wall_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Alias for consistency
    public function updater()
    {
        return $this->updatedBy();
    }

    /**
     * Scopes
     */
    
    public function scopeByJobAdvice($query, $jobAdviceId)
    {
        return $query->where('job_advice_id', $jobAdviceId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeTrial($query)
    {
        return $query->where('is_trial', true);
    }

    public function scopeUnitAlreadyInstalled($query)
    {
        return $query->where('unit_already_installed', true);
    }

    /**
     * Accessors
     */
    
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_SCHEDULED => 'info',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary'
        };
    }

    public function getStatusTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    /**
     * Methods
     */

    /**
     * Check if unit already installed from previous trial
     * 
     * @return bool
     */
    public function checkUnitAlreadyInstalled()
    {
        // Check if there's an existing ACTIVE UnitOnWall for this room
        // User Request: "kuncinya adalah serial number atau SN di unit on wall"
        // Must have a wall-active status AND a valid Serial Number.
        $query = UnitOnWall::whereIn('status', ['active', 'installed', 'on_wall', 'on wall', 'onwall'])
               ->whereNotNull('serial_number_id');

        if ($this->contract_room_id) {
            $query->where('contract_room_id', $this->contract_room_id);
        } elseif ($this->quotation_room_id) {
             // Fallback for Install Free from Quotation
             // Find room_id from quotation room
             $quotationRoom = QuotationRoom::find($this->quotation_room_id);
             if ($quotationRoom && $quotationRoom->room_id) {
                 $query->where('room_id', $quotationRoom->room_id);
             } else {
                 return false;
             }
        } else {
            return false;
        }

        $existingUnit = $query->first();

        if ($existingUnit) {
            $this->update([
                'unit_already_installed' => true,
                'existing_unit_on_wall_id' => $existingUnit->id
            ]);
            
            return true;
        }

        return false;
    }

    /**
     * Calculate rental specification (total ML from components)
     * 
     * @return float
     */
    public function calculateRentalSpecification()
    {
        if (!$this->rentalProduct) {
            return 0;
        }

        // MOM6: Calculate from rental details (not rental components)
        // Load rental details with product and packaging size
        $rental = $this->rentalProduct;
        $rental->load(['rentalDetails.masterProduct.packagingSize']);
        
        // MOM6: Formula: quantity × package size (ml) = total ML
        // Contoh: quantity = 1, package size = 500ml → total = 500ml
        // Contoh: quantity = 2, package size = 250ml → total = 500ml
        $totalML = 0;
        foreach ($rental->rentalDetails as $detail) {
            if ($detail->masterProduct && $detail->masterProduct->packagingSize) {
                // Extract number from package size (e.g., "500ml" → 500)
                $packageSizeName = $detail->masterProduct->packagingSize->name;
                preg_match('/(\d+)/', $packageSizeName, $matches);
                $packageSizeML = isset($matches[1]) ? (float)$matches[1] : 0;
                
                // Calculate: quantity × package size (ml)
                $detailML = ($detail->quantity ?? 0) * $packageSizeML;
                $totalML += $detailML;
            }
        }

        $this->update(['rental_specification_ml' => $totalML]);

        return $totalML;
    }

    /**
     * Mark as scheduled
     */
    public function markAsScheduled()
    {
        $this->update(['status' => self::STATUS_SCHEDULED]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted()
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Mark as cancelled
     */
    public function markAsCancelled()
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }
}


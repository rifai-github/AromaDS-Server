<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use App\Traits\HasComprehensiveAuditTrail;

class QuotationRental extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail, AutoFilterable;

    protected $fillable = [
        'quotation_id',
        'quotation_room_id',
        'master_rental_id',
        'aroma_name',
        'quantity',
        'qty_free',
        'unit_price',
        'total_price',
        'rental_specifications',
        'rental_notes',
        'has_bottom_price',
        'bottom_price',
        'requires_approval',
        'top_mismatch_warning',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'qty_free' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'bottom_price' => 'decimal:2',
        'has_bottom_price' => 'boolean',
        'requires_approval' => 'boolean'
    ];

    // Relationships
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function quotationRoom()
    {
        return $this->belongsTo(QuotationRoom::class);
    }

    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
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
    public function scopeByQuotation($query, $quotationId)
    {
        return $query->where('quotation_id', $quotationId);
    }

    public function scopeByQuotationRoom($query, $quotationRoomId)
    {
        return $query->where('quotation_room_id', $quotationRoomId);
    }

    public function scopeByMasterRental($query, $masterRentalId)
    {
        return $query->where('master_rental_id', $masterRentalId);
    }

    public function scopeByAromaName($query, $aromaName)
    {
        return $query->where('aroma_name', 'like', '%' . $aromaName . '%');
    }

    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    public function scopeHasBottomPrice($query)
    {
        return $query->where('has_bottom_price', true);
    }

    public function scopeWithoutBottomPrice($query)
    {
        return $query->where('has_bottom_price', false);
    }

    // Accessors
    public function getFormattedUnitPriceAttribute()
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getFormattedTotalPriceAttribute()
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    public function getFormattedBottomPriceAttribute()
    {
        return $this->bottom_price ? 'Rp ' . number_format($this->bottom_price, 0, ',', '.') : '-';
    }

    public function getApprovalStatusAttribute()
    {
        if ($this->requires_approval) {
            return 'Memerlukan Persetujuan';
        }
        return 'Tidak Memerlukan Persetujuan';
    }

    public function getBottomPriceStatusAttribute()
    {
        if ($this->has_bottom_price) {
            return 'Ada Harga Terendah';
        }
        return 'Belum Ada Harga Terendah';
    }

    // Methods
    public function calculateTotalPrice()
    {
        return $this->quantity * $this->unit_price;
    }

    public function getOperationalQuantityAttribute()
    {
        return (float) ($this->quantity ?? 0) + (float) ($this->qty_free ?? 0);
    }

    public function updateTotalPrice()
    {
        $this->update([
            'total_price' => $this->calculateTotalPrice()
        ]);
    }

    public function checkBottomPrice()
    {
        // Get applicable price slab for this rental and quantity
        $priceSlab = MasterPriceSlab::getApplicableSlab($this->master_rental_id, $this->quantity);
        
        if ($priceSlab) {
            // Calculate bottom price using price slab percentage
            $basePrice = $this->masterRental->monthly_price ?? $this->masterRental->daily_price ?? 0;
            $discountAmount = $priceSlab->calculateDiscount($basePrice);
            $bottomPrice = $basePrice - $discountAmount;
            
            $this->update([
                'has_bottom_price' => true,
                'bottom_price' => $bottomPrice,
                'requires_approval' => $this->unit_price < $bottomPrice
            ]);
        } else {
            // No price slab found, check if there's a rental bottom price
            // for this branch and the quotation's offer type (harian/bulanan).
            $rentalBottomPrice = RentalBottomPrice::active()
                ->where('master_rental_id', $this->master_rental_id)
                ->where('branch_id', $this->quotation->prospect->customer->branch_id ?? null)
                ->where('offer_type', $this->quotation->rental_unit ?? 'bulan')
                ->first();

            if ($rentalBottomPrice) {
                $this->update([
                    'has_bottom_price' => true,
                    'bottom_price' => $rentalBottomPrice->bottom_price,
                    'requires_approval' => $this->unit_price < $rentalBottomPrice->bottom_price
                ]);
            } else {
                $this->update([
                    'has_bottom_price' => false,
                    'bottom_price' => null,
                    'requires_approval' => true
                ]);
            }
        }

        $this->validateTermOfPayment();
        $this->save();
    }

    /**
     * Cross-check ToP against the rental's component replacement frequency
     * (per spec: NOT the service frequency). Skipped for Job Order
     * (rental_unit === 'hari') since daily jobs don't have recurring billing periods.
     *
     * Non-blocking: stores a warning on the record instead of failing,
     * since there's no prior enforcement and we don't want to block
     * quotations that were previously accepted under looser rules.
     */
    public function validateTermOfPayment()
    {
        $quotation = $this->quotation;

        if (!$quotation || $quotation->rental_unit === 'hari') {
            $this->top_mismatch_warning = null;
            return true;
        }

        $topMonths = $this->parseTermOfPaymentMonths($quotation->terms_of_payment);
        if ($topMonths === null) {
            $this->top_mismatch_warning = null;
            return true;
        }

        $replacementComponent = $this->masterRental?->rentalComponents()
            ->whereNotNull('replacement_frequency_months')
            ->where('replacement_frequency_months', '>', 0)
            ->orderBy('replacement_frequency_months')
            ->first();

        if (!$replacementComponent) {
            $this->top_mismatch_warning = null;
            return true;
        }

        $replacementMonths = (int) $replacementComponent->replacement_frequency_months;

        if ($topMonths > $replacementMonths) {
            $this->top_mismatch_warning = "ToP {$topMonths} bulan lebih panjang dari frekuensi penggantian komponen rental ({$replacementMonths} bulan).";
            return false;
        }

        $this->top_mismatch_warning = null;
        return true;
    }

    /**
     * Parse a ToP label like "1 bulan 1x" or "3 bulan" into a month count.
     * Returns null when the label doesn't express a month-based period (e.g. "Cash", "Tahunan" handled as 12).
     */
    private function parseTermOfPaymentMonths(?string $term): ?int
    {
        $term = strtolower(trim($term ?? ''));

        if ($term === '' || $term === 'cash' || $term === 'tunai') {
            return null;
        }

        if (preg_match('/(\d+)\s*bulan/i', $term, $matches)) {
            return (int) $matches[1];
        }

        if (str_contains($term, 'tahunan') || str_contains($term, 'annual')) {
            return 12;
        }

        if (str_contains($term, 'triwulan') || str_contains($term, 'quarter')) {
            return 3;
        }

        if (str_contains($term, 'semester')) {
            return 6;
        }

        return null;
    }

    public function isBelowBottomPrice()
    {
        return $this->has_bottom_price && $this->unit_price < $this->bottom_price;
    }

    public function isAboveBottomPrice()
    {
        return $this->has_bottom_price && $this->unit_price >= $this->bottom_price;
    }

    public function getApplicablePriceSlab()
    {
        return MasterPriceSlab::getApplicableSlab($this->master_rental_id, $this->quantity);
    }

    public function getPriceSlabDiscountPercentage()
    {
        $priceSlab = $this->getApplicablePriceSlab();
        return $priceSlab ? $priceSlab->discount_percentage : 0;
    }

    public function getPriceSlabName()
    {
        $priceSlab = $this->getApplicablePriceSlab();
        return $priceSlab ? $priceSlab->slab_name : 'No Slab';
    }

    public function calculatePriceSlabBottomPrice()
    {
        $priceSlab = $this->getApplicablePriceSlab();
        
        if (!$priceSlab) {
            return null;
        }

        $basePrice = $this->masterRental->monthly_price ?? $this->masterRental->daily_price ?? 0;
        $discountAmount = $priceSlab->calculateDiscount($basePrice);
        return $basePrice - $discountAmount;
    }

    public function getPriceSlabInfo()
    {
        $priceSlab = $this->getApplicablePriceSlab();
        
        if (!$priceSlab) {
            return [
                'slab_name' => 'No Slab',
                'discount_percentage' => 0,
                'bottom_price' => null,
                'has_slab' => false
            ];
        }

        return [
            'slab_name' => $priceSlab->slab_name,
            'discount_percentage' => $priceSlab->discount_percentage,
            'bottom_price' => $this->calculatePriceSlabBottomPrice(),
            'has_slab' => true,
            'quantity_range' => $priceSlab->quantity_range
        ];
    }
}

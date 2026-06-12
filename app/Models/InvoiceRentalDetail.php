<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceRentalDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'job_number',
        'building_name',
        'room_name',
        'rental_name',
        'quantity',
        'qty_free',
        'unit_price',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'qty_free' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function jobSchedule()
    {
        return $this->belongsTo(JobAssignSchedule::class, 'job_number', 'job_number');
    }

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_name', 'building_name');
    }

    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class, 'rental_name', 'rental_name');
    }

    // Scopes
    public function scopeByInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeByJobNumber($query, $jobNumber)
    {
        return $query->where('job_number', $jobNumber);
    }

    public function scopeByBuilding($query, $buildingName)
    {
        return $query->where('building_name', 'like', "%{$buildingName}%");
    }

    public function scopeByRoom($query, $roomName)
    {
        return $query->where('room_name', 'like', "%{$roomName}%");
    }

    public function scopeByRental($query, $rentalName)
    {
        return $query->where('rental_name', 'like', "%{$rentalName}%");
    }

    public function scopeByPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('unit_price', [$minPrice, $maxPrice]);
    }

    public function scopeByTotalRange($query, $minTotal, $maxTotal)
    {
        return $query->whereBetween('total_price', [$minTotal, $maxTotal]);
    }

    // Accessors
    public function getUnitPriceFormattedAttribute()
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getTotalPriceFormattedAttribute()
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    public function getQuantityFormattedAttribute()
    {
        return number_format($this->quantity, 0, ',', '.');
    }

    public function getQtyFreeFormattedAttribute()
    {
        return number_format($this->qty_free ?? 0, 0, ',', '.');
    }

    public function getBuildingNameFormattedAttribute()
    {
        return ucwords($this->building_name);
    }

    public function getRoomNameFormattedAttribute()
    {
        return ucwords($this->room_name);
    }

    public function getRentalNameFormattedAttribute()
    {
        return ucwords($this->rental_name);
    }

    public function getJobNumberFormattedAttribute()
    {
        return $this->job_number ? strtoupper($this->job_number) : 'N/A';
    }

    public function getSubtotalAttribute()
    {
        return $this->unit_price * $this->quantity;
    }

    public function getSubtotalFormattedAttribute()
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    // Methods
    public function calculateTotal()
    {
        $this->total_price = $this->unit_price * $this->quantity;
        return $this->total_price;
    }

    public function recalculateTotal()
    {
        $this->total_price = $this->calculateTotal();
        $this->save();
    }

    public function getInvoiceInfo()
    {
        return $this->invoice;
    }

    public function getJobInfo()
    {
        return $this->jobSchedule;
    }

    public function getBuildingInfo()
    {
        return $this->building;
    }

    public function getRentalInfo()
    {
        return $this->masterRental;
    }

    public function getCustomerInfo()
    {
        return $this->invoice->customer ?? null;
    }

    public function getContractInfo()
    {
        return $this->invoice->contract ?? null;
    }

    public function getNetAmount()
    {
        return $this->total_price;
    }

    public function getGrossAmount()
    {
        return $this->subtotal;
    }

    public function getRelatedJobSchedule()
    {
        return JobAssignSchedule::where('job_number', $this->job_number)->first();
    }

    public function getRelatedBuilding()
    {
        return Building::where('building_name', $this->building_name)->first();
    }

    public function getRelatedRental()
    {
        return MasterRental::where('rental_name', $this->rental_name)->first();
    }
}

<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\MasterRental;

class InvoiceRentalDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'master_rental_id',
        'job_no',
        'building_name',
        'rental_name',
        'room_name',
        'quantity',
        'qty_free',
        'unit_price',
        'total_price',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'qty_free' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
    }

    public function jobSchedule()
    {
        return $this->belongsTo(\App\Models\JobSchedule::class, 'job_no', 'job_number');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors & Mutators
    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 0);
    }

    public function getFormattedQtyFreeAttribute()
    {
        return number_format($this->qty_free ?? 0, 0);
    }

    public function getFormattedUnitPriceAttribute()
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getFormattedTotalPriceAttribute()
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }
}

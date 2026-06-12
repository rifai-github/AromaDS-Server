<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\AutoFilterable;

class QuotationDetail extends Model
{
    use HasFactory, AutoFilterable;

    protected $fillable = [
        'quotation_id',
        'survey_id',
        'room_id',
        'master_rental_id',
        'rental_alias',
        'remark',
        'room_name',
        'quantity',
        'qty_free',
        'unit_price',
        'total_price',
        'specifications',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'qty_free' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2'
    ];

    // Relationships
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
    }

    public function room()
    {
        return $this->belongsTo(SurveyDetail::class, 'room_id');
    }

    public function masterRoom()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    // Accessor for dynamic room name
    public function getRoomNameAttribute($value)
    {
        // Debugging
        // \Log::info("QuotationDetail getRoomNameAttribute ID: {$this->id}, room_id: {$this->room_id}");
        
        // If related survey detail exists, prefer its dynamic name
        if ($this->room) {
             // \Log::info(" - Found SurveyDetail ID: {$this->room->id}. SurveyDetail->room: " . ($this->room->room ? 'YES' : 'NO'));
             if ($this->room->room) {
                 // \Log::info(" - MasterRoom Name: " . $this->room->room->room_name);
             }
            return $this->room->room_name;
        }
        return $value;
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

    public function scopeByMasterRental($query, $masterRentalId)
    {
        return $query->where('master_rental_id', $masterRentalId);
    }

    public function scopeByRoom($query, $roomName)
    {
        return $query->where('room_name', 'like', '%' . $roomName . '%');
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

    // Methods
    public function calculateTotalPrice()
    {
        return $this->quantity * $this->unit_price;
    }

    public function getOperationalQuantityAttribute()
    {
        return (float) ($this->quantity ?? 0) + (float) ($this->qty_free ?? 0);
    }
}

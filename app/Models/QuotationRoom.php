<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class QuotationRoom extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'quotation_id',
        'room_id',
        'aroma_product_id', // New field for aroma/variant selection
        'aroma_variant', // New field for aroma variant name
        'room_name',
        'room_specifications',
        'room_total_amount',
        'room_notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'room_total_amount' => 'decimal:2'
    ];

    // Relationships
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function room()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    public function aromaProduct()
    {
        return $this->belongsTo(MasterProduct::class, 'aroma_product_id');
    }

    public function quotationRentals()
    {
        return $this->hasMany(QuotationRental::class);
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

    public function scopeByRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeByRoomName($query, $roomName)
    {
        return $query->where('room_name', 'like', '%' . $roomName . '%');
    }

    // Accessors
    public function getFormattedRoomTotalAmountAttribute()
    {
        return 'Rp ' . number_format($this->room_total_amount, 0, ',', '.');
    }

    // Methods
    public function calculateTotalAmount()
    {
        return $this->quotationRentals()->sum('total_price');
    }

    public function updateTotalAmount()
    {
        $this->update([
            'room_total_amount' => $this->calculateTotalAmount()
        ]);
    }

    public function getRentalCount()
    {
        return $this->quotationRentals()->count();
    }

    public function hasRentals()
    {
        return $this->quotationRentals()->exists();
    }
}
<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceFormRental extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_form_id',
        'job_number',
        'building_name',
        'room_name',
        'rental_name',
        'quantity',
        'price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    // Relationships
    public function invoiceForm()
    {
        return $this->belongsTo(InvoiceForm::class);
    }

    // Accessors
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2);
    }

    public function getTotalAttribute()
    {
        return $this->quantity * $this->price;
    }

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total, 2);
    }
}

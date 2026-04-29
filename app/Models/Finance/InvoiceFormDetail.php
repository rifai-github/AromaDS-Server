<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceFormDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_form_id',
        'reference_number',
        'item_name',
        'price',
        'quantity',
        'discount',
        'total',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
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

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total, 2);
    }
}

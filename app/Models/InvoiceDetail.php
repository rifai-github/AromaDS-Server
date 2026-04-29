<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
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

    // Scopes
    public function scopeByInvoice($query, $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    // Accessors
    public function getPriceFormattedAttribute()
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getTotalFormattedAttribute()
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    public function getQuantityFormattedAttribute()
    {
        return number_format($this->quantity, 2, ',', '.');
    }

    public function getSubtotalAttribute()
    {
        return $this->unit_price * $this->quantity;
    }

    public function getSubtotalFormattedAttribute()
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    public function getItemNameFormattedAttribute()
    {
        return $this->description;
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
}

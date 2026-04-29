<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_adjustment_id',
        'master_product_id',
        'adjustment_qty',
        'adjustment_type',
        'notes'
    ];

    protected $casts = [
        'adjustment_qty' => 'integer'
    ];

    public function stockAdjustment()
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function masterProduct()
    {
        return $this->belongsTo(MasterProduct::class);
    }
}

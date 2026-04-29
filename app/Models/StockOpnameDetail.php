<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'master_product_id',
        'system_stock',
        'physical_stock',
        'variance',
        'notes',
        'scanned_serial_numbers'
    ];

    protected $casts = [
        'system_stock' => 'integer',
        'physical_stock' => 'integer',
        'variance' => 'integer',
        'scanned_serial_numbers' => 'array'
    ];

    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function masterProduct()
    {
        return $this->belongsTo(MasterProduct::class);
    }
}

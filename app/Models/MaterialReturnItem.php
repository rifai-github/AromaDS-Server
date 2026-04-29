<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialReturnItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'material_return_id',
        'material_issue_item_id',
        'product_id',
        'room_name',
        'room_id',
        'quantity',
        'convert',
        'bom_quantity',
        'unit_price',
        'total_price',
        'notes',
        'return_reason',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'convert' => 'decimal:2',
        'bom_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    
    public function materialReturn()
    {
        return $this->belongsTo(MaterialReturn::class);
    }

    public function materialIssueItem()
    {
        return $this->belongsTo(MaterialIssueItem::class);
    }

    public function product()
    {
        return $this->belongsTo(MasterProduct::class, 'product_id');
    }

    public function room()
    {
        return $this->belongsTo(MasterRoom::class, 'room_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    
    public function scopeByMaterialReturn($query, $materialReturnId)
    {
        return $query->where('material_return_id', $materialReturnId);
    }

    public function scopeByRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }
}

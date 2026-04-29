<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialIssueItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'material_issue_id',
        'job_assign_schedule_id',
        'product_id',
        'room_name',
        'quantity',
        'convert',
        'bom_quantity',
        'unit_price',
        'total_price',
        'notes',
        'is_copied',
        'usage_status',
        'used_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'convert' => 'decimal:2',
        'bom_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_copied' => 'boolean',
        'used_at' => 'datetime',
    ];

    // Relationships
    public function materialIssue()
    {
        return $this->belongsTo(MaterialIssue::class);
    }

    public function product()
    {
        return $this->belongsTo(MasterProduct::class, 'product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

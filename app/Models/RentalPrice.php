<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalPrice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'master_rental_id',
        'branch_id',
        'daily_price',
        'monthly_price',
        'lost_unit_price',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'daily_price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'lost_unit_price' => 'decimal:2',
    ];

    // Relationships
    public function masterRental()
    {
        return $this->belongsTo(MasterRental::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
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
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByMasterRental($query, $masterRentalId)
    {
        return $query->where('master_rental_id', $masterRentalId);
    }

    // Accessors
    public function getFormattedDailyPriceAttribute()
    {
        return 'Rp ' . number_format($this->daily_price, 0, ',', '.');
    }

    public function getFormattedMonthlyPriceAttribute()
    {
        return 'Rp ' . number_format($this->monthly_price, 0, ',', '.');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;

class RentalComponentProduct extends Model
{
    use HasFactory, SoftDeletes, HasComprehensiveAuditTrail;

    protected $fillable = [
        'rental_component_id',
        'master_product_id',
        'is_preferred',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_preferred' => 'boolean',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function rentalComponent()
    {
        return $this->belongsTo(RentalComponent::class);
    }

    public function masterProduct()
    {
        return $this->belongsTo(MasterProduct::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePreferred($query)
    {
        return $query->where('is_preferred', true);
    }

    // Helper methods
    public function setAsPreferred()
    {
        // Remove preferred status from other products in the same component
        $this->rentalComponent->componentProducts()
            ->where('id', '!=', $this->id)
            ->update(['is_preferred' => false, 'updated_by' => auth()->id()]);
        
        // Set this product as preferred
        $this->update([
            'is_preferred' => true,
            'updated_by' => auth()->id()
        ]);
    }

    public function removeFromPreferred()
    {
        $this->update([
            'is_preferred' => false,
            'updated_by' => auth()->id()
        ]);
    }
}
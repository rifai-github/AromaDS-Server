<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialIssue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'issue_number',
        'warehouse_id',
        'issued_by',
        'team_id',
        'product_id',
        'previous_product_id', // MOM9: Track previous product when changed
        'product_change_note', // MOM9: Note for product change (wajib ketika produk di-edit)
        'product_changed_at', // MOM9: Date when product was changed
        'product_changed_by', // MOM9: User who changed the product
        'issue_date',
        'quantity',
        'unit_price',
        'total_amount',
        'requested_by',
        'request_reason',
        'status',
        'priority',
        'description',
        'notes',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'product_changed_at' => 'datetime', // MOM9: Date when product was changed
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function product()
    {
        return $this->belongsTo(MasterProduct::class, 'product_id');
    }

    /**
     * MOM9: Relationship to previous product (when product is changed)
     */
    public function previousProduct()
    {
        return $this->belongsTo(MasterProduct::class, 'previous_product_id');
    }

    /**
     * MOM9: Relationship to user who changed the product
     */
    public function productChangedBy()
    {
        return $this->belongsTo(User::class, 'product_changed_by');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function jobAssignMaterialIssues()
    {
        return $this->hasMany(JobAssignMaterialIssue::class);
    }

    /**
     * MOM11: Relationship to material issue items (detail items)
     */
    public function items()
    {
        return $this->hasMany(MaterialIssueItem::class);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    // Note: These scopes removed as they reference columns that don't exist in material_issues table
    // period, rental_name, product_type are not in material_issues table
    // issue_status should be 'status'

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'status-pending',
            'approved' => 'status-approved',
            'rejected' => 'status-rejected',
            'issued' => 'status-issued',
        ];

        return $badges[$this->status] ?? 'status-pending';
    }

    public function getStatusTextAttribute()
    {
        return ucfirst($this->status);
    }

    public function getIssueDateFormattedAttribute()
    {
        return $this->issue_date ? $this->issue_date->format('d M Y') : 'No Date';
    }

    public function getQuantityFormattedAttribute()
    {
        return $this->quantity ? "{$this->quantity} units" : 'No Quantity';
    }

    public function getUnitPriceFormattedAttribute()
    {
        return $this->unit_price ? 'Rp ' . number_format($this->unit_price, 0, ',', '.') : 'Rp 0';
    }

    public function getTotalAmountFormattedAttribute()
    {
        return $this->total_amount ? 'Rp ' . number_format($this->total_amount, 0, ',', '.') : 'Rp 0';
    }

    public function getPriorityBadgeAttribute()
    {
        $badges = [
            'low' => 'status-low',
            'medium' => 'status-medium',
            'high' => 'status-high',
            'urgent' => 'status-urgent',
        ];

        return $badges[$this->priority] ?? 'status-medium';
    }

    public function getPriorityTextAttribute()
    {
        return ucfirst($this->priority);
    }

    // Methods
    public function canApprove()
    {
        return $this->status === 'pending';
    }

    public function canReject()
    {
        return $this->status === 'pending';
    }

    public function canIssue()
    {
        return $this->status === 'approved';
    }

    public function approve()
    {
        $this->update(['status' => 'approved']);
    }

    public function reject()
    {
        $this->update(['status' => 'rejected']);
    }

    public function issue()
    {
        $this->update(['status' => 'issued']);
    }

    public function calculateTotalAmount()
    {
        $this->total_amount = $this->quantity * $this->unit_price;
        $this->save();
    }

    public function getTeamName()
    {
        return $this->team ? $this->team->team_name : 'No Team';
    }

    public function getProductName()
    {
        return $this->product ? $this->product->name : 'No Product';
    }

    public function getWarehouseName()
    {
        return $this->warehouse ? $this->warehouse->name : 'No Warehouse';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Invoice;
use Carbon\Carbon;
use App\Http\Traits\AutoFilterable;

class FakturPajak extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'faktur_number',
        'invoice_id',
        'faktur_date',
        'tax_amount',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'faktur_date' => 'date',
        'tax_amount' => 'decimal:2',
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Accessors
    public function getFormattedFakturDateAttribute()
    {
        return $this->faktur_date ? $this->faktur_date->format('d/m/Y') : '-';
    }

    public function getFormattedFakturDateWithMonthAttribute()
    {
        return $this->faktur_date ? $this->faktur_date->format('d/m/Y') : '-';
    }

    public function getFormattedTaxAmountAttribute()
    {
        return number_format($this->tax_amount, 2);
    }

    public function getStatusTextAttribute()
    {
        return ucfirst($this->status);
    }

    // Methods
    public function submit()
    {
        $this->update(['status' => 'submitted']);
    }

    public function approve()
    {
        $this->update(['status' => 'approved']);
    }

    public function reject()
    {
        $this->update(['status' => 'rejected']);
    }

    public function draft()
    {
        $this->update(['status' => 'draft']);
    }

    // Auto-generate faktur number
    public static function generateFakturNumber()
    {
        $year = date('Y');
        $month = date('m');
        
        // Get the last faktur number for this year/month
        $lastFaktur = self::where('faktur_number', 'like', "FP{$year}{$month}%")
            ->orderBy('faktur_number', 'desc')
            ->first();
        
        if ($lastFaktur) {
            $lastNumber = (int) substr($lastFaktur->faktur_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return "FP{$year}{$month}" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($fakturPajak) {
            if (empty($fakturPajak->faktur_number)) {
                $fakturPajak->faktur_number = self::generateFakturNumber();
            }
            $fakturPajak->created_by = auth()->id();
        });
        
        static::updating(function ($fakturPajak) {
            $fakturPajak->updated_by = auth()->id();
        });
    }
}

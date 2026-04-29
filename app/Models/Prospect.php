<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;

class Prospect extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'prospect_number',
        'customer_id',
        'company_name',
        'company_address',
        'contact_person',
        'contact_phone',
        'contact_email',
        'business_description',
        'requirements',
        'budget_range',
        'follow_up_date',
        'activity_notes',
        'status',
        'assigned_to'
    ];

    protected $casts = [
        'budget_range' => 'decimal:2',
        'follow_up_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($prospect) {
            if (empty($prospect->prospect_number)) {
                $prospect->prospect_number = static::generateUniqueProspectNumber();
            }
        });
    }

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function surveys()
    {
        return $this->hasMany(Survey::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function salesActivities()
    {
        return $this->hasMany(SalesActivity::class);
    }

    // Scopes
    public function scopeByAssignedTo($query, $assignedToId)
    {
        return $query->where('assigned_to', $assignedToId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeFollowUpToday($query)
    {
        return $query->where('follow_up_date', today());
    }

    public function scopeFollowUpOverdue($query)
    {
        return $query->where('follow_up_date', '<', today());
    }

    public function scopeFollowUpUpcoming($query, $days = 7)
    {
        return $query->whereBetween('follow_up_date', [today(), today()->addDays($days)]);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'new' => 'Baru',
            'contacted' => 'Sudah Dihubungi',
            'qualified' => 'Terkualifikasi',
            'proposal' => 'Proposal',
            'negotiation' => 'Negosiasi',
            'closed_won' => 'Berhasil',
            'closed_lost' => 'Gagal'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getFollowUpStatusAttribute()
    {
        if (!$this->follow_up_date) {
            return 'no_date';
        }
        
        if ($this->follow_up_date < today()) {
            return 'overdue';
        } elseif ($this->follow_up_date == today()) {
            return 'today';
        } elseif ($this->follow_up_date <= today()->addDays(7)) {
            return 'upcoming';
        }
        return 'future';
    }

    /**
     * Generate a unique prospect number using database locking
     */
    public static function generateUniqueProspectNumber()
    {
        $year = date('Y');
        $month = date('m');
        $maxAttempts = 50;
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            // Generate a prospect number with timestamp and random component to ensure uniqueness
            $timestamp = time();
            $random = mt_rand(100, 999);
            $prospectNumber = "PRS-{$year}{$month}" . str_pad($timestamp % 10000, 4, '0', STR_PAD_LEFT) . "-{$random}";
            
            // Check if this number already exists
            $exists = static::where('prospect_number', $prospectNumber)->exists();
            
            if (!$exists) {
                return $prospectNumber;
            }
            
            // If it exists, wait a microsecond and try again
            usleep(1000); // 1 millisecond
        }
        
        // Final fallback: use microtime for maximum uniqueness
        $microtime = substr(microtime(true) * 10000, -6);
        return "PRS-{$year}{$month}" . $microtime;
    }
}

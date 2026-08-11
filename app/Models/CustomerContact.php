<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use Illuminate\Support\Str;

class CustomerContact extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    /**
     * Boot method untuk validasi model
     */
    protected static function boot()
    {
        parent::boot();

        // customer_id boleh kosong saat creating: dipakai oleh flow "Tambah Kontak
        // Cepat" dari modal Create Customer (context 'customer-create'), di mana
        // customer belum tersimpan/punya ID. Contact-nya di-link belakangan oleh
        // CustomerController::store() via contact_ids. Cukup log untuk observability,
        // jangan blok pembuatannya.
        static::creating(function ($contact) {
            if (empty($contact->customer_id)) {
                \Log::info('Creating contact without customer_id (expected for pending customer-create flow)', [
                    'name' => $contact->name,
                    'position' => $contact->position,
                ]);
            }
        });
    }

    protected $fillable = [
        'customer_id',
        'salutation',
        'position',
        'name',
        'email',
        'email_verified_at',
        'email_verification_token',
        'email_verification_sent_at',
        'phone',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'email_verification_sent_at' => 'datetime',
    ];

    // ==========================================
    // Email Verification Methods
    // ==========================================
    
    /**
     * Check if email is verified
     */
    public function isEmailVerified(): bool
    {
        return !empty($this->email) && !empty($this->email_verified_at);
    }
    
    /**
     * Check if verification email has been sent but not yet verified
     */
    public function isEmailVerificationPending(): bool
    {
        return !empty($this->email) 
            && !empty($this->email_verification_sent_at) 
            && empty($this->email_verified_at);
    }
    
    /**
     * Generate a new verification token
     */
    public function generateVerificationToken(): string
    {
        $token = Str::random(64);
        $this->email_verification_token = $token;
        $this->email_verification_sent_at = now();
        $this->email_verified_at = null; // Reset verification status
        $this->save();
        
        return $token;
    }
    
    /**
     * Mark email as verified
     */
    public function markEmailAsVerified(): bool
    {
        $this->email_verified_at = now();
        $this->email_verification_token = null; // Clear token after verification
        return $this->save();
    }
    
    /**
     * Clear verification status (when email is changed)
     */
    public function clearEmailVerification(): void
    {
        $this->email_verified_at = null;
        $this->email_verification_token = null;
        $this->email_verification_sent_at = null;
    }

    // ==========================================
    // Relationships
    // ==========================================
    
    public function customer()
    {
        // Legacy: Original one-to-many relationship
        return $this->belongsTo(Customer::class);
    }

    /**
     * Multi PIC: Many-to-many relationship with Customers
     * A contact can serve as PIC for multiple customers
     */
    public function customers()
    {
        return $this->belongsToMany(Customer::class, 'customer_customer_contact')
            ->withPivot('is_primary')
            ->withTimestamps();
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

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByPosition($query, $position)
    {
        return $query->where('position', 'like', "%{$position}%");
    }

    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where('email', 'like', "%{$email}%");
    }

    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone', 'like', "%{$phone}%");
    }

    // Accessors
    public function getIsActiveTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getFormattedPhoneAttribute()
    {
        return $this->phone ?: '-';
    }

    public function getFormattedEmailAttribute()
    {
        return $this->email ?: '-';
    }
    
    /**
     * Get email verification status text
     */
    public function getEmailVerificationStatusAttribute(): string
    {
        if ($this->isEmailVerified()) {
            return 'verified';
        }
        if ($this->isEmailVerificationPending()) {
            return 'pending';
        }
        return 'not_sent';
    }
    
    /**
     * Get email verification status badge HTML
     */
    public function getEmailVerificationBadgeAttribute(): string
    {
        if (empty($this->email)) {
            return '';
        }
        
        if ($this->isEmailVerified()) {
            return '<span class="badge bg-success" title="Email terverifikasi pada ' . $this->email_verified_at->format('d M Y H:i') . '"><i class="fas fa-check-circle"></i></span>';
        }
        if ($this->isEmailVerificationPending()) {
            return '<span class="badge bg-warning text-dark" title="Menunggu verifikasi, dikirim pada ' . $this->email_verification_sent_at->format('d M Y H:i') . '"><i class="fas fa-clock"></i></span>';
        }
        return '<span class="badge bg-danger" title="Belum diverifikasi"><i class="fas fa-times-circle"></i></span>';
    }
}


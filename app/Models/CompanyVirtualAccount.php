<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\AutoFilterable;

class CompanyVirtualAccount extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $table = 'company_virtual_accounts';

    protected $fillable = [
        'company_id',
        'customer_id',
        'bank_payment_id',
        'account_number',
        'account_name',
        'description',
        'daily_limit',
        'monthly_limit',
        'is_active',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'daily_limit' => 'decimal:2',
        'monthly_limit' => 'decimal:2'
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function bankPayment()
    {
        return $this->belongsTo(BankPayment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function transactions()
    {
        return $this->hasMany(VirtualAccountTransaction::class, 'virtual_account_id');
    }

    public function assignments()
    {
        return $this->hasMany(CompanyVirtualAccountAssignment::class, 'virtual_account_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByBank($query, $bankId)
    {
        return $query->where('bank_id', $bankId);
    }

    // Accessors
    public function getIsActiveTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getFormattedAccountNumberAttribute()
    {
        return $this->account_number ? str_pad($this->account_number, 16, '0', STR_PAD_LEFT) : null;
    }

    /**
     * Resolve an incoming bank VA number to its account.
     *
     * Stored numbers are whatever was entered/imported, with no single width:
     * most legacy Catalyst rows are 6 digits while newer generated ones are 11,
     * so the incoming value is matched as given rather than reformatted.
     *
     * Matching goes strictly narrowest-first:
     *   1. exact string, as stored
     *   2. digits-only exact (tolerates separators/spacing from the bank)
     *   3. zero-padding difference only (e.g. '000007' vs '7')
     *
     * Precision always outranks activeness: an exact hit wins even when it is
     * inactive, because '000007' and '7' are different accounts belonging to
     * different customers, and letting a looser tier answer first would credit
     * the payment to the wrong one. Activeness only breaks ties *within* a
     * tier, and a tie that stays ambiguous is refused rather than guessed.
     */
    public static function resolveByAccountNumber(?string $incoming): ?self
    {
        $incoming = trim((string) $incoming);

        if ($incoming === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $incoming);
        $unpadded = ltrim($digits, '0');

        $tiers = [
            'exact' => fn () => static::query()->where('account_number', $incoming)->get(),
        ];

        if ($digits !== '') {
            $tiers['digits'] = fn () => static::query()
                ->whereRaw("REPLACE(REPLACE(account_number, ' ', ''), '-', '') = ?", [$digits])
                ->get();
        }

        if ($unpadded !== '') {
            // Compared in PHP so the rule behaves identically on MySQL and on the
            // SQLite test database (TRIM(LEADING ...) is MySQL-only).
            $tiers['padding'] = fn () => static::query()
                ->where('account_number', 'like', '%'.$unpadded)
                ->get()
                ->filter(function ($candidate) use ($unpadded) {
                    $candidateDigits = preg_replace('/\D/', '', (string) $candidate->account_number);

                    return ltrim($candidateDigits, '0') === $unpadded;
                })
                ->values();
        }

        foreach ($tiers as $tier => $lookup) {
            $matches = $lookup();

            if ($matches->count() === 1) {
                return $matches->first();
            }

            if ($matches->count() > 1) {
                $active = $matches->where('is_active', true)->values();

                if ($active->count() === 1) {
                    return $active->first();
                }

                Log::warning('VA number is ambiguous; refusing to guess.', [
                    'incoming' => $incoming,
                    'tier' => $tier,
                    'candidates' => $matches->pluck('account_number', 'id')->all(),
                ]);

                return null;
            }
        }

        return null;
    }

    // Business Logic Methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function activate()
    {
        $this->is_active = true;
        $this->save();
    }

    public function deactivate()
    {
        $this->is_active = false;
        $this->save();
    }

    public function getTotalTransactions()
    {
        return $this->transactions()->count();
    }

    public function getCompletedTransactions()
    {
        return $this->transactions()->where('status', 'completed')->count();
    }

    public function getTotalAmount()
    {
        return $this->transactions()->where('status', 'completed')->sum('amount');
    }

    // Static Methods
    public static function generateAccountNumber($companyId = null, $bankId = null)
    {
        // Format: 5 digit company code + 6 digit free digits = 11 digit total
        // Get company code from setting or use default
        $companyCode = self::getCompanyCode();
        
        // Get next available 6-digit sequence
        $nextSequence = self::getNextAvailableSequence($companyCode);
        
        // Combine: 5 digit company code + 6 digit sequence = 11 digits
        $accountNumber = $companyCode . $nextSequence;
        
        return $accountNumber;
    }

    /**
     * Get company code from setting or return default
     */
    public static function getCompanyCode()
    {
        // Try to get from CompanySetting (using company_id = 7 as default)
        $companyId = 7;
        $companyCode = CompanySetting::get($companyId, 'va_company_code', null);
        
        if ($companyCode && strlen($companyCode) === 5 && ctype_digit($companyCode)) {
            return $companyCode;
        }
        
        // Return default
        return '88997';
    }

    /**
     * Get next available 6-digit sequence for company code
     */
    private static function getNextAvailableSequence(string $companyCode): string
    {
        $maxSequence = 0;
        
        // Check in company_virtual_accounts table
        $lastVA = self::where('account_number', 'like', $companyCode . '%')
            ->whereRaw('LENGTH(account_number) = 11')
            ->orderBy('account_number', 'desc')
            ->first();
        
        if ($lastVA && strlen($lastVA->account_number) === 11) {
            // Extract 6-digit sequence (last 6 digits)
            $sequence = (int)substr($lastVA->account_number, 5, 6);
            $maxSequence = max($maxSequence, $sequence);
        }
        
        // Check in BillingGroup table (if exists)
        if (class_exists(\App\Models\Finance\BillingGroup::class)) {
            $lastBillingGroup = \App\Models\Finance\BillingGroup::where('virtual_account_number', 'like', $companyCode . '%')
                ->whereRaw('LENGTH(virtual_account_number) = 11')
                ->orderBy('virtual_account_number', 'desc')
                ->first();
            
            if ($lastBillingGroup && strlen($lastBillingGroup->virtual_account_number) === 11) {
                $sequence = (int)substr($lastBillingGroup->virtual_account_number, 5, 6);
                $maxSequence = max($maxSequence, $sequence);
            }
        }
        
        // Check in VirtualAccount table (if exists)
        if (class_exists(\App\Models\Finance\VirtualAccount::class)) {
            $lastVirtualAccount = \App\Models\Finance\VirtualAccount::where('va_number', 'like', $companyCode . '%')
                ->whereRaw('LENGTH(va_number) = 11')
                ->orderBy('va_number', 'desc')
                ->first();
            
            if ($lastVirtualAccount && strlen($lastVirtualAccount->va_number) === 11) {
                $sequence = (int)substr($lastVirtualAccount->va_number, 5, 6);
                $maxSequence = max($maxSequence, $sequence);
            }
        }
        
        // Start from 000001 if no existing VA found
        if ($maxSequence === 0) {
            $maxSequence = 0;
        }
        
        // Get next sequence
        $nextSequence = $maxSequence + 1;
        
        // Check if we've reached the maximum (999999)
        if ($nextSequence > 999999) {
            throw new \Exception('Maximum VA sequence reached (999999)');
        }
        
        // Pad to 6 digits
        return str_pad($nextSequence, 6, '0', STR_PAD_LEFT);
    }
}

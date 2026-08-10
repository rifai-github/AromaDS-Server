<?php

namespace App\Models;

use App\Http\Traits\AutoFilterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobAdvice extends Model
{
    use AutoFilterable, HasFactory, SoftDeletes;

    protected $table = 'job_advices';

    /**
     * Prefix nomor JA yang dibuat oleh importer Catalyst
     * (lihat CatalystMasterDataImporter::catalystJobAdviceNumber()).
     *
     * JA ini hanya catatan riwayat install dari sistem lama: statusnya langsung
     * 'approved' tapi tidak pernah menghasilkan job schedule maupun unit-on-wall.
     */
    public const MIGRATED_NUMBER_PREFIX = 'JA-CATALYST-';

    protected $fillable = [
        'job_advice_number',
        'type',
        'reference_number',
        'company_name',
        'contract_id',
        'quotation_id', // MOM9: For Install Free from Quotation
        'customer_id',
        'request_by',
        'customer_contact_id',
        'submitted_by',
        'submitted_at',
        'expected_date',
        'first_service_date',
        'remove_date',
        'status',
        'date_approval',
        'approved_by',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'with_invoicing',
        'with_materials',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expected_date' => 'date',
        'first_service_date' => 'date',
        'remove_date' => 'date',
        'date_approval' => 'datetime',
        'submitted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'with_invoicing' => 'boolean',
        'with_materials' => 'boolean',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerContact()
    {
        return $this->belongsTo(CustomerContact::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * MOM9: Relationship to Quotation (for Install Free flow)
     */
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by', 'id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'request_by', 'id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by', 'id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    /**
     * Relationship to Job Advice Rooms (MOM6 requirement)
     * "di sana ada rental ruangan. pilih ruangan karena based on ruangan"
     */
    public function rooms()
    {
        return $this->hasMany(JobAdviceRoom::class);
    }

    /**
     * Relationship to Job Schedules created from this JA
     */
    public function jobSchedules()
    {
        return $this->hasMany(JobSchedule::class, 'job_advice_id');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeBySubmitter($query, $submitterId)
    {
        return $query->where('submitted_by', $submitterId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expected_date', [$startDate, $endDate]);
    }

    public function scopeByCompany($query, $companyName)
    {
        return $query->where('company_name', 'like', "%{$companyName}%");
    }

    public function scopePending($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeWithInvoicing($query)
    {
        return $query->where('with_invoicing', true);
    }

    public function scopeWithMaterials($query)
    {
        return $query->where('with_materials', true);
    }

    /**
     * JA hasil migrasi Catalyst — riwayat install lama, bukan pekerjaan berjalan.
     */
    public function scopeMigrated($query)
    {
        return $query->where('job_advice_number', 'like', self::MIGRATED_NUMBER_PREFIX.'%');
    }

    public function scopeNotMigrated($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('job_advice_number')
                ->orWhere('job_advice_number', 'not like', self::MIGRATED_NUMBER_PREFIX.'%');
        });
    }

    public function isMigrated(): bool
    {
        return str_starts_with((string) $this->job_advice_number, self::MIGRATED_NUMBER_PREFIX);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getTypeTextAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getFormattedExpectedDateAttribute()
    {
        return $this->expected_date ? $this->expected_date->format('d/m/Y') : '-';
    }

    public function getFormattedRemoveDateAttribute()
    {
        return $this->remove_date ? $this->remove_date->format('d/m/Y') : '-';
    }

    public function getFormattedApprovalDateAttribute()
    {
        return $this->date_approval ? $this->date_approval->format('d/m/Y') : '-';
    }

    public function getIsApprovedAttribute()
    {
        return $this->status === 'approved';
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'draft';
    }

    public function getIsRejectedAttribute()
    {
        return $this->status === 'rejected';
    }

    // Relationship to Lost Unit Report (if auto-generated)
    public function lostUnitReport()
    {
        return $this->belongsTo(LostUnitReport::class, 'reference_number', 'report_number');
    }
}

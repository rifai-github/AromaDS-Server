<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\AutoFilterable;
use Illuminate\Support\Str;

class Survey extends Model
{
    use HasFactory, SoftDeletes, AutoFilterable;

    protected $fillable = [
        'survey_number',
        'customer_id', // Changed from prospect_id to customer_id
        'building_id',
        'building_location_detail',
        'surveyor_id',
        'marketing_id',
        'survey_date',
        'survey_location',
        'temperature',
        'latitude',
        'longitude',
        'company_name',
        'customer_type',
        'contact_person',
        'email',
        'phone_1',
        'phone_2',
        'position',
        'building_name',
        'address_1',
        'address_2',
        'province',
        'city',
        'district',
        'village',
        'postal_code',
        'survey_result',
        'recommendations',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'survey_date' => 'date',
        'temperature' => 'decimal:2',
    ];

    // Accessor for 3-digit month format display
    public function getSurveyDateFormattedAttribute()
    {
        if (!$this->survey_date) {
            return null;
        }
        
        $day = $this->survey_date->format('d');
        $month = str_pad($this->survey_date->format('n'), 3, '0', STR_PAD_LEFT);
        $year = $this->survey_date->format('Y');
        
        return $day . '/' . $month . '/' . $year;
    }

    // Relationships
    // public function prospect()
    // {
    //     return $this->belongsTo(Prospect::class);
    // }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function surveyor()
    {
        return $this->belongsTo(User::class, 'surveyor_id');
    }

    public function marketing()
    {
        return $this->belongsTo(User::class, 'marketing_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function surveyDetails()
    {
        return $this->hasMany(SurveyDetail::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    // Multiple Survey Enhancement relationships
    public function quotationSurveys()
    {
        return $this->hasMany(QuotationSurvey::class);
    }

    public function multipleQuotations()
    {
        return $this->belongsToMany(Quotation::class, 'quotation_surveys')
            ->withPivot('added_at', 'added_by', 'sort_order')
            ->withTimestamps();
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    // Scopes
    public function scopeBySurveyor($query, $surveyorId)
    {
        return $query->where('surveyor_id', $surveyorId);
    }

    public function scopeByProspect($query, $prospectId)
    {
        return $query->where('prospect_id', $prospectId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('survey_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getStatusTextAttribute()
    {
        $statuses = [
            'draft' => 'Draft',
            'submitted' => 'Waiting for Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getDisplayCompanyNameAttribute()
    {
        return $this->normalizeDisplayValue($this->company_name)
            ?? $this->normalizeDisplayValue($this->customer?->name)
            ?? '-';
    }

    public function getDisplayEmailAttribute()
    {
        return $this->normalizeDisplayValue($this->customer?->email)
            ?? $this->normalizeDisplayValue($this->email)
            ?? '-';
    }

    public function getDisplayPhoneOneAttribute()
    {
        return $this->normalizeDisplayValue($this->customer?->phone)
            ?? $this->normalizeDisplayValue($this->phone_1)
            ?? '-';
    }

    public function getDisplayBuildingNameAttribute()
    {
        return $this->normalizeDisplayValue($this->building_name)
            ?? $this->normalizeDisplayValue($this->building?->building_name)
            ?? '-';
    }

    public function getDisplayAddressOneAttribute()
    {
        return $this->normalizeDisplayValue($this->address_1)
            ?? $this->normalizeDisplayValue($this->building?->alamat_1)
            ?? $this->normalizeDisplayValue($this->building?->address);
    }

    public function getDisplayAddressTwoAttribute()
    {
        return $this->normalizeDisplayValue($this->address_2)
            ?? $this->normalizeDisplayValue($this->building?->alamat_2);
    }

    public function getDisplayProvinceAttribute()
    {
        return $this->normalizeDisplayValue($this->province)
            ?? $this->normalizeDisplayValue($this->building?->province?->name);
    }

    public function getDisplayCityAttribute()
    {
        return $this->normalizeDisplayValue($this->city)
            ?? $this->normalizeDisplayValue($this->building?->city?->name)
            ?? $this->extractLabelValue($this->building?->notes, 'CityName')
            ?? $this->extractLabelValue($this->building?->notes, 'AreaCity');
    }

    public function getDisplayDistrictAttribute()
    {
        return $this->normalizeDisplayValue($this->district)
            ?? $this->normalizeDisplayValue($this->building?->district?->name);
    }

    public function getDisplayVillageAttribute()
    {
        return $this->normalizeDisplayValue($this->village)
            ?? $this->normalizeDisplayValue($this->building?->subdistrict?->name);
    }

    public function getDisplayPostalCodeAttribute()
    {
        return $this->normalizeDisplayValue($this->postal_code)
            ?? $this->normalizeDisplayValue($this->building?->postal_code)
            ?? $this->normalizeDisplayValue($this->building?->kode_pos);
    }

    public function getTemperatureTextAttribute()
    {
        return $this->temperature ? $this->temperature . '°C' : '-';
    }

    public function getIsApprovedAttribute()
    {
        return $this->status === 'approved';
    }

    public function getIsPendingAttribute()
    {
        return in_array($this->status, ['submitted', 'in_progress']);
    }

    public function getIsUsedInQuotationAttribute()
    {
        // Check standard relationship
        if ($this->quotations()->exists()) {
            return true;
        }

        // Check pivot table for multiple survey selection
        if ($this->quotationSurveys()->exists()) {
            return true;
        }

        return false;
    }

    protected function normalizeDisplayValue($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '-' || Str::lower($value) === 'null') {
            return null;
        }

        return $value;
    }

    protected function extractLabelValue($text, string $label): ?string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            if (str_starts_with($line, $label . ':')) {
                return $this->normalizeDisplayValue(trim(substr($line, strlen($label) + 1)));
            }
        }

        return null;
    }
}

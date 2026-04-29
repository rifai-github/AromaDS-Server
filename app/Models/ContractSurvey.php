<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'survey_id',
        'added_at',
        'added_by',
        'sort_order'
    ];

    protected $casts = [
        'added_at' => 'datetime'
    ];

    // Relationships
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    // Scopes
    public function scopeByContract($query, $contractId)
    {
        return $query->where('contract_id', $contractId);
    }

    public function scopeBySurvey($query, $surveyId)
    {
        return $query->where('survey_id', $surveyId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('added_at');
    }

    // Static methods
    public static function addSurveyToContract($contractId, $surveyId, $userId = null)
    {
        return self::create([
            'contract_id' => $contractId,
            'survey_id' => $surveyId,
            'added_at' => now(),
            'added_by' => $userId ?? auth()->id(),
            'sort_order' => self::where('contract_id', $contractId)->max('sort_order') + 1
        ]);
    }

    public static function removeSurveyFromContract($contractId, $surveyId)
    {
        return self::where('contract_id', $contractId)
            ->where('survey_id', $surveyId)
            ->delete();
    }

    public static function getContractSurveys($contractId)
    {
        return self::where('contract_id', $contractId)
            ->with(['survey.building', 'survey.customer', 'addedBy'])
            ->ordered()
            ->get();
    }

    public static function getSurveyContracts($surveyId)
    {
        return self::where('survey_id', $surveyId)
            ->with(['contract.customer', 'contract.marketing', 'addedBy'])
            ->ordered()
            ->get();
    }
}

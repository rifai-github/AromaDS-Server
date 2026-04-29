<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'survey_id',
        'added_at',
        'added_by',
        'sort_order'
    ];

    protected $casts = [
        'added_at' => 'datetime'
    ];

    // Relationships
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
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
    public function scopeByQuotation($query, $quotationId)
    {
        return $query->where('quotation_id', $quotationId);
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
    public static function addSurveyToQuotation($quotationId, $surveyId, $userId = null)
    {
        return self::create([
            'quotation_id' => $quotationId,
            'survey_id' => $surveyId,
            'added_at' => now(),
            'added_by' => $userId ?? auth()->id(),
            'sort_order' => self::where('quotation_id', $quotationId)->max('sort_order') + 1
        ]);
    }

    public static function removeSurveyFromQuotation($quotationId, $surveyId)
    {
        return self::where('quotation_id', $quotationId)
            ->where('survey_id', $surveyId)
            ->delete();
    }

    public static function getQuotationSurveys($quotationId)
    {
        return self::where('quotation_id', $quotationId)
            ->with(['survey.building', 'survey.customer', 'addedBy'])
            ->ordered()
            ->get();
    }

    public static function getSurveyQuotations($surveyId)
    {
        return self::where('survey_id', $surveyId)
            ->with(['quotation.prospect', 'quotation.marketing', 'addedBy'])
            ->ordered()
            ->get();
    }
}
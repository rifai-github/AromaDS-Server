<?php

namespace App\Services\Marketing;

use App\Models\Quotation;
use App\Models\Survey;
use App\Models\QuotationSurvey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MultipleSurveyService
{
    /**
     * Add survey to quotation
     */
    public function addSurveyToQuotation($quotationId, $surveyId, $userId = null)
    {
        try {
            DB::beginTransaction();

            $quotation = Quotation::findOrFail($quotationId);
            $survey = Survey::findOrFail($surveyId);

            // Check if survey is already added to this quotation
            $existingRelation = QuotationSurvey::where('quotation_id', $quotationId)
                ->where('survey_id', $surveyId)
                ->first();

            if ($existingRelation) {
                throw new \Exception("Survey is already added to this quotation");
            }

            // Check if survey belongs to the same customer
            if ($survey->customer_id !== $quotation->customer_id) {
                throw new \Exception("Survey must belong to the same customer as the quotation");
            }

            // Create quotation-survey relationship
            $quotationSurvey = QuotationSurvey::create([
                'quotation_id' => $quotationId,
                'survey_id' => $surveyId,
                'added_at' => now(),
                'added_by' => $userId ?? auth()->id()
            ]);

            // Update quotation totals if needed
            $this->updateQuotationTotals($quotation);

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Survey added to quotation successfully',
                'data' => [
                    'quotation_id' => $quotationId,
                    'survey_id' => $surveyId,
                    'survey_name' => $survey->building_name ?? 'Survey #' . $survey->survey_number
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to add survey to quotation: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove survey from quotation
     */
    public function removeSurveyFromQuotation($quotationId, $surveyId, $userId = null)
    {
        try {
            DB::beginTransaction();

            $quotation = Quotation::findOrFail($quotationId);
            
            // Check if quotation has more than one survey
            $surveyCount = QuotationSurvey::where('quotation_id', $quotationId)->count();
            if ($surveyCount <= 1) {
                throw new \Exception("Cannot remove the last survey from quotation");
            }

            // Remove quotation-survey relationship
            $removed = QuotationSurvey::where('quotation_id', $quotationId)
                ->where('survey_id', $surveyId)
                ->delete();

            if (!$removed) {
                throw new \Exception("Survey not found in this quotation");
            }

            // Update quotation totals
            $this->updateQuotationTotals($quotation);

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Survey removed from quotation successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to remove survey from quotation: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get surveys for quotation
     */
    public function getQuotationSurveys($quotationId)
    {
        $quotation = Quotation::with(['quotationSurveys.survey.building', 'quotationSurveys.survey.customer'])
            ->findOrFail($quotationId);

        return $quotation->quotationSurveys;
    }

    /**
     * Get available surveys for quotation
     */
    public function getAvailableSurveys($quotationId, $search = null)
    {
        $quotation = Quotation::findOrFail($quotationId);
        
        $query = Survey::where('customer_id', $quotation->customer_id)
            ->whereDoesntHave('quotationSurveys', function($q) use ($quotationId) {
                $q->where('quotation_id', $quotationId);
            });

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('building_name', 'like', '%' . $search . '%')
                  ->orWhere('company_name', 'like', '%' . $search . '%')
                  ->orWhere('survey_number', 'like', '%' . $search . '%');
            });
        }

        return $query->with(['building', 'customer', 'surveyor', 'marketing'])
                    ->orderBy('survey_date', 'desc')
                    ->get();
    }

    /**
     * Bulk add surveys to quotation
     */
    public function bulkAddSurveys($quotationId, $surveyIds, $userId = null)
    {
        try {
            DB::beginTransaction();

            $quotation = Quotation::findOrFail($quotationId);
            $addedSurveys = [];
            $errors = [];

            foreach ($surveyIds as $surveyId) {
                $result = $this->addSurveyToQuotation($quotationId, $surveyId, $userId);
                
                if ($result['status'] === 'success') {
                    $addedSurveys[] = $surveyId;
                } else {
                    $errors[] = [
                        'survey_id' => $surveyId,
                        'error' => $result['message']
                    ];
                }
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Bulk add surveys completed',
                'data' => [
                    'added_surveys' => $addedSurveys,
                    'errors' => $errors,
                    'success_count' => count($addedSurveys),
                    'error_count' => count($errors)
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to bulk add surveys: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update quotation totals based on all surveys
     */
    public function updateQuotationTotals(Quotation $quotation)
    {
        $surveys = $quotation->quotationSurveys()->with('survey.surveyDetails')->get();
        
        $totalAmount = 0;
        $totalDiscount = 0;
        $totalTax = 0;

        foreach ($surveys as $quotationSurvey) {
            $survey = $quotationSurvey->survey;
            
            // Calculate totals from survey details
            $surveyTotal = $survey->surveyDetails()->sum('total_amount');
            $surveyDiscount = $survey->surveyDetails()->sum('discount_amount');
            $surveyTax = $survey->surveyDetails()->sum('tax_amount');
            
            $totalAmount += $surveyTotal;
            $totalDiscount += $surveyDiscount;
            $totalTax += $surveyTax;
        }

        $grandTotal = $totalAmount - $totalDiscount + $totalTax;

        $quotation->update([
            'total_amount' => $totalAmount,
            'discount_amount' => $totalDiscount,
            'tax_amount' => $totalTax,
            'grand_total' => $grandTotal
        ]);

        return [
            'total_amount' => $totalAmount,
            'discount_amount' => $totalDiscount,
            'tax_amount' => $totalTax,
            'grand_total' => $grandTotal
        ];
    }

    /**
     * Get quotation survey statistics
     */
    public function getQuotationSurveyStatistics($quotationId)
    {
        $quotation = Quotation::with('quotationSurveys.survey')->findOrFail($quotationId);
        
        $surveys = $quotation->quotationSurveys;
        $surveyCount = $surveys->count();
        
        $buildings = $surveys->pluck('survey.building_name')->filter()->unique();
        $buildingCount = $buildings->count();
        
        $totalAmount = $quotation->total_amount;
        $averageAmountPerSurvey = $surveyCount > 0 ? $totalAmount / $surveyCount : 0;
        
        return [
            'survey_count' => $surveyCount,
            'building_count' => $buildingCount,
            'total_amount' => $totalAmount,
            'average_amount_per_survey' => $averageAmountPerSurvey,
            'buildings' => $buildings->values()->toArray()
        ];
    }

    /**
     * Validate quotation with multiple surveys
     */
    public function validateQuotationWithMultipleSurveys($quotationId)
    {
        $quotation = Quotation::with('quotationSurveys.survey')->findOrFail($quotationId);
        
        $errors = [];
        $warnings = [];
        
        // Check if quotation has at least one survey
        if ($quotation->quotationSurveys->count() === 0) {
            $errors[] = "Quotation must have at least one survey";
        }
        
        // Check if all surveys belong to the same customer
        $customerIds = $quotation->quotationSurveys->pluck('survey.customer_id')->unique();
        if ($customerIds->count() > 1) {
            $errors[] = "All surveys must belong to the same customer";
        }
        
        // Check if surveys are approved
        $unapprovedSurveys = $quotation->quotationSurveys->filter(function($qs) {
            return $qs->survey->status !== 'approved';
        });
        
        if ($unapprovedSurveys->count() > 0) {
            $warnings[] = "Some surveys are not approved yet";
        }
        
        // Check if surveys have building information
        $surveysWithoutBuilding = $quotation->quotationSurveys->filter(function($qs) {
            return !$qs->survey->building_id;
        });
        
        if ($surveysWithoutBuilding->count() > 0) {
            $warnings[] = "Some surveys don't have building information";
        }
        
        return [
            'is_valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Get survey details for quotation
     */
    public function getSurveyDetailsForQuotation($quotationId, $surveyId)
    {
        $quotationSurvey = QuotationSurvey::where('quotation_id', $quotationId)
            ->where('survey_id', $surveyId)
            ->with(['survey.building', 'survey.customer', 'survey.surveyDetails'])
            ->firstOrFail();

        return $quotationSurvey;
    }

    /**
     * Reorder surveys in quotation
     */
    public function reorderSurveys($quotationId, $surveyOrder, $userId = null)
    {
        try {
            DB::beginTransaction();

            $quotation = Quotation::findOrFail($quotationId);
            
            foreach ($surveyOrder as $index => $surveyId) {
                QuotationSurvey::where('quotation_id', $quotationId)
                    ->where('survey_id', $surveyId)
                    ->update(['sort_order' => $index + 1]);
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Survey order updated successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to reorder surveys: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}

<?php

namespace App\Services\Marketing;

use App\Models\Quotation;
use App\Models\Survey;
use App\Models\Customer;
use App\Models\Prospect;
use App\Models\QuotationSurvey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SurveyCustomerService
{
    /**
     * Add survey from same customer to quotation
     */
    public function addSurveyFromSameCustomer($quotationId, $surveyId, $userId = null)
    {
        try {
            DB::beginTransaction();

            $quotation = Quotation::with('prospect.customer')->findOrFail($quotationId);
            $survey = Survey::with('customer')->findOrFail($surveyId);

            // Check if survey is already added to this quotation
            $existingRelation = QuotationSurvey::where('quotation_id', $quotationId)
                ->where('survey_id', $surveyId)
                ->first();

            if ($existingRelation) {
                throw new \Exception("Survey is already added to this quotation");
            }

            // Check if survey belongs to the same customer as the quotation
            $quotationCustomerId = $quotation->prospect->customer_id;
            $surveyCustomerId = $survey->customer_id;

            if ($quotationCustomerId !== $surveyCustomerId) {
                throw new \Exception("Survey must belong to the same customer as the quotation");
            }

            // Check if customer exists
            if (!$quotationCustomerId || !$surveyCustomerId) {
                throw new \Exception("Customer information is missing for quotation or survey");
            }

            // Create quotation-survey relationship
            $quotationSurvey = QuotationSurvey::create([
                'quotation_id' => $quotationId,
                'survey_id' => $surveyId,
                'added_at' => now(),
                'added_by' => $userId ?? auth()->id()
            ]);

            // Update quotation totals
            $this->updateQuotationTotals($quotation);

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Survey from same customer added to quotation successfully',
                'data' => [
                    'quotation_id' => $quotationId,
                    'survey_id' => $surveyId,
                    'customer_id' => $quotationCustomerId,
                    'survey_name' => $survey->building_name ?? 'Survey #' . $survey->survey_number
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to add survey from same customer: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get surveys from same customer for quotation
     */
    public function getSurveysFromSameCustomer($quotationId, $search = null)
    {
        $quotation = Quotation::with('prospect.customer')->findOrFail($quotationId);
        
        if (!$quotation->prospect->customer_id) {
            return collect();
        }

        $query = Survey::where('customer_id', $quotation->prospect->customer_id)
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

        return $query->with(['customer', 'building', 'surveyor', 'marketing'])
                    ->orderBy('survey_date', 'desc')
                    ->get();
    }

    /**
     * Get customer surveys for quotation
     */
    public function getCustomerSurveysForQuotation($quotationId)
    {
        $quotation = Quotation::with(['quotationSurveys.survey.customer'])->findOrFail($quotationId);
        
        $surveys = $quotation->quotationSurveys;
        $customerSurveys = $surveys->groupBy('survey.customer_id');
        
        return $customerSurveys;
    }

    /**
     * Get customer information for quotation
     */
    public function getCustomerInfoForQuotation($quotationId)
    {
        $quotation = Quotation::with(['prospect.customer', 'quotationSurveys.survey.customer'])->findOrFail($quotationId);
        
        $customer = $quotation->prospect->customer;
        $surveys = $quotation->quotationSurveys;
        
        $surveyCount = $surveys->count();
        $buildingCount = $surveys->pluck('survey.building_name')->filter()->unique()->count();
        
        return [
            'customer' => $customer,
            'survey_count' => $surveyCount,
            'building_count' => $buildingCount,
            'surveys' => $surveys
        ];
    }

    /**
     * Validate customer surveys for quotation
     */
    public function validateCustomerSurveysForQuotation($quotationId)
    {
        $quotation = Quotation::with(['prospect.customer', 'quotationSurveys.survey.customer'])->findOrFail($quotationId);
        
        $errors = [];
        $warnings = [];
        
        // Check if quotation has customer
        if (!$quotation->prospect->customer_id) {
            $errors[] = "Quotation must have a customer";
        }
        
        // Check if all surveys belong to the same customer
        $customerIds = $quotation->quotationSurveys->pluck('survey.customer_id')->unique();
        if ($customerIds->count() > 1) {
            $errors[] = "All surveys must belong to the same customer";
        }
        
        // Check if surveys belong to the quotation's customer
        $quotationCustomerId = $quotation->prospect->customer_id;
        $surveyCustomerIds = $quotation->quotationSurveys->pluck('survey.customer_id');
        
        foreach ($surveyCustomerIds as $surveyCustomerId) {
            if ($surveyCustomerId !== $quotationCustomerId) {
                $errors[] = "Survey belongs to different customer than quotation";
            }
        }
        
        // Check if surveys are approved
        $unapprovedSurveys = $quotation->quotationSurveys->filter(function($qs) {
            return $qs->survey->status !== 'approved';
        });
        
        if ($unapprovedSurveys->count() > 0) {
            $warnings[] = "Some surveys are not approved yet";
        }
        
        return [
            'is_valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Get customer survey statistics
     */
    public function getCustomerSurveyStatistics($quotationId)
    {
        $quotation = Quotation::with(['prospect.customer', 'quotationSurveys.survey'])->findOrFail($quotationId);
        
        $customer = $quotation->prospect->customer;
        $surveys = $quotation->quotationSurveys;
        
        $surveyCount = $surveys->count();
        $buildingCount = $surveys->pluck('survey.building_name')->filter()->unique()->count();
        $totalAmount = $quotation->total_amount;
        $averageAmountPerSurvey = $surveyCount > 0 ? $totalAmount / $surveyCount : 0;
        
        // Get all surveys for this customer
        $allCustomerSurveys = Survey::where('customer_id', $customer->id)->count();
        
        $quotationCoverage = $allCustomerSurveys > 0 ? ($surveyCount / $allCustomerSurveys) * 100 : 0;
        
        return [
            'customer' => $customer,
            'survey_count' => $surveyCount,
            'building_count' => $buildingCount,
            'total_amount' => $totalAmount,
            'average_amount_per_survey' => $averageAmountPerSurvey,
            'all_customer_surveys' => $allCustomerSurveys,
            'quotation_coverage' => round($quotationCoverage, 2)
        ];
    }

    /**
     * Bulk add surveys from same customer
     */
    public function bulkAddSurveysFromSameCustomer($quotationId, $surveyIds, $userId = null)
    {
        try {
            DB::beginTransaction();

            $quotation = Quotation::with('prospect.customer')->findOrFail($quotationId);
            $addedSurveys = [];
            $errors = [];

            foreach ($surveyIds as $surveyId) {
                $result = $this->addSurveyFromSameCustomer($quotationId, $surveyId, $userId);
                
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
                'message' => 'Bulk add surveys from same customer completed',
                'data' => [
                    'added_surveys' => $addedSurveys,
                    'errors' => $errors,
                    'success_count' => count($addedSurveys),
                    'error_count' => count($errors)
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to bulk add surveys from same customer: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get customer survey history
     */
    public function getCustomerSurveyHistory($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        $surveys = Survey::where('customer_id', $customerId)
            ->with(['customer', 'building', 'surveyor', 'marketing', 'quotationSurveys.quotation'])
          ->orderBy('survey_date', 'desc')
          ->get();
        
        $quotations = Quotation::whereHas('prospect', function($q) use ($customerId) {
            $q->where('customer_id', $customerId);
        })->with(['prospect', 'marketing', 'quotationSurveys.survey'])
          ->orderBy('quotation_date', 'desc')
          ->get();
        
        return [
            'customer' => $customer,
            'surveys' => $surveys,
            'quotations' => $quotations,
            'survey_count' => $surveys->count(),
            'quotation_count' => $quotations->count()
        ];
    }

    /**
     * Update quotation totals based on customer surveys
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
     * Get customer survey recommendations
     */
    public function getCustomerSurveyRecommendations($quotationId)
    {
        $quotation = Quotation::with(['prospect.customer', 'quotationSurveys.survey'])->findOrFail($quotationId);
        
        $customer = $quotation->prospect->customer;
        $currentSurveys = $quotation->quotationSurveys->pluck('survey_id');
        
        // Get other surveys from same customer
        $recommendedSurveys = Survey::where('customer_id', $customer->id)
            ->whereNotIn('id', $currentSurveys)
          ->where('status', 'approved')
          ->with(['customer', 'building', 'surveyor', 'marketing'])
          ->orderBy('survey_date', 'desc')
          ->limit(10)
          ->get();
        
        return [
            'customer' => $customer,
            'recommended_surveys' => $recommendedSurveys,
            'recommendation_count' => $recommendedSurveys->count()
        ];
    }
}

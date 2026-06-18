<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Services\Marketing\SurveyCustomerService;
use App\Models\Quotation;
use App\Models\Survey;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurveyCustomerController extends Controller
{
    protected $surveyCustomerService;

    public function __construct(SurveyCustomerService $surveyCustomerService)
    {
        $this->surveyCustomerService = $surveyCustomerService;
    }

    /**
     * Add survey from same customer to quotation
     */
    public function addSurveyFromSameCustomer(Request $request)
    {
        $request->validate([
            'quotation_id' => 'required|exists:quotations,id',
            'survey_id' => 'required|exists:surveys,id'
        ]);

        $result = $this->surveyCustomerService->addSurveyFromSameCustomer(
            $request->quotation_id,
            $request->survey_id,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Get surveys from same customer for quotation
     */
    public function getSurveysFromSameCustomer(Request $request, $quotationId)
    {
        $search = $request->get('search');
        $surveys = $this->surveyCustomerService->getSurveysFromSameCustomer($quotationId, $search);

        return response()->json([
            'status' => 'success',
            'data' => $surveys
        ]);
    }

    /**
     * Get customer surveys for quotation
     */
    public function getCustomerSurveysForQuotation($quotationId)
    {
        $customerSurveys = $this->surveyCustomerService->getCustomerSurveysForQuotation($quotationId);

        return response()->json([
            'status' => 'success',
            'data' => $customerSurveys
        ]);
    }

    /**
     * Get customer information for quotation
     */
    public function getCustomerInfoForQuotation($quotationId)
    {
        $customerInfo = $this->surveyCustomerService->getCustomerInfoForQuotation($quotationId);

        return response()->json([
            'status' => 'success',
            'data' => $customerInfo
        ]);
    }

    /**
     * Validate customer surveys for quotation
     */
    public function validateCustomerSurveys($quotationId)
    {
        $validation = $this->surveyCustomerService->validateCustomerSurveysForQuotation($quotationId);

        return response()->json([
            'status' => 'success',
            'data' => $validation
        ]);
    }

    /**
     * Get customer survey statistics
     */
    public function getCustomerSurveyStatistics($quotationId)
    {
        $statistics = $this->surveyCustomerService->getCustomerSurveyStatistics($quotationId);

        return response()->json([
            'status' => 'success',
            'data' => $statistics
        ]);
    }

    /**
     * Bulk add surveys from same customer
     */
    public function bulkAddSurveysFromSameCustomer(Request $request)
    {
        $request->validate([
            'quotation_id' => 'required|exists:quotations,id',
            'survey_ids' => 'required|array',
            'survey_ids.*' => 'exists:surveys,id'
        ]);

        $result = $this->surveyCustomerService->bulkAddSurveysFromSameCustomer(
            $request->quotation_id,
            $request->survey_ids,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Get customer survey history
     */
    public function getCustomerSurveyHistory($customerId)
    {
        $history = $this->surveyCustomerService->getCustomerSurveyHistory($customerId);

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }

    /**
     * Get customer survey recommendations
     */
    public function getCustomerSurveyRecommendations($quotationId)
    {
        $recommendations = $this->surveyCustomerService->getCustomerSurveyRecommendations($quotationId);

        return response()->json([
            'status' => 'success',
            'data' => $recommendations
        ]);
    }

    /**
     * Update quotation totals
     */
    public function updateQuotationTotals($quotationId)
    {
        $quotation = Quotation::findOrFail($quotationId);
        $totals = $this->surveyCustomerService->updateQuotationTotals($quotation);

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation totals updated successfully',
            'data' => $totals
        ]);
    }

    /**
     * Get quotation with customer surveys
     */
    public function getQuotationWithCustomerSurveys($quotationId)
    {
        $quotation = Quotation::with([
            'prospect.customer',
            'quotationSurveys.survey.customer',
            'quotationSurveys.survey.building',
            'quotationSurveys.survey.surveyDetails',
            'quotationSurveys.addedBy',
            'marketing',
            'creator'
        ])->findOrFail($quotationId);

        return response()->json([
            'status' => 'success',
            'data' => $quotation
        ]);
    }

    /**
     * Search customer surveys
     */
    public function searchCustomerSurveys(Request $request, $quotationId)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        
        $quotation = Quotation::with('prospect.customer')->findOrFail($quotationId);
        
        if (!$quotation->prospect->customer_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quotation does not have a customer'
            ]);
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

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $surveys = $query->with(['customer', 'building', 'surveyor', 'marketing'])
                        ->orderBy('survey_date', 'desc')
                        ->paginateStd(25);

        return response()->json([
            'status' => 'success',
            'data' => $surveys->items(),
            'pagination' => [
                'total' => $surveys->total(),
                'per_page' => $surveys->perPage(),
                'current_page' => $surveys->currentPage(),
                'last_page' => $surveys->lastPage(),
                'from' => $surveys->firstItem(),
                'to' => $surveys->lastItem(),
            ]
        ]);
    }

    /**
     * Get customer survey analytics
     */
    public function getCustomerSurveyAnalytics($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        $surveys = Survey::where('customer_id', $customerId)
            ->with(['customer', 'building', 'quotationSurveys.quotation'])->get();
        
        $quotations = Quotation::whereHas('prospect', function($q) use ($customerId) {
            $q->where('customer_id', $customerId);
        })->with(['prospect', 'quotationSurveys.survey'])->get();
        
        $analytics = [
            'customer' => $customer,
            'total_surveys' => $surveys->count(),
            'total_quotations' => $quotations->count(),
            'surveys_by_status' => $surveys->groupBy('status')->map->count(),
            'quotations_by_status' => $quotations->groupBy('status')->map->count(),
            'surveys_by_building' => $surveys->groupBy('building_name')->map->count(),
            'average_surveys_per_quotation' => $quotations->count() > 0 ? $surveys->count() / $quotations->count() : 0,
            'recent_surveys' => $surveys->sortByDesc('survey_date')->take(5),
            'recent_quotations' => $quotations->sortByDesc('quotation_date')->take(5)
        ];
        
        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ]);
    }
}

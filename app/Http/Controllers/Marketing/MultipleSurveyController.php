<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Services\Marketing\MultipleSurveyService;
use App\Models\Quotation;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MultipleSurveyController extends Controller
{
    protected $multipleSurveyService;

    public function __construct(MultipleSurveyService $multipleSurveyService)
    {
        $this->multipleSurveyService = $multipleSurveyService;
    }

    /**
     * Add survey to quotation
     */
    public function addSurvey(Request $request)
    {
        $request->validate([
            'quotation_id' => 'required|exists:quotations,id',
            'survey_id' => 'required|exists:surveys,id'
        ]);

        $result = $this->multipleSurveyService->addSurveyToQuotation(
            $request->quotation_id,
            $request->survey_id,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Remove survey from quotation
     */
    public function removeSurvey(Request $request)
    {
        $request->validate([
            'quotation_id' => 'required|exists:quotations,id',
            'survey_id' => 'required|exists:surveys,id'
        ]);

        $result = $this->multipleSurveyService->removeSurveyFromQuotation(
            $request->quotation_id,
            $request->survey_id,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Get surveys for quotation
     */
    public function getQuotationSurveys($quotationId)
    {
        $surveys = $this->multipleSurveyService->getQuotationSurveys($quotationId);

        return response()->json([
            'status' => 'success',
            'data' => $surveys
        ]);
    }

    /**
     * Get available surveys for quotation
     */
    public function getAvailableSurveys(Request $request, $quotationId)
    {
        $search = $request->get('search');
        $surveys = $this->multipleSurveyService->getAvailableSurveys($quotationId, $search);

        return response()->json([
            'status' => 'success',
            'data' => $surveys
        ]);
    }

    /**
     * Bulk add surveys to quotation
     */
    public function bulkAddSurveys(Request $request)
    {
        $request->validate([
            'quotation_id' => 'required|exists:quotations,id',
            'survey_ids' => 'required|array',
            'survey_ids.*' => 'exists:surveys,id'
        ]);

        $result = $this->multipleSurveyService->bulkAddSurveys(
            $request->quotation_id,
            $request->survey_ids,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Get quotation survey statistics
     */
    public function getQuotationStatistics($quotationId)
    {
        $statistics = $this->multipleSurveyService->getQuotationSurveyStatistics($quotationId);

        return response()->json([
            'status' => 'success',
            'data' => $statistics
        ]);
    }

    /**
     * Validate quotation with multiple surveys
     */
    public function validateQuotation($quotationId)
    {
        $validation = $this->multipleSurveyService->validateQuotationWithMultipleSurveys($quotationId);

        return response()->json([
            'status' => 'success',
            'data' => $validation
        ]);
    }

    /**
     * Get survey details for quotation
     */
    public function getSurveyDetails($quotationId, $surveyId)
    {
        $surveyDetails = $this->multipleSurveyService->getSurveyDetailsForQuotation($quotationId, $surveyId);

        return response()->json([
            'status' => 'success',
            'data' => $surveyDetails
        ]);
    }

    /**
     * Reorder surveys in quotation
     */
    public function reorderSurveys(Request $request, $quotationId)
    {
        $request->validate([
            'survey_order' => 'required|array',
            'survey_order.*' => 'exists:surveys,id'
        ]);

        $result = $this->multipleSurveyService->reorderSurveys(
            $quotationId,
            $request->survey_order,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Update quotation totals
     */
    public function updateQuotationTotals($quotationId)
    {
        $quotation = Quotation::findOrFail($quotationId);
        $totals = $this->multipleSurveyService->updateQuotationTotals($quotation);

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation totals updated successfully',
            'data' => $totals
        ]);
    }

    /**
     * Get quotation with multiple surveys
     */
    public function getQuotationWithSurveys($quotationId)
    {
        $quotation = Quotation::with([
            'quotationSurveys.survey.building',
            'quotationSurveys.survey.customer',
            'quotationSurveys.survey.surveyDetails',
            'quotationSurveys.addedBy',
            'prospect',
            'marketing',
            'creator'
        ])->findOrFail($quotationId);

        return response()->json([
            'status' => 'success',
            'data' => $quotation
        ]);
    }

    /**
     * Search surveys for quotation
     */
    public function searchSurveys(Request $request, $quotationId)
    {
        $search = $request->get('search', '');
        $status = $request->get('status', 'all');
        
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

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $surveys = $query->with(['building', 'customer', 'surveyor', 'marketing'])
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
}

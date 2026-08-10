<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\Survey;
use App\Models\SurveyDetail;
use App\Models\User;
use App\Models\Prospect;
use App\Models\IndonesiaRegion;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Subdistrict;
use App\Models\Quotation;
use App\Models\QuotationSurvey;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class SurveyController extends Controller
{
    use ColumnFilterTrait, AccessControlFilterTrait;
    
    protected $accessControlService;
    
    public function __construct(AccessControlService $accessControlService)
    {
        $this->accessControlService = $accessControlService;
    }
    
    public function index(Request $request)
    {
        $query = Survey::with([
            'surveyor',
            'marketing',
            'creator',
            'updater',
            'customer',
            'building.province',
            'building.city',
            'building.district',
            'building.subdistrict',
        ]);

        // Apply access control filter (hierarchical data)
        // Default: Jika tidak set hirarki, hanya bisa lihat data sendiri
        // NOTE: Pass null for branchField and warehouseField since surveys table doesn't have these columns
        $query = $this->applyAccessControlFilter($query, null, 'created_by', 'marketing_id', null, null, null);

        // Apply column filters - handle updated_at filter manually to avoid relation issues
        $filters = $request->input('filter', []);
        
        // Handle is_active filter (map to status 'cancelled')
        // Default to active (not cancelled) if not specified
        if (!isset($filters['is_active'])) {
            $filters['is_active'] = '1';
        }
        
        $isActive = $filters['is_active'];
        if ($isActive == '1' || $isActive == 'true' || $isActive === true) {
            $query->where('status', '!=', 'cancelled');
        } elseif ($isActive == '0' || $isActive == 'false' || $isActive === false) {
            $query->where('status', 'cancelled');
        }
        // If 'all' or empty, show everything (don't filter status)
        
        // Remove is_active from filters so applyColumnFilters doesn't try to query a non-existent column
        unset($filters['is_active']);
        
        $hasUpdatedAtFilter = false;
        $updatedAtFilterValue = null;
        
        // Check for surveys.updated_at filter (supports both dot and double underscore notation)
        if (isset($filters['surveys.updated_at'])) {
            $updatedAtFilterValue = $filters['surveys.updated_at'];
            $hasUpdatedAtFilter = true;
            unset($filters['surveys.updated_at']);
        } elseif (isset($filters['surveys__updated_at'])) {
            $updatedAtFilterValue = $filters['surveys__updated_at'];
            $hasUpdatedAtFilter = true;
            unset($filters['surveys__updated_at']);
        }
        
        if ($hasUpdatedAtFilter && !empty(trim($updatedAtFilterValue))) {
            $term = trim($updatedAtFilterValue);
            // Filter by updated_at column directly on surveys table
            // Search in multiple date formats to handle "nov" (November) search
            $query->where(function($q) use ($term) {
                $q->whereRaw("DATE_FORMAT(surveys.updated_at, '%d %M %Y') LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("DATE_FORMAT(surveys.updated_at, '%M %Y') LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("DATE_FORMAT(surveys.updated_at, '%Y-%m-%d') LIKE ?", ["%{$term}%"]);
            });
        }

        // Apply other column filters (excluding surveys.updated_at to avoid conflicts)
        $columnMap = [];
        // Temporarily replace filter input to exclude surveys.updated_at
        // We also need to update request filter to exclude is_active so it handles it correctly
        $originalFilters = $request->input('filter', []);
        $request->merge(['filter' => $filters]); // Use modified filters (without is_active)
        
        $this->applyColumnFilters($query, null, $columnMap);
        
        // Restore original filters for frontend consistency
        $request->merge(['filter' => $originalFilters]);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('survey_number', 'like', "%{$search}%")
                  ->orWhere('survey_location', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('surveyor_id')) {
            $query->where('surveyor_id', $request->surveyor_id);
        }

        if ($request->filled('date_from')) {
            $query->where('survey_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('survey_date', '<=', $request->date_to);
        }

        // Sorting
        if ($request->filled('sort')) {
            $sort = $request->sort;
            $direction = $request->input('direction', 'asc');
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('surveys.survey_date', 'desc')
                  ->orderBy('surveys.created_at', 'desc');
        }

        $surveys = $query->paginateStd(25);
        $surveyors = User::where('department_name', 'Marketing')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $prospects = Prospect::where('status', '!=', 'closed_lost')->get();
        $provinces = IndonesiaRegion::getProvinces();
        $buildings = collect();

        return view('marketing.surveys.index', compact('surveys', 'surveyors', 'prospects', 'provinces', 'buildings'));
    }

    public function getSurveyors()
    {
        $surveyors = User::where('department_name', 'Marketing')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return response()->json($surveyors);
    }

    public function create()
    {
        $surveyors = User::where('department_name', 'Marketing')->active()->get();
        $prospects = Prospect::where('status', '!=', 'closed_lost')->get();

        return view('marketing.surveys.create', compact('surveyors', 'prospects'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'survey_number' => 'nullable|string|max:255',
            'prospect_id' => 'required|exists:prospects,id',
            'building_id' => 'required|exists:buildings,id',
            'surveyor_id' => 'required|exists:users,id',
            'survey_date' => 'required|date',
            'survey_location' => 'required|string',
            'temperature' => 'required|numeric|min:-50|max:100',
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'customer_type' => 'nullable|in:individual,corporate,government',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_1' => 'nullable|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'building_name' => 'nullable|string|max:255',
            'address_1' => 'nullable|string',
            'address_2' => 'nullable|string',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'village' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'survey_result' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'status' => 'required|in:draft,submitted,approved,rejected,in_progress,completed,cancelled'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessages = [];
            
            foreach ($errors->all() as $error) {
                $errorMessages[] = $error;
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Please fix the following errors:',
                'errors' => $errorMessages
            ], 422);
        }

        try {
        // Generate survey number using DocumentNumberService with Intersection Logic (User Branch x Building Location)
        $surveyNumber = $request->survey_number;
        if (!$surveyNumber) {
            $documentNumberService = new \App\Services\DocumentNumberService();
            $user = Auth::user();
            $branchCode = null;
            
            if ($user) {
                // 1. Get branch code from building location
                $buildingBranchCode = $documentNumberService->getBranchCodeFromBuilding($request->building_id);
                
                // 2. Check if building branch is in user's assigned branches
                $hasAccessToBuildingBranch = $user->assignedBranches()->where('code', $buildingBranchCode)->exists();
                
                if ($buildingBranchCode && $hasAccessToBuildingBranch) {
                    // Match found: Use building's branch
                    $branchCode = $buildingBranchCode;
                } else {
                    // No match or no access: Fallback to user's primary branch
                    if ($user->branch) {
                        $branchCode = $user->branch->branch_code ?? $user->branch->code;
                    }
                }
            }
            
            // Generate number
            $surveyNumber = $documentNumberService->generate(
                'survey',
                $branchCode,
                $request->building_id,
                null,
                null,
                null,
                null
            );
        }
            
            // Auto-inherit marketing staff from prospect for data consistency
            $prospect = \App\Models\Prospect::find($request->prospect_id);
            $marketingId = $prospect ? $prospect->assigned_to : $request->marketing_id;
            
            // Get building info for auto-filling building_name
            $building = \App\Models\Building::find($request->building_id);
            $buildingName = $building ? ($building->nama_gedung ?? $building->name) : null;
            
            $survey = Survey::create([
                'survey_number' => $surveyNumber,
                'prospect_id' => $request->prospect_id,
                'building_id' => $request->building_id,
                'surveyor_id' => $request->surveyor_id,
                'marketing_id' => $marketingId, // Auto-inherit from prospect
                'survey_date' => $request->survey_date,
                'survey_location' => $request->survey_location,
                'temperature' => $request->temperature,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'company_name' => $request->company_name,
                'customer_type' => $request->customer_type,
                'contact_person' => $request->contact_person,
                'email' => $request->email,
                'phone_1' => $request->phone_1,
                'phone_2' => $request->phone_2,
                'position' => $request->position,
                'building_name' => $buildingName, // Auto-fill from selected building
                'address_1' => $request->address_1,
                'address_2' => $request->address_2,
                'province' => $request->province,
                'city' => $request->city,
                'district' => $request->district,
                'village' => $request->village,
                'postal_code' => $request->postal_code,
                'survey_result' => $request->survey_result,
                'recommendations' => $request->recommendations,
                'status' => $request->status,
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Survey created successfully',
                'data' => $survey
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating survey: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Survey $survey)
    {
        $survey->load([
            'surveyor', 
            'marketing', 
            'creator', 
            'updater', 
            'building.province', 
            'building.city', 
            'building.district', 
            'building.subdistrict',
            'customer', 
            'surveyDetails'
        ]);
        return view('marketing.surveys.show', compact('survey'));
    }

    public function edit(Survey $survey)
    {
        $survey->load(['customer', 'building', 'surveyor', 'marketing', 'creator', 'updater']);
        return response()->json($survey);
    }

    public function update(Request $request, Survey $survey)
    {
        $validator = Validator::make($request->all(), [
            'survey_number' => 'required|string|max:255',
            'status' => 'required|in:draft,submitted,approved,rejected,in_progress,completed,cancelled',
            'survey_date' => 'required|date',
            'surveyor_id' => 'required|exists:users,id',
            'marketing_id' => 'nullable|exists:users,id',
            'survey_location' => 'required|string|max:255',
            'temperature' => 'nullable|numeric|min:-50|max:100',  // Changed to nullable
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'prospect_id' => 'nullable|exists:prospects,id',
            'building_id' => 'nullable|exists:buildings,id',
            'company_name' => 'required|string|max:255',
            'customer_type' => 'nullable|string|max:50',  // Accept any string, no strict validation
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_1' => 'required|string|max:20',
            'phone_2' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255',
            'building_name' => 'nullable|string|max:255',
            'address_1' => 'required|string',
            'address_2' => 'nullable|string',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'village' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'survey_result' => 'nullable|string',
            'recommendations' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $errorMessages = [];
            
            foreach ($errors->all() as $error) {
                $errorMessages[] = $error;
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Please fix the following errors:',
                'errors' => $errorMessages
            ], 422);
        }

        try {
            // Priority for marketing staff: 1. Request, 2. Prospect, 3. Current Survey
            $prospect = \App\Models\Prospect::find($request->prospect_id);
            $marketingId = $request->marketing_id ?? ($prospect ? $prospect->assigned_to : $survey->marketing_id);
            
            // Get building info for auto-filling building_name
            $building = \App\Models\Building::find($request->building_id);
            $buildingName = $building ? ($building->nama_gedung ?? $building->name) : null;
            
            $survey->update([
                'survey_number' => $request->survey_number,
                'status' => $request->status,
                'survey_date' => $request->survey_date,
                'surveyor_id' => $request->surveyor_id,
                'survey_location' => $request->survey_location,
                'temperature' => $request->temperature,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'prospect_id' => $request->prospect_id,
                'building_id' => $request->building_id,
                'marketing_id' => $marketingId, // Prioritize request or prospect
                'company_name' => $request->company_name,
                'customer_type' => $request->customer_type,
                'contact_person' => $request->contact_person,
                'email' => $request->email,
                'phone_1' => $request->phone_1,
                'phone_2' => $request->phone_2,
                'position' => $request->position,
                'building_name' => $buildingName, // Auto-fill from selected building
                'address_1' => $request->address_1,
                'address_2' => $request->address_2,
                'province' => $request->province,
                'city' => $request->city,
                'district' => $request->district,
                'village' => $request->village,
                'postal_code' => $request->postal_code,
                'survey_result' => $request->survey_result,
                'recommendations' => $request->recommendations,
                'updated_by' => Auth::id()
            ]);

            // Sync customer_id to all Master Rooms for this survey (Fix for missing customer_id)
            if ($survey->surveyDetails->isNotEmpty()) {
                foreach ($survey->surveyDetails as $detail) {
                    if ($detail->room_id) {
                        $masterRoom = \App\Models\MasterRoom::find($detail->room_id);
                        if ($masterRoom) {
                            $masterRoom->update([
                                'customer_id' => $survey->customer_id
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Survey updated successfully',
                'data' => $survey
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating survey: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Survey $survey)
    {
        try {
            // Check if survey is linked to any quotation (direct relation)
            $linkedQuotation = Quotation::where('survey_id', $survey->id)->first();
            if ($linkedQuotation) {
                $errorMessage = "Cannot cancel survey '{$survey->survey_number}' because it is linked to Quotation '{$linkedQuotation->quotation_number}'.";
                if (request()->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errorMessage,
                        'errors' => [$errorMessage]
                    ], 422);
                }
                return back()->with('error', $errorMessage);
            }

            // Check if survey is linked to any quotation (many-to-many relation via QuotationSurvey)
            $linkedQuotationSurvey = QuotationSurvey::where('survey_id', $survey->id)->first();
            if ($linkedQuotationSurvey) {
                // Try to get quotation number
                 $quotation = Quotation::find($linkedQuotationSurvey->quotation_id);
                 $quotationNum = $quotation ? $quotation->quotation_number : 'Unknown';
                 
                $errorMessage = "Cannot cancel survey '{$survey->survey_number}' because it is included in Quotation '{$quotationNum}'.";
                if (request()->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errorMessage,
                        'errors' => [$errorMessage]
                    ], 422);
                }
                return back()->with('error', $errorMessage);
            }

            // Soft delete by setting status to cancelled
            $survey->update(['status' => 'cancelled']);
            
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Survey berhasil dibatalkan (cancelled).'
                ]);
            }
            return back()->with('success', 'Survey berhasil dibatalkan (cancelled).');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error cancelling survey: ' . $e->getMessage(),
                    'errors' => [$e->getMessage()]
                ], 500);
            }
            return back()->with('error', 'Error cancelling survey: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:surveys,id'
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid data provided',
                    'errors' => $validator->errors()->all()
                ], 422);
            }

            $ids = $request->ids;
            $surveys = Survey::whereIn('id', $ids)->get();
            
            $deletedCount = 0;
            $errors = [];
            
            \DB::beginTransaction();
            
            foreach ($surveys as $survey) {
                // Check direct quotation link
                $linkedQuotation = Quotation::where('survey_id', $survey->id)->first();
                if ($linkedQuotation) {
                    $errors[] = "Survey '{$survey->survey_number}' cannot be cancelled because it is linked to Quotation '{$linkedQuotation->quotation_number}'.";
                    continue;
                }

                // Check pivot quotation link
                $linkedQuotationSurvey = QuotationSurvey::where('survey_id', $survey->id)->first();
                if ($linkedQuotationSurvey) {
                    $quotation = Quotation::find($linkedQuotationSurvey->quotation_id);
                    $quotationNum = $quotation ? $quotation->quotation_number : 'Unknown';
                    $errors[] = "Survey '{$survey->survey_number}' cannot be cancelled because it is included in Quotation '{$quotationNum}'.";
                    continue;
                }
                
                $survey->update(['status' => 'cancelled']);
                $deletedCount++;
            }
            
            \DB::commit();

            $success = $deletedCount > 0;
            $message = "Berhasil membatalkan {$deletedCount} survey.";
            
            if ($deletedCount === 0 && !empty($errors)) {
                $message = "Gagal membatalkan survey.";
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                    'count' => 0,
                    'errors' => $errors
                ], 422);
            } elseif (!empty($errors)) {
                 $message = "Berhasil membatalkan {$deletedCount} survey. Beberapa gagal.";
            }

            return response()->json([
                'status' => 'success', // Keep 'success' status for consistency with updated frontend logic check
                'success' => $success, // Legacy support just in case
                'message' => $message,
                'count' => $deletedCount,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Error cancelling surveys: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    public function dashboard()
    {
        $total_surveys = Survey::count();
        $pending_surveys = Survey::where('status', 'pending')->count();
        $in_progress_surveys = Survey::where('status', 'in_progress')->count();
        $completed_surveys = Survey::where('status', 'completed')->count();
        $cancelled_surveys = Survey::where('status', 'cancelled')->count();

        $recent_surveys = Survey::with('marketingStaff')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $surveys_by_status = [
            'pending' => $pending_surveys,
            'in_progress' => $in_progress_surveys,
            'completed' => $completed_surveys,
            'cancelled' => $cancelled_surveys
        ];

        return view('marketing.dashboard', compact(
            'total_surveys',
            'pending_surveys',
            'in_progress_surveys',
            'completed_surveys',
            'cancelled_surveys',
            'recent_surveys',
            'surveys_by_status'
        ));
    }

    public function draft(Survey $survey)
    {
        $survey->update(['status' => 'draft']);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Survey saved as draft'
        ]);
    }

    public function email(Survey $survey)
    {
        // Logic to send survey via email
        return response()->json([
            'status' => 'success',
            'message' => 'Survey sent via email'
        ]);
    }

    public function report(Survey $survey)
    {
        return view('marketing.surveys.report', compact('survey'));
    }

    public function downloadPdf(Survey $survey)
    {
        try {
            // Load all necessary relationships
            $survey->load([
                'surveyor', 
                'marketing', 
                'creator', 
                'updater', 
                'building.province', 
                'building.city', 
                'building.district', 
                'building.subdistrict',
                'customer', 
                'surveyDetails'
            ]);

            // Generate PDF using DomPDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('marketing.surveys.pdf', compact('survey'));
            
            // Set PDF options
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial'
            ]);
            
            // Generate filename (remove invalid characters)
            $cleanSurveyNumber = preg_replace('/[\/\\\\]/', '_', $survey->survey_number);
            $filename = 'Survey_Report_' . $cleanSurveyNumber . '_' . now()->format('Y-m-d') . '.pdf';
            
            // Return PDF download
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate survey PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function approve(Survey $survey)
    {
        if (!Auth::user()->canApprove('surveys')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk approve Survey. Pastikan role Anda memiliki permission "Approve" untuk Surveys.'
            ], 403);
        }
        if ($survey->status !== 'submitted') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only submitted surveys can be approved'
            ], 422);
        }

        $survey->update([
            'status' => 'approved',
            'updated_by' => Auth::id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Survey approved successfully',
            'data' => $survey
        ]);
    }

    public function reject(Survey $survey, Request $request)
    {
        if (!Auth::user()->canApprove('surveys')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk reject Survey. Pastikan role Anda memiliki permission "Approve" untuk Surveys.'
            ], 403);
        }

        if ($survey->status !== 'submitted') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only submitted surveys can be rejected'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $survey->update([
            'status' => 'rejected',
            'recommendations' => $request->rejection_reason,
            'updated_by' => Auth::id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Survey rejected successfully',
            'data' => $survey
        ]);
    }

    public function unpost(Survey $survey)
    {
        if (!Auth::user()->canApprove('surveys')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk unpost Survey. Pastikan role Anda memiliki permission "Approve" untuk Surveys.'
            ], 403);
        }
        // Only approved surveys can be unposted
        if ($survey->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only approved surveys can be unposted'
            ], 422);
        }

        // Check if used in any quotation
        if ($survey->is_used_in_quotation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot unpost survey because it is used in one or more quotations.'
            ], 422);
        }

        $survey->update([
            'status' => 'draft',
            'updated_by' => Auth::id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Survey unposted successfully (returned to Draft)',
            'data' => $survey
        ]);
    }

    public function getBuildingsByCustomer($customerId)
    {
        $buildings = \App\Models\Building::whereHas('customers', function($query) use ($customerId) {
                $query->where('customers.id', $customerId);
            })
            ->select('id', 'nama_gedung', 'name', 'address')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $buildings
        ]);
    }

    public function storeDetail(Request $request, Survey $survey)
    {
        $validator = Validator::make($request->all(), [
            'room_name' => 'required|string|max:255',
            'room_id' => 'nullable|exists:master_rooms,id', // Validasi room_id (optional)
            'room_type' => 'required|string|max:100',
            'floor' => 'required|string|max:50',
            'scent_intensity' => 'required|string|max:50',
            'installation_type' => 'required|string|max:100',
            'qty' => 'required|integer|min:1',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'temperature' => 'nullable|numeric|min:0|max:100',
            'remark' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Calculate area
            $area = null;
            if ($request->length && $request->width) {
                $area = $request->length * $request->width;
            }

            // Prepare specifications JSON
            $specifications = [
                'floor' => $request->floor,
                'intensity' => $request->scent_intensity,
                'installation_type' => $request->installation_type,
                'qty' => $request->qty,
                'length' => $request->length,
                'width' => $request->width,
                'height' => $request->height,
                'area' => $area,
                'temperature' => $request->temperature,
                'remark' => $request->remark
            ];

            $surveyDetail = SurveyDetail::create([
                'survey_id' => $survey->id,
                'room_id' => $request->room_id, // Save link to master room
                'room_name' => $request->room_name,
                'room_type' => $request->room_type,
                'room_area' => $area,
                'quantity_needed' => $request->qty,
                'specifications' => json_encode($specifications),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Create corresponding master room ONLY if it's a new room (no room_id provided)
            if (!$request->room_id) {
                try {
                    $masterRoom = \App\Models\MasterRoom::create([
                        'id' => $surveyDetail->id, // Try to use same ID if possible (legacy logic)
                        'building_id' => $survey->building_id ?? null,
                        'customer_id' => $survey->customer_id ?? null, // Add customer_id
                        'room_name' => $request->room_name,
                        'room_type' => $request->room_type,
                        'room_floor' => $request->floor,
                        'room_qty' => $request->qty,
                        'room_temperature' => $request->temperature ?? 0,
                        'room_intensity' => $request->scent_intensity,
                        'room_installation_type' => $request->installation_type,
                        'room_length' => $request->length,
                        'room_width' => $request->width,
                        'room_height' => $request->height,
                        'room_remark' => $request->remark,
                        'is_active' => true,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);
                    
                    // Link the newly created master room to the detail
                    $surveyDetail->room_id = $masterRoom->id;
                    $surveyDetail->save();
                } catch (\Exception $e) {
                    \Log::warning("Failed to create master room for survey detail {$surveyDetail->id}: " . $e->getMessage());
                    // Continue without master room if creation fails (e.g. ID conflict)
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Survey detail berhasil ditambahkan',
                'data' => $surveyDetail
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save survey detail: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showDetail(SurveyDetail $detail)
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $detail
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get survey detail: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateDetail(Request $request, SurveyDetail $detail)
    {
        $validator = Validator::make($request->all(), [
            'room_name' => 'required|string|max:255',
            'room_id' => 'nullable|exists:master_rooms,id', // Validasi room_id
            'room_type' => 'required|string|max:100',
            'floor' => 'required|string|max:50',
            'scent_intensity' => 'required|string|max:50',
            'installation_type' => 'required|string|max:100',
            'qty' => 'required|integer|min:1',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'temperature' => 'nullable|numeric|min:0|max:100',
            'remark' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $area = null;
            if ($request->length && $request->width) {
                $area = $request->length * $request->width;
            }

            $specifications = [
                'floor' => $request->floor,
                'intensity' => $request->scent_intensity,
                'installation_type' => $request->installation_type,
                'qty' => $request->qty,
                'length' => $request->length,
                'width' => $request->width,
                'height' => $request->height,
                'area' => $area,
                'temperature' => $request->temperature,
                'remark' => $request->remark
            ];

            $updateData = [
                'room_name' => $request->room_name,
                'room_type' => $request->room_type,
                'room_area' => $area,
                'quantity_needed' => $request->qty,
                'specifications' => json_encode($specifications),
                'updated_by' => Auth::id()
            ];
            
            // Only update room_id if provided (don't nullify if not provided, unless explicitly intended?)
            // Assuming frontend sends room_id if selected
            if ($request->has('room_id')) {
                $updateData['room_id'] = $request->room_id;
            }

            $detail->update($updateData);

            // SYNC: Update corresponding master room
            $this->syncToMasterRoom($detail);

            return response()->json(['status' => 'success', 'message' => 'Survey detail berhasil diperbarui', 'data' => $detail]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to update survey detail: ' . $e->getMessage()], 500);
        }
    }

    public function copyDetail(SurveyDetail $detail)
    {
        try {
            // Create a copy of the survey detail
            $newDetail = $detail->replicate();
            $newDetail->room_name = $detail->room_name . ' (Copy)';
            $newDetail->room_id = null; // Reset room_id link for copy
            $newDetail->created_by = Auth::id();
            $newDetail->updated_by = Auth::id();
            $newDetail->save();
            
            // SYNC: Create corresponding master room with SAME ID
            $specifications = json_decode($detail->specifications ?? '{}', true);
            
            try {
                $masterRoom = \App\Models\MasterRoom::create([
                    'id' => $newDetail->id, // Use same ID as survey detail
                    'building_id' => null, // Copy doesn't inherit building
                    'customer_id' => $detail->survey->customer_id ?? null, // Add customer_id from original survey
                    'room_name' => $newDetail->room_name,
                    'room_type' => $newDetail->room_type,
                    'room_floor' => $specifications['floor'] ?? null,
                    'room_qty' => $specifications['qty'] ?? $newDetail->quantity_needed,
                    'room_temperature' => $specifications['temperature'] ?? 0,
                    'room_intensity' => $specifications['intensity'] ?? null,
                    'room_installation_type' => $specifications['installation_type'] ?? null,
                    'room_length' => $specifications['length'] ?? null,
                    'room_width' => $specifications['width'] ?? null,
                    'room_height' => $specifications['height'] ?? null,
                    'room_remark' => $specifications['remark'] ?? null,
                    'is_active' => true,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
                
                // Link
                $newDetail->room_id = $masterRoom->id;
                $newDetail->save();
            } catch (\Exception $e) {
                \Log::warning("Failed to create master room copy: " . $e->getMessage());
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Survey detail berhasil diduplikasi',
                'data' => $newDetail
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to copy survey detail: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyDetail(SurveyDetail $detail)
    {
        try {
            // SYNC: Delete corresponding master room with same ID (Legacy logic)
            // Only if it was created with same ID? Maybe dangerous to delete if linked?
            // Safer to check if room_id matches id?
            // Keeping original logic but adding check
            
            // If room_id exists, we typically don't delete Master Data unless explicitly asked
            // But legacy logic deleted it.
            // Let's restrict delete to standard rule: Don't delete master data automatically.
            // Commenting out delete logic to preserve data integrity
            // \App\Models\MasterRoom::where('id', $detail->id)->delete();
            
            $detail->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Survey detail berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete survey detail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync survey detail data to corresponding master room
     */
    private function syncToMasterRoom(SurveyDetail $detail)
    {
        try {
            $masterRoom = null;
            
            // 1. Try to find by direct link (room_id)
            if ($detail->room_id) {
                $masterRoom = \App\Models\MasterRoom::find($detail->room_id);
            }
            
            if ($masterRoom) {
                // IMPORTANT: Use raw attributes because accessors in SurveyDetail 
                // prioritize existing MasterRoom data, which would cause an update loop with old data.
                $attributes = $detail->getAttributes();
                $rawSpecs = json_decode($attributes['specifications'] ?? '{}', true);
                
                $masterRoom->update([
                    'customer_id' => $detail->survey->customer_id ?? $masterRoom->customer_id, // Sync customer_id
                    'room_name' => $attributes['room_name'] ?? $detail->room_name,
                    'room_type' => $attributes['room_type'] ?? $detail->room_type,
                    'room_floor' => $rawSpecs['floor'] ?? $masterRoom->room_floor,
                    'room_qty' => $rawSpecs['qty'] ?? $attributes['quantity_needed'] ?? $detail->quantity_needed,
                    'room_temperature' => $rawSpecs['temperature'] ?? $masterRoom->room_temperature,
                    'room_intensity' => $rawSpecs['intensity'] ?? $masterRoom->room_intensity,
                    'room_installation_type' => $rawSpecs['installation_type'] ?? $masterRoom->room_installation_type,
                    'room_length' => $rawSpecs['length'] ?? $masterRoom->room_length,
                    'room_width' => $rawSpecs['width'] ?? $masterRoom->room_width,
                    'room_height' => $rawSpecs['height'] ?? $masterRoom->room_height,
                    'room_remark' => $rawSpecs['remark'] ?? $masterRoom->room_remark,
                    'updated_by' => Auth::id()
                ]);

                \Log::info("Synced survey detail {$detail->id} to master room {$masterRoom->id} (Raw sync enabled)");
            }
        } catch (\Exception $e) {
            \Log::error("Failed to sync survey detail to master room: " . $e->getMessage());
        }
    }

    public function finalize(Survey $survey)
    {
        // Check if survey is in draft status
        if ($survey->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft surveys can be finalized'
            ], 422);
        }

        try {
            $survey->update([
                'status' => 'approved',
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Survey finalized successfully',
                'data' => $survey
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error finalizing survey: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function updateLocationDetail(Request $request, Survey $survey)
    {
        // MOM29: Only allow editing if status is draft
        if ($survey->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya survey dengan status Draft yang dapat diubah lokasi detailnya.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'building_location_detail' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $survey->update([
                'building_location_detail' => $request->building_location_detail,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Lokasi detail berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui lokasi detail: ' . $e->getMessage()
            ], 500);
        }
    }
}

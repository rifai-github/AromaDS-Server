<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\MarketingPipeline;
use App\Models\User;
use App\Models\Branch;
use App\Models\Team;
use App\Models\Department;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Building;
use App\Models\Province;
use App\Models\CustomerCategory;
use App\Models\CustomerType;
use App\Models\CustomerContact;
use App\Models\BankPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketingPipelineController extends Controller
{
    use ColumnFilterTrait, AccessControlFilterTrait;
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MarketingPipeline::with(['assignedTo', 'createdBy', 'updatedBy', 'referenceUser']);

        // Apply access control filter (hierarchical data)
        // Default: Jika tidak set hirarki, hanya bisa lihat data sendiri
        // NOTE: Pass null for branchField and warehouseField since marketing_pipelines table doesn't have these columns
        $query = $this->applyAccessControlFilter($query, null, 'created_by', 'assigned_to', null, null, null);

        $columnMap = [
            'follow_up_date' => ['column' => 'marketing_pipelines.follow_up_date', 'type' => 'date'],
            'company_name' => ['column' => 'marketing_pipelines.company_name'],
            'company_address' => ['column' => 'marketing_pipelines.company_address'],
            'pic_name' => ['column' => 'marketing_pipelines.pic_name'],
            'pic_phone' => ['column' => 'marketing_pipelines.pic_phone'],
            'pic_email' => ['column' => 'marketing_pipelines.pic_email'],
            'visit_result' => ['column' => 'marketing_pipelines.visit_result'],
            'notes' => ['column' => 'marketing_pipelines.notes'],
            'creator.name' => ['relation' => 'createdBy', 'column' => 'name'],
            'marketing_pipelines.created_at' => ['column' => 'marketing_pipelines.created_at', 'type' => 'date'],
            'updater.name' => ['relation' => 'updatedBy', 'column' => 'name'],
            'marketing_pipelines.updated_at' => ['column' => 'marketing_pipelines.updated_at', 'type' => 'date'],
        ];
        $this->applyColumnFilters($query, null, $columnMap);
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by assigned user
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('visit_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('visit_date', '<=', $request->end_date);
        }

        // Filter by company name (only if not using column filter)
        if ($request->filled('company_name') && !$request->has('filter')) {
            $query->where('company_name', 'like', '%' . $request->company_name . '%');
        }

        // Search (only if not using column filters)
        if ($request->filled('search') && !$request->has('filter')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('pipeline_number', 'like', '%' . $search . '%')
                  ->orWhere('company_name', 'like', '%' . $search . '%')
                  ->orWhere('pic_name', 'like', '%' . $search . '%')
                  ->orWhere('pic_phone', 'like', '%' . $search . '%')
                  ->orWhere('pic_email', 'like', '%' . $search . '%');
            });
        }

        // Sorting
        // Sorting
        if ($request->filled('sort')) {
            $sort = $request->sort;
            $direction = $request->input('direction', 'asc');
            
            // Handle relationship sorting
            if ($sort === 'creator.name') {
                $query->leftJoin('users as creator', 'marketing_pipelines.created_by', '=', 'creator.id')
                      ->select('marketing_pipelines.*')
                      ->orderBy('creator.name', $direction);
            } elseif ($sort === 'updater.name') {
                $query->leftJoin('users as updater', 'marketing_pipelines.updated_by', '=', 'updater.id')
                      ->select('marketing_pipelines.*')
                      ->orderBy('updater.name', $direction);
            } elseif ($sort === 'assignedTo.name') {
                $query->leftJoin('users as assignee', 'marketing_pipelines.assigned_to', '=', 'assignee.id')
                      ->select('marketing_pipelines.*')
                      ->orderBy('assignee.name', $direction);
            } else {
                $query->orderBy($sort, $direction);
            }
        } else {
            $query->orderBy('marketing_pipelines.created_at', 'desc');
        }

        $pipelines = $query->paginate(20);

        // Get filter options - Get all active users for reference
        $users = User::where('is_active', true)
            ->orderBy('name')
            ->get();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $teams = Team::where('active_status', true)->orderBy('team_name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        
        // Get all document lists for dropdowns (will be filtered by customer on client-side)
        // EXCLUDE DRAFT STATUS documents
        $contracts = Contract::with('customer')
            ->where('contract_status', '!=', 'draft')
            ->orderBy('contract_number')->get();
        $quotations = \App\Models\Quotation::with('customer')
            ->where('status', '!=', 'draft')
            ->orderBy('quotation_number')->get();
        $surveys = \App\Models\Survey::with('customer')
            ->where('status', '!=', 'draft')
            ->orderBy('survey_number')->get();
        $job_advices = \App\Models\JobAdvice::with('customer')
            ->where('status', '!=', 'draft')
            ->orderBy('job_advice_number')->get();
        
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $buildings = Building::where('status_update', true)->orderBy('name')->get(); // Only active buildings
        $provinces = Province::orderBy('name')->get(); // For add building modal

        // Data for Comprehensive Customer Create Modal
        $categories = CustomerCategory::active()->orderBy('name')->get();
        $customerTypes = CustomerType::active()->orderBy('name')->get();
        $ppnCodes = Customer::getPpnCodes();
        $bankPayments = BankPayment::active()->with('bank')->orderBy('account_name')->get();
        $allContacts = CustomerContact::active()->orderBy('name')->get();
        $salutations = \App\Models\OptionDetail::byMasterOption(13)->where('is_active', true)->orderBy('option_name')->get();
        $positions = \App\Models\OptionDetail::byMasterOption(1)->where('is_active', true)->orderBy('option_name')->get();

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $pipelines->items(),
                'pagination' => [
                    'total' => $pipelines->total(),
                    'per_page' => $pipelines->perPage(),
                    'current_page' => $pipelines->currentPage(),
                    'last_page' => $pipelines->lastPage(),
                    'from' => $pipelines->firstItem(),
                    'to' => $pipelines->lastItem(),
                ],
                'ppnCodes' => $ppnCodes,
                'bankPayments' => $bankPayments,
                'allContacts' => $allContacts,
                'salutations' => $salutations,
                'positions' => $positions,
            ]);
        }

        return view('marketing.pipeline.index', compact('pipelines', 'users', 'branches', 'teams', 'departments', 'contracts', 'quotations', 'surveys', 'job_advices', 'customers', 'buildings', 'provinces', 'categories', 'customerTypes', 'ppnCodes', 'bankPayments', 'allContacts', 'salutations', 'positions'))->with('pipeline', $pipelines);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        
        // Check permission - allow marketing staff to create pipeline
        // Check if user has marketing.create or marketing.write permission
        $hasPermission = $user->hasPermission('marketing.create') || 
                        $user->hasPermission('marketing.write');
        
        if (!$hasPermission) {
            $hasPermission = $user->hasPermission('marketing.pipeline.create');
        }
        
        if (!$hasPermission) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You do not have the required permission to create pipeline.',
                    'error' => 'permission_denied'
                ], 403);
            }
            abort(403, 'Unauthorized. You do not have the required permission to create pipeline.');
        }

        $users = User::where('is_active', true)
            ->orderBy('name')
            ->get();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $teams = Team::where('active_status', true)->orderBy('team_name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $contracts = Contract::where('status', 'active')->orderBy('contract_number')->get();

        return view('marketing.pipeline.create', compact('users', 'branches', 'teams', 'departments', 'contracts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Check permission - allow marketing staff to create pipeline
        // Check if user has marketing.create or marketing.write permission
        $hasPermission = $user->hasPermission('marketing.create') || 
                        $user->hasPermission('marketing.write');
        
        if (!$hasPermission) {
            $hasPermission = $user->hasPermission('marketing.pipeline.create');
        }
        
        if (!$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have the required permission to create pipeline.',
                'error' => 'permission_denied'
            ], 403);
        }
        
        try {
            $request->validate([
                'visit_date' => 'required|date',
                'follow_up_date' => 'nullable|date|after:visit_date', // Field yang diminta - optional saat create
                'customer_id' => 'nullable|exists:customers,id', // Customer ID from dropdown
                'building_id' => 'nullable|exists:buildings,id', // Building ID from dropdown
                'company_name' => 'required|string|max:255', // Nama perusahaan (customer) - will be from dropdown or manual
                'company_address' => 'required|string|max:500', // Alamat perusahaan - will be from building or manual
                'pic_name' => 'required|string|max:255', // Nama PIC
                'pic_phone' => 'required|string|max:20', // Nomor kontak
                'pic_email' => 'required|email|max:255', // Email kontak
                'visit_result' => 'nullable|string|max:1000', // Kegiatan/agenda - sekarang optional karena ada agenda_list
                'agenda_list' => 'required|array|min:1', // Agenda list sekarang required
                'agenda_list.*' => 'required|string|max:255', // Setiap agenda item harus ada
                'contract_list' => 'nullable|array',
                'contract_list.*' => 'nullable|exists:contracts,id', // Contract IDs harus valid
                'quotation_list' => 'nullable|array',
                'quotation_list.*' => 'nullable|exists:quotations,id', // Quotation IDs harus valid
                'survey_list' => 'nullable|array',
                'survey_list.*' => 'nullable|exists:surveys,id', // Survey IDs harus valid
                'job_advice_list' => 'nullable|array',
                'job_advice_list.*' => 'nullable|exists:job_advices,id', // Job Advice IDs harus valid
                'reference' => 'nullable|string|max:255', // Reference manual input
                'notes' => 'nullable|string|max:2000',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Generate unique pipeline number (including soft deleted records)
            $year = date('Y');
            $lastPipeline = MarketingPipeline::withTrashed()
                ->whereYear('created_at', $year)
                ->orderBy('id', 'desc')
                ->first();
            
            if ($lastPipeline && $lastPipeline->pipeline_number) {
                // Extract number from last pipeline number (e.g., PL20250001 -> 0001)
                $lastNumber = (int) substr($lastPipeline->pipeline_number, -4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            
            $pipelineNumber = 'PL' . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

            $pipeline = MarketingPipeline::create([
                'pipeline_number' => $pipelineNumber,
                'visit_date' => $request->visit_date,
                'follow_up_date' => $request->follow_up_date, // Field yang diminta - optional saat create
                'customer_id' => $request->customer_id, // Customer ID from dropdown
                'building_id' => $request->building_id, // Building ID from dropdown
                'company_name' => $request->company_name, // Nama perusahaan (customer)
                'company_address' => $request->company_address, // Alamat perusahaan
                'pic_name' => $request->pic_name, // Nama PIC
                'pic_phone' => $request->pic_phone, // Nomor kontak
                'pic_email' => $request->pic_email, // Email kontak
                'visit_result' => $request->visit_result ?? '', // Kegiatan/agenda - default empty string
                'agenda_list' => $request->agenda_list ? array_filter($request->agenda_list) : null,
                'contract_list' => $request->contract_list ? array_filter($request->contract_list) : null,
                'quotation_list' => $request->quotation_list ? array_filter($request->quotation_list) : null,
                'survey_list' => $request->survey_list ? array_filter($request->survey_list) : null,
                'job_advice_list' => $request->job_advice_list ? array_filter($request->job_advice_list) : null,
                'reference' => $request->reference, // Reference manual input
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Pipeline created successfully',
                'data' => $pipeline
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to create pipeline: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MarketingPipeline $pipeline)
    {
        $pipeline->load(['assignedTo', 'createdBy', 'updatedBy', 'referenceUser', 'customer', 'building']);
        
        // Load SELECTED document details from document lists
        // Filter by customer_id if available to prevent showing wrong customer's documents
        
        // Load selected contracts
        if ($pipeline->contract_list && is_array($pipeline->contract_list)) {
            $contractQuery = Contract::with('customer')->whereIn('id', $pipeline->contract_list);
            if ($pipeline->customer_id) {
                $contractQuery->where('customer_id', $pipeline->customer_id);
            }
            $pipeline->contract_details = $contractQuery->get();
        }
        
        // Load selected quotations
        if ($pipeline->quotation_list && is_array($pipeline->quotation_list)) {
            $quotationQuery = \App\Models\Quotation::with('customer')->whereIn('id', $pipeline->quotation_list);
            if ($pipeline->customer_id) {
                $quotationQuery->where('customer_id', $pipeline->customer_id);
            }
            $pipeline->quotation_details = $quotationQuery->get();
        }
        
        // Load selected surveys
        if ($pipeline->survey_list && is_array($pipeline->survey_list)) {
            $surveyQuery = \App\Models\Survey::with('customer')->whereIn('id', $pipeline->survey_list);
            if ($pipeline->customer_id) {
                $surveyQuery->where('customer_id', $pipeline->customer_id);
            }
            $pipeline->survey_details = $surveyQuery->get();
        }
        
        // Load selected job advices
        if ($pipeline->job_advice_list && is_array($pipeline->job_advice_list)) {
            $jobAdviceQuery = \App\Models\JobAdvice::with('customer')->whereIn('id', $pipeline->job_advice_list);
            if ($pipeline->customer_id) {
                $jobAdviceQuery->where('customer_id', $pipeline->customer_id);
            }
            $pipeline->job_advice_details = $jobAdviceQuery->get();
        }
        
        // Return JSON for AJAX requests
        return response()->json([
            'success' => true,
            'data' => $pipeline
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MarketingPipeline $pipeline)
    {
        $pipeline->load(['assignedTo', 'createdBy', 'updatedBy', 'referenceUser', 'customer', 'building']);
        
        // Return JSON for AJAX requests
        return response()->json([
            'success' => true,
            'data' => $pipeline
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MarketingPipeline $pipeline)
    {
        try {
            $request->validate([
                'visit_date' => 'required|date',
                'follow_up_date' => 'nullable|date',
                'customer_id' => 'nullable|exists:customers,id',
                'building_id' => 'nullable|exists:buildings,id',
                'company_name' => 'required|string|max:255',
                'company_address' => 'required|string|max:500',
                'pic_name' => 'required|string|max:255',
                'pic_phone' => 'required|string|max:20',
                'pic_email' => 'required|email|max:255',
                'visit_result' => 'nullable|string|max:1000',
                'agenda_list' => 'required|array|min:1',
                'agenda_list.*' => 'required|string|max:255',
                'contract_list' => 'nullable|array',
                'contract_list.*' => 'nullable|exists:contracts,id',
                'quotation_list' => 'nullable|array',
                'quotation_list.*' => 'nullable|exists:quotations,id',
                'survey_list' => 'nullable|array',
                'survey_list.*' => 'nullable|exists:surveys,id',
                'job_advice_list' => 'nullable|array',
                'job_advice_list.*' => 'nullable|exists:job_advices,id',
                'reference' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:2000',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $pipeline->update([
                'visit_date' => $request->visit_date,
                'follow_up_date' => $request->follow_up_date,
                'customer_id' => $request->customer_id,
                'building_id' => $request->building_id,
                'company_name' => $request->company_name,
                'company_address' => $request->company_address,
                'pic_name' => $request->pic_name,
                'pic_phone' => $request->pic_phone,
                'pic_email' => $request->pic_email,
                'visit_result' => $request->visit_result ?? '',
                'agenda_list' => $request->agenda_list ? array_filter($request->agenda_list) : null,
                'contract_list' => $request->contract_list ? array_filter($request->contract_list) : null,
                'quotation_list' => $request->quotation_list ? array_filter($request->quotation_list) : null,
                'survey_list' => $request->survey_list ? array_filter($request->survey_list) : null,
                'job_advice_list' => $request->job_advice_list ? array_filter($request->job_advice_list) : null,
                'reference' => $request->reference,
                'notes' => $request->notes,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Pipeline updated successfully',
                'data' => $pipeline
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Pipeline update error: ' . $e->getMessage(), [
                'pipeline_id' => $pipeline->id,
                'request_data' => $request->all(),
                'exception' => $e
            ]);
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to update pipeline: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MarketingPipeline $pipeline)
    {
        try {
            $pipeline->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Pipeline deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete pipeline: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employees by branch and team for pipeline assignment
     */
    public function getEmployeesByBranchAndTeam(Request $request)
    {
        $query = User::where('is_active', true)
            ->where(function($q) {
                $q->whereHas('permissions', function($pq) {
                    $pq->where('name', 'marketing.pipeline.view');
                })->orWhereHas('roles.permissions', function($rpq) {
                    $rpq->where('name', 'marketing.pipeline.view');
                });
            });

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('team_id')) {
            $query->whereHas('teams', function($q) use ($request) {
                $q->where('team_id', $request->team_id);
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $employees = $query->with(['branch', 'department', 'teams'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $employees
        ]);
    }

    /**
     * Get pipeline statistics
     */
    public function getStatistics(Request $request)
    {
        $query = MarketingPipeline::query();

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('visit_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('visit_date', '<=', $request->end_date);
        }

        $statistics = [
            'total' => $query->count(),
            'prospect' => $query->where('status', 'prospect')->count(),
            'qualified' => $query->where('status', 'qualified')->count(),
            'converted' => $query->where('status', 'converted')->count(),
            'lost' => $query->where('status', 'lost')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $statistics
        ]);
    }

    /**
     * Bulk delete pipelines
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:marketing_pipelines,id'
        ]);

        try {
            $deletedCount = MarketingPipeline::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} pipeline(s)",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete pipelines: ' . $e->getMessage()
            ], 500);
        }
    }
}

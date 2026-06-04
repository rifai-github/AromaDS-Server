<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use App\Models\Survey;
use App\Models\MasterOption;
use App\Models\MasterRental;
use App\Models\MasterPriceSlab;
use App\Models\QuotationRental;
use App\Models\QuotationRoom;
use App\Models\FreeTrial;
use App\Models\ContractRenewal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class QuotationController extends Controller
{
    use ColumnFilterTrait, AccessControlFilterTrait;
    
    public function index(Request $request)
    {
        $query = Quotation::query()
            ->select([
                'id',
                'quotation_number',
                'quotation_date',
                'valid_until',
                'grand_total',
                'status',
                'quotation_type',
                'branch_id',
                'rental_period',
                'rental_unit',
                'terms_of_payment',
                'marketing_id',
                'approved_by',
                'date_approved',
                'internal_notes',
                'additional_notes',
                'survey_id',
                'goal_sq',
                'created_by',
                'created_at',
                'updated_by',
                'updated_at',
                'revision_number',
                'customer_id',
                'prospect_id',
            ])
            ->with([
                'customer:id,name',
                'survey:id,survey_location',
                'creator:id,name',
                'updater:id,name',
                'marketing:id,name',
                'approver:id,name',
                'branch:id,name',
            ]);

        // Apply access control filter (hierarchical data)
        // Default: Jika tidak set hirarki, hanya bisa lihat data sendiri
        // NOTE: Pass null for branchField and warehouseField since quotations table doesn't have these columns
        $query = $this->applyAccessControlFilter($query, null, 'created_by', 'marketing_id', null, null, null);

        // ENABLE AUTO FILTERABLE
        // This handles generic columns, relations, and robust logic via trait
        // Note: AutoFilterable is now a Local Scope, so we MUST call the method explicitly.
        $query->filter($request->all());

        // Apply column filters - handle date filters manually to avoid relation issues
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
        
        // Remove is_active from filters so applyColumnFilters/AutoFilterable doesn't try to query a non-existent column
        unset($filters['is_active']);
        
        $hasQuotationDateFilter = false;
        $quotationDateFilterValue = null;
        $hasUpdatedAtFilter = false;
        $updatedAtFilterValue = null;
        
        // Check for quotation_date filter (supports all possible formats)
        // PHP converts dots to underscores in parameter names, so check all variations
        // Also check direct request input in case filter array structure is different
        foreach (['quotation_date', 'quotation__date'] as $filterKey) {
            if (isset($filters[$filterKey]) && !empty(trim($filters[$filterKey]))) {
                $quotationDateFilterValue = trim($filters[$filterKey]);
                $hasQuotationDateFilter = true;
                unset($filters[$filterKey]);
                break;
            }
        }
        
        // Also check direct request input as fallback
        if (!$hasQuotationDateFilter) {
            foreach (['filter.quotation_date', 'filter.quotation__date'] as $filterKey) {
                $value = $request->input($filterKey);
                if (!empty(trim($value))) {
                    $quotationDateFilterValue = trim($value);
                    $hasQuotationDateFilter = true;
                    // Remove from filters array if it exists
                    if (isset($filters['quotation_date'])) {
                        unset($filters['quotation_date']);
                    }
                    if (isset($filters['quotation__date'])) {
                        unset($filters['quotation__date']);
                    }
                    break;
                }
            }
        }
        
        // Check for quotations.updated_at filter (supports dot, double underscore, and single underscore notation)
        if (isset($filters['quotations.updated_at'])) {
            $updatedAtFilterValue = $filters['quotations.updated_at'];
            $hasUpdatedAtFilter = true;
            unset($filters['quotations.updated_at']);
        } elseif (isset($filters['quotations__updated_at'])) {
            $updatedAtFilterValue = $filters['quotations__updated_at'];
            $hasUpdatedAtFilter = true;
            unset($filters['quotations__updated_at']);
        } elseif (isset($filters['quotations_updated_at'])) {
            $updatedAtFilterValue = $filters['quotations_updated_at'];
            $hasUpdatedAtFilter = true;
            unset($filters['quotations_updated_at']);
        }
        
        // MANUAL DATE FILTERS REMOVED - Handled by AutoFilterable trait
        /*
        // Handle quotation_date filter
        if ($hasQuotationDateFilter && !empty(trim($quotationDateFilterValue))) {
            try {
                $dateTerm = trim($quotationDateFilterValue);
                \Log::info('QuotationController: Processing quotation_date filter', ['term' => $dateTerm]);
                
                // Try to parse date if it's in dd/mm/yyyy format
                if (strpos($dateTerm, '/') !== false) {
                    $date = \Carbon\Carbon::createFromFormat('d/m/Y', $dateTerm)->format('Y-m-d');
                    $query->whereDate('quotation_date', $date);
                    \Log::info('QuotationController: Applied date filter (dd/mm/yyyy)', ['date' => $date]);
                } else {
                    // Fallback for other formats or partial search (e.g. "November", "nov", "november")
                    // Check if it's a valid date string first
                    $timestamp = strtotime($dateTerm);
                    if ($timestamp !== false && strlen($dateTerm) > 5) {
                        // Only use strtotime if it's a reasonable date string (not just "november")
                        $query->whereDate('quotation_date', date('Y-m-d', $timestamp));
                        \Log::info('QuotationController: Applied date filter (strtotime)', ['date' => date('Y-m-d', $timestamp)]);
                    } else {
                        // If not a date, try matching month name or just LIKE
                        // This handles cases like "november", "nov", "November", etc.
                        $query->where(function($q) use ($dateTerm) {
                            $q->whereRaw("DATE_FORMAT(quotation_date, '%d %M %Y') LIKE ?", ["%{$dateTerm}%"])
                              ->orWhereRaw("DATE_FORMAT(quotation_date, '%M %Y') LIKE ?", ["%{$dateTerm}%"])
                              ->orWhereRaw("DATE_FORMAT(quotation_date, '%M') LIKE ?", ["%{$dateTerm}%"])
                              ->orWhereRaw("DATE_FORMAT(quotation_date, '%m') LIKE ?", ["%{$dateTerm}%"])
                              ->orWhereRaw("DATE_FORMAT(quotation_date, '%Y-%m-%d') LIKE ?", ["%{$dateTerm}%"]);
                        });
                        \Log::info('QuotationController: Applied date filter (month name search)', ['term' => $dateTerm]);
                    }
                }
            } catch (\Exception $e) {
                // If parsing fails, use simple LIKE search
                $dateTerm = trim($quotationDateFilterValue);
                $query->where(function($q) use ($dateTerm) {
                    $q->whereRaw("DATE_FORMAT(quotation_date, '%d %M %Y') LIKE ?", ["%{$dateTerm}%"])
                      ->orWhereRaw("DATE_FORMAT(quotation_date, '%M %Y') LIKE ?", ["%{$dateTerm}%"])
                      ->orWhereRaw("DATE_FORMAT(quotation_date, '%M') LIKE ?", ["%{$dateTerm}%"]);
                });
                \Log::warning('QuotationController: Date filter parsing failed, using fallback', [
                    'term' => $dateTerm,
                    'error' => $e->getMessage()
                ]);
            }
        }
        */
        
        // Handle quotations.updated_at filter
        /*
        if ($hasUpdatedAtFilter && !empty(trim($updatedAtFilterValue))) {
            $term = trim($updatedAtFilterValue);
            // Filter by updated_at column directly on quotations table
            // Search in multiple date formats to handle "nov" (November) search
            $query->where(function($q) use ($term) {
                $q->whereRaw("DATE_FORMAT(quotations.updated_at, '%d %M %Y') LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("DATE_FORMAT(quotations.updated_at, '%M %Y') LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("DATE_FORMAT(quotations.updated_at, '%Y-%m-%d') LIKE ?", ["%{$term}%"]);
            });
        }
        */

        // Apply other column filters (excluding date filters to avoid conflicts)
        /*
        $columnMap = [];
        // Temporarily replace filter input to exclude date filters
        $originalFilters = $request->input('filter', []);
        if ($hasQuotationDateFilter || $hasUpdatedAtFilter) {
            // Remove date filters from request to prevent AutoFilterable from processing them
            $request->merge(['filter' => $filters]);
            \Log::info('QuotationController: Removed date filters from request', [
                'remaining_filters' => $filters,
                'had_quotation_date' => $hasQuotationDateFilter,
                'had_updated_at' => $hasUpdatedAtFilter
            ]);
        }
        $this->applyColumnFilters($query, null, $columnMap);
        // Restore original filters after processing
        if ($hasQuotationDateFilter || $hasUpdatedAtFilter) {
            $request->merge(['filter' => $originalFilters]);
        }
        */

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('prospect_id')) {
            $query->where('prospect_id', $request->prospect_id);
        }

        if ($request->filled('survey_id')) {
            $query->where('survey_id', $request->survey_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('quotation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('quotation_date', '<=', $request->date_to);
        }

        if ($request->filled('company')) {
            $query->whereHas('prospect', function ($q) use ($request) {
                $q->where('company_name', 'like', '%' . $request->company . '%');
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quotation_number', 'like', '%' . $search . '%')
                  ->orWhereHas('prospect', function ($prospectQuery) use ($search) {
                      $prospectQuery->where('company_name', 'like', '%' . $search . '%');
                  });
            });
        }

        // Sorting
        // We let AutoFilterable trait handle the sorting (including joins for relations like branch.name)
        // We only apply default sorting if no specific sort is requested
        if (!$request->filled('sort')) {
            $query->orderBy('quotations.quotation_date', 'desc')
                  ->orderBy('quotations.created_at', 'desc');
        }

        $quotations = $query->paginate(15);

        // Data for dropdowns
        $prospects = Cache::remember('quotation:index:prospects', 300, function () {
            return \App\Models\Prospect::select('id', 'company_name', 'contact_person')
                ->orderBy('company_name')
                ->get();
        });

        $surveys = Cache::remember('quotation:index:surveys', 300, function () {
            return \App\Models\Survey::with(['surveyor:id,name'])
                ->select('id', 'survey_number', 'surveyor_id')
                ->orderByDesc('id')
                ->get();
        });

        $approvers = Cache::remember('quotation:index:approvers', 300, function () {
            return User::where('is_active', true)
                ->where(function ($q) {
                    $q->where('data_restriction', 'manager')
                        ->orWhereHas('roles', function ($rq) {
                            $rq->whereHas('permissions', function ($pq) {
                                $pq->where('name', 'marketing.quotations.approve');
                            });
                        });
                })
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        });

        $masterRentals = Cache::remember('quotation:index:master-rentals', 300, function () {
            return MasterRental::select('id', 'rental_name', 'category')
                ->with(['rentalPrices:id,master_rental_id,monthly_price'])
                ->where('is_active', true)
                ->orderBy('rental_name')
                ->get();
        });

        $pagination = $quotations->toArray();

        return view('marketing.quotations.index', compact(
            'quotations', 
            'pagination', // Add pagination array for view logic
            'prospects', 
            'surveys', 
            'approvers', 
            'masterRentals'
        ));
    }

    public function create()
    {
        // MOM10: Generate quotation number using DocumentNumberService (default branch code)
        $documentNumberService = new \App\Services\DocumentNumberService();
        $quotationNumber = $documentNumberService->generate('quotation');
        $customers = Customer::all();
        $marketingStaff = User::all();
        $billingMethods = MasterOption::where('name', 'Billing Method')->first()?->optionDetails ?? collect();
        // Status field removed during creation - default to 'draft'
        $surveys = collect(); // Empty collection for now
        $provinces = collect(); // Empty collection for now
        $products = collect(); // Empty collection for now
        
        // Fetch rental aliases from MasterOption
        $rentalAliases = MasterOption::where('name', 'rental_alias')->first()?->optionDetails()->where('is_active', true)->get() ?? collect();

        return view('marketing.quotations.create', compact('quotationNumber', 'customers', 'marketingStaff', 'billingMethods', 'surveys', 'provinces', 'products', 'rentalAliases'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quotation_number' => 'nullable|string|unique:quotations,quotation_number',
            'quotation_date' => 'nullable|date',
            'valid_until' => 'required|date|after_or_equal:today',
            'prospect_id' => 'required|exists:prospects,id',
            'survey_id' => 'required|exists:surveys,id',
            'company_name' => 'required|string|max:255',
            'pic_name' => 'required|string|max:255',
            'billing_methods' => 'required|string',
            // Status field removed - default to 'draft'
            'rental_period' => 'nullable|string',
            'terms_of_payment' => 'nullable|string',
            'marketing_id' => 'required|exists:users,id',
            'approved_by' => 'nullable|exists:users,id',
            'date_approved' => 'nullable|date',
            'internal_notes' => 'nullable|string',
            'additional_notes' => 'nullable|string',
            'quotation_type' => 'nullable|in:new,renewal',
            'existing_contract_id' => 'nullable|exists:contracts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $this->ensureRenewalSourceCanProceed($request->quotation_type, $request->existing_contract_id);

        // MOM10: Auto-generate quotation number if not provided using DocumentNumberService
        $quotationNumber = $request->quotation_number;
        if (!$quotationNumber) {
            $documentNumberService = new \App\Services\DocumentNumberService();
            // Get branch code from survey's building location
            $quotationNumber = $documentNumberService->generate(
                'quotation',
                null, // Will get from survey
                null, // Will get from survey
                null,
                null,
                $request->survey_id, // Get branch from survey
                null
            );
        }
        
        // Auto-generate quotation_date if not provided (use current date)
        $quotationDate = $request->quotation_date ?: now()->toDateString();
        
        // Default status to 'draft' - status field removed during creation
        $status = 'draft';
        
        // Auto-inherit marketing staff from prospect for data consistency
        $prospect = \App\Models\Prospect::find($request->prospect_id);
        $marketingId = $prospect ? $prospect->assigned_to : $request->marketing_id;
        
        $quotation = Quotation::create([
            'quotation_number' => $quotationNumber,
            'prospect_id' => $request->prospect_id,
            'survey_id' => $request->survey_id,
            'quotation_date' => $quotationDate,
            'valid_until' => $request->valid_until,
            'company_name' => $request->company_name,
            'pic_name' => $request->pic_name,
            'billing_methods' => $request->billing_methods,
            'status' => $status,
            'rental_period' => $request->rental_period,
            'terms_of_payment' => $request->terms_of_payment,
            'marketing_id' => $marketingId, // Auto-inherit from prospect
            'approved_by' => $request->approved_by,
            'date_approved' => $request->date_approved,
            'internal_notes' => $request->internal_notes,
            'additional_notes' => $request->additional_notes,
            'quotation_type' => $request->quotation_type ?? 'new',
            'existing_contract_id' => $request->existing_contract_id,
            'total_amount' => $request->total_amount ?? 0,
            'discount_amount' => $request->discount_amount ?? 0,
            'tax_amount' => $request->tax_amount ?? 0,
            'grand_total' => $request->grand_total ?? 0,
            'terms_conditions' => null,
            'created_by' => Auth::id() ?? 1, // Fallback to admin user if not authenticated
        ]);

        // Check auto approval after quotation is created
        $isAutoApprovable = $this->checkAutoApproval($quotation);
        $contract = null;
        
        if ($isAutoApprovable) {
            // Auto approve quotation
            $quotation->update([
                'status' => 'approved',
                'approved_by' => null, // Auto approved
                'date_approved' => now(),
                'updated_by' => Auth::id() ?? 1
            ]);
            
            // Auto-generate contract
            $contract = $this->generateContractFromQuotation($quotation);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation created successfully' . ($contract ? ' and contract generated' : ''),
            'data' => [
                'quotation' => $quotation->load(['prospect', 'survey', 'creator', 'marketing', 'approver']),
                'contract' => $contract
            ]
        ]);
    }

    public function show(Quotation $quotation)
    {
        $quotation->load([
            'prospect', 
            'customer',
            'survey', 
            'creator', 
            'updater', 
            'marketing', 
            'approver', 
            'contracts', 
            'branch',
            'quotationDetails.masterRental',
            'quotationDetails.survey',
            'quotationDetails.room.survey',
            'quotationDetails.room.room', // Eager load SurveyDetail -> MasterRoom for dynamic names
            'quotationSurveys.survey.surveyDetails',
            'quotationRooms.room',
            'existingContract.contractRooms.room',
            'existingContract.contractRentals.masterRental',
            'quotationRentals.masterRental'
        ]);
        return view('marketing.quotations.show', compact('quotation'));
    }

    public function downloadPdf(Quotation $quotation)
    {
        // Load quotation with all necessary relationships
        $quotation->load([
            'prospect', 
            'survey', 
            'creator', 
            'updater', 
            'marketing', 
            'approver', 
            'contracts', 
            'branch',
            'quotationDetails.masterRental',
            'quotationSurveys.survey',
            'quotationRooms',
            'quotationRentals.masterRental'
        ]);

        // Generate PDF using DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('marketing.quotations.pdf', compact('quotation'));
        
        // Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');
        
        // Generate filename - replace invalid characters (/ and \) with dash
        $safeQuotationNumber = str_replace(['/', '\\'], '-', $quotation->quotation_number);
        $filename = 'Quotation_' . $safeQuotationNumber . '_' . date('Y-m-d') . '.pdf';
        
        // Return PDF download
        return $pdf->download($filename);
    }

    public function cancel(Quotation $quotation, Request $request)
    {
        // Check if quotation can be cancelled (only waiting_for_approval status)
        if ($quotation->status !== 'waiting_for_approval') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya Quotation dengan status Waiting for Approval yang bisa dibatalkan.'
            ], 422);
        }

        // Check if user has permission to cancel (using canApprove - permission-based)
        $user = Auth::user();
        
        if (!$user->canApprove('quotations')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk membatalkan Quotation. Pastikan role Anda memiliki permission "Approve" untuk Quotations.'
            ], 403);
        }

        // Validate reason
        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        try {
            // Store cancellation reason in internal_notes with prefix
            $cancellationNote = "[CANCELLED " . now()->format('d/m/Y H:i') . " by " . Auth::user()->name . "]\n" . $request->reason;
            
            $quotation->update([
                'status' => 'cancelled',
                'internal_notes' => $cancellationNote,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Quotation berhasil dibatalkan.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit(Quotation $quotation)
    {
        $quotation->load([
            'prospect', 
            'survey', 
            'creator', 
            'updater', 
            'marketing', 
            'approver', 
            'contracts', 
            'quotationDetails.masterRental',
            'quotationDetails.room',
            'quotationDetails.survey',
            'quotationSurveys.survey'
        ]);
        
        // Fetch rental aliases from MasterOption
        $rentalAliases = MasterOption::where('name', 'rental_alias')->first()?->optionDetails()->where('is_active', true)->get() ?? collect();
        
        return view('marketing.quotations.edit', compact('quotation', 'rentalAliases'));
    }

    public function update(Request $request, Quotation $quotation)
    {
        $validator = Validator::make($request->all(), [
            'quotation_number' => 'nullable|string|unique:quotations,quotation_number,' . $quotation->id,
            'quotation_date' => 'nullable|date',
            'valid_until' => 'required|date|after:quotation_date',
            'prospect_id' => 'required|exists:prospects,id',
            'survey_id' => 'required|exists:surveys,id',
            'company_name' => 'required|string|max:255',
            'pic_name' => 'required|string|max:255',
            'billing_methods' => 'required|string',
            'status' => 'required|in:draft,sent,approved,accepted,rejected,expired',
            'rental_period' => 'nullable|string',
            'terms_of_payment' => 'nullable|string',
            'marketing_id' => 'required|exists:users,id',
            'approved_by' => 'nullable|exists:users,id',
            'date_approved' => 'nullable|date',
            'internal_notes' => 'nullable|string',
            'additional_notes' => 'nullable|string',
            'quotation_type' => 'nullable|in:new,renewal',
            'existing_contract_id' => 'nullable|exists:contracts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $this->ensureRenewalSourceCanProceed($request->quotation_type, $request->existing_contract_id);

        $oldStatus = $quotation->status;
        
        // Auto-inherit marketing staff from prospect for data consistency
        $prospect = \App\Models\Prospect::find($request->prospect_id);
        $marketingId = $prospect ? $prospect->assigned_to : $quotation->marketing_id;
        
        $quotation->update([
            'quotation_number' => $request->quotation_number ?? $quotation->quotation_number,
            'quotation_date' => $request->quotation_date ?? $quotation->quotation_date,
            'valid_until' => $request->valid_until,
            'prospect_id' => $request->prospect_id,
            'survey_id' => $request->survey_id,
            'company_name' => $request->company_name,
            'pic_name' => $request->pic_name,
            'billing_methods' => $request->billing_methods,
            'status' => $request->status,
            'rental_period' => $request->rental_period,
            'terms_of_payment' => $request->terms_of_payment,
            'marketing_id' => $marketingId, // Auto-inherit from prospect, not from request
            'approved_by' => $request->approved_by,
            'date_approved' => $request->date_approved,
            'internal_notes' => $request->internal_notes,
            'additional_notes' => $request->additional_notes,
            'quotation_type' => $request->quotation_type ?? $quotation->quotation_type,
            'existing_contract_id' => $request->existing_contract_id,
            'total_amount' => $request->total_amount ?? $quotation->total_amount,
            'discount_amount' => $request->discount_amount ?? $quotation->discount_amount,
            'tax_amount' => $request->tax_amount ?? $quotation->tax_amount,
            'grand_total' => $request->grand_total ?? $quotation->grand_total,
            'terms_conditions' => $request->terms_conditions,
            'updated_by' => Auth::id() ?? 1 // Fallback to admin user if not authenticated
        ]);

        // Auto-generate contract if status changed to 'approved' or 'accepted' (as per BRD)
        if (($oldStatus !== 'approved' && $request->status === 'approved') || 
            ($oldStatus !== 'accepted' && $request->status === 'accepted')) {
            
            // Only generate contract if not already exists
            if (!$quotation->contracts()->exists()) {
                $contract = $this->generateContractFromQuotation($quotation);
            }
        }

        return redirect()->route('marketing.quotations.show', $quotation)
            ->with('success', 'Quotation updated successfully');
    }

    /**
     * Get prospect data with marketing staff for auto-fill
     */
    public function getProspectData($prospectId)
    {
        $prospect = \App\Models\Prospect::with('assignedTo')->find($prospectId);
        
        if (!$prospect) {
            return response()->json([
                'status' => 'error',
                'message' => 'Prospect not found'
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $prospect->id,
                'company_name' => $prospect->company_name,
                'contact_person' => $prospect->contact_person,
                'contact_phone' => $prospect->contact_phone,
                'contact_email' => $prospect->contact_email,
                'company_address' => $prospect->company_address,
                'assigned_to' => $prospect->assigned_to,
                'marketing_staff' => [
                    'id' => $prospect->assignedTo->id ?? null,
                    'name' => $prospect->assignedTo->name ?? 'Not Assigned'
                ]
            ]
        ]);
    }

    /**
     * Update Goal SQ via AJAX auto-save
     */
    public function updateGoal(Request $request, Quotation $quotation)
    {
        $request->validate([
            'goal_sq' => 'nullable|integer|min:10|max:100'
        ]);

        try {
            $quotation->update([
                'goal_sq' => $request->goal_sq,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Goal SQ updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Goal SQ: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateEditableFields(Request $request, Quotation $quotation)
    {
        $validated = $request->validate([
            'quotation_date' => 'nullable|date',
        ]);

        if ($quotation->contracts()->exists() || in_array($quotation->status, ['cancelled', 'contract'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tanggal Quotation tidak bisa diubah untuk SQ ini.'
            ], 422);
        }

        try {
            $updateData = [];

            if (array_key_exists('quotation_date', $validated) && $validated['quotation_date']) {
                $quotationDate = \Carbon\Carbon::parse($validated['quotation_date'])->toDateString();
                $updateData['quotation_date'] = $quotationDate;
                $updateData['valid_until'] = \Carbon\Carbon::parse($quotationDate)->addDays(30)->toDateString();
            }

            if (empty($updateData)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada data yang diperbarui.'
                ], 422);
            }

            $updateData['updated_by'] = Auth::id();
            $quotation->update($updateData);

            return response()->json([
                'status' => 'success',
                'message' => 'Tanggal Quotation berhasil diperbarui',
                'data' => [
                    'quotation_date' => $quotation->quotation_date?->format('Y-m-d'),
                    'valid_until' => $quotation->valid_until?->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui Tanggal Quotation: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Quotation $quotation, Request $request)
    {
        // Only allow deletion if status is 'draft'
        if ($quotation->status !== 'draft') {
            $msg = 'Hanya Quotation dengan status Draft yang bisa dihapus.';
            return response()->json([
                'status' => 'error',
                'message' => $msg,
                'errors' => [$msg]
            ], 422);
        }

        // Check if quotation has related contracts
        if ($quotation->contracts()->count() > 0) {
            $msg = 'Quotation tidak bisa dihapus karena sudah memiliki Contract terkait.';
            return response()->json([
                'status' => 'error',
                'message' => $msg,
                'errors' => [$msg]
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Perform Soft Delete (Set status to 'cancelled')
            $quotation->update([
                'status' => 'cancelled',
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Quotation berhasil dibatalkan (cancelled).'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete quotation: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus quotation: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:quotations,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()->all()
            ], 422);
        }

        try {
            $ids = $request->ids;
            $quotations = Quotation::whereIn('id', $ids)->get();
            
            $deletedCount = 0;
            $errors = [];
            
            DB::beginTransaction();
            
            foreach ($quotations as $quotation) {
                if ($quotation->status !== 'draft') {
                    $errors[] = "Quotation '{$quotation->quotation_number}' can only be cancelled if status is Draft.";
                    continue;
                }
                
                if ($quotation->contracts()->count() > 0) {
                    $errors[] = "Quotation '{$quotation->quotation_number}' cannot be cancelled because it has related Contracts.";
                    continue;
                }
                
                $quotation->update([
                    'status' => 'cancelled',
                    'updated_by' => Auth::id()
                ]);
                
                $deletedCount++;
            }
            
            DB::commit();
            
            $success = $deletedCount > 0;
            $message = "Berhasil membatalkan {$deletedCount} quotation(s).";
            
            if ($deletedCount === 0 && !empty($errors)) {
                $message = "Gagal membatalkan quotation.";
                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                    'count' => 0,
                    'errors' => $errors
                ], 422);
            } elseif (!empty($errors)) {
                 $message = "Berhasil membatalkan {$deletedCount} quotation(s). Beberapa gagal.";
            }
            
            return response()->json([
                'status' => 'success',
                'success' => $success,
                'message' => $message,
                'count' => $deletedCount,
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error hiding records: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    /**
     * Copy an approved quotation as a new draft revision.
     * Keeps the same quotation_number but increments revision_number.
     */
    public function copy(Quotation $quotation)
    {
        // Only approved quotations can be copied
        if ($quotation->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya Quotation dengan status Approved yang bisa di-copy.'
            ], 422);
        }

        try {
            // Create a new revision copy
            $newQuotation = $quotation->copyAsRevision();

            return response()->json([
                'status' => 'success',
                'message' => 'Quotation berhasil di-copy sebagai revisi baru (Draft).',
                'data' => [
                    'new_quotation_id' => $newQuotation->id,
                    'revision_number' => $newQuotation->revision_number,
                    'redirect_url' => route('marketing.quotations.show', $newQuotation->id)
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to copy quotation: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal meng-copy quotation: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dashboard()
    {
        $totalQuotations = Quotation::count();
        $pendingQuotations = Quotation::where('status', 'Pending')->count();
        $approvedQuotations = Quotation::where('status', 'Approved')->count();
        $rejectedQuotations = Quotation::where('status', 'Rejected')->count();

        $recentQuotations = Quotation::with(['customer', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $monthlyQuotations = Quotation::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_quotations' => $totalQuotations,
                'pending_quotations' => $pendingQuotations,
                'approved_quotations' => $approvedQuotations,
                'rejected_quotations' => $rejectedQuotations,
                'recent_quotations' => $recentQuotations,
                'monthly_quotations' => $monthlyQuotations
            ]
        ]);
    }

    private function generateQuotationNumber()
    {
        $prefix = 'QT';
        $year = date('Y');
        $month = date('m');
        
        $lastQuotation = Quotation::where('quotation_number', 'like', $prefix . $year . $month . '%')
            ->orderBy('quotation_number', 'desc')
            ->first();

        if ($lastQuotation) {
            $lastNumber = intval(substr($lastQuotation->quotation_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function email(Quotation $quotation)
    {
        // Logic to send quotation via email
        return response()->json([
            'status' => 'success',
            'message' => 'Quotation sent via email successfully'
        ]);
    }

    public function print(Quotation $quotation)
    {
        // Logic to generate and print quotation
        return response()->json([
            'status' => 'success',
            'message' => 'Quotation printed successfully'
        ]);
    }

    public function finalize(Quotation $quotation)
    {
        if ($quotation->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft quotations can be finalized'
            ], 422);
        }

        // Update quotation status to waiting for approval
        $quotation->update([
            'status' => 'waiting_for_approval',
            'updated_by' => Auth::id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation has been finalized and sent for approval',
            'data' => [
                'quotation' => $quotation
            ]
        ]);
    }

    public function approve(Quotation $quotation)
    {
        if ($quotation->status !== 'waiting_for_approval') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only quotations waiting for approval can be approved'
            ], 422);
        }
        
        // Validate quotation has details before approval
        $detailsCount = \App\Models\QuotationDetail::where('quotation_id', $quotation->id)->count();
        if ($detailsCount === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot approve quotation without any quotation details. Please add rental items first.'
            ], 422);
        }

        $this->ensureQuotationRenewalCanProceed($quotation);

        // Check approval permission
        if (!Auth::user()->canApprove('quotations')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin untuk menyetujui Quotation.'
            ], 403);
        }

        $quotation->update([
            'status' => 'approved',
            'approved_by' => Auth::id() ?? 1,
            'date_approved' => now(),
            'updated_by' => Auth::id() ?? 1
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation approved successfully',
            'data' => [
                'quotation' => $quotation
            ]
        ]);
    }

    public function reject(Quotation $quotation, Request $request)
    {
        if ($quotation->status !== 'waiting_for_approval') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only quotations waiting for approval can be rejected'
            ], 422);
        }

        // Check if user has permission to reject (using canApprove - permission-based)
        $user = Auth::user();
        
        if (!$user->canApprove('quotations')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk menolak Quotation. Pastikan role Anda memiliki permission "Approve" untuk Quotations.'
            ], 403);
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

        $quotation->update([
            'status' => 'rejected',
            'terms_conditions' => $request->rejection_reason,
            'updated_by' => Auth::id() ?? 1 // Fallback to admin user if not authenticated
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation rejected successfully',
            'data' => $quotation
        ]);
    }

    /**
     * Get approval workflow for quotation
     */
    public function getApprovalWorkflow(Quotation $quotation)
    {
        $user = Auth::user();
        
        // Get managers who can approve (strictly permission-based)
        $managers = User::where('is_active', true)
          ->where(function($q) {
              $q->whereHas('permissions', function($pq) {
                  $pq->where('name', 'marketing.quotations.approve');
              })->orWhereHas('roles.permissions', function($rpq) {
                  $rpq->where('name', 'marketing.quotations.approve');
              });
          })->get();

        // Check if current user can approve (using permission-based check)
        $canApprove = $user->canApprove('quotations');
        
        // Get approval history
        $approvalHistory = $quotation->approvalHistory ?? collect();

        return response()->json([
            'status' => 'success',
            'data' => [
                'can_approve' => $canApprove,
                'managers' => $managers,
                'approval_history' => $approvalHistory,
                'current_status' => $quotation->status,
                'approval_workflow' => [
                    'draft' => 'Created by marketing',
                    'sent' => 'Sent to customer',
                    'approved' => 'Approved by manager',
                    'accepted' => 'Accepted by customer',
                    'rejected' => 'Rejected by manager/customer'
                ]
            ]
        ]);
    }

    /**
     * Request approval for quotation
     */
    public function requestApproval(Quotation $quotation, Request $request)
    {
        if ($quotation->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft quotations can be sent for approval'
            ], 422);
        }
        
        // Validate quotation has details before sending for approval
        $detailsCount = \App\Models\QuotationDetail::where('quotation_id', $quotation->id)->count();
        if ($detailsCount === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot send quotation for approval without any quotation details. Please add rental items first.'
            ], 422);
        }

        $user = Auth::user();
        
        // Check if user can request approval (has write/create permission for quotations)
        $canRequestApproval = $user->hasPermission('marketing.quotations.write') 
                           || $user->hasPermission('marketing.quotations.create')
                           || $user->canApprove('quotations');
        
        if (!$canRequestApproval) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk mengirim quotation untuk approval.'
            ], 403);
        }

        // Update status to sent for approval
        $quotation->update([
            'status' => 'sent',
            'updated_by' => Auth::id()
        ]);

        // Log approval request
        $this->logApprovalRequest($quotation, $user);

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation sent for approval successfully',
            'data' => $quotation
        ]);
    }

    /**
     * Get pending approvals for managers
     */
    public function getPendingApprovals(Request $request)
    {
        try {
            \Log::info('getPendingApprovals called');
            $user = Auth::user();
            \Log::info('User: ' . ($user ? $user->id : 'null'));
            
            // Check if user has permission to view pending approvals (using canApprove - permission-based)
            $canViewApprovals = $user->canApprove('quotations');
            
            \Log::info('Can View Approvals: ' . ($canViewApprovals ? 'true' : 'false'));
            
            if (!$canViewApprovals) {
                \Log::info('User cannot view approvals, returning 403');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses untuk melihat daftar approval. Pastikan role Anda memiliki permission "Approve" untuk Quotations.'
                ], 403);
            }

            $query = Quotation::with(['prospect', 'marketing', 'creator'])
                ->where('status', 'sent');

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Filter by marketing staff
            if ($request->filled('marketing_id')) {
                $query->where('marketing_id', $request->marketing_id);
            }

            $quotations = $query->orderBy('created_at', 'desc')->paginate(15);
            \Log::info('Found ' . $quotations->count() . ' pending quotations');

            return response()->json([
                'status' => 'success',
                'data' => $quotations
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getPendingApprovals: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load pending approvals: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log approval request
     */
    private function logApprovalRequest($quotation, $user)
    {
        // This would typically log to an approval_logs table
        // For now, we'll use the existing audit trail
        $quotation->update([
            'internal_notes' => $quotation->internal_notes . "\n\nApproval requested by: " . $user->name . " at " . now()->format('Y-m-d H:i:s')
        ]);
    }

    public function send(Quotation $quotation)
    {
        if ($quotation->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft quotations can be sent'
            ], 422);
        }

        $quotation->update([
            'status' => 'sent',
            'updated_by' => Auth::id() ?? 1 // Fallback to admin user if not authenticated
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation sent successfully',
            'data' => $quotation
        ]);
    }

    public function accept(Quotation $quotation)
    {
        if (!in_array($quotation->status, ['sent', 'approved'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only sent or approved quotations can be accepted by customer'
            ], 422);
        }

        $this->ensureQuotationRenewalCanProceed($quotation);

        $quotation->update([
            'status' => 'accepted',
            'updated_by' => Auth::id() ?? 1 // Fallback to admin user if not authenticated
        ]);

        // Auto-generate contract when customer accepts (as per BRD)
        $contract = null;
        if (!$quotation->contracts()->exists()) {
            $contract = $this->generateContractFromQuotation($quotation);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation accepted by customer and contract generated successfully',
            'data' => [
                'quotation' => $quotation,
                'contract' => $contract
            ]
        ]);
    }

    public function convertToContract(Quotation $quotation)
    {
        if ($quotation->status !== 'accepted') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only accepted quotations can be converted to contract'
            ], 422);
        }

        // Check if contract already exists
        if ($quotation->contracts()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contract already exists for this quotation'
            ], 422);
        }

        $this->ensureQuotationRenewalCanProceed($quotation);

        $contract = $this->generateContractFromQuotation($quotation);

        return response()->json([
            'status' => 'success',
            'message' => 'Contract created successfully',
            'data' => $contract
        ]);
    }

    /**
     * Generate contract from quotation (as per BRD requirement)
     */
    private function generateContractFromQuotation(Quotation $quotation)
    {
        $this->ensureQuotationRenewalCanProceed($quotation);

        // MOM10: Generate contract number using DocumentNumberService
        // Get branch code from quotation's survey building location
        $documentNumberService = new \App\Services\DocumentNumberService();
        $contractNumber = $documentNumberService->generate(
            'contract',
            null, // Will get from quotation
            null, // Will get from quotation
            null, // Will get from quotation
            $quotation->id, // Get branch from quotation
            null,
            null
        );
        
        // Calculate contract dates based on quotation
        $startDate = $quotation->quotation_date ? \Carbon\Carbon::parse($quotation->quotation_date)->addDays(7) : now()->addDays(7);
        $endDate = $quotation->rental_period ? $this->calculateEndDate($startDate, $quotation->rental_period) : $startDate->copy()->addYear();
        
        // Target Marketing Check: Yes if rental period >= 12 months
        $isContractTarget = false;
        if ($quotation->rental_period) {
            $period = strtolower(trim($quotation->rental_period));
            // Check for numeric variants (e.g. "18 bulan", "2 tahun")
            if (preg_match('/(\d+)\s*(bulan|month|months)/i', $period, $matches)) {
                $isContractTarget = (int)$matches[1] >= 12;
            } elseif (preg_match('/(\d+)\s*(tahun|year|years)/i', $period, $matches)) {
                $isContractTarget = (int)$matches[1] >= 1;
            } elseif (preg_match('/^(\d+)$/', $period, $matches)) {
                // Number only - assume months
                $isContractTarget = (int)$matches[1] >= 12;
            } else {
                // Fallback to date difference if regex fails but dates are valid
                $daysDiff = $startDate->diffInDays(\Carbon\Carbon::parse($endDate));
                $isContractTarget = $daysDiff >= 360; 
            }
        } else {
            // Default logic if rental_period is empty but dates exist
            $monthsDiff = $startDate->diffInMonths(\Carbon\Carbon::parse($endDate));
            $isContractTarget = $monthsDiff >= 12;
        }
        
        // Map payment terms from quotation to contract enum values
        $paymentTerms = $this->mapPaymentTerms($quotation->terms_of_payment);
        
        // Get or create customer from prospect or survey
        $customerId = null;
        
        if ($quotation->prospect_id) {
            // Quotation created from prospect (traditional flow)
            $customerId = $this->getOrCreateCustomerFromProspect($quotation->prospect_id);
        } else {
            // Quotation created from wizard (new flow) - get customer from surveys
            // Try to get customer from multiple surveys (quotationSurveys) first
            $quotation->load('quotationSurveys.survey.customer');
            $survey = null;
            
            if ($quotation->quotationSurveys->isNotEmpty()) {
                // Get customer from first survey in quotationSurveys
                $firstQuotationSurvey = $quotation->quotationSurveys->first();
                $survey = $firstQuotationSurvey->survey ?? null;
                
                // Log all surveys for debugging
                \Log::info("Creating contract from quotation with multiple surveys", [
                    'quotation_id' => $quotation->id,
                    'surveys_count' => $quotation->quotationSurveys->count(),
                    'survey_numbers' => $quotation->quotationSurveys->map(function($qs) {
                        return $qs->survey->survey_number ?? 'N/A';
                    })->toArray()
                ]);
            } else {
                // Fallback to singular survey relationship
                $survey = Survey::with('customer')->find($quotation->survey_id);
            }
            
            if ($survey && $survey->customer) {
                $customerId = $survey->customer->id;
            } else {
                throw new \Exception("Customer not found for quotation surveys. Quotation ID: {$quotation->id}");
            }
        }
        
        $contract = Contract::create([
            'contract_number' => $contractNumber,
            'customer_id' => $customerId, // Use proper customer_id
            'quotation_id' => $quotation->id,
            'branch_id' => $quotation->branch_id, // Cascade branch from quotation
            'contract_date' => now(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'contract_value' => $quotation->grand_total ?? 0,
            'payment_terms' => $paymentTerms,
            'contract_terms' => $quotation->terms_conditions,
            'status' => 'active', // Auto-activate as per BRD
            'contract_type' => $quotation->quotation_type ?? 'new',
            'is_contract' => $isContractTarget,
            'marketing_id' => $quotation->marketing_id,
            'created_by' => Auth::id() ?? 1,
            'notes' => 'Auto-generated from quotation: ' . $quotation->quotation_number
        ]);

        // Copy all surveys from quotation to contract
        $quotation->load('quotationSurveys.survey');
        if ($quotation->quotationSurveys->isNotEmpty()) {
            foreach ($quotation->quotationSurveys as $quotationSurvey) {
                \App\Models\ContractSurvey::create([
                    'contract_id' => $contract->id,
                    'survey_id' => $quotationSurvey->survey_id,
                    'added_at' => now(),
                    'added_by' => Auth::id() ?? 1,
                    'sort_order' => $quotationSurvey->sort_order ?? 0
                ]);
            }
            \Log::info("Copied surveys from quotation to contract", [
                'contract_id' => $contract->id,
                'quotation_id' => $quotation->id,
                'surveys_count' => $quotation->quotationSurveys->count()
            ]);
        } elseif ($quotation->survey_id) {
            // Fallback: if no quotationSurveys, use singular survey_id
            \App\Models\ContractSurvey::create([
                'contract_id' => $contract->id,
                'survey_id' => $quotation->survey_id,
                'added_at' => now(),
                'added_by' => Auth::id() ?? 1,
                'sort_order' => 0
            ]);
        }

        // Create contract rooms from quotation rooms (MOM9 Fix: Ensure contract has rooms)
        $quotation->load('quotationRooms');
        $addedRoomIds = [];
        foreach ($quotation->quotationRooms as $quotationRoom) {
            // Only add rooms that have a valid room_id and haven't been added yet
            if ($quotationRoom->room_id && !in_array($quotationRoom->room_id, $addedRoomIds)) {
                \App\Models\ContractRoom::create([
                    'contract_id' => $contract->id,
                    'room_id' => $quotationRoom->room_id,
                    'created_by' => Auth::id() ?? $quotation->created_by ?? 1,
                    'updated_by' => Auth::id() ?? $quotation->updated_by ?? 1
                ]);
                $addedRoomIds[] = $quotationRoom->room_id;
            }
        }

        // Create contract rentals from quotation details
        $quotation = Quotation::with('quotationDetails')->find($quotation->id);
        if ($quotation && $quotation->quotationDetails) {
            foreach ($quotation->quotationDetails as $detail) {
                // Find matching room_id for this rental
                $masterRoomId = null;
                if ($detail->room_id) {
                    // QuotationDetail.room_id links to SurveyDetail.id
                    // SurveyDetail.room_id links to MasterRoom.id
                    if ($detail->room) {
                        $masterRoomId = $detail->room->room_id;
                    } else {
                        // Fallback to direct room_id if room relation not loaded
                        $surveyDetail = \App\Models\SurveyDetail::find($detail->room_id);
                        $masterRoomId = $surveyDetail ? $surveyDetail->room_id : $detail->room_id;
                    }
                }

                \App\Models\ContractRental::create([
                    'contract_id' => $contract->id,
                    'master_rental_id' => $detail->master_rental_id,
                    'rental_alias' => $detail->rental_alias, // Copy rental_alias from quotation detail
                    'room_id' => $masterRoomId,
                    'quantity' => $detail->quantity,
                    'unit_price' => $detail->unit_price,
                    'total_price' => $detail->total_price,
                    'created_by' => Auth::id() ?? 1,
                    'updated_by' => Auth::id() ?? 1
                ]);
            }
        }

        // Auto-generate Job Advice after contract creation (as per BRD)
        $this->generateJobAdviceFromContract($contract);

        // Auto-create Virtual Account if contract is active
        if ($contract->status === 'active' || $contract->contract_status === 'active') {
            // Update contract_status if not set
            if (!$contract->contract_status) {
                $contract->update(['contract_status' => 'active']);
            }
            // Create Virtual Account for customer
            $contractController = new \App\Http\Controllers\Marketing\ContractController();
            $contractController->createVirtualAccountForContract($contract);
        }

        // === AUTO-CANCEL JOB SCHEDULES SAAT RENEWAL ===
        // Jika quotation ini adalah renewal dari contract lama,
        // cancel semua job schedule yang tersisa dari contract lama
        if ($quotation->quotation_type === 'renewal' && $quotation->existing_contract_id) {
            try {
                $oldContract = Contract::withoutGlobalScopes()->find($quotation->existing_contract_id);

                if ($oldContract) {
                    // 1. Cancel semua job schedule sisa dari contract lama
                    $cancelledCount = $oldContract->cancelRemainingJobSchedules(
                        "Contract di-renewal menjadi {$contract->contract_number}"
                    );

                    // 2. Contract lama tetap active/draft sesuai status asal.
                    // Pembeda renewal dibaca dari relasi quotation.existing_contract_id -> contract baru.

                    // 3. Update ContractRenewal record jika ada
                    $renewal = ContractRenewal::where('contract_id', $oldContract->id)
                        ->whereIn('status', [
                            ContractRenewal::STATUS_DRAFT,
                            ContractRenewal::STATUS_PENDING_CUSTOMER,
                            ContractRenewal::STATUS_CUSTOMER_APPROVED,
                            ContractRenewal::STATUS_PENDING_INTERNAL,
                            ContractRenewal::STATUS_APPROVED
                        ])
                        ->latest()
                        ->first();

                    if ($renewal) {
                        $renewal->complete($contract->id);
                    }

                    \Log::info("Renewal completed: Contract {$oldContract->contract_number} → {$contract->contract_number}", [
                        'old_contract_id' => $oldContract->id,
                        'new_contract_id' => $contract->id,
                        'cancelled_job_schedules' => $cancelledCount,
                        'quotation_id' => $quotation->id
                    ]);
                }
            } catch (\Exception $e) {
                // Log error tapi jangan gagalkan pembuatan contract baru
                \Log::error("Error processing renewal auto-cancel: " . $e->getMessage(), [
                    'quotation_id' => $quotation->id,
                    'existing_contract_id' => $quotation->existing_contract_id,
                    'new_contract_id' => $contract->id
                ]);
            }
        }

        // === AUTO-CANCEL JOB REMOVE FREE ===
        // Jika quotation ini sebelumnya ada trial (Install Free), 
        // maka job 'remove_free' yang mungkin sudah terjadwal harus dibatalkan
        // karena unit sekarang sudah resmi menjadi kontrak (tetap di lokasi).
        try {
            $pendingRemoveFreeJobs = \App\Models\JobSchedule::where('quotation_number', $quotation->quotation_number)
                ->where('type', 'remove_free')
                ->whereIn('status', ['scheduled', 'new_job', 'assign_team', 'material_assign', 'material_ready'])
                ->get();
                
            foreach ($pendingRemoveFreeJobs as $removeJob) {
                $removeJob->update([
                    'status' => 'cancelled',
                    'internal_notes' => ($removeJob->internal_notes ? $removeJob->internal_notes . "\n" : "") . "Auto-cancelled because quotation turned into Contract: {$contractNumber}"
                ]);
                \Log::info("Auto-cancelled Remove Free Job: {$removeJob->job_number} for Quotation: {$quotation->quotation_number}");
            }
        } catch (\Exception $e) {
            \Log::error("Error auto-cancelling remove free jobs in Quotation: " . $e->getMessage());
        }

        return $contract;
    }

    private function ensureQuotationRenewalCanProceed(Quotation $quotation): void
    {
        $this->ensureRenewalSourceCanProceed(
            $quotation->quotation_type,
            $quotation->existing_contract_id
        );
    }

    private function ensureRenewalSourceCanProceed(?string $quotationType, ?int $existingContractId): void
    {
        if ($quotationType !== 'renewal') {
            return;
        }

        if (!$existingContractId) {
            throw ValidationException::withMessages([
                'existing_contract_id' => 'Contract lama wajib dipilih untuk quotation renewal.',
            ]);
        }

        $contract = Contract::withoutGlobalScopes()->find($existingContractId);
        if (!$contract) {
            throw ValidationException::withMessages([
                'existing_contract_id' => 'Contract lama tidak ditemukan.',
            ]);
        }

        $blockReason = $contract->getRenewalBlockReason();
        if ($blockReason) {
            throw ValidationException::withMessages([
                'existing_contract_id' => $blockReason,
            ]);
        }
    }

    /**
     * Calculate contract end date based on rental period
     * Format: "3 bulan", "6 hari", "12 bulan", etc.
     */
    private function calculateEndDate($startDate, $rentalPeriod)
    {
        $start = \Carbon\Carbon::parse($startDate);
        
        if (empty($rentalPeriod)) {
            \Log::warning("Empty rental_period in calculateEndDate, defaulting to 12 months");
            return $start->copy()->addMonths(12)->toDateString();
        }
        
        // Normalize rental period string
        $period = strtolower(trim($rentalPeriod));
        
        // Parse format like "3 bulan", "6 hari", "12 bulan"
        // Support both Indonesian and English
        if (preg_match('/(\d+)\s*(bulan|month|months|hari|day|days|tahun|year|years)/i', $period, $matches)) {
            $number = (int) $matches[1];
            $unit = strtolower(trim($matches[2]));
            
            if (in_array($unit, ['hari', 'day', 'days'])) {
                \Log::info("Contract period calculated: {$number} days (from rental_period: {$rentalPeriod})");
                return $start->copy()->addDays($number)->toDateString();
            } elseif (in_array($unit, ['bulan', 'month', 'months'])) {
                \Log::info("Contract period calculated: {$number} months (from rental_period: {$rentalPeriod})");
                return $start->copy()->addMonths($number)->toDateString();
            } elseif (in_array($unit, ['tahun', 'year', 'years'])) {
                \Log::info("Contract period calculated: {$number} years (from rental_period: {$rentalPeriod})");
                return $start->copy()->addYears($number)->toDateString();
            }
        }
        
        // Try legacy format matches (for backward compatibility)
        switch ($period) {
            case 'daily':
            case '1 day':
                return $start->copy()->addDay()->toDateString();
            case 'weekly':
            case '1 week':
                return $start->copy()->addWeek()->toDateString();
            case 'monthly':
            case '1 month':
                return $start->copy()->addMonth()->toDateString();
            case '3 months':
            case 'quarterly':
                return $start->copy()->addMonths(3)->toDateString();
            case '6 months':
            case 'semi-annually':
                return $start->copy()->addMonths(6)->toDateString();
            case 'yearly':
            case '1 year':
            case 'annually':
                return $start->copy()->addYear()->toDateString();
            case '2 years':
                return $start->copy()->addYears(2)->toDateString();
            case '3 years':
                return $start->copy()->addYears(3)->toDateString();
        }
        
        // If parsing failed, try to parse as number only (assume months)
        if (preg_match('/^(\d+)$/', $period, $matches)) {
            $number = (int) $matches[1];
            \Log::info("Contract period calculated: {$number} months (assuming months from number only: {$rentalPeriod})");
            return $start->copy()->addMonths($number)->toDateString();
        }
        
        // Default to 12 months if can't parse
        \Log::warning("Could not parse rental_period: {$rentalPeriod}, defaulting to 12 months");
        return $start->copy()->addMonths(12)->toDateString();
    }

    /**
     * Map payment terms from quotation form to contract enum values
     */
    private function mapPaymentTerms($termsOfPayment)
    {
        // Since form now uses same enum values as database, just return as is
        // with fallback for any legacy values
        switch ($termsOfPayment) {
            case 'cash':
                return 'cash';
            case 'credit_30':
                return 'credit_30';
            case 'credit_60':
                return 'credit_60';
            case 'credit_90':
                return 'credit_90';
            // Legacy support for old numeric values
            case '0':
            case '30':
            case '60':
            case '90':
                return $termsOfPayment === '0' ? 'cash' : 'credit_' . $termsOfPayment;
            default:
                return 'credit_30'; // Default fallback
        }
    }

    /**
     * Get or create customer from prospect (as per BRD requirement)
     */
    private function getOrCreateCustomerFromProspect($prospectId)
    {
        $prospect = \App\Models\Prospect::find($prospectId);
        if (!$prospect) {
            throw new \Exception("Prospect not found with ID: {$prospectId}");
        }

        // Check if customer already exists with same company name
        $existingCustomer = \App\Models\Customer::where('name', $prospect->company_name)->first();
        
        if ($existingCustomer) {
            // Update prospect to link to existing customer
            $prospect->update(['customer_id' => $existingCustomer->id]);
            return $existingCustomer->id;
        }

        // Create new customer from prospect data (Prospect becomes Customer)
        $customer = \App\Models\Customer::create([
            'customer_code' => \App\Models\Customer::generateCustomerCode($prospect->company_name),
            'name' => $prospect->company_name,
            'label_alias' => $prospect->company_name,
            'status' => 'customer',
            'customer_type' => 'company', // Use customer_type instead of company_type
            'company_type' => 'PT', // Default company type
            'email' => $prospect->contact_email,
            'phone' => $prospect->contact_phone,
            'address' => $prospect->company_address,
            'is_active' => true,
            'member_since' => now(),
            'assigned_to' => $prospect->assigned_to, // Assign to same marketing staff
            'created_by' => Auth::id() ?? 1,
        ]);

        // Update prospect to link to new customer
        $prospect->update(['customer_id' => $customer->id]);

        // Auto-assign customer to building if survey has building_id
        $this->assignCustomerToBuilding($customer->id, $prospect->id);

        return $customer->id;
    }

    /**
     * Auto-assign customer to building from survey
     * Support multiple buildings per customer
     */
    private function assignCustomerToBuilding($customerId, $prospectId)
    {
        try {
            // Find all surveys associated with this prospect
            $surveys = \App\Models\Survey::where('prospect_id', $prospectId)
                ->whereNotNull('building_id')
                ->get();
            
            $assignedBuildings = [];
            
            foreach ($surveys as $survey) {
                // Check if building is already assigned to another customer
                $building = \App\Models\Building::find($survey->building_id);
                
                if ($building) {
                    // Check if building already has this customer relationship
                    if (!$building->customers()->where('customers.id', $customerId)->exists()) {
                        // Attach customer to building (many-to-many)
                        $building->customers()->attach($customerId, [
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $assignedBuildings[] = $building->id;
                    }
                }
            }
            
            if (!empty($assignedBuildings)) {
                \Log::info("Auto-assigned customer {$customerId} to buildings: " . implode(', ', $assignedBuildings));
            }
        } catch (\Exception $e) {
            \Log::warning("Failed to auto-assign customer to buildings: " . $e->getMessage());
        }
    }

    /**
     * Manually assign multiple buildings to customer
     * This can be called from BuildingController or CustomerController
     */
    public function assignBuildingsToCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'building_ids' => 'required|array',
            'building_ids.*' => 'exists:buildings,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customerId = $request->customer_id;
            $buildingIds = $request->building_ids;
            $assignedBuildings = [];
            $skippedBuildings = [];

            foreach ($buildingIds as $buildingId) {
                $building = \App\Models\Building::find($buildingId);
                
                // Check if building already has this customer relationship
                if ($building->customers()->where('customers.id', $customerId)->exists()) {
                    $skippedBuildings[] = [
                        'id' => $buildingId,
                        'name' => $building->nama_gedung ?? $building->name,
                        'reason' => 'Already assigned to this customer'
                    ];
                    continue;
                }
                
                // Attach customer to building (many-to-many)
                $building->customers()->attach($customerId, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $assignedBuildings[] = [
                    'id' => $buildingId,
                    'name' => $building->nama_gedung ?? $building->name
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Buildings assigned successfully',
                'data' => [
                    'assigned_buildings' => $assignedBuildings,
                    'skipped_buildings' => $skippedBuildings
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to assign buildings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate Job Advice from Contract (as per BRD requirement)
     */
    private function generateJobAdviceFromContract(Contract $contract)
    {
        // Load quotation relationship if not loaded
        if (!$contract->relationLoaded('quotation')) {
            $contract->load('quotation');
        }
        
        // Get quotation number for reference_number
        $quotationNumber = $contract->quotation ? $contract->quotation->quotation_number : null;
        
        // Auto-generate job advice number
        $jobAdviceNumber = app(\App\Services\DocumentNumberService::class)->generate('job_advice', null, null, $contract->id);
        
        // Create Job Advice for installation
        $jobAdvice = \App\Models\JobAdvice::create([
            'job_advice_number' => $jobAdviceNumber,
            'contract_id' => $contract->id,
            'customer_id' => $contract->customer_id,
            'company_name' => $contract->customer->name,
            'type' => 'install',
            'reference_number' => $quotationNumber, // Auto-set from quotation
            'expected_date' => $contract->start_date,
            'status' => 'approved', // Auto-approve as per BRD
            'with_invoicing' => true,
            'with_materials' => true,
            'notes' => 'Auto-generated from contract: ' . $contract->contract_number,
            'submitted_by' => $contract->marketing_id,
            'created_by' => Auth::id() ?? 1,
        ]);

        // Create Job Advice Rooms (MOM9 Fix: Ensure JA has rooms for scheduling)
        $contract->load('contractRooms');
        foreach ($contract->contractRooms as $contractRoom) {
            // Find all rentals specifically for this room
            $rentalsForRoom = $contract->contractRentals()
                ->where('room_id', $contractRoom->room_id)
                ->get();

            // Fallback to legacy room-name matching when room_id is missing on old data
            if ($rentalsForRoom->isEmpty() && $contractRoom->room?->room_name) {
                $rentalsForRoom = $contract->contractRentals()
                    ->where('rental_alias', $contractRoom->room->room_name)
                    ->get();
            }

            if ($rentalsForRoom->isEmpty()) {
                \Log::warning("No contract rentals found for room {$contractRoom->room_id} while generating Job Advice {$jobAdvice->job_advice_number}");
                continue;
            }

            foreach ($rentalsForRoom as $rental) {
                // Create one JobAdviceRoom per rental so downstream job schedules can keep the full mapping
                \App\Models\JobAdviceRoom::create([
                    'job_advice_id' => $jobAdvice->id,
                    'contract_room_id' => $contractRoom->id,
                    'rental_product_id' => $rental->master_rental_id,
                    'room_name' => $contractRoom->room->room_name ?? 'Room ' . $contractRoom->id,
                    'rental_name' => $rental->rental_alias ?? ($rental->masterRental->rental_name ?? 'N/A'),
                    'quantity' => $rental->quantity ?? 1,
                    'status' => 'pending',
                    'created_by' => Auth::id() ?? 1,
                    'updated_by' => Auth::id() ?? 1
                ]);
            }
        }

        // Auto-generate Job Schedule from Job Advice (as per BRD)
        $this->generateJobScheduleFromJobAdvice($jobAdvice, $contract);

        return $jobAdvice;
    }

    /**
     * Generate Job Schedule from Job Advice (as per BRD requirement)
     */
    private function generateJobScheduleFromJobAdvice(\App\Models\JobAdvice $jobAdvice, Contract $contract)
    {
        try {
            // Get building from survey first, then fallback to customer's first building
            $building = null;
            if ($contract->quotation && $contract->quotation->survey && $contract->quotation->survey->building_id) {
                $building = $contract->quotation->survey->building;
            }
            
            // Fallback to customer's first building if no building from survey
            if (!$building) {
                $building = $contract->customer->buildings()->first();
            }
            
            // Check if building exists and has valid ID, if not, skip JobSchedule creation
            if (!$building || !$building->id) {
                \Log::warning("No valid building found for JobAdvice: {$jobAdvice->job_advice_number}. Survey building: " . ($contract->quotation->survey->building_id ?? 'none') . ", Customer buildings: " . $contract->customer->buildings()->count());
                return null;
            }
            
            // Normalize type: if Job Advice type is "install_free", use "install" for Job Schedule
            // (Job Schedule enum only allows: install, service, maintenance, removal, trial)
            // The display will show "Install Free" via JobSchedule->display_type accessor
            $jobScheduleType = strtolower($jobAdvice->type ?? 'install');
            if ($jobScheduleType === 'install_free') {
                $jobScheduleType = 'install'; // Keep as "install" for enum consistency
            }

            $jobScheduleNumber = app(\App\Services\DocumentNumberService::class)->generate(
                $jobScheduleType === 'remove' ? 'remove' : 'job_schedule',
                null,
                $building->id,
                $contract->id
            );
            
            // Create Job Schedule automatically
            $jobSchedule = \App\Models\JobSchedule::create([
                'job_number' => $jobScheduleNumber,
                'job_advice_id' => $jobAdvice->id,
                'building_id' => $building->id,
                'schedule_date' => $jobAdvice->expected_date,
                'expected_date' => $jobAdvice->expected_date,
                'status' => 'scheduled', // Default status for auto-created schedules
                'type' => $jobScheduleType,
                'company_name' => $jobAdvice->company_name,
                'contract_number' => $contract->contract_number,
                'service_period_type' => 'monthly', // Default value to prevent null
                'internal_notes' => 'Auto-generated from Job Advice: ' . $jobAdvice->job_advice_number,
                'created_by' => Auth::id() ?? 1,
                'updated_by' => Auth::id() ?? 1
            ]);

            \Log::info("Job Schedule auto-created: {$jobScheduleNumber} for Job Advice: {$jobAdvice->job_advice_number}");
            
            return $jobSchedule;
            
        } catch (\Exception $e) {
            \Log::error("Failed to auto-create Job Schedule for Job Advice {$jobAdvice->job_advice_number}: " . $e->getMessage());
            // Don't throw exception to avoid breaking the main workflow
        }
    }

    private function checkAutoApproval($quotation)
    {
        // Check if quotation meets auto approval criteria
        $autoApprovalCriteria = $this->getAutoApprovalCriteria($quotation);
        
        return $autoApprovalCriteria['is_auto_approvable'];
    }

    private function getAutoApprovalCriteria($quotation)
    {
        $criteria = [
            'is_auto_approvable' => false,
            'reasons' => [],
            'price_slab_validation' => [],
            'bottom_price_validation' => []
        ];

        // Check 1: Total amount threshold
        $totalAmountThreshold = config('quotation.auto_approval_threshold', 1000000);
        if ($quotation->grand_total >= $totalAmountThreshold) {
            $criteria['reasons'][] = "Total amount ({$quotation->grand_total}) meets threshold ({$totalAmountThreshold})";
        }

        // Check 2: Price slab validation for each rental
        if ($quotation->quotationRentals) {
            foreach ($quotation->quotationRentals as $rental) {
                $priceSlab = $rental->getApplicablePriceSlab();
                if ($priceSlab) {
                    $criteria['price_slab_validation'][] = [
                        'rental_name' => $rental->masterRental->rental_name ?? 'N/A',
                        'quantity' => $rental->quantity,
                        'slab_name' => $priceSlab->slab_name,
                        'discount_percentage' => $priceSlab->discount_percentage,
                        'is_applicable' => true
                    ];
                } else {
                    $criteria['price_slab_validation'][] = [
                        'rental_name' => $rental->masterRental->rental_name ?? 'N/A',
                        'quantity' => $rental->quantity,
                        'is_applicable' => false,
                        'reason' => 'No applicable price slab found'
                    ];
                }
            }
        }

        // Check 3: Bottom price validation
        if ($quotation->quotationRentals) {
            foreach ($quotation->quotationRentals as $rental) {
                $rental->checkBottomPrice();
                $criteria['bottom_price_validation'][] = [
                    'rental_name' => $rental->masterRental->rental_name ?? 'N/A',
                    'has_bottom_price' => $rental->has_bottom_price,
                    'bottom_price' => $rental->bottom_price,
                    'current_price' => $rental->total_price
                ];
            }
        }

        // Determine if auto approvable
        $hasValidPriceSlabs = collect($criteria['price_slab_validation'])->every('is_applicable', true);
        $meetsThreshold = $quotation->grand_total >= $totalAmountThreshold;
        
        $criteria['is_auto_approvable'] = $hasValidPriceSlabs || $meetsThreshold;

        return $criteria;
    }

    // ========================================
    // QUOTATION DETAILS MANAGEMENT
    // ========================================

    public function getDetails(Quotation $quotation)
    {
        $details = $quotation->quotationDetails()->with('masterRental')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $details
        ]);
    }

    public function addDetail(Request $request, Quotation $quotation)
    {
        $validator = Validator::make($request->all(), [
            'master_rental_id' => 'required|exists:master_rentals,id',
            'room_name' => 'required|string|max:100',
            'survey_id' => 'nullable|exists:surveys,id',
            'room_id' => 'nullable|exists:survey_details,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'specifications' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $totalPrice = $request->quantity * $request->unit_price;
        [$surveyId, $surveyDetailId] = $this->resolveQuotationDetailSurveyAndRoom($quotation, [
            'survey_id' => $request->survey_id,
            'room_id' => $request->room_id,
            'room_name' => $request->room_name,
        ]);

        $detail = QuotationDetail::create([
            'quotation_id' => $quotation->id,
            'survey_id' => $surveyId,
            'room_id' => $surveyDetailId,
            'master_rental_id' => $request->master_rental_id,
            'room_name' => $request->room_name,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'total_price' => $totalPrice,
            'specifications' => $request->specifications,
            'created_by' => Auth::id()
        ]);

        // Recalculate quotation totals
        $this->recalculateQuotationTotals($quotation);

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation detail added successfully',
            'data' => $detail->load('masterRental')
        ]);
    }

    public function updateDetail(Request $request, Quotation $quotation, QuotationDetail $detail)
    {
        // Ensure detail belongs to quotation
        if ($detail->quotation_id !== $quotation->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Detail not found for this quotation'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'master_rental_id' => 'required|exists:master_rentals,id',
            'room_name' => 'required|string|max:100',
            'survey_id' => 'nullable|exists:surveys,id',
            'room_id' => 'nullable|exists:survey_details,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'specifications' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $totalPrice = $request->quantity * $request->unit_price;
        [$surveyId, $surveyDetailId] = $this->resolveQuotationDetailSurveyAndRoom($quotation, [
            'survey_id' => $request->survey_id ?? $detail->survey_id,
            'room_id' => $request->room_id ?? $detail->room_id,
            'room_name' => $request->room_name,
        ]);

        $detail->update([
            'survey_id' => $surveyId,
            'room_id' => $surveyDetailId,
            'master_rental_id' => $request->master_rental_id,
            'room_name' => $request->room_name,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'total_price' => $totalPrice,
            'specifications' => $request->specifications,
            'updated_by' => Auth::id() ?? 1 // Fallback to admin user if not authenticated
        ]);

        // Recalculate quotation totals
        $this->recalculateQuotationTotals($quotation);

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation detail updated successfully',
            'data' => $detail->load('masterRental')
        ]);
    }

    public function deleteDetail(Quotation $quotation, QuotationDetail $detail)
    {
        // Only allow deletion if quotation status is 'draft'
        if ($quotation->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya bisa menghapus room untuk Quotation dengan status Draft.'
            ], 422);
        }

        // Ensure detail belongs to quotation
        if ($detail->quotation_id !== $quotation->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Detail not found for this quotation'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Delete the detail
            $detail->delete();

            // Recalculate quotation totals
            $this->recalculateQuotationTotals($quotation);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Room berhasil dihapus dari quotation.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete quotation detail: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus room: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkAddDetails(Request $request, Quotation $quotation)
    {
        $validator = Validator::make($request->all(), [
            'details' => 'required|array|min:1',
            'details.*.master_rental_id' => 'required|exists:master_rentals,id',
            'details.*.room_name' => 'required|string|max:100',
            'details.*.survey_id' => 'nullable|exists:surveys,id',
            'details.*.room_id' => 'nullable|exists:survey_details,id',
            'details.*.quantity' => 'required|numeric|min:0.01',
            'details.*.unit_price' => 'required|numeric|min:0',
            'details.*.specifications' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $details = [];
        foreach ($request->details as $detailData) {
            $totalPrice = $detailData['quantity'] * $detailData['unit_price'];
            [$surveyId, $surveyDetailId] = $this->resolveQuotationDetailSurveyAndRoom($quotation, $detailData);
            
            $details[] = [
                'quotation_id' => $quotation->id,
                'survey_id' => $surveyId,
                'room_id' => $surveyDetailId,
                'master_rental_id' => $detailData['master_rental_id'],
                'room_name' => $detailData['room_name'],
                'quantity' => $detailData['quantity'],
                'unit_price' => $detailData['unit_price'],
                'total_price' => $totalPrice,
                'specifications' => $detailData['specifications'] ?? null,
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        QuotationDetail::insert($details);

        // Recalculate quotation totals
        $this->recalculateQuotationTotals($quotation);

        return response()->json([
            'status' => 'success',
            'message' => 'Quotation details added successfully',
            'count' => count($details)
        ]);
    }

    public function getMasterRentals()
    {
        $masterRentals = MasterRental::select('id', 'rental_name', 'description', 'unit_price')
            ->where('is_active', true)
            ->orderBy('rental_name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $masterRentals
        ]);
    }

    private function recalculateQuotationTotals(Quotation $quotation)
    {
        $totalAmount = $quotation->quotationDetails()->sum('total_price');
        
        // Apply discount and tax if any
        $discountAmount = $quotation->discount_amount ?? 0;
        $taxAmount = $quotation->tax_amount ?? 0;
        
        $grandTotal = $totalAmount - $discountAmount + $taxAmount;

        $quotation->update([
            'total_amount' => $totalAmount,
            'grand_total' => $grandTotal,
            'updated_by' => Auth::id() ?? 1 // Fallback to admin user if not authenticated
        ]);
    }

    private function resolveQuotationDetailSurveyAndRoom(Quotation $quotation, array $data): array
    {
        $surveyId = isset($data['survey_id']) && $data['survey_id'] !== 'null'
            ? (int) $data['survey_id']
            : null;
        $roomId = isset($data['room_id']) && $data['room_id'] !== 'null'
            ? (int) $data['room_id']
            : null;

        if ($surveyId && $roomId) {
            $exists = \App\Models\SurveyDetail::where('id', $roomId)
                ->where('survey_id', $surveyId)
                ->exists();

            if ($exists) {
                return [$surveyId, $roomId];
            }
        }

        $roomName = trim((string) ($data['room_name'] ?? ''));
        if ($roomName === '') {
            return [$surveyId, $roomId];
        }

        $quotation->loadMissing('quotationSurveys');
        $candidateSurveyIds = $quotation->quotationSurveys
            ->pluck('survey_id')
            ->when($quotation->survey_id, fn ($ids) => $ids->push($quotation->survey_id))
            ->when($surveyId, fn ($ids) => $ids->prepend($surveyId))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($candidateSurveyIds->isEmpty()) {
            return [$surveyId, $roomId];
        }

        $matches = \App\Models\SurveyDetail::whereIn('survey_id', $candidateSurveyIds)
            ->whereRaw('LOWER(TRIM(room_name)) = ?', [mb_strtolower($roomName)])
            ->get();

        if ($matches->count() === 1) {
            $match = $matches->first();
            return [(int) $match->survey_id, (int) $match->id];
        }

        return [$surveyId, $roomId];
    }


    public function getSurveyData($id)
    {
        $survey = Survey::with(['surveyor', 'marketing'])->find($id);
        
        if (!$survey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Survey not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'survey_number' => $survey->survey_number,
                'marketing_id' => $survey->marketing_id ?? $survey->surveyor_id ?? null,
                'marketing_name' => $survey->marketing->name ?? $survey->surveyor->name ?? null
            ]
        ]);
    }

    // New methods for approval and price slab functionality

    public function getApprovalSummary($id)
    {
        $quotation = Quotation::with(['quotationRentals.masterRental'])->findOrFail($id);
        
        $summary = $quotation->getApprovalSummary();
        $priceSlabSummary = $quotation->getPriceSlabSummary();

        return response()->json([
            'status' => 'success',
            'data' => [
                'approval_summary' => $summary,
                'price_slab_summary' => $priceSlabSummary
            ]
        ]);
    }

    public function validateBottomPrice($id)
    {
        $quotation = Quotation::with(['quotationRentals.masterRental'])->findOrFail($id);
        
        // Check all rentals for bottom price validation
        foreach ($quotation->quotationRentals as $rental) {
            $rental->checkBottomPrice();
        }

        $summary = $quotation->getApprovalSummary();

        return response()->json([
            'status' => 'success',
            'data' => $summary
        ]);
    }

    public function getPriceSlabInfo(Request $request)
    {
        $request->validate([
            'rental_id' => 'required|exists:master_rentals,id',
            'quantity' => 'required|numeric|min:1'
        ]);

        $priceSlab = MasterPriceSlab::getApplicableSlab($request->rental_id, $request->quantity);
        
        if (!$priceSlab) {
            return response()->json([
                'status' => 'error',
                'message' => 'No applicable price slab found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'slab_name' => $priceSlab->slab_name,
                'discount_percentage' => $priceSlab->discount_percentage,
                'quantity_range' => $priceSlab->quantity_range,
                'is_applicable' => $priceSlab->isApplicableForQuantity($request->quantity)
            ]
        ]);
    }

    /**
     * Get auto approval criteria for quotation
     */
    public function getAutoApprovalCriteriaData(Quotation $quotation)
    {
        $criteria = $this->getAutoApprovalCriteria($quotation);
        
        return response()->json([
            'status' => 'success',
            'data' => $criteria
        ]);
    }

    /**
     * Check if quotation can be auto approved
     */
    public function checkAutoApprovalStatus(Quotation $quotation)
    {
        $isAutoApprovable = $this->checkAutoApproval($quotation);
        $criteria = $this->getAutoApprovalCriteria($quotation);
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'is_auto_approvable' => $isAutoApprovable,
                'criteria' => $criteria,
                'current_status' => $quotation->status,
                'can_auto_approve' => $quotation->status === 'draft' && $isAutoApprovable
            ]
        ]);
    }

    public function createRevision(Request $request, $id)
    {
        $request->validate([
            'revision_notes' => 'nullable|string|max:1000',
            'total_amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'terms_conditions' => 'nullable|string',
            'rental_period' => 'nullable|string',
            'terms_of_payment' => 'nullable|string'
        ]);

        $quotation = Quotation::findOrFail($id);
        
        if ($quotation->status === 'accepted') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot create revision for accepted quotation'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $revision = $quotation->createRevision([
                'revision_notes' => $request->revision_notes,
                'total_amount' => $request->total_amount,
                'discount_amount' => $request->discount_amount ?? 0,
                'tax_amount' => $request->tax_amount ?? 0,
                'grand_total' => $request->grand_total,
                'terms_conditions' => $request->terms_conditions,
                'rental_period' => $request->rental_period,
                'terms_of_payment' => $request->terms_of_payment,
                'status' => 'draft'
            ], Auth::id());

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Quotation revision created successfully',
                'data' => $revision
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create revision: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRevisions($id)
    {
        $quotation = Quotation::findOrFail($id);
        $revisions = $quotation->revisions()->with(['creator', 'approver'])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $revisions
        ]);
    }

    public function getFreeTrials($id)
    {
        $quotation = Quotation::findOrFail($id);
        $freeTrials = $quotation->freeTrials()->with(['room.building', 'masterRental', 'requestedBy', 'approvedBy'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $freeTrials
        ]);
    }

    public function canCreateContract($id)
    {
        $quotation = Quotation::findOrFail($id);
        $canCreate = $quotation->canCreateContract();

        return response()->json([
            'status' => 'success',
            'data' => [
                'can_create_contract' => $canCreate,
                'reason' => $canCreate ? 'Quotation is approved and ready for contract' : 'Quotation must be approved, without existing contract, and no active free trials'
            ]
        ]);
    }

    /**
     * Get quotations for dropdown (no authentication required)
     * Filtered by marketing_id and approved status
     */
    public function getForDropdown(Request $request)
    {
        try {
            // Build query with optional filters
            $query = Quotation::with('customer')
                ->select('id', 'quotation_number', 'customer_id', 'marketing_id', 'created_by', 'status', 'revision_number', 'is_latest_revision')
                ->usable();
            
            // Apply filters if provided
            if ($request->filled('marketing_id')) {
                $marketingId = $request->marketing_id;
                $query->where(function($q) use ($marketingId) {
                    $q->where('marketing_id', $marketingId)
                      ->orWhere('created_by', $marketingId);
                });
            }
            
            // Only show approved quotations for Job Advice unless specified
            $status = $request->get('status', 'approved');
            $query->where('status', $status);
            
            $quotations = $query->orderBy('quotation_number', 'desc')
                ->orderBy('revision_number', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($quotation) {
                    return [
                        'id' => $quotation->id,
                        'quotation_number' => $quotation->quotation_number,
                        'revision_number' => $quotation->revision_number,
                        'is_latest_revision' => $quotation->is_latest_revision,
                        'customer_id' => $quotation->customer_id,
                        'customer_name' => $quotation->customer ? $quotation->customer->name : 'N/A',
                        'marketing_id' => $quotation->marketing_id,
                        'created_by' => $quotation->created_by,
                        'status' => $quotation->status,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $quotations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get contracts by marketing ID for quotation wizard
     */
    public function getContractsByMarketing($marketingId)
    {
        try {
            $contracts = Contract::with('customer')
                ->where('marketing_id', $marketingId)
                ->where('status', 'active')
                ->orderBy('contract_number', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'contracts' => $contracts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load contracts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quotation details for Job Advice (including quotationRooms)
     * Only returns available rooms that haven't been used in previous Job Advices
     */
    public function getForJobAdvice($id)
    {
        try {
            $quotation = Quotation::with([
                'customer',
                'prospect',
                'quotationRooms.room.building',
                'quotationRooms.aromaProduct',
                'quotationRentals.masterRental',
                'quotationDetails.masterRental'
            ])->findOrFail($id);

            if (!Quotation::usable()->whereKey($quotation->id)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Quotation ini bukan revisi terakhir. Silakan pilih quotation revisi terbaru.',
                    'contract_rooms' => [],
                    'quotation_rooms' => []
                ], 422);
            }

            // User Request: "satu ruangan bisa lebih dari 1 unit dari contract/quotation yg berbeda"
            $usedQuotationRoomIds = [];

            // Format quotation rooms for Job Advice selection modal
            // Filter out rooms that don't exist (broken references) AND rooms that are already used
            // MOM9 Fix: For Extra, Change, Complain, Remove types, we load ALL rooms (even if used)
            $type = request('type');
            $shouldIncludeUsedRooms = $type && in_array(strtolower($type), ['extra', 'change', 'complain', 'remove', 'change_unit', 'change unit', 'removal']);

            $quotationRooms = $quotation->quotationRooms
                ->filter(function ($quotationRoom) use ($usedQuotationRoomIds, $shouldIncludeUsedRooms) {
                    // Only include rooms that exist
                    // If shouldIncludeUsedRooms is true, we ignore the used filtering
                    return $quotationRoom->room !== null 
                        && ($shouldIncludeUsedRooms || !in_array($quotationRoom->id, $usedQuotationRoomIds));
                })
                ->map(function ($quotationRoom) use ($quotation) {
                    // Find ALL corresponding rentals for this room
                    $rentalsForRoom = $quotation->quotationRentals->where('quotation_room_id', $quotationRoom->id);
                    
                    // MOM9 Fallback: If no quotationRentals, check quotationDetails
                    if ($rentalsForRoom->isEmpty()) {
                        $detailsForRoom = $this->resolveQuotationDetailRentalsForRoom($quotation, $quotationRoom);
                        $rentalsArray = $detailsForRoom->map(function($detail) {
                            return [
                                'id' => null, // No quotation_rental_id
                                'quotation_detail_id' => $detail->id,
                                'master_rental_id' => $detail->master_rental_id,
                                'rental_name' => $detail->rental_alias ?? ($detail->masterRental?->rental_name ?? 'Unknown Rental'),
                            ];
                        })->values()->toArray();
                    } else {
                        // Format rentals from quotationRentals
                        $rentalsArray = $rentalsForRoom->map(function($rental) {
                            return [
                                'id' => $rental->id, // This is quotation_rental_id
                                'quotation_detail_id' => null,
                                'master_rental_id' => $rental->master_rental_id,
                                'rental_name' => $rental->rental_alias ?? ($rental->masterRental?->rental_name ?? 'Unknown Rental'),
                            ];
                        })->values()->toArray();
                    }

                    return [
                        'id' => $quotationRoom->id,
                        'room_id' => $quotationRoom->room_id,
                        'room_name' => $quotationRoom->room_name,
                        'aroma_product_id' => $quotationRoom->aroma_product_id,
                        'aroma_variant' => $quotationRoom->aroma_variant,
                        'quotation_rental_id' => $rentalsForRoom->first()?->id,
                        'rental_product_id' => $rentalsForRoom->first()?->master_rental_id,
                        'rentals' => $rentalsArray,
                        'rental_count' => count($rentalsArray),
                        'room' => [
                            'id' => $quotationRoom->room->id,
                            'room_name' => $quotationRoom->room->room_name,
                            'room_type' => $quotationRoom->room->room_type ?? 'N/A',
                            'room_floor' => $quotationRoom->room->room_floor ?? 'N/A',
                            'building' => [
                                'id' => $quotationRoom->room->building?->id,
                                'nama_gedung' => $quotationRoom->room->building?->nama_gedung ?? $quotationRoom->room->building?->name ?? 'N/A',
                                'name' => $quotationRoom->room->building?->nama_gedung ?? $quotationRoom->room->building?->name ?? 'N/A',
                            ]
                        ],
                        'aroma_product' => $quotationRoom->aromaProduct ? [
                            'id' => $quotationRoom->aromaProduct->id,
                            'name' => $quotationRoom->aromaProduct->name,
                        ] : null,
                        'has_active_unit' => $quotationRoom->room->unitOnWalls()
                            ->where('status', 'active')
                            ->whereNotNull('serial_number_id')
                            ->exists(),
                        'active_sn' => $quotationRoom->room->unitOnWalls()
                            ->where('status', 'active')
                            ->whereNotNull('serial_number_id')
                            ->value('serial_number')
                    ];
                })
                ->values();

            // Check for broken rooms (rooms that don't exist, not rooms that are used)
            $totalQuotationRooms = $quotation->quotationRooms->count();
            $validRooms = $quotation->quotationRooms->filter(function ($quotationRoom) {
                return $quotationRoom->room !== null; // Only count rooms that exist
            })->count();
            $brokenRooms = $totalQuotationRooms - $validRooms;
            $availableRooms = $quotationRooms->count();
            
            $message = null;
            if ($brokenRooms > 0) {
                $message = "⚠️ Perhatian: Quotation ini memiliki {$brokenRooms} ruangan yang tidak valid (data room tidak ditemukan). Silakan hubungi admin untuk memperbaiki data.";
                \Log::warning("Quotation {$quotation->quotation_number} has {$brokenRooms} broken room references", [
                    'quotation_id' => $id,
                    'total_rooms' => $totalQuotationRooms,
                    'valid_rooms' => $validRooms
                ]);
            }

            return response()->json([
                'status' => 'success',
                'quotation_id' => $quotation->id,
                'quotation_number' => $quotation->quotation_number,
                'customer_name' => $quotation->customer?->name ?? $quotation->prospect?->company_name,
                'contract_rooms' => $quotationRooms, // Use same key name for compatibility
                'quotation_rooms' => $quotationRooms, // Also provide as quotation_rooms
                'total_rooms' => $availableRooms, // Available rooms (not used yet)
                'total_quotation_rooms' => $totalQuotationRooms,
                'valid_rooms' => $validRooms, // Rooms that exist (not broken)
                'broken_rooms' => $brokenRooms,
                'used_rooms' => count($usedQuotationRoomIds), // Number of rooms already used
                'message' => $message
            ]);
        } catch (\Exception $e) {
            \Log::error('Quotation getForJobAdvice error: ' . $e->getMessage(), [
                'quotation_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'contract_rooms' => [],
                'quotation_rooms' => []
            ], 500);
        }
    }

    /**
     * Resolve legacy quotation_details for one quotation room without leaking rentals
     * to another room with the same display name in a different building.
     */
    private function resolveQuotationDetailRentalsForRoom(Quotation $quotation, $quotationRoom)
    {
        $details = $quotation->quotationDetails;

        $exactDetails = $details->filter(function ($detail) use ($quotationRoom) {
            $surveyDetailMasterRoomId = $detail->room?->room_id;

            return $surveyDetailMasterRoomId && (int) $surveyDetailMasterRoomId === (int) $quotationRoom->room_id;
        });

        if ($exactDetails->isNotEmpty()) {
            return $exactDetails->values();
        }

        $targetName = $this->normalizeRoomNameForFallback($quotationRoom->room_name);
        $sameNameRooms = $quotation->quotationRooms->filter(function ($room) use ($targetName) {
            return $this->normalizeRoomNameForFallback($room->room_name) === $targetName;
        });

        // Name-only fallback is safe only when the room name is unique in the quotation.
        if ($sameNameRooms->count() === 1) {
            return $details->filter(function ($detail) use ($targetName) {
                return $this->normalizeRoomNameForFallback($detail->getRawOriginal('room_name') ?? $detail->room_name) === $targetName;
            })->values();
        }

        \Log::warning('Skipped ambiguous quotation detail fallback for Job Advice room selection', [
            'quotation_id' => $quotation->id,
            'quotation_room_id' => $quotationRoom->id,
            'room_name' => $quotationRoom->room_name,
            'same_name_room_count' => $sameNameRooms->count(),
        ]);

        return collect();
    }

    private function normalizeRoomNameForFallback(?string $name): string
    {
        return trim(mb_strtolower((string) $name));
    }
    
    /**
     * MOM9: Get rental products from quotation for Install Free job materials
     * Returns products that were selected in the quotation's rental configuration
     */
    public function getRentalProducts($id)
    {
        try {
            $quotation = Quotation::with([
                'quotationRooms.rental.masterProduct.productType',
                'quotationRooms.aromaProduct.productType',
                'quotationRentals.product.productType'
            ])->findOrFail($id);
            
            $products = collect();
            
            // Get products from quotation rooms' rentals
            foreach ($quotation->quotationRooms as $qRoom) {
                if ($qRoom->rental && $qRoom->rental->masterProduct) {
                    $product = $qRoom->rental->masterProduct;
                    if (!$products->contains('id', $product->id)) {
                        $products->push([
                            'id' => $product->id,
                            'name' => $product->name,
                            'sku' => $product->sku,
                            'product_type_name' => $product->productType?->name ?? 'Unknown',
                            'is_unit' => $product->productType?->is_unit ?? false,
                            'source' => 'rental_product'
                        ]);
                    }
                }
                
                // Also include aroma products if available
                if ($qRoom->aromaProduct) {
                    $aromaProduct = $qRoom->aromaProduct;
                    if (!$products->contains('id', $aromaProduct->id)) {
                        $products->push([
                            'id' => $aromaProduct->id,
                            'name' => $aromaProduct->name,
                            'sku' => $aromaProduct->sku ?? null,
                            'product_type_name' => $aromaProduct->productType?->name ?? 'Refill/Aroma',
                            'is_unit' => $aromaProduct->productType?->is_unit ?? false,
                            'source' => 'aroma_product'
                        ]);
                    }
                }
            }
            
            // Get products from quotation rentals (direct rental selections)
            if ($quotation->quotationRentals) {
                foreach ($quotation->quotationRentals as $qRental) {
                    if ($qRental->product) {
                        $product = $qRental->product;
                        if (!$products->contains('id', $product->id)) {
                            $products->push([
                                'id' => $product->id,
                                'name' => $product->name,
                                'sku' => $product->sku ?? null,
                                'product_type_name' => $product->productType?->name ?? 'Unknown',
                                'is_unit' => $product->productType?->is_unit ?? false,
                                'source' => 'quotation_rental'
                            ]);
                        }
                    }
                }
            }
            
            return response()->json([
                'status' => 'success',
                'data' => $products->values(),
                'quotation_id' => $quotation->id,
                'quotation_number' => $quotation->quotation_number,
                'total_products' => $products->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('Quotation getRentalProducts error: ' . $e->getMessage(), [
                'quotation_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}

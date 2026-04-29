<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Traits\ColumnFilterTrait;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\Contract;
use App\Models\ContractRenewal;
use App\Models\ContractSwitching;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use App\Models\MasterOption;
use App\Models\ContractRemark;
use App\Models\ContractRevision;
use App\Services\ContractMergeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ContractController extends Controller
{
    use ColumnFilterTrait, AccessControlFilterTrait;
    
    public function index(Request $request)
    {
        $query = Contract::with([
            'customer', 
            'quotation.prospect', 
            'quotation.survey.surveyor', 
            'quotation.marketing', 
            'quotation.approver',
            'quotation.existingContract',
            'creator', 
            'updater',
            'marketing',
            'renewals.newContract',
            'renewedByContract'
        ]);

        // Apply access control filter (hierarchical data)
        // Default: Jika tidak set hirarki, hanya bisa lihat data sendiri
        // NOTE: Pass null for branchField and warehouseField since contracts table doesn't have these columns
        $query = $this->applyAccessControlFilter($query, null, 'created_by', 'marketing_id', null, null, null);

        // Handle column filters manually for status and updated_at
        $filters = $request->input('filter', []);
        $hasStatusFilter = false;
        $statusFilterValue = null;
        $hasUpdatedAtFilter = false;
        $updatedAtFilterValue = null;
        
        // Check for status filter (map to contract_status column)
        foreach (['status', 'contract_status', 'contract__status'] as $filterKey) {
            if (isset($filters[$filterKey]) && !empty(trim($filters[$filterKey]))) {
                $statusFilterValue = trim($filters[$filterKey]);
                $hasStatusFilter = true;
                unset($filters[$filterKey]);
                \Log::info('ContractController: Found status filter', [
                    'key' => $filterKey,
                    'value' => $statusFilterValue
                ]);
                break;
            }
        }
        
        // Check for updated_at filter (supports all possible formats)
        foreach (['updated_at', 'updated__at', 'contracts.updated_at', 'contracts__updated_at'] as $filterKey) {
            if (isset($filters[$filterKey]) && !empty(trim($filters[$filterKey]))) {
                $updatedAtFilterValue = trim($filters[$filterKey]);
                $hasUpdatedAtFilter = true;
                unset($filters[$filterKey]);
                \Log::info('ContractController: Found updated_at filter', [
                    'key' => $filterKey,
                    'value' => $updatedAtFilterValue
                ]);
                break;
            }
        }
        
        // Handle status filter (map to contract_status column)
        if ($hasStatusFilter && !empty($statusFilterValue)) {
            $term = trim($statusFilterValue);
            // Try exact match first, then fallback to LIKE
            $query->where(function($q) use ($term) {
                $q->where('contract_status', $term)
                  ->orWhere('contract_status', 'LIKE', "%{$term}%");
            });
        }
        
        // Handle updated_at filter
        if ($hasUpdatedAtFilter && !empty($updatedAtFilterValue)) {
            $term = trim($updatedAtFilterValue);
            // Filter by updated_at column directly on contracts table
            // Search in multiple date formats to handle "nov" (November) search
            $query->where(function($q) use ($term) {
                $q->whereRaw("DATE_FORMAT(contracts.updated_at, '%d %M %Y') LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("DATE_FORMAT(contracts.updated_at, '%M %Y') LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("DATE_FORMAT(contracts.updated_at, '%M') LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("DATE_FORMAT(contracts.updated_at, '%Y-%m-%d') LIKE ?", ["%{$term}%"]);
            });
        }
        
        // Temporarily replace filter input to exclude manually handled filters
        $originalFilters = $request->input('filter', []);
        if ($hasStatusFilter || $hasUpdatedAtFilter) {
            $request->merge(['filter' => $filters]);
            \Log::info('ContractController: Removed manual filters from request', [
                'remaining_filters' => $filters,
                'had_status' => $hasStatusFilter,
                'had_updated_at' => $hasUpdatedAtFilter
            ]);
        }
        
        // Apply other column filters (excluding manually handled ones)
        $columnMap = [
            // 'status' and 'updated_at' are handled manually above
        ];
        $this->applyColumnFilters($query, null, $columnMap);
        
        // Restore original filters after processing
        if ($hasStatusFilter || $hasUpdatedAtFilter) {
            $request->merge(['filter' => $originalFilters]);
        }

        // Filtering (legacy filters - only apply if not using column filters)
        if ($request->filled('status') && !$hasStatusFilter) {
            $query->where('contract_status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('quotation_id')) {
            $query->where('quotation_id', $request->quotation_id);
        }

        if ($request->filled('marketing_id')) {
            $query->where('marketing_id', $request->marketing_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('contract_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('contract_date', '<=', $request->date_to);
        }

        if ($request->filled('company')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('company_name', 'like', '%' . $request->company . '%');
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', '%' . $search . '%')
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', '%' . $search . '%');
                  })
                  ->orWhereHas('quotation.prospect', function ($prospectQuery) use ($search) {
                      $prospectQuery->where('company_name', 'like', '%' . $search . '%')
                                   ->orWhere('pic_name', 'like', '%' . $search . '%');
                  });
            });
        }

        // Auto-filters are applied via AutoFilterable trait - no manual code needed!

        // --- Custom Sorting for Fallback Columns ---
        // Handle sorting for Company Name (Customer Name or Prospect Company Name)
        if ($request->input('sort') === 'customer.name') {
            $direction = $request->input('direction', 'asc');
            $query->leftJoin('customers', 'contracts.customer_id', '=', 'customers.id')
                  ->leftJoin('quotations', 'contracts.quotation_id', '=', 'quotations.id')
                  ->leftJoin('prospects', 'quotations.prospect_id', '=', 'prospects.id')
                  ->select('contracts.*')
                  ->orderByRaw("COALESCE(customers.name, prospects.company_name) $direction");
            
            // Clear sort param so AutoFilterable trait doesn't override or double-sort
            $request->merge(['sort' => null]);
        }
        
        // Handle sorting for Email (Customer Email or Prospect Email)
        if ($request->input('sort') === 'customer.email') {
            $direction = $request->input('direction', 'asc');
            // Check if joins already exist to avoid duplicate join errors (basic check)
            // Note: In Laravel query builder, re-joining same table name often aliases or errors. 
            // For safety here, we'll repeat the joins as they are idempotent if identical in some versions, 
            // but to be safe we should check. However, for now assuming linear flow.
            // A safer way is to use 'leftJoin' which usually handles it or just check binding.
            
            // To be safe and clean, we reuse the same logic
            if (!$request->has('company_sort_applied')) { // logic flag
                 $query->leftJoin('customers', 'contracts.customer_id', '=', 'customers.id')
                  ->leftJoin('quotations', 'contracts.quotation_id', '=', 'quotations.id')
                  ->leftJoin('prospects', 'quotations.prospect_id', '=', 'prospects.id');
            }
            
            $query->select('contracts.*')
                  ->orderByRaw("COALESCE(customers.email, prospects.contact_email) $direction");
            
             $request->merge(['sort' => null]);
        }

        // Apply default sort ONLY if no manual sort is present (and we haven't handled it above)
        // Note: we cleared 'sort' above if we handled it, so this check works for both cases.
        if (!$request->has('sort') && !$request->has('direction')) {
            $query->orderBy('contract_date', 'desc')
                  ->orderBy('created_at', 'desc');
        }
        
        $contracts = $query->paginate(15);

        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $contracts
            ]);
        }

        $marketingStaff = User::where('department_name', 'Marketing')->get();

        $customers = Customer::all();
        $quotations = Quotation::where('status', 'Approved')->get();
        $statuses = MasterOption::where('name', 'Contract Status')->first()?->optionDetails ?? collect();
        $types = MasterOption::where('name', 'Contract Type')->first()?->optionDetails ?? collect();

        $pagination = $contracts->toArray();
        return view('marketing.contracts.index', compact('contracts', 'marketingStaff', 'customers', 'quotations', 'statuses', 'types', 'pagination'));
    }

    public function create()
    {
        $contractNumber = 'CT-' . date('Ymd') . '-' . str_pad(Contract::count() + 1, 4, '0', STR_PAD_LEFT);
        $customers = Customer::all();
        $quotations = Quotation::all();
        $marketingStaff = User::all();
        $statuses = MasterOption::where('name', 'Contract Status')->first()?->optionDetails ?? collect();
        $types = MasterOption::where('name', 'Contract Type')->first()?->optionDetails ?? collect();
        $paymentTerms = MasterOption::where('name', 'Payment Terms')->first()?->optionDetails ?? collect();
        $provinces = collect(); // Empty collection for now

        return view('marketing.contracts.create', compact('contractNumber', 'customers', 'quotations', 'marketingStaff', 'statuses', 'types', 'paymentTerms', 'provinces'));
    }

    /**
     * Get latest Survey Quotation (SQ) data for auto-population
     */
    public function getLatestSQ(Request $request)
    {
        try {
            $quotationId = $request->quotation_id;
            
            if (!$quotationId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Quotation ID is required'
                ], 400);
            }

            $quotation = Quotation::with([
                'prospect',
                'survey',
                'marketing',
                'quotationRooms.rental',
                'quotationRentals.rental'
            ])->find($quotationId);

            if (!$quotation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Quotation not found'
                ], 404);
            }

            // Auto-populate contract data from quotation
            $contractData = [
                'customer_name' => $quotation->prospect->company_name ?? $quotation->company_name,
                'customer_address' => $quotation->prospect->company_address ?? '',
                'pic_name' => $quotation->pic_name,
                'pic_phone' => $quotation->prospect->contact_phone ?? '',
                'pic_email' => $quotation->prospect->contact_email ?? '',
                'contract_value' => $quotation->grand_total,
                'payment_terms' => $quotation->terms_of_payment,
                'contract_terms' => $quotation->terms_conditions,
                'rental_period' => $quotation->rental_period,
                'marketing_id' => $quotation->marketing_id,
                'quotation_data' => [
                    'quotation_number' => $quotation->quotation_number,
                    'quotation_date' => $quotation->quotation_date,
                    'valid_until' => $quotation->valid_until,
                    'total_amount' => $quotation->total_amount,
                    'discount_amount' => $quotation->discount_amount,
                    'tax_amount' => $quotation->tax_amount,
                    'grand_total' => $quotation->grand_total,
                    'internal_notes' => $quotation->internal_notes,
                    'additional_notes' => $quotation->additional_notes
                ],
                'rooms_data' => $quotation->quotationRooms->map(function($room) {
                    return [
                        'room_name' => $room->room_name,
                        'room_type' => $room->room_type,
                        'room_area' => $room->room_area,
                        'quantity_needed' => $room->quantity_needed,
                        'rental_name' => $room->rental->rental_name ?? '',
                        'rental_price' => $room->rental->rental_price ?? 0
                    ];
                }),
                'rentals_data' => $quotation->quotationRentals->map(function($rental) {
                    return [
                        'rental_name' => $rental->rental->rental_name ?? '',
                        'rental_price' => $rental->rental->rental_price ?? 0,
                        'quantity' => $rental->quantity,
                        'total_price' => $rental->total_price
                    ];
                })
            ];

            return response()->json([
                'status' => 'success',
                'data' => $contractData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get quotation data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_number' => 'required|string|max:50|unique:contracts',
            'quotation_number' => 'nullable|string|max:50',
            'company_name' => 'required|string|max:255',
            'contract_type' => 'required|in:rental,service,maintenance',
            'contract_date' => 'required|date',
            'contract_status' => 'required|in:draft,active,completed,terminated,cancelled',
            'locked_status' => 'boolean',
            'terms_of_payment' => 'nullable|string|max:255',
            'contract_terms' => 'nullable|string',
            'marketing_id' => 'required|exists:users,id',
            'rental_period' => 'nullable|string|max:100',
            'return_contract' => 'boolean',
            'approved_by' => 'nullable|exists:users,id',
            'staff_signature' => 'nullable|string|max:255',
            'customer_signature' => 'nullable|string|max:255',
            'expected_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'tax_code' => 'nullable|string|max:50',
            'install_date' => 'nullable|date',
            'first_service_date' => 'nullable|date',
            'pic_service_email' => 'nullable|email|max:255',
            'internal_notes' => 'nullable|string',
            'additional_notes' => 'nullable|string',
            'contract_back' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Auto-inherit marketing staff from quotation for data consistency
        $quotation = null;
        $marketingId = $request->marketing_id;
        if ($request->quotation_id) {
            $quotation = Quotation::with('prospect')->find($request->quotation_id);
            if ($quotation && $quotation->prospect) {
                $marketingId = $quotation->prospect->assigned_to;
            }
        }
        
        // Auto-detect is_contract (Target Marketing)
        $isContractTarget = false;
        $rentalPeriod = $request->rental_period;
        if ($rentalPeriod) {
            $period = strtolower(trim($rentalPeriod));
            if (preg_match('/(\d+)\s*(bulan|month|months)/i', $period, $matches)) {
                $isContractTarget = (int)$matches[1] >= 12;
            } elseif (preg_match('/(\d+)\s*(tahun|year|years)/i', $period, $matches)) {
                $isContractTarget = (int)$matches[1] >= 1;
            } elseif (preg_match('/^(\d+)$/', $period, $matches)) {
                // Number only - assume months
                $isContractTarget = (int)$matches[1] >= 12;
            } elseif ($request->start_date && $request->end_date) {
                $start = Carbon::parse($request->start_date);
                $end = Carbon::parse($request->end_date);
                $isContractTarget = $start->diffInDays($end) >= 360;
            }
        }

        $contract = Contract::create([
            'contract_number' => $request->contract_number,
            'quotation_number' => $request->quotation_number,
            'company_name' => $request->company_name,
            'contract_type' => $request->contract_type,
            'contract_date' => $request->contract_date,
            'contract_status' => $request->contract_status,
            'locked_status' => $request->locked_status ?? false,
            'terms_of_payment' => $request->terms_of_payment,
            'contract_terms' => $request->contract_terms,
            'marketing_id' => $marketingId, // Auto-inherit from quotation/prospect
            'return_contract' => $request->return_contract ?? false,
            'approved_by' => $request->approved_by,
            'staff_signature' => $request->staff_signature,
            'customer_signature' => $request->customer_signature,
            'expected_date' => $request->expected_date,
            'start_date' => $request->start_date,
            'tax_code' => $request->tax_code,
            'install_date' => $request->install_date,
            'first_service_date' => $request->first_service_date,
            'pic_service_email' => $request->pic_service_email,
            'notes' => $request->internal_notes,
            'additional_notes' => $request->additional_notes,
            'contract_back' => $request->contract_back ?? false,
            'is_contract' => $isContractTarget,
            'created_by' => Auth::id(),
        ]);


        if ($quotation) {
            
            $freeTrials = $quotation->freeTrials()
                ->whereIn('status', ['active', 'completed'])
                ->get();
            
            if ($freeTrials->count() > 0) {
                // Cari JobSchedule type 'remove' atau 'removal' yang belum cancelled
                foreach ($freeTrials as $trial) {
                    $removeJobs = \App\Models\JobSchedule::whereHas('jobAdvice', function($q) use ($trial) {
                        $q->where('reference_number', 'like', '%' . $trial->trial_number . '%')
                          ->whereIn('type', ['remove', 'removal']);
                    })
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->get();
                    
                    foreach ($removeJobs as $job) {
                        $job->update([
                            'status' => 'cancelled',
                            'notes' => ($job->notes ? $job->notes . "\n\n" : '') . 
                                      "[AUTO-CANCELLED by SYSTEM] " .
                                      "Trial lanjut ke Contract {$contract->contract_number}. " .
                                      "Job remove dibatalkan otomatis.",
                            'updated_by' => Auth::id()
                        ]);
                        
                        \Log::info("Auto-cancelled job remove: {$job->job_number} due to contract creation: {$contract->contract_number}");
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Contract created successfully',
            'data' => $contract->load(['customer', 'quotation', 'creator'])
        ]);
    }

    public function show(Contract $contract)
    {
        if (
            !$contract->contractRooms()->exists() ||
            !$contract->contractRentals()->exists() ||
            !$contract->billingGroups()->exists()
        ) {
            if (ContractSwitching::syncNewContractStructureIfMissing($contract, Auth::id())) {
                $contract->refresh();
            }
        }

        $contract->load([
            'marketing', 
            'customer.customerContacts', // Load customer contacts for Additional Info
            'quotation.prospect', 
            'quotation.survey.surveyor', 
            'quotation.survey.building.city', // Load survey building with city
            'quotation.marketing', 
            'quotation.approver',
            'quotation.quotationDetails',
            'quotation.quotationSurveys.survey', // Load multiple surveys from quotation
            'quotation.existingContract', // Old contract for renewal tracking
            'creator', 
            'updater',
            'contractRooms.contract.quotation.survey.building.city', // Load building with city through survey
            'contractRooms.room',
            'contractRentals.masterRental', 
            'billingGroups.buildings.province',
            'billingGroups.buildings.city',
            'billingGroups.buildings.district',
            'billingGroups.buildings.subdistrict',
            'billingGroups.creator',
            'billingGroups.updater', 
            'contractFiles', 
            'invoices',
            // Multiple Survey Enhancement relationships
            'contractSurveys.survey.building.city',
            'contractSurveys.survey.surveyor',
            'contractSurveys.addedBy',
            // Additional Info relationships
            'customerSigning1',
            'customerSigning2',
            'customerSigning3',
            'customerSigning4',
            'internalSigning',
            // Renewal tracking
            'renewals.newContract',
            'renewedByContract'
        ]);

        // For job schedule creation, we need building and rental data
        $building = null;
        $rentals = [];

        if ($contract->contractRooms && $contract->contractRooms->count() > 0) {
            // Get the first building from contract rooms
            $firstRoom = $contract->contractRooms->first();
            if ($firstRoom && $firstRoom->building) {
                $building = $firstRoom->building;
            }

        }

        if ($contract->contractRentals && $contract->contractRentals->count() > 0) {
            $rentals = $contract->contractRentals->map(function($rental) {
                if ($rental->masterRental) {
                    return [
                        'id' => $rental->masterRental->id,
                        'rental_name' => $rental->masterRental->rental_name,
                        'rental_code' => $rental->masterRental->rental_code
                    ];
                }
                return null;
            })->filter()->unique('id')->values()->toArray();
        }

        // Return JSON for AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $contract,
                'building' => $building,
                'rentals' => $rentals,
                // Contract Signing Enhancement data
                'digital_signature_status' => $contract->digital_signature_status,
                'npwp_status' => $contract->npwp_status,
                'can_be_signed' => $contract->canBeSigned(),
                'can_generate_schedule' => $contract->canGenerateSchedule(),
                'is_ready_for_posting' => $contract->isReadyForPosting(),
                'has_digital_signature' => $contract->hasDigitalSignature(),
                'is_npwp_verified' => $contract->isNPWPVerified(),
                'schedule_generated' => $contract->schedule_generated
            ]);
        }

        // Fetch file types from MasterOption
        $fileTypes = \App\Models\MasterOption::where('name', 'Contract File Type')
            ->with(['optionDetails' => function($query) {
                $query->where('is_active', true);
            }])
            ->first();

        $fileTypes = $fileTypes ? $fileTypes->optionDetails : collect();

        // Return view for regular requests
        return view('marketing.contracts.show', compact('contract', 'fileTypes'));
    }

    public function edit(Contract $contract)
    {
        $contract->load(['marketing', 'customer', 'quotation.survey.surveyor', 'creator', 'contractRooms', 'contractRentals.masterRental', 'billingGroups', 'contractFiles', 'invoices']);
        return response()->json($contract);
    }
    
    /**
     * Update Additional Info (from contract wizard step 3)
     */
    public function updateAdditionalInfo(Request $request, Contract $contract)
    {
        // Check if contract is active - if active, cannot edit
        if ($contract->contract_status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit additional info for active contracts'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'ppn_code' => 'nullable|string|in:01,02,03,04,05,06,07,08,09',
            'customer_signing_1_id' => 'required|exists:customer_contacts,id',
            'customer_signing_2_id' => 'nullable|exists:customer_contacts,id',
            'customer_signing_3_id' => 'nullable|exists:customer_contacts,id',
            'customer_signing_4_id' => 'nullable|exists:customer_contacts,id',
            'internal_signing_id' => 'required|exists:users,id',
            'install_date' => 'required|date',
            'first_service_date' => 'required|date',
            'pic_service_email' => 'nullable|email|max:255',
            'external_remark' => 'nullable|string',
            'internal_remark' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Check if is_installed is being set to true for the first time
            $wasInstalled = $contract->is_installed;
            $installDateChanged = $contract->install_date != $request->install_date;
            
            $contract->update([
                'ppn_code' => $request->ppn_code,
                'customer_signing_1_id' => $request->customer_signing_1_id,
                'customer_signing_2_id' => $request->customer_signing_2_id,
                'customer_signing_3_id' => $request->customer_signing_3_id,
                'customer_signing_4_id' => $request->customer_signing_4_id,
                'internal_signing_id' => $request->internal_signing_id,
                'install_date' => $request->install_date,
                'first_service_date' => $request->first_service_date,
                'pic_service_email' => $request->pic_service_email,
                'external_remark' => $request->external_remark,
                'internal_remark' => $request->internal_remark,
                'updated_by' => Auth::id()
            ]);

            // If install_date is set and contract is not yet marked as installed, mark as installed and trigger commission calculation
            if ($request->install_date && (!$wasInstalled || $installDateChanged)) {
                $contract->refresh();
                $contract->update([
                    'is_installed' => true,
                    'installed_date' => $request->install_date
                ]);

                // Trigger commission calculation for installed contract
                try {
                    $commissionService = new \App\Services\Finance\CommissionCalculationService();
                    $result = $commissionService->calculateCommissionForContract($contract);
                    
                    if ($result['success']) {
                        Log::info("Commission calculated for contract {$contract->contract_number} after installation: {$result['amount']}");
                    } else {
                        Log::warning("Commission calculation skipped for contract {$contract->contract_number}: {$result['message']}");
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to calculate commission for contract {$contract->contract_number}: " . $e->getMessage());
                    // Don't fail the update if commission calculation fails
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Additional Info updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating additional info: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update additional info: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Contract $contract)
    {
        $validator = Validator::make($request->all(), [
            'contract_number' => 'required|string|max:50|unique:contracts,contract_number,' . $contract->id,
            'quotation_number' => 'nullable|string|max:50',
            'company_name' => 'required|string|max:255',
            'contract_type' => 'required|in:rental,service,maintenance',
            'contract_date' => 'required|date',
            'contract_status' => 'required|in:draft,active,completed,terminated,cancelled',
            'locked_status' => 'boolean',
            'terms_of_payment' => 'nullable|string|max:255',
            'contract_terms' => 'nullable|string',
            'marketing_id' => 'required|exists:users,id',
            'rental_period' => 'nullable|string|max:100',
            'return_contract' => 'boolean',
            'approved_by' => 'nullable|exists:users,id',
            'staff_signature' => 'nullable|string|max:255',
            'customer_signature' => 'nullable|string|max:255',
            'expected_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            // 'ppn_code' => 'nullable|string|max:50', // Moved to Billing Group
            'tax_code' => 'nullable|string|max:50',
            'install_date' => 'nullable|date',
            'first_service_date' => 'nullable|date',
            'pic_service_email' => 'nullable|email|max:255',
            'internal_notes' => 'nullable|string',
            'additional_notes' => 'nullable|string',
            'contract_back' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if net_value is being updated
        $netValueChanged = $contract->net_value != ($request->net_value ?? $contract->net_value);
        $oldNetValue = $contract->net_value;
        
        $contract->update([
            'contract_number' => $request->contract_number,
            'quotation_number' => $request->quotation_number,
            'company_name' => $request->company_name,
            'contract_type' => $request->contract_type,
            'contract_date' => $request->contract_date,
            'contract_status' => $request->contract_status,
            'locked_status' => $request->locked_status ?? false,
            'terms_of_payment' => $request->terms_of_payment,
            'contract_terms' => $request->contract_terms,
            'marketing_id' => $request->marketing_id,
            'rental_period' => $request->rental_period,
            'return_contract' => $request->return_contract ?? false,
            'approved_by' => $request->approved_by,
            'staff_signature' => $request->staff_signature,
            'customer_signature' => $request->customer_signature,
            'start_date' => $request->start_date,
            // 'ppn_code' => $request->ppn_code, // Moved to Billing Group
            'tax_code' => $request->tax_code,
            'install_date' => $request->install_date,
            'first_service_date' => $request->first_service_date,
            'pic_service_email' => $request->pic_service_email,
            'notes' => $request->internal_notes,
            'additional_notes' => $request->additional_notes,
            'contract_back' => $request->contract_back ?? false,
            'net_value' => $request->net_value ?? $contract->net_value,
            'is_installed' => $request->is_installed ?? $contract->is_installed,
            'installed_date' => $request->installed_date ?? $contract->installed_date,
        ]);

        // If net_value changed and contract is installed, recalculate commission
        if ($netValueChanged && $contract->is_installed) {
            try {
                $commissionService = new \App\Services\Finance\CommissionCalculationService();
                $result = $commissionService->recalculateCommissionForContract($contract);
                
                if ($result['success']) {
                    Log::info("Commission recalculated for contract {$contract->contract_number} after net_value update: {$result['amount']}");
                } else {
                    Log::warning("Commission recalculation skipped for contract {$contract->contract_number}: {$result['message']}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to recalculate commission for contract {$contract->contract_number}: " . $e->getMessage());
                // Don't fail the update if commission calculation fails
            }
        }

        // If is_installed is set to true for the first time, trigger commission calculation
        if ($request->is_installed && !$contract->is_installed && $request->installed_date) {
            try {
                $commissionService = new \App\Services\Finance\CommissionCalculationService();
                $result = $commissionService->calculateCommissionForContract($contract);
                
                if ($result['success']) {
                    Log::info("Commission calculated for contract {$contract->contract_number} after installation: {$result['amount']}");
                } else {
                    Log::warning("Commission calculation skipped for contract {$contract->contract_number}: {$result['message']}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to calculate commission for contract {$contract->contract_number}: " . $e->getMessage());
                // Don't fail the update if commission calculation fails
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Contract updated successfully',
            'data' => $contract->load(['customer', 'quotation', 'createdBy'])
        ]);
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Contract deleted successfully'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:contracts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = Contract::whereIn('id', $request->ids)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully hidden {$count} record(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error hiding records: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dashboard()
    {
        $totalContracts = Contract::count();
        $activeContracts = Contract::count(); // Remove status filter for now
        $expiredContracts = Contract::where('end_date', '<', now())->count();
        $pendingContracts = Contract::count(); // Remove status filter for now

        $recentContracts = Contract::with(['customer', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $monthlyContracts = Contract::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $contractValues = Contract::selectRaw('contract_type, SUM(total_value) as total_value')
            ->groupBy('contract_type')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_contracts' => $totalContracts,
                'active_contracts' => $activeContracts,
                'expired_contracts' => $expiredContracts,
                'pending_contracts' => $pendingContracts,
                'recent_contracts' => $recentContracts,
                'monthly_contracts' => $monthlyContracts,
                'contract_values' => $contractValues
            ]
        ]);
    }

    /**
     * Download contract as PDF
     */
    public function download(Contract $contract)
    {
        // Check if user has permission to download contracts (permission-based)
        $user = Auth::user();
        $hasDownloadPermission = $user->hasPermission('contracts.download') 
                              || $user->hasPermission('marketing.contracts.download')
                              || $user->hasPermission('marketing.contracts.view')
                              || $user->canApprove('contracts');
        
        if (!$hasDownloadPermission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk download contract.'
            ], 403);
        }

        try {
            $contract->load(['customer', 'quotation.prospect', 'quotation.survey', 'marketing', 'creator']);
            
            // Generate PDF using DomPDF
            $pdf = Pdf::loadView('marketing.contracts.pdf', compact('contract'));
            
            // Set PDF options
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial'
            ]);
            
            // Generate filename - sanitize to remove invalid characters
            $contractNumber = $contract->contract_number ?? 'Unknown';
            // Remove invalid filename characters: / \ : * ? " < > | and any whitespace
            // Also replace any path separators
            $sanitizedContractNumber = str_replace(['/', '\\'], '_', $contractNumber);
            $sanitizedContractNumber = preg_replace('/[:\*\?"<>\|\s]+/', '_', $sanitizedContractNumber);
            // Remove multiple consecutive underscores and trim underscores from start/end
            $sanitizedContractNumber = preg_replace('/_+/', '_', $sanitizedContractNumber);
            $sanitizedContractNumber = trim($sanitizedContractNumber, '_');
            // If empty after sanitization, use fallback
            if (empty($sanitizedContractNumber)) {
                $sanitizedContractNumber = 'Unknown';
            }
            $filename = 'Contract_' . $sanitizedContractNumber . '_' . now()->format('Y-m-d') . '.pdf';
            
            // Final sanitization: ensure no path separators in filename
            $filename = str_replace(['/', '\\'], '_', $filename);
            $filename = preg_replace('/[:\*\?"<>\|\s]+/', '_', $filename);
            $filename = preg_replace('/_+/', '_', $filename);
            $filename = trim($filename, '_');
            
            // Use response()->streamDownload() to avoid filename issues
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate contract download: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print contract as PDF (inline view)
     */
    public function print(Contract $contract)
    {
        // Check if user has permission to print contracts (permission-based)
        $user = Auth::user();
        $hasPrintPermission = $user->hasPermission('contracts.print') 
                           || $user->hasPermission('marketing.contracts.print')
                           || $user->hasPermission('marketing.contracts.view')
                           || $user->canApprove('contracts');
        
        if (!$hasPrintPermission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk print contract.'
            ], 403);
        }

        try {
            $contract->load(['customer', 'quotation.prospect', 'quotation.survey', 'marketing', 'creator']);
            
            // Generate PDF using DomPDF
            $pdf = Pdf::loadView('marketing.contracts.pdf', compact('contract'));
            
            // Set PDF options for printing
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial'
            ]);
            
            // Generate filename - sanitize to remove invalid characters
            $contractNumber = $contract->contract_number ?? 'Unknown';
            // Remove invalid filename characters: / \ : * ? " < > | and any whitespace
            // Also replace any path separators
            $sanitizedContractNumber = str_replace(['/', '\\'], '_', $contractNumber);
            $sanitizedContractNumber = preg_replace('/[:\*\?"<>\|\s]+/', '_', $sanitizedContractNumber);
            // Remove multiple consecutive underscores and trim underscores from start/end
            $sanitizedContractNumber = preg_replace('/_+/', '_', $sanitizedContractNumber);
            $sanitizedContractNumber = trim($sanitizedContractNumber, '_');
            // If empty after sanitization, use fallback
            if (empty($sanitizedContractNumber)) {
                $sanitizedContractNumber = 'Unknown';
            }
            $filename = 'Contract_' . $sanitizedContractNumber . '_' . now()->format('Y-m-d') . '.pdf';
            
            // Final sanitization: ensure no path separators in filename
            $filename = str_replace(['/', '\\'], '_', $filename);
            $filename = preg_replace('/[:\*\?"<>\|\s]+/', '_', $filename);
            $filename = preg_replace('/_+/', '_', $filename);
            $filename = trim($filename, '_');
            
            // Return PDF inline for printing (using stream() instead of streamDownload())
            // This will display PDF in browser instead of downloading
            return response()->make($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Cache-Control' => 'public, max-age=0'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate contract print: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateContractNumber()
    {
        $prefix = 'CT';
        $year = date('Y');
        $month = date('m');
        
        $lastContract = Contract::where('contract_number', 'like', $prefix . $year . $month . '%')
            ->orderBy('contract_number', 'desc')
            ->first();

        if ($lastContract) {
            $lastNumber = intval(substr($lastContract->contract_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function lock(Contract $contract)
    {
        $contract->update(['locked' => true]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Contract locked successfully'
        ]);
    }

    public function unlock(Contract $contract)
    {
        $contract->update(['locked' => false]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Contract unlocked successfully'
        ]);
    }

    public function unapprove(Contract $contract)
    {
        $contract->update(['status' => 'pending']);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Contract unapproved successfully'
        ]);
    }


    /**
     * Get contracts for dropdown (no authentication required)
     */
    public function getForDropdown(Request $request)
    {
        try {
            // Build query with optional filters
            $query = Contract::with('customer')
                ->select('id', 'contract_number', 'customer_id', 'marketing_id', 'created_by', 'status', 'contract_status');
            
            // Apply filters if provided
            if ($request->filled('marketing_id')) {
                $marketingId = $request->marketing_id;
                $query->where(function($q) use ($marketingId) {
                    $q->where('marketing_id', $marketingId)
                      ->orWhere('created_by', $marketingId);
                });
            }
            
            if ($request->filled('status')) {
                $status = $request->status;
                $query->where(function($q) use ($status) {
                    $q->where('status', $status)
                      ->orWhere('contract_status', $status);
                });
            }
            
            $contracts = $query->get()
                ->filter(function ($contract) use ($request) {
                    if (!$request->boolean('for_job_advice')) {
                        return true;
                    }

                    return !$contract->hasRenewalSuccessor();
                })
                ->values()
                ->map(function ($contract) {
                    return [
                        'id' => $contract->id,
                        'contract_number' => $contract->contract_number,
                        'customer_id' => $contract->customer_id,
                        'customer_name' => $contract->customer ? $contract->customer->name : 'N/A',
                        'marketing_id' => $contract->marketing_id,
                        'created_by' => $contract->created_by,
                        'status' => $contract->status,
                        'contract_status' => $contract->contract_status,
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $contracts
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
     * Get contract details for Job Advice (including contractRooms and contractRentals)
     * Only returns available rooms that haven't been used in previous Job Advices
     */
    public function getForJobAdvice($id)
    {
        try {
            $contract = Contract::with([
                'customer',
                'contractRooms.room.building',
                'contractRentals',
                'quotation.quotationDetails'
            ])->findOrFail($id);

            if ($contract->hasRenewalSuccessor()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Contract {$contract->contract_number} sudah direnewal/current contract, sehingga tidak bisa dipakai untuk Job Advice baru."
                ], 422);
            }

            // Get current job_advice_id if editing (passed via query param)
            $currentJobAdviceId = request('job_advice_id');
            
            // Fix Issue 2: Get contract_room_ids that are already used in any Job Advice (not cancelled)
            // This prevents the same room from being added to multiple JAs
            $usedContractRoomIds = \App\Models\JobAdviceRoom::whereHas('jobAdvice', function($q) use ($id, $currentJobAdviceId) {
                $q->where('contract_id', $id)
                  ->whereNotIn('status', ['cancelled']);
                
                // If editing existing JA, exclude its own rooms from the "used" list
                if ($currentJobAdviceId) {
                    $q->where('job_advice_id', '!=', $currentJobAdviceId);
                }
            })
            ->whereNotNull('contract_room_id')
            ->pluck('contract_room_id')
            ->toArray(); 

            // Format contract rooms for Job Advice selection modal
            // Filter out rooms that don't exist (broken references) AND rooms that are already used
            // MOM: For Extra, Change, Complain, Remove types, we load ALL rooms (even if used)
            $type = request('type');
            $typeLower = strtolower((string) $type);
            $shouldIncludeUsedRooms = $type && in_array(strtolower($type), ['extra', 'change', 'complain', 'remove', 'change_unit', 'change unit', 'removal', 'service']);
            $isInstallJobAdvice = $typeLower === 'install';
            $isServiceJobAdvice = $typeLower === 'service';
            
            $contractRooms = $contract->contractRooms
                ->filter(function ($contractRoom) use ($contract, $usedContractRoomIds, $shouldIncludeUsedRooms, $isInstallJobAdvice, $isServiceJobAdvice, $id) {
                    // Only include rooms that exist
                    // If shouldIncludeUsedRooms is true, we ignore the used filtering
                    if ($contractRoom->room === null) {
                        return false;
                    }

                    if (!$shouldIncludeUsedRooms && in_array($contractRoom->id, $usedContractRoomIds)) {
                        return false;
                    }

                    // Install JA must only show rooms that still need installation.
                    // Rooms with an active/on-wall unit are service/remove candidates, not install candidates.
                    if ($isInstallJobAdvice && $this->contractRoomHasActiveUnitOnWall($contract, $contractRoom)) {
                        return false;
                    }

                    if ($isServiceJobAdvice) {
                        $hasActiveUnit = $this->contractRoomHasActiveUnitOnWall($contract, $contractRoom);

                        $hasCompletedInstall = \App\Models\JobSchedule::whereIn('type', ['install', 'installation'])
                            ->whereIn('status', ['completed', 'done_job'])
                            ->where(function ($query) use ($id, $contractRoom) {
                                $query->where(function ($q) use ($id, $contractRoom) {
                                    $q->whereHas('jobAdvice', function ($jaQuery) use ($id) {
                                            $jaQuery->where('contract_id', $id);
                                        })
                                        ->whereHas('jobAdvice.rooms', function ($roomQuery) use ($contractRoom) {
                                            $roomQuery->where('contract_room_id', $contractRoom->id);
                                        });
                                })
                                ->orWhere('room_id', $contractRoom->room_id);
                            })
                            ->exists();

                        if (!$hasActiveUnit && !$hasCompletedInstall) {
                            return false;
                        }
                    }

                    return true;
                })
                ->map(function ($contractRoom, $index) use ($contract, $usedContractRoomIds) {
                    // Fix Issue 1: Get ALL rentals matching this room
                    // We need the ID for unique tracking of units
                    $rentalsForRoom = $contract->contractRentals->where('room_id', $contractRoom->room_id);
                    
                    // Fallback to room_name bridge if room_id is empty (legacy data)
                    if ($rentalsForRoom->isEmpty()) {
                        $roomName = $contractRoom->room?->room_name;
                        if ($roomName) {
                            $rentalsForRoom = $contract->contractRentals->where('rental_alias', $roomName);
                        }
                    }
                    
                    // Format rentals for frontend selection
                    $rentalsArray = $rentalsForRoom->map(function($rental) {
                        return [
                            'id' => $rental->id, // This is contract_rental_id
                            'master_rental_id' => $rental->master_rental_id,
                            'rental_name' => $rental->rental_alias ?? ($rental->masterRental?->rental_name ?? 'Unknown Rental'),
                        ];
                    })->values()->toArray();
                    
                    // Check if this room is used in another JA
                    $isUsedInOtherJa = in_array($contractRoom->id, $usedContractRoomIds);
                    
                    // Get which JA uses this room (for display)
                    $usedByJa = null;
                    if ($isUsedInOtherJa) {
                        $jaRoom = \App\Models\JobAdviceRoom::where('contract_room_id', $contractRoom->id)
                            ->whereHas('jobAdvice', function($q) {
                                $q->whereNotIn('status', ['cancelled']);
                            })
                            ->with('jobAdvice:id,job_advice_number,status')
                            ->first();
                        if ($jaRoom && $jaRoom->jobAdvice) {
                            $usedByJa = [
                                'job_advice_number' => $jaRoom->jobAdvice->job_advice_number,
                                'status' => $jaRoom->jobAdvice->status
                            ];
                        }
                    }
                    
                    return [
                        'id' => $contractRoom->id,
                        'room_id' => $contractRoom->room_id,
                        // First rental for backward compatibility
                        'contract_rental_id' => $rentalsForRoom->first()?->id, 
                        'rental_product_id' => $rentalsForRoom->first()?->master_rental_id,
                        // New: All rentals for this room
                        'rentals' => $rentalsArray,
                        'rental_count' => count($rentalsArray),
                        'room' => [
                            'id' => $contractRoom->room->id,
                            'room_name' => $contractRoom->room->room_name,
                            'room_type' => $contractRoom->room->room_type ?? 'N/A',
                            'room_floor' => $contractRoom->room->room_floor ?? 'N/A',
                            'building' => [
                                'id' => $contractRoom->room->building?->id,
                                'nama_gedung' => $contractRoom->room->building?->nama_gedung ?? $contractRoom->room->building?->name ?? 'N/A',
                                'name' => $contractRoom->room->building?->nama_gedung ?? $contractRoom->room->building?->name ?? 'N/A',
                            ]
                        ],
                        'has_active_unit' => $this->contractRoomHasActiveUnitOnWall($contract, $contractRoom),
                        'active_sn' => $this->getContractRoomActiveSerialNumber($contract, $contractRoom),
                        // Fix Issue 2: Mark if used in another JA
                        'is_used_in_other_ja' => $isUsedInOtherJa,
                        'used_by_ja' => $usedByJa
                    ];
                })
                ->values();

            // Check for broken rooms (rooms that don't exist, not rooms that are used)
            $totalContractRooms = $contract->contractRooms->count();
            $validRooms = $contract->contractRooms->filter(function ($contractRoom) {
                return $contractRoom->room !== null; // Only count rooms that exist
            })->count();
            $brokenRooms = $totalContractRooms - $validRooms;
            $availableRooms = $contractRooms->count();
            
            $message = null;
            if ($brokenRooms > 0) {
                $message = "⚠️ Perhatian: Contract ini memiliki {$brokenRooms} ruangan yang tidak valid (data room tidak ditemukan). Silakan hubungi admin untuk memperbaiki data.";
                \Log::warning("Contract {$contract->contract_number} has {$brokenRooms} broken room references", [
                    'contract_id' => $id,
                    'total_rooms' => $totalContractRooms,
                    'valid_rooms' => $validRooms
                ]);
            }

            return response()->json([
                'status' => 'success',
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'customer_name' => $contract->customer?->name,
                'notes_operation' => $contract->notes_operation, // Exposed for Job Advice notification
                'notes_finance' => $contract->notes_finance,     // Exposed for reference
                'notes_sales' => $contract->notes_sales,         // Exposed for reference
                'contract_rooms' => $contractRooms->values(),    // Primary key expected by JS
                'data' => [
                    'contract_rooms' => $contractRooms->values(), // Fallback nested key
                ],
                'rentals' => $contract->contractRentals, // Also return rentals for reference if needed
                'quotation_details' => $contract->quotation->quotationDetails ?? [],
                'total_rooms' => $availableRooms, // Available rooms (not used yet)
                'total_contract_rooms' => $totalContractRooms,
                'valid_rooms' => $validRooms, // Rooms that exist (not broken)
                'broken_rooms' => $brokenRooms,
                'used_rooms' => count($usedContractRoomIds), // Number of rooms already used
                'message' => $message
            ]);
        } catch (\Exception $e) {
            \Log::error('Contract getForJobAdvice error: ' . $e->getMessage(), [
                'contract_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'contract_rooms' => []
            ], 500);
        }
    }

    private function contractRoomHasActiveUnitOnWall(Contract $contract, $contractRoom): bool
    {
        return $this->getContractRoomActiveUnitOnWallQuery($contract, $contractRoom)->exists();
    }

    private function getContractRoomActiveSerialNumber(Contract $contract, $contractRoom): ?string
    {
        return $this->getContractRoomActiveUnitOnWallQuery($contract, $contractRoom)->value('serial_number');
    }

    private function getContractRoomActiveUnitOnWallQuery(Contract $contract, $contractRoom)
    {
        $room = $contractRoom->room;
        $roomName = trim((string) ($room->room_name ?? ''));
        $normalizedRoomName = mb_strtolower(preg_replace('/\s+/', ' ', $roomName));
        $buildingId = $room->building_id ?? null;

        return \App\Models\UnitOnWall::query()
            ->whereIn('status', ['active', 'installed', 'on_wall', 'on wall', 'onwall'])
            ->whereNotNull('serial_number_id')
            ->where('customer_id', $contract->customer_id)
            ->where(function ($query) use ($contractRoom, $buildingId, $normalizedRoomName) {
                if ($contractRoom->room_id) {
                    $query->where('room_id', $contractRoom->room_id);
                }

                if ($buildingId && $normalizedRoomName !== '') {
                    $query->orWhere(function ($roomQuery) use ($buildingId, $normalizedRoomName) {
                        $roomQuery->where('building_id', $buildingId)
                            ->whereRaw('LOWER(TRIM(room_name)) = ?', [$normalizedRoomName]);
                    });
                }
            });
    }

    // New methods for enhanced contract management

    /**
     * Get contract remarks by type
     */
    public function getRemarks(Request $request, Contract $contract)
    {
        $query = $contract->contractRemarks()
            ->where('is_active', true)
            ->with(['creator', 'updater'])
            ->orderBy('created_at', 'desc');
        
        // Optional filter by type
        if ($request->filled('type')) {
            $query->where('remark_type', $request->type);
        }
        
        $remarks = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $remarks
        ]);
    }

    /**
     * Store contract remark
     */
    public function storeRemark(Request $request, Contract $contract)
    {
        $validator = Validator::make($request->all(), [
            'remark_type' => 'required|in:contract,operation,finance,marketing',
            'remark_content' => 'required|string',
            'is_editable_after_approval' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $remark = $contract->contractRemarks()->create([
            'remark_type' => $request->remark_type,
            'remark_content' => $request->remark_content,
            'is_editable_after_approval' => $request->is_editable_after_approval ?? true,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Remark added successfully',
            'data' => $remark->load(['creator', 'updater'])
        ]);
    }

    /**
     * Update contract remark
     */
    public function updateRemark(Request $request, Contract $contract, ContractRemark $remark)
    {
        $validator = Validator::make($request->all(), [
            'remark_content' => 'required|string',
            'is_editable_after_approval' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $remark->update([
            'remark_content' => $request->remark_content,
            'is_editable_after_approval' => $request->is_editable_after_approval ?? $remark->is_editable_after_approval,
            'updated_by' => Auth::id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Remark updated successfully',
            'data' => $remark->load(['creator', 'updater'])
        ]);
    }

    /**
     * Delete contract remark
     */
    public function deleteRemark(Contract $contract, ContractRemark $remark)
    {
        $remark->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Remark deleted successfully'
        ]);
    }

    /**
     * Destroy contract remark (alias for deleteRemark for route compatibility)
     */
    public function destroyRemark(Contract $contract, ContractRemark $remark)
    {
        return $this->deleteRemark($contract, $remark);
    }

    /**
     * Get contract revisions
     */
    public function getRevisions(Contract $contract)
    {
        $revisions = $contract->contractRevisions()
            ->with(['requester', 'approver', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $revisions
        ]);
    }

    /**
     * Create contract revision
     */
    public function createRevision(Request $request, Contract $contract)
    {
        $validator = Validator::make($request->all(), [
            'revision_reason' => 'required|string',
            'changed_fields' => 'required|array',
            'old_values' => 'required|array',
            'new_values' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $revisionNumber = 'REV-' . $contract->contract_number . '-' . str_pad($contract->contractRevisions()->count() + 1, 3, '0', STR_PAD_LEFT);

        $revision = $contract->contractRevisions()->create([
            'revision_number' => $revisionNumber,
            'revision_reason' => $request->revision_reason,
            'changed_fields' => $request->changed_fields,
            'old_values' => $request->old_values,
            'new_values' => $request->new_values,
            'status' => 'pending_approval',
            'requested_by' => Auth::id(),
            'requested_at' => now(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Revision request created successfully',
            'data' => $revision->load(['requester', 'approver'])
        ]);
    }

    /**
     * Approve contract revision
     */
    public function approveRevision(Request $request, Contract $contract, ContractRevision $revision)
    {
        $validator = Validator::make($request->all(), [
            'approval_notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $revision->update([
            'status' => 'approved',
            'approval_notes' => $request->approval_notes,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'updated_by' => Auth::id()
        ]);

        // Apply changes to contract
        foreach ($revision->changed_fields as $field) {
            if (isset($revision->new_values[$field])) {
                $contract->update([$field => $revision->new_values[$field]]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Revision approved and applied successfully',
            'data' => $revision->load(['requester', 'approver'])
        ]);
    }

    /**
     * Reject contract revision
     */
    public function rejectRevision(Request $request, Contract $contract, ContractRevision $revision)
    {
        $validator = Validator::make($request->all(), [
            'approval_notes' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $revision->update([
            'status' => 'rejected',
            'approval_notes' => $request->approval_notes,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'updated_by' => Auth::id()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Revision rejected successfully',
            'data' => $revision->load(['requester', 'approver'])
        ]);
    }

    /**
     * Approve contract (change status from waiting_for_approval to active)
     * Only allowed for managers and admin roles
     */
    public function approveContract(Request $request, Contract $contract)
    {
        try {
            // Check permission using canApprove (checks permission checkbox first)
            $user = Auth::user();
            
            if (!$user->canApprove('contracts')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses untuk approve Contract. Pastikan role Anda memiliki permission "Approve" untuk Contracts.'
                ], 403);
            }
            
            // Check if contract is in waiting_for_approval status
            if ($contract->contract_status !== 'waiting_for_approval') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only contracts with "Waiting for Approval" status can be approved. Current status: ' . $contract->contract_status
                ], 400);
            }

            $renewalBlockReason = $this->getRenewalActivationBlockReason($contract);
            if ($renewalBlockReason) {
                return response()->json([
                    'status' => 'error',
                    'message' => $renewalBlockReason,
                ], 422);
            }
            
            // Update contract status to active and set approval info
            $contract->update([
                'contract_status' => 'active',
                'approved_by' => Auth::id(),
                'date_approved' => now(),
                'updated_by' => Auth::id(),
            ]);
            
            // Auto-create Virtual Account for customer if not exists
            $this->createVirtualAccountForContract($contract);
            $this->completeRenewalSourceContractLink($contract);
            
            // Log the action
            Log::info("Contract {$contract->contract_number} approved by user " . Auth::id());

            return response()->json([
                'status' => 'success',
                'message' => 'Contract approved successfully',
                'data' => [
                    'contract_id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'contract_status' => $contract->contract_status,
                    'approved_by' => $contract->approved_by,
                    'date_approved' => $contract->date_approved,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Error approving contract {$contract->id}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve contract: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unpost contract (revert status from waiting_for_approval or active to draft)
     */
    public function unpost(Request $request, Contract $contract)
    {
        try {
            // Check if contract is in valid status for unposting
            if (!in_array($contract->contract_status, ['waiting_for_approval', 'active'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only contracts with "Waiting for Approval" or "Active" status can be unposted. Current status: ' . $contract->contract_status
                ], 400);
            }

            // Check for existing Job Advices that are NOT cancelled
            // We use jobAdvices relationship and filter where status is NOT cancelled
            // Note: soft deleted records are already excluded by default
            $activeJobAdvicesCount = $contract->jobAdvices()
                ->where('status', '!=', 'cancelled')
                ->count();

            if ($activeJobAdvicesCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Gagal unpost: Contract ini memiliki {$activeJobAdvicesCount} Job Advice yang aktif. Harap hapus atau batalkan Job Advice terkait terlebih dahulu sebelum melakukan Unpost."
                ], 400);
            }

            // Update status back to draft
            $contract->update([
                'contract_status' => 'draft',
                'updated_by' => Auth::id(),
            ]);

            Log::info("Contract {$contract->contract_number} unposted back to draft by user " . Auth::id());

            return response()->json([
                'status' => 'success',
                'message' => 'Contract has been successfully unposted and reverted to draft.'
            ]);

        } catch (\Exception $e) {
            Log::error("Error unposting contract {$contract->id}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to unpost contract: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject contract (change status from waiting_for_approval to rejected)
     * Only allowed for managers and admin roles
     */
    public function reject(Request $request, Contract $contract)
    {
        try {
            // Check permission using canApprove (checks permission checkbox first)
            $user = Auth::user();
            
            if (!$user->canApprove('contracts')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses untuk reject Contract. Pastikan role Anda memiliki permission "Approve" untuk Contracts.'
                ], 403);
            }

            // Check if contract is in waiting_for_approval status
            if ($contract->contract_status !== 'waiting_for_approval') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only contracts with "Waiting for Approval" status can be rejected. Current status: ' . $contract->contract_status
                ], 400);
            }

            // Update status to rejected
            $contract->update([
                'contract_status' => 'rejected',
                'updated_by' => Auth::id(),
            ]);

            Log::info("Contract {$contract->contract_number} rejected by user " . Auth::id());

            return response()->json([
                'status' => 'success',
                'message' => 'Contract has been rejected.'
            ]);

        } catch (\Exception $e) {
            Log::error("Error rejecting contract {$contract->id}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject contract: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Post contract (activate after signing and verification)
     */
    public function postContract(Request $request, Contract $contract)
    {
        if (!$contract->isReadyForPosting()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contract is not ready for posting. Please ensure it is approved, signed, and NPWP verified.'
            ], 422);
        }

        $renewalBlockReason = $this->getRenewalActivationBlockReason($contract);
        if ($renewalBlockReason) {
            return response()->json([
                'status' => 'error',
                'message' => $renewalBlockReason,
            ], 422);
        }

        try {
            $contract->post(Auth::id());
            $contract->updateContractStatus('active');
            
            // Auto-create Virtual Account for customer if not exists
            $this->createVirtualAccountForContract($contract);
            $this->completeRenewalSourceContractLink($contract);
            
            // Auto-generate schedule if applicable
            if ($contract->canGenerateSchedule()) {
                $contract->generateScheduleAutomatically();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Contract posted successfully',
                'data' => [
                    'contract' => $contract,
                    'posted_at' => $contract->posted_at,
                    'schedule_generated' => $contract->schedule_generated
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to post contract: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getRenewalActivationBlockReason(Contract $contract): ?string
    {
        $quotation = $contract->quotation;

        if (!$quotation || $quotation->quotation_type !== 'renewal' || !$quotation->existing_contract_id) {
            return null;
        }

        $oldContract = Contract::find($quotation->existing_contract_id);
        if (!$oldContract) {
            return 'Contract lama untuk renewal tidak ditemukan.';
        }

        return $oldContract->getRenewalBlockReason($contract->id);
    }

    private function completeRenewalSourceContractLink(Contract $contract): void
    {
        $quotation = $contract->quotation;

        if (!$quotation || $quotation->quotation_type !== 'renewal' || !$quotation->existing_contract_id) {
            return;
        }

        $oldContract = Contract::find($quotation->existing_contract_id);
        if (!$oldContract) {
            return;
        }

        $renewal = ContractRenewal::where('contract_id', $oldContract->id)
            ->whereIn('status', [
                ContractRenewal::STATUS_DRAFT,
                ContractRenewal::STATUS_PENDING_CUSTOMER,
                ContractRenewal::STATUS_CUSTOMER_APPROVED,
                ContractRenewal::STATUS_PENDING_INTERNAL,
                ContractRenewal::STATUS_APPROVED,
            ])
            ->latest()
            ->first();

        if ($renewal) {
            $renewal->complete($contract->id);
        }

        $cancelledJobs = $oldContract->cancelRemainingJobSchedules(
            "Contract di-renewal ke {$contract->contract_number}"
        );

        Log::info("Renewal source contract linked to successor", [
            'old_contract_id' => $oldContract->id,
            'old_contract_number' => $oldContract->contract_number,
            'old_contract_status' => $oldContract->contract_status,
            'new_contract_id' => $contract->id,
            'new_contract_number' => $contract->contract_number,
            'cancelled_job_schedules' => $cancelledJobs,
        ]);
    }

    /**
     * Generate QR code for contract
     */
    public function generateQrCode(Contract $contract)
    {
        $qrCode = $contract->generateQrCode();

        return response()->json([
            'status' => 'success',
            'message' => 'QR code generated successfully',
            'data' => [
                'qr_code' => $qrCode
            ]
        ]);
    }

    /**
     * Update editable fields after approval
     */
    public function updateEditableFields(Request $request, Contract $contract)
    {
        // Allow editing if approved OR if we are updating contract_date and status is draft
        $isDraftDateUpdate = $contract->contract_status === 'draft' && $request->has('contract_date');
        
        if (!$contract->is_approved && !$isDraftDateUpdate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contract must be approved to edit these fields'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'signatory_name' => 'nullable|string|max:255',
            'signatory_position' => 'nullable|string|max:255',
            'signatory_npwp' => 'nullable|string|max:255',
            'signatory_address' => 'nullable|string',
            'marketing_name' => 'nullable|string|max:255',
            'marketing_phone' => 'nullable|string|max:255',
            'marketing_email' => 'nullable|email|max:255',
            'contract_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate contract_date vs quotation_date
        if ($request->has('contract_date') && $contract->quotation) {
            $contractDate = \Carbon\Carbon::parse($request->contract_date)->startOfDay();
            $quotationDate = \Carbon\Carbon::parse($contract->quotation->quotation_date)->startOfDay();
            
            if ($contractDate->lt($quotationDate)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tanggal Kontrak (' . $contractDate->format('d/m/Y') . ') tidak boleh sebelum Tanggal Quotation (' . $quotationDate->format('d/m/Y') . ')'
                ], 422);
            }
        }

        $editableFields = [
            'signatory_name', 'signatory_position', 'signatory_npwp', 'signatory_address',
            'marketing_name', 'marketing_phone', 'marketing_email',
            'contract_date'
        ];

        $updateData = [];
        foreach ($editableFields as $field) {
            if ($request->has($field)) {
                $updateData[$field] = $request->$field;
            }
        }

        if (!empty($updateData)) {
            $updateData['updated_by'] = Auth::id();
            $contract->update($updateData);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Editable fields updated successfully',
            'data' => $contract
        ]);
    }

    /**
     * Add digital signature to contract
     */
    public function addDigitalSignature(Request $request, Contract $contract)
    {
        if (!$contract->canBeSigned()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Contract cannot be signed at this time'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'signature_data' => 'required|string',
            'signature_file' => 'nullable|file|mimes:png,jpg,jpeg|max:2048',
            'position' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $signatureData = $request->signature_data;
        $signedBy = Auth::user()->name;
        $position = $request->position ?? Auth::user()->position ?? 'Staff';

        // Handle signature file upload if provided
        $signatureFile = null;
        if ($request->hasFile('signature_file')) {
            $signatureFile = $request->file('signature_file')->store('contracts/signatures', 'public');
        }

        // Add digital signature
        $contract->addDigitalSignature($signatureData, $signedBy, $position);
        
        // Update signature file if provided
        if ($signatureFile) {
            $contract->update(['digital_signature_file' => $signatureFile]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Digital signature added successfully',
            'data' => [
                'contract' => $contract,
                'signature_status' => $contract->digital_signature_status,
                'signed_at' => $contract->signature_at,
                'signed_by' => $contract->signed_by
            ]
        ]);
    }

    /**
     * Verify NPWP for contract
     */
    public function verifyNPWP(Request $request, Contract $contract)
    {
        $validator = Validator::make($request->all(), [
            'npwp_number' => 'required|string|max:20',
            'verification_data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $npwpNumber = $request->npwp_number;
        $verificationData = $request->verification_data ?? [];

        // Simulate NPWP verification (in real implementation, integrate with NPWP API)
        $isValid = $this->validateNPWPNumber($npwpNumber);
        
        if ($isValid) {
            $contract->verifyNPWP($npwpNumber, $verificationData);
            
            return response()->json([
                'status' => 'success',
                'message' => 'NPWP verified successfully',
                'data' => [
                    'contract' => $contract,
                    'npwp_status' => $contract->npwp_status,
                    'npwp_verified_at' => $contract->npwp_verified_at
                ]
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid NPWP number'
            ], 422);
        }
    }

    /**
     * Generate schedule automatically for contract
     */
    public function generateSchedule(Contract $contract)
    {
        if (!$contract->canGenerateSchedule()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Schedule cannot be generated at this time'
            ], 422);
        }

        try {
            $contract->generateScheduleAutomatically();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Schedule generated successfully',
                'data' => [
                    'contract' => $contract,
                    'schedule_data' => $contract->schedule_data,
                    'generated_at' => $contract->schedule_generated_at
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update contract status
     */
    public function updateStatus(Request $request, Contract $contract)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,pending_signature,signed,active,completed,cancelled'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $contract->updateContractStatus($request->status);

        return response()->json([
            'status' => 'success',
            'message' => 'Contract status updated successfully',
            'data' => [
                'contract' => $contract,
                'status_badge' => $contract->contract_status_badge,
                'status_text' => $contract->contract_status_text
            ]
        ]);
    }

    /**
     * Get contract signing status
     */
    public function getSigningStatus(Contract $contract)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'contract_status' => $contract->contract_status,
                'digital_signature_status' => $contract->digital_signature_status,
                'npwp_status' => $contract->npwp_status,
                'can_be_signed' => $contract->canBeSigned(),
                'can_generate_schedule' => $contract->canGenerateSchedule(),
                'is_ready_for_posting' => $contract->isReadyForPosting(),
                'has_digital_signature' => $contract->hasDigitalSignature(),
                'is_npwp_verified' => $contract->isNPWPVerified(),
                'schedule_generated' => $contract->schedule_generated
            ]
        ]);
    }


    /**
     * Validate NPWP number (simplified validation)
     */
    private function validateNPWPNumber($npwpNumber)
    {
        // Remove any non-numeric characters
        $npwpNumber = preg_replace('/[^0-9]/', '', $npwpNumber);
        
        // Check if NPWP number has correct length (15 digits)
        if (strlen($npwpNumber) !== 15) {
            return false;
        }
        
        // Basic NPWP validation (in real implementation, use NPWP API)
        return true;
    }

    /**
     * Finalize Contract (change status from draft to waiting_for_approval)
     * Marketing Staff submits contract for manager approval
     */
    // Save Draft (placeholder/compatibility method)
    public function saveDraft(Request $request, Contract $contract)
    {
        // For now, it just returns success as requested by the frontend flow
        // The contract remains in draft status as it already is
        return response()->json([
            'status' => 'success',
            'message' => 'Draft berhasil disimpan',
            'data' => $contract
        ]);
    }

    public function finalize(Request $request, Contract $contract)
    {
        try {
            // Check if contract is in draft status
            if ($contract->contract_status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only draft contracts can be finalized. Current status: ' . $contract->contract_status
                ], 400);
            }

            // Update contract status to waiting_for_approval (requires manager approval)
            $contract->update([
                'contract_status' => 'waiting_for_approval',
                'updated_by' => Auth::id(),
            ]);

            // Log the action
            Log::info("Contract {$contract->contract_number} submitted for approval by user " . Auth::id());

            return response()->json([
                'status' => 'success',
                'message' => 'Contract submitted for approval successfully. Waiting for manager approval.',
                'data' => [
                    'contract_id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'contract_status' => $contract->contract_status,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error finalizing contract {$contract->id}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to finalize contract: ' . $e->getMessage()
            ], 500);
        }
    }


    public function updateNotes(Request $request, Contract $contract)
    {
        $validator = Validator::make($request->all(), [
            'notes_operation' => 'nullable|string',
            'notes_finance' => 'nullable|string',
            'notes_sales' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contract->update([
                'notes_operation' => $request->notes_operation,
                'notes_finance' => $request->notes_finance,
                'notes_sales' => $request->notes_sales,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Contract notes updated successfully',
                'data' => $contract->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update contract notes: ' . $e->getMessage()
            ], 500);
        }
    }


    public function uploadFile(Request $request, Contract $contract)
    {
        // Fetch allowed file types from MasterOption
        $allowedTypes = \App\Models\MasterOption::where('name', 'Contract File Type')
            ->first()
            ?->optionDetails
            ->pluck('code')
            ->toArray() ?? [];
            
        // Fallback to default types if MasterOption not found or empty
        if (empty($allowedTypes)) {
            $allowedTypes = ['contract_scan', 'tax_scan', 'npwp_scan', 'invoice', 'payment_proof', 'other'];
        }

        // Check permission for uploading files
        // Extended: Allow anyone who can view contracts or has marketing role to upload files
        // Only approval/delete remains restricted
        $user = Auth::user();
        if (!$user->hasPermission('marketing.contract_files.create') && 
            !$user->hasPermission('marketing.contracts.view') &&
            !$user->hasRole('Marketing')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk upload file kontrak.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // Max 10MB
            'file_type' => 'required|string|in:' . implode(',', $allowedTypes),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            
            // IMPORTANT: Get file properties BEFORE moving the file
            // Once file is moved, the temporary file no longer exists
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();
            
            // Create directory if not exists
            $uploadPath = public_path('uploads/contracts');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Generate unique filename
            $filename = time() . '_' . $originalName;
            
            // Move file to public/uploads/contracts
            $file->move($uploadPath, $filename);

            // Create ContractFile record
            $contractFile = \App\Models\ContractFile::create([
                'contract_id' => $contract->id,
                'file_type' => $request->file_type,
                'file_name' => $originalName,
                'file_path' => 'uploads/contracts/' . $filename,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'uploaded_by' => Auth::id(),
                'uploaded_at' => now(),
                'verification_status' => 'pending',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded successfully. Waiting for verification.',
                'data' => $contractFile->load(['uploader', 'verifier'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verifyFile(Request $request, Contract $contract, $fileId)
    {
        $validator = Validator::make($request->all(), [
            'verification_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contractFile = \App\Models\ContractFile::where('contract_id', $contract->id)
                ->where('id', $fileId)
                ->firstOrFail();

            // Check permission
            if (!Auth::user()->hasPermission('marketing.contract_files.approve') && !Auth::user()->canApprove('contract_files')) {
                 return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses untuk memverifikasi file.'
                ], 403);
            }

            $contractFile->verify(
                Auth::id(),
                $request->verification_notes ?? 'File verified'
            );

            // Link to existing invoices for this contract
            $this->linkContractFileToExistingInvoices($contractFile);

            return response()->json([
                'status' => 'success',
                'message' => 'File verified successfully',
                'data' => $contractFile->fresh()->load(['uploader', 'verifier'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to verify file: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function rejectFile(Request $request, Contract $contract, $fileId)
    {
        $validator = Validator::make($request->all(), [
            'verification_notes' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contractFile = \App\Models\ContractFile::where('contract_id', $contract->id)
                ->where('id', $fileId)
                ->firstOrFail();

            // Check permission
            if (!Auth::user()->hasPermission('marketing.contract_files.approve') && !Auth::user()->canApprove('contract_files')) {
                 return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses untuk reject file.'
                ], 403);
            }

            $contractFile->reject(
                Auth::id(),
                $request->verification_notes
            );

            return response()->json([
                'status' => 'success',
                'message' => 'File rejected successfully',
                'data' => $contractFile->fresh()->load(['uploader', 'verifier'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject file: ' . $e->getMessage()
            ], 500);
        }
    }


    public function deleteFile(Contract $contract, $fileId)
    {
        // Check if user has permission to delete files (using permission checkbox)
        $user = Auth::user();
        
        // Check for delete permission or approve permission (managers can delete)
        // Standardized to marketing.contract_files.delete
        $hasPermission = $user->hasPermission('marketing.contract_files.delete')
                      || $user->hasPermission('marketing.contracts.delete') // Legacy/Fallback
                      || $user->canApprove('contract_files'); // Approvers usually can delete too

        if (!$hasPermission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki akses untuk menghapus file. Pastikan role Anda memiliki permission "Delete" Contract Files.'
            ], 403);
        }

        try {
            $contractFile = \App\Models\ContractFile::where('contract_id', $contract->id)
                ->where('id', $fileId)
                ->firstOrFail();

            // Cleanup linked invoice files to prevent broken links
            $cleanPath = str_replace('uploads/', '', $contractFile->file_path);
            \App\Models\InvoiceFile::where('file_path', $cleanPath)->delete();

            $contractFile->deleteFile();

            Log::info("Contract file {$fileId} deleted from contract {$contract->contract_number} by user " . Auth::id());

            return response()->json([
                'status' => 'success',
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete file: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getFiles(Contract $contract, Request $request)
    {
        try {
            $query = $contract->contractFiles()->with(['uploader', 'verifier']);

            // Filter by verification status
            if ($request->filled('status')) {
                if ($request->status === 'pending') {
                    $query->pending();
                } elseif ($request->status === 'verified') {
                    $query->verified();
                } elseif ($request->status === 'rejected') {
                    $query->rejected();
                }
            }

            $files = $query->latest()->get();

            return response()->json([
                'status' => 'success',
                'data' => $files
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-create Virtual Account for customer when contract becomes active
     */
    public function createVirtualAccountForContract(Contract $contract)
    {
        try {
            // Load customer relationship
            $contract->load('customer');
            
            if (!$contract->customer) {
                Log::warning("Contract {$contract->contract_number} has no customer, skipping VA creation");
                return;
            }

            $customer = $contract->customer;
            
            // Check if VA already exists for this customer
            // Check by account_name (exact match or contains customer name)
            $existingVA = \App\Models\CompanyVirtualAccount::where(function($q) use ($customer) {
                    $q->where('account_name', $customer->name)
                      ->orWhere('account_name', 'like', "%{$customer->name}%");
                })
                ->orWhere('description', 'like', "%Customer: {$customer->name}%")
                ->orWhere('description', 'like', "%customer: {$customer->name}%")
                ->orWhere('description', 'like', "%{$customer->name}%")
                ->first();
            
            if ($existingVA) {
                Log::info("Virtual Account already exists for customer {$customer->name} (ID: {$customer->id}, VA ID: {$existingVA->id})");
                return;
            }

            // Get bank payment from billing group or use default
            $bankPayment = null;
            
            // Try to get from billing group
            $billingGroup = $contract->billingGroup;
            if ($billingGroup && isset($billingGroup->bank_payment_id)) {
                $bankPayment = \App\Models\BankPayment::find($billingGroup->bank_payment_id);
            }
            
            // If not found, try to get default bank payment
            if (!$bankPayment) {
                $bankPayment = \App\Models\BankPayment::where('is_default_va', true)
                    ->where('is_active', true)
                    ->first();
            }
            
            // If still not found, get first active bank payment
            if (!$bankPayment) {
                $bankPayment = \App\Models\BankPayment::where('is_active', true)->first();
            }
            
            if (!$bankPayment) {
                Log::warning("No bank payment found for contract {$contract->contract_number}, skipping VA creation");
                return;
            }

            // Get company (default to company_id = 7 or customer's company if exists)
            $companyId = 7; // Default company ID
            if ($customer->company_id) {
                $companyId = $customer->company_id;
            }

            // Generate account number using CompanyVirtualAccount::generateAccountNumber
            $accountNumber = \App\Models\CompanyVirtualAccount::generateAccountNumber($companyId, $bankPayment->id);

            // Create Virtual Account
            $virtualAccount = \App\Models\CompanyVirtualAccount::create([
                'company_id' => $companyId,
                'bank_payment_id' => $bankPayment->id,
                'account_number' => $accountNumber,
                'account_name' => $customer->name,
                'description' => "Auto-generated for customer: {$customer->name} (Contract: {$contract->contract_number})",
                'is_active' => true,
                'notes' => "Auto-created when contract {$contract->contract_number} became active",
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            Log::info("Virtual Account created automatically for customer {$customer->name} (VA ID: {$virtualAccount->id}, Account Number: {$accountNumber})");
            
            return $virtualAccount;
        } catch (\Exception $e) {
            Log::error("Failed to create Virtual Account for contract {$contract->contract_number}: " . $e->getMessage());
            // Don't throw exception, just log the error
            return null;
        }
    }

    // ================================================
    // Contract Room Management Methods
    // ================================================

    /**
     * Get buildings available for room management (from contract surveys)
     */
    public function getBuildingsForRooms(Contract $contract)
    {
        try {
            $buildings = collect();
            
            // Get buildings from contract surveys
            $contractSurveys = $contract->contractSurveys()->with('survey.building')->get();
            
            foreach ($contractSurveys as $contractSurvey) {
                if ($contractSurvey->survey && $contractSurvey->survey->building) {
                    $building = $contractSurvey->survey->building;
                    if (!$buildings->contains('id', $building->id)) {
                        $buildings->push([
                            'id' => $building->id,
                            'nama_gedung' => $building->nama_gedung ?? $building->name,
                            'name' => $building->name
                        ]);
                    }
                }
            }
            
            // Also get buildings from existing contract rooms
            $contractRooms = $contract->contractRooms()->with('room.building')->get();
            foreach ($contractRooms as $contractRoom) {
                if ($contractRoom->room && $contractRoom->room->building) {
                    $building = $contractRoom->room->building;
                    if (!$buildings->contains('id', $building->id)) {
                        $buildings->push([
                            'id' => $building->id,
                            'nama_gedung' => $building->nama_gedung ?? $building->name,
                            'name' => $building->name
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'buildings' => $buildings->values()
            ]);
        } catch (\Exception $e) {
            Log::error("Error getting buildings for contract {$contract->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get buildings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single contract room details for editing
     */
    public function getContractRoom($contractRoomId)
    {
        try {
            $contractRoom = \App\Models\ContractRoom::with(['room.building', 'contract'])
                ->findOrFail($contractRoomId);

            return response()->json([
                'success' => true,
                'data' => $contractRoom
            ]);
        } catch (\Exception $e) {
            Log::error("Error getting contract room {$contractRoomId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get room: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a new room to contract
     */
    public function addRoom(Request $request, Contract $contract)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:master_rooms,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if contract is in draft status
        if ($contract->contract_status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Rooms can only be added to draft contracts'
            ], 400);
        }

        try {
            // Check if room already exists in contract
            $existingRoom = \App\Models\ContractRoom::where('contract_id', $contract->id)
                ->where('room_id', $request->room_id)
                ->first();

            if ($existingRoom) {
                return response()->json([
                    'success' => false,
                    'message' => 'This room is already added to the contract'
                ], 400);
            }

            $contractRoom = \App\Models\ContractRoom::create([
                'contract_id' => $contract->id,
                'room_id' => $request->room_id,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            Log::info("Room {$request->room_id} added to contract {$contract->contract_number} by user " . Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Room added successfully',
                'data' => $contractRoom->load(['room.building'])
            ]);
        } catch (\Exception $e) {
            Log::error("Error adding room to contract {$contract->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add room: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update contract room
     */
    public function updateRoom(Request $request, Contract $contract, $contractRoomId)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:master_rooms,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if contract is in draft status
        if ($contract->contract_status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Rooms can only be updated in draft contracts'
            ], 400);
        }

        try {
            $contractRoom = \App\Models\ContractRoom::where('contract_id', $contract->id)
                ->where('id', $contractRoomId)
                ->firstOrFail();

            // Check if new room is not a duplicate
            if ($request->room_id != $contractRoom->room_id) {
                $duplicate = \App\Models\ContractRoom::where('contract_id', $contract->id)
                    ->where('room_id', $request->room_id)
                    ->where('id', '!=', $contractRoomId)
                    ->first();

                if ($duplicate) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This room is already added to the contract'
                    ], 400);
                }
            }

            $contractRoom->update([
                'room_id' => $request->room_id,
                'updated_by' => Auth::id()
            ]);

            Log::info("Contract room {$contractRoomId} updated in contract {$contract->contract_number} by user " . Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully',
                'data' => $contractRoom->fresh()->load(['room.building'])
            ]);
        } catch (\Exception $e) {
            Log::error("Error updating contract room {$contractRoomId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update room: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete contract room
     */
    public function deleteRoom($contractRoomId)
    {
        try {
            $contractRoom = \App\Models\ContractRoom::with('contract')->findOrFail($contractRoomId);

            // Check if contract is in draft status
            if ($contractRoom->contract->contract_status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Rooms can only be deleted from draft contracts'
                ], 400);
            }

            $contractNumber = $contractRoom->contract->contract_number;
            $roomId = $contractRoom->room_id;
            $contractRoom->delete();

            Log::info("Contract room (room_id: {$roomId}) deleted from contract {$contractNumber} by user " . Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Room deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error deleting contract room {$contractRoomId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete room: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk approve contract files
     * Only allowed for admin, manager, supervisor, and similar roles
     */
    public function bulkApproveFiles(Request $request, Contract $contract)
    {
        $validator = Validator::make($request->all(), [
            'file_ids' => 'required|array|min:1',
            'file_ids.*' => 'required|integer|exists:contract_files,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user has permission to approve files
        if (!Auth::user()->canApprove('contract_files')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki izin untuk menyetujui file kontrak. Hubungi admin untuk mendapatkan permission contract_files.approve.'
            ], 403);
        }

        try {
            $approvedCount = 0;
            $errors = [];

            foreach ($request->file_ids as $fileId) {
                $contractFile = \App\Models\ContractFile::where('contract_id', $contract->id)
                    ->where('id', $fileId)
                    ->first();

                if (!$contractFile) {
                    $errors[] = "File ID {$fileId} not found in this contract";
                    continue;
                }

                if ($contractFile->verification_status === 'verified') {
                    continue; // Already verified, skip
                }

                // Approve the file
                $contractFile->verify(
                    Auth::id(),
                    'Bulk approved'
                );
                $approvedCount++;
            }

            $message = "{$approvedCount} file(s) approved successfully.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }

            Log::info("Bulk file approval for contract {$contract->contract_number}: {$approvedCount} files approved by user " . Auth::id());

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'approved_count' => $approvedCount,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to bulk approve files for contract {$contract->id}: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Link verified contract file to existing invoices for that contract
     */
    private function linkContractFileToExistingInvoices($contractFile)
    {
        $contract = $contractFile->contract;
        if (!$contract) return;

        // Find all active/sent/draft invoices for this contract
        $invoices = \App\Models\Invoice::where('contract_number', $contract->contract_number)
            ->where('invoice_status', '!=', 'cancelled')
            ->get();

        foreach ($invoices as $invoice) {
            $cleanPath = str_replace('uploads/', '', $contractFile->file_path);
            
            // Ensure no double attachment
            $exists = \App\Models\InvoiceFile::where('invoice_id', $invoice->id)
                ->where('file_path', $cleanPath)
                ->exists();

            if (!$exists) {
                \App\Models\InvoiceFile::create([
                    'invoice_id' => $invoice->id,
                    'file_type' => 'attachment',
                    'file_name' => 'Contract File - ' . $contractFile->file_name,
                    'file_path' => $cleanPath,
                    'file_size' => $contractFile->file_size,
                    'mime_type' => $contractFile->mime_type,
                    'description' => "File Kontrak #{$contract->contract_number}: {$contractFile->file_type}",
                    'uploaded_by' => $contractFile->uploaded_by,
                    'uploaded_at' => $contractFile->uploaded_at,
                ]);
                Log::info("Contract File #{$contractFile->id} linked to existing Invoice #{$invoice->id}");
            }
        }
    }

    /**
     * Toggle BA Files Supported status for a contract
     */
    public function toggleBaFilesSupported(Request $request, Contract $contract)
    {
        try {
            $value = $request->input('ba_files_supported');
            $baFilesSupported = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            
            $contract->update([
                'ba_files_supported' => $baFilesSupported,
                'updated_by' => Auth::id()
            ]);

            // NEW: If BA files requirement is turned OFF, try to auto-generate invoices
            if (!$baFilesSupported) {
                try {
                    $invoiceService = app(\App\Services\Finance\InvoiceGenerationService::class);
                    $invoiceService->attemptAutoInvoiceForContract($contract->id);
                } catch (\Exception $e) {
                    Log::error("Failed to trigger auto-invoice after disabling ba_files_supported for contract {$contract->contract_number}: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Status BA Files Supported berhasil diperbarui.',
                'ba_files_supported' => $contract->ba_files_supported
            ]);
        } catch (\Exception $e) {
            Log::error('ContractController@toggleBaFilesSupported error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle Hold Invoice status for a contract
     */
    public function toggleHoldInvoice(Request $request, Contract $contract)
    {
        try {
            $value = $request->input('hold_invoice');
            $holdInvoice = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            
            $contract->update([
                'hold_invoice' => $holdInvoice,
                'updated_by' => Auth::id()
            ]);

            // NEW: If hold_invoice is turned OFF, try to auto-generate invoices
            if (!$holdInvoice) {
                try {
                    $invoiceService = app(\App\Services\Finance\InvoiceGenerationService::class);
                    $invoiceService->attemptAutoInvoiceForContract($contract->id);
                } catch (\Exception $e) {
                    Log::error("Failed to trigger auto-invoice after disabling hold_invoice for contract {$contract->contract_number}: " . $e->getMessage());
                }
            }

            $statusMessage = $contract->hold_invoice ? 'Status Hold Invoice aktif. Invoice tidak akan dibuat secara otomatis.' : 'Status Hold Invoice dinonaktifkan. Invoice akan dibuat secara normal.';

            return response()->json([
                'success' => true,
                'message' => $statusMessage,
                'hold_invoice' => $contract->hold_invoice
            ]);
        } catch (\Exception $e) {
            Log::error('ContractController@toggleHoldInvoice error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle Target Contract (YES/NO) status for a contract
     */
    public function toggleContractTarget(Request $request, Contract $contract)
    {
        try {
            // Check permission
            if (!Auth::user()->hasPermission('marketing.contracts.target.update') && !Auth::user()->hasPermission('marketing.contracts.target.create')) {
                 return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki ijin untuk mengubah Target Kontrak.'
                ], 403);
            }

            $value = $request->input('is_contract');
            $isContract = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            
            $contract->update([
                'is_contract' => $isContract,
                'updated_by' => Auth::id()
            ]);

            $statusMessage = $contract->is_contract ? 'Status Contract (Target Marketing) diaktifkan (YES).' : 'Status Contract dinonaktifkan (NO).';

            return response()->json([
                'success' => true,
                'message' => $statusMessage,
                'is_contract' => $contract->is_contract
            ]);
        } catch (\Exception $e) {
            Log::error('ContractController@toggleContractTarget error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update Achiever (Commission Recipient) for a contract
     * Only allowed when contract is active
     */
    public function updateAchiever(Request $request, Contract $contract)
    {
        try {
            // Check if contract is active
            if ($contract->contract_status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya kontrak dengan status Active yang dapat diubah achiever-nya.'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'commission_recipient_id' => 'required|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get user to verify they are active
            $user = User::find($request->commission_recipient_id);
            if (!$user || !$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'User yang dipilih tidak aktif'
                ], 400);
            }

            $contract->update([
                'commission_recipient_id' => $request->commission_recipient_id,
                'updated_by' => Auth::id()
            ]);

            Log::info("Contract {$contract->contract_number} achiever updated to user {$request->commission_recipient_id} by user " . Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Achiever berhasil diperbarui.',
                'data' => [
                    'commission_recipient_id' => $contract->commission_recipient_id,
                    'commission_recipient_name' => $user->name
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('ContractController@updateAchiever error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui achiever: ' . $e->getMessage()
            ], 500);
        }
    }
    public function updateNetValue(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);

        // Check permission (Support new granular permissions and legacy one)
        if (!Auth::user()->hasPermission('marketing.contract-net.edit') && 
            !Auth::user()->hasPermission('marketing.contract-net.approve') &&
            !Auth::user()->hasPermission('contractNet_approved')) {
             return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki ijin untuk mengubah Contract Net'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'net_value' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Nilai Contract Net harus berupa angka positif'
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            $contract->update([
                'net_value' => $request->net_value,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contract Net berhasil diperbarui',
                'formatted_value' => 'Rp ' . number_format($request->net_value, 0, ',', '.')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ===========================
    // CONTRACT MERGE METHODS
    // ===========================

    /**
     * Get contracts yang bisa di-merge untuk customer tertentu.
     * Digunakan oleh merge wizard untuk populate list pilihan.
     */
    public function getMergeCandidates(Request $request)
    {
        try {
            $customerId = $request->input('customer_id');
            $excludeId  = $request->input('exclude_contract_id');

            if (!$customerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'customer_id wajib diisi'
                ], 422);
            }

            // Status yang eligible untuk di-merge
            $mergableStatuses = ['active', 'approved', 'signed'];

            $query = Contract::with(['contractRooms.room', 'contractRentals.masterRental'])
                ->where('customer_id', $customerId)
                ->whereIn('contract_status', $mergableStatuses)
                // Exclude contract yang sudah hasil dari merge
                ->where(function ($q) {
                    $q->whereNull('contract_type')
                      ->orWhereNotIn('contract_type', ['merge']);
                });

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $contracts = $query->orderByDesc('created_at')->get();

            $data = $contracts->map(function ($c) {
                return [
                    'id'              => $c->id,
                    'contract_number' => $c->contract_number,
                    'contract_status' => $c->contract_status,
                    'end_date'        => $c->end_date ? \Carbon\Carbon::parse($c->end_date)->format('d/m/Y') : '-',
                    'rooms_count'     => $c->contractRooms->count(),
                    'rentals_count'   => $c->contractRentals->count(),
                ];
            });

            return response()->json([
                'success' => true,
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            Log::error('getMergeCandidates error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat candidates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview data yang akan di-merge sebelum eksekusi.
     * Returns: summary detail dari semua source contracts.
     */
    public function previewMerge(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'source_contract_ids' => 'required|array|min:1',
                'source_contract_ids.*' => 'exists:contracts,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $service = app(ContractMergeService::class);
            $preview = $service->preview($request->source_contract_ids);

            return response()->json([
                'success' => true,
                'data' => $preview
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eksekusi merge: contract baru + source contracts dimasukkan ke merge.
     * Contract lama di-terminate dengan status term-renew.
     * Dipanggil setelah contract baru berhasil dibuat via wizard.
     */
    public function executeMerge(Request $request, Contract $contract)
    {
        try {
            $validator = Validator::make($request->all(), [
                'source_contract_ids' => 'required|array|min:1',
                'source_contract_ids.*' => 'exists:contracts,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $service = app(ContractMergeService::class);
            $result = $service->execute($contract, $request->source_contract_ids);

            DB::commit();

            Log::info('Contract merge executed', [
                'new_contract_id' => $contract->id,
                'source_ids' => $request->source_contract_ids,
                'result' => $result,
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'stats' => $result['stats'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('executeMerge error: ' . $e->getMessage(), [
                'contract_id' => $contract->id,
                'source_ids' => $request->source_contract_ids ?? [],
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan merge: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tampilkan Pure Merge Wizard — dedicated page untuk merge tanpa quotation baru.
     * Jalur B: user klik "Merge Contracts" dari halaman list contract.
     */
    public function mergeWizard(Request $request)
    {
        // Hanya tampilkan customer yang memiliki minimal 1 contract eligible untuk merge
        $mergableStatuses = ['active', 'approved', 'signed'];

        $customerIds = Contract::whereIn('contract_status', $mergableStatuses)
            ->where(function ($q) {
                $q->whereNull('contract_type')
                  ->orWhereNotIn('contract_type', ['merge']);
            })
            ->pluck('customer_id')
            ->unique();

        $customers = Customer::whereIn('id', $customerIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $branches = \App\Models\Branch::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return view('marketing.contracts.merge-wizard', compact('customers', 'branches'));
    }

    /**
     * Simpan hasil Pure Merge Wizard.
     * Membuat contract baru bertipe 'merge' tanpa quotation,
     * lalu eksekusi merge dari source contracts.
     */
    public function saveMergeWizard(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id'          => 'required|exists:customers,id',
                'source_contract_ids'  => 'required|array|min:1',
                'source_contract_ids.*'=> 'exists:contracts,id',
                'branch_id'            => 'required|exists:branches,id',
                'contract_date'        => 'required|date',
                'start_date'           => 'required|date',
                'end_date'             => 'required|date|after:start_date',
                'marketing_id'         => 'required|exists:users,id',
                'notes'                => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Generate contract number mengikuti pola standar: [BRANCH]-CA/[YY]-[MM]/[NNNN]
            $documentNumberService = app(\App\Services\DocumentNumberService::class);
            $contractNumber = $documentNumberService->generate(
                'contract',
                null,        // branchCode: null, ambil dari branchId
                null,        // buildingId
                null,        // contractId
                null,        // quotationId
                null,        // surveyId
                null,        // warehouseId
                (int) $request->branch_id // branchId langsung
            );

            // Buat contract baru bertipe merge
            $newContract = Contract::create([
                'contract_number' => $contractNumber,
                'customer_id'     => $request->customer_id,
                'branch_id'       => $request->branch_id,
                'contract_type'   => 'merge',
                'contract_date'   => $request->contract_date,
                'start_date'      => $request->start_date,
                'end_date'        => $request->end_date,
                'marketing_id'    => $request->marketing_id,
                'contract_status' => 'active',
                'notes'           => $request->notes,
                'created_by'      => Auth::id(),
                'updated_by'      => Auth::id(),
            ]);

            // Eksekusi merge
            $service = app(ContractMergeService::class);
            $result  = $service->execute($newContract, $request->source_contract_ids);

            DB::commit();

            return response()->json([
                'success'        => true,
                'message'        => 'Contract merge berhasil dibuat.',
                'contract_id'    => $newContract->id,
                'contract_number'=> $newContract->contract_number,
                'redirect_url'   => route('marketing.contracts.show', $newContract->id),
                'stats'          => $result['stats'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('saveMergeWizard error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat merge contract: ' . $e->getMessage()
            ], 500);
        }
    }
}


<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\JobAdvice;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\User;
use App\Models\ContractRental;
use App\Models\MasterRental;
use App\Models\JobAdviceRoom;
use App\Models\ContractRoom;
use App\Models\JobSchedule;
use App\Models\UnitOnWall;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JobAdviceController extends Controller
{
    use AccessControlFilterTrait, ColumnFilterTrait;
    
    public function index(Request $request)
    {
        // IMPORTANT: Remove manually handled filters from request BEFORE creating query
        // This prevents AutoFilterable (via global scope) from processing them
        $filters = $request->input('filter', []);
        $originalFilters = $filters;
        
        // Check and remove manually handled filters early
        $hasSubmittedByFilter = false;
        $submittedByFilterValue = null;
        $hasWithInvoicingFilter = false;
        $withInvoicingFilterValue = null;
        $hasWithMaterialsFilter = false;
        $withMaterialsFilterValue = null;
        $hasUpdatedAtFilter = false;
        $updatedAtFilterValue = null;
        $hasReferenceNumberFilter = false;
        $referenceNumberFilterValue = null;
        
        // Check for submittedBy.name filter early
        foreach ($filters as $filterKey => $filterValue) {
            if (!empty(trim($filterValue))) {
                $normalizedKey = str_replace('__', '.', $filterKey);
                
                // Identify Submitted By Filter
                if (in_array($normalizedKey, ['submittedBy.name', 'submitted_by.name']) || 
                    in_array($filterKey, ['submittedBy__name', 'submitted_by__name', 'submittedBy.name', 'submitted_by.name']) ||
                    preg_match('/^submitted[_.]?by[_.]?name$/i', $normalizedKey)) {
                    $submittedByFilterValue = trim($filterValue);
                    $hasSubmittedByFilter = true;
                    unset($filters[$filterKey]);
                    continue;
                }
                
                // Identify Reference Number Filter
                if ($filterKey === 'reference_number') {
                    $referenceNumberFilterValue = trim($filterValue);
                    $hasReferenceNumberFilter = true;
                    unset($filters[$filterKey]);
                    continue;
                }
            }
        }
        
        // Check for other filters early
        foreach (['with_invoicing', 'with__invoicing'] as $filterKey) {
            if (isset($filters[$filterKey]) && !empty(trim($filters[$filterKey]))) {
                $withInvoicingFilterValue = trim($filters[$filterKey]);
                $hasWithInvoicingFilter = true;
                unset($filters[$filterKey]);
                break;
            }
        }
        
        foreach (['with_materials', 'with__materials'] as $filterKey) {
            if (isset($filters[$filterKey]) && !empty(trim($filters[$filterKey]))) {
                $withMaterialsFilterValue = trim($filters[$filterKey]);
                $hasWithMaterialsFilter = true;
                unset($filters[$filterKey]);
                break;
            }
        }
        
        foreach (['updated_at', 'updated__at', 'job_advices.updated_at', 'job_advices__updated_at'] as $filterKey) {
            if (isset($filters[$filterKey]) && !empty(trim($filters[$filterKey]))) {
                $updatedAtFilterValue = trim($filters[$filterKey]);
                $hasUpdatedAtFilter = true;
                unset($filters[$filterKey]);
                break;
            }
        }
        
        // Remove manually handled filters from request BEFORE creating query
        // Use request()->replace() to ensure the change is immediately reflected
        if ($hasSubmittedByFilter || $hasWithInvoicingFilter || $hasWithMaterialsFilter || $hasUpdatedAtFilter || $hasReferenceNumberFilter) {
            // Store original request data
            $requestData = $request->all();
            // Update filter array
            $requestData['filter'] = $filters;
            // Replace entire request data to ensure global scope sees the change
            $request->replace($requestData);
            
            // Also set a flag to prevent AutoFilterable from processing these filters
            // Include both original format (with __) and normalized format (with .)
            $skipFilters = [];
            if ($hasSubmittedByFilter) {
                $skipFilters['submittedBy__name'] = true;
                $skipFilters['submittedBy.name'] = true;
                $skipFilters['submitted_by__name'] = true;
                $skipFilters['submitted_by.name'] = true;
            }
            if ($hasWithInvoicingFilter) {
                $skipFilters['with_invoicing'] = true;
                $skipFilters['with__invoicing'] = true;
            }
            if ($hasWithMaterialsFilter) {
                $skipFilters['with_materials'] = true;
                $skipFilters['with__materials'] = true;
            }
            if ($hasUpdatedAtFilter) {
                $skipFilters['updated_at'] = true;
                $skipFilters['updated__at'] = true;
                $skipFilters['job_advices.updated_at'] = true;
                $skipFilters['job_advices__updated_at'] = true;
            }
            if ($hasReferenceNumberFilter) {
                $skipFilters['reference_number'] = true;
            }
            $request->merge(['_skip_auto_filter' => $skipFilters]);
            
        }
        
        // Create query WITHOUT global scope to prevent AutoFilterable from processing filters
        // We'll apply filters manually instead
        $query = JobAdvice::withoutGlobalScope('autoFilter')
            ->with(['contract.customer', 'contract.quotation', 'quotation', 'submittedBy', 'approvedBy', 'updater', 'requestedBy', 'rooms']);

        // Apply access control filter (hierarchical data)
        // Default: Jika tidak set hirarki, hanya bisa lihat data sendiri
        // NOTE: Pass null for branchField and warehouseField since job_advices table doesn't have these columns
        $query = $this->applyAccessControlFilter($query, null, 'created_by', 'request_by', null, null, null);

        // Handle submittedBy.name filter (relation filter)
        if ($hasSubmittedByFilter && !empty($submittedByFilterValue)) {
            $term = trim($submittedByFilterValue);
            
            // Use whereExists for direct table join - more reliable than whereHas
            // Also use case-insensitive search with LOWER() for better matching
            $query->where(function($q) use ($term) {
                // Check submitted_by column via users table (case-insensitive)
                $q->whereExists(function($subQ) use ($term) {
                    $subQ->select(DB::raw(1))
                         ->from('users')
                         ->whereColumn('users.id', 'job_advices.submitted_by')
                         ->whereRaw("LOWER(users.name) LIKE ?", ["%" . strtolower($term) . "%"]);
                })
                // Also check request_by column via users table (case-insensitive)
                ->orWhereExists(function($subQ) use ($term) {
                    $subQ->select(DB::raw(1))
                         ->from('users')
                         ->whereColumn('users.id', 'job_advices.request_by')
                         ->whereRaw("LOWER(users.name) LIKE ?", ["%" . strtolower($term) . "%"]);
                });
            });
            
        }
        
        // Handle with_invoicing filter (boolean filter)
        if ($hasWithInvoicingFilter && !empty($withInvoicingFilterValue)) {
            $term = strtolower(trim($withInvoicingFilterValue));
            // Handle various boolean representations
            if (in_array($term, ['yes', 'y', '1', 'true', 'ya'])) {
                $query->where('with_invoicing', true);
            } elseif (in_array($term, ['no', 'n', '0', 'false', 'tidak'])) {
                $query->where('with_invoicing', false);
            } else {
                // Fallback to LIKE search
                $query->where('with_invoicing', 'LIKE', "%{$term}%");
            }
        }
        
        // Handle with_materials filter (boolean filter)
        if ($hasWithMaterialsFilter && !empty($withMaterialsFilterValue)) {
            $term = strtolower(trim($withMaterialsFilterValue));
            // Handle various boolean representations
            if (in_array($term, ['yes', 'y', '1', 'true', 'ya'])) {
                $query->where('with_materials', true);
            } elseif (in_array($term, ['no', 'n', '0', 'false', 'tidak'])) {
                $query->where('with_materials', false);
            } else {
                // Fallback to LIKE search
                $query->where('with_materials', 'LIKE', "%{$term}%");
            }
        }
        
        // Handle updated_at filter (date filter)
        if ($hasUpdatedAtFilter && !empty($updatedAtFilterValue)) {
            $term = trim($updatedAtFilterValue);
            
            // Filter by updated_at column directly on job_advices table
            // Search in multiple date formats to handle various formats including 3-digit month (012, 011)
            $query->where(function($q) use ($term) {
                // Standard date formats
                $q->whereRaw("DATE_FORMAT(job_advices.updated_at, '%d %M %Y') LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("DATE_FORMAT(job_advices.updated_at, '%M %Y') LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("DATE_FORMAT(job_advices.updated_at, '%M') LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("DATE_FORMAT(job_advices.updated_at, '%Y-%m-%d') LIKE ?", ["%{$term}%"])
                  // Format with 2-digit month: DD/MM/YYYY
                  ->orWhereRaw("DATE_FORMAT(job_advices.updated_at, '%d/%m/%Y') LIKE ?", ["%{$term}%"])
                  // Format with 3-digit month (leading zero): DD/0MM/YYYY (e.g., 02/012/2025)
                  ->orWhereRaw("CONCAT(DATE_FORMAT(job_advices.updated_at, '%d/'), LPAD(MONTH(job_advices.updated_at), 3, '0'), DATE_FORMAT(job_advices.updated_at, '/%Y')) LIKE ?", ["%{$term}%"])
                  // Format with 3-digit month and time: DD/0MM/YYYY HH:MM (e.g., 02/012/2025 10:07)
                  ->orWhereRaw("CONCAT(DATE_FORMAT(job_advices.updated_at, '%d/'), LPAD(MONTH(job_advices.updated_at), 3, '0'), DATE_FORMAT(job_advices.updated_at, '/%Y %H:%i')) LIKE ?", ["%{$term}%"])
                  // Also handle if user types just the month number (012, 011, etc.) - extract month from term if it's 3 digits
                  ->orWhereRaw("LPAD(MONTH(job_advices.updated_at), 3, '0') LIKE ?", ["%{$term}%"]);
                
                // If term is 3 digits (like 012, 011), also try to match as month number
                if (preg_match('/^0?\d{1,3}$/', $term)) {
                    // If term is 3 digits with leading zero (012), extract month (12)
                    if (strlen($term) === 3 && $term[0] === '0') {
                        $monthNum = (int)substr($term, 1); // Extract 12 from 012
                        if ($monthNum >= 1 && $monthNum <= 12) {
                            $q->orWhereRaw("MONTH(job_advices.updated_at) = ?", [$monthNum]);
                        }
                    } elseif (strlen($term) === 2 || (strlen($term) === 3 && $term[0] !== '0')) {
                        // If term is 2 digits or 3 digits without leading zero, try as month number
                        $monthNum = (int)$term;
                        if ($monthNum >= 1 && $monthNum <= 12) {
                            $q->orWhereRaw("MONTH(job_advices.updated_at) = ?", [$monthNum]);
                        }
                    }
                }
            });
            
            \Log::info('JobAdviceController: Applied updated_at filter to query', [
                'term' => $term
            ]);
        }
        
        // Handle Reference Number filter (cross-table search)
        if ($hasReferenceNumberFilter && !empty($referenceNumberFilterValue)) {
            $term = trim($referenceNumberFilterValue);
            \Log::info('JobAdviceController: Applying reference_number filter', [
                'term' => $term
            ]);
            
            $query->where(function($q) use ($term) {
                // 1. Check job_advices.reference_number
                $q->where('job_advices.reference_number', 'LIKE', "%{$term}%")
                
                // 2. Check contracts.contract_number (via contract_id)
                  ->orWhereHas('contract', function($subQ) use ($term) {
                      $subQ->where('contract_number', 'LIKE', "%{$term}%");
                  })
                  
                // 3. Check quotations.quotation_number (via quotation_id)
                  ->orWhereHas('quotation', function($subQ) use ($term) {
                      $subQ->where('quotation_number', 'LIKE', "%{$term}%");
                  })
                  
                // 4. Also check contract's quotation number for legacy data if needed
                  ->orWhereHas('contract.quotation', function($subQ) use ($term) {
                      $subQ->where('quotation_number', 'LIKE', "%{$term}%");
                  });
            });
        }
        
        // Apply other column filters (excluding manually handled ones)
        // Since we're using withoutGlobalScope('autoFilter'), we need to manually process remaining filters
        // Only process remaining filters if there are any (excluding manually handled ones)
        // IMPORTANT: Don't restore original filters if all filters were manually handled, to prevent AutoFilterable from processing them
        if (!empty($filters)) {
            // Temporarily restore original filters, but keep skip flag so AutoFilterable won't process manually handled ones
            $request->merge(['filter' => $originalFilters]);
            
            // Manually apply AutoFilterable for remaining filters (it will skip manually handled ones via _skip_auto_filter flag)
            $jobAdviceModel = new \App\Models\JobAdvice();
            $query->filter($originalFilters);
            
            // Restore filtered request (without manually handled filters)
            $request->merge(['filter' => $filters]);
        }
        // If all filters were manually handled (filters array is empty), don't call filter() at all
        // This prevents AutoFilterable from processing them
        
        $columnMap = [
            // 'submittedBy.name', 'with_invoicing', 'with_materials', and 'updated_at' are handled manually above
        ];
        $this->applyColumnFilters($query, null, $columnMap);
        
        // Restore original filters after processing (for pagination links, etc.)
        if ($hasSubmittedByFilter || $hasWithInvoicingFilter || $hasWithMaterialsFilter || $hasUpdatedAtFilter || $hasReferenceNumberFilter) {
            $request->merge(['filter' => $originalFilters]);
        }

        // Filter by date (legacy filters - only apply if not using column filters)
        if ($request->filled('start_date') && !$request->has('filter')) {
            $query->whereDate('expected_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date') && !$request->has('filter')) {
            $query->whereDate('expected_date', '<=', $request->end_date);
        }

        // Filter by company name (legacy filters)
        if ($request->filled('company_name') && !$request->has('filter')) {
            $query->where('company_name', 'like', '%' . $request->company_name . '%');
        }

        // Filter by type (legacy filters)
        if ($request->filled('type') && !$request->has('filter')) {
            $query->where('type', $request->type);
        }

        // Filter by status (legacy filters)
        if ($request->filled('status') && !$request->has('filter')) {
            $query->where('status', $request->status);
        }

        // Log final query state before execution
        if ($hasSubmittedByFilter) {
            \Log::info('JobAdviceController: Final query state before pagination', [
                'hasSubmittedByFilter' => $hasSubmittedByFilter,
                'submittedByFilterValue' => $submittedByFilterValue,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
        }

        $jobAdvices = $query->orderBy('created_at', 'desc')->paginateStd(25);
        
        // MOM9: Get selected quotation_id from URL if provided
        $selectedQuotationId = $request->get('quotation_id');

        // MOM14: Get salutation options from master data
        $salutationOption = \App\Models\MasterOption::where('name', 'Salutation')->first();
        $salutations = $salutationOption ? $salutationOption->optionDetails()->where('is_active', true)->pluck('option_name') : collect();
        
        // MOM14: Get position options from master data
        $positionOption = \App\Models\MasterOption::where('name', 'Position')->first();
        $positions = $positionOption ? $positionOption->optionDetails()->where('is_active', true)->pluck('option_name') : collect();

        $pagination = $jobAdvices->toArray();
        return view('marketing.job-advices.index', compact('jobAdvices', 'selectedQuotationId', 'salutations', 'positions', 'pagination'));
    }

    public function create(Request $request)
    {
        // MOM9: Load both active contracts and approved quotations
        $contracts = Contract::with('customer')->where('status', 'active')->get();
        
        // MOM9: Load approved quotations for Install Free flow
        $quotations = \App\Models\Quotation::with('customer', 'prospect')
            ->usable()
            ->where('status', 'approved')
            ->get();
        
        $users = User::active()->get();
        
        // MOM9: If quotation_id is provided, pre-select it
        $selectedQuotationId = $request->get('quotation_id');
        
        // Get current user
        $currentUser = Auth::user();
        
        // Auto-set type to 'Install Free' if quotation_id is provided
        $autoType = $selectedQuotationId ? 'Install Free' : null;
        
        // Return JSON for modal pop-up (not a view page)
        if ($request->expectsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'status' => 'success',
                'contracts' => $contracts,
                'quotations' => $quotations,
                'users' => $users,
                'selected_quotation_id' => $selectedQuotationId,
                'current_user_id' => $currentUser->id,
                'current_user_name' => $currentUser->name,
                'auto_type' => $autoType
            ]);
        }
        
        // If not AJAX request, redirect to index page (where modal will be opened)
        return redirect()->route('marketing.job-advices.index')
            ->with('open_create_modal', true)
            ->with('selected_quotation_id', $selectedQuotationId);
    }

    public function store(Request $request)
    {
        \Log::info('Job Advice Store Payload:', $request->all());

        if ($this->shouldSelectRoomsAfterJobAdviceCreate($request->type)) {
            $request->merge(['rooms' => []]);
        }

        // MOM9: Either contract_id OR quotation_id is required (for Install Free from Quotation)
        $request->validate([
            'contract_id' => 'nullable|exists:contracts,id',
            'quotation_id' => 'nullable|exists:quotations,id',
            'type' => [
                'required',
                \Illuminate\Validation\Rule::in([
                    'Install Free', 'Install', 'Remove', 'Extra', 'Change Unit', 'Change Rental', 'Complain',
                    'install', 'service', 'remove', 'maintenance', 'installation', 'removal', 'install_free', 'complain',
                    'change_rental', 'change'
                ])
            ],
            'reference_number' => 'nullable|string|max:100',
            'request_by' => 'nullable|exists:users,id',
            'customer_contact_id' => 'nullable|exists:customer_contacts,id',
            'expected_date' => 'required|date',
            'first_service_date' => 'nullable|date',
            'remove_date' => [
                'nullable',
                'date',
                'after:expected_date',
                function ($attribute, $value, $fail) use ($request) {
                    // Remove Date is mandatory for Install Free
                    $type = strtolower($request->type ?? '');
                    if (in_array($type, ['install free', 'install_free']) && empty($value)) {
                        $fail('Remove date harus diisi untuk Install Free.');
                    }
                }
            ],
            'status' => 'nullable|in:draft,waiting_for_approval,submitted,approved,rejected,cancelled,completed',
            'with_invoicing' => 'nullable|boolean',
            'with_materials' => 'nullable|boolean',
            'notes' => 'nullable|string',
            
            'rooms' => 'nullable|array',
            'rooms.*.contract_room_id' => 'nullable|exists:contract_rooms,id',
            'rooms.*.quotation_room_id' => 'nullable|exists:quotation_rooms,id',
            'rooms.*.quotation_rental_id' => 'nullable|exists:quotation_rentals,id',
            'rooms.*.quotation_detail_id' => 'nullable|exists:quotation_details,id',
            'rooms.*.rental_product_id' => 'required|exists:master_rentals,id',
            'rooms.*.quantity' => 'nullable|integer|min:0',
            'rooms.*.qty_free' => 'nullable|numeric|min:0',
            'rooms.*.is_trial' => 'nullable|boolean',
            'rooms.*.notes' => 'nullable|string',
        ], [
            'remove_date.after' => 'Remove date harus lebih tinggi dari expected date.',
        ]);

        if (empty($request->contract_id) && empty($request->quotation_id)) {
            return back()->withErrors(['contract_id' => 'Either Contract or Quotation must be selected.'])->withInput();
        }

        if ($dateValidationResponse = $this->validateJobAdviceSourceDate(
            $request,
            $request->contract_id ? (int) $request->contract_id : null,
            $request->quotation_id ? (int) $request->quotation_id : null,
            $request->expected_date
        )) {
            return $dateValidationResponse;
        }

        $lock = Cache::lock('job-advice:create:' . $this->buildJobAdviceCreateLockKey($request), 30);
        if (! $lock->get()) {
            return $this->jobAdviceCreateInProgressResponse($request);
        }

        try {
            DB::beginTransaction();

            $customer = null;
            $customerId = null;
            $companyName = null;
            $referenceNumber = $request->reference_number;
            $contractId = $request->contract_id;
            $quotationId = $request->quotation_id;
            $jaType = strtolower(str_replace(' ', '_', $request->type ?? ''));

            if ($quotationId && !$contractId) {
                $quotation = \App\Models\Quotation::with('customer', 'prospect')->find($quotationId);
                
                if (!$quotation) {
                    return back()->withErrors(['quotation_id' => 'Quotation not found.'])->withInput();
                }

                if ($quotation->status !== 'approved') {
                    if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Only approved quotations can be used for Job Advice.',
                            'errors' => ['quotation_id' => ['Only approved quotations can be used for Job Advice.']]
                        ], 422);
                    }

                    return back()->withErrors(['quotation_id' => 'Only approved quotations can be used for Job Advice.'])->withInput();
                }

                if (!\App\Models\Quotation::usable()->whereKey($quotation->id)->exists()) {
                    if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Quotation ini bukan revisi terakhir. Silakan pilih quotation revisi terbaru.',
                            'errors' => ['quotation_id' => ['Quotation ini bukan revisi terakhir. Silakan pilih quotation revisi terbaru.']]
                        ], 422);
                    }

                    return back()->withErrors(['quotation_id' => 'Quotation ini bukan revisi terakhir. Silakan pilih quotation revisi terbaru.'])->withInput();
                }

                // Get customer from quotation
                $customer = $quotation->customer ?? $quotation->prospect;
                $customerId = $customer->id ?? null;
                $companyName = $quotation->company_name ?? ($customer->name ?? null);
                
                // Auto-set reference_number from quotation if not provided
                if (empty($referenceNumber)) {
                    $referenceNumber = $quotation->quotation_number;
                }

                if (empty($request->type) || strtolower($request->type) === 'install free') {
                    $request->merge(['type' => 'install_free']);
                }
            } 
            // Handle Job Advice from Contract (existing flow)
            else if ($contractId) {
                $contract = Contract::with('customer', 'quotation', 'contractRooms.room')->find($contractId);
                
                if (!$contract) {
                    return back()->withErrors(['contract_id' => 'Contract not found.'])->withInput();
                }

                $customer = $contract->customer;
                $customerId = $contract->customer_id;
                $companyName = $contract->customer->name;
                
                // Extra and Remove Job Advices are contract-based, so keep the visible reference
                // pointing at the Contract (CA) rather than the original Quotation (SQ).
                if (empty($referenceNumber) && ($jaType === 'extra' || str_contains($jaType, 'remove'))) {
                    $referenceNumber = $contract->contract_number;
                }

                // Auto-set reference_number from quotation if not provided
                if (empty($referenceNumber) && $contract->quotation) {
                    $referenceNumber = $contract->quotation->quotation_number;
                }
            }

            if (!$customerId) {
                return back()->withErrors(['customer_id' => 'Customer not found.'])->withInput();
            }

            if ($jaType === 'service') {
                $errorMsg = 'Job Advice type Service sementara dinonaktifkan untuk input manual. Gunakan flow service otomatis dari contract/job schedule.';

                if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errorMsg
                    ], 422);
                }

                return back()->withInput()->withErrors(['type' => $errorMsg]);
            }

            // MOM14: Validation for unfinished jobs before creating JA type Change Rental or Remove
            if ($contractId && (str_contains($jaType, 'remove') || str_contains($jaType, 'change'))) {
                $unfinishedJob = JobSchedule::findBlockingUnfinishedJob($contract->contract_number);

                if ($unfinishedJob) {
                    $errorMsg = "maaf job advice untuk referensi no {$contract->contract_number} tidak dapat di buat karena masih ada pekerjaan bernomor {$unfinishedJob->job_number} yang belum selesai";
                    
                    if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                        return response()->json([
                            'status' => 'error',
                            'message' => $errorMsg
                        ], 422);
                    }
                    
                    return back()->withInput()->with('error', $errorMsg);
                }
            }
            
            $recentDuplicate = $this->findRecentDuplicateJobAdvice(
                $request,
                $contractId,
                $quotationId,
                $customerId,
                $referenceNumber
            );

            if ($recentDuplicate) {
                DB::rollBack();

                return $this->duplicateJobAdviceResponse($request, $recentDuplicate);
            }

            $documentNumberService = new DocumentNumberService();

            // Check if type is Complain to use different prefix (COM)
            $documentType = 'job_advice';
            if (!empty($request->type) && strtolower($request->type) === 'complain') {
                $documentType = 'job_advice_complain';
            }
            
            $jobAdviceNumber = $documentNumberService->generate(
                $documentType,
                null, // Will get from contract/quotation
                null, // Will get from contract/quotation
                $contractId, // Get branch from contract
                $quotationId, // Or get branch from quotation
                null,
                null
            );
            
            $jobAdvice = JobAdvice::create([
                'job_advice_number' => $jobAdviceNumber,
                'contract_id' => $contractId,
                'quotation_id' => $quotationId, // MOM9: For Install Free from Quotation
                'customer_id' => $customerId,
                'company_name' => $companyName,
                'type' => $request->type,
                'reference_number' => $referenceNumber,
                'request_by' => $request->request_by ?? Auth::id(),
                'customer_contact_id' => $request->customer_contact_id,
                'expected_date' => $request->expected_date,
                'first_service_date' => $request->first_service_date,
                'remove_date' => $request->remove_date,
                'status' => $request->status ?? 'draft',
                'with_invoicing' => $request->with_invoicing ?? false,
                'with_materials' => $request->with_materials ?? false,
                'notes' => $request->notes,
                'submitted_by' => Auth::id(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            
            if ($request->has('rooms') && is_array($request->rooms)) {
                $processedRooms = []; // Keep track of processed rooms to prevent duplicates in request

                foreach ($request->rooms as $roomData) {
                    
                    // Generate a unique key for this room entry to check for duplicates within the request
                    $roomKey = ($roomData['contract_room_id'] ?? 'null') . '_' . 
                               ($roomData['quotation_room_id'] ?? 'null') . '_' . 
                               ($roomData['rental_product_id'] ?? 'null') . '_' .
                               ($roomData['quotation_rental_id'] ?? 'null') . '_' .
                               ($roomData['quotation_detail_id'] ?? 'null');

                    if (in_array($roomKey, $processedRooms)) {
                        continue; // Skip duplicate in the same request
                    }
                    $processedRooms[] = $roomKey;

                    if (empty($roomData['contract_room_id']) && empty($roomData['quotation_room_id'])) {
                        \Log::warning("Room data missing both contract_room_id and quotation_room_id", ['room_data' => $roomData]);
                        continue;
                    }

                    if (!empty($roomData['quotation_room_id']) && !empty($roomData['quotation_rental_id'])) {
                        $quotationRental = \App\Models\QuotationRental::whereKey($roomData['quotation_rental_id'])
                            ->where('quotation_room_id', $roomData['quotation_room_id'])
                            ->first();

                        if (!$quotationRental) {
                            \Log::warning("Quotation rental does not belong to selected quotation room", ['room_data' => $roomData]);
                            continue;
                        }

                        $roomData['rental_product_id'] = $quotationRental->master_rental_id;
                        $roomData['quantity'] = $this->sourcePaidQuantity($quotationRental);
                        $roomData['qty_free'] = $quotationRental->qty_free ?? 0;
                    } elseif (!empty($roomData['quotation_room_id']) && !empty($roomData['quotation_detail_id'])) {
                        $quotationRoom = \App\Models\QuotationRoom::with('quotation.quotationDetails.room')->find($roomData['quotation_room_id']);
                        $quotationDetail = $this->resolveQuotationDetailForRoom($quotationRoom, (int) $roomData['quotation_detail_id']);

                        if (!$quotationDetail) {
                            \Log::warning("Quotation detail does not belong to selected quotation room", ['room_data' => $roomData]);
                            continue;
                        }

                        $roomData['rental_product_id'] = $quotationDetail->master_rental_id;
                        $roomData['quantity'] = $this->sourcePaidQuantity($quotationDetail);
                        $roomData['qty_free'] = $quotationDetail->qty_free ?? 0;
                    }

                    $this->createJobAdviceRoom($jobAdvice, $roomData);
                }
            }

            if ($jobAdvice->status === 'approved') {
                $this->createJobSchedulesFromJobAdvice($jobAdvice);
            }

            DB::commit();

            // Check if request expects JSON response
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Job Advice berhasil dibuat.',
                    'data' => $jobAdvice->load(['contract', 'customer'])
                ]);
            }

            return redirect()->route('marketing.job-advices.show', $jobAdvice)
                ->with('success', 'Job Advice berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Check if request expects JSON response
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        } finally {
            optional($lock)->release();
        }
    }

    private function buildJobAdviceCreateLockKey(Request $request): string
    {
        $payload = [
            'user_id' => Auth::id(),
            'contract_id' => $request->contract_id,
            'quotation_id' => $request->quotation_id,
            'type' => strtolower(trim((string) $request->type)),
            'request_by' => $request->request_by ?? Auth::id(),
            'customer_contact_id' => $request->customer_contact_id,
            'expected_date' => $request->expected_date,
            'remove_date' => $request->remove_date,
            'rooms' => $this->normalizeJobAdviceRequestRooms($request->input('rooms', [])),
        ];

        return sha1(json_encode($payload));
    }

    private function findRecentDuplicateJobAdvice(
        Request $request,
        $contractId,
        $quotationId,
        $customerId,
        ?string $referenceNumber
    ): ?JobAdvice {
        $requestRooms = $this->normalizeJobAdviceRequestRooms($request->input('rooms', []));

        $candidates = JobAdvice::with('rooms')
            ->where('created_by', Auth::id())
            ->where('customer_id', $customerId)
            ->where('type', $request->type)
            ->where('request_by', $request->request_by ?? Auth::id())
            ->whereDate('expected_date', $request->expected_date)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->whereIn('status', ['draft', 'waiting_for_approval', 'submitted'])
            ->when($referenceNumber, fn ($query) => $query->where('reference_number', $referenceNumber), fn ($query) => $query->whereNull('reference_number'))
            ->when($request->filled('customer_contact_id'), fn ($query) => $query->where('customer_contact_id', $request->customer_contact_id), fn ($query) => $query->whereNull('customer_contact_id'))
            ->when($request->filled('remove_date'), fn ($query) => $query->whereDate('remove_date', $request->remove_date), fn ($query) => $query->whereNull('remove_date'))
            ->when($contractId, fn ($query) => $query->where('contract_id', $contractId), fn ($query) => $query->whereNull('contract_id'))
            ->when($quotationId, fn ($query) => $query->where('quotation_id', $quotationId), fn ($query) => $query->whereNull('quotation_id'))
            ->latest('id')
            ->get();

        return $candidates->first(function (JobAdvice $jobAdvice) use ($requestRooms) {
            return $this->normalizeExistingJobAdviceRooms($jobAdvice) === $requestRooms;
        });
    }

    private function normalizeJobAdviceRequestRooms($rooms): array
    {
        return collect(is_array($rooms) ? $rooms : [])
            ->map(function ($room) {
                return [
                    'contract_room_id' => $this->nullableInt($room['contract_room_id'] ?? null),
                    'quotation_room_id' => $this->nullableInt($room['quotation_room_id'] ?? null),
                    'quotation_rental_id' => $this->nullableInt($room['quotation_rental_id'] ?? null),
                    'quotation_detail_id' => $this->nullableInt($room['quotation_detail_id'] ?? null),
                    'rental_product_id' => $this->nullableInt($room['rental_product_id'] ?? null),
                    'quantity' => (int) ($room['quantity'] ?? 1),
                    'qty_free' => (float) ($room['qty_free'] ?? 0),
                ];
            })
            ->sortBy(fn ($room) => implode('|', array_map(fn ($value) => (string) ($value ?? ''), $room)))
            ->values()
            ->all();
    }

    private function normalizeExistingJobAdviceRooms(JobAdvice $jobAdvice): array
    {
        return $jobAdvice->rooms
            ->map(function ($room) {
                return [
                    'contract_room_id' => $this->nullableInt($room->contract_room_id),
                    'quotation_room_id' => $this->nullableInt($room->quotation_room_id),
                    'quotation_rental_id' => $this->nullableInt($room->quotation_rental_id),
                    'quotation_detail_id' => $this->nullableInt($room->quotation_detail_id),
                    'rental_product_id' => $this->nullableInt($room->rental_product_id),
                    'quantity' => (int) ($room->quantity ?? 1),
                    'qty_free' => (float) ($room->qty_free ?? 0),
                ];
            })
            ->sortBy(fn ($room) => implode('|', array_map(fn ($value) => (string) ($value ?? ''), $room)))
            ->values()
            ->all();
    }

    private function nullableInt($value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function sourcePaidQuantity($source): float
    {
        if (is_array($source)) {
            return max(0, (float) ($source['quantity'] ?? 0));
        }

        return max(0, (float) ($source->quantity ?? 0));
    }

    private function sourceFreeQuantity($source): float
    {
        if (is_array($source)) {
            return max(0, (float) ($source['qty_free'] ?? 0));
        }

        return max(0, (float) ($source->qty_free ?? 0));
    }

    private function operationalQuantity($source): int
    {
        return max(1, (int) ceil($this->sourcePaidQuantity($source) + $this->sourceFreeQuantity($source)));
    }

    private function normalizedJobAdviceType($type): string
    {
        return str_replace(' ', '_', strtolower(trim((string) $type)));
    }

    private function shouldSelectRoomsAfterJobAdviceCreate($type): bool
    {
        return in_array($this->normalizedJobAdviceType($type), ['install', 'install_free'], true);
    }

    private function duplicateJobAdviceResponse(Request $request, JobAdvice $jobAdvice)
    {
        $jobAdvice->loadMissing(['contract', 'customer']);

        if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'status' => 'success',
                'message' => 'Job Advice sudah dibuat dari request yang sama.',
                'data' => $jobAdvice,
                'duplicate_prevented' => true,
            ]);
        }

        return redirect()->route('marketing.job-advices.show', $jobAdvice)
            ->with('success', 'Job Advice sudah dibuat dari request yang sama.');
    }

    private function jobAdviceCreateInProgressResponse(Request $request)
    {
        $message = 'Request pembuatan Job Advice sedang diproses. Mohon tunggu sebentar.';

        if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'status' => 'error',
                'message' => $message,
            ], 409);
        }

        return back()->withInput()->with('error', $message);
    }

    public function show($id)
    {
        $jobAdvice = JobAdvice::with([
            'contract.customer', 
            'contract.quotation', 
            'contract.contractRentals',
            'quotation.customer', 
            'quotation.prospect',
            'quotation.quotationDetails',
            'quotation.quotationRooms',
            'quotation.survey.building',
            'quotation.survey.surveyDetails',
            'submittedBy', 
            'approvedBy', 
            'creator',
            'updater',
            'requestedBy',
            'customerContact',
            'rooms.rentalProduct',
            'rooms.contractRental',
            'rooms.quotationRental',
            'rooms.quotationDetail',
            'rooms.contractRoom.room.building',
            'rooms.quotationRoom.room.building',
            'contract.quotation.survey.building', // Load building from survey
            'contract.quotation.quotationRooms.room',
            'contract.quotation.quotationRooms.aromaProduct', // Load aroma product for quotation rooms
            'contract.quotation.quotationDetails', // Load quotation details for rental_alias
            'contract.contractRentals', // Load contract rentals for rental_alias
            'rooms.contractRoom.contract.quotation.survey.building', // For building accessor
        ])->findOrFail($id);
        
        // Quotation relationships are already loaded via with() above
        
        // Return view for web request
        if (request()->wantsJson()) {
            return response()->json($this->formatJobAdviceForJson($jobAdvice));
        }
        
        return view('marketing.job-advices.show', compact('jobAdvice'));
    }

    public function edit(JobAdvice $jobAdvice)
    {
        // Keep date-only fields as calendar dates so browser timezone conversion cannot move them.
        return response()->json($this->formatJobAdviceForJson($jobAdvice));
    }

    private function formatJobAdviceForJson(JobAdvice $jobAdvice): array
    {
        $payload = $jobAdvice->toArray();

        foreach (['expected_date', 'first_service_date', 'remove_date'] as $field) {
            $payload[$field] = $jobAdvice->{$field}
                ? $jobAdvice->{$field}->format('Y-m-d')
                : null;
        }

        return $payload;
    }

    public function update(Request $request, JobAdvice $jobAdvice)
    {
        // Only validate editable fields (expected_date, remove_date, notes)
        $request->validate([
            'expected_date' => 'required|date',
            'remove_date' => [
                'nullable',
                'date',
                'after:expected_date',
                function ($attribute, $value, $fail) use ($jobAdvice) {
                    // Remove Date is mandatory for Install Free
                    $type = strtolower($jobAdvice->type ?? '');
                    if (in_array($type, ['install free', 'install_free']) && empty($value)) {
                        $fail('Remove date harus diisi untuk Install Free.');
                    }
                }
            ],
            'notes' => 'nullable|string',
        ], [
            'remove_date.after' => 'Remove date harus lebih tinggi dari expected date.',
        ]);

        if ($dateValidationResponse = $this->validateJobAdviceSourceDate(
            $request,
            $jobAdvice->contract_id ? (int) $jobAdvice->contract_id : null,
            $jobAdvice->quotation_id ? (int) $jobAdvice->quotation_id : null,
            $request->expected_date
        )) {
            return $dateValidationResponse;
        }

        try {
            DB::beginTransaction();

            // Only update the 3 editable fields as per JA.md requirements
            $jobAdvice->update([
                'expected_date' => $request->expected_date,
                'remove_date' => $request->remove_date,
                'notes' => $request->notes,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            // Check if request expects JSON response
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Job Advice berhasil diperbarui.',
                    'data' => $jobAdvice->load(['contract', 'customer'])
                ]);
            }

            return redirect()->route('marketing.job-advices.show', $jobAdvice)
                ->with('success', 'Job Advice berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Check if request expects JSON response
            if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(JobAdvice $jobAdvice)
    {
        try {
            // Allow deletion for both cancelled and draft statuses
            if (!in_array($jobAdvice->status, ['cancelled', 'draft'])) {
                return back()->with('error', 'Hanya Job Advice dengan status Cancelled atau Draft yang bisa dihapus.');
            }

            DB::beginTransaction();
            
            // Cascade delete: Get all Job Schedules linked to this Job Advice
            $jobSchedules = \App\Models\JobSchedule::where('job_advice_id', $jobAdvice->id)->get();
            $deletedJobScheduleCount = $jobSchedules->count();
            
            foreach ($jobSchedules as $jobSchedule) {
                // First delete JobScheduleRoom records
                \App\Models\JobScheduleRoom::where('job_schedule_id', $jobSchedule->id)->delete();
                // Also delete JobScheduleRoomAssignment records if exists
                \App\Models\JobScheduleRoomAssignment::where('job_schedule_id', $jobSchedule->id)->delete();
                // Then delete the JobSchedule
                $jobSchedule->delete();
            }
            
            if ($deletedJobScheduleCount > 0) {
                \Log::info("ðŸ—‘ï¸ Cascade deleted {$deletedJobScheduleCount} Job Schedule(s) for Job Advice: {$jobAdvice->job_advice_number}");
            }
            
            // Delete Job Advice Rooms
            $jobAdvice->rooms()->delete();
            
            // Delete Job Advice
            $jobAdvice->delete();
            
            DB::commit();
            
            return redirect()->route('marketing.job-advices.index')
                ->with('success', "Job Advice dan {$deletedJobScheduleCount} Job Schedule terkait berhasil dihapus.");
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("âŒ Failed to delete Job Advice {$jobAdvice->job_advice_number}: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function removeRoom(JobAdvice $jobAdvice, JobAdviceRoom $room)
    {
        try {
            // Check if status is draft
            if ($jobAdvice->status !== 'draft') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya Job Advice dengan status Draft yang bisa dihapus roomnya.'
                ], 422);
            }

            // MOM9: Grouped deletion support
            // If the room being deleted has a source room ID, delete all rooms sharing that ID in this JA
            if ($room->contract_room_id) {
                \App\Models\JobAdviceRoom::where('job_advice_id', $jobAdvice->id)
                    ->where('contract_room_id', $room->contract_room_id)
                    ->delete();
                $message = 'Grup Ruangan (Contract) berhasil dihapus.';
            } elseif ($room->quotation_room_id) {
                \App\Models\JobAdviceRoom::where('job_advice_id', $jobAdvice->id)
                    ->where('quotation_room_id', $room->quotation_room_id)
                    ->delete();
                $message = 'Grup Ruangan (Quotation) berhasil dihapus.';
            } else {
                // Fallback to single delete or same room name
                \App\Models\JobAdviceRoom::where('job_advice_id', $jobAdvice->id)
                    ->where('room_name', $room->room_name)
                    ->delete();
                $message = 'Ruangan berhasil dihapus.';
            }

            return response()->json([
                'status' => 'success',
                'message' => $message
            ]);

        } catch (\Exception $e) {
            \Log::error('Error removing job advice room: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus room.'
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:job_advices,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Fetch the Job Advices to check statuses
            $jobAdvices = JobAdvice::whereIn('id', $request->ids)->get();
            
            // Strict check: Ensure ALL selected items are cancelled or draft
            $invalidCount = $jobAdvices->filter(function ($ja) {
                return !in_array($ja->status, ['cancelled', 'draft']);
            })->count();
            
            if ($invalidCount > 0) {
                 return response()->json([
                    'success' => false,
                    'message' => "Gagal menghapus! Terdapat {$invalidCount} Job Advice yang tidak bisa dihapus. Hanya status Cancelled atau Draft yang boleh dihapus."
                ], 422);
            }

            $count = JobAdvice::whereIn('id', $request->ids)->delete();
            
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

    public function finalize(Request $request, JobAdvice $jobAdvice)
    {
        try {
            // Check if status is draft
            if ($jobAdvice->status !== 'draft') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Hanya Job Advice dengan status Draft yang bisa di-finalize.'
                    ], 422);
                }
                return back()->with('error', 'Hanya Job Advice dengan status Draft yang bisa di-finalize.');
            }

            if ($dateValidationResponse = $this->validateJobAdviceSourceDate(
                $request,
                $jobAdvice->contract_id ? (int) $jobAdvice->contract_id : null,
                $jobAdvice->quotation_id ? (int) $jobAdvice->quotation_id : null,
                $jobAdvice->expected_date
            )) {
                return $dateValidationResponse;
            }

            $jobAdvice->update([
                'status' => 'waiting_for_approval',
                'submitted_by' => $request->submitted_by ?? Auth::id(),
                'submitted_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Job Advice telah difinalize dan menunggu approval.'
                ]);
            }

            return back()->with('success', 'Job Advice telah difinalize dan menunggu approval.');
        } catch (\Exception $e) {
            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // Keep submitForApproval as alias for backward compatibility
    public function submitForApproval(Request $request, JobAdvice $jobAdvice)
    {
        return $this->finalize($request, $jobAdvice);
    }

    public function approve(Request $request, JobAdvice $jobAdvice)
    {
        try {
            // Check if status is waiting_for_approval
            if ($jobAdvice->status !== 'waiting_for_approval') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Hanya Job Advice dengan status Waiting for Approval yang bisa disetujui.'
                    ], 422);
                }
                return back()->with('error', 'Hanya Job Advice dengan status Waiting for Approval yang bisa disetujui.');
            }

            // Check approval permission
            if (!Auth::user()->canApprove('job_advices')) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Anda tidak memiliki izin untuk menyetujui Job Advice.'
                    ], 403);
                }
                return back()->with('error', 'Anda tidak memiliki izin untuk menyetujui Job Advice.');
            }

            if ($dateValidationResponse = $this->validateJobAdviceSourceDate(
                $request,
                $jobAdvice->contract_id ? (int) $jobAdvice->contract_id : null,
                $jobAdvice->quotation_id ? (int) $jobAdvice->quotation_id : null,
                $jobAdvice->expected_date
            )) {
                return $dateValidationResponse;
            }

            DB::transaction(function () use ($jobAdvice) {
                $jobAdvice->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id(),
                    'date_approval' => now(),
                    'updated_by' => Auth::id(),
                ]);

                $jobAdvice->refresh();

                // Create Job Schedules atomically with approval. If schedule creation fails,
                // the approval is rolled back so we never leave an Approved JA without JS.
                $this->createJobSchedulesFromJobAdvice($jobAdvice);
            });

            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Job Advice telah disetujui dan Job Schedule telah dibuat.'
                ]);
            }

            return back()->with('success', 'Job Advice telah disetujui dan Job Schedule telah dibuat.');
        } catch (\Exception $e) {
            \Log::error("Failed to approve Job Advice {$jobAdvice->job_advice_number}: " . $e->getMessage(), [
                'job_advice_id' => $jobAdvice->id,
                'trace' => $e->getTraceAsString(),
            ]);

            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function revertToDraft(Request $request, JobAdvice $jobAdvice)
    {
        try {
            // Check if status is waiting_for_approval
            if ($jobAdvice->status !== 'waiting_for_approval') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Hanya Job Advice dengan status Waiting for Approval yang bisa dikembalikan ke Draft.'
                    ], 422);
                }
                return back()->with('error', 'Hanya Job Advice dengan status Waiting for Approval yang bisa dikembalikan ke Draft.');
            }

            $jobAdvice->update([
                'status' => 'draft',
                'updated_by' => Auth::id(),
            ]);

            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Job Advice berhasil dikembalikan ke Draft.'
                ]);
            }

            return back()->with('success', 'Job Advice berhasil dikembalikan ke Draft.');
        } catch (\Exception $e) {
            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function cancelRequest(Request $request, JobAdvice $jobAdvice)
    {
        try {
            DB::beginTransaction();

            // Load job schedules to check status
            $jobSchedules = \App\Models\JobSchedule::where('job_advice_id', $jobAdvice->id)->get();
            
            // Verify ALL job schedules are in 'new_job' or 'scheduled' status
            // If any schedule is already 'assign_team' or further, CANNOT cancel
            foreach ($jobSchedules as $schedule) {
                if (!in_array($schedule->status, ['new_job', 'scheduled'])) {
                    throw new \Exception("Job sudah dalam tahap yg lanjut dan tidak dapat dibatalkan");
                }
            }

            // Delete all associated Job Schedules
            foreach ($jobSchedules as $jobSchedule) {
                // Delete related room data first
                \App\Models\JobScheduleRoom::where('job_schedule_id', $jobSchedule->id)->delete();
                \App\Models\JobScheduleRoomAssignment::where('job_schedule_id', $jobSchedule->id)->delete();
                $jobSchedule->delete();
            }

            // Update Job Advice status to cancelled
            $jobAdvice->update([
                'status' => 'cancelled',
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => "Success Cancel {$jobAdvice->job_advice_number} Cancelled"
                ]);
            }

            return back()->with('success', "Success Cancel {$jobAdvice->job_advice_number} Cancelled");

        } catch (\Exception $e) {
            DB::rollback();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Unpost Job Advice - Revert approved status back to draft
     * Only works when all associated job schedules are in new_job or scheduled status
     */
    public function unpost(Request $request, JobAdvice $jobAdvice)
    {
        if (!Auth::user()->canApprove('job_advices')) {
            $message = 'Anda tidak memiliki akses untuk unpost Job Advice. Pastikan role Anda memiliki permission "Approve" untuk Job Advices.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $message], 403);
            }
            return back()->with('error', $message);
        }
        try {
            // Check if status is approved
            if ($jobAdvice->status !== 'approved') {
                $message = 'Hanya Job Advice dengan status Approved yang bisa di-unpost.';
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['status' => 'error', 'message' => $message], 422);
                }
                return back()->with('error', $message);
            }

            DB::beginTransaction();

            // Load job schedules to check status
            $jobSchedules = \App\Models\JobSchedule::where('job_advice_id', $jobAdvice->id)->get();
            
            // Verify ALL job schedules are in 'new_job' or 'scheduled' status
            foreach ($jobSchedules as $schedule) {
                if (!in_array($schedule->status, ['new_job', 'scheduled'])) {
                    throw new \Exception("Tidak dapat unpost karena ada Job Schedule yang sudah dalam proses (status: {$schedule->status})");
                }
            }

            // Delete all associated Job Schedules
            $deletedCount = $jobSchedules->count();
            foreach ($jobSchedules as $jobSchedule) {
                // Delete related room data first
                \App\Models\JobScheduleRoom::where('job_schedule_id', $jobSchedule->id)->delete();
                \App\Models\JobScheduleRoomAssignment::where('job_schedule_id', $jobSchedule->id)->delete();
                $jobSchedule->delete();
            }

            // Revert Job Advice status to draft (not cancelled)
            $jobAdvice->update([
                'status' => 'draft',
                'approved_by' => null,
                'date_approval' => null,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            $message = "Job Advice {$jobAdvice->job_advice_number} berhasil di-unpost ke Draft. {$deletedCount} Job Schedule dihapus.";
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'success', 'message' => $message]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, JobAdvice $jobAdvice)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:1000',
        ]);

        try {
            // Check if status is waiting_for_approval
            if ($jobAdvice->status !== 'waiting_for_approval') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Hanya Job Advice dengan status Waiting for Approval yang bisa dibatalkan.'
                    ], 422);
                }
                return back()->with('error', 'Hanya Job Advice dengan status Waiting for Approval yang bisa dibatalkan.');
            }

            // Check if user has permission to cancel (using same permission as approve)
            if (!Auth::user()->canApprove('job_advices')) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Anda tidak memiliki akses untuk membatalkan Job Advice. Pastikan role Anda memiliki permission "Approve" untuk Job Advices.'
                    ], 403);
                }
                return back()->with('error', 'Anda tidak memiliki akses untuk membatalkan Job Advice.');
            }

            $jobAdvice->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Job Advice berhasil dibatalkan.'
                ]);
            }

            return back()->with('success', 'Job Advice berhasil dibatalkan.');
        } catch (\Exception $e) {
            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, JobAdvice $jobAdvice)
    {
        if (!Auth::user()->canApprove('job_advices')) {
            $message = 'Anda tidak memiliki akses untuk reject Job Advice. Pastikan role Anda memiliki permission "Approve" untuk Job Advices.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => 'error', 'message' => $message], 403);
            }
            return back()->with('error', $message);
        }
        $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        try {
            $jobAdvice->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
                'rejected_by' => $request->rejected_by ?? Auth::id(),
                'rejected_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Job Advice berhasil ditolak.'
                ]);
            }

            return back()->with('success', 'Job Advice berhasil ditolak.');
        } catch (\Exception $e) {
            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function getIneligibleServiceRooms(array $roomsData, int $contractId)
    {
        if (empty($roomsData)) {
            return collect(['Pilih minimal satu room']);
        }

        return collect($roomsData)
            ->map(function ($roomData) use ($contractId) {
                $contractRoomId = $roomData['contract_room_id'] ?? null;
                if (!$contractRoomId) {
                    return 'Room tanpa contract';
                }

                $contractRoom = ContractRoom::with('room')
                    ->where('contract_id', $contractId)
                    ->find($contractRoomId);

                if (!$contractRoom || !$contractRoom->room) {
                    return 'Contract room ID ' . $contractRoomId;
                }

                if ($this->isServiceRoomEligible($contractRoom, $contractId)) {
                    return null;
                }

                return $contractRoom->room->room_name ?? ('Contract room ID ' . $contractRoomId);
            })
            ->filter()
            ->values();
    }

    private function isServiceRoomEligible(ContractRoom $contractRoom, int $contractId): bool
    {
        $roomId = $contractRoom->room_id;

        $hasUnitOnWall = UnitOnWall::where('room_id', $roomId)
            ->whereIn('status', $this->activeUnitOnWallStatuses())
            ->exists();

        if ($hasUnitOnWall) {
            return true;
        }

        return JobSchedule::whereIn('type', ['install', 'installation'])
            ->whereIn('status', ['completed', 'done_job'])
            ->where(function ($query) use ($contractId, $contractRoom, $roomId) {
                $query->where(function ($q) use ($contractId, $contractRoom) {
                    $q->whereHas('jobAdvice', function ($jaQuery) use ($contractId) {
                            $jaQuery->where('contract_id', $contractId);
                        })
                        ->whereHas('jobAdvice.rooms', function ($roomQuery) use ($contractRoom) {
                            $roomQuery->where('contract_room_id', $contractRoom->id);
                        });
                })
                ->orWhere('room_id', $roomId);
            })
            ->exists();
    }

    private function activeUnitOnWallStatuses(): array
    {
        return ['active', 'installed', 'on_wall', 'on wall', 'onwall'];
    }

    private function resolveQuotationDetailForRoom($quotationRoom, int $quotationDetailId)
    {
        if (!$quotationRoom) {
            return null;
        }

        return $this->resolveQuotationDetailsForRoom($quotationRoom)
            ->firstWhere('id', $quotationDetailId);
    }

    /**
     * Resolve legacy quotation_details for one quotation room without matching by
     * room name alone when the same name exists in several buildings.
     */
    private function resolveQuotationDetailsForRoom($quotationRoom)
    {
        if (!$quotationRoom) {
            return collect();
        }

        $quotationRoom->loadMissing('quotation.quotationRooms', 'quotation.quotationDetails.room');
        $quotation = $quotationRoom->quotation;

        if (!$quotation) {
            return collect();
        }

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

        if ($sameNameRooms->count() === 1) {
            return $details->filter(function ($detail) use ($targetName) {
                return $this->normalizeRoomNameForFallback($detail->getRawOriginal('room_name') ?? $detail->room_name) === $targetName;
            })->values();
        }

        \Log::warning('Skipped ambiguous quotation detail fallback for Job Advice room creation', [
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
     * MOM6: Create Job Advice Room
     * MOM9: Support both contract rooms and quotation rooms
     */
    private function createJobAdviceRoom(JobAdvice $jobAdvice, array $roomData)
    {
        $contractRoom = null;
        $quotationRoom = null;
        $room = null;
        $roomName = null;
        
        // Tracking source IDs
        $contractRentalId = $roomData['contract_rental_id'] ?? null;
        $quotationRentalId = $roomData['quotation_rental_id'] ?? null;
        $quotationDetailId = $roomData['quotation_detail_id'] ?? null;
        $paidQuantity = max(0, (int) ceil((float) ($roomData['quantity'] ?? 1)));
        $qtyFree = max(0, (float) ($roomData['qty_free'] ?? 0));

        // MOM9: Handle both contract room and quotation room
        if (!empty($roomData['contract_room_id'])) {
            $contractRoom = \App\Models\ContractRoom::with('room')->find($roomData['contract_room_id']);
            if ($contractRoom) {
                $room = $contractRoom->room;
                $roomName = $contractRoom->room_name ?? ($room->room_name ?? 'Room ' . $contractRoom->id);
            }
        } elseif (!empty($roomData['quotation_room_id'])) {
            $quotationRoom = \App\Models\QuotationRoom::with('room')->find($roomData['quotation_room_id']);
            if ($quotationRoom) {
                $room = $quotationRoom->room;
                $roomName = $quotationRoom->room_name ?? ($room->room_name ?? 'Room ' . $quotationRoom->id);
            }
        }
        
        $rental = \App\Models\MasterRental::with(['rentalDetails.masterProduct.packagingSize'])->find($roomData['rental_product_id']);
        
        if ((!$contractRoom && !$quotationRoom) || !$rental) {
            \Log::error("Invalid room or rental for Job Advice {$jobAdvice->job_advice_number}", [
                'contract_room_id' => $roomData['contract_room_id'] ?? null,
                'quotation_room_id' => $roomData['quotation_room_id'] ?? null,
                'rental_product_id' => $roomData['rental_product_id'] ?? null
            ]);
            return null;
        }
        
        // MOM9: Get rental_alias from quotation detail if job advice is from quotation
        $rentalAlias = null;
        $quotationDetail = null;
        if ($jobAdvice->quotation_id && $quotationRoom) {
            if ($quotationDetailId) {
                $quotationDetail = $this->resolveQuotationDetailForRoom($quotationRoom, (int) $quotationDetailId);
            }

            // Try to find quotation detail by exact room source before falling back
            // to name-only matching. Name-only is unsafe for repeated names like Lobby.
            if (!$quotationDetail) {
                $quotationDetail = $this->resolveQuotationDetailsForRoom($quotationRoom)
                    ->firstWhere('master_rental_id', $rental->id);
            }
            
            if ($quotationDetail && $quotationDetail->rental_alias) {
                $rentalAlias = $quotationDetail->rental_alias;
            }
        } elseif ($jobAdvice->contract_id && $contractRoom) {
            // For contract-based, try from contract rental or quotation detail
            $contractRental = \App\Models\ContractRental::where('contract_id', $jobAdvice->contract_id)
                ->where('master_rental_id', $rental->id)
                ->where('room_id', $room->id ?? null)
                ->first();
            
            if ($contractRental && $contractRental->rental_alias) {
                $rentalAlias = $contractRental->rental_alias;
            } elseif ($jobAdvice->contract->quotation) {
                $quotationDetail = \App\Models\QuotationDetail::where('quotation_id', $jobAdvice->contract->quotation_id)
                    ->where('master_rental_id', $rental->id)
                    ->where('room_name', $roomName)
                    ->first();
                
                if ($quotationDetail && $quotationDetail->rental_alias) {
                    $rentalAlias = $quotationDetail->rental_alias;
                }
            }
        }
        
        // Use rental_alias if available, otherwise use default rental name
        $finalRentalName = $rentalAlias ?? ($rental->rental_name ?? $rental->name ?? 'N/A');
        
        // MOM6: Calculate rental specification (total ML) from rental details
        // Formula: quantity Ã— package size (ml) = total ML
        // Contoh: quantity = 1, package size = 500ml â†’ total = 500ml
        // Contoh: quantity = 2, package size = 250ml â†’ total = 500ml
        $rentalSpecML = 0;
        foreach ($rental->rentalDetails as $detail) {
            if ($detail->masterProduct && $detail->masterProduct->packagingSize) {
                // Extract number from package size (e.g., "500ml" â†’ 500)
                $packageSizeName = $detail->masterProduct->packagingSize->name;
                preg_match('/(\d+)/', $packageSizeName, $matches);
                $packageSizeML = isset($matches[1]) ? (float)$matches[1] : 0;
                
                // Calculate: quantity Ã— package size (ml)
                $detailML = ($detail->quantity ?? 0) * $packageSizeML;
                $rentalSpecML += $detailML;
                
                \Log::info("Rental Detail ML calculation: Product '{$detail->masterProduct->name}', Quantity: {$detail->quantity}, Package Size: {$packageSizeName} ({$packageSizeML}ml), Detail ML: {$detailML}ml");
            }
        }
        
        \Log::info("Total Rental Specification ML for rental '{$rental->rental_name}': {$rentalSpecML}ml");
        
        // Note: rental_has_installation and rental_has_service are determined by Job Advice type, not Master Rental
        
        // MOM6: Check if unit already installed (from trial or previous install)
        // "setelah dari contract kita balik ke JA lagi untuk ngecek. system akan ngecek yang mana saja yang sudah terinstal"
        $unitAlreadyInstalled = false;
        $existingUnitId = null;
        
        // Check if unit already installed (status 'active' or 'installed')
        // UnitOnWall uses room_id, so we need to get room_id from contractRoom or quotationRoom
        $roomId = $room->id ?? null;
        if ($roomId) {
            $existingUnit = \App\Models\UnitOnWall::where('room_id', $roomId)
                ->whereIn('status', $this->activeUnitOnWallStatuses())
                ->first();
            
            if ($existingUnit) {
                if ($this->hasCompletedInstallSourceForJobAdviceRoom($jobAdvice, $contractRoom, $quotationRoom, $room, $existingUnit)) {
                    $unitAlreadyInstalled = true;
                    $existingUnitId = $existingUnit->id;
                    $roomSource = $contractRoom ? "contract room {$contractRoom->id}" : "quotation room {$quotationRoom->id}";
                    \Log::info("Unit already installed for {$roomSource} (Room ID: {$roomId}, Unit On Wall ID: {$existingUnit->id}, Status: {$existingUnit->status})");
                } else {
                    \Log::info('Ignoring active Unit On Wall for Job Advice room because no completed install source belongs to this Job Advice source.', [
                        'job_advice_number' => $jobAdvice->job_advice_number,
                        'contract_id' => $jobAdvice->contract_id,
                        'quotation_id' => $jobAdvice->quotation_id,
                        'room_id' => $roomId,
                        'unit_on_wall_id' => $existingUnit->id,
                    ]);
                }
            }
        }

        if ($unitAlreadyInstalled && in_array(strtolower((string) $jobAdvice->type), ['install'], true)) {
            \Log::info("Job Advice install room already has active Unit On Wall; keeping JA room for first service/check while skipping duplicate install schedule.", [
                'job_advice_number' => $jobAdvice->job_advice_number,
                'contract_room_id' => $contractRoom->id ?? null,
                'quotation_room_id' => $quotationRoom->id ?? null,
                'room_id' => $roomId,
                'unit_on_wall_id' => $existingUnitId,
            ]);
        }
        
        // Also check if there's completed install job schedule for this room from this contract
        if (!$unitAlreadyInstalled && $jobAdvice->contract_id && $contractRoom) {
            $completedInstallJob = \App\Models\JobSchedule::whereHas('jobAdvice', function($q) use ($jobAdvice) {
                    $q->where('contract_id', $jobAdvice->contract_id);
                })
                ->whereHas('jobAdvice.rooms', function($q) use ($contractRoom) {
                    $q->where('contract_room_id', $contractRoom->id);
                })
                ->where('type', 'install')
                ->where('status', 'completed')
                ->first();
            
            if ($completedInstallJob) {
                $unitAlreadyInstalled = true;
                \Log::info("Install job already completed for room {$contractRoom->id} (Job Schedule: {$completedInstallJob->job_number})");
            }
        }
        
        $payload = [
            'job_advice_id' => $jobAdvice->id,
            'contract_room_id' => $contractRoom->id ?? null,
            'quotation_room_id' => $quotationRoom->id ?? null,
            'contract_rental_id' => $contractRentalId,
            'quotation_rental_id' => $quotationRentalId,
            'quotation_detail_id' => $quotationDetail?->id ?? $quotationDetailId,
            'rental_product_id' => $rental->id,
            'room_name' => $roomName,
            'rental_name' => $finalRentalName, // MOM9: Use rental_alias from quotation detail if available
            'quantity' => $paidQuantity,
            'rental_specification_ml' => $rentalSpecML,
            // rental_has_installation and rental_has_service will be determined based on Job Advice type
            // Install Free â†’ no service (only install)
            // Install â†’ has service (install + service first)
            'rental_has_installation' => true, // Default: all rentals need installation
            'rental_has_service' => false, // Will be updated based on Job Advice type in createJobSchedulesFromJobAdvice
            'status' => 'pending',
            'is_trial' => $roomData['is_trial'] ?? false,
            'unit_already_installed' => $unitAlreadyInstalled,
            'existing_unit_on_wall_id' => $existingUnitId,
            // Note: unit_already_removed is not a field in job_advice_rooms, but we check it in createJobSchedulesFromJobAdvice
            'notes' => $roomData['notes'] ?? null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('job_advice_rooms', 'qty_free')) {
            $payload['qty_free'] = $qtyFree;
        }

        $jaRoom = \App\Models\JobAdviceRoom::create($payload);
        
        return $jaRoom;
    }

    /**
     * MOM6: Auto-create JobSchedule(s) from JobAdvice
     * 
     * This method implements MOM6 requirements:
     * 1. Create multiple job schedules based on rental specification
     * 2. Skip install if unit already installed from trial
     * 3. Create separate install and service jobs if needed
     */
    private function splitRoomsByRentalJobFlow($rooms): array
    {
        $installRooms = collect();
        $serviceRooms = collect();
        $checkRooms = collect();

        foreach ($rooms as $room) {
            $flow = $this->determineRentalJobFlow($room);

            if ($flow['needs_install']) {
                $installRooms->push($room);
            }

            if ($flow['needs_service']) {
                $serviceRooms->push($room);
            }

            if ($flow['needs_check']) {
                $checkRooms->push($room);
            }
        }

        return [
            'install' => $installRooms,
            'service' => $serviceRooms,
            'check' => $checkRooms,
        ];
    }

    private function determineRentalJobFlow($jaRoom): array
    {
        $rental = $jaRoom->rentalProduct;
        $rentalType = strtolower(trim((string) ($rental?->rental_type ?? '')));

        if ($rentalType === 'unit_only') {
            return [
                'needs_install' => true,
                'needs_service' => false,
                'needs_check' => true,
            ];
        }

        if ($rentalType === 'refill_only') {
            return [
                'needs_install' => false,
                'needs_service' => true,
                'needs_check' => false,
            ];
        }

        $composition = $this->detectRentalMaterialComposition($rental);

        if ($composition['has_unit'] && !$composition['has_non_unit']) {
            return [
                'needs_install' => true,
                'needs_service' => false,
                'needs_check' => true,
            ];
        }

        if (!$composition['has_unit'] && $composition['has_non_unit']) {
            return [
                'needs_install' => false,
                'needs_service' => true,
                'needs_check' => false,
            ];
        }

        return [
            'needs_install' => true,
            'needs_service' => true,
            'needs_check' => false,
        ];
    }

    private function jobAdviceRoomRepresentsUnitOnlyCheck($jaRoom): bool
    {
        $flow = $this->determineRentalJobFlow($jaRoom);

        return $flow['needs_check'] === true && $flow['needs_service'] === false;
    }

    private function roomsRepresentUnitOnlyCheckFlow($rooms): bool
    {
        $rooms = collect($rooms);

        return $rooms->isNotEmpty()
            && $rooms->every(fn ($room) => $this->jobAdviceRoomRepresentsUnitOnlyCheck($room));
    }

    private function detectRentalMaterialComposition($rental): array
    {
        $hasUnit = false;
        $hasNonUnit = false;

        if (!$rental) {
            return ['has_unit' => false, 'has_non_unit' => false];
        }

        $rental->loadMissing([
            'rentalDetails.productCategory',
            'rentalDetails.productType',
            'rentalDetails.masterProduct.productCategory',
            'rentalDetails.masterProduct.productType',
            'rentalDetails.allowedProducts.productCategory',
            'rentalDetails.allowedProducts.productType',
        ]);

        foreach ($rental->rentalDetails as $detail) {
            $isUnit = $this->rentalDetailIsUnit($detail);

            if ($isUnit === true) {
                $hasUnit = true;
            } elseif ($isUnit === false) {
                $hasNonUnit = true;
            }

            if ($hasUnit && $hasNonUnit) {
                break;
            }
        }

        return ['has_unit' => $hasUnit, 'has_non_unit' => $hasNonUnit];
    }

    private function rentalDetailIsUnit($detail): ?bool
    {
        if ($detail->productCategory && $detail->productCategory->is_unit !== null) {
            return (bool) $detail->productCategory->is_unit;
        }

        if ($detail->productType && $detail->productType->is_unit !== null) {
            return (bool) $detail->productType->is_unit;
        }

        $product = $detail->masterProduct;
        if ($product) {
            if ($product->productCategory && $product->productCategory->is_unit !== null) {
                return (bool) $product->productCategory->is_unit;
            }

            if ($product->productType && $product->productType->is_unit !== null) {
                return (bool) $product->productType->is_unit;
            }
        }

        $allowedProduct = $detail->allowedProducts->first();
        if ($allowedProduct) {
            if ($allowedProduct->productCategory && $allowedProduct->productCategory->is_unit !== null) {
                return (bool) $allowedProduct->productCategory->is_unit;
            }

            if ($allowedProduct->productType && $allowedProduct->productType->is_unit !== null) {
                return (bool) $allowedProduct->productType->is_unit;
            }
        }

        return null;
    }

    private function createJobSchedulesFromJobAdvice(JobAdvice $jobAdvice)
    {
        try {
            \Log::info("ðŸ”§ Starting createJobSchedulesFromJobAdvice for Job Advice: {$jobAdvice->job_advice_number} (ID: {$jobAdvice->id})");
            
            // MOM9: Load relationships based on source (contract or quotation)
            // STUDY CASE A: Load building for each room to support multiple buildings
            if ($jobAdvice->quotation_id) {
                // Load from quotation
                $jobAdvice->load([
                    'quotation.survey.building', 
                    'quotation.customer', 
                    'quotation.prospect',
                    'rooms.quotationRoom.room.building', // MOM9: Load quotation room with building (for Study Case A)
                    'rooms.rentalProduct.rentalDetails.productCategory',
                    'rooms.rentalProduct.rentalDetails.productType',
                    'rooms.rentalProduct.rentalDetails.masterProduct.productCategory',
                    'rooms.rentalProduct.rentalDetails.masterProduct.productType',
                    'rooms.rentalProduct.rentalDetails.allowedProducts.productCategory',
                    'rooms.rentalProduct.rentalDetails.allowedProducts.productType',
                ]);
            } else {
                // Load from contract (existing flow)
                $jobAdvice->load([
                    'rooms.contractRoom.room.building', // STUDY CASE A: Load building for each room
                    'contract.quotation.survey.building',
                    'rooms.rentalProduct.rentalDetails.productCategory',
                    'rooms.rentalProduct.rentalDetails.productType',
                    'rooms.rentalProduct.rentalDetails.masterProduct.productCategory',
                    'rooms.rentalProduct.rentalDetails.masterProduct.productType',
                    'rooms.rentalProduct.rentalDetails.allowedProducts.productCategory',
                    'rooms.rentalProduct.rentalDetails.allowedProducts.productType',
                ]);
            }
            
            \Log::info("ðŸ”§ Job Advice loaded with {$jobAdvice->rooms->count()} room(s)");
            
            // Check if job schedules already exist for this job advice
            $existingJobSchedules = \App\Models\JobSchedule::where('job_advice_id', $jobAdvice->id)->count();
            if ($existingJobSchedules > 0) {
                \Log::info("Job schedules already exist for Job Advice: {$jobAdvice->job_advice_number} (Count: {$existingJobSchedules}). Checking missing CSR schedules.");
                $createdServiceSchedules = $this->ensureMissingFirstServiceSchedulesForInstallContinuation($jobAdvice);

                if ($createdServiceSchedules->isNotEmpty()) {
                    $cancelledCount = $this->cancelPendingRemoveFreeJobsForServiceContinuation($jobAdvice, $createdServiceSchedules);
                    if ($cancelledCount > 0) {
                        \Log::info("Auto-cancelled {$cancelledCount} pending Remove Free Job(s) after missing CSR schedule(s) were generated for {$jobAdvice->job_advice_number}.");
                    }
                }

                return;
            }
            
            // MOM9: For Install Free from Quotation, rooms might not be required initially
            // Check if Job Advice has rooms (only required for contract-based Job Advice)
            if (!$jobAdvice->quotation_id && $jobAdvice->rooms->isEmpty()) {
                \Log::warning("âš ï¸ No rooms found for Job Advice: {$jobAdvice->job_advice_number} (ID: {$jobAdvice->id}). Cannot create Job Schedules.");
                return;
            }
            
            // Get building from contract or quotation
            $building = null;
            
            // MOM9: Try to get from quotation->survey (for Install Free)
            if ($jobAdvice->quotation_id && $jobAdvice->quotation && $jobAdvice->quotation->survey) {
                $building = $jobAdvice->quotation->survey->building;
                \Log::info("ðŸ”§ Building found from quotation->survey: " . ($building ? "ID {$building->id}, Name: {$building->name}" : "null"));
            }
            // Try to get from contract's quotation->survey (existing flow)
            else if ($jobAdvice->contract && $jobAdvice->contract->quotation && $jobAdvice->contract->quotation->survey) {
                $building = $jobAdvice->contract->quotation->survey->building;
                \Log::info("ðŸ”§ Building found from contract->quotation->survey: " . ($building ? "ID {$building->id}, Name: {$building->name}" : "null"));
            }
            
            // If not found, try to get from first room (contract room or quotation room)
            if (!$building && $jobAdvice->rooms->count() > 0) {
                $firstRoom = $jobAdvice->rooms->first();
                
                // Try from contract room
                if ($firstRoom && $firstRoom->contractRoom && $firstRoom->contractRoom->room) {
                    $building = $firstRoom->contractRoom->room->building;
                    \Log::info("ðŸ”§ Building found from first contract room: " . ($building ? "ID {$building->id}, Name: {$building->name}" : "null"));
                }
                
                // MOM9: Try from quotation room if not found
                if (!$building && $firstRoom && $firstRoom->quotationRoom && $firstRoom->quotationRoom->room) {
                    $building = $firstRoom->quotationRoom->room->building;
                    \Log::info("ðŸ”§ Building found from first quotation room: " . ($building ? "ID {$building->id}, Name: {$building->name}" : "null"));
                }
            }
            
            if (!$building || !$building->id) {
                \Log::warning("âŒ No building found for Job Advice: {$jobAdvice->job_advice_number} (ID: {$jobAdvice->id}). Skipping JobSchedule creation.");
                \Log::warning("   Contract ID: " . ($jobAdvice->contract_id ?? 'null'));
                \Log::warning("   Quotation ID: " . ($jobAdvice->quotation_id ?? ($jobAdvice->contract->quotation_id ?? 'null')));
                if ($jobAdvice->quotation_id && $jobAdvice->quotation) {
                    \Log::warning("   Survey ID: " . ($jobAdvice->quotation->survey_id ?? 'null'));
                } else if ($jobAdvice->contract && $jobAdvice->contract->quotation) {
                    \Log::warning("   Survey ID: " . ($jobAdvice->contract->quotation->survey_id ?? 'null'));
                }
                return;
            }
            
            $jobScheduleModel = \App\Models\JobSchedule::class;
            
            // MOM6: Create job schedules FOR EACH ROOM
            // MOM9: For Install Free from Quotation, if no rooms, create single job schedule
            // "setelah dari contract kita balik ke JA lagi untuk ngecek. system akan ngecek yang mana saja yang sudah terinstal"
            // "misal dari 10 room, baru ter install 2 dan ter remove 3. nah sisanya akan otomatis membuat job schedule lagi sesuai kontrak"
            
            $totalJobsCreated = 0;
            $createdServiceSchedules = collect();
            
            // MOM9: Handle Install Free from Quotation without rooms
            if ($jobAdvice->quotation_id && ($jobAdvice->type === 'install_free' || $jobAdvice->type === 'install free') && $jobAdvice->rooms->isEmpty()) {
                \Log::info("ðŸ”§ Install Free from Quotation without rooms. Creating single job schedule.");
                
                // Create single install job schedule for Install Free
                $jobScheduleModel = \App\Models\JobSchedule::class;
                
                // MOM: Use DocumentNumberService for standard format
                $documentNumberService = new \App\Services\DocumentNumberService();
                $jobNumber = $documentNumberService->generate(
                    'installation_free', // Maps to IF-xxx
                    null,
                    $building->id,
                    $jobAdvice->contract_id ?? null,
                    $jobAdvice->quotation_id ?? null
                );
                
                $quotationNumber = $jobAdvice->quotation->quotation_number ?? null;
                $customer = $jobAdvice->quotation->customer ?? $jobAdvice->quotation->prospect;
                
                $installJob = $jobScheduleModel::create([
                    'job_number' => $jobNumber,
                    'type' => 'install_free', // Explicit mapping
                    'status' => 'scheduled', 
                    'job_advice_id' => $jobAdvice->id,
                    'building_id' => $building->id,
                    'building_name' => $building->nama_gedung ?? $building->name,
                    'company_name' => $jobAdvice->company_name,
                    'quotation_number' => $quotationNumber,
                    'schedule_date' => $jobAdvice->expected_date,
                    'expected_date' => $jobAdvice->expected_date,
                    'service_period_type' => 'monthly',
                    'internal_notes' => "Auto-generated from JA (Install Free): {$jobAdvice->job_advice_number} | Quotation: {$quotationNumber}",
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);

                 // MOM14: REMOVED AUTO-GENERATION OF REMOVE FREE at JA finalize
                 // "harusnya jangann generate job remove free dulu, job remove free baru ada ketika kerjaan atau job type install free selesai"
                 /*
                 // Auto create remove_free if remove_date exists for Install Free
                 if ($jobAdvice->remove_date) {
                    $removeJobNumber = app(\App\Services\DocumentNumberService::class)->generate('remove_free', null, $building->id, $jobAdvice->contract_id, $jobAdvice->quotation_id);
                    $removeJob = $jobScheduleModel::create([
                        'job_number' => $removeJobNumber,
                        'type' => 'remove_free', // Install Free -> remove_free
                        'status' => 'scheduled',
                        'job_advice_id' => $jobAdvice->id,
                        'building_id' => $building->id,
                        'building_name' => $building->nama_gedung ?? $building->name,
                        'company_name' => $jobAdvice->company_name,
                        'quotation_number' => $quotationNumber,
                        'schedule_date' => $jobAdvice->remove_date,
                        'expected_date' => $jobAdvice->remove_date,
                        'service_period_type' => 'monthly',
                         'internal_notes' => "Auto-generated Remove for Install Free: {$jobAdvice->job_advice_number}",
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);
                    \Log::info("âœ… Install Free Remove JobSchedule created: {$removeJob->job_number}");
                 }
                 */
                
                $totalJobsCreated++;
                \Log::info("âœ… Install Free JobSchedule created: {$installJob->job_number}");
                } else {
                    // MOM13: Create job schedule(s) - 1 per building if multiple buildings, otherwise 1 for all rooms
                    // "1 contract/quotation = 1 job schedule meski di dalamnya ada banyak room"
                    // "Jika beda building, maka 1 contract bisa banyak job schedule (1 per building)"
                    \Log::info("ðŸ”§ MOM13: Processing {$jobAdvice->rooms->count()} room(s) in Job Advice: {$jobAdvice->job_advice_number}");
                    
                    $jobAdviceTypeRaw = strtolower($jobAdvice->type ?? 'install');
                    // Normalize Types
                    $jobAdviceType = $jobAdviceTypeRaw;
                    if ($jobAdviceTypeRaw === 'install free' || $jobAdviceTypeRaw === 'install_free') $jobAdviceType = 'install_free';
                    if ($jobAdviceTypeRaw === 'change unit' || $jobAdviceTypeRaw === 'change_rental') $jobAdviceType = 'change';
                    
                    $isInstallFree = ($jobAdviceType === 'install_free');
                    $isInstall = ($jobAdviceType === 'install'); // Standard Install from Contract
                    
                    // Filter rooms that need jobs (skip already completed/installed rooms)
                    $eligibleRooms = collect();
                    $allUnitsAlreadyInstalled = true;
                    
                    foreach ($jobAdvice->rooms as $jaRoom) {
                        $skipRoom = false;
                        
                        // MOM13: User Request: "satu ruangan bisa lebih dari 1 unit dari contract/quotation yg berbeda"
                        // So we no longer skip rooms even if they already have completed install jobs 
                        // as long as they are distinct job advices.
                        if (!$skipRoom) {
                            $eligibleRooms->push($jaRoom);
                            if (!$jaRoom->unit_already_installed) {
                                $allUnitsAlreadyInstalled = false;
                            }
                        }
                    }
                    
                    if ($eligibleRooms->isEmpty()) {
                        \Log::warning("âš ï¸ No eligible rooms for Job Advice {$jobAdvice->job_advice_number}. All rooms skipped.");
                    } else {
                        // STUDY CASE A: Group rooms by building_id
                        // If multiple buildings, create 1 job schedule per building
                        $roomsByBuilding = $eligibleRooms->groupBy(function($jaRoom) {
                            // Get building_id from contractRoom or quotationRoom
                            $buildingId = null;
                            
                            if ($jaRoom->contractRoom && $jaRoom->contractRoom->room) {
                                $buildingId = $jaRoom->contractRoom->room->building_id;
                            } elseif ($jaRoom->quotationRoom && $jaRoom->quotationRoom->room) {
                                $buildingId = $jaRoom->quotationRoom->room->building_id;
                            }
                            
                            return $buildingId ?? 'unknown';
                        });
                        
                        \Log::info("ðŸ”§ Grouped rooms by building: " . $roomsByBuilding->count() . " building(s) found");
                        
                        $jobsCreated = [];
                        
                        // Process each building group
                        foreach ($roomsByBuilding as $buildingId => $buildingRooms) {
                            // Get building object for this group
                            $groupBuilding = null;
                            if ($buildingId !== 'unknown') {
                                $groupBuilding = \App\Models\Building::find($buildingId);
                            }
                            
                            // Fallback to original building if not found
                            if (!$groupBuilding) {
                                $groupBuilding = $building;
                                \Log::warning("âš ï¸ Building ID {$buildingId} not found, using fallback building");
                            }
                            
                            if (!$groupBuilding) {
                                \Log::error("âŒ No building found for building group {$buildingId}. Skipping.");
                                continue;
                            }
                            
                            // Build room list for internal notes for this building
                            $roomNames = $buildingRooms->pluck('room_name')->filter()->toArray();
                            $roomListNote = count($roomNames) > 0 
                                ? "\n[Rooms: " . implode(', ', $roomNames) . "] (" . count($roomNames) . " rooms)"
                                : '';
                            
                            \Log::info("ðŸ”§ Processing building: {$groupBuilding->nama_gedung} (ID: {$groupBuilding->id}) with " . count($buildingRooms) . " room(s)");
                            
                            // Check if all units already installed for this building group
                            $allUnitsInstalledForBuilding = $buildingRooms->every(function($jaRoom) {
                                return $jaRoom->unit_already_installed;
                            });
                            
                            // -----------------------------------------------------
                            // LOGIC 1: INSTALL / INSTALL FREE (One Job PER UNIQUE ROOM)
                            // MOM: Group by unique room first to prevent duplicate JS for multi-rental rooms
                            // -----------------------------------------------------
                            if ($isInstall || $isInstallFree) {
                                // Group buildingRooms by unique room (same logic as View grouping)
                                $roomsByUniqueRoom = $buildingRooms->groupBy(function($item) {
                                    return $item->contract_room_id 
                                        ? 'c_' . $item->contract_room_id 
                                        : ($item->quotation_room_id 
                                            ? 'q_' . $item->quotation_room_id 
                                            : 'n_' . $item->room_name);
                                });
                                
                                \Log::info("ðŸ”§ Grouped " . count($buildingRooms) . " room records into " . $roomsByUniqueRoom->count() . " unique room(s)");
                                
                                // Loop through EACH UNIQUE ROOM to create distinct Job Schedules
                                foreach ($roomsByUniqueRoom as $roomKey => $roomsInGroup) {
                                    // Use first room in group as representative for room info
                                    $jaRoom = $roomsInGroup->first();
                                    $roomNote = "\n[Room: {$jaRoom->room_name}] (Rentals: {$roomsInGroup->count()})";
                                    $flowRooms = $this->splitRoomsByRentalJobFlow($roomsInGroup);
                                    $installRooms = $flowRooms['install'];
                                    $serviceRooms = $flowRooms['service'];
                                    $checkRooms = $flowRooms['check'];

                                    $roomAlreadyOnWall = $this->jobAdviceRoomHasActiveUnitOnWall($jobAdvice, $jaRoom, $groupBuilding);

                                    // If a trial/IF already installed the unit, the contract install JA must not
                                    // create another IR for the same physical room. It should continue with first CSR.
                                    // This rule must NOT apply to Install Free itself: an Install Free JA should
                                    // always generate IF and let material assignment follow the selected rental.
                                    if (!$isInstallFree && $roomAlreadyOnWall) {
                                        $installRooms = collect();
                                        \Log::info("Room {$jaRoom->room_name} already has active Unit On Wall. Skipping Install and creating first service/check instead.");
                                    }

                                    $checkRoomsDeferredUntilInstallDone = collect();

                                    if ($installRooms->isNotEmpty()) {
                                        // Determine JS Type: 'install' or 'install_free'
                                        $installType = $isInstallFree ? 'install_free' : 'install';
                                        
                                        $installJobs = $this->createJobSchedulesPerRoom(
                                            $jobAdvice,
                                            $installRooms,
                                            $installType,
                                            $groupBuilding,
                                            "\n[Room: {$jaRoom->room_name}] (Install rentals: {$installRooms->count()})"
                                        );
                                        
                                        if ($installJobs->isNotEmpty()) {
                                            $jobsCreated[] = 'install';
                                            $totalJobsCreated += $installJobs->count();
                                            
                                            // Link unit-bearing rentals to the install job schedule.
                                            $installJob = $installJobs->first();
                                            foreach ($installRooms as $roomToLink) {
                                                $roomToLink->update(['install_job_schedule_id' => $installJob->id]);
                                            }
                                            
                                            \Log::info("âœ… Created Install JobSchedule for room '{$jaRoom->room_name}' with {$roomsInGroup->count()} rental(s)");
                                        }
                                    }

                                    if ($installRooms->isNotEmpty()) {
                                        // Rental/unit-only checks must be generated only after the IR is done BA.
                                        // If created here, end users see IR and CHK together while installation is not complete yet.
                                        $checkRoomsDeferredUntilInstallDone = $checkRooms->filter(function ($checkRoom) use ($installRooms) {
                                            return $installRooms->contains('id', $checkRoom->id);
                                        });

                                        if ($checkRoomsDeferredUntilInstallDone->isNotEmpty()) {
                                            $checkRooms = $checkRooms->reject(function ($checkRoom) use ($checkRoomsDeferredUntilInstallDone) {
                                                return $checkRoomsDeferredUntilInstallDone->contains('id', $checkRoom->id);
                                            })->values();

                                            \Log::info("Deferred {$checkRoomsDeferredUntilInstallDone->count()} Check JobSchedule(s) for room '{$jaRoom->room_name}' until install completion.");
                                        }
                                    }

                                    // Logic 1.b: Service/Check Logic
                                    // Unit-only rentals continue as Check jobs, while refill-only
                                    // rentals continue as Service/Refill jobs.
                                    if (!$isInstallFree && $checkRooms->isNotEmpty()) {
                                        $checkJobs = $this->createJobSchedulesPerRoom(
                                            $jobAdvice,
                                            $checkRooms,
                                            'check',
                                            $groupBuilding,
                                            "\n[Room: {$jaRoom->room_name}] (Check rentals: {$checkRooms->count()})",
                                            1,
                                            true
                                        );

                                        if ($checkJobs->isNotEmpty()) {
                                            $jobsCreated[] = 'check';
                                            $totalJobsCreated += $checkJobs->count();
                                            $createdServiceSchedules = $createdServiceSchedules->merge($checkJobs);

                                            $checkJob = $checkJobs->first();
                                            foreach ($checkRooms as $roomToLink) {
                                                $roomToLink->update([
                                                    'service_job_schedule_id' => $checkJob->id,
                                                    'rental_has_service' => false,
                                                    'updated_by' => Auth::id(),
                                                ]);
                                            }

                                            \Log::info("Created Check JobSchedule for room '{$jaRoom->room_name}' with {$checkRooms->count()} unit-only rental(s)");
                                        }
                                    }

                                    if (!$isInstallFree && $serviceRooms->isNotEmpty()) {
                                        $serviceJobs = $this->createJobSchedulesPerRoom(
                                            $jobAdvice,
                                            $serviceRooms,
                                            'service_first',
                                            $groupBuilding,
                                            "\n[Room: {$jaRoom->room_name}] (Service rentals: {$serviceRooms->count()})",
                                            1,
                                            false
                                        );

                                        if ($serviceJobs->isNotEmpty()) {
                                            $jobsCreated[] = 'service';
                                            $totalJobsCreated += $serviceJobs->count();
                                            $createdServiceSchedules = $createdServiceSchedules->merge($serviceJobs);

                                            $serviceJob = $serviceJobs->first();
                                            foreach ($serviceRooms as $roomToLink) {
                                                $roomToLink->update([
                                                    'service_job_schedule_id' => $serviceJob->id,
                                                    'rental_has_service' => true,
                                                    'updated_by' => Auth::id(),
                                                ]);
                                            }

                                            \Log::info("Created First Service JobSchedule for room '{$jaRoom->room_name}' with {$serviceRooms->count()} refill rental(s)");
                                        }
                                    }

                                    // Legacy block disabled; rental splitting above owns Check vs Service creation.
                                    // Standard Install OR Refill Only ==> Create Service (or Check)
                                    // OR if we skipped install because of Non-Unit Only content in Unit+Refill mode
                                    if (false) {
                                        // serviceJobType is already determined above
                                        
                                        $serviceJobs = $this->createJobSchedulesPerRoom(
                                            $jobAdvice,
                                            $roomsInGroup, // Pass all rentals for materials
                                            $serviceJobType,
                                            $groupBuilding,
                                            $roomNote,
                                            1, // First service period
                                            $serviceDoesNotNeedMaterial
                                        );
                                        
                                        if ($serviceJobs->isNotEmpty()) {
                                            $jobsCreated[] = 'service';
                                            $totalJobsCreated += $serviceJobs->count();
                                            $createdServiceSchedules = $createdServiceSchedules->merge($serviceJobs);
                                            
                                            // Link ALL rooms in group to the same service job schedule
                                            $serviceJob = $serviceJobs->first();
                                            foreach ($roomsInGroup as $roomToLink) {
                                                $roomToLink->update([
                                                    'service_job_schedule_id' => $serviceJob->id,
                                                    'rental_has_service' => true
                                                ]);
                                            }
                                            
                                            \Log::info("âœ… Created First {$serviceJobType} JobSchedule for room '{$jaRoom->room_name}' with {$roomsInGroup->count()} rental(s)");
                                        }
                                    } 
                                    // Logic 1.c: Auto-create Remove for INSTALL FREE (if remove_date exists)
                                    elseif ($isInstallFree && $jobAdvice->remove_date) {
                                          /* 
                                          // MOM14: Disabled auto-generation of Remove Free here. Delayed until Install Free is complete.
                                          $jaRoom->update(['rental_has_service' => false]);
    
                                          $removeJobs = $this->createJobSchedulesPerRoom(
                                            $jobAdvice,
                                            $singleRoomCollection,
                                            'remove_free',
                                            $groupBuilding,
                                            $roomNote
                                          );
                                          
                                          if ($removeJobs->isNotEmpty()) {
                                             $jobsCreated[] = 'remove_free';
                                             $totalJobsCreated += $removeJobs->count();
                                             foreach($removeJobs as $rJob) {
                                                 $rJob->update([
                                                     'schedule_date' => $jobAdvice->remove_date,
                                                     'expected_date' => $jobAdvice->remove_date
                                                 ]);
                                             }
                                             \Log::info("âœ… Created Remove Free JobSchedule for room '{$jaRoom->room_name}'");
                                          }
                                          */
                                    }
                                }
                            }
                            
                            // -----------------------------------------------------
                            // LOGIC 2: EXTRA / CHANGE / COMPLAIN
                            // -----------------------------------------------------
                            elseif (in_array($jobAdviceType, ['extra', 'change', 'complain'])) {
                                $typedJobs = $this->createJobSchedulesPerRoom(
                                    $jobAdvice,
                                    $buildingRooms,
                                    $jobAdviceType, // 'extra', 'change', or 'complain'
                                    $groupBuilding,
                                    $roomListNote
                                );

                                if ($typedJobs->isNotEmpty()) {
                                    $jobsCreated[] = $jobAdviceType;
                                    $totalJobsCreated += $typedJobs->count();
                                    $sharedNumber = $typedJobs->first()->job_number;
                                    \Log::info("âœ… Created {$typedJobs->count()} {$jobAdviceType} JobSchedule(s) with shared job_number: {$sharedNumber}");
                                }
                            }
                            // -----------------------------------------------------
                            // LOGIC 3: REMOVE / MAINTENANCE / SERVICE / REMOVAL
                            // -----------------------------------------------------
                            // For other Job Advice types (service, remove, maintenance)
                            else {
                                // Default types: service, remove, maintenance
                                $actualType = $jobAdviceType;
                                
                                // Specific logic for Remove
                                if ($jobAdviceType === 'remove' || $jobAdviceType === 'removal') {
                                     // Remove always 'remove' (remove_free is auto only from Install Free or specific context if requested, but generally 'remove')
                                     // Actually, if we want to be strict: 
                                     // "job schedule type remove free hanya akan tercipta karna install free ada remove datenya."
                                     // So a manual 'Remove' JA should be standard 'remove' ? 
                                     // "jika remove nya untuk install free maka tulisannya remove free"
                                     
                                     // Check context: If this JA is linked to a Quote only (no contract), assume it's related to Install Free flow
                                     if ($jobAdvice->quotation_id && !$jobAdvice->contract_id) {
                                         $actualType = 'remove_free';
                                     } else {
                                         $actualType = 'remove';
                                     }
                                }
                                
                                // Service that is NOT service_first (auto-generated routine) â†’ service_routine
                                // Note: service_first is already handled above for first CSR with IR
                                // This handles standalone service JA which should be service_routine
                                // Service Handling: Split into Check (Unit Only) and Service Routine (others)
                                if ($jobAdviceType === 'service') {
                                    $unitOnlyRooms = $buildingRooms->filter(function($r) {
                                        return ($r->rentalProduct->rental_type ?? 'unit_refill') === 'unit_only';
                                    });
                                    $otherRooms = $buildingRooms->reject(function($r) {
                                        return ($r->rentalProduct->rental_type ?? 'unit_refill') === 'unit_only';
                                    });
                                    
                                    // 1. Process unit-only jobs as service_routine without material.
                                    // The DB enum intentionally does not support a separate "check" type.
                                    // Mark as period 1 so completing this first check fans out the
                                    // remaining check periods without recreating period 1.
                                    if ($unitOnlyRooms->isNotEmpty()) {
                                        $checkJobs = $this->createJobSchedulesPerRoom(
                                            $jobAdvice, $unitOnlyRooms, 'service_routine', $groupBuilding, $roomListNote, 1, true
                                        );
                                        
                                        if ($checkJobs->isNotEmpty()) {
                                            $jobsCreated[] = 'check';
                                            $totalJobsCreated += $checkJobs->count();
                                            $createdServiceSchedules = $createdServiceSchedules->merge($checkJobs);
                                            // Link Check jobs
                                            foreach ($checkJobs as $idx => $jb) {
                                                $r = $unitOnlyRooms->values()->get($idx);
                                                if ($r) $r->update(['service_job_schedule_id' => $jb->id]);
                                            }
                                            \Log::info("âœ… Created {$checkJobs->count()} Check Jobs (Unit Only)");
                                        }
                                    }
                                    
                                    // 2. Process Service Routine Jobs as period 1 so completing this
                                    // first service fans out the remaining refill service periods.
                                    if ($otherRooms->isNotEmpty()) {
                                        $srvJobs = $this->createJobSchedulesPerRoom(
                                            $jobAdvice, $otherRooms, 'service_routine', $groupBuilding, $roomListNote, 1
                                        );
                                        
                                        if ($srvJobs->isNotEmpty()) {
                                            $jobsCreated[] = 'service_routine';
                                            $totalJobsCreated += $srvJobs->count();
                                            $createdServiceSchedules = $createdServiceSchedules->merge($srvJobs);
                                            // Link Service jobs
                                            foreach ($srvJobs as $idx => $jb) {
                                                 $r = $otherRooms->values()->get($idx);
                                                 if ($r) $r->update(['service_job_schedule_id' => $jb->id]);
                                            }
                                            \Log::info("âœ… Created {$srvJobs->count()} Service Routine Jobs");
                                        }
                                    }
                                    
                                    // Set typedJobs to empty to skip logic below (since we handled it here)
                                    $typedJobs = collect(); 
                                } else {
                                    // Standard handling for non-service types using calculated actualType
                                    $typedJobs = $this->createJobSchedulesPerRoom(
                                        $jobAdvice,
                                        $buildingRooms,
                                        $actualType,
                                        $groupBuilding,
                                        $roomListNote
                                    );
                                }
                                
                                if ($typedJobs->isNotEmpty()) {
                                    $jobsCreated[] = $jobAdviceType;
                                    $totalJobsCreated += $typedJobs->count();
                                    
                                    // Link each room to its corresponding job schedule
                                    foreach ($typedJobs as $index => $typedJob) {
                                        $jaRoom = $buildingRooms->values()->get($index);
                                        if ($jaRoom) {
                                            if ($jobAdviceType === 'service') {
                                                $jaRoom->update(['service_job_schedule_id' => $typedJob->id]);
                                            } elseif (in_array($actualType, ['remove', 'remove_free', 'removal'])) {
                                                $jaRoom->update(['remove_job_schedule_id' => $typedJob->id]);
                                            }
                                        }
                                    }
                                    
                                    $sharedNumber = $typedJobs->first()->job_number;
                                    \Log::info("âœ… Created {$typedJobs->count()} {$jobAdviceType} JobSchedule(s) with shared job_number: {$sharedNumber}");
                                }
                            }
                        } // End foreach building
                    }
            } // End else (rooms-based flow)
            
            if (
                $totalJobsCreated > 0
                && $jobAdvice->contract_id
                && in_array(strtolower((string) $jobAdvice->type), ['install', 'service'], true)
            ) {
                $missingServiceSchedules = $this->ensureMissingFirstServiceSchedulesForInstallContinuation($jobAdvice);
                if ($missingServiceSchedules->isNotEmpty()) {
                    $createdServiceSchedules = $createdServiceSchedules->merge($missingServiceSchedules);
                    $totalJobsCreated += $missingServiceSchedules->count();
                }

                $cancelledCount = $this->cancelPendingRemoveFreeJobsForServiceContinuation($jobAdvice, $createdServiceSchedules);
                if ($cancelledCount > 0) {
                    \Log::info("Auto-cancelled {$cancelledCount} pending Remove Free Job(s) after contract JA {$jobAdvice->job_advice_number} was generated.");
                }
            }

            if ($totalJobsCreated > 0) {
                \Log::info("âœ… JobSchedule(s) auto-created for JobAdvice: {$jobAdvice->job_advice_number} (Total: {$totalJobsCreated} job schedule(s))");
            } else {
                \Log::warning("âš ï¸ No JobSchedule(s) created for JobAdvice: {$jobAdvice->job_advice_number} (All rooms were skipped or no jobs created)");
            }
            
        } catch (\Exception $e) {
            \Log::error("Failed to auto-create JobSchedule(s) for JobAdvice {$jobAdvice->job_advice_number}: " . $e->getMessage());
            throw $e; // Re-throw to trigger transaction rollback
        }
    }

    private function ensureMissingFirstServiceSchedulesForInstallContinuation(JobAdvice $jobAdvice): \Illuminate\Support\Collection
    {
        $createdServiceSchedules = collect();
        $jobAdviceType = strtolower(trim((string) $jobAdvice->type));

        if ($jobAdviceType !== 'install') {
            return $createdServiceSchedules;
        }

        $jobAdvice->loadMissing([
            'contract.quotation.survey.building',
            'rooms.contractRoom.room.building',
            'rooms.quotationRoom.room.building',
            'rooms.rentalProduct.serviceFrequency',
            'rooms.rentalProduct.rentalDetails.productCategory',
            'rooms.rentalProduct.rentalDetails.productType',
            'rooms.rentalProduct.rentalDetails.masterProduct.productCategory',
            'rooms.rentalProduct.rentalDetails.masterProduct.productType',
            'rooms.rentalProduct.rentalDetails.allowedProducts.productCategory',
            'rooms.rentalProduct.rentalDetails.allowedProducts.productType',
        ]);

        if (!$jobAdvice->contract_id || $jobAdvice->rooms->isEmpty()) {
            return $createdServiceSchedules;
        }

        $roomsByUniqueRoom = $jobAdvice->rooms->groupBy(function ($item) {
            return $item->contract_room_id
                ? 'c_' . $item->contract_room_id
                : ($item->quotation_room_id ? 'q_' . $item->quotation_room_id : 'n_' . $item->room_name);
        });

        foreach ($roomsByUniqueRoom as $roomsInGroup) {
            $jaRoom = $roomsInGroup->first();
            $serviceRoomsInGroup = $roomsInGroup->filter(function ($roomItem) {
                $rentalType = strtolower((string) ($roomItem->rentalProduct?->rental_type ?? 'unit_refill'));
                return $rentalType !== 'unit_only';
            });

            if ($serviceRoomsInGroup->isEmpty()) {
                continue;
            }

            $jaRoomIds = $serviceRoomsInGroup->pluck('id')->all();

            if ($this->activeServiceScheduleExistsForJobAdviceRooms($jobAdvice->id, $jaRoomIds)) {
                continue;
            }

            $room = $jaRoom->contractRoom?->room ?? $jaRoom->quotationRoom?->room;
            $building = $room?->building
                ?? $jobAdvice->contract?->quotation?->survey?->building;

            if (!$building) {
                \Log::warning("Cannot create missing CSR for {$jobAdvice->job_advice_number}: building not found.", [
                    'job_advice_room_id' => $jaRoom->id,
                    'room_name' => $jaRoom->room_name,
                ]);
                continue;
            }

            $roomNote = "\n[Room: {$jaRoom->room_name}] (Service rentals: {$serviceRoomsInGroup->count()})";
            $serviceJobs = $this->createJobSchedulesPerRoom(
                $jobAdvice,
                $serviceRoomsInGroup,
                'service_first',
                $building,
                $roomNote,
                1,
                false
            );

            if ($serviceJobs->isEmpty()) {
                continue;
            }

            $serviceJob = $serviceJobs->first();
            foreach ($serviceRoomsInGroup as $roomToLink) {
                $roomToLink->update([
                    'service_job_schedule_id' => $serviceJob->id,
                    'rental_has_service' => true,
                    'unit_already_installed' => true,
                    'updated_by' => Auth::id(),
                ]);
            }

            $createdServiceSchedules = $createdServiceSchedules->merge($serviceJobs);
            \Log::info("Created missing first CSR for JA {$jobAdvice->job_advice_number}, room {$jaRoom->room_name}.");
        }

        return $createdServiceSchedules;
    }

    private function activeServiceScheduleExistsForJobAdviceRooms(int $jobAdviceId, array $jobAdviceRoomIds): bool
    {
        return \App\Models\JobSchedule::where('job_advice_id', $jobAdviceId)
            ->whereIn('type', ['service', 'service_first', 'service_routine'])
            ->whereNotIn('status', ['cancelled', 'undone'])
            ->where(function ($query) use ($jobAdviceRoomIds) {
                $query->whereHas('jobScheduleRooms', function ($roomQuery) use ($jobAdviceRoomIds) {
                    $roomQuery->whereIn('job_advice_room_id', $jobAdviceRoomIds);
                })->orWhereHas('jobScheduleRooms.rentals', function ($rentalQuery) use ($jobAdviceRoomIds) {
                    $rentalQuery->whereIn('job_advice_room_id', $jobAdviceRoomIds);
                });
            })
            ->exists();
    }

    private function cancelPendingRemoveFreeJobsForServiceContinuation(JobAdvice $jobAdvice, $serviceSchedules): int
    {
        $serviceSchedules = collect($serviceSchedules);
        $roomIds = $serviceSchedules->pluck('room_id')->filter()->unique()->values();

        $quotationNumbers = collect([
            $jobAdvice->quotation?->quotation_number,
            $jobAdvice->contract?->quotation?->quotation_number,
        ])->filter()->unique()->values();

        if ($quotationNumbers->isEmpty() && $roomIds->isEmpty()) {
            return 0;
        }

        $cancelableStatuses = [
            'scheduled',
            'new_job',
            'assign_team',
            'assign_material',
            'barang_dipersiapkan',
            'barang_siap_diambil',
        ];

        $query = JobSchedule::whereIn('type', ['remove_free', 'remove free'])
            ->whereIn('status', $cancelableStatuses)
            ->where(function ($q) use ($quotationNumbers, $roomIds) {
                if ($quotationNumbers->isNotEmpty()) {
                    $q->whereIn('quotation_number', $quotationNumbers);
                } elseif ($roomIds->isNotEmpty()) {
                    $q->whereIn('room_id', $roomIds);
                }
            });

        $cancelledCount = 0;
        foreach ($query->get() as $removeJob) {
            $removeJob->update([
                'status' => 'cancelled',
                'internal_notes' => trim(($removeJob->internal_notes ? $removeJob->internal_notes . "\n" : '') . "Auto-cancelled because contract continued with Service JA {$jobAdvice->job_advice_number}."),
                'updated_by' => Auth::id(),
            ]);
            $cancelledCount++;
        }

        return $cancelledCount;
    }

    /**
     * MOM10 UPDATE: Generate ONLY first service schedule (not all)
     * Remaining service schedules will be auto-generated after install + first service are completed
     * 
     * @param JobAdvice $jobAdvice
     * @param $jaRoom JobAdviceRoom
     * @param $building
     * @return \Illuminate\Support\Collection Collection of created JobSchedule models
     */
    private function generateAllServiceSchedules(JobAdvice $jobAdvice, $jaRoom, $building)
    {
        $serviceSchedules = collect();
        
        try {
            // Get rental product (MasterRental)
            $rental = $jaRoom->rentalProduct;
            if (!$rental) {
                \Log::warning("âš ï¸ No rental product found for room {$jaRoom->room_name}. Cannot generate service schedules.");
                return $serviceSchedules;
            }
            
            // Get service frequency from MasterRental
            $serviceFrequencyObj = $rental->serviceFrequency;
            if (!$serviceFrequencyObj) {
                \Log::warning("âš ï¸ No service frequency found for rental {$rental->rental_name}. Cannot generate service schedules.");
                return $serviceSchedules;
            }
            
            // Get frequency_times_per_month (misal: 1x/bulan = 1, 3x/bulan = 3)
            $frequencyTimesPerMonth = $serviceFrequencyObj->frequency_times_per_month ?? 1;
            
            // Get rental period from contract (start_date sampai end_date, dalam bulan)
            $contract = $jobAdvice->contract;
            if (!$contract || !$contract->start_date || !$contract->end_date) {
                \Log::warning("âš ï¸ No contract or contract dates found for Job Advice {$jobAdvice->job_advice_number}. Cannot calculate rental period.");
                return $serviceSchedules;
            }
            
            // Calculate rental period in months
            $startDate = \Carbon\Carbon::parse($contract->start_date);
            $endDate = \Carbon\Carbon::parse($contract->end_date);
            $rentalPeriodMonths = $startDate->diffInMonths($endDate) + 1; // +1 to include both start and end month
            
            // MOM10 UPDATE: Only generate FIRST service (period 1)
            // Remaining services will be auto-generated after install + first service completed
            \Log::info("ðŸ”§ Generating FIRST service schedule only for room {$jaRoom->room_name}:");
            \Log::info("   - Service Frequency: {$frequencyTimesPerMonth}x per month");
            \Log::info("   - Rental Period: {$rentalPeriodMonths} months");
            \Log::info("   - Will generate remaining services after install + first service completed");
            
            // Generate ONLY first service (period = 1)
            $serviceSchedule = $this->createJobSchedule(
                $jobAdvice,
                $jaRoom,
                'service',
                $building,
                1, // MOM10 UPDATE: Only period 1 (first service)
                $serviceFrequencyObj
            );
            
            if ($serviceSchedule) {
                $serviceSchedules->push($serviceSchedule);
            }
            
            \Log::info("âœ… Successfully generated first service schedule for room {$jaRoom->room_name}");
            
        } catch (\Exception $e) {
            \Log::error("âŒ Failed to generate service schedule for room {$jaRoom->room_name}: " . $e->getMessage());
        }
        
        return $serviceSchedules;
    }

    /**
     * MOM10 UPDATE: Generate remaining service schedules (service 2 to N)
     * Called after install + first service are completed
     * 
     * @param JobAdvice $jobAdvice
     * @param $jaRoom JobAdviceRoom
     * @param $building
     * @return \Illuminate\Support\Collection Collection of created JobSchedule models
     */
    private function generateRemainingServiceSchedules(JobAdvice $jobAdvice, $jaRoom, $building)
    {
        $serviceSchedules = collect();
        
        try {
            // Get rental product (MasterRental)
            $rental = $jaRoom->rentalProduct;
            if (!$rental) {
                \Log::warning("âš ï¸ No rental product found for room {$jaRoom->room_name}. Cannot generate remaining service schedules.");
                return $serviceSchedules;
            }
            
            // Get service frequency from MasterRental
            $serviceFrequencyObj = $rental->serviceFrequency;
            if (!$serviceFrequencyObj) {
                \Log::warning("âš ï¸ No service frequency found for rental {$rental->rental_name}. Cannot generate remaining service schedules.");
                return $serviceSchedules;
            }
            
            // Get frequency_times_per_month (misal: 1x/bulan = 1, 3x/bulan = 3)
            $frequencyTimesPerMonth = $serviceFrequencyObj->frequency_times_per_month ?? 1;
            
            // Get rental period from contract (start_date sampai end_date, dalam bulan)
            $contract = $jobAdvice->contract;
            if (!$contract || !$contract->start_date || !$contract->end_date) {
                \Log::warning("âš ï¸ No contract or contract dates found for Job Advice {$jobAdvice->job_advice_number}. Cannot calculate rental period.");
                return $serviceSchedules;
            }
            
            // Calculate rental period in months
            $startDate = \Carbon\Carbon::parse($contract->start_date);
            $endDate = \Carbon\Carbon::parse($contract->end_date);
            $rentalPeriodMonths = $startDate->diffInMonths($endDate) + 1; // +1 to include both start and end month
            
            // MOM10: Total service = frequency_times_per_month Ã— rental_period_months
            // Contoh: 1x/bulan Ã— 12 bulan = 12 service schedules total
            $totalServices = $frequencyTimesPerMonth * $rentalPeriodMonths;
            
            \Log::info("ðŸ”§ Generating REMAINING service schedules for room {$jaRoom->room_name}:");
            \Log::info("   - Service Frequency: {$frequencyTimesPerMonth}x per month");
            \Log::info("   - Rental Period: {$rentalPeriodMonths} months");
            \Log::info("   - Total Services: {$totalServices}");
            \Log::info("   - Will generate from period 2 to {$totalServices}");
            
            // MOM16: Determine Job Type based on Rental Type (Unit Only -> Check)
            $rentalType = $rental->rental_type ?? 'unit_refill';
            $jobType = ($rentalType === 'unit_only') ? 'check' : 'service';
            
            // Generate remaining service schedules (period 2 to totalServices)
            for ($period = 2; $period <= $totalServices; $period++) {
                $serviceSchedule = $this->createJobSchedule(
                    $jobAdvice,
                    $jaRoom,
                    $jobType,
                    $building,
                    $period, // Period 2, 3, 4, ..., N
                    $serviceFrequencyObj
                );
                
                if ($serviceSchedule) {
                    $serviceSchedules->push($serviceSchedule);
                }
            }
            
            \Log::info("âœ… Successfully generated {$serviceSchedules->count()} remaining service schedules for room {$jaRoom->room_name}");
            
        } catch (\Exception $e) {
            \Log::error("âŒ Failed to generate remaining service schedules for room {$jaRoom->room_name}: " . $e->getMessage());
        }
        
        return $serviceSchedules;
    }

    /**
     * Helper: Create individual job schedule
     * 
     * @param JobAdvice $jobAdvice
     * @param $jaRoom JobAdviceRoom
     * @param string $type
     * @param $building
     * @param int|null $period MOM10: Period number for service (1, 2, 3, ...)
     * @param \App\Models\RentalServiceFrequency|null $serviceFrequencyObj MOM10: Service frequency object
     * @return \App\Models\JobSchedule|null
     */
    private function createJobSchedule(JobAdvice $jobAdvice, $jaRoom, string $type, $building, $period = null, $serviceFrequencyObj = null)
    {
        $jobScheduleModel = \App\Models\JobSchedule::class;
        $requestedType = strtolower(trim($type));
        if ($requestedType === 'check') {
            // Legacy unit-only check jobs are stored as valid service types because
            // job_schedules.type on production does not include a separate "check" enum.
            $type = ($period !== null && (int) $period === 1) ? 'service_first' : 'service_routine';
        }
        $isUnitOnlyCheckFlow = $requestedType === 'check'
            || (in_array($type, ['service', 'service_first', 'service_routine'], true)
                && $this->jobAdviceRoomRepresentsUnitOnlyCheck($jaRoom));
        
        // MOM10 UPDATE: Use DocumentNumberService with proper type codes (IR/IF/CSR/RV/RF/RR)
        // IR = Installation Report (install from contract)
        // IF = Installation Free (install from quotation - trial/uji coba)
        // CSR = Customer Service Report (service)
        // RV = Remove
        // RF = Remove Free
        // RR = Remove Report
        $documentNumberService = new DocumentNumberService();
        $documentType = 'job_schedule'; // Default
        
        // Check if this is an Install Free (from quotation)
        $jobAdviceType = strtolower($jobAdvice->type ?? 'install');
        $isInstallFree = ($jobAdviceType === 'install free' || $jobAdviceType === 'install_free');
        
        // Map job type to document type code
        if ($type === 'install') {
            // Differentiate between IR (install from contract) and IF (install free from quotation)
            $documentType = $isInstallFree ? 'installation_free' : 'installation_report'; // IF or IR
        } elseif (in_array($type, ['service', 'service_first', 'service_routine'], true)) {
            $documentType = $isUnitOnlyCheckFlow ? 'installation_report' : 'customer_service_report'; // IR for Unit Only checks, CSR for refill service
        } elseif (in_array($type, ['remove_free', 'remove free'])) {
            $documentType = 'remove_free'; // RF
        } elseif ($type === 'remove') {
            $documentType = 'remove'; // RV
        } elseif ($type === 'extra') {
            $documentType = 'job_schedule_extra'; // EXT
        } elseif ($type === 'complain') {
            $documentType = 'job_schedule_complain'; // NR (for Complain Job Advice -> Complain Schedule)
        } elseif ($type === 'change' || $type === 'change unit') {
            $documentType = 'job_schedule_extra'; // Use EXT for change unit as well (or define separate if needed) but user asked for Extra -> EXT
        }
        
        // Handle explicit Status-based prefixes (SUS, DPF)
        // Note: Use these only if we are generating a number specifically FOR that status
        // Usually job starts as 'scheduled' but if logic dictates:
        if (isset($jobAdvice->status) && $jobAdvice->status === 'suspend') {
             $documentType = 'job_schedule_suspend'; // SUS
        } elseif (isset($jobAdvice->status) && $jobAdvice->status === 'dpf') {
             $documentType = 'job_schedule_dpf'; // DPF
        }
        
        // Generate job number using DocumentNumberService
        $jobNumber = $documentNumberService->generate(
            $documentType,
            null, // Branch code will be determined from context
            $building->id ?? null,
            $jobAdvice->contract_id,
            $jobAdvice->quotation_id,
            null,
            null
        );
        
        // MOM10: Calculate period and service frequency
        // If period is provided (from generateAllServiceSchedules), use it directly
        // Otherwise, calculate based on existing service jobs (for backward compatibility)
        $serviceFrequency = null;
        $servicePeriodType = 'monthly'; // Default value to prevent null
        
        if (in_array($type, ['service', 'service_first', 'service_routine'], true)) {
            // MOM10: If period is provided, use it (from generateAllServiceSchedules)
            if ($period === null) {
                // Backward compatibility: Count existing service jobs for same contract/room to determine period number
                $contractRoom = $jaRoom->contractRoom;
                $existingServiceCount = 0;
                
                if ($jobAdvice->contract_id && $contractRoom) {
                    // Count completed service jobs for this contract room
                    $existingServiceCount = \App\Models\JobSchedule::whereHas('jobAdvice', function($q) use ($jobAdvice) {
                            $q->where('contract_id', $jobAdvice->contract_id);
                        })
                        ->whereHas('jobAdvice.rooms', function($q) use ($contractRoom) {
                            $q->where('contract_room_id', $contractRoom->id);
                        })
                        ->whereIn('type', ['service', 'service_first', 'service_routine'])
                        ->where('status', 'completed')
                        ->count();
                }
                
                // Period = next service number (1 for first service, 2 for second, etc.)
                $period = $existingServiceCount + 1;
            }
            
            // Get service_frequency from MasterRental or provided serviceFrequencyObj
            if ($serviceFrequencyObj) {
                // MOM10: Use provided service frequency object
                $serviceFrequency = $serviceFrequencyObj->frequency_times_per_month ?? $serviceFrequencyObj->frequency_months ?? null;
                $servicePeriodType = $serviceFrequencyObj->name ?? 'monthly';
            } else {
                // Fallback: Get from rental product
                $rental = $jaRoom->rentalProduct;
                if ($rental && $rental->serviceFrequency) {
                    $serviceFrequencyObj = $rental->serviceFrequency;
                    $serviceFrequency = $serviceFrequencyObj->frequency_times_per_month ?? $serviceFrequencyObj->frequency_months ?? null;
                    $servicePeriodType = $serviceFrequencyObj->name ?? 'monthly';
                } elseif ($rental && $rental->service_frequency_id) {
                    // Fallback: get from service_frequency_id if relationship not loaded
                    $serviceFrequencyObj = \App\Models\RentalServiceFrequency::find($rental->service_frequency_id);
                    if ($serviceFrequencyObj) {
                        $serviceFrequency = $serviceFrequencyObj->frequency_times_per_month ?? $serviceFrequencyObj->frequency_months ?? null;
                        $servicePeriodType = $serviceFrequencyObj->name ?? 'monthly';
                    }
                }
            }
        } else {
            // For non-service types (install, remove, etc.), set default period and service_period_type
            $period = null;
            $servicePeriodType = 'monthly'; // Default value for non-service types
        }
        
        // MOM9: Get quotation_number if from quotation (Install Free)
        $quotationNumber = null;
        if ($jobAdvice->quotation_id && $jobAdvice->quotation) {
            $quotationNumber = $jobAdvice->quotation->quotation_number;
        } else if ($jobAdvice->contract && $jobAdvice->contract->quotation) {
            $quotationNumber = $jobAdvice->contract->quotation->quotation_number;
        }
        
        // MOM10: Calculate schedule_date based on period and service frequency
        $scheduleDate = $jobAdvice->expected_date;
        if (in_array($type, ['service', 'service_first', 'service_routine'], true) && $period !== null && $serviceFrequencyObj) {
            // Calculate schedule date based on period
            // Period 1 = expected_date (first service date)
            // Period 2 = expected_date + (1 month / frequency_times_per_month)
            // Example: frequency 1x/month, period 1 = 11 Nov, period 2 = 11 Dec
            // Example: frequency 2x/month, period 1 = 11 Nov, period 2 = 26 Nov, period 3 = 11 Dec
            
            $baseDate = \Carbon\Carbon::parse($jobAdvice->expected_date);
            
            if ($serviceFrequencyObj->frequency_times_per_month && $serviceFrequencyObj->frequency_times_per_month > 0) {
                // Service frequency is per month (e.g., 1x/month, 2x/month)
                // Calculate which month this period falls into
                $monthsToAdd = floor(($period - 1) / $serviceFrequencyObj->frequency_times_per_month);
                
                // Calculate the day within the month (for multiple services per month)
                $serviceIndexInMonth = (($period - 1) % $serviceFrequencyObj->frequency_times_per_month);
                
                // Start from the base date, add months
                $targetMonth = $baseDate->copy()->addMonths($monthsToAdd);
                
                // For multiple services per month, distribute evenly
                if ($serviceFrequencyObj->frequency_times_per_month > 1 && $serviceIndexInMonth > 0) {
                    // Calculate days to add within the month
                    $daysInMonth = $targetMonth->daysInMonth;
                    $daysPerService = floor($daysInMonth / $serviceFrequencyObj->frequency_times_per_month);
                    $daysToAdd = $serviceIndexInMonth * $daysPerService;
                    $scheduleDate = $targetMonth->copy()->addDays($daysToAdd);
                } else {
                    // First service of the month, use the same day
                    $scheduleDate = $targetMonth;
                }
            } elseif ($serviceFrequencyObj->frequency_months && $serviceFrequencyObj->frequency_months > 0) {
                // Service frequency is every N months (e.g., every 3 months)
                $monthsToAdd = ($period - 1) * $serviceFrequencyObj->frequency_months;
                $scheduleDate = $baseDate->copy()->addMonths($monthsToAdd);
            } else {
                // Default: monthly (1 month per period)
                $scheduleDate = $baseDate->copy()->addMonths($period - 1);
            }
            
            \Log::info("ðŸ“… Calculated schedule_date for service period {$period}: {$scheduleDate->format('Y-m-d')} (base: {$baseDate->format('Y-m-d')}, frequency: {$serviceFrequencyObj->frequency_times_per_month}x/month)");
        }
        
        return $jobScheduleModel::create([
            'job_number' => $jobNumber,
            'type' => $type,
            'status' => 'scheduled', // MOM9: Will auto-update to 'new_job' or 'assign_team' when team assigned
            'job_advice_id' => $jobAdvice->id,
            'building_id' => $building->id,
            'building_name' => $building->nama_gedung ?? $building->name,
            'company_name' => $jobAdvice->company_name,
            'contract_number' => $jobAdvice->contract->contract_number ?? null,
            'quotation_number' => $quotationNumber, // MOM9: For Install Free from Quotation
            'schedule_date' => $scheduleDate instanceof \Carbon\Carbon ? $scheduleDate->format('Y-m-d') : $scheduleDate,
            'expected_date' => $jobAdvice->expected_date,
            'period' => $period, // MOM8: Urutan service ke berapa (1, 2, 3, dst), bukan service_frequency
            'service_frequency' => $serviceFrequency, // MOM8: Diambil dari MasterRental, bukan contract
            'service_period_type' => $servicePeriodType, // MOM8: Diambil dari MasterRental, default 'monthly' if not available
            'internal_notes' => "Auto-generated from JA: {$jobAdvice->job_advice_number} | Room: {$jaRoom->room_name} | Rental: {$jaRoom->rental_name}",
            'material_checked' => $isUnitOnlyCheckFlow,
            'material_checked_at' => $isUnitOnlyCheckFlow ? now() : null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);
    }

    /**
     * Create JobSchedule(s) for each room with SHARED job_number
     * 
     * Each room gets its own JobSchedule record, but all share the same job_number
     * This allows per-room material issue and team assignment while grouping as 1 task
     * 
     * Example: 3 rooms = 3 JobSchedule records with same job_number (e.g., IR-001)
     * 
     * @param JobAdvice $jobAdvice
     * @param \Illuminate\Support\Collection $rooms Collection of JA Rooms
     * @param string $type Job type (install, service, remove, etc.)
     * @param $building
     * @param string $roomListNote Note with room list
     * @param int|null $period Service period (for service type)
     * @return \Illuminate\Support\Collection Collection of created JobSchedule models
     */
    private function createJobSchedulesPerRoom(JobAdvice $jobAdvice, $rooms, string $type, $building, $roomListNote = '', $period = null, bool $forceMaterialChecked = false)
    {
        $jobScheduleModel = \App\Models\JobSchedule::class;
        $createdJobSchedules = collect();
        
        if ($rooms->isEmpty()) {
            \Log::warning("âš ï¸ No rooms provided to createJobSchedulesPerRoom");
            return $createdJobSchedules;
        }
        
        // Normalize unsupported legacy pseudo-types before writing to job_schedules.type.
        // "check" is a unit-only service/check workflow, but the production enum stores it
        // as service_first/service_routine and marks material as already checked.
        $requestedType = strtolower(trim($type));
        $isLegacyCheckType = $requestedType === 'check';
        if ($isLegacyCheckType) {
            $type = ($period !== null && (int) $period === 1) ? 'service_first' : 'service_routine';
            $forceMaterialChecked = true;
        }
        $isUnitOnlyCheckFlow = $isLegacyCheckType
            || (in_array($type, ['service', 'service_first', 'service_routine'], true)
                && $this->roomsRepresentUnitOnlyCheckFlow($rooms));
        $forceMaterialChecked = $forceMaterialChecked || $isUnitOnlyCheckFlow;
        $isRemoveType = in_array(strtolower(trim($type)), ['remove', 'remove_free', 'remove free'], true);

        if ($isRemoveType) {
            $rooms = $this->filterRemoveRoomsWithActiveOnWallUnits($jobAdvice, $rooms, $building);

            if ($rooms->isEmpty()) {
                \Log::warning("No active Unit On Wall with serial number found for remove Job Advice {$jobAdvice->job_advice_number}. No RV/RF job schedule created.");
                return $createdJobSchedules;
            }
        }

        // Check if this is an Install Free (from quotation)
        $jobAdviceType = strtolower($jobAdvice->type ?? 'install');
        $isInstallFree = ($jobAdviceType === 'install free' || $jobAdviceType === 'install_free');
        
        // Generate SINGLE shared job_number for ALL rooms of this type
        $documentNumberService = new DocumentNumberService();
        
        // Map type to document type code for number generation
        $documentTypeMap = [
            'install' => 'installation_report',           // IR-xxx
            'install_free' => 'installation_free',        // IF-xxx
            'service' => 'customer_service_report',       // CSR-xxx (legacy)
            'service_first' => 'customer_service_report', // CSR-xxx (first service with IR)
            'service_routine' => 'customer_service_report', // CSR-xxx (routine/auto-gen)
            'remove' => 'remove',                         // RV-xxx
            'remove_free' => 'remove_free',               // RF-xxx (from install free)
            'maintenance' => 'job_schedule',              // JS-xxx
        ];
        
        $documentType = $isUnitOnlyCheckFlow
            ? 'installation_report'
            : ($documentTypeMap[$type] ?? 'job_schedule');
        
        // Generate job number ONCE - all rooms will share this number
        // UPDATE MOM: Job Number generated ONLY upon assignment (set to NULL initially)
        $sharedJobNumber = null;
        /*
        $sharedJobNumber = $documentNumberService->generate(
            $documentType,
            null,
            $building->id ?? null,
            $jobAdvice->contract_id,
            $jobAdvice->quotation_id,
            null,
            null
        );
        */
        
        \Log::info("ðŸ”§ Creating {$rooms->count()} JobSchedule(s) with pending job_number (Type: {$type})");
        
        // Get common data
        $quotationNumber = null;
        if ($jobAdvice->quotation_id && $jobAdvice->quotation) {
            $quotationNumber = $jobAdvice->quotation->quotation_number;
        } else if ($jobAdvice->contract && $jobAdvice->contract->quotation) {
            $quotationNumber = $jobAdvice->contract->quotation->quotation_number;
        }
        
        // Get service frequency from first room (for service type)
        $serviceFrequency = null;
        $servicePeriodType = 'monthly';
        $scheduleDate = $jobAdvice->expected_date;
        
        if (in_array($type, ['service', 'service_first', 'service_routine']) && $rooms->isNotEmpty()) {
            $firstRoom = $rooms->first();
            $rental = $firstRoom->rentalProduct;
            
            if ($rental && $rental->serviceFrequency) {
                $serviceFrequencyObj = $rental->serviceFrequency;
                $serviceFrequency = $serviceFrequencyObj->frequency_times_per_month ?? $serviceFrequencyObj->frequency_months ?? null;
                $servicePeriodType = $serviceFrequencyObj->name ?? 'monthly';
                
                // Calculate schedule date for service period
                if ($period !== null && $period > 0) {
                    $baseDate = \Carbon\Carbon::parse($jobAdvice->expected_date);
                    $frequencyMonths = $serviceFrequencyObj->frequency_months ?? 1;
                    $scheduleDate = $baseDate->copy()->addMonths(($period - 1) * $frequencyMonths);
                }
            }
        }
        
        // Create 1 JobSchedule PER ROOM with SAME job_number
        // Group rooms by their source room ID to ensure one schedule per room even if multiple rentals
        $groupedRooms = $rooms->groupBy(function($item) use ($isRemoveType) {
            if ($isRemoveType) {
                $roomId = $item->room_id
                    ?? $item->contractRoom?->room_id
                    ?? $item->quotationRoom?->room_id;

                if ($roomId) {
                    return 'room_' . $roomId;
                }
            }

            return $item->contract_room_id ? 'c_' . $item->contract_room_id : 'q_' . $item->quotation_room_id;
        });

        foreach ($groupedRooms as $roomGroup) {
            $jaRoom = $roomGroup->first(); // Take first room in group as representative for schedule data
            
            // Get room_id and building from contractRoom or quotationRoom
            $roomId = null;
            $roomBuilding = $building; // Default to passed building
            
            if ($jaRoom->contractRoom && $jaRoom->contractRoom->room) {
                $roomId = $jaRoom->contractRoom->room_id;
                if ($jaRoom->contractRoom->room->building) {
                    $roomBuilding = $jaRoom->contractRoom->room->building;
                }
            } elseif ($jaRoom->quotationRoom && $jaRoom->quotationRoom->room) {
                $roomId = $jaRoom->quotationRoom->room_id;
                if ($jaRoom->quotationRoom->room->building) {
                    $roomBuilding = $jaRoom->quotationRoom->room->building;
                }
            }
            
            // Create JobSchedule for THIS ROOM
            $jobTypeLower = strtolower(trim($type));
            $doesNotNeedMaterial = $forceMaterialChecked || in_array($jobTypeLower, ['remove', 'remove_free', 'remove free'], true);

            $jobSchedule = $jobScheduleModel::create([
                'job_number' => $sharedJobNumber, // SAME job_number for all rooms
                'type' => $type,
                'status' => 'scheduled',
                'job_advice_id' => $jobAdvice->id,
                'building_id' => $roomBuilding->id,
                'building_name' => $roomBuilding->nama_gedung ?? $roomBuilding->name,
                'room_id' => $roomId, // Direct room reference
                'room_name' => $jaRoom->room_name, // Direct room name
                'company_name' => $jobAdvice->company_name,
                'contract_number' => $jobAdvice->contract->contract_number ?? null,
                'quotation_number' => $quotationNumber,
                'schedule_date' => $scheduleDate instanceof \Carbon\Carbon ? $scheduleDate->format('Y-m-d') : $scheduleDate,
                'expected_date' => $jobAdvice->expected_date,
                'period' => $period,
                'service_frequency' => $serviceFrequency,
                'service_period_type' => $servicePeriodType,
                'internal_notes' => "Auto-generated from JA: {$jobAdvice->job_advice_number} | Room: {$jaRoom->room_name}",
                'reference_number' => $jobAdvice->job_advice_number,
                'postal_code' => $roomBuilding->kode_pos ?? $roomBuilding->postal_code ?? null,
                'district' => $roomBuilding->district?->name ?? null,
                'sub_district' => $roomBuilding->subdistrict?->name ?? null,
                'material_checked' => $doesNotNeedMaterial,
                'material_checked_at' => $doesNotNeedMaterial ? now() : null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);
            
            // Create SINGLE JobScheduleRoom for this room (using first jaRoom as reference)
            // MOM: SOP is 1 job per room, not per rental
            $jobScheduleRoom = \App\Models\JobScheduleRoom::create([
                'job_schedule_id' => $jobSchedule->id,
                'job_advice_room_id' => $jaRoom->id, // Use first jaRoom as reference (legacy support)
                'room_name' => $jaRoom->room_name,
                'room_id' => $roomId,
                'status' => \App\Models\JobScheduleRoom::STATUS_PENDING,
                'material_return_status' => \App\Models\JobScheduleRoom::MATERIAL_RETURN_NOT_REQUIRED,
                'notes' => "Rentals in this room: " . $roomGroup->count(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);
            
            // Populate pivot table with ALL rentals for future-proof multi-rental tracking
            $isFirst = true;
            foreach ($roomGroup as $rentalItem) {
                \App\Models\JobScheduleRoomRental::create([
                    'job_schedule_room_id' => $jobScheduleRoom->id,
                    'job_advice_room_id' => $rentalItem->id,
                    'is_primary' => $isFirst, // Mark first one as primary
                ]);
                $isFirst = false;
            }
            
            $createdJobSchedules->push($jobSchedule);
            
            \Log::info("   âœ… Created JobSchedule #{$jobSchedule->id} for room '{$jaRoom->room_name}' with " . $roomGroup->count() . " rental(s) linked via pivot");
        }
        
        \Log::info("âœ… Created {$createdJobSchedules->count()} JobSchedule(s) with shared job_number: {$sharedJobNumber}");
        
        return $createdJobSchedules;
    }

    private function filterRemoveRoomsWithActiveOnWallUnits(JobAdvice $jobAdvice, $rooms, $building)
    {
        $jobAdvice->loadMissing(['contract', 'quotation']);
        $customerId = $jobAdvice->customer_id
            ?? $jobAdvice->contract?->customer_id
            ?? $jobAdvice->quotation?->customer_id;
        $buildingId = $building?->id;

        if (! $customerId || ! $buildingId) {
            return collect();
        }

        $activeStatuses = ['active', 'installed', 'on_wall', 'on wall', 'onwall'];

        return collect($rooms)
            ->filter(function ($jaRoom) use ($customerId, $buildingId, $activeStatuses) {
                $roomId = $jaRoom->room_id
                    ?? $jaRoom->contractRoom?->room_id
                    ?? $jaRoom->quotationRoom?->room_id;
                $rentalId = $jaRoom->rental_product_id;

                if (! $roomId || ! $rentalId) {
                    return false;
                }

                return \App\Models\UnitOnWall::query()
                    ->where('customer_id', $customerId)
                    ->where('building_id', $buildingId)
                    ->where('room_id', $roomId)
                    ->where('rental_id', $rentalId)
                    ->whereIn('status', $activeStatuses)
                    ->whereNotNull('serial_number_id')
                    ->exists();
            })
            ->unique(function ($jaRoom) {
                $roomId = $jaRoom->room_id
                    ?? $jaRoom->contractRoom?->room_id
                    ?? $jaRoom->quotationRoom?->room_id;

                return ($roomId ? 'room:' . $roomId : 'name:' . strtolower(trim((string) $jaRoom->room_name)))
                    . ':rental:' . (int) $jaRoom->rental_product_id;
            })
            ->values();
    }
    
    /**
     * @deprecated Use createJobSchedulesPerRoom instead - kept for backward compatibility
     */
    private function createSingleJobScheduleForAllRooms(JobAdvice $jobAdvice, $rooms, string $type, $building, $roomListNote = '', $period = null)
    {
        // Redirect to new method, return first created schedule for backward compatibility
        $schedules = $this->createJobSchedulesPerRoom($jobAdvice, $rooms, $type, $building, $roomListNote, $period);
        return $schedules->first();
    }

    /**
     * OLD METHOD - Keep for backward compatibility but deprecated
     * Use createJobSchedulesFromJobAdvice() instead
     * 
     * @deprecated
     */
    private function createJobScheduleFromJobAdvice(JobAdvice $jobAdvice, Contract $contract)
    {
        // If jobAdvice has rooms, use new method
        if ($jobAdvice->rooms()->count() > 0) {
            return $this->createJobSchedulesFromJobAdvice($jobAdvice);
        }
        
        // Fallback to old single job schedule creation
        try {
            $jobScheduleModel = \App\Models\JobSchedule::class;
            $building = $contract->customer->buildings()->first();
            
            if (!$building || !$building->id) {
                \Log::warning("Customer {$contract->customer->name} has no valid buildings. Skipping JobSchedule creation for JA: {$jobAdvice->job_advice_number}");
                return;
            }

            $typeLower = strtolower($jobAdvice->type ?? '');
            $documentType = match ($typeLower) {
                'install', 'install_free', 'install free' => in_array($typeLower, ['install_free', 'install free'])
                    ? 'installation_free'
                    : 'installation_report',
                'service', 'service_first', 'service_routine' => 'customer_service_report',
                'remove', 'removal' => 'remove',
                'remove_free', 'remove free' => 'remove_free',
                default => 'job_schedule',
            };
            $jobNumber = app(\App\Services\DocumentNumberService::class)->generate(
                $documentType,
                null,
                $building->id,
                $jobAdvice->contract_id,
                $jobAdvice->quotation_id
            );
            
            $jobScheduleModel::create([
                'job_number' => $jobNumber,
                'type' => $jobAdvice->type,
                'status' => 'scheduled',
                'job_advice_id' => $jobAdvice->id,
                'building_id' => $building->id,
                'building_name' => $building->nama_gedung,
                'company_name' => $jobAdvice->company_name,
                'contract_number' => $contract->contract_number ?? null,
                'schedule_date' => $jobAdvice->expected_date,
                'expected_date' => $jobAdvice->expected_date,
                'service_period_type' => 'monthly', // Default value to prevent null
                'internal_notes' => 'Auto-generated from Job Advice: ' . $jobAdvice->job_advice_number,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);
            
            \Log::info("JobSchedule auto-created for JobAdvice: {$jobAdvice->job_advice_number}");
            
        } catch (\Exception $e) {
            \Log::error("Failed to auto-create JobSchedule for JobAdvice {$jobAdvice->job_advice_number}: " . $e->getMessage());
        }
    }
    
    /**
     * Add rooms to Job Advice
     */
    public function addRooms(Request $request, JobAdvice $jobAdvice)
    {
        // MOM9: Support both contract rooms and quotation rooms
        $validator = \Validator::make($request->all(), [
            'rooms' => 'required|array|min:1',
            'rooms.*.contract_room_id' => 'nullable|exists:contract_rooms,id',
            'rooms.*.quotation_room_id' => 'nullable|exists:quotation_rooms,id',
            'rooms.*.quotation_rental_id' => 'nullable|exists:quotation_rentals,id',
            'rooms.*.quotation_detail_id' => 'nullable|exists:quotation_details,id',
            'rooms.*.rental_product_id' => 'nullable|exists:master_rentals,id',
            'rooms.*.quantity' => 'nullable|integer|min:0',
            'rooms.*.qty_free' => 'nullable|numeric|min:0',
            'rooms.*.notes' => 'nullable|string',
        ]);
        
        // MOM9: Custom validation - at least one of contract_room_id or quotation_room_id must be provided
        $validator->after(function ($validator) use ($request) {
            foreach ($request->rooms as $index => $room) {
                if (empty($room['contract_room_id']) && empty($room['quotation_room_id'])) {
                    $validator->errors()->add("rooms.{$index}.contract_room_id", 'Either contract_room_id or quotation_room_id must be provided.');
                }
            }
        });
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            DB::beginTransaction();
            
            $addedCount = 0;
            $errors = [];
            
            foreach ($request->rooms as $roomData) {
                // MOM9: Validate that either contract_room_id or quotation_room_id is provided
                if (empty($roomData['contract_room_id']) && empty($roomData['quotation_room_id'])) {
                    \Log::warning("Room data missing both contract_room_id and quotation_room_id", ['room_data' => $roomData]);
                    continue;
                }

                if ($this->shouldSelectRoomsAfterJobAdviceCreate($jobAdvice->type)) {
                    unset(
                        $roomData['contract_rental_id'],
                        $roomData['quotation_rental_id'],
                        $roomData['quotation_detail_id'],
                        $roomData['rental_product_id']
                    );
                }
                
                // BACKEND VALIDATION FOR INSTALL JOB
                // User Request: "satu ruangan bisa lebih dari 1 unit dari contract/quotation yg berbeda"
                // Validasi unit aktif dihapus agar ruangan tetap bisa ditambahkan.
                
                // MOM9: Check if room + specific rental already added
                $exists = false;
                if (!empty($roomData['contract_room_id'])) {
                    $query = $jobAdvice->rooms()
                        ->where('contract_room_id', $roomData['contract_room_id']);
                    
                    if (!empty($roomData['contract_rental_id'])) {
                        $query->where('contract_rental_id', $roomData['contract_rental_id']);
                    }
                    $exists = $query->exists();
                } elseif (!empty($roomData['quotation_room_id'])) {
                    $query = $jobAdvice->rooms()
                        ->where('quotation_room_id', $roomData['quotation_room_id']);
                    
                    if (!empty($roomData['quotation_rental_id'])) {
                        $query->where('quotation_rental_id', $roomData['quotation_rental_id']);
                    } elseif (!empty($roomData['quotation_detail_id'])) {
                        $query->where('quotation_detail_id', $roomData['quotation_detail_id']);
                    }
                    $exists = $query->exists();
                }
                
                if ($exists) {
                    continue; // Skip if already added
                }
                
                // MOM9: Handle both contract room and quotation room
                $contractRoom = null;
                $quotationRoom = null;
                $room = null;
                $roomName = null;
                
                if (!empty($roomData['contract_room_id'])) {
                    // Get contract room data with contract
                    $contractRoom = \App\Models\ContractRoom::with(['room', 'contract'])->find($roomData['contract_room_id']);
                    if ($contractRoom) {
                        $room = $contractRoom->room;
                        $roomName = $contractRoom->room->room_name ?? 'N/A';
                    }
                } elseif (!empty($roomData['quotation_room_id'])) {
                    // Get quotation room data
                    $quotationRoom = \App\Models\QuotationRoom::with(['room'])->find($roomData['quotation_room_id']);
                    if ($quotationRoom) {
                        $room = $quotationRoom->room;
                        $roomName = $quotationRoom->room_name ?? ($room->room_name ?? 'N/A');
                    }
                }
                
                if (!$contractRoom && !$quotationRoom) {
                    \Log::warning("Room not found", ['room_data' => $roomData]);
                    continue;
                }
                
                // BACKEND VALIDATION FOR INSTALL JOB - REMOVED (MOM13: Allow multiple units per room)
                // if (strtolower($jobAdvice->type) === 'install' && $room) {
                //    $hasActiveUnit = $room->unitOnWalls()
                //        ->where('status', 'active')
                //        ->whereNotNull('serial_number_id')
                //        ->exists();
                        
                //    if ($hasActiveUnit) {
                //        \Log::warning("Skipping room addition: Room '{$room->room_name}' already has active unit.", ['room_id' => $room->id]);
                //        $errors[] = "Room '{$room->room_name}' sudah memiliki unit aktif. Tidak bisa ditambahkan untuk Install Job.";
                //        continue;
                //    }
                // }
                
                // Get rental_product_id(s) - FIX ISSUE 1: Get ALL rentals for this room
                $rentalProductDetails = [];

                // If specific rental source ID is provided, use it
                if (!empty($roomData['contract_rental_id'])) {
                    $cr = \App\Models\ContractRental::find($roomData['contract_rental_id']);
                    if ($cr) {
                        $rentalProductDetails[] = [
                            'rental_product_id' => $cr->master_rental_id,
                            'contract_rental_id' => $cr->id,
                            'quotation_rental_id' => null,
                            'quantity' => $this->sourcePaidQuantity($cr),
                            'qty_free' => $this->sourceFreeQuantity($cr),
                        ];
                    }
                } elseif (!empty($roomData['quotation_rental_id'])) {
                    $qr = \App\Models\QuotationRental::whereKey($roomData['quotation_rental_id'])
                        ->where('quotation_room_id', $roomData['quotation_room_id'] ?? null)
                        ->first();
                    if ($qr) {
                        $rentalProductDetails[] = [
                            'rental_product_id' => $qr->master_rental_id,
                            'contract_rental_id' => null,
                            'quotation_rental_id' => $qr->id,
                            'quantity' => $this->sourcePaidQuantity($qr),
                            'qty_free' => $this->sourceFreeQuantity($qr),
                        ];
                    }
                } elseif (!empty($roomData['quotation_detail_id']) && $quotationRoom) {
                    $qd = $this->resolveQuotationDetailForRoom($quotationRoom, (int) $roomData['quotation_detail_id']);
                    if ($qd) {
                        $rentalProductDetails[] = [
                            'rental_product_id' => $qd->master_rental_id,
                            'contract_rental_id' => null,
                            'quotation_rental_id' => null,
                            'quotation_detail_id' => $qd->id,
                            'quantity' => $this->sourcePaidQuantity($qd),
                            'qty_free' => $this->sourceFreeQuantity($qd),
                        ];
                    } else {
                        \Log::warning("Quotation detail does not belong to selected quotation room", ['room_data' => $roomData]);
                    }
                } elseif (!empty($roomData['rental_product_id'])) {
                    // Fallback to direct rental product ID
                    $rentalProductDetails[] = [
                        'rental_product_id' => $roomData['rental_product_id'],
                        'contract_rental_id' => null,
                        'quotation_rental_id' => null
                    ];
                }

                // If NO rental details found, try to get ALL from contract/quotation
                if (empty($rentalProductDetails)) {
                    if ($contractRoom) {
                        $contractRentals = \App\Models\ContractRental::where('contract_id', $contractRoom->contract_id)
                            ->where('room_id', $contractRoom->room_id)
                            ->get();

                        foreach ($contractRentals as $cr) {
                            $rentalProductDetails[] = [
                                'rental_product_id' => $cr->master_rental_id,
                                'contract_rental_id' => $cr->id,
                                'quotation_rental_id' => null,
                                'quantity' => $this->sourcePaidQuantity($cr),
                                'qty_free' => $this->sourceFreeQuantity($cr),
                            ];
                        }
                    } elseif ($quotationRoom) {
                        $quotationRentals = \App\Models\QuotationRental::where('quotation_room_id', $quotationRoom->id)
                            ->get();

                        if ($quotationRentals->isNotEmpty()) {
                            foreach ($quotationRentals as $qr) {
                                $rentalProductDetails[] = [
                                    'rental_product_id' => $qr->master_rental_id,
                                    'contract_rental_id' => null,
                                    'quotation_rental_id' => $qr->id,
                                    'quantity' => $this->sourcePaidQuantity($qr),
                                    'qty_free' => $this->sourceFreeQuantity($qr),
                                ];
                            }
                        } else {
                            // MOM9 Fallback: Check QuotationDetail with exact room source.
                            // Name-only matching is used only for unique room names.
                            $quotationDetails = $this->resolveQuotationDetailsForRoom($quotationRoom);
                            
                            foreach ($quotationDetails as $qd) {
                                $rentalProductDetails[] = [
                                    'rental_product_id' => $qd->master_rental_id,
                                    'contract_rental_id' => null,
                                    'quotation_rental_id' => null,
                                    'quotation_detail_id' => $qd->id,
                                    'quantity' => $this->sourcePaidQuantity($qd),
                                    'qty_free' => $this->sourceFreeQuantity($qd),
                                ];
                            }
                        }
                    }
                }

                // If still no rentals found, get first available master_rental as fallback
                if (empty($rentalProductDetails)) {
                    $defaultRental = \App\Models\MasterRental::first();
                    if ($defaultRental) {
                        $rentalProductDetails[] = [
                            'rental_product_id' => $defaultRental->id,
                            'contract_rental_id' => null,
                            'quotation_rental_id' => null,
                            'quantity' => 1 // Default
                        ];
                    }
                }

                // Skip if still no rental found
                if (empty($rentalProductDetails)) {
                    \Log::warning("No rental found for room", ['room_data' => $roomData]);
                    continue;
                }

                // FIX ISSUE 1: Create JobAdviceRoom for EACH rental
                foreach ($rentalProductDetails as $detail) {
                    $roomDataForCreate = array_merge($roomData, $detail);
                    
                    // Final duplicate check inside the loop to ensure we don't add same contract_rental_id twice
                    $existsFinal = false;
                    $sourceQty = null;
                    $sourceQtyFree = null;

                    if (!empty($detail['contract_rental_id'])) {
                        $existsFinal = $jobAdvice->rooms()->where('contract_rental_id', $detail['contract_rental_id'])->exists();
                        // Get quantity from ContractRental
                        $contractRental = \App\Models\ContractRental::find($detail['contract_rental_id']);
                        if ($contractRental) {
                            $sourceQty = $this->sourcePaidQuantity($contractRental);
                            $sourceQtyFree = $this->sourceFreeQuantity($contractRental);
                        }
                    } elseif (!empty($detail['quotation_rental_id'])) {
                        $existsFinal = $jobAdvice->rooms()->where('quotation_rental_id', $detail['quotation_rental_id'])->exists();
                        $quotationRental = \App\Models\QuotationRental::find($detail['quotation_rental_id']);
                        if ($quotationRental) {
                            $sourceQty = $this->sourcePaidQuantity($quotationRental);
                            $sourceQtyFree = $this->sourceFreeQuantity($quotationRental);
                        }
                    } elseif (!empty($detail['quotation_detail_id'])) {
                         // Fallback logic used quotation_detail_id
                         $existsFinal = $jobAdvice->rooms()->where('quotation_detail_id', $detail['quotation_detail_id'])->exists();
                         $qd = \App\Models\QuotationDetail::find($detail['quotation_detail_id']);
                         if ($qd) {
                             $sourceQty = $this->sourcePaidQuantity($qd);
                             $sourceQtyFree = $this->sourceFreeQuantity($qd);
                         }
                    }
                    
                    if ($existsFinal) continue;

                    // Use source quantity if available, otherwise fallback to request data or 1
                    if ($sourceQty !== null) {
                        $roomDataForCreate['quantity'] = $sourceQty;
                    }
                    if ($sourceQtyFree !== null) {
                        $roomDataForCreate['qty_free'] = $sourceQtyFree;
                    }

                    $jaRoom = $this->createJobAdviceRoom($jobAdvice, $roomDataForCreate);
                    if ($jaRoom) {
                        $addedCount++;
                    }
                }
            }
            
            DB::commit();
            
            if (count($errors) > 0) {
                return response()->json([
                    'status' => 'warning', // Use warning to show partial success or specific errors
                    'message' => implode("\\n", $errors) . ($addedCount > 0 ? "\\n\\n{$addedCount} room berhasil ditambahkan." : ""),
                    'success' => $addedCount > 0, // success is true if at least one was added, or usage depends on FE
                    'added_count' => $addedCount
                ]);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => "{$addedCount} ruangan berhasil ditambahkan.",
                'added_count' => $addedCount
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error adding rooms to Job Advice: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan ruangan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update room rental product (Change Rental Workflow)
     */
    public function updateRoomRental(Request $request, \App\Models\JobAdviceRoom $jobAdviceRoom)
    {
        $jobAdviceRoom->loadMissing('jobAdvice');
        if ($this->normalizedJobAdviceType($jobAdviceRoom->jobAdvice?->type) !== 'change_rental') {
            return response()->json([
                'status' => 'error',
                'message' => 'Perubahan rental harus melalui JA Change Rental.',
            ], 422);
        }

         $request->validate([
            'rental_product_id' => 'required|exists:master_rentals,id',
            'quantity' => 'nullable|integer|min:1',
            'qty_free' => 'nullable|numeric|min:0',
        ]);

        try {
            $oldRentalId = $jobAdviceRoom->rental_product_id;
            $newRentalId = $request->rental_product_id;
            $newQuantity = $request->quantity;
            $newQtyFree = $request->qty_free;

            $updateData = [
                'rental_product_id' => $newRentalId
            ];

            // Update quantity jika dikirim
            if ($newQuantity) {
                $updateData['quantity'] = $newQuantity;
            }
            if ($request->has('qty_free') && \Illuminate\Support\Facades\Schema::hasColumn('job_advice_rooms', 'qty_free')) {
                $updateData['qty_free'] = max(0, (float) $newQtyFree);
            }

            $jobAdviceRoom->update($updateData);
            
            \Log::info("Job Advice Room {$jobAdviceRoom->id} updated. Rental: {$oldRentalId} -> {$newRentalId}, Qty: " . ($newQuantity ?? 'unchanged'));
            
            return response()->json([
                'status' => 'success',
                'message' => 'Rental dan Quantity berhasil diubah.'
            ]);
        } catch (\Exception $e) {
             \Log::error("Failed to update room rental: " . $e->getMessage());
             return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah rental: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get marketing users list for dropdown
     * MOM13: Load ALL marketing users, not just current user
     */
    public function getMarketingUsers(Request $request)
    {
        try {
            // Get current user ID to mark as default
            $currentUserId = Auth::id();
            
            $qText = $request->get('q', '');
        
            $users = User::where('is_active', true)
                ->where(function($q) use ($qText) {
                    $q->where('name', 'like', '%' . $qText . '%')
                      ->orWhere('email', 'like', '%' . $qText . '%');
                })
                ->orderBy('name')
                ->limit(200)
                ->get(['id', 'name', 'salutation', 'email']);
            
            // Add role name from relationship if available
            $users = $users->map(function($user) use ($currentUserId) {
                $roleName = $user->getRoleName(); // From user_roles relationship
                return [
                    'id' => $user->id,
                    'name' => ($user->salutation ? $user->salutation . ' ' : '') . $user->name,
                    'email' => $user->email,
                    'role' => $roleName ?? $user->getAttributes()['roles'] ?? 'Marketing',
                    'is_current_user' => $user->id == $currentUserId
                ];
            });
            
            return response()->json([
                'status' => 'success',
                'data' => $users,
                'current_user_id' => $currentUserId
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get marketing users: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Generate Auto Remove job schedule for daily rentals after Install is completed
     */
    public function generateAutoRemoveDailySchedule(\App\Models\JobAdvice $jobAdvice, \App\Models\JobSchedule $installJobSchedule)
    {
        $jobAdvice->loadMissing(['rooms.contractRoom', 'rooms.quotationRoom', 'quotation', 'contract.quotation']);

        // 1. Determine rental period and unit
        $rentalPeriod = null;
        $rentalUnit = null;

        if ($jobAdvice->quotation) {
            $rentalPeriod = $jobAdvice->quotation->rental_period;
            $rentalUnit = $jobAdvice->quotation->rental_unit;
        } elseif ($jobAdvice->contract) {
            $rentalPeriod = $jobAdvice->contract->rental_period;
            $rentalUnit = $jobAdvice->contract->rental_unit;
        }

        // 2. Only process for daily rentals (unit: 'hari' or 'day')
        $dailyUnits = ['hari', 'day', 'days'];
        if (!in_array(strtolower($rentalUnit ?? ''), $dailyUnits)) {
            \Log::info("Skipping Auto-Remove generation: Rental unit is '{$rentalUnit}', not daily.");
            return null;
        }

        if (empty($rentalPeriod) || $rentalPeriod <= 0) {
            \Log::warning("Skipping Auto-Remove generation: Invalid rental period '{$rentalPeriod}'.");
            return null;
        }

        // 3. Only process for install jobs (install, trial, install_free)
        $installTypes = ['install', 'trial', 'install_free', 'installation'];
        if (!in_array(strtolower($installJobSchedule->type ?? ''), $installTypes)) {
            \Log::info("Skipping Auto-Remove generation: Job type is '{$installJobSchedule->type}', not an install job.");
            return null;
        }

        // 4. Only process if install job is completed
        if (!in_array($installJobSchedule->status, ['completed', 'done_job', 'selesai'], true)) {
            \Log::warning("Skipping Auto-Remove generation: Install job {$installJobSchedule->job_number} is not completed.");
            return null;
        }

        $completedRooms = \App\Models\JobScheduleRoom::where('job_schedule_id', $installJobSchedule->id)
            ->whereIn('status', ['completed', 'done_job', 'done job'])
            ->whereNotNull('job_advice_room_id')
            ->get()
            ->unique(function ($room) {
                if ($room->job_advice_room_id) {
                    return 'ja-room:' . $room->job_advice_room_id;
                }

                if ($room->room_id) {
                    return 'room:' . $room->room_id;
                }

                return 'name:' . strtolower(trim((string) $room->room_name));
            })
            ->values();

        if ($completedRooms->isEmpty()) {
            \Log::warning("Skipping Auto-Remove generation: Install job {$installJobSchedule->job_number} has no completed rooms.");
            return null;
        }

        $completedJobAdviceRoomIds = $completedRooms->pluck('job_advice_room_id')->unique()->values();
        $completedPhysicalRoomKeys = $completedRooms->map(function ($room) {
            if ($room->room_id) {
                return 'room:' . $room->room_id;
            }

            return 'name:' . strtolower(trim((string) $room->room_name));
        })->filter()->values();

        $roomsNeedingRemove = $jobAdvice->rooms
            ->whereIn('id', $completedJobAdviceRoomIds)
            ->unique(function ($room) {
                if ($room->id) {
                    return 'ja-room:' . $room->id;
                }

                $roomId = $room->room_id
                    ?? $room->contractRoom?->room_id
                    ?? $room->quotationRoom?->room_id;

                if ($roomId) {
                    return 'room:' . $roomId;
                }

                return 'name:' . strtolower(trim((string) $room->room_name));
            })
            ->reject(function ($room) use ($completedPhysicalRoomKeys) {
                $roomId = $room->room_id
                    ?? $room->contractRoom?->room_id
                    ?? $room->quotationRoom?->room_id;
                $key = $roomId
                    ? 'room:' . $roomId
                    : 'name:' . strtolower(trim((string) $room->room_name));

                return !$completedPhysicalRoomKeys->contains($key)
                    || $this->activeRemoveRoomExistsForJobAdviceRoom((int) $room->id);
            })
            ->values();

        if ($roomsNeedingRemove->isEmpty()) {
            \Log::info("Auto-Remove job already linked for completed rooms in install job {$installJobSchedule->job_number}, skipping.");
            return null;
        }

        // 5. Calculate removal date (ba_date or completed_at + rental_period days)
        $startDate = $installJobSchedule->ba_date ? \Carbon\Carbon::parse($installJobSchedule->ba_date) : \Carbon\Carbon::parse($installJobSchedule->completed_at);
        $removalDate = $startDate->copy()->addDays($rentalPeriod);

        \Log::info("Generating Auto-Remove job for Job Advice {$jobAdvice->job_advice_number} (Duration: {$rentalPeriod} {$rentalUnit})");

        $isInstallFree = in_array(strtolower($installJobSchedule->type ?? ''), ['install_free', 'install free', 'trial'], true)
            || in_array(strtolower($jobAdvice->type ?? ''), ['install_free', 'install free', 'trial'], true);
        $removeDocumentType = $isInstallFree ? 'remove_free' : 'remove';
        $removeJobType = $isInstallFree ? 'remove_free' : 'remove';

        // 7. Generate Job Number
        $documentNumberService = new \App\Services\DocumentNumberService();
        $jobNumber = $documentNumberService->generate(
            $removeDocumentType,
            null,
            $installJobSchedule->building_id,
            $jobAdvice->contract_id ?? null,
            $jobAdvice->quotation_id ?? null
        );

        $quotationNumber = $jobAdvice->quotation ? $jobAdvice->quotation->quotation_number : ($jobAdvice->contract ? $jobAdvice->contract->quotation->quotation_number ?? null : null);

        try {
            DB::beginTransaction();

            $removeJob = \App\Models\JobSchedule::create([
                'job_number' => $jobNumber,
                'type' => $removeJobType,
                'status' => 'scheduled',
                'job_advice_id' => $jobAdvice->id,
                'building_id' => $installJobSchedule->building_id,
                'company_name' => $jobAdvice->company_name,
                'contract_number' => $installJobSchedule->contract_number,
                'quotation_number' => $quotationNumber,
                'schedule_date' => $removalDate->toDateString(),
                'expected_date' => $removalDate->toDateString(),
                'service_period_type' => 'one_time',
                'internal_notes' => "Auto-generated Removal for daily rental ({$rentalPeriod} {$rentalUnit}).\nSource: Install Job {$installJobSchedule->job_number}\nInstall Date: {$startDate->toDateString()}\nCompleted at: " . now(),
                'created_by' => Auth::id() ?? $installJobSchedule->created_by,
                'updated_by' => Auth::id() ?? $installJobSchedule->updated_by
            ]);

            // 8. Link only rooms completed by this install job.
            foreach ($completedRooms->whereIn('job_advice_room_id', $roomsNeedingRemove->pluck('id')->all()) as $installRoom) {
                \App\Models\JobScheduleRoom::create([
                    'job_schedule_id' => $removeJob->id,
                    'job_advice_room_id' => $installRoom->job_advice_room_id,
                    'room_name' => $installRoom->room_name,
                    'room_id' => $installRoom->room_id,
                    'status' => 'pending',
                    'created_by' => Auth::id() ?? $installJobSchedule->created_by,
                    'updated_by' => Auth::id() ?? $installJobSchedule->updated_by,
                ]);
            }

            foreach ($roomsNeedingRemove as $jaRoom) {
                $jaRoom->update(['remove_job_schedule_id' => $removeJob->id]);
            }

            DB::commit();
            \Log::info("âœ… Auto-Remove job created: {$removeJob->job_number} for Date: {$removalDate->toDateString()}");
            return $removeJob;

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("âŒ Failed to create Auto-Remove job: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate removal job for free trials
     */
    public function generateRemoveFreeSchedule($requestOrJobAdvice, $idOrInstallJobSchedule = null)
    {
        $jobAdvice = $requestOrJobAdvice instanceof \App\Models\JobAdvice
            ? $requestOrJobAdvice->loadMissing(['rooms.contractRoom', 'rooms.quotationRoom', 'quotation', 'contract.quotation'])
            : \App\Models\JobAdvice::with(['rooms.contractRoom', 'rooms.quotationRoom', 'quotation', 'contract.quotation'])->findOrFail($idOrInstallJobSchedule);

        $installJobSchedule = $idOrInstallJobSchedule instanceof \App\Models\JobSchedule
            ? $idOrInstallJobSchedule
            : null;

        // Cari job 'install_free' yang sudah 'completed' atau 'done job'
        if (!$installJobSchedule) {
            $installJobSchedule = \App\Models\JobSchedule::where('job_advice_id', $jobAdvice->id)
                ->whereIn('type', ['install_free', 'install free'])
                ->whereIn('status', ['completed', 'done_job', 'done job'])
                ->first();
        }

        if (!$installJobSchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate remove free: Tidak ditemukan job Install Free yang berstatus Completed atau Done Job.'
            ], 422);
        }

        // Ambil daftar ruangan yang BERHASIL diinstal (status 'completed' atau 'done job' di JobScheduleRoom milik Install Free)
        $completedRooms = \App\Models\JobScheduleRoom::where('job_schedule_id', $installJobSchedule->id)
            ->whereIn('status', ['completed', 'done_job', 'done job'])
            ->whereNotNull('job_advice_room_id')
            ->get()
            ->unique(function ($room) {
                if ($room->job_advice_room_id) {
                    return 'ja-room:' . $room->job_advice_room_id;
                }

                if ($room->room_id) {
                    return 'room:' . $room->room_id;
                }

                return 'name:' . strtolower(trim((string) $room->room_name));
            })
            ->values();

        if ($completedRooms->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate remove free: Tidak ada ruangan dengan status Completed atau Done Job pada job Install Free.'
            ], 422);
        }

        $completedJobAdviceRoomIds = $completedRooms->pluck('job_advice_room_id')->unique()->values();
        $completedPhysicalRoomKeys = $completedRooms->map(function ($room) {
            if ($room->room_id) {
                return 'room:' . $room->room_id;
            }

            return 'name:' . strtolower(trim((string) $room->room_name));
        })->filter()->values();

        $roomsNeedingRemove = $jobAdvice->rooms
            ->whereIn('id', $completedJobAdviceRoomIds)
            ->unique(function ($room) {
                if ($room->id) {
                    return 'ja-room:' . $room->id;
                }

                $roomId = $room->room_id
                    ?? $room->contractRoom?->room_id
                    ?? $room->quotationRoom?->room_id;

                if ($roomId) {
                    return 'room:' . $roomId;
                }

                return 'name:' . strtolower(trim((string) $room->room_name));
            })
            ->reject(function ($room) use ($completedPhysicalRoomKeys) {
                $roomId = $room->room_id
                    ?? $room->contractRoom?->room_id
                    ?? $room->quotationRoom?->room_id;
                $key = $roomId
                    ? 'room:' . $roomId
                    : 'name:' . strtolower(trim((string) $room->room_name));

                return !$completedPhysicalRoomKeys->contains($key)
                    || $this->activeRemoveRoomExistsForJobAdviceRoom((int) $room->id);
            })
            ->values();

        if ($roomsNeedingRemove->isEmpty()) {
            $existingRemoveJob = \App\Models\JobSchedule::where('job_advice_id', $jobAdvice->id)
                ->whereIn('type', ['remove_free', 'remove free'])
                ->latest('id')
                ->first();

            return response()->json([
                'success' => false,
                'message' => 'Job Remove Free untuk room install yang selesai sudah ada: ' . ($existingRemoveJob?->job_number ?? '-'),
                'data' => $existingRemoveJob
            ], 422);
        }

        $documentNumberService = new \App\Services\DocumentNumberService();
        $jobNumber = $documentNumberService->generate(
            'remove_free',
            null,
            $installJobSchedule->building_id,
            $jobAdvice->contract_id ?? null,
            $jobAdvice->quotation_id ?? null
        );

        $quotationNumber = $jobAdvice->quotation ? $jobAdvice->quotation->quotation_number : ($jobAdvice->contract ? $jobAdvice->contract->quotation->quotation_number ?? null : null);

        try {
            DB::beginTransaction();

            $newSchedule = \App\Models\JobSchedule::create([
                'job_number' => $jobNumber,
                'type' => 'remove_free',
                'status' => 'new_job',
                'job_advice_id' => $jobAdvice->id,
                'building_id' => $installJobSchedule->building_id,
                'company_name' => $jobAdvice->company_name,
                'contract_number' => $installJobSchedule->contract_number,
                'quotation_number' => $quotationNumber,
                'schedule_date' => $jobAdvice->remove_date ?: now()->toDateString(),
                'expected_date' => $jobAdvice->remove_date ?: now()->toDateString(),
                'service_period_type' => 'one_time',
                'material_checked' => true,
                'material_checked_at' => now(),
                'created_by' => Auth::id() ?? $installJobSchedule->created_by,
                'updated_by' => Auth::id() ?? $installJobSchedule->updated_by
            ]);

            // Hanya daftarkan ruangan yang statusnya 'completed' dari job instalasi
            foreach ($completedRooms->whereIn('job_advice_room_id', $roomsNeedingRemove->pluck('id')->all()) as $installRoom) {
                \App\Models\JobScheduleRoom::create([
                    'job_schedule_id' => $newSchedule->id,
                    'job_advice_room_id' => $installRoom->job_advice_room_id,
                    'room_name' => $installRoom->room_name,
                    'room_id' => $installRoom->room_id,
                    'status' => 'pending',
                    'created_by' => Auth::id() ?? $installJobSchedule->created_by,
                    'updated_by' => Auth::id() ?? $installJobSchedule->updated_by,
                ]);
            }

            foreach ($roomsNeedingRemove as $jaRoom) {
                $jaRoom->update(['remove_job_schedule_id' => $newSchedule->id]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Job Remove Free berhasil dibuat: ' . $jobNumber,
                'data' => $newSchedule
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function activeRemoveRoomExistsForJobAdviceRoom(int $jobAdviceRoomId): bool
    {
        return \App\Models\JobScheduleRoom::where('job_advice_room_id', $jobAdviceRoomId)
            ->whereHas('jobSchedule', function ($query) {
                $query->whereIn('type', ['remove', 'remove_free', 'remove free'])
                    ->whereNotIn('status', ['cancelled', 'undone']);
            })
            ->exists();
    }

    private function activeRemoveRoomExistsForPhysicalRoom($roomId, ?string $roomName = null): bool
    {
        $normalizedRoomName = strtolower(trim((string) $roomName));
        if (!$roomId && $normalizedRoomName === '') {
            return false;
        }

        return \App\Models\JobScheduleRoom::where(function ($query) use ($roomId, $normalizedRoomName) {
                if ($roomId) {
                    $query->where('room_id', $roomId);
                }

                if ($normalizedRoomName !== '') {
                    $method = $roomId ? 'orWhereRaw' : 'whereRaw';
                    $query->{$method}('LOWER(TRIM(room_name)) = ?', [$normalizedRoomName]);
                }
            })
            ->whereHas('jobSchedule', function ($query) {
                $query->whereIn('type', ['remove', 'remove_free', 'remove free'])
                    ->whereNotIn('status', ['cancelled', 'undone']);
            })
            ->exists();
    }

    private function jobAdviceRoomHasActiveUnitOnWall(\App\Models\JobAdvice $jobAdvice, \App\Models\JobAdviceRoom $jaRoom, $building = null): bool
    {
        if ($jaRoom->existing_unit_on_wall_id) {
            $linkedUnitStillActive = \App\Models\UnitOnWall::whereKey($jaRoom->existing_unit_on_wall_id)
                ->whereIn('status', $this->activeUnitOnWallStatuses())
                ->first();

            if ($linkedUnitStillActive && $this->hasCompletedInstallSourceForJobAdviceRoom(
                $jobAdvice,
                $jaRoom->contractRoom,
                $jaRoom->quotationRoom,
                $jaRoom->contractRoom?->room ?? $jaRoom->quotationRoom?->room,
                $linkedUnitStillActive,
                $building
            )) {
                return true;
            }
        }

        if ($jaRoom->unit_already_installed && $this->hasCompletedInstallSourceForJobAdviceRoom(
            $jobAdvice,
            $jaRoom->contractRoom,
            $jaRoom->quotationRoom,
            $jaRoom->contractRoom?->room ?? $jaRoom->quotationRoom?->room,
            null,
            $building
        )) {
            return true;
        }

        $roomId = $jaRoom->contractRoom?->room_id ?? $jaRoom->quotationRoom?->room_id;
        $roomName = trim((string) ($jaRoom->room_name ?: ($jaRoom->contractRoom?->room?->room_name ?? $jaRoom->quotationRoom?->room?->room_name)));
        $normalizedRoomName = mb_strtolower(preg_replace('/\s+/', ' ', $roomName));
        $buildingId = $building?->id
            ?? $jaRoom->contractRoom?->room?->building_id
            ?? $jaRoom->quotationRoom?->room?->building_id
            ?? null;

        if (!$roomId && ($normalizedRoomName === '' || !$buildingId)) {
            return false;
        }

        return \App\Models\UnitOnWall::query()
            ->whereIn('status', $this->activeUnitOnWallStatuses())
            ->where(function ($query) use ($roomId, $buildingId, $normalizedRoomName) {
                if ($roomId) {
                    $query->where('room_id', $roomId);
                }

                if ($buildingId && $normalizedRoomName !== '') {
                    $query->orWhere(function ($roomQuery) use ($buildingId, $normalizedRoomName) {
                        $roomQuery->where('building_id', $buildingId)
                            ->whereRaw('LOWER(TRIM(room_name)) = ?', [$normalizedRoomName]);
                    });
                }
            })
            ->get()
            ->contains(fn ($unitOnWall) => $this->hasCompletedInstallSourceForJobAdviceRoom(
                $jobAdvice,
                $jaRoom->contractRoom,
                $jaRoom->quotationRoom,
                $jaRoom->contractRoom?->room ?? $jaRoom->quotationRoom?->room,
                $unitOnWall,
                $building
            ));
    }

    private function hasCompletedInstallSourceForJobAdviceRoom(
        \App\Models\JobAdvice $jobAdvice,
        $contractRoom = null,
        $quotationRoom = null,
        $room = null,
        ?\App\Models\UnitOnWall $unitOnWall = null,
        $building = null
    ): bool {
        $jobAdvice->loadMissing(['contract.quotation', 'quotation']);

        $contractNumber = $jobAdvice->contract?->contract_number;
        $quotationId = $jobAdvice->quotation_id ?? $jobAdvice->contract?->quotation_id;
        $quotationNumber = $jobAdvice->quotation?->quotation_number ?? $jobAdvice->contract?->quotation?->quotation_number;

        if (! $jobAdvice->contract_id && ! $contractNumber && ! $quotationId && ! $quotationNumber) {
            return false;
        }

        $roomId = $contractRoom?->room_id ?? $quotationRoom?->room_id ?? $room?->id ?? $unitOnWall?->room_id;
        $roomName = trim((string) ($room?->room_name ?? $unitOnWall?->room_name ?? ''));
        $normalizedRoomName = mb_strtolower(preg_replace('/\s+/', ' ', $roomName));
        $buildingId = $building?->id ?? $room?->building_id ?? $unitOnWall?->building_id ?? null;

        return \App\Models\JobSchedule::query()
            ->whereIn(\Illuminate\Support\Facades\DB::raw("LOWER(REPLACE(COALESCE(type, ''), ' ', '_'))"), ['install', 'install_free', 'if'])
            ->whereIn('status', ['completed', 'done_job', 'selesai'])
            ->where(function ($sourceQuery) use ($jobAdvice, $contractNumber, $quotationId, $quotationNumber) {
                if ($contractNumber) {
                    $sourceQuery->where('contract_number', $contractNumber);
                }

                if ($quotationNumber) {
                    $sourceQuery->orWhere('quotation_number', $quotationNumber);
                }

                if ($jobAdvice->contract_id) {
                    $sourceQuery->orWhereHas('jobAdvice', function ($jobAdviceQuery) use ($jobAdvice) {
                        $jobAdviceQuery->where('contract_id', $jobAdvice->contract_id);
                    });
                }

                if ($quotationId) {
                    $sourceQuery->orWhereHas('jobAdvice', function ($jobAdviceQuery) use ($quotationId) {
                        $jobAdviceQuery->where('quotation_id', $quotationId);
                    });
                }
            })
            ->where(function ($roomQuery) use ($roomId, $buildingId, $normalizedRoomName) {
                if ($roomId) {
                    $roomQuery->where('room_id', $roomId);
                }

                if ($buildingId && $normalizedRoomName !== '') {
                    $roomQuery->orWhere(function ($namedRoomQuery) use ($buildingId, $normalizedRoomName) {
                        $namedRoomQuery->where('building_id', $buildingId)
                            ->whereRaw('LOWER(TRIM(room_name)) = ?', [$normalizedRoomName]);
                    });
                }
            })
            ->exists();
    }

    private function validateJobAdviceSourceDate(Request $request, ?int $contractId, ?int $quotationId, $expectedDate)
    {
        $sourceDate = null;
        $sourceLabel = null;

        if ($contractId) {
            $contract = Contract::find($contractId);
            $sourceDate = $contract?->contract_date;
            $sourceLabel = 'Contract';
        } elseif ($quotationId) {
            $quotation = \App\Models\Quotation::find($quotationId);
            $sourceDate = $quotation?->quotation_date;
            $sourceLabel = 'SQ';
        }

        if (!$sourceDate || !$expectedDate) {
            return null;
        }

        $expected = \Carbon\Carbon::parse($expectedDate)->startOfDay();
        $minimum = \Carbon\Carbon::parse($sourceDate)->startOfDay();

        if ($expected->greaterThanOrEqualTo($minimum)) {
            return null;
        }

        $message = sprintf(
            'Tanggal Job Advice tidak boleh lebih kecil dari tanggal %s (%s).',
            $sourceLabel,
            $minimum->format('d/m/Y')
        );

        if ($request->expectsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'status' => 'error',
                'message' => $message,
                'errors' => [
                    'expected_date' => [$message],
                ],
            ], 422);
        }

        return back()
            ->withInput()
            ->withErrors(['expected_date' => $message])
            ->with('error', $message);
    }
}

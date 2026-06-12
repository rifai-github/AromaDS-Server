<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\Contract;
use App\Models\ContractBuilding;
use App\Models\ContractRental;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\CustomerTax;
use App\Models\FinanceTaxCode;
use App\Models\TaxSetting;
use App\Models\User;
use App\Models\TaxCode;
use App\Models\Building;
use App\Models\BankPayment;
use App\Models\MasterOption;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ContractWizardController extends Controller
{
    /**
     * Build a normalized quotation payload for the wizard.
     */
    private function buildQuotationWizardPayload(Quotation $quotation): array
    {
        $quotation->loadMissing('customer.customerTaxSettings');

        if ($quotation->customer) {
            $this->attachCustomerTaxSettingsFallback($quotation->customer);
            $quotation->setRelation('customer', $quotation->customer);
        }

        return [
            'success' => true,
            'quotation' => $quotation,
            'customer' => $quotation->customer,
            'marketing' => $quotation->marketing,
            'quotationDetails' => $quotation->quotationDetails->values(),
        ];
    }

    private function attachCustomerTaxSettingsFallback(Customer $customer): void
    {
        $customer->loadMissing('customerTaxSettings');

        if ($customer->customerTaxSettings->isNotEmpty()) {
            return;
        }

        $normalizedName = trim((string) $customer->name);
        if ($normalizedName === '') {
            return;
        }

        $fallbackTaxes = CustomerTax::query()
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('status', 'active');
            })
            ->whereHas('customer', function ($query) use ($customer, $normalizedName) {
                $query->where('id', '!=', $customer->id)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($normalizedName)]);
            })
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get();

        if ($fallbackTaxes->isNotEmpty()) {
            $customer->setRelation('customerTaxSettings', $fallbackTaxes);
        }
    }

    /**
     * Show the contract wizard form
     */
    public function create()
    {
        $user = Auth::user();
        
        // Check if user has permission to see all quotations (Management/Admin level)
        $isAdminOrManagement = $user->hasPermission('marketing.contracts.approve');
        
        // Get approved quotations
        // If user is admin/management manager, show all quotations
        // Otherwise, show only quotations for current user (marketing)
        $quotationsQuery = Quotation::with(['customer.customerTaxSettings', 'marketing'])
            ->usable()
            ->where('status', 'approved')
            ->whereDoesntHave('contracts');
        
        if (!$isAdminOrManagement) {
            // Regular user: only see their own quotations
            $quotationsQuery->where('marketing_id', $user->id);
        }
        // Admin/Management Manager: see all quotations (no additional where clause)
        
        $quotations = $quotationsQuery->get();


        // Get users for signing
        $users = User::where('is_active', true)
            ->get(['id', 'name', 'position_name']);

        // Get customer contacts
        $customerContacts = CustomerContact::with(['customer'])
            ->where('is_active', true)
            ->get();

        // Get salutation options from master option ID 13
        $salutationOption = MasterOption::with(['optionDetails' => function($query) {
            $query->where('is_active', true)->orderBy('option_name');
        }])->find(13);
        $salutations = $salutationOption ? $salutationOption->optionDetails : collect();
        
        // Get position options from master data
        $positionOption = MasterOption::where('name', 'Position')->first();
        if ($positionOption) {
            $positions = $positionOption->optionDetails()
                ->where('is_active', true)
                ->orderBy('option_name')
                ->get();
        } else {
            $positions = collect();
        }

        $defaultVatSetting = TaxSetting::getDefaultPpnSetting();
        $financeTaxCodes = FinanceTaxCode::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
        $financeTaxCodeRules = $financeTaxCodes->mapWithKeys(function (FinanceTaxCode $taxCode) {
            return [
                $taxCode->code => [
                    'code' => $taxCode->code,
                    'description' => $taxCode->description,
                    'ppn_status' => $taxCode->ppn_status,
                    'customer_status' => $taxCode->customer_status,
                    'zero_tax' => $taxCode->hasZeroTaxPrint(),
                ],
            ];
        });

        return view('marketing.contracts.wizard.create', compact(
            'quotations',
            'users',
            'customerContacts',
            'positions',
            'salutations',
            'defaultVatSetting',
            'financeTaxCodes',
            'financeTaxCodeRules'
        ));
    }

    /**
     * Save contract wizard
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quotation_id' => 'required|exists:quotations,id',
            'ppn_code' => 'nullable|string|in:01,02,03,04,05,06,07,08,09',
            'contract_date' => 'required|date',
            'install_date' => 'required|date',
            'first_service' => 'required|date',
            'pic_service_email' => 'nullable|email',
            'remark_internal' => 'nullable|string',
            'remark_external' => 'nullable|string',
            'ads_signing' => 'required|exists:users,id',
            'company_signing_1' => 'required|exists:customer_contacts,id',
            'company_signing_2' => 'nullable|exists:customer_contacts,id',
            'company_signing_3' => 'nullable|exists:customer_contacts,id',
            'company_signing_4' => 'nullable|exists:customer_contacts,id',
            'billing_addresses' => 'nullable|array',
            'billing_addresses.*.billing_group_id' => 'nullable|exists:billing_groups,id', // For reuse existing billing group
            'billing_addresses.*.buildings' => 'nullable|array',
            'billing_addresses.*.buildings.*' => 'nullable|exists:buildings,id',
            'source_contract_ids' => 'nullable|array',
            'source_contract_ids.*' => 'exists:contracts,id'
        ]);

        if ($validator->fails()) {
            \Log::info('Validation failed', [
                'errors' => $validator->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $billingAddresses = $request->input('billing_addresses', []);
        if (empty($billingAddresses) || !is_array($billingAddresses)) {
            return response()->json([
                'success' => false,
                'message' => 'Minimal satu billing group wajib dibuat dan setiap billing group wajib memiliki building.',
            ], 422);
        }

        foreach ($billingAddresses as $index => $billingAddress) {
            $hasReusableBillingGroup = !empty($billingAddress['billing_group_id']);
            $selectedBuildings = collect($billingAddress['buildings'] ?? [])
                ->filter(fn ($buildingId) => filled($buildingId))
                ->values();
            $hasSelectedBuildings = $selectedBuildings->isNotEmpty();

            if (!$hasReusableBillingGroup && !$hasSelectedBuildings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Billing Group ' . ($index + 1) . ' wajib memiliki minimal satu building.',
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            // MOM10: Generate contract number - MOVED below after fetching quotation
            // We need the quotation data to determine the branch code correctly

            // Get quotation data with quotationRooms (ordered by id to ensure consistent order)
            // Only get active quotation rooms (not soft deleted)
            $quotation = Quotation::with([
                'customer', 
                'branch', // Load branch for document numbering
                'quotationDetails', 
                'quotationRooms' => function($query) {
                    $query->orderBy('id', 'asc');
                },
                'quotationRooms.room',
                'quotationSurveys.survey' // Load all surveys for logging
            ])->find($request->quotation_id);

            if (!$quotation) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Quotation tidak ditemukan.',
                ], 404);
            }

            if (!$quotation->canCreateContract()) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Quotation ini sudah memiliki contract atau belum memenuhi syarat untuk dibuatkan contract.',
                ], 422);
            }

            if ($quotation && $quotation->quotation_type === 'renewal' && $quotation->existing_contract_id) {
                $oldContract = Contract::findRenewalSource($quotation->existing_contract_id);
                $blockReason = $oldContract?->getRenewalBlockReason();

                if (!$oldContract) {
                    \Log::warning('Renewal source contract lookup failed during contract draft creation', [
                        'quotation_id' => $quotation->id,
                        'quotation_number' => $quotation->quotation_number,
                        'existing_contract_id' => $quotation->existing_contract_id,
                    ]);
                }

                if ($blockReason) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => $blockReason,
                    ], 422);
                }
            }
            
            // Log all surveys for debugging
            if ($quotation->quotationSurveys->isNotEmpty()) {
                \Log::info("Creating contract from quotation with multiple surveys (Wizard)", [
                    'quotation_id' => $quotation->id,
                    'surveys_count' => $quotation->quotationSurveys->count(),
                    'survey_numbers' => $quotation->quotationSurveys->map(function($qs) {
                        return $qs->survey->survey_number ?? 'N/A';
                    })->toArray()
                ]);
            }
            
            // Log quotation rooms for debugging
            \Log::info("Quotation rooms for contract creation", [
                'quotation_id' => $quotation->id,
                'quotation_rooms_count' => $quotation->quotationRooms->count(),
                'quotation_rooms' => $quotation->quotationRooms->map(function($qr) {
                    return [
                        'id' => $qr->id,
                        'room_id' => $qr->room_id,
                        'room_name' => $qr->room_name,
                        'master_room_id' => $qr->room_id,
                        'master_room_name' => $qr->room ? $qr->room->room_name : 'N/A'
                    ];
                })->toArray()
            ]);

            // Create contract
            // Calculate end_date based on rental_period from quotation, NOT from TOP
            $startDate = \Carbon\Carbon::parse($request->contract_date);
            $endDate = $this->calculateEndDateFromRentalPeriod($startDate, $quotation->rental_period);
            
            // MOM10: Generate contract number using DocumentNumberService
            // Get branch code from quotation (inheritance)
            $documentNumberService = new DocumentNumberService();
            
            // Get branch code from quotation
            $branchCode = null;
            if ($quotation->branch) {
                $branchCode = $quotation->branch->code ?? $quotation->branch->branch_code;
            }
            
            $contractNumber = $documentNumberService->generate(
                'contract',
                $branchCode, // Will get from quotation branch
                null, 
                null,
                $quotation->id, // Get branch from quotation
                null,
                null
            );
            
            // Log request data for debugging
            \Log::info('Creating contract with step 3 data', [
                // 'ppn_code' => $request->ppn_code, // Removed from Step 3 / Contract level
                'install_date' => $request->install_date,
                'first_service' => $request->first_service,
                'pic_service_email' => $request->pic_service_email,
                'remark_internal' => $request->remark_internal,
                'remark_external' => $request->remark_external,
                'ads_signing' => $request->ads_signing,
                'company_signing_1' => $request->company_signing_1,
                'company_signing_2' => $request->company_signing_2,
                'company_signing_3' => $request->company_signing_3,
                'company_signing_4' => $request->company_signing_4,
            ]);

            // Get customer from quotation
            $customer = $quotation->customer;
            $customerId = $customer->id;

            // Generate or Retrieve Virtual Account
            $virtualAccountNumber = $this->getOrCreateVirtualAccount($customerId);
            
            // Auto-detect is_contract (Target Marketing)
            $isContractTarget = false;
            $period = strtolower(trim($quotation->rental_period ?? ''));
            if (preg_match('/(\d+)\s*(bulan|month|months)/i', $period, $matches)) {
                $isContractTarget = (int)$matches[1] >= 12;
            } elseif (preg_match('/(\d+)\s*(tahun|year|years)/i', $period, $matches)) {
                $isContractTarget = (int)$matches[1] >= 1;
            } elseif (preg_match('/^(\d+)$/', $period, $matches)) {
                // Number only - assume months
                $isContractTarget = (int)$matches[1] >= 12;
            } elseif ($startDate && $endDate) {
                $start = \Carbon\Carbon::parse($startDate);
                $end = \Carbon\Carbon::parse($endDate);
                $isContractTarget = $start->diffInDays($end) >= 360;
            }

            $contract = Contract::create([
                'contract_number' => $contractNumber,
                'quotation_id' => $request->quotation_id,
                'customer_id' => $quotation->customer_id,
                'marketing_id' => $quotation->marketing_id, // Add marketing_id from quotation
                'ppn_code' => $request->ppn_code,
                'contract_date' => $request->contract_date,
                'start_date' => $request->contract_date, // Use contract_date as start_date
                'end_date' => $endDate, // Based on rental_period, NOT TOP
                'contract_value' => $quotation->grand_total ?? 0, // Use quotation grand total as contract value
                'term_of_payment' => $quotation->terms_of_payment ?? $quotation->term_of_payment,
                'install_date' => $request->install_date,
                'first_service_date' => $request->first_service,
                'pic_service_email' => $request->pic_service_email,
                'virtual_account' => $virtualAccountNumber, // Add Virtual Account
                'internal_remark' => $request->remark_internal,
                'notes' => $request->remark_internal,
                'external_remark' => $request->remark_external,
                'internal_signing_id' => $request->ads_signing,
                'customer_signing_1_id' => $request->company_signing_1,
                'customer_signing_2_id' => $request->company_signing_2,
                'customer_signing_3_id' => $request->company_signing_3,
                'customer_signing_4_id' => $request->company_signing_4,
                'status' => 'draft',
                'is_contract' => $isContractTarget,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);
            
            // Log created contract data for verification
            \Log::info('Contract created successfully', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'ppn_code' => $contract->ppn_code,
                'install_date' => $contract->install_date,
                'first_service_date' => $contract->first_service_date,
                'pic_service_email' => $contract->pic_service_email,
            ]);

            // Validate: No duplicate buildings across billing groups in this contract
            $allBuildings = [];
            foreach ($request->billing_addresses as $billingAddress) {
                // Skip if reusing existing billing group (buildings already assigned)
                if (isset($billingAddress['billing_group_id']) && $billingAddress['billing_group_id']) {
                    // Get buildings from existing billing group
                    $existingBillingGroup = \App\Models\Finance\BillingGroup::find($billingAddress['billing_group_id']);
                    if ($existingBillingGroup) {
                        $existingBuildings = $existingBillingGroup->buildings()->pluck('buildings.id')->toArray();
                        foreach ($existingBuildings as $buildingId) {
                            if (in_array($buildingId, $allBuildings)) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Building dengan ID ' . $buildingId . ' dari billing group yang dipilih sudah ada di billing group lain dalam contract yang sama.'
                                ], 422);
                            }
                            $allBuildings[] = $buildingId;
                        }
                    }
                } else {
                    // New billing group - check buildings from request
                    if (isset($billingAddress['buildings']) && is_array($billingAddress['buildings'])) {
                        foreach ($billingAddress['buildings'] as $buildingId) {
                            if (in_array($buildingId, $allBuildings)) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Building dengan ID ' . $buildingId . ' tidak boleh ada di lebih dari satu billing group dalam contract yang sama.'
                                ], 422);
                            }
                            $allBuildings[] = $buildingId;
                        }
                    }
                }
            }

            // Customer ID is already set above
            // $customer = $quotation->customer;
            // $customerId = $customer->id;

            // Create or reuse billing groups for each billing address
            $billingGroups = [];
            foreach ($request->billing_addresses as $index => $billingAddress) {
                // Check if reusing existing billing group
                if (isset($billingAddress['billing_group_id']) && $billingAddress['billing_group_id']) {
                    // Reuse existing billing group (must be from same customer)
                    $existingBillingGroup = \App\Models\Finance\BillingGroup::find($billingAddress['billing_group_id']);
                    
                    if (!$existingBillingGroup) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Billing group tidak ditemukan'
                        ], 422);
                    }
                    
                    // Validate: Billing group must belong to same customer
                    if ($existingBillingGroup->customer_id != $customerId) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Billing group hanya bisa digunakan untuk customer yang sama'
                        ], 422);
                    }
                    
                    // Link existing billing group to this contract
                    $existingBillingGroup->update([
                        'contract_id' => $contract->id,
                        'updated_by' => Auth::id()
                    ]);
                    
                    $billingGroups[] = $existingBillingGroup;
                } else {
                    // Create new billing group
                    // Get tax data directly from individual fields (NEW: NPWP+NITKU paired dropdown)
                    // Log the raw data for debugging
                    \Log::info("Billing Address {$index} tax data:", [
                        'npwp_raw' => $billingAddress['npwp'] ?? 'null',
                        'nitku_raw' => $billingAddress['nitku'] ?? 'null',
                        'tax_id_raw' => $billingAddress['tax_id'] ?? 'null'
                    ]);

                    $npwp = $billingAddress['npwp'] ?? null;
                    $nitku = $billingAddress['nitku'] ?? null;
                    
                    // Sanitize inputs: if they contain the full label "NPWP: ...", extract just the number
                    if ($npwp && strpos($npwp, 'NPWP:') !== false) {
                        preg_match('/NPWP:\s*([\d\.\-]+)/', $npwp, $matches);
                        $npwp = $matches[1] ?? $npwp;
                        // Determine if it was actually just the label passed as value
                        // If it's too long, just take the first 20 chars of what's left
                         if (strlen($npwp) > 25) $npwp = substr($npwp, 0, 25);
                    }
                    if ($nitku && strpos($nitku, 'NITKU:') !== false) {
                         preg_match('/NITKU:\s*(\d+)/', $nitku, $matches);
                         $nitku = $matches[1] ?? $nitku;
                         if (strlen($nitku) > 25) $nitku = substr($nitku, 0, 25);
                    }

                    $nik = $billingAddress['nik'] ?? null;
                    $taxAddress = $billingAddress['tax_address'] ?? null;
                    
                    // Build combined tax_type and tax_number for BillingGroup storage
                    $taxTypes = [];
                    $taxNumbers = [];
                    
                    if ($npwp) {
                        $taxTypes[] = 'NPWP';
                        $taxNumbers[] = $npwp;
                    }
                    if ($nitku) {
                        $taxTypes[] = 'NITKU';
                        $taxNumbers[] = $nitku;
                    }
                    if ($nik) {
                        $taxTypes[] = 'NIK';
                        $taxNumbers[] = $nik;
                    }
                    
                    $taxType = !empty($taxTypes) ? implode(', ', $taxTypes) : null;
                    $taxNumber = !empty($taxNumbers) ? implode(', ', $taxNumbers) : null;
                    
                    // Get PIC Finance (customer contact, bukan finance user internal)
                    $picFinanceContact = null;
                    $picName = null;
                    $picEmail = null;
                    if (isset($billingAddress['pic_finance']) && $billingAddress['pic_finance']) {
                        $picFinanceContact = \App\Models\CustomerContact::find($billingAddress['pic_finance']);
                        if ($picFinanceContact) {
                            $picName = $picFinanceContact->name;
                            $picEmail = $picFinanceContact->email;
                        }
                    }
                    
                    // Get Bank Payment details
                    $bankName = null;
                    $accountName = null;
                    $accountNumber = null;
                    $virtualAccountNumber = null;
                    if (isset($billingAddress['bank_payment']) && $billingAddress['bank_payment']) {
                        $bankPayment = \App\Models\Finance\BankPayment::with('bank')->find($billingAddress['bank_payment']);
                        if ($bankPayment) {
                            $bankName = $bankPayment->bank->bank_name ?? null;
                            $accountName = $bankPayment->account_name;
                            $accountNumber = $bankPayment->account_number;
                            // Format: "Bank Name - Account Name (Account Number)"
                            $virtualAccountNumber = ($bankName ? $bankName . ' - ' : '') . $accountName . ' (' . $accountNumber . ')';
                        }
                    }
                    
                    // Get invoice type (invoice_receipt)
                    $invoiceType = $billingAddress['invoice_receipt'] ?? 'soft_copy';
                    
                    // Get mandatory tax (wajib pungut)
                    $mandatoryTax = $billingAddress['mandatory_tax'] ?? 'no';
                    
                    // Get billing email from first building
                    $billingEmail = null;
                    if (isset($billingAddress['buildings']) && is_array($billingAddress['buildings']) && count($billingAddress['buildings']) > 0) {
                        $firstBuilding = \App\Models\Building::find($billingAddress['buildings'][0]);
                        if ($firstBuilding) {
                            $billingEmail = $firstBuilding->email;
                        }
                    }
                    
                    $billingGroup = \App\Models\Finance\BillingGroup::create([
                        'billing_group_name' => "Billing Group " . ($index + 1),
                        'customer_id' => $customerId, // Parent adalah customer_id
                        'contract_id' => $contract->id, // Contract yang menggunakan billing group ini
                        'billing_frequency' => 'monthly',
                        'billing_start_date' => $contract->start_date,
                        'billing_end_date' => $contract->end_date,
                        'billing_amount' => $contract->contract_value / count($request->billing_addresses),
                        'tax_type' => $taxType,
                        'tax_number' => $taxNumber,
                        'npwp' => $npwp,
                        'nitku' => $nitku,
                        'nik' => $nik,
                        'npwp_address' => $taxAddress, // NEW: Save the tax address
                        'pic_name' => $picName,
                        'pic_email' => $picEmail ?? $billingEmail,
                        'bank_name' => $bankName,
                        'virtual_account_number' => $virtualAccountNumber,
                        'invoice_type' => $invoiceType,
                        'is_active' => true,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);
                    $billingGroups[] = $billingGroup;
                }
            }

            // Create contract buildings (only for new billing groups, not reused ones)
            foreach ($request->billing_addresses as $index => $billingAddress) {
                $billingGroup = $billingGroups[$index];
                
                // Only add buildings if this is a new billing group (not reused)
                if (!isset($billingAddress['billing_group_id']) || !$billingAddress['billing_group_id']) {
                    if (isset($billingAddress['buildings']) && is_array($billingAddress['buildings'])) {
                        foreach ($billingAddress['buildings'] as $buildingId) {
                            // Save to contract_buildings (for backward compatibility)
                            ContractBuilding::create([
                                'billing_id' => $billingGroup->id,
                                'building_id' => $buildingId,
                                'created_by' => Auth::id(),
                                'updated_by' => Auth::id()
                            ]);
                            
                            // Also save to billing_group_buildings (pivot table for BillingGroup->buildings relationship)
                            \App\Models\Finance\BillingGroupBuilding::create([
                                'billing_group_id' => $billingGroup->id,
                                'building_id' => $buildingId,
                                'is_active' => true,
                                'created_by' => Auth::id(),
                                'updated_by' => Auth::id()
                            ]);
                        }
                    }
                }
            }

            // Copy all surveys from quotation to contract
            $quotation->load('quotationSurveys.survey');
            if ($quotation->quotationSurveys->isNotEmpty()) {
                foreach ($quotation->quotationSurveys as $quotationSurvey) {
                    \App\Models\ContractSurvey::create([
                        'contract_id' => $contract->id,
                        'survey_id' => $quotationSurvey->survey_id,
                        'added_at' => now(),
                        'added_by' => Auth::id(),
                        'sort_order' => $quotationSurvey->sort_order ?? 0
                    ]);
                }
                \Log::info("Copied surveys from quotation to contract (Wizard)", [
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
                    'added_by' => Auth::id(),
                    'sort_order' => 0
                ]);
            }

            // Create contract rentals from quotation details
            
            // Get only active quotation rooms (not soft deleted and with valid room_id)
            $activeQuotationRooms = $quotation->quotationRooms->filter(function($qr) {
                return $qr->room_id !== null; // Only include rooms with valid room_id
            });
            
            foreach ($quotation->quotationDetails as $detail) {
                // QuotationDetail.room_id -> SurveyDetail.id
                // SurveyDetail.room_id -> MasterRoom.id
                $masterRoomId = null;
                if ($detail->room) {
                    $masterRoomId = $detail->room->room_id; // SurveyDetail->room_id is MasterRoom.id
                }
                
                // Fallback: If room_id is still null, try to match by room_name from quotation rooms
                if (!$masterRoomId && $detail->room_name) {
                    $matchingRoom = $activeQuotationRooms->where('room_name', $detail->room_name)->first();
                    if ($matchingRoom) {
                        $masterRoomId = $matchingRoom->room_id;
                    }
                }
                
                ContractRental::create([
                    'contract_id' => $contract->id,
                    'master_rental_id' => $detail->master_rental_id,
                    'rental_alias' => $detail->rental_alias,
                    'room_id' => $masterRoomId,
                    'quantity' => $detail->quantity,
                    'qty_free' => $detail->qty_free ?? 0,
                    'unit_price' => $detail->unit_price,
                    'total_price' => $detail->total_price,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            // Create contract rooms from quotation rooms (NOT from survey details)
            // Contract rooms harus mengikuti quotation rooms yang dipilih di wizard
            // Pastikan tidak ada duplikasi room dengan menggunakan array untuk tracking
            $addedRoomIds = [];
            
            \Log::info("Creating contract rooms from quotation rooms", [
                'quotation_id' => $quotation->id,
                'total_quotation_rooms' => $quotation->quotationRooms->count(),
                'active_quotation_rooms' => $activeQuotationRooms->count(),
                'active_rooms' => $activeQuotationRooms->map(function($qr) {
                    return [
                        'id' => $qr->id,
                        'room_id' => $qr->room_id,
                        'room_name' => $qr->room_name,
                        'master_room_name' => $qr->room ? $qr->room->room_name : 'N/A'
                    ];
                })->toArray()
            ]);
            
            if ($activeQuotationRooms && $activeQuotationRooms->count() > 0) {
                foreach ($activeQuotationRooms as $quotationRoom) {
                    // QuotationRoom sudah memiliki room_id yang mengarah ke MasterRoom
                    // Pastikan room_id valid dan belum ditambahkan
                    if ($quotationRoom->room_id && !in_array($quotationRoom->room_id, $addedRoomIds)) {
                        // Check if MasterRoom exists
                        $masterRoom = \App\Models\MasterRoom::find($quotationRoom->room_id);
                        
                        // Verify that the MasterRoom found matches the room_name from quotation room
                        if ($masterRoom && $masterRoom->room_name !== $quotationRoom->room_name) {
                            \Log::warning("MasterRoom ID {$masterRoom->id} found but room_name mismatch: MasterRoom='{$masterRoom->room_name}' vs QuotationRoom='{$quotationRoom->room_name}'. Trying to find by name.");
                            
                            // Try to find MasterRoom by room_name and building_id from quotation's survey
                            $masterRoom = null;
                            if ($quotation->survey_id) {
                                $survey = \App\Models\Survey::with('building')->find($quotation->survey_id);
                                if ($survey && $survey->building_id) {
                                    $masterRoom = \App\Models\MasterRoom::where('building_id', $survey->building_id)
                                        ->where('room_name', $quotationRoom->room_name)
                                        ->first();
                                }
                            }
                            
                            if ($masterRoom) {
                                \Log::info("Found MasterRoom by name: {$masterRoom->room_name} (Room ID: {$masterRoom->id})");
                            } else {
                                \Log::error("Cannot find MasterRoom for quotation room: {$quotationRoom->room_name} (QuotationRoom ID: {$quotationRoom->id}, room_id: {$quotationRoom->room_id})");
                            }
                        }
                        
                        if ($masterRoom) {
                            // Verify room_name matches before creating contract room
                            if ($masterRoom->room_name !== $quotationRoom->room_name) {
                                \Log::error("MasterRoom room_name mismatch: MasterRoom='{$masterRoom->room_name}' vs QuotationRoom='{$quotationRoom->room_name}'. Skipping contract room creation.");
                                continue;
                            }
                            
                            // Check if contract room already exists for this room_id
                            $existingContractRoom = \App\Models\ContractRoom::where('contract_id', $contract->id)
                                ->where('room_id', $masterRoom->id)
                                ->first();
                            
                            if (!$existingContractRoom) {
                                // Create contract room using MasterRoom from quotation
                                \App\Models\ContractRoom::create([
                                    'contract_id' => $contract->id,
                                    'room_id' => $masterRoom->id,
                                    'created_by' => Auth::id(),
                                    'updated_by' => Auth::id()
                                ]);
                                
                                $addedRoomIds[] = $masterRoom->id;
                                
                                \Log::info("Created contract room from quotation room: {$quotationRoom->room_name} (Room ID: {$masterRoom->id}, MasterRoom Name: {$masterRoom->room_name})");
                            } else {
                                \Log::info("Contract room already exists for room: {$quotationRoom->room_name} (Room ID: {$masterRoom->id})");
                            }
                        } else {
                            \Log::warning("MasterRoom not found for quotation room ID: {$quotationRoom->room_id}, Room Name: {$quotationRoom->room_name}");
                            
                            // MOM11: Fallback - Try to find or create by room_name
                            if ($quotation->survey_id) {
                                $survey = \App\Models\Survey::with('building')->find($quotation->survey_id);
                                if ($survey && $survey->building_id) {
                                    $masterRoom = \App\Models\MasterRoom::where('building_id', $survey->building_id)
                                        ->where('room_name', $quotationRoom->room_name)
                                        ->first();
                                    
                                    // If not found, auto-create master_room
                                    if (!$masterRoom) {
                                        $masterRoom = \App\Models\MasterRoom::create([
                                            'room_name' => $quotationRoom->room_name,
                                            'room_code' => strtoupper(substr($quotationRoom->room_name, 0, 3)) . '-' . $quotationRoom->id,
                                            'building_id' => $survey->building_id,
                                            'created_by' => Auth::id(),
                                            'updated_by' => Auth::id()
                                        ]);
                                        
                                        \Log::info("Auto-created MasterRoom from quotation_room", [
                                            'quotation_room_id' => $quotationRoom->id,
                                            'room_name' => $quotationRoom->room_name,
                                            'master_room_id' => $masterRoom->id,
                                            'building_id' => $survey->building_id
                                        ]);
                                    }
                                    
                                    if ($masterRoom && !in_array($masterRoom->id, $addedRoomIds)) {
                                        // Check if contract room already exists
                                        $existingContractRoom = \App\Models\ContractRoom::where('contract_id', $contract->id)
                                            ->where('room_id', $masterRoom->id)
                                            ->first();
                                        
                                        if (!$existingContractRoom) {
                                            \App\Models\ContractRoom::create([
                                                'contract_id' => $contract->id,
                                                'room_id' => $masterRoom->id,
                                                'created_by' => Auth::id(),
                                                'updated_by' => Auth::id()
                                            ]);
                                            
                                            $addedRoomIds[] = $masterRoom->id;
                                            
                                            \Log::info("Created contract room from fallback: {$quotationRoom->room_name} (Room ID: {$masterRoom->id})");
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        if (!$quotationRoom->room_id) {
                            \Log::warning("QuotationRoom has no room_id: QuotationRoom ID: {$quotationRoom->id}, Room Name: {$quotationRoom->room_name}");
                        } else {
                            \Log::info("Room ID {$quotationRoom->room_id} already added to contract, skipping duplicate");
                        }
                    }
                }
            } else {
                \Log::warning("No quotation rooms found for quotation ID: {$quotation->id}");
            }

            // Update quotation status to 'contract' after successful contract creation
            $quotation->update([
                'status' => 'contract',
                'updated_by' => Auth::id()
            ]);

            // [NEW] Execute Contract Merge if requested
            if ($request->has('source_contract_ids') && !empty($request->source_contract_ids)) {
                try {
                    $mergeService = app(\App\Services\ContractMergeService::class);
                    $mergeResult = $mergeService->execute($contract, $request->source_contract_ids);
                    
                    \Log::info("Wizard: Contract merge executed successfully during creation", [
                        'new_contract_id' => $contract->id,
                        'source_ids' => $request->source_contract_ids,
                        'stats' => $mergeResult['stats'] ?? []
                    ]);
                } catch (\Exception $me) {
                    \Log::error("Wizard: Contract merge failed, but contract creation continued", [
                        'error' => $me->getMessage(),
                        'contract_id' => $contract->id
                    ]);
                    // Kita tidak me-rollback contract utama (opsional), 
                    // tapi jika merge gagal, mungkin sebaiknya throw exception agar user tahu.
                    throw new \Exception("Gagal menggabungkan kontrak lama: " . $me->getMessage());
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
                \Log::error("Error auto-cancelling remove free jobs in Wizard: " . $e->getMessage());
                // Jangan gagalkan pembuatan kontrak jika ini gagal
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Contract created successfully',
                'contract_id' => $contract->id,
                'redirect_url' => route('marketing.contracts.show', $contract->id)
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create contract: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quotation details
     */
    public function getQuotationDetails(Request $request)
    {
        $quotationId = $request->get('quotation_id');
        
        if (!$quotationId) {
            return response()->json([
                'success' => false,
                'message' => 'Quotation ID is required'
            ]);
        }

        $quotation = Quotation::with([
            'customer.customerContacts',
            'customer.customerTaxSettings',
            'quotationDetails.masterRental',
            'marketing',
            'approver',
            'creator'
        ])->find($quotationId);

        if (!$quotation) {
            return response()->json([
                'success' => false,
                'message' => 'Quotation not found'
            ]);
        }

        if (!$quotation->canCreateContract()) {
            return response()->json([
                'success' => false,
                'message' => 'Quotation ini sudah memiliki contract atau belum memenuhi syarat untuk dibuatkan contract.'
            ], 422);
        }

        return response()->json($this->buildQuotationWizardPayload($quotation));
    }

    /**
     * Get bank payments for contract wizard without depending on company menu permission.
     */
    public function getBankPayments(Request $request)
    {
        $bankPayments = BankPayment::with('bank')
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'bankPayments' => $bankPayments,
        ]);
    }

    /**
     * Get reusable billing groups by customer (for reuse in new contract)
     */
    public function getReusableBillingGroups(Request $request)
    {
        $customerId = $request->get('customer_id');
        
        if (!$customerId) {
            return response()->json([]);
        }

        // Get billing groups from same customer that can be reused
        $billingGroups = \App\Models\Finance\BillingGroup::where('customer_id', $customerId)
            ->where('is_active', true)
            ->with(['buildings' => function($query) {
                $query->select('buildings.id', 'buildings.nama_gedung', 'buildings.name', 'buildings.alamat_1', 'buildings.address');
            }])
            ->get(['id', 'billing_group_name', 'customer_id', 'billing_frequency', 'billing_start_date', 'billing_end_date']);

        // Format response
        $formattedGroups = $billingGroups->map(function($bg) {
            return [
                'id' => $bg->id,
                'billing_group_name' => $bg->billing_group_name,
                'customer_id' => $bg->customer_id,
                'billing_frequency' => $bg->billing_frequency,
                'billing_start_date' => $bg->billing_start_date,
                'billing_end_date' => $bg->billing_end_date,
                'buildings' => $bg->buildings->map(function($building) {
                    return [
                        'id' => $building->id,
                        'name' => $building->nama_gedung || $building->name,
                        'address' => $building->alamat_1 || $building->address
                    ];
                })
            ];
        });

        return response()->json($formattedGroups);
    }

    /**
     * Get buildings by customer
     */
    public function getBuildingsByCustomer(Request $request)
    {
        $customerId = $request->get('customer_id');
        
        if (!$customerId) {
            return response()->json([]);
        }

        $buildings = Building::whereHas('customers', function($query) use ($customerId) {
                $query->where('customers.id', $customerId);
            })
            ->where('status_update', true)
            ->get(['id', 'name', 'address']);

        return response()->json($buildings);
    }

    
    private function generateContractNumber()
    {
        
        $documentNumberService = new DocumentNumberService();
        return $documentNumberService->generate('contract');
    }

    
    private function calculateEndDateFromTOP($startDate, $termOfPayment)
    {
        $start = \Carbon\Carbon::parse($startDate);
        
        // Normalize term of payment string
        $top = strtolower(trim($termOfPayment ?? ''));
        
        // Map TOP to months
        $topMapping = [
            'bulanan' => 1,
            'monthly' => 1,
            '1 bulan' => 1,
            'triwulan' => 3,
            'quarterly' => 3,
            '3 bulan' => 3,
            'semesteran' => 6,
            'semester' => 6,
            'semi-annual' => 6,
            '6 bulan' => 6,
            'tahunan' => 12,
            'annual' => 12,
            'yearly' => 12,
            '12 bulan' => 12,
            '1 tahun' => 12,
        ];
        
        // Find matching TOP
        foreach ($topMapping as $key => $months) {
            if (stripos($top, $key) !== false) {
                \Log::info("Contract period calculated: {$months} months (from TOP: {$termOfPayment})");
                return $start->addMonths($months)->toDateString();
            }
        }
        
        // Default to 12 months if can't parse
        \Log::warning("Could not parse term_of_payment: {$termOfPayment}, defaulting to 12 months");
        return $start->addMonths(12)->toDateString();
    }
    
    /**
     * Calculate end date based on rental period from quotation
     * Format: "3 bulan", "6 hari", "12 bulan", etc.
     */
    private function calculateEndDateFromRentalPeriod($startDate, $rentalPeriod)
    {
        $start = \Carbon\Carbon::parse($startDate);
        
        if (empty($rentalPeriod)) {
            \Log::warning("Empty rental_period, defaulting to 12 months");
            return $start->copy()->addMonths(12)->subDay()->toDateString();
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
                return $start->copy()->addDays($number)->subDay()->toDateString();
            } elseif (in_array($unit, ['bulan', 'month', 'months'])) {
                \Log::info("Contract period calculated: {$number} months (from rental_period: {$rentalPeriod})");
                return $start->copy()->addMonths($number)->subDay()->toDateString();
            } elseif (in_array($unit, ['tahun', 'year', 'years'])) {
                \Log::info("Contract period calculated: {$number} years (from rental_period: {$rentalPeriod})");
                return $start->copy()->addYears($number)->subDay()->toDateString();
            }
        }
        
        // If parsing failed, try to parse as number only (assume months)
        if (preg_match('/^(\d+)$/', $period, $matches)) {
            $number = (int) $matches[1];
            \Log::info("Contract period calculated: {$number} months (assuming months from number only: {$rentalPeriod})");
            return $start->copy()->addMonths($number)->subDay()->toDateString();
        }
        
        // Default to 12 months if can't parse
        \Log::warning("Could not parse rental_period: {$rentalPeriod}, defaulting to 12 months");
        return $start->copy()->addMonths(12)->subDay()->toDateString();
    }
    
    /**
     * Calculate end date based on rental period (OLD METHOD - kept for backward compatibility)
     */
    private function calculateEndDate($startDate, $rentalPeriod)
    {
        return $this->calculateEndDateFromRentalPeriod($startDate, $rentalPeriod);
    }

    /**
     * Save client contact from contract wizard modal
     */
    public function saveClientContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:100',
            'salutation' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'email_alt' => 'nullable|email|max:255',
            'phone_1' => 'nullable|string|max:50',
            'phone_2' => 'nullable|string|max:50',
            'employee_status' => 'nullable|string|in:active,leave,resigned',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contact = CustomerContact::create([
                'customer_id' => $request->customer_id,
                'name' => $request->name,
                'position' => $request->position,
                'salutation' => $request->salutation,
                'email' => $request->email,
                'phone' => $request->phone_1, // Map phone_1 to phone field
                'is_active' => $request->employee_status !== 'resigned',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Client contact saved successfully',
                'contact' => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'position' => $contact->position,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error saving client contact: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save client contact: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get existing Virtual Account for customer or create a new one automatically.
     * 
     * @param int $customerId
     * @return string|null
     */
    private function getOrCreateVirtualAccount($customerId)
    {
        // 1. Check if customer already has a Virtual Account
        $existingVA = \App\Models\CompanyVirtualAccount::where('customer_id', $customerId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($existingVA) {
            \Log::info("Using existing Virtual Account for customer {$customerId}: {$existingVA->account_number}");
            return $existingVA->account_number;
        }

        // 2. If not found, generate new one from Default Bank Payment
        $defaultBank = \App\Models\BankPayment::where('is_default_va', true)
            ->where('is_active', true)
            ->first();

        if (!$defaultBank) {
            \Log::warning("No default Bank Payment found for VA generation. Customer {$customerId} will have null VA.");
            return null;
        }

        // Lock the row to prevent race conditions
        // Note: For high concurrency, use DB::transaction and lockForUpdate
        // Since we are already inside a transaction in save(), we can lock the row.
        $defaultBank = \App\Models\BankPayment::where('id', $defaultBank->id)->lockForUpdate()->first();

        // Calculate new number
        $currentNumber = $defaultBank->current_number ?? 0;
        $nextNumber = intval($currentNumber) + 1;
        
        // Format padded number
        $length = $defaultBank->length ?? 5; // Default length 5 if not set
        $paddedNumber = str_pad($nextNumber, $length, '0', STR_PAD_LEFT);
        
        // Construct Full VA
        $prefix = $defaultBank->bank_va_number ?? '';
        // Clean prefix just in case
        $prefix = preg_replace('/\D/', '', $prefix);
        
        $fullVANumber = $prefix . $paddedNumber;
        
        \Log::info("Generating NEW Virtual Account for customer {$customerId}: {$fullVANumber}");

        // Create Company Virtual Account Record
        // We use 'customer_id' as provided.
        // account_name uses customer name or alias.
        $customer = Customer::find($customerId);
        
        \App\Models\CompanyVirtualAccount::create([
            'customer_id' => $customerId,
            'bank_payment_id' => $defaultBank->id,
            'account_number' => $fullVANumber,
            'account_name' => $customer ? $customer->name : 'Unknown Customer',
            'description' => 'Auto-generated from Contract Creation',
            'is_active' => true,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);

        // Update Bank Payment current number
        // We assume 'current_number' stores the integer suffix.
        $defaultBank->update(['current_number' => $nextNumber]);
        // OR if current_number is string, it might be tricky. 
        // Based on discussion: "auto generate berurutan misal 00001" -> implied integer counter.
        // BankPayment model casts: not strict. 
        
        return $fullVANumber;
    }
}

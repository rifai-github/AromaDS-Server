<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Models\Survey;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\QuotationRoom;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\User;
use App\Models\MasterOption;
use App\Models\TaxSetting;
use App\Models\MasterRental;
use App\Models\MasterProduct;
use App\Models\Branch;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuotationWizardController extends Controller
{
    use AccessControlFilterTrait;

    private function surveySelectionDuplicateKey(Survey $survey): string
    {
        $surveyNumber = strtolower(trim((string) $survey->survey_number));

        return $surveyNumber !== '' ? $surveyNumber : 'survey-id:' . $survey->id;
    }

    private function accessibleSurveyQuery($user = null)
    {
        $query = Survey::query();

        return $this->applyAccessControlFilter($query, $user, 'created_by', 'marketing_id', null, null, null);
    }

    private function accessibleApprovedSurveyQuery($user = null)
    {
        return $this->accessibleSurveyQuery($user)->where('status', 'approved');
    }

    private function userCanAccessMarketingId($marketingId, $user = null): bool
    {
        if (!$marketingId) {
            return false;
        }

        $user ??= Auth::user();

        if ($this->hasUnrestrictedAccessControlData($user)) {
            return true;
        }

        return in_array((int) $marketingId, array_map('intval', $this->getAccessibleUserIds($user)), true);
    }

    private function isSelectableAromaProduct(?MasterProduct $product): bool
    {
        if (!$product) {
            return false;
        }

        $name = strtolower(trim((string) $product->name));
        $sku = strtolower(trim((string) $product->sku));
        $variant = strtolower(trim((string) $product->variant_name));
        $categoryName = strtolower($product->productCategory?->name ?? '');
        $typeName = strtolower($product->productType?->name ?? '');

        $isUnit = (bool) ($product->productCategory?->is_unit ?? $product->productType?->is_unit ?? false);
        $hasSerialNumber = (bool) ($product->productCategory?->has_serial_number ?? $product->productType?->has_serial_number ?? false);
        $isTestProduct = str_contains($name, 'test')
            || str_contains($sku, 'test')
            || str_contains($variant, 'test')
            || preg_match('/^ta\d*/i', (string) $product->sku);

        $looksLikeAroma = str_contains($categoryName, 'refill')
            || str_contains($categoryName, 'aroma')
            || str_contains($categoryName, 'fragrance')
            || str_contains($categoryName, 'scent')
            || str_contains($typeName, 'aroma')
            || str_contains($typeName, 'fragrance')
            || str_contains($typeName, 'scent')
            || str_contains($typeName, 'variant')
            || str_contains($typeName, 'refill');

        return !$isUnit && !$hasSerialNumber && !$isTestProduct && $looksLikeAroma;
    }

    private function resolveCanonicalAromaProductId($productId): ?int
    {
        if (!$productId) {
            return null;
        }

        $product = MasterProduct::with(['productCategory', 'productType', 'packagingSize'])->find($productId);
        if (!$product) {
            return null;
        }

        if ($this->isSelectableAromaProduct($product)) {
            return (int) $product->id;
        }

        $variantName = trim((string) $product->variant_name);
        $brandLine = trim(strtolower((string) $product->brand_line));
        if ($variantName === '') {
            return null;
        }

        return MasterProduct::with(['productCategory', 'productType', 'packagingSize'])
            ->where('is_active', true)
            ->where('variant_name', $variantName)
            ->when($brandLine !== '', fn ($query) => $query->whereRaw('LOWER(TRIM(brand_line)) = ?', [$brandLine]))
            ->get()
            ->filter(fn ($candidate) => $this->isSelectableAromaProduct($candidate))
            ->sortBy(function ($candidate) {
                $categoryName = strtolower($candidate->productCategory?->name ?? '');
                $packageName = strtolower($candidate->packagingSize?->name ?? '');

                return [
                    str_contains($categoryName, 'refill') ? 0 : 1,
                    $packageName === '100ml' ? 0 : 1,
                    $candidate->id,
                ];
            })
            ->first()?->id;
    }

    private function sanitizeRenewalRoomName($value): string
    {
        return trim((string) preg_replace('/\s*Aroma\s*Lama\s*:.*$/iu', '', (string) ($value ?? '')));
    }

    private function masterRoomSpecifications(?\App\Models\MasterRoom $room): string
    {
        if (!$room) {
            return '';
        }

        return json_encode([
            'floor' => $room->room_floor,
            'intensity' => $room->room_intensity,
            'installation_type' => $room->room_installation_type,
            'qty' => $room->room_qty,
            'length' => $room->room_length,
            'width' => $room->room_width,
            'height' => $room->room_height,
            'temperature' => $room->room_temperature,
            'remark' => $room->room_remark,
        ]);
    }

    private function normalizeSpecifications($specifications): string
    {
        if (is_array($specifications)) {
            return json_encode($specifications);
        }

        if (is_string($specifications) && trim($specifications) !== '') {
            $decodedSpecs = json_decode($specifications, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedSpecs)) {
                return json_encode($decodedSpecs);
            }
        }

        return '';
    }

    private function resolveContractRoomFromItem(array $item, $contractId = null): ?\App\Models\ContractRoom
    {
        $contractRoomId = !empty($item['contract_room_id']) ? (int) $item['contract_room_id'] : null;
        if ($contractRoomId) {
            return \App\Models\ContractRoom::with('room')
                ->when($contractId, fn ($query) => $query->where('contract_id', $contractId))
                ->find($contractRoomId);
        }

        return null;
    }

    private function resolveQuotationRoomSelection(array $roomData): array
    {
        $roomName = $this->sanitizeRenewalRoomName($roomData['room_name'] ?? '');
        $masterRoomId = !empty($roomData['master_room_id']) ? (int) $roomData['master_room_id'] : null;
        $surveyId = $roomData['survey_id'] ?? null;
        $roomId = $roomData['survey_detail_id'] ?? $roomData['room_id'] ?? null;

        if ($surveyId && $roomId) {
            $surveyForRoom = Survey::find($surveyId);
            if ($surveyForRoom) {
                $roomDetail = $surveyForRoom->surveyDetails()->where('id', $roomId)->first();
                if ($roomDetail) {
                    $roomName = $roomDetail->room_name;

                    if ($surveyForRoom->building_id) {
                        $masterRoom = \App\Models\MasterRoom::where('building_id', $surveyForRoom->building_id)
                            ->where('room_name', $roomDetail->room_name)
                            ->first();

                        if (!$masterRoom) {
                            $masterRoom = \App\Models\MasterRoom::create([
                                'room_name' => $roomDetail->room_name,
                                'room_code' => strtoupper(substr($roomDetail->room_name, 0, 3)) . '-' . $roomDetail->id,
                                'building_id' => $surveyForRoom->building_id,
                                'room_type' => $roomDetail->room_type,
                                'room_area' => $roomDetail->room_area,
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id(),
                            ]);
                        }

                        return [$masterRoom->id, $roomName];
                    }

                    \Log::error('Cannot create/find MasterRoom: Survey has no building_id', [
                        'survey_id' => $surveyForRoom->id,
                        'survey_detail_id' => $roomDetail->id,
                        'room_name' => $roomDetail->room_name,
                    ]);
                }
            }
        }

        if ($masterRoomId) {
            $masterRoom = \App\Models\MasterRoom::find($masterRoomId);
            if ($masterRoom) {
                return [$masterRoom->id, $masterRoom->room_name ?: $roomName];
            }
        }

        if ($surveyId && $roomName !== '') {
            $surveyForRoom = Survey::find($surveyId);
            if ($surveyForRoom?->building_id) {
                $masterRoom = \App\Models\MasterRoom::where('building_id', $surveyForRoom->building_id)
                    ->where('room_name', $roomName)
                    ->first();

                if (!$masterRoom) {
                    $masterRoom = \App\Models\MasterRoom::create([
                        'room_name' => $roomName,
                        'room_code' => strtoupper(substr($roomName, 0, 3)) . '-' . uniqid(),
                        'building_id' => $surveyForRoom->building_id,
                        'room_type' => $roomData['room_type'] ?? null,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }

                return [$masterRoom->id, $roomName];
            }
        }

        return [null, $roomName !== '' ? $roomName : 'Unknown Room'];
    }

    /**
     * Store quotation data
     */
    public function getProducts(Request $request)
    {
        try {
            $query = $request->get('q', '');
            $productId = $request->get('product_id');
            $branchId = $request->get('branch_id'); // Get branch_id from request
            $surveyId = $request->get('survey_id'); // Get survey_id from request
            
            // If no branch_id provided, try to get from survey/building location
            if (!$branchId && $surveyId) {
                if (!$this->accessibleSurveyQuery()->whereKey($surveyId)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Survey is outside your accessible data scope.'
                    ], 403);
                }

                $branchId = $this->getBranchIdFromSurvey($surveyId);
            }
            
            // If still no branch_id, try to get from authenticated user
            if (!$branchId && auth()->check()) {
                $branchId = auth()->user()->branch_id;
            }
            
            // 1. Check if Customer is Corporate (Has entries in MasterCorporate)
            $corporateRentals = collect();
            $isCorporateCustomer = false;
            
            if ($surveyId) {
                $survey = Survey::find($surveyId);
                if ($survey && $survey->customer_id) {
                    $corporateRentals = \App\Models\MasterCorporate::where('customer_id', $survey->customer_id)
                        ->where('status', \App\Models\MasterCorporate::STATUS_APPROVED)
                        ->pluck('master_rental_id');
                    
                    if ($corporateRentals->isNotEmpty()) {
                        $isCorporateCustomer = true;
                    }
                }
            }

            $productsQuery = MasterRental::active();

            if ($productId) {
                $productsQuery->where('id', $productId);
            } else {
                $productsQuery->where(function($q) use ($query) {
                    $q->where('rental_name', 'like', "%{$query}%")
                      ->orWhere('rental_code', 'like', "%{$query}%")
                      ->orWhere('alias', 'like', "%{$query}%");
                });
            }

            // If Corporate Customer, filter by corporate rentals ONLY
            if ($isCorporateCustomer) {
                $productsQuery->whereIn('id', $corporateRentals);
            }

            $products = $productsQuery->select('id', 'rental_code', 'rental_name', 'daily_price', 'monthly_price', 'unit')
                ->orderBy('rental_name')
                ->limit(50)
                ->get();

            $formattedProducts = $products->map(function($product) use ($branchId, $surveyId, $isCorporateCustomer) {
                $dailyPrice = $product->daily_price;
                $monthlyPrice = $product->monthly_price;
                $isCorporatePrice = false;
                
                // 1. Check Master Corporate Price (Highest Priority)
                if ($isCorporateCustomer && $surveyId) {
                     // We already know it's a corporate customer, and this product is in the list
                     // So we just fetch the price
                     $survey = Survey::find($surveyId); // Optimizable, but safe
                     $corporatePrice = \App\Models\MasterCorporate::where('customer_id', $survey->customer_id)
                            ->where('master_rental_id', $product->id)
                            ->where('status', \App\Models\MasterCorporate::STATUS_APPROVED)
                            ->latest()
                            ->first();

                    if ($corporatePrice) {
                        $monthlyPrice = $corporatePrice->price;
                        $isCorporatePrice = true;
                    }
                }
                
                // 2. Check Branch Price (If not corporate price)
                if (!$isCorporatePrice && $branchId) {
                    $branchPrice = \App\Models\RentalPrice::where('master_rental_id', $product->id)
                        ->where('branch_id', $branchId)
                        ->first();
                    
                    if ($branchPrice) {
                        // Use branch pricing if available
                        $dailyPrice = $branchPrice->daily_price > 0 ? $branchPrice->daily_price : $dailyPrice;
                        $monthlyPrice = $branchPrice->monthly_price > 0 ? $branchPrice->monthly_price : $monthlyPrice;
                    }
                }
                
                return [
                    'id' => $product->id,
                    'text' => $product->rental_name . ' (' . $product->rental_code . ')',
                    'name' => $product->rental_name,
                    'code' => $product->rental_code,
                    'daily_price' => $dailyPrice,
                    'monthly_price' => $monthlyPrice,
                    'unit' => $product->unit,
                    'is_corporate_price' => $isCorporatePrice
                ];
            });

            return response()->json($formattedProducts);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get branch_id from survey/building location
     * Match building's city/province with branch location
     */
    private function getBranchIdFromSurvey($surveyId)
    {
        try {
            return Cache::remember("quotation-wizard:branch-from-survey:{$surveyId}", now()->addMinutes(10), function () use ($surveyId) {
                $survey = Survey::with(['building.city:id,name', 'building.province:id,name'])
                    ->select('id', 'building_id')
                    ->find($surveyId);

                if (!$survey || !$survey->building) {
                    return null;
                }

                $building = $survey->building;
                $cityId = $building->city_id;
                $provinceId = $building->province_id;

                if ($cityId) {
                    $branch = \App\Models\Branch::where('city_id', $cityId)
                        ->where('is_active', true)
                        ->first(['id']);

                    if ($branch) {
                        return $branch->id;
                    }
                }

                if ($provinceId) {
                    $branch = \App\Models\Branch::where('province_id', $provinceId)
                        ->where('is_active', true)
                        ->first(['id']);

                    if ($branch) {
                        return $branch->id;
                    }
                }

                $provinceName = strtolower($building->province->name ?? '');
                if (str_contains($provinceName, 'jakarta')) {
                    $jktBranch = \App\Models\Branch::whereIn('code', ['JKT', 'HQ', 'JAKARTA'])
                        ->where('is_active', true)
                        ->first(['id']);

                    if ($jktBranch) {
                        return $jktBranch->id;
                    }
                }

                return null;
            });
        } catch (\Exception $e) {
            \Log::warning('Error getting branch_id from survey: ' . $e->getMessage());
            return null;
        }
    }



    /**
     * Get tax settings for dropdown
     */
    public function getTaxSettings()
    {
        try {
            $taxSettings = TaxSetting::where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'tax_rate', 'tax_code']);

            return response()->json($taxSettings);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch tax settings'], 500);
        }
    }

    /**
     * Get PIC contacts for dropdown
     */
    public function getPicContacts()
    {
        try {
            $internalPics = collect();
            $selectedSurveys = request()->get('survey_ids', []);
            $customerContacts = collect();

            if (!empty($selectedSurveys)) {
                $cacheKey = 'quotation-wizard:pic-contacts:' . md5(json_encode(array_values($selectedSurveys)));

                $customerContacts = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($selectedSurveys) {
                    $customerIds = Survey::whereIn('id', $selectedSurveys)
                        ->pluck('customer_id')
                        ->unique()
                        ->filter()
                        ->toArray();

                    if (empty($customerIds)) {
                        return collect();
                    }

                    $legacyContacts = CustomerContact::with('customer')
                        ->whereIn('customer_id', $customerIds)
                        ->where('is_active', true)
                        ->get();

                    $multiPicContacts = CustomerContact::whereHas('customers', function ($q) use ($customerIds) {
                            $q->whereIn('customers.id', $customerIds);
                        })
                        ->where('is_active', true)
                        ->get();

                    return $legacyContacts->merge($multiPicContacts)->unique('id')->values();
                });
            }

            return response()->json([
                'internal_pics' => $internalPics,
                'customer_contacts' => $customerContacts
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getPicContacts: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch PIC contacts: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Debug: Log all request data
            
            // Validate the request
            $surveyTagsRule = $this->surveyTagsValidationRule($request);
            $validator = Validator::make($request->all(), [
                'marketing_id' => 'required|exists:users,id',
                'branch_id' => 'nullable|exists:branches,id',
                'quotation_date' => 'required|date',
                'quotation_type' => 'required|in:new,renewal',
                'survey_tags' => $surveyTagsRule,
                'survey_tags.*' => 'exists:surveys,id',
                'rental_period' => 'required|numeric|min:1',
                'rental_unit' => 'required|in:hari,bulan',
                'payment_method' => 'required|in:Before Service,After Service',
                'term_of_payment' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (!$this->userCanAccessMarketingId($request->get('marketing_id'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Marketing user is outside your accessible data scope.'
                ], 403);
            }

            $surveyIds = collect($request->get('survey_tags', []))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($surveyIds->isNotEmpty()) {
                $accessibleSurveyCount = $this->accessibleApprovedSurveyQuery()
                    ->whereIn('id', $surveyIds->all())
                    ->count();

                if ($accessibleSurveyCount !== $surveyIds->count()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more selected surveys are outside your accessible data scope.'
                    ], 403);
                }
            }

            $this->ensureRenewalSourceCanProceed(
                $request->get('quotation_type'),
                $request->get('existing_contract_id')
            );
            $this->ensureRenewalSourceMatchesSelection($request);

            // Get action parameter
            $action = $request->get('action', 'draft');
            $quotationDate = \Carbon\Carbon::parse($request->get('quotation_date'))->toDateString();
            $topMonths = $this->resolveTopMonthsFromRequest($request);
            
            // MOM10: Generate quotation number using DocumentNumberService
            // Get branch code from selected branch_id or from survey's building location
            $documentNumberService = new \App\Services\DocumentNumberService();
            $firstSurveyId = $request->get('survey_tags')[0] ?? null;
            
            // Get branch code from selected branch
            $branchCode = null;
            $branchId = $request->get('branch_id');
            if ($branchId) {
                $branch = Branch::find($branchId);
                $branchCode = $branch ? $branch->code : null;
            }
            
            $quotationNumber = $documentNumberService->generate(
                'quotation',
                $branchCode, // Use selected branch code
                null,
                null,
                null,
                $firstSurveyId, // Fallback: get branch from survey if branch_id not provided
                null
            );
            
            // Get survey data for the first survey (for customer info)
            $survey = null;
            $prospectId = null;
            $renewalCustomerId = null;
            $renewalCompanyName = null;
            
            if ($firstSurveyId) {
                $survey = Survey::with(['customer'])->find($firstSurveyId);
                
                // Get prospect_id from survey
                if ($survey && $survey->prospect_id) {
                    $prospectId = $survey->prospect_id;
                } else {
                    // If survey doesn't have prospect_id, try to create one from customer data
                    if ($survey && $survey->customer_id) {
                        // Check if prospect already exists for this customer
                        $existingProspect = \App\Models\Prospect::where('customer_id', $survey->customer_id)->first();
                        
                        if ($existingProspect) {
                            $prospectId = $existingProspect->id;
                        } else {
                            // Create new prospect from customer data
                            $customer = $survey->customer;
                            if ($customer) {
                                $prospect = \App\Models\Prospect::create([
                                    'company_name' => $customer->name,
                                    'contact_person' => $customer->name, // Use customer name as contact person
                                    'contact_email' => $customer->email,
                                    'contact_phone' => $customer->phone,
                                    'company_address' => $customer->address,
                                    'customer_id' => $customer->id,
                                    'status' => 'qualified',
                                    'assigned_to' => $survey->marketing_id
                                ]);
                                $prospectId = $prospect->id;
                                
                                // Update survey with prospect_id
                                // $survey->update(['prospect_id' => $prospectId]);
                            }
                        }
                    }
                }
                
                // Validate that we have a valid prospect_id
                if (!$prospectId) {
                    throw new \Exception('Unable to create quotation: Survey must be associated with a valid prospect. Please ensure the survey has prospect information.');
                }
            } elseif ($request->get('quotation_type') === 'renewal') {
                [$renewalCustomerId, $prospectId, $renewalCompanyName] = $this->resolveRenewalCustomerContext(
                    Contract::findRenewalSource($request->get('existing_contract_id'))?->load('customer'),
                    $request->get('marketing_id')
                );
            }
            
            // Create quotation
            $quotation = Quotation::create([
                'quotation_number' => $quotationNumber,
                'prospect_id' => $prospectId,
                'customer_id' => $survey ? $survey->customer_id : $renewalCustomerId,
                'survey_id' => $firstSurveyId,
                'quotation_date' => $quotationDate,
                'valid_until' => \Carbon\Carbon::parse($quotationDate)->addDays(30)->toDateString(), // 30 days validity
                'company_name' => $survey && $survey->customer ? $survey->customer->name : ($survey && $survey->prospect ? $survey->prospect->company_name : ($renewalCompanyName ?? 'Unknown Company')),
                'pic_name' => $request->get('pic_quotation') ?? 'Unknown PIC',
                'billing_methods' => $request->get('payment_method'),
                'payment_method' => $request->get('payment_method'),
                'status' => $action === 'finalize' ? 'waiting_for_approval' : 'draft',
                'rental_period' => $request->get('rental_period'),
                'rental_unit' => $request->get('rental_unit'),
                'terms_of_payment' => $request->get('term_of_payment'),
                'top_months' => $topMonths,
                'marketing_id' => $request->get('marketing_id'),
                'internal_notes' => $request->get('remark_internal'),
                'additional_notes' => $request->get('remark_external'),
                'quotation_type' => $request->get('quotation_type'),
                'existing_contract_id' => $request->get('existing_contract_id'),
                'branch_id' => $request->get('branch_id'), // Multi-branch support
                'price_basis' => $request->get('price_basis'),
                'total_amount' => $this->parseNumericValue($request->get('sub_total', '0')),
                'tax_amount' => $this->calculateTaxAmount($request->get('sub_total', '0'), $request->get('tax_id')),
                'grand_total' => $this->parseNumericValue($request->get('total_penawaran', '0')),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id()
            ]);
            
            // Attach surveys to quotation
            $surveyIds = $request->get('survey_tags', []);
            foreach ($surveyIds as $index => $surveyId) {
                $quotation->surveys()->attach($surveyId, [
                    'added_at' => now(),
                    'added_by' => auth()->id(),
                    'sort_order' => $index + 1
                ]);
            }
            
            // Create quotation details from rental items
            // Structure: rental_items[uniqueId][field] where uniqueId is like "surveyId-timestamp"
            $rentalItems = $request->get('rental_items', []);
            foreach ($rentalItems as $uniqueId => $item) {
                // Extract survey_id from item (it's stored in the item data)
                [$surveyId, $surveyDetailId] = $this->resolveQuotationDetailSurveyAndRoom(
                    $item,
                    $request->get('survey_tags', [])
                );
                
                // Get survey info for room details
                $survey = $surveyId ? Survey::with('surveyDetails')->find($surveyId) : null;
                $room = null;
                $roomName = $this->sanitizeRenewalRoomName($item['room_name'] ?? 'Unknown Room');
                $specifications = $item['specifications'] ?? '';
                $contractRoom = $this->resolveContractRoomFromItem($item, $request->get('existing_contract_id'));
                
                // Try to get room details from survey if room_id is provided
                // room_id should be survey_detail.id
                if ($survey && isset($item['room_id'])) {
                    $room = $survey->surveyDetails()->where('id', $item['room_id'])->first();
                    
                    // If room found, use its data (prioritize survey detail data)
                    if ($room) {
                        $roomName = $room->room_name;
                        // Use specifications from survey detail if available, otherwise use from form
                        $specifications = $room->specifications ?? $specifications;
                    } else {
                        // If room not found by ID, try to find by room_name
                        if (!empty($item['room_name']) && $item['room_name'] !== 'Unknown Room') {
                            $room = $survey->surveyDetails()->where('room_name', $item['room_name'])->first();
                            if ($room) {
                                $roomName = $room->room_name;
                                $specifications = $room->specifications ?? $specifications;
                            }
                        }
                    }
                }

                if (!$room && $contractRoom?->room) {
                    $roomName = $contractRoom->room->room_name;
                    $specifications = $specifications ?: $this->masterRoomSpecifications($contractRoom->room);
                }
                
                // If specifications is JSON string from form, ensure it's valid JSON
                $specifications = $this->normalizeSpecifications($specifications);
                
                // Enforce Corporate Price Validation
                if ($survey && $survey->customer_id && !empty($item['product_id'])) {
                    $corporatePrice = \App\Models\MasterCorporate::where('customer_id', $survey->customer_id)
                        ->where('master_rental_id', $item['product_id'])
                        ->where('status', \App\Models\MasterCorporate::STATUS_APPROVED)
                        ->latest()
                        ->first();

                    if ($corporatePrice) {
                        $item['price'] = $corporatePrice->price; 
                    }
                }

                [$quantity, $qtyFree] = $this->normalizeRentalItemQuantities($item);
                $unitPrice = $quantity > 0 ? ($item['price'] ?? 0) : 0;

                // Create quotation detail
                $quotation->quotationDetails()->create([
                    'survey_id' => $surveyId,
                    'room_id' => $surveyDetailId,
                    'master_rental_id' => $item['product_id'] ?? null, // Map product_id to master_rental_id
                    'rental_alias' => $item['rental_alias'] ?? null, // Rental alias per item
                    'remark' => $item['remark'] ?? null, // Remark per rental item
                    'room_name' => $roomName,
                    'quantity' => $quantity,
                    'qty_free' => $qtyFree,
                    'unit_price' => $unitPrice,
                    'total_price' => $quantity * $unitPrice,
                    'specifications' => $specifications,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id()
                ]);
            }
            
            // Save room selections with aroma data
            if ($request->has('room_selections_data')) {
                
                foreach ($request->room_selections_data as $roomData) {
                    [$masterRoomId, $roomName] = $this->resolveQuotationRoomSelection($roomData);
                    
                    // Only create QuotationRoom if we have a valid MasterRoom ID
                    if ($masterRoomId) {
                        $canonicalAromaProductId = $this->resolveCanonicalAromaProductId($roomData['aroma_product_id'] ?? null);
                        QuotationRoom::create([
                            'quotation_id' => $quotation->id,
                            'room_id' => $masterRoomId, // MasterRoom ID, not survey_detail_id
                            'room_name' => $roomName, // Use room_name from survey detail, not from master room
                            'aroma_product_id' => $canonicalAromaProductId,
                            'aroma_variant' => $roomData['aroma_variant'] ?? null,
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id()
                        ]);
                        
                    } else {
                        \Log::error("Cannot create QuotationRoom: MasterRoom not found for room_data: " . json_encode($roomData));
                    }
                }
            } else {
                \Log::warning('No room_selections_data found in request');
            }
            
            // Determine status and message based on action
            $status = $action === 'finalize' ? 'waiting_for_approval' : 'draft';
            $message = $action === 'finalize' 
                ? 'Quotation has been submitted for approval' 
                : 'Quotation saved as draft';
            
            // Log the quotation creation
            
                // Check if request is AJAX
                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'status' => $status,
                        'action' => $action,
                        'data' => $request->all(),
                        'quotation_id' => $quotation->id,
                        'redirect_url' => route('marketing.quotations.show', $quotation->id)
                    ]);
                } else {
                    // Redirect for non-AJAX requests
                    return redirect()->route('marketing.quotations.show', $quotation->id)
                        ->with('success', $message);
                }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Quotation creation error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error creating quotation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update quotation data
     */
    public function update(Request $request, $id)
    {
        try {
            // Debug: Log all request data
            
            $quotation = Quotation::findOrFail($id);

            // Validate the request
            $surveyTagsRule = $this->surveyTagsValidationRule($request);
            $validator = Validator::make($request->all(), [
                'marketing_id' => 'required|exists:users,id',
                'branch_id' => 'nullable|exists:branches,id',
                'quotation_date' => 'required|date',
                'quotation_type' => 'required|in:new,renewal',
                'survey_tags' => $surveyTagsRule,
                'survey_tags.*' => 'exists:surveys,id',
                'rental_period' => 'required|numeric|min:1',
                'rental_unit' => 'required|in:hari,bulan',
                'payment_method' => 'required|in:Before Service,After Service',
                'term_of_payment' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $this->ensureRenewalSourceCanProceed(
                $request->get('quotation_type'),
                $request->get('existing_contract_id')
            );
            $this->ensureRenewalSourceMatchesSelection($request);

            // Get action parameter
            $action = $request->get('action', 'draft');
            $quotationDate = \Carbon\Carbon::parse($request->get('quotation_date'))->toDateString();
            $topMonths = $this->resolveTopMonthsFromRequest($request);

            DB::beginTransaction();

            try {
                // Get survey data for the first survey (for customer info)
                $firstSurveyId = $request->get('survey_tags')[0] ?? null;
                $survey = null;
                $prospectId = null;
                $renewalCustomerId = null;
                $renewalCompanyName = null;
                
                if ($firstSurveyId) {
                    $survey = Survey::with(['customer'])->find($firstSurveyId);
                    
                    // Logic to find/create prospect (same as store)
                    if ($survey && $survey->prospect_id) {
                        $prospectId = $survey->prospect_id;
                    } else {
                        if ($survey && $survey->customer_id) {
                            $existingProspect = \App\Models\Prospect::where('customer_id', $survey->customer_id)->first();
                            if ($existingProspect) {
                                $prospectId = $existingProspect->id;
                            } else {
                                $customer = $survey->customer;
                                if ($customer) {
                                    $prospect = \App\Models\Prospect::create([
                                        'company_name' => $customer->name,
                                        'contact_person' => $customer->name,
                                        'contact_email' => $customer->email,
                                        'contact_phone' => $customer->phone,
                                        'company_address' => $customer->address,
                                        'customer_id' => $customer->id,
                                        'status' => 'qualified',
                                        'assigned_to' => $survey->marketing_id
                                    ]);
                                    $prospectId = $prospect->id;
                                }
                            }
                        }
                    }
                } elseif ($request->get('quotation_type') === 'renewal') {
                    [$renewalCustomerId, $prospectId, $renewalCompanyName] = $this->resolveRenewalCustomerContext(
                        Contract::findRenewalSource($request->get('existing_contract_id'))?->load('customer'),
                        $request->get('marketing_id')
                    );
                }
                
                // Update Quotation
                $quotation->update([
                    'prospect_id' => $prospectId,
                    'customer_id' => $survey ? $survey->customer_id : $renewalCustomerId,
                    'survey_id' => $firstSurveyId, // Primary survey
                    'quotation_date' => $quotationDate,
                    'valid_until' => \Carbon\Carbon::parse($quotationDate)->addDays(30)->toDateString(),
                    'company_name' => $survey && $survey->customer ? $survey->customer->name : ($survey && $survey->prospect ? $survey->prospect->company_name : ($renewalCompanyName ?? 'Unknown Company')),
                    'pic_name' => $request->get('pic_quotation') ?? 'Unknown PIC',
                    'billing_methods' => $request->get('payment_method'),
                    'payment_method' => $request->get('payment_method'),
                    'status' => $action === 'finalize' ? 'waiting_for_approval' : 'draft',
                    'rental_period' => $request->get('rental_period'),
                    'rental_unit' => $request->get('rental_unit'),
                    'terms_of_payment' => $request->get('term_of_payment'),
                    'top_months' => $topMonths,
                    'marketing_id' => $request->get('marketing_id'),
                    'internal_notes' => $request->get('remark_internal'),
                    'additional_notes' => $request->get('remark_external'),
                    'quotation_type' => $request->get('quotation_type'),
                    'existing_contract_id' => $request->get('existing_contract_id'),
                    'branch_id' => $request->get('branch_id'),
                    'price_basis' => $request->get('price_basis'),
                    'total_amount' => $this->parseNumericValue($request->get('sub_total', '0')),
                    'tax_amount' => $this->calculateTaxAmount($request->get('sub_total', '0'), $request->get('tax_id')),
                    'grand_total' => $this->parseNumericValue($request->get('total_penawaran', '0')),
                    'updated_by' => auth()->id()
                ]);

                // Sync Surveys
                $surveyIds = $request->get('survey_tags', []);
                $syncData = [];
                foreach ($surveyIds as $index => $sId) {
                    $syncData[$sId] = [
                        'added_at' => now(),
                        'added_by' => auth()->id(),
                        'sort_order' => $index + 1
                    ];
                }
                $quotation->surveys()->sync($syncData);

                // Recreate Details
                // First delete existing
                $quotation->quotationDetails()->delete();

                $rentalItems = $request->get('rental_items', []);
                foreach ($rentalItems as $uniqueId => $item) {
                     [$surveyId, $surveyDetailId] = $this->resolveQuotationDetailSurveyAndRoom(
                         $item,
                         $request->get('survey_tags', [])
                     );
                     $surveyForDetail = $surveyId ? Survey::with('surveyDetails')->find($surveyId) : null;
                     $roomName = $this->sanitizeRenewalRoomName($item['room_name'] ?? 'Unknown Room');
                     $specifications = $item['specifications'] ?? '';
                     $contractRoom = $this->resolveContractRoomFromItem($item, $request->get('existing_contract_id'));
                     $roomId = $surveyDetailId;

                     // Resolve Room Name & Specs
                     if ($surveyForDetail && $roomId) {
                         $roomDetail = $surveyForDetail->surveyDetails()->where('id', $roomId)->first();
                         if ($roomDetail) {
                             $roomName = $roomDetail->room_name;
                             $specifications = $roomDetail->specifications ?? $specifications;
                         }
                     }

                     if ((!$surveyForDetail || !$roomId) && $contractRoom?->room) {
                         $roomName = $contractRoom->room->room_name;
                         $specifications = $specifications ?: $this->masterRoomSpecifications($contractRoom->room);
                     }

                     // Decode JSON specs if needed
                     $specifications = $this->normalizeSpecifications($specifications);


                     // Enforce Corporate Price Validation
                     if ($surveyForDetail && $surveyForDetail->customer_id && !empty($item['product_id'])) {
                        $corporatePrice = \App\Models\MasterCorporate::where('customer_id', $surveyForDetail->customer_id)
                            ->where('master_rental_id', $item['product_id'])
                            ->where('status', \App\Models\MasterCorporate::STATUS_APPROVED)
                            ->latest()
                            ->first();

                        if ($corporatePrice) {
                            $item['price'] = $corporatePrice->price;
                        }
                     }

                     [$quantity, $qtyFree] = $this->normalizeRentalItemQuantities($item);
                     $unitPrice = $quantity > 0 ? ($item['price'] ?? 0) : 0;

                     $quotation->quotationDetails()->create([
                        'master_rental_id' => $item['product_id'] ?? null,
                        'rental_alias' => $item['rental_alias'] ?? null,
                        'remark' => $item['remark'] ?? null,
                        'room_name' => $roomName,
                        'survey_id' => $surveyId, // NEW COLUMN - Important for Sync!
                        'room_id' => $roomId,     // NEW COLUMN - Important for Sync!
                        'quantity' => $quantity,
                        'qty_free' => $qtyFree,
                        'unit_price' => $unitPrice,
                        'total_price' => $quantity * $unitPrice,
                        'specifications' => $specifications,
                        'created_by' => $quotation->created_by, // Preserve creator logic? Or auth?
                        'updated_by' => auth()->id()
                     ]);
                }

                // Recreate Rooms (Complex logic for MasterRoom creation kept)
                $quotation->quotationRooms()->delete();

                if ($request->has('room_selections_data')) {
                    foreach ($request->room_selections_data as $roomData) {
                        [$masterRoomId, $roomName] = $this->resolveQuotationRoomSelection($roomData);

                        if ($masterRoomId) {
                            $canonicalAromaProductId = $this->resolveCanonicalAromaProductId($roomData['aroma_product_id'] ?? null);
                            QuotationRoom::create([
                                'quotation_id' => $quotation->id,
                                'room_id' => $masterRoomId,
                                'room_name' => $roomName,
                                'aroma_product_id' => $canonicalAromaProductId,
                                'aroma_variant' => $roomData['aroma_variant'] ?? null,
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id()
                            ]);
                        }
                    }
                }

                DB::commit();

                // Response
                $status = $action === 'finalize' ? 'waiting_for_approval' : 'draft';
                $message = $action === 'finalize' ? 'Quotation updated and submitted' : 'Quotation draft updated';

                if ($request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'status' => $status,
                        'redirect_url' => route('marketing.quotations.show', $quotation->id)
                    ]);
                } else {
                    return redirect()->route('marketing.quotations.show', $quotation->id)->with('success', $message);
                }

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
             return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Quotation update error: ' . $e->getMessage());
             return response()->json([
                'success' => false,
                'message' => 'Error updating quotation: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate quotation number (DEPRECATED - Use DocumentNumberService instead)
     * @deprecated Use DocumentNumberService::generate('quotation', ...) instead
     */
    private function generateQuotationNumber()
    {
        // MOM10: This method is deprecated, but kept for backward compatibility
        // New code should use DocumentNumberService
        $documentNumberService = new \App\Services\DocumentNumberService();
        return $documentNumberService->generate('quotation');
    }
    
    /**
     * Calculate tax amount
     */
    private function calculateTaxAmount($subTotal, $taxId)
    {
        if (!$taxId) {
            return 0;
        }
        
        $taxSetting = TaxSetting::find($taxId);
        if (!$taxSetting) {
            return 0;
        }
        
        $subTotalNumeric = $this->parseNumericValue($subTotal);
        return ($subTotalNumeric * $taxSetting->tax_rate) / 100;
    }
    
    /**
     * Parse numeric value from Indonesian format
     */
    private function parseNumericValue($value)
    {
        if (empty($value)) {
            return 0;
        }
        
        // Remove all non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.,]/', '', $value);
        
        // Handle Indonesian number format (1.000.000,50)
        if (strpos($cleaned, ',') !== false && strpos($cleaned, '.') !== false) {
            // Has both comma and dot - comma is decimal separator
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif (strpos($cleaned, ',') !== false) {
            // Only comma - could be decimal separator or thousand separator
            if (substr_count($cleaned, ',') === 1 && strlen($cleaned) - strpos($cleaned, ',') <= 3) {
                // Likely decimal separator
                $cleaned = str_replace(',', '.', $cleaned);
            } else {
                // Likely thousand separator
                $cleaned = str_replace(',', '', $cleaned);
            }
        } else {
            // Only dots - remove them (thousand separators)
            $cleaned = str_replace('.', '', $cleaned);
        }
        
        return (float) $cleaned;
    }

    private function normalizeRentalItemQuantities(array $item): array
    {
        $quantity = max(0, (float) ($item['quantity'] ?? 0));
        $qtyFree = max(0, (float) ($item['qty_free'] ?? 0));

        if ($quantity <= 0 && $qtyFree <= 0) {
            $quantity = 1;
        }

        return [$quantity, $qtyFree];
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

        $contract = Contract::findRenewalSource($existingContractId);
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

    private function ensureRenewalSourceMatchesSelection(Request $request): void
    {
        if ($request->get('quotation_type') !== 'renewal' || !$request->filled('existing_contract_id')) {
            return;
        }

        $contract = Contract::findRenewalSource($request->get('existing_contract_id'))?->load('quotation');
        if (!$contract) {
            return;
        }

        if (
            $request->filled('branch_id')
            && $contract->quotation
            && (int) $contract->quotation->branch_id !== (int) $request->get('branch_id')
        ) {
            throw ValidationException::withMessages([
                'existing_contract_id' => 'Contract lama tidak sesuai dengan cabang yang dipilih.',
            ]);
        }
    }

    private function surveyTagsValidationRule(Request $request): string
    {
        if ($this->allowsRenewalWithoutSurvey($request)) {
            return 'nullable|array';
        }

        return 'required|array|min:1';
    }

    private function allowsRenewalWithoutSurvey(Request $request): bool
    {
        if ($request->get('quotation_type') !== 'renewal' || !$request->filled('existing_contract_id')) {
            return false;
        }

        return count($request->get('room_selections_data', [])) > 0
            || count($request->get('rental_items', [])) > 0;
    }

    private function resolveRenewalCustomerContext(?Contract $contract, $marketingId): array
    {
        if (!$contract || !$contract->customer) {
            return [null, null, null];
        }

        $customer = $contract->customer;
        $prospect = \App\Models\Prospect::where('customer_id', $customer->id)->first();

        if (!$prospect) {
            $prospect = \App\Models\Prospect::create([
                'company_name' => $customer->name,
                'contact_person' => $customer->name,
                'contact_email' => $customer->email,
                'contact_phone' => $customer->phone,
                'company_address' => $customer->address,
                'customer_id' => $customer->id,
                'status' => 'qualified',
                'assigned_to' => $marketingId,
            ]);
        }

        return [$customer->id, $prospect->id, $customer->name];
    }

    private function resolveQuotationDetailSurveyAndRoom(array $item, array $surveyIds = []): array
    {
        $surveyId = isset($item['survey_id']) && $item['survey_id'] !== 'custom' && $item['survey_id'] !== 'null'
            ? (int) $item['survey_id']
            : null;
        $roomId = isset($item['room_id']) && $item['room_id'] !== 'custom' && $item['room_id'] !== 'null'
            ? (int) $item['room_id']
            : null;

        if ($surveyId && $roomId) {
            $exists = \App\Models\SurveyDetail::where('id', $roomId)
                ->where('survey_id', $surveyId)
                ->exists();

            if ($exists) {
                return [$surveyId, $roomId];
            }
        }

        $roomName = trim((string) ($item['room_name'] ?? ''));
        $candidateSurveyIds = collect([$surveyId])
            ->merge($surveyIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($roomName !== '' && $candidateSurveyIds->isNotEmpty()) {
            $surveyDetail = \App\Models\SurveyDetail::whereIn('survey_id', $candidateSurveyIds)
                ->whereRaw('LOWER(TRIM(room_name)) = ?', [mb_strtolower($roomName)])
                ->orderByRaw($surveyId ? 'CASE WHEN survey_id = ? THEN 0 ELSE 1 END' : 'survey_id ASC', $surveyId ? [$surveyId] : [])
                ->first();

            if ($surveyDetail) {
                return [(int) $surveyDetail->survey_id, (int) $surveyDetail->id];
            }
        }

        return [$surveyId, $roomId];
    }
    

    
    /**
     * Approve quotation (Manager/Admin only)
     */
    public function approveQuotation(Request $request, $id)
    {
        try {
            $quotation = Quotation::findOrFail($id);
            
            // Check if user has permission to approve (using canApprove - permission-based)
            $user = auth()->user();
            if (!$user->canApprove('quotations')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk approve Quotation. Pastikan role Anda memiliki permission "Approve" untuk Quotations.'
                ], 403);
            }
            
            // Check if quotation is in waiting for approval status
            if ($quotation->status !== 'waiting_for_approval') {
                return response()->json([
                    'success' => false,
                    'message' => 'Quotation is not in waiting for approval status'
                ], 400);
            }
            
            // Validate quotation has details before approval
            $detailsCount = QuotationDetail::where('quotation_id', $quotation->id)->count();
            if ($detailsCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot approve quotation without any quotation details. Please add rental items first.'
                ], 400);
            }

            $this->ensureRenewalSourceCanProceed(
                $quotation->quotation_type,
                $quotation->existing_contract_id
            );
            
            // Update quotation status
            $quotation->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'date_approved' => now(),
                'updated_by' => $user->id
            ]);
            
            
            return response()->json([
                'success' => true,
                'message' => 'Quotation has been approved successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Quotation approval error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error approving quotation: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Reject quotation (Manager/Admin only)
     */
    public function rejectQuotation(Request $request, $id)
    {
        try {
            $quotation = Quotation::findOrFail($id);
            
            // Check if user has permission to reject (using canApprove - permission-based)
            $user = auth()->user();
            if (!$user->canApprove('quotations')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk reject Quotation. Pastikan role Anda memiliki permission "Approve" untuk Quotations.'
                ], 403);
            }
            
            // Check if quotation is in waiting for approval status
            if ($quotation->status !== 'waiting_for_approval') {
                return response()->json([
                    'success' => false,
                    'message' => 'Quotation is not in waiting for approval status'
                ], 400);
            }
            
            $rejectionReason = $request->get('rejection_reason', 'No reason provided');
            
            // Update quotation status
            $quotation->update([
                'status' => 'rejected',
                'approved_by' => $user->id,
                'date_approved' => now(),
                'updated_by' => $user->id,
                'rejection_reason' => $rejectionReason
            ]);
            
            
            return response()->json([
                'success' => true,
                'message' => 'Quotation has been rejected'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Quotation rejection error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting quotation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the quotation wizard form for editing.
     */
    public function edit($id)
    {
        $quotation = Quotation::with([
            'prospect',
            'survey',
            'marketing',
            'quotationDetails.masterRental',
            'quotationRooms',
            'surveys',
        ])->findOrFail($id);

        extract($this->getQuotationWizardViewData());

        if (!$quotation->relationLoaded('quotationDetails')) {
            $quotation->load('quotationDetails.masterRental');
        }

        return view('marketing.quotations.wizard.create', compact(
            'quotation',
            'surveys', 'paymentMethods', 'termOfPaymentOptions',
            'taxSettings', 'marketingUsers', 'departments', 'salutations', 'positions', 'branches',
            'roomTypes', 'floors', 'intensities', 'installationTypes', 'rentalAliases'
        ));
    }



    /**
     * Show the quotation wizard form
     */
    public function create()
    {
        extract($this->getQuotationWizardViewData());

        return view('marketing.quotations.wizard.create', compact(
            'surveys', 'paymentMethods', 'termOfPaymentOptions',
            'taxSettings', 'marketingUsers', 'departments', 'salutations', 'positions', 'branches',
            'roomTypes', 'floors', 'intensities', 'installationTypes', 'rentalAliases'
        ));
    }



    /**
     * Save quotation wizard
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'marketing_id' => 'required|exists:users,id',
            'quotation_type' => 'required|in:New,Renewal',
            'rental_period' => 'required|integer|min:1',
            'rental_unit' => 'required|in:Hari,Bulan',
            'payment_method' => 'required|in:Before Service,After Service',
            'term_of_payment' => 'required|string',
            'survey_ids' => 'required|array|min:1',
            'survey_ids.*' => 'exists:surveys,id',
            'room_selections' => 'required|array',
            'rental_items' => 'required|array',
            'internal_remark' => 'nullable|string',
            'external_remark' => 'nullable|string',
            'tax_setting_id' => 'required|exists:tax_settings,id',
            'pic_quotation' => 'required|string|max:255',
            'price_basis' => 'required|in:room,rental',
            'quotation_id' => 'nullable|exists:quotations,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!$this->userCanAccessMarketingId($request->get('marketing_id'))) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing user is outside your accessible data scope.'
            ], 403);
        }

        $surveyIds = collect($request->get('survey_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $accessibleSurveyCount = $this->accessibleApprovedSurveyQuery()
            ->whereIn('id', $surveyIds->all())
            ->count();

        if ($accessibleSurveyCount !== $surveyIds->count()) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected surveys are outside your accessible data scope.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $firstSurveyId = $request->survey_ids[0] ?? null;
            $prospectId = null;
            $customerId = null;

            // Ensure Prospect/Customer exists based on Survey
            if ($firstSurveyId) {
                $survey = Survey::with(['prospect', 'customer'])->find($firstSurveyId);
                
                if ($survey && $survey->prospect_id) {
                    $prospectId = $survey->prospect_id;
                    $customerId = $survey->customer_id;
                } else if ($survey && $survey->customer_id) {
                    $existingProspect = \App\Models\Prospect::where('customer_id', $survey->customer_id)->first();
                    if ($existingProspect) {
                        $prospectId = $existingProspect->id;
                    } else {
                        // Create new prospect
                        $customer = $survey->customer;
                        if ($customer) {
                            $prospect = \App\Models\Prospect::create([
                                'company_name' => $customer->name,
                                'contact_person' => $customer->name,
                                'contact_email' => $customer->email,
                                'contact_phone' => $customer->phone,
                                'company_address' => $customer->address,
                                'customer_id' => $customer->id,
                                'status' => 'qualified',
                                'assigned_to' => $survey->marketing_id
                            ]);
                            $prospectId = $prospect->id;
                            $survey->update(['prospect_id' => $prospectId]);
                        }
                    }
                    $customerId = $survey->customer_id;
                }

                if (!$prospectId) {
                    throw new \Exception('Unable to process quotation: Survey must be associated with a valid prospect.');
                }
            }

            // CREATE or UPDATE Quotation
            if ($request->filled('quotation_id')) {
                // Update existing quotation
                $quotation = Quotation::findOrFail($request->quotation_id);
                $quotation->update([
                    'prospect_id' => $prospectId ?? $quotation->prospect_id,
                    'customer_id' => $customerId ?? $quotation->customer_id,
                    'survey_id' => $firstSurveyId,
                    'marketing_id' => $request->marketing_id,
                    'quotation_type' => $request->quotation_type,
                    'rental_period' => $request->rental_period,
                    'rental_unit' => $request->rental_unit,
                    'payment_method' => $request->payment_method,
                    'term_of_payment' => $request->term_of_payment,
                    'internal_remark' => $request->internal_remark,
                    'external_remark' => $request->external_remark,
                    'tax_setting_id' => $request->tax_setting_id,
                    'pic_name' => $request->pic_quotation,
                    'price_basis' => $request->price_basis,
                    'updated_by' => Auth::id()
                ]);

                // Delete existing details (will be re-created below)
                $quotation->quotationDetails()->delete();
            } else {
                // Create new quotation
                $documentNumberService = new \App\Services\DocumentNumberService();
                $quotationNumber = $documentNumberService->generate(
                    'quotation', null, null, null, null, $firstSurveyId, null
                );
    
                $quotation = Quotation::create([
                    'quotation_number' => $quotationNumber,
                    'prospect_id' => $prospectId,
                    'customer_id' => $customerId,
                    'survey_id' => $firstSurveyId,
                    'marketing_id' => $request->marketing_id,
                    'quotation_type' => $request->quotation_type,
                    'rental_period' => $request->rental_period,
                    'rental_unit' => $request->rental_unit,
                    'payment_method' => $request->payment_method,
                    'term_of_payment' => $request->term_of_payment,
                    'internal_remark' => $request->internal_remark,
                    'external_remark' => $request->external_remark,
                    'tax_setting_id' => $request->tax_setting_id,
                    'pic_name' => $request->pic_quotation,
                    'price_basis' => $request->price_basis,
                    'status' => 'draft',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            // Create quotation details from rental items
            // Assuming rental_items is an array of objects directly from frontend (Object.keys iteration)
            if ($request->has('rental_items')) {
                foreach ($request->rental_items as $item) {
                    $contractRoom = $this->resolveContractRoomFromItem($item, $request->get('existing_contract_id'));
                    $fallbackRoomName = !empty($item['room_id']) ? 'Room ' . $item['room_id'] : 'General';
                    $roomName = $this->sanitizeRenewalRoomName($item['room_name'] ?? $fallbackRoomName);
                    $specifications = $this->normalizeSpecifications($item['specifications'] ?? '');

                    if ($contractRoom?->room) {
                        $roomName = $contractRoom->room->room_name;
                        $specifications = $specifications ?: $this->masterRoomSpecifications($contractRoom->room);
                    }

                    [$quantity, $qtyFree] = $this->normalizeRentalItemQuantities($item);
                    $unitPrice = $quantity > 0 ? ($item['price'] ?? 0) : 0;

                     QuotationDetail::create([
                        'quotation_id' => $quotation->id,
                        'survey_id' => $item['survey_id'],
                        'room_id' => isset($item['room_id']) && $item['room_id'] !== 'null' ? $item['room_id'] : null,
                        'master_rental_id' => $item['product_id'],
                        'rental_alias' => $item['rental_alias'] ?? null,
                        'remark' => $item['remark'] ?? null,
                        'room_name' => $roomName,
                        'quantity' => $quantity,
                        'qty_free' => $qtyFree,
                        'unit_price' => $unitPrice,
                        'total_price' => $unitPrice * $quantity,
                        'specifications' => $specifications,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);
                }
            }

            // Save room selections with aroma data
            if ($request->has('room_selections_data')) {
                
                foreach ($request->room_selections_data as $roomData) {
                    [$masterRoomId, $roomName] = $this->resolveQuotationRoomSelection($roomData);
                    
                    // Only create QuotationRoom if we have a valid MasterRoom ID
                    if ($masterRoomId) {
                        $canonicalAromaProductId = $this->resolveCanonicalAromaProductId($roomData['aroma_product_id'] ?? null);
                        QuotationRoom::create([
                            'quotation_id' => $quotation->id,
                            'room_id' => $masterRoomId, // MasterRoom ID, not survey_detail_id
                            'room_name' => $roomName, // Use room_name from survey detail, not from master room
                            'aroma_product_id' => $canonicalAromaProductId,
                            'aroma_variant' => $roomData['aroma_variant'] ?? null,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id()
                        ]);
                        
                    } else {
                        \Log::error("Cannot create QuotationRoom: MasterRoom not found for room_data: " . json_encode($roomData));
                    }
                }
            } else {
                \Log::warning('No room_selections_data found in request');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Quotation created successfully',
                'quotation_id' => $quotation->id
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create quotation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get surveys by marketing
     */
    public function getSurveysByCustomer(Request $request)
    {
        $marketingId = $request->get('marketing_id');
        $customerId = $request->get('customer_id');
        $user = auth()->user();

        if (!$customerId && $marketingId && !$this->userCanAccessMarketingId($marketingId, $user)) {
            return response()->json([]);
        }

        $query = $this->accessibleApprovedSurveyQuery($user)
            ->with(['customer', 'building', 'surveyDetails']);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        } elseif ($marketingId) {
            $query->where('marketing_id', $marketingId);
        }

        $surveys = $query->latest('created_at')
            ->get()
            ->unique(fn (Survey $survey) => $this->surveySelectionDuplicateKey($survey))
            ->values();

        return response()->json($surveys);
    }

    /**
     * Get rooms by survey
     */
    public function getRoomsBySurvey(Request $request)
    {
        $surveyId = $request->get('survey_id');
        
        if (!$surveyId) {
            return response()->json([]);
        }

        $survey = $this->accessibleApprovedSurveyQuery()
            ->with(['surveyDetails'])
            ->find($surveyId);
        
        if (!$survey) {
            return response()->json([]);
        }

        return response()->json($survey->surveyDetails);
    }

    /**
     * Get survey rooms for multiple surveys
     */
    public function getSurveyRooms(Request $request)
    {
        $surveyIds = $request->get('survey_ids', []);
        
        if (empty($surveyIds)) {
            return response()->json([]);
        }

        $surveys = $this->accessibleApprovedSurveyQuery()
            ->with(['customer', 'building', 'surveyDetails'])
            ->whereIn('id', $surveyIds)
            ->get();

        $result = [];
        
        foreach ($surveys as $survey) {
            
            $rooms = [];
            
            foreach ($survey->surveyDetails as $detail) {
                $rooms[] = [
                    'id' => $detail->id,
                    'room_id' => $detail->room_id,
                    'room_name' => $detail->room_name,
                    'room_type' => $detail->room_type ?? '-',
                    'room_area' => $detail->room_area ?? 0,
                    'quantity_needed' => $detail->quantity_needed ?? 1,
                    'specifications' => $detail->specifications ?? '-',
                    'survey_id' => $survey->id
                ];
            }
            
            $result[] = [
                'id' => $survey->id,
                'survey_number' => $survey->survey_number,
                'customer_name' => $survey->customer->name ?? 'Unknown Customer',
                'customer_address' => $survey->customer->address ?? '-',
                'building_name' => $survey->building->name ?? $survey->building->nama_gedung ?? '-',
                'building_address' => $this->getBuildingFullAddress($survey->building),
                'rooms' => $rooms
            ];
        }
        

        return response()->json($result);
    }

    /**
     * Get aroma products for room selection
     */
    public function getAromaProducts(Request $request)
    {
        try {
            // Get real aroma/refill products consolidated by variant_name.
            // Some valid refill products do not have product_type_id, so category
            // must be part of the source of truth. Exclude unit/SN products to avoid
            // options like test devices or hand-sanitizer placeholders.
            $products = MasterProduct::with(['productCategory', 'productType', 'packagingSize'])
                ->where('is_active', true)
                ->whereNotNull('brand_line')
                ->where('brand_line', '!=', '')
                ->whereNotNull('variant_name')
                ->where('variant_name', '!=', '')
                ->where(function ($query) {
                    $query->whereHas('productCategory', function ($categoryQuery) {
                        $categoryQuery->where('is_unit', false)
                            ->where(function ($nameQuery) {
                                $nameQuery->where('name', 'LIKE', '%Refill%')
                                    ->orWhere('name', 'LIKE', '%Aroma%')
                                    ->orWhere('name', 'LIKE', '%Fragrance%')
                                    ->orWhere('name', 'LIKE', '%Scent%')
                                    ->orWhere('name', 'LIKE', '%Luxo%')
                                    ->orWhere('name', 'LIKE', '%Artisan%')
                                    ->orWhere('name', 'LIKE', '%Signature%');
                            });
                    })->orWhereHas('productType', function ($typeQuery) {
                        $typeQuery->where('is_unit', false)
                            ->where(function ($nameQuery) {
                                $nameQuery->where('name', 'LIKE', '%Aroma%')
                                    ->orWhere('name', 'LIKE', '%Fragrance%')
                                    ->orWhere('name', 'LIKE', '%Scent%')
                                    ->orWhere('name', 'LIKE', '%Variant%')
                                    ->orWhere('name', 'LIKE', '%Refill%');
                            });
                    });
                })
                ->get();

            $aromaProducts = $products
                ->filter(fn ($product) => $this->isSelectableAromaProduct($product))
                ->groupBy(fn ($product) => strtolower(trim((string) $product->brand_line)) . '|' . trim((string) $product->variant_name))
                ->map(function ($group) {
                    $product = $group->sortBy(function ($candidate) {
                        $categoryName = strtolower($candidate->productCategory?->name ?? '');
                        $productName = strtolower($candidate->name ?? '');
                        $packageName = strtolower($candidate->packagingSize?->name ?? '');

                        return [
                            str_contains($categoryName, 'refill') ? 0 : 1,
                            str_contains($productName, 'test') ? 1 : 0,
                            $packageName === '100ml' ? 0 : 1,
                            $candidate->id,
                        ];
                    })->first();

                    return [
                        'id' => $product->id,
                        'name' => $product->variant_name,
                        'variant' => $product->variant_name,
                        'display_name' => $product->variant_name,
                        'brand_line' => $product->brand_line,
                        'packaging_size' => '',
                        'product_type' => 'Aroma/Variant'
                    ];
                })
                ->sortBy('display_name')
                ->values();

            return response()->json($aromaProducts);

        } catch (\Exception $e) {
            \Log::error('Error loading aroma products:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load aroma products'], 500);
        }
    }

    /**
     * Get default aroma from master rental
     */
    public function getRentalAroma(Request $request, $rentalId)
    {
        try {
            $rental = MasterRental::with([
                'rentalComponents.allowedProducts' => function($query) {
                    $query->wherePivot('is_active', true)
                          ->wherePivot('is_preferred', true)
                          ->with('productType');
                }
            ])->findOrFail($rentalId);

            // Find aroma component
            $aromaComponent = $rental->rentalComponents()
                ->where('is_active', true)
                ->where(function($q) {
                    $q->where('component_name', 'LIKE', '%aroma%')
                      ->orWhere('component_name', 'LIKE', '%refill%')
                      ->orWhere('component_name', 'LIKE', '%fragrance%');
                })
                ->first();

            if ($aromaComponent) {
                // Get preferred product first, or first allowed product
                $aromaProduct = $aromaComponent->preferredProducts()->first();
                
                if (!$aromaProduct) {
                    $aromaProduct = $aromaComponent->allowedProducts()
                        ->wherePivot('is_active', true)
                        ->first();
                }

                if ($aromaProduct) {
                    return response()->json([
                        'id' => $aromaProduct->id,
                        'name' => $aromaProduct->name,
                        'variant' => $aromaProduct->variant_name ?? '',
                        'display_name' => $aromaProduct->variant_name 
                            ? "{$aromaProduct->name} - {$aromaProduct->variant_name}" 
                            : $aromaProduct->name
                    ]);
                }
            }

            return response()->json(null); // No default aroma found
            
        } catch (\Exception $e) {
            \Log::error('Error loading rental aroma:', ['error' => $e->getMessage()]);
            return response()->json(null);
        }
    }

    /**
     * Get branches for a specific user (marketing)
     * Returns user's assigned branches from branch_user pivot table
     */
    public function getUserBranches(Request $request)
    {
        try {
            $userId = $request->get('user_id');
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required',
                    'branches' => []
                ], 400);
            }
            
            $user = User::with('assignedBranches')->find($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'branches' => []
                ], 404);
            }
            
            // Get user's assigned branches
            $branches = $user->assignedBranches()->where('is_active', true)->get();
            
            // If user has no assigned branches, fallback to user's primary branch
            if ($branches->isEmpty() && $user->branch_id) {
                $branches = Branch::where('id', $user->branch_id)->where('is_active', true)->get();
            }
            
            // Get primary branch ID
            $primaryBranchId = null;
            $primaryBranch = $user->assignedBranches()->wherePivot('is_primary', true)->first();
            if ($primaryBranch) {
                $primaryBranchId = $primaryBranch->id;
            } elseif ($user->branch_id) {
                $primaryBranchId = $user->branch_id;
            }
            
            return response()->json([
                'success' => true,
                'branches' => $branches,
                'primary_branch_id' => $primaryBranchId,
                'is_single' => $branches->count() <= 1
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error getting user branches:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error getting user branches: ' . $e->getMessage(),
                'branches' => []
            ], 500);
        }
    }

    /**
     * Get full building address combining alamat_1 and alamat_2
     */
    private function getBuildingFullAddress($building)
    {
        if (!$building) {
            return '-';
        }
        
        $address1 = $building->alamat_1 ?? $building->address ?? '';
        $address2 = $building->alamat_2 ?? '';
        
        $fullAddress = $address1;
        if (!empty($address2)) {
            $fullAddress .= '. ' . $address2;
        }
        
        return $fullAddress ?: '-';
    }
    private function getQuotationWizardViewData(): array
    {
        $user = Auth::user();
        $surveys = Cache::remember("quotation-wizard:surveys:user:{$user->id}", now()->addMinutes(5), function () use ($user) {
            return $this->accessibleApprovedSurveyQuery($user)
                ->with(['customer:id,name', 'building:id,name,nama_gedung'])
                ->get();
        });

        $paymentMethods = ['Before Service', 'After Service'];

        $termOfPaymentOptions = $this->getTermOfPaymentOptions();

        $taxSettings = Cache::remember('quotation-wizard:tax-settings', now()->addMinutes(10), function () {
            return TaxSetting::all();
        });

        $marketingUsers = Cache::remember("quotation-wizard:marketing-users:user:{$user->id}", now()->addMinutes(10), function () use ($user) {
            return $this->applyAccessibleUserFilter(User::where('is_active', true), $user)
                ->orderBy('name')
                ->get(['id', 'name', 'salutation']);
        });

        $departments = Cache::remember('quotation-wizard:departments', now()->addMinutes(10), function () {
            return User::select('position_name')
                ->whereNotNull('position_name')
                ->where('position_name', '!=', '')
                ->distinct()
                ->pluck('position_name')
                ->filter()
                ->values();
        });

        $salutations = Cache::remember('quotation-wizard:salutations', now()->addMinutes(10), function () {
            return User::select('salutation')
                ->whereNotNull('salutation')
                ->where('salutation', '!=', '')
                ->distinct()
                ->pluck('salutation')
                ->filter()
                ->values();
        });

        $positions = Cache::remember('quotation-wizard:positions', now()->addMinutes(10), function () {
            return User::select('position_name')
                ->whereNotNull('position_name')
                ->where('position_name', '!=', '')
                ->distinct()
                ->pluck('position_name')
                ->filter()
                ->values();
        });

        $branches = Cache::remember('quotation-wizard:branches', now()->addMinutes(10), function () {
            return Branch::where('is_active', true)->orderBy('name')->get();
        });

        $roomTypes = Cache::remember('quotation-wizard:room-types', now()->addMinutes(10), function () {
            return MasterOption::where('name', 'Room Type')->first()?->optionDetails ?? collect();
        });

        $floors = Cache::remember('quotation-wizard:floors', now()->addMinutes(10), function () {
            return MasterOption::where('name', 'Floor')->first()?->optionDetails ?? collect();
        });

        $intensities = Cache::remember('quotation-wizard:intensities', now()->addMinutes(10), function () {
            return MasterOption::where('name', 'Scent Intensity')->first()?->optionDetails ?? collect();
        });

        $installationTypes = Cache::remember('quotation-wizard:installation-types', now()->addMinutes(10), function () {
            return MasterOption::where('name', 'Installation Type')->first()?->optionDetails ?? collect();
        });

        $rentalAliases = Cache::remember('quotation-wizard:rental-aliases', now()->addMinutes(10), function () {
            return MasterOption::where('name', 'rental_alias')->first()?->optionDetails()->where('is_active', true)->get() ?? collect();
        });

        return compact(
            'surveys',
            'paymentMethods',
            'termOfPaymentOptions',
            'taxSettings',
            'marketingUsers',
            'departments',
            'salutations',
            'positions',
            'branches',
            'roomTypes',
            'floors',
            'intensities',
            'installationTypes',
            'rentalAliases'
        );
    }

    private function getTermOfPaymentOptions()
    {
        return Cache::remember('quotation-wizard:term-of-payment-options', now()->addMinutes(10), function () {
            $masterOption = MasterOption::where('name', 'Term of Payment')
                ->where('is_active', true)
                ->first();

            $options = $masterOption
                ? $masterOption->optionDetails()
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->get()
                    ->map(function ($detail) {
                        $isAdvance = $detail->code === 'advance';
                        $metadata = $this->decodeTermOfPaymentMetadata($detail->option_description);
                        $billingMode = $isAdvance
                            ? 'advance'
                            : ($metadata['billing_mode'] ?? ($detail->code === 'installments' ? 'per_contract_period' : 'fixed_interval'));
                        $months = $billingMode === 'fixed_interval' ? (int) ($metadata['months'] ?? $detail->code) : null;

                        return [
                            'value' => $detail->option_name,
                            'label' => $detail->label ?: Quotation::formatTermsOfPaymentLabel($detail->option_name),
                            'months' => $isAdvance ? null : $months,
                            'is_advance' => $isAdvance,
                            'billing_mode' => $billingMode,
                            'payment_count' => $billingMode === 'per_contract_period' ? (int) ($metadata['payment_count'] ?? 0) : null,
                        ];
                    })
                    ->values()
                : collect();

            return $options->isNotEmpty() ? $options : collect($this->defaultTermOfPaymentOptions());
        });
    }

    private function defaultTermOfPaymentOptions(): array
    {
        return [
            $this->fixedIntervalTopOption('1 bulan 1x', 1),
            $this->fixedIntervalTopOption('2 bulan 1x', 2),
            $this->fixedIntervalTopOption('3 bulan 1x', 3),
            $this->fixedIntervalTopOption('4 bulan 1x', 4),
            $this->fixedIntervalTopOption('5 bulan 1x', 5),
            $this->fixedIntervalTopOption('6 bulan 1x', 6),
            ['value' => 'Tahunan', 'label' => '1x Advance', 'months' => null, 'is_advance' => true, 'billing_mode' => 'advance', 'payment_count' => null],
            $this->perContractPeriodTopOption(2),
            $this->perContractPeriodTopOption(3),
            $this->perContractPeriodTopOption(4),
            $this->fixedIntervalTopOption('7 bulan 1x', 7),
            $this->fixedIntervalTopOption('8 bulan 1x', 8),
            $this->fixedIntervalTopOption('9 bulan 1x', 9),
            $this->fixedIntervalTopOption('10 bulan 1x', 10),
            $this->fixedIntervalTopOption('11 bulan 1x', 11),
            $this->fixedIntervalTopOption('13 bulan 1x', 13),
            $this->fixedIntervalTopOption('14 bulan 1x', 14),
            $this->fixedIntervalTopOption('15 bulan 1x', 15),
            $this->fixedIntervalTopOption('16 bulan 1x', 16),
            $this->fixedIntervalTopOption('17 bulan 1x', 17),
            $this->fixedIntervalTopOption('18 bulan 1x', 18),
            $this->fixedIntervalTopOption('19 bulan 1x', 19),
            $this->fixedIntervalTopOption('20 bulan 1x', 20),
            $this->fixedIntervalTopOption('21 bulan 1x', 21),
            $this->fixedIntervalTopOption('22 bulan 1x', 22),
            $this->fixedIntervalTopOption('23 bulan 1x', 23),
            $this->fixedIntervalTopOption('2 tahunan', 24),
            $this->fixedIntervalTopOption('3 tahunan', 36),
        ];
    }

    private function fixedIntervalTopOption(string $value, int $months): array
    {
        return [
            'value' => $value,
            'label' => $value,
            'months' => $months,
            'is_advance' => false,
            'billing_mode' => 'fixed_interval',
            'payment_count' => null,
        ];
    }

    private function perContractPeriodTopOption(int $paymentCount): array
    {
        return [
            'value' => "{$paymentCount}x per periode contract",
            'label' => "{$paymentCount}x dalam Periode Contract",
            'months' => null,
            'is_advance' => false,
            'billing_mode' => 'per_contract_period',
            'payment_count' => $paymentCount,
        ];
    }

    private function decodeTermOfPaymentMetadata(?string $description): array
    {
        if (! $description) {
            return [];
        }

        $decoded = json_decode($description, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolveTopMonthsFromRequest(Request $request): ?int
    {
        $termOfPayment = trim((string) $request->get('term_of_payment', ''));
        $rentalMonths = $this->resolveRentalPeriodMonths($request->get('rental_period'), $request->get('rental_unit'));

        if ($termOfPayment === '' || $rentalMonths <= 0) {
            return null;
        }

        $selectedOption = $this->getTermOfPaymentOptions()
            ->first(fn ($option) => ($option['value'] ?? null) === $termOfPayment);

        if ($selectedOption && ! empty($selectedOption['is_advance'])) {
            return null;
        }

        if (($selectedOption['billing_mode'] ?? null) === 'per_contract_period') {
            $paymentCount = (int) ($selectedOption['payment_count'] ?? 0);

            if ($paymentCount <= 0 || $rentalMonths % $paymentCount !== 0) {
                throw ValidationException::withMessages([
                    'term_of_payment' => "Term of Payment {$termOfPayment} tidak cocok untuk periode {$rentalMonths} bulan. Periode kontrak harus habis dibagi {$paymentCount}x.",
                ]);
            }

            return (int) ($rentalMonths / $paymentCount);
        }

        $topMonths = (int) ($selectedOption['months'] ?? 0);

        if ($topMonths <= 0) {
            $topMonths = $this->parseFixedTopMonths($termOfPayment);
        }

        if ($topMonths > 0 && $rentalMonths % $topMonths !== 0) {
            throw ValidationException::withMessages([
                'term_of_payment' => "Term of Payment {$termOfPayment} tidak cocok untuk periode {$rentalMonths} bulan.",
            ]);
        }

        return $topMonths > 0 ? $topMonths : null;
    }

    private function resolveRentalPeriodMonths($rentalPeriod, ?string $rentalUnit): int
    {
        $period = (int) $rentalPeriod;

        if ($period <= 0) {
            return 0;
        }

        if ($rentalUnit === 'hari') {
            return $period < 30 ? 1 : (int) ceil($period / 30);
        }

        return $period;
    }

    private function parseFixedTopMonths(string $termOfPayment): int
    {
        if (preg_match('/(\d+)\s*(bulan|month)/i', $termOfPayment, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/(\d+)\s*(tahun|year|tahunan)/i', $termOfPayment, $matches)) {
            return (int) $matches[1] * 12;
        }

        return 0;
    }

}

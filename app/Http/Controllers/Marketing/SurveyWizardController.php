<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyDetail;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Building;
use App\Models\Room;
use App\Models\User;
use App\Models\MasterOption;
use App\Models\OptionDetail;
use App\Models\CustomerType;
use App\Models\Prospect;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Subdistrict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Cache\LockTimeoutException;

class SurveyWizardController extends Controller
{
    private function forgetSurveyWizardCustomerCaches(): void
    {
        Cache::forget('survey-wizard:customers');
    }

    private function forgetSurveyWizardContactCaches(?int ...$customerIds): void
    {
        foreach (array_unique(array_filter($customerIds)) as $customerId) {
            Cache::forget("survey-wizard:contacts:{$customerId}");
            Cache::forget($this->surveyWizardContactsCacheKey((int) $customerId));
        }
    }

    private function surveyWizardContactsCacheKey(int $customerId): string
    {
        return "survey-wizard:contacts:v2:{$customerId}";
    }

    private function surveyWizardBuildingsCacheKey(): string
    {
        return 'survey-wizard:buildings:all-active:v2';
    }

    private function forgetSurveyWizardBuildingCaches(): void
    {
        Cache::forget('survey-wizard:buildings:all-active');
        Cache::forget($this->surveyWizardBuildingsCacheKey());
    }

    private function surveyWizardSubmissionCacheKey(Request $request): string
    {
        $payload = $request->except(['_token']);
        $this->recursiveKeySort($payload);

        return 'survey-wizard:submission:' . Auth::id() . ':' . sha1(json_encode($payload));
    }

    private function recursiveKeySort(array &$value): void
    {
        ksort($value);

        foreach ($value as &$item) {
            if (is_array($item)) {
                $this->recursiveKeySort($item);
            }
        }
    }

    private function duplicateSurveyResponse(int $surveyId): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Survey sudah tersimpan dari request yang sama.',
            'survey_id' => $surveyId,
            'duplicate_prevented' => true,
            'redirect_url' => route('marketing.surveys.show', $surveyId),
        ]);
    }

    private function normalizeLookupValue(?string $value): string
    {
        $value = strtolower(trim((string) $value));

        return preg_replace('/\s+/', ' ', $value) ?: '';
    }

    private function buildingDisplayName(Building $building): string
    {
        return trim((string) ($building->name ?: $building->nama_gedung));
    }

    private function buildingAddressLine(Building $building): string
    {
        return trim((string) ($building->alamat_1 ?: $building->address));
    }

    private function findReusableBuilding(string $name, ?string $address, ?int $cityId): ?Building
    {
        $normalizedName = $this->normalizeLookupValue($name);
        $normalizedAddress = $this->normalizeLookupValue($address);

        if ($normalizedName === '') {
            return null;
        }

        return Building::with(['province:id,name', 'city:id,name,type', 'district:id,name', 'subdistrict:id,name,postal_code'])
            ->where('status_update', true)
            ->whereNull('deleted_at')
            ->when($cityId, fn ($query) => $query->where('city_id', $cityId))
            ->get()
            ->first(function (Building $building) use ($normalizedName, $normalizedAddress) {
                $sameName = $this->normalizeLookupValue($this->buildingDisplayName($building)) === $normalizedName;

                if (!$sameName) {
                    return false;
                }

                if ($normalizedAddress === '') {
                    return true;
                }

                return $this->normalizeLookupValue($this->buildingAddressLine($building)) === $normalizedAddress;
            });
    }

    private function attachCustomerToBuilding(Building $building, ?int $customerId): void
    {
        if (!$customerId) {
            return;
        }

        $building->customers()->syncWithoutDetaching([
            $customerId => [
                'is_active' => true,
                'updated_at' => now(),
            ],
        ]);
    }

    private function formatBuildingForSurveyWizard(Building $building): array
    {
        return [
            'id' => $building->id,
            'name' => $building->name,
            'nama_gedung' => $building->nama_gedung,
            'address' => $building->address,
            'alamat_1' => $building->alamat_1,
            'alamat_2' => $building->alamat_2,
            'postal_code' => $building->postal_code,
            'kode_pos' => $building->kode_pos,
            'phone_1' => $building->phone_1,
            'phone_2' => $building->phone_2,
            'fax' => $building->fax,
            'province_id' => $building->province_id,
            'city_id' => $building->city_id,
            'district_id' => $building->district_id,
            'subdistrict_id' => $building->subdistrict_id,
            'province' => $building->province ? ['id' => $building->province->id, 'name' => $building->province->name] : null,
            'city' => $building->city ? ['id' => $building->city->id, 'name' => $building->city->name] : null,
            'district' => $building->district ? ['id' => $building->district->id, 'name' => $building->district->name] : null,
            'subdistrict' => $building->subdistrict ? ['id' => $building->subdistrict->id, 'name' => $building->subdistrict->name] : null,
            'created_at' => optional($building->created_at)->toDateTimeString(),
        ];
    }

    /**
     * Show Survey Wizard
     */
    public function create()
    {
        extract($this->getSurveyWizardFormData(includeStaffAndCustomers: false));

        // Marketing staff and customer selects are populated via AJAX
        // (get-marketing-staff / get-customers) once the page loads, so we
        // only need the current user pre-selected here instead of eager
        // loading all users/customers into the page on every request.
        $marketingStaff = collect([Auth::user()])->filter();

        return view('marketing.surveys.wizard.create', compact(
            'marketingStaff', 'customers', 'companyTypes',
            'roomTypes', 'floors', 'intensities', 'installationTypes',
            'salutations', 'positions', 'provinces', 'addressTypes', 'companyOptions'
        ));
    }



    /**
     * Process Survey Wizard Step 1
     */
    public function processStep1(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'marketing_id' => 'required|exists:users,id',
            'survey_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Store step 1 data in session
        session(['survey_wizard' => [
            'step' => 1,
            'data' => [
                'marketing_id' => $request->marketing_id,
                'survey_date' => $request->survey_date
            ]
        ]]);

        return response()->json([
            'status' => 'success',
            'message' => 'Step 1 completed',
            'next_step' => 2
        ]);
    }

    /**
     * Show Survey Wizard Step 2 - Customer Data
     */
    public function step2()
    {
        $wizardData = $this->getSurveyWizardFormData();
        $customers = $wizardData['customers'];
        $companyTypes = $wizardData['companyTypes'];

        return view('marketing.surveys.wizard.step2', compact('customers', 'companyTypes'));
    }

    /**
     * Process Survey Wizard Step 2
     */
    public function processStep2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'add_new_customer' => 'boolean',
            'new_company_type' => 'required_if:add_new_customer,true',
            'new_customer_name' => 'required_if:add_new_customer,true|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $wizardData = session('survey_wizard', []);
        $wizardData['step'] = 2;
        $wizardData['data']['customer_id'] = $request->customer_id;
        $wizardData['data']['add_new_customer'] = $request->add_new_customer;
        
        if ($request->add_new_customer) {
            $wizardData['data']['new_company_type'] = $request->new_company_type;
            $wizardData['data']['new_customer_name'] = $request->new_customer_name;
        }

        session(['survey_wizard' => $wizardData]);

        return response()->json([
            'status' => 'success',
            'message' => 'Step 2 completed',
            'next_step' => 3
        ]);
    }

    /**
     * Show Survey Wizard Step 3 - Contact Data
     */
    public function step3()
    {
        $wizardData = session('survey_wizard', []);
        $customerId = $wizardData['data']['customer_id'] ?? null;
        
        $contacts = collect();
        if ($customerId) {
            $contacts = CustomerContact::where('customer_id', $customerId)
                ->where('is_active', true)
                ->get();
        }

        return view('marketing.surveys.wizard.step3', compact('contacts'));
    }

    /**
     * Process Survey Wizard Step 3
     */
    public function processStep3(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact_id' => 'nullable|exists:customer_contacts,id',
            'add_new_contact' => 'boolean',
            'new_contact_name' => 'required_if:add_new_contact,true|string|max:255',
            'new_contact_email' => 'required_if:add_new_contact,true|email',
            'new_contact_phone' => 'required_if:add_new_contact,true|string|max:20',
            'new_contact_position' => 'required_if:add_new_contact,true|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $wizardData = session('survey_wizard', []);
        $wizardData['step'] = 3;
        $wizardData['data']['contact_id'] = $request->contact_id;
        $wizardData['data']['add_new_contact'] = $request->add_new_contact;
        
        if ($request->add_new_contact) {
            $wizardData['data']['new_contact_name'] = $request->new_contact_name;
            $wizardData['data']['new_contact_email'] = $request->new_contact_email;
            $wizardData['data']['new_contact_phone'] = $request->new_contact_phone;
            $wizardData['data']['new_contact_position'] = $request->new_contact_position;
        }

        session(['survey_wizard' => $wizardData]);

        return response()->json([
            'status' => 'success',
            'message' => 'Step 3 completed',
            'next_step' => 4
        ]);
    }

    /**
     * Show Survey Wizard Step 4 - Location Data
     */
    public function step4()
    {
        $wizardData = session('survey_wizard', []);
        $customerId = $wizardData['data']['customer_id'] ?? null;
        
        $buildings = collect();
        if ($customerId) {
            $buildings = Building::whereHas('customers', function($query) use ($customerId) {
                $query->where('customers.id', $customerId);
            })->get();
        }

        return view('marketing.surveys.wizard.step4', compact('buildings'));
    }

    /**
     * Process Survey Wizard Step 4
     */
    public function processStep4(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'building_id' => 'nullable|exists:buildings,id',
            'add_new_building' => 'boolean',
            'new_building_name' => 'required_if:add_new_building,true|string|max:255',
            'new_building_address' => 'required_if:add_new_building,true|string|max:500',
            'new_building_province' => 'required_if:add_new_building,true|string|max:100',
            'new_building_city' => 'required_if:add_new_building,true|string|max:100',
            'new_building_district' => 'required_if:add_new_building,true|string|max:100',
            'new_building_village' => 'required_if:add_new_building,true|string|max:100',
            'new_building_postal_code' => 'required_if:add_new_building,true|string|max:10',
            'new_building_type' => 'required_if:add_new_building,true|string|max:100' // Required field
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $wizardData = session('survey_wizard', []);
        $wizardData['step'] = 4;
        $wizardData['data']['building_id'] = $request->building_id;
        $wizardData['data']['add_new_building'] = $request->add_new_building;
        
        if ($request->add_new_building) {
            $wizardData['data']['new_building_name'] = $request->new_building_name;
            $wizardData['data']['new_building_address'] = $request->new_building_address;
            $wizardData['data']['new_building_province'] = $request->new_building_province;
            $wizardData['data']['new_building_city'] = $request->new_building_city;
            $wizardData['data']['new_building_district'] = $request->new_building_district;
            $wizardData['data']['new_building_village'] = $request->new_building_village;
            $wizardData['data']['new_building_postal_code'] = $request->new_building_postal_code;
            $wizardData['data']['new_building_type'] = $request->new_building_type;
        }

        session(['survey_wizard' => $wizardData]);

        return response()->json([
            'status' => 'success',
            'message' => 'Step 4 completed',
            'next_step' => 5
        ]);
    }

    /**
     * Show Survey Wizard Step 5 - Room Survey
     */
    public function step5()
    {
        $wizardData = session('survey_wizard', []);
        $buildingId = $wizardData['data']['building_id'] ?? null;
        
        $rooms = collect();
        if ($buildingId) {
            $rooms = Room::where('building_id', $buildingId)->get();
        }

        $roomTypes = MasterOption::where('name', 'Room Type')->first()?->optionDetails ?? collect();
        $floors = MasterOption::where('name', 'Floor')->first()?->optionDetails ?? collect();
        $intensities = MasterOption::where('name', 'Scent Intensity')->first()?->optionDetails ?? collect();
        $installationTypes = MasterOption::where('name', 'Installation Type')->first()?->optionDetails ?? collect();

        return view('marketing.surveys.wizard.step5', compact('rooms', 'roomTypes', 'floors', 'intensities', 'installationTypes'));
    }

    /**
     * Process Survey Wizard Step 5
     */
    public function processStep5(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rooms' => 'required|array|min:1',
            'rooms.*.room_name' => 'required|string|max:255',
            'rooms.*.room_type' => 'required|string|max:255',
            'rooms.*.floor' => 'required|string|max:255',
            'rooms.*.intensity' => 'required|string|max:255',
            'rooms.*.installation_type' => 'required|string|max:255',
            'rooms.*.qty' => 'required|integer|min:1',
            'rooms.*.length' => 'required|numeric|min:0',
            'rooms.*.width' => 'required|numeric|min:0',
            'rooms.*.height' => 'required|numeric|min:0',
            'rooms.*.remark' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $wizardData = session('survey_wizard', []);
        $wizardData['step'] = 5;
        $wizardData['data']['rooms'] = $request->rooms;

        session(['survey_wizard' => $wizardData]);

        return response()->json([
            'status' => 'success',
            'message' => 'Step 5 completed',
            'next_step' => 6
        ]);
    }

    /**
     * Show Survey Wizard Step 6 - Summary
     */
    public function step6()
    {
        $wizardData = session('survey_wizard', []);
        
        if (!$wizardData || !isset($wizardData['data'])) {
            return redirect()->route('surveys.wizard.step1');
        }

        $data = $wizardData['data'];
        
        // Get related data for summary
        $marketing = User::find($data['marketing_id']);
        $customer = Customer::find($data['customer_id']);
        $building = Building::find($data['building_id']);
        $contact = CustomerContact::find($data['contact_id']);

        return view('marketing.surveys.wizard.step6', compact('data', 'marketing', 'customer', 'building', 'contact'));
    }

    /**
     * Process Survey Wizard Step 6 - Save Survey
     */
    public function processStep6(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:save_draft,finalize_email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $wizardData = session('survey_wizard', []);
        $data = $wizardData['data'];

        try {
            DB::beginTransaction();

            // Create new customer if needed
            if ($data['add_new_customer']) {
                // Get customer type from database based on MasterOptionDetail ID
                // The dropdown is populated from MasterOption ID 14 (Jenis Customer)
                $customerTypeEnum = 'company'; // Default to 'company'
                $companyTypeName = 'PT'; // Default company type name
                
                if (isset($data['new_company_type']) && $data['new_company_type']) {
                    // Look up in MasterOptionDetail (NOT CustomerType!)
                    $optionDetail = MasterOptionDetail::find($data['new_company_type']);
                    if ($optionDetail) {
                        // Store the actual company type name (e.g., CV, PT, UD)
                        $companyTypeName = $optionDetail->option_name;
                        
                        // Map company type name to customer_type enum
                        $typeName = strtolower($optionDetail->option_name);
                        if (in_array($typeName, ['individual', 'personal', 'perorangan'])) {
                            $customerTypeEnum = 'individual';
                        } else {
                            $customerTypeEnum = 'company';
                        }
                    }
                }
                
                $customer = Customer::create([
                    'name' => $data['new_customer_name'],
                    'customer_type' => $customerTypeEnum,
                    'company_type' => $companyTypeName, // Use actual selected company type
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
                $data['customer_id'] = $customer->id;
            }

            // Create new contact if needed
            if ($data['add_new_contact']) {
                $contact = CustomerContact::create([
                    'customer_id' => $data['customer_id'],
                    'name' => $data['new_contact_name'],
                    'email' => $data['new_contact_email'],
                    'phone' => $data['new_contact_phone'],
                    'position' => $data['new_contact_position'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
                $data['contact_id'] = $contact->id;
            }

            // Create new building if needed
            if ($data['add_new_building']) {
                $building = Building::create([
                    'nama_gedung' => $data['new_building_name'],
                    'name' => $data['new_building_name'],
                    'building_type' => $data['new_building_type'] ?? null,
                    'alamat_1' => $data['new_building_address'] ?? null,
                    'address' => $data['new_building_address'] ?? null,
                    'province_id' => $data['new_building_province'] ?? null,
                    'city_id' => $data['new_building_city'] ?? null,
                    'district_id' => $data['new_building_district'] ?? null,
                    'subdistrict_id' => $data['new_building_village'] ?? null,
                    'kode_pos' => $data['new_building_postal_code'] ?? null,
                    'postal_code' => $data['new_building_postal_code'] ?? null,
                    'status_update' => true,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
                $data['building_id'] = $building->id;
                
                // Assign building to customer (many-to-many relationship)
                if ($data['customer_id']) {
                    $this->attachCustomerToBuilding($building, (int) $data['customer_id']);
                }

                $this->forgetSurveyWizardBuildingCaches();
            }

            // Generate survey number with Intersection Logic
            $documentNumberService = new \App\Services\DocumentNumberService();
            $user = Auth::user();
            $branchCode = null;

            if ($user) {
                // 1. Get branch code from building location
                $buildingBranchCode = $documentNumberService->getBranchCodeFromBuilding($data['building_id']);
                
                // 2. Check if building branch is in user's assigned branches
                $hasAccessToBuildingBranch = $user->assignedBranches()->where('code', $buildingBranchCode)->exists();
                
                if ($buildingBranchCode && $hasAccessToBuildingBranch) {
                    $branchCode = $buildingBranchCode;
                } else {
                    if ($user->branch) {
                        $branchCode = $user->branch->branch_code ?? $user->branch->code;
                    }
                }
            }

            $surveyNumber = $documentNumberService->generate(
                'survey',
                $branchCode,
                $data['building_id'],
                null,
                null,
                null,
                null
            );

            // Create survey
            $survey = Survey::create([
                'survey_number' => $surveyNumber,
                'customer_id' => $data['customer_id'],
                'building_id' => $data['building_id'],
                'surveyor_id' => Auth::id(),
                'marketing_id' => $data['marketing_id'],
                'survey_date' => $data['survey_date'],
                'survey_location' => $data['new_building_address'] ?? '',
                'name' => $data['new_customer_name'] ?? '',
                'customer_type' => $data['new_company_type'] ?? '',
                'contact_person' => $data['new_contact_name'] ?? '',
                'email' => $data['new_contact_email'] ?? '',
                'phone_1' => $data['new_contact_phone'] ?? '',
                'position' => $data['new_contact_position'] ?? '',
                'building_name' => $data['new_building_name'] ?? '',
                'building_location_detail' => $data['building_location_detail'] ?? '',
                'address_1' => $data['new_building_address'] ?? '',
                'province' => $data['new_building_province'] ?? '',
                'city' => $data['new_building_city'] ?? '',
                'district' => $data['new_building_district'] ?? '',
                'village' => $data['new_building_village'] ?? '',
                'postal_code' => $data['new_building_postal_code'] ?? '',
                'status' => $request->action === 'finalize_email' ? 'approved' : 'draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Create survey details (rooms)
            foreach ($data['rooms'] as $roomData) {
                // MOM11: Find or Create MasterRoom FIRST by room_name + building_id
                $masterRoom = Room::where('building_id', $data['building_id'])
                    ->where('room_name', $roomData['room_name'])
                    ->first();
                
                if ($masterRoom) {
                    // Update existing MasterRoom with survey detail data
                    $masterRoom->update([
                        'room_type' => $roomData['room_type'],
                        'room_floor' => $roomData['floor'],
                        'room_qty' => $roomData['qty'],
                        'room_temperature' => $roomData['temperature'] ?? 0,
                        'room_intensity' => $roomData['intensity'],
                        'room_installation_type' => $roomData['installation_type'],
                        'room_length' => $roomData['length'],
                        'room_width' => $roomData['width'],
                        'room_height' => $roomData['height'],
                        'room_remark' => $roomData['remark'],
                        'is_active' => true,
                        'customer_id' => $data['customer_id'],
                        'updated_by' => Auth::id()
                    ]);
                } else {
                    // Create new MasterRoom (let database auto-increment ID)
                    $masterRoom = Room::create([
                        'building_id' => $data['building_id'],
                        'customer_id' => $data['customer_id'],
                        'room_name' => $roomData['room_name'],
                        'room_code' => 'RM-' . strtoupper(substr($roomData['room_name'], 0, 3)) . '-' . time(),
                        'room_type' => $roomData['room_type'],
                        'room_floor' => $roomData['floor'],
                        'room_qty' => $roomData['qty'],
                        'room_temperature' => $roomData['temperature'] ?? 0,
                        'room_intensity' => $roomData['intensity'],
                        'room_installation_type' => $roomData['installation_type'],
                        'room_length' => $roomData['length'],
                        'room_width' => $roomData['width'],
                        'room_height' => $roomData['height'],
                        'room_remark' => $roomData['remark'],
                        'is_active' => true,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);
                }

                // Create SurveyDetail WITH room_id link to MasterRoom
                $surveyDetail = SurveyDetail::create([
                    'survey_id' => $survey->id,
                    'room_id' => $masterRoom->id, // CRITICAL: Link to MasterRoom!
                    'room_name' => $roomData['room_name'],
                    'room_type' => $roomData['room_type'],
                    'room_area' => $roomData['length'] * $roomData['width'],
                    'quantity_needed' => $roomData['qty'],
                    'specifications' => json_encode([
                        'floor' => $roomData['floor'],
                        'intensity' => $roomData['intensity'],
                        'installation_type' => $roomData['installation_type'],
                        'qty' => $roomData['qty'],
                        'length' => $roomData['length'],
                        'width' => $roomData['width'],
                        'height' => $roomData['height'],
                        'area' => $roomData['length'] * $roomData['width'],
                        'temperature' => $roomData['temperature'] ?? null,
                        'remark' => $roomData['remark']
                    ]),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
                
            }

            // Assign building to customer (if not already assigned)
            if ($data['customer_id'] && $data['building_id']) {
                $building = Building::find($data['building_id']);
                if ($building) {
                    // Check if building-customer relationship already exists
                    if (!$building->customers()->where('customers.id', $data['customer_id'])->exists()) {
                        // Attach customer to building (many-to-many)
                        $building->customers()->attach($data['customer_id'], [
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            DB::commit();

            $this->forgetSurveyWizardCustomerCaches();
            $this->forgetSurveyWizardContactCaches((int) ($data['customer_id'] ?? 0));

            // Clear wizard session
            session()->forget('survey_wizard');

            return response()->json([
                'status' => 'success',
                'message' => $request->action === 'finalize_email' ? 'Survey finalized and approved' : 'Survey saved as draft',
                'survey_id' => $survey->id,
                'redirect_url' => route('marketing.surveys.show', $survey->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save survey: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customers for autocomplete
     */
    public function getCustomers(Request $request)
    {
        $query = $request->get('q', '');
        
        $customers = Customer::with('customerTaxSettings')
            ->where('name', 'like', '%' . $query . '%')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'customer_type', 'company_type'])
            ->map(function (Customer $customer) {
                $tax = $customer->customerTaxSettings->first();
                $label = $customer->name;

                if ($tax) {
                    $npwp = $tax->tax_number ?: '-';
                    $address = $tax->tax_address ?: '-';
                    $shortAddress = strlen($address) > 30 ? substr($address, 0, 30) . '...' : $address;
                    $label = "{$customer->name} - {$npwp} - {$shortAddress}";
                }

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'text' => $label,
                    'customer_type' => $customer->customer_type,
                    'company_type' => $customer->company_type,
                ];
            });

        return response()->json($customers);
    }

    /**
     * Get marketing staff for autocomplete
     */
    public function getMarketingStaff(Request $request)
    {
        $query = $request->get('q', '');
        
        $staff = User::where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                  ->orWhere('email', 'like', '%' . $query . '%');
            })
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'salutation', 'email']);

        // Marketing dropdowns show the sales name without salutation titles.
        $staff = $staff->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ];
        });

        return response()->json($staff);
    }

    /**
     * Get contacts for customer
     */
    public function getContacts(Request $request)
    {
        $customerId = $request->get('customer_id');

        if ($request->filled('contact_id')) {
            $contact = CustomerContact::where('is_active', true)
                ->whereKey($request->get('contact_id'))
                ->first(['id', 'name', 'email', 'phone', 'position']);

            return response()->json($contact ? [$contact] : []);
        }

        if (!$customerId) {
            return response()->json([]);
        }

        $contacts = Cache::remember($this->surveyWizardContactsCacheKey((int) $customerId), now()->addMinutes(5), function () use ($customerId) {
            $customer = Customer::find($customerId);
            if (!$customer) {
                return collect();
            }

            $legacyContacts = CustomerContact::where('customer_id', $customerId)
                ->where('is_active', true)
                ->get(['id', 'name', 'email', 'phone', 'position']);

            $multiPicContacts = $customer->contacts()
                ->where('customer_contacts.is_active', true)
                ->get(['customer_contacts.id', 'customer_contacts.name', 'customer_contacts.email', 'customer_contacts.phone', 'customer_contacts.position']);

            return $legacyContacts->merge($multiPicContacts)->unique('id')->values();
        });

        return response()->json($contacts);
    }



    /**
     * Get buildings for customer
     * Business Rules (Updated 2024-12-25):
     * Always load ALL buildings - customer can select any existing building.
     * If the selected building is not yet assigned to customer, it will be 
     * assigned when the survey is saved.
     */
    public function getBuildings(Request $request)
    {
        $buildings = Cache::remember($this->surveyWizardBuildingsCacheKey(), now()->addMinutes(5), function () {
            return Building::where('status_update', true)
                ->whereNull('deleted_at')
                ->whereNotNull('province_id')
                ->whereNotNull('city_id')
                ->with([
                    'province:id,name',
                    'city:id,name,type',
                    'district:id,name',
                    'subdistrict:id,name,postal_code',
                ])
                ->orderBy('created_at', 'desc')
                ->get([
                    'id', 'name', 'nama_gedung', 'address', 'alamat_1', 'alamat_2',
                    'postal_code', 'kode_pos', 'phone_1', 'phone_2', 'fax',
                    'province_id', 'city_id', 'district_id', 'subdistrict_id', 'deleted_at', 'created_at'
                ])
                ->filter(function ($building) {
                    return !empty($building->name) || !empty($building->nama_gedung);
                })
                ->unique(function ($building) {
                    return implode('|', [
                        $this->normalizeLookupValue($this->buildingDisplayName($building)),
                        $this->normalizeLookupValue($this->buildingAddressLine($building)),
                        $building->city_id,
                    ]);
                })
                ->map(fn (Building $building) => $this->formatBuildingForSurveyWizard($building))
                ->values();
        });

        return response()->json($buildings);
    }



    /**
     * Get cities by province
     */
    public function getCitiesByProvince(Request $request)
    {
        $provinceId = $request->get('province_id');
        
        if (!$provinceId) {
            return response()->json([]);
        }

        $cities = City::where('province_id', $provinceId)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return response()->json($cities);
    }

    /**
     * Get districts by city
     */
    public function getDistrictsByCity(Request $request)
    {
        $cityId = $request->get('city_id');

        if (!$cityId) {
            return response()->json([]);
        }

        $districts = Cache::remember("survey-wizard:districts:city:{$cityId}", now()->addMinutes(10), function () use ($cityId) {
            return District::where('city_id', $cityId)
                ->orderBy('name')
                ->get(['id', 'name']);
        });

        return response()->json($districts);
    }



    /**
     * Get subdistricts by district
     */
    public function getSubdistrictsByDistrict(Request $request)
    {
        $districtId = $request->get('district_id');
        
        if (!$districtId) {
            return response()->json([]);
        }

        $subdistricts = Subdistrict::where('district_id', $districtId)
            ->orderBy('name')
            ->get(['id', 'name', 'postal_code']);

        return response()->json($subdistricts);
    }


    /**
     * Create master building
     */
    public function createMasterBuilding(Request $request)
    {
        try {
            // Validate required fields
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'address1' => 'required|string|max:255',
                'province_id' => 'required|exists:provinces,id',
                'city_id' => 'required|exists:cities,id',
                'district_id' => 'required|exists:districts,id',
                'subdistrict_id' => 'required|exists:subdistricts,id',
                'postal_code' => 'required|string|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $building = $this->findReusableBuilding($request->name, $request->address1, (int) $request->city_id);
            $wasReused = (bool) $building;

            if ($building) {
                $building->update([
                    'name' => $building->name ?: $request->name,
                    'nama_gedung' => $building->nama_gedung ?: $request->name,
                    'building_type' => $building->building_type ?: $request->building_type,
                    'address' => $building->address ?: $request->address1,
                    'alamat_1' => $building->alamat_1 ?: $request->address1,
                    'alamat_2' => $building->alamat_2 ?: $request->address2,
                    'postal_code' => $building->postal_code ?: $request->postal_code,
                    'kode_pos' => $building->kode_pos ?: $request->postal_code,
                    'province_id' => $building->province_id ?: $request->province_id,
                    'city_id' => $building->city_id ?: $request->city_id,
                    'district_id' => $building->district_id ?: $request->district_id,
                    'subdistrict_id' => $building->subdistrict_id ?: $request->subdistrict_id,
                    'updated_by' => Auth::id(),
                ]);
            } else {
                $building = Building::create([
                    'name' => $request->name,
                    'nama_gedung' => $request->name,
                    'building_type' => $request->building_type,
                    'address' => $request->address1,
                    'alamat_1' => $request->address1,
                    'alamat_2' => $request->address2,
                    'postal_code' => $request->postal_code,
                    'kode_pos' => $request->postal_code,
                    'province_id' => $request->province_id,
                    'city_id' => $request->city_id,
                    'district_id' => $request->district_id,
                    'subdistrict_id' => $request->subdistrict_id,
                    'status_update' => true, // Always active for new buildings
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            // Create building-customer relationship if customer_id provided
            $this->attachCustomerToBuilding($building, $request->customer_id ? (int) $request->customer_id : null);

            $this->forgetSurveyWizardBuildingCaches();

            // Load building with location data
            $building->load(['province', 'city', 'district', 'subdistrict']);
            
            return response()->json([
                'success' => true,
                'reused' => $wasReused,
                'building' => [
                    'id' => $building->id,
                    'name' => $building->name,
                    'nama_gedung' => $building->nama_gedung,
                    'address1' => $building->alamat_1,
                    'address2' => $building->alamat_2,
                    'postal_code' => $building->kode_pos,
                    'province_id' => $building->province_id,
                    'city_id' => $building->city_id,
                    'district_id' => $building->district_id,
                    'subdistrict_id' => $building->subdistrict_id,
                    'province' => $building->province ? $building->province->name : '',
                    'city' => $building->city ? $building->city->name : '',
                    'district' => $building->district ? $building->district->name : '',
                    'subdistrict' => $building->subdistrict ? $building->subdistrict->name : '',
                    'phone1' => $building->phone_1,
                    'phone2' => $building->phone_2
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show survey detail
     */
    public function show($id)
    {
        $survey = Survey::with([
            'marketing',
            'creator',
            'updater',
            'surveyDetails.updater'
        ])->findOrFail($id);

        return view('marketing.surveys.wizard.show', compact('survey'));
    }

    /**
     * Show survey edit form
     */
    public function edit($id)
    {
        $survey = Survey::with([
            'marketing',
            'creator',
            'updater',
            'surveyDetails.updater'
        ])->findOrFail($id);

        $wizardData = $this->getSurveyWizardFormData();
        $marketingStaff = $wizardData['marketingStaff'];
        $customers = $wizardData['customers'];
        $companyTypes = $wizardData['companyTypes'];
        $roomTypes = $wizardData['roomTypes'];
        $floors = $wizardData['floors'];
        $intensities = $wizardData['intensities'];
        $installationTypes = $wizardData['installationTypes'];

        return view('marketing.surveys.wizard.edit', compact(
            'survey', 'marketingStaff', 'customers', 'companyTypes', 
            'roomTypes', 'floors', 'intensities', 'installationTypes'
        ));
    }

    /**
     * Update survey
     */
    public function update(Request $request, $id)
    {
        $survey = Survey::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'marketing_id' => 'required|exists:users,id',
            'survey_date' => 'required|date',
            'customer_id' => 'nullable|exists:customers,id',
            'add_new_customer' => 'boolean',
            'new_company_type' => 'required_if:add_new_customer,true',
            'new_customer_name' => 'required_if:add_new_customer,true|string|max:255',
            'contact_id' => 'nullable|exists:customer_contacts,id',
            'add_new_contact' => 'boolean',
            'new_contact_name' => 'required_if:add_new_contact,true|string|max:255',
            'new_contact_email' => 'required_if:add_new_contact,true|email',
            'new_contact_phone' => 'required_if:add_new_contact,true|string|max:20',
            'new_contact_position' => 'required_if:add_new_contact,true|string|max:255',
            'building_id' => 'nullable|exists:buildings,id',
            'add_new_building' => 'boolean',
            'new_building_name' => 'required_if:add_new_building,true|string|max:255',
            'new_building_address' => 'required_if:add_new_building,true|string|max:500',
            'new_building_province' => 'required_if:add_new_building,true|string|max:100',
            'new_building_city' => 'required_if:add_new_building,true|string|max:100',
            'new_building_district' => 'required_if:add_new_building,true|string|max:100',
            'new_building_village' => 'required_if:add_new_building,true|string|max:100',
            'new_building_postal_code' => 'required_if:add_new_building,true|string|max:10',
            'rooms' => 'required|array|min:1',
            'rooms.*.room_name' => 'required|string|max:255',
            'rooms.*.room_type' => 'required|string|max:255',
            'rooms.*.floor' => 'required|string|max:255',
            'rooms.*.intensity' => 'required|string|max:255',
            'rooms.*.installation_type' => 'required|string|max:255',
            'rooms.*.qty' => 'required|integer|min:1',
            'rooms.*.length' => 'required|numeric|min:0',
            'rooms.*.width' => 'required|numeric|min:0',
            'rooms.*.height' => 'required|numeric|min:0',
            'rooms.*.remark' => 'nullable|string|max:500',
            'action' => 'required|in:save_draft,finalize'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create new customer if needed
            if ($request->add_new_customer) {
                // Get customer type from database based on company type ID
                $customerTypeEnum = 'company'; // Default to 'company'
                if ($request->new_company_type) {
                    $optionDetail = OptionDetail::find($request->new_company_type);
                    if ($optionDetail) {
                        // Store the actual company type name (e.g., PT, CV, Perorangan)
                        $companyTypeName = $optionDetail->option_name;
                        
                        // Map company type name to customer_type enum
                        $typeName = strtolower($optionDetail->option_name);
                        if (in_array($typeName, ['individual', 'personal', 'perorangan'])) {
                            $customerTypeEnum = 'individual';
                        } else {
                            $customerTypeEnum = 'company';
                        }
                    }
                }
                
                $customer = Customer::create([
                    'customer_code' => Customer::generateCustomerCode($request->new_customer_name),
                    'name' => $request->new_customer_name,
                    'customer_type' => $customerTypeEnum,
                    'status' => 'customer',
                    'company_type' => $companyTypeName ?? 'PT', // Use actual selected company type
                    'is_active' => true,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
                $customerId = $customer->id;
            } else {
                $customerId = $request->customer_id;
            }

            // Create new contact if needed
            if ($request->add_new_contact) {
                $contact = CustomerContact::create([
                    'customer_id' => $customerId,
                    'name' => $request->new_contact_name,
                    'email' => $request->new_contact_email,
                    'phone' => $request->new_contact_phone,
                    'position' => $request->new_contact_position,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
            }

            // Create new building if needed
            if ($request->add_new_building) {
                $building = Building::create([
                    'customer_id' => $customerId,
                    'name' => $request->new_building_name,
                    'address' => $request->new_building_address,
                    'alamat_1' => $request->new_building_address,
                    'postal_code' => $request->new_building_postal_code,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
                $buildingId = $building->id;
            } else {
                $buildingId = $request->building_id;
            }

            // Get customer data
            $customer = Customer::find($customerId);
            $contact = CustomerContact::find($request->contact_id);
            $building = Building::find($buildingId);

            // Update survey
            $survey->update([
                'customer_id' => $customerId,
                'building_id' => $buildingId,
                'marketing_id' => $request->marketing_id,
                'survey_date' => $request->survey_date,
                'survey_location' => $building->address ?? $building->alamat_1 ?? '',
                'name' => $customer->name ?? '',
                'customer_type' => $customer->company_type ?? '',
                'contact_person' => $contact->name ?? '',
                'email' => $contact->email ?? '',
                'phone_1' => $contact->phone ?? '',
                'position' => $contact->position ?? '',
                'building_name' => $building->name ?? $building->nama_gedung ?? '',
                'address_1' => $building->address ?? $building->alamat_1 ?? '',
                'postal_code' => $building->postal_code ?? $building->kode_pos ?? '',
                'status' => $request->action === 'finalize' ? 'approved' : 'draft',
                'updated_by' => Auth::id()
            ]);

            // Delete existing survey details
            $survey->surveyDetails()->delete();

            // Create new survey details (rooms)
            foreach ($request->rooms as $roomData) {
                // MOM11: Find or Create MasterRoom FIRST by room_name + building_id
                $masterRoom = Room::where('building_id', $buildingId)
                    ->where('room_name', $roomData['room_name'])
                    ->first();
                
                if ($masterRoom) {
                    // Update existing MasterRoom with survey detail data
                    $masterRoom->update([
                        'room_type' => $roomData['room_type'],
                        'room_floor' => $roomData['floor'],
                        'room_qty' => $roomData['qty'],
                        'room_temperature' => $roomData['temperature'] ?? 0,
                        'room_intensity' => $roomData['intensity'],
                        'room_installation_type' => $roomData['installation_type'],
                        'room_length' => $roomData['length'],
                        'room_width' => $roomData['width'],
                        'room_height' => $roomData['height'],
                        'room_remark' => $roomData['remark'],
                        'is_active' => true,
                        'updated_by' => Auth::id()
                    ]);
                } else {
                    // Create new MasterRoom (let database auto-increment ID)
                    $masterRoom = Room::create([
                        'building_id' => $buildingId,
                        'room_name' => $roomData['room_name'],
                        'room_code' => 'RM-' . strtoupper(substr($roomData['room_name'], 0, 3)) . '-' . time(),
                        'room_type' => $roomData['room_type'],
                        'room_floor' => $roomData['floor'],
                        'room_qty' => $roomData['qty'],
                        'room_temperature' => $roomData['temperature'] ?? 0,
                        'room_intensity' => $roomData['intensity'],
                        'room_installation_type' => $roomData['installation_type'],
                        'room_length' => $roomData['length'],
                        'room_width' => $roomData['width'],
                        'room_height' => $roomData['height'],
                        'room_remark' => $roomData['remark'],
                        'is_active' => true,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);
                }

                // Create SurveyDetail WITH room_id link to MasterRoom
                $surveyDetail = SurveyDetail::create([
                    'survey_id' => $survey->id,
                    'room_id' => $masterRoom->id, // CRITICAL: Link to MasterRoom!
                    'room_name' => $roomData['room_name'],
                    'room_type' => $roomData['room_type'],
                    'room_area' => $roomData['length'] * $roomData['width'],
                    'quantity_needed' => $roomData['qty'],
                    'specifications' => json_encode([
                        'floor' => $roomData['floor'],
                        'intensity' => $roomData['intensity'],
                        'installation_type' => $roomData['installation_type'],
                        'qty' => $roomData['qty'],
                        'length' => $roomData['length'],
                        'width' => $roomData['width'],
                        'height' => $roomData['height'],
                        'area' => $roomData['length'] * $roomData['width'],
                        'temperature' => $roomData['temperature'] ?? null,
                        'remark' => $roomData['remark']
                    ]),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
                
            }

            DB::commit();

            $this->forgetSurveyWizardCustomerCaches();
            $this->forgetSurveyWizardContactCaches((int) $customerId);

            return response()->json([
                'status' => 'success',
                'message' => 'Survey updated successfully',
                'redirect_url' => route('marketing.surveys.wizard.show', $survey->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update survey: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get survey detail for editing
     */
    public function getSurveyDetail($id)
    {
        $detail = SurveyDetail::findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $detail
        ]);
    }

    /**
     * Update survey detail
     */
    public function updateSurveyDetail(Request $request, $id)
    {
        $detail = SurveyDetail::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'room_name' => 'required|string|max:255',
            'room_type' => 'required|string|max:255',
            'floor' => 'required|string|max:255',
            'intensity' => 'required|string|max:255',
            'installation_type' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
            'length' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'remark' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $detail->update([
                'room_name' => $request->room_name,
                'room_type' => $request->room_type,
                'room_area' => $request->length * $request->width,
                'quantity_needed' => $request->qty,
                'specifications' => json_encode([
                    'floor' => $request->floor,
                    'intensity' => $request->intensity,
                    'installation_type' => $request->installation_type,
                    'qty' => $request->qty,
                    'length' => $request->length,
                    'width' => $request->width,
                    'height' => $request->height,
                    'area' => $request->length * $request->width,
                    'temperature' => $request->temperature ?? null,
                    'remark' => $request->remark
                ]),
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Survey detail updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update survey detail: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save survey from wizard - COMPLETE DATA SAVE
     */
    public function save(Request $request)
    {
        // Debug logging untuk melihat data yang dikirim
        
        $validator = Validator::make($request->all(), [
            'marketing_id' => 'required|exists:users,id',
            'survey_date' => 'required|date',
            'customer_id' => 'nullable|exists:customers,id',
            'add_new_customer' => 'nullable|in:true,false,1,0',
            'new_company_type' => 'required_if:add_new_customer,true|nullable|string|max:255',
            'new_customer_name' => 'required_if:add_new_customer,true|nullable|string|max:255',
            'contact_id' => 'nullable|exists:customer_contacts,id',
            'add_new_contact' => 'nullable|in:true,false,1,0',
            'new_contact_salutation' => 'nullable|string|max:255',
            'new_contact_name' => 'required_if:add_new_contact,true|nullable|string|max:255',
            'new_contact_email' => 'required_if:add_new_contact,true|nullable|email',
            'new_contact_phone' => 'required_if:add_new_contact,true|nullable|string|max:20',
            'new_contact_position' => 'required_if:add_new_contact,true|nullable|string|max:255',
            'building_id' => 'nullable|exists:buildings,id',
            'add_new_building' => 'nullable|in:true,false,1,0',
            'new_building_name' => 'required_if:add_new_building,true|nullable|string|max:255',
            'new_building_type' => 'nullable|string|max:255',
            'new_building_location_detail' => 'required_if:add_new_building,true|nullable|string|max:500',
            'new_building_phone1' => 'nullable|string|max:20',
            'new_building_phone2' => 'nullable|string|max:20',
            'new_building_fax' => 'nullable|string|max:20',
            'building_location_detail' => 'nullable|string|max:255',
            'rooms' => 'required|array|min:1',
            'rooms.*.room_name' => 'required|string|max:255',
            'rooms.*.room_type' => 'required|string|max:255',
            'rooms.*.floor' => 'required|string|max:255',
            'rooms.*.intensity' => 'required|string|max:255',
            'rooms.*.installation_type' => 'required|string|max:255',
            'rooms.*.qty' => 'required|integer|min:1',
            'rooms.*.length' => 'required|numeric|min:0',
            'rooms.*.width' => 'required|numeric|min:0',
            'rooms.*.height' => 'required|numeric|min:0',
            'rooms.*.remark' => 'nullable|string|max:500',
            'action' => 'required|in:save_draft,finalize'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Convert string boolean values to actual booleans
        $addNewCustomer = filter_var($request->add_new_customer, FILTER_VALIDATE_BOOLEAN);
        $addNewContact = filter_var($request->add_new_contact, FILTER_VALIDATE_BOOLEAN);
        $addNewBuilding = filter_var($request->add_new_building, FILTER_VALIDATE_BOOLEAN);

        $submissionCacheKey = $this->surveyWizardSubmissionCacheKey($request);
        $existingSurveyId = Cache::get($submissionCacheKey);
        if ($existingSurveyId) {
            return $this->duplicateSurveyResponse((int) $existingSurveyId);
        }

        $submissionLock = Cache::lock($submissionCacheKey . ':lock', 30);
        $submissionLockAcquired = false;

        try {
            $submissionLock->block(10);
            $submissionLockAcquired = true;

            $existingSurveyId = Cache::get($submissionCacheKey);
            if ($existingSurveyId) {
                return $this->duplicateSurveyResponse((int) $existingSurveyId);
            }

            DB::beginTransaction();

            // ========================================
            // 1. CREATE NEW CUSTOMER IF NEEDED
            // ========================================
            $customerId = null;
            if ($addNewCustomer) {
                // Generate customer code using the standard function
                $customerCode = Customer::generateCustomerCode($request->new_customer_name);
                
                // Get customer type from database based on company type ID
                $customerTypeEnum = 'company'; // Default to 'company'
                if ($request->new_company_type) {
                    $optionDetail = OptionDetail::find($request->new_company_type);
                    if ($optionDetail) {
                        // Store the actual company type name (e.g., PT, CV, Perorangan)
                        $companyTypeName = $optionDetail->option_name;
                        
                        // Map company type name to customer_type enum
                        $typeName = strtolower($optionDetail->option_name);
                        if (in_array($typeName, ['individual', 'personal', 'perorangan'])) {
                            $customerTypeEnum = 'individual';
                        } else {
                            $customerTypeEnum = 'company';
                        }
                    }
                }
                
                $customer = Customer::create([
                    'customer_code' => $customerCode,
                    'name' => $request->new_customer_name,
                    'customer_type' => $customerTypeEnum,
                    'status' => 'customer',
                    'company_type' => $companyTypeName ?? 'PT', // Store actual company type name
                    'is_active' => true,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                    'update_by_1' => Auth::id(),
                    'update_at_1' => now()
                ]);
                $customerId = $customer->id;
            } else {
                $customerId = $request->customer_id;
            }

            // ========================================
            // 2. CREATE NEW CONTACT IF NEEDED
            // ========================================
            $contactId = null;
            if ($addNewContact) {
                $contact = CustomerContact::create([
                    'customer_id' => $customerId,
                    'name' => $request->new_contact_name,
                    'email' => $request->new_contact_email,
                    'phone' => $request->new_contact_phone,
                    'position' => $request->new_contact_position,
                    'salutation' => $request->new_contact_salutation,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
                $contactId = $contact->id;
            } else {
                $contactId = $request->contact_id;
            }

            // ========================================
            // 3. CREATE NEW BUILDING IF NEEDED
            // ========================================
            $buildingId = null;
            if ($addNewBuilding) {
                if (!$request->master_building_province || !$request->master_building_city) {
                    DB::rollBack();

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Data lokasi building belum lengkap. Pilih province dan city terlebih dahulu agar survey tidak tersimpan dengan City Unknown.'
                    ], 422);
                }

                $buildingName = trim((string) $request->new_building_name);
                $buildingAddress = trim((string) ($request->new_building_location_detail ?: $request->building_location_detail));
                $building = $this->findReusableBuilding($buildingName, $buildingAddress, (int) $request->master_building_city);

                if ($building) {
                    $building->update([
                        'name' => $building->name ?: $buildingName,
                        'nama_gedung' => $building->nama_gedung ?: $buildingName,
                        'alamat_1' => $building->alamat_1 ?: $buildingAddress,
                        'address' => $building->address ?: $buildingAddress,
                        'phone_1' => $building->phone_1 ?: ($request->new_building_phone1 ?? ''),
                        'phone_2' => $building->phone_2 ?: ($request->new_building_phone2 ?? ''),
                        'fax' => $building->fax ?: ($request->new_building_fax ?? ''),
                        'province_id' => $building->province_id ?: $request->master_building_province,
                        'city_id' => $building->city_id ?: $request->master_building_city,
                        'district_id' => $building->district_id ?: $request->master_building_district,
                        'subdistrict_id' => $building->subdistrict_id ?: $request->master_building_subdistrict,
                        'kode_pos' => $building->kode_pos ?: $request->master_building_postal_code,
                        'postal_code' => $building->postal_code ?: $request->master_building_postal_code,
                        'updated_by' => Auth::id(),
                    ]);
                } else {
                    $building = Building::create([
                        'name' => $buildingName,
                        'nama_gedung' => $buildingName,
                        'alamat_1' => $buildingAddress,
                        'address' => $buildingAddress,
                        'alamat_2' => '',
                        'phone_1' => $request->new_building_phone1 ?? '',
                        'phone_2' => $request->new_building_phone2 ?? '',
                        'fax' => $request->new_building_fax ?? '',
                        'province_id' => $request->master_building_province,
                        'city_id' => $request->master_building_city,
                        'district_id' => $request->master_building_district,
                        'subdistrict_id' => $request->master_building_subdistrict,
                        'kode_pos' => $request->master_building_postal_code,
                        'postal_code' => $request->master_building_postal_code,
                        'status_update' => true,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);
                }

                $buildingId = $building->id;
                
                // Create building-customer relationship
                $this->attachCustomerToBuilding($building, $customerId ? (int) $customerId : null);
                $this->forgetSurveyWizardBuildingCaches();
            } else {
                $buildingId = $request->building_id;
            }

            // ========================================
            // 4. GENERATE SURVEY NUMBER (MOM10)
            // ========================================
            // MOM10: Generate survey number using DocumentNumberService with Intersection Logic
            $documentNumberService = new \App\Services\DocumentNumberService();
            $user = Auth::user();
            $branchCode = null;

            if ($user) {
                // 1. Get branch code from building location
                $buildingBranchCode = $documentNumberService->getBranchCodeFromBuilding($buildingId);
                
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

            $surveyNumber = $documentNumberService->generate(
                'survey',
                $branchCode,
                $buildingId,
                null,
                null,
                null,
                null
            );
            

            // ========================================
            // 5. GET RELATED DATA
            // ========================================
            $customer = Customer::find($customerId);
            $contact = CustomerContact::find($contactId);
            $building = Building::with(['province', 'city', 'district', 'subdistrict'])->find($buildingId);

            // ========================================
            // 6. CREATE SURVEY
            // ========================================
            // companyTypeName already set in Step 1 (Create New Customer)
            if (!$addNewCustomer && $customer) {
                $companyTypeName = $customer->company_type;
            }
            
            // Validate customer exists
            $customerExists = Customer::find($customerId);
            if (!$customerExists) {
                \Log::error('❌ Customer not found for survey creation:', ['customer_id' => $customerId]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer not found. Please try again.'
                ], 422);
            }
            
            // Validate building exists
            $buildingExists = Building::find($buildingId);
            if (!$buildingExists) {
                \Log::error('❌ Building not found for survey creation:', ['building_id' => $buildingId]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Building not found. Please try again.'
                ], 422);
            }
            
            
            if ($buildingExists && (!$buildingExists->province_id || !$buildingExists->city_id)) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Building yang dipilih belum punya province/city. Silakan pilih alamat building lain yang lengkap atau update master building terlebih dahulu.'
                ], 422);
            }

            // Final validation: ensure customer_id is not null
            if (is_null($customerId)) {
                \Log::error('❌ CRITICAL ERROR: customer_id is null before survey creation', [
                    'add_new_customer' => $addNewCustomer,
                    'request_customer_id' => $request->customer_id,
                    'all_request_data' => $request->all()
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer ID is missing. Please try again or contact support.'
                ], 422);
            }
            
            $survey = Survey::create([
                'survey_number' => $surveyNumber,
                'customer_id' => $customerId,
                'building_id' => $buildingId,
                'surveyor_id' => $request->marketing_id, // Marketing staff is also the surveyor
                'marketing_id' => $request->marketing_id,
                'survey_date' => $request->survey_date,
                'survey_location' => $building->alamat_1 ?? $building->address ?? 'Lokasi Survey Tidak Diketahui',
                'company_name' => $customer->name ?? '',
                'customer_type' => $companyTypeName ?? 'corporate', // Store actual company type name
                'contact_person' => $contact->name ?? '',
                'email' => $contact->email ?? '',
                'phone_1' => $contact->phone ?? '',
                'position' => $contact->position ?? '',
                'building_name' => $building->nama_gedung ?? $building->name ?? 'Nama Gedung Tidak Diketahui',
                'building_location_detail' => $request->building_location_detail ?: $request->new_building_location_detail ?: $building->location_detail ?: '',
                'address_1' => $building->alamat_1 ?? $building->address ?? 'Alamat Tidak Diketahui',
                'address_2' => $building->alamat_2 ?? '',
                'province' => $building->province->name ?? 'Provinsi Tidak Diketahui',
                'city' => $building->city->name ?? 'Kota Tidak Diketahui',
                'district' => $building->district->name ?? 'Kecamatan Tidak Diketahui',
                'village' => $building->subdistrict->name ?? 'Kelurahan Tidak Diketahui',
                'postal_code' => $building->kode_pos ?? $building->postal_code ?? '',
                'phone_2' => $building->phone_1 ?? '',
                'status' => $request->action === 'finalize' ? 'approved' : 'draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // ========================================
            // 7. CREATE SURVEY DETAILS (ROOMS)
            // ========================================
            foreach ($request->rooms as $roomData) {
                // MOM11: Find or Create MasterRoom FIRST by room_name + building_id
                $masterRoom = Room::where('building_id', $buildingId)
                    ->where('room_name', $roomData['room_name'])
                    ->first();
                
                if ($masterRoom) {
                    // Update existing MasterRoom with survey detail data
                    $masterRoom->update([
                        'room_type' => $roomData['room_type'],
                        'room_floor' => $roomData['floor'],
                        'room_qty' => $roomData['qty'],
                        'room_temperature' => $roomData['temperature'] ?? 0,
                        'room_intensity' => $roomData['intensity'],
                        'room_installation_type' => $roomData['installation_type'],
                        'room_length' => $roomData['length'],
                        'room_width' => $roomData['width'],
                        'room_height' => $roomData['height'],
                        'room_remark' => $roomData['remark'],
                        'is_active' => true,
                        'updated_by' => Auth::id()
                    ]);
                } else {
                    // Create new MasterRoom (let database auto-increment ID)
                    $masterRoom = Room::create([
                        'building_id' => $buildingId,
                        'room_name' => $roomData['room_name'],
                        'room_code' => 'RM-' . strtoupper(substr($roomData['room_name'], 0, 3)) . '-' . time(),
                        'room_type' => $roomData['room_type'],
                        'room_floor' => $roomData['floor'],
                        'room_qty' => $roomData['qty'],
                        'room_temperature' => $roomData['temperature'] ?? 0,
                        'room_intensity' => $roomData['intensity'],
                        'room_installation_type' => $roomData['installation_type'],
                        'room_length' => $roomData['length'],
                        'room_width' => $roomData['width'],
                        'room_height' => $roomData['height'],
                        'room_remark' => $roomData['remark'],
                        'is_active' => true,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]);
                }

                // Create SurveyDetail WITH room_id link to MasterRoom
                $surveyDetail = SurveyDetail::create([
                    'survey_id' => $survey->id,
                    'room_id' => $masterRoom->id, // CRITICAL: Link to MasterRoom!
                    'room_name' => $roomData['room_name'],
                    'room_type' => $roomData['room_type'],
                    'room_area' => $roomData['length'] * $roomData['width'],
                    'quantity_needed' => $roomData['qty'],
                    'specifications' => json_encode([
                        'floor' => $roomData['floor'],
                        'intensity' => $roomData['intensity'],
                        'installation_type' => $roomData['installation_type'],
                        'qty' => $roomData['qty'],
                        'length' => $roomData['length'],
                        'width' => $roomData['width'],
                        'height' => $roomData['height'],
                        'area' => $roomData['length'] * $roomData['width'],
                        'temperature' => $roomData['temperature'] ?? null,
                        'remark' => $roomData['remark']
                    ]),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ]);
                
            }

            // ========================================
            // 8. ASSIGN BUILDING TO CUSTOMER (if not already assigned)
            // ========================================
            // Ensure building is assigned to customer via many-to-many relationship
            if ($customerId && $buildingId) {
                $building = Building::find($buildingId);
                if ($building) {
                    // Check if building-customer relationship already exists
                    if (!$building->customers()->where('customers.id', $customerId)->exists()) {
                        // Attach customer to building (many-to-many)
                        $building->customers()->attach($customerId, [
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    } else {
                    }
                }
            }

            DB::commit();

            Cache::put($submissionCacheKey, $survey->id, now()->addMinutes(10));

            return response()->json([
                'status' => 'success',
                'message' => 'Survey saved successfully',
                'survey_id' => $survey->id,
                'redirect_url' => route('marketing.surveys.show', $survey->id),
                'data' => [
                    'survey_id' => $survey->id,
                    'survey_number' => $survey->survey_number,
                    'status' => $survey->status
                ]
            ]);

        } catch (LockTimeoutException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Survey sedang diproses. Mohon tunggu sebentar dan cek kembali sebelum submit ulang.'
            ], 429);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Survey save error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save survey: ' . $e->getMessage()
            ], 500);
        } finally {
            if ($submissionLockAcquired) {
                $submissionLock->release();
            }
        }
    }


    /**
     * Create master contact
     */
    public function createMasterContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'position' => 'required|string|max:255',
            'status' => 'required|in:aktif,cuti,berhenti'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $contact = CustomerContact::create([
                'customer_id' => null, // Will be set when associated with customer
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'position' => $request->position,
                'is_active' => $request->status === 'aktif' ? 1 : 0,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Master contact created successfully',
                'data' => $contact
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create master contact: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add room to survey
     */
    public function addRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'survey_id' => 'required|exists:surveys,id',
            'room_name' => 'required|string|max:255',
            'room_type' => 'required|string|max:255',
            'floor' => 'required|string|max:255',
            'scent_intensity' => 'required|string|max:255',
            'installation_type' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
            'length' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'temperature' => 'nullable|numeric|min:0|max:100',
            'remark' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get survey to get building_id
            $survey = \App\Models\Survey::find($request->survey_id);
            if (!$survey) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Survey not found'
                ], 404);
            }

            // Calculate area
            $area = $request->length * $request->width;
            
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
                'temperature' => $request->temperature ?? null,
                'remark' => $request->remark
            ];

            // MOM11: Find or Create MasterRoom FIRST by room_name + building_id
            $masterRoom = \App\Models\MasterRoom::where('building_id', $survey->building_id)
                ->where('room_name', $request->room_name)
                ->first();
            
            if ($masterRoom) {
                // Update existing MasterRoom with survey detail data
                $masterRoom->update([
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
                    'updated_by' => Auth::id()
                ]);
            } else {
                // Create new MasterRoom (let database auto-increment ID)
                $masterRoom = \App\Models\MasterRoom::create([
                    'building_id' => $survey->building_id,
                    'room_name' => $request->room_name,
                    'room_code' => 'RM-' . strtoupper(substr($request->room_name, 0, 3)) . '-' . time(),
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
            }

            // Create SurveyDetail WITH room_id link to MasterRoom
            $surveyDetail = \App\Models\SurveyDetail::create([
                'survey_id' => $request->survey_id,
                'room_id' => $masterRoom->id, // CRITICAL: Link to MasterRoom!
                'room_name' => $request->room_name,
                'room_type' => $request->room_type,
                'room_area' => $area,
                'quantity_needed' => $request->qty,
                'specifications' => json_encode($specifications),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);
            

            return response()->json([
                'status' => 'success',
                'message' => 'Room added successfully',
                'data' => $surveyDetail
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add room: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get room types for dropdown
     */
    public function getRoomTypes()
    {
        try {
            $roomTypes = MasterOption::where('name', 'Room Type')
                ->first()
                ?->optionDetails()
                ->where('is_active', true)
                ->get(['id', 'option_name as value', 'option_name as text']);

            return response()->json($roomTypes);
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    /**
     * Get floors for dropdown
     */
    public function getFloors()
    {
        try {
            $floors = MasterOption::where('name', 'Floor')
                ->first()
                ?->optionDetails()
                ->where('is_active', true)
                ->get(['id', 'option_name as value', 'option_name as text']);

            return response()->json($floors);
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    /**
     * Get scent intensities for dropdown
     */
    public function getScentIntensities()
    {
        try {
            $intensities = MasterOption::where('name', 'Scent Intensity')
                ->first()
                ?->optionDetails()
                ->where('is_active', true)
                ->get(['id', 'option_name as value', 'option_name as text']);

            return response()->json($intensities);
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    /**
     * Get installation types for dropdown
     */
    public function getInstallationTypes()
    {
        try {
            $installationTypes = MasterOption::where('name', 'Installation Type')
                ->first()
                ?->optionDetails()
                ->where('is_active', true)
                ->get(['id', 'option_name as value', 'option_name as text']);

            return response()->json($installationTypes);
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }
    
    private function getSurveyWizardFormData(bool $includeStaffAndCustomers = true): array
    {
        $marketingStaff = collect();
        $customers = collect();

        if ($includeStaffAndCustomers) {
            $marketingStaff = Cache::remember('survey-wizard:marketing-staff', now()->addMinutes(10), function () {
                return User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'salutation']);
            });

            $customers = Cache::remember('survey-wizard:customers', now()->addMinutes(5), function () {
                return Customer::with('customerTaxSettings')->get();
            });
        }

        $companyTypes = Cache::remember('survey-wizard:company-types', now()->addMinutes(10), function () {
            return CustomerType::active()->orderBy('name')->get();
        });

        $roomTypes = Cache::remember('survey-wizard:room-types', now()->addMinutes(10), function () {
            return MasterOption::where('name', 'Room Type')->first()?->optionDetails ?? collect();
        });

        $floors = Cache::remember('survey-wizard:floors', now()->addMinutes(10), function () {
            return MasterOption::where('name', 'Floor')->first()?->optionDetails ?? collect();
        });

        $intensities = Cache::remember('survey-wizard:intensities', now()->addMinutes(10), function () {
            return MasterOption::where('name', 'Scent Intensity')->first()?->optionDetails ?? collect();
        });

        $installationTypes = Cache::remember('survey-wizard:installation-types', now()->addMinutes(10), function () {
            return MasterOption::where('name', 'Installation Type')->first()?->optionDetails ?? collect();
        });

        $salutations = Cache::remember('survey-wizard:salutations', now()->addMinutes(10), function () {
            $salutationOption = MasterOption::with(['optionDetails' => function ($query) {
                $query->where('is_active', true)->orderBy('option_name');
            }])->where('name', 'Salutation')->first();

            return $salutationOption ? $salutationOption->optionDetails : collect();
        });

        $positions = Cache::remember('survey-wizard:positions', now()->addMinutes(10), function () {
            $positionOption = MasterOption::where('name', 'Position')->first();
            return $positionOption ? $positionOption->optionDetails()->where('is_active', true)->pluck('option_name') : collect();
        });

        $provinces = Cache::remember('survey-wizard:provinces', now()->addMinutes(10), function () {
            return Province::orderBy('name')->get(['id', 'name']);
        });

        $addressTypes = Cache::remember('survey-wizard:address-types', now()->addMinutes(10), function () {
            $addressTypeOption = MasterOption::with(['optionDetails' => function ($query) {
                $query->where('is_active', true)->orderBy('option_name');
            }])->where('name', 'Address Type')->first();

            return $addressTypeOption ? $addressTypeOption->optionDetails : collect();
        });

        // "Jenis Customer" field — uses the Customer Type master option.
        $companyOptions = Cache::remember('survey-wizard:company-options', now()->addMinutes(10), function () {
            $companyOption = MasterOption::with(['optionDetails' => function ($query) {
                $query->where('is_active', true)->orderBy('option_name');
            }])->where('name', 'Customer Type')->first();

            return $companyOption ? $companyOption->optionDetails : collect();
        });

        return compact(
            'marketingStaff',
            'customers',
            'companyTypes',
            'roomTypes',
            'floors',
            'intensities',
            'installationTypes',
            'salutations',
            'positions',
            'provinces',
            'addressTypes',
            'companyOptions'
        );
    }

}

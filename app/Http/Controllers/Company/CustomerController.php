<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\BankPayment;
use App\Models\Building;
use App\Models\City;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerCategory;
use App\Models\CustomerContact;
use App\Models\CustomerCreditLimit;
use App\Models\CustomerPaymentTerm;
use App\Models\CustomerType;
use App\Models\District;
use App\Models\Province;
use App\Models\Subdistrict;
use App\Models\User;
use App\Services\Company\CustomerIdentifierUniquenessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    use AccessControlFilterTrait, ColumnFilterTrait;

    public function __construct(private CustomerIdentifierUniquenessService $customerIdentifierUniqueness) {}

    private function forgetSurveyWizardCustomerCaches(): void
    {
        Cache::forget('survey-wizard:customers');
    }

    public function index(Request $request)
    {
        // IMPORTANT: Remove manually handled filters from request BEFORE creating query
        // This prevents AutoFilterable (via global scope) from processing them
        $filters = $request->input('filter', []);
        $originalFilters = $filters;

        // Check and remove manually handled filters early
        $hasUpdatedAtFilter = false;
        $updatedAtFilterValue = null;
        $hasIsActiveFilter = false;
        $isActiveFilterValue = null;
        $hasIsPkpFilter = false;
        $isPkpFilterValue = null;

        // [Global Soft Delete] Default Filter: Active Only
        // If no is_active filter is present in request, enforce is_active = 1
        $isActiveKey = null;
        foreach (['is_active', 'is__active', 'status'] as $key) {
            if (isset($filters[$key])) {
                $isActiveKey = $key;
                break;
            }
        }

        if (! $isActiveKey) {
            // No status filter present -> Default to Active
            $filters['is_active'] = '1';
            $request->merge(['filter' => $filters]); // Merge back to request for consistnecy
        } elseif ($filters[$isActiveKey] === 'all') {
            // "All" selected -> Remove filter to show everything
            unset($filters[$isActiveKey]);
            // Also need to skip auto-filter for this key if it stays in request
            $requestData = $request->all();
            $requestData['filter'] = $filters;
            $request->replace($requestData);
        }

        // Check for updated_at filter (date filter with 3-digit month format)
        foreach (['updated_at', 'updated__at', 'customers.updated_at', 'customers__updated_at'] as $filterKey) {
            if (isset($filters[$filterKey]) && ! empty(trim($filters[$filterKey]))) {
                $updatedAtFilterValue = trim($filters[$filterKey]);
                $hasUpdatedAtFilter = true;
                unset($filters[$filterKey]);
                break;
            }
        }

        // Check for is_active filter (boolean filter for Status)
        foreach (['is_active', 'is__active', 'status'] as $filterKey) {
            if (isset($filters[$filterKey]) && ! empty(trim($filters[$filterKey]))) {
                $isActiveFilterValue = trim($filters[$filterKey]);
                $hasIsActiveFilter = true;
                unset($filters[$filterKey]);
                break;
            }
        }

        // Check for is_pkp filter (boolean filter for PKP)
        foreach (['is_pkp', 'is__pkp', 'pkp'] as $filterKey) {
            if (isset($filters[$filterKey]) && ! empty(trim($filters[$filterKey]))) {
                $isPkpFilterValue = trim($filters[$filterKey]);
                $hasIsPkpFilter = true;
                unset($filters[$filterKey]);
                break;
            }
        }

        // Remove manually handled filters from request BEFORE creating query
        if ($hasUpdatedAtFilter || $hasIsActiveFilter || $hasIsPkpFilter) {
            // Store original request data
            $requestData = $request->all();
            // Update filter array
            $requestData['filter'] = $filters;
            // Replace entire request data to ensure global scope sees the change
            $request->replace($requestData);

            // Also set a flag to prevent AutoFilterable from processing these filters
            $skipFilters = [];
            if ($hasUpdatedAtFilter) {
                $skipFilters['updated_at'] = true;
                $skipFilters['updated__at'] = true;
                $skipFilters['customers.updated_at'] = true;
                $skipFilters['customers__updated_at'] = true;
            }
            if ($hasIsActiveFilter) {
                $skipFilters['is_active'] = true;
                $skipFilters['is__active'] = true;
                $skipFilters['status'] = true;
            }
            if ($hasIsPkpFilter) {
                $skipFilters['is_pkp'] = true;
                $skipFilters['is__pkp'] = true;
                $skipFilters['pkp'] = true;
            }
            $request->merge(['_skip_auto_filter' => $skipFilters]);
        }

        $query = Customer::withoutGlobalScope('autoFilter')
            ->with(['assignedTo', 'customerCategory', 'customerType', 'province', 'city', 'district', 'subdistrict', 'createdBy', 'updatedBy', 'classification']);

        $this->applyAccessControlFilter($query, Auth::user(), 'created_by', 'updated_by', null, null, null);

        // Handle updated_at filter (date filter with 3-digit month format)
        if ($hasUpdatedAtFilter && ! empty($updatedAtFilterValue)) {
            $term = trim($updatedAtFilterValue);

            // Filter by updated_at column directly on customers table
            // Get table name from model
            $tableName = (new Customer)->getTable();

            // Search in multiple date formats to handle various formats including 3-digit month (012, 011)
            $query->where(function ($q) use ($term, $tableName) {
                // Standard date formats
                $q->whereRaw("DATE_FORMAT({$tableName}.updated_at, '%d %M %Y') LIKE ?", ["%{$term}%"])
                    ->orWhereRaw("DATE_FORMAT({$tableName}.updated_at, '%M %Y') LIKE ?", ["%{$term}%"])
                    ->orWhereRaw("DATE_FORMAT({$tableName}.updated_at, '%M') LIKE ?", ["%{$term}%"])
                    ->orWhereRaw("DATE_FORMAT({$tableName}.updated_at, '%Y-%m-%d') LIKE ?", ["%{$term}%"])
                  // Format with 2-digit month: DD/MM/YYYY
                    ->orWhereRaw("DATE_FORMAT({$tableName}.updated_at, '%d/%m/%Y') LIKE ?", ["%{$term}%"])
                  // Format with 3-digit month (leading zero): DD/0MM/YYYY (e.g., 02/012/2025)
                    ->orWhereRaw("CONCAT(DATE_FORMAT({$tableName}.updated_at, '%d/'), LPAD(MONTH({$tableName}.updated_at), 3, '0'), DATE_FORMAT({$tableName}.updated_at, '/%Y')) LIKE ?", ["%{$term}%"])
                  // Format with 3-digit month and time: DD/0MM/YYYY HH:MM (e.g., 02/012/2025 10:07)
                    ->orWhereRaw("CONCAT(DATE_FORMAT({$tableName}.updated_at, '%d/'), LPAD(MONTH({$tableName}.updated_at), 3, '0'), DATE_FORMAT({$tableName}.updated_at, '/%Y %H:%i')) LIKE ?", ["%{$term}%"])
                  // Also handle if user types just the month number (012, 011, etc.) - extract month from term if it's 3 digits
                    ->orWhereRaw("LPAD(MONTH({$tableName}.updated_at), 3, '0') LIKE ?", ["%{$term}%"]);

                // If term is 3 digits (like 012, 011), also try to match as month number
                if (preg_match('/^0?\d{1,3}$/', $term)) {
                    // If term is 3 digits with leading zero (012), extract month (12)
                    if (strlen($term) === 3 && $term[0] === '0') {
                        $monthNum = (int) substr($term, 1); // Extract 12 from 012
                        if ($monthNum >= 1 && $monthNum <= 12) {
                            $q->orWhereRaw("MONTH({$tableName}.updated_at) = ?", [$monthNum]);
                        }
                    } elseif (strlen($term) === 2 || (strlen($term) === 3 && $term[0] !== '0')) {
                        // If term is 2 digits or 3 digits without leading zero, try as month number
                        $monthNum = (int) $term;
                        if ($monthNum >= 1 && $monthNum <= 12) {
                            $q->orWhereRaw("MONTH({$tableName}.updated_at) = ?", [$monthNum]);
                        }
                    }
                }
            });

        }

        // Handle is_active filter (boolean filter for Status)
        if ($hasIsActiveFilter && ! empty($isActiveFilterValue)) {
            $term = strtolower(trim($isActiveFilterValue));

            // Handle various boolean representations
            if (in_array($term, ['yes', 'y', '1', 'true', 'ya', 'active', 'aktif'])) {
                $query->where('is_active', true);
            } elseif (in_array($term, ['no', 'n', '0', 'false', 'tidak', 'inactive', 'tidak aktif'])) {
                $query->where('is_active', false);
            } else {
                // Fallback: try to match status column if term matches status values
                // Check if term matches status values (active/inactive)
                if (in_array($term, ['active', 'inactive'])) {
                    $query->where('status', $term);
                } else {
                    // For other terms, try to match status column
                    $query->where('status', 'LIKE', "%{$term}%");
                }
            }

        }

        // Handle is_pkp filter (boolean filter for PKP)
        if ($hasIsPkpFilter && ! empty($isPkpFilterValue)) {
            $term = strtolower(trim($isPkpFilterValue));

            // Handle various boolean representations
            if (in_array($term, ['yes', 'y', '1', 'true', 'ya', 'pkp'])) {
                $query->where('is_pkp', true);
            } elseif (in_array($term, ['no', 'n', '0', 'false', 'tidak', 'non-pkp', 'non pkp', 'nonpkp'])) {
                $query->where('is_pkp', false);
            } else {
                // For unrecognized terms, default to searching for PKP (true)
                // This handles cases where user types partial matches
                $query->where('is_pkp', true);
            }

        }

        // Apply other column filters (excluding manually handled ones)
        // Keep filtered request (without manually handled filters) so applyColumnFilters won't process them
        // But we need to temporarily restore original filters for applyColumnFilters to work correctly
        // However, applyColumnFilters should only process filters in the column map, which doesn't include our manual filters
        $this->applyColumnFilters($query, 'customersTable', [
            0 => ['column' => 'customer_code'],
            1 => ['column' => 'name'],
            2 => ['relation' => 'customerCategory', 'column' => 'name'],
            3 => ['column' => 'email'],
            4 => ['column' => 'phone'],
            5 => ['relation' => 'assignedTo', 'column' => 'name'],
            6 => ['column' => 'status'],
        ]);

        // Manually apply AutoFilterable for remaining filters (it will skip manually handled ones via _skip_auto_filter flag)
        // Only process if there are remaining filters (excluding manually handled ones)
        if (! empty($filters)) {
            // Use the scopeFilter from AutoFilterable trait
            $query->filter($filters);
        }

        // Restore original filters after processing (for pagination links, etc.)
        if ($hasUpdatedAtFilter || $hasIsActiveFilter || $hasIsPkpFilter) {
            $request->merge(['filter' => $originalFilters]);
        }

        // Filter by name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        // Filter by company type
        if ($request->filled('company_type')) {
            $query->where('company_type', $request->company_type);
        }

        // Filter by category
        if ($request->filled('customer_category_id')) {
            $query->where('customer_category_id', $request->customer_category_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by assigned user
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Filter by province
        if ($request->filled('province_id')) {
            $query->where('province_id', $request->province_id);
        }

        // Filter by city
        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        // Filter by district
        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        // Filter by subdistrict
        if ($request->filled('subdistrict_id')) {
            $query->where('subdistrict_id', $request->subdistrict_id);
        }

        // Filter by search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('tax_code', 'like', "%{$search}%")
                    ->orWhere('nib_number', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortFields = ['name', 'email', 'phone', 'status', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $customers = $query->paginateStd(25);
        $categories = CustomerCategory::active()->orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();

        // Customer Classification options (MOM requirement)
        $classificationMaster = \App\Models\MasterOption::where('name', 'Customer Classification')->first();
        $classificationOptions = $classificationMaster ? \App\Models\OptionDetail::where('master_option_id', $classificationMaster->id)->where('is_active', 1)->orderBy('option_name')->get() : collect();

        // New dropdown data for Customer fields
        $ppnCodes = Customer::getPpnCodes();
        $bankPayments = BankPayment::active()->with('bank')->orderBy('account_name')->get();
        $allContacts = CustomerContact::active()->orderBy('name')->get();

        // Return JSON for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $customers->items(),
                'pagination' => [
                    'total' => $customers->total(),
                    'per_page' => $customers->perPage(),
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'from' => $customers->firstItem(),
                    'to' => $customers->lastItem(),
                ],
            ]);
        }

        return view('company.customers.index', compact('customers', 'categories', 'users', 'provinces', 'ppnCodes', 'bankPayments', 'allContacts', 'classificationOptions'));
    }

    public function create()
    {
        // Manual customer creation is disabled - customers are created automatically from contracts
        return redirect()->route('company.customers.index')
            ->with('info', 'Customers are created automatically through the marketing pipeline: Prospect → Survey → Quotation → Contract');
    }

    public function store(Request $request)
    {
        // Check permission - allow marketing staff to create customers from pipeline
        $user = Auth::user();

        // Check if user has customers.create or customers.view permission
        $hasPermission = $user->hasPermission('customers.create') ||
                        $user->hasPermission('customers.view') ||
                        $user->hasPermission('company.customers.create') ||
                        $user->hasPermission('company.customers.view');

        // Also allow if user is in marketing module (for pipeline functionality)
        if (! $hasPermission) {
            // Check if user has marketing module access
            $hasPermission = $user->canAccessModule('marketing');
        }

        // Or check if user role contains "marketing" (this should allow marketing staff)
        if (! $hasPermission) {
            $hasPermission = $user->hasRoleStartingWith('Marketing') ||
                            $user->hasAnyRole(['Marketing', 'Marketing Staff', 'Marketing Manager', 'marketing', 'marketing staff', 'marketing manager']);
        }

        // Also check canCreateInModule for marketing module
        if (! $hasPermission) {
            $hasPermission = $user->canCreateInModule('marketing');
        }

        // Also check if user has permission through menu access (Akses Menu)
        // Check if user has "Buat" (Create) permission for Customers menu
        if (! $hasPermission) {
            // Try to check menu access permissions in various possible table names
            $menuAccessTables = ['menu_access', 'user_menu_access', 'menu_permissions', 'user_menu_permissions'];
            $menuNames = ['Customers', 'Customer', 'customers', 'customer'];

            foreach ($menuAccessTables as $tableName) {
                if (\Schema::hasTable($tableName)) {
                    foreach ($menuNames as $menuName) {
                        $menuAccess = \DB::table($tableName)
                            ->where('user_id', $user->id)
                            ->where(function ($q) use ($menuName) {
                                $q->where('menu_name', $menuName)
                                    ->orWhere('menu_name', 'LIKE', "%{$menuName}%");
                            })
                            ->where(function ($q) {
                                $q->where('can_create', true)
                                    ->orWhere('create', true)
                                    ->orWhere('can_create', 1)
                                    ->orWhere('create', 1);
                            })
                            ->first();

                        if ($menuAccess) {
                            $hasPermission = true;
                            break 2; // Break both loops
                        }
                    }
                }
            }
        }

        // Also check if user has any marketing-related permission that might allow customer creation
        if (! $hasPermission) {
            $marketingPermissions = [
                'marketing.create',
                'marketing.write',
                'marketing.pipeline.create',
                'marketing.pipeline.write',
                'marketing.*',
            ];

            foreach ($marketingPermissions as $perm) {
                if ($user->hasPermission($perm)) {
                    $hasPermission = true;
                    break;
                }
            }
        }

        if (! $hasPermission) {
            \Log::warning('CustomerController: Permission denied for customer creation', [
                'user_id' => $user->id,
                'user_name' => $user->name,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. You do not have the required permission to access this resource.',
            ], 403);
        }

        // Allow simple customer creation from pipeline modal
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'company_type' => 'nullable|string',
            'customer_category_id' => 'nullable|exists:customer_types,id',
            'status' => 'nullable|in:active,inactive',
            'assigned_to' => 'nullable|exists:customer_contacts,id', // Changed from users to customer_contacts
            // New fields
            'nib' => 'nullable|string|max:50',
            'customer_group' => 'nullable|string|max:50',
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'district_id' => 'nullable|exists:districts,id',
            'subdistrict_id' => 'nullable|exists:subdistricts,id',
            'postal_code' => 'nullable|string|max:10',
            'label_alias' => 'nullable|string|max:255',
            'is_pkp' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'classification_id' => 'nullable|exists:option_details,id',
        ]);

        $this->customerIdentifierUniqueness->validateUniqueNib($request->nib);

        try {
            // Auto-generate customer code if not provided
            $customerCode = $request->customer_code ?: Customer::generateCustomerCode($request->name);

            $customer = Customer::create([
                'customer_code' => $customerCode,
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'company_type' => $request->company_type ?: 'PT', // Default to 'PT' if not provided
                'customer_category_id' => $request->customer_category_id,
                'status' => $request->status ?? ($request->boolean('is_active', true) ? 'active' : 'inactive'),
                'assigned_to' => $request->assigned_to,
                // New fields
                'customer_group' => $request->customer_group,
                'default_bank_payment_id' => $request->default_bank_payment_id,

                'nib' => $request->nib,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'subdistrict_id' => $request->subdistrict_id,
                'postal_code' => $request->postal_code,
                'label_alias' => $request->label_alias,
                'is_pkp' => $request->boolean('is_pkp'),
                'is_active' => $request->boolean('is_active', true),
                'classification_id' => $request->classification_id,
                'customer_type' => 'regular',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            // Sync Multi PIC contacts if provided
            if ($request->has('contact_ids') && is_array($request->contact_ids)) {
                $syncData = [];
                foreach ($request->contact_ids as $index => $contactId) {
                    $syncData[$contactId] = ['is_primary' => $index === 0]; // First one is primary
                }
                $customer->contacts()->sync($syncData);

                // Contacts created via the inline "Add Contact" modal while creating
                // this company have no customer_id yet (it wasn't known until now).
                // Auto-link them here so they behave like the direct PIC-add flow,
                // which always sets customer_id immediately. Contacts that already
                // belong to another customer (genuine multi-customer PICs) are left
                // untouched.
                CustomerContact::whereIn('id', array_keys($syncData))
                    ->whereNull('customer_id')
                    ->update(['customer_id' => $customer->id]);
            }

            // Link the assigned contact to this customer if one was selected
            if ($request->assigned_to) {
                $contact = CustomerContact::find($request->assigned_to);
                if ($contact) {
                    $contact->update(['customer_id' => $customer->id]);
                }
            }

            $this->forgetSurveyWizardCustomerCaches();

            return response()->json([
                'status' => 'success',
                'message' => 'Customer created successfully',
                'data' => $customer,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create customer: '.$e->getMessage(),
            ], 500);
        }

        /* DISABLED - Full manual customer creation flow
        // Manual customer creation is disabled - customers are created automatically from contracts
        return response()->json([
            'status' => 'error',
            'message' => 'Customers are created automatically through the marketing pipeline: Prospect → Survey → Quotation → Contract'
        ], 403);

        /* DISABLED - Manual customer creation
        $request->validate([
            'company_type' => 'required|string|in:pt,cv,ud,firma,persero,yayasan,koperasi,perorangan',
            'customer_category_id' => 'nullable|exists:customer_categories,id',
            'customer_code' => 'nullable|string|max:50|unique:customers',
            'name' => 'required|string|max:255',
            'label_alias' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'tax_code' => 'nullable|string|max:50',
            'nib_number' => 'nullable|string|max:50',
            'is_pkp' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'grace_period_days' => 'nullable|integer|min:0',
            'default_payment' => 'nullable|string|max:50',
            'member_since' => 'nullable|date',
            'balance' => 'nullable|numeric|min:0',
            'email' => 'required|email|max:255|unique:customers',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'customer_type' => 'nullable|string|max:50',
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'district_id' => 'nullable|exists:districts,id',
            'subdistrict_id' => 'nullable|exists:subdistricts,id',
            'website' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:100',
            'employee_count' => 'nullable|integer|min:0',
            'annual_revenue' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:customer_contacts,id' // Changed from users to customer_contacts
        ]);

        $this->customerIdentifierUniqueness->validateUniqueNib($request->nib, $customer->id);
        $this->customerIdentifierUniqueness->validateUniqueNib($request->nib_number, $customer->id, 'nib_number');

        try {
            DB::beginTransaction();

            // Generate customer code if not provided
            $customerCode = $request->customer_code;
            if (!$customerCode) {
                $customerCode = Customer::generateCustomerCode($request->name);
            }

            $customer = Customer::create([
                'customer_category_id' => $request->customer_category_id,
                'customer_code' => $customerCode,
                'name' => $request->name,
                'label_alias' => $request->label_alias,
                'status' => $request->status,
                'company_type' => $request->company_type,
                'tax_code' => $request->tax_code,
                'nib_number' => $request->nib_number,
                'is_pkp' => $request->boolean('is_pkp'),
                'is_active' => $request->boolean('is_active'),
                'grace_period_days' => $request->grace_period_days,
                'default_payment' => $request->default_payment,
                'member_since' => $request->member_since,
                'balance' => $request->balance ?? 0,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'customer_type' => $request->customer_type ?? 'regular', // Default to 'regular' if not provided
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'subdistrict_id' => $request->subdistrict_id,
                'website' => $request->website,
                'industry' => $request->industry,
                'employee_count' => $request->employee_count,
                'annual_revenue' => $request->annual_revenue,
                'description' => $request->description,
                'notes' => $request->notes,
                'assigned_to' => $request->assigned_to,
                'created_by' => Auth::id()
            ]);

            // Create default credit limit if specified
            if ($request->credit_limit > 0) {
                $customer->creditLimits()->create([
                    'credit_limit' => $request->credit_limit,
                    'is_active' => true,
                    'created_by' => Auth::id()
                ]);
            }

            // Create default payment terms if specified
            if ($request->payment_terms > 0) {
                $customer->paymentTerms()->create([
                    'payment_terms' => $request->payment_terms,
                    'is_active' => true,
                    'created_by' => Auth::id()
                ]);
            }

            DB::commit();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer created successfully.',
                    'data' => $customer->load(['customerCategory', 'assignedTo', 'province', 'city', 'district', 'subdistrict', 'createdBy'])
                ]);
            }

            return redirect()->route('company.customers.show', $customer)
                ->with('success', 'Pelanggan berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create customer: ' . $e->getMessage()
                ], 422);
            }

            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
        */
    }

    public function show(Customer $customer)
    {
        $customer->load([
            'assignedTo',
            'customerCategory',
            'customerType',
            'customerContacts', // Load customer contacts (pegawai/staff)
            'province',
            'subdistrict',
            'createdBy',
            'updatedBy',
            'creditLimits',
            'paymentTerms',
            'contracts',
            'contacts', // Multi PIC
            'defaultBankPayment.bank',
            'buildingCustomers.province', // Load building with province
            'buildingCustomers.city', // Load building with city
            'buildingCustomers.district', // Load building with district
            'buildingCustomers.subdistrict', // Load building with subdistrict
            'customerTaxSettings', // Load tax settings
            'classification', // Added classification
        ]);

        // Get city from district relationship (customers table doesn't have city_id)
        if ($customer->district_id && $customer->district) {
            $customer->city = \App\Models\City::find($customer->district->city_id);
        }

        // Return JSON for AJAX requests
        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $customer,
            ]);
        }

        // Data for edit modal
        $categories = CustomerType::active()->orderBy('name')->get();
        // Option 14 is Company Types (PT, CV, etc)
        $companyTypeOptions = \App\Models\OptionDetail::where('master_option_id', 14)
            ->where('is_active', true)
            ->orderBy('option_name')
            ->get();

        $ppnCodes = Customer::getPpnCodes();
        $bankPayments = BankPayment::active()->with('bank')->orderBy('account_name')->get();
        $allContacts = CustomerContact::active()->orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();

        // Initial location data for edit modal
        $cities = collect();
        $districts = collect();
        $subdistricts = collect();

        if ($customer->province_id) {
            $cities = City::where('province_id', $customer->province_id)->orderBy('name')->get();
        }

        $currentCityId = $customer->city_id;
        if (! $currentCityId && $customer->district_id) {
            $district = District::find($customer->district_id);
            if ($district) {
                $currentCityId = $district->city_id;
            }
        }

        if ($currentCityId) {
            $districts = District::where('city_id', $currentCityId)->orderBy('name')->get();
            // If we didn't have cities (e.g. province_id was null but city exists), load them
            if ($cities->isEmpty() && $customer->district && $customer->district->city && $customer->district->city->province_id) {
                $cities = City::where('province_id', $customer->district->city->province_id)->orderBy('name')->get();
            }
        }

        if ($customer->district_id) {
            $subdistricts = Subdistrict::where('district_id', $customer->district_id)->orderBy('name')->get();
        }

        // Customer Classification options (MOM requirement)
        $classificationMaster = \App\Models\MasterOption::where('name', 'Customer Classification')->first();
        $classificationOptions = $classificationMaster ? \App\Models\OptionDetail::where('master_option_id', $classificationMaster->id)->where('is_active', 1)->orderBy('option_name')->get() : collect();

        return view('company.customers.show', compact('customer', 'categories', 'ppnCodes', 'bankPayments', 'allContacts', 'provinces', 'cities', 'districts', 'subdistricts', 'companyTypeOptions', 'classificationOptions'));
    }

    public function edit(Customer $customer)
    {
        $customer->load(['assignedTo', 'customerType', 'province', 'district', 'subdistrict', 'createdBy', 'updatedBy', 'contacts', 'defaultBankPayment', 'classification']);

        // Get city_id from district if district exists
        $cityId = null;
        if ($customer->district_id) {
            $district = District::find($customer->district_id);
            if ($district) {
                $cityId = $district->city_id;
                // Load city relationship for customer
                $customer->city_from_district = \App\Models\City::find($cityId);
            }
        }

        $users = User::orderBy('name')->get();
        $provinces = Province::orderBy('name')->get();

        // Load cities for the province
        $cities = City::where('province_id', $customer->province_id)->orderBy('name')->get();

        // Load districts for the city (from district relationship)
        $districts = $cityId ? District::where('city_id', $cityId)->orderBy('name')->get() : collect();

        // Load subdistricts for the district
        $subdistricts = Subdistrict::where('district_id', $customer->district_id)->orderBy('name')->get();

        // Option 14 is Company Types (PT, CV, etc)
        $companyTypeOptions = \App\Models\OptionDetail::where('master_option_id', 14)
            ->where('is_active', true)
            ->orderBy('option_name')
            ->get();

        // Customer Classification options (MOM requirement)
        $classificationMaster = \App\Models\MasterOption::where('name', 'Customer Classification')->first();
        $classificationOptions = $classificationMaster ? \App\Models\OptionDetail::where('master_option_id', $classificationMaster->id)->where('is_active', 1)->orderBy('option_name')->get() : collect();

        // Return JSON for AJAX requests
        if (request()->ajax()) {
            // Add city_id to customer data for frontend
            $customerData = $customer->toArray();
            $customerData['city_id'] = $cityId; // Add computed city_id from district

            return response()->json([
                'status' => 'success',
                'data' => $customerData,
                'users' => $users,
                'provinces' => $provinces,
                'cities' => $cities,
                'districts' => $districts,
                'subdistricts' => $subdistricts,
                'companyTypeOptions' => $companyTypeOptions,
                'classificationOptions' => $classificationOptions,
            ]);
        }

        return view('company.customers.edit', compact('customer', 'users', 'provinces', 'cities', 'districts', 'subdistricts', 'companyTypeOptions', 'classificationOptions'));
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'company_type' => 'nullable|string|max:100', // From /system/customer-types (hotel, restaurant, mall, etc)
            'customer_category_id' => 'nullable|exists:customer_types,id',
            'customer_code' => 'nullable|string|max:50|unique:customers,customer_code,'.$customer->id,
            'name' => 'required|string|max:255',
            'label_alias' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'tax_code' => 'nullable|string|max:50',
            // New fields
            'customer_group' => 'nullable|string|max:50',
            'default_bank_payment_id' => 'nullable|exists:bank_payments,id',
            'contact_ids' => 'nullable|array', // Multi PIC
            'contact_ids.*' => 'exists:customer_contacts,id',
            // Legacy tax fields removed

            'nib' => 'nullable|string|max:50',
            'nib_number' => 'nullable|string|max:50',
            'is_pkp' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'grace_period_days' => 'nullable|integer|min:0',
            'default_payment' => 'nullable|string|max:50',
            'member_since' => 'nullable|date',
            'balance' => 'nullable|numeric|min:0',
            'email' => 'nullable|email|max:255|unique:customers,email,'.$customer->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'customer_type' => 'nullable|string|max:50',
            'province_id' => 'nullable|exists:provinces,id',
            'city_id' => 'nullable|exists:cities,id',
            'district_id' => 'nullable|exists:districts,id',
            'subdistrict_id' => 'nullable|exists:subdistricts,id',
            'website' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:100',
            'employee_count' => 'nullable|integer|min:0',
            'annual_revenue' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:customer_contacts,id', // Changed from users to customer_contacts
        ]);

        $this->customerIdentifierUniqueness->validateUniqueNib($request->nib, $customer->id);
        $this->customerIdentifierUniqueness->validateUniqueNib($request->nib_number, $customer->id, 'nib_number');

        try {
            DB::beginTransaction();

            $customer->update([
                'customer_category_id' => $request->customer_category_id,
                'customer_code' => $request->customer_code,
                'name' => $request->name,
                'label_alias' => $request->label_alias,
                'status' => $request->status ?? ($request->boolean('is_active') ? 'active' : 'inactive'),
                'company_type' => $request->company_type,
                'tax_code' => $request->tax_code,
                // New fields
                'customer_group' => $request->customer_group,
                'default_bank_payment_id' => $request->default_bank_payment_id,

                'nib' => $request->nib,
                'nib_number' => $request->nib_number,
                'is_pkp' => $request->boolean('is_pkp'),
                'is_active' => $request->boolean('is_active'),
                'grace_period_days' => $request->grace_period_days ?? 0, // Default to 0 if not provided
                'default_payment' => $request->default_payment ?? 'cash', // Default to cash if not provided
                'member_since' => $request->member_since,
                'balance' => $request->balance ?? 0,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'postal_code' => $request->postal_code,
                'customer_type' => $request->customer_type ?? 'regular', // Default to 'regular' if not provided
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'subdistrict_id' => $request->subdistrict_id,
                'website' => $request->website,
                'industry' => $request->industry,
                'employee_count' => $request->employee_count,
                'annual_revenue' => $request->annual_revenue,
                'description' => $request->description,
                'notes' => $request->notes,
                'assigned_to' => $request->assigned_to,
                'classification_id' => $request->classification_id,
                'updated_by' => Auth::id(),
            ]);

            // Sync Multi PIC contacts if provided
            if ($request->has('contact_ids')) {
                $contactIds = $request->contact_ids ?? [];
                $syncData = [];
                foreach ($contactIds as $index => $contactId) {
                    $syncData[$contactId] = ['is_primary' => $index === 0]; // First one is primary
                }
                $customer->contacts()->sync($syncData);
            }

            DB::commit();

            $this->forgetSurveyWizardCustomerCaches();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer updated successfully.',
                    'data' => $customer->load(['customerCategory', 'assignedTo', 'province', 'city', 'district', 'subdistrict', 'createdBy', 'updatedBy']),
                ]);
            }

            return redirect()->route('company.customers.show', $customer)
                ->with('success', 'Pelanggan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();

            // Return JSON for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update customer: '.$e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function destroy(Customer $customer)
    {
        try {
            // [Global Soft Delete] Deactivate instead of Delete
            // Check active contracts?
            $activeContracts = $customer->contracts()->where('contract_status', 'active')->exists();

            if ($activeContracts) {
                $errorMessage = 'Cannot deactivate customer with active contracts.';
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $errorMessage,
                        'errors' => [$errorMessage],
                    ], 422);
                }

                return back()->with('error', $errorMessage);
            }

            // Perform Deactivation
            $customer->update(['is_active' => false]);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Customer successfully deactivated.',
                ]);
            }

            return back()->with('success', 'Customer successfully deactivated.');
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error: '.$e->getMessage(),
                    'errors' => ['Error: '.$e->getMessage()],
                ], 500);
            }

            return back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    // Credit Limit Management
    public function creditLimits(Customer $customer)
    {
        $creditLimits = $customer->creditLimits()
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        return view('company.customers.credit-limits', compact('customer', 'creditLimits'));
    }

    public function storeCreditLimit(Request $request, Customer $customer)
    {
        $request->validate([
            'credit_limit' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            $customer->creditLimits()->create([
                'credit_limit' => $request->credit_limit,
                'is_active' => $request->boolean('is_active'),
                'created_by' => Auth::id(),
            ]);

            // Update customer's current credit limit
            $customer->update(['credit_limit' => $request->credit_limit]);

            return back()->with('success', 'Batas kredit berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function updateCreditLimit(Request $request, Customer $customer, CustomerCreditLimit $creditLimit)
    {
        $request->validate([
            'credit_limit' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            if ($creditLimit->customer_id !== $customer->id) {
                throw new \Exception('Batas kredit tidak ditemukan.');
            }

            $creditLimit->update([
                'credit_limit' => $request->credit_limit,
                'is_active' => $request->boolean('is_active'),
                'updated_by' => Auth::id(),
            ]);

            // Update customer's current credit limit if this is the active one
            if ($creditLimit->is_active) {
                $customer->update(['credit_limit' => $request->credit_limit]);
            }

            return back()->with('success', 'Batas kredit berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function deleteCreditLimit(Customer $customer, CustomerCreditLimit $creditLimit)
    {
        try {
            if ($creditLimit->customer_id !== $customer->id) {
                throw new \Exception('Batas kredit tidak ditemukan.');
            }

            $creditLimit->delete();

            return back()->with('success', 'Batas kredit berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    // Payment Terms Management
    public function paymentTerms(Customer $customer)
    {
        $paymentTerms = $customer->paymentTerms()
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        return view('company.customers.payment-terms', compact('customer', 'paymentTerms'));
    }

    public function storePaymentTerm(Request $request, Customer $customer)
    {
        $request->validate([
            'payment_terms' => 'required|integer|min:0|max:365',
            'is_active' => 'boolean',
        ]);

        try {
            $customer->paymentTerms()->create([
                'payment_terms' => $request->payment_terms,
                'is_active' => $request->boolean('is_active'),
                'created_by' => Auth::id(),
            ]);

            // Update customer's current payment terms
            $customer->update(['payment_terms' => $request->payment_terms]);

            return back()->with('success', 'Syarat pembayaran berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function updatePaymentTerm(Request $request, Customer $customer, CustomerPaymentTerm $paymentTerm)
    {
        $request->validate([
            'payment_terms' => 'required|integer|min:0|max:365',
            'is_active' => 'boolean',
        ]);

        try {
            if ($paymentTerm->customer_id !== $customer->id) {
                throw new \Exception('Syarat pembayaran tidak ditemukan.');
            }

            $paymentTerm->update([
                'payment_terms' => $request->payment_terms,
                'is_active' => $request->boolean('is_active'),
                'updated_by' => Auth::id(),
            ]);

            // Update customer's current payment terms if this is the active one
            if ($paymentTerm->is_active) {
                $customer->update(['payment_terms' => $request->payment_terms]);
            }

            return back()->with('success', 'Syarat pembayaran berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function deletePaymentTerm(Customer $customer, CustomerPaymentTerm $paymentTerm)
    {
        try {
            if ($paymentTerm->customer_id !== $customer->id) {
                throw new \Exception('Syarat pembayaran tidak ditemukan.');
            }

            $paymentTerm->delete();

            return back()->with('success', 'Syarat pembayaran berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    // API Methods
    public function getCustomers(Request $request)
    {
        $customers = Customer::where('status', 'active')
            ->with(['customerCategory'])
            ->orderBy('name')
            ->get();

        return response()->json($customers);
    }

    public function getCustomersByCompanyType(Request $request)
    {
        $request->validate([
            'company_type' => 'required|string|in:pt,cv,ud,firma,persero,yayasan,koperasi,perorangan',
        ]);

        $customers = Customer::where('company_type', $request->company_type)
            ->where('status', 'active')
            ->with(['customerCategory'])
            ->orderBy('name')
            ->get();

        return response()->json($customers);
    }

    public function searchCustomers(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:2',
        ]);

        $customers = Customer::where('name', 'like', '%'.$request->search.'%')
            ->orWhere('email', 'like', '%'.$request->search.'%')
            ->orWhere('phone', 'like', '%'.$request->search.'%')
            ->where('status', 'active')
            ->with(['customerCategory'])
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json($customers);
    }

    // Bulk Operations
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'exists:customers,id',
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            $errors = [];

            foreach ($request->customer_ids as $customerId) {
                $customer = Customer::find($customerId);

                if ($customer) {
                    // [Global Soft Delete] Deactivate instead of Delete
                    // Optional: Check active contracts before deactivating?
                    // For bulk, let's allow deactivation but maybe log if they have active contracts?
                    // Or keep it strict:
                    // Validate before deactivation
                    $hasActiveContracts = $customer->contracts()->where('contract_status', 'active')->exists();
                    if ($hasActiveContracts) {
                        $errors[] = "Customer '{$customer->name}' has active contracts.";

                        continue;
                    }

                    $customer->update(['is_active' => false]);
                    $deletedCount++;
                }
            }

            DB::commit();

            $message = "Berhasil menghapus {$deletedCount} pelanggan.";
            $success = true;
            $statusCode = 200;

            if ($deletedCount === 0 && ! empty($errors)) {
                $message = 'Gagal menghapus pelanggan.';
                $success = false;
                $statusCode = 422;
            } elseif (! empty($errors)) {
                $message = "Berhasil menghapus {$deletedCount} pelanggan. Beberapa gagal.";
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => $success ? 'success' : 'error',
                    'success' => $success,
                    'message' => $message,
                    'count' => $deletedCount,
                    'errors' => $errors,
                ], $statusCode);
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollback();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: '.$e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'exists:customers,id',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            DB::beginTransaction();

            $updatedCount = Customer::whereIn('id', $request->customer_ids)
                ->update(['status' => $request->status]);

            DB::commit();

            return back()->with('success', "Berhasil memperbarui status {$updatedCount} pelanggan.");
        } catch (\Exception $e) {
            DB::rollback();

            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function toggleStatus(Customer $customer)
    {
        try {
            $newStatus = $customer->status === 'active' ? 'inactive' : 'active';

            $customer->update(['status' => $newStatus]);
            $this->forgetSurveyWizardCustomerCaches();

            return back()->with('success', "Status pelanggan berhasil diubah menjadi {$newStatus}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function getStatistics()
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $customersWithCreditLimit = Customer::where('credit_limit', '>', 0)->count();
        $customersByCompanyType = Customer::selectRaw('company_type, COUNT(*) as count')
            ->groupBy('company_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'customers_with_credit_limit' => $customersWithCreditLimit,
            'customers_by_company_type' => $customersByCompanyType,
        ]);
    }

    public function getBuildings($customerId)
    {
        try {
            $cacheKey = "customer:{$customerId}:api-buildings:v1";
            $formattedBuildings = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($customerId) {
                return DB::table('building_customers')
                    ->join('buildings', 'buildings.id', '=', 'building_customers.building_id')
                    ->leftJoin('provinces', 'provinces.id', '=', 'buildings.province_id')
                    ->leftJoin('cities', 'cities.id', '=', 'buildings.city_id')
                    ->leftJoin('districts', 'districts.id', '=', 'buildings.district_id')
                    ->leftJoin('subdistricts', 'subdistricts.id', '=', 'buildings.subdistrict_id')
                    ->where('building_customers.customer_id', $customerId)
                    ->where('building_customers.is_active', true)
                    ->where('buildings.status_update', true)
                    ->whereNull('buildings.deleted_at')
                    ->orderByRaw("COALESCE(NULLIF(buildings.nama_gedung, ''), buildings.name)")
                    ->select([
                        'buildings.id',
                        DB::raw("COALESCE(NULLIF(buildings.nama_gedung, ''), buildings.name) as nama_gedung"),
                        DB::raw("COALESCE(NULLIF(buildings.name, ''), buildings.nama_gedung) as name"),
                        DB::raw("COALESCE(NULLIF(buildings.alamat_1, ''), buildings.address) as alamat_1"),
                        DB::raw("COALESCE(NULLIF(buildings.address, ''), buildings.alamat_1) as address"),
                        'buildings.alamat_2',
                        'provinces.name as province',
                        'cities.name as city',
                        'districts.name as district',
                        'subdistricts.name as subdistrict',
                        DB::raw("COALESCE(NULLIF(buildings.kode_pos, ''), buildings.postal_code) as kode_pos"),
                        DB::raw("COALESCE(NULLIF(buildings.postal_code, ''), buildings.kode_pos) as postal_code"),
                        'buildings.phone_1',
                        'buildings.phone_2',
                        'buildings.email',
                        'buildings.total_floors',
                        'buildings.total_area',
                    ])
                    ->get()
                    ->map(fn ($building) => [
                        'id' => $building->id,
                        'nama_gedung' => $building->nama_gedung,
                        'name' => $building->name,
                        'alamat_1' => $building->alamat_1,
                        'address' => $building->address,
                        'alamat_2' => $building->alamat_2,
                        'province' => $building->province,
                        'city' => $building->city,
                        'district' => $building->district,
                        'subdistrict' => $building->subdistrict,
                        'kode_pos' => $building->kode_pos,
                        'postal_code' => $building->postal_code,
                        'phone_1' => $building->phone_1,
                        'phone_2' => $building->phone_2,
                        'email' => $building->email,
                        'total_floors' => $building->total_floors,
                        'total_area' => $building->total_area,
                    ])
                    ->values()
                    ->all();
            });

            return response()
                ->json($formattedBuildings)
                ->header('Cache-Control', 'private, max-age=300');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch buildings',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get customer contacts by customer ID (API)
     */
    public function getContacts($customerId)
    {
        try {
            $cacheKey = "customer:{$customerId}:api-contacts:v3";
            $contacts = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($customerId) {
                $legacyContacts = CustomerContact::where('customer_id', $customerId)
                    ->where('is_active', true)
                    ->get(['id', 'name', 'salutation', 'position', 'email', 'phone', 'is_active']);

                $multiPicContacts = CustomerContact::whereHas('customers', function ($query) use ($customerId) {
                    $query->where('customers.id', $customerId);
                })
                    ->where('is_active', true)
                    ->get(['id', 'name', 'salutation', 'position', 'email', 'phone', 'is_active']);

                return $legacyContacts
                    ->merge($multiPicContacts)
                    ->unique('id')
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->toArray();
            });

            return response()
                ->json($contacts)
                ->header('Cache-Control', 'private, max-age=300');
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch customer contacts',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

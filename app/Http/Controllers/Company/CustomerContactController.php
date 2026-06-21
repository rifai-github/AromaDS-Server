<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\CustomerContact;
use App\Models\Customer;
use App\Models\User;
use App\Mail\EmailVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class CustomerContactController extends Controller
{
    use ColumnFilterTrait, AccessControlFilterTrait;

    protected function forgetSurveyWizardContactCaches(?int ...$customerIds): void
    {
        foreach (array_unique(array_filter($customerIds)) as $customerId) {
            Cache::forget("survey-wizard:contacts:{$customerId}");
            Cache::forget("survey-wizard:contacts:v2:{$customerId}");
        }
    }

    /**
     * Display a listing of customer contacts
     */
    public function index()
    {
        $query = CustomerContact::with(['customer', 'customers', 'createdBy', 'updatedBy']);

        $this->applyCustomerContactAccessFilter($query);

        // Handle custom status filter - manual implementation needed because 
        // AutoFilterable treats it as text search on boolean column
        if (request()->has('filter.is_active')) {
            $statusTerm = strtolower(request()->input('filter.is_active'));
            $filters = request()->input('filter');
            
            if (str_contains($statusTerm, 'non')) {
                $query->where('is_active', 0);
            } elseif (str_contains($statusTerm, 'act')) {
                $query->where('is_active', 1);
            }
            
            // Remove from filters so it doesn't get processed again by traits with wrong logic
            unset($filters['is_active']);
            request()->merge(['filter' => $filters]);
        }

        // Apply column filters via global filter row (table id: customerContactsTable)
        $this->applyColumnFilters($query, 'customerContactsTable', [
            // 0 => No (skip)
            1 => ['relation' => 'customer', 'column' => 'name'],
            2 => ['column' => 'name'],
            3 => ['column' => 'position'],
            4 => ['column' => 'email'],
            5 => ['column' => 'phone'],
            6 => ['column' => 'is_active', 'boolean' => true],
            7 => ['relation' => 'createdBy', 'column' => 'name'],
            // 8 => Actions (skip)
        ]);

        $customerContacts = $query->orderBy('created_at', 'desc')->paginateStd(25);

        $customers = $this->accessibleCustomerLookupQuery()
            ->orderBy('name')
            ->get(['id', 'name']);
        
        // Get salutation options from master data
        $salutationOption = \App\Models\MasterOption::where('name', 'Salutation')->first();
        $salutations = $salutationOption ? $salutationOption->optionDetails()->where('is_active', true)->pluck('option_name') : collect();
            
        // Get position options from master data
        $positionOption = \App\Models\MasterOption::where('name', 'Position')->first();
        $positions = $positionOption ? $positionOption->optionDetails()->where('is_active', true)->pluck('option_name') : collect();

        return view('company.customer-contacts.index', compact('customerContacts', 'customers', 'salutations', 'positions'));
    }

    /**
     * Show the form for creating a new customer contact
     */
    public function create()
    {
        $customers = $this->accessibleCustomerLookupQuery()
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::where('is_active', true)->orderBy('name');
        $this->applyAccessControlFilter($users, Auth::user(), 'id', null, 'branch_id', null, null);
        $users = $users->get(['id', 'name']);
        
        return view('company.customer-contacts.create', compact('customers', 'users'));
    }

    /**
     * Store a newly created customer contact
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'exists:customers,id',
            'salutation' => 'nullable|string|max:20',
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $accessError = $this->validateRequestedCustomerAccess($request);
        if ($accessError) {
            if ($request->ajax()) {
                return response()->json($accessError, 403);
            }

            return redirect()->back()->withErrors(['customer_id' => $accessError['message']])->withInput();
        }

        $customerContact = CustomerContact::create([
            'customer_id' => $request->customer_id ?: ($request->customer_ids[0] ?? null),
            'salutation' => $request->salutation,
            'name' => $request->name,
            'position' => $request->position,
            'email' => $request->email,
            'phone' => $request->phone,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id()
        ]);

        // Sync many-to-many relationship
        if ($request->has('customer_ids')) {
            $syncData = [];
            foreach ($request->customer_ids as $index => $id) {
                $syncData[$id] = ['is_primary' => $index === 0];
            }
            $customerContact->customers()->sync($syncData);
        } elseif ($request->customer_id) {
            $customerContact->customers()->sync([$request->customer_id => ['is_primary' => true]]);
        }

        $this->forgetCustomerContactLookupCache($customerContact->customer_id);
        $this->forgetSurveyWizardContactCaches($customerContact->customer_id);

        if ($request->ajax()) {
            try {
                $data = [
                    'id' => $customerContact->id,
                    'customer_id' => $customerContact->customer_id,
                    'salutation' => $customerContact->salutation ?? '',
                    'name' => $customerContact->name ?? '',
                    'position' => $customerContact->position ?? '',
                    'email' => $customerContact->email ?? '',
                    'phone' => $customerContact->phone ?? '',
                    'is_active' => $customerContact->is_active ?? true,
                ];
                
                // Clean any invalid UTF-8 characters
                array_walk_recursive($data, function(&$value) {
                    if (is_string($value)) {
                        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }
                });
                
                return response()->json([
                    'success' => true,
                    'message' => 'Customer contact created successfully.',
                    'data' => $data
                ]);
            } catch (\Exception $e) {
                \Log::error('CustomerContactController@store - JSON encoding error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Customer contact created but failed to return data',
                    'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
                ], 500);
            }
        }

        return redirect()->route('company.customer-contacts.index')
            ->with('success', 'Customer contact created successfully.');
    }

    /**
     * Display the specified customer contact
     */
    public function show(CustomerContact $customerContact)
    {
        $this->authorizeCustomerContactAccess($customerContact);

        $customerContact->load(['customer', 'customers', 'createdBy', 'updatedBy']);
        
        if (request()->ajax()) {
            try {
                // Convert to array to avoid JSON encoding issues
                $data = [
                    'id' => $customerContact->id,
                    'customer_id' => $customerContact->customer_id,
                    'salutation' => $customerContact->salutation ?? '',
                    'name' => $customerContact->name ?? '',
                    'position' => $customerContact->position ?? '',
                    'email' => $customerContact->email ?? '',
                    'phone' => $customerContact->phone ?? '',
                    'notes' => $customerContact->notes ?? '',
                    'is_active' => $customerContact->is_active ?? true,
                    'customer' => $customerContact->customer ? [
                        'id' => $customerContact->customer->id,
                        'name' => $customerContact->customer->name ?? '',
                        'customer_type' => $customerContact->customer->customer_type ?? '',
                    ] : null,
                    'customers' => $customerContact->customers->map(function($cust) {
                        return [
                            'id' => $cust->id,
                            'name' => $cust->name,
                            'pivot' => $cust->pivot
                        ];
                    }),
                    'created_by' => $customerContact->createdBy ? [
                        'id' => $customerContact->createdBy->id,
                        'name' => $customerContact->createdBy->name ?? '',
                    ] : null,
                    'updated_by' => $customerContact->updatedBy ? [
                        'id' => $customerContact->updatedBy->id,
                        'name' => $customerContact->updatedBy->name ?? '',
                    ] : null,
                    'created_at' => $customerContact->created_at ? $customerContact->created_at->toDateTimeString() : null,
                    'updated_at' => $customerContact->updated_at ? $customerContact->updated_at->toDateTimeString() : null,
                ];
                
                // Clean any invalid UTF-8 characters
                array_walk_recursive($data, function(&$value) {
                    if (is_string($value)) {
                        // Remove invalid UTF-8 characters
                        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }
                });
                
                return response()->json($data);
            } catch (\Exception $e) {
                \Log::error('CustomerContactController@show - JSON encoding error', [
                    'customer_contact_id' => $customerContact->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                return response()->json([
                    'error' => 'Failed to load customer contact data',
                    'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
                ], 500);
            }
        }
        
        return view('company.customer-contacts.show', compact('customerContact'));
    }

    /**
     * Show the form for editing the specified customer contact
     */
    public function edit(CustomerContact $customerContact)
    {
        $this->authorizeCustomerContactAccess($customerContact);

        $customerContact->load(['customer']);
        
        if (request()->ajax()) {
            try {
                // Convert to array to avoid JSON encoding issues
                $data = [
                    'id' => $customerContact->id,
                    'customer_id' => $customerContact->customer_id,
                    'salutation' => $customerContact->salutation ?? '',
                    'name' => $customerContact->name ?? '',
                    'position' => $customerContact->position ?? '',
                    'email' => $customerContact->email ?? '',
                    'phone' => $customerContact->phone ?? '',
                    'notes' => $customerContact->notes ?? '',
                    'is_active' => $customerContact->is_active ?? true,
                    'customer' => $customerContact->customer ? [
                        'id' => $customerContact->customer->id,
                        'name' => $customerContact->customer->name ?? '',
                    ] : null,
                ];
                
                // Clean any invalid UTF-8 characters
                array_walk_recursive($data, function(&$value) {
                    if (is_string($value)) {
                        // Remove invalid UTF-8 characters
                        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }
                });
                
                return response()->json($data);
            } catch (\Exception $e) {
                \Log::error('CustomerContactController@edit - JSON encoding error', [
                    'customer_contact_id' => $customerContact->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                return response()->json([
                    'error' => 'Failed to load customer contact data',
                    'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
                ], 500);
            }
        }
        
        $customers = $this->accessibleCustomerLookupQuery()
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::where('is_active', true)->orderBy('name');
        $this->applyAccessControlFilter($users, Auth::user(), 'id', null, 'branch_id', null, null);
        $users = $users->get(['id', 'name']);
        
        return view('company.customer-contacts.edit', compact('customerContact', 'customers', 'users'));
    }

    /**
     * Update the specified customer contact
     */
    public function update(Request $request, CustomerContact $customerContact)
    {
        $this->authorizeCustomerContactAccess($customerContact);

        $originalCustomerId = $customerContact->customer_id;

        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|exists:customers,id',
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'exists:customers,id',
            'salutation' => 'nullable|string|max:20',
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $accessError = $this->validateRequestedCustomerAccess($request);
        if ($accessError) {
            if ($request->ajax()) {
                return response()->json($accessError, 403);
            }

            return redirect()->back()->withErrors(['customer_id' => $accessError['message']])->withInput();
        }

        $customerContact->update([
            'customer_id' => $request->customer_id ?: ($request->customer_ids[0] ?? null),
            'salutation' => $request->salutation,
            'name' => $request->name,
            'position' => $request->position,
            'email' => $request->email,
            'phone' => $request->phone,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => Auth::id()
        ]);

        // Sync many-to-many relationship
        if ($request->has('customer_ids')) {
            $syncData = [];
            foreach ($request->customer_ids as $index => $id) {
                // Keep existing primary if it exists, otherwise set first one as primary
                $isPrimary = $request->primary_customer_id ? ($id == $request->primary_customer_id) : ($index === 0);
                $syncData[$id] = ['is_primary' => $isPrimary];
            }
            $customerContact->customers()->sync($syncData);
        } elseif ($request->customer_id) {
            $customerContact->customers()->sync([$request->customer_id => ['is_primary' => true]]);
        }

        $this->forgetCustomerContactLookupCache($originalCustomerId, $customerContact->customer_id);
        $this->forgetSurveyWizardContactCaches($originalCustomerId, $customerContact->customer_id);

        if ($request->ajax()) {
            try {
                $data = [
                    'id' => $customerContact->id,
                    'customer_id' => $customerContact->customer_id,
                    'salutation' => $customerContact->salutation ?? '',
                    'name' => $customerContact->name ?? '',
                    'position' => $customerContact->position ?? '',
                    'email' => $customerContact->email ?? '',
                    'phone' => $customerContact->phone ?? '',
                    'is_active' => $customerContact->is_active ?? true,
                ];
                
                // Clean any invalid UTF-8 characters
                array_walk_recursive($data, function(&$value) {
                    if (is_string($value)) {
                        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                    }
                });
                
                return response()->json([
                    'success' => true,
                    'message' => 'Customer contact updated successfully.',
                    'data' => $data
                ]);
            } catch (\Exception $e) {
                \Log::error('CustomerContactController@update - JSON encoding error', [
                    'customer_contact_id' => $customerContact->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Customer contact updated but failed to return data',
                    'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
                ], 500);
            }
        }

        return redirect()->route('company.customer-contacts.index')
            ->with('success', 'Customer contact updated successfully.');
    }

    /**
     * Remove the specified customer contact
     */
    public function destroy(CustomerContact $customerContact)
    {
        $this->authorizeCustomerContactAccess($customerContact);

        $this->forgetCustomerContactLookupCache($customerContact->customer_id);
        $this->forgetSurveyWizardContactCaches($customerContact->customer_id);
        $customerContact->delete();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer contact deleted successfully.'
            ]);
        }

        return redirect()->route('company.customer-contacts.index')
            ->with('success', 'Customer contact deleted successfully.');
    }

    /**
     * Get customer contacts by customer ID (API)
     */
    public function getByCustomer(Request $request)
    {
        $customerId = $request->get('customer_id');
        
        if (!$customerId) {
            return response()->json([]);
        }

        $contacts = CustomerContact::where('customer_id', $customerId)
            ->where('is_active', true)
            ->orderBy('name');

        $this->applyCustomerContactAccessFilter($contacts);

        $contacts = $contacts->get(['id', 'name', 'position', 'email', 'phone']);

        return response()->json($contacts);
    }

    /**
     * Get customer contacts by customer ID (path parameter).
     *
     * Used by the Job Advice PIC dropdown. Deliberately not access-control filtered:
     * a customer's PIC contacts belong to the customer, not to whichever staff member
     * happened to create the record, so anyone handling that customer's contract must
     * be able to see them (e.g. a "none"/strict access user would otherwise see an
     * empty PIC list for a customer they legitimately work with).
     */
    public function getByCustomerId($customerId)
    {
        if (!$customerId) {
            return response()->json([]);
        }

        $contacts = CustomerContact::where('customer_id', $customerId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'position', 'email', 'phone']);

        return response()->json($contacts);
    }

    /**
     * Get customer contacts for autocomplete (API)
     */
    public function getCustomerContacts(Request $request)
    {
        $query = $request->get('q', '');
        
        $contacts = CustomerContact::with('customer:id,name')
            ->where('is_active', true)
            ->when($query, function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(50);

        $this->applyCustomerContactAccessFilter($contacts);

        $contacts = $contacts->get(['id', 'name', 'position', 'email', 'phone', 'customer_id']);

        // Return simple format with nested customer object
        // Frontend will handle the display format
        $results = $contacts->map(function($contact) {
            return [
                'id' => $contact->id,
                'name' => $contact->name,
                'position' => $contact->position,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'customer_id' => $contact->customer_id,
                'customer' => $contact->customer ? [
                    'id' => $contact->customer->id,
                    'name' => $contact->customer->name
                ] : null
            ];
        });

        return response()->json($results);
    }

    protected function forgetCustomerContactLookupCache(?int ...$customerIds): void
    {
        foreach (array_unique(array_filter($customerIds)) as $customerId) {
            Cache::forget("customer:{$customerId}:api-contacts:v1");
        }
    }

    protected function accessibleCustomerLookupQuery()
    {
        $query = Customer::active();
        $this->applyAccessControlFilter($query, Auth::user(), 'created_by', 'updated_by', null, null, null);

        return $query;
    }

    protected function applyCustomerContactAccessFilter($query)
    {
        $user = Auth::user();
        $strictOwnOnly = $user?->accessLevels()
            ->where('access_type', 'none')
            ->where('is_active', true)
            ->exists();

        return $this->applyAccessControlFilter(
            $query,
            $user,
            'customer_contacts.created_by',
            'customer_contacts.updated_by',
            null,
            $strictOwnOnly ? null : function ($accessQuery) use ($user) {
                $accessibleUserIds = $this->getAccessibleUserIds($user);

                $accessQuery
                    ->orWhereHas('customer', function ($customerQuery) use ($accessibleUserIds) {
                        $customerQuery
                            ->whereIn('created_by', $accessibleUserIds)
                            ->orWhereIn('updated_by', $accessibleUserIds);
                    })
                    ->orWhereHas('customers', function ($customerQuery) use ($accessibleUserIds) {
                        $customerQuery
                            ->whereIn('created_by', $accessibleUserIds)
                            ->orWhereIn('updated_by', $accessibleUserIds);
                    });
            },
            null
        );
    }

    protected function authorizeCustomerContactAccess(CustomerContact $customerContact): void
    {
        $query = CustomerContact::whereKey($customerContact->getKey());
        $this->applyCustomerContactAccessFilter($query);

        abort_unless($query->exists(), 403, 'Anda tidak memiliki akses ke contact ini.');
    }

    protected function validateRequestedCustomerAccess(Request $request): ?array
    {
        $requestedCustomerIds = collect([$request->input('customer_id')])
            ->merge($request->input('customer_ids', []))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($requestedCustomerIds->isEmpty()) {
            return null;
        }

        $accessibleCount = $this->accessibleCustomerLookupQuery()
            ->whereIn('id', $requestedCustomerIds->all())
            ->count();

        if ($accessibleCount === $requestedCustomerIds->count()) {
            return null;
        }

        return [
            'success' => false,
            'message' => 'Anda tidak memiliki akses ke salah satu customer yang dipilih.',
        ];
    }

    /**
     * Verify email via token (public route - no auth required)
     */
    public function verifyEmail($token)
    {
        $contact = CustomerContact::where('email_verification_token', $token)->first();
        
        if (!$contact) {
            return view('emails.verification-result', [
                'success' => false,
                'message' => 'Link verifikasi tidak valid atau sudah kadaluarsa.',
                'contact' => null
            ]);
        }
        
        // Check if already verified
        if ($contact->isEmailVerified()) {
            return view('emails.verification-result', [
                'success' => true,
                'message' => 'Email sudah terverifikasi sebelumnya.',
                'contact' => $contact
            ]);
        }
        
        // Mark as verified
        $contact->markEmailAsVerified();
        
        \Log::info('📧 Email verified for customer contact', [
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'customer' => $contact->customer?->name
        ]);
        
        return view('emails.verification-result', [
            'success' => true,
            'message' => 'Email berhasil diverifikasi!',
            'contact' => $contact
        ]);
    }
    
    /**
     * Send or resend verification email
     */
    public function sendVerificationEmail(Request $request, CustomerContact $customerContact)
    {
        if (empty($customerContact->email)) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact tidak memiliki email.'
                ], 422);
            }
            return redirect()->back()->with('error', 'Contact tidak memiliki email.');
        }
        
        try {
            // Generate new token
            $customerContact->generateVerificationToken();
            
            // Send email
            Mail::to($customerContact->email)->send(new EmailVerificationMail($customerContact));
            
            \Log::info('📧 Verification email sent', [
                'contact_id' => $customerContact->id,
                'email' => $customerContact->email,
                'customer' => $customerContact->customer?->name
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email verifikasi berhasil dikirim ke ' . $customerContact->email
                ]);
            }
            
            return redirect()->back()->with('success', 'Email verifikasi berhasil dikirim ke ' . $customerContact->email);
            
        } catch (\Exception $e) {
            \Log::error('❌ Failed to send verification email', [
                'contact_id' => $customerContact->id,
                'email' => $customerContact->email,
                'error' => $e->getMessage()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim email: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Gagal mengirim email verifikasi: ' . $e->getMessage());
        }
    }
    
    /**
     * Helper: Send verification email if email is new or changed
     */
    protected function sendVerificationIfNeeded(CustomerContact $contact, ?string $oldEmail = null)
    {
        // Only send if email exists and is different from old email
        if (empty($contact->email)) {
            return;
        }
        
        // If email changed, clear verification and send new
        if ($oldEmail !== $contact->email) {
            try {
                $contact->generateVerificationToken();
                Mail::to($contact->email)->send(new EmailVerificationMail($contact));
                
                \Log::info('📧 Auto-sent verification email on create/update', [
                    'contact_id' => $contact->id,
                    'email' => $contact->email,
                    'old_email' => $oldEmail
                ]);
            } catch (\Exception $e) {
                // Don't fail the main operation, just log
                \Log::warning('⚠️ Failed to auto-send verification email', [
                    'contact_id' => $contact->id,
                    'email' => $contact->email,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}


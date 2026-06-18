<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Traits\AccessControlFilterTrait;
use App\Http\Traits\ColumnFilterTrait;
use App\Models\User;
use App\Models\Department;
use App\Models\Branch;
use App\Models\Bank;
use App\Models\MasterOption;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Helpers\FileUploadHelper;

class UserController extends Controller
{
    use ColumnFilterTrait, AccessControlFilterTrait;

    protected function normalizeBranchSelection(Request $request): array
    {
        $branchIds = collect($request->input('branch_ids', []))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $primaryBranchId = $request->filled('primary_branch_id')
            ? (int) $request->input('primary_branch_id')
            : null;

        if ($primaryBranchId && !$branchIds->contains($primaryBranchId)) {
            $branchIds->push($primaryBranchId);
        }

        if (!$primaryBranchId && $branchIds->isNotEmpty()) {
            $primaryBranchId = $branchIds->first();
        }

        $primaryBranch = $primaryBranchId ? Branch::find($primaryBranchId) : null;

        return [
            'branch_ids' => $branchIds->all(),
            'primary_branch_id' => $primaryBranch?->id,
            'primary_branch_name' => $primaryBranch?->name,
        ];
    }

    public function index(Request $request)
    {
        $query = User::select([
                'id',
                'nik',
                'salutation',
                'name',
                'email',
                'username',
                'branch_id',
                'branch_name',
                'department_id',
                'department_name',
                'position_name',
                'roles',
                'is_active',
                'is_commission_achiever',
                'phone',
                'handphone_1',
                'join_date',
                'created_by',
                'updated_by',
                'created_at',
            ])
            ->with(['branch:id,name', 'assignedBranches:id,name', 'createdBy:id,name', 'updatedBy:id,name', 'roles:id,name']);

        $this->applyAccessControlFilter($query, Auth::user(), 'id', null, 'branch_id', null, null);

        $statusFilter = $request->input('filter.is_active');
        if ($statusFilter === null || $statusFilter === '') {
            $query->where('is_active', true);
        }

        // Apply AutoFilterable filters
        $query->filter($request->all());

        // Apply per-column filters (table id: usersTable)
        $this->applyColumnFilters($query, 'usersTable', [
            'nik' => ['column' => 'nik'],
            'name' => ['column' => 'name'],
            'email' => ['column' => 'email'],
            'branch_id' => ['column' => 'branch_id'],
            'department_name' => ['column' => 'department_name'],
            'position_name' => ['column' => 'position_name'],
            'roles' => ['column' => 'roles'],
            'is_active' => ['boolean' => true, 'column' => 'is_active'],
            'is_commission_achiever' => ['boolean' => true, 'column' => 'is_commission_achiever'],
            'phone' => ['column' => 'phone'],
        ]);

        $users = $query->orderBy('created_at', 'desc')->paginateStd(25);
        $departments = Cache::remember('user:index:departments', 300, function () {
            return Department::select('id', 'name')->orderBy('name')->get();
        });
        $branches = Cache::remember('user:index:branches', 300, function () {
            return Branch::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        });
        $banks = Cache::remember('user:index:banks', 300, function () {
            return Bank::where('is_active', true)->select('id', 'bank_name')->orderBy('bank_name')->get();
        });
        $roles = Cache::remember('user:index:roles', 300, function () {
            return Role::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        });
        $positions = Cache::remember('user:index:positions', 300, function () {
            $positionOption = MasterOption::where('name', 'Position')->first();
            return $positionOption 
                ? $positionOption->optionDetails()->where('is_active', true)->select('id', 'option_name')->get()
                : collect();
        });
        
        // Get salutation options from master data
        $salutations = Cache::remember('user:index:salutations', 300, function () {
            $salutationOption = MasterOption::where('name', 'Salutation')->first();
            return $salutationOption
                ? $salutationOption->optionDetails()->where('is_active', true)->select('id', 'option_name')->get()
                : collect();
        });

        return view('system.users.index', compact('users', 'departments', 'branches', 'banks', 'salutations', 'roles', 'positions'));
    }

    public function create()
    {
        $departments = Department::all();
        $positions = MasterOption::where('name', 'Position')->first();
        $genders = MasterOption::where('name', 'Gender')->first();
        $marital_statuses = MasterOption::where('name', 'Marital Status')->first();
        $religions = MasterOption::where('name', 'Religion')->first();
        $identity_types = MasterOption::where('name', 'Identity Type')->first();
        $blood_types = MasterOption::where('name', 'Blood Type')->first();
        $rhesus_types = MasterOption::where('name', 'Rhesus')->first();
        $employee_statuses = MasterOption::where('name', 'Employee Status')->first();
        $permissions = Permission::all();

        return view('system.users.create', compact(
            'departments', 'positions', 'genders', 'marital_statuses', 
            'religions', 'identity_types', 'blood_types', 'rhesus_types', 
            'employee_statuses', 'permissions'
        ));
    }

    public function store(Request $request)
    {
        $normalizedBranches = $this->normalizeBranchSelection($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'password' => 'required|string|min:8',
            'nik' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('users', 'nik')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    // Check Customers (NPWP, NIK, NITKU)
                    if (\App\Models\Customer::where(function($q) use ($value) {
                        $q->where('npwp', $value)
                          ->orWhere('nik', $value)
                          ->orWhere('nitku', $value);
                    })->whereNull('deleted_at')->exists()) {
                        $fail("Data NIK dengan nomor {$value} sudah ada di data Customer (NPWP/NIK/NITKU).");
                    }
                    // Check Customer Taxes
                    if (\App\Models\CustomerTax::where('tax_number', $value)->whereNull('deleted_at')->exists()) {
                        $fail("Data NIK dengan nomor {$value} sudah ada di data Customer Tax.");
                    }
                },
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'username')->whereNull('deleted_at'),
            ],
            'branch_id' => 'nullable|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
            'primary_branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'handphone_1' => 'nullable|string|max:20',
            'salutation' => 'nullable|string|max:10',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'join_date' => 'nullable|date',
            'is_active' => 'boolean',
            // New fields
            'emergency_contact_number' => 'nullable|numeric',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:255',
            'ktp_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'npwp_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'photo_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'bpjs_number' => [
                'nullable',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('users', 'bpjs_number')->whereNull('deleted_at'),
            ],
            'bpjs_date' => 'nullable|date',
            'npwp_number' => [
                'nullable',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('users', 'npwp_number')->whereNull('deleted_at'),
            ],
            'is_commission_achiever' => 'nullable|boolean'
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar di sistem.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'nik.required' => 'Nomor NIK wajib diisi.',
            'nik.unique' => 'Nomor NIK sudah terdaftar di sistem.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah terdaftar di sistem.',
            'bpjs_number.unique' => 'Nomor BPJS sudah terdaftar di sistem.',
            'npwp_number.unique' => 'Nomor NPWP sudah terdaftar di sistem.',
            'roles.required' => 'Setidaknya satu role harus dipilih.',
        ]);

        $validator->after(function ($validator) use ($normalizedBranches) {
            if (
                $normalizedBranches['primary_branch_id']
                && !in_array($normalizedBranches['primary_branch_id'], $normalizedBranches['branch_ids'], true)
            ) {
                $validator->errors()->add('primary_branch_id', 'Branch utama harus termasuk dalam daftar branch yang dipilih.');
            }
        });

        if ($validator->fails()) {
            \Log::info('User creation validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $branchId = $normalizedBranches['primary_branch_id'];
            $branchName = $normalizedBranches['primary_branch_name'];

            $departmentName = null;
            if ($request->department_id) {
                $department = Department::find($request->department_id);
                $departmentName = $department ? $department->name : null;
            }

            // Handle file uploads
            $ktpFilePath = null;
            $npwpFilePath = null;
            $photoFilePath = null;
            
            if ($request->hasFile('ktp_file')) {
                try {
                    $ktpFile = $request->file('ktp_file');
                    // Alternative method: direct storage
                    $ktpFilePath = $ktpFile->store('users/documents', 'public');
                } catch (\Exception $e) {
                    \Log::error('KTP file upload failed', ['error' => $e->getMessage()]);
                    throw new \Exception('Failed to upload KTP file: ' . $e->getMessage());
                }
            }
            
            if ($request->hasFile('npwp_file')) {
                try {
                    $npwpFile = $request->file('npwp_file');
                    // Alternative method: direct storage
                    $npwpFilePath = $npwpFile->store('users/documents', 'public');
                } catch (\Exception $e) {
                    \Log::error('NPWP file upload failed', ['error' => $e->getMessage()]);
                    throw new \Exception('Failed to upload NPWP file: ' . $e->getMessage());
                }
            }
            
            if ($request->hasFile('photo_file')) {
                try {
                    $photoFile = $request->file('photo_file');
                    // Alternative method: direct storage
                    $photoFilePath = $photoFile->store('users/photos', 'public');
                } catch (\Exception $e) {
                    \Log::error('Photo file upload failed', ['error' => $e->getMessage()]);
                    throw new \Exception('Failed to upload photo file: ' . $e->getMessage());
                }
            }

            // Get first role name for backward compatibility (roles field)
            $firstRole = Role::find($request->roles[0]);
            $rolesString = $firstRole ? $firstRole->name : null;

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'nik' => $request->nik,
                'username' => $request->username,
                'branch_id' => $branchId,
                'branch_name' => $branchName,
                'department_id' => $request->department_id,
                'department_name' => $departmentName,
                'position_name' => $request->position_name,
                'phone' => $request->phone,
                'handphone_1' => $request->handphone_1,
                'salutation' => $request->salutation,
                'roles' => $rolesString, // Keep for backward compatibility
                'join_date' => $request->join_date ? \Carbon\Carbon::parse($request->join_date)->setTimezone('Asia/Jakarta')->format('Y-m-d') : null,
                'is_active' => $request->boolean('is_active', true),
                // New fields
                'emergency_contact_number' => $request->emergency_contact_number,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_account_holder' => $request->bank_account_holder,
                'ktp_file_path' => $ktpFilePath,
                'npwp_file_path' => $npwpFilePath,
                'photo_file_path' => $photoFilePath,
                'bpjs_number' => $request->bpjs_number,
                'bpjs_date' => $request->bpjs_date ? \Carbon\Carbon::parse($request->bpjs_date)->setTimezone('Asia/Jakarta')->format('Y-m-d') : null,
                'npwp_number' => $request->npwp_number,
                'is_commission_achiever' => $request->boolean('is_commission_achiever', false),
                'created_by' => Auth::id()
            ]);

            // Attach multiple roles using relationship
            $user->roles()->sync($request->roles);
            
            // Attach multiple branches if provided
            if (!empty($normalizedBranches['branch_ids'])) {
                $branchData = [];
                foreach ($normalizedBranches['branch_ids'] as $selectedBranchId) {
                    $branchData[$selectedBranchId] = [
                        'is_primary' => $selectedBranchId === $normalizedBranches['primary_branch_id'],
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ];
                }
                $user->assignedBranches()->sync($branchData);
            }
            
            // Reload roles relationship
            $user->load(['roles', 'assignedBranches']);
            
            // Auto-set multi_login for Administrator or Management Manager
            if ($user->requiresMultiLogin() && !$user->multi_login) {
                $user->update(['multi_login' => true]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating user', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(User $user)
    {
        $this->authorizeUserRecordAccess($user);

        $user->load([
            'branch', 'department', 'positionOption', 'genderOption', 
            'maritalStatusOption', 'religionOption', 'identityTypeOption',
            'bloodTypeOption', 'rhesusOption', 'employeeStatusOption',
            'createdBy', 'permissions', 'loginHistories', 'assignedBranches', 'roles'
        ]);
        
        // Branch data is already loaded via relationship
        
        // Always return JSON since we're using modal system
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function edit(User $user)
    {
        try {
            $this->authorizeUserRecordAccess($user);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
            $departments = Department::all();
            $banks = Bank::where('is_active', true)->orderBy('bank_name')->get();
            $positions = MasterOption::where('name', 'Position')->first();
            $genders = MasterOption::where('name', 'Gender')->first();
            $marital_statuses = MasterOption::where('name', 'Marital Status')->first();
            $religions = MasterOption::where('name', 'Religion')->first();
            $identity_types = MasterOption::where('name', 'Identity Type')->first();
            $blood_types = MasterOption::where('name', 'Blood Type')->first();
            $rhesus_types = MasterOption::where('name', 'Rhesus')->first();
            $employee_statuses = MasterOption::where('name', 'Employee Status')->first();
            $permissions = Permission::all();
            $roles = Role::where('is_active', true)->get();
            $user->load(['permissions', 'branch', 'department', 'roles', 'assignedBranches']);

            // Get salutation options from master data
            $salutationOption = MasterOption::where('name', 'Salutation')->first();
            $salutations = $salutationOption ? $salutationOption->optionDetails()->where('is_active', true)->get() : collect();

            // Always return JSON since we're using modal system
            return response()->json([
                'success' => true,
                'data' => $user->load('roles'), // Ensure roles are loaded
                'branches' => $branches,
                'departments' => $departments,
                'banks' => $banks,
                'positions' => $positions,
                'genders' => $genders,
                'marital_statuses' => $marital_statuses,
                'religions' => $religions,
                'identity_types' => $identity_types,
                'blood_types' => $blood_types,
                'rhesus_types' => $rhesus_types,
                'employee_statuses' => $employee_statuses,
                'permissions' => $permissions,
                'salutations' => $salutations,
                'roles' => $roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading user data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeUserRecordAccess($user);

        $normalizedBranches = $this->normalizeBranchSelection($request);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($user->id)->whereNull('deleted_at'),
            ],
            'password' => 'nullable|string|min:8',
            'nik' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('users', 'nik')->ignore($user->id)->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    // Check Customers (NPWP, NIK, NITKU)
                    if (\App\Models\Customer::where(function($q) use ($value) {
                        $q->where('npwp', $value)
                          ->orWhere('nik', $value)
                          ->orWhere('nitku', $value);
                    })->whereNull('deleted_at')->exists()) {
                        $fail("Data NIK dengan nomor {$value} sudah ada di data Customer (NPWP/NIK/NITKU).");
                    }
                    // Check Customer Taxes
                    if (\App\Models\CustomerTax::where('tax_number', $value)->whereNull('deleted_at')->exists()) {
                        $fail("Data NIK dengan nomor {$value} sudah ada di data Customer Tax.");
                    }
                },
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'username')->ignore($user->id)->whereNull('deleted_at'),
            ],
            'branch_id' => 'nullable|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
            'primary_branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'position_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'handphone_1' => 'nullable|string|max:20',
            'salutation' => 'nullable|string|max:10',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'join_date' => 'nullable|date',
            'is_active' => 'boolean',
            // New fields
            'emergency_contact_number' => 'nullable|numeric',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:255',
            'ktp_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'npwp_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'photo_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'bpjs_number' => [
                'nullable',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('users', 'bpjs_number')->ignore($user->id)->whereNull('deleted_at'),
            ],
            'bpjs_date' => 'nullable|date',
            'npwp_number' => [
                'nullable',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('users', 'npwp_number')->ignore($user->id)->whereNull('deleted_at'),
            ],
            'is_commission_achiever' => 'nullable|boolean'
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar di sistem.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'nik.required' => 'Nomor NIK wajib diisi.',
            'nik.unique' => 'Nomor NIK sudah terdaftar di sistem.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah terdaftar di sistem.',
            'bpjs_number.unique' => 'Nomor BPJS sudah terdaftar di sistem.',
            'npwp_number.unique' => 'Nomor NPWP sudah terdaftar di sistem.',
            'roles.required' => 'Setidaknya satu role harus dipilih.',
        ]);

        $validator->after(function ($validator) use ($normalizedBranches) {
            if (
                $normalizedBranches['primary_branch_id']
                && !in_array($normalizedBranches['primary_branch_id'], $normalizedBranches['branch_ids'], true)
            ) {
                $validator->errors()->add('primary_branch_id', 'Branch utama harus termasuk dalam daftar branch yang dipilih.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $branchId = $normalizedBranches['primary_branch_id'];
            $branchName = $normalizedBranches['primary_branch_name'];

            // Get department name if department_id is provided
            $departmentName = null;
            if ($request->department_id) {
                $department = Department::find($request->department_id);
                $departmentName = $department ? $department->name : null;
            }

            // Handle file uploads
            $ktpFilePath = $user->ktp_file_path;
            $npwpFilePath = $user->npwp_file_path;
            $photoFilePath = $user->photo_file_path;
            
            if ($request->hasFile('ktp_file')) {
                // Delete old file if exists
                if ($user->ktp_file_path && Storage::disk('public')->exists($user->ktp_file_path)) {
                    Storage::disk('public')->delete($user->ktp_file_path);
                }
                $ktpFile = $request->file('ktp_file');
                $ktpFilePath = FileUploadHelper::storeFile($ktpFile, 'users/documents/ktp_' . time() . '.' . $ktpFile->getClientOriginalExtension());
            }
            
            if ($request->hasFile('npwp_file')) {
                // Delete old file if exists
                if ($user->npwp_file_path && Storage::disk('public')->exists($user->npwp_file_path)) {
                    Storage::disk('public')->delete($user->npwp_file_path);
                }
                $npwpFile = $request->file('npwp_file');
                $npwpFilePath = FileUploadHelper::storeFile($npwpFile, 'users/documents/npwp_' . time() . '.' . $npwpFile->getClientOriginalExtension());
            }
            
            if ($request->hasFile('photo_file')) {
                // Delete old file if exists
                if ($user->photo_file_path && Storage::disk('public')->exists($user->photo_file_path)) {
                    Storage::disk('public')->delete($user->photo_file_path);
                }
                $photoFile = $request->file('photo_file');
                $photoFilePath = FileUploadHelper::storeFile($photoFile, 'users/photos/photo_' . time() . '.' . $photoFile->getClientOriginalExtension());
            }

            // Get first role name for backward compatibility (roles field)
            $firstRole = Role::find($request->roles[0]);
            $rolesString = $firstRole ? $firstRole->name : null;

            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'nik' => $request->nik,
                'username' => $request->username,
                'branch_id' => $branchId,
                'branch_name' => $branchName,
                'department_id' => $request->department_id,
                'department_name' => $departmentName,
                'position_name' => $request->position_name,
                'phone' => $request->phone,
                'handphone_1' => $request->handphone_1,
                'salutation' => $request->salutation,
                'roles' => $rolesString, // Keep for backward compatibility
                'join_date' => $request->join_date ? \Carbon\Carbon::parse($request->join_date)->setTimezone('Asia/Jakarta')->format('Y-m-d') : null,
                'is_active' => $request->boolean('is_active', true),
                // New fields
                'emergency_contact_number' => $request->emergency_contact_number,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_account_holder' => $request->bank_account_holder,
                'ktp_file_path' => $ktpFilePath,
                'npwp_file_path' => $npwpFilePath,
                'photo_file_path' => $photoFilePath,
                'bpjs_number' => $request->bpjs_number,
                'bpjs_date' => $request->bpjs_date ? \Carbon\Carbon::parse($request->bpjs_date)->setTimezone('Asia/Jakarta')->format('Y-m-d') : null,
                'npwp_number' => $request->npwp_number,
                'is_commission_achiever' => $request->boolean('is_commission_achiever', false),
                'updated_by' => Auth::id()
            ];

            // Only update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            // Sync multiple roles using relationship
            $user->roles()->sync($request->roles);
            
            // Sync multiple branches if provided
            if (!empty($normalizedBranches['branch_ids'])) {
                $branchData = [];
                foreach ($normalizedBranches['branch_ids'] as $selectedBranchId) {
                    $branchData[$selectedBranchId] = [
                        'is_primary' => $selectedBranchId === $normalizedBranches['primary_branch_id'],
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ];
                }
                $user->assignedBranches()->sync($branchData);
            } else {
                $user->assignedBranches()->detach();
            }
            
            // Reload roles and branches relationship
            $user->load(['roles', 'assignedBranches']);
            
            // Auto-set multi_login for Administrator or Management Manager
            if ($user->requiresMultiLogin() && !$user->multi_login) {
                $user->update(['multi_login' => true]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating user', [
                'user_id' => $user->id,
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(User $user)
    {
        $this->authorizeUserRecordAccess($user);

        // Prevent deleting self
        if ($user->id === Auth::id()) {
            $errorMessage = 'Cannot deactivate your own account.';
            return response()->json([
                'status' => 'error', // Changed from success => false to status => error for consistency
                'success' => false,
                'message' => $errorMessage,
                'errors' => [$errorMessage]
            ], 422); // Unprocessable Entity
        }

        try {
            // [Global Soft Delete] Deactivate instead of Delete
            // Detach permissions? Maybe keep them for history or re-activation?
            
            // $user->permissions()->detach(); // Optional: keep permissions if they might come back
            
            $user->update(['is_active' => false]);
            
            if (request()->ajax() || request()->wantsJson()) {
                 return response()->json([
                    'status' => 'success',
                    'success' => true,
                    'message' => 'User deactivated successfully'
                ]);
            }
            return back()->with('success', 'User deactivated successfully');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Error deactivating user: ' . $e->getMessage(),
                'errors' => ['Error: ' . $e->getMessage()]
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id'
        ]);

        try {
            $ids = $request->ids;
            $currentUserId = Auth::id();
            
            $errors = [];
            $processedIds = [];
            
            // Filter invalid deletions
            foreach ($ids as $id) {
                if ($id == $currentUserId) {
                    $errors[] = "Cannot deactivate your own account.";
                    continue;
                }

                $targetUser = User::find($id);
                if (!$targetUser || !$this->canAccessUserRecord($targetUser)) {
                    $errors[] = "You do not have access to deactivate user ID {$id}.";
                    continue;
                }

                $processedIds[] = $id;
            }

            if (empty($processedIds) && !empty($errors)) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 422);
            }

            $users = User::whereIn('id', $processedIds)->get();
            $count = 0;
            
            foreach ($users as $user) {
                // [Global Soft Delete] Deactivate instead of Delete
                // $user->permissions()->detach();
                $user->update(['is_active' => false]);
                $count++;
            }

            $message = "Successfully deactivated {$count} users.";
            $success = true;
            $statusCode = 200;

            if ($count === 0 && !empty($errors)) {
                $message = "Failed to deactivate users.";
                $success = false;
                $statusCode = 422;
            } elseif (!empty($errors)) {
                $message = "Deactivated {$count} users. Some failed.";
            }

            return response()->json([
                'status' => $success ? 'success' : 'error',
                'success' => $success,
                'message' => $message,
                'count' => $count,
                'errors' => $errors
            ], $statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Error processing request: ' . $e->getMessage(),
                'errors' => [$e->getMessage()]
            ], 500);
        }
    }

    public function dashboard()
    {
        $total_users = User::count();
        $active_users = User::where('is_active', true)->count();
        $inactive_users = User::where('is_active', false)->count();

        $users_by_department = User::with('department')
            ->selectRaw('department_id, count(*) as count')
            ->groupBy('department_id')
            ->get();

        $users_by_branch = User::with('branch')
            ->selectRaw('branch_id, count(*) as count')
            ->groupBy('branch_id')
            ->get();

        $recent_users = User::with(['department', 'branch', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recent_logins = User::whereHas('loginHistories', function($q) {
            $q->whereDate('login_at', today());
        })->count();

        return view('system.dashboard', compact(
            'total_users',
            'active_users',
            'inactive_users',
            'users_by_department',
            'users_by_branch',
            'recent_users',
            'recent_logins'
        ));
    }

    public function resetPassword(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update([
                'password' => Hash::make($request->password),
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Password reset successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'message' => 'Error resetting password: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUsers(Request $request)
    {
        $query = User::where('is_active', true)
            ->with(['department'])
            ->orderBy('name');

        $this->applyAccessControlFilter($query, Auth::user(), 'id', null, 'branch_id', null, null);

        $users = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    protected function authorizeUserRecordAccess(User $targetUser): void
    {
        abort_unless($this->canAccessUserRecord($targetUser), 403, 'Anda tidak memiliki akses ke data user ini.');
    }

    protected function canAccessUserRecord(User $targetUser): bool
    {
        $actor = Auth::user();

        if (!$actor) {
            return false;
        }

        $query = User::whereKey($targetUser->getKey());
        $this->applyAccessControlFilter($query, $actor, 'id', null, 'branch_id', null, null);

        return $query->exists();
    }
}

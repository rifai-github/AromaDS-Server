<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasComprehensiveAuditTrail;
use App\Http\Traits\AutoFilterable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use App\Models\AuditLog;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasComprehensiveAuditTrail, AutoFilterable;

    protected ?array $resolvedRoleNamesCache = null;

    protected ?array $resolvedPermissionLookupCache = null;

    protected array $resolvedModuleAccessCache = [];

    protected array $resolvedMenuAccessCache = [];

    protected ?string $resolvedPrimaryRoleNameCache = null;

    protected $fillable = [
        'nik',
        'department_id',
        'department_name',
        'branch_id',
        'branch_name',
        'position_id',
        'position_name',
        'price_category_id',
        'salutation',
        'name',
        'join_date',
        'email',
        'username',
        'password',
        'address_1',
        'address_2',
        'phone',
        'handphone_1',
        'handphone_2',
        'gender',
        'marital_status',
        'religion',
        'identity_type',
        'identity_number',
        'npwp_number',
        'bpjs_number',
        'bpjs_date',
        'blood_type',
        'rhesus',
        'emergency_contact_name',
        'emergency_contact_number',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'ktp_file_path',
        'npwp_file_path',
        'photo_file_path',
        'employee_status',
        'data_restriction',
        'roles',
        'is_active',
        'multi_login',
        'is_frozen',
        'screenshot_allowed',
        'is_commission_achiever',
        'created_by',
        'updated_by'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'join_date' => 'date',
        'bpjs_date' => 'date',
        'is_active' => 'boolean',
        'multi_login' => 'boolean',
        'is_frozen' => 'boolean',
        'screenshot_allowed' => 'boolean',
        'is_commission_achiever' => 'boolean',
    ];

    public function forgetAccessResolutionCache(): void
    {
        $this->resolvedRoleNamesCache = null;
        $this->resolvedPermissionLookupCache = null;
        $this->resolvedModuleAccessCache = [];
        $this->resolvedMenuAccessCache = [];
        $this->resolvedPrimaryRoleNameCache = null;
    }

    // Accessor for join_date to ensure proper timezone handling
    public function getJoinDateAttribute($value)
    {
        if ($value) {
            return \Carbon\Carbon::parse($value)->setTimezone('Asia/Jakarta')->format('Y-m-d');
        }
        return $value;
    }

    // Relationships

    public function department()
    {
        return $this->belongsTo(Department::class);
    }



    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Multi-Branch: Branches this user is assigned to (many-to-many)
     */
    public function assignedBranches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user')
            ->withPivot('is_primary', 'created_by', 'updated_by')
            ->withTimestamps();
    }

    /**
     * Get primary branch from multi-branch assignment
     */
    public function primaryBranch()
    {
        return $this->assignedBranches()->wherePivot('is_primary', true)->first();
    }

    public function position()
    {
        return $this->belongsTo(MasterOption::class, 'position_id');
    }

    public function positionOption()
    {
        return $this->belongsTo(MasterOption::class, 'position_id');
    }

    public function genderOption()
    {
        return $this->belongsTo(MasterOption::class, 'gender');
    }

    public function maritalStatusOption()
    {
        return $this->belongsTo(MasterOption::class, 'marital_status');
    }

    public function religionOption()
    {
        return $this->belongsTo(MasterOption::class, 'religion');
    }

    public function identityTypeOption()
    {
        return $this->belongsTo(MasterOption::class, 'identity_type');
    }

    public function bloodTypeOption()
    {
        return $this->belongsTo(MasterOption::class, 'blood_type');
    }

    public function rhesusOption()
    {
        return $this->belongsTo(MasterOption::class, 'rhesus');
    }

    public function employeeStatusOption()
    {
        return $this->belongsTo(MasterOption::class, 'employee_status');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function priceCategory()
    {
        return $this->belongsTo(MasterOption::class, 'price_category_id');
    }

    public function salesActivities()
    {
        return $this->hasMany(SalesActivity::class, 'staff_id');
    }

    public function prospects()
    {
        return $this->hasMany(Prospect::class, 'staff_id');
    }

    public function surveys()
    {
        return $this->hasMany(Survey::class, 'marketing_id');
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class, 'marketing_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'marketing_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'pic_finance_id');
    }

    public function jobSchedules()
    {
        return $this->hasMany(JobSchedule::class, 'assigned_by');
    }

    public function assignedCustomers()
    {
        return $this->hasMany(Customer::class, 'assigned_to');
    }

    // Building Multi-User Enhancement relationships
    public function buildingUsers()
    {
        return $this->hasMany(BuildingUser::class);
    }

    public function buildings()
    {
        return $this->belongsToMany(Building::class, 'building_users')
            ->withPivot('role', 'is_primary', 'is_active', 'assigned_at', 'unassigned_at', 'notes', 'assigned_by', 'unassigned_by')
            ->withTimestamps();
    }

    public function activeBuildings()
    {
        return $this->belongsToMany(Building::class, 'building_users')
            ->wherePivot('is_active', true)
            ->withPivot('role', 'is_primary', 'assigned_at', 'notes', 'assigned_by')
            ->withTimestamps();
    }

    public function primaryBuildings()
    {
        return $this->belongsToMany(Building::class, 'building_users')
            ->wherePivot('is_primary', true)
            ->wherePivot('is_active', true)
            ->withPivot('role', 'assigned_at', 'notes', 'assigned_by')
            ->withTimestamps();
    }

    public function buildingsByRole($role)
    {
        return $this->belongsToMany(Building::class, 'building_users')
            ->wherePivot('role', $role)
            ->wherePivot('is_active', true)
            ->withPivot('is_primary', 'assigned_at', 'notes', 'assigned_by')
            ->withTimestamps();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
                    ->withTimestamps();
    }
    
    /**
     * Override the roles attribute to always return the relationship
     * This fixes conflict with the 'roles' column in database
     * 
     * IMPORTANT: When you need the raw string value from database column,
     * use getRolesColumnValue() instead of $this->roles
     */
    public function getRolesAttribute($value)
    {
        // If relationship is already loaded, return it
        if ($this->relationLoaded('roles')) {
            return $this->getRelation('roles');
        }
        
        // Otherwise load and return the relationship
        return $this->roles()->get();
    }
    
    /**
     * Get the raw roles column value from database (string)
     * Use this when you need the string value, not the relationship collection
     * 
     * @return string|null
     */
    public function getRolesColumnValue()
    {
        $rolesColumn = $this->getAttributes()['roles'] ?? null;
        return ($rolesColumn && is_string($rolesColumn)) ? $rolesColumn : null;
    }

    /**
     * Get the location logs for the technician.
     */
    public function locationLogs()
    {
        return $this->hasMany(TechnicianLocation::class, 'technician_id');
    }

    /**
     * Get the latest location for the technician.
     */
    public function latestLocation()
    {
        return $this->hasOne(TechnicianLocation::class, 'technician_id')->latest('timestamp');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members', 'user_id', 'team_id')
                    ->withPivot('role', 'is_active', 'joined_date', 'left_date', 'notes')
                    ->withTimestamps();
    }

    public function jobAssignments()
    {
        return $this->hasMany(JobAssignment::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permission', 'user_id', 'permission_id');
    }

    public function accessManagements()
    {
        return $this->belongsToMany(AccessManagement::class, 'user_access_management', 'user_id', 'access_management_id');
    }

    // Note: roles() relationship removed - using string column 'roles' instead

    public function workingHours()
    {
        return $this->hasMany(WorkingHour::class);
    }

    public function workingHoursExceptions()
    {
        return $this->hasMany(WorkingHoursException::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    public function userSessions()
    {
        return $this->hasMany(UserSession::class);
    }

    public function passwordHistories()
    {
        return $this->hasMany(PasswordHistory::class);
    }

    // New relationships for operational module
    public function technicianLocations()
    {
        return $this->hasMany(TechnicianLocation::class, 'technician_id');
    }

    public function jobReports()
    {
        return $this->hasMany(JobReport::class, 'technician_id');
    }

    public function serviceHistories()
    {
        return $this->hasMany(ServiceHistory::class, 'technician_id');
    }

    public function jobSignatures()
    {
        return $this->hasMany(JobSignature::class, 'signed_by');
    }

    public function temperatureRecords()
    {
        return $this->hasMany(TemperatureRecord::class, 'recorded_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->salutation . ' ' . $this->name;
    }

    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }

    public function getDepartmentNameAttribute($value)
    {
        // If department_name is null, try to get from relationship
        if (empty($value) && $this->relationLoaded('department') && $this->department) {
            return $this->department->name ?? null;
        }
        
        // If department_name is JSON string, decode it
        if (!empty($value)) {
            // Check if it's a JSON string
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // If it's an array, try to get name or title
                return $decoded['name'] ?? $decoded['title'] ?? $decoded['department_name'] ?? $value;
            }
            // Check if it's already an object/array
            if (is_array($decoded)) {
                return $decoded['name'] ?? $decoded['title'] ?? $decoded['department_name'] ?? $value;
            }
        }
        
        return $value;
    }

    // Role and Permission Helper Methods
    public function hasRole($role)
    {
        $roleNames = $this->getResolvedRoleNames();

        if (is_string($role)) {
            return in_array($role, $roleNames, true);
        }

        if (is_array($role)) {
            return !empty(array_intersect($role, $roleNames));
        }

        return false;
    }

    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return !empty(array_intersect($roles, $this->getResolvedRoleNames()));
    }

    public function hasPermission($permission)
    {
        $permissionLookup = $this->getResolvedPermissionLookup();

        foreach ($this->expandPermissionCandidates($permission) as $candidate) {
            if (isset($permissionLookup[$candidate])) {
                return true;
            }
        }

        return false;
        
        // Fallback: Handle permission name variations/aliases
        // company.customers.* → marketing.company.customers.*
        if (str_starts_with($permission, 'company.customers.')) {
            $altPermission = str_replace('company.customers.', 'marketing.company.customers.', $permission);
            // Avoid infinite recursion by directly checking role permissions
            foreach ($userRoles as $role) {
                if ($role->hasPermission($altPermission)) {
                    return true;
                }
            }
        }
        
        // marketing.customers.* → marketing.company.customers.*
        if (str_starts_with($permission, 'marketing.customers.')) {
            $altPermission = str_replace('marketing.customers.', 'marketing.company.customers.', $permission);
            foreach ($userRoles as $role) {
                if ($role->hasPermission($altPermission)) {
                    return true;
                }
            }
            
            // Also try fallback to company.customers.* (for roles with only company.customers.view)
            $altPermission2 = str_replace('marketing.customers.', 'company.customers.', $permission);
            foreach ($userRoles as $role) {
                if ($role->hasPermission($altPermission2)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function hasAnyPermission($permissions)
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }
        
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }

    public function getAllPermissions()
    {
        $permissions = collect();
        
        // Get direct permissions (load if not loaded)
        if (!$this->relationLoaded('permissions')) {
            $this->load('permissions');
        }
        $permissions = $permissions->merge($this->permissions);
        
        // Get role permissions from relationship
        if ($this->roles && $this->roles->count() > 0) {
            foreach ($this->roles as $role) {
                // Load permissions for each role if not loaded
                if (!$role->relationLoaded('permissions')) {
                    $role->load('permissions');
                }
                if ($role->permissions) {
                    $permissions = $permissions->merge($role->permissions);
                }
            }
        }
        
        return $permissions->unique('id');
    }

    public function canAccessModule($module)
    {
        if (array_key_exists($module, $this->resolvedModuleAccessCache)) {
            return $this->resolvedModuleAccessCache[$module];
        }

        // Check if user has any permission for this module
        $modulePermissions = [
            "{$module}.dashboard",
            "{$module}.view",
            "{$module}.read",
            "{$module}.*"
        ];
        
        foreach ($modulePermissions as $permission) {
            if ($this->hasPermission($permission)) {
                return $this->resolvedModuleAccessCache[$module] = true;
            }
        }
        
        // Check for specific module permissions (e.g., marketing.pipeline, marketing.surveys)
        foreach (array_keys($this->getResolvedPermissionLookup()) as $permissionName) {
            if (str_starts_with($permissionName, "{$module}.")) {
                return $this->resolvedModuleAccessCache[$module] = true;
            }
        }

        
        // Allow role-based access for main departments
        // e.g. "Marketing Staff" can access "marketing" module
        if ($this->hasRoleStartingWith(ucfirst($module))) {
            return $this->resolvedModuleAccessCache[$module] = true;
        }

        // Management/Admin can access all modules
        if ($this->hasRoleStartingWith('Management') || $this->hasRole('Admin')) {
            return $this->resolvedModuleAccessCache[$module] = true;
        }
        
        return $this->resolvedModuleAccessCache[$module] = false;
    }
    
    /**
     * Check if user can access a specific menu item by permission name
     */
    public function canAccessMenuItem($permissionName)
    {
        if (array_key_exists($permissionName, $this->resolvedMenuAccessCache)) {
            return $this->resolvedMenuAccessCache[$permissionName];
        }

        // Management/Admin can access all menu items
        if ($this->hasRoleStartingWith('Management') || $this->hasRole('Admin')) {
            return $this->resolvedMenuAccessCache[$permissionName] = true;
        }
        
        // Check exact permission
        if ($this->hasPermission($permissionName)) {
            return $this->resolvedMenuAccessCache[$permissionName] = true;
        }

        // Check for .view suffix (common pattern for menu access)
        // If app checks "marketing.dashboard", we also check "marketing.dashboard.view"
        if ($this->hasPermission($permissionName . '.view')) {
            return $this->resolvedMenuAccessCache[$permissionName] = true;
        }
        
        return $this->resolvedMenuAccessCache[$permissionName] = false;
    }

    public function canCreateInModule($module)
    {
        // Management/Admin can create in all modules
        if ($this->hasRoleStartingWith('Management') || $this->hasRole('Admin') || $this->hasRole('super_admin')) {
            return true;
        }
        
        // Allow Marketing Staff/Manager to create in marketing module
        if ($module === 'marketing' && $this->hasRoleStartingWith('Marketing')) {
            return true;
        }
        
        return $this->hasPermission("{$module}.create") || 
               $this->hasPermission("{$module}.write");
    }

    public function canEditInModule($module)
    {
        // Management/Admin can edit in all modules
        if ($this->hasRoleStartingWith('Management') || $this->hasRole('Admin') || $this->hasRole('super_admin')) {
            return true;
        }
        
        return $this->hasPermission("{$module}.edit") || 
               $this->hasPermission("{$module}.write");
    }

    public function canDeleteInModule($module)
    {
        // Management/Admin can delete in all modules
        if ($this->hasRoleStartingWith('Management') || $this->hasRole('Admin') || $this->hasRole('super_admin')) {
            return true;
        }
        
        return $this->hasPermission("{$module}.delete") || 
               $this->hasPermission("{$module}.admin");
    }

    /**
     * Check if user can approve items in a specific module
     * 
     * Checks:
     * 1. Specific approval permission (e.g., surveys.approve)
     * 2. Manager data restriction
     * 3. Admin/Management role
     * 
     * @param string $module Module name (surveys, quotations, contracts, job_advices, contract_files)
     * @return bool
     */
    public function canApprove($module)
    {
        // Check for specific permission
        // Try prefixed format first (e.g. marketing.surveys.approve)
        $possiblePermissions = [
            $module . '.approve',
            'marketing.' . $module . '.approve',
            'operational.' . $module . '.approve',
            'finance.' . $module . '.approve',
            'warehouse.' . $module . '.approve',
            'system.' . $module . '.approve',
            // Handle kebab-case variations
            str_replace('_', '-', $module) . '.approve',
            'marketing.' . str_replace('_', '-', $module) . '.approve',
        ];

        foreach ($possiblePermissions as $perm) {
            if ($this->hasPermission($perm)) {
                return true;
            }
        }
        
        return false;
    }

    // Data Restriction Helper Methods
    public function canViewAllData()
    {
        return $this->data_restriction === 'none' || $this->hasRole('super_admin');
    }

    public function canViewBranchData($branchId = null)
    {
        if ($this->canViewAllData()) {
            return true;
        }
        
        if ($this->data_restriction === 'branch') {
            return $branchId === null || $this->branch_id == $branchId;
        }
        
        return false;
    }

    public function canViewDepartmentData($departmentId = null)
    {
        if ($this->canViewAllData()) {
            return true;
        }
        
        if ($this->data_restriction === 'department') {
            return $departmentId === null || $this->department_id == $departmentId;
        }
        
        return false;
    }

    public function canViewOwnData($userId = null)
    {
        if ($this->canViewAllData()) {
            return true;
        }
        
        if ($this->data_restriction === 'own') {
            return $userId === null || $this->id == $userId;
        }
        
        return false;
    }

    // Mutator removed; rely on Laravel 'hashed' cast

    /**
     * Get user access levels
     */
    public function accessLevels()
    {
        return $this->hasMany(UserAccessLevel::class);
    }

    // Department-based role assignment
    public function getDepartmentRole()
    {
        if (!$this->department_id) {
            return null;
        }

        $departmentRole = DepartmentRole::where('department_id', $this->department_id)
            ->where('is_active', true)
            ->with('role')
            ->first();

        return $departmentRole ? $departmentRole->role : null;
    }

    public function getEffectiveRole()
    {
        // First, try to get role from relationship (user_roles)
        $userRole = $this->roles()->first();
        if ($userRole) {
            return $userRole->name;
        }
        
        // Fallback: Check if user has individual role set in database column (for backward compatibility)
        $rolesColumn = $this->getAttributes()['roles'] ?? null;
        if ($rolesColumn && is_string($rolesColumn)) {
            return $rolesColumn;
        }

        // Otherwise, get role from department
        $departmentRole = $this->getDepartmentRole();
        return $departmentRole ? $departmentRole->name : null;
    }

    public function hasEffectiveRole($role)
    {
        $effectiveRole = $this->getEffectiveRole();
        return $effectiveRole === $role;
    }

    public function hasEffectivePermission($permission)
    {
        // Check direct user permissions first
        if ($this->permissions()->where('name', $permission)->exists()) {
            return true;
        }

        // Check role permissions
        $effectiveRole = $this->getEffectiveRole();
        if ($effectiveRole) {
            $roleModel = Role::where('name', $effectiveRole)->first();
            if ($roleModel && $roleModel->permissions()->where('name', $permission)->exists()) {
                return true;
            }
        }

        return false;
    }

    // Position-based role assignment
    public function getPositionRole()
    {
        if (!$this->department_id || !$this->position_name) {
            return null;
        }

        $positionRole = PositionRole::where('department_id', $this->department_id)
            ->where('position_name', $this->position_name)
            ->where('is_active', true)
            ->with('role')
            ->first();

        return $positionRole ? $positionRole->role : null;
    }

    /**
     * Get effective role (using Individual Role only - simplified system)
     * MOM10: Simplified to use only Individual Role from user_roles relationship
     */
    public function getEffectiveRoleWithPosition()
    {
        // MOM10: Use only Individual Role from relationship
        $userRole = $this->roles()->first();
        return $userRole ? $userRole->name : null;
    }

    /**
     * Check if user has specific role (using Individual Role only)
     * MOM10: Simplified to use only Individual Role from user_roles relationship
     */
    public function hasEffectiveRoleWithPosition($role)
    {
        // MOM10: Check role name from relationship
        if (is_string($role)) {
            return $this->roles()->where('name', $role)->exists();
        }
        
        if (is_array($role)) {
            return $this->roles()->whereIn('name', $role)->exists();
        }
        
        return false;
    }
    
    /**
     * Check if user has any of the specified roles (using Individual Role only)
     */
    public function hasAnyEffectiveRole($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        return $this->roles()->whereIn('name', $roles)->exists();
    }
    
    /**
     * Get user's role name (first role from relationship)
     */
    public function getRoleName()
    {
        if ($this->resolvedPrimaryRoleNameCache !== null) {
            return $this->resolvedPrimaryRoleNameCache;
        }

        $this->loadMissing('roles');
        $userRole = $this->roles->first();

        if ($userRole?->name) {
            return $this->resolvedPrimaryRoleNameCache = $userRole->name;
        }

        return $this->resolvedPrimaryRoleNameCache = $this->getResolvedRoleNames()[0] ?? null;
    }
    
    /**
     * Check if user role name starts with department name (e.g., "Marketing Staff" starts with "Marketing")
     */
    public function hasRoleStartingWith($prefix)
    {
        foreach ($this->getResolvedRoleNames() as $roleName) {
            if ($roleName && stripos($roleName, $prefix) === 0) {
                return true;
            }
        }
        
        return false;
    }

    protected function getResolvedRoleNames(): array
    {
        if ($this->resolvedRoleNamesCache !== null) {
            return $this->resolvedRoleNamesCache;
        }

        $this->loadMissing('roles');

        $roleNames = $this->roles
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($roleNames)) {
            $rolesColumn = $this->getAttributes()['roles'] ?? null;

            if ($rolesColumn && is_string($rolesColumn)) {
                $decoded = json_decode($rolesColumn, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    if (isset($decoded[0]['name'])) {
                        $roleNames = array_values(array_unique(array_filter(array_map(
                            static fn ($role) => $role['name'] ?? null,
                            $decoded
                        ))));
                    } elseif (isset($decoded['name'])) {
                        $roleNames = [$decoded['name']];
                    }
                }

                if (empty($roleNames)) {
                    $roleNames = [$rolesColumn];
                }
            }
        }

        return $this->resolvedRoleNamesCache = array_values(array_unique(array_filter($roleNames)));
    }

    protected function getResolvedPermissionLookup(): array
    {
        if ($this->resolvedPermissionLookupCache !== null) {
            return $this->resolvedPermissionLookupCache;
        }

        $this->loadMissing('permissions', 'roles.permissions');

        $permissionNames = [];

        foreach ($this->permissions as $permission) {
            if (!empty($permission->name)) {
                $permissionNames[] = $permission->name;
            }
        }

        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                if (!empty($permission->name)) {
                    $permissionNames[] = $permission->name;
                }
            }
        }

        if (empty($permissionNames)) {
            foreach ($this->getResolvedRoleNames() as $roleName) {
                $roleModel = Role::query()
                    ->with('permissions')
                    ->where('name', $roleName)
                    ->first();

                if (!$roleModel) {
                    continue;
                }

                foreach ($roleModel->permissions as $permission) {
                    if (!empty($permission->name)) {
                        $permissionNames[] = $permission->name;
                    }
                }
            }
        }

        $permissionNames = array_values(array_unique(array_filter($permissionNames)));

        return $this->resolvedPermissionLookupCache = array_fill_keys($permissionNames, true);
    }

    protected function expandPermissionCandidates(string $permission): array
    {
        $candidates = [$permission];

        if (str_starts_with($permission, 'contracts.')) {
            $candidates[] = 'marketing.' . $permission;
        }

        if (str_starts_with($permission, 'marketing.contracts.')) {
            $candidates[] = str_replace('marketing.contracts.', 'contracts.', $permission);
        }

        if (str_starts_with($permission, 'company.customers.')) {
            $candidates[] = str_replace('company.customers.', 'marketing.company.customers.', $permission);
        }

        if (str_starts_with($permission, 'marketing.customers.')) {
            $candidates[] = str_replace('marketing.customers.', 'marketing.company.customers.', $permission);
            $candidates[] = str_replace('marketing.customers.', 'company.customers.', $permission);
        }

        return array_values(array_unique($candidates));
    }

    public function hasEffectivePermissionWithPosition($permission)
    {
        // Check direct user permissions first
        if ($this->permissions()->where('name', $permission)->exists()) {
            return true;
        }

        // Check role permissions with position hierarchy
        $effectiveRole = $this->getEffectiveRoleWithPosition();
        if ($effectiveRole) {
            $roleModel = Role::where('name', $effectiveRole)->first();
            if ($roleModel && $roleModel->permissions()->where('name', $permission)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function requiresMultiLogin()
    {
        // Use permission-based check instead of hardcoded roles
        return $this->hasPermission('system.access-control.bypass') || $this->hasPermission('admin.view');
    }

    /**
     * Check if user is always allowed to take screenshots (administrator/management)
     */
    public function isAlwaysAllowedScreenshot()
    {
        return $this->hasPermission('system.access-control.bypass') || $this->hasPermission('admin.view');
    }

    /**
     * Check if user is allowed to take screenshots/print screen
     * Administrator, Admin, Super Admin, and Management Manager are always allowed (via permission)
     * Other users check from screenshot_allowed field (default: false)
     */
    public function canTakeScreenshot()
    {
        // Priority 1: Check from bypass permission
        if ($this->isAlwaysAllowedScreenshot()) {
            return true;
        }
        
        // Priority 2: Fallback to database column (for backward compatibility during migration)
        $rolesColumn = $this->getRolesColumnValue();
        if ($rolesColumn) {
            $rolesLower = strtolower($rolesColumn);
            if (stripos($rolesLower, 'administrator') !== false || 
                stripos($rolesLower, 'admin') !== false ||
                stripos($rolesLower, 'super_admin') !== false ||
                stripos($rolesLower, 'management manager') !== false) {
                return true;
            }
        }
        
        // For other users, check screenshot_allowed field (default: false)
        return (bool)($this->screenshot_allowed ?? false);
    }

    /**
     * Get user login restrictions
     */
    public function loginRestrictions()
    {
        return $this->hasMany(UserLoginRestriction::class);
    }

    /**
     * Get user login history
     */
    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class);
    }

    // Building Multi-User Enhancement helper methods
    public function assignToBuilding($buildingId, $role = 'user', $isPrimary = false, $notes = null)
    {
        return BuildingUser::assignUserToBuilding($buildingId, $this->id, $role, $isPrimary, $notes);
    }

    public function removeFromBuilding($buildingId)
    {
        $buildingUser = BuildingUser::where('building_id', $buildingId)
            ->where('user_id', $this->id)
            ->where('is_active', true)
            ->first();

        if ($buildingUser) {
            $buildingUser->deactivate();
            return true;
        }

        return false;
    }

    public function setAsPrimaryForBuilding($buildingId)
    {
        $buildingUser = BuildingUser::where('building_id', $buildingId)
            ->where('user_id', $this->id)
            ->where('is_active', true)
            ->first();

        if ($buildingUser) {
            $buildingUser->setAsPrimary();
            return true;
        }

        return false;
    }

    public function getActiveBuildings()
    {
        return BuildingUser::getUserBuildings($this->id, true);
    }

    public function getPrimaryBuildings()
    {
        return BuildingUser::where('user_id', $this->id)
            ->where('is_primary', true)
            ->where('is_active', true)
            ->with('building')
            ->get();
    }

    public function getBuildingCount()
    {
        return BuildingUser::where('user_id', $this->id)
            ->where('is_active', true)
            ->count();
    }

    public function getBuildingsByRole($role)
    {
        return BuildingUser::where('user_id', $this->id)
            ->where('role', $role)
            ->where('is_active', true)
            ->with('building')
            ->get();
    }

    public function hasBuilding($buildingId)
    {
        return BuildingUser::where('user_id', $this->id)
            ->where('building_id', $buildingId)
            ->where('is_active', true)
            ->exists();
    }

    public function getBuildingRole($buildingId)
    {
        $buildingUser = BuildingUser::where('user_id', $this->id)
            ->where('building_id', $buildingId)
            ->where('is_active', true)
            ->first();

        return $buildingUser ? $buildingUser->role : null;
    }

    public function isPrimaryForBuilding($buildingId)
    {
        return BuildingUser::where('user_id', $this->id)
            ->where('building_id', $buildingId)
            ->where('is_primary', true)
            ->where('is_active', true)
            ->exists();
    }

    public function canAccessBuilding($buildingId)
    {
        return $this->hasBuilding($buildingId);
    }

    public function getBuildingPermissions($buildingId)
    {
        $buildingUser = BuildingUser::where('user_id', $this->id)
            ->where('building_id', $buildingId)
            ->where('is_active', true)
            ->first();

        if (!$buildingUser) {
            return [];
        }

        $permissions = [];
        
        // Basic permissions based on role
        switch ($buildingUser->role) {
            case 'admin':
                $permissions = ['view', 'edit', 'delete', 'assign_users', 'manage_settings'];
                break;
            case 'manager':
                $permissions = ['view', 'edit', 'assign_users'];
                break;
            case 'user':
                $permissions = ['view'];
                break;
            case 'viewer':
                $permissions = ['view'];
                break;
        }

        // Primary user gets additional permissions
        if ($buildingUser->is_primary) {
            $permissions[] = 'primary_access';
            $permissions[] = 'manage_primary';
        }

        return $permissions;
    }
}

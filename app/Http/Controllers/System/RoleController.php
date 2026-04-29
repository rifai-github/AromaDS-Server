<?php

namespace App\Http\Controllers\System;

use App\Http\Traits\ColumnFilterTrait;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RoleController extends Controller
{
    use ColumnFilterTrait;
    public function index(Request $request)
    {
        $query = Role::select([
                'id',
                'name',
                'description',
                'permissions',
                'system_reserved',
                'is_active',
                'created_by',
                'updated_by',
                'created_at',
            ])
            ->with(['createdBy:id,name', 'updatedBy:id,name'])
            ->withCount('users');

        // Define column map for filters
        $columnMap = [
            'name' => ['column' => 'name'],
            'description' => ['column' => 'description'],
            'is_active' => ['column' => 'is_active', 'boolean' => true],
            'system_reserved' => ['column' => 'system_reserved', 'boolean' => true],
            'created_by' => ['relation' => 'createdBy', 'column' => 'name'],
            'updated_by' => ['relation' => 'updatedBy', 'column' => 'name'],
        ];

        // Apply advanced filters using ColumnFilterTrait
        $this->applyColumnFilters($query, null, $columnMap);

        // Sorting logic (integrated with global sorting)
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');
        
        // Handle sorting for relation columns if needed
        if (strpos($sort, '.') !== false) {
            // Simplified sorting for basic usage
            $roles = $query->paginate(25);
        } else {
            $roles = $query->orderBy($sort, $direction)->paginate(25);
        }

        // Get filter options
        $permissions = Cache::remember('role:index:permissions', 300, function () {
            return Permission::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        });

        return view('system.roles.index', compact('roles', 'permissions'));
    }

    public function create()
    {
        $permissions = Permission::where('is_active', true)->get();
        $users = User::where('is_active', true)->get();

        return view('system.roles.create', compact('permissions', 'users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'system_reserved' => 'required|in:0,1,true,false',
            'is_active' => 'required|in:0,1,true,false'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
                'permissions' => $request->permissions ?? [],
                'system_reserved' => $request->system_reserved === '1' || $request->system_reserved === 'true' || $request->system_reserved === true,
                'is_active' => $request->is_active === '1' || $request->is_active === 'true' || $request->is_active === true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Sync role permissions if provided (only for edit, not create)
            if ($request->has('permissions') && !empty($request->permissions)) {
                $role->rolePermissions()->delete();
                
                foreach ($request->permissions as $permissionId) {
                    $role->rolePermissions()->create([
                        'permission_id' => $permissionId
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Role created successfully',
                    'data' => $role,
                    'redirect' => route('system.roles.edit', $role->id)
                ]);
            }

            return redirect()->route('system.roles.edit', $role->id)
                           ->with('success', 'Role created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error creating role: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error creating role: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        // CRITICAL: Clear any cached relationships and reload fresh data
        $role = Role::with(['users', 'rolePermissions.permission', 'createdBy', 'updatedBy'])
                    ->findOrFail($id);
        
        // Force reload relationships to ensure we have the latest data
        $role->load(['rolePermissions.permission']);
        $role->refresh();

        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $role
            ]);
        }

        $menuItems = $this->getGroupedPermissions($role, true);

        $moduleIcons = [
            'marketing' => 'fas fa-bullhorn',
            'operational' => 'fas fa-file-signature',
            'finance' => 'fas fa-calculator',
            'warehouse' => 'fas fa-cube',
            'company' => 'fas fa-building',
            'system' => 'fas fa-gear',
            'report' => 'fas fa-chart-line',
            'other' => 'fas fa-ellipsis-h'
        ];

        return view('system.roles.show', compact('role', 'menuItems', 'moduleIcons'));
    }

    private function getGroupedPermissions($role, $isShowView = false)
    {
        // 1. Load Role Permissions
        $role->load('rolePermissions.permission');
        $rolePermissionIds = $role->rolePermissions->pluck('permission_id')->toArray();
        $rolePermissionsCollection = $role->rolePermissions;

        // 2. Get All Active Permissions (plus role's permissions even if inactive)
        $allPermissions = Permission::where(function($q) use ($rolePermissionIds) {
                $q->where('is_active', true)
                  ->orWhereIn('id', $rolePermissionIds);
            })
            ->orderBy('name')
            ->get()
            ->unique('id');

        // 3. Define Sidebar Structure Map
        // Map permission prefix/resource -> Module Name (Strict matching from app.blade.php)
        $moduleMap = [
            // Marketing (Grouped as per app.blade.php)
            'marketing' => 'Marketing',
            'company.customers' => 'Marketing',
            'company.customer-contacts' => 'Marketing',
            'company.customer-taxes' => 'Marketing',
            'system.customer-types' => 'Marketing',
            'company.company-virtual-accounts' => 'Marketing',
            'system.salutations' => 'Marketing',
            'operational.buildings' => 'Marketing',
            'operational.master-buildings' => 'Marketing',
            
            // Operational
            'operational' => 'Operational',
            'operational.job-schedules' => 'Operational',
            'operational.job-assign' => 'Operational',
            'operational.job-assign-material-issues' => 'Operational',
            'operational.teams' => 'Operational',
            'operational.master-rooms' => 'Operational',
            'operational.room-rental-units' => 'Operational',
            
            // Finance
            'finance' => 'Finance',
            'finance.invoices' => 'Finance',
            'finance.invoice-follow-ups' => 'Finance',
            'finance.tax-file-imports' => 'Finance',
            'finance.tax-file-exports' => 'Finance',
            'finance.tax-settings' => 'Finance',
            'finance.tax-codes' => 'Finance',
            'finance.commissions' => 'Finance',
            'finance.achievements' => 'Finance',
            'finance.achievement-periods' => 'Finance',
            'finance.commission-levels' => 'Finance',
            'finance.marketing-levels' => 'Finance',
            'finance.cr-variables' => 'Finance',
            'finance.marketing-targets' => 'Finance',
            'finance.renewal-contract-assignments' => 'Finance',
            'finance.commission-transfers' => 'Finance',
            'finance.commission-payments' => 'Finance',
            
            // Warehouse
            'warehouse' => 'Warehouse',
            'warehouse.warehouses' => 'Warehouse',
            'warehouse.product-structure' => 'Warehouse',
            'warehouse.product-types' => 'Warehouse',
            'warehouse.brand-variants' => 'Warehouse',
            'warehouse.master-products' => 'Warehouse',
            'warehouse.master-rentals' => 'Warehouse',
            'warehouse.inventory-issuings' => 'Warehouse',
            'warehouse.inventory-receivings' => 'Warehouse',
            'warehouse.inventory-requests' => 'Warehouse',
            'warehouse.stock-opnames' => 'Warehouse',
            'warehouse.stock-adjustments' => 'Warehouse',
            'warehouse.serial-numbers' => 'Warehouse',
            'warehouse.unit-on-walls' => 'Warehouse',
            'other.unit-on-walls' => 'Warehouse',
            
            // Company
            'company.branches' => 'Company',
            'company.master-banks' => 'Company',
            'company.bank-payments' => 'Company',
            'company.master-price-slabs' => 'Company',
            'company.companies' => 'Company',
            'system.positions' => 'Company',
            'other.master-options' => 'Company',
            'company' => 'Company',
            
            // System
            'system.departments' => 'System',
            'system.users' => 'System',
            'system.roles' => 'System',
            'access-control' => 'System',
            'system.provinces' => 'System',
            'audit-trails' => 'System',
            'system' => 'System',
            
            // Report
            'reports' => 'Report',
            'report' => 'Report',
        ];

        // 4. Initialize Menu Items Structure (Ordered as per app.blade.php)
        $menuItems = [
            'Marketing' => [],
            'Operational' => [],
            'Finance' => [],
            'Warehouse' => [],
            'Company' => [],
            'System' => [],
            'Report' => [],
            'Other' => []
        ];

        // 5. Process Permissions
        foreach ($allPermissions as $permission) {
            $permissionName = $permission->name;
            
            // DONT process 'other.' permissions that are likely garbage (duplicates)
            // EXCEPT for master-options which is legitimate
            $isOtherPrefix = str_starts_with(strtolower($permissionName), 'other.');
            $isMasterOptions = str_contains(strtolower($permissionName), 'master-options');
            $isUnitOnWall = str_contains(strtolower($permissionName), 'unit-on-wall');
            
            if ($isOtherPrefix && !$isMasterOptions && !$isUnitOnWall) {
                // Garbage 'other.' permission - SKIP IT to prevent duplicates
                continue;
            }

            // FILTER SPECIFIC SINGULAR DUPLICATES (caused by previous bugs/manual entry)
            // We use plural 'master-products' and 'master-rentals', so skip the singulars.
            // NOTE: We keep marketing.company.customers.* visible because it's the main permission set
            if (str_contains($permissionName, 'warehouse.master-product.') || 
                str_contains($permissionName, 'warehouse.master-rental.') ||
                str_contains($permissionName, 'warehouse.product-type.') ||
                str_contains($permissionName, 'warehouse.unit-on-wall.') ||
                str_contains($permissionName, 'warehouse.warehouse-material-issue.') ||
                str_contains($permissionName, 'marketing.customers.') || // Skip this duplicate, use marketing.company.customers instead
                str_starts_with($permissionName, 'customers.')) {
                continue;
            }

            $parts = explode('.', $permissionName);
            
            // Determine Module
            $module = 'Other';
            $matchedLength = 0;

            foreach ($moduleMap as $prefix => $targetModule) {
                if (str_starts_with($permissionName, $prefix)) {
                    if (strlen($prefix) > $matchedLength) {
                        $module = $targetModule;
                        $matchedLength = strlen($prefix);
                    }
                }
            }

            // Fallback for permissions without prefix (e.g. "invoices.view")
            if ($module === 'Other') {
                foreach ($moduleMap as $prefix => $targetModule) {
                    $partsPrefix = explode('.', $prefix);
                    $baseResource = end($partsPrefix);
                    if (str_starts_with($permissionName, $baseResource)) {
                        $module = $targetModule;
                        break;
                    }
                }
            }

            // Determine Resource and Action
            $resourceLabel = '';
            $originalResource = '';
            $action = 'view';
            $isActionOnly = false;
            $actionKeywords = ['create', 'update', 'edit', 'delete', 'remove', 'view', 'read', 'show', 'add', 'ubah', 'hapus', 'lihat', 'download', 'unduh', 'print', 'cetak', 'approve', 'setuju', 'dashboard'];

            if (count($parts) >= 2) {
                $lastPart = end($parts);
                if (in_array(strtolower($lastPart), $actionKeywords)) {
                    $action = strtolower($lastPart);
                    $resourceParts = array_slice($parts, 0, -1);
                    $originalResource = implode('.', $resourceParts);
                    
                    // Specific mapping for nice labels
                    $fullResourceName = $originalResource;
                    
                    // Remove module prefix for label matching
                    $cleanResource = $fullResourceName;
                    if (str_contains($fullResourceName, '.')) {
                        $parts_temp = explode('.', $fullResourceName);
                        $cleanResource = end($parts_temp);
                    }

                    if ($cleanResource === 'buildings' || $cleanResource === 'master-buildings') {
                        $resourceLabel = 'Master Building';
                    } elseif ($cleanResource === 'customers') {
                        $resourceLabel = 'Master Customer';
                    } elseif ($cleanResource === 'customer-contacts') {
                        $resourceLabel = 'Customer Contacts';
                    } elseif ($cleanResource === 'customer-taxes') {
                        $resourceLabel = 'Master Customer Tax';
                    } elseif ($cleanResource === 'customer-types') {
                        $resourceLabel = 'Master Customer Category';
                    } elseif ($cleanResource === 'company-virtual-accounts') {
                        $resourceLabel = 'Company Virtual Account';
                    } elseif ($cleanResource === 'salutations') {
                        $resourceLabel = 'Master Salutation';
                    } elseif ($cleanResource === 'aroma-changes') {
                        $resourceLabel = 'Aroma Switching';
                    } elseif ($cleanResource === 'contract-terminations') {
                        $resourceLabel = 'Contract Termination';
                    } elseif ($cleanResource === 'contract-assigned') {
                        $resourceLabel = 'Contract Assigned';
                    } elseif ($cleanResource === 'contract-switchings') {
                        $resourceLabel = 'Contract Switching';
                    } elseif ($cleanResource === 'contract-net') {
                        $resourceLabel = 'Contract Net';
                    } elseif ($cleanResource === 'lost-unit-reports') {
                        $resourceLabel = 'Lost Unit Report';
                    } elseif ($cleanResource === 'job-advices') {
                        $resourceLabel = 'Job Advice';
                    } elseif ($cleanResource === 'unit-on-walls') {
                        $resourceLabel = 'Unit On Wall';
                    } elseif ($cleanResource === 'positions') {
                        $resourceLabel = 'Master Position';
                    } elseif ($cleanResource === 'master-options') {
                        $resourceLabel = 'Master Options';
                    } elseif ($cleanResource === 'branches') {
                         $resourceLabel = 'Master Branch';
                    } elseif ($cleanResource === 'master-banks') {
                         $resourceLabel = 'Master Bank';
                    } elseif ($cleanResource === 'bank-payments') {
                         $resourceLabel = 'Bank Payment';
                    } elseif ($cleanResource === 'master-price-slabs') {
                         $resourceLabel = 'Master Price Slab';
                    } elseif ($cleanResource === 'companies') {
                         $resourceLabel = 'Master Company';
                    } elseif ($cleanResource === 'job-schedules') {
                         $resourceLabel = 'Job Schedule';
                    } elseif (str_starts_with($cleanResource, 'job-schedules-')) {
                         $actionGroup = [
                             'suspend' => 'Suspend',
                             'dpf' => 'DPF',
                             'unpost-ba' => 'Unpost BA',
                             'unpost-issue' => 'Unpost Issue',
                             'unassign-team' => 'Unassign Team',
                             'material-assign' => 'Material Assign',
                             'unassign-material' => 'Unassign Material',
                             'print' => 'Print',
                             'assign-team' => 'Assign Team',
                         ];
                         $actionKey = str_replace('job-schedules-', '', $cleanResource);
                         $resourceLabel = 'Job Schedule (' . ($actionGroup[$actionKey] ?? ucwords(str_replace('-', ' ', $actionKey))) . ')';
                         $isActionOnly = true;
                    } elseif ($cleanResource === 'job-schedules-approve-ba') {
                         $resourceLabel = 'Job Schedule (Approve BA)';
                    } elseif ($cleanResource === 'job-schedules-approve-material-return') {
                         $resourceLabel = 'Job Schedule (Approve Material Return)';
                    } elseif ($cleanResource === 'job-assign-material-issues') {
                         $resourceLabel = 'Job Material Issue';
                    } elseif ($cleanResource === 'material-issues') {
                         $resourceLabel = 'Warehouse Material Issue';
                    } elseif ($cleanResource === 'teams') {
                         $resourceLabel = 'Master Team';
                    } elseif ($cleanResource === 'master-rooms') {
                         $resourceLabel = 'Master Room';
                    } elseif ($cleanResource === 'invoices') {
                         $resourceLabel = 'Invoice';
                    } elseif ($cleanResource === 'product-types') {
                         $resourceLabel = 'Product Type';
                    } elseif ($cleanResource === 'brand-variants') {
                         $resourceLabel = 'Brand Variant';
                    } elseif ($cleanResource === 'master-products') {
                         $resourceLabel = 'Master Product';
                    } elseif ($cleanResource === 'master-rentals') {
                         $resourceLabel = 'Master Rental';
                    } elseif ($cleanResource === 'contract-files') {
                         $resourceLabel = 'Contract Files';
                    } elseif ($cleanResource === 'room-rental-units') {
                         $resourceLabel = 'Room Rental Units';
                    } elseif ($cleanResource === 'commissions-dashboard') {
                         $resourceLabel = 'Commissions Dashboard';
                    } elseif ($cleanResource === 'quotations') {
                         $resourceLabel = 'Quotation';
                    } elseif ($cleanResource === 'surveys') {
                         $resourceLabel = 'Survey';
                    } elseif ($cleanResource === 'contracts') {
                         $resourceLabel = 'Contracts';
                    } elseif ($cleanResource === 'pipeline') {
                         $resourceLabel = 'Pipeline';
                    } elseif ($cleanResource === 'dashboard') {
                         $resourceLabel = 'Dashboard';
                    } else {
                        // Generic formatting
                        $tempParts = $resourceParts;
                        if ($module !== 'Other' && str_starts_with(strtolower($originalResource), strtolower($module) . '.')) {
                            array_shift($tempParts);
                        }
                        if (str_starts_with(strtolower($originalResource), 'other.')) {
                            array_shift($tempParts);
                        }

                        $resourceLabel = implode(' ', $tempParts);
                        $resourceLabel = str_replace(['-', '_'], ' ', $resourceLabel);
                        $resourceLabel = ucwords($resourceLabel);
                    }
                } else {
                    $originalResource = implode('.', array_slice($parts, 0));
                    
                    // Specific mapping for special long-named actions
                    if (str_ends_with($originalResource, '.approve-contract-net')) {
                        $action = 'approve';
                        $resourceParts = explode('.', $originalResource);
                        array_pop($resourceParts); // remove approve-contract-net
                        $originalResource = implode('.', $resourceParts);
                        $resourceLabel = 'Contracts';
                    } else {
                        $resourceLabel = ucwords(str_replace(['.', '-', '_'], ' ', $originalResource));
                    }
                }
            } else {
                $originalResource = $permissionName;
                $resourceLabel = ucwords(str_replace(['.', '-', '_'], ' ', $permissionName));
            }

            // Normalize Resource Key - VERY IMPORTANT: must match database resource name
            // If the permission is 'marketing.surveys.view', the resource segment is 'surveys'
            // We want the key to be just 'surveys' if it's already prefixed, or the full thing if not.
            $resourceKey = $originalResource;
            
            // Remove module prefix from resourceKey to avoid double prefixing in JS
            if ($module !== 'Other' && str_starts_with(strtolower($resourceKey), strtolower($module) . '.')) {
                $resourceKey = substr($resourceKey, strlen($module) + 1);
            }
            if (str_starts_with(strtolower($resourceKey), 'other.')) {
                $resourceKey = substr($resourceKey, 6);
            }

            // Normalize Action
            $normalizedAction = 'view';
            if (in_array($action, ['create', 'add', 'tambah'])) $normalizedAction = 'create';
            elseif (in_array($action, ['update', 'edit', 'ubah'])) $normalizedAction = 'update';
            elseif (in_array($action, ['delete', 'remove', 'hapus'])) $normalizedAction = 'delete';
            elseif (in_array($action, ['view', 'read', 'lihat', 'show'])) $normalizedAction = 'view';
            elseif (in_array($action, ['download', 'unduh'])) $normalizedAction = 'download';
            elseif (in_array($action, ['print', 'cetak'])) $normalizedAction = 'print';
            elseif (in_array($action, ['approve', 'setuju'])) $normalizedAction = 'approve';
            elseif ($action === 'dashboard') $normalizedAction = 'view';

            if (!isset($menuItems[$module][$resourceKey])) {
                $menuItems[$module][$resourceKey] = [
                    'name' => $resourceLabel,
                    'is_action_only' => $isActionOnly ?? false,
                    'permissions' => [
                        'active' => false,
                        'create' => false,
                        'update' => false,
                        'delete' => false,
                        'view' => false,
                        'approve' => false,
                        'download' => false,
                        'print' => false,
                        'ids' => [
                            'create' => null, 'update' => null, 'delete' => null, 'view' => null, 
                            'approve' => null, 'download' => null, 'print' => null
                        ]
                    ]
                ];
            }

            $hasPermission = in_array($permission->id, $rolePermissionIds);

            if ($hasPermission) {
                $menuItems[$module][$resourceKey]['permissions'][$normalizedAction] = true;
                $menuItems[$module][$resourceKey]['permissions']['active'] = true;
            }

            $menuItems[$module][$resourceKey]['permissions']['ids'][$normalizedAction] = $permission->id;
        }

        return $menuItems;
    }

    public function edit($id)
    {
        // CRITICAL: Clear any cached relationships and reload fresh data
        $role = Role::with(['rolePermissions.permission'])->findOrFail($id);
        
        // Force reload relationships to ensure we have the latest data
        $role->load(['rolePermissions.permission']);
        $role->refresh();
        
        $permissions = Permission::where('is_active', true)->get();
        $users = User::where('is_active', true)->get();

        $moduleIcons = [
            'marketing' => 'fas fa-bullhorn',
            'operational' => 'fas fa-file-signature',
            'finance' => 'fas fa-calculator',
            'warehouse' => 'fas fa-cube',
            'company' => 'fas fa-building',
            'system' => 'fas fa-gear',
            'report' => 'fas fa-chart-line',
            'other' => 'fas fa-ellipsis-h'
        ];

        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $role,
                'permissions' => $permissions,
                'users' => $users
            ]);
        }

        $menuItems = $this->getGroupedPermissions($role, false);

        return view('system.roles.edit', compact('role', 'permissions', 'users', 'menuItems', 'moduleIcons'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        // ROBUST APPROACH: Process checkbox_states to create missing permissions
        $newPermissionIds = [];
        
        // ROBUST APPROACH: Process role_permissions_json to create missing permissions
        $newPermissionIds = [];
        
        // Check if role_permissions_json exists (Primary Source)
        $jsonInput = $request->input('role_permissions_json');
        
        // Fallback to checkbox_states if role_permissions_json is missing (Backward Compatibility)
        if (empty($jsonInput) && $request->has('checkbox_states')) {
            $jsonInput = $request->input('checkbox_states');
        }

        if (!empty($jsonInput)) {
            $checkboxStatesRaw = $jsonInput;
            
            if (!empty($checkboxStatesRaw) && $checkboxStatesRaw !== '{}' && $checkboxStatesRaw !== 'null') {
                try {
                    $checkboxStates = is_array($checkboxStatesRaw) ? $checkboxStatesRaw : json_decode($checkboxStatesRaw, true);
                    
                    if (is_array($checkboxStates) && !empty($checkboxStates)) {
                        // Track processed permission IDs to avoid duplicates
                        $processedPermissionIds = [];
                        
                        foreach ($checkboxStates as $permissionKey => $state) {
                            // Only process checked checkboxes
                            if (!isset($state['checked']) || !$state['checked']) {
                                continue;
                            }
                            
                            // If permission already has an ID, use it
                            if (isset($state['has_existing_id']) && $state['has_existing_id'] && isset($state['existing_id'])) {
                                $permissionId = (int)$state['existing_id'];
                                
                                // CRITICAL: Only add if not already processed (prevent duplicates)
                                if (!in_array($permissionId, $processedPermissionIds)) {
                                    $newPermissionIds[] = $permissionId;
                                    $processedPermissionIds[] = $permissionId;
                                } else {
                                    \Log::warning('Skipping duplicate permission ID', [
                                        'permission_key' => $permissionKey,
                                        'existing_id' => $permissionId
                                    ]);
                                }
                                continue;
                            }
                            
                            // Create permission if it doesn't exist
                            $permissionName = strtolower($permissionKey); // Format: module.resource.action
                            
                            // CRITICAL: Prevent creating 'other.' permissions for known resources
                            // This stops the loop of garbage permission creation
                            if (str_starts_with($permissionName, 'other.') && 
                                !str_contains($permissionName, 'master-options') && 
                                !str_contains($permissionName, 'unit-on-wall')) {
                                \Log::info('Blocked creation of garbage Other permission', ['name' => $permissionName]);
                                continue;
                            }

                            $description = isset($state['menu_name']) 
                                ? ucfirst($state['action'] ?? 'access') . ' ' . $state['menu_name']
                                : $permissionName;
                            
                            $permission = \App\Models\Permission::firstOrCreate(
                                ['name' => $permissionName],
                                [
                                    'description' => $description,
                                    'system_reserved' => false,
                                    'is_active' => true,
                                    'created_by' => Auth::id()
                                ]
                            );
                            
                            // CRITICAL: Only add if not already processed (prevent duplicates)
                            if (!in_array($permission->id, $processedPermissionIds)) {
                                $newPermissionIds[] = $permission->id;
                                $processedPermissionIds[] = $permission->id;
                            } else {
                                \Log::warning('Skipping duplicate permission ID (from firstOrCreate)', [
                                    'permission_key' => $permissionKey,
                                    'permission_id' => $permission->id
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Error processing checkbox_states: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                        'raw_data' => $checkboxStatesRaw
                    ]);
                }
            }
        }
        
        // Handle new_permissions that need to be created FIRST (before validation) - BACKWARD COMPATIBILITY
        // Check if new_permissions exists in request (even if empty string)
        if ($request->has('new_permissions')) {
            $newPermsRaw = $request->input('new_permissions');
            
            if (!empty($newPermsRaw) && $newPermsRaw !== '[]' && $newPermsRaw !== 'null') {
                try {
                    $newPermissionsData = json_decode($newPermsRaw, true);
                    
                    if (is_array($newPermissionsData) && !empty($newPermissionsData)) {
                        foreach ($newPermissionsData as $newPermData) {
                            if (!isset($newPermData['name'])) {
                                \Log::warning('Skipping invalid permission data', ['data' => $newPermData]);
                                continue;
                            }
                            
                            // Create permission if it doesn't exist
                            $permission = \App\Models\Permission::firstOrCreate(
                                ['name' => $newPermData['name']],
                                [
                                    'description' => $newPermData['description'] ?? $newPermData['name'],
                                    'system_reserved' => false,
                                    'is_active' => true,
                                    'created_by' => Auth::id()
                                ]
                            );
                            $newPermissionIds[] = $permission->id;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Error creating new permissions: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                        'raw_data' => $newPermsRaw
                    ]);
                }
            }
        }
        
        // CRITICAL FIX: Use checkbox_states as the single source of truth
        // If checkbox_states was processed (newPermissionIds from checkbox_states), use ONLY those IDs
        // This prevents duplication because checkbox_states already contains ALL checked permissions
        $allPermissionIds = [];
        
        if (!empty($newPermissionIds)) {
            // checkbox_states was processed - use ONLY those IDs (no merge with request->permissions)
            // This is because checkbox_states already contains all checked permissions including existing ones
            // Remove duplicates first (in case same permission ID was added multiple times)
            $allPermissionIds = array_unique($newPermissionIds);
            $allPermissionIds = array_values($allPermissionIds);
            $allPermissionIds = array_map('intval', $allPermissionIds);
        } else {
            // Fallback: use request permissions if checkbox_states not available
            $existingPermissionIds = [];
            if ($request->has('permissions') && is_array($request->permissions)) {
                foreach ($request->permissions as $id) {
                    // Handle both array values and direct values
                    $permissionId = is_array($id) ? ($id['value'] ?? $id['id'] ?? null) : $id;
                    
                    if (!empty($permissionId) && (is_numeric($permissionId) || ctype_digit((string)$permissionId))) {
                        $existingPermissionIds[] = (int)$permissionId;
                    }
                }
            }
            $allPermissionIds = array_unique($existingPermissionIds);
            $allPermissionIds = array_values($allPermissionIds);
            $allPermissionIds = array_map('intval', $allPermissionIds);
        }
        
        // Replace request permissions with merged permissions for validation
        // Ensure it's a simple array, not associative
        $request->merge(['permissions' => array_values($allPermissionIds)]);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id . ',id',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
            'system_reserved' => 'required|in:0,1,true,false',
            'is_active' => 'required|in:0,1,true,false'
        ]);

        if ($validator->fails()) {
            \Log::error('Role validation failed:', [
                'errors' => $validator->errors(),
                'input' => $request->all(),
                'json_input' => $request->json()->all(),
                'all_permission_ids' => $allPermissionIds,
                'new_permission_ids' => $newPermissionIds,
            ]);
            
            // Return proper response based on request type
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()
                           ->withInput()
                           ->withErrors($validator)
                           ->with('error', 'Validation failed. Please check your input.');
        }

        try {
            DB::beginTransaction();

            $role->update([
                'name' => $request->name,
                'description' => $request->description,
                'permissions' => $request->permissions ?? [],
                'system_reserved' => $request->system_reserved === '1' || $request->system_reserved === 'true' || $request->system_reserved === true,
                'is_active' => $request->is_active === '1' || $request->is_active === 'true' || $request->is_active === true,
                'updated_by' => Auth::id()
            ]);

            // Sync role permissions (including newly created ones)
            // Use the merged permissions from request (which includes new ones)
            $finalPermissionIds = $allPermissionIds; // Use the merged array that includes new permissions
            
            // Always delete existing permissions first
            $deletedCount = $role->rolePermissions()->delete();
            
            if (!empty($finalPermissionIds)) {
                $createdCount = 0;
                $failedCount = 0;
                $errors = [];
                
                foreach ($finalPermissionIds as $permissionId) {
                    if (!empty($permissionId) && is_numeric($permissionId)) {
                        try {
                            // Verify permission exists
                            $permission = \App\Models\Permission::find($permissionId);
                            if (!$permission) {
                                \Log::warning('Permission not found when creating role permission', [
                                    'role_id' => $role->id,
                                    'permission_id' => $permissionId
                                ]);
                                $failedCount++;
                                continue;
                            }
                            
                            $rolePermission = $role->rolePermissions()->create([
                                'permission_id' => (int)$permissionId
                            ]);
                            
                            if ($rolePermission) {
                                $createdCount++;
                            } else {
                                $failedCount++;
                                $errors[] = "Failed to create role permission for permission_id: {$permissionId}";
                            }
                        } catch (\Exception $e) {
                            $failedCount++;
                            $errors[] = $e->getMessage();
                            \Log::warning('Error creating role permission', [
                                'role_id' => $role->id,
                                'permission_id' => $permissionId,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    } else {
                        \Log::warning('Invalid permission ID in finalPermissionIds', [
                            'role_id' => $role->id,
                            'permission_id' => $permissionId,
                            'type' => gettype($permissionId)
                        ]);
                        $failedCount++;
                    }
                }
            } else {
                \Log::warning('No permissions to sync', [
                    'role_id' => $role->id,
                    'final_permission_ids' => $finalPermissionIds,
                    'checkbox_states_processed' => !empty($newPermissionIds),
                    'request_has_permissions' => $request->has('permissions')
                ]);
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Role updated successfully',
                    'data' => $role
                ]);
            }

            // Reload role with fresh permissions to ensure data is up to date
            $role->refresh();
            $role->load('rolePermissions.permission');
            
            return redirect()->route('system.roles.show', $role->id)
                           ->with('success', 'Role updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error updating role: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error updating role: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        // Check if role is system reserved
        if ($role->system_reserved) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'System reserved roles cannot be deleted'
                ], 403);
            }

            return redirect()->back()
                           ->with('error', 'System reserved roles cannot be deleted');
        }

        // Check if role is being used by users
        if ($role->users()->count() > 0) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role that is assigned to users'
                ], 403);
            }

            return redirect()->back()
                           ->with('error', 'Cannot delete role that is assigned to users');
        }

        try {
            $role->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role deleted successfully'
                ]);
            }

            return redirect()->route('system.roles.index')
                           ->with('success', 'Role deleted successfully');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting role: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Error deleting role: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:roles,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $roles = Role::whereIn('id', $request->ids)
                        ->where('system_reserved', false)
                        ->whereDoesntHave('users')
                        ->get();

            if ($roles->count() !== count($request->ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some roles cannot be deleted (system reserved or in use)'
                ], 403);
            }

            $count = Role::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} role(s)",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting roles: ' . $e->getMessage()
            ], 500);
        }
    }

    public function activate($id)
    {
        $role = Role::findOrFail($id);

        try {
            $role->update([
                'is_active' => true,
                'updated_by' => Auth::id()
            ]);

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role activated successfully'
                ]);
            }

            return redirect()->back()
                           ->with('success', 'Role activated successfully');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error activating role: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Error activating role: ' . $e->getMessage());
        }
    }

    public function deactivate($id)
    {
        $role = Role::findOrFail($id);

        // Check if role is system reserved
        if ($role->system_reserved) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'System reserved roles cannot be deactivated'
                ], 403);
            }

            return redirect()->back()
                           ->with('error', 'System reserved roles cannot be deactivated');
        }

        try {
            $role->update([
                'is_active' => false,
                'updated_by' => Auth::id()
            ]);

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role deactivated successfully'
                ]);
            }

            return redirect()->back()
                           ->with('success', 'Role deactivated successfully');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deactivating role: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Error deactivating role: ' . $e->getMessage());
        }
    }

    public function assignUsers(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $role->users()->sync($request->user_ids);

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Users assigned to role successfully'
                ]);
            }

            return redirect()->back()
                           ->with('success', 'Users assigned to role successfully');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error assigning users: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                           ->with('error', 'Error assigning users: ' . $e->getMessage());
        }
    }

    public function duplicate($id)
    {
        $sourceRole = Role::findOrFail($id);

        try {
            DB::beginTransaction();

            // Create new role name with unique check
            $newName = $sourceRole->name . ' (Copy)';
            $checkName = Role::where('name', $newName)->exists();
            $counter = 1;
            while ($checkName) {
                $newName = $sourceRole->name . ' (Copy ' . $counter . ')';
                $checkName = Role::where('name', $newName)->exists();
                $counter++;
            }

            $newRole = Role::create([
                'name' => $newName,
                'description' => $sourceRole->description,
                'permissions' => $sourceRole->permissions ?? [], // Deprecated but kept
                'system_reserved' => false, // New copies aren't system reserved
                'is_active' => $sourceRole->is_active,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Copy permissions
            foreach ($sourceRole->rolePermissions as $rolePermission) {
                $newRole->rolePermissions()->create([
                    'permission_id' => $rolePermission->permission_id
                ]);
            }

            DB::commit();

            return redirect()->route('system.roles.edit', $newRole->id)
                           ->with('success', 'Role duplicated successfully. You can now edit the copy.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                           ->with('error', 'Error duplicating role: ' . $e->getMessage());
        }
    }

    public function export()
    {
        $roles = Role::with(['users', 'createdBy'])->get();

        $csvData = [];
        $csvData[] = ['Name', 'Description', 'Permissions', 'Users Count', 'Status', 'Created By', 'Created At'];

        foreach ($roles as $role) {
            $csvData[] = [
                $role->name,
                $role->description,
                implode(', ', $role->permissions ?? []),
                $role->users->count(),
                $role->is_active ? 'Active' : 'Inactive',
                $role->createdBy->name ?? 'N/A',
                $role->created_at->format('Y-m-d H:i:s')
            ];
        }

        $filename = 'roles_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}

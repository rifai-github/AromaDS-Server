<?php

namespace App\Http\Controllers\Other;

use App\Http\Controllers\Controller;
use App\Models\AccessManagement;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HakAksesController extends Controller
{
    public function index()
    {
        $accessList = AccessManagement::with(['user', 'department'])
            ->orderBy('created_at', 'desc')
            ->paginateStd(25);

        return view('other.hak-akses.index', compact('accessList'));
    }

    public function create()
    {
        $users = User::where('status', 'active')->get();
        $departments = Department::where('system_reserved', false)->get();
        $accessTypes = ['Full Access', 'Read Only', 'Limited Access'];
        $modules = [
            'System' => ['Users', 'Departments', 'Provinces', 'Notifications'],
            'Company' => ['Branches', 'Companies', 'Customers', 'Customer Tax', 'Bank Payments', 'Price Slabs'],
            'Finance' => ['Invoices', 'Contracts', 'Quotations', 'Bank Receipts', 'Tax Settings', 'Virtual Accounts', 'Faktur Pajak'],
            'Warehouse' => ['Products', 'Warehouses', 'Stock Opnames', 'Stock Adjustments', 'Inventory', 'Product Types', 'Master Rental', 'Serial Numbers'],
            'Marketing' => ['Prospects', 'Surveys', 'Quotations', 'Contracts', 'Job Advice', 'Lost Unit Reports', 'Sales Activities'],
            'Operational' => ['Job Schedules', 'Buildings', 'Teams', 'Master Rooms', 'Room Rental Units', 'Unit on Wall'],
            'Reports' => ['Warehouse Reports', 'Operational Reports', 'Finance Reports', 'Marketing Reports'],
            'Settings' => ['Theme Settings'],
        ];

        return view('other.hak-akses.create', compact('users', 'departments', 'accessTypes', 'modules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
            'access_type' => 'required|string|max:50',
            'module_access' => 'required|array',
            'module_access.*' => 'string|max:100',
            'status' => 'required|in:active,inactive',
            'granted_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:granted_date',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Check if user already has active access for this department
            $existingAccess = AccessManagement::where('user_id', $request->user_id)
                ->where('department_id', $request->department_id)
                ->where('status', 'active')
                ->first();

            if ($existingAccess) {
                return back()->with('error', 'User already has active access for this department.');
            }

            $access = AccessManagement::create([
                'user_id' => $request->user_id,
                'department_id' => $request->department_id,
                'access_type' => $request->access_type,
                'module_access' => json_encode($request->module_access),
                'status' => $request->status,
                'granted_date' => $request->granted_date,
                'expiry_date' => $request->expiry_date,
                'remarks' => $request->remarks,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('other.hak-akses.index')
                ->with('success', 'Access permission created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to create access permission: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $access = AccessManagement::with(['user', 'department', 'createdBy'])->findOrFail($id);
        $moduleAccess = json_decode($access->module_access, true);
        
        return view('other.hak-akses.show', compact('access', 'moduleAccess'));
    }

    public function edit($id)
    {
        $access = AccessManagement::findOrFail($id);
        $users = User::where('status', 'active')->get();
        $departments = Department::where('system_reserved', false)->get();
        $accessTypes = ['Full Access', 'Read Only', 'Limited Access'];
        $modules = [
            'System' => ['Users', 'Departments', 'Provinces', 'Notifications'],
            'Company' => ['Branches', 'Companies', 'Customers', 'Customer Tax', 'Bank Payments', 'Price Slabs'],
            'Finance' => ['Invoices', 'Contracts', 'Quotations', 'Bank Receipts', 'Tax Settings', 'Virtual Accounts', 'Faktur Pajak'],
            'Warehouse' => ['Products', 'Warehouses', 'Stock Opnames', 'Stock Adjustments', 'Inventory', 'Product Types', 'Master Rental', 'Serial Numbers'],
            'Marketing' => ['Prospects', 'Surveys', 'Quotations', 'Contracts', 'Job Advice', 'Lost Unit Reports', 'Sales Activities'],
            'Operational' => ['Job Schedules', 'Buildings', 'Teams', 'Master Rooms', 'Room Rental Units', 'Unit on Wall'],
            'Reports' => ['Warehouse Reports', 'Operational Reports', 'Finance Reports', 'Marketing Reports'],
            'Settings' => ['Theme Settings'],
        ];

        return view('other.hak-akses.edit', compact('access', 'users', 'departments', 'accessTypes', 'modules'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
            'access_type' => 'required|string|max:50',
            'module_access' => 'required|array',
            'module_access.*' => 'string|max:100',
            'status' => 'required|in:active,inactive',
            'granted_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:granted_date',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $access = AccessManagement::findOrFail($id);
            
            // Check if user already has active access for this department (excluding current record)
            $existingAccess = AccessManagement::where('user_id', $request->user_id)
                ->where('department_id', $request->department_id)
                ->where('status', 'active')
                ->where('id', '!=', $id)
                ->first();

            if ($existingAccess) {
                return back()->with('error', 'User already has active access for this department.');
            }

            $access->update([
                'user_id' => $request->user_id,
                'department_id' => $request->department_id,
                'access_type' => $request->access_type,
                'module_access' => json_encode($request->module_access),
                'status' => $request->status,
                'granted_date' => $request->granted_date,
                'expiry_date' => $request->expiry_date,
                'remarks' => $request->remarks,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('other.hak-akses.index')
                ->with('success', 'Access permission updated successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to update access permission: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $access = AccessManagement::findOrFail($id);
            $access->delete();

            return redirect()->route('other.hak-akses.index')
                ->with('success', 'Access permission deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete access permission: ' . $e->getMessage());
        }
    }

    public function revoke($id)
    {
        try {
            DB::beginTransaction();

            $access = AccessManagement::findOrFail($id);
            $access->update([
                'status' => 'inactive',
                'revoked_date' => now(),
                'revoked_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('other.hak-akses.index')
                ->with('success', 'Access permission revoked successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to revoke access permission: ' . $e->getMessage());
        }
    }

    public function renew($id)
    {
        try {
            DB::beginTransaction();

            $access = AccessManagement::findOrFail($id);
            $access->update([
                'status' => 'active',
                'granted_date' => now(),
                'expiry_date' => now()->addYear(),
                'renewed_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('other.hak-akses.index')
                ->with('success', 'Access permission renewed successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to renew access permission: ' . $e->getMessage());
        }
    }

    // API Methods
    public function getUserAccess($userId)
    {
        $access = AccessManagement::with(['department'])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $access
        ]);
    }

    public function getDepartmentAccess($departmentId)
    {
        $access = AccessManagement::with(['user'])
            ->where('department_id', $departmentId)
            ->where('status', 'active')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $access
        ]);
    }

    public function checkUserPermission(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'module' => 'required|string',
            'action' => 'required|string',
        ]);

        $access = AccessManagement::where('user_id', $request->user_id)
            ->where('status', 'active')
            ->where('module_access', 'like', '%' . $request->module . '%')
            ->first();

        $hasPermission = false;
        if ($access) {
            $moduleAccess = json_decode($access->module_access, true);
            $hasPermission = in_array($request->module, $moduleAccess);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_permission' => $hasPermission,
                'access_type' => $access ? $access->access_type : null,
            ]
        ]);
    }

    public function getAccessStatistics()
    {
        $stats = [
            'total_access' => AccessManagement::count(),
            'active_access' => AccessManagement::where('status', 'active')->count(),
            'inactive_access' => AccessManagement::where('status', 'inactive')->count(),
            'expired_access' => AccessManagement::where('expiry_date', '<', now())->count(),
            'full_access_users' => AccessManagement::where('access_type', 'Full Access')->count(),
            'read_only_users' => AccessManagement::where('access_type', 'Read Only')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}

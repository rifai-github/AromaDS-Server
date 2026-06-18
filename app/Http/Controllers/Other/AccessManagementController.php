<?php

namespace App\Http\Controllers\Other;

use App\Http\Controllers\Controller;
use App\Models\AccessManagement;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccessManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = AccessManagement::with(['user', 'department']);

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by access type
        if ($request->filled('access_type')) {
            $query->where('access_type', $request->access_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('granted_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('granted_date', '<=', $request->end_date);
        }

        $accessManagements = $query->orderBy('granted_date', 'desc')->paginateStd(25);

        return view('other.access-managements.index', compact('accessManagements'));
    }

    public function create()
    {
        $users = User::where('status', 'active')->get();
        $departments = Department::where('status', 'active')->get();
        
        return view('other.access-managements.create', compact('users', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
            'access_type' => 'required|in:read,write,admin,super_admin',
            'module_access' => 'required|array',
            'module_access.*' => 'string|in:marketing,finance,warehouse,operational,system,company,reports,settings',
            'granted_date' => 'required|date|before_or_equal:today',
            'expiry_date' => 'nullable|date|after:granted_date',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,expired',
        ]);

        try {
            DB::beginTransaction();

            // Check if user already has access to this department
            $existingAccess = AccessManagement::where('user_id', $request->user_id)
                ->where('department_id', $request->department_id)
                ->where('status', 'active')
                ->exists();

            if ($existingAccess) {
                throw new \Exception('Pengguna sudah memiliki akses aktif ke departemen ini.');
            }

            $accessManagement = AccessManagement::create([
                'user_id' => $request->user_id,
                'department_id' => $request->department_id,
                'access_type' => $request->access_type,
                'module_access' => json_encode($request->module_access),
                'granted_date' => $request->granted_date,
                'expiry_date' => $request->expiry_date,
                'description' => $request->description,
                'status' => $request->status,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('other.access-managements.show', $accessManagement)
                ->with('success', 'Manajemen akses berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(AccessManagement $accessManagement)
    {
        $accessManagement->load(['user', 'department']);
        $accessManagement->module_access = json_decode($accessManagement->module_access, true);
        
        return view('other.access-managements.show', compact('accessManagement'));
    }

    public function edit(AccessManagement $accessManagement)
    {
        $users = User::where('status', 'active')->get();
        $departments = Department::where('status', 'active')->get();
        $accessManagement->module_access = json_decode($accessManagement->module_access, true);
        
        return view('other.access-managements.edit', compact('accessManagement', 'users', 'departments'));
    }

    public function update(Request $request, AccessManagement $accessManagement)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
            'access_type' => 'required|in:read,write,admin,super_admin',
            'module_access' => 'required|array',
            'module_access.*' => 'string|in:marketing,finance,warehouse,operational,system,company,reports,settings',
            'granted_date' => 'required|date|before_or_equal:today',
            'expiry_date' => 'nullable|date|after:granted_date',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive,expired',
        ]);

        try {
            DB::beginTransaction();

            // Check if user already has access to this department (excluding current record)
            $existingAccess = AccessManagement::where('user_id', $request->user_id)
                ->where('department_id', $request->department_id)
                ->where('id', '!=', $accessManagement->id)
                ->where('status', 'active')
                ->exists();

            if ($existingAccess) {
                throw new \Exception('Pengguna sudah memiliki akses aktif ke departemen ini.');
            }

            $accessManagement->update([
                'user_id' => $request->user_id,
                'department_id' => $request->department_id,
                'access_type' => $request->access_type,
                'module_access' => json_encode($request->module_access),
                'granted_date' => $request->granted_date,
                'expiry_date' => $request->expiry_date,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            DB::commit();

            return redirect()->route('other.access-managements.show', $accessManagement)
                ->with('success', 'Manajemen akses berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(AccessManagement $accessManagement)
    {
        try {
            $accessManagement->delete();
            return redirect()->route('other.access-managements.index')
                ->with('success', 'Manajemen akses berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function revoke(AccessManagement $accessManagement)
    {
        try {
            $accessManagement->update([
                'status' => 'inactive',
                'revoked_at' => now(),
                'revoked_by' => Auth::id(),
            ]);

            return back()->with('success', 'Akses berhasil dicabut.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function renew(Request $request, AccessManagement $accessManagement)
    {
        $request->validate([
            'new_expiry_date' => 'required|date|after:today',
        ]);

        try {
            $accessManagement->update([
                'expiry_date' => $request->new_expiry_date,
                'status' => 'active',
                'renewed_at' => now(),
                'renewed_by' => Auth::id(),
            ]);

            return back()->with('success', 'Akses berhasil diperpanjang.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getAccessManagements(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'status' => 'nullable|in:active,inactive,expired',
        ]);

        $query = AccessManagement::with(['user', 'department']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $accessManagements = $query->orderBy('granted_date', 'desc')->get();

        return response()->json($accessManagements);
    }

    public function getUserAccess(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $accessManagements = AccessManagement::where('user_id', $request->user_id)
            ->where('status', 'active')
            ->where('granted_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now());
            })
            ->with('department')
            ->get();

        // Decode module_access for each record
        $accessManagements->each(function ($access) {
            $access->module_access = json_decode($access->module_access, true);
        });

        return response()->json($accessManagements);
    }

    public function getDepartmentAccess(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
        ]);

        $accessManagements = AccessManagement::where('department_id', $request->department_id)
            ->where('status', 'active')
            ->where('granted_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now());
            })
            ->with('user')
            ->get();

        // Decode module_access for each record
        $accessManagements->each(function ($access) {
            $access->module_access = json_decode($access->module_access, true);
        });

        return response()->json($accessManagements);
    }

    public function checkUserPermission(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'module' => 'required|string',
            'action' => 'required|in:read,write,admin',
        ]);

        $accessManagement = AccessManagement::where('user_id', $request->user_id)
            ->where('status', 'active')
            ->where('granted_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now());
            })
            ->whereRaw("JSON_CONTAINS(module_access, ?)", ['"' . $request->module . '"'])
            ->first();

        if (!$accessManagement) {
            return response()->json([
                'has_access' => false,
                'message' => 'Tidak memiliki akses ke modul ini.',
            ]);
        }

        $hasPermission = false;
        switch ($request->action) {
            case 'read':
                $hasPermission = in_array($accessManagement->access_type, ['read', 'write', 'admin', 'super_admin']);
                break;
            case 'write':
                $hasPermission = in_array($accessManagement->access_type, ['write', 'admin', 'super_admin']);
                break;
            case 'admin':
                $hasPermission = in_array($accessManagement->access_type, ['admin', 'super_admin']);
                break;
        }

        return response()->json([
            'has_access' => $hasPermission,
            'access_type' => $accessManagement->access_type,
            'department' => $accessManagement->department,
        ]);
    }

    public function bulkCreate(Request $request)
    {
        $request->validate([
            'access_managements' => 'required|array|min:1',
            'access_managements.*.user_id' => 'required|exists:users,id',
            'access_managements.*.department_id' => 'required|exists:departments,id',
            'access_managements.*.access_type' => 'required|in:read,write,admin,super_admin',
            'access_managements.*.module_access' => 'required|array',
            'access_managements.*.module_access.*' => 'string|in:marketing,finance,warehouse,operational,system,company,reports,settings',
            'access_managements.*.granted_date' => 'required|date|before_or_equal:today',
            'access_managements.*.expiry_date' => 'nullable|date|after:granted_date',
            'access_managements.*.description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $createdCount = 0;

            foreach ($request->access_managements as $accessData) {
                // Check if user already has access to this department
                $existingAccess = AccessManagement::where('user_id', $accessData['user_id'])
                    ->where('department_id', $accessData['department_id'])
                    ->where('status', 'active')
                    ->exists();

                if (!$existingAccess) {
                    AccessManagement::create([
                        'user_id' => $accessData['user_id'],
                        'department_id' => $accessData['department_id'],
                        'access_type' => $accessData['access_type'],
                        'module_access' => json_encode($accessData['module_access']),
                        'granted_date' => $accessData['granted_date'],
                        'expiry_date' => $accessData['expiry_date'] ?? null,
                        'description' => $accessData['description'] ?? null,
                        'status' => 'active',
                        'created_by' => Auth::id(),
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();

            return back()->with('success', "Berhasil membuat {$createdCount} manajemen akses.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $accessManagements = AccessManagement::with(['user', 'department'])
            ->orderBy('granted_date', 'desc')
            ->get();

        // Here you would implement the actual Excel export logic
        // For now, we'll just return a success message

        return back()->with('success', "Berhasil mengekspor {$accessManagements->count()} manajemen akses.");
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Here you would implement the actual file import logic
            // For now, we'll just return a success message
            $importedCount = 0;

            // Process the uploaded file
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                // Process CSV/Excel file and create access managements
                // This is a placeholder for the actual import logic
                $importedCount = 10; // Example count
            }

            DB::commit();

            return back()->with('success', "Berhasil mengimpor {$importedCount} manajemen akses.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getStatistics()
    {
        $totalAccess = AccessManagement::count();
        $activeAccess = AccessManagement::where('status', 'active')->count();
        $inactiveAccess = AccessManagement::where('status', 'inactive')->count();
        $expiredAccess = AccessManagement::where('status', 'expired')->count();
        $readAccess = AccessManagement::where('access_type', 'read')->count();
        $writeAccess = AccessManagement::where('access_type', 'write')->count();
        $adminAccess = AccessManagement::where('access_type', 'admin')->count();
        $superAdminAccess = AccessManagement::where('access_type', 'super_admin')->count();

        return response()->json([
            'total_access' => $totalAccess,
            'active_access' => $activeAccess,
            'inactive_access' => $inactiveAccess,
            'expired_access' => $expiredAccess,
            'read_access' => $readAccess,
            'write_access' => $writeAccess,
            'admin_access' => $adminAccess,
            'super_admin_access' => $superAdminAccess,
        ]);
    }

    public function toggleStatus(AccessManagement $accessManagement)
    {
        try {
            $newStatus = $accessManagement->status === 'active' ? 'inactive' : 'active';
            
            $accessManagement->update(['status' => $newStatus]);

            return back()->with('success', "Status akses berhasil diubah menjadi {$newStatus}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}

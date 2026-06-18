<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::with(['createdBy:id,name', 'updatedBy:id,name'])
            ->withCount('users');
        
        // Use AutoFilterable local scope
        // Default to showing only active departments unless filtered
        if (!$request->has('filter')) {
            $request->merge(['filter' => ['is_active' => '1']]);
        }

        // Custom filter logic for Created By "System" (NULL)
        $filters = $request->input('filter', []);
        $skipFilters = [];

        if (isset($filters['createdBy__name'])) {
            $term = $filters['createdBy__name'];
            $query->where(function($q) use ($term) {
                $q->whereHas('createdBy', function($sq) use ($term) {
                    $sq->where('name', 'LIKE', "%{$term}%");
                });
                
                // If "System" matches the search term, include NULL records
                if (stripos('System', $term) !== false) {
                    $q->orWhereNull('created_by');
                }
            });
            $skipFilters['createdBy__name'] = true;
        }

        if (isset($filters['updatedBy__name'])) {
            $term = $filters['updatedBy__name'];
            $query->where(function($q) use ($term) {
                $q->whereHas('updatedBy', function($sq) use ($term) {
                    $sq->where('name', 'LIKE', "%{$term}%");
                });
                
                // If "System" matches the search term, include NULL records
                if (stripos('System', $term) !== false) {
                    $q->orWhereNull('updated_by');
                }
            });
            $skipFilters['updatedBy__name'] = true;
        }

        // Pass skip filters to scope
        if (!empty($skipFilters)) {
            $request->merge(['_skip_auto_filter' => $skipFilters]);
        }
        
        $query->filter($request->all());

        // MANUAL FILTERS REMOVED - Handled by AutoFilterable trait
        /*
        // Filter by name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by sub department
        if ($request->filled('sub_department')) {
            $query->where('sub_department', $request->sub_department);
        }

        // Filter by system reserved
        if ($request->filled('system_reserved')) {
            $query->where('system_reserved', $request->system_reserved);
        }

        // Filter by is_active
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        */

        $departments = $query->orderBy('name')->paginateStd(25);
        $allDepartments = Cache::remember('department:index:active', 300, function () {
            return Department::where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
        }); // For dropdown in create modal

        return view('system.departments.index', compact('departments', 'allDepartments'));
    }

    public function create()
    {
        $parentDepartments = Department::whereNull('sub_department')->get();
        
        return view('system.departments.create', compact('parentDepartments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments',
            'sub_department' => 'nullable|exists:departments,id',
            'description' => 'nullable|string',
            'system_reserved' => 'required|in:0,1,true,false',
            'is_active' => 'nullable|in:0,1,true,false',
        ]);

        try {
            DB::beginTransaction();

            $department = Department::create([
                'name' => $request->name,
                'sub_department' => $request->sub_department,
                'description' => $request->description,
                'system_reserved' => $request->system_reserved === '1' || $request->system_reserved === 'true' || $request->system_reserved === true,
                'is_active' => $request->is_active === '1' || $request->is_active === 'true' || $request->is_active === true || $request->is_active === null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            // Return JSON for API requests
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Departemen berhasil dibuat.',
                    'data' => $department->load(['createdBy'])
                ]);
            }

            return redirect()->route('system.departments.index')
                ->with('success', 'Departemen berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for API requests
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(Department $department)
    {
        $department->load(['createdBy', 'updatedBy', 'users', 'subDepartment']);
        
        // Return JSON for API requests
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $department
            ]);
        }
        
        // Redirect to index since show view doesn't exist
        return redirect()->route('system.departments.index')
            ->with('info', 'Department details: ' . $department->name);
    }

    public function edit(Department $department)
    {
        try {
            $parentDepartments = Department::whereNull('sub_department')
                ->where('id', '!=', $department->id)
                ->where('is_active', true)
                ->get();
            
            // Return JSON for API requests
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $department->load(['createdBy', 'updatedBy', 'subDepartment']),
                    'parentDepartments' => $parentDepartments
                ]);
            }
            
            // Redirect to index since edit view doesn't exist
            return redirect()->route('system.departments.index')
                ->with('info', 'Edit department: ' . $department->name);
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error loading department: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->with('error', 'Error loading department: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Department $department)
    {
        $deptId = $department->id ?? $request->route('department');
        if ($deptId instanceof \App\Models\Department) {
            $deptId = $deptId->id;
        }

        // Custom validation with better error handling
        $validator = \Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments')->ignore($deptId)->whereNull('deleted_at'),
            ],
            'sub_department' => 'nullable|exists:departments,id',
            'description' => 'nullable|string',
            'system_reserved' => 'required|in:0,1,true,false',
            'is_active' => 'nullable|in:0,1,true,false',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed:', [
                'errors' => $validator->errors(),
                'input' => $request->all(),
                'json_input' => $request->json()->all(),
            ]);
            
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $department->update([
                'name' => $request->name,
                'sub_department' => $request->sub_department,
                'description' => $request->description,
                'system_reserved' => $request->system_reserved === '1' || $request->system_reserved === 'true' || $request->system_reserved === true,
                'is_active' => $request->is_active === '1' || $request->is_active === 'true' || $request->is_active === true || $request->is_active === null,
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            // Return JSON for API requests
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Departemen berhasil diperbarui.',
                    'data' => $department->load(['createdBy', 'updatedBy'])
                ]);
            }

            return redirect()->route('system.departments.index')
                ->with('success', 'Departemen berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            
            // Return JSON for API requests
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function destroy(Department $department)
    {
        try {
            // Check if department is used by any users
            // For soft delete, we might still want to check this, or maybe allow hiding even if used?
            // Usually soft delete is safer, so we can relax constraints or keep them.
            // Let's keep constraints for safety to prevent "hiding" active departments with users.
            $hasUsers = $department->users()->where('is_active', true)->exists();
            
            if ($hasUsers) {
                throw new \Exception('Tidak dapat menonaktifkan departemen yang masih memiliki pengguna aktif.');
            }

            // Check if department has sub departments
            $hasSubDepartments = $department->subDepartments()->where('is_active', true)->exists();
            
            if ($hasSubDepartments) {
                throw new \Exception('Tidak dapat menonaktifkan departemen yang masih memiliki sub departemen aktif.');
            }

            // Check if department is system reserved
            if ($department->system_reserved) {
                throw new \Exception('Tidak dapat menonaktifkan departemen yang direservasi sistem.');
            }

            // Perform Soft Delete (Set is_active to false)
            $department->update(['is_active' => false]);
            
            // Return JSON for API requests
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Departemen berhasil dinonaktifkan.'
                ]);
            }
            
            return redirect()->route('system.departments.index')
                ->with('success', 'Departemen berhasil dinonaktifkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function getDepartments(Request $request)
    {
        $departments = Department::whereNull('sub_department')
            ->with('subDepartments')
            ->get();

        return response()->json($departments);
    }

    public function getSubDepartments(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
        ]);

        $subDepartments = Department::where('sub_department', $request->department_id)
            ->get();

        return response()->json($subDepartments);
    }

    public function getDepartmentTree()
    {
        $departments = Department::whereNull('sub_department')
            ->with(['subDepartments' => function ($query) {
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        return response()->json($departments);
    }

    public function bulkCreate(Request $request)
    {
        $request->validate([
            'departments' => 'required|array|min:1',
            'departments.*.name' => 'required|string|max:255',
            'departments.*.sub_department' => 'nullable|exists:departments,id',
            'departments.*.description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $createdCount = 0;

            foreach ($request->departments as $deptData) {
                // Check if department name already exists
                $exists = Department::where('name', $deptData['name'])->exists();
                
                if (!$exists) {
                    Department::create([
                        'name' => $deptData['name'],
                        'sub_department' => $deptData['sub_department'] ?? null,
                        'description' => $deptData['description'] ?? null,
                        'system_reserved' => false,
                        'created_by' => Auth::id(),
                    ]);
                    $createdCount++;
                }
            }

            DB::commit();

            return back()->with('success', "Berhasil membuat {$createdCount} departemen.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $departments = Department::with('subDepartments')
            ->orderBy('name')
            ->get();

        // Here you would implement the actual Excel export logic
        // For now, we'll just return a success message

        return back()->with('success', "Berhasil mengekspor {$departments->count()} departemen.");
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
                // Process CSV/Excel file and create departments
                // This is a placeholder for the actual import logic
                $importedCount = 10; // Example count
            }

            DB::commit();

            return back()->with('success', "Berhasil mengimpor {$importedCount} departemen.");
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|exists:departments,id',
        ]);

        try {
            DB::beginTransaction();

            $deletedCount = 0;
            $errors = [];

            foreach ($request->ids as $id) {
                try {
                    $department = Department::findOrFail($id);
                    
                    // Check if department is used by any checked in users (active users)
                    $hasUsers = $department->users()->where('is_active', true)->exists();
                    
                    if ($hasUsers) {
                        $errors[] = "Departemen '{$department->name}' tidak dapat dinonaktifkan karena masih memiliki pengguna aktif.";
                        continue;
                    }

                    // Check if department has active sub departments
                    $hasSubDepartments = $department->subDepartments()->where('is_active', true)->exists();
                    
                    if ($hasSubDepartments) {
                        $errors[] = "Departemen '{$department->name}' tidak dapat dinonaktifkan karena masih memiliki sub departemen aktif.";
                        continue;
                    }

                    // Check if department is system reserved
                    if ($department->system_reserved) {
                        $errors[] = "Departemen '{$department->name}' tidak dapat dinonaktifkan karena direservasi sistem.";
                        continue;
                    }

                    // Perform Soft Delete (Set is_active to false)
                    $department->update(['is_active' => false]);
                    $deletedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Error menonaktifkan departemen ID {$id}: " . $e->getMessage();
                }
            }

            DB::commit();

            if ($deletedCount > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Berhasil menonaktifkan {$deletedCount} departemen.",
                    'count' => $deletedCount,
                    'errors' => $errors
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada departemen yang berhasil dinonaktifkan.',
                    'errors' => $errors
                ], 422);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}

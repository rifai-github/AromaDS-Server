<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use App\Models\DashboardPermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Dashboard::with(['creator', 'widgets', 'permissions']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $dashboards = $query->orderBy('created_at', 'desc')->paginateStd(25);

        return view('reports.dashboard.index', compact('dashboards'));
    }

    public function create()
    {
        $users = User::where('is_active', true)->get();
        return view('reports.dashboard.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:dashboards,name',
            'description' => 'nullable|string',
            'layout' => 'nullable|array',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*.user_id' => 'required|exists:users,id',
            'permissions.*.can_view' => 'boolean',
            'permissions.*.can_edit' => 'boolean'
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

            $dashboard = Dashboard::create([
                'name' => $request->name,
                'description' => $request->description,
                'layout' => $request->layout ?? [],
                'is_active' => $request->is_active ?? true,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // Create permissions
            if ($request->permissions) {
                foreach ($request->permissions as $permission) {
                    DashboardPermission::create([
                        'dashboard_id' => $dashboard->id,
                        'user_id' => $permission['user_id'],
                        'can_view' => $permission['can_view'] ?? false,
                        'can_edit' => $permission['can_edit'] ?? false
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dashboard created successfully',
                    'data' => $dashboard
                ]);
            }

            return redirect()->route('reports.dashboard.index')
                           ->with('success', 'Dashboard created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating dashboard: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error creating dashboard: ' . $e->getMessage())
                        ->withInput();
        }
    }

    public function show($id)
    {
        $dashboard = Dashboard::with(['creator', 'widgets', 'permissions.user'])
                             ->findOrFail($id);

        if ($request->ajax()) {
            return response()->json($dashboard);
        }

        return view('reports.dashboard.show', compact('dashboard'));
    }

    public function edit($id)
    {
        $dashboard = Dashboard::with(['permissions'])->findOrFail($id);
        $users = User::where('is_active', true)->get();

        if ($request->ajax()) {
            return response()->json($dashboard);
        }

        return view('reports.dashboard.edit', compact('dashboard', 'users'));
    }

    public function update(Request $request, $id)
    {
        $dashboard = Dashboard::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:dashboards,name,' . $id,
            'description' => 'nullable|string',
            'layout' => 'nullable|array',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*.user_id' => 'required|exists:users,id',
            'permissions.*.can_view' => 'boolean',
            'permissions.*.can_edit' => 'boolean'
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

            $dashboard->update([
                'name' => $request->name,
                'description' => $request->description,
                'layout' => $request->layout ?? $dashboard->layout,
                'is_active' => $request->is_active ?? $dashboard->is_active,
                'updated_by' => Auth::id()
            ]);

            // Update permissions
            if ($request->permissions) {
                $dashboard->permissions()->delete();
                foreach ($request->permissions as $permission) {
                    DashboardPermission::create([
                        'dashboard_id' => $dashboard->id,
                        'user_id' => $permission['user_id'],
                        'can_view' => $permission['can_view'] ?? false,
                        'can_edit' => $permission['can_edit'] ?? false
                    ]);
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dashboard updated successfully',
                    'data' => $dashboard
                ]);
            }

            return redirect()->route('reports.dashboard.index')
                           ->with('success', 'Dashboard updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating dashboard: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error updating dashboard: ' . $e->getMessage())
                        ->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $dashboard = Dashboard::findOrFail($id);
            $dashboard->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dashboard deleted successfully'
                ]);
            }

            return redirect()->route('reports.dashboard.index')
                           ->with('success', 'Dashboard deleted successfully');

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting dashboard: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error deleting dashboard: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:dashboards,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data provided',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = Dashboard::whereIn('id', $request->ids)->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$count} dashboard(s)",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting dashboards: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addWidget(Request $request, $id)
    {
        $dashboard = Dashboard::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'widget_type' => 'required|string|max:255',
            'widget_config' => 'nullable|array',
            'position_x' => 'required|integer|min:0',
            'position_y' => 'required|integer|min:0',
            'width' => 'required|integer|min:100',
            'height' => 'required|integer|min:100',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $widget = DashboardWidget::create([
                'dashboard_id' => $dashboard->id,
                'widget_type' => $request->widget_type,
                'widget_config' => $request->widget_config ?? [],
                'position_x' => $request->position_x,
                'position_y' => $request->position_y,
                'width' => $request->width,
                'height' => $request->height,
                'is_active' => $request->is_active ?? true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Widget added successfully',
                'data' => $widget
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding widget: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateWidget(Request $request, $id, $widgetId)
    {
        $dashboard = Dashboard::findOrFail($id);
        $widget = $dashboard->widgets()->findOrFail($widgetId);

        $validator = Validator::make($request->all(), [
            'widget_type' => 'required|string|max:255',
            'widget_config' => 'nullable|array',
            'position_x' => 'required|integer|min:0',
            'position_y' => 'required|integer|min:0',
            'width' => 'required|integer|min:100',
            'height' => 'required|integer|min:100',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $widget->update([
                'widget_type' => $request->widget_type,
                'widget_config' => $request->widget_config ?? $widget->widget_config,
                'position_x' => $request->position_x,
                'position_y' => $request->position_y,
                'width' => $request->width,
                'height' => $request->height,
                'is_active' => $request->is_active ?? $widget->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Widget updated successfully',
                'data' => $widget
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating widget: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteWidget($id, $widgetId)
    {
        try {
            $dashboard = Dashboard::findOrFail($id);
            $widget = $dashboard->widgets()->findOrFail($widgetId);
            $widget->delete();

            return response()->json([
                'success' => true,
                'message' => 'Widget deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting widget: ' . $e->getMessage()
            ], 500);
        }
    }
}

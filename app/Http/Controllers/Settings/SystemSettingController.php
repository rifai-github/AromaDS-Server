<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SystemSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SystemSetting::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('setting_key', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('setting_type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $settings = $query->with(['creator', 'updater'])
            ->orderBy('setting_key')
            ->paginate(15);

        return view('settings.system.index', compact('settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('settings.system.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'setting_key' => 'required|string|max:255|unique:system_settings,setting_key',
            'setting_value' => 'required',
            'setting_type' => 'required|in:string,integer,boolean,json,array',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $setting = SystemSetting::create([
                'setting_key' => $request->setting_key,
                'setting_value' => $request->setting_value,
                'setting_type' => $request->setting_type,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', true),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'System setting created successfully',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create system setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SystemSetting $systemSetting)
    {
        $systemSetting->load(['creator', 'updater']);
        return view('settings.system.show', compact('systemSetting'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SystemSetting $systemSetting)
    {
        return view('settings.system.edit', compact('systemSetting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SystemSetting $systemSetting)
    {
        $validator = Validator::make($request->all(), [
            'setting_key' => 'required|string|max:255|unique:system_settings,setting_key,' . $systemSetting->id,
            'setting_value' => 'required',
            'setting_type' => 'required|in:string,integer,boolean,json,array',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $systemSetting->update([
                'setting_key' => $request->setting_key,
                'setting_value' => $request->setting_value,
                'setting_type' => $request->setting_type,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', true),
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'System setting updated successfully',
                'data' => $systemSetting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update system setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SystemSetting $systemSetting)
    {
        try {
            $systemSetting->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'System setting deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete system setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete system settings.
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:system_settings,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = SystemSetting::whereIn('id', $request->ids)->delete();

            return response()->json([
                'status' => 'success',
                'message' => "Successfully deleted {$count} system settings"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete system settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate system setting.
     */
    public function activate(SystemSetting $systemSetting)
    {
        try {
            $systemSetting->update(['is_active' => true, 'updated_by' => Auth::id()]);

            return response()->json([
                'status' => 'success',
                'message' => 'System setting activated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate system setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate system setting.
     */
    public function deactivate(SystemSetting $systemSetting)
    {
        try {
            $systemSetting->update(['is_active' => false, 'updated_by' => Auth::id()]);

            return response()->json([
                'status' => 'success',
                'message' => 'System setting deactivated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate system setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export system settings.
     */
    public function export(Request $request)
    {
        $query = SystemSetting::query();

        if ($request->filled('type')) {
            $query->where('setting_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $settings = $query->orderBy('setting_key')->get();

        $filename = 'system_settings_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($settings) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, ['Setting Key', 'Setting Value', 'Setting Type', 'Description', 'Is Active', 'Created At', 'Updated At']);
            
            // CSV data
            foreach ($settings as $setting) {
                fputcsv($file, [
                    $setting->setting_key,
                    is_array($setting->setting_value) ? json_encode($setting->setting_value) : $setting->setting_value,
                    $setting->setting_type,
                    $setting->description,
                    $setting->is_active ? 'Yes' : 'No',
                    $setting->created_at->format('Y-m-d H:i:s'),
                    $setting->updated_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use App\Models\ThemeCustomization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ThemeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Theme::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('theme_name', 'like', "%{$search}%")
                  ->orWhere('theme_description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $themes = $query->with(['creator', 'updater'])
            ->orderBy('is_default', 'desc')
            ->orderBy('theme_name')
            ->paginate(15);

        return view('settings.theme.index', compact('themes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('settings.theme.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme_name' => 'required|string|max:255|unique:themes,theme_name',
            'theme_description' => 'nullable|string|max:1000',
            'color_primary' => 'required|string|max:7',
            'color_secondary' => 'required|string|max:7',
            'color_accent' => 'required|string|max:7',
            'font_family' => 'required|string|max:255',
            'font_size' => 'required|integer|min:8|max:72',
            'is_default' => 'boolean',
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
            $theme = Theme::create([
                'theme_name' => $request->theme_name,
                'theme_description' => $request->theme_description,
                'color_primary' => $request->color_primary,
                'color_secondary' => $request->color_secondary,
                'color_accent' => $request->color_accent,
                'font_family' => $request->font_family,
                'font_size' => $request->font_size,
                'is_default' => $request->boolean('is_default', false),
                'is_active' => $request->boolean('is_active', true),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]);

            // If this theme is set as default, remove default from others
            if ($theme->is_default) {
                Theme::where('id', '!=', $theme->id)->update(['is_default' => false]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Theme created successfully',
                'data' => $theme
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create theme',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Theme $theme)
    {
        $theme->load(['creator', 'updater', 'customizations']);
        return view('settings.theme.show', compact('theme'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Theme $theme)
    {
        return view('settings.theme.edit', compact('theme'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Theme $theme)
    {
        $validator = Validator::make($request->all(), [
            'theme_name' => 'required|string|max:255|unique:themes,theme_name,' . $theme->id,
            'theme_description' => 'nullable|string|max:1000',
            'color_primary' => 'required|string|max:7',
            'color_secondary' => 'required|string|max:7',
            'color_accent' => 'required|string|max:7',
            'font_family' => 'required|string|max:255',
            'font_size' => 'required|integer|min:8|max:72',
            'is_default' => 'boolean',
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
            $theme->update([
                'theme_name' => $request->theme_name,
                'theme_description' => $request->theme_description,
                'color_primary' => $request->color_primary,
                'color_secondary' => $request->color_secondary,
                'color_accent' => $request->color_accent,
                'font_family' => $request->font_family,
                'font_size' => $request->font_size,
                'is_default' => $request->boolean('is_default', false),
                'is_active' => $request->boolean('is_active', true),
                'updated_by' => Auth::id()
            ]);

            // If this theme is set as default, remove default from others
            if ($theme->is_default) {
                Theme::where('id', '!=', $theme->id)->update(['is_default' => false]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Theme updated successfully',
                'data' => $theme
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update theme',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Theme $theme)
    {
        try {
            // Check if this is the default theme
            if ($theme->is_default) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete the default theme'
                ], 422);
            }

            $theme->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Theme deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete theme',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set theme as default.
     */
    public function setDefault(Theme $theme)
    {
        try {
            $theme->setAsDefault();

            return response()->json([
                'status' => 'success',
                'message' => 'Theme set as default successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to set theme as default',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate theme.
     */
    public function activate(Theme $theme)
    {
        try {
            $theme->activate();

            return response()->json([
                'status' => 'success',
                'message' => 'Theme activated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to activate theme',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate theme.
     */
    public function deactivate(Theme $theme)
    {
        try {
            // Check if this is the default theme
            if ($theme->is_default) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot deactivate the default theme'
                ], 422);
            }

            $theme->deactivate();

            return response()->json([
                'status' => 'success',
                'message' => 'Theme deactivated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to deactivate theme',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply theme to user.
     */
    public function apply(Theme $theme)
    {
        try {
            // Create or update user's theme customization
            ThemeCustomization::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'theme_id' => $theme->id
                ],
                [
                    'is_active' => true,
                    'updated_by' => Auth::id()
                ]
            );

            // Deactivate other theme customizations for this user
            ThemeCustomization::where('user_id', Auth::id())
                ->where('theme_id', '!=', $theme->id)
                ->update(['is_active' => false]);

            return response()->json([
                'status' => 'success',
                'message' => 'Theme applied successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to apply theme',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview theme.
     */
    public function preview(Theme $theme)
    {
        return response()->json([
            'status' => 'success',
            'data' => $theme->getThemeConfig()
        ]);
    }

    /**
     * Export themes.
     */
    public function export(Request $request)
    {
        $query = Theme::query();

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $themes = $query->orderBy('theme_name')->get();

        $filename = 'themes_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($themes) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, ['Theme Name', 'Description', 'Primary Color', 'Secondary Color', 'Accent Color', 'Font Family', 'Font Size', 'Is Default', 'Is Active', 'Created At', 'Updated At']);
            
            // CSV data
            foreach ($themes as $theme) {
                fputcsv($file, [
                    $theme->theme_name,
                    $theme->theme_description,
                    $theme->color_primary,
                    $theme->color_secondary,
                    $theme->color_accent,
                    $theme->font_family,
                    $theme->font_size,
                    $theme->is_default ? 'Yes' : 'No',
                    $theme->is_active ? 'Yes' : 'No',
                    $theme->created_at->format('Y-m-d H:i:s'),
                    $theme->updated_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

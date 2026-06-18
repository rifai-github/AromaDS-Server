<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ThemeSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ThemeSettingController extends Controller
{
    public function index(Request $request)
    {
        $query = ThemeSetting::with(['user', 'createdBy']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('user')) {
            $query->where('user_id', $request->user);
        }

        if ($request->filled('layout_style')) {
            $query->where('layout_style', $request->layout_style);
        }

        if ($request->filled('main_theme')) {
            $query->where('main_theme', $request->main_theme);
        }

        $theme_settings = $query->orderBy('created_at', 'desc')->paginateStd(25);
        $users = User::all();

        return view('settings.theme.index', compact('theme_settings', 'users'))->with('themes', $theme_settings);
    }

    public function create()
    {
        $users = User::all();
        return view('settings.theme.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id|unique:theme_settings',
            'layout_style' => 'required|in:vertical,horizontal,detached',
            'main_theme' => 'required|in:light,dark,semi-dark',
            'navbar_style' => 'required|in:floating,sticky,static,hidden',
            'navbar_color' => 'required|in:primary,secondary,success,info,warning,danger,dark,light',
            'toolbar_style' => 'required|in:fixed,static,hidden',
            'footer_style' => 'required|in:fixed,static,hidden',
            'is_custom_scrollbar' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $theme_setting = ThemeSetting::create([
                'user_id' => $request->user_id,
                'layout_style' => $request->layout_style,
                'main_theme' => $request->main_theme,
                'navbar_style' => $request->navbar_style,
                'navbar_color' => $request->navbar_color,
                'toolbar_style' => $request->toolbar_style,
                'footer_style' => $request->footer_style,
                'is_custom_scrollbar' => $request->is_custom_scrollbar,
                'created_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Theme setting created successfully',
                'data' => $theme_setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating theme setting: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(ThemeSetting $themeSetting)
    {
        $themeSetting->load(['user', 'createdBy']);
        return view('settings.theme.show', compact('themeSetting'));
    }

    public function edit(ThemeSetting $themeSetting)
    {
        $users = User::all();
        return view('settings.theme.edit', compact('themeSetting', 'users'));
    }

    public function update(Request $request, ThemeSetting $themeSetting)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id|unique:theme_settings,user_id,' . $themeSetting->id,
            'layout_style' => 'required|in:vertical,horizontal,detached',
            'main_theme' => 'required|in:light,dark,semi-dark',
            'navbar_style' => 'required|in:floating,sticky,static,hidden',
            'navbar_color' => 'required|in:primary,secondary,success,info,warning,danger,dark,light',
            'toolbar_style' => 'required|in:fixed,static,hidden',
            'footer_style' => 'required|in:fixed,static,hidden',
            'is_custom_scrollbar' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $themeSetting->update([
                'user_id' => $request->user_id,
                'layout_style' => $request->layout_style,
                'main_theme' => $request->main_theme,
                'navbar_style' => $request->navbar_style,
                'navbar_color' => $request->navbar_color,
                'toolbar_style' => $request->toolbar_style,
                'footer_style' => $request->footer_style,
                'is_custom_scrollbar' => $request->is_custom_scrollbar,
                'updated_by' => Auth::id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Theme setting updated successfully',
                'data' => $themeSetting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating theme setting: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(ThemeSetting $themeSetting)
    {
        try {
            $themeSetting->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Theme setting deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error deleting theme setting: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserTheme(Request $request)
    {
        $user_id = $request->user_id ?? Auth::id();
        
        $theme_setting = ThemeSetting::where('user_id', $user_id)->first();
        
        if (!$theme_setting) {
            // Return default theme settings
            $theme_setting = [
                'layout_style' => 'vertical',
                'main_theme' => 'light',
                'navbar_style' => 'sticky',
                'navbar_color' => 'primary',
                'toolbar_style' => 'fixed',
                'footer_style' => 'static',
                'is_custom_scrollbar' => true
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $theme_setting
        ]);
    }

    public function updateUserTheme(Request $request)
    {
        $user_id = $request->user_id ?? Auth::id();
        
        $validator = Validator::make($request->all(), [
            'layout_style' => 'sometimes|in:vertical,horizontal,detached',
            'main_theme' => 'sometimes|in:light,dark,semi-dark',
            'navbar_style' => 'sometimes|in:floating,sticky,static,hidden',
            'navbar_color' => 'sometimes|in:primary,secondary,success,info,warning,danger,dark,light',
            'toolbar_style' => 'sometimes|in:fixed,static,hidden',
            'footer_style' => 'sometimes|in:fixed,static,hidden',
            'is_custom_scrollbar' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $theme_setting = ThemeSetting::updateOrCreate(
                ['user_id' => $user_id],
                array_merge($request->only([
                    'layout_style', 'main_theme', 'navbar_style', 'navbar_color',
                    'toolbar_style', 'footer_style', 'is_custom_scrollbar'
                ]), [
                    'updated_by' => Auth::id()
                ])
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Theme settings updated successfully',
                'data' => $theme_setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating theme settings: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resetToDefault(Request $request)
    {
        $user_id = $request->user_id ?? Auth::id();

        try {
            $theme_setting = ThemeSetting::where('user_id', $user_id)->first();
            
            if ($theme_setting) {
                $theme_setting->update([
                    'layout_style' => 'vertical',
                    'main_theme' => 'light',
                    'navbar_style' => 'sticky',
                    'navbar_color' => 'primary',
                    'toolbar_style' => 'fixed',
                    'footer_style' => 'static',
                    'is_custom_scrollbar' => true,
                    'updated_by' => Auth::id()
                ]);
            } else {
                $theme_setting = ThemeSetting::create([
                    'user_id' => $user_id,
                    'layout_style' => 'vertical',
                    'main_theme' => 'light',
                    'navbar_style' => 'sticky',
                    'navbar_color' => 'primary',
                    'toolbar_style' => 'fixed',
                    'footer_style' => 'static',
                    'is_custom_scrollbar' => true,
                    'created_by' => Auth::id()
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Theme settings reset to default successfully',
                'data' => $theme_setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error resetting theme settings: ' . $e->getMessage()
            ], 500);
        }
    }

    public function dashboard()
    {
        $total_theme_settings = ThemeSetting::count();
        $users_with_custom_themes = ThemeSetting::count();
        $users_without_themes = User::whereNotIn('id', ThemeSetting::pluck('user_id'))->count();

        $theme_usage = [
            'layout_styles' => ThemeSetting::selectRaw('layout_style, count(*) as count')
                ->groupBy('layout_style')
                ->get(),
            'main_themes' => ThemeSetting::selectRaw('main_theme, count(*) as count')
                ->groupBy('main_theme')
                ->get(),
            'navbar_styles' => ThemeSetting::selectRaw('navbar_style, count(*) as count')
                ->groupBy('navbar_style')
                ->get(),
            'navbar_colors' => ThemeSetting::selectRaw('navbar_color, count(*) as count')
                ->groupBy('navbar_color')
                ->get()
        ];

        $recent_theme_changes = ThemeSetting::with(['user', 'createdBy'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return view('settings.dashboard', compact(
            'total_theme_settings',
            'users_with_custom_themes',
            'users_without_themes',
            'theme_usage',
            'recent_theme_changes'
        ));
    }

    public function bulkApplyTheme(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'layout_style' => 'required|in:vertical,horizontal,detached',
            'main_theme' => 'required|in:light,dark,semi-dark',
            'navbar_style' => 'required|in:floating,sticky,static,hidden',
            'navbar_color' => 'required|in:primary,secondary,success,info,warning,danger,dark,light',
            'toolbar_style' => 'required|in:fixed,static,hidden',
            'footer_style' => 'required|in:fixed,static,hidden',
            'is_custom_scrollbar' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            foreach ($request->user_ids as $user_id) {
                ThemeSetting::updateOrCreate(
                    ['user_id' => $user_id],
                    [
                        'layout_style' => $request->layout_style,
                        'main_theme' => $request->main_theme,
                        'navbar_style' => $request->navbar_style,
                        'navbar_color' => $request->navbar_color,
                        'toolbar_style' => $request->toolbar_style,
                        'footer_style' => $request->footer_style,
                        'is_custom_scrollbar' => $request->is_custom_scrollbar,
                        'updated_by' => Auth::id()
                    ]
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Theme settings applied to selected users successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error applying theme settings: ' . $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GoogleMapsSettingsController extends Controller
{
    /**
     * Display Google Maps settings page
     */
    public function index()
    {
        $settings = $this->getSettings();
        
        if (isset($settings['updated_by'])) {
            $user = \App\Models\User::find($settings['updated_by']);
            $settings['updated_by_name'] = $user ? $user->name : 'Unknown';
        }
        
        return view('system.google-maps.index', compact('settings'));
    }

    /**
     * Save Google Maps settings
     */
    public function saveSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'google_maps_api_key' => 'required|string|max:255',
            'default_latitude' => 'nullable|numeric|between:-90,90',
            'default_longitude' => 'nullable|numeric|between:-180,180',
            'default_zoom' => 'nullable|integer|between:1,20',
            'map_type' => 'nullable|in:roadmap,satellite,hybrid,terrain',
            'api_restrictions' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $settings = [
                'google_maps_api_key' => $request->google_maps_api_key,
                'default_latitude' => $request->default_latitude ?? -6.2088,
                'default_longitude' => $request->default_longitude ?? 106.8456,
                'default_zoom' => $request->default_zoom ?? 15,
                'map_type' => $request->map_type ?? 'roadmap',
                'api_restrictions' => $request->api_restrictions,
                'updated_at' => now(),
                'updated_by' => \Illuminate\Support\Facades\Auth::id()
            ];

            // Save to cache and database
            Cache::put('google_maps_settings', $settings, 86400); // 24 hours
            
            // Also save to config file or database table
            $this->saveToDatabase($settings);

            return response()->json([
                'status' => 'success',
                'message' => 'Google Maps settings saved successfully',
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error saving settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test Google Maps API key
     */
    public function testApiKey(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $apiKey = $request->api_key;
            
            // Test with a simple geocoding request
            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => 'Jakarta, Indonesia',
                'key' => $apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK') {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'API key is valid and working',
                        'data' => [
                            'test_location' => 'Jakarta, Indonesia',
                            'results_count' => count($data['results'] ?? [])
                        ]
                    ]);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'API key test failed: ' . $data['error_message'] ?? 'Unknown error'
                    ], 400);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to connect to Google Maps API'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error testing API key: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current settings
     */
    private function getSettings()
    {
        // Try to get from cache first
        $settings = Cache::get('google_maps_settings');
        
        if (!$settings) {
            // Get from database or default values
            $settings = $this->getFromDatabase();
            
            // Cache for 24 hours
            Cache::put('google_maps_settings', $settings, 86400);
        }

        return $settings;
    }

    /**
     * Get settings from database
     */
    private function getFromDatabase()
    {
        // You can implement this to get from a settings table
        // For now, return default values
        return [
            'google_maps_api_key' => config('services.google_maps.api_key', ''),
            'default_latitude' => config('services.google_maps.default_latitude', -6.2088),
            'default_longitude' => config('services.google_maps.default_longitude', 106.8456),
            'default_zoom' => config('services.google_maps.default_zoom', 15),
            'map_type' => config('services.google_maps.map_type', 'roadmap'),
            'api_restrictions' => config('services.google_maps.api_restrictions', ''),
        ];
    }

    /**
     * Save settings to database
     */
    private function saveToDatabase($settings)
    {
        // You can implement this to save to a settings table
        // For now, we'll just update the config cache
        
        // Update config values
        config([
            'services.google_maps.api_key' => $settings['google_maps_api_key'],
            'services.google_maps.default_latitude' => $settings['default_latitude'],
            'services.google_maps.default_longitude' => $settings['default_longitude'],
            'services.google_maps.default_zoom' => $settings['default_zoom'],
            'services.google_maps.map_type' => $settings['map_type'],
            'services.google_maps.api_restrictions' => $settings['api_restrictions'],
        ]);
    }

    /**
     * Get Google Maps API key for frontend use
     */
    public function getApiKey()
    {
        $settings = $this->getSettings();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'api_key' => $settings['google_maps_api_key'] ?? '',
                'default_latitude' => $settings['default_latitude'] ?? -6.2088,
                'default_longitude' => $settings['default_longitude'] ?? 106.8456,
                'default_zoom' => $settings['default_zoom'] ?? 15,
                'map_type' => $settings['map_type'] ?? 'roadmap'
            ]
        ]);
    }
}

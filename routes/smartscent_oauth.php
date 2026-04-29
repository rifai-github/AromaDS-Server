<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| Smart Scent OAuth Helper Routes
|--------------------------------------------------------------------------
| Temporary routes to help generate Smart Scent access token via OAuth
*/

// Step 1: Redirect to Smart Scent authorization page
Route::get('/smartscent/auth', function () {
    $clientId = env('SMART_SCENT_CLIENT_ID', '9F3C7A8D2E5B49F1A4C6839BE72D1C0E');
    $redirectUri = url('/smartscent/callback');
    
    $authUrl = "http://udb3.uarm.lbslm.com/oauth2/authorize.do?" . http_build_query([
        'appid' => 'YOUR_APPID', // You need to get this from Smart Scent
        'redirect_uri' => $redirectUri,
        'client_id' => $clientId,
        'response_type' => 'code',
        'state' => 'random_state_string',
    ]);
    
    return redirect($authUrl);
});

// Step 2: Callback to exchange code for access token
Route::get('/smartscent/callback', function (Request $request) {
    $code = $request->get('code');
    
    if (!$code) {
        return response()->json([
            'error' => 'No authorization code received',
            'request' => $request->all(),
        ], 400);
    }
    
    $clientId = env('SMART_SCENT_CLIENT_ID', '9F3C7A8D2E5B49F1A4C6839BE72D1C0E');
    $clientSecret = env('SMART_SCENT_CLIENT_SECRET', 'B7D6F2C491AB6E3F0D1984A63C5E8A27');
    
    try {
        $response = Http::get('http://udb3.uarm.lbslm.com/oauth/token.do', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            
            // Cache tokens
            Cache::put('smart_scent_access_token', $data['access_token'], now()->addSeconds($data['expires_in'] - 300));
            Cache::put('smart_scent_refresh_token', $data['refresh_token'], now()->addDays(30));
            
            return response()->json([
                'success' => true,
                'message' => 'Access token generated and cached successfully!',
                'access_token' => $data['access_token'],
                'expires_in' => $data['expires_in'],
                'cached_until' => now()->addSeconds($data['expires_in'] - 300)->toDateTimeString(),
            ]);
        }
        
        return response()->json([
            'error' => 'Failed to get access token',
            'response' => $response->json(),
        ], 400);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Exception occurred',
            'message' => $e->getMessage(),
        ], 500);
    }
});

// Step 3: Test API with serial number
Route::get('/smartscent/test/{serialNumber}', function ($serialNumber) {
    $service = app(\App\Services\SmartScentApiService::class);
    $result = $service->getDeviceFullData($serialNumber);
    
    return response()->json([
        'serial_number' => $serialNumber,
        'result' => $result,
        'access_token_cached' => Cache::has('smart_scent_access_token'),
        'refresh_token_cached' => Cache::has('smart_scent_refresh_token'),
    ]);
});

// Helper: Check token status
Route::get('/smartscent/status', function () {
    return response()->json([
        'access_token_exists' => Cache::has('smart_scent_access_token'),
        'refresh_token_exists' => Cache::has('smart_scent_refresh_token'),
        'access_token' => Cache::get('smart_scent_access_token') ? 'EXISTS (hidden)' : null,
    ]);
});

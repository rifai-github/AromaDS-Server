<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmartScentApiService
{
    private $baseUrl = 'http://ems.zhiyixiangfen.com';
    private $clientId;
    private $clientSecret;
    private $accessToken;

    public function __construct()
    {
        $this->clientId = env('SMART_SCENT_CLIENT_ID', '9F3C7A8D2E5B49F1A4C6839BE72D1C0E');
        $this->clientSecret = env('SMART_SCENT_CLIENT_SECRET', 'B7D6F2C491AB6E3F0D1984A63C5E8A27');
        
        // Get access token from cache or refresh
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * Get or refresh access token
     */
    private function getAccessToken()
    {
        // Check if token exists in cache
        $token = Cache::get('smart_scent_access_token');
        
        if (!$token) {
            // Refresh token
            $token = $this->refreshAccessToken();
        }
        
        return $token;
    }

    /**
     * Refresh access token
     */
    private function refreshAccessToken()
    {
        try {
            $refreshToken = Cache::get('smart_scent_refresh_token');
            
            if (!$refreshToken) {
                Log::warning('Smart Scent: No refresh token available');
                return null;
            }

            $response = Http::get('http://udb3.uarm.lbslm.com/oauth/token.do', [
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $refreshToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Cache tokens
                Cache::put('smart_scent_access_token', $data['access_token'], now()->addSeconds($data['expires_in'] - 300));
                Cache::put('smart_scent_refresh_token', $data['refresh_token'], now()->addDays(30));
                
                return $data['access_token'];
            }
            
            Log::error('Smart Scent: Failed to refresh token', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Smart Scent: Exception refreshing token', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate signature for API request
     */
    private function generateSignature($body)
    {
        $timestamp = (string) (time() * 1000);
        
        // SHA256 of body
        $bodySha256 = hash('sha256', $body);
        
        // Sign raw string
        $signRaw = $bodySha256 . "\n" . $this->clientId . "\n" . $timestamp;
        
        // HMAC SHA256
        $sign = strtoupper(hash_hmac('sha256', $signRaw, $this->clientSecret));
        
        return [
            'timestamp' => $timestamp,
            'sign' => $sign,
        ];
    }

    /**
     * Get device full data by MAC address
     */
    public function getDeviceFullData($macAddress)
    {
        try {
            if (!$this->accessToken) {
                return [
                    'success' => false,
                    'message' => 'No access token available',
                ];
            }

            $body = json_encode([
                'deviceMacList' => [$macAddress],
            ]);

            $signature = $this->generateSignature($body);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'accessToken' => $this->accessToken,
                'timestamp' => $signature['timestamp'],
                'sign' => $signature['sign'],
            ])->withBody($body, 'application/json')
              ->post($this->baseUrl . '/v1/device/getFullData.do');

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['code'] === 0 && isset($data['data']['deviceList'][0])) {
                    return [
                        'success' => true,
                        'data' => $data['data']['deviceList'][0],
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => $data['message'] ?? 'Device not found',
                ];
            }

            return [
                'success' => false,
                'message' => 'API request failed',
            ];
        } catch (\Exception $e) {
            Log::error('Smart Scent API Error', [
                'mac' => $macAddress,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get device basic data list
     */
    public function getDeviceBasicData($deviceType = 'E500', $netMode = 'WIFI')
    {
        try {
            if (!$this->accessToken) {
                return [
                    'success' => false,
                    'message' => 'No access token available',
                ];
            }

            $body = json_encode([
                'deviceTypeList' => [
                    [
                        'deviceType' => $deviceType,
                        'netMode' => $netMode,
                    ],
                ],
            ]);

            $signature = $this->generateSignature($body);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'accessToken' => $this->accessToken,
                'timestamp' => $signature['timestamp'],
                'sign' => $signature['sign'],
            ])->withBody($body, 'application/json')
              ->post($this->baseUrl . '/v1/device/getBasicData.do');

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['code'] === 0) {
                    return [
                        'success' => true,
                        'data' => $data['data'],
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => $data['message'] ?? 'Failed to get devices',
                ];
            }

            return [
                'success' => false,
                'message' => 'API request failed',
            ];
        } catch (\Exception $e) {
            Log::error('Smart Scent API Error', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Control device
     */
    public function controlDevice($macAddress, $deviceType, $snapshot)
    {
        try {
            if (!$this->accessToken) {
                return [
                    'success' => false,
                    'message' => 'No access token available',
                ];
            }

            $body = json_encode([
                'mac' => $macAddress,
                'deviceType' => $deviceType,
                'snapshot' => $snapshot,
            ]);

            $signature = $this->generateSignature($body);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'accessToken' => $this->accessToken,
                'timestamp' => $signature['timestamp'],
                'sign' => $signature['sign'],
            ])->withBody($body, 'application/json')
              ->post($this->baseUrl . '/v1/device/control.do');

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['code'] === 0) {
                    return [
                        'success' => true,
                        'message' => 'Device controlled successfully',
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => $data['message'] ?? 'Failed to control device',
                ];
            }

            return [
                'success' => false,
                'message' => 'API request failed',
            ];
        } catch (\Exception $e) {
            Log::error('Smart Scent API Error', [
                'mac' => $macAddress,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }
}

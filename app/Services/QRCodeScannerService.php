<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QRCodeScannerService
{
    private $baseUrl = 'http://ems.zhiyixiangfen.com/v1/device';
    private $clientId;
    private $clientSecret;
    private $accessToken;

    public function __construct()
    {
        $this->clientId = config('services.qr_scanner.client_id', 'smart');
        $this->clientSecret = config('services.qr_scanner.client_secret');
    }

    /**
     * Get access token for API authentication
     */
    public function getAccessToken()
    {
        try {
            $response = Http::get('http://udb3.uarm.lbslm.com/oauth/token.do', [
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $this->getCode() // This should be obtained from OAuth flow
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->accessToken = $data['access_token'];
                return $this->accessToken;
            }
        } catch (\Exception $e) {
            Log::error('QR Scanner API Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get device basic data list
     */
    public function getDeviceBasicData($deviceType = 'E500', $netMode = 'WIFI')
    {
        $this->ensureAccessToken();

        $timestamp = time() * 1000;
        $body = json_encode([
            'deviceTypeList' => [
                [
                    'deviceType' => $deviceType,
                    'netMode' => $netMode
                ]
            ]
        ]);

        $signature = $this->generateSignature($body, $timestamp);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'accessToken' => $this->accessToken,
                'timestamp' => $timestamp,
                'sign' => $signature
            ])->post($this->baseUrl . '/getBasicData.do', json_decode($body, true));

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Get Device Basic Data Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get full device data by MAC addresses
     */
    public function getFullDeviceData(array $macAddresses)
    {
        $this->ensureAccessToken();

        $timestamp = time() * 1000;
        $body = json_encode([
            'deviceMacList' => $macAddresses
        ]);

        $signature = $this->generateSignature($body, $timestamp);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'accessToken' => $this->accessToken,
                'timestamp' => $timestamp,
                'sign' => $signature
            ])->post($this->baseUrl . '/getFullData.do', json_decode($body, true));

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Get Full Device Data Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Control device
     */
    public function controlDevice($mac, $deviceType, $snapshot)
    {
        $this->ensureAccessToken();

        $timestamp = time() * 1000;
        $body = json_encode([
            'mac' => $mac,
            'deviceType' => $deviceType,
            'snapshot' => $snapshot
        ]);

        $signature = $this->generateSignature($body, $timestamp);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'accessToken' => $this->accessToken,
                'timestamp' => $timestamp,
                'sign' => $signature
            ])->post($this->baseUrl . '/control.do', json_decode($body, true));

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Control Device Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Validate QR code and get device information
     */
    public function validateQRCode($qrCode)
    {
        // Extract MAC address from QR code
        $macAddress = $this->extractMacFromQR($qrCode);
        
        if (!$macAddress) {
            return [
                'valid' => false,
                'message' => 'Invalid QR code format'
            ];
        }

        // Get device data
        $deviceData = $this->getFullDeviceData([$macAddress]);
        
        if ($deviceData && isset($deviceData['data']['deviceList'][0])) {
            $device = $deviceData['data']['deviceList'][0];
            return [
                'valid' => true,
                'device' => $device,
                'mac' => $device['mac'],
                'deviceType' => $device['deviceType'],
                'snapshot' => $device['deviceSnapshot'] ?? null
            ];
        }

        return [
            'valid' => false,
            'message' => 'Device not found or offline'
        ];
    }

    /**
     * Generate signature for API requests
     */
    private function generateSignature($body, $timestamp)
    {
        $signRaw = hash('sha256', $body) . "\n" . $this->clientId . "\n" . $timestamp;
        return strtoupper(hash_hmac('sha256', $signRaw, $this->clientSecret));
    }

    /**
     * Ensure access token is available
     */
    private function ensureAccessToken()
    {
        if (!$this->accessToken) {
            $this->getAccessToken();
        }
    }

    /**
     * Extract MAC address from QR code
     */
    private function extractMacFromQR($qrCode)
    {
        // QR code format: "DEVICE:MAC:DEVICE_TYPE" or just MAC address
        if (strpos($qrCode, 'DEVICE:') === 0) {
            $parts = explode(':', $qrCode);
            return $parts[1] ?? null;
        }
        
        // If it's just a MAC address (12 hex characters)
        if (preg_match('/^[0-9a-fA-F]{12}$/', $qrCode)) {
            return $qrCode;
        }

        return null;
    }

    /**
     * Get OAuth code (this should be implemented based on your OAuth flow)
     */
    private function getCode()
    {
        // This should be implemented based on your OAuth flow
        // For now, return a placeholder
        return 'your_oauth_code_here';
    }
}

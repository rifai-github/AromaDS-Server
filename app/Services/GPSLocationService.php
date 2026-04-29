<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GPSLocationService
{
    /**
     * Get current location from browser/device
     */
    public function getCurrentLocation()
    {
        // This will be called from JavaScript in the frontend
        // Returns the current GPS coordinates
        return [
            'latitude' => null,
            'longitude' => null,
            'accuracy' => null,
            'timestamp' => now()
        ];
    }

    /**
     * Reverse geocoding - convert coordinates to address
     */
    public function reverseGeocode($latitude, $longitude)
    {
        try {
            // Using OpenStreetMap Nominatim API (free)
            $response = Http::get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'json',
                'lat' => $latitude,
                'lon' => $longitude,
                'zoom' => 18,
                'addressdetails' => 1
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->formatAddress($data);
            }
        } catch (\Exception $e) {
            Log::error('Reverse Geocoding Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Calculate distance between two coordinates
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c; // Distance in kilometers
    }

    /**
     * Validate GPS coordinates
     */
    public function validateCoordinates($latitude, $longitude)
    {
        return (
            is_numeric($latitude) && 
            is_numeric($longitude) &&
            $latitude >= -90 && $latitude <= 90 &&
            $longitude >= -180 && $longitude <= 180
        );
    }

    /**
     * Format address from reverse geocoding response
     */
    private function formatAddress($data)
    {
        $address = $data['display_name'] ?? '';
        
        // Extract specific components
        $components = $data['address'] ?? [];
        
        $formatted = [
            'full_address' => $address,
            'street' => $components['road'] ?? '',
            'building' => $components['house_number'] ?? '',
            'suburb' => $components['suburb'] ?? $components['neighbourhood'] ?? '',
            'city' => $components['city'] ?? $components['town'] ?? $components['village'] ?? '',
            'state' => $components['state'] ?? '',
            'postcode' => $components['postcode'] ?? '',
            'country' => $components['country'] ?? ''
        ];

        return $formatted;
    }

    /**
     * Get location accuracy level
     */
    public function getAccuracyLevel($accuracy)
    {
        if ($accuracy <= 5) {
            return 'excellent';
        } elseif ($accuracy <= 10) {
            return 'good';
        } elseif ($accuracy <= 20) {
            return 'fair';
        } else {
            return 'poor';
        }
    }
}

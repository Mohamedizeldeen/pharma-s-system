<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DistanceService
{
    /**
     * Calculate distances from user location to pharmacies
     */
    public function calculateDistances(
        Collection $results,
        float $userLat,
        float $userLng
    ): Collection {
        $useGoogleApi = config('services.google_maps.key') !== null;

        if ($useGoogleApi) {
            return $this->calculateWithGoogleMaps($results, $userLat, $userLng);
        }

        return $this->calculateWithHaversine($results, $userLat, $userLng);
    }

    /**
     * Calculate distance using Haversine formula
     */
    private function calculateWithHaversine(
        Collection $results,
        float $userLat,
        float $userLng
    ): Collection {
        return $results->map(function ($result) use ($userLat, $userLng) {
            $pharmacyLat = (float) $result['latitude'];
            $pharmacyLng = (float) $result['longitude'];

            $distance = $this->haversineDistance($userLat, $userLng, $pharmacyLat, $pharmacyLng);

            $result['distance_km'] = round($distance, 2);
            $result['eta_minutes'] = $this->estimateTravelTime($distance);

            return $result;
        })->sortBy('distance_km')->values();
    }

    /**
     * Haversine formula to calculate distance between two coordinates
     */
    private function haversineDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate distance and duration using Google Distance Matrix API
     */
    private function calculateWithGoogleMaps(
        Collection $results,
        float $userLat,
        float $userLng
    ): Collection {
        try {
            $apiKey = config('services.google_maps.key');
            $origin = "{$userLat},{$userLng}";
            
            // Group destinations (max 25 per request for Google API)
            $destinations = $results->map(fn($r) => "{$r['latitude']},{$r['longitude']}")->toArray();
            $destinationsString = implode('|', $destinations);

            $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                'origins' => $origin,
                'destinations' => $destinationsString,
                'key' => $apiKey,
                'mode' => 'driving', // or 'walking'
                'language' => 'ar'
            ]);

            if (!$response->successful()) {
                Log::warning('Google Distance Matrix API failed, falling back to Haversine');
                return $this->calculateWithHaversine($results, $userLat, $userLng);
            }

            $data = $response->json();

            if ($data['status'] !== 'OK') {
                Log::warning('Google Distance Matrix returned error', ['status' => $data['status']]);
                return $this->calculateWithHaversine($results, $userLat, $userLng);
            }

            $elements = $data['rows'][0]['elements'];

            return $results->map(function ($result, $index) use ($elements) {
                $element = $elements[$index] ?? null;

                if ($element && $element['status'] === 'OK') {
                    $result['distance_km'] = round($element['distance']['value'] / 1000, 2);
                    $result['eta_minutes'] = round($element['duration']['value'] / 60);
                } else {
                    // Fallback to Haversine for this specific location
                    $distance = $this->haversineDistance(
                        $result['latitude'],
                        $result['longitude'],
                        $result['latitude'],
                        $result['longitude']
                    );
                    $result['distance_km'] = round($distance, 2);
                    $result['eta_minutes'] = $this->estimateTravelTime($distance);
                }

                return $result;
            })->sortBy('distance_km')->values();

        } catch (\Exception $e) {
            Log::error('Google Maps distance calculation failed', ['error' => $e->getMessage()]);
            return $this->calculateWithHaversine($results, $userLat, $userLng);
        }
    }

    /**
     * Estimate travel time based on distance (simple formula)
     * Assumes average speed of 30 km/h in city traffic
     */
    private function estimateTravelTime(float $distanceKm): int
    {
        $averageSpeedKmh = 30;
        $timeHours = $distanceKm / $averageSpeedKmh;
        return (int) ceil($timeHours * 60); // Convert to minutes
    }

    /**
     * Get directions URL
     */
    public function getDirectionsUrl(float $userLat, float $userLng, float $destLat, float $destLng): string
    {
        return "https://www.google.com/maps/dir/?api=1&origin={$userLat},{$userLng}&destination={$destLat},{$destLng}";
    }
}

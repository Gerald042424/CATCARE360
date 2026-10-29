<?php

namespace App\Controllers\Adopter;

use App\Controllers\BaseController;
use Throwable;

class LocationController extends BaseController
{
    public function reverse()
    {
        $latitude = filter_var($this->request->getGet('latitude'), FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($this->request->getGet('longitude'), FILTER_VALIDATE_FLOAT);

        if ($latitude === false || $longitude === false
            || $latitude < -90 || $latitude > 90
            || $longitude < -180 || $longitude > 180) {
            return $this->response
                ->setStatusCode(422)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setJSON(['error' => 'Valid GPS coordinates are required.']);
        }

        $fallback = sprintf('GPS %.6f, %.6f', $latitude, $longitude);
        $cacheKey = 'gps_address_' . hash('sha256', sprintf('%.4f,%.4f', $latitude, $longitude));

        try {
            $cached = cache()->get($cacheKey);
            if (is_array($cached) && isset($cached['location'])) {
                return $this->locationResponse($cached);
            }
        } catch (Throwable) {
            // Reverse geocoding must still work when a cache backend is unavailable.
        }

        $baseUrl = rtrim((string) env('geocoding.reverseUrl', 'https://nominatim.openstreetmap.org/reverse'), '?&');
        $query = http_build_query([
            'format' => 'jsonv2',
            'lat' => $latitude,
            'lon' => $longitude,
            'zoom' => 18,
            'addressdetails' => 1,
            'layer' => 'address',
        ]);

        $payload = null;
        if (str_starts_with($baseUrl, 'https://')) {
            try {
                $response = service('curlrequest')->get($baseUrl . '?' . $query, [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Accept-Language' => 'en-PH,en;q=0.9',
                        'User-Agent' => 'CatCare360/1.0 (' . base_url('/') . ')',
                    ],
                    'connect_timeout' => 3,
                    'timeout' => 7,
                    'http_errors' => false,
                ]);

                if ($response->getStatusCode() === 200) {
                    $decoded = json_decode($response->getBody(), true);
                    $displayName = trim((string) ($decoded['display_name'] ?? ''));
                    if ($displayName !== '') {
                        $payload = [
                            'location' => mb_substr(preg_replace('/\s+/', ' ', $displayName) ?? $displayName, 0, 300),
                            'source' => 'openstreetmap',
                            'attribution' => '© OpenStreetMap contributors',
                        ];
                    }
                }
            } catch (Throwable) {
                // The exact GPS coordinates below remain a reliable, privacy-safe fallback.
            }
        }

        if ($payload === null) {
            $payload = [
                'location' => $fallback,
                'source' => 'gps_coordinates',
                'attribution' => null,
            ];
        } else {
            try {
                cache()->save($cacheKey, $payload, DAY);
            } catch (Throwable) {
                // A cache failure must not prevent the adopter from capturing a document.
            }
        }

        return $this->locationResponse($payload);
    }

    private function locationResponse(array $payload)
    {
        return $this->response
            ->setHeader('Cache-Control', 'private, no-store')
            ->setJSON($payload);
    }
}

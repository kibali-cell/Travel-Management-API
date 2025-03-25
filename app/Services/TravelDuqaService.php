<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TravelDuqaService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('services.travelduqa');
    }

    /**
     * Get the static access token from config (with optional caching)
     *
     * @return string
     */
    private function getAccessToken()
    {
        if (Cache::has('travelduqa_token')) {
            return Cache::get('travelduqa_token');
        }

        $token = $this->config['token'];
        Cache::put('travelduqa_token', $token, now()->addHours(24));

        return $token;
    }

    /**
     * Fetch all locations from the Travelduqa getLocation API
     *
     * @return array
     */
    public function getLocations()
    {
        // Check if locations are cached
        if (Cache::has('travelduqa_locations')) {
            return Cache::get('travelduqa_locations');
        }

        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Travelduqa-Version' => 'v1',
        ])->post($this->config['base_url'] . '/connect/getLocation', [
            'filter' => 'none',
            'value' => 'all',
        ]);

        if (!$response->successful()) {
            \Log::error('Failed to fetch locations from Travelduqa API', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            throw new \Exception('Unable to fetch locations: ' . $response->body());
        }

        $locations = $response->json();
        // Cache for 24 hours (adjust as needed)
        Cache::put('travelduqa_locations', $locations, now()->addHours(24));

        return $locations;
    }

    /**
     * Search flights using the TravelDuqa API.
     *
     * @param array $params
     * @return array
     */
    public function searchFlights($params)
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Travelduqa-Version' => 'v1',
        ])->post($this->config['base_url'] . $this->config['offers_endpoint'], $params);

        if (!$response->successful()) {
            \Log::error('API Request Failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            throw new \Exception('API Error: ' . $response->body());
        }

        return $response->json();
    }
}
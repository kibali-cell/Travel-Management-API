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

    private function authenticate()
    {
        $response = Http::asForm()->post($this->config['base_url'].$this->config['auth_endpoint'], [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config['api_key'],
            'client_secret' => $this->config['api_secret']
        ]);

        if (!$response->successful()) {
            \Log::error('TravelDuqa Auth Failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            throw new \Exception('Authentication failed: '.$response->body());
        }

        $data = $response->json();
        Cache::put('travelduqa_token', $data['access_token'], now()->addSeconds($data['expires_in'] - 60));
        
        return $data['access_token'];
    }

    private function getAccessToken()
    {
        if (Cache::has('travelduqa_token')) {
            return Cache::get('travelduqa_token');
        }
        return $this->authenticate();
    }

    public function searchFlights($params)
    {
        $token = $this->getAccessToken();
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json'
        ])->post($this->config['base_url'].$this->config['offers_endpoint'], $params);

        if (!$response->successful()) {
            \Log::error('Flight Search Failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            throw new \Exception('Flight search failed: '.$response->body());
        }

        return $response->json();
    }
}
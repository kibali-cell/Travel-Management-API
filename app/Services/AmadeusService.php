<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\ApiException;

class AmadeusService
{
    protected $baseUrl;
    protected $apiKey;
    protected $apiSecret;
    
    public function __construct()
    {
        $this->baseUrl = config('services.amadeus.base_url');
        $this->apiKey = config('services.amadeus.api_key');
        $this->apiSecret = config('services.amadeus.api_secret');
    }
    
    protected function getAccessToken()
    {
        // Check if we have a valid cached token
        if (Cache::has('amadeus_token')) {
            return Cache::get('amadeus_token');
        }
        
        // Request new token
        $response = Http::asForm()->post("{$this->baseUrl}/v1/security/oauth2/token", [
            'grant_type' => 'client_credentials',
            'client_id' => $this->apiKey,
            'client_secret' => $this->apiSecret,
        ]);
        
        if (!$response->successful()) {
            throw new ApiException('Failed to obtain Amadeus access token', $response->status(), $response->json());
        }
        
        $data = $response->json();
        $token = $data['access_token'];
        $expiresIn = $data['expires_in'] ?? 1800; // Default to 30 minutes if not specified
        
        // Cache the token (for slightly less than its expiry time)
        Cache::put('amadeus_token', $token, now()->addSeconds($expiresIn - 60));
        
        return $token;
    }

    public function getHotelListByCity($params)
    {
        // Obtain the access token
        $token = $this->getAccessToken();

        // Call the hotel list endpoint (v1) with the city code
        $hotelListResponse = Http::withToken($token)
            ->get("{$this->baseUrl}/v1/reference-data/locations/hotels/by-city", [
                'cityCode' => $params['city_code']
            ]);

        if (!$hotelListResponse->successful()) {
            throw new ApiException('Failed to retrieve hotel list', $hotelListResponse->status(), $hotelListResponse->json());
        }

        return $hotelListResponse->json();
    }


// public function searchHotelOffers($params)
// {
//     $token = $this->getAccessToken();
    
//     $response = Http::withToken($token)
//         ->get("{$this->baseUrl}/v3/shopping/hotel-offers", $params);
        
//     if (!$response->successful()) {
//         throw new ApiException('Hotel offers search failed', $response->status(), $response->json());
//     }
    
//     return $response->json();
// }

    
    public function searchHotels($params)
    {
        $token = $this->getAccessToken();
        
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/v2/shopping/hotel-offers", $params);
            
        if (!$response->successful()) {
            throw new ApiException('Hotel search failed', $response->status(), $response->json());
        }
        
        return $response->json();
    }
    
    public function bookHotel($bookingData)
    {
        $token = $this->getAccessToken();
        
        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v1/booking/hotel-bookings", $bookingData);
            
        if (!$response->successful()) {
            throw new ApiException('Hotel booking failed', $response->status(), $response->json());
        }
        
        return $response->json();
    }
    
    public function searchCars($params)
    {
        $token = $this->getAccessToken();
        
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/v2/shopping/car-rental-offers", $params);
            
        if (!$response->successful()) {
            throw new ApiException('Car rental search failed', $response->status(), $response->json());
        }
        
        return $response->json();
    }
    
    public function bookCar($bookingData)
    {
        $token = $this->getAccessToken();
        
        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v1/booking/car-rentals", $bookingData);
            
        if (!$response->successful()) {
            throw new ApiException('Car rental booking failed', $response->status(), $response->json());
        }
        
        return $response->json();
    }
}
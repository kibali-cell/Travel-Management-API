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
        if (Cache::has('amadeus_token')) {
            return Cache::get('amadeus_token');
        }
        
        $response = Http::asForm()->post("{$this->baseUrl}/v1/security/oauth2/token", [
            'grant_type'     => 'client_credentials',
            'client_id'      => $this->apiKey,
            'client_secret'  => $this->apiSecret,
        ]);
        
        if (!$response->successful()) {
            throw new ApiException('Failed to obtain Amadeus access token', $response->status(), $response->json());
        }
        
        $data = $response->json();
        $token = $data['access_token'];
        $expiresIn = $data['expires_in'] ?? 1800;
        Cache::put('amadeus_token', $token, now()->addSeconds($expiresIn - 60));
        return $token;
    }
    
    public function getHotelListByCity($params)
    {
        $token = $this->getAccessToken();
        $hotelListResponse = Http::withToken($token)
            ->get("{$this->baseUrl}/v1/reference-data/locations/hotels/by-city", [
                'cityCode' => $params['city_code']
            ]);
            
        if (!$hotelListResponse->successful()) {
            throw new ApiException('Failed to retrieve hotel list', $hotelListResponse->status(), $hotelListResponse->json());
        }
        
        return $hotelListResponse->json();
    }
    
    public function searchHotelsByCity($params)
    {
        $token = $this->getAccessToken();
        
        // Get basic hotel list by city
        $hotelListResponse = Http::withToken($token)
            ->get("{$this->baseUrl}/v1/reference-data/locations/hotels/by-city", [
                'cityCode' => $params['city_code']
            ]);
            
        if (!$hotelListResponse->successful()) {
            throw new ApiException('Failed to retrieve hotel list', $hotelListResponse->status(), $hotelListResponse->json());
        }
        
        $hotelList = $hotelListResponse->json();
        
        // Extract up to 20 hotel IDs for the next API call
        $hotelIds = [];
        foreach ($hotelList['data'] as $hotel) {
            $hotelIds[] = $hotel['hotelId'];
            if (count($hotelIds) >= 20) {
                break;
            }
        }
        
        // Prepare parameters for searching hotel offers
        $offerParams = [
            'hotelIds'    => implode(',', $hotelIds),
            'adults'      => $params['adults'],
            'checkInDate' => $params['check_in'],
            'checkOutDate'=> $params['check_out'],
            'roomQuantity'=> 1,
            'currency'    => 'USD', // Customize as needed
            'bestRateOnly'=> true
        ];
        
        // Search for hotel offers to get pricing
        $offerResponse = Http::withToken($token)
            ->get("{$this->baseUrl}/v3/shopping/hotel-offers", $offerParams);
            
        // Instead of throwing an exception on any error, log the issue and default to empty offers
        if (!$offerResponse->successful()) {
            $errorData = $offerResponse->json();
            \Log::warning('Hotel Offers API call unsuccessful. Fallback to empty offers.', [
                'status'   => $offerResponse->status(),
                'response' => $errorData,
                'params'   => $offerParams
            ]);
            $offers = ['data' => []];
        } else {
            $offers = $offerResponse->json();
        }
        
        // Merge hotel list with pricing data.
        $enrichedHotels = $this->mergeHotelData($hotelList, $offers);
        
        // Enrich each hotel with additional details like ratings, photos, and amenities
        return $this->enrichHotelData($enrichedHotels);
    }

    
    private function mergeHotelData($hotelList, $offers)
    {
        $hotelOffers = [];
        
        // Index offers by hotel ID for quick lookup
        foreach ($offers['data'] ?? [] as $offer) {
            $hotelId = $offer['hotel']['hotelId'];
            $hotelOffers[$hotelId] = $offer;
        }
        
        // Merge data for each hotel
        foreach ($hotelList['data'] as &$hotel) {
            $hotelId = $hotel['hotelId'];
            if (isset($hotelOffers[$hotelId])) {
                $hotel['price'] = $hotelOffers[$hotelId]['offers'][0]['price'] ?? null;
                $hotel['offers'] = $hotelOffers[$hotelId]['offers'] ?? [];
            } else {
                $hotel['price'] = null;
                $hotel['offers'] = [];
            }
        }
        
        return $hotelList;
    }
    
    public function getHotelSentiments($hotelIds)
    {
        // Limit to 20 hotels for API performance
        $hotelIds = array_slice($hotelIds, 0, 20);
        
        $token = $this->getAccessToken();
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/v2/e-reputation/hotel-sentiments", [
                'hotelIds' => implode(',', $hotelIds)
            ]);
        
        if (!$response->successful()) {
            // Log the error but don't throw exception to allow the process to continue
            \Log::warning('Failed to get hotel ratings: ' . $response->status() . ' - ' . json_encode($response->json()));
            return ['data' => []];
        }

        \Log::debug('Sentiments API Response:', $response->json());
        
        return $response->json();
    }
    
    public function getHotelPhotos($hotelId, $hotelName)
{
    $imageId = crc32($hotelId) % 1000;
    
    return [
        'main' => "https://source.unsplash.com/800x600/?hotel,room,{$imageId}",
        'thumbnails' => [
            "https://source.unsplash.com/400x300/?hotel,lobby,{$imageId}",
            "https://source.unsplash.com/400x300/?hotel,bedroom,{$imageId}",
            "https://source.unsplash.com/400x300/?hotel,bathroom,{$imageId}"
        ]
    ];
}
    
    public function enrichHotelData($hotelList)
    {
        // Extract hotel IDs from the list
        $hotelIds = array_column($hotelList['data'] ?? [], 'hotelId');

        \Log::debug('Enriching hotels with these IDs:', $hotelIds);
        
        if (empty($hotelIds)) {
            return $hotelList;
        }
        
        // Get hotel sentiments (ratings)
        try {
            $sentiments = $this->getHotelSentiments($hotelIds);
            
            // Create a lookup array indexed by hotel ID
            $ratingsByHotelId = [];
            foreach ($sentiments['data'] ?? [] as $sentiment) {
                $ratingsByHotelId[$sentiment['hotelId']] = [
                    'overallRating' => $sentiment['overallRating'] ?? 4.0,
                    'numberOfReviews' => $sentiment['numberOfReviews'] ?? 0,
                    'sentiments' => $sentiment['sentiments'] ?? []
                ];
            }
            
            // Enrich each hotel with additional details
            foreach ($hotelList['data'] as &$hotel) {
                $hotelId = $hotel['hotelId'];
                
                // Add rating data
                $hotel['ratings'] = $ratingsByHotelId[$hotelId] ?? [
                    'overallRating' => 4.0,
                    'numberOfReviews' => 0,
                    'sentiments' => []
                ];
                
                // Get photos (using your placeholder or external API)
                $photos = $this->getHotelPhotos($hotelId, $hotel['name']);
                $hotel['images'] = $photos;
                
                // Calculate estimated stars (1-5) based on overall rating
                $rating = $hotel['ratings']['overallRating'] ?? 4.0;
                $hotel['stars'] = min(5, max(1, round($rating / 2))); // Convert 10-point scale to 5-star
                
                // Add amenities (you might want to pull this from actual hotel data)
                $hotel['amenities'] = $this->getDefaultAmenities();
            }
            
            return $hotelList;
        } catch (\Exception $e) {
            \Log::error('Error enriching hotel data: ' . $e->getMessage());
            return $hotelList;
        }
    }
    
    private function getDefaultAmenities()
    {
        $allAmenities = ['WiFi', 'Restaurant', 'Breakfast', 'Parking', 'Pool', 'Spa', 'Gym', 'Air Conditioning'];
        // Return 4-6 random amenities
        shuffle($allAmenities);
        return array_slice($allAmenities, 0, rand(4, 6));
    }
    
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
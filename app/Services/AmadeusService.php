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
            'grant_type' => 'client_credentials',
            'client_id' => $this->apiKey,
            'client_secret' => $this->apiSecret,
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
        
        $hotelListResponse = Http::withToken($token)
            ->get("{$this->baseUrl}/v1/reference-data/locations/hotels/by-city", [
                'cityCode' => $params['city_code']
            ]);
            
        if (!$hotelListResponse->successful()) {
            throw new ApiException('Failed to retrieve hotel list', $hotelListResponse->status(), $hotelListResponse->json());
        }
        
        $hotelList = $hotelListResponse->json();
        
        $hotelIds = [];
        foreach ($hotelList['data'] as $hotel) {
            $hotelIds[] = $hotel['hotelId'];
            if (count($hotelIds) >= 20) {
                break;
            }
        }
        
        $offerParams = [
            'hotelIds' => implode(',', $hotelIds),
            'adults' => $params['adults'],
            'checkInDate' => $params['check_in'],
            'checkOutDate' => $params['check_out'],
            'roomQuantity' => 1,
            'currency' => 'USD',
            'bestRateOnly' => true
        ];
        
        $offerResponse = Http::withToken($token)
            ->get("{$this->baseUrl}/v3/shopping/hotel-offers", $offerParams);
            
        if (!$offerResponse->successful()) {
            \Log::warning('Hotel Offers API call unsuccessful.', [
                'status' => $offerResponse->status(),
                'response' => $offerResponse->json(),
                'params' => $offerParams
            ]);
            $offers = ['data' => []];
        } else {
            $offers = $offerResponse->json();
        }
        
        $enrichedHotels = $this->mergeHotelData($hotelList, $offers);
        
        return $this->enrichHotelData($enrichedHotels);
    }

    private function mergeHotelData($hotelList, $offers)
    {
        $hotelOffers = [];
        
        foreach ($offers['data'] ?? [] as $offer) {
            $hotelId = $offer['hotel']['hotelId'];
            $hotelOffers[$hotelId] = $offer;
        }
        
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
        $hotelIds = array_slice($hotelIds, 0, 20);
        
        $token = $this->getAccessToken();
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/v2/e-reputation/hotel-sentiments", [
                'hotelIds' => implode(',', $hotelIds)
            ]);
        
        if (!$response->successful()) {
            \Log::warning('Failed to get hotel ratings: ' . $response->status());
            return ['data' => []];
        }
        
        return $response->json();
    }

    public function getHotelDetails($hotelId)
    {
        $token = $this->getAccessToken();
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/v1/reference-data/locations/hotels/by-hotels", [
                'hotelIds' => $hotelId
            ]);

        if (!$response->successful()) {
            \Log::warning('Failed to get hotel details', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return null;
        }

        return $response->json()['data'][0] ?? null;
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
        $hotelIds = array_column($hotelList['data'] ?? [], 'hotelId');
        
        if (empty($hotelIds)) {
            return $hotelList;
        }
        
        try {
            $sentiments = $this->getHotelSentiments($hotelIds);
            $ratingsByHotelId = [];
            foreach ($sentiments['data'] ?? [] as $sentiment) {
                $ratingsByHotelId[$sentiment['hotelId']] = [
                    'overallRating' => $sentiment['overallRating'] ?? 4.0,
                    'numberOfReviews' => $sentiment['numberOfReviews'] ?? 0,
                    'sentiments' => $sentiment['sentiments'] ?? []
                ];
            }
            
            foreach ($hotelList['data'] as &$hotel) {
                $hotelId = $hotel['hotelId'];
                
                // Fetch detailed hotel info (amenities, description)
                $hotelDetails = $this->getHotelDetails($hotelId);
                if ($hotelDetails) {
                    $hotel['amenities'] = $hotelDetails['amenities'] ?? $this->getDefaultAmenities();
                    $hotel['description'] = $hotelDetails['description']['text'] ?? 'No description available';
                } else {
                    $hotel['amenities'] = $this->getDefaultAmenities();
                    $hotel['description'] = 'No description available';
                }

                $hotel['images'] = $this->getHotelPhotos($hotelId, $hotel['name']);
                
                $hotel['ratings'] = $ratingsByHotelId[$hotelId] ?? [
                    'overallRating' => 4.0,
                    'numberOfReviews' => 0,
                    'sentiments' => []
                ];

                $rating = $hotel['ratings']['overallRating'] ?? 4.0;
                $hotel['stars'] = min(5, max(1, round($rating / 2)));
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

    public function getHotelAutocomplete($keyword)
    {
        $token = $this->getAccessToken();
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/v1/reference-data/locations/hotel", [
                'keyword' => $keyword,
                'subType' => 'HOTEL_LEISURE'
            ]);

        if (!$response->successful()) {
            throw new ApiException(
                'Failed to retrieve hotel autocomplete suggestions',
                $response->status(),
                $response->json()
            );
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
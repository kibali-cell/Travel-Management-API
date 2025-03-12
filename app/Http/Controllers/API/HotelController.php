<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AmadeusService;
use App\Models\HotelBooking;
use App\Models\Trip;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\HotelResource;
use App\Http\Resources\HotelBookingResource;

class HotelController extends Controller
{
    protected $amadeusService;
    
    public function __construct(AmadeusService $amadeusService)
    {
        $this->amadeusService = $amadeusService;
    }

    public function searchByCity(Request $request)
{
    $validator = Validator::make($request->all(), [
        'city_code' => 'required|string|size:3'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        // Call the service method to get the hotel list by city
        $hotelList = $this->amadeusService->getHotelListByCity([
            'city_code' => $request->city_code
        ]);

        return response()->json([
            'hotel_list' => $hotelList
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Hotel search failed',
            'error'   => $e->getMessage()
        ], 500);
    }
}

    
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city_code' => 'required|string|size:3',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1',
            // other validations...
            'trip_id' => 'nullable|exists:trips,id'
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        try {
            // Use the new searchHotelsByCity method
            $results = $this->amadeusService->searchHotelsByCity([
                'city_code' => $request->city_code,
                'check_in' => $request->check_in,
                'check_out' => $request->check_out,
                'adults' => $request->adults
            ]);
    
            // Cache the search results for later use in booking
            $cacheKey = 'hotel_search_' . md5(json_encode($results));
            \Cache::put($cacheKey, $results, now()->addHours(1));
    
            return response()->json([
                'search_id' => $cacheKey,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Hotel search failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    
    
    public function book(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search_id' => 'required|string',
            'offer_id' => 'required|string',
            'trip_id' => 'required|exists:trips,id',
            'guests' => 'required|array',
            'guests.*.title' => 'required|string',
            'guests.*.first_name' => 'required|string',
            'guests.*.last_name' => 'required|string',
            'payment' => 'required|array',
            'payment.card_number' => 'required|string',
            'payment.expiry_date' => 'required|string',
            'payment.card_holder' => 'required|string',
            'contact' => 'required|array',
            'contact.email' => 'required|email',
            'contact.phone' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        try {
            // Retrieve the cached search results
            $searchResults = \Cache::get($request->search_id);
            if (!$searchResults) {
                return response()->json([
                    'message' => 'Search results expired. Please search again.'
                ], 400);
            }
            
            // Find the selected hotel offer
            $selectedOffer = null;
            foreach ($searchResults['data'] as $hotel) {
                foreach ($hotel['offers'] as $offer) {
                    if ($offer['id'] === $request->offer_id) {
                        $selectedOffer = $offer;
                        $selectedHotel = $hotel;
                        break 2;
                    }
                }
            }
            
            if (!$selectedOffer) {
                return response()->json([
                    'message' => 'Selected hotel offer not found in search results'
                ], 400);
            }
            
            // Format booking data for Amadeus API
            $bookingData = [
                'data' => [
                    'offerId' => $request->offer_id,
                    'guests' => array_map(function($guest) {
                        return [
                            'name' => [
                                'title' => $guest['title'],
                                'firstName' => $guest['first_name'],
                                'lastName' => $guest['last_name']
                            ],
                        ];
                    }, $request->guests),
                    'payments' => [
                        [
                            'method' => 'creditCard',
                            'card' => [
                                'vendorCode' => $this->detectCardType($request->payment['card_number']),
                                'cardNumber' => $request->payment['card_number'],
                                'expiryDate' => $request->payment['expiry_date']
                            ]
                        ]
                    ]
                ]
            ];
            
            // Call the Amadeus service to book the hotel
            $bookingResult = $this->amadeusService->bookHotel($bookingData);
            
            // Get the trip
            $trip = Trip::findOrFail($request->trip_id);
            
            // Save booking details to the database
            $hotelBooking = new HotelBooking();
            $hotelBooking->trip_id = $request->trip_id;
            $hotelBooking->user_id = auth()->id();
            $hotelBooking->booking_reference = $bookingResult['data']['id'];
            $hotelBooking->provider = 'amadeus';
            $hotelBooking->status = $bookingResult['data']['status'];
            $hotelBooking->hotel_name = $selectedHotel['hotel']['name'];
            $hotelBooking->hotel_code = $selectedHotel['hotel']['hotelId'];
            $hotelBooking->city_code = $request->city_code;
            $hotelBooking->check_in = $request->check_in;
            $hotelBooking->check_out = $request->check_out;
            $hotelBooking->booking_data = json_encode($bookingResult);
            $hotelBooking->amount = $selectedOffer['price']['total'];
            $hotelBooking->currency = $selectedOffer['price']['currency'];
            $hotelBooking->save();
            
            return response()->json([
                'message' => 'Hotel booked successfully',
                'booking' => new HotelBookingResource($hotelBooking)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Hotel booking failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function detectCardType($cardNumber)
    {
        // Simplified card type detection
        $firstDigit = substr($cardNumber, 0, 1);
        $firstTwoDigits = substr($cardNumber, 0, 2);
        
        if ($firstDigit === '4') {
            return 'VI'; // Visa
        } elseif (in_array($firstTwoDigits, ['51', '52', '53', '54', '55'])) {
            return 'MC'; // MasterCard
        } elseif (in_array($firstTwoDigits, ['34', '37'])) {
            return 'AX'; // American Express
        } else {
            return 'CA'; // Default to generic card
        }
    }
}
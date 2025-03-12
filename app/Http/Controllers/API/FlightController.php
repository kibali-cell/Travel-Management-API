<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\TravelDuqaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class FlightController extends Controller
{
    protected $travelDuqaService;

    public function __construct(TravelDuqaService $travelDuqaService)
    {
        $this->travelDuqaService = $travelDuqaService;
    }

    public function search(Request $request)
{
    $validator = Validator::make($request->all(), [
        'origin' => 'required|string|size:3',
        'destination' => 'required|string|size:3',
        'departure_date' => 'required|date|after_or_equal:today',
        'return_date' => 'nullable|date|after_or_equal:departure_date',
        'adults' => 'required|integer|min:1|max:9',
        'children' => 'nullable|integer|min:0',
        'infants' => 'nullable|integer|min:0',
        'cabin_class' => 'nullable|string|in:economy,premium_economy,business,first'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        $params = [
            'origin' => $request->origin,
            'destination' => $request->destination,
            'departure_date' => $request->departure_date,
            'return_date' => $request->return_date,
            'adults' => $request->adults,
            'children' => $request->children ?? 0,
            'infants' => $request->infants ?? 0,
            'cabin_class' => $request->cabin_class ?? 'economy'
        ];

        $results = $this->travelDuqaService->searchFlights($params);
        
        return response()->json([
            'data' => $results,
            'meta' => ['status' => 'success']
        ]);

    } catch (\Exception $e) {
        \Log::error('Flight Search Error: '.$e->getMessage());
        return response()->json([
            'error' => 'Flight search failed',
            'message' => $e->getMessage()
        ], 500);
    }
}

public function getOffersByHotelIds(Request $request)
{
    $validator = Validator::make($request->all(), [
        'hotelIds' => 'required|string',
        'adults'   => 'required|integer|min:1',
        'check_in' => 'nullable|date',
        'check_out'=> 'nullable|date'
    ]);
    
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        $params = [
            'hotelIds'     => $request->hotelIds, // e.g., "RTPAR001,RTPAR002"
            'adults'       => $request->adults,
            'checkInDate'  => $request->check_in,
            'checkOutDate' => $request->check_out
        ];
        $results = $this->amadeusService->searchHotelOffers($params);
        return response()->json($results);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Hotel offers search failed',
            'error'   => $e->getMessage()
        ], 500);
    }
}

    public function book(Request $request)
    {
        $bookingData = $request->validate([
            'flightId' => 'required|string',
            'passengerDetails' => 'required|array',
        ]);

        $result = $this->travelDuqaService->bookFlight($bookingData);
        return response()->json($result);
    }
}
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

    /**
     * Convert city name or IATA code to IATA code
     *
     * @param string $input
     * @return string
     */
    private function resolveIataCode($input)
    {
        if (strlen($input) === 3 && ctype_upper($input)) {
            return strtoupper($input);
        }

        $locations = $this->travelDuqaService->getLocations();

        
        $location = array_filter($locations, function ($loc) use ($input) {
            return strcasecmp($loc['city'], $input) === 0 ||
                strcasecmp($loc['iata'], $input) === 0 ||
                (isset($loc['name']) && strcasecmp($loc['name'], $input) === 0);
        });

        $location = reset($location); // Get first match

        if ($location) {
            return $location['iata'];
        }

        throw new \Exception("Invalid city or IATA code: $input");
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'flight_type' => 'required|in:oneway,return',
            'origin' => 'required|string', 
            'destination' => 'required|string', 
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date' => 'nullable|required_if:flight_type,return|date|after_or_equal:departure_date',
            'adults' => 'required|integer|min:1|max:9',
            'children' => 'nullable|integer|min:0',
            'infants' => 'nullable|integer|min:0',
            'cabin_class' => 'nullable|string|in:economy,business,first,premium',
            'currency' => 'required|string|size:3|in:KES,USD',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $originIata = $this->resolveIataCode($request->origin);
            $destinationIata = $this->resolveIataCode($request->destination);

            
            $iataValidator = Validator::make(
                ['origin' => $originIata, 'destination' => $destinationIata],
                [
                    'origin' => 'required|string|size:3',
                    'destination' => 'required|string|size:3',
                ]
            );

            if ($iataValidator->fails()) {
                return response()->json(['errors' => $iataValidator->errors()], 422);
            }

            // Build journey parameters
            $journeyParams = [
                'flight_type' => $request->flight_type,
                'cabin_class' => $request->cabin_class ?? 'economy',
                'depature' => $originIata, 
                'arrival' => $destinationIata, 
                'depature_date' => $request->departure_date,
                'adult_count' => $request->adults,
                'child_count' => $request->children ?? 0,
                'infant_count' => $request->infants ?? 0,
                'currency' => $request->currency,
            ];

            // Handle arrival date for return flights
            $journeyParams['arrival_date'] = $request->flight_type === 'return'
                ? $request->return_date
                : '-';

            // Wrap the journey parameters in the final payload
            $params = ['journey' => $journeyParams];

            \Log::debug('API Request:', $params);

            $results = $this->travelDuqaService->searchFlights($params);

            return response()->json([
                'data' => $results,
                'meta' => ['status' => 'success'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Flight Search Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Flight search failed',
                'message' => $e->getMessage(),
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
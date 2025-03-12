<?php

namespace App\Http\Controllers;

use App\Services\AmadeusService;
use App\Services\TravelDuqaService;
use Illuminate\Http\Request;

class ApiTestController extends Controller
{
    protected $amadeus;
    protected $travelDuqa;
    
    public function __construct(AmadeusService $amadeus, TravelDuqaService $travelDuqa)
    {
        $this->amadeus = $amadeus;
        $this->travelDuqa = $travelDuqa;
    }
    
    public function testAmadeus()
    {
        try {
            // Test token generation
            $reflection = new \ReflectionClass($this->amadeus);
            $method = $reflection->getMethod('getAccessToken');
            $method->setAccessible(true);
            $token = $method->invoke($this->amadeus);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully authenticated with Amadeus API',
                'token_preview' => substr($token, 0, 10) . '...',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => app()->environment('local') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
    
    public function testTravelDuqa()
    {
        try {
            // Test token generation
            $reflection = new \ReflectionClass($this->travelDuqa);
            $method = $reflection->getMethod('getAccessToken');
            $method->setAccessible(true);
            $token = $method->invoke($this->travelDuqa);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully authenticated with TravelDuqa API',
                'token_preview' => substr($token, 0, 10) . '...',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => app()->environment('local') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
}
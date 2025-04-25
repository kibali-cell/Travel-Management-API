<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PolicyController extends Controller
{
    /**
     * Retrieve a list of all policies.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $policies = Policy::all();
        return response()->json($policies);
    }

    /**
     * Store a newly created policy in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        // Check if the user is a travel_admin and restrict company_id to their own
        if ($user->hasRole('travel_admin') && $user->company_id !== $request->input('company_id')) {
            return response()->json(['message' => 'You can only create policies for your own company'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
            'flight_dynamic_pricing' => 'boolean',
            'flight_price_threshold_percent' => 'nullable|integer|min:0|max:100',
            'flight_max_amount' => 'nullable|numeric|min:0',
            'flight_advance_booking_days' => 'nullable|integer|min:0',
            'economy_class' => 'nullable|string|in:Always allowed,0-3 hour flights,3-6 hour flights,6-10 hour flights,10+ hour flights',
            'premium_economy_class' => 'nullable|string|in:Always allowed,0-3 hour flights,3-6 hour flights,6-10 hour flights,10+ hour flights',
            'business_class' => 'nullable|string|in:Always allowed,0-3 hour flights,3-6 hour flights,6-10 hour flights,10+ hour flights',
            'first_class' => 'nullable|string|in:Always allowed,0-3 hour flights,3-6 hour flights,6-10 hour flights,10+ hour flights',
            'hotel_dynamic_pricing' => 'boolean',
            'hotel_price_threshold_percent' => 'nullable|integer|min:0|max:100',
            'hotel_max_amount' => 'nullable|numeric|min:0',
            'hotel_advance_booking_days' => 'nullable|integer|min:0',
            'hotel_max_star_rating' => 'nullable|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $policy = Policy::create($request->all());
        return response()->json($policy, 201);
    }

    /**
     * Display the specified policy.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $policy = Policy::findOrFail($id);
        return response()->json($policy);
    }

    /**
     * Update the specified policy in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'company_id' => 'sometimes|required|exists:companies,id',
            'flight_dynamic_pricing' => 'sometimes|boolean',
            'flight_max_amount' => 'sometimes|nullable|numeric|min:0',
            'flight_advance_booking_days' => 'sometimes|nullable|integer|min:0',
            'economy_class' => 'sometimes|nullable|in:always,never,conditional',
            'premium_economy_class' => 'sometimes|nullable|in:always,never,conditional',
            'business_class' => 'sometimes|nullable|in:always,never,conditional',
            'first_class' => 'sometimes|nullable|in:always,never,conditional',
            'hotel_dynamic_pricing' => 'sometimes|boolean',
            'hotel_price_threshold_percent' => 'sometimes|nullable|integer|min:0|max:100',
            'hotel_max_amount' => 'sometimes|nullable|numeric|min:0',
            'hotel_advance_booking_days' => 'sometimes|nullable|integer|min:0',
            'hotel_max_star_rating' => 'sometimes|nullable|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $policy = Policy::findOrFail($id);
        $policy->update($request->all());
        return response()->json($policy);
    }

    /**
     * Remove the specified policy from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $policy = Policy::findOrFail($id);
        $policy->delete();
        return response()->json(null, 204);
    }
}
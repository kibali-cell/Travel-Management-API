<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TripController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // If admin, can see all trips in their company
        if ($user->hasRole('admin')) {
            $trips = Trip::where('company_id', $user->company_id)
                ->with('user', 'approver')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } else {
            // If employee, can only see their own trips
            $trips = Trip::where('user_id', $user->id)
                ->with('approver')
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }
        
        return response()->json($trips);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'destination' => 'required|string|max:255',
            'purpose' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        
        $trip = Trip::create([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'destination' => $request->destination,
            'purpose' => $request->purpose,
            'status' => 'pending',
        ]);
        
        return response()->json([
            'message' => 'Trip created successfully',
            'trip' => $trip->load('user')
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $trip = Trip::with('user', 'approver', 'expenses', 'bookings')->findOrFail($id);
        
        // Check if user has permission to view this trip
        if (!$user->hasRole('admin') && $trip->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($trip);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'destination' => 'sometimes|required|string|max:255',
            'purpose' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:pending,approved,rejected,completed',
            'rejection_reason' => 'required_if:status,rejected|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $trip = Trip::findOrFail($id);
        
        // Only the trip owner can update basic details
        if ($trip->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // If status is being changed to approved or rejected, only admin can do it
        if (isset($request->status) && in_array($request->status, ['approved', 'rejected'])) {
            if (!$user->hasRole('admin')) {
                return response()->json(['message' => 'Only admins can approve or reject trips'], 403);
            }
            
            // Set approval details
            if ($request->status === 'approved') {
                $trip->approved_by = $user->id;
                $trip->approved_at = now();
            } elseif ($request->status === 'rejected') {
                $trip->rejection_reason = $request->rejection_reason;
            }
        }
        
        $trip->update($request->except(['user_id', 'company_id', 'approved_by', 'approved_at']));
        
        return response()->json([
            'message' => 'Trip updated successfully',
            'trip' => $trip->fresh()->load('user', 'approver')
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $trip = Trip::findOrFail($id);
        
        // Only the trip owner or admin can delete
        if ($trip->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Cannot delete approved trips
        if ($trip->status === 'approved') {
            return response()->json(['message' => 'Cannot delete approved trips'], 403);
        }
        
        $trip->delete();
        
        return response()->json(['message' => 'Trip deleted successfully']);
    }
}
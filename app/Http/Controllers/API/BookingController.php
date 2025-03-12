<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Approval;
use App\Services\PolicyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    protected $policyService;

    public function __construct(PolicyService $policyService)
    {
        $this->policyService = $policyService;
    }

    public function index(Request $request)
    {
        $query = Trip::query();

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        return TripResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $policy = $user->company->policy;

        $request->validate([
            'type' => 'required|in:flight,hotel',
            'price' => 'required|numeric|min:0',
            'booking_date' => 'required|date|after:today',
            'details' => 'required|array',
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'type' => $request->type,
            'price' => $request->price,
            'booking_date' => $request->booking_date,
            'details' => $request->details,
            'status' => 'pending',
        ]);

        $compliance = $this->policyService->checkCompliance($booking, $policy);
        if (!$compliance['isCompliant']) {
            Approval::create([
                'booking_id' => $booking->id,
                'approver_id' => $user->manager_id ?? $user->company->users()->where('role', 'admin')->first()->id,
                'status' => 'pending',
                'comments' => implode('; ', $compliance['violations']),
            ]);
            $booking->status = 'pending_approval';
            $booking->save();
        }

        return response()->json($booking, 201);
    }
}
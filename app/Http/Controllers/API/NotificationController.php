<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    public function sendEmail(Request $request)
    {
        $validated = $request->validate([
            'recipient' => 'required|email',
            'booking' => 'required|array',
        ]);

        Mail::to($validated['recipient'])->send(new \App\Mail\BookingConfirmation($validated['booking']));
        return response()->json(['message' => 'Notification sent']);
    }
}
<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Policy;
use Carbon\Carbon;

class PolicyService
{
    public function checkCompliance(Booking $booking, Policy $policy): array
    {
        $violations = [];

        if ($booking->type === 'flight') {
            if ($policy->flight_max_amount && $booking->price > $policy->flight_max_amount) {
                $violations[] = "Flight price exceeds maximum allowed amount of {$policy->flight_max_amount}";
            }
            $daysAhead = Carbon::parse($booking->booking_date)->diffInDays(Carbon::now());
            if ($policy->flight_advance_booking_days && $daysAhead < $policy->flight_advance_booking_days) {
                $violations[] = "Flight must be booked {$policy->flight_advance_booking_days} days in advance";
            }
        } elseif ($booking->type === 'hotel') {
            if ($policy->hotel_max_amount && $booking->price > $policy->hotel_max_amount) {
                $violations[] = "Hotel price exceeds maximum allowed amount of {$policy->hotel_max_amount}";
            }
            // Add more hotel-specific checks as per Figma designs
        }

        return [
            'isCompliant' => empty($violations),
            'violations' => $violations,
        ];
    }
}
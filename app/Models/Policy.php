<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'is_active',
        'flight_dynamic_pricing',
        'flight_max_amount',
        'flight_advance_booking_days',
        'economy_class',
        'premium_economy_class',
        'business_class',
        'first_class',
        'hotel_dynamic_pricing',
        'hotel_price_threshold_percent',
        'hotel_max_amount',
        'hotel_advance_booking_days',
        'hotel_max_star_rating',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function rules()
    {
        return $this->hasMany(PolicyRule::class);
    }
}
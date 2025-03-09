<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'trip_id', 'type', 'status', 'booking_date', 'details', 'cost'
    ];

    protected $casts = [
        'details' => 'array',  // JSON field for flexible storage
        'cost' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }
}
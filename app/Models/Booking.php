<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id', 'type', 'status', 'is_policy_compliant', 
        'compliance_notes', 'cost'
    ];

    protected $casts = [
        'is_policy_compliant' => 'boolean',
        'cost' => 'decimal:2',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;


    protected $fillable = [
        'name', 'address', 'city', 'state', 'country', 
        'postal_code', 'phone', 'website', 'email', 'settings'
    ];

    protected $casts = ['settings' => 'array'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function policies()
    {
        return $this->hasMany(Policy::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}
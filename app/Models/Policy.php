<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'description', 'is_active'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function rules()
    {
        return $this->hasMany(PolicyRule::class);
    }
}
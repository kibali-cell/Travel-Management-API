<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyRule extends Model
{
    use HasFactory;

    protected $fillable = ['policy_id', 'category', 'rule_type', 'rule_value'];

    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    protected $fillable = ['policy_id', 'restriction', 'approvers'];
    protected $casts = ['approvers' => 'array'];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'department_id',
        'manager_id',
        'avatar',
        'phone',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'language',
        'email_notifications',
        'push_notifications',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function hasRole($roleName)
    {
        return $this->roles()->where('slug', $roleName)->exists();
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isEmployee()
    {
        return $this->hasRole('employee');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    
    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = \App\Models\Role::where('slug', $role)->first();
        }

        if ($role) {
            $this->roles()->syncWithoutDetaching($role->id);
        }

        return $this;
    }
}

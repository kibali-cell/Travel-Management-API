<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Create Super Admin role
        Role::updateOrCreate(
            ['slug' => 'super_admin'],
            [
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'description' => 'System administrator with full permissions'
            ]
        );

        // Create Travel Admin role
        Role::updateOrCreate(
            ['slug' => 'travel_admin'],
            [
                'name' => 'Travel Admin',
                'slug' => 'travel_admin',
                'description' => 'Company administrator who manages travel policies'
            ]
        );

        // Create Employee role
        Role::updateOrCreate(
            ['slug' => 'employee'],
            [
                'name' => 'Employee',
                'slug' => 'employee',
                'description' => 'Regular employee who books travel'
            ]
        );
    }
}

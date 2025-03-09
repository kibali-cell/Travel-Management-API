<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Travel Admin',
                'slug' => 'admin',
                'description' => 'Company administrator who manages travel policies'
            ]
        );

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

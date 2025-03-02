<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        Role::create([
            'name' => 'Travel Admin',
            'slug' => 'admin',
            'description' => 'Company administrator who manages travel policies'
        ]);

        Role::create([
            'name' => 'Employee',
            'slug' => 'employee',
            'description' => 'Regular employee who books travel'
        ]);
    }
}
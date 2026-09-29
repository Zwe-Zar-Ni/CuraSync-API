<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application roles.
     */
    public function run(): void
    {
        foreach (['patient', 'doctor', 'admin'] as $role) {
            Role::findOrCreate($role);
        }
    }
}

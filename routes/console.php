<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('roles:create', function () {
    $roles = ['patient', 'doctor', 'admin'];

    foreach ($roles as $role) {
        Role::findOrCreate($role);
        $this->info("Created role: {$role}");
    }
})->purpose('Create the patient, doctor, and admin roles');

Artisan::command('create:admin', function () {
    $user = User::firstOrCreate([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
    ]);
    $this->info("Created admin user: {$user->name}");
    $user->assignRole('admin');
    $this->info("Assigned admin role to user: {$user->name}");
});

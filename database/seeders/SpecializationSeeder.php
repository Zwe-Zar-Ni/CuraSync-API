<?php

namespace Database\Seeders;

use App\Models\Specialization;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    /**
     * Seed the specializations table.
     */
    public function run(): void
    {
        $specializations = [
            'Cardiology',
            'Dermatology',
            'Endocrinology',
            'Gastroenterology',
            'Neurology',
            'Obstetrics & Gynecology',
            'Orthopedics',
            'Pediatrics',
            'Psychiatry',
            'Pulmonology',
        ];

        foreach ($specializations as $name) {
            Specialization::firstOrCreate(['name' => $name]);
        }
    }
}

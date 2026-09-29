<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DoctorScheduleSeeder extends Seeder
{
    /**
     * Seed the weekly schedules for the seeded doctors.
     *
     * Day indexes follow the doctor_schedules enum, where 0 = Sunday.
     */
    public function run(): void
    {
        foreach ($this->schedules() as $email => $blocks) {
            $doctor = $this->doctorFor($email);

            DB::transaction(function () use ($doctor, $blocks) {
                foreach ($blocks as $block) {
                    foreach ($block['days'] as $day) {
                        DoctorSchedule::updateOrCreate(
                            [
                                'doctor_id' => $doctor->id,
                                'day_of_week' => (string) $day,
                                'start_time' => $block['start_time'],
                            ],
                            [
                                'end_time' => $block['end_time'],
                                'slot_duration_minutes' => $block['slot_duration_minutes'],
                                'is_active' => $block['is_active'],
                            ],
                        );
                    }
                }
            });
        }
    }

    /**
     * Resolve the doctor profile for a seeded user email.
     */
    private function doctorFor(string $email): Doctor
    {
        $doctor = User::where('email', $email)->first()?->doctor;

        if (! $doctor) {
            throw new RuntimeException(
                "No doctor profile for {$email}. DoctorSeeder must run before DoctorScheduleSeeder."
            );
        }

        return $doctor;
    }

    /**
     * The schedules to seed, keyed by the doctor's user email.
     *
     * Each entry is a recurring block expanded across its listed days.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function schedules(): array
    {
        return [
            // Weekday mornings and evenings, plus Saturday morning clinic.
            'aungmyat@curasync.test' => [
                ['days' => [1, 2, 3, 4, 5], 'start_time' => '09:00', 'end_time' => '12:00', 'slot_duration_minutes' => 20, 'is_active' => true],
                ['days' => [1, 3, 5], 'start_time' => '14:00', 'end_time' => '17:00', 'slot_duration_minutes' => 30, 'is_active' => true],
                ['days' => [6], 'start_time' => '09:00', 'end_time' => '12:00', 'slot_duration_minutes' => 20, 'is_active' => true],
            ],
            // Works Sundays too, given the vaccination clinic.
            'thidawin@curasync.test' => [
                ['days' => [0, 1, 2, 3, 4, 5, 6], 'start_time' => '08:30', 'end_time' => '11:30', 'slot_duration_minutes' => 15, 'is_active' => true],
                ['days' => [1, 2, 3, 4, 5], 'start_time' => '15:00', 'end_time' => '18:00', 'slot_duration_minutes' => 15, 'is_active' => true],
            ],
            // Tuesday to Saturday, no weekends. Second slot left inactive.
            'minthiha@curasync.test' => [
                ['days' => [2, 3, 4, 5, 6], 'start_time' => '10:00', 'end_time' => '13:00', 'slot_duration_minutes' => 20, 'is_active' => true],
                ['days' => [2, 4, 6], 'start_time' => '15:00', 'end_time' => '18:00', 'slot_duration_minutes' => 20, 'is_active' => false],
            ],
            // Long surgical sessions, 30 minute slots.
            'hlaingkyaw@curasync.test' => [
                ['days' => [1, 2, 3, 4], 'start_time' => '08:00', 'end_time' => '12:00', 'slot_duration_minutes' => 30, 'is_active' => true],
                ['days' => [1, 2, 3, 4, 5], 'start_time' => '14:00', 'end_time' => '17:00', 'slot_duration_minutes' => 30, 'is_active' => true],
            ],
            // Weekdays plus Sunday antenatal clinic.
            'suhlaing@curasync.test' => [
                ['days' => [1, 2, 3, 4, 5], 'start_time' => '09:00', 'end_time' => '12:00', 'slot_duration_minutes' => 15, 'is_active' => true],
                ['days' => [0], 'start_time' => '09:00', 'end_time' => '12:00', 'slot_duration_minutes' => 20, 'is_active' => true],
            ],
            // Longer 40 minute neurology slots, clinic-based rather than daily.
            'naingzin@curasync.test' => [
                ['days' => [1, 4], 'start_time' => '09:00', 'end_time' => '13:00', 'slot_duration_minutes' => 40, 'is_active' => true],
                ['days' => [2, 5], 'start_time' => '14:00', 'end_time' => '18:00', 'slot_duration_minutes' => 40, 'is_active' => true],
            ],
            'khinmalwin@curasync.test' => [
                ['days' => [1, 2, 3, 4, 5], 'start_time' => '08:30', 'end_time' => '12:00', 'slot_duration_minutes' => 15, 'is_active' => true],
                ['days' => [2, 4], 'start_time' => '14:00', 'end_time' => '17:00', 'slot_duration_minutes' => 20, 'is_active' => true],
                ['days' => [6], 'start_time' => '09:00', 'end_time' => '12:00', 'slot_duration_minutes' => 15, 'is_active' => true],
            ],
            'zawzawnaing@curasync.test' => [
                ['days' => [1, 2, 3, 4, 5, 6], 'start_time' => '10:00', 'end_time' => '13:00', 'slot_duration_minutes' => 20, 'is_active' => true],
                ['days' => [0], 'start_time' => '10:00', 'end_time' => '12:00', 'slot_duration_minutes' => 20, 'is_active' => true],
            ],
            // Afternoon-only psychiatry practice.
            'myomyonyein@curasync.test' => [
                ['days' => [1, 2, 3, 4, 5], 'start_time' => '14:00', 'end_time' => '18:00', 'slot_duration_minutes' => 45, 'is_active' => true],
            ],
            'thethar@curasync.test' => [
                ['days' => [1, 2, 3, 4, 5], 'start_time' => '09:00', 'end_time' => '12:00', 'slot_duration_minutes' => 30, 'is_active' => true],
                ['days' => [1, 2, 3, 4, 5], 'start_time' => '14:00', 'end_time' => '16:00', 'slot_duration_minutes' => 30, 'is_active' => true],
                ['days' => [6], 'start_time' => '09:00', 'end_time' => '11:00', 'slot_duration_minutes' => 30, 'is_active' => true],
            ],
        ];
    }
}

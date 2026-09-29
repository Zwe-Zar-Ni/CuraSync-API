<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorQualification;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DoctorQualificationSeeder extends Seeder
{
    /**
     * Seed the qualifications for the seeded doctors.
     */
    public function run(): void
    {
        foreach ($this->qualifications() as $email => $qualifications) {
            $doctor = $this->doctorFor($email);

            DB::transaction(function () use ($doctor, $qualifications) {
                foreach ($qualifications as $qualification) {
                    DoctorQualification::updateOrCreate(
                        [
                            'doctor_id' => $doctor->id,
                            'name' => $qualification['name'],
                            'institution' => $qualification['institution'],
                            'year' => $qualification['year'],
                        ],
                        [
                            'certificate_url' => $qualification['certificate_url'] ?? null,
                        ],
                    );
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
                "No doctor profile for {$email}. DoctorSeeder must run before DoctorQualificationSeeder."
            );
        }

        return $doctor;
    }

    /**
     * The qualifications to seed, keyed by the doctor's user email.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function qualifications(): array
    {
        return [
            'aungmyat@curasync.test' => [
                [
                    'name' => 'MBBS',
                    'institution' => 'University of Medicine 1, Yangon',
                    'year' => 2008,
                ],
                [
                    'name' => 'Master of Medicine (Internal Medicine)',
                    'institution' => 'Postgraduate Institute of Medicine, Yangon',
                    'year' => 2013,
                ],
                [
                    'name' => 'Fellowship in Interventional Cardiology',
                    'institution' => 'National Heart Centre, Singapore',
                    'year' => 2016,
                    'certificate_url' => 'https://cdn.curasync.test/certificates/aungmyat-fic.pdf',
                ],
            ],
            'thidawin@curasync.test' => [
                [
                    'name' => 'MBBS',
                    'institution' => 'University of Medicine 2, Yangon',
                    'year' => 2009,
                ],
                [
                    'name' => 'Master of Medicine (Paediatrics)',
                    'institution' => 'Yangon Children Hospital',
                    'year' => 2015,
                ],
            ],
            'minthiha@curasync.test' => [
                [
                    'name' => 'MBBS',
                    'institution' => 'University of Medicine 1, Yangon',
                    'year' => 2010,
                ],
                [
                    'name' => 'Master of Medicine (Dermatology)',
                    'institution' => 'Postgraduate Institute of Medicine, Yangon',
                    'year' => 2016,
                ],
                [
                    'name' => 'Diploma in Cosmetic Dermatology',
                    'institution' => 'Bangkok Dermatological Institute',
                    'year' => 2019,
                ],
            ],
            'hlaingkyaw@curasync.test' => [
                [
                    'name' => 'MBBS',
                    'institution' => 'University of Medicine, Mandalay',
                    'year' => 2007,
                ],
                [
                    'name' => 'Master of Medicine (Orthopaedic Surgery)',
                    'institution' => 'Mahabandula Medical University, Mandalay',
                    'year' => 2013,
                ],
                [
                    'name' => 'Fellowship in Arthroscopic Surgery',
                    'institution' => 'Fortis Hospital, New Delhi',
                    'year' => 2018,
                ],
            ],
            'suhlaing@curasync.test' => [
                [
                    'name' => 'MBBS',
                    'institution' => 'University of Medicine 1, Yangon',
                    'year' => 2009,
                ],
                [
                    'name' => 'Master of Medicine (Obstetrics and Gynaecology)',
                    'institution' => 'Yangon General Hospital',
                    'year' => 2015,
                ],
                [
                    'name' => 'Diploma in Reproductive Medicine',
                    'institution' => 'Bangkok Hospital, Thailand',
                    'year' => 2020,
                ],
            ],
            'naingzin@curasync.test' => [
                [
                    'name' => 'MBBS',
                    'institution' => 'University of Medicine 2, Yangon',
                    'year' => 2008,
                ],
                [
                    'name' => 'Master of Medicine (Neurology)',
                    'institution' => 'Postgraduate Institute of Medicine, Yangon',
                    'year' => 2014,
                ],
                [
                    'name' => 'Fellowship in Stroke Medicine',
                    'institution' => 'St Georges Hospital, London',
                    'year' => 2017,
                ],
            ],
            'khinmalwin@curasync.test' => [
                [
                    'name' => 'MBBS',
                    'institution' => 'University of Medicine 1, Yangon',
                    'year' => 2011,
                ],
                [
                    'name' => 'Master of Medicine (Endocrinology)',
                    'institution' => 'Yangon General Hospital',
                    'year' => 2017,
                ],
            ],
            'zawzawnaing@curasync.test' => [
                [
                    'name' => 'MBBS',
                    'institution' => 'University of Medicine, Mandalay',
                    'year' => 2006,
                ],
                [
                    'name' => 'Master of Medicine (Respiratory Medicine)',
                    'institution' => 'Postgraduate Institute of Medicine, Yangon',
                    'year' => 2012,
                ],
                [
                    'name' => 'Fellowship in Sleep Medicine',
                    'institution' => 'National University Hospital, Singapore',
                    'year' => 2018,
                ],
            ],
            'myomyonyein@curasync.test' => [
                [
                    'name' => 'MBBS',
                    'institution' => 'Defence Services Medical Academy',
                    'year' => 2010,
                ],
                [
                    'name' => 'Master of Medicine (Psychiatry)',
                    'institution' => 'Postgraduate Institute of Medicine, Yangon',
                    'year' => 2016,
                ],
            ],
            'thethar@curasync.test' => [
                [
                    'name' => 'MBBS',
                    'institution' => 'University of Medicine 2, Yangon',
                    'year' => 2009,
                ],
                [
                    'name' => 'Master of Medicine (Gastroenterology)',
                    'institution' => 'Yangon General Hospital',
                    'year' => 2015,
                ],
                [
                    'name' => 'Fellowship in Diagnostic Endoscopy',
                    'institution' => 'Apollo Hospitals, Chennai',
                    'year' => 2019,
                ],
            ],
        ];
    }
}

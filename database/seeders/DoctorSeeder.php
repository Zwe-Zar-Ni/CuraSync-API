<?php

namespace Database\Seeders;

use App\Enums\DoctorStatus;
use App\Models\Doctor;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoctorSeeder extends Seeder
{
    /**
     * Seed doctor user accounts and their profiles.
     */
    public function run(): void
    {
        $specializationIds = Specialization::whereIn('name', array_column($this->doctors(), 'specialization'))
            ->pluck('id', 'name');

        if ($specializationIds->count() !== count(array_unique(array_column($this->doctors(), 'specialization')))) {
            throw new \RuntimeException('DoctorSeeder requires SpecializationSeeder to have run first.');
        }

        foreach ($this->doctors() as $record) {
            DB::transaction(function () use ($record, $specializationIds) {
                $this->seedDoctor($record, $specializationIds);
            });
        }
    }

    /**
     * Seed a single doctor: the user account, the doctor profile, and the specialty pivot.
     *
     * @param  array<string, mixed>  $record
     * @param  Collection<string, int>  $specializationIds
     */
    private function seedDoctor(array $record, $specializationIds): void
    {
        $user = User::withTrashed()->updateOrCreate(
            ['email' => $record['email']],
            [
                'name' => $record['name'],
                'password' => 'internet',
                'email_verified_at' => now(),
            ],
        );

        $user->restore();
        $user->forceFill([
            'phone_number' => $record['phone_number'],
            'profile_url' => $record['profile_url'],
        ])->save();

        $user->assignRole('doctor');

        $doctor = Doctor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => DoctorStatus::Active->value,
                'license_number' => $record['license_number'],
                'standard_consultation_fee' => $record['standard_consultation_fee'],
                'bio' => $record['bio'],
                'total_patient_count' => $record['total_patient_count'],
                'rating_count' => $record['rating_count'],
                'average_rating' => $record['average_rating'],
            ],
        );

        $doctor->specializations()->sync([$specializationIds->get($record['specialization'])]);
    }

    /**
     * The doctors to seed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function doctors(): array
    {
        return [
            [
                'name' => 'Dr. Aung Myat Oo',
                'email' => 'aungmyat@curasync.test',
                'phone_number' => '+95 09 250 1101',
                'profile_url' => '/images/doctors/aungmyat.png',
                'license_number' => 'MM-D-10001',
                'specialization' => 'Cardiology',
                'standard_consultation_fee' => 35000.00,
                'bio' => 'Interventional cardiologist with 12 years of experience in echocardiography and coronary angioplasty. Consults at Yangon General Hospital and previously trained in Singapore.',
                'total_patient_count' => 1240,
                'rating_count' => 310,
                'average_rating' => 4.8,
            ],
            [
                'name' => 'Dr. Thida Win',
                'email' => 'thidawin@curasync.test',
                'phone_number' => '+95 09 250 1102',
                'profile_url' => '/images/doctors/thidawin.png',
                'license_number' => 'MM-D-10002',
                'specialization' => 'Pediatrics',
                'standard_consultation_fee' => 20000.00,
                'bio' => 'Paediatrician focused on neonatal care and childhood asthma. Runs a vaccination clinic six days a week and speaks English, Burmese and Karen.',
                'total_patient_count' => 1980,
                'rating_count' => 512,
                'average_rating' => 4.9,
            ],
            [
                'name' => 'Dr. Min Thiha',
                'email' => 'minthiha@curasync.test',
                'phone_number' => '+95 09 250 1103',
                'profile_url' => '/images/doctors/minthiha.png',
                'license_number' => 'MM-D-10003',
                'specialization' => 'Dermatology',
                'standard_consultation_fee' => 25000.00,
                'bio' => 'Dermatologist treating chronic eczema, psoriasis and acne, with an interest in cosmetic procedures. Certified in dermatoscopy and minor skin surgery.',
                'total_patient_count' => 860,
                'rating_count' => 204,
                'average_rating' => 4.6,
            ],
            [
                'name' => 'Dr. Hlaing Kyaw',
                'email' => 'hlaingkyaw@curasync.test',
                'phone_number' => '+95 09 250 1104',
                'profile_url' => '/images/doctors/hlaingkyaw.png',
                'license_number' => 'MM-D-10004',
                'specialization' => 'Orthopedics',
                'standard_consultation_fee' => 40000.00,
                'bio' => 'Orthopaedic surgeon specialising in joint replacement and sports injury. Performs arthroscopic ACL repair and manages post-operative physiotherapy planning.',
                'total_patient_count' => 720,
                'rating_count' => 158,
                'average_rating' => 4.5,
            ],
            [
                'name' => 'Dr. Su Hlaing',
                'email' => 'suhlaing@curasync.test',
                'phone_number' => '+95 09 250 1105',
                'profile_url' => '/images/doctors/suhlaing.png',
                'license_number' => 'MM-D-10005',
                'specialization' => 'Obstetrics & Gynecology',
                'standard_consultation_fee' => 30000.00,
                'bio' => 'Obstetrician and gynaecologist providing antenatal care, delivery services and fertility counselling, with special interest in high-risk pregnancy management.',
                'total_patient_count' => 1450,
                'rating_count' => 402,
                'average_rating' => 4.7,
            ],
            [
                'name' => 'Dr. Naing Zin',
                'email' => 'naingzin@curasync.test',
                'phone_number' => '+95 09 250 1106',
                'profile_url' => '/images/doctors/naingzin.png',
                'license_number' => 'MM-D-10006',
                'specialization' => 'Neurology',
                'standard_consultation_fee' => 45000.00,
                'bio' => 'Neurologist managing epilepsy, stroke rehabilitation and movement disorders. Reads EEG and brain imaging and is involved in a rural stroke outreach programme.',
                'total_patient_count' => 640,
                'rating_count' => 143,
                'average_rating' => 4.4,
            ],
            [
                'name' => 'Dr. Khin Ma Lwin',
                'email' => 'khinmalwin@curasync.test',
                'phone_number' => '+95 09 250 1107',
                'profile_url' => '/images/doctors/khinmalwin.png',
                'license_number' => 'MM-D-10007',
                'specialization' => 'Endocrinology',
                'standard_consultation_fee' => 30000.00,
                'bio' => 'Endocrinologist treating type 1 and type 2 diabetes, thyroid disease and PCOS. Runs a nurse-led diabetes education programme alongside clinic hours.',
                'total_patient_count' => 1105,
                'rating_count' => 287,
                'average_rating' => 4.7,
            ],
            [
                'name' => 'Dr. Zaw Zaw Naing',
                'email' => 'zawzawnaing@curasync.test',
                'phone_number' => '+95 09 250 1108',
                'profile_url' => '/images/doctors/zawzawnaing.png',
                'license_number' => 'MM-D-10008',
                'specialization' => 'Pulmonology',
                'standard_consultation_fee' => 28000.00,
                'bio' => 'Respiratory physician covering asthma, COPD and tuberculosis follow-up, with expertise in sleep studies and long-term inhaler therapy for COPD patients.',
                'total_patient_count' => 980,
                'rating_count' => 226,
                'average_rating' => 4.6,
            ],
            [
                'name' => 'Dr. Myo Myo Nyein',
                'email' => 'myomyonyein@curasync.test',
                'phone_number' => '+95 09 250 1109',
                'profile_url' => '/images/doctors/myomyonyein.png',
                'license_number' => 'MM-D-10009',
                'specialization' => 'Psychiatry',
                'standard_consultation_fee' => 25000.00,
                'bio' => 'Psychiatrist providing medication management and psychological therapy for depression, anxiety and bipolar disorder. Offers confidential telehealth sessions.',
                'total_patient_count' => 530,
                'rating_count' => 121,
                'average_rating' => 4.5,
            ],
            [
                'name' => 'Dr. Thet Htar',
                'email' => 'thethar@curasync.test',
                'phone_number' => '+95 09 250 1110',
                'profile_url' => '/images/doctors/thethar.png',
                'license_number' => 'MM-D-10010',
                'specialization' => 'Gastroenterology',
                'standard_consultation_fee' => 32000.00,
                'bio' => 'Gastroenterologist diagnosing and treating chronic liver disease, peptic ulcers and inflammatory bowel conditions, with expertise in endoscopy and colonoscopy.',
                'total_patient_count' => 775,
                'rating_count' => 189,
                'average_rating' => 4.6,
            ],
        ];
    }
}

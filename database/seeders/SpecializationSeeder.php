<?php

namespace Database\Seeders;

use App\Models\Specialization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SpecializationSeeder extends Seeder
{
    /**
     * Seed the specializations table.
     */
    public function run(): void
    {
        foreach ($this->specializations() as $specialization) {
            Specialization::updateOrCreate(
                ['name' => $specialization['name']],
                $specialization,
            );
        }
    }

    /**
     * The specializations to seed.
     *
     * @return array<int, array{name: string, name_mm: string, description: string, icon_url: string}>
     */
    private function specializations(): array
    {
        return [
            [
                'name' => 'Cardiology',
                'name_mm' => 'နှလုံး',
                'description' => 'Diagnoses and treats diseases of the heart, blood vessels and circulation. Covers chest pain, hypertension, heart failure, arrhythmias, heart attack and cardiac imaging such as ECG and echocardiogram.',
                'icon_url' => $this->iconUrl('cardiology'),
            ],
            [
                'name' => 'Dermatology',
                'name_mm' => 'အရေပြား',
                'description' => 'Care for skin, hair and nail conditions including acne, eczema, psoriasis, rashes, infections and skin cancer. Performs skin checks, biopsies and cosmetic dermatology.',
                'icon_url' => $this->iconUrl('dermatology'),
            ],
            [
                'name' => 'Endocrinology',
                'name_mm' => 'ဟော်မုန်းနှင့် ဆီးချို',
                'description' => 'Manages hormones and metabolic disorders such as diabetes, thyroid disease, PCOS and osteoporosis. Handles insulin, thyroid medication and hormone replacement therapy.',
                'icon_url' => $this->iconUrl('endocrinology'),
            ],
            [
                'name' => 'Gastroenterology',
                'name_mm' => 'အစာအိမ်နှင့် အူလမ်းကြောင်း',
                'description' => 'Treats the digestive tract, including the oesophagus, stomach, intestines, liver, pancreas and gallbladder. Addresses acidity, IBS, ulcers, hepatitis, gallstones and colonoscopy.',
                'icon_url' => $this->iconUrl('gastroenterology'),
            ],
            [
                'name' => 'Neurology',
                'name_mm' => 'ဦးနှောက်နှင့် အာရုံကြော',
                'description' => 'Specializes in the brain, spinal cord, nerves and muscles. Treats migraine, epilepsy, stroke, neuropathy, Parkinson disease and multiple sclerosis with EEG and brain imaging.',
                'icon_url' => $this->iconUrl('neurology'),
            ],
            [
                'name' => 'Obstetrics & Gynecology',
                'name_mm' => 'သားဖွားနှင့်မီးယပ်',
                'description' => 'Covers pregnancy, childbirth, fertility and women’s reproductive health. Includes antenatal care, deliveries, contraception, PCOS, menstrual disorders and cervical screening.',
                'icon_url' => $this->iconUrl('obstetrics-gynecology'),
            ],
            [
                'name' => 'Orthopedics',
                'name_mm' => 'အရိုးအထူးကု',
                'description' => 'Cares for bones, joints, ligaments, tendons and the spine. Manages fractures, arthritis, joint replacement, sports injuries, scoliosis and physiotherapy planning.',
                'icon_url' => $this->iconUrl('orthopedics'),
            ],
            [
                'name' => 'Pediatrics',
                'name_mm' => 'ကလေး',
                'description' => 'Medical care for infants, children and adolescents, from newborn checkups to growth monitoring. Treats childhood infections, asthma, allergies, vaccinations and developmental concerns.',
                'icon_url' => $this->iconUrl('pediatrics'),
            ],
            [
                'name' => 'Psychiatry',
                'name_mm' => 'စိတ်ကျန်းမာရေး',
                'description' => 'Diagnosis and treatment of mental health conditions including depression, anxiety, bipolar disorder, schizophrenia and eating disorders. Provides counselling, medication management and psychological therapy.',
                'icon_url' => $this->iconUrl('psychiatry'),
            ],
            [
                'name' => 'Pulmonology',
                'name_mm' => 'အဆုတ်နှင့် အသက်ရှူလမ်းကြောင်း',
                'description' => 'Specializes in the lungs and airways, treating asthma, COPD, pneumonia, tuberculosis, bronchitis and sleep apnea. Performs lung function tests, chest imaging and long-term breathing care.',
                'icon_url' => $this->iconUrl('pulmonology'),
            ],
        ];
    }

    /**
     * Build the icon path for a specialization slug.
     */
    private function iconUrl(string $slug): string
    {
        return '/images/specializations/'.Str::slug($slug).'.png';
    }
}

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
            'Cardiology' => 'Diagnoses and treats diseases of the heart, blood vessels and circulation. Covers chest pain, hypertension, heart failure, arrhythmias, heart attack and cardiac imaging such as ECG and echocardiogram.',
            'Dermatology' => 'Care for skin, hair and nail conditions including acne, eczema, psoriasis, rashes, infections and skin cancer. Performs skin checks, biopsies and cosmetic dermatology.',
            'Endocrinology' => 'Manages hormones and metabolic disorders such as diabetes, thyroid disease, PCOS and osteoporosis. Handles insulin, thyroid medication and hormone replacement therapy.',
            'Gastroenterology' => 'Treats the digestive tract, including the oesophagus, stomach, intestines, liver, pancreas and gallbladder. Addresses acidity, IBS, ulcers, hepatitis, gallstones and colonoscopy.',
            'Neurology' => 'Specializes in the brain, spinal cord, nerves and muscles. Treats migraine, epilepsy, stroke, neuropathy, Parkinson disease and multiple sclerosis with EEG and brain imaging.',
            'Obstetrics & Gynecology' => 'Covers pregnancy, childbirth, fertility and women’s reproductive health. Includes antenatal care, deliveries, contraception, PCOS, menstrual disorders and cervical screening.',
            'Orthopedics' => 'Cares for bones, joints, ligaments, tendons and the spine. Manages fractures, arthritis, joint replacement, sports injuries, scoliosis and physiotherapy planning.',
            'Pediatrics' => 'Medical care for infants, children and adolescents, from newborn checkups to growth monitoring. Treats childhood infections, asthma, allergies, vaccinations and developmental concerns.',
            'Psychiatry' => 'Diagnosis and treatment of mental health conditions including depression, anxiety, bipolar disorder, schizophrenia and eating disorders. Provides counselling, medication management and psychological therapy.',
            'Pulmonology' => 'Specializes in the lungs and airways, treating asthma, COPD, pneumonia, tuberculosis, bronchitis and sleep apnea. Performs lung function tests, chest imaging and long-term breathing care.',
        ];

        foreach ($specializations as $name => $description) {
            Specialization::updateOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }
    }
}

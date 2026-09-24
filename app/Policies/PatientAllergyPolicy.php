<?php

namespace App\Policies;

use App\Exceptions\CustomApiException;
use App\Models\PatientAllergy;
use App\Models\User;

class PatientAllergyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->patient !== null;
    }

    public function create(User $user): bool
    {
        return $user->patient !== null;
    }

    public function view(User $user, PatientAllergy $allergy): bool
    {
        return $this->owns($user, $allergy);
    }

    public function update(User $user, PatientAllergy $allergy): bool
    {
        return $this->owns($user, $allergy);
    }

    public function delete(User $user, PatientAllergy $allergy): bool
    {
        return $this->owns($user, $allergy);
    }

    private function owns(User $user, PatientAllergy $allergy): bool
    {
        if ($allergy->patient_id === $user->patient?->id) {
            return true;
        }

        throw new CustomApiException([], 404, 'Allergy not found.');
    }
}

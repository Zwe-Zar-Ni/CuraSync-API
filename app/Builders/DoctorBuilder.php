<?php

namespace App\Builders;

use App\Enums\DoctorStatus;
use Illuminate\Database\Eloquent\Builder;

class DoctorBuilder extends Builder
{
    public function whereActive(): self
    {
        return $this->where('status', DoctorStatus::Active->value);
    }

    public function whereSpecialization(?int $specializationId): self
    {
        if ($specializationId) {
            return $this->whereHas('specializations', function ($query) use ($specializationId) {
                $query->where('specialization_id', $specializationId);
            });
        } else {
            return $this;
        }
    }
}

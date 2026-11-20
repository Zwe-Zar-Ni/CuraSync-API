<?php

namespace App\Http\Resources;

use App\Http\Resources\Patient\AllergyResource;
use App\Http\Resources\Patient\ConditionResource;
use App\Http\Resources\Patient\ContactResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $allergies = $this->whenLoaded('allergies', function () {
            return $this->allergies->map(function ($allergy) {
                return new AllergyResource($allergy);
            });
        });
        $conditions = $this->whenLoaded('conditions', function () {
            return $this->conditions->map(function ($condition) {
                return new ConditionResource($condition);
            });
        });
        $contacts = $this->whenLoaded('contacts', function () {
            return $this->contacts->map(function ($contact) {
                return new ContactResource($contact);
            });
        });

        return [
            'id' => $this->id,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'blood_type' => $this->blood_type,
            'allergies' => $allergies,
            'conditions' => $conditions,
            'contacts' => $contacts,
            'name' => $this->whenLoaded('user', function () {
                return $this->user->name;
            }),
            'email' => $this->whenLoaded('user', function () {
                return $this->user->email;
            }),
            'phone_number' => $this->whenLoaded('user', function () {
                return $this->user->phone_number;
            }),
            'profile_image' => $this->whenLoaded('user', function () {
                return $this->user->profile_image ? $this->user->profile_image->url : null;
            }),
        ];
    }
}

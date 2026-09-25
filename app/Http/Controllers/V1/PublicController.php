<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\Specialization;
use Illuminate\Http\Request;

class PublicController extends BaseController
{
    public function getSpecializations()
    {
        $specializations = Specialization::select('id', 'name')->get();

        return $this->success($specializations);
    }

    public function getDoctors(Request $request)
    {
        $per_page = $request->per_page ?? 15;
        $doctors = Doctor::whereActive()->whereSpecialization($request->specialization_id)->with([
            'user' => function ($query) {
                $query->select('id', 'name', 'email');
            },
            'specializations',
        ])->orderBy('created_at', 'asc')->paginate($per_page);

        return $this->success([
            'data' => DoctorResource::collection($doctors),
            'meta' => $this->extractPaginationMeta($doctors),
        ]);
    }

    public function getDoctorDetails(int $id)
    {
        $doctor = Doctor::whereActive()->with([
            'user' => function ($query) {
                $query->select('id', 'name', 'email');
            },
            'specializations',
        ])->find($id);
        if (! $doctor) {
            return $this->error('Doctor not found', 404);
        }

        return $this->success(new DoctorResource($doctor));
    }
}

<?php

namespace App\Http\Controllers\V1\Public;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Public\DoctorResource;
use App\Http\Resources\Public\SpecializationResource;
use App\Models\Doctor;
use App\Models\Specialization;
use Illuminate\Http\Request;

class DoctorController extends BaseController
{
    public function getSpecializations()
    {
        $specializations = Specialization::get();
        return $this->success(SpecializationResource::collection($specializations));
    }

    public function getSpecializationDetails(int $id)
    {
        $specialization = Specialization::with(['doctors' => function ($q) {
            return $q->whereActive()->with('user');
        }])->find($id);
        return $this->success(new SpecializationResource($specialization));
    }

    public function getDoctorDetails(int $id)
    {
        $doctor = Doctor::whereActive()->with(['user', 'specializations',])->find($id);
        if (! $doctor) {
            return $this->error('Doctor not found', 404);
        }

        return $this->success(new DoctorResource($doctor));
    }

    public function search(Request $request)
    {
        $q = $request->query('query', "");
        if (empty($q)) {
            return $this->error('Please enter a query', 400);
        }
        if (strlen($q) < 3) {
            return $this->error('Query too short', 400);
        }
        $specializations = Specialization::where('name', 'like', '%' . $q . '%')->orWhere('description', 'like', '%' . $q . '%')->get();

        $doctors = Doctor::whereActive()->whereHas('specializations', function ($query) use ($specializations) {
            $query->whereIn('specialization_id', $specializations->pluck('id'));
        })->orWhereHas('user', function ($query) use ($q) {
            $query->where('name', 'like', '%' . $q . '%')->orWhere('bio', 'like', '%' . $q . '%');
        })->with('user')->limit(10)->get();

        return $this->success([
            'specializations' => SpecializationResource::collection($specializations),
            'doctors' => DoctorResource::collection($doctors),
        ]);
    }

    // public function index(Request $request)
    // {
    //     $per_page = $request->per_page ?? 10;
    //     $doctors = Doctor::whereActive()
    //         ->whereSpecialization($request->specialization_id)
    //         ->with(['user', 'specializations',])
    //         ->orderBy(
    //             'created_at',
    //             'asc'
    //         )->paginate($per_page);

    //     return $this->success([
    //         'data' => DoctorResource::collection($doctors),
    //         'meta' => $this->extractPaginationMeta($doctors),
    //     ]);
    // }
}

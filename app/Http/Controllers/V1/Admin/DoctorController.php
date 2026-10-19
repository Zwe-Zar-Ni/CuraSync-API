<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Doctor;
use App\Http\Resources\Public\DoctorResource;
use Carbon\Carbon;

class DoctorController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Doctor::with('user')->latest();
        if ($request->filled('search')) {
            $query->whereHas('user', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('email')) {
            $query->whereHas('user', function ($query) use ($request) {
                $query->where('email', $request->email);
            });
        }
        if ($request->filled('phone_number')) {
            $query->whereHas('user', function ($query) use ($request) {
                $query->where('phone_number', $request->phone_number);
            });
        }
        if ($request->filled('specialization_id')) {
            $query->whereHas('specializations', function ($query) use ($request) {
                $query->where('specialization_id', $request->specialization_id);
            });
        }
        $doctors = $query->paginate($request->query('per_page', 10));
        return $this->success([
            'data' => DoctorResource::collection($doctors),
            'meta' => $this->extractPaginationMeta($doctors),
        ], 'Doctors fetched successfully.');
    }

    public function show($id): JsonResponse
    {
        $lastSevenDays = Carbon::now()->subDays(7);
        $doctor = Doctor::with([
            'user',
            'specializations',
            'qualifications',
            'schedules',
            'scheduleOverrides' => function ($query) use ($lastSevenDays) {
                $query->whereDate('date', '>=', $lastSevenDays);
            }
        ])->find($id);
        if (! $doctor) {
            return $this->error('Doctor not found.', 404);
        }
        return $this->success(DoctorResource::make($doctor));
    }
}

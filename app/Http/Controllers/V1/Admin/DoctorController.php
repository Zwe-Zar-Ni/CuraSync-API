<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Http\Resources\ProfileResource;

class DoctorController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = User::role('doctor')->with('doctor')->latest();
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->whereHas('doctor', function ($query) use ($request) {
                $query->where('status', $request->status);
            });
        }
        if ($request->filled('email')) {
            $query->where('email', $request->email);
        }
        if ($request->filled('phone_number')) {
            $query->where('phone_number', $request->phone_number);
        }
        $doctors = $query->paginate($request->query('per_page', 10));
        return $this->success([
            'data' => ProfileResource::collection($doctors),
            'meta' => $this->extractPaginationMeta($doctors),
        ], 'Doctors fetched successfully.');
    }
}

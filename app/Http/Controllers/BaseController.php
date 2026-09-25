<?php

namespace App\Http\Controllers;

class BaseController extends Controller
{
    public function baseResponse($data, $errors = [], $message = '', $status = 200)
    {
        return response()->json([
            'data' => $data,
            'errors' => $errors,
            'message' => $message,
            'status' => $status,
        ]);
    }

    public function success($data, $message = '', $status = 200)
    {
        return $this->baseResponse($data, [], $message, $status);
    }

    public function error($errors, $message = '', $status = 400)
    {
        return $this->baseResponse([], $errors, $message, $status);
    }

    public function extractPaginationMeta($data)
    {
        return [
            'currentPage' => $data->currentPage(),
            'perPage' => $data->perPage(),
            'total' => $data->total(),
            'lastPage' => $data->lastPage(),
            'hasMorePages' => $data->hasMorePages(),
            'nextPageUrl' => $data->nextPageUrl(),
            'previousPageUrl' => $data->previousPageUrl(),
        ];
    }
}

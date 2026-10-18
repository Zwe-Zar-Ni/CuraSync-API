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
        ], $status);
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
            'hasMorePages' => $data->hasMorePages(),
            'lastPage' => $data->lastPage(),
            'nextPageUrl' => $data->nextPageUrl(),
            'perPage' => $data->perPage(),
            'previousPageUrl' => $data->previousPageUrl(),
            'total' => $data->total(),
        ];
    }
}

<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\BaseController;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;

class JwksController extends BaseController
{
    public function __construct(private readonly JwtService $jwt) {}

    public function jwks(): JsonResponse
    {
        return response()->json($this->jwt->jwks());
    }

    public function configuration(): JsonResponse
    {
        $issuer = rtrim((string) config('jwt.issuer'), '/');

        return response()->json([
            'issuer' => $issuer,
            'jwks_uri' => $issuer.'/.well-known/jwks.json',
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => [config('jwt.algo', 'RS256')],
            'scopes_supported' => ['openid'],
        ]);
    }
}

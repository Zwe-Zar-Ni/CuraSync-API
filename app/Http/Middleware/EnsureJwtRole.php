<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureJwtRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->attributes->get('jwt.role');

        if (! in_array($role, $roles, true)) {
            throw new AccessDeniedHttpException('Forbidden.');
        }

        return $next($request);
    }
}

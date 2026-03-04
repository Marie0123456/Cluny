<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            abort(403, 'Accès non autorisé.');
        }

        $allowedRoles = array_map(fn ($r) => Role::from($r), $roles);

        if (! in_array($request->user()->role, $allowedRoles)) {
            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}

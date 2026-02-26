<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureConcoursAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $concours = $request->route('concours');

        $concoursId = is_object($concours) ? $concours->id : $concours;

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (! $user->concours()->where('concours.id', $concoursId)->exists()) {
            abort(403, 'Vous n\'avez pas accès à ce concours.');
        }

        return $next($request);
    }
}

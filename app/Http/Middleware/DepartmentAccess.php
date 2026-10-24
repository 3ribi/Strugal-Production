<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentAccess
{
    public function handle(
        Request $request,
        Closure $next,
        string $department
    ): Response {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        // Admin can access every department
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Department user must belong to a department
        if (!$user->department) {
            abort(403);
        }

        // Check requested department
        if ($user->department->slug !== $department) {
            abort(403);
        }

        return $next($request);
    }
}
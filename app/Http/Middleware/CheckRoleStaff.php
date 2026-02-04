<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !in_array($user->role, ['staff', 'admin'])) {
            abort(403, 'Unauthorized Access');
        }

        return $next($request);
    }
}

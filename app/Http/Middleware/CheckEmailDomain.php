<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckEmailDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // if (!$user || !str_ends_with($user->email, '@travelid.id')) {
        //     abort(403, 'Unauthorized Access');
        // }
        if (!$user || $user->email !== 'masterrally1808@gmail.com') {
            abort(403, 'Unauthorized Access');
        }

        return $next($request);
    }
}
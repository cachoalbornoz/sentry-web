<?php

namespace App\Http\Middleware;

use App\Support\AdminRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureElevatedAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!AdminRole::isElevated($request->session()->get('api_user'))) {
            return redirect()
                ->route('dashboard')
                ->with('status', 'No tenés permisos para acceder al panel de administración.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->session()->get('api_token', '');

        if ($token === '') {
            return redirect()->route('login.form');
        }

        return $next($request);
    }
}


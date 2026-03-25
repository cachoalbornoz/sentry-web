<?php

namespace App\Http\Controllers;

use App\Services\SentryApiClient;
use Illuminate\Http\Request;

class DashboardWebController extends Controller
{
    public function __invoke(Request $request, SentryApiClient $api)
    {
        $token = (string) $request->session()->get('api_token');

        try {
            $me = $api->me($token);
        } catch (\Throwable) {
            $request->session()->forget(['api_token', 'api_user', 'api_token_expires_at']);
            return redirect()->route('login.form');
        }

        $user = $me['user'] ?? null;

        return view('dashboard', [
            'user' => $user,
            'token_expires_at' => $request->session()->get('api_token_expires_at'),
        ]);
    }
}


<?php

namespace App\Http\Controllers;

use App\Services\SentryApiClient;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthWebController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request, SentryApiClient $api)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $result = $api->login($data['email'], $data['password']);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'email' => 'No se pudo iniciar sesión. Verificá credenciales o rol.',
            ]);
        }

        if (($result['token'] ?? '') === '') {
            throw ValidationException::withMessages([
                'email' => 'La API no devolvió token de sesión.',
            ]);
        }

        $request->session()->put('api_token', $result['token']);
        $request->session()->put('api_user', $result['user']);
        $request->session()->put('api_token_expires_at', $result['token_expires_at']);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request, SentryApiClient $api)
    {
        $token = (string) $request->session()->get('api_token');
        if ($token !== '') {
            $api->logout($token);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.form');
    }
}


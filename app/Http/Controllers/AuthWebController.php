<?php

namespace App\Http\Controllers;

use App\Services\SentryApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
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
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'email' => 'La API no responde en este momento. Intentá nuevamente.',
            ]);
        } catch (RequestException $e) {
            $status = $e->response?->status() ?? 0;
            $msg = 'No se pudo iniciar sesión. Verificá credenciales o rol.';
            if ($status === 401 || $status === 422) {
                $msg = 'Credenciales inválidas.';
            } elseif ($status >= 500) {
                $msg = 'La API devolvió un error interno. Intentá nuevamente.';
            }
            throw ValidationException::withMessages([
                'email' => $msg,
            ]);
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
            try {
                $api->logout($token);
            } catch (\Throwable) {
                // Nunca bloqueamos la salida local por fallas remotas de la API.
            }
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.form');
    }
}


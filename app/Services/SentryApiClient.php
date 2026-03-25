<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class SentryApiClient
{
    public function request(?string $token = null): PendingRequest
    {
        $baseUrl = config('services.sentry_api.base_url');

        return Http::baseUrl(rtrim((string) $baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->when($token, fn (PendingRequest $req) => $req->withToken($token));
    }

    /**
     * @return array{token:string,user:mixed,token_expires_at:?string}
     */
    public function login(string $email, string $password): array
    {
        $response = $this->request()
            ->post('/login', [
                'email' => $email,
                'password' => $password,
            ])
            ->throw();

        return [
            'token' => (string) ($response->json('token') ?? ''),
            'user' => $response->json('user'),
            'token_expires_at' => $response->json('token_expires_at'),
        ];
    }

    public function me(string $token): array
    {
        return $this->request($token)
            ->get('/me')
            ->throw()
            ->json();
    }

    public function logout(string $token): void
    {
        try {
            $this->request($token)->post('/logout')->throw();
        } catch (RequestException) {
            // No bloqueamos el logout local si el token ya expiró o fue revocado.
        }
    }

    public function eventos(string $token): array
    {
        return $this->request($token)
            ->get('/eventos')
            ->throw()
            ->json();
    }

    public function objetivos(string $token): array
    {
        $response = $this->request($token)->get('/objetivos')->throw();

        // ObjetivosController devuelve resource collection (Laravel default): { data: [...] }
        return $response->json();
    }

    public function cedulacionTipos(string $token): array
    {
        return $this->request($token)
            ->get('/cedulacion/getTipos')
            ->throw()
            ->json();
    }

    public function cedulacionObservaciones(string $token): array
    {
        return $this->request($token)
            ->get('/cedulacion/getObservaciones')
            ->throw()
            ->json();
    }

    /**
     * @param array{eventos:int[],cedulacion_tipo_id:int,observaciones?:?string} $payload
     */
    public function guardarCedulacion(string $token, array $payload): array
    {
        return $this->request($token)
            ->post('/cedulacion/guardar', $payload)
            ->throw()
            ->json();
    }
}


<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SentryApiClient
{
    public function request(?string $token = null): PendingRequest
    {
        $baseUrl = trim((string) config('services.sentry_api.base_url', ''));
        if ($baseUrl === '') {
            throw new RuntimeException('SENTRY_API_BASE_URL no está configurado en el entorno.');
        }

        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(3)
            ->timeout(15)
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
            // Logout no debe bloquear UX si la API está lenta o no responde.
            $this->request($token)
                ->connectTimeout(2)
                ->timeout(4)
                ->post('/logout')
                ->throw();
        } catch (RequestException|ConnectionException) {
            // No bloqueamos el logout local si el token ya expiró, fue revocado o hay timeout.
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

    public function objetivoContactos(string $token, int $objetivoId): array
    {
        return $this->request($token)
            ->get("/objetivos/contactos/{$objetivoId}")
            ->throw()
            ->json();
    }

    public function objetivoDetalle(string $token, int $objetivoId): array
    {
        return $this->request($token)
            ->get("/objetivos/{$objetivoId}")
            ->throw()
            ->json();
    }

    public function objetivoEventos(string $token, int $objetivoId, int $cantidad = 10): array
    {
        return $this->request($token)
            ->get("/objetivos/eventos/{$objetivoId}/{$cantidad}")
            ->throw()
            ->json();
    }

    public function objetivoZonas(string $token, int $objetivoId): array
    {
        return $this->request($token)
            ->get("/objetivos/zonas/{$objetivoId}")
            ->throw()
            ->json();
    }

    /**
     * @param array{eventos:int[],cedulacion_tipo_id:int,observaciones?:?string} $payload
     */
    public function guardarCedulacion(string $token, array $payload): array
    {
        return $this->request($token)
            // Cedulación puede procesar lotes grandes; damos más margen que el default.
            ->connectTimeout(5)
            ->timeout(60)
            ->post('/cedulacion/guardar', $payload)
            ->throw()
            ->json();
    }

    public function clientes(string $token): array
    {
        return $this->request($token)
            ->get('/clientes')
            ->throw()
            ->json();
    }

    public function jurisdicciones(string $token): array
    {
        return $this->request($token)
            ->get('/jurisdicciones')
            ->throw()
            ->json();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createObjetivo(string $token, array $payload): array
    {
        return $this->request($token)
            ->post('/objetivos', $payload)
            ->throw()
            ->json();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateObjetivo(string $token, int $objetivoId, array $payload): array
    {
        return $this->request($token)
            ->patch("/objetivos/{$objetivoId}", $payload)
            ->throw()
            ->json();
    }

    public function deleteObjetivo(string $token, int $objetivoId): void
    {
        $this->request($token)
            ->delete("/objetivos/{$objetivoId}")
            ->throw();
    }
}


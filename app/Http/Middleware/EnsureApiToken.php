<?php

namespace App\Http\Middleware;

use App\Services\SentryApiClient;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiToken
{
    public function __construct(private readonly SentryApiClient $api)
    {
    }

    private function isRefreshDue(Request $request): bool
    {
        // Para endpoints de datos usados por la UI en vivo, refrescamos siempre
        // el scope para reflejar cambios de permisos al instante.
        if ($request->is('x/*')) {
            return true;
        }

        $lastSyncAt = $request->session()->get('api_user_scope_synced_at');
        $interval = max(1, (int) config('services.sentry_api.scope_refresh_seconds', 3));

        if (!is_string($lastSyncAt) || $lastSyncAt === '') {
            return true;
        }

        $lastTs = strtotime($lastSyncAt);
        if ($lastTs === false) {
            return true;
        }

        return (time() - $lastTs) >= $interval;
    }

    private function unauthenticatedResponse(Request $request): Response
    {
        if ($request->is('x/*') || $request->expectsJson()) {
            return response()->json([
                'message' => 'Sesion vencida. Inicia sesion nuevamente.',
                'session_expired' => true,
            ], 401);
        }

        return redirect()->route('login.form');
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->session()->get('api_token', '');

        if ($token === '') {
            return $this->unauthenticatedResponse($request);
        }

        // Refresca permisos en caliente desde /me con una cadencia baja para
        // evitar sobrecargar la API en cada request.
        if ($this->isRefreshDue($request)) {
            try {
                $mePayload = $this->api->me($token);
                $user = $mePayload['user'] ?? null;
                if (is_array($user)) {
                    $request->session()->put('api_user', $user);
                }
                $request->session()->put('api_user_scope_synced_at', now()->toIso8601String());
            } catch (RequestException $e) {
                if (($e->response?->status() ?? 0) === 401) {
                    $request->session()->forget([
                        'api_token',
                        'api_user',
                        'api_token_expires_at',
                        'api_user_scope_synced_at',
                    ]);
                    return $this->unauthenticatedResponse($request);
                }
            } catch (ConnectionException) {
                // Si hay timeout momentáneo, seguimos con la sesión local actual.
            } catch (\Throwable) {
                // Falla no crítica: no bloqueamos navegación por refresh fallido.
            }
        }

        return $next($request);
    }
}


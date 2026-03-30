<?php

namespace App\Http\Controllers;

use App\Services\SentryApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiProxyController extends Controller
{
    /**
     * @return array{0: array<int>, 1: bool}
     */
    private function objetivosScope(Request $request): array
    {
        $user = $request->session()->get('api_user');
        if (!is_array($user)) {
            return [[], false];
        }

        $candidateKeys = [
            'objetivos_alcanzables_id',
            'objetivos_accessibles_id',
            'objetivosAlcanzablesId',
            'objetivosAccesiblesId',
        ];

        foreach ($candidateKeys as $key) {
            if (!array_key_exists($key, $user)) {
                continue;
            }

            $raw = $user[$key];
            if (!is_array($raw)) {
                return [[], true];
            }

            $ids = array_values(array_unique(array_filter(array_map(
                static fn ($id) => is_numeric($id) ? (int) $id : 0,
                $raw
            ), static fn (int $id) => $id > 0)));

            return [$ids, true];
        }

        return [[], false];
    }

    private function objectiveIdFromEvent(mixed $event): int
    {
        if (!is_array($event)) {
            return 0;
        }

        return (int) ($event['idObjetivo'] ?? $event['objetivoId'] ?? $event['objetivo_id'] ?? 0);
    }

    private function filterEventosByScope(array $eventos, array $allowedObjetivoIds, bool $hasScope): array
    {
        if (!$hasScope) {
            return $eventos;
        }
        if (empty($allowedObjetivoIds)) {
            return [];
        }

        $allowedMap = array_flip($allowedObjetivoIds);
        return array_values(array_filter($eventos, fn ($event) => isset($allowedMap[$this->objectiveIdFromEvent($event)])));
    }

    private function filterObjetivosPayloadByScope(array $payload, array $allowedObjetivoIds, bool $hasScope): array
    {
        if (!$hasScope) {
            return $payload;
        }

        $allowedMap = array_flip($allowedObjetivoIds);
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $payload['data'] = array_values(array_filter($data, static fn ($item) => isset($allowedMap[(int) ($item['id'] ?? 0)])));
        return $payload;
    }

    private function canAccessObjetivo(int $objetivoId, array $allowedObjetivoIds, bool $hasScope): bool
    {
        return !$hasScope || in_array($objetivoId, $allowedObjetivoIds, true);
    }

    private function unauthorizedResponse(Request $request): JsonResponse
    {
        $request->session()->forget([
            'api_token',
            'api_user',
            'api_token_expires_at',
            'last_eventos_payload',
            'last_objetivos_payload',
        ]);

        return response()->json([
            'message' => 'Sesión vencida. Iniciá sesión nuevamente.',
            'session_expired' => true,
        ], 401);
    }

    public function eventos(Request $request, SentryApiClient $api)
    {
        $token = (string) $request->session()->get('api_token');
        [$allowedObjetivoIds, $hasScope] = $this->objetivosScope($request);
        try {
            $payload = $api->eventos($token);
            $events = is_array($payload) ? $payload : [];
            $events = $this->filterEventosByScope($events, $allowedObjetivoIds, $hasScope);
            $request->session()->put('last_eventos_payload', $events);
            $request->session()->put('last_eventos_ok_at', now()->toIso8601String());

            return response()->json($events);
        } catch (ConnectionException) {
            $cached = $request->session()->get('last_eventos_payload');
            if (is_array($cached)) {
                return response()->json($cached, 200, ['X-Sentry-Stale' => '1']);
            }

            return response()->json([], 200, ['X-Sentry-Stale' => '1']);
        } catch (RequestException $e) {
            if (($e->response?->status() ?? 0) === 401) {
                return $this->unauthorizedResponse($request);
            }
            throw $e;
        }
    }

    public function objetivos(Request $request, SentryApiClient $api)
    {
        $token = (string) $request->session()->get('api_token');
        [$allowedObjetivoIds, $hasScope] = $this->objetivosScope($request);
        try {
            $payload = $api->objetivos($token);
            if (is_array($payload)) {
                $payload = $this->filterObjetivosPayloadByScope($payload, $allowedObjetivoIds, $hasScope);
            }
            $request->session()->put('last_objetivos_payload', $payload);
            $request->session()->put('last_objetivos_ok_at', now()->toIso8601String());

            return response()->json($payload);
        } catch (ConnectionException) {
            $cached = $request->session()->get('last_objetivos_payload');
            if (is_array($cached)) {
                return response()->json([
                    ...$cached,
                    'stale' => true,
                ]);
            }

            return response()->json([
                'data' => [],
                'stale' => true,
                'message' => 'Sin respuesta temporal de API de objetivos.',
            ]);
        } catch (RequestException $e) {
            if (($e->response?->status() ?? 0) === 401) {
                return $this->unauthorizedResponse($request);
            }
            throw $e;
        }
    }

    public function cedulacionTipos(Request $request, SentryApiClient $api)
    {
        $token = (string) $request->session()->get('api_token');
        try {
            $payload = $api->cedulacionTipos($token);
            $request->session()->put('last_cedulacion_tipos_payload', $payload);
            $request->session()->put('last_cedulacion_tipos_ok_at', now()->toIso8601String());

            return response()->json($payload);
        } catch (ConnectionException) {
            $cached = $request->session()->get('last_cedulacion_tipos_payload');
            if (is_array($cached)) {
                return response()->json([
                    ...$cached,
                    'stale' => true,
                ], 200, ['X-Sentry-Stale' => '1']);
            }

            return response()->json([
                'data' => [],
                'stale' => true,
                'message' => 'Sin respuesta temporal de API para tipos de señal.',
            ], 200, ['X-Sentry-Stale' => '1']);
        } catch (RequestException $e) {
            if (($e->response?->status() ?? 0) === 401) {
                return $this->unauthorizedResponse($request);
            }
            throw $e;
        }
    }

    public function cedulacionObservaciones(Request $request, SentryApiClient $api)
    {
        $token = (string) $request->session()->get('api_token');
        try {
            $payload = $api->cedulacionObservaciones($token);
            $request->session()->put('last_cedulacion_observaciones_payload', $payload);
            $request->session()->put('last_cedulacion_observaciones_ok_at', now()->toIso8601String());

            return response()->json($payload);
        } catch (ConnectionException) {
            $cached = $request->session()->get('last_cedulacion_observaciones_payload');
            if (is_array($cached)) {
                return response()->json([
                    ...$cached,
                    'stale' => true,
                ], 200, ['X-Sentry-Stale' => '1']);
            }

            return response()->json([
                'data' => [],
                'stale' => true,
                'message' => 'Sin respuesta temporal de API para observaciones predefinidas.',
            ], 200, ['X-Sentry-Stale' => '1']);
        } catch (RequestException $e) {
            if (($e->response?->status() ?? 0) === 401) {
                return $this->unauthorizedResponse($request);
            }
            throw $e;
        }
    }

    public function objetivoContactos(Request $request, SentryApiClient $api, int $objetivo): JsonResponse
    {
        $token = (string) $request->session()->get('api_token');
        [$allowedObjetivoIds, $hasScope] = $this->objetivosScope($request);
        if (!$this->canAccessObjetivo($objetivo, $allowedObjetivoIds, $hasScope)) {
            return response()->json(['message' => 'Objetivo no alcanzable.'], 404);
        }
        try {
            return response()->json($api->objetivoContactos($token, $objetivo));
        } catch (ConnectionException) {
            return response()->json(['data' => [], 'stale' => true]);
        } catch (RequestException $e) {
            if (($e->response?->status() ?? 0) === 401) {
                return $this->unauthorizedResponse($request);
            }
            throw $e;
        }
    }

    public function objetivoDetalle(Request $request, SentryApiClient $api, int $objetivo): JsonResponse
    {
        $token = (string) $request->session()->get('api_token');
        [$allowedObjetivoIds, $hasScope] = $this->objetivosScope($request);
        if (!$this->canAccessObjetivo($objetivo, $allowedObjetivoIds, $hasScope)) {
            return response()->json(['message' => 'Objetivo no alcanzable.'], 404);
        }
        try {
            return response()->json($api->objetivoDetalle($token, $objetivo));
        } catch (ConnectionException) {
            return response()->json(['stale' => true], 200);
        } catch (RequestException $e) {
            if (($e->response?->status() ?? 0) === 401) {
                return $this->unauthorizedResponse($request);
            }
            throw $e;
        }
    }

    public function objetivoEventos(Request $request, SentryApiClient $api, int $objetivo): JsonResponse
    {
        $token = (string) $request->session()->get('api_token');
        [$allowedObjetivoIds, $hasScope] = $this->objetivosScope($request);
        if (!$this->canAccessObjetivo($objetivo, $allowedObjetivoIds, $hasScope)) {
            return response()->json(['eventos' => []], 200);
        }
        try {
            return response()->json($api->objetivoEventos($token, $objetivo, 10));
        } catch (ConnectionException) {
            return response()->json(['eventos' => [], 'stale' => true]);
        } catch (RequestException $e) {
            if (($e->response?->status() ?? 0) === 401) {
                return $this->unauthorizedResponse($request);
            }
            throw $e;
        }
    }

    public function objetivoZonas(Request $request, SentryApiClient $api, int $objetivo): JsonResponse
    {
        $token = (string) $request->session()->get('api_token');
        [$allowedObjetivoIds, $hasScope] = $this->objetivosScope($request);
        if (!$this->canAccessObjetivo($objetivo, $allowedObjetivoIds, $hasScope)) {
            return response()->json(['data' => []], 200);
        }
        try {
            return response()->json($api->objetivoZonas($token, $objetivo));
        } catch (ConnectionException) {
            return response()->json(['data' => [], 'stale' => true]);
        } catch (RequestException $e) {
            if (($e->response?->status() ?? 0) === 401) {
                return $this->unauthorizedResponse($request);
            }
            throw $e;
        }
    }

    public function guardarCedulacion(Request $request, SentryApiClient $api)
    {
        $token = (string) $request->session()->get('api_token');

        $payload = $request->validate([
            'eventos' => ['required', 'array', 'min:1'],
            'eventos.*' => ['required', 'integer'],
            'cedulacion_tipo_id' => ['required', 'integer'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $batchSize = (int) config('services.sentry_api.cedulacion_batch_size', 1);
        $eventos = array_values($payload['eventos'] ?? []);

        try {
            if (count($eventos) <= $batchSize) {
                $result = $api->guardarCedulacion($token, $payload);
                return response()->json($result);
            }

            $chunks = array_chunk($eventos, $batchSize);
            $messages = [];
            $created = [];
            $existentes = [];

            foreach ($chunks as $chunk) {
                $chunkPayload = $payload;
                $chunkPayload['eventos'] = $chunk;
                $partial = $api->guardarCedulacion($token, $chunkPayload);

                if (isset($partial['message']) && is_string($partial['message'])) {
                    $messages[] = $partial['message'];
                }
                if (isset($partial['cedulaciones_creadas']) && is_array($partial['cedulaciones_creadas'])) {
                    $created = array_merge($created, $partial['cedulaciones_creadas']);
                }
                if (isset($partial['cedulaciones_existentes']) && is_array($partial['cedulaciones_existentes'])) {
                    $existentes = array_merge($existentes, $partial['cedulaciones_existentes']);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Cedulación procesada en '.count($chunks).' lote(s).',
                'message_details' => $messages,
                'cedulaciones_creadas' => $created,
                'cedulaciones_existentes' => $existentes,
            ]);
        } catch (ConnectionException) {
            return response()->json([
                'status' => 'error',
                'message' => 'La API demoró en responder al guardar la cedulación. Intentá nuevamente.',
                'timeout' => true,
            ], 504);
        } catch (RequestException $e) {
            $status = $e->response?->status() ?? 500;
            if ($status === 401) {
                return $this->unauthorizedResponse($request);
            }
            $json = $e->response?->json() ?? ['status' => 'error', 'message' => $e->getMessage()];
            return response()->json($json, $status);
        }
    }
}


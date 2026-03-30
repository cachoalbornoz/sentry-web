<?php

namespace App\Http\Controllers;

use App\Services\SentryApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiProxyController extends Controller
{
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
        try {
            $payload = $api->eventos($token);
            $events = is_array($payload) ? $payload : [];
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
        try {
            $payload = $api->objetivos($token);
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


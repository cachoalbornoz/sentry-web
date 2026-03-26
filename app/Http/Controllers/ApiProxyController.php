<?php

namespace App\Http\Controllers;

use App\Services\SentryApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiProxyController extends Controller
{
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
        }
    }

    public function cedulacionTipos(Request $request, SentryApiClient $api)
    {
        $token = (string) $request->session()->get('api_token');
        return response()->json($api->cedulacionTipos($token));
    }

    public function cedulacionObservaciones(Request $request, SentryApiClient $api)
    {
        $token = (string) $request->session()->get('api_token');
        return response()->json($api->cedulacionObservaciones($token));
    }

    public function objetivoContactos(Request $request, SentryApiClient $api, int $objetivo): JsonResponse
    {
        $token = (string) $request->session()->get('api_token');
        try {
            return response()->json($api->objetivoContactos($token, $objetivo));
        } catch (ConnectionException) {
            return response()->json(['data' => [], 'stale' => true]);
        }
    }

    public function objetivoDetalle(Request $request, SentryApiClient $api, int $objetivo): JsonResponse
    {
        $token = (string) $request->session()->get('api_token');
        try {
            return response()->json($api->objetivoDetalle($token, $objetivo));
        } catch (ConnectionException) {
            return response()->json(['stale' => true], 200);
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

        try {
            $result = $api->guardarCedulacion($token, $payload);
            return response()->json($result);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $status = $e->response?->status() ?? 500;
            $json = $e->response?->json() ?? ['status' => 'error', 'message' => $e->getMessage()];
            return response()->json($json, $status);
        }
    }
}


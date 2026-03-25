<?php

namespace App\Http\Controllers;

use App\Services\SentryApiClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApiProxyController extends Controller
{
    public function eventos(Request $request, SentryApiClient $api)
    {
        $token = (string) $request->session()->get('api_token');
        return response()->json($api->eventos($token));
    }

    public function objetivos(Request $request, SentryApiClient $api)
    {
        $token = (string) $request->session()->get('api_token');
        return response()->json($api->objetivos($token));
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


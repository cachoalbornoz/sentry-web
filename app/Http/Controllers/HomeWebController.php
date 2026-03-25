<?php

namespace App\Http\Controllers;

use App\Services\SentryApiClient;
use Illuminate\Http\Request;

class HomeWebController extends Controller
{
    public function __invoke(Request $request, SentryApiClient $api)
    {
        $token = (string) $request->session()->get('api_token');

        try {
            $eventos = $api->eventos($token);
            $objetivos = $api->objetivos($token);
        } catch (\Throwable) {
            $request->session()->forget(['api_token', 'api_user', 'api_token_expires_at']);
            return redirect()->route('login.form');
        }

        $objetivosData = $objetivos['data'] ?? [];

        $cartoProxy = route('x.tiles.carto', ['z' => 0, 'x' => 0, 'y' => '0.png']);
        $stadiaProxy = route('x.tiles.stadia', ['z' => 0, 'x' => 0, 'y' => '0.png']);

        $useStadiaBasemap = (bool) config('services.stadiamaps.use_basemap')
            && (string) config('services.stadiamaps.key', '') !== '';

        return view('inicio', [
            'eventos' => $eventos ?? [],
            'objetivos' => $objetivosData,
            'stadiaKey' => (string) config('services.stadiamaps.key', ''),
            'useStadiaBasemap' => $useStadiaBasemap,
            'cartoTileTemplate' => str_replace('/0/0/0.png', '/{z}/{x}/{y}.png', $cartoProxy),
            'stadiaTileTemplate' => str_replace('/0/0/0.png', '/{z}/{x}/{y}.png', $stadiaProxy),
        ]);
    }
}


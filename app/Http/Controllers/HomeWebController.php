<?php

namespace App\Http\Controllers;

use App\Services\SentryApiClient;
use App\Support\AdminRole;
use Illuminate\Http\Request;

class HomeWebController extends Controller
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

    public function __invoke(Request $request, SentryApiClient $api)
    {
        if (AdminRole::isElevated($request->session()->get('api_user'))) {
            return redirect()->route('admin.home');
        }

        $token = (string) $request->session()->get('api_token');

        try {
            $eventos = $api->eventos($token);
            $objetivos = $api->objetivos($token);
        } catch (\Throwable) {
            $request->session()->forget(['api_token', 'api_user', 'api_token_expires_at']);
            return redirect()->route('login.form');
        }

        [$allowedObjetivoIds, $hasObjetivoScope] = $this->objetivosScope($request);

        $objetivosData = is_array($objetivos['data'] ?? null) ? $objetivos['data'] : [];
        if ($hasObjetivoScope) {
            $allowedMap = array_flip($allowedObjetivoIds);
            $objetivosData = array_values(array_filter(
                $objetivosData,
                static fn ($item) => isset($allowedMap[(int) ($item['id'] ?? 0)])
            ));
        }

        $eventosData = is_array($eventos) ? $eventos : [];
        if ($hasObjetivoScope) {
            $allowedMap = array_flip($allowedObjetivoIds);
            $eventosData = array_values(array_filter(
                $eventosData,
                static fn ($item) => isset($allowedMap[(int) ($item['idObjetivo'] ?? $item['objetivoId'] ?? $item['objetivo_id'] ?? 0)])
            ));
        }

        $cartoProxy = route('x.tiles.carto', ['z' => 0, 'x' => 0, 'y' => '0.png']);
        $stadiaProxy = route('x.tiles.stadia', ['z' => 0, 'x' => 0, 'y' => '0.png']);

        $useStadiaBasemap = (bool) config('services.stadiamaps.use_basemap')
            && (string) config('services.stadiamaps.key', '') !== '';

        return view('inicio', [
            'eventos' => $eventosData,
            'objetivos' => $objetivosData,
            'allowedObjetivoIds' => $allowedObjetivoIds,
            'hasObjetivoScope' => $hasObjetivoScope,
            'stadiaKey' => (string) config('services.stadiamaps.key', ''),
            'useStadiaBasemap' => $useStadiaBasemap,
            'cartoTileTemplate' => str_replace('/0/0/0.png', '/{z}/{x}/{y}.png', $cartoProxy),
            'stadiaTileTemplate' => str_replace('/0/0/0.png', '/{z}/{x}/{y}.png', $stadiaProxy),
        ]);
    }
}


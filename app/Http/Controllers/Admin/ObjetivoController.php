<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SentryApiClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ObjetivoController extends Controller
{
    private function apiErrorsRedirect(Request $request, RequestException $e): ?RedirectResponse
    {
        if (($e->response?->status() ?? 0) !== 422) {
            return null;
        }

        $json = $e->response->json();
        $errors = $json['errors'] ?? null;
        if (is_array($errors)) {
            return back()->withInput()->withErrors($errors);
        }

        $message = is_array($json) ? (string) ($json['message'] ?? 'Error de validación.') : 'Error de validación.';

        return back()->withInput()->withErrors(['api' => $message]);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizarDetalle(mixed $detalle): array
    {
        if (!is_array($detalle)) {
            return [];
        }
        if (isset($detalle['data']) && is_array($detalle['data'])) {
            return $detalle['data'];
        }

        return $detalle;
    }

    private function cartoTileTemplate(): string
    {
        $cartoProxy = route('x.tiles.carto', ['z' => 0, 'x' => 0, 'y' => '0.png']);

        return str_replace('/0/0/0.png', '/{z}/{x}/{y}.png', $cartoProxy);
    }

    public function index(Request $request, SentryApiClient $api): View
    {
        $token = (string) $request->session()->get('api_token');
        $rows = [];
        $loadError = null;
        try {
            $payload = $api->objetivos($token);
            $rows = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        } catch (RequestException) {
            $loadError = 'No se pudo cargar el listado de objetivos desde la API.';
        } catch (\Throwable) {
            $loadError = 'Error al conectar con la API.';
        }

        return view('admin.objetivos.index', [
            'user' => $request->session()->get('api_user'),
            'objetivos' => $rows,
            'loadError' => $loadError,
        ]);
    }

    public function create(Request $request, SentryApiClient $api): View|RedirectResponse
    {
        $token = (string) $request->session()->get('api_token');
        try {
            $clientesPayload = $api->clientes($token);
            $jurisdiccionesPayload = $api->jurisdicciones($token);
        } catch (\Throwable) {
            return redirect()->route('admin.objetivos.index')
                ->withErrors(['api' => 'No se pudieron cargar clientes o jurisdicciones para el formulario.']);
        }

        $clientes = is_array($clientesPayload['data'] ?? null) ? $clientesPayload['data'] : [];
        $jurisdicciones = is_array($jurisdiccionesPayload['data'] ?? null) ? $jurisdiccionesPayload['data'] : [];

        return view('admin.objetivos.create', [
            'user' => $request->session()->get('api_user'),
            'clientes' => $clientes,
            'jurisdicciones' => $jurisdicciones,
            'cartoTileTemplate' => $this->cartoTileTemplate(),
        ]);
    }

    public function store(Request $request, SentryApiClient $api): RedirectResponse
    {
        $validated = $request->validate([
            'cliente_id' => ['required', 'integer'],
            'nombre' => ['required', 'string', 'max:190'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
            'jurisdiccion_id' => ['nullable', 'integer'],
            'localidad_id' => ['nullable', 'integer'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'central_nro' => ['nullable', 'integer', 'min:1', 'max:32767'],
        ]);

        $payload = [
            'cliente_id' => (int) $validated['cliente_id'],
            'nombre' => $validated['nombre'],
            'ubicacion' => [
                'latitud' => (float) $validated['latitud'],
                'longitud' => (float) $validated['longitud'],
            ],
        ];

        if (($validated['descripcion'] ?? '') !== '') {
            $payload['descripcion'] = $validated['descripcion'];
        }
        if (($validated['jurisdiccion_id'] ?? null) !== null) {
            $payload['jurisdiccion_id'] = (int) $validated['jurisdiccion_id'];
        }
        if (($validated['localidad_id'] ?? null) !== null) {
            $payload['localidad_id'] = (int) $validated['localidad_id'];
        }
        if (($validated['direccion'] ?? '') !== '') {
            $payload['direccion'] = $validated['direccion'];
        }
        if (($validated['central_nro'] ?? null) !== null) {
            $payload['central_nro'] = (int) $validated['central_nro'];
        }

        $token = (string) $request->session()->get('api_token');

        try {
            $api->createObjetivo($token, $payload);
        } catch (RequestException $e) {
            $redirect = $this->apiErrorsRedirect($request, $e);
            if ($redirect) {
                return $redirect;
            }
            return redirect()->route('admin.objetivos.create')
                ->withInput()
                ->withErrors(['api' => 'Error al crear el objetivo.']);
        }

        return redirect()->route('admin.objetivos.index')
            ->with('status', 'Objetivo creado correctamente.');
    }

    public function show(Request $request, SentryApiClient $api, int $objetivo): View|RedirectResponse
    {
        $token = (string) $request->session()->get('api_token');
        try {
            $detalle = $api->objetivoDetalle($token, $objetivo);
        } catch (RequestException $e) {
            if (($e->response?->status() ?? 0) === 404) {
                abort(404);
            }

            return redirect()->route('admin.objetivos.index')
                ->withErrors(['api' => 'No se pudo obtener el objetivo.']);
        } catch (\Throwable) {
            return redirect()->route('admin.objetivos.index')
                ->withErrors(['api' => 'Error al conectar con la API.']);
        }

        $row = $this->normalizarDetalle($detalle);
        if (!isset($row['id'])) {
            return redirect()->route('admin.objetivos.index')
                ->withErrors(['api' => 'Respuesta inesperada al consultar el objetivo.']);
        }

        return view('admin.objetivos.show', [
            'user' => $request->session()->get('api_user'),
            'objetivo' => $row,
            'cartoTileTemplate' => $this->cartoTileTemplate(),
        ]);
    }

    public function edit(Request $request, SentryApiClient $api, int $objetivo): View|RedirectResponse
    {
        $token = (string) $request->session()->get('api_token');

        try {
            $clientesPayload = $api->clientes($token);
            $jurisdiccionesPayload = $api->jurisdicciones($token);
            $detalle = $api->objetivoDetalle($token, $objetivo);
        } catch (RequestException $e) {
            if (($e->response?->status() ?? 0) === 404) {
                abort(404);
            }

            return redirect()->route('admin.objetivos.index')
                ->withErrors(['api' => 'No se pudo cargar el formulario de edición.']);
        } catch (\Throwable) {
            return redirect()->route('admin.objetivos.index')
                ->withErrors(['api' => 'Error al conectar con la API.']);
        }

        $clientes = is_array($clientesPayload['data'] ?? null) ? $clientesPayload['data'] : [];
        $jurisdicciones = is_array($jurisdiccionesPayload['data'] ?? null) ? $jurisdiccionesPayload['data'] : [];
        $row = $this->normalizarDetalle($detalle);
        if (!isset($row['id'])) {
            return redirect()->route('admin.objetivos.index')
                ->withErrors(['api' => 'Respuesta inesperada al consultar el objetivo.']);
        }

        return view('admin.objetivos.edit', [
            'user' => $request->session()->get('api_user'),
            'clientes' => $clientes,
            'jurisdicciones' => $jurisdicciones,
            'objetivo' => $row,
            'cartoTileTemplate' => $this->cartoTileTemplate(),
        ]);
    }

    public function update(Request $request, SentryApiClient $api, int $objetivo): RedirectResponse
    {
        $validated = $request->validate([
            'cliente_id' => ['required', 'integer'],
            'nombre' => ['required', 'string', 'max:190'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'jurisdiccion_id' => ['nullable', 'integer'],
            'localidad_id' => ['nullable', 'integer'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'cliente_id' => (int) $validated['cliente_id'],
            'nombre' => $validated['nombre'],
        ];

        if (array_key_exists('descripcion', $validated)) {
            $payload['descripcion'] = $validated['descripcion'] ?? null;
        }
        if (array_key_exists('jurisdiccion_id', $validated) && $validated['jurisdiccion_id'] !== null) {
            $payload['jurisdiccion_id'] = (int) $validated['jurisdiccion_id'];
        } elseif (array_key_exists('jurisdiccion_id', $validated)) {
            $payload['jurisdiccion_id'] = null;
        }
        if (array_key_exists('localidad_id', $validated) && $validated['localidad_id'] !== null) {
            $payload['localidad_id'] = (int) $validated['localidad_id'];
        } elseif (array_key_exists('localidad_id', $validated)) {
            $payload['localidad_id'] = null;
        }
        if (array_key_exists('direccion', $validated)) {
            $payload['direccion'] = $validated['direccion'] ?? null;
        }

        if (isset($validated['latitud'], $validated['longitud']) && $validated['latitud'] !== null && $validated['longitud'] !== null) {
            $payload['ubicacion'] = [
                'latitud' => (float) $validated['latitud'],
                'longitud' => (float) $validated['longitud'],
            ];
        }

        $token = (string) $request->session()->get('api_token');

        try {
            $api->updateObjetivo($token, $objetivo, $payload);
        } catch (RequestException $e) {
            $redirect = $this->apiErrorsRedirect($request, $e);
            if ($redirect) {
                return $redirect;
            }

            return redirect()->route('admin.objetivos.edit', $objetivo)
                ->withInput()
                ->withErrors(['api' => 'No se pudo guardar.']);
        }

        return redirect()->route('admin.objetivos.show', $objetivo)
            ->with('status', 'Cambios guardados.');
    }

    public function destroy(Request $request, SentryApiClient $api, int $objetivo): RedirectResponse
    {
        $token = (string) $request->session()->get('api_token');
        try {
            $api->deleteObjetivo($token, $objetivo);
        } catch (RequestException $e) {
            if (($e->response?->status() ?? 0) === 404) {
                return redirect()->route('admin.objetivos.index')
                    ->withErrors(['api' => 'El objetivo no existe o ya fue eliminado.']);
            }
            if (($e->response?->status() ?? 0) === 403) {
                return redirect()->route('admin.objetivos.index')
                    ->withErrors(['api' => 'No tenés permiso para eliminar este objetivo.']);
            }

            return redirect()->route('admin.objetivos.index')
                ->withErrors(['api' => 'No se pudo eliminar el objetivo.']);
        } catch (\Throwable) {
            return redirect()->route('admin.objetivos.index')
                ->withErrors(['api' => 'Error al conectar con la API.']);
        }

        return redirect()->route('admin.objetivos.index')
            ->with('status', 'Objetivo eliminado (baja lógica).');
    }
}

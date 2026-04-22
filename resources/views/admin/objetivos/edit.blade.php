@extends('layouts.admin', ['user' => $user ?? session('api_user')])

@section('title', 'Editar objetivo')

@php
    $activeAdminNav = 'crud';
    $o = is_array($objetivo) ? $objetivo : [];
    $u = $o['ubicacion'] ?? null;
    $lat = old('latitud', is_array($u) ? ($u['latitud'] ?? '-32.06') : '-32.06');
    $lon = old('longitud', is_array($u) ? ($u['longitud'] ?? '-59.23') : '-59.23');
@endphp

@section('content')
    <div class="mx-auto w-full max-w-[1600px]">
        <div>
            <a href="{{ route('admin.objetivos.show', (int) ($o['id'] ?? 0)) }}" class="text-sm text-sky-400 hover:text-sky-300">← Volver a detalle</a>
            <h1 class="mt-2 text-2xl font-semibold text-white">Modificar objetivo</h1>
        </div>

        <form method="post" action="{{ route('admin.objetivos.update', (int) ($o['id'] ?? 0)) }}" class="mt-8">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-start">
                <div class="max-w-xl space-y-4">
                    <div>
                        <label class="block text-sm text-slate-300" for="cliente_id">Cliente <span class="text-red-400">*</span></label>
                        <select name="cliente_id" id="cliente_id" required
                                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100">
                            @foreach ($clientes as $c)
                                <option value="{{ (int) ($c['id'] ?? 0) }}"
                                    @selected((string) old('cliente_id', (string) ($o['cliente_id'] ?? '')) === (string) ($c['id'] ?? ''))>
                                    {{ $c['nombre'] ?? '—' }} ({{ $c['id'] ?? '?' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300" for="nombre">Nombre <span class="text-red-400">*</span></label>
                        <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $o['nombre'] ?? '') }}" required maxlength="190"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100" />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300" for="descripcion">Descripción</label>
                        <input type="text" name="descripcion" id="descripcion" value="{{ old('descripcion', $o['descripcion'] ?? '') }}"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100" />
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm text-slate-300" for="latitud">Latitud</label>
                            <input type="text" name="latitud" id="latitud" value="{{ $lat }}"
                                   class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100" />
                        </div>
                        <div>
                            <label class="block text-sm text-slate-300" for="longitud">Longitud</label>
                            <input type="text" name="longitud" id="longitud" value="{{ $lon }}"
                                   class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300" for="direccion">Dirección</label>
                        <input type="text" name="direccion" id="direccion" value="{{ old('direccion', $o['direccion'] ?? '') }}"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100" />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300" for="jurisdiccion_id">Jurisdicción <span class="text-slate-500">(opcional)</span></label>
                        <select name="jurisdiccion_id" id="jurisdiccion_id"
                                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100">
                            <option value="">— Sin jurisdicción —</option>
                            @foreach ($jurisdicciones as $j)
                                <option value="{{ (int) ($j['id'] ?? 0) }}"
                                    @selected((string) old('jurisdiccion_id', (string) ($o['jurisdiccion_id'] ?? '')) === (string) ($j['id'] ?? ''))>
                                    {{ $j['nombre'] ?? '—' }} ({{ $j['id'] ?? '?' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300" for="localidad_id">Localidad ID <span class="text-slate-500">(opcional)</span></label>
                        <input type="number" name="localidad_id" id="localidad_id" value="{{ old('localidad_id', $o['localidad_id'] ?? '') }}"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100" />
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-sky-500">Guardar</button>
                    </div>
                </div>

                <div class="lg:sticky lg:top-6">
                    <div class="text-sm font-medium text-slate-200">Ubicación en el mapa</div>
                    <p class="mt-1 text-xs text-slate-500">La posición se sincroniza con latitud y longitud del formulario.</p>
                    <p id="admin-map-coords-hint" class="mt-2 hidden text-xs text-amber-200/90" role="status"></p>
                    <p class="mt-1 text-xs text-slate-500">Si el marcador cae al mar, revisá que <strong class="text-slate-300">no estén invertidos</strong> los campos: lat ≈ -34, long ≈ -58 (Buenos Aires).</p>
                    <div id="admin-objetivo-map" role="img" aria-label="Mapa de ubicación del objetivo"
                         class="relative z-0 mt-3 h-[min(70vh,520px)] min-h-[280px] w-full overflow-hidden rounded-xl border border-slate-700"
                         data-tile-template="{{ e($cartoTileTemplate) }}"
                         data-map-mode="form"></div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        @vite(['resources/js/admin-objetivos.js'])
    @endpush
@endsection

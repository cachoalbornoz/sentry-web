@extends('layouts.admin', ['user' => $user ?? session('api_user')])

@section('title', 'Nuevo objetivo')

@php
    $activeAdminNav = 'crud';
@endphp

@section('content')
    <div class="mx-auto w-full max-w-[1600px]">
        <div>
            <a href="{{ route('admin.objetivos.index') }}" class="text-sm text-sky-400 hover:text-sky-300">← Volver al listado</a>
            <h1 class="mt-2 text-2xl font-semibold text-white">Nuevo objetivo</h1>
        </div>

        <form method="post" action="{{ route('admin.objetivos.store') }}" class="mt-8">
            @csrf
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-start">
                <div class="max-w-xl space-y-4">
                    <div>
                        <label class="block text-sm text-slate-300" for="cliente_id">Cliente <span class="text-red-400">*</span></label>
                        <select name="cliente_id" id="cliente_id" required
                                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100">
                            <option value="">— Elegir —</option>
                            @foreach ($clientes as $c)
                                <option value="{{ (int) ($c['id'] ?? 0) }}" @selected((string) old('cliente_id') === (string) ($c['id'] ?? ''))>
                                    {{ $c['nombre'] ?? '—' }} ({{ $c['id'] ?? '?' }})
                                </option>
                            @endforeach
                        </select>
                        @error('cliente_id')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300" for="nombre">Nombre <span class="text-red-400">*</span></label>
                        <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" required maxlength="190"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100" />
                        @error('nombre')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300" for="descripcion">Descripción</label>
                        <input type="text" name="descripcion" id="descripcion" value="{{ old('descripcion') }}"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100" />
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm text-slate-300" for="latitud">Latitud <span class="text-red-400">*</span></label>
                            <input type="text" name="latitud" id="latitud" value="{{ old('latitud', '-32.06') }}" required
                                   class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100" placeholder="-34.6" />
                        </div>
                        <div>
                            <label class="block text-sm text-slate-300" for="longitud">Longitud <span class="text-red-400">*</span></label>
                            <input type="text" name="longitud" id="longitud" value="{{ old('longitud', '-59.23') }}" required
                                   class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100" placeholder="-58.3" />
                        </div>
                    </div>
                    @error('latitud')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    @error('longitud')<p class="text-sm text-red-300">{{ $message }}</p>@enderror
                    <div>
                        <label class="block text-sm text-slate-300" for="direccion">Dirección</label>
                        <input type="text" name="direccion" id="direccion" value="{{ old('direccion') }}"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100" />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300" for="jurisdiccion_id">Jurisdicción <span class="text-slate-500">(opcional)</span></label>
                        <select name="jurisdiccion_id" id="jurisdiccion_id"
                                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100">
                            <option value="">— Sin jurisdicción —</option>
                            @foreach ($jurisdicciones as $j)
                                <option value="{{ (int) ($j['id'] ?? 0) }}" @selected((string) old('jurisdiccion_id', '') === (string) ($j['id'] ?? ''))>
                                    {{ $j['nombre'] ?? '—' }} ({{ $j['id'] ?? '?' }})
                                </option>
                            @endforeach
                        </select>
                        @error('jurisdiccion_id')<p class="mt-1 text-sm text-red-300">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300" for="localidad_id">Localidad ID <span class="text-slate-500">(opcional)</span></label>
                        <input type="number" name="localidad_id" id="localidad_id" value="{{ old('localidad_id') }}"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100" />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300" for="central_nro">Nº central (opcional)</label>
                        <input type="number" name="central_nro" id="central_nro" value="{{ old('central_nro') }}"
                               class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-slate-100" />
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-sky-500">Crear</button>
                    </div>
                </div>

                <div class="lg:sticky lg:top-6">
                    <div class="text-sm font-medium text-slate-200">Vista de mapa</div>
                    <p class="mt-1 text-xs text-slate-500">Clic en el mapa, arrastrá el marcador o editá las coordenadas. Los tiles se cargan vía <code class="text-slate-400">/x/tiles/…</code> (mismo origen que el dashboard).</p>
                    <p id="admin-map-coords-hint" class="mt-2 hidden text-xs text-amber-200/90" role="status"></p>
                    <p class="mt-1 text-xs text-slate-500">En el Cono Sur, la <strong class="text-slate-300">latitud</strong> suele estar entre aprox. -22 y -55, y la <strong class="text-slate-300">longitud</strong> entre -35 y -75 (cada vez más oeste = más negativo).</p>
                    <div id="admin-objetivo-map" role="img" aria-label="Mapa de ubicación del objetivo"
                         class="relative z-0 mt-3 h-[min(70vh,520px)] min-h-[280px] w-full overflow-hidden rounded-xl border border-slate-700"
                         data-tile-template="{{ e($cartoTileTemplate) }}"
                         data-map-mode="form"></div>
                    <p class="mt-3 text-xs leading-relaxed text-slate-500">
                        Al confirmar, la <abbr title="aplicación en api">API</abbr> crea el <strong class="text-slate-400">estado</strong> de alta inicial
                        y la <strong class="text-slate-400">central</strong> automáticamente (número según lógica del servidor, salvo que indiques <em>Nº central</em> y sea válido).
                    </p>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        @vite(['resources/js/admin-objetivos.js'])
    @endpush
@endsection

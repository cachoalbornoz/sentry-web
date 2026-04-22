@extends('layouts.admin', ['user' => $user ?? session('api_user')])

@section('title', 'Detalle de objetivo')

@php
    $activeAdminNav = 'crud';
    $o = is_array($objetivo) ? $objetivo : [];
    $u = $o['ubicacion'] ?? null;
    $lat = is_array($u) ? ($u['latitud'] ?? null) : null;
    $lon = is_array($u) ? ($u['longitud'] ?? null) : null;
    $hasMapCoords = is_numeric($lat) && is_numeric($lon);
@endphp

@section('content')
    <div class="mx-auto w-full max-w-[1600px]">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('admin.objetivos.index') }}" class="text-sm text-sky-400 hover:text-sky-300">← Volver al listado</a>
                <h1 class="mt-2 text-2xl font-semibold text-white">Consulta de objetivo</h1>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.objetivos.edit', (int) ($o['id'] ?? 0)) }}"
                   class="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800">Editar</a>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-2 lg:items-start">
            <div>
                <dl class="grid max-w-2xl gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-800 bg-slate-900/40 p-4">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">ID</dt>
                        <dd class="mt-1 text-slate-100">{{ $o['id'] ?? '—' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-800 bg-slate-900/40 p-4">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Código</dt>
                        <dd class="mt-1 text-slate-100">{{ $o['codigo'] ?? '—' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-800 bg-slate-900/40 p-4 sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Nombre</dt>
                        <dd class="mt-1 text-slate-100">{{ $o['nombre'] ?? '—' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-800 bg-slate-900/40 p-4 sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Descripción</dt>
                        <dd class="mt-1 text-slate-100">{{ $o['descripcion'] ?? '—' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-800 bg-slate-900/40 p-4">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Cliente</dt>
                        <dd class="mt-1 text-slate-100">
                            @if (is_array($o['cliente'] ?? null))
                                {{ $o['cliente']['nombre'] ?? '—' }} <span class="text-slate-500">(id: {{ $o['cliente_id'] ?? '—' }})</span>
                            @else
                                <span class="text-slate-500">id: {{ $o['cliente_id'] ?? '—' }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-800 bg-slate-900/40 p-4">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Estado (último)</dt>
                        <dd class="mt-1 text-slate-100">{{ $o['estado'] ?? '—' }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-800 bg-slate-900/40 p-4 sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Jurisdicción</dt>
                        <dd class="mt-1 text-slate-100">
                            @if (is_array($o['jurisdiccion'] ?? null))
                                {{ $o['jurisdiccion']['nombre'] ?? '—' }} <span class="text-slate-500">(id: {{ $o['jurisdiccion_id'] ?? '—' }})</span>
                            @else
                                <span class="text-slate-500">id: {{ $o['jurisdiccion_id'] ?? '—' }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-800 bg-slate-900/40 p-4 sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Ubicación (coordenadas)</dt>
                        <dd class="mt-1 text-slate-100">
                            @if ($hasMapCoords)
                                <span class="font-mono text-sm">{{ $lat }}</span>, <span class="font-mono text-sm">{{ $lon }}</span>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="rounded-lg border border-slate-800 bg-slate-900/40 p-4 sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Dirección</dt>
                        <dd class="mt-1 text-slate-100">{{ $o['direccion'] ?? '—' }}</dd>
                    </div>
                </dl>

                <form action="{{ route('admin.objetivos.destroy', (int) ($o['id'] ?? 0)) }}" method="post" class="mt-10"
                      onsubmit="return confirm('¿Dar de baja lógicamente este objetivo?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg border border-rose-500/50 bg-rose-950/30 px-4 py-2 text-sm text-rose-200 hover:bg-rose-950/50">
                        Eliminar (baja lógica)
                    </button>
                </form>
            </div>

            <div class="lg:sticky lg:top-6" data-ubicacion-aviso>
                <div class="text-sm font-medium text-slate-200">Ubicación en el mapa</div>
                <p class="mt-1 text-xs text-slate-500">Sólo consulta. La posición se obtiene de la API (misma lógica que al editar).</p>
                @if ($hasMapCoords)
                    <div id="admin-objetivo-map" role="img" aria-label="Mapa de ubicación"
                         class="relative z-0 mt-3 h-[min(70vh,520px)] min-h-[280px] w-full overflow-hidden rounded-xl border border-slate-700"
                         data-tile-template="{{ e($cartoTileTemplate) }}"
                         data-map-mode="view"
                         data-initial-lat="{{ $lat }}"
                         data-initial-lng="{{ $lon }}"></div>
                @else
                    <p class="mt-3 rounded-lg border border-dashed border-slate-700 p-4 text-sm text-slate-500">No hay coordenadas de ubicación para mostrar en el mapa.</p>
                @endif
            </div>
        </div>
    </div>

    @if ($hasMapCoords)
        @push('scripts')
            @vite(['resources/js/admin-objetivos.js'])
        @endpush
    @endif
@endsection

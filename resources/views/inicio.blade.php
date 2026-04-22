@extends('layouts.app', ['activeNav' => 'inicio'])

@section('title', 'Inicio')

@section('content')
        {{-- Cards superiores --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="card rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium">Objetivos críticos</div>
                        <div class="text-xs text-slate-400">Requieren atención inmediata</div>
                    </div>
                    <div class="dashboard-state-icon icon-critico" aria-hidden="true">
                        <svg width="56" height="56" viewBox="0 0 64 64">
                            <polygon class="state-hex-fill" points="32,7 50,17 50,38 32,49 14,38 14,17"></polygon>
                            <path class="state-mark-contrast" d="M32 20v14"></path>
                            <circle cx="32" cy="40" r="3.2" fill="#f8fafc"></circle>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-semibold" id="count-critico">0</div>
            </div>
            <div class="card rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium">Objetivos conectados</div>
                        <div class="text-xs text-slate-400">Comunicación activa</div>
                    </div>
                    <div class="dashboard-state-icon icon-online" aria-hidden="true">
                        <svg width="56" height="56" viewBox="0 0 64 64">
                            <polygon class="state-hex" points="32,7 50,17 50,38 32,49 14,38 14,17"></polygon>
                            <circle class="state-badge-bg" cx="49" cy="46" r="8"></circle>
                            <path class="state-mark" d="M45 46l3 3 6-6"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-semibold" id="count-online">0</div>
            </div>
            <div class="card rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium">Objetivo inactivo</div>
                        <div class="text-xs text-slate-400">Sin comunicación</div>
                    </div>
                    <div class="dashboard-state-icon icon-offline" aria-hidden="true">
                        <svg width="56" height="56" viewBox="0 0 64 64">
                            <polygon class="state-hex" points="32,7 50,17 50,38 32,49 14,38 14,17"></polygon>
                            <circle class="state-badge-bg" cx="49" cy="46" r="8"></circle>
                            <path class="state-mark" d="M44 46h10"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-semibold" id="count-offline">0</div>
            </div>
            <div class="card rounded-xl p-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium">Objetivos sin señal</div>
                        <div class="text-xs text-slate-400">Desconectados</div>
                    </div>
                    <div class="dashboard-state-icon icon-muerto" aria-hidden="true">
                        <svg width="56" height="56" viewBox="0 0 64 64">
                            <polygon class="state-hex" points="32,7 50,17 50,38 32,49 14,38 14,17"></polygon>
                            <circle class="state-badge-bg" cx="49" cy="46" r="8"></circle>
                            <path class="state-mark" d="M45 42l8 8"></path>
                            <path class="state-mark" d="M53 42l-8 8"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-semibold" id="count-muerto">0</div>
            </div>
        </div>

        {{-- Grilla eventos + mapa --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-stretch">
            <section class="panel rounded-xl p-0 overflow-hidden lg:col-span-7">
                <div class="px-4 py-3 border-b border-slate-800 flex items-center justify-between">
                    <div>
                        <div class="font-medium">Eventos - <span id="eventos-total">0</span></div>
                        <div class="text-xs text-slate-400">Última actualización: <span id="eventos-updated">—</span></div>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <button id="toggle-group-eventos" type="button" class="inline-flex items-center gap-1 rounded-md border border-slate-700 bg-slate-900/60 px-2 py-1 text-slate-300 hover:bg-slate-800" title="Agrupar por tipo de señal">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M4 5h6v6H4zM14 5h6v4h-6zM14 13h6v6h-6zM4 15h6v4H4z"></path>
                            </svg>
                            <span id="group-eventos-label">Agrupar</span>
                        </button>
                    </div>
                </div>

                <div id="batchbar" class="hidden px-4 py-2 bg-blue-600/90 text-white text-sm items-center justify-between">
                    <div><span id="selected-count">0</span> evento(s) seleccionado(s)</div>
                    <div class="flex items-center gap-4">
                        <button id="open-cedular" class="cursor-pointer rounded-md border border-blue-200/50 bg-slate-900/35 px-3 py-1.5 font-medium text-white transition-colors duration-150 hover:bg-slate-900/55">
                            Cedular eventos
                        </button>
                        <button id="clear-selection" class="cursor-pointer rounded-md border border-blue-200/35 bg-slate-900/25 px-3 py-1.5 font-medium text-blue-100 transition-colors duration-150 hover:bg-slate-900/45 hover:text-white">
                            Cancelar
                        </button>
                    </div>
                </div>

                <div class="p-3">
                    <div class="mb-2">
                        <input id="eventos-search" type="text" placeholder="Buscar eventos"
                               class="w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-400/40"/>
                    </div>

                    <div id="eventos-scroll" class="rounded-lg border border-slate-800">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-800 text-slate-100 border-b border-slate-600/80">
                                <tr>
                                    <th class="p-2 w-10 text-left">
                                        <input id="select-all" type="checkbox" class="accent-blue-500"/>
                                    </th>
                                    <th class="p-2 text-left">#</th>
                                    <th class="p-2 text-left">
                                        <button type="button" class="sort-header-btn" data-sort-key="tipoSenal">
                                            Tipo de señal <span class="sort-indicator">↕</span>
                                        </button>
                                    </th>
                                    <th class="p-2 text-left">
                                        <button type="button" class="sort-header-btn" data-sort-key="fecha">
                                            Fecha y Hora <span class="sort-indicator">↕</span>
                                        </button>
                                    </th>
                                    <th class="p-2 text-left">
                                        <button type="button" class="sort-header-btn" data-sort-key="objetivo">
                                            Objetivo <span class="sort-indicator">↕</span>
                                        </button>
                                    </th>
                                    <th class="p-2 text-left">
                                        <button type="button" class="sort-header-btn" data-sort-key="zona">
                                            Zona <span class="sort-indicator">↕</span>
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="eventos-tbody" class="text-slate-100">
                                {{-- Render inicial (SSR) --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="panel rounded-xl overflow-hidden lg:col-span-5">
                <div class="px-4 py-3 border-b border-slate-800">
                    <div class="font-medium">Mapa</div>
                </div>
                <div class="p-3">
                    <div id="map" class="rounded-lg border border-slate-800 overflow-hidden"></div>
                    <div class="mt-2 text-xs text-slate-500">
                        Marcadores: <span id="map-count">0</span>
                    </div>
                </div>
            </section>
        </div>

    {{-- Modal cedulación --}}
    <div id="cedular-modal" class="hidden fixed inset-0 z-4000 p-4">
        <div class="absolute inset-0 z-4000 modal-backdrop"></div>
        <div id="cedular-panel" class="relative z-4010 mx-auto mt-8 w-full max-w-4xl h-[80vh] overflow-hidden bg-slate-900/95 border border-slate-700 rounded-xl shadow-2xl shadow-black flex flex-col">
            <div id="cedular-header" class="flex items-center justify-between px-4 py-3 border-b border-slate-800">
                <div>
                    <div class="text-sm text-slate-400" id="cedular-subtitle">Cedular evento</div>
                    <div class="text-xl font-semibold" id="cedular-title">—</div>
                    <div class="text-sm text-slate-200 mt-1" id="cedular-objetivo">—</div>
                    <div class="text-xs text-slate-400 mt-2">
                        Fecha y hora: <span id="cedular-fecha">—</span> · Zona: <span id="cedular-zona">—</span>
                    </div>
                </div>
                <button id="cedular-close" class="text-slate-300 hover:text-white text-xl leading-none">×</button>
            </div>

            <div id="cedular-multi-list" class="cedular-multi-list hidden px-4 pt-3">
                <div class="rounded-lg border border-slate-700 overflow-hidden">
                    <div class="cedular-multi-scroll">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-800 text-slate-100 border-b border-slate-600/80">
                                <tr>
                                    <th class="p-2 text-left">#</th>
                                    <th class="p-2 text-left">Tipo de Señal</th>
                                    <th class="p-2 text-left">Fecha y Hora</th>
                                    <th class="p-2 text-left">Objetivo</th>
                                    <th class="p-2 text-left">Zona</th>
                                </tr>
                            </thead>
                            <tbody id="cedular-multi-tbody" class="bg-slate-800/70"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3 overflow-y-auto flex-1 min-h-0">
                <div class="space-y-2.5">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-blue-600 p-3 text-center">
                            <div class="text-xl font-semibold">911</div>
                            <div class="text-sm">Policía</div>
                        </div>
                        <div class="cedular-emergency-card-danger rounded-lg p-3 text-center">
                            <div class="text-xl font-semibold">100</div>
                            <div class="text-sm">Bomberos</div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-800 chip p-2.5">
                        <div class="text-xs text-slate-400 mb-1">Contactos por objetivo</div>
                        <select id="contactos-objetivo" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                            <option value="">—</option>
                        </select>
                        <div id="contactos-empty" class="mt-3 text-slate-300">
                            <div class="text-lg font-medium">No hay contactos</div>
                            <div class="text-sm text-slate-400">No hay contactos cargados para este objetivo.</div>
                        </div>
                        <div id="contactos-list" class="hidden mt-3 space-y-2 text-sm"></div>
                    </div>
                </div>

                <div class="space-y-2.5">
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Tipo de señal</label>
                        <select id="cedulacion-tipo" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                            <option value="">Cargando…</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Observaciones predefinidas</label>
                        <select id="obs-predef" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm">
                            <option value="">Cargando…</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-400 mb-1">Observaciones</label>
                        <textarea id="obs-text" rows="3"
                                  class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-slate-400/40"
                                  placeholder="Observaciones del evento"></textarea>
                    </div>
                    <div id="cedular-msg" class="hidden text-sm rounded-lg border p-3"></div>
                </div>
            </div>

            <div class="px-4 py-3 border-t border-slate-800 flex items-center justify-between bg-slate-950/90">
                <button id="cedular-cancel" class="text-slate-300 hover:text-white underline underline-offset-4">Cancelar</button>
                <button id="cedular-save" class="rounded-lg bg-blue-600 px-4 py-2 font-semibold hover:bg-blue-500">
                    <span id="cedular-save-label">Guardar cedulación</span>
                    <span id="cedular-save-loading" class="hidden items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" opacity=".3"></circle>
                            <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" fill="none"></path>
                        </svg>
                        Ocupado...
                    </span>
                </button>
            </div>
        </div>
    </div>

    @php
        $inicioPageConfig = [
            'initialEventos' => $eventos,
            'initialObjetivos' => $objetivos,
            'stadiaKey' => $stadiaKey,
            'useStadiaBasemap' => $useStadiaBasemap,
            'contactosRouteTemplate' => route('x.objetivos.contactos', ['objetivo' => '__OBJETIVO__']),
            'objetivoDetalleRouteTemplate' => route('x.objetivos.detalle', ['objetivo' => '__OBJETIVO__']),
            'loginUrl' => route('login.form'),
            'cedulacionTiposUrl' => route('x.cedulacion.tipos'),
            'cedulacionObservacionesUrl' => route('x.cedulacion.observaciones'),
            'cedulacionGuardarUrl' => route('x.cedulacion.guardar'),
            'cartoTileTemplate' => $cartoTileTemplate,
            'stadiaTileTemplate' => $stadiaTileTemplate,
            'eventosUrl' => route('x.eventos'),
            'objetivosUrl' => route('x.objetivos'),
            'sseDashboardUrl' => route('x.sse.dashboard'),
            'hasObjetivoScope' => $hasObjetivoScope ?? false,
            'allowedObjetivoIds' => $allowedObjetivoIds ?? [],
            'csrfToken' => csrf_token(),
        ];
    @endphp
    <div id="inicio-page-config" hidden
         data-config='@json($inicioPageConfig)'></div>
@endsection

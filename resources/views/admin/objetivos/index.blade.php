@extends('layouts.admin', ['user' => $user ?? session('api_user')])

@section('title', 'Objetivos (admin)')

@php
    $activeAdminNav = 'crud';
@endphp

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <a href="{{ route('admin.crud') }}" class="text-sm text-sky-400 hover:text-sky-300">← Volver a ABM</a>
            <h1 class="mt-2 text-2xl font-semibold text-white">Objetivos</h1>
        </div>
        <a href="{{ route('admin.objetivos.create') }}"
           class="inline-flex items-center rounded-lg bg-sky-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-sky-500">Agregar objetivo</a>
    </div>

    @if (!empty($loadError))
        <p class="mt-6 text-amber-200/90">{{ $loadError }}</p>
    @else
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="w-full min-w-0 sm:max-w-md">
                <label for="admin-objetivos-search" class="mb-1.5 block text-sm font-medium text-slate-200">Buscar</label>
                <input type="search" id="admin-objetivos-search" name="q" enterkeyhint="search"
                       placeholder="Código, descripción, estado, cliente, jurisdicción…"
                       class="w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2.5 text-sm text-slate-100 shadow-sm placeholder:text-slate-500 focus:border-sky-500/50 focus:outline-none focus:ring-1 focus:ring-sky-500/30"
                       autocomplete="off" />
                <p class="mt-1.5 text-xs leading-snug text-slate-500 md:hidden">
                    En pantalla chica, <span class="text-slate-300">jurisdicción</span> (y cliente/estado) no se listan: usá este cuadro para filtrarlas igual.
                </p>
            </div>
            <div class="shrink-0 text-xs text-slate-500 sm:max-w-xs sm:pt-7">
                <p class="hidden md:block">Hacé clic en el encabezado de una columna para ordenar (segundo clic invierte el orden).</p>
                <p class="md:hidden">Vista resumida: <span class="text-slate-300">Acciones</span> en cada fila.</p>
            </div>
        </div>

        <script type="application/json" id="admin-objetivos-data">@json($objetivos)</script>
        <div id="admin-objetivos-list-root"
             class="mt-4 overflow-x-auto rounded-xl border border-slate-800"
             data-base-url="{{ url('/admin/objetivos') }}"
             data-csrf="{{ csrf_token() }}">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-800 bg-slate-900/80 text-slate-300">
                        <th scope="col" class="px-3 py-3">
                            <button type="button" data-sort-key="codigo" class="inline-flex w-full items-center text-left font-medium text-slate-200 hover:text-white">
                                Código<span class="sort-ind text-slate-500" aria-hidden="true"></span>
                            </button>
                        </th>
                        <th scope="col" class="px-3 py-3">
                            <button type="button" data-sort-key="descripcion" class="inline-flex w-full min-w-0 items-center text-left font-medium text-slate-200 hover:text-white">
                                <span class="truncate">Descripción</span><span class="sort-ind shrink-0 text-slate-500" aria-hidden="true"></span>
                            </button>
                        </th>
                        <th scope="col" class="hidden px-3 py-3 md:table-cell">
                            <button type="button" data-sort-key="estado" class="inline-flex w-full items-center text-left font-medium text-slate-200 hover:text-white">
                                Estado actual<span class="sort-ind text-slate-500" aria-hidden="true"></span>
                            </button>
                        </th>
                        <th scope="col" class="hidden px-3 py-3 md:table-cell">
                            <button type="button" data-sort-key="cliente" class="inline-flex w-full items-center text-left font-medium text-slate-200 hover:text-white">
                                Cliente<span class="sort-ind text-slate-500" aria-hidden="true"></span>
                            </button>
                        </th>
                        <th scope="col" class="hidden px-3 py-3 md:table-cell">
                            <button type="button" data-sort-key="jurisdiccion" class="inline-flex w-full items-center text-left font-medium text-slate-200 hover:text-white">
                                Jurisdicción<span class="sort-ind text-slate-500" aria-hidden="true"></span>
                            </button>
                        </th>
                        <th scope="col" class="px-2 py-3 pr-3 text-right font-medium text-slate-300 md:px-3">Acciones</th>
                    </tr>
                </thead>
                <tbody id="admin-objetivos-tbody"></tbody>
            </table>
        </div>

        @push('scripts')
            @vite(['resources/js/admin-objetivos.js'])
        @endpush
    @endif
@endsection

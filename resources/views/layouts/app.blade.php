<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sentry Guard')</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
@php
    $apiUser = session('api_user');
    $scopeKeys = ['objetivos_alcanzables_id', 'objetivos_accessibles_id', 'objetivosAlcanzablesId', 'objetivosAccesiblesId'];
    $scopeRaw = null;
    $hasObjetivoScope = false;
    if (is_array($apiUser)) {
        foreach ($scopeKeys as $key) {
            if (array_key_exists($key, $apiUser)) {
                $scopeRaw = $apiUser[$key];
                $hasObjetivoScope = true;
                break;
            }
        }
    }
    $allowedObjetivoIds = collect(is_array($scopeRaw) ? $scopeRaw : [])
        ->map(fn ($id) => is_numeric($id) ? (int) $id : 0)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->values()
        ->all();
@endphp
<body class="min-h-screen bg-slate-950 text-slate-100 p-4 md:p-6"
      data-api-status-url="{{ route('x.objetivos') }}"
      data-login-url="{{ route('login.form') }}"
      data-objetivos-url="{{ route('x.objetivos') }}"
      data-eventos-url="{{ route('x.eventos') }}"
      data-dashboard-url="{{ route('dashboard') }}"
      data-has-objetivos-scope="{{ $hasObjetivoScope ? '1' : '0' }}"
      data-allowed-objetivos-ids='@json($allowedObjetivoIds)'>
    <div class="w-full space-y-4">
        @include('layouts.navbar', ['activeNav' => $activeNav ?? ''])
        @include('layouts.profile-sidebar')

        <div class="grid grid-cols-1 gap-3">
            <aside class="hidden" id="layout-left-panel">
                @yield('leftPanel')
            </aside>

            <main id="layout-center-panel">
                @yield('content')
            </main>
        </div>

    </div>

    @include('components.critical-alert-stack', ['id' => 'global-critical-alerts-stack'])

    <div id="critical-sound-unlock" class="critical-sound-unlock hidden fixed bottom-4 right-4 w-full max-w-sm rounded-xl border border-amber-500/35 bg-slate-950/95 p-4 shadow-2xl shadow-black/40">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-amber-200">Sonido crítico bloqueado</div>
                <div id="critical-sound-unlock-text" class="mt-1 text-sm text-slate-200">
                    El navegador requiere una interacción para habilitar el audio crítico.
                </div>
                <div id="critical-sound-unlock-status" class="mt-2 text-xs text-slate-400"></div>
            </div>
            <button id="critical-sound-dismiss-btn" type="button" class="text-slate-400 hover:text-white" aria-label="Ocultar aviso">×</button>
        </div>
        <div class="mt-4 flex justify-end">
            <button id="critical-sound-unlock-btn" type="button" class="inline-flex items-center rounded-md border border-amber-400/40 bg-amber-500/10 px-3 py-2 text-sm font-medium text-amber-100 hover:bg-amber-500/15">
                Activar sonido
            </button>
        </div>
    </div>

    @stack('scripts')
</body>
</html>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administración') — Sentry Guard</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <header class="border-b border-slate-800 bg-[#0d0f14] px-4 py-3">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.home') }}" class="inline-flex items-center" aria-label="Panel administración">
                    <img src="{{ asset('isotipo-grises.svg') }}" alt="" class="h-8 w-auto object-contain opacity-90">
                </a>
                <div class="text-sm text-slate-300">
                    <span class="font-medium text-slate-100">Administración</span>
                    @if (is_array($user ?? null) && !empty($user['nombre'] ?? $user['email'] ?? null))
                        <span class="text-slate-500"> — </span>
                        <span class="text-slate-400">{{ $user['nombre'] ?? $user['email'] }}</span>
                    @endif
                </div>
            </div>
            <nav class="flex flex-wrap items-center gap-2 text-sm">
                <a href="{{ route('admin.home') }}"
                   class="rounded-md px-3 py-1.5 {{ ($activeAdminNav ?? '') === 'home' ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">Panel</a>
                <a href="{{ route('admin.crud') }}"
                   class="rounded-md px-3 py-1.5 {{ ($activeAdminNav ?? '') === 'crud' ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">ABM / CRUD</a>
                <a href="{{ route('admin.settings') }}"
                   class="rounded-md px-3 py-1.5 {{ ($activeAdminNav ?? '') === 'settings' ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">Settings</a>
                <form method="post" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="rounded-md border border-slate-600 px-3 py-1.5 text-slate-200 hover:border-slate-500 hover:text-white">Salir</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-100">
                {{ session('status') }}
            </div>
        @endif
        @if (session('message'))
            <div class="mb-6 rounded-lg border border-slate-600/50 bg-slate-900/60 px-4 py-3 text-sm text-slate-200">
                {{ session('message') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-500/35 bg-red-950/30 px-4 py-3 text-sm text-red-100">
                {{ $errors->first() }}
            </div>
        @endif
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
